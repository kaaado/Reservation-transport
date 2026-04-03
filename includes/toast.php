<?php
// Toast Notifications Component
?>
<div id="toast-container" class="toast-container"></div>

<style>
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.toast {
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #fff;
    padding: 15px 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    min-width: 300px;
    max-width: 400px;
    transform: translateX(120%);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s ease;
    opacity: 0;
}

.toast.show {
    transform: translateX(0);
    opacity: 1;
}

.toast.hiding {
    transform: translateX(120%);
    opacity: 0;
}

.toast-icon {
    font-size: 20px;
}

.toast-success .toast-icon { color: #10b981; }
.toast-error .toast-icon { color: #ef4444; }
.toast-info .toast-icon { color: #3b82f6; }
.toast-warning .toast-icon { color: #f59e0b; }

.toast-content {
    flex: 1;
}

.toast-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
}

.toast-message {
    font-size: 13px;
    color: #94a3b8;
}

.toast-close {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    font-size: 16px;
    padding: 0;
    transition: color 0.2s;
}

.toast-close:hover {
    color: #fff;
}
</style>

<script>
function showToast(type, title, message, duration = 5000) {
    const container = document.getElementById('toast-container');
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        info: 'fa-info-circle',
        warning: 'fa-exclamation-triangle'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas ${icons[type] || icons.info}"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="closeToast(this.parentElement)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    if (duration > 0) {
        setTimeout(() => closeToast(toast), duration);
    }
}

function closeToast(toast) {
    toast.classList.remove('show');
    toast.classList.add('hiding');
    setTimeout(() => toast.remove(), 300); // Wait for transition
}

// Display PHP Session messages if present
<?php if (isset($_SESSION['success'])): ?>
    document.addEventListener('DOMContentLoaded', () => {
        showToast('success', 'Succès', '<?php echo addslashes($_SESSION['success']); ?>');
    });
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    document.addEventListener('DOMContentLoaded', () => {
        showToast('error', 'Erreur', '<?php echo addslashes($_SESSION['error']); ?>');
    });
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
</script>
