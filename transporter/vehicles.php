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

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF validation failed");
    }

    $vehicle_id = (int)$_POST['vehicle_id'];
    if (deleteVehicle($vehicle_id, $transporter_id, $pdo)) {
        $_SESSION['success'] = "Véhicule supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression ou véhicule introuvable.";
    }
    header('Location: vehicles.php');
    exit;
}

$vehicles = getVehiclesByTransporter($transporter_id, $pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Véhicules - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    <style>
        .vehicle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .vehicle-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .vehicle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            border-color: rgba(255, 140, 0, 0.3);
        }
        .vehicle-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .vehicle-plate {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
            background: rgba(0,0,0,0.3);
            padding: 5px 10px;
            border-radius: 6px;
            border-left: 3px solid #ff8c00;
        }
        .vehicle-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
            color: #94a3b8;
        }
        .vehicle-detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .vehicle-detail-item i {
            color: #ff8c00;
            width: 20px;
            text-align: center;
        }
        .vehicle-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>

<div class="dashboard-layout">
    <?php include INC_PATH . 'sidebar.php'; ?>
    <main class="main-content">
        <?php include INC_PATH . 'topbar.php'; ?>
        
        <div class="page-header">
            <div>
                <h1>Mes Véhicules</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Gérez votre flotte de véhicules disponibles.</p>
            </div>
            <div>
                <a href="vehicle_form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter un Véhicule</a>
            </div>
        </div>

        <?php if (empty($vehicles)): ?>
            <div style="text-align: center; padding: 60px 20px; background: rgba(30, 41, 59, 0.5); border-radius: 12px; border: 1px dashed rgba(255, 255, 255, 0.1); margin-top: 20px;">
                <i class="fas fa-truck-pickup" style="font-size: 60px; color: #475569; margin-bottom: 20px;"></i>
                <h3 style="color: #fff; margin-bottom: 10px;">Aucun véhicule dans votre flotte</h3>
                <p style="color: #94a3b8; margin-bottom: 25px;">Vous devez ajouter au moins un véhicule pour accepter des missions.</p>
                <a href="vehicle_form.php" class="btn btn-primary">Ajouter mon premier véhicule</a>
            </div>
        <?php else: ?>
            <div class="vehicle-grid">
                <?php foreach ($vehicles as $vehicle): ?>
                    <div class="vehicle-card">
                        <div class="vehicle-header">
                            <div class="vehicle-plate"><?php echo htmlspecialchars($vehicle['plate_number']); ?></div>
                            <div>
                                <?php if ($vehicle['status'] === 'active'): ?>
                                    <span class="badge badge-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactif</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="vehicle-details">
                            <div class="vehicle-detail-item">
                                <i class="fas fa-truck"></i>
                                <span><strong>Type:</strong> <?php echo htmlspecialchars($vehicle['vehicle_type']); ?></span>
                            </div>
                            <div class="vehicle-detail-item">
                                <i class="fas fa-weight-hanging"></i>
                                <span><strong>Capacité:</strong> <?php echo number_format($vehicle['capacity'], 2); ?> Tonnes</span>
                            </div>
                            <div class="vehicle-detail-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span><strong>Ajouté le:</strong> <?php echo date('d/m/Y', strtotime($vehicle['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="vehicle-actions">
                            <a href="vehicle_form.php?id=<?php echo htmlspecialchars($vehicle['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline" style="flex: 1; text-align: center;"><i class="fas fa-edit"></i> Modifier</a>
                            <form method="POST" action="vehicles.php" style="flex: 1;" onsubmit="if(!confirm('Êtes-vous sûr de vouloir supprimer ce véhicule ?')) return false; this.querySelector('button').classList.add('loading'); this.querySelector('button').innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Patientez...'; return true;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="vehicle_id" value="<?php echo htmlspecialchars($vehicle['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn btn-outline" style="width: 100%; color: #ef4444; border-color: rgba(239, 68, 68, 0.3); transition: all 0.3s ease;"><i class="fas fa-trash"></i> Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <style>
                button.loading { opacity: 0.7; pointer-events: none; }
            </style>
        <?php endif; ?>

    </main>
</div>

<?php include INC_PATH . 'toast.php'; ?>
<script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
