<?php
/**
 * API - Réservations (créer, lister, annuler, paiement)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/Chambre.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        handleCreate();
        break;
    case 'list':
        handleList();
        break;
    case 'detail':
        handleDetail();
        break;
    case 'annuler':
        handleAnnuler();
        break;
    case 'payer':
        handlePayer();
        break;
    default:
        jsonResponse(['error' => 'Action non reconnue'], 400);
}

function handleCreate(): void {
    requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $idChambre      = (int)($input['id_chambre'] ?? 0);
    $dateDebut      = sanitize($input['date_debut'] ?? '');
    $dateFin        = sanitize($input['date_fin'] ?? '');
    $heureArrivee   = sanitize($input['heure_arrivee'] ?? '15:00:00');
    $heureDepart    = sanitize($input['heure_depart'] ?? '11:00:00');
    $nombreAdultes  = (int)($input['nombre_adultes'] ?? 1);
    $nombreEnfants  = (int)($input['nombre_enfants'] ?? 0);
    $remarques      = sanitize($input['remarques'] ?? '');

    // Validations
    $errors = [];
    if ($idChambre <= 0)  $errors[] = 'Chambre invalide';
    if (empty($dateDebut)) $errors[] = 'Date de début requise';
    if (empty($dateFin))   $errors[] = 'Date de fin requise';
    if ($dateDebut >= $dateFin) $errors[] = 'La date de fin doit être après la date de début';
    if (strtotime($dateDebut) < strtotime('today')) $errors[] = 'La date de début ne peut pas être dans le passé';
    if ($nombreAdultes < 1) $errors[] = 'Au moins 1 adulte requis';

    // Heures valides (simple regex Check)
    if (!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/', $heureArrivee)) {
        $heureArrivee = '15:00:00';
    }
    if (!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/', $heureDepart)) {
        $heureDepart = '11:00:00';
    }

    if (!empty($errors)) {
        jsonResponse(['error' => implode('. ', $errors), 'errors' => $errors], 400);
    }

    // Vérifier disponibilité
    $chambreModel = new Chambre();
    $chambre = $chambreModel->getById($idChambre);
    if (!$chambre) {
        jsonResponse(['error' => 'Chambre introuvable'], 404);
    }
    
    // Vérifier capacité
    if (($nombreAdultes + $nombreEnfants) > $chambre['capacite']) {
        jsonResponse(['error' => 'Le nombre de personnes dépasse la capacité de la chambre (' . $chambre['capacite'] . ' personnes max)'], 400);
    }

    if (!$chambreModel->isDisponible($idChambre, $dateDebut, $dateFin, $heureArrivee, $heureDepart)) {
        jsonResponse(['error' => 'Cette chambre n\'est pas disponible pour les dates et horaires sélectionnés'], 409);
    }

    // Calcul du montant
    $nuits = calculerNuits($dateDebut, $dateFin);
    $montantTotal = $chambre['prix_nuit'] * $nuits;

    // Créer la réservation
    $reservationModel = new Reservation();
    $idReservation = $reservationModel->create([
        'id_client'      => getCurrentUserId(),
        'id_chambre'     => $idChambre,
        'date_debut'     => $dateDebut,
        'date_fin'       => $dateFin,
        'heure_arrivee'  => $heureArrivee,
        'heure_depart'   => $heureDepart,
        'nombre_adultes' => $nombreAdultes,
        'nombre_enfants' => $nombreEnfants,
        'montant_total'  => $montantTotal,
        'remarques'      => $remarques
    ]);

    jsonResponse([
        'success'  => true,
        'message'  => 'Réservation créée avec succès !',
        'reservation' => [
            'id'            => $idReservation,
            'chambre'       => $chambre['numero'],
            'type'          => $chambre['type'],
            'date_debut'    => $dateDebut,
            'date_fin'      => $dateFin,
            'heure_arrivee' => $heureArrivee,
            'heure_depart'  => $heureDepart,
            'nuits'         => $nuits,
            'montant_total' => $montantTotal
        ]
    ], 201);
}

function handleList(): void {
    requireAuth();
    
    $page = max(1, (int)($_GET['page'] ?? 1));
    $reservationModel = new Reservation();
    $result = $reservationModel->getByClient(getCurrentUserId(), $page);

    jsonResponse(['success' => true, 'data' => $result]);
}

function handleDetail(): void {
    requireAuth();
    
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['error' => 'ID de réservation invalide'], 400);
    }

    $reservationModel = new Reservation();
    $reservation = $reservationModel->getById($id);

    if (!$reservation) {
        jsonResponse(['error' => 'Réservation introuvable'], 404);
    }

    // Vérifier que c'est le client ou un admin
    if ($reservation['id_client'] != getCurrentUserId() && !isAdmin()) {
        jsonResponse(['error' => 'Accès non autorisé'], 403);
    }

    jsonResponse(['success' => true, 'reservation' => $reservation]);
}

function handleAnnuler(): void {
    requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        jsonResponse(['error' => 'ID de réservation invalide'], 400);
    }

    $reservationModel = new Reservation();
    $result = $reservationModel->annuler($id, getCurrentUserId());

    if ($result['success']) {
        jsonResponse($result);
    } else {
        jsonResponse($result, 400);
    }
}

function handlePayer(): void {
    requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $idReservation = (int)($input['id_reservation'] ?? 0);
    $modePaiement  = sanitize($input['mode_paiement'] ?? '');

    if ($idReservation <= 0) {
        jsonResponse(['error' => 'ID de réservation invalide'], 400);
    }

    $modesValides = ['Carte', 'Espèces', 'Virement', 'Mobile Money'];
    if (!in_array($modePaiement, $modesValides)) {
        jsonResponse(['error' => 'Mode de paiement invalide'], 400);
    }

    $reservationModel = new Reservation();
    $reservation = $reservationModel->getById($idReservation);

    if (!$reservation) {
        jsonResponse(['error' => 'Réservation introuvable'], 404);
    }
    if ($reservation['id_client'] != getCurrentUserId() && !isAdmin()) {
        jsonResponse(['error' => 'Accès non autorisé'], 403);
    }

    if ($reservation['statut'] === 'Confirmée') {
        jsonResponse(['error' => 'Cette réservation est déjà confirmée et payée'], 409);
    }
    if ($reservation['statut'] === 'Annulée') {
        jsonResponse(['error' => 'Impossible de payer une réservation annulée'], 400);
    }

    $db = Database::getInstance();

    $checkPay = $db->prepare("SELECT COUNT(*) FROM paiement WHERE id_reservation = :id");
    $checkPay->execute([':id' => $idReservation]);
    if ((int)$checkPay->fetchColumn() > 0) {
        jsonResponse(['error' => 'Un paiement existe déjà pour cette réservation'], 409);
    }

    // Simuler le paiement
    $stmt = $db->prepare(
        "INSERT INTO paiement (id_reservation, montant, mode_paiement, statut) VALUES (:id, :montant, :mode, 'Payé')"
    );
    $stmt->execute([
        ':id'      => $idReservation,
        ':montant' => $reservation['montant_total'],
        ':mode'    => $modePaiement
    ]);

    // Mettre à jour le statut de la réservation
    $reservationModel->updateStatut($idReservation, 'Confirmée');

    jsonResponse([
        'success' => true,
        'message' => 'Paiement effectué avec succès ! Votre réservation est confirmée.',
        'paiement' => [
            'id'       => (int)$db->lastInsertId(),
            'montant'  => $reservation['montant_total'],
            'mode'     => $modePaiement,
            'statut'   => 'Payé'
        ]
    ]);
}
