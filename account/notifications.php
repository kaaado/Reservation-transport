<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
require_once CONF_PATH . 'database.php';

$database = new Database();
$pdo = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Initial Fetch for Notifications Page
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to resolve absolute path link based on notification content/role
if (!function_exists('getRoleDashboardPath')) {
    function getRoleDashboardPath($role) {
        if ($role === 'client') return URL_ROOT . 'client/dashboard.php';
        if ($role === 'transporter') return URL_ROOT . 'transporter/dashboard.php';
        if ($role === 'admin') return URL_ROOT . 'admin/dashboard.php';
        return URL_ROOT . 'auth/login.php';
    }
}
$dashPath = getRoleDashboardPath($_SESSION['role']);

if (!function_exists('resolveNotifLink')) {
    function resolveNotifLink($notif, $role) {
        if ($role === 'client') return '../client/reservations.php';
        if ($role === 'transporter') return '../transporter/requests.php';
        return '#';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centre de Notifications - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token']); ?>">
</head>
<body>

<div class="dashboard-layout">
    
    <!-- Unified Sidebar -->
    <?php include INC_PATH . 'sidebar.php'; ?>
    <main class="main-content">
        <?php include INC_PATH . 'topbar.php'; ?>
        
        <div class="page-header">
            <div>
                <h1>Centre de Notifications</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Historique complet de vos alertes systèmes.</p>
            </div>
            <div style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 15px;">
                <a href="<?php echo $dashPath; ?>" class="btn-secondary" style="width: auto; padding: 10px 20px;"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a>
                <button onclick="markAsRead('all'); location.reload();" class="btn-primary" style="width: auto;"><i class="fas fa-check-double"></i> Tout marquer lu</button>
            </div>
        </div>

        <?php 
        $table_headers = ['#', '', 'Message', 'Date', 'Action'];
        $table_data = [];
        $i = 1;
        foreach($notifications as $notif) {
            
            $isUnread = $notif['status'] === 'unread';
            $iconColor = $isUnread ? '#ff8c00' : '#94a3b8';
            $iconBg = $isUnread ? 'rgba(255,140,0,0.1)' : 'rgba(255,255,255,0.05)';
            $icon = "<div style='display:flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:50%; background:$iconBg;'><i class='fas fa-bell' style='color:$iconColor;'></i></div>";
            
            $msgColor = $isUnread ? 'white' : '#cbd5e1';
            $fontWeight = $isUnread ? '600' : '400';
            $msgHTML = "<div style='color:$msgColor; font-weight:$fontWeight;'>" . htmlspecialchars($notif['message']) . "</div>";
            
            $dateHTML = "<div style='color:#64748b; font-size:13px;'><i class='far fa-clock'></i> " . date('d/m/Y H:i', strtotime($notif['created_at'])) . "</div>";
            
            $link = resolveNotifLink($notif, $_SESSION['role']);
            $actionBtn = "<a href='#' onclick=\"markAndNavigate({$notif['id']}, '{$link}', event)\" class='btn-small'><i class='fas fa-eye'></i> Afficher</a>";
            $table_data[] = [$i++, $icon, $msgHTML, $dateHTML, $actionBtn];
        }
        $table_empty_message = "Aucune notification enregistrée.";
        
        include INC_PATH . 'table_component.php'; 
        ?>

    </main>
</div>

<!-- Essential JS for API binding -->
<script src="<?php echo JS_URL; ?>dashboard.js"></script>
<script>
    async function markAndNavigate(id, url, event) {
        event.preventDefault();
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', token);
        
        await fetch(`../api/notifications.php?action=mark_read`, {
            method: 'POST',
            body: formData
        });
        
        window.location.href = url;
    }
</script>
</body>
</html>
