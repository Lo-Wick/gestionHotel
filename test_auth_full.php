<?php
require_once 'php/models/Client.php';
$logFile = 'debug_auth_log.txt';

function logMsg($msg) {
    global $logFile;
    file_put_contents($logFile, $msg . "\n", FILE_APPEND);
}

file_put_contents($logFile, "Auth Debug Started\n");

try {
    $client = new Client();
    $email = 'admin@hotel.com';
    $password = 'Admin123!';
    
    logMsg("Checking for user: $email");
    $user = $client->findByEmail($email);
    
    if ($user) {
        logMsg("User found in database.");
        logMsg("Stored Hash: " . $user['password']);
        
        if (password_verify($password, $user['password'])) {
            logMsg("PASSWORD VERIFIED SUCCESS");
        } else {
            logMsg("PASSWORD VERIFIED FAILURE");
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            logMsg("Suggested new hash: $newHash");
        }
    } else {
        logMsg("User NOT found in database.");
        // List all users to see what we have
        $stmt = Database::getInstance()->query("SELECT email, role FROM client");
        $users = $stmt->fetchAll();
        logMsg("Users in DB: " . json_encode($users));
    }
} catch (Exception $e) {
    logMsg("ERROR: " . $e->getMessage());
}
