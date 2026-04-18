<?php
/**
 * Admin Functions — Vehicle Management Module
 */

function getAllVehicles($pdo, $filters = []) {
    $where = [];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = "v.status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['search'])) {
        $where[] = "(u.name LIKE ? OR v.plate_number LIKE ? OR v.vehicle_type LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }

    $sql = "SELECT v.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone,
            (SELECT COUNT(*) FROM reservations r WHERE r.vehicle_id = v.id) as total_trips
            FROM vehicles v
            JOIN users u ON v.owner_id = u.id";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY v.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function adminUpdateVehicleStatus($vehicle_id, $status, $pdo) {
    $valid = ['active', 'inactive'];
    if (!in_array($status, $valid)) return false;
    
    // If admin sets to inactive, set deactivated_by_admin = 1
    // If admin sets to active, clear deactivated_by_admin and is_activation_requested
    $deactivatedByAdmin = ($status === 'inactive') ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE vehicles SET status = ?, deactivated_by_admin = ?, is_activation_requested = 0 WHERE id = ?");
    return $stmt->execute([$status, $deactivatedByAdmin, $vehicle_id]);
}

function requestVehicleActivation($vehicle_id, $pdo) {
    $stmt = $pdo->prepare("UPDATE vehicles SET is_activation_requested = 1 WHERE id = ?");
    return $stmt->execute([$vehicle_id]);
}
