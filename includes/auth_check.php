<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Force HTTPS and strict proxy headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 2. Session Timeout Implementation (1 hour)
$timeout_duration = 3600; // 1 hour in seconds
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: " . URL_ROOT . "auth/login.php?timeout=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time(); // Update last activity

if (!isset($_SESSION['user_id'])) {
    header("Location: " . URL_ROOT . "auth/login.php");
    exit();
}

// 3. Database Status Middleware Validation Engine
require_once CONF_PATH . "database.php";
if (!isset($pdo_check)) {
    $db_check = new Database();
    $pdo_check = $db_check->getConnection();
}

try {
    $stmt_check_status = $pdo_check->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
    $stmt_check_status->execute([$_SESSION['user_id']]);
    $current_status = $stmt_check_status->fetchColumn();

    if ($current_status !== 'active') {
        if ($current_status === 'pending') {
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page !== 'pending.php' && $current_page !== 'logout.php') {
                header("Location: " . URL_ROOT . "system/pending.php");
                exit();
            }
        } else {
            session_unset();
            session_destroy();
            header("Location: " . URL_ROOT . "auth/login.php?error=inactive");
            exit();
        }
    }
} catch (Exception $e) {
    // Failsafe execution policy
    session_unset();
    session_destroy();
    header("Location: " . URL_ROOT . "auth/login.php");
    exit();
}

// Global XSS Prevention Function
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
?>