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
        header("Location: reservations.php"); exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $res_id = (int)($_POST['reservation_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';
        $admin_id = $_SESSION['user_id'];

        $result = adminUpdateReservationStatus($res_id, $new_status, $admin_id, $pdo);
        if ($result === true) {
            $_SESSION['success'] = "Réservation #$res_id mise à jour → $new_status.";
        } else {
            $_SESSION['error'] = is_string($result) ? $result : "Échec de la mise à jour.";
        }
        header("Location: reservations.php"); exit;
    }
}

// Filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
];
$reservations = getAllReservations($pdo, $filters);

function getStatusBadgeAdmin($status) {
    return [
        'class' => getStatusBadgeClass($status),
        'label' => translateStatus($status),
        'icon' => match($status) {
            'pending' => 'fa-clock',
            'negotiation' => 'fa-handshake',
            'accepted' => 'fa-check',
            'in_progress' => 'fa-truck',
            'completed' => 'fa-check-double',
            'cancelled' => 'fa-ban',
            'rejected' => 'fa-times',
            default => 'fa-question',
        }
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Réservations - Admin CargoConnect</title>
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
        .res-table { width: 100%; border-collapse: collapse; }
        .res-table thead th {
            padding: 12px 14px; text-align: left; font-size: 11px;
            text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px;
            font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.06);
            background: rgba(15,23,42,0.4);
        }
        .res-table tbody td {
            padding: 12px 14px; border-bottom: 1px solid rgba(255,255,255,0.04);
            font-size: 13px; color: #cbd5e1; vertical-align: middle;
        }
        .res-table tbody tr { transition: background 0.2s; }
        .res-table tbody tr:hover { background: rgba(255,140,0,0.04); }
        .route-cell { display: flex; align-items: center; gap: 6px; }
        .route-cell i { color: #64748b; font-size: 10px; }
        .table-container { 
            background: rgba(30,41,59,0.6); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px; overflow: hidden; margin-top: 16px;
        }
        .results-count { font-size: 13px; color: #64748b; margin-top: 16px; }
        .status-form { display: flex; gap: 6px; align-items: center; }
        .status-form select {
            background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.1);
            color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 12px; outline: none;
        }
        .status-form button {
            background: rgba(255,140,0,0.15); border: 1px solid rgba(255,140,0,0.3);
            color: #f59e0b; border-radius: 6px; padding: 6px 10px; cursor: pointer;
            font-size: 12px; transition: all 0.2s;
        }
        .status-form button:hover { background: rgba(255,140,0,0.3); }
        .commission-tag {
            font-size: 11px; padding: 2px 8px; border-radius: 4px;
        }
        .commission-paid { background: rgba(16,185,129,0.15); color: #10b981; }
        .commission-unpaid { background: rgba(239,68,68,0.15); color: #ef4444; }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php include INC_PATH . 'sidebar.php'; ?>
    <main class="main-content">
        <?php include INC_PATH . 'topbar.php'; ?>

        <div class="page-header">
            <div>
                <h1>Gestion des Réservations</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Supervisez, filtrez et intervenez sur les réservations de la plateforme.</p>
            </div>
        </div>

        <!-- Premium Filter Bar -->
        <div class="filter-wrapper">
            <form method="GET" class="premium-filter" id="filterForm">
                <div class="filter-group">
                    <div class="filter-input-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Rechercher par client, lieu, ID..." value="<?php echo htmlspecialchars($filters['search']); ?>" onchange="this.form.submit()">
                    </div>
                    
                    <div class="filter-select-wrap">
                        <i class="fas fa-filter"></i>
                        <select name="status" onchange="this.form.submit()">
                            <option value="">Tous les statuts</option>
                            <option value="pending" <?php echo ($filters['status'] === 'pending') ? 'selected' : ''; ?>><?php echo translateStatus('pending'); ?></option>
                            <option value="negotiation" <?php echo ($filters['status'] === 'negotiation') ? 'selected' : ''; ?>><?php echo translateStatus('negotiation'); ?></option>
                            <option value="accepted" <?php echo ($filters['status'] === 'accepted') ? 'selected' : ''; ?>><?php echo translateStatus('accepted'); ?></option>
                            <option value="in_progress" <?php echo ($filters['status'] === 'in_progress') ? 'selected' : ''; ?>><?php echo translateStatus('in_progress'); ?></option>
                            <option value="completed" <?php echo ($filters['status'] === 'completed') ? 'selected' : ''; ?>><?php echo translateStatus('completed'); ?></option>
                            <option value="cancelled" <?php echo ($filters['status'] === 'cancelled') ? 'selected' : ''; ?>><?php echo translateStatus('cancelled'); ?></option>
                        </select>
                    </div>

                    <?php if ($filters['status'] || $filters['search']): ?>
                        <a href="reservations.php" class="btn-clear" title="Réinitialiser"><i class="fas fa-rotate-left"></i></a>
                    <?php endif; ?>
                </div>

                <!-- Active Filter Tags -->
                <div class="filter-tags">
                    <?php if ($filters['status']): ?>
                        <span class="filter-tag">Statut: <?php echo translateStatus($filters['status']); ?></span>
                    <?php endif; ?>
                    <?php if ($filters['search']): ?>
                        <span class="filter-tag">Recherche: "<?php echo htmlspecialchars($filters['search']); ?>"</span>
                    <?php endif; ?>
                    <span class="filter-count"><strong><?php echo count($reservations); ?></strong> résultats</span>
                </div>
            </form>
        </div>

        <style>
            .filter-wrapper { margin-top: 25px; margin-bottom: 20px; }
            .premium-filter { display: flex; flex-direction: column; gap: 12px; }
            .filter-group {
                display: flex; align-items: center; gap: 12px;
                background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(255,255,255,0.08);
                padding: 8px; border-radius: 14px; backdrop-filter: blur(10px);
            }
            .filter-input-wrap, .filter-select-wrap {
                position: relative; flex: 1; display: flex; align-items: center;
            }
            .filter-input-wrap i, .filter-select-wrap i {
                position: absolute; left: 14px; color: #64748b; font-size: 14px; pointer-events: none;
            }
            .filter-input-wrap input, .filter-select-wrap select {
                width: 100%; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1);
                border-radius: 10px; padding: 10px 14px 10px 40px; color: #f8fafc; font-size: 13px;
                transition: all 0.2s; outline: none;
            }
            .filter-input-wrap input:focus, .filter-select-wrap select:focus {
                border-color: #f59e0b; background: rgba(15, 23, 42, 0.9); box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
            }
            .btn-clear {
                width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;
                background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 10px;
                transition: all 0.2s; border: 1px solid rgba(239, 68, 68, 0.2);
            }
            .btn-clear:hover { background: rgba(239, 68, 68, 0.2); transform: rotate(-90deg); }
            .filter-tags { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-left: 5px; }
            .filter-tag {
                background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2);
                padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;
            }
            .filter-count { font-size: 12px; color: #64748b; margin-left: auto; }

            @media (max-width: 768px) {
                .filter-group { flex-direction: column; align-items: stretch; }
                .btn-clear { align-self: flex-end; width: 100%; }
            }
        </style>

        <div class="table-container">
            <table class="res-table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Client</th>
                        <th>Itinéraire</th>
                        <th>Date</th>
                        <th>Prix</th>
                        <th>Commission</th>
                        <th>Transporteur</th>
                        <th>Statut</th>
                        <th>Intervention</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                        <tr><td colspan="9" style="text-align:center; padding:40px; color:#64748b;">Aucune réservation trouvée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reservations as $res): 
                            $badge = getStatusBadgeAdmin($res['status']);
                        ?>
                        <tr>
                            <td><strong style="color:#f8fafc;">#<?php echo $res['id']; ?></strong></td>
                            <td>
                                <div style="font-weight:500; color:#f8fafc;"><?php echo htmlspecialchars($res['client_name']); ?></div>
                                <div style="font-size:11px; color:#64748b;"><?php echo htmlspecialchars($res['client_phone']); ?></div>
                            </td>
                            <td>
                                <div class="route-cell">
                                    <span><?php echo htmlspecialchars(mb_strimwidth($res['pickup_location'], 0, 18, '…')); ?></span>
                                    <i class="fas fa-arrow-right"></i>
                                    <span><?php echo htmlspecialchars(mb_strimwidth($res['destination'], 0, 18, '…')); ?></span>
                                </div>
                            </td>
                            <td style="font-size:12px; white-space:nowrap;"><?php echo date('d/m/Y H:i', strtotime($res['reservation_date'])); ?></td>
                            <td>
                                <?php if ($res['price'] > 0): ?>
                                    <strong style="color:#10b981;"><?php echo number_format($res['price'], 2); ?> DA</strong>
                                <?php else: ?>
                                    <span style="color:#64748b; font-style:italic;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($res['status'] === 'completed' && $res['platform_commission'] > 0): ?>
                                    <div style="font-size:12px; color:#ef4444; font-weight:600;"><?php echo number_format($res['platform_commission'], 2); ?> DA</div>
                                    <span class="commission-tag <?php echo $res['is_commission_paid'] ? 'commission-paid' : 'commission-unpaid'; ?>">
                                        <?php echo $res['is_commission_paid'] ? 'Payé' : 'Impayé'; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#64748b;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($res['transporter_name']): ?>
                                    <div style="font-size:12px; color:#fbbf24;"><?php echo htmlspecialchars($res['transporter_name']); ?></div>
                                    <div style="font-size:11px; color:#64748b;"><?php echo htmlspecialchars($res['plate_number'] ?? ''); ?></div>
                                <?php else: ?>
                                    <span style="color:#64748b; font-size:12px;">Non assigné</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $badge['class']; ?>" style="font-size:11px;">
                                    <i class="fas <?php echo $badge['icon']; ?>"></i> <?php echo $badge['label']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!in_array($res['status'], ['completed', 'cancelled', 'rejected'])): ?>
                                <form method="POST" class="status-form" onsubmit="return confirm('Confirmer le changement de statut pour #<?php echo $res['id']; ?> ?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                    <select name="status">
                                        <option value="pending" <?php echo $res['status']==='pending'?'selected':''; ?>><?php echo translateStatus('pending'); ?></option>
                                        <option value="accepted" <?php echo $res['status']==='accepted'?'selected':''; ?>><?php echo translateStatus('accepted'); ?></option>
                                        <option value="in_progress" <?php echo $res['status']==='in_progress'?'selected':''; ?>><?php echo translateStatus('in_progress'); ?></option>
                                        <option value="completed" <?php echo $res['status']==='completed'?'selected':''; ?>><?php echo translateStatus('completed'); ?></option>
                                        <option value="cancelled"><?php echo translateStatus('cancelled'); ?></option>
                                    </select>
                                    <button type="submit" title="Forcer la transition"><i class="fas fa-bolt"></i></button>
                                </form>
                                <?php else: ?>
                                    <span style="font-size:11px; color:#64748b;">—</span>
                                <?php endif; ?>
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
