<?php
/**
 * Admin Functions — Commission & Billing Module
 */

function getUnpaidCommissions($pdo) {
    $stmt = $pdo->query("
        SELECT u.id as transporter_id, u.name, u.email, u.phone,
            COUNT(r.id) as unpaid_count,
            SUM(r.platform_commission) as total_owed
        FROM reservations r
        JOIN vehicles v ON r.vehicle_id = v.id
        JOIN users u ON v.owner_id = u.id
        WHERE r.status = 'completed' AND r.is_commission_paid = 0
        GROUP BY u.id, u.name, u.email, u.phone
        ORDER BY unpaid_count DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTransporterUnpaidReservations($transporter_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT r.id, r.pickup_location, r.destination, r.price, r.platform_commission, r.created_at 
        FROM reservations r
        JOIN vehicles v ON r.vehicle_id = v.id
        WHERE v.owner_id = ? AND r.status = 'completed' AND r.is_commission_paid = 0
        ORDER BY r.created_at ASC
    ");
    $stmt->execute([$transporter_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function toggleBatchPayment($transporter_id, $is_paid, $pdo) {
    $stmt = $pdo->prepare("
        UPDATE reservations r
        JOIN vehicles v ON r.vehicle_id = v.id
        SET r.is_commission_paid = ?
        WHERE v.owner_id = ? AND r.status = 'completed'
    ");
    return $stmt->execute([$is_paid ? 1 : 0, $transporter_id]);
}

function updateReservationCommission($reservation_id, $is_paid, $pdo) {
    $stmt = $pdo->prepare("UPDATE reservations SET is_commission_paid = ? WHERE id = ?");
    return $stmt->execute([$is_paid ? 1 : 0, $reservation_id]);
}

function markBatchPaid($transporter_id, $pdo) {
    return toggleBatchPayment($transporter_id, true, $pdo);
}

function checkTransporterBlock($transporter_id, $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN vehicles v ON r.vehicle_id = v.id WHERE v.owner_id = ? AND r.status = 'completed' AND r.is_commission_paid = 0");
    $stmt->execute([$transporter_id]);
    $count = $stmt->fetchColumn();
    if ($count >= 5) {
        // NOTIFICATION: Notify Admin
        require_once FUNC_PATH . 'admin/notifications.php';
        notifyAdmin("Le transporteur #$transporter_id a atteint le seuil critique de $count commissions impayées.", $pdo);
        return true;
    }
    return false;
}
