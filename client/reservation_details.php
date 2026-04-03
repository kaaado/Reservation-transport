<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('client');

require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'reservation.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: reservations.php");
    exit();
}

$reservation_id = $_GET['id'];

$database = new Database();
$pdo = $database->getConnection();

$res = getReservationDetails($reservation_id, $_SESSION['user_id'], $pdo);

if (!$res) {
    header("Location: reservations.php");
    exit();
}

// Handle Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF invalid");
    }
    $result = cancelReservation($reservation_id, $_SESSION['user_id'], $pdo);
    if ($result === true) {
        $_SESSION['success'] = "La réservation a été annulée.";
        header("Location: reservation_details.php?id=" . $reservation_id);
        exit;
    } else {
        $error = $result;
    }
}

$timeline = getReservationTimeline($reservation_id, $pdo);

if (!function_exists('getRoleDashboardPath')) {
    function getRoleDashboardPath($role) {
        if ($role === 'client') return URL_ROOT . 'client/dashboard.php';
        if ($role === 'transporter') return URL_ROOT . 'transporter/dashboard.php';
        if ($role === 'admin') return URL_ROOT . 'admin/dashboard.php';
        return URL_ROOT . 'auth/login.php';
    }
}
$dashPath = getRoleDashboardPath($_SESSION['role']);

function getStatusBadgeClass($status) {
    switch($status) {
        case 'pending': return 'status-pending';
        case 'accepted': return 'status-accepted';
        case 'in_progress': return 'status-in_progress';
        case 'completed': return 'status-completed';
        case 'cancelled': return 'status-cancelled';
        default: return '';
    }
}

function getStatusLabel($status) {
    switch($status) {
        case 'pending': return 'En attente';
        case 'accepted': return 'Accepté';
        case 'in_progress': return 'En cours';
        case 'completed': return 'Terminé';
        case 'cancelled': return 'Annulé';
        default: return $status;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Réservation #<?php echo $res['id']; ?> - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    
    <style>
        .timeline-container {
            background: rgba(30, 41, 59, 0.65);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 40px 30px;
            margin-bottom: 30px;
            width: 100%;
            height: 250px;

        }
        .timeline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            max-width: 900px;
            margin: 0 auto;
        }
        .timeline::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            z-index: 1;
        }
        .timeline-progress {
            position: absolute;
            top: 20px;
            left: 20px;
            height: 4px;
            background: #10b981; /* Green */
            z-index: 1;
            transition: width 0.4s ease;
        }
        .timeline-step {
            position: relative;
            z-index: 2;
            background: #0f172a;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 3px solid #334155;
            color: #64748b;
            font-size: 16px;
            transition: all 0.3s;
        }
        .timeline-step.completed {
            background: #10b981;
            border-color: #10b981;
            color: #fff;
        }
        .timeline-step.active {
            background: #0f172a;
            border-color: #3b82f6; /* Blue */
            color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        }
        .timeline-step.error {
            background: #ef4444;
            border-color: #ef4444;
            color: #fff;
        }
        .step-label {
            position: absolute;
            top: 60px;
            text-align: center;
            width: 120px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 13px;
            font-weight: 600;
            color: #94a3b8;
        }
        .timeline-step.active .step-label { color: #f8fafc; }
        .timeline-step.completed .step-label { color: #10b981; }
        .timeline-step.error .step-label { color: #ef4444; }
    </style>
    
    <!-- Security token for JavaScript Hooks -->
    <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token']); ?>">
</head>
<body>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <?php include INC_PATH . 'sidebar.php'; ?>
<!-- Main Content -->
    <main class="main-content">
        <?php include INC_PATH . 'topbar.php'; ?>
        <div class="page-header">
            <div>
                <h1>Détails de la réservation #<?php echo $res['id']; ?></h1>
                <p style="color: #64748b; margin-top: 5px;">Consultez les informations complètes de votre demande.</p>
            </div>
            <a href="<?php echo $dashPath; ?>" class="btn-secondary" style="width: auto; padding: 10px 20px;"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a>
        </div>

        <?php
        $statuses = ['pending', 'accepted', 'in_progress', 'completed'];
        $statusLabels = [
            'pending' => ['icon' => 'fa-clipboard-list', 'label' => 'Reçue'],
            'accepted' => ['icon' => 'fa-check-double', 'label' => 'Acceptée'],
            'in_progress' => ['icon' => 'fa-truck-fast', 'label' => 'En cours'],
            'completed' => ['icon' => 'fa-box-check', 'label' => 'Livrée']
        ];
        
        $currentIdx = array_search($res['status'], $statuses);
        $isCancelled = in_array($res['status'], ['cancelled', 'rejected']);
        
        // Progress percentage for the bar
        if ($isCancelled) {
            $progressWidth = 100;
        } else {
            $progressWidth = $currentIdx !== false ? ($currentIdx / (count($statuses) - 1)) * 100 : 0;
        }
        ?>

        <div class="timeline-container">
            <h3 style="margin-bottom: 30px; text-align: center; color: #f8fafc; font-size: 18px;">Suivi de votre expédition</h3>
            <div class="timeline">
                <div class="timeline-progress" style="width: <?php echo $progressWidth; ?>%; <?php echo $isCancelled ? 'background: #ef4444;' : ''; ?>"></div>
                
                <?php if ($isCancelled): ?>
                    <div class="timeline-step completed" style="background:#475569; border-color:#475569;">
                        <i class="fas fa-clipboard-list"></i>
                        <div class="step-label" style="color: #64748b;">Reçue</div>
                    </div>
                    <div class="timeline-step error" style="margin-left: auto;">
                        <i class="fas fa-times"></i>
                        <?php 
                        $cancelledLog = array_filter($timeline, fn($l) => in_array($l['new_status'], ['cancelled', 'rejected']));
                        $lastLog = end($cancelledLog);
                        ?>
                        <div class="step-label">
                            Annulée / Rejetée
                            <?php if ($lastLog): ?>
                                <br><small style="font-weight: 400; font-size: 10px; opacity: 0.7;"><?php echo date('d/m H:i', strtotime($lastLog['created_at'])); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($statuses as $idx => $s): ?>
                        <?php 
                        $statusClass = '';
                        if ($idx < $currentIdx) $statusClass = 'completed';
                        else if ($idx === $currentIdx) $statusClass = 'active';
                        
                        $stepLog = array_filter($timeline, fn($l) => $l['new_status'] === $s);
                        $logEntry = !empty($stepLog) ? end($stepLog) : null;
                        ?>
                        <div class="timeline-step <?php echo $statusClass; ?>">
                            <i class="fas <?php echo $statusLabels[$s]['icon']; ?>"></i>
                            <div class="step-label">
                                <?php echo $statusLabels[$s]['label']; ?>
                                <?php if ($logEntry): ?>
                                    <br><small style="font-weight: 400; font-size: 10px; opacity: 0.7; display: block; margin-top: 5px;">
                                        <?php echo date('d/m H:i', strtotime($logEntry['created_at'])); ?>
                                        <br>par <?php echo htmlspecialchars($logEntry['author_name']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($res['status'] === ReservationStatus::PENDING): ?>
            <div style="margin-top: 100px; text-align: center;">
                <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette demande ?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn btn-outline" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3); width: auto; padding: 10px 25px;">
                        <i class="fas fa-ban"></i> Annuler ma demande
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div class="details-grid">
            
            <!-- Itinerary Card -->
            <div class="details-card">
                <h3><i class="fas fa-route" style="color: #ff8c00;"></i> Itinéraire & Calendrier</h3>
                
                <div class="detail-row">
                    <span class="label">Statut Actuel</span>
                    <span class="value"><span class="status-badge <?php echo getStatusBadgeClass($res['status']); ?>"><?php echo getStatusLabel($res['status']); ?></span></span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Date de demande originale</span>
                    <span class="value"><?php echo date('d/m/Y à H:i', strtotime($res['created_at'])); ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Date Intervention Souhaitée</span>
                    <span class="value" style="color: #ff8c00; font-weight: 700;"><?php echo date('d/m/Y à H:i', strtotime($res['reservation_date'])); ?></span>
                </div>
                
                <div style="margin-top: 30px; padding: 20px; background: rgba(255, 255, 255, 0.05); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                        <div style="display: flex; flex-direction: column; align-items: center;">
                            <i class="fas fa-circle" style="color: #64748b; font-size: 12px;"></i>
                            <div style="width: 2px; height: 30px; background: #cbd5e1; margin: 5px 0;"></div>
                            <i class="fas fa-location-dot" style="color: #16a34a; font-size: 16px;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="margin-bottom: 15px;">
                                <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Point de Départ</div>
                                <div style="color: #f8fafc; font-weight: 500;"><?php echo htmlspecialchars($res['pickup_location']); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Point d'Arrivée</div>
                                <div style="color: #f8fafc; font-weight: 500;"><?php echo htmlspecialchars($res['destination']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cargo & Transporter Card -->
            <div style="display: flex; flex-direction: column; gap: 30px;">
                <div class="details-card">
                    <h3><i class="fas fa-box" style="color: #ff8c00;"></i> Informations Marchandise</h3>
                    
                    <div class="detail-row">
                        <span class="label">Type de cargaison</span>
                        <span class="value"><?php echo htmlspecialchars($res['cargo_type']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="label">Poids estimé</span>
                        <span class="value"><?php echo $res['weight'] ? htmlspecialchars($res['weight']) . ' kg' : 'Non spécifié'; ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="label">Volume estimé</span>
                        <span class="value"><?php echo $res['volume'] ? htmlspecialchars($res['volume']) . ' m³' : 'Non spécifié'; ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="label">Prix Cible Transporteur</span>
                        <span class="value"><?php echo ($res['price'] > 0) ? number_format($res['price'], 2) . ' DA' : '<span style="color:#64748b;font-style:italic;">En attente de devis</span>'; ?></span>
                    </div>
                </div>

                <div class="details-card">
                    <h3><i class="fas fa-id-card" style="color: #ff8c00;"></i> Informations Transporteur</h3>
                    
                    <?php if($res['transporter_name']): ?>
                        <div class="detail-row">
                            <span class="label">Nom du transporteur</span>
                            <span class="value"><?php echo htmlspecialchars($res['transporter_name']); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label">Téléphone de contact</span>
                            <span class="value"><a href="tel:<?php echo htmlspecialchars($res['transporter_phone']); ?>" style="color: #0284c7; text-decoration: none;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($res['transporter_phone']); ?></a></span>
                        </div>

                        <div class="detail-row">
                            <span class="label">Véhicule assigné</span>
                            <span class="value"><?php echo htmlspecialchars($res['vehicle_type']); ?> (<?php echo htmlspecialchars($res['plate_number']); ?>)</span>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: #64748b;">
                            <i class="fas fa-clock" style="font-size: 24px; margin-bottom: 10px; color: #cbd5e1; display:block;"></i>
                            Aucun transporteur n'a encore été assigné à cette demande. Veuillez patienter pendant que nous trouvons le véhicule adéquat.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>
</div>

<script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
