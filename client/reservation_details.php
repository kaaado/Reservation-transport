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
    // If not found or does not belong to client
    header("Location: reservations.php");
    exit();
}

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
                        <span class="value"><?php echo $res['price'] ? htmlspecialchars($res['price']) . ' DA' : '<span style="color:#64748b;font-style:italic;">En attente de devis</span>'; ?></span>
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
