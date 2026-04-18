<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('transporter');

require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'vehicle.php';
require_once FUNC_PATH . 'reservation.php';
require_once FUNC_PATH . 'earning.php';

$db = new Database();
$pdo = $db->getConnection();
$transporter_id = $_SESSION['user_id'];

// MIDDLEWARE: Force contract signature
require_once INC_PATH . 'contract_middleware.php';
checkTransporterContract($pdo);

// Get statistics
$vehicles = getVehiclesByTransporter($transporter_id, $pdo);
$active_vehicles = count(array_filter($vehicles, fn($v) => $v['status'] === 'active'));

$pending_requests = count(getPendingRequests($pdo));

$total_earnings = calculateEarnings($transporter_id, $pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Transporteur - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
</head>
<body>

<div class="dashboard-layout">
    <?php include INC_PATH . 'sidebar.php'; ?>
    <main class="main-content">
        <?php include INC_PATH . 'topbar.php'; ?>
        
        <div class="page-header">
            <div>
                <h1>Aperçu Transporteur</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Gérez vos missions et véhicules.</p>
            </div>
            <div>
                <a href="<?php echo URL_ROOT; ?>transporter/vehicle_form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter un véhicule</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-truck"></i></div>
                <div class="stat-details">
                    <h3>Véhicules Actifs</h3>
                    <div class="num"><?php echo $active_vehicles; ?></div>
                </div>
            </div>
            
            <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='requests.php'">
                <div class="stat-icon orange"><i class="fas fa-list"></i></div>
                <div class="stat-details">
                    <h3>Demandes en Attente</h3>
                    <div class="num"><?php echo $pending_requests; ?></div>
                </div>
            </div>

            <div class="stat-card" style="cursor: pointer;" onclick="window.location.href='earnings.php'">
                <div class="stat-icon green"><i class="fas fa-wallet"></i></div>
                <div class="stat-details">
                    <h3>Revenus du mois</h3>
                    <div class="num"><?php echo number_format($total_earnings, 2); ?> DA</div>
                </div>
            </div>
        </div>
        
        <div class="recent-section" style="margin-top: 30px;">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2>Mes Véhicules</h2>
                <a href="vehicles.php" style="color: #ff8c00; text-decoration: none; font-size: 14px;">Voir tout <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <?php if (empty($vehicles)): ?>
                <div style="text-align: center; padding: 40px; background: rgba(30, 41, 59, 0.5); border-radius: 12px; border: 1px dashed rgba(255, 255, 255, 0.1);">
                    <i class="fas fa-truck" style="font-size: 40px; color: #64748b; margin-bottom: 15px;"></i>
                    <p style="color: #94a3b8;">Aucun véhicule enregistré.</p>
                    <a href="vehicle_form.php" class="btn btn-primary" style="margin-top: 15px; display: inline-block;">Ajouter un véhicule</a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Immatriculation</th>
                                <th>Type</th>
                                <th>Capacité</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($vehicles, 0, 3) as $vehicle): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($vehicle['plate_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['vehicle_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo number_format($vehicle['capacity'], 2); ?> T</td>
                                    <td>
                                        <?php if ($vehicle['status'] === 'active'): ?>
                                            <span class="badge badge-success">Actif</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include INC_PATH . 'toast.php'; ?>
<script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
