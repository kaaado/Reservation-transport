<?php

function addVehicle($data, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO vehicles (owner_id, vehicle_type, capacity, plate_number, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'active', NOW(), NOW())");
    try {
        $stmt->execute([
            $data['owner_id'],
            $data['vehicle_type'],
            $data['capacity'],
            $data['plate_number']
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            return "Une erreur s'est produite. Le numéro d'immatriculation existe peut-être déjà.";
        }
        return "Erreur BDD : " . $e->getMessage();
    }
}

function updateVehicle($id, $data, $pdo) {
    // Check ownership first
    if (!checkVehicleOwnership($id, $data['owner_id'], $pdo)) {
        return "Non autorisé.";
    }

    $current = getVehicleById($id, $pdo);
    
    // If admin deactivated it and transporter tries to reactivate
    if ($current['deactivated_by_admin'] == 1 && $data['status'] === 'active') {
        return "Ce véhicule a été désactivé par l'administration. Vous devez envoyer une demande d'activation.";
    }

    $stmt = $pdo->prepare("UPDATE vehicles SET vehicle_type = ?, capacity = ?, plate_number = ?, status = ?, updated_at = NOW() WHERE id = ?");
    try {
        $stmt->execute([
            $data['vehicle_type'],
            $data['capacity'],
            $data['plate_number'],
            $data['status'],
            $id
        ]);
        return true;
    } catch (PDOException $e) {
        return "Erreur BDD : " . $e->getMessage();
    }
}

function deleteVehicle($id, $owner_id, $pdo) {
    if (!checkVehicleOwnership($id, $owner_id, $pdo)) {
        return false;
    }
    
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
    return $stmt->execute([$id]);
}

function getVehiclesByTransporter($transporter_id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE owner_id = ? ORDER BY created_at DESC");
    $stmt->execute([$transporter_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getVehicleById($id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function checkVehicleOwnership($vehicle_id, $transporter_id, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ? AND owner_id = ?");
    $stmt->execute([$vehicle_id, $transporter_id]);
    return $stmt->fetch() !== false;
}

function requestVehicleActivation($vehicle_id, $owner_id, $pdo) {
    if (!checkVehicleOwnership($vehicle_id, $owner_id, $pdo)) return false;
    $stmt = $pdo->prepare("UPDATE vehicles SET is_activation_requested = 1 WHERE id = ?");
    if ($stmt->execute([$vehicle_id])) {
        // NOTIFICATION: Notify Admin
        require_once FUNC_PATH . 'admin/notifications.php';
        notifyAdmin("Demande d'activation reçue pour le véhicule #$vehicle_id (Transporteur #$owner_id).", $pdo);
        return true;
    }
    return false;
}
