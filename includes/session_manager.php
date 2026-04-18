<?php
// Function to safely start a session with a custom, secure path
function safe_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        // Define a local session path within the project to bypass XAMPP permission issues
        $session_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'sessions';
        
        // Ensure the directory exists
        if (!is_dir($session_path)) {
            mkdir($session_path, 0777, true);
        }
        
        // Secure session configuration
        session_save_path($session_path);
        
        // Security headers for cookies
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        session_start();
    }
}
