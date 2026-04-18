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

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: users.php"); exit;
    }

    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    $admin_id = $_SESSION['user_id'];

    switch ($action) {
        case 'update_status':
            $status = $_POST['status'] ?? '';
            if (updateUserStatus($user_id, $status, $pdo)) {
                $u = getUserById($user_id, $pdo);
                sendAdminNotification($user_id, "Votre compte a été mis à jour : statut → {$status}.", $pdo);
                logAdminAction($admin_id, 'update_user_status', 'user', $user_id, "Status changed to: {$status}", $pdo);
                $_SESSION['success'] = "Statut de l'utilisateur mis à jour.";
            } else {
                $_SESSION['error'] = "Échec de la mise à jour du statut.";
            }
            break;

        case 'verify_id':
            if (verifyUserID($user_id, $pdo)) {
                sendAdminNotification($user_id, "Votre identité a été vérifiée par l'administration. Bienvenue sur CargoConnect !", $pdo);
                logAdminAction($admin_id, 'verify_user_id', 'user', $user_id, "ID card verified", $pdo);
                $_SESSION['success'] = "Identité vérifiée avec succès.";
            } else {
                $_SESSION['error'] = "Échec de la vérification.";
            }
            break;

        case 'unverify_id':
            if (unverifyUserID($user_id, $pdo)) {
                sendAdminNotification($user_id, "Votre vérification d'identité a été révoquée. Veuillez re-soumettre votre pièce.", $pdo);
                logAdminAction($admin_id, 'unverify_user_id', 'user', $user_id, "ID verification revoked", $pdo);
                $_SESSION['success'] = "Vérification révoquée.";
            } else {
                $_SESSION['error'] = "Échec de la révocation.";
            }
            break;

        case 'delete_user':
            $result = deleteUser($user_id, $pdo);
            if ($result === "HAS_ACTIVE_RESERVATIONS") {
                $_SESSION['error'] = "Impossible de supprimer : l'utilisateur a des réservations actives.";
            } elseif ($result === "HAS_ACTIVE_VEHICLES") {
                $_SESSION['error'] = "Impossible de supprimer : l'utilisateur a des véhicules actifs.";
            } elseif ($result === "HAS_UNPAID_COMMISSIONS") {
                $_SESSION['error'] = "Impossible de supprimer : l'utilisateur a des commissions impayées.";
            } elseif ($result) {
                logAdminAction($admin_id, 'delete_user', 'user', $user_id, "User deleted", $pdo);
                $_SESSION['success'] = "Utilisateur supprimé.";
            } else {
                $_SESSION['error'] = "Impossible de supprimer cet utilisateur.";
            }
            break;

        case 'update_profile':
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'role' => $_POST['role'] ?? 'client',
                'region' => trim($_POST['region'] ?? 'Algérie'),
            ];
            if (updateUserProfile($user_id, $data, $pdo)) {
                logAdminAction($admin_id, 'update_user_profile', 'user', $user_id, "Profile updated", $pdo);
                $_SESSION['success'] = "Profil utilisateur mis à jour.";
            } else {
                $_SESSION['error'] = "Échec de la mise à jour du profil.";
            }
            break;
    }
    header("Location: users.php"); exit;
}

// Filters
$filters = [
    'role' => $_GET['role'] ?? '',
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
    'verification' => $_GET['verification'] ?? '',
];
$users = getAllUsers($pdo, $filters);

// If ?verify=ID, load that user for modal
$verifyUser = null;
if (isset($_GET['verify'])) {
    $verifyUser = getUserById((int)$_GET['verify'], $pdo);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Utilisateurs - Admin CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    <style>
        /* ═══════════════════════════════════════════════
           FILTER BAR — Premium, single line, flex-wrap
        ═══════════════════════════════════════════════ */
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            background: rgba(30,41,59,0.6);
            border-radius: 12px;
            padding: 14px 16px;
            border: 1px solid rgba(255,255,255,0.06);
            margin-top: 20px;
        }
        .filter-bar select,
        .filter-bar input,
        .filter-bar .btn {
            background: rgba(15,23,42,0.8);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            height: 40px;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .filter-bar select:focus,
        .filter-bar input:focus {
            border-color: rgba(255,140,0,0.5);
            box-shadow: 0 0 0 2px rgba(255,140,0,0.1);
        }
        .filter-bar select { min-width: 140px; cursor: pointer; }
        .filter-bar input { flex: 1; min-width: 200px; }
        .filter-bar .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
            text-decoration: none;
        }
        .filter-bar .btn-filter {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border: none;
            color: #000;
        }
        .filter-bar .btn-filter:hover { box-shadow: 0 4px 15px rgba(245,158,11,0.3); transform: translateY(-1px); }
        .filter-bar .btn-clear {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #94a3b8;
        }
        .filter-bar .btn-clear:hover { background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.3); color: #f87171; }

        /* ═══════════════════════════════════════════════
           USER TABLE
        ═══════════════════════════════════════════════ */
        .user-table { width: 100%; border-collapse: collapse; }
        .user-table thead th {
            padding: 12px 16px; text-align: left; font-size: 12px;
            text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px; font-weight: 600;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            background: rgba(15,23,42,0.4);
        }
        .user-table tbody td {
            padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,0.04);
            font-size: 13px; color: #cbd5e1; vertical-align: middle;
        }
        .user-table tbody tr { transition: background 0.2s; }
        .user-table tbody tr:hover { background: rgba(255,140,0,0.04); }

        /* Highlight unverified rows with pending ID */
        .user-table tbody tr.row-unverified {
            background: rgba(245,158,11,0.04);
            border-left: 3px solid rgba(245,158,11,0.5);
        }
        .user-table tbody tr.row-unverified:hover {
            background: rgba(245,158,11,0.08);
        }

        /* ═══════════════════════════════════════════════
           USER CELL — Split layout (avatar + info)
        ═══════════════════════════════════════════════ */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 15px;
            flex-shrink: 0;
        }
        .user-avatar.avatar-default {
            background: rgba(255,140,0,0.15); color: #f59e0b;
        }
        .user-avatar img {
            width: 40px; height: 40px; border-radius: 10px;
            object-fit: cover;
        }
        .user-data {
            display: flex;
            flex-direction: column;
            gap: 1px;
            min-width: 0;
        }
        .user-name { 
            font-weight: 600; color: #f8fafc; font-size: 13px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .user-email { font-size: 11px; color: #64748b; }
        .user-phone { font-size: 11px; color: #64748b; }

        /* ═══════════════════════════════════════════════
           BADGES
        ═══════════════════════════════════════════════ */
        .role-badge {
            font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px;
            text-transform: uppercase; letter-spacing: 0.3px;
        }
        .role-client { background: rgba(59,130,246,0.15); color: #60a5fa; }
        .role-transporter { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .role-admin { background: rgba(239,68,68,0.15); color: #f87171; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
        .status-dot-active { background: #10b981; }
        .status-dot-pending { background: #f59e0b; }
        .status-dot-suspended { background: #ef4444; }

        /* ═══════════════════════════════════════════════
           IDENTITY COLUMN — Verify button inline
        ═══════════════════════════════════════════════ */
        .id-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .verify-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; }
        .verify-yes { color: #10b981; }
        .verify-no { color: #ef4444; }
        .verify-pending { color: #f59e0b; }
        .btn-verify-inline {
            background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(59,130,246,0.25));
            border: 1px solid rgba(59,130,246,0.3);
            color: #60a5fa;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }
        .btn-verify-inline:hover {
            background: rgba(59,130,246,0.3);
            box-shadow: 0 2px 8px rgba(59,130,246,0.2);
            transform: translateY(-1px);
        }
        .btn-view-id {
            background: rgba(16,185,129,0.1);
            border: 1px solid rgba(16,185,129,0.3);
            color: #34d399;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-view-id:hover {
            background: rgba(16,185,129,0.2);
        }

        /* ═══════════════════════════════════════════════
           ACTION DROPDOWN
        ═══════════════════════════════════════════════ */
        .action-dropdown { position: relative; display: inline-block; }
        .action-btn {
            background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
            color: #94a3b8; border-radius: 6px; padding: 6px 10px; cursor: pointer;
            font-size: 13px; transition: all 0.2s;
        }
        .action-btn:hover { background: rgba(255,140,0,0.15); color: #f59e0b; }
        .action-menu {
            display: none; position: absolute; right: 0; top: 100%;
            background: rgba(15,23,42,0.95); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; padding: 6px 0; z-index: 50; min-width: 180px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4); margin-top: 4px;
        }
        .action-menu.show { display: block; }
        .action-menu button, .action-menu a {
            display: flex; width: 100%; align-items: center; gap: 8px;
            padding: 8px 14px; font-size: 13px; color: #cbd5e1;
            background: none; border: none; cursor: pointer; text-decoration: none;
            transition: background 0.2s;
        }
        .action-menu button:hover, .action-menu a:hover { background: rgba(255,255,255,0.05); }
        .action-menu .danger { color: #ef4444; }

        /* ═══════════════════════════════════════════════
           MODALS — Verification + Edit + View ID
        ═══════════════════════════════════════════════ */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7); z-index: 100; align-items: center; justify-content: center;
            backdrop-filter: blur(4px);
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #1e293b; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px; padding: 30px; max-width: 600px; width: 90%;
            max-height: 85vh; overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-box h3 { color: #f8fafc; font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .modal-box h3 i { color: #f59e0b; }
        .modal-close {
            float: right; background: none; border: none; color: #64748b; cursor: pointer;
            font-size: 18px; transition: color 0.2s;
        }
        .modal-close:hover { color: #fff; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; color: #94a3b8; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; }
        .form-group input, .form-group select {
            width: 100%; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.1);
            color: #fff; padding: 10px 14px; border-radius: 8px; font-size: 13px; outline: none;
            box-sizing: border-box;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .id-preview { text-align: center; margin: 15px 0; }
        .id-preview img { max-width: 100%; max-height: 350px; border-radius: 10px; border: 2px solid rgba(255,255,255,0.1); }
        .table-container { 
            background: rgba(30,41,59,0.6); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px; overflow: hidden; margin-top: 16px;
        }
        .results-count { font-size: 13px; color: #64748b; margin-top: 16px; }

        /* Verified check icon in modal */
        .verified-check {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            color: #10b981; font-size: 14px; font-weight: 700;
            padding: 12px;
            background: rgba(16,185,129,0.1);
            border-radius: 10px;
            margin-top: 16px;
        }
        .verified-check i { font-size: 22px; }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }
        .empty-state i {
            font-size: 40px;
            color: rgba(255,255,255,0.08);
            margin-bottom: 12px;
            display: block;
        }

        /* Active filter indicators */
        .active-filters {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(245,158,11,0.1);
            border: 1px solid rgba(245,158,11,0.2);
            color: #fbbf24;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .filter-tag i { font-size: 8px; }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php include INC_PATH . 'sidebar.php'; ?>
    <main class="main-content">
        <?php include INC_PATH . 'topbar.php'; ?>

        <div class="page-header">
            <div>
                <h1>Gestion des Utilisateurs</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Administrer, vérifier et contrôler tous les comptes de la plateforme.</p>
            </div>
        </div>

        <!-- Filter Bar — Single line, same height elements, responsive flex-wrap -->
        <form method="GET" class="filter-bar" id="filterForm">
            <select name="role" onchange="this.form.submit()">
                <option value="">Tous les rôles</option>
                <option value="client" <?php echo ($filters['role'] === 'client') ? 'selected' : ''; ?>>Client</option>
                <option value="transporter" <?php echo ($filters['role'] === 'transporter') ? 'selected' : ''; ?>>Transporteur</option>
                <option value="admin" <?php echo ($filters['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
            </select>
            <select name="status" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                <option value="active" <?php echo ($filters['status'] === 'active') ? 'selected' : ''; ?>>Actif</option>
                <option value="pending" <?php echo ($filters['status'] === 'pending') ? 'selected' : ''; ?>>En attente</option>
                <option value="suspended" <?php echo ($filters['status'] === 'suspended') ? 'selected' : ''; ?>>Suspendu</option>
            </select>
            <select name="verification" onchange="this.form.submit()">
                <option value="">Vérification ID</option>
                <option value="verified" <?php echo ($filters['verification'] === 'verified') ? 'selected' : ''; ?>>Vérifié</option>
                <option value="pending" <?php echo ($filters['verification'] === 'pending') ? 'selected' : ''; ?>>En attente</option>
                <option value="unverified" <?php echo ($filters['verification'] === 'unverified') ? 'selected' : ''; ?>>Non vérifié</option>
            </select>
            <input type="text" name="search" placeholder="Rechercher par nom, email ou téléphone..." value="<?php echo htmlspecialchars($filters['search']); ?>">
            <button type="submit" class="btn btn-filter"><i class="fas fa-search"></i> Filtrer</button>
            <?php if ($filters['role'] || $filters['status'] || $filters['search'] || $filters['verification']): ?>
                <a href="users.php" class="btn btn-clear"><i class="fas fa-times"></i> Effacer</a>
            <?php endif; ?>
        </form>

        <!-- Active filter tags -->
        <?php if ($filters['role'] || $filters['status'] || $filters['search'] || $filters['verification']): ?>
        <div class="active-filters">
            <?php if ($filters['role']): ?>
                <span class="filter-tag"><i class="fas fa-circle"></i> Rôle: <?php echo ucfirst($filters['role']); ?></span>
            <?php endif; ?>
            <?php if ($filters['status']): ?>
                <span class="filter-tag"><i class="fas fa-circle"></i> Statut: <?php echo ucfirst($filters['status']); ?></span>
            <?php endif; ?>
            <?php if ($filters['verification']): ?>
                <span class="filter-tag"><i class="fas fa-circle"></i> Vérification: <?php echo ucfirst($filters['verification']); ?></span>
            <?php endif; ?>
            <?php if ($filters['search']): ?>
                <span class="filter-tag"><i class="fas fa-circle"></i> "<?php echo htmlspecialchars($filters['search']); ?>"</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <p class="results-count"><strong><?php echo count($users); ?></strong> utilisateur(s) trouvé(s)</p>

        <!-- Users Table -->
        <div class="table-container">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Identité</th>
                        <th>Région</th>
                        <th>Stats</th>
                        <th>Inscription</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <p>Aucun utilisateur trouvé.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): 
                            $isUnverifiedPending = (!$user['id_is_verified'] && $user['id_card_url']);
                        ?>
                        <tr class="<?php echo $isUnverifiedPending ? 'row-unverified' : ''; ?>">
                            <!-- User Cell: Avatar + Info side by side -->
                            <td>
                                <div class="user-cell">
                                    <?php if (!empty($user['profile_image'])): ?>
                                        <div class="user-avatar">
                                            <img src="<?php echo URL_ROOT . htmlspecialchars($user['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>">
                                        </div>
                                    <?php else: ?>
                                        <div class="user-avatar avatar-default"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                                    <?php endif; ?>
                                    <div class="user-data">
                                        <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                        <?php if ($user['phone']): ?><div class="user-phone"><i class="fas fa-phone" style="font-size:9px; margin-right:3px;"></i><?php echo htmlspecialchars($user['phone']); ?></div><?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo translateStatus($user['role']); ?></span></td>
                            <td>
                                <span class="status-dot status-dot-<?php echo $user['status']; ?>"></span>
                                <?php echo translateStatus($user['status']); ?>
                            </td>
                            <!-- Identity Column — with inline verify/view button -->
                            <td>
                                <div class="id-status">
                                    <?php if ($user['id_is_verified']): ?>
                                        <span class="verify-badge verify-yes"><i class="fas fa-check-circle"></i> <?php echo translateStatus('verified'); ?></span>
                                        <?php if ($user['id_card_url']): ?>
                                            <button type="button" class="btn-view-id" data-user="<?php echo htmlspecialchars(json_encode([
                                                'id' => $user['id'],
                                                'name' => $user['name'],
                                                'id_card_url' => $user['id_card_url'],
                                                'verified' => true
                                            ]), ENT_QUOTES, 'UTF-8'); ?>" onclick="openIdViewModal(JSON.parse(this.dataset.user))">
                                                <i class="fas fa-image"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php elseif ($user['id_card_url']): ?>
                                        <span class="verify-badge verify-pending"><i class="fas fa-clock"></i> <?php echo translateStatus('pending'); ?></span>
                                        <button type="button" class="btn-verify-inline" data-user="<?php echo htmlspecialchars(json_encode([
                                            'id' => $user['id'],
                                            'name' => $user['name'],
                                            'email' => $user['email'],
                                            'role' => $user['role'],
                                            'id_card_url' => $user['id_card_url']
                                        ]), ENT_QUOTES, 'UTF-8'); ?>" onclick="openIdVerifyModal(JSON.parse(this.dataset.user))">
                                            <i class="fas fa-shield-alt"></i> Vérifier
                                        </button>
                                    <?php else: ?>
                                        <span style="color:#64748b; font-size:11px;">Non soumis</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="font-size:12px;"><?php echo htmlspecialchars($user['region'] ?? 'Algérie'); ?></td>
                            <td style="font-size:12px;">
                                <?php if ($user['role'] === 'client'): ?>
                                    <span style="color:#3b82f6;"><?php echo $user['reservation_count']; ?> rés.</span>
                                <?php elseif ($user['role'] === 'transporter'): ?>
                                    <span style="color:#f59e0b;"><?php echo $user['vehicle_count']; ?> véh.</span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td style="font-size:12px; color:#64748b;"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="action-dropdown">
                                    <button class="action-btn" onclick="toggleMenu(this)"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="action-menu">
                                        <!-- Status Actions -->
                                        <?php if ($user['status'] !== 'active'): ?>
                                        <form method="POST"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><input type="hidden" name="action" value="update_status"><input type="hidden" name="user_id" value="<?php echo $user['id']; ?>"><input type="hidden" name="status" value="active">
                                            <button type="submit"><i class="fas fa-check-circle" style="color:#10b981;"></i> Activer</button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if ($user['status'] !== 'suspended' && $user['role'] !== 'admin'): ?>
                                        <form method="POST" onsubmit="return confirm('Suspendre cet utilisateur ?');"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><input type="hidden" name="action" value="update_status"><input type="hidden" name="user_id" value="<?php echo $user['id']; ?>"><input type="hidden" name="status" value="suspended">
                                            <button type="submit"><i class="fas fa-ban" style="color:#f59e0b;"></i> Suspendre</button>
                                        </form>
                                        <?php endif; ?>

                                        <!-- ID Verification in dropdown too -->
                                        <?php if ($user['id_card_url'] && !$user['id_is_verified']): ?>
                                        <a href="javascript:void(0)" data-user="<?php echo htmlspecialchars(json_encode([
                                            'id' => $user['id'],
                                            'name' => $user['name'],
                                            'email' => $user['email'],
                                            'role' => $user['role'],
                                            'id_card_url' => $user['id_card_url']
                                        ]), ENT_QUOTES, 'UTF-8'); ?>" onclick="openIdVerifyModal(JSON.parse(this.dataset.user))"><i class="fas fa-id-card" style="color:#3b82f6;"></i> Vérifier ID</a>
                                        <?php endif; ?>
                                        <?php if ($user['id_is_verified']): ?>
                                        <form method="POST"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><input type="hidden" name="action" value="unverify_id"><input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit"><i class="fas fa-times-circle" style="color:#ef4444;"></i> Révoquer vérification</button>
                                        </form>
                                        <?php endif; ?>

                                        <!-- Edit -->
                                        <button data-user="<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>" onclick="openEditModal(JSON.parse(this.dataset.user))"><i class="fas fa-edit" style="color:#8b5cf6;"></i> Modifier</button>

                                        <!-- Delete -->
                                        <?php if ($user['role'] !== 'admin'): ?>
                                        <form method="POST" onsubmit="return confirm('Supprimer définitivement cet utilisateur ? Cette action est irréversible.');"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="danger"><i class="fas fa-trash-alt"></i> Supprimer</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<!-- ═══════════════════════════════════════════════
     ID VERIFICATION MODAL (Unverified — shows Verify button)
═══════════════════════════════════════════════ -->
<div class="modal-overlay" id="idVerifyModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('idVerifyModal')"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-shield-alt"></i> Vérification d'Identité</h3>
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <div class="user-avatar avatar-default" style="width:48px;height:48px;font-size:18px;" id="verifyAvatar"></div>
            <div>
                <div style="font-weight:700; color:#f8fafc;" id="verifyName"></div>
                <div style="font-size:12px; color:#64748b;" id="verifyMeta"></div>
            </div>
        </div>
        <div class="id-preview">
            <img id="verifyIdImage" src="" alt="ID Card" onerror="this.style.display='none'; document.getElementById('verifyIdError').style.display='block';">
            <p id="verifyIdError" style="color:#ef4444; display:none; margin-top:10px;">Image introuvable ou lien brisé</p>
        </div>
        <div style="display:flex; gap:12px; justify-content:center; margin-top:20px;">
            <form method="POST" id="verifyIdForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="verify_id">
                <input type="hidden" name="user_id" id="verifyUserId">
                <button type="submit" class="btn btn-primary" style="padding:10px 24px;"><i class="fas fa-check-double"></i> Vérifier l'identité</button>
            </form>
            <button class="btn btn-outline" style="padding:10px 24px; border-color:rgba(255,255,255,0.1);" onclick="closeModal('idVerifyModal')"><i class="fas fa-times"></i> Fermer</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     ID VIEW MODAL (Already verified — shows check + close only)
═══════════════════════════════════════════════ -->
<div class="modal-overlay" id="idViewModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('idViewModal')"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-id-card"></i> Carte d'Identité</h3>
        <div class="id-preview">
            <img id="viewIdImage" src="" alt="ID Card" onerror="this.style.display='none'; document.getElementById('viewIdError').style.display='block';">
            <p id="viewIdError" style="color:#ef4444; display:none; margin-top:10px;">Image introuvable ou lien brisé</p>
        </div>
        <div class="verified-check">
            <i class="fas fa-check-circle"></i>
            <span>Identité vérifiée</span>
        </div>
        <div style="text-align:center; margin-top:16px;">
            <button class="btn btn-outline" style="padding:10px 24px; border-color:rgba(255,255,255,0.1);" onclick="closeModal('idViewModal')"><i class="fas fa-times"></i> Fermer</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     LEGACY VERIFY MODAL (for ?verify= URL param)
═══════════════════════════════════════════════ -->
<?php if ($verifyUser && $verifyUser['id_card_url']): ?>
<div class="modal-overlay active" id="verifyModalLegacy">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('verifyModalLegacy'); history.replaceState(null,'','users.php');"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-shield-alt"></i> Vérification d'Identité</h3>
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <div class="user-avatar avatar-default" style="width:48px;height:48px;font-size:18px;"><?php echo strtoupper(substr($verifyUser['name'], 0, 1)); ?></div>
            <div>
                <div style="font-weight:700; color:#f8fafc;"><?php echo htmlspecialchars($verifyUser['name']); ?></div>
                <div style="font-size:12px; color:#64748b;"><?php echo htmlspecialchars($verifyUser['email']); ?> · <?php echo ucfirst($verifyUser['role']); ?></div>
            </div>
        </div>
        <div class="id-preview">
            <img src="<?php echo URL_ROOT . htmlspecialchars($verifyUser['id_card_url']); ?>" alt="ID Card" onerror="this.style.display='none'; this.parentElement.innerHTML='<p style=\'color:#ef4444;\'>Image introuvable</p>';">
        </div>
        <?php if ($verifyUser['id_is_verified']): ?>
            <div class="verified-check">
                <i class="fas fa-check-circle"></i>
                <span>Identité déjà vérifiée</span>
            </div>
            <div style="text-align:center; margin-top:16px;">
                <button class="btn btn-outline" style="padding:10px 24px; border-color:rgba(255,255,255,0.1);" onclick="closeModal('verifyModalLegacy'); history.replaceState(null,'','users.php');"><i class="fas fa-times"></i> Fermer</button>
            </div>
        <?php else: ?>
            <div style="display:flex; gap:12px; justify-content:center; margin-top:20px;">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="verify_id">
                    <input type="hidden" name="user_id" value="<?php echo $verifyUser['id']; ?>">
                    <button type="submit" class="btn btn-primary" style="padding:10px 24px;"><i class="fas fa-check-double"></i> Approuver l'identité</button>
                </form>
                <button class="btn btn-outline" style="padding:10px 24px; border-color:rgba(255,255,255,0.1);" onclick="closeModal('verifyModalLegacy'); history.replaceState(null,'','users.php');"><i class="fas fa-times"></i> Fermer</button>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('editModal')"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-user-edit"></i> Modifier l'Utilisateur</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-row">
                <div class="form-group">
                    <label>Nom complet</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="text" name="phone" id="edit_phone">
                </div>
                <div class="form-group">
                    <label>Rôle</label>
                    <select name="role" id="edit_role">
                        <option value="client">Client</option>
                        <option value="transporter">Transporteur</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Région</label>
                <input type="text" name="region" id="edit_region" minlength="5">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;"><i class="fas fa-save"></i> Enregistrer les Modifications</button>
        </form>
    </div>
</div>

<?php include INC_PATH . 'toast.php'; ?>
<script src="<?php echo JS_URL; ?>dashboard.js"></script>
<script>
// ═══════════════════════════════════════════════
//  ACTION MENU TOGGLE
// ═══════════════════════════════════════════════
function toggleMenu(btn) {
    document.querySelectorAll('.action-menu.show').forEach(m => { if (m !== btn.nextElementSibling) m.classList.remove('show'); });
    btn.nextElementSibling.classList.toggle('show');
}
document.addEventListener('click', (e) => {
    if (!e.target.closest('.action-dropdown')) document.querySelectorAll('.action-menu.show').forEach(m => m.classList.remove('show'));
});

// ═══════════════════════════════════════════════
//  MODAL HELPERS
// ═══════════════════════════════════════════════
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// ═══════════════════════════════════════════════
//  EDIT USER MODAL
// ═══════════════════════════════════════════════
function openEditModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_phone').value = user.phone || '';
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_region').value = user.region || 'Algérie';
    document.getElementById('editModal').classList.add('active');
}

// ═══════════════════════════════════════════════
//  ID VERIFY MODAL (for unverified users)
// ═══════════════════════════════════════════════
function openIdVerifyModal(user) {
    // Close any open action menus
    document.querySelectorAll('.action-menu.show').forEach(m => m.classList.remove('show'));
    
    document.getElementById('verifyAvatar').textContent = user.name.charAt(0).toUpperCase();
    document.getElementById('verifyName').textContent = user.name;
    document.getElementById('verifyMeta').textContent = user.email + ' · ' + user.role.charAt(0).toUpperCase() + user.role.slice(1);
    document.getElementById('verifyUserId').value = user.id;
    
    const imgUrl = '<?php echo URL_ROOT; ?>' + user.id_card_url;
    document.getElementById('verifyIdError').style.display = 'none';
    document.getElementById('verifyIdImage').style.display = '';
    document.getElementById('verifyIdImage').src = imgUrl;
    
    document.getElementById('idVerifyModal').classList.add('active');
}

// ═══════════════════════════════════════════════
//  ID VIEW MODAL (for already verified users — check + close only)
// ═══════════════════════════════════════════════
function openIdViewModal(user) {
    document.querySelectorAll('.action-menu.show').forEach(m => m.classList.remove('show'));
    
    const imgUrl = '<?php echo URL_ROOT; ?>' + user.id_card_url;
    document.getElementById('viewIdError').style.display = 'none';
    document.getElementById('viewIdImage').style.display = '';
    document.getElementById('viewIdImage').src = imgUrl;
    
    document.getElementById('idViewModal').classList.add('active');
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.classList.remove('active');
    });
});

// Close modals on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    }
});
</script>
</body>
</html>
