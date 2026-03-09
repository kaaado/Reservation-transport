<?php
// User Profile Info
$userName = $_SESSION['name'] ?? 'Utilisateur';
$userRole = ucfirst($_SESSION['role'] ?? '');
$userInitials = strtoupper(substr(trim($userName), 0, 2));
?>
<!-- Topbar Component -->
<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" id="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    </div>
    
    <div class="topbar-right">
        <!-- Notification Bell -->
        <div class="notification-bell dropdown-wrapper">
            <i class="fas fa-bell"></i>
            <span class="badge pulse" style="display: none;">0</span>
            
            <div class="dropdown-menu">
                <div class="dropdown-header">
                    Notifications Systèmes
                    <button onclick="markAsRead('all')" style="background:none;border:none;color:#ff8c00;font-size:11px;cursor:pointer;">Tout marquer lu</button>
                </div>
                <div class="dropdown-content" id="notif-list-container" style="max-height: 250px; overflow-y: auto;">
                    <div class="dropdown-item" style="color: #64748b;justify-content:center;padding:20px;">
                        Chargement...
                    </div>
                </div>
                <div class="dropdown-footer" style="padding:10px;text-align:center;border-top:1px solid rgba(255,255,255,0.05);">
                    <a href="<?php echo URL_ROOT; ?>account/notifications.php" style="color:#ff8c00;text-decoration:none;font-size:12px;">Voir tout</a>
                </div>
            </div>
        </div>
        
        <!-- User Profile -->
        <div class="user-profile-top dropdown-wrapper">
            <div class="user-info">
                <span class="name"><?php echo htmlspecialchars($userName); ?></span>
                <span class="role"><?php echo htmlspecialchars($userRole); ?></span>
            </div>
            <div class="avatar" style="width: 42px; height: 42px; border-radius: 50%; background: rgba(255,140,0,0.15); border: 1px solid rgba(255,140,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; color: #ff8c00; letter-spacing: 1px;">
                <?php echo htmlspecialchars($userInitials); ?>
            </div>
            
            <div class="dropdown-menu">
                <div class="dropdown-header">Compte</div>
                <div class="dropdown-content">
                    <a href="<?php echo URL_ROOT; ?>account/profile.php" class="dropdown-item"><i class="fas fa-id-badge"></i> Mon Profil</a>
                    <a href="<?php echo URL_ROOT; ?>account/settings.php" class="dropdown-item"><i class="fas fa-cog"></i> Paramètres</a>
                    <div class="dropdown-divider"></div>
                    <a href="/Reservation-transport/logout.php" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </div>
    </div>
</div>
