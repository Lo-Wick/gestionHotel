<?php
/**
 * Fonctions utilitaires
 */

// Démarrage sécurisé de session
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false, // true en production HTTPS
            'httponly'  => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

// Génération token CSRF
function generateCsrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérification token CSRF
function verifyCsrfToken(string $token): bool {
    startSecureSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitization des entrées
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Réponse JSON
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Formater le prix
function formatPrix(float $montant): string {
    return number_format($montant, 2, ',', ' ') . ' €';
}

// Formater la date
function formatDate(string $date): string {
    return date('d/m/Y', strtotime($date));
}

// Formater date et heure
function formatDateTime(string $datetime): string {
    return date('d/m/Y à H:i', strtotime($datetime));
}

// Calculer nombre de nuits
function calculerNuits(string $dateDebut, string $dateFin): int {
    $d1 = new DateTime($dateDebut);
    $d2 = new DateTime($dateFin);
    return (int) $d1->diff($d2)->days;
}

// Valider email
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Valider téléphone
function isValidPhone(string $phone): bool {
    return preg_match('/^[\+]?[0-9\s\-\(\)]{8,20}$/', $phone) === 1;
}

// Valider mot de passe (min 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre, 1 spécial)
function isValidPassword(string $password): bool {
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password);
}

// Export CSV
function exportCSV(array $data, string $filename): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]), ';');
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }
    }
    fclose($output);
    exit;
}
