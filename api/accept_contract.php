<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once CONF_PATH . 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }

    $db = new Database();
    $pdo = $db->getConnection();
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("UPDATE users SET has_accepted_contract = 1, contract_signed_at = NOW() WHERE id = ?");
    if ($stmt->execute([$user_id])) {
        $_SESSION['has_accepted_contract'] = 1;
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database update failed']);
    }
    exit;
}
