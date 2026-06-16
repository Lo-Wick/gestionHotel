<?php
require_once 'php/config/database.php';
try {
    $db = Database::getInstance();
    echo "SUCCESS: Connection established!\n";
    $query = $db->query("SELECT COUNT(*) as count FROM chambre");
    $row = $query->fetch();
    echo "Rooms in database: " . $row['count'] . "\n";
} catch (Exception $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
}
