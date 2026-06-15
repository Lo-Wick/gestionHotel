<?php
/**
 * API - Authentification (login, register, logout, session check)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Client.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        handleCheck();
        break;
    case 'profile':
        handleProfile();
        break;
    case 'updateProfile':
        handleUpdateProfile();
        break;
    case 'changePassword':
        handleChangePassword();
        break;
    case 'forgot':
        handleForgotPassword();
        break;
    default:
        jsonResponse(['error' => 'Action non reconnue'], 400);
}

function handleLogin(): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $email    = sanitize($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(['error' => 'Email et mot de passe requis'], 400);
    }
    if (!isValidEmail($email)) {
        jsonResponse(['error' => 'Format d\'email invalide'], 400);
    }

    $client = new Client();
    $user = $client->verifyPassword($email, $password);

    if (!$user) {
        jsonResponse(['error' => 'Email ou mot de passe incorrect'], 401);
    }

    loginUser($user);

    jsonResponse([
        'success' => true,
        'message' => 'Connexion réussie',
        'user' => [
            'id'     => $user['id_client'],
            'nom'    => $user['nom'],
            'prenom' => $user['prenom'],
            'email'  => $user['email'],
            'role'   => $user['role']
        ]
    ]);
}

function handleRegister(): void {
    $input = json_decode(file_get_contents('php://input'), true);

    $nom       = sanitize($input['nom'] ?? '');
    $prenom    = sanitize($input['prenom'] ?? '');
    $email     = sanitize($input['email'] ?? '');
    $telephone = sanitize($input['telephone'] ?? '');
    $password  = $input['password'] ?? '';
    $confirm   = $input['password_confirm'] ?? '';

    // Validations
    $errors = [];
    if (empty($nom))       $errors[] = 'Le nom est requis';
    if (empty($prenom))    $errors[] = 'Le prénom est requis';
    if (empty($email))     $errors[] = 'L\'email est requis';
    if (!isValidEmail($email)) $errors[] = 'Format d\'email invalide';
    if (empty($telephone)) $errors[] = 'Le téléphone est requis';
    if (!isValidPhone($telephone)) $errors[] = 'Format de téléphone invalide';
    if (empty($password))  $errors[] = 'Le mot de passe est requis';
    if (!isValidPassword($password)) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial';
    }
    if ($password !== $confirm) $errors[] = 'Les mots de passe ne correspondent pas';

    if (!empty($errors)) {
        jsonResponse(['error' => implode('. ', $errors), 'errors' => $errors], 400);
    }

    $client = new Client();

    // Vérifier email unique
    if ($client->findByEmail($email)) {
        jsonResponse(['error' => 'Cet email est déjà utilisé'], 409);
    }

    $id = $client->create([
        'nom'      => $nom,
        'prenom'   => $prenom,
        'email'    => $email,
        'telephone'=> $telephone,
        'password' => $password
    ]);

    // Auto-login après inscription
    $user = $client->getById($id);
    $user['id_client'] = $user['id_client'] ?? $id;
    $user['role'] = 'client';
    loginUser($user);

    jsonResponse([
        'success' => true,
        'message' => 'Inscription réussie ! Bienvenue.',
        'user'    => [
            'id'     => $id,
            'nom'    => $nom,
            'prenom' => $prenom,
            'email'  => $email,
            'role'   => 'client'
        ]
    ], 201);
}

function handleLogout(): void {
    logoutUser();
    jsonResponse(['success' => true, 'message' => 'Déconnexion réussie']);
}

function handleCheck(): void {
    $user = getCurrentUser();
    if ($user) {
        jsonResponse(['logged_in' => true, 'user' => $user]);
    } else {
        jsonResponse(['logged_in' => false]);
    }
}

function handleProfile(): void {
    requireAuth();
    $client = new Client();
    $user = $client->getById(getCurrentUserId());
    if (!$user) {
        jsonResponse(['error' => 'Utilisateur introuvable'], 404);
    }
    jsonResponse(['success' => true, 'user' => $user]);
}

function handleUpdateProfile(): void {
    requireAuth();
    $input = json_decode(file_get_contents('php://input'), true);

    $data = [];
    if (!empty($input['nom']))       $data['nom'] = sanitize($input['nom']);
    if (!empty($input['prenom']))    $data['prenom'] = sanitize($input['prenom']);
    if (!empty($input['email'])) {
        if (!isValidEmail($input['email'])) {
            jsonResponse(['error' => 'Format d\'email invalide'], 400);
        }
        $data['email'] = sanitize($input['email']);
    }
    if (!empty($input['telephone'])) {
        if (!isValidPhone($input['telephone'])) {
            jsonResponse(['error' => 'Format de téléphone invalide'], 400);
        }
        $data['telephone'] = sanitize($input['telephone']);
    }

    $client = new Client();
    try {
        $client->updateProfile(getCurrentUserId(), $data);
        // Update session
        if (isset($data['nom']))    $_SESSION['user_nom'] = $data['nom'];
        if (isset($data['prenom'])) $_SESSION['user_prenom'] = $data['prenom'];
        if (isset($data['email']))  $_SESSION['user_email'] = $data['email'];

        jsonResponse(['success' => true, 'message' => 'Profil mis à jour avec succès']);
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 400);
    }
}

function handleChangePassword(): void {
    requireAuth();
    $input = json_decode(file_get_contents('php://input'), true);

    $currentPassword = $input['current_password'] ?? '';
    $newPassword     = $input['new_password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        jsonResponse(['error' => 'Tous les champs sont requis'], 400);
    }
    if ($newPassword !== $confirmPassword) {
        jsonResponse(['error' => 'Les mots de passe ne correspondent pas'], 400);
    }
    if (!isValidPassword($newPassword)) {
        jsonResponse(['error' => 'Le nouveau mot de passe ne respecte pas les critères de sécurité'], 400);
    }

    $client = new Client();
    $user = $client->verifyPassword($_SESSION['user_email'], $currentPassword);
    if (!$user) {
        jsonResponse(['error' => 'Mot de passe actuel incorrect'], 401);
    }

    $client->updateProfile(getCurrentUserId(), ['password' => $newPassword]);
    jsonResponse(['success' => true, 'message' => 'Mot de passe modifié avec succès']);
}

function handleForgotPassword(): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = sanitize($input['email'] ?? '');

    if (empty($email)) {
        jsonResponse(['error' => 'Veuillez saisir votre adresse email'], 400);
    }
    if (!isValidEmail($email)) {
        jsonResponse(['error' => 'Format d\'email invalide'], 400);
    }

    $client = new Client();
    $user = $client->findByEmail($email);

    // Réponse identique que l'email existe ou non (protection contre l'énumération)
    $successMessage = 'Si cet email est enregistré, un nouveau mot de passe vous a été envoyé par email.';

    if ($user) {
        $newPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$'), 0, 12);
        $client->updateProfile((int)$user['id_client'], ['password' => $newPassword]);
        // En production : envoi par email. Simulation locale uniquement.
    }

    jsonResponse(['success' => true, 'message' => $successMessage]);
}
