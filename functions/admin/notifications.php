<?php
/**
 * Admin Functions — Notifications Module
 */

function sendAdminNotification($user_id, $message, $pdo) {
    if (!$user_id) return false;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, status) VALUES (?, ?, 'unread')");
    return $stmt->execute([$user_id, $message]);
}

function notifyAdmin($message, $pdo) {
    // Find all admin IDs
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($admins as $admin_id) {
        sendAdminNotification($admin_id, $message, $pdo);
    }
    return true;
}
