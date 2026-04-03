<?php
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/validation.php';

function logReservationChange($reservation_id, $old, $new, $user_id, $pdo) {
    if ($old === $new && $old !== null) return; // Idempotency check 
    $stmt = $pdo->prepare("INSERT INTO reservation_logs (reservation_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$reservation_id, $old, $new, $user_id]);
}

function createReservation($clientId, $pickup, $destination, $cargoType, $weight, $volume, $date, $pdo) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            INSERT INTO reservations 
            (client_id, pickup_location, destination, cargo_type, weight, volume, reservation_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$clientId, $pickup, $destination, $cargoType, $weight, $volume, $date, ReservationStatus::PENDING]);
        $reservation_id = $pdo->lastInsertId();
        
        logReservationChange($reservation_id, null, ReservationStatus::PENDING, $clientId, $pdo);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

function acceptReservation($reservation_id, $transporter_id, $vehicle_id, $pdo, $price = null) {
    require_once __DIR__ . '/../vehicle.php';
    if (!checkVehicleOwnership($vehicle_id, $transporter_id, $pdo)) return "Véhicule non autorisé.";

    try {
        $pdo->beginTransaction();

        // Row lock with FOR UPDATE to prevent race conditions
        $stmtLock = $pdo->prepare("SELECT status FROM reservations WHERE id = ? FOR UPDATE");
        $stmtLock->execute([$reservation_id]);
        $res = $stmtLock->fetch();

        if (!$res) {
            $pdo->rollBack();
            return "Demande introuvable.";
        }

        $current_status = $res['status'];
        if ($current_status !== ReservationStatus::PENDING) {
            $pdo->rollBack();
            return "La demande n'est plus disponible.";
        }

        if (!validateStatusTransition($current_status, ReservationStatus::ACCEPTED)) {
            $pdo->rollBack();
            return "Transition invalide.";
        }

        $query = "UPDATE reservations SET vehicle_id = ?, status = ?";
        $params = [$vehicle_id, ReservationStatus::ACCEPTED];
        
        if ($price !== null && $price > 0) {
            $query .= ", price = ?";
            $params[] = $price;
        }
        
        $query .= " WHERE id = ? AND status = ?"; // Atomic status condition as extra safety
        $params[] = $reservation_id;
        $params[] = ReservationStatus::PENDING;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return "Échec de l'acceptation.";
        }
        
        logReservationChange($reservation_id, $current_status, ReservationStatus::ACCEPTED, $transporter_id, $pdo);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return "Erreur BDD : " . $e->getMessage();
    }
}

function rejectReservation($reservation_id, $user_id, $pdo) {
    try {
        $pdo->beginTransaction();
        $stmtLock = $pdo->prepare("SELECT status FROM reservations WHERE id = ? FOR UPDATE");
        $stmtLock->execute([$reservation_id]);
        $res = $stmtLock->fetch();

        if (!$res || $res['status'] !== ReservationStatus::PENDING) {
            $pdo->rollBack();
            return false;
        }

        if (!validateStatusTransition($res['status'], ReservationStatus::REJECTED)) {
            $pdo->rollBack();
            return false;
        }

        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ? AND status = ?");
        $stmt->execute([ReservationStatus::REJECTED, $reservation_id, ReservationStatus::PENDING]);
        
        logReservationChange($reservation_id, ReservationStatus::PENDING, ReservationStatus::REJECTED, $user_id, $pdo);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

function cancelReservation($reservation_id, $client_id, $pdo) {
    try {
        $pdo->beginTransaction();
        $stmtLock = $pdo->prepare("SELECT status FROM reservations WHERE id = ? AND client_id = ? FOR UPDATE");
        $stmtLock->execute([$reservation_id, $client_id]);
        $res = $stmtLock->fetch();

        if (!$res) {
            $pdo->rollBack();
            return "Accès non autorisé ou demande inexistant.";
        }

        if (!validateStatusTransition($res['status'], ReservationStatus::CANCELLED)) {
            $pdo->rollBack();
            return "Impossible d'annuler une demande dans cet état.";
        }

        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmt->execute([ReservationStatus::CANCELLED, $reservation_id]);
        
        logReservationChange($reservation_id, $res['status'], ReservationStatus::CANCELLED, $client_id, $pdo);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return "Erreur BDD : " . $e->getMessage();
    }
}

function updateJobStatus($job_id, $transporter_id, $new_status, $pdo) {
    try {
        $pdo->beginTransaction();

        $stmtCheck = $pdo->prepare("SELECT r.status, r.price, r.id FROM reservations r 
                                    JOIN vehicles v ON r.vehicle_id = v.id 
                                    WHERE r.id = ? AND v.owner_id = ? FOR UPDATE");
        $stmtCheck->execute([$job_id, $transporter_id]);
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $pdo->rollBack();
            return false;
        }

        if ($row['status'] === $new_status) {
            $pdo->commit(); // Idempotent
            return true;
        }

        if (!validateStatusTransition($row['status'], $new_status)) {
            $pdo->rollBack();
            return false;
        }

        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $job_id]);

        logReservationChange($job_id, $row['status'], $new_status, $transporter_id, $pdo);

        if ($new_status === ReservationStatus::COMPLETED && $row['price'] > 0) {
            $checkEarning = $pdo->prepare("SELECT id FROM earnings WHERE reservation_id = ? FOR UPDATE");
            $checkEarning->execute([$job_id]);
            if (!$checkEarning->fetch()) {
                $earningStmt = $pdo->prepare("INSERT INTO earnings (transporter_id, reservation_id, amount, created_at) VALUES (?, ?, ?, NOW())");
                $earningStmt->execute([$transporter_id, $job_id, $row['price']]);
            }
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}
