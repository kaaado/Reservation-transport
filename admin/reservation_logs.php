<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('admin');

require_once CONF_PATH . 'database.php';
$db = new Database();
$pdo = $db->getConnection();

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$totalStmt = $pdo->query("SELECT COUNT(*) FROM reservation_logs");
$totalLogs = $totalStmt->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

$stmt = $pdo->prepare("
    SELECT l.*, r.pickup_location, r.destination, u.name as user_name, u.role as user_role 
    FROM reservation_logs l
    JOIN reservations r ON l.reservation_id = r.id
    JOIN users u ON l.changed_by = u.id
    ORDER BY l.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getStatusBadgeClass($status)
{
    if (!$status)
        return '';
    switch ($status) {
        case 'pending':
            return 'status-pending';
        case 'accepted':
            return 'status-accepted';
        case 'in_progress':
            return 'status-in_progress';
        case 'completed':
            return 'status-completed';
        case 'cancelled':
            return 'status-cancelled';
        case 'rejected':
            return 'status-cancelled';
        default:
            return '';
    }
}
function getStatusLabel($status)
{
    if (!$status)
        return 'Création';
    switch ($status) {
        case 'pending':
            return 'En attente';
        case 'accepted':
            return 'Accepté';
        case 'in_progress':
            return 'En cours';
        case 'completed':
            return 'Terminé';
        case 'cancelled':
            return 'Annulé';
        case 'rejected':
            return 'Rejeté';
        default:
            return $status;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journaux d'Audit Réservations - Admin CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                    <h1>Journaux des Réservations (Audit Logs)</h1>
                    <p style="color: #94a3b8; margin-top: 5px;">Suivi détaillé de toutes les transitions de statut.</p>
                </div>
            </div>

            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>ID Réservation</th>
                            <th>Itinéraire</th>
                            <th>Utilisateur (Auteur)</th>
                            <th>Transition</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #94a3b8;">Aucun journal disponible.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td style="color: #cbd5e1;"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td><strong>#<?php echo $log['reservation_id']; ?></strong></td>
                                    <td style="font-size: 13px;">
                                        <?php echo htmlspecialchars(substr($log['pickup_location'], 0, 15)) . '...'; ?> <i
                                            class="fas fa-arrow-right" style="color: #64748b; margin: 0 5px;"></i>
                                        <?php echo htmlspecialchars(substr($log['destination'], 0, 15)) . '...'; ?>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($log['user_name']); ?></div>
                                        <span
                                            style="font-size: 11px; color: #94a3b8; text-transform: uppercase;"><?php echo $log['user_role']; ?></span>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <?php if ($log['old_status']): ?>
                                                <span
                                                    class="status-badge <?php echo getStatusBadgeClass($log['old_status']); ?>"><?php echo getStatusLabel($log['old_status']); ?></span>
                                                <i class="fas fa-long-arrow-alt-right" style="color: #64748b;"></i>
                                            <?php endif; ?>
                                            <span
                                                class="status-badge <?php echo getStatusBadgeClass($log['new_status']); ?>"><?php echo getStatusLabel($log['new_status']); ?></span>
                                        </div>
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