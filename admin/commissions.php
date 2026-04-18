<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('admin');

require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'admin.php';

$db = new Database();
$pdo = $db->getConnection();

// Handle batch payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: commissions.php"); exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'confirm_payment') {
        $transporter_id = (int)($_POST['transporter_id'] ?? 0);
        $result = markBatchPaid($transporter_id, $pdo);
        if ($result) {
            sendAdminNotification($transporter_id, "Votre paiement de commissions a été confirmé par l'administration. Votre compte est débloqué.", $pdo);
            logAdminAction($_SESSION['user_id'], 'confirm_commission_payment', 'transporter', $transporter_id, "Batch payment confirmed", $pdo);
            $_SESSION['success'] = "Paiement confirmé — transporteur #$transporter_id débloqué.";
        } else {
            $_SESSION['error'] = "Échec de la confirmation.";
        }
        header("Location: commissions.php" . (isset($_GET['detail']) ? "?detail=".$_GET['detail'] : "")); exit;
    }

    if ($action === 'toggle_single') {
        $res_id = (int)($_POST['reservation_id'] ?? 0);
        $new_status = (int)($_POST['is_paid'] ?? 0);
        if (updateReservationCommission($res_id, $new_status, $pdo)) {
            $statusLabel = $new_status ? 'paid' : 'unpaid';
            logAdminAction($_SESSION['user_id'], 'toggle_single_commission', 'reservation', $res_id, "Commission status changed to: $statusLabel", $pdo);
            $_SESSION['success'] = "Statut mis à jour.";
        } else {
            $_SESSION['error'] = "Erreur de mise à jour.";
        }
        header("Location: commissions.php?detail=" . (int)$_GET['detail']); exit;
    }
}

$unpaid = getUnpaidCommissions($pdo);
$totalUnpaid = array_sum(array_column($unpaid, 'total_owed'));
$totalBlocked = count(array_filter($unpaid, fn($t) => $t['unpaid_count'] >= 5));

// Detail view
$detailTransporter = null;
$detailReservations = [];
if (isset($_GET['detail'])) {
    $detailId = (int)$_GET['detail'];
    $detailTransporter = getUserById($detailId, $pdo);
    $detailReservations = getTransporterUnpaidReservations($detailId, $pdo);
}

$ripAccount = defined('APP_RIP_ACCOUNT') ? APP_RIP_ACCOUNT : '07999999999999999999';
$commRate = defined('APP_COMMISSION') ? (APP_COMMISSION * 100) : 20;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commissions & Paiements - Admin CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    <style>
        .comm-stats {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px; margin-top: 20px;
        }
        .comm-stat {
            background: rgba(30,41,59,0.6); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px; padding: 20px; position: relative; overflow: hidden;
        }
        .comm-stat::before {
            content: ''; position: absolute; top: 0; left: 0;
            width: 100%; height: 3px;
        }
        .comm-stat .cs-icon {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; margin-bottom: 12px;
        }
        .comm-stat .cs-val { font-size: 24px; font-weight: 800; color: #f8fafc; }
        .comm-stat .cs-label { font-size: 12px; color: #94a3b8; margin-top: 4px; text-transform: uppercase; font-weight: 600; }
        .cs-red .cs-icon { background: rgba(239,68,68,0.15); color: #ef4444; }
        .cs-red::before { background: #ef4444; }
        .cs-orange .cs-icon { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .cs-orange::before { background: #f59e0b; }
        .cs-green .cs-icon { background: rgba(16,185,129,0.15); color: #10b981; }
        .cs-green::before { background: #10b981; }

        .rip-card {
            background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(255,140,0,0.04));
            border: 1px solid rgba(245,158,11,0.2);
            border-radius: 12px; padding: 16px 20px; margin-top: 20px;
            display: flex; align-items: center; gap: 15px;
        }
        .rip-card i { font-size: 24px; color: #f59e0b; }
        .rip-card .rip-num { font-family: 'Courier New', monospace; color: #fbbf24; font-size: 16px; font-weight: 700; letter-spacing: 2px; }
        .rip-card .rip-label { font-size: 12px; color: #94a3b8; }

        .transporter-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; margin-top: 20px; }
        .tr-card {
            background: rgba(30,41,59,0.6); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px; padding: 20px; transition: all 0.3s;
        }
        .tr-card:hover { border-color: rgba(255,140,0,0.3); transform: translateY(-2px); }
        .tr-card.blocked { border-color: rgba(239,68,68,0.3); }
        .tr-header { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
        .tr-avatar {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px;
        }
        .tr-avatar.ok { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .tr-avatar.danger { background: rgba(239,68,68,0.15); color: #ef4444; }
        .tr-name { font-weight: 700; color: #f8fafc; font-size: 15px; }
        .tr-meta { font-size: 11px; color: #64748b; }
        .tr-stats { display: flex; gap: 16px; margin-bottom: 14px; }
        .tr-stat-item { text-align: center; flex: 1; }
        .tr-stat-item .ts-val { font-size: 18px; font-weight: 800; }
        .tr-stat-item .ts-label { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.3px; }
        .tr-actions { display: flex; gap: 8px; align-items: center; }
        .blocked-flag {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 11px; font-weight: 700; padding: 3px 10px;
            border-radius: 20px; background: rgba(239,68,68,0.15);
            color: #ef4444; border: 1px solid rgba(239,68,68,0.3);
        }

        .table-container { 
            background: rgba(30,41,59,0.6); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px; overflow: hidden; margin-top: 16px;
        }
        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table thead th {
            padding: 12px 14px; text-align: left; font-size: 11px;
            text-transform: uppercase; color: #94a3b8; font-weight: 600;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            background: rgba(15,23,42,0.4);
        }
        .detail-table tbody td {
            padding: 12px 14px; font-size: 13px; color: #cbd5e1;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }

        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7); z-index: 100;
            align-items: center; justify-content: center; backdrop-filter: blur(4px);
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #1e293b; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px; padding: 30px; max-width: 700px; width: 90%;
            max-height: 80vh; overflow-y: auto;
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
                <h1><i class="fas fa-money-bill-wave" style="color:#f59e0b;"></i> Commissions & Paiements</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Gérez les commissions de la plateforme et validez les reçus de paiement.</p>
            </div>
        </div>

        <!-- KPIs -->
        <div class="comm-stats">
            <div class="comm-stat cs-red">
                <div class="cs-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="cs-val"><?php echo number_format($totalUnpaid, 0); ?> DA</div>
                <div class="cs-label">Total Commissions Impayées</div>
            </div>
            <div class="comm-stat cs-orange">
                <div class="cs-icon"><i class="fas fa-users"></i></div>
                <div class="cs-val"><?php echo count($unpaid); ?></div>
                <div class="cs-label">Transporteurs avec Dettes</div>
            </div>
            <div class="comm-stat cs-red">
                <div class="cs-icon"><i class="fas fa-lock"></i></div>
                <div class="cs-val"><?php echo $totalBlocked; ?></div>
                <div class="cs-label">Transporteurs Bloqués (≥5)</div>
            </div>
            <div class="comm-stat cs-green">
                <div class="cs-icon"><i class="fas fa-percentage"></i></div>
                <div class="cs-val"><?php echo $commRate; ?>%</div>
                <div class="cs-label">Taux Commission</div>
            </div>
        </div>

        <!-- RIP Account Info -->
        <div class="rip-card">
            <i class="fas fa-university"></i>
            <div>
                <div class="rip-label">Compte RIP Plateforme (CCP)</div>
                <div class="rip-num"><?php echo $ripAccount; ?></div>
            </div>
        </div>

        <!-- Transporter Cards -->
        <?php if (empty($unpaid)): ?>
            <div style="text-align:center; padding:50px; background:rgba(30,41,59,0.5); border-radius:14px; border:1px dashed rgba(255,255,255,0.1); margin-top:24px;">
                <i class="fas fa-check-circle" style="font-size:50px; color:#10b981; margin-bottom:15px;"></i>
                <h3 style="color:#f8fafc;">Toutes les commissions sont réglées</h3>
                <p style="color:#94a3b8; margin-top:10px;">Aucun transporteur n'a de commissions impayées.</p>
            </div>
        <?php else: ?>
            <div class="transporter-cards">
                <?php foreach ($unpaid as $t): 
                    $isBlocked = $t['unpaid_count'] >= 5;
                ?>
                <div class="tr-card <?php echo $isBlocked ? 'blocked' : ''; ?>">
                    <div class="tr-header">
                        <div class="tr-avatar <?php echo $isBlocked ? 'danger' : 'ok'; ?>">
                            <?php echo strtoupper(substr($t['name'], 0, 1)); ?>
                        </div>
                        <div style="flex:1;">
                            <div class="tr-name"><?php echo htmlspecialchars($t['name']); ?></div>
                            <div class="tr-meta"><?php echo htmlspecialchars($t['email']); ?> · <?php echo htmlspecialchars($t['phone']); ?></div>
                        </div>
                        <?php if ($isBlocked): ?>
                            <span class="blocked-flag"><i class="fas fa-lock"></i> BLOQUÉ</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tr-stats">
                        <div class="tr-stat-item">
                            <div class="ts-val" style="color:#ef4444;"><?php echo $t['unpaid_count']; ?></div>
                            <div class="ts-label">Missions Impayées</div>
                        </div>
                        <div class="tr-stat-item">
                            <div class="ts-val" style="color:#fbbf24;"><?php echo number_format($t['total_owed'], 0); ?> DA</div>
                            <div class="ts-label">Montant Dû</div>
                        </div>
                    </div>

                    <div class="tr-actions">
                        <a href="commissions.php?detail=<?php echo $t['transporter_id']; ?>" class="btn btn-outline btn-sm" style="flex:1; text-align:center; border-color:rgba(255,255,255,0.1); font-size:12px;"><i class="fas fa-eye"></i> Détails</a>
                        <form method="POST" onsubmit="return confirm('Confirmer le paiement de <?php echo htmlspecialchars($t['name']); ?> ? Cela marquera TOUTES ses commissions comme réglées et débloquera son compte.');" style="flex:1;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="action" value="confirm_payment">
                            <input type="hidden" name="transporter_id" value="<?php echo $t['transporter_id']; ?>">
                            <button type="submit" class="btn btn-primary btn-sm" style="width:100%; font-size:12px;"><i class="fas fa-check-double"></i> Confirmer Reçu</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Detail View -->
        <?php if ($detailTransporter): 
            // Fetch both paid and unpaid for the detail view to allow "Toggling"
            $stmt = $pdo->prepare("
                SELECT r.id, r.pickup_location, r.destination, r.price, r.platform_commission, r.is_commission_paid, r.created_at 
                FROM reservations r
                JOIN vehicles v ON r.vehicle_id = v.id
                WHERE v.owner_id = ? AND r.status = 'completed'
                ORDER BY r.created_at DESC LIMIT 50
            ");
            $stmt->execute([$detailTransporter['id']]);
            $allRes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="modal-overlay active" id="detailModal">
            <div class="modal-box">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="color:#f8fafc; margin:0;"><i class="fas fa-file-invoice-dollar" style="color:#f59e0b;"></i> Historique & Commissions — <?php echo htmlspecialchars($detailTransporter['name']); ?></h3>
                    <a href="commissions.php" style="color:#64748b; font-size:18px;"><i class="fas fa-times"></i></a>
                </div>
                <div class="table-container" style="margin-top:0;">
                    <table class="detail-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Itinéraire</th>
                                <th>Commission</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allRes)): ?>
                                <tr><td colspan="5" style="text-align:center; padding:20px;">Aucune mission terminée.</td></tr>
                            <?php else: ?>
                                <?php foreach ($allRes as $dr): ?>
                                <tr>
                                    <td><strong>#<?php echo $dr['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($dr['pickup_location']); ?> → <?php echo htmlspecialchars($dr['destination']); ?></td>
                                    <td style="color:#ef4444; font-weight:700;"><?php echo number_format($dr['platform_commission'], 2); ?> DA</td>
                                    <td>
                                        <?php if ($dr['is_commission_paid']): ?>
                                            <span style="color:#10b981; font-weight:600;"><i class="fas fa-check-circle"></i> Payé</span>
                                        <?php else: ?>
                                            <span style="color:#f59e0b; font-weight:600;"><i class="fas fa-clock"></i> Impayé</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="toggle_single">
                                            <input type="hidden" name="reservation_id" value="<?php echo $dr['id']; ?>">
                                            <input type="hidden" name="is_paid" value="<?php echo $dr['is_commission_paid'] ? 0 : 1; ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" style="padding:4px 8px; font-size:10px;">
                                                <?php echo $dr['is_commission_paid'] ? 'Rendre Impayé' : 'Marquer Payé'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div style="text-align:center; margin-top:20px; display:flex; gap:12px; justify-content:center;">
                    <form method="POST" onsubmit="return confirm('Marquer TOUTES les missions impayées comme réglées ?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="confirm_payment">
                        <input type="hidden" name="transporter_id" value="<?php echo $detailTransporter['id']; ?>">
                        <button type="submit" class="btn btn-primary" style="padding:12px 28px;"><i class="fas fa-check-double"></i> Régler toutes les dettes</button>
                    </form>
                    <a href="commissions.php" class="btn btn-outline" style="padding:12px 28px; border-color:rgba(255,255,255,0.1);">Fermer</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php include INC_PATH . 'toast.php'; ?>
<script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
