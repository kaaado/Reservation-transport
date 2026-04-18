<?php
require_once __DIR__ . '/../includes/session_manager.php';
safe_session_start();

/**
 * Register a new user
 */
function registerUser($name, $email, $password, $phone, $role, $pdo) {
    // 1. Check Uniqueness First
    $check = $pdo->prepare("SELECT email, phone FROM users WHERE email = ? OR phone = ?");
    $check->execute([$email, $phone]);
    $existing = $check->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($existing as $row) {
        if ($row['email'] === $email || $row['phone'] === $phone) return "Un compte avec cet e-mail ou ce numéro de téléphone existe déjà.";
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, phone, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW())");
    
    try {
        $stmt->execute([$name, $email, $hash, $phone, $role]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return "Erreur BDD : " . $e->getMessage();
    }
}

/**
 * Log in an existing user with optional session persistence
 */
function loginUser($email, $password, $pdo, $remember = false) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if (!$user) {
        return "Identifiants invalides. L'adresse e-mail ou le mot de passe est incorrect.";
    }

    if (!password_verify($password, $user['password_hash'])) {
        return "Identifiants invalides. L'adresse e-mail ou le mot de passe est incorrect.";
    }

    if ($user['status'] === 'suspended') {
        return "Votre compte a été suspendu par l'administration.";
    }

    if ($user['status'] === 'pending') {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['status'] = 'pending';
        return "pending_redirect";
    }

    // Session Hardening
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['has_accepted_contract'] = $user['has_accepted_contract'] ?? 0;
    $_SESSION['last_login'] = $user['last_login'] ?? date('Y-m-d H:i:s');

    // Record last login
    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$user['id']]);

    // Remember Me Implementation
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $updateToken = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $updateToken->execute([$token, $user['id']]);
        
        // Store user ID and token in a cookie (valid for 30 days)
        $cookieData = $user['id'] . ":" . $token;
        setcookie('remember_me', $cookieData, time() + (86400 * 30), "/", "", false, true);
    }

    return true;
}

/**
 * Check if a persistent session (Remember Me) exists
 */
function checkRememberMe($pdo) {
    if (isset($_COOKIE['remember_me']) && !isset($_SESSION['user_id'])) {
        $parts = explode(":", $_COOKIE['remember_me']);
        if (count($parts) === 2) {
            $user_id = $parts[0];
            $token = $parts[1];
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND remember_token = ? AND status = 'active'");
            $stmt->execute([$user_id, $token]);
            $user = $stmt->fetch();
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['has_accepted_contract'] = $user['has_accepted_contract'] ?? 0;
                return true;
            }
        }
    }
    return false;
}

