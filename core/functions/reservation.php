<?php
// Function definitions for handling reservations
function createReservation($clientId, $pickup, $destination, $cargoType, $weight, $volume, $date, $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO reservations 
            (client_id, pickup_location, destination, cargo_type, weight, volume, reservation_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$clientId, $pickup, $destination, $cargoType, $weight, $volume, $date]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function getClientReservationsSummary($clientId, $pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM reservations 
        WHERE client_id = ?
    ");
    $stmt->execute([$clientId]);
    return $stmt->fetch();
}

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
    return $stmt->fetchAll();
}

function getReservationDetails($reservationId, $clientId, $pdo) {
    // Client security check built-in to query
    $stmt = $pdo->prepare("
        SELECT r.*, v.vehicle_type, v.plate_number, u.name as transporter_name, u.phone as transporter_phone
        FROM reservations r
        LEFT JOIN vehicles v ON r.vehicle_id = v.id
        LEFT JOIN users u ON v.owner_id = u.id
        WHERE r.id = ? AND r.client_id = ?
    ");
    $stmt->execute([$reservationId, $clientId]);
    return $stmt->fetch();
}
?>
