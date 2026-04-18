<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once CONF_PATH . 'database.php';

$database = new Database();
$pdo = $database->getConnection();

$action = $_GET['action'] ?? null;
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

if ($action === 'check') {
    // Return unread count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'unread'");
    $stmt->execute([$user_id]);
    echo json_encode(['unread' => $stmt->fetchColumn()]);
    exit();
}

if ($action === 'fetch') {
    // Cursor/Pagination Logic
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 3;
    $offset = ($page - 1) * $limit;
    
    $stmt = $pdo->prepare("SELECT id, message, status, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['notifications' => $notifications]);
    exit();
}

if ($action === 'mark_read') {
    // CSRF Check since state is changing
    require_once INC_PATH . 'role_gate.php';
    if(!isset($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Missing CSRF']);
        exit();
    }
    
    // For JS API requests, validate CSRF directly
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF']);
        exit();
    }

    $notif_id = $_POST['id'] ?? 'all';
    
    if ($notif_id === 'all') {
        $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ? AND id = ?");
        $stmt->execute([$user_id, (int)$notif_id]);
    }
    
    echo json_encode(['success' => true]);
    exit();
}

if ($action === 'delete') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF']);
        exit();
    }

    $notif_id = $_POST['id'] ?? null;
    if ($notif_id) {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND id = ?");
        $stmt->execute([$user_id, (int)$notif_id]);
    }
    echo json_encode(['success' => true]);
    exit();
}

if ($action === 'delete_all') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF']);
        exit();
    }
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true]);
    exit();
}

http_response_code(400);
echo json_encode(['error' => 'Invalid Action']);
