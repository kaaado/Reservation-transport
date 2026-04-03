<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['role'] ?? 'guest';
$current_page = basename($_SERVER['PHP_SELF']);

if (!function_exists('getRoleDashboardPath')) {
    function getRoleDashboardPath($role) {
        if ($role === 'client') return URL_ROOT . 'client/dashboard.php';
        if ($role === 'transporter') return URL_ROOT . 'transporter/dashboard.php';
        if ($role === 'admin') return URL_ROOT . 'admin/dashboard.php';
        return URL_ROOT . 'auth/login.php';
    }
}
$dashPath = getRoleDashboardPath($role);
?>
<aside class="sidebar">
    <a href="<?php echo $dashPath; ?>" class="logo">Cargo<span>Connect</span></a>
    
    <ul class="nav-menu">
        <li><a href="<?php echo $dashPath; ?>" <?php echo ($current_page == 'dashboard.php') ? 'class="active"' : ''; ?>><i class="fas fa-home"></i> Tableau de bord</a></li>
        
        <?php if ($role === 'client' || $role === 'admin'): ?>
            <li><a href="<?php echo URL_ROOT; ?>client/request_transport.php" <?php echo ($current_page == 'request_transport.php') ? 'class="active"' : ''; ?>><i class="fas fa-truck-fast"></i> Demander un transport</a></li>
            <li><a href="<?php echo URL_ROOT; ?>client/reservations.php" <?php echo ($current_page == 'reservations.php' || $current_page == 'reservation_details.php') ? 'class="active"' : ''; ?>><i class="fas fa-list-check"></i> Mes Réservations</a></li>
        <?php endif; ?>
        
        <?php if ($role === 'transporter' || $role === 'admin'): ?>
            <li><a href="<?php echo URL_ROOT; ?>transporter/requests.php" <?php echo ($current_page == 'requests.php') ? 'class="active"' : ''; ?>><i class="fas fa-list-ul"></i> Demandes en attente</a></li>
            <li><a href="<?php echo URL_ROOT; ?>transporter/jobs.php" <?php echo ($current_page == 'jobs.php') ? 'class="active"' : ''; ?>><i class="fas fa-route"></i> Mes Missions</a></li>
            <li><a href="<?php echo URL_ROOT; ?>transporter/vehicles.php" <?php echo ($current_page == 'vehicles.php' || $current_page == 'vehicle_form.php') ? 'class="active"' : ''; ?>><i class="fas fa-truck"></i> Mes Véhicules</a></li>
            <li><a href="<?php echo URL_ROOT; ?>transporter/earnings.php" <?php echo ($current_page == 'earnings.php') ? 'class="active"' : ''; ?>><i class="fas fa-wallet"></i> Mes Revenus</a></li>
        <?php endif; ?>
        
        <?php if ($role === 'admin'): ?>
            <li><a href="<?php echo URL_ROOT; ?>admin/users.php" <?php echo ($current_page == 'users.php') ? 'class="active"' : ''; ?>><i class="fas fa-users"></i> Utilisateurs</a></li>
            <li><a href="<?php echo URL_ROOT; ?>admin/reservation_logs.php" <?php echo ($current_page == 'reservation_logs.php') ? 'class="active"' : ''; ?>><i class="fas fa-history"></i> Journaux d'Audit</a></li>
            <li><a href="<?php echo URL_ROOT; ?>admin/reports.php" <?php echo ($current_page == 'reports.php') ? 'class="active"' : ''; ?>><i class="fas fa-chart-bar"></i> Rapports</a></li>
        <?php endif; ?>
        
        <li><a href="<?php echo URL_ROOT; ?>account/notifications.php" <?php echo ($current_page == 'notifications.php') ? 'class="active"' : ''; ?>><i class="fas fa-bell"></i> Notifications</a></li>
        <li><a href="<?php echo URL_ROOT; ?>account/profile.php" <?php echo ($current_page == 'profile.php') ? 'class="active"' : ''; ?>><i class="fas fa-id-badge"></i> Mon Profil</a></li>
        <li><a href="<?php echo URL_ROOT; ?>account/settings.php" <?php echo ($current_page == 'settings.php') ? 'class="active"' : ''; ?>><i class="fas fa-cog"></i> Paramètres</a></li>
    </ul>

    <div class="user-profile">
        <a href="<?php echo URL_ROOT; ?>logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</aside>
