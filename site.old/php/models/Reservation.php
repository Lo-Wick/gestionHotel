<?php
/**
 * Model Reservation
 */

require_once __DIR__ . '/../config/database.php';

class Reservation {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Créer une réservation
    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO reservation (id_client, id_chambre, date_debut, date_fin, nombre_adultes, nombre_enfants, montant_total, remarques) 
             VALUES (:id_client, :id_chambre, :date_debut, :date_fin, :nombre_adultes, :nombre_enfants, :montant_total, :remarques)"
        );
        $stmt->execute([
            ':id_client'       => $data['id_client'],
            ':id_chambre'      => $data['id_chambre'],
            ':date_debut'      => $data['date_debut'],
            ':date_fin'        => $data['date_fin'],
            ':nombre_adultes'  => $data['nombre_adultes'] ?? 1,
            ':nombre_enfants'  => $data['nombre_enfants'] ?? 0,
            ':montant_total'   => $data['montant_total'],
            ':remarques'       => $data['remarques'] ?? ''
        ]);
        return (int)$this->db->lastInsertId();
    }

    // Récupérer par ID avec détails
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT r.*, c.nom, c.prenom, c.email, c.telephone,
                    ch.numero, ch.type, ch.prix_nuit, ch.image_url
             FROM reservation r
             JOIN client c ON r.id_client = c.id_client
             JOIN chambre ch ON r.id_chambre = ch.id_chambre
             WHERE r.id_reservation = :id"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // Réservations d'un client
    public function getByClient(int $idClient, int $page = 1, int $limit = 10): array {
        $offset = ($page - 1) * $limit;

        $stmt = $this->db->prepare(
            "SELECT r.*, ch.numero, ch.type, ch.prix_nuit, ch.image_url
             FROM reservation r
             JOIN chambre ch ON r.id_chambre = ch.id_chambre
             WHERE r.id_client = :id_client
             ORDER BY r.date_reservation DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':id_client', $idClient, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $reservations = $stmt->fetchAll();

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM reservation WHERE id_client = :id_client");
        $countStmt->execute([':id_client' => $idClient]);
        $total = (int)$countStmt->fetchColumn();

        return [
            'reservations' => $reservations,
            'total'        => $total,
            'page'         => $page,
            'total_pages'  => ceil($total / $limit)
        ];
    }

    // Toutes les réservations (admin)
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array {
        $sql = "SELECT r.*, c.nom, c.prenom, c.email, ch.numero, ch.type, ch.prix_nuit
                FROM reservation r
                JOIN client c ON r.id_client = c.id_client
                JOIN chambre ch ON r.id_chambre = ch.id_chambre
                WHERE 1=1";
        $params = [];

        if (!empty($filters['statut'])) {
            $sql .= " AND r.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }
        if (!empty($filters['date_debut'])) {
            $sql .= " AND r.date_debut >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }
        if (!empty($filters['date_fin'])) {
            $sql .= " AND r.date_fin <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (c.nom LIKE :search OR c.prenom LIKE :search OR c.email LIKE :search OR ch.numero LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Count
        $countSql = str_replace(
            "SELECT r.*, c.nom, c.prenom, c.email, ch.numero, ch.type, ch.prix_nuit",
            "SELECT COUNT(*)",
            $sql
        );
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql .= " ORDER BY r.date_reservation DESC LIMIT :limit OFFSET :offset";
        $offset = ($page - 1) * $limit;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'reservations' => $stmt->fetchAll(),
            'total'        => $total,
            'page'         => $page,
            'total_pages'  => ceil($total / $limit)
        ];
    }

    // Modifier le statut
    public function updateStatut(int $id, string $statut): bool {
        $stmt = $this->db->prepare("UPDATE reservation SET statut = :statut WHERE id_reservation = :id");
        return $stmt->execute([':statut' => $statut, ':id' => $id]);
    }

    // Annuler (client - vérification 48h avant)
    public function annuler(int $id, int $idClient): array {
        $reservation = $this->getById($id);
        if (!$reservation) {
            return ['success' => false, 'message' => 'Réservation introuvable'];
        }
        if ($reservation['id_client'] != $idClient) {
            return ['success' => false, 'message' => 'Accès non autorisé'];
        }
        if ($reservation['statut'] === 'Annulée' || $reservation['statut'] === 'Terminée') {
            return ['success' => false, 'message' => 'Cette réservation ne peut pas être annulée'];
        }
        
        $dateDebut = new DateTime($reservation['date_debut']);
        $now = new DateTime();
        $diff = $now->diff($dateDebut);
        if ($dateDebut < $now || ($diff->days < 2 && !$diff->invert)) {
            return ['success' => false, 'message' => 'Annulation impossible : moins de 48h avant le début du séjour'];
        }

        $this->updateStatut($id, 'Annulée');
        return ['success' => true, 'message' => 'Réservation annulée avec succès'];
    }

    // Statistiques (admin)
    public function getStats(): array {
        $db = $this->db;

        $total = (int)$db->query("SELECT COUNT(*) FROM reservation")->fetchColumn();
        $confirmees = (int)$db->query("SELECT COUNT(*) FROM reservation WHERE statut = 'Confirmée'")->fetchColumn();
        $enAttente = (int)$db->query("SELECT COUNT(*) FROM reservation WHERE statut = 'En attente'")->fetchColumn();
        $annulees = (int)$db->query("SELECT COUNT(*) FROM reservation WHERE statut = 'Annulée'")->fetchColumn();

        $ca = $db->query(
            "SELECT COALESCE(SUM(montant_total), 0) FROM reservation WHERE statut IN ('Confirmée', 'Terminée')"
        )->fetchColumn();

        $caMensuel = $db->query(
            "SELECT COALESCE(SUM(montant_total), 0) FROM reservation 
             WHERE statut IN ('Confirmée', 'Terminée') 
             AND MONTH(date_reservation) = MONTH(CURRENT_DATE())
             AND YEAR(date_reservation) = YEAR(CURRENT_DATE())"
        )->fetchColumn();

        // Réservations par mois (12 derniers mois)
        $parMois = $db->query(
            "SELECT DATE_FORMAT(date_reservation, '%Y-%m') as mois, COUNT(*) as count, 
                    SUM(montant_total) as revenus
             FROM reservation 
             WHERE date_reservation >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             AND statut NOT IN ('Annulée')
             GROUP BY DATE_FORMAT(date_reservation, '%Y-%m')
             ORDER BY mois ASC"
        )->fetchAll();

        // Taux d'occupation
        $totalChambres = (int)$db->query("SELECT COUNT(*) FROM chambre")->fetchColumn();
        $chambresOccupees = (int)$db->query(
            "SELECT COUNT(DISTINCT id_chambre) FROM reservation 
             WHERE statut IN ('Confirmée', 'En attente') 
             AND date_debut <= CURDATE() AND date_fin > CURDATE()"
        )->fetchColumn();
        $tauxOccupation = $totalChambres > 0 ? round(($chambresOccupees / $totalChambres) * 100, 1) : 0;

        return [
            'total'           => $total,
            'confirmees'      => $confirmees,
            'en_attente'      => $enAttente,
            'annulees'        => $annulees,
            'chiffre_affaires'=> (float)$ca,
            'ca_mensuel'      => (float)$caMensuel,
            'par_mois'        => $parMois,
            'taux_occupation' => $tauxOccupation,
            'chambres_total'  => $totalChambres,
            'chambres_occupees'=> $chambresOccupees
        ];
    }

    // Export data for CSV
    public function getAllForExport(): array {
        return $this->db->query(
            "SELECT r.id_reservation, c.nom, c.prenom, c.email, ch.numero as chambre, ch.type,
                    r.date_debut, r.date_fin, r.nombre_adultes, r.nombre_enfants,
                    r.statut, r.montant_total, r.date_reservation, r.remarques
             FROM reservation r
             JOIN client c ON r.id_client = c.id_client
             JOIN chambre ch ON r.id_chambre = ch.id_chambre
             ORDER BY r.date_reservation DESC"
        )->fetchAll();
    }
}
