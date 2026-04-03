<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('transporter');

require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'earning.php';

$db = new Database();
$pdo = $db->getConnection();
$transporter_id = $_SESSION['user_id'];

$month_earnings = calculateEarnings($transporter_id, $pdo);
$total_earnings = calculateTotalEarnings($transporter_id, $pdo);
$history = getEarningsHistory($transporter_id, $pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Revenus - CargoConnect</title>
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
        
        <div class="page-header">
            <div>
                <h1>Tableau des Revenus</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Consultez vos statistiques de gains pour toutes les missions complétées.</p>
            </div>
        </div>

        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); margin-top: 20px;">
            <div class="stat-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05)); border: 1px solid rgba(16, 185, 129, 0.2);">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.2); color: #10b981;"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-details">
                    <h3>Revenus du mois (<?php echo date('F'); ?>)</h3>
                    <div class="num" style="color: #10b981;"><?php echo number_format($month_earnings, 2); ?> DA</div>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(59, 130, 246, 0.05)); border: 1px solid rgba(59, 130, 246, 0.2);">
                <div class="stat-icon" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;"><i class="fas fa-wallet"></i></div>
                <div class="stat-details">
                    <h3>Revenus Totaux</h3>
                    <div class="num" style="color: #3b82f6;"><?php echo number_format($total_earnings, 2); ?> DA</div>
                </div>
            </div>
        </div>

        <div class="recent-section" style="margin-top: 30px;">
            <div class="section-header">
                <h2>Historique des paiements</h2>
            </div>
            
            <?php if (empty($history)): ?>
                <div style="text-align: center; padding: 40px; background: rgba(30, 41, 59, 0.5); border-radius: 12px; border: 1px dashed rgba(255, 255, 255, 0.1);">
                    <i class="fas fa-file-invoice-dollar" style="font-size: 40px; color: #64748b; margin-bottom: 15px;"></i>
                    <p style="color: #94a3b8;">Aucun historique de revenu. Terminez des missions pour voir vos gains ici.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date d'encaissement</th>
                                <th>Trajet</th>
                                <th>Montant</th>
                                <th>Statut du paiement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $earning): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($earning['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($earning['pickup_location'] . ' → ' . $earning['destination'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td style="font-weight: 700; color: #10b981;">+ <?php echo number_format($earning['amount'], 2); ?> DA</td>
                                    <td><span class="badge badge-success">Crédité</span></td>
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
