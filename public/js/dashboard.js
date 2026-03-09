// Toast System
function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed; top:20px; right:20px; z-index:9999; display:flex; flex-direction:column; gap:10px;';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    const colors = {
        'success': 'background: rgba(34, 197, 94, 0.9); border-color: #4ade80;',
        'error': 'background: rgba(239, 68, 68, 0.9); border-color: #f87171;',
        'info': 'background: rgba(14, 165, 233, 0.9); border-color: #38bdf8;'
    };
    
    toast.style.cssText = `
        ${colors[type]}
        color: white; padding: 15px 25px; border-radius: 12px;
        border: 1px solid; backdrop-filter: blur(10px);
        font-weight: 500; font-size: 14px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        transform: translateX(120%); opacity: 0;
        transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    `;
    toast.innerHTML = `<i class="fas fa-info-circle" style="margin-right:10px;"></i> ${message}`;
    
    container.appendChild(toast);
    
    requestAnimationFrame(() => {
        toast.style.transform = 'translateX(0)';
        toast.style.opacity = '1';
    });
    
    setTimeout(() => {
        toast.style.transform = 'translateX(120%)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

// Notification System Logic
let notifPage = 1;
let loadingNotifs = false;
let hasMoreNotifs = true;

async function fetchNotifications(page = 1) {
    if (loadingNotifs || !hasMoreNotifs) return;
    loadingNotifs = true;
    
    try {
        const response = await fetch(`../api/notifications.php?action=fetch&page=${page}`);
        const data = await response.json();
        
        const container = document.getElementById('notif-list-container');
        if (!container) return;
        
        if (page === 1) container.innerHTML = ''; // reset on fresh load
        
        if (data.notifications.length === 0) {
            hasMoreNotifs = false;
            if (page === 1) {
                 container.innerHTML = '<div class="dropdown-item" style="color:#64748b;justify-content:center;padding:20px;">Aucune notification</div>';
            }
            return;
        }

        data.notifications.forEach(notif => {
            const isUnread = notif.status === 'unread';
            const item = document.createElement('a');
            item.href = '#'; 
            item.className = 'dropdown-item ' + (isUnread ? 'unread-item' : '');
            if(isUnread) item.style.backgroundColor = 'rgba(255, 140, 0, 0.15)'; // more visible orange hint
            
            item.innerHTML = `
                <div style="flex:1;">
                    <div style="font-size: 14px; color: ${isUnread ? '#fff' : '#cbd5e1'}; font-weight: ${isUnread ? '600' : '400'}; line-height: 1.4;">
                        ${notif.message}
                    </div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 6px;">
                        <i class="far fa-clock" style="margin-right:4px;"></i>${new Date(notif.created_at).toLocaleString()}
                    </div>
                </div>
            `;
            
            item.onclick = async (e) => {
                e.preventDefault();
                await markAsRead(notif.id);
                item.style.backgroundColor = 'transparent';
                item.querySelector('div>div').style.fontWeight = '400';
                item.querySelector('div>div').style.color = '#cbd5e1';
                checkUnreadCount();
                // Routing logic can go here (e.g., window.location = notif.url)
            };
            
            container.appendChild(item);
        });
        
    } catch (e) { console.error("Notif Error:", e); }
    finally { loadingNotifs = false; }
}

let lastUnreadCount = -1;

async function checkUnreadCount() {
    try {
        const response = await fetch(`../api/notifications.php?action=check`);
        const data = await response.json();
        const badge = document.querySelector('.notification-bell .badge');
        
        if (badge && data !== undefined) {
            if (data.unread > 0) {
                badge.textContent = data.unread;
                badge.style.display = 'block';
                
                // Realtime feedback for new notification
                if (lastUnreadCount !== -1 && data.unread > lastUnreadCount) {
                    showToast('Nouvelle notification reçue', 'info');
                    const bellIcon = document.querySelector('.notification-bell i');
                    if (bellIcon) {
                        bellIcon.classList.add('fa-shake');
                        setTimeout(() => bellIcon.classList.remove('fa-shake'), 1000);
                    }
                }
            } else {
                badge.style.display = 'none';
            }
            lastUnreadCount = data.unread;
        }
    } catch(e) {}
}

async function markAsRead(id = 'all') {
    // We need CSRF token for POST actions, get it from a meta tag or hidden input generated securely
    const tokenEl = document.querySelector('meta[name="csrf-token"]');
    if(!tokenEl) return;
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('csrf_token', tokenEl.content);
    
    await fetch(`../api/notifications.php?action=mark_read`, {
        method: 'POST',
        body: formData
    });
    
    if(id === 'all') {
        notifPage = 1;
        hasMoreNotifs = true;
        fetchNotifications(1);
        checkUnreadCount();
        showToast('Toutes les notifications ont été lues', 'success');
    }
}

// Initial Hooks Setup
document.addEventListener('DOMContentLoaded', () => {
    
    // Sidebar Drawer Logic
    const menuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 992 && sidebar.classList.contains('active')) {
                if (!sidebar.contains(e.target) && e.target !== menuBtn) {
                    sidebar.classList.remove('active');
                }
            }
        });
    }

    // Dropdown Toggles (preventing auto-close on inner clicks)
    const dropdowns = document.querySelectorAll('.dropdown-wrapper');
    dropdowns.forEach(dropdown => {
        const menu = dropdown.querySelector('.dropdown-menu');
        if(menu) {
            dropdown.addEventListener('mouseenter', () => {
                // If it's the notification bell, fetch page 1 payload
                if(dropdown.classList.contains('notification-bell')) {
                    notifPage = 1;
                    hasMoreNotifs = true;
                    fetchNotifications(1);
                }
            });
            
            // Scroll interceptor for pagination
            menu.addEventListener('scroll', (e) => {
                if (menu.scrollTop + menu.clientHeight >= menu.scrollHeight - 10) {
                    notifPage++;
                    fetchNotifications(notifPage);
                }
            });
            
            menu.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
    });
    
    // Global Hooks
    checkUnreadCount();
    setInterval(checkUnreadCount, 5000); // Check unread count every 5 seconds for Real-time feel
    
});
