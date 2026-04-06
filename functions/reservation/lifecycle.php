<?php
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/validation.php';

function logReservationChange($reservation_id, $old, $new, $user_id, $pdo) {
    if ($old === $new && $old !== null) return; // Idempotency check 
    $stmt = $pdo->prepare("INSERT INTO reservation_logs (reservation_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$reservation_id, $old, $new, $user_id]);
}

function createReservation($clientId, $pickup, $destination, $cargoType, $weight, $volume, $date, $price, $priceType, $pdo) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            INSERT INTO reservations 
            (client_id, pickup_location, destination, cargo_type, weight, volume, reservation_date, price, price_type, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$clientId, $pickup, $destination, $cargoType, $weight, $volume, $date, $price, $priceType, ReservationStatus::PENDING]);
        $reservation_id = $pdo->lastInsertId();
        
        logReservationChange($reservation_id, null, ReservationStatus::PENDING, $clientId, $pdo);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return $e->getMessage();
    }
}

function acceptReservation($reservation_id, $transporter_id, $vehicle_id, $pdo, $price = null) {
    require_once __DIR__ . '/../vehicle.php';
    if (!checkVehicleOwnership($vehicle_id, $transporter_id, $pdo)) return "Véhicule non autorisé.";

    $vehicle = getVehicleById($vehicle_id, $pdo);
    if (!$vehicle) return "Véhicule introuvable.";

    try {
        $pdo->beginTransaction();

        // Row lock with FOR UPDATE to prevent race conditions
        $stmtLock = $pdo->prepare("SELECT status, weight, price_type FROM reservations WHERE id = ? FOR UPDATE");
        $stmtLock->execute([$reservation_id]);
        $res = $stmtLock->fetch();

        if (!$res) {
            $pdo->rollBack();
            return "Demande introuvable.";
        }

        $current_status = $res['status'];
        if ($current_status !== ReservationStatus::PENDING) {
            $pdo->rollBack();
            return "Cette demande a déjà été prise en charge par un autre transporteur.";
        }

        // Weight verification: cargo weight (kg) must not exceed vehicle capacity (tons * 1000)
        $cargoWeight = (float)$res['weight'];
        $vehicleCapacityKg = (float)$vehicle['capacity'] * 1000;
        if ($cargoWeight > 0 && $vehicleCapacityKg > 0 && $cargoWeight > $vehicleCapacityKg) {
            $pdo->rollBack();
            return "Le poids de la cargaison (" . number_format($cargoWeight, 2) . " kg) dépasse la capacité de votre véhicule (" . number_format((float)$vehicle['capacity'], 2) . " tonnes / " . number_format($vehicleCapacityKg, 2) . " kg).";
        }

        $new_status = ReservationStatus::ACCEPTED;
        if ($res['price_type'] === 'negotiable') {
            if ($price === null || $price <= 0) {
                $pdo->rollBack();
                return "Un prix doit être proposé pour une demande négociable.";
            }
            $new_status = ReservationStatus::NEGOTIATION;
        }

        if (!validateStatusTransition($current_status, $new_status)) {
            $pdo->rollBack();
            return "Transition invalide.";
        }

        $query = "UPDATE reservations SET vehicle_id = ?, status = ?";
        $params = [$vehicle_id, $new_status];
        
        if ($new_status === ReservationStatus::NEGOTIATION) {
            $query .= ", transporter_proposed_price = ?";
            $params[] = $price;
        } else if ($price !== null && $price > 0 && $res['price_type'] !== 'negotiable') {
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
        
        logReservationChange($reservation_id, $current_status, $new_status, $transporter_id, $pdo);
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

function clientAcceptNegotiation($reservation_id, $client_id, $pdo) {
    try {
        $pdo->beginTransaction();
        $stmtLock = $pdo->prepare("SELECT status, transporter_proposed_price FROM reservations WHERE id = ? AND client_id = ? FOR UPDATE");
        $stmtLock->execute([$reservation_id, $client_id]);
        $res = $stmtLock->fetch();

        if (!$res || $res['status'] !== ReservationStatus::NEGOTIATION) {
            $pdo->rollBack();
            return "Demande introuvable ou n'est plus en négociation.";
        }

        if (!validateStatusTransition($res['status'], ReservationStatus::ACCEPTED)) {
            $pdo->rollBack();
            return "Transition invalide.";
        }

        $stmt = $pdo->prepare("UPDATE reservations SET status = ?, price = transporter_proposed_price WHERE id = ?");
        $stmt->execute([ReservationStatus::ACCEPTED, $reservation_id]);
        
        logReservationChange($reservation_id, ReservationStatus::NEGOTIATION, ReservationStatus::ACCEPTED, $client_id, $pdo);
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return "Erreur BDD : " . $e->getMessage();
    }
}
