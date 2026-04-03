<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('transporter');

require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'reservation.php';

$db = new Database();
$pdo = $db->getConnection();
$transporter_id = $_SESSION['user_id'];

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Token de sécurité invalide.";
    } else {
        $job_id = (int)$_POST['job_id'];
        $new_status = $_POST['status'];
        
        if (updateJobStatus($job_id, $transporter_id, $new_status, $pdo)) {
            $_SESSION['success'] = "Statut de la mission mis à jour.";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour du statut.";
        }
        header('Location: jobs.php');
        exit;
    }
}

$jobs = getJobsByTransporter($transporter_id, $pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Missions - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    <style>
        .job-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.2s;
        }
        .job-card:hover {
            transform: translateX(5px);
            border-color: rgba(255, 140, 0, 0.3);
        }
        .job-status-indicator {
            width: 8px;
            height: 100%;
            min-height: 80px;
            border-radius: 4px;
        }
        .status-accepted { background: #3b82f6; } /* Blue */
        .status-in_progress { background: #f59e0b; } /* Orange */
        .status-completed { background: #10b981; } /* Green */
        .status-cancelled { background: #ef4444; } /* Red */
        
        .job-info {
            flex: 1;
        }
        .job-route {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #fff;
        }
        .job-details {
            color: #94a3b8;
            font-size: 0.9rem;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .job-detail-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .job-actions {
            min-width: 200px;
        }
        .action-form select {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            width: 100%;
            margin-bottom: 10px;
            outline: none;
        }
        .action-form button {
            width: 100%;
            transition: all 0.3s ease;
        }
        .action-form button.loading {
            opacity: 0.7;
            pointer-events: none;
            cursor: not-allowed;
        }
        .action-form button.loading i {
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="dashboard-layout">
    <?php include INC_PATH . 'sidebar.php'; ?>
    <main class="main-content">
        <?php include INC_PATH . 'topbar.php'; ?>
        
        <div class="page-header">
            <div>
                <h1>Mes Missions</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Suivez et mettez à jour l'évolution de vos transports en cours.</p>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <?php if (empty($jobs)): ?>
                <div style="text-align: center; padding: 50px 20px; background: rgba(30, 41, 59, 0.5); border-radius: 12px; border: 1px dashed rgba(255, 255, 255, 0.1);">
                    <i class="fas fa-route" style="font-size: 50px; color: #475569; margin-bottom: 15px;"></i>
                    <h3 style="color: #fff;">Aucune mission pour l'instant</h3>
                    <p style="color: #94a3b8; margin-top: 10px;">Allez dans "Demandes en attente" pour accepter de nouvelles missions.</p>
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-status-indicator status-<?php echo htmlspecialchars($job['status']); ?>"></div>
                        
                        <div class="job-info">
                            <div class="job-route">
                                <?php echo htmlspecialchars($job['pickup_location']); ?> <i class="fas fa-long-arrow-alt-right" style="color: #64748b; margin: 0 10px;"></i> <?php echo htmlspecialchars($job['destination']); ?>
                            </div>
                            <div class="job-details">
                                <div class="job-detail-item">
                                    <i class="fas fa-calendar" style="color: #3b82f6;"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($job['reservation_date'])); ?>
                                </div>
                                <div class="job-detail-item">
                                    <i class="fas fa-user" style="color: #8b5cf6;"></i>
                                    <?php echo htmlspecialchars($job['client_name']); ?> (<?php echo htmlspecialchars($job['client_phone']); ?>)
                                </div>
                                <div class="job-detail-item">
                                    <i class="fas fa-truck" style="color: #f59e0b;"></i>
                                    Véhicule: <?php echo htmlspecialchars($job['plate_number']); ?>
                                </div>
                                <div class="job-detail-item">
                                    <i class="fas fa-money-bill-wave" style="color: #10b981;"></i>
                                    <?php echo ($job['price'] > 0) ? number_format($job['price'], 2) . ' DA' : 'N/A'; ?>
                                </div>
                            </div>
                        </div>

                        <div class="job-actions">
                            <?php if ($job['status'] === 'accepted' || $job['status'] === 'in_progress'): ?>
                                <form method="POST" action="" class="action-form" onsubmit="this.querySelector('button').classList.add('loading'); this.querySelector('button').querySelector('i').className='fas fa-spinner';">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    
                                    <select name="status" required>
                                        <option value="accepted" <?php echo $job['status'] === 'accepted' ? 'selected' : ''; ?>>Acceptée (En attente de départ)</option>
                                        <option value="in_progress" <?php echo $job['status'] === 'in_progress' ? 'selected' : ''; ?>>En cours (En transit)</option>
                                        <option value="completed" <?php echo $job['status'] === 'completed' ? 'selected' : ''; ?>>Terminée (Livrée)</option>
                                    </select>
                                    
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync-alt"></i> Mettre à jour</button>
                                </form>
                            <?php else: ?>
                                <div style="text-align: center;">
                                    <?php if ($job['status'] === 'completed'): ?>
                                        <span class="badge badge-success" style="font-size: 14px; padding: 8px 15px;"><i class="fas fa-check-circle"></i> Terminée</span>
                                    <?php elseif ($job['status'] === 'cancelled'): ?>
                                        <span class="badge badge-danger" style="font-size: 14px; padding: 8px 15px;"><i class="fas fa-times-circle"></i> Annulée</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include INC_PATH . 'toast.php'; ?>
<script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
