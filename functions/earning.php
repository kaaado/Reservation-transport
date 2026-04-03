<?php

function calculateEarnings($transporter_id, $pdo) {
    // Total earnings from completed jobs for the current month
    $stmt = $pdo->prepare("SELECT SUM(amount) as total 
                           FROM earnings 
                           WHERE transporter_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stmt->execute([$transporter_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

function calculateTotalEarnings($transporter_id, $pdo) {
    // All-time earnings
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM earnings WHERE transporter_id = ?");
    $stmt->execute([$transporter_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

function getEarningsHistory($transporter_id, $pdo) {
    // Get list of earnings with reservation details
    $stmt = $pdo->prepare("SELECT e.*, r.pickup_location, r.destination, r.reservation_date 
                           FROM earnings e
                           JOIN reservations r ON e.reservation_id = r.id
                           WHERE e.transporter_id = ?
                           ORDER BY e.created_at DESC");
    $stmt->execute([$transporter_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
