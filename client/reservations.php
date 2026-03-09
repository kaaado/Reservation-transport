<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('client');

require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'reservation.php';

$database = new Database();
$pdo = $database->getConnection();

$reservations = getClientReservations($_SESSION['user_id'], $pdo);

if (!function_exists('getRoleDashboardPath')) {
    function getRoleDashboardPath($role) {
        if ($role === 'client') return URL_ROOT . 'client/dashboard.php';
        if ($role === 'transporter') return URL_ROOT . 'transporter/dashboard.php';
        if ($role === 'admin') return URL_ROOT . 'admin/dashboard.php';
        return URL_ROOT . 'auth/login.php';
    }
}
$dashPath = getRoleDashboardPath($_SESSION['role']);

if (!function_exists('getStatusBadgeClass')) {
    function getStatusBadgeClass($status) {
        switch($status) {
            case 'pending': return 'status-pending';
            case 'accepted': return 'status-accepted';
            case 'in_progress': return 'status-in_progress';
            case 'completed': return 'status-completed';
            case 'cancelled': return 'status-cancelled';
            default: return '';
        }
    }
}

if (!function_exists('getStatusLabel')) {
    function getStatusLabel($status) {
        switch($status) {
            case 'pending': return 'En attente';
            case 'accepted': return 'Accepté';
            case 'in_progress': return 'En cours';
            case 'completed': return 'Terminé';
            case 'cancelled': return 'Annulé';
            default: return $status;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    
    <!-- Security token for JavaScript Hooks -->
    <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token']); ?>">
</head>
<body>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <?php include INC_PATH . 'sidebar.php'; ?>
    <!-- Main Content -->
    <main class="main-content">
        <?php include INC_PATH . 'topbar.php'; ?>
        
        <div class="page-header">
            <div>
                <h1>Mes Réservations</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Consultez et suivez l'historique de vos expéditions.</p>
            </div>
            <a href="<?php echo $dashPath; ?>" class="btn-secondary" style="width: auto; padding: 10px 20px;"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a>
        </div>

        <?php 
        $table_headers = ['ID', 'Date demandée', 'Itinéraire', 'Marchandise', 'Transporteur', 'Statut', 'Action'];
        $table_data = [];
        $i = 1;
        foreach($reservations as $res) {
            $pickup = htmlspecialchars(substr($res['pickup_location'], 0, 20)) . '...';
            $dest = htmlspecialchars(substr($res['destination'], 0, 20)) . '...';
            $itineraire = "<div><strong><i class='fas fa-map-marker-alt' style='color: #64748b; width: 15px;'></i></strong> $pickup</div>
                           <div style='margin-top: 5px;'><strong><i class='fas fa-flag-checkered' style='color: #16a34a; width: 15px;'></i></strong> $dest</div>";

            $transporter = $res['transporter_name'] ? htmlspecialchars($res['transporter_name']) : "<span style='color: #94a3b8; font-style: italic;'>Non assigné</span>";
            
            $statusClass = getStatusBadgeClass($res['status']);
            $statusLabel = getStatusLabel($res['status']);
            $statusBadge = "<span class='status-badge $statusClass'>$statusLabel</span>";
            
            $actionBtn = "<a href='reservation_details.php?id={$res['id']}' class='btn-small'><i class='fas fa-eye'></i> Détails</a>";

            $table_data[] = [
                "#" . $i++,
                date('d/m/Y H:i', strtotime($res['reservation_date'])),
                $itineraire,
                htmlspecialchars($res['cargo_type']),
                $transporter,
                $statusBadge,
                $actionBtn
            ];
        }
        $table_empty_message = "Aucune réservation trouvée.";
        
        include INC_PATH . 'table_component.php'; 
        ?>

    </main>
</div>

<script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
