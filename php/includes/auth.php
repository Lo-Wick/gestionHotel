<?php
/**
 * Middleware d'authentification
 */

require_once __DIR__ . '/functions.php';
startSecureSession();

// Vérifier si l'utilisateur est connecté
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Vérifier si l'utilisateur est admin
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Récupérer l'ID utilisateur connecté
function getCurrentUserId(): ?int {
    return isLoggedIn() ? (int) $_SESSION['user_id'] : null;
}

// Récupérer les infos utilisateur
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'     => $_SESSION['user_id'],
        'nom'    => $_SESSION['user_nom'] ?? '',
        'prenom' => $_SESSION['user_prenom'] ?? '',
        'email'  => $_SESSION['user_email'] ?? '',
        'role'   => $_SESSION['user_role'] ?? 'client'
    ];
}

// Exiger une connexion client
function requireAuth(): void {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Authentification requise'], 401);
    }
}

// Exiger une connexion admin
function requireAdmin(): void {
    if (!isAdmin()) {
        jsonResponse(['error' => 'Accès réservé aux administrateurs'], 403);
    }
}

// Connecter un utilisateur (stocker en session)
function loginUser(array $user): void {
    $_SESSION['user_id']     = $user['id_client'];
    $_SESSION['user_nom']    = $user['nom'];
    $_SESSION['user_prenom'] = $user['prenom'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_role']   = $user['role'];
    session_regenerate_id(true);
}

// Déconnecter
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
