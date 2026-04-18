<?php
/**
 * Admin Functions — Audit Logging Module
 * Logs all admin actions for accountability and traceability.
 */

function logAdminAction($admin_id, $action, $target_type, $target_id, $details, $pdo) {
    $stmt = $pdo->prepare("
        INSERT INTO admin_audit_logs (admin_id, action, target_type, target_id, details, ip_address, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return $stmt->execute([$admin_id, $action, $target_type, $target_id, $details, $ip]);
}

function getAuditLogs($pdo, $limit = 50) {
    $stmt = $pdo->prepare("
        SELECT al.*, u.name as admin_name 
        FROM admin_audit_logs al
        JOIN users u ON al.admin_id = u.id
        ORDER BY al.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Create the audit logs table if it doesn't exist.
 * Should be called once during setup/migration.
 */
function ensureAuditLogTable($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            target_type VARCHAR(50) NOT NULL,
            target_id INT DEFAULT NULL,
            details TEXT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_action (action),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
