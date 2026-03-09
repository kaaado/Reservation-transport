<?php
require_once __DIR__ . '/core/paths.php';
require_once CONF_PATH . 'database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Clear database token if user was remembered
if (isset($_SESSION['user_id'])) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        $updateToken = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $updateToken->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        // Silent fail for logout
    }
}

// 2. Clear "Remember Me" Cookie
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, "/");
}

// 3. Clear Session Cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destroy Session
$_SESSION = array();
session_unset();
session_destroy();

header("Location: " . URL_ROOT . "auth/login.php");
exit();
