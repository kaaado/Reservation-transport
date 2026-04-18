<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('admin');

require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'admin.php';

$db = new Database();
$pdo = $db->getConnection();

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Ensure table exists
ensureAuditLogTable($pdo);

// Get total count for pagination
$totalStmt = $pdo->query("SELECT COUNT(*) FROM admin_audit_logs");
$totalLogs = $totalStmt->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

// Fetch logs
$stmt = $pdo->prepare("
    SELECT al.*, u.name as admin_name, u.email as admin_email 
    FROM admin_audit_logs al
    JOIN users u ON al.admin_id = u.id
    ORDER BY al.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getActionIconAndColor($action) {
    if (strpos($action, 'update_user_status') !== false) return ['icon' => 'fas fa-user-shield', 'color' => '#3b82f6'];
    if (strpos($action, 'verify_user_id') !== false) return ['icon' => 'fas fa-id-card', 'color' => '#10b981'];
    if (strpos($action, 'unverify_user_id') !== false) return ['icon' => 'fas fa-id-card', 'color' => '#ef4444'];
    if (strpos($action, 'delete_user') !== false) return ['icon' => 'fas fa-user-times', 'color' => '#ef4444'];
    if (strpos($action, 'update_user_profile') !== false) return ['icon' => 'fas fa-user-edit', 'color' => '#8b5cf6'];
    if (strpos($action, 'update_vehicle_status') !== false) return ['icon' => 'fas fa-truck-moving', 'color' => '#f59e0b'];
    if (strpos($action, 'confirm_commission') !== false) return ['icon' => 'fas fa-money-check-alt', 'color' => '#10b981'];
    if (strpos($action, 'toggle_single_commission') !== false) return ['icon' => 'fas fa-hand-holding-usd', 'color' => '#8b5cf6'];
    
    return ['icon' => 'fas fa-cog', 'color' => '#64748b'];
}

function getActionLabel($action) {
    switch ($action) {
        case 'update_user_status': return 'Modification Statut Utilisateur';
        case 'verify_user_id': return 'Vérification Identité Accordée';
        case 'unverify_user_id': return 'Vérification Identité Révoquée';
        case 'delete_user': return 'Suppression Utilisateur';
        case 'update_user_profile': return 'Modification Profil Utilisateur';
        case 'update_vehicle_status': return 'Changement Statut Véhicule';
        case 'confirm_commission_payment': return 'Validation Bloc Commissions';
        case 'toggle_single_commission': return 'Ajustement Commission Unitaire';
        default: return ucfirst(str_replace('_', ' ', $action));
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traçabilité Administrative - Admin CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    <style>
        .action-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #fff;
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
                    <h1>Traçabilité Administrative (Audit)</h1>
                    <p style="color: #94a3b8; margin-top: 5px;">Historique exhaustif de toutes les actions de gouvernance (blocages, vérifications, paiements).</p>
                </div>
            </div>

            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Opérateur (Admin)</th>
                            <th>Action Réalisée</th>
                            <th>Cible</th>
                            <th>Détails & Valeurs</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #94a3b8;">Aucune action enregistrée pour le moment.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): 
                                $style = getActionIconAndColor($log['action']);
                            ?>
                                <tr>
                                    <td style="color: #cbd5e1; font-size:12px;"><i class="far fa-clock" style="color:#64748b; margin-right:5px;"></i><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td>
                                        <div style="font-weight:600; color:#f8fafc;"><?php echo htmlspecialchars($log['admin_name']); ?></div>
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div class="action-icon" style="background: <?php echo $style['color']; ?>20; border: 1px solid <?php echo $style['color']; ?>50;">
                                                <i class="<?php echo $style['icon']; ?>" style="color: <?php echo $style['color']; ?>;"></i>
                                            </div>
                                            <span style="font-size:13px; font-weight:500; color:<?php echo $style['color']; ?>;"><?php echo getActionLabel($log['action']); ?></span>
                                        </div>
                                    </td>
                                    <td style="font-size:12px; text-transform:uppercase; color:#94a3b8; letter-spacing:0.5px;">
                                        <?php echo htmlspecialchars($log['target_type']); ?> <span style="color:#f8fafc; font-weight:bold;">#<?php echo htmlspecialchars($log['target_id'] ?: 'N/A'); ?></span>
                                    </td>
                                    <td style="font-size:13px; color:#cbd5e1;">
                                        <?php echo htmlspecialchars($log['details'] ?: '—'); ?>
                                    </td>
                                    <td style="font-size:11px; color:#64748b; font-family:monospace;">
                                        <?php echo htmlspecialchars($log['ip_address'] ?: '—'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>"
                            class="btn <?php echo ($page == $i) ? 'btn-primary' : 'btn-outline'; ?>"
                            style="width: 40px; height: 40px; justify-content: center; padding: 0;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
