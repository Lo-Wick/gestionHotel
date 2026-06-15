<?php
/**
 * Model Chambre - Gestion des chambres d'hôtel
 */

require_once __DIR__ . '/../config/database.php';

class Chambre {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Récupérer toutes les chambres avec filtres optionnels
    public function getAll(array $filters = []): array {
        $sql = "SELECT * FROM chambre WHERE 1=1";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['numero'])) {
            $sql .= " AND numero LIKE :numero";
            $params[':numero'] = '%' . $filters['numero'] . '%';
        }
        if (!empty($filters['prix_min'])) {
            $sql .= " AND prix_nuit >= :prix_min";
            $params[':prix_min'] = $filters['prix_min'];
        }
        if (!empty($filters['prix_max'])) {
            $sql .= " AND prix_nuit <= :prix_max";
            $params[':prix_max'] = $filters['prix_max'];
        }
        if (!empty($filters['capacite'])) {
            $sql .= " AND capacite >= :capacite";
            $params[':capacite'] = $filters['capacite'];
        }
        if (isset($filters['disponibilite'])) {
            $sql .= " AND disponibilite = :disponibilite";
            $params[':disponibilite'] = $filters['disponibilite'];
        }

        // Vérifier disponibilité sur dates
        if (!empty($filters['date_debut']) && !empty($filters['date_fin'])) {
            $sql .= " AND id_chambre NOT IN (
                SELECT id_chambre FROM reservation 
                WHERE statut NOT IN ('Annulée', 'Terminée')
                AND CONCAT(date_debut, ' ', heure_arrivee) < :end_datetime_check 
                AND CONCAT(date_fin, ' ', heure_depart) > :start_datetime_check
            )";
            $params[':start_datetime_check'] = $filters['date_debut'] . ' 15:00:00';
            $params[':end_datetime_check'] = $filters['date_fin'] . ' 11:00:00';
        }

        // Tri
        $orderBy = " ORDER BY ";
        switch ($filters['tri'] ?? 'numero') {
            case 'prix_asc':  $orderBy .= "prix_nuit ASC"; break;
            case 'prix_desc': $orderBy .= "prix_nuit DESC"; break;
            case 'capacite':  $orderBy .= "capacite DESC"; break;
            case 'type':      $orderBy .= "type ASC"; break;
            default:          $orderBy .= "numero ASC"; break;
        }
        $sql .= $orderBy;

        // Pagination
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = min(20, max(1, (int)($filters['limit'] ?? 8)));
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $chambres = $stmt->fetchAll();

        // Compter le total
        $countSql = "SELECT COUNT(*) FROM chambre WHERE 1=1";
        $countParams = [];
        if (!empty($filters['type'])) {
            $countSql .= " AND type = :type";
            $countParams[':type'] = $filters['type'];
        }
        if (!empty($filters['prix_min'])) {
            $countSql .= " AND prix_nuit >= :prix_min";
            $countParams[':prix_min'] = $filters['prix_min'];
        }
        if (!empty($filters['prix_max'])) {
            $countSql .= " AND prix_nuit <= :prix_max";
            $countParams[':prix_max'] = $filters['prix_max'];
        }
        if (!empty($filters['capacite'])) {
            $countSql .= " AND capacite >= :capacite";
            $countParams[':capacite'] = $filters['capacite'];
        }
        if (isset($filters['disponibilite'])) {
            $countSql .= " AND disponibilite = :disponibilite";
            $countParams[':disponibilite'] = $filters['disponibilite'];
        }
        if (!empty($filters['date_debut']) && !empty($filters['date_fin'])) {
            $countSql .= " AND id_chambre NOT IN (
                SELECT id_chambre FROM reservation 
                WHERE statut NOT IN ('Annulée', 'Terminée')
                AND CONCAT(date_debut, ' ', heure_arrivee) < :end_datetime_check 
                AND CONCAT(date_fin, ' ', heure_depart) > :start_datetime_check
            )";
            $countParams[':start_datetime_check'] = $filters['date_debut'] . ' 15:00:00';
            $countParams[':end_datetime_check'] = $filters['date_fin'] . ' 11:00:00';
        }
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($countParams);
        $total = (int)$countStmt->fetchColumn();

        return [
            'chambres'    => $chambres,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    // Récupérer une chambre par ID
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM chambre WHERE id_chambre = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // Créer une chambre
    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO chambre (numero, type, prix_nuit, capacite, description, disponibilite, image_url) 
             VALUES (:numero, :type, :prix_nuit, :capacite, :description, :disponibilite, :image_url)"
        );
        $stmt->execute([
            ':numero'        => $data['numero'],
            ':type'          => $data['type'],
            ':prix_nuit'     => $data['prix_nuit'],
            ':capacite'      => $data['capacite'],
            ':description'   => $data['description'] ?? '',
            ':disponibilite' => $data['disponibilite'] ?? 1,
            ':image_url'     => $data['image_url'] ?? ''
        ]);
        return (int) $this->db->lastInsertId();
    }

    // Mettre à jour
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];
        
        $allowed = ['numero', 'type', 'prix_nuit', 'capacite', 'description', 'disponibilite', 'image_url'];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) return false;

        $sql = "UPDATE chambre SET " . implode(', ', $fields) . " WHERE id_chambre = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // Supprimer
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM chambre WHERE id_chambre = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Vérifier disponibilité d'une chambre sur une période (avec heures pour éviter les conflits)
    public function isDisponible(int $idChambre, string $dateDebut, string $dateFin, string $heureArrivee = '15:00:00', string $heureDepart = '11:00:00', ?int $excludeReservation = null): bool {
        $sql = "SELECT COUNT(*) FROM reservation 
                WHERE id_chambre = :id_chambre 
                AND statut NOT IN ('Annulée', 'Terminée')
                AND CONCAT(date_debut, ' ', heure_arrivee) < :end_datetime
                AND CONCAT(date_fin, ' ', heure_depart) > :start_datetime";
        $params = [
            ':id_chambre'     => $idChambre,
            ':start_datetime' => "$dateDebut $heureArrivee",
            ':end_datetime'   => "$dateFin $heureDepart"
        ];
        if ($excludeReservation) {
            $sql .= " AND id_reservation != :exclude";
            $params[':exclude'] = $excludeReservation;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() === 0;
    }

    // Statistiques
    public function getStats(): array {
        $total = $this->db->query("SELECT COUNT(*) FROM chambre")->fetchColumn();
        $disponibles = $this->db->query("SELECT COUNT(*) FROM chambre WHERE disponibilite = 1")->fetchColumn();
        
        $parType = $this->db->query(
            "SELECT type, COUNT(*) as count, AVG(prix_nuit) as prix_moyen 
             FROM chambre GROUP BY type ORDER BY type"
        )->fetchAll();

        return [
            'total'       => (int)$total,
            'disponibles' => (int)$disponibles,
            'occupees'    => (int)$total - (int)$disponibles,
            'par_type'    => $parType
        ];
    }
}
