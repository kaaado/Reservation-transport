<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('transporter');

require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'vehicle.php';

$db = new Database();
$pdo = $db->getConnection();
$transporter_id = $_SESSION['user_id'];

$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $vehicle_id > 0;
$vehicle = null;

if ($is_edit) {
    if (!checkVehicleOwnership($vehicle_id, $transporter_id, $pdo)) {
        $_SESSION['error'] = "Véhicule introuvable ou non autorisé.";
        header('Location: vehicles.php');
        exit;
    }
    $vehicle = getVehicleById($vehicle_id, $pdo);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF validation failed");
    } else {
        $data = [
            'owner_id' => $transporter_id,
            'vehicle_type' => trim($_POST['vehicle_type']),
            'capacity' => (float)$_POST['capacity'],
            'plate_number' => trim($_POST['plate_number']),
            'status' => $_POST['status'] ?? 'active'
        ];

        // Validation
        // Stricter Validation
        $plate_pattern = "/^[0-9A-Za-z\s-]{5,20}$/"; // Basic alphanumeric check
        
        if (empty($data['vehicle_type']) || strlen($data['vehicle_type']) < 3) {
            $_SESSION['error'] = "Le type de véhicule doit comporter au moins 3 caractères.";
        } elseif (empty($data['plate_number']) || !preg_match($plate_pattern, $data['plate_number'])) {
            $_SESSION['error'] = "Le format du numéro d'immatriculation est invalide.";
        } elseif ($data['capacity'] <= 0 || $data['capacity'] > 100) {
            $_SESSION['error'] = "La capacité doit être comprise entre 0.1 et 100 Tonnes.";
        } else {
            if ($is_edit) {
                $result = updateVehicle($vehicle_id, $data, $pdo);
                if ($result === true) {
                    $_SESSION['success'] = "Véhicule mis à jour avec succès.";
                    header('Location: vehicles.php');
                    exit;
                } else {
                    $_SESSION['error'] = $result;
                }
            } else {
                $result = addVehicle($data, $pdo);
                if (is_numeric($result)) {
                    $_SESSION['success'] = "Véhicule ajouté avec succès.";
                    header('Location: vehicles.php');
                    exit;
                } else {
                    $_SESSION['error'] = $result;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Modifier' : 'Ajouter'; ?> un Véhicule - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
</head>
<body>

<div class="dashboard-layout">
    <?php include INC_PATH . 'sidebar.php'; ?>
    <main class="main-content">
        <?php include INC_PATH . 'topbar.php'; ?>
        
        <div class="page-header" style="max-width: 800px; margin: 0 auto 30px auto;">
            <div>
                <h1><?php echo $is_edit ? 'Modifier le Véhicule' : 'Ajouter un Véhicule'; ?></h1>
                <p style="color: #94a3b8; margin-top: 5px;">Configurez les détails techniques de votre unité de transport.</p>
            </div>
            <a href="vehicles.php" class="btn-secondary" style="width: auto; padding: 10px 20px;"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
        </div>

        <div class="form-card" style="max-width: 800px; margin: 0 auto;">
            <form method="POST" action="" id="vehicleForm" onsubmit="return validateForm(this);">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                
                <h3 style="margin-bottom: 25px; font-size: 18px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">Informations Générales</h3>

                <div class="form-row">
                    <div class="form-group-dash">
                        <label for="plate_number">Numéro d'immatriculation *</label>
                        <input type="text" id="plate_number" name="plate_number" placeholder="Ex: 01234-112-16" value="<?php echo htmlspecialchars($vehicle['plate_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        <small style="color: #64748b; font-size: 11px;">Format standard accepté (Lettres, chiffres, tirets)</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-dash">
                        <label for="vehicle_type">Type de véhicule *</label>
                        <input type="text" id="vehicle_type" name="vehicle_type" placeholder="Ex: Camion Frigorifique, Fourgonnette..." value="<?php echo htmlspecialchars($vehicle['vehicle_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="form-group-dash">
                        <label for="capacity">Capacité de charge (Tonnes) *</label>
                        <input type="number" id="capacity" step="0.1" min="0.1" max="100" name="capacity" placeholder="Ex: 3.5" value="<?php echo htmlspecialchars($vehicle['capacity'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                </div>

                <?php if ($is_edit): ?>
                <div class="form-row">
                    <div class="form-group-dash">
                        <label for="status">Statut opérationnel</label>
                        <select name="status" id="status" required>
                            <option value="active" <?php echo ($vehicle['status'] ?? '') === 'active' ? 'selected' : ''; ?>>En service (Actif)</option>
                            <option value="inactive" <?php echo ($vehicle['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Hors service (Inactif)</option>
                        </select>
                    </div>
                </div>
                <?php endif; ?>

                <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-primary" style="width: auto; padding: 12px 40px; font-size: 16px;">
                        <i class="fas fa-save"></i> <?php echo $is_edit ? 'Mettre à jour' : 'Enregistrer le véhicule'; ?>
                    </button>
                </div>
            </form>
            <style>
                button.loading { opacity: 0.7; pointer-events: none; }
            </style>
        </div>

    </main>
</div>

<?php include INC_PATH . 'toast.php'; ?>
<script>
function validateForm(form) {
    const btn = form.querySelector('button[type=submit]');
    const plate = form.plate_number.value.trim();
    const capacity = parseFloat(form.capacity.value);
    
    if (plate.length < 5) {
        alert("Le numéro d'immatriculation est trop court.");
        return false;
    }
    
    if (capacity <= 0 || capacity > 100) {
        alert("Veuillez saisir une capacité valide (0.1 - 100 T).");
        return false;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Patientez...';
    return true;
}
</script>
</body>
</html>
