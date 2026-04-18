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
                <p style="color: #94a3b8; margin-top: 5px;">Gérez vos alertes et l'historique de votre activité.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <a href="<?php echo $dashPath; ?>" class="btn-secondary" style="width: auto; padding: 10px 20px; font-size: 13px; background: rgba(255,255,255,0.05);"><i class="fas fa-arrow-left"></i> Retour Dashboard</a>
                <button onclick="markAsRead('all')" class="btn-primary" style="width: auto; padding: 10px 20px; font-size: 13px;"><i class="fas fa-check-double"></i> Tout marquer lu</button>
                <button onclick="deleteAllNotifs()" class="btn-outline" style="width: auto; padding: 10px 20px; font-size: 13px; color: #ef4444; border-color: rgba(239, 68, 68, 0.2);"><i class="fas fa-trash-alt"></i> Vider tout</button>
            </div>
        </div>

        <div class="notifications-list" id="main-notif-container">
            <?php if (empty($notifications)): ?>
                <div id="empty-notif" style="text-align:center; padding:80px; color:#64748b; background: rgba(30,41,59,0.3); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.05); margin-top: 20px;">
                    <i class="fas fa-bell-slash" style="font-size:40px; margin-bottom:15px; opacity:0.3;"></i>
                    <p>Aucune notification enregistrée pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach($notifications as $notif): ?>
                    <div class="notif-row <?php echo $notif['status']; ?>" id="page-notif-<?php echo $notif['id']; ?>">
                        <div class="notif-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="notif-body">
                            <div class="notif-msg"><?php echo htmlspecialchars($notif['message']); ?></div>
                            <div class="notif-date"><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></div>
                        </div>
                        <div class="notif-row-actions">
                            <?php if ($notif['status'] === 'unread'): ?>
                                <button onclick="markAsRead(<?php echo $notif['id']; ?>)" title="Marquer lu" class="mark-read-btn"><i class="fas fa-circle"></i></button>
                            <?php endif; ?>
                            <button onclick="deleteNotif(<?php echo $notif['id']; ?>)" class="delete" title="Supprimer"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<style>
.notifications-list { display: flex; flex-direction: column; gap: 12px; margin-top: 24px; }
.notif-row {
    background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(255,255,255,0.04);
    border-radius: 12px; padding: 18px; display: flex; align-items: center; gap: 18px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.notif-row:hover { background: rgba(30, 41, 59, 0.8); border-color: rgba(255,140,0,0.3); transform: translateX(5px); }
.notif-row.unread { border-left: 4px solid #f59e0b; background: rgba(245, 158, 11, 0.04); }
.notif-icon {
    width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.03); color: #64748b; font-size: 18px;
}
.notif-row.unread .notif-icon { background: rgba(245,158,11,0.08); color: #f59e0b; }
.notif-body { flex: 1; }
.notif-msg { color: #f1f5f9; font-size: 14px; margin-bottom: 6px; font-weight: 500; }
.notif-row.read .notif-msg { color: #94a3b8; font-weight: 400; }
.notif-date { font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 6px; }
.notif-row-actions { display: flex; gap: 8px; }
.notif-row-actions button {
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); color: #64748b; 
    cursor: pointer; font-size: 14px; padding: 8px; border-radius: 8px;
    transition: all 0.2s;
}
.notif-row-actions button:hover { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: rgba(245, 158, 11, 0.2); }
.notif-row-actions button.delete:hover { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.2); }
</style>

<script>
// Specialized sync for this page when SSE events occur
function syncNotificationPage(data) {
    // We could re-render the whole list, but let's just handle state updates via markAsRead
}

async function markAsRead(id) {
    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('id', id);
    
    await fetch(URL_ROOT + 'api/notifications.php?action=mark_read', {
        method: 'POST',
        body: formData
    });
    
    if (id === 'all') {
        document.querySelectorAll('.notif-row.unread').forEach(row => {
            row.classList.remove('unread');
            row.classList.add('read');
            const btn = row.querySelector('.mark-read-btn');
            if (btn) btn.remove();
        });
    } else {
        const row = document.getElementById(`page-notif-${id}`);
        if(row) {
            row.classList.remove('unread');
            row.classList.add('read');
            const btn = row.querySelector('.mark-read-btn');
            if(btn) btn.remove();
        }
    }
}

async function deleteNotif(id) {
    if(!confirm('Supprimer cette notification ?')) return;
    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('id', id);
    
    await fetch(URL_ROOT + 'api/notifications.php?action=delete', {
        method: 'POST',
        body: formData
    });
    
    const row = document.getElementById(`page-notif-${id}`);
    if(row) {
        row.style.transform = 'translateX(50px)';
        row.style.opacity = '0';
        setTimeout(() => {
            row.remove();
            if (document.querySelectorAll('.notif-row').length === 0) {
                location.reload(); // Show empty state
            }
        }, 300);
    }
}

async function deleteAllNotifs() {
    if(!confirm('Voulez-vous supprimer toutes vos notifications ?')) return;
    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    
    await fetch(URL_ROOT + 'api/notifications.php?action=delete_all', {
        method: 'POST',
        body: formData
    });
    location.reload();
}
</script>

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
