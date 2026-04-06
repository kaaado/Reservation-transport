<?php
require_once __DIR__ . '/constants.php';

function getClientReservations($clientId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT r.*, v.vehicle_type, u.name as transporter_name 
        FROM reservations r
        LEFT JOIN vehicles v ON r.vehicle_id = v.id
        LEFT JOIN users u ON v.owner_id = u.id
        WHERE r.client_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$clientId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getReservationDetails($reservationId, $clientId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT r.*, v.vehicle_type, v.plate_number, u.name as transporter_name, u.phone as transporter_phone
        FROM reservations r
        LEFT JOIN vehicles v ON r.vehicle_id = v.id
        LEFT JOIN users u ON v.owner_id = u.id
        WHERE r.id = ? AND r.client_id = ?
    ");
    $stmt->execute([$reservationId, $clientId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getClientReservationsSummary($clientId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as accepted,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed
        FROM reservations 
        WHERE client_id = ?
    ");
    $stmt->execute([ReservationStatus::PENDING, ReservationStatus::ACCEPTED, ReservationStatus::IN_PROGRESS, ReservationStatus::COMPLETED, $clientId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPendingRequests($pdo) {
    // Exclude expired requests (older than 24h)
    $stmt = $pdo->prepare("SELECT r.*, u.name as client_name, u.phone as client_phone 
                           FROM reservations r 
                           JOIN users u ON r.client_id = u.id 
                           WHERE r.status = ? 
                           AND r.created_at >= (NOW() - INTERVAL 1 DAY)
                           ORDER BY r.created_at ASC");
    $stmt->execute([ReservationStatus::PENDING]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function hasActiveVehicles($transporter_id, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE owner_id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$transporter_id]);
    return $stmt->fetch() !== false;
}

function getJobsByTransporter($transporter_id, $pdo) {
    $stmt = $pdo->prepare("SELECT r.*, u.name as client_name, u.phone as client_phone, v.plate_number, v.vehicle_type 
                           FROM reservations r 
                           JOIN users u ON r.client_id = u.id 
                           JOIN vehicles v ON r.vehicle_id = v.id 
                           WHERE v.owner_id = ? 
                           ORDER BY 
                              CASE r.status 
                                WHEN ? THEN 1 
                                WHEN ? THEN 2 
                                WHEN ? THEN 3 
                                WHEN ? THEN 4 
                                ELSE 5 
                              END, r.reservation_date DESC");
    $stmt->execute([$transporter_id, ReservationStatus::NEGOTIATION, ReservationStatus::ACCEPTED, ReservationStatus::IN_PROGRESS, ReservationStatus::COMPLETED]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getReservationTimeline($reservation_id, $pdo) {
    $stmt = $pdo->prepare("SELECT l.*, u.name as author_name, u.role as author_role 
                           FROM reservation_logs l 
                           JOIN users u ON l.changed_by = u.id 
                           WHERE l.reservation_id = ? 
                           ORDER BY l.created_at ASC");
    $stmt->execute([$reservation_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
