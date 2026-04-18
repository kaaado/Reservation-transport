<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('admin');

require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'admin.php';

$db = new Database();
$pdo = $db->getConnection();

// Ensure audit log table exists
ensureAuditLogTable($pdo);

$stats = getAdminDashboardStats($pdo);

// Recent activity
$recentLogs = $pdo->query("
    SELECT l.*, r.pickup_location, r.destination, u.name as user_name, u.role as user_role
    FROM reservation_logs l
    JOIN reservations r ON l.reservation_id = r.id
    JOIN users u ON l.changed_by = u.id
    ORDER BY l.created_at DESC LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Pending ID verifications — show latest with scroll support
$pendingVerifications = $pdo->query("
    SELECT id, name, email, role, id_card_url, created_at 
    FROM users 
    WHERE id_is_verified = 0 AND id_card_url IS NOT NULL 
    ORDER BY created_at DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Tableau de Bord - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token']); ?>">
    <style>
        /* ═══════════════════════════════════════════════
           KPI GRID — Fixed: All 8 cards visible  
        ═══════════════════════════════════════════════ */
        .admin-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 20px;
            overflow: visible;
        }
        .kpi-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: kpiFadeIn 0.5s ease forwards;
            opacity: 0;
            transform: translateY(15px);
        }
        .kpi-card:nth-child(1) { animation-delay: 0.05s; }
        .kpi-card:nth-child(2) { animation-delay: 0.10s; }
        .kpi-card:nth-child(3) { animation-delay: 0.15s; }
        .kpi-card:nth-child(4) { animation-delay: 0.20s; }
        .kpi-card:nth-child(5) { animation-delay: 0.25s; }
        .kpi-card:nth-child(6) { animation-delay: 0.30s; }
        .kpi-card:nth-child(7) { animation-delay: 0.35s; }
        .kpi-card:nth-child(8) { animation-delay: 0.40s; }
        @keyframes kpiFadeIn {
            to { opacity: 1; transform: translateY(0); }
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 3px;
        }
        .kpi-card:hover { transform: translateY(-3px); border-color: rgba(255,140,0,0.3); box-shadow: 0 8px 25px rgba(0,0,0,0.2); }
        .kpi-card .kpi-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
        }
        .kpi-card .kpi-value {
            font-size: 26px; font-weight: 800; color: #f8fafc;
            line-height: 1;
        }
        .kpi-card .kpi-label {
            font-size: 12px; color: #94a3b8; margin-top: 6px;
            text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;
        }
        .kpi-blue .kpi-icon { background: rgba(59,130,246,0.15); color: #3b82f6; }
        .kpi-blue::before { background: #3b82f6; }
        .kpi-green .kpi-icon { background: rgba(16,185,129,0.15); color: #10b981; }
        .kpi-green::before { background: #10b981; }
        .kpi-orange .kpi-icon { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .kpi-orange::before { background: #f59e0b; }
        .kpi-purple .kpi-icon { background: rgba(139,92,246,0.15); color: #8b5cf6; }
        .kpi-purple::before { background: #8b5cf6; }
        .kpi-red .kpi-icon { background: rgba(239,68,68,0.15); color: #ef4444; }
        .kpi-red::before { background: #ef4444; }
        .kpi-emerald .kpi-icon { background: rgba(52,211,153,0.15); color: #34d399; }
        .kpi-emerald::before { background: #34d399; }

        /* ═══════════════════════════════════════════════
           SECTIONS
        ═══════════════════════════════════════════════ */
        .admin-section {
            background: rgba(30,41,59,0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 24px;
            margin-top: 24px;
        }
        .admin-section h3 {
            color: #f8fafc; font-size: 16px; font-weight: 700;
            margin-bottom: 16px; display: flex; align-items: center; gap: 10px;
        }
        .admin-section h3 i { color: #f59e0b; }
        .admin-section h3 .section-count {
            margin-left: auto;
            background: rgba(245,158,11,0.15);
            color: #fbbf24;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .admin-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 900px) { .admin-grid-2 { grid-template-columns: 1fr; } }

        /* ═══════════════════════════════════════════════
           ACTIVITY FEED 
        ═══════════════════════════════════════════════ */
        .activity-item {
            display: flex; gap: 12px; padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            align-items: flex-start;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-dot {
            width: 8px; height: 8px; border-radius: 50%;
            margin-top: 6px; flex-shrink: 0;
        }
        .activity-content { flex: 1; }
        .activity-content .act-title { font-size: 13px; color: #cbd5e1; }
        .activity-content .act-meta { font-size: 11px; color: #64748b; margin-top: 2px; }

        /* ═══════════════════════════════════════════════
           QUICK ACTIONS 
        ═══════════════════════════════════════════════ */
        .quick-action-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;
        }
        .quick-action-btn {
            background: rgba(15,23,42,0.6);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 13px; font-weight: 600;
        }
        .quick-action-btn:hover { background: rgba(255,140,0,0.1); border-color: rgba(255,140,0,0.3); color: #fff; transform: translateY(-2px); }
        .quick-action-btn i { display: block; font-size: 22px; margin-bottom: 8px; }

        .pending-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.3);
            color: #fbbf24; font-size: 12px; font-weight: 600;
            padding: 3px 10px; border-radius: 20px;
        }

        /* ═══════════════════════════════════════════════
           VERIFICATION LIST — Enhanced Design + Scroll 
        ═══════════════════════════════════════════════ */
        .verification-scroll {
            max-height: 340px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .verification-scroll::-webkit-scrollbar { width: 4px; }
        .verification-scroll::-webkit-scrollbar-track { background: transparent; }
        .verification-scroll::-webkit-scrollbar-thumb { background: rgba(255,140,0,0.3); border-radius: 10px; }
        .verification-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,140,0,0.5); }

        .verif-item {
            display: flex;
            gap: 10px;
            padding: 10px 8px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            align-items: center;
            transition: all 0.2s ease;
            border-radius: 8px;
            margin-bottom: 2px;
        }
        .verif-item:last-child { border-bottom: none; margin-bottom: 0; }
        .verif-item:hover {
            background: rgba(245,158,11,0.06);
            border-color: rgba(245,158,11,0.1);
        }

        .verif-avatar {
            width: 36px; height: 36px; border-radius: 10px;
            background: rgba(245,158,11,0.15);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; color: #f59e0b; font-size: 13px;
            flex-shrink: 0;
        }

        .verif-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .verif-name-row {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
            min-width: 0;
        }
        .verif-name {
            font-weight: 600; color: #f8fafc; font-size: 13px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .verif-role-tag {
            font-size: 9px; font-weight: 700; padding: 2px 5px;
            border-radius: 4px; text-transform: uppercase; letter-spacing: 0.3px;
            flex-shrink: 0;
            line-height: 1;
        }
        .verif-role-client { background: rgba(59,130,246,0.15); color: #60a5fa; }
        .verif-role-transporter { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .verif-meta { font-size: 11px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .verif-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(59,130,246,0.25));
            border: 1px solid rgba(59,130,246,0.3);
            color: #60a5fa;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .verif-btn:hover {
            background: rgba(59,130,246,0.35);
            box-shadow: 0 2px 8px rgba(59,130,246,0.2);
            transform: translateY(-1px);
            color: #93bbfd;
        }
        .verif-btn i { font-size: 10px; }

        @media (max-width: 600px) {
            .verif-item {
                flex-wrap: wrap;
                gap: 8px;
            }
            .verif-btn {
                margin-left: 46px;
                margin-top: -4px;
            }
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: #64748b;
        }
        .empty-state i {
            font-size: 36px;
            color: rgba(255,255,255,0.08);
            margin-bottom: 12px;
            display: block;
        }
        .empty-state p { font-size: 13px; }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php include INC_PATH . 'sidebar.php'; ?>
    <main class="main-content">
        <?php include INC_PATH . 'topbar.php'; ?>
        
        <div class="page-header">
            <div>
                <h1>Centre de Contrôle Administrateur</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Supervisez l'intégralité de la plateforme CargoConnect.</p>
            </div>
            <?php if ($stats['blocked_transporters'] > 0): ?>
                <span class="pending-badge"><i class="fas fa-exclamation-triangle"></i> <?php echo $stats['blocked_transporters']; ?> transporteur(s) bloqué(s)</span>
            <?php endif; ?>
        </div>

        <!-- KPI Grid — All 8 cards visible with proper responsive wrapping -->
        <div class="admin-kpi-grid">
            <div class="kpi-card kpi-blue">
                <div class="kpi-icon"><i class="fas fa-users"></i></div>
                <div class="kpi-value"><?php echo $stats['total_users']; ?></div>
                <div class="kpi-label">Utilisateurs Total</div>
            </div>
            <div class="kpi-card kpi-purple">
                <div class="kpi-icon"><i class="fas fa-user-tie"></i></div>
                <div class="kpi-value"><?php echo $stats['total_clients']; ?> / <?php echo $stats['total_transporters']; ?></div>
                <div class="kpi-label">Clients / Transporteurs</div>
            </div>
            <div class="kpi-card kpi-orange">
                <div class="kpi-icon"><i class="fas fa-id-card"></i></div>
                <div class="kpi-value"><?php echo $stats['pending_verifications']; ?></div>
                <div class="kpi-label">Vérifications en Attente</div>
            </div>
            <div class="kpi-card kpi-green">
                <div class="kpi-icon"><i class="fas fa-check-double"></i></div>
                <div class="kpi-value"><?php echo $stats['completed_reservations']; ?></div>
                <div class="kpi-label">Réservations Terminées</div>
            </div>
            <div class="kpi-card kpi-blue">
                <div class="kpi-icon"><i class="fas fa-clock"></i></div>
                <div class="kpi-value"><?php echo $stats['active_reservations']; ?></div>
                <div class="kpi-label">Réservations Actives</div>
            </div>
            <div class="kpi-card kpi-emerald">
                <div class="kpi-icon"><i class="fas fa-coins"></i></div>
                <div class="kpi-value"><?php echo number_format($stats['total_revenue'], 0); ?></div>
                <div class="kpi-label">Revenu Plateforme (DA)</div>
            </div>
            <div class="kpi-card kpi-red">
                <div class="kpi-icon"><i class="fas fa-hand-holding-usd"></i></div>
                <div class="kpi-value"><?php echo number_format($stats['unpaid_commissions'], 0); ?></div>
                <div class="kpi-label">Commissions Impayées (DA)</div>
            </div>
            <div class="kpi-card kpi-orange">
                <div class="kpi-icon"><i class="fas fa-truck"></i></div>
                <div class="kpi-value"><?php echo $stats['total_vehicles']; ?></div>
                <div class="kpi-label">Véhicules Enregistrés</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="admin-section">
            <h3><i class="fas fa-bolt"></i> Actions Rapides</h3>
            <div class="quick-action-grid">
                <a href="users.php" class="quick-action-btn"><i class="fas fa-users-cog" style="color:#3b82f6;"></i>Gestion Utilisateurs</a>
                <a href="reservations.php" class="quick-action-btn"><i class="fas fa-clipboard-list" style="color:#f59e0b;"></i>Gestion Réservations</a>
                <a href="vehicles.php" class="quick-action-btn"><i class="fas fa-truck" style="color:#8b5cf6;"></i>Gestion Véhicules</a>
                <a href="commissions.php" class="quick-action-btn"><i class="fas fa-money-bill-wave" style="color:#10b981;"></i>Commissions & Paiements</a>
                <a href="reservation_logs.php" class="quick-action-btn"><i class="fas fa-history" style="color:#ef4444;"></i>Journaux d'Audit</a>
            </div>
        </div>

        <div class="admin-grid-2">
            <!-- Recent Activity -->
            <div class="admin-section">
                <h3><i class="fas fa-stream"></i> Activité Récente</h3>
                <?php if (empty($recentLogs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Aucune activité récente.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                    <div class="activity-item">
                        <div class="activity-dot" style="background: <?php
                            echo match($log['new_status'] ?? '') {
                                'completed' => '#10b981',
                                'cancelled','rejected' => '#ef4444',
                                'accepted' => '#3b82f6',
                                'in_progress' => '#f59e0b',
                                default => '#64748b'
                            };
                        ?>;"></div>
                        <div class="activity-content">
                            <div class="act-title">
                                <strong><?php echo htmlspecialchars($log['user_name']); ?></strong>
                                <?php if ($log['old_status']): ?>
                                    → <span style="font-weight:600;"><?php echo translateStatus($log['new_status']); ?></span>
                                <?php else: ?>
                                    a créé la réservation
                                <?php endif; ?>
                                <span style="color:#64748b;">#<?php echo $log['reservation_id']; ?></span>
                            </div>
                            <div class="act-meta">
                                <?php echo htmlspecialchars($log['pickup_location']); ?> → <?php echo htmlspecialchars($log['destination']); ?> · <?php echo date('d/m H:i', strtotime($log['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pending Verifications — Scrollable list -->
            <div class="admin-section">
                <h3>
                    <i class="fas fa-shield-alt"></i> Vérifications d'Identité en Attente
                    <?php if (!empty($pendingVerifications)): ?>
                        <span class="section-count"><?php echo count($pendingVerifications); ?></span>
                    <?php endif; ?>
                </h3>
                <?php if (empty($pendingVerifications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="color: rgba(16,185,129,0.3);"></i>
                        <p>Aucune vérification en attente.</p>
                    </div>
                <?php else: ?>
                    <div class="verification-scroll">
                        <?php foreach ($pendingVerifications as $pv): ?>
                        <div class="verif-item">
                            <div class="verif-avatar"><?php echo strtoupper(substr($pv['name'], 0, 1)); ?></div>
                            <div class="verif-info">
                                <div class="verif-name-row">
                                    <span class="verif-name"><?php echo htmlspecialchars($pv['name']); ?></span>
                                    <span class="verif-role-tag verif-role-<?php echo $pv['role']; ?>"><?php echo translateStatus($pv['role']); ?></span>
                                </div>
                                <div class="verif-meta">
                                    <?php echo htmlspecialchars($pv['email']); ?> · <?php echo date('d/m', strtotime($pv['created_at'])); ?>
                                </div>
                            </div>
                            <button type="button" class="verif-btn" onclick="openIdVerifyModal(<?php echo htmlspecialchars(json_encode([
                                'id' => $pv['id'],
                                'name' => $pv['name'],
                                'email' => $pv['email'],
                                'role' => $pv['role'],
                                'id_card_url' => $pv['id_card_url']
                            ]), ENT_QUOTES); ?>)">
                                <i class="fas fa-eye"></i> Vérifier
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($pendingVerifications) >= 10): ?>
                        <div style="text-align:center; padding-top:12px; border-top:1px solid rgba(255,255,255,0.04); margin-top:8px;">
                            <a href="users.php?verification=pending" style="color:#f59e0b; font-size:12px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                Voir tout <i class="fas fa-arrow-right" style="font-size:10px;"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<!-- ═══════════════════════════════════════════════
     ID VERIFICATION MODAL (In-place on Dashboard)
═══════════════════════════════════════════════ -->
<div class="modal-overlay" id="idVerifyModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('idVerifyModal')"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-shield-alt"></i> Vérification d'Identité</h3>
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <div class="verif-avatar" style="width:48px;height:48px;font-size:18px;" id="verifyAvatar"></div>
            <div>
                <div style="font-weight:700; color:#f8fafc;" id="verifyName"></div>
                <div style="font-size:12px; color:#64748b;" id="verifyMeta"></div>
            </div>
        </div>
        <div class="id-preview" style="text-align: center; margin: 15px 0;">
            <img id="verifyIdImage" src="" alt="ID Card" style="max-width: 100%; max-height: 350px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1);">
        </div>
        <div style="display:flex; gap:12px; justify-content:center; margin-top:20px;">
            <form method="POST" action="users.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="verify_id">
                <input type="hidden" name="user_id" id="verifyUserId">
                <!-- Add a hidden redirect target so it comes back to dashboard -->
                <input type="hidden" name="redirect_to" value="dashboard.php">
                <button type="submit" class="btn btn-primary" style="padding:10px 24px; font-size:13px; font-weight:600; border-radius:10px;">
                    <i class="fas fa-check-double"></i> Approuver
                </button>
            </form>
            <button class="btn btn-outline" style="padding:10px 24px; border-color:rgba(255,255,255,0.1); color:#cbd5e1; font-size:13px; font-weight:600; border-radius:10px;" onclick="closeModal('idVerifyModal')">
                <i class="fas fa-times"></i> Fermer
            </button>
        </div>
    </div>
</div>

<style>
    /* Add missing modal styles to dashboard */
    .modal-overlay {
        display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;
        backdrop-filter: blur(5px);
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
        background: #1e293b; border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px; padding: 25px; max-width: 500px; width: 90%;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        animation: modalFadeIn 0.3s ease;
    }
    @keyframes modalFadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    .modal-close { float: right; background: none; border: none; color: #64748b; cursor: pointer; font-size: 18px; }
    .modal-close:hover { color: #fff; }
</style>

<script>
function openIdVerifyModal(user) {
    document.getElementById('verifyAvatar').textContent = user.name.charAt(0).toUpperCase();
    document.getElementById('verifyName').textContent = user.name;
    document.getElementById('verifyMeta').textContent = user.email + ' · ' + user.role.charAt(0).toUpperCase() + user.role.slice(1);
    document.getElementById('verifyUserId').value = user.id;
    document.getElementById('verifyIdImage').src = '<?php echo URL_ROOT; ?>' + user.id_card_url;
    document.getElementById('idVerifyModal').classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
// Close on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(overlay.id); });
});
</script>
<?php include INC_PATH . 'toast.php'; ?>
</body>
</html>
