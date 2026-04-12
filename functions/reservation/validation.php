<?php
require_once __DIR__ . '/constants.php';

function validateStatusTransition($current, $next) {
    if ($current === $next) return true;
    
    $allowed = [
        ReservationStatus::PENDING => [ReservationStatus::ACCEPTED, ReservationStatus::NEGOTIATION, ReservationStatus::REJECTED, ReservationStatus::CANCELLED],
        ReservationStatus::NEGOTIATION => [ReservationStatus::ACCEPTED, ReservationStatus::REJECTED, ReservationStatus::CANCELLED],
        ReservationStatus::ACCEPTED => [ReservationStatus::IN_PROGRESS, ReservationStatus::COMPLETED, ReservationStatus::CANCELLED],
        ReservationStatus::IN_PROGRESS => [ReservationStatus::COMPLETED],
        ReservationStatus::COMPLETED => [],
        ReservationStatus::CANCELLED => [],
        ReservationStatus::REJECTED => []
    ];

    return isset($allowed[$current]) && in_array($next, $allowed[$current]);
}

function checkReservationOwnership($reservation_id, $user_id, $role, $pdo) {
    if ($role === 'client') {
        $stmt = $pdo->prepare("SELECT id FROM reservations WHERE id = ? AND client_id = ?");
        $stmt->execute([$reservation_id, $user_id]);
    } elseif ($role === 'transporter') {
        $stmt = $pdo->prepare("SELECT r.id FROM reservations r 
                               JOIN vehicles v ON r.vehicle_id = v.id 
                               WHERE r.id = ? AND v.owner_id = ?");
        $stmt->execute([$reservation_id, $user_id]);
    } else {
        return false;
    }
    return $stmt->fetch() !== false;
}
