<?php
/**
 * Admin Functions — Reservation Management Module
 */

function getAllReservations($pdo, $filters = []) {
    $where = [];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = "r.status = ?";
        $params[] = $filters['status'];
    }
    if (!empty($filters['search'])) {
        $where[] = "(c.name LIKE ? OR r.pickup_location LIKE ? OR r.destination LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }

    $sql = "SELECT r.*, c.name as client_name, c.phone as client_phone,
            t.name as transporter_name, t.phone as transporter_phone,
            v.vehicle_type, v.plate_number
            FROM reservations r
            JOIN users c ON r.client_id = c.id
            LEFT JOIN vehicles v ON r.vehicle_id = v.id
            LEFT JOIN users t ON v.owner_id = t.id";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY r.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function adminUpdateReservationStatus($reservation_id, $new_status, $admin_id, $pdo) {
    require_once __DIR__ . '/../reservation/constants.php';
    require_once __DIR__ . '/../reservation/validation.php';
    require_once __DIR__ . '/../reservation/lifecycle.php';

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT status, price FROM reservations WHERE id = ? FOR UPDATE");
        $stmt->execute([$reservation_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res) {
            $pdo->rollBack();
            return "Réservation introuvable.";
        }

        if ($res['status'] === $new_status) {
            $pdo->commit();
            return true;
        }

        if (!validateStatusTransition($res['status'], $new_status)) {
            $pdo->rollBack();
            return "Transition invalide : {$res['status']} → {$new_status}";
        }

        $upd = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $upd->execute([$new_status, $reservation_id]);

        logReservationChange($reservation_id, $res['status'], $new_status, $admin_id, $pdo);

        // If completing + has price → insert earnings + commission
        if ($new_status === 'completed' && $res['price'] > 0) {
            $chk = $pdo->prepare("SELECT id FROM earnings WHERE reservation_id = ?");
            $chk->execute([$reservation_id]);
            if (!$chk->fetch()) {
                $vehicleStmt = $pdo->prepare("SELECT v.owner_id FROM vehicles v JOIN reservations r ON r.vehicle_id = v.id WHERE r.id = ?");
                $vehicleStmt->execute([$reservation_id]);
                $vRow = $vehicleStmt->fetch();
                if ($vRow) {
                    $commRate = defined('APP_COMMISSION') ? APP_COMMISSION : 0.20;
                    $commValue = $res['price'] * $commRate;
                    $pdo->prepare("INSERT INTO earnings (transporter_id, reservation_id, amount, created_at) VALUES (?, ?, ?, NOW())")->execute([$vRow['owner_id'], $reservation_id, $res['price']]);
                    $pdo->prepare("UPDATE reservations SET platform_commission = ? WHERE id = ?")->execute([$commValue, $reservation_id]);
                }
            }
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return "Erreur DB: " . $e->getMessage();
    }
}
