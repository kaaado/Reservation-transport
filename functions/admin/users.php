<?php
/**
 * Admin Functions — User Management Module
 * Split from admin.php for maintainability.
 */

function getAllUsers($pdo, $filters = []) {
    $where = [];
    $params = [];

    if (!empty($filters['role'])) {
        $where[] = "u.role = ?";
        $params[] = $filters['role'];
    }
    if (!empty($filters['status'])) {
        $where[] = "u.status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['search'])) {
        $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['verification'])) {
        if ($filters['verification'] === 'verified') {
            $where[] = "u.id_is_verified = 1";
        } elseif ($filters['verification'] === 'pending') {
            $where[] = "u.id_is_verified = 0 AND u.id_card_url IS NOT NULL";
        } elseif ($filters['verification'] === 'unverified') {
            $where[] = "(u.id_is_verified = 0)";
        }
    }

    $sql = "SELECT u.*, 
            (SELECT COUNT(*) FROM reservations r WHERE r.client_id = u.id) as reservation_count,
            (SELECT COUNT(*) FROM vehicles v WHERE v.owner_id = u.id) as vehicle_count
            FROM users u";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);

    // Sort unverified users first (those with pending ID cards)
    $sql .= " ORDER BY 
        CASE WHEN u.id_is_verified = 0 AND u.id_card_url IS NOT NULL THEN 0 ELSE 1 END ASC,
        u.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserById($user_id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateUserStatus($user_id, $status, $pdo) {
    $valid = ['active', 'pending', 'suspended'];
    if (!in_array($status, $valid)) return false;
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $user_id])) {
        $msg = ($status === 'suspended') 
            ? "Votre compte a été suspendu par l'administration. Veuillez contacter le support."
            : "Votre compte est désormais actif. Vous pouvez utiliser toutes les fonctionnalités.";
        sendAdminNotification($user_id, $msg, $pdo);
        return true;
    }
    return false;
}

function verifyUserID($user_id, $pdo) {
    // Verify ID card AND auto-activate user status
    $stmt = $pdo->prepare("UPDATE users SET id_is_verified = 1, status = 'active' WHERE id = ?");
    if ($stmt->execute([$user_id])) {
        sendAdminNotification($user_id, "Félicitations ! Votre identité a été vérifiée avec succès. Votre compte est maintenant pleinement opérationnel.", $pdo);
        return true;
    }
    return false;
}

function unverifyUserID($user_id, $pdo) {
    $stmt = $pdo->prepare("UPDATE users SET id_is_verified = 0 WHERE id = ?");
    return $stmt->execute([$user_id]);
}

function deleteUser($user_id, $pdo) {
    // Prevent deletion if user has active reservations or vehicles
    $checkRes = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE client_id = ? AND status IN ('pending','accepted','in_progress','negotiation')");
    $checkRes->execute([$user_id]);
    if ($checkRes->fetchColumn() > 0) {
        return "HAS_ACTIVE_RESERVATIONS";
    }

    $checkVehicles = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE owner_id = ? AND status = 'active'");
    $checkVehicles->execute([$user_id]);
    if ($checkVehicles->fetchColumn() > 0) {
        return "HAS_ACTIVE_VEHICLES";
    }

    // Check for unpaid commissions
    $checkComm = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN vehicles v ON r.vehicle_id = v.id WHERE v.owner_id = ? AND r.status = 'completed' AND r.is_commission_paid = 0");
    $checkComm->execute([$user_id]);
    if ($checkComm->fetchColumn() > 0) {
        return "HAS_UNPAID_COMMISSIONS";
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    return $stmt->execute([$user_id]);
}

function updateUserProfile($user_id, $data, $pdo) {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, region = ? WHERE id = ?");
    return $stmt->execute([
        $data['name'], $data['email'], $data['phone'], $data['role'], $data['region'], $user_id
    ]);
}
