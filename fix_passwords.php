<?php
/**
 * Script de correction des mots de passe de test (à exécuter une fois)
 * Accès : http://localhost/gestionHotel/fix_passwords.php
 */
require_once __DIR__ . '/php/config/database.php';

$updates = [
    'admin@hotel.com'  => 'Admin123!',
    'client@test.com'  => 'Client123!',
];

$db = Database::getInstance();
$stmt = $db->prepare("UPDATE client SET password = :pwd WHERE email = :email");

$results = [];
foreach ($updates as $email => $plain) {
    $stmt->execute([
        ':pwd'   => password_hash($plain, PASSWORD_DEFAULT),
        ':email' => $email
    ]);
    $results[] = "$email → mis à jour (" . ($stmt->rowCount() ? 'OK' : 'non trouvé') . ")";
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'message' => 'Mots de passe corrigés', 'details' => $results], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
