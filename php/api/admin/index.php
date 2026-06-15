<?php
/**
 * API Admin - Gestion complète (stats, chambres CRUD, réservations, clients, paiements)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/Chambre.php';
require_once __DIR__ . '/../../models/Reservation.php';
require_once __DIR__ . '/../../models/Client.php';

// Vérifier les droits admin
requireAdmin();

$resource = $_GET['resource'] ?? '';
$action   = $_GET['action'] ?? '';

switch ($resource) {
    case 'stats':
        handleStats();
        break;
    case 'chambres':
        handleChambres($action);
        break;
    case 'reservations':
        handleReservations($action);
        break;
    case 'clients':
        handleClients($action);
        break;
    case 'paiements':
        handlePaiements($action);
        break;
    default:
        jsonResponse(['error' => 'Ressource non reconnue'], 400);
}

// =============== STATS ===============
function handleStats(): void {
    $reservationModel = new Reservation();
    $chambreModel     = new Chambre();
    $clientModel      = new Client();

    $stats = [
        'reservations' => $reservationModel->getStats(),
        'chambres'     => $chambreModel->getStats(),
        'clients'      => $clientModel->getStats()
    ];

    jsonResponse(['success' => true, 'stats' => $stats]);
}

// =============== CHAMBRES ===============
function handleChambres(string $action): void {
    $chambreModel = new Chambre();

    switch ($action) {
        case 'list':
            $filters = [
                'type'     => $_GET['type'] ?? null,
                'numero'   => $_GET['numero'] ?? null,
                'page'     => $_GET['page'] ?? 1,
                'limit'    => $_GET['limit'] ?? 20,
                'tri'      => $_GET['tri'] ?? 'numero'
            ];
            $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');
            $result = $chambreModel->getAll($filters);
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);
            $errors = [];
            if (empty($input['numero']))   $errors[] = 'Numéro de chambre requis';
            if (empty($input['type']))     $errors[] = 'Type de chambre requis';
            if (empty($input['prix_nuit'])) $errors[] = 'Prix par nuit requis';
            if (empty($input['capacite'])) $errors[] = 'Capacité requise';
            if (!empty($errors)) {
                jsonResponse(['error' => implode('. ', $errors)], 400);
            }
            try {
                $id = $chambreModel->create($input);
                jsonResponse(['success' => true, 'message' => 'Chambre créée avec succès', 'id' => $id], 201);
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    jsonResponse(['error' => 'Ce numéro de chambre existe déjà'], 409);
                }
                jsonResponse(['error' => 'Erreur lors de la création'], 500);
            }
            break;

        case 'update':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) jsonResponse(['error' => 'ID invalide'], 400);
            try {
                $chambreModel->update($id, $input);
                jsonResponse(['success' => true, 'message' => 'Chambre mise à jour']);
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    jsonResponse(['error' => 'Ce numéro de chambre existe déjà'], 409);
                }
                jsonResponse(['error' => 'Erreur lors de la mise à jour'], 500);
            }
            break;

        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) jsonResponse(['error' => 'ID invalide'], 400);
            $chambreModel->delete($id);
            jsonResponse(['success' => true, 'message' => 'Chambre supprimée']);
            break;

        default:
            jsonResponse(['error' => 'Action non reconnue'], 400);
    }
}

// =============== RESERVATIONS ===============
function handleReservations(string $action): void {
    $reservationModel = new Reservation();

    switch ($action) {
        case 'list':
            $filters = [
                'statut'     => $_GET['statut'] ?? null,
                'date_debut' => $_GET['date_debut'] ?? null,
                'date_fin'   => $_GET['date_fin'] ?? null,
                'search'     => $_GET['search'] ?? null
            ];
            $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $result = $reservationModel->getAll($filters, $page);
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'detail':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) jsonResponse(['error' => 'ID invalide'], 400);
            $reservation = $reservationModel->getById($id);
            if (!$reservation) jsonResponse(['error' => 'Réservation introuvable'], 404);
            jsonResponse(['success' => true, 'reservation' => $reservation]);
            break;

        case 'updateStatut':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            $statut = sanitize($input['statut'] ?? '');
            $statutsValides = ['En attente', 'Confirmée', 'Annulée', 'Terminée'];
            if ($id <= 0 || !in_array($statut, $statutsValides)) {
                jsonResponse(['error' => 'Paramètres invalides'], 400);
            }
            $reservationModel->updateStatut($id, $statut);
            jsonResponse(['success' => true, 'message' => 'Statut mis à jour']);
            break;

        case 'export':
            $data = $reservationModel->getAllForExport();
            exportCSV($data, 'reservations_export_' . date('Y-m-d') . '.csv');
            break;

        default:
            jsonResponse(['error' => 'Action non reconnue'], 400);
    }
}

// =============== CLIENTS ===============
function handleClients(string $action): void {
    $clientModel = new Client();

    switch ($action) {
        case 'list':
            $page = max(1, (int)($_GET['page'] ?? 1));
            $search = $_GET['search'] ?? null;
            $result = $clientModel->getAll($page, 10, $search);
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'detail':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) jsonResponse(['error' => 'ID invalide'], 400);
            $client = $clientModel->getById($id);
            if (!$client) jsonResponse(['error' => 'Client introuvable'], 404);
            
            // Charger aussi les réservations du client
            $reservationModel = new Reservation();
            $reservations = $reservationModel->getByClient($id);
            
            jsonResponse(['success' => true, 'client' => $client, 'reservations' => $reservations]);
            break;

        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) jsonResponse(['error' => 'ID invalide'], 400);
            $clientModel->delete($id);
            jsonResponse(['success' => true, 'message' => 'Client supprimé']);
            break;

        default:
            jsonResponse(['error' => 'Action non reconnue'], 400);
    }
}

// =============== PAIEMENTS ===============
function handlePaiements(string $action): void {
    $db = Database::getInstance();

    switch ($action) {
        case 'list':
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;

            $stmt = $db->prepare(
                "SELECT p.*, r.date_debut, r.date_fin, c.nom, c.prenom, c.email, ch.numero as chambre
                 FROM paiement p
                 JOIN reservation r ON p.id_reservation = r.id_reservation
                 JOIN client c ON r.id_client = c.id_client
                 JOIN chambre ch ON r.id_chambre = ch.id_chambre
                 ORDER BY p.date_paiement DESC
                 LIMIT :limit OFFSET :offset"
            );
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $paiements = $stmt->fetchAll();

            $total = (int)$db->query("SELECT COUNT(*) FROM paiement")->fetchColumn();

            jsonResponse([
                'success' => true,
                'data' => [
                    'paiements'   => $paiements,
                    'total'       => $total,
                    'page'        => $page,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            break;

        case 'export':
            $data = $db->query(
                "SELECT p.id_paiement, c.nom, c.prenom, c.email, ch.numero as chambre,
                        r.date_debut, r.date_fin, p.montant, p.mode_paiement, p.statut, p.date_paiement
                 FROM paiement p
                 JOIN reservation r ON p.id_reservation = r.id_reservation
                 JOIN client c ON r.id_client = c.id_client
                 JOIN chambre ch ON r.id_chambre = ch.id_chambre
                 ORDER BY p.date_paiement DESC"
            )->fetchAll();
            exportCSV($data, 'paiements_export_' . date('Y-m-d') . '.csv');
            break;

        default:
            jsonResponse(['error' => 'Action non reconnue'], 400);
    }
}
