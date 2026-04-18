<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('admin');

require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'admin.php';

$db = new Database();
$pdo = $db->getConnection();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: vehicles.php"); exit;
    }

    $action = $_POST['action'] ?? '';
    $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        if (adminUpdateVehicleStatus($vehicle_id, $status, $pdo)) {
            // Notify the vehicle owner
            $ownerStmt = $pdo->prepare("SELECT owner_id FROM vehicles WHERE id = ?");
            $ownerStmt->execute([$vehicle_id]);
            $ownerId = $ownerStmt->fetchColumn();
            if ($ownerId) {
                $label = ($status === 'active') ? 'approuvé' : 'désactivé';
                sendAdminNotification($ownerId, "Votre véhicule #$vehicle_id a été $label par l'administration.", $pdo);
            }
            logAdminAction($_SESSION['user_id'], 'update_vehicle_status', 'vehicle', $vehicle_id, "Status changed to: {$status}", $pdo);
            $_SESSION['success'] = "Véhicule #$vehicle_id → $status.";
        } else {
            $_SESSION['error'] = "Échec de la mise à jour.";
        }
        header("Location: vehicles.php"); exit;
    }
}

// Filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
];
$vehicles = getAllVehicles($pdo, $filters);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Véhicules - Admin CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    <style>
        .filter-bar {
            display: flex; gap: 12px; flex-wrap: wrap; align-items: center;
            background: rgba(30,41,59,0.6); border-radius: 12px; padding: 16px;
            border: 1px solid rgba(255,255,255,0.06); margin-top: 20px;
        }
        .filter-bar select, .filter-bar input {
            background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.1);
            color: #fff; padding: 9px 14px; border-radius: 8px; font-size: 13px; outline: none;
        }
        .filter-bar input { flex: 1; min-width: 200px; }
        .table-container { 
            background: rgba(30,41,59,0.6); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px; overflow: hidden; margin-top: 16px;
        }
        .veh-table { width: 100%; border-collapse: collapse; }
        .veh-table thead th {
            padding: 12px 14px; text-align: left; font-size: 11px;
            text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px;
            font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.06);
            background: rgba(15,23,42,0.4);
        }
        .veh-table tbody td {
            padding: 14px; border-bottom: 1px solid rgba(255,255,255,0.04);
            font-size: 13px; color: #cbd5e1; vertical-align: middle;
        }
        .veh-table tbody tr { transition: background 0.2s; }
        .veh-table tbody tr:hover { background: rgba(255,140,0,0.04); }
        .plate-badge {
            background: rgba(255,140,0,0.12); border: 1px solid rgba(255,140,0,0.3);
            color: #fbbf24; font-size: 12px; font-weight: 700; padding: 4px 10px;
            border-radius: 6px; font-family: 'Courier New', monospace; letter-spacing: 1px;
        }
        .results-count { font-size: 13px; color: #64748b; margin-top: 16px; }
        .vehicle-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }
        .vehicle-icon.active { background: rgba(16,185,129,0.15); color: #10b981; }
        .vehicle-icon.inactive { background: rgba(239,68,68,0.15); color: #ef4444; }
        .action-btns { display: flex; gap: 6px; }
        .action-btns form button {
            padding: 6px 12px; border-radius: 6px; font-size: 12px;
            border: 1px solid; cursor: pointer; transition: all 0.2s; font-weight: 600;
        }
        .btn-approve { background: rgba(16,185,129,0.15); border-color: rgba(16,185,129,0.3); color: #10b981; }
        .btn-approve:hover { background: rgba(16,185,129,0.3); }
        .btn-reject { background: rgba(239,68,68,0.15); border-color: rgba(239,68,68,0.3); color: #ef4444; }
        .btn-reject:hover { background: rgba(239,68,68,0.3); }
        .activation-request-badge {
            background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3);
            color: #f59e0b; font-size: 10px; font-weight: 700; padding: 2px 6px;
            border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px;
            display: inline-flex; align-items: center; gap: 3px;
            animation: pulse-orange 2s infinite;
        }
        @keyframes pulse-orange {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        @media (max-width: 768px) {
            .filter-group { flex-direction: column; align-items: stretch !important; }
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
                <h1>Gestion de la Flotte Véhicules</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Inspectez, approuvez ou désactivez les véhicules enregistrés sur la plateforme.</p>
            </div>
        </div>

        <!-- Premium Filter Bar -->
        <div class="filter-wrapper" style="margin-top: 25px; margin-bottom: 20px;">
            <form method="GET" class="premium-filter" style="display: flex; flex-direction: column; gap: 12px;">
                <div class="filter-group" style="display: flex; align-items: center; gap: 12px; background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(255,255,255,0.08); padding: 8px; border-radius: 14px; backdrop-filter: blur(10px);">
                    <div class="filter-input-wrap" style="position: relative; flex: 1; display: flex; align-items: center;">
                        <i class="fas fa-search" style="position: absolute; left: 14px; color: #64748b; font-size: 14px; pointer-events: none;"></i>
                        <input type="text" name="search" placeholder="Propriétaire, plaque, type..." value="<?php echo htmlspecialchars($filters['search']); ?>" onchange="this.form.submit()" style="width: 100%; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 10px 14px 10px 40px; color: #f8fafc; font-size: 13px; outline: none;">
                    </div>
                    
                    <div class="filter-select-wrap" style="position: relative; flex: 1; display: flex; align-items: center;">
                        <i class="fas fa-filter" style="position: absolute; left: 14px; color: #64748b; font-size: 14px; pointer-events: none;"></i>
                        <select name="status" onchange="this.form.submit()" style="width: 100%; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 10px 14px 10px 40px; color: #f8fafc; font-size: 13px; outline: none;">
                            <option value="">Tous les statuts</option>
                            <option value="active" <?php echo ($filters['status'] === 'active') ? 'selected' : ''; ?>>Actif</option>
                            <option value="inactive" <?php echo ($filters['status'] === 'inactive') ? 'selected' : ''; ?>>Inactif</option>
                        </select>
                    </div>

                    <?php if ($filters['status'] || $filters['search']): ?>
                        <a href="vehicles.php" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 10px; border: 1px solid rgba(239, 68, 68, 0.2);"><i class="fas fa-rotate-left"></i></a>
                    <?php endif; ?>
                </div>

                <!-- Active Filter Tags -->
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-left: 5px; margin-top: 10px;">
                    <?php if ($filters['status']): ?>
                        <span style="background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2); padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;">Statut: <?php echo htmlspecialchars($filters['status']); ?></span>
                    <?php endif; ?>
                    <span style="font-size: 12px; color: #64748b; margin-left: auto;"><strong><?php echo count($vehicles); ?></strong> véhicule(s)</span>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="veh-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Propriétaire</th>
                        <th>Type</th>
                        <th>Plaque</th>
                        <th>Capacité</th>
                        <th>Missions</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vehicles)): ?>
                        <tr><td colspan="8" style="text-align:center; padding:40px; color:#64748b;">Aucun véhicule trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($vehicles as $v): ?>
                        <tr>
                            <td>
                                <div class="vehicle-icon <?php echo $v['status']; ?>">
                                    <i class="fas fa-truck"></i>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#f8fafc;"><?php echo htmlspecialchars($v['owner_name']); ?></div>
                                <div style="font-size:11px; color:#64748b;"><?php echo htmlspecialchars($v['owner_email']); ?></div>
                                <div style="font-size:11px; color:#64748b;"><?php echo htmlspecialchars($v['owner_phone']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($v['vehicle_type']); ?></td>
                            <td><span class="plate-badge"><?php echo htmlspecialchars($v['plate_number']); ?></span></td>
                            <td><?php echo number_format($v['capacity'], 2); ?> t</td>
                            <td style="text-align:center;">
                                <span style="color:#3b82f6; font-weight:700;"><?php echo $v['total_trips']; ?></span>
                            </td>
                            <td>
                                <?php if ($v['status'] === 'active'): ?>
                                    <span style="color:#10b981; font-weight:600; font-size:12px;"><i class="fas fa-check-circle"></i> Actif</span>
                                <?php else: ?>
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <span style="color:#ef4444; font-weight:600; font-size:12px;"><i class="fas fa-times-circle"></i> Inactif</span>
                                        <?php if ($v['is_activation_requested']): ?>
                                            <span class="activation-request-badge"><i class="fas fa-bolt"></i> Activation demandée</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <?php if ($v['status'] !== 'active'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="vehicle_id" value="<?php echo $v['id']; ?>">
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="btn-approve">
                                            <?php echo $v['is_activation_requested'] ? '<i class="fas fa-check-double"></i> Approuver' : '<i class="fas fa-check"></i> Activer'; ?>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($v['status'] !== 'inactive'): ?>
                                    <form method="POST" onsubmit="return confirm('Désactiver ce véhicule ?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="vehicle_id" value="<?php echo $v['id']; ?>">
                                        <input type="hidden" name="status" value="inactive">
                                        <button type="submit" class="btn-reject"><i class="fas fa-ban"></i> Désactiver</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </tbody>
            </table>
        </div>

    </main>
</div>

<?php include INC_PATH . 'toast.php'; ?>
<script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
