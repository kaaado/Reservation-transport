<?php
// Disable output buffering
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}
ini_set('zlib.output_compression', '0');
ini_set('output_buffering', '0');
ini_set('implicit_flush', '1');

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store, must-revalidate'); // Extra caching prevention
header('Pragma: no-cache');
header('Expires: 0');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Essential for Nginx/some Reverse Proxies

require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once CONF_PATH . 'database.php';

$database = new Database();
$pdo = $database->getConnection();
$user_id = $_SESSION['user_id'];

session_write_close();

set_time_limit(0);
ignore_user_abort(true);

function sendNotifUpdate($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'unread'");
    $stmt->execute([$user_id]);
    $count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "data: " . json_encode(['unread' => (int)$count, 'notifications' => $notifs]) . "\n\n";
    while (ob_get_level() > 0) ob_end_flush();
    flush();
}

// Initial push
sendNotifUpdate($pdo, $user_id);

$lastCount = null;
$counter = 0;

while (true) {
    if (connection_aborted()) break;

    // Send a heartbeat every 20 seconds to keep connection alive if no data
    if ($counter >= 20) {
        echo ": heartbeat\n\n";
        while (ob_get_level() > 0) ob_end_flush();
        flush();
        $counter = 0;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'unread'");
    $stmt->execute([$user_id]);
    $currentCount = $stmt->fetchColumn();

    if ($currentCount !== $lastCount) {
        sendNotifUpdate($pdo, $user_id);
        $lastCount = $currentCount;
    }

    $counter++;
    sleep(2);
}
