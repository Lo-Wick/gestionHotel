<?php
/**
 * Model Client - Gestion des clients
 */

require_once __DIR__ . '/../config/database.php';

class Client {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Trouver par email
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM client WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // Trouver par ID
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT id_client, nom, prenom, email, telephone, role, date_inscription 
             FROM client WHERE id_client = :id"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // Créer un client
    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO client (nom, prenom, email, telephone, password, role) 
             VALUES (:nom, :prenom, :email, :telephone, :password, :role)"
        );
        $stmt->execute([
            ':nom'      => $data['nom'],
            ':prenom'   => $data['prenom'],
            ':email'    => $data['email'],
            ':telephone'=> $data['telephone'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':role'     => $data['role'] ?? 'client'
        ]);
        return (int)$this->db->lastInsertId();
    }

    // Mettre à jour le profil
    public function updateProfile(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];

        if (!empty($data['nom'])) {
            $fields[] = "nom = :nom";
            $params[':nom'] = $data['nom'];
        }
        if (!empty($data['prenom'])) {
            $fields[] = "prenom = :prenom";
            $params[':prenom'] = $data['prenom'];
        }
        if (!empty($data['email'])) {
            // Vérifier que le mail n'est pas pris par un autre
            $check = $this->db->prepare("SELECT id_client FROM client WHERE email = :email AND id_client != :check_id");
            $check->execute([':email' => $data['email'], ':check_id' => $id]);
            if ($check->fetch()) {
                throw new Exception('Cet email est déjà utilisé par un autre compte');
            }
            $fields[] = "email = :email";
            $params[':email'] = $data['email'];
        }
        if (!empty($data['telephone'])) {
            $fields[] = "telephone = :telephone";
            $params[':telephone'] = $data['telephone'];
        }
        if (!empty($data['password'])) {
            $fields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) return false;

        $sql = "UPDATE client SET " . implode(', ', $fields) . " WHERE id_client = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // Lister tous les clients (admin)
    public function getAll(int $page = 1, int $limit = 10, ?string $search = null): array {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT id_client, nom, prenom, email, telephone, role, date_inscription FROM client WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (nom LIKE :search OR prenom LIKE :search OR email LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY date_inscription DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $clients = $stmt->fetchAll();

        $countSql = "SELECT COUNT(*) FROM client WHERE 1=1";
        if ($search) {
            $countSql .= " AND (nom LIKE :search OR prenom LIKE :search OR email LIKE :search)";
        }
        $countStmt = $this->db->prepare($countSql);
        if ($search) {
            $countStmt->bindValue(':search', '%' . $search . '%');
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        return [
            'clients'     => $clients,
            'total'       => $total,
            'page'        => $page,
            'total_pages' => ceil($total / $limit)
        ];
    }

    // Supprimer un client
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM client WHERE id_client = :id AND role != 'admin'");
        return $stmt->execute([':id' => $id]);
    }

    // Vérifier le mot de passe
    public function verifyPassword(string $email, string $password): ?array {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }

    // Stats clients
    public function getStats(): array {
        $total = (int)$this->db->query("SELECT COUNT(*) FROM client WHERE role = 'client'")->fetchColumn();
        $recent = (int)$this->db->query(
            "SELECT COUNT(*) FROM client WHERE role = 'client' AND date_inscription >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )->fetchColumn();
        return ['total' => $total, 'nouveaux_30j' => $recent];
    }
}
