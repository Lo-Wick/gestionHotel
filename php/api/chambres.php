<?php
/**
 * API - Chambres (liste, détail, disponibilité)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Chambre.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        handleList();
        break;
    case 'detail':
        handleDetail();
        break;
    case 'disponibilite':
        handleDisponibilite();
        break;
    case 'types':
        handleTypes();
        break;
    default:
        jsonResponse(['error' => 'Action non reconnue'], 400);
}

function handleList(): void {
    $chambre = new Chambre();
    $filters = [
        'type'         => $_GET['type'] ?? null,
        'prix_min'     => $_GET['prix_min'] ?? null,
        'prix_max'     => $_GET['prix_max'] ?? null,
        'capacite'     => $_GET['capacite'] ?? null,
        'date_debut'   => $_GET['date_debut'] ?? null,
        'date_fin'     => $_GET['date_fin'] ?? null,
        'disponibilite'=> isset($_GET['disponibilite']) ? (int)$_GET['disponibilite'] : null,
        'tri'          => $_GET['tri'] ?? 'numero',
        'page'         => $_GET['page'] ?? 1,
        'limit'        => $_GET['limit'] ?? 8
    ];
    // Remove null values
    $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');
    
    $result = $chambre->getAll($filters);
    jsonResponse(['success' => true, 'data' => $result]);
}

function handleDetail(): void {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['error' => 'ID de chambre invalide'], 400);
    }

    $chambre = new Chambre();
    $data = $chambre->getById($id);

    if (!$data) {
        jsonResponse(['error' => 'Chambre introuvable'], 404);
    }

    jsonResponse(['success' => true, 'chambre' => $data]);
}

function handleDisponibilite(): void {
    $idChambre  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $dateDebut  = $_GET['date_debut'] ?? $_POST['date_debut'] ?? '';
    $dateFin    = $_GET['date_fin'] ?? $_POST['date_fin'] ?? '';

    if ($idChambre <= 0 || empty($dateDebut) || empty($dateFin)) {
        jsonResponse(['error' => 'Paramètres manquants (id, date_debut, date_fin)'], 400);
    }

    $chambre = new Chambre();
    $room = $chambre->getById($idChambre);
    if (!$room) {
        jsonResponse(['error' => 'Chambre introuvable'], 404);
    }

    $disponible = $chambre->isDisponible($idChambre, $dateDebut, $dateFin);
    $nuits = calculerNuits($dateDebut, $dateFin);
    $montantTotal = $room['prix_nuit'] * $nuits;

    jsonResponse([
        'success'      => true,
        'disponible'   => $disponible,
        'chambre'      => $room,
        'nuits'        => $nuits,
        'montant_total'=> $montantTotal
    ]);
}

function handleTypes(): void {
    jsonResponse([
        'success' => true,
        'types'   => ['Standard', 'Confort', 'Suite', 'Présidentielle']
    ]);
}
