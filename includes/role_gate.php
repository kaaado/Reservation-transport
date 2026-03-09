<?php
require_once "auth_check.php"; // Ensure base auth is loaded

function enforceRole($required_role) {
    if (!isset($_SESSION['role'])) {
        header("Location: " . URL_ROOT . "auth/login.php");
        exit();
    }
    
    // Master Admin Override: Admin can access everything
    if ($_SESSION['role'] === 'admin') {
        return;
    }

    if ($_SESSION['role'] !== $required_role) {
        // Log unauthorized attempt visually mapping it backward
        header("Location: " . URL_ROOT . "system/unauthorized.php");
        exit();
    }
}

// Global Validation Function
function validateCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("CSRF Token Verification Failed.");
    }
}

// Generate Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
