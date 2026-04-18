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

<script>
const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token']; ?>';
const URL_ROOT = '<?php echo URL_ROOT; ?>';

// Initialize Real-time SSE
let source = null;
let isUnloading = false;

window.addEventListener('beforeunload', () => {
    isUnloading = true;
    if (source) source.close();
});

if (!!window.EventSource) {
    source = new EventSource(URL_ROOT + 'api/notif_stream.php');
    
    source.onmessage = function(event) {
        const data = JSON.parse(event.data);
        updateNotificationUI(data);
    };

    source.onerror = function(e) {
        if (!isUnloading) {
            console.log("SSE Stream re-establishing connection...");
        }
    };
} else {
    // Fallback for very old browsers
    setInterval(async () => {
        const r = await fetch(URL_ROOT + 'api/notifications.php?action=check');
        const d = await r.json();
        const r2 = await fetch(URL_ROOT + 'api/notifications.php?action=fetch');
        const d2 = await r2.json();
        updateNotificationUI({ unread: d.unread, notifications: d2.notifications });
    }, 15000);
}

function updateNotificationUI(data) {
    // Update Badge
    const badge = document.querySelector('.notification-bell .badge');
    if (data.unread > 0) {
        badge.textContent = data.unread;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }

    // Update Dropdown Container if it exists
    const container = document.getElementById('notif-list-container');
    if (!container) return;

    if (!data.notifications || data.notifications.length === 0) {
        container.innerHTML = '<div class="dropdown-item" style="color:#64748b;justify-content:center;padding:20px;">Aucune notification</div>';
        return;
    }

    container.innerHTML = data.notifications.map(n => `
        <div class="dropdown-item ${n.status === 'unread' ? 'unread' : ''}" id="notif-${n.id}" style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; border-bottom:1px solid rgba(255,255,255,0.03);">
            <div class="notif-text" style="flex:1;">
                <p style="margin:0; font-size:12px; color:#cbd5e1; line-height:1.4;">${n.message}</p>
                <span style="font-size:10px; color:#64748b;">${new Date(n.created_at).toLocaleDateString()}</span>
            </div>
            <div class="notif-actions" style="display:flex; gap:5px;">
                ${n.status === 'unread' ? `<button onclick="markAsRead(${n.id}, event)" title="Marquer lu" style="background:none; border:none; color:#f59e0b; cursor:pointer; font-size:10px;"><i class="fas fa-circle"></i></button>` : ''}
                <button onclick="deleteNotif(${n.id}, event)" title="Supprimer" style="background:none; border:none; color:#64748b; cursor:pointer; font-size:10px;"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `).join('');

    // If we are on account/notifications.php, we might want to refresh the list there too if count changed
    if (typeof syncNotificationPage === 'function') {
        syncNotificationPage(data);
    }
}

async function markAsRead(id, event) {
    if(event) event.stopPropagation();
    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('id', id);
    
    const response = await fetch(URL_ROOT + 'api/notifications.php?action=mark_read', {
        method: 'POST',
        body: formData
    });
    
    // Immediate local feedback
    const item = document.getElementById(`notif-${id}`);
    if (item) {
        item.classList.remove('unread');
        const btn = item.querySelector('button[onclick*="markAsRead"]');
        if (btn) btn.remove();
    }
}

async function deleteNotif(id, event) {
    if(event) event.stopPropagation();
    if(!confirm('Supprimer cette notification ?')) return;
    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('id', id);
    
    await fetch(URL_ROOT + 'api/notifications.php?action=delete', {
        method: 'POST',
        body: formData
    });
}
</script>

<style>
.notification-bell { position: relative; }
.notification-bell .badge {
    position: absolute; top: -5px; right: -5px; background: #ef4444; color: white;
    font-size: 10px; font-weight: 700; min-width: 16px; height: 16px; border-radius: 50%;
    display: none; align-items: center; justify-content: center; border: 2px solid #1e293b;
    z-index: 10;
}
.dropdown-item.unread { background: rgba(245, 158, 11, 0.05) !important; }
.notif-actions button:hover { color: #fff !important; }
</style>
