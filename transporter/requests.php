<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('transporter');

require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'reservation.php';
require_once FUNC_PATH . 'vehicle.php';

$db = new Database();
$pdo = $db->getConnection();
$transporter_id = $_SESSION['user_id'];

// Check if transporter has vehicles
$has_vehicles = hasActiveVehicles($transporter_id, $pdo);
$vehicles = getVehiclesByTransporter($transporter_id, $pdo);
$active_vehicles = array_filter($vehicles, fn($v) => $v['status'] === 'active');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF validation failed");
    }
    
    $request_id = (int)$_POST['request_id'];
        
        if ($_POST['action'] === 'accept') {
            $vehicle_id = (int)$_POST['vehicle_id'];
            $negotiated_price = isset($_POST['negotiated_price']) ? (float)$_POST['negotiated_price'] : null;

            if (!$has_vehicles || empty($vehicle_id)) {
                $_SESSION['error'] = "Vous devez sélectionner un véhicule actif pour accepter une mission.";
            } else {
                $result = acceptReservation($request_id, $transporter_id, $vehicle_id, $pdo, $negotiated_price);
                if ($result === true) {
                    $_SESSION['success'] = "Mission acceptée avec succès!";
                    header('Location: jobs.php');
                    exit;
                } else {
                    $_SESSION['error'] = $result;
                }
            }
        } elseif ($_POST['action'] === 'reject') {
            // Usually any transporter can ignore it, but standard might reject
            // Depending on logic, reject might just mean it stays pending for others. But here we cancel it or something.
            // Let's just say we don't do 'reject' global cancel, but 'ignore' for user... 
            // The instruction says "Reject request -> status: cancelled or rejected".
            $result = rejectReservation($request_id, $transporter_id, $pdo);
            if ($result === true) {
                $_SESSION['success'] = "Demande rejetée/annulée.";
            } else {
                $_SESSION['error'] = $result;
            }
        }
    }

$requests = getPendingRequests($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demandes Disponibles - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    <style>
        .request-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .request-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        .request-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            border-color: rgba(59, 130, 246, 0.4);
            transform: translateY(-3px);
        }
        .req-route {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .req-location {
            flex: 1;
        }
        .req-loc-title { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
        .req-loc-value { font-weight: 600; color: #fff; font-size: 15px; }
        .req-arrow { color: #ff8c00; font-size: 18px; }
        
        .req-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #cbd5e1;
            font-size: 14px;
        }
        .req-detail strong { color: #fff; }
        .price-badge {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 18px;
            text-align: center;
            margin: 15px 0;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .accept-form {
            display: flex;
            gap: 10px;
            flex-direction: column;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .select-vehicle {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 10px;
            border-radius: 6px;
            width: 100%;
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
        .btn-group .btn {
            flex: 1;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .btn.loading {
            opacity: 0.7;
            pointer-events: none;
            cursor: not-allowed;
        }
        .btn.loading i {
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
                <h1>Demandes de Transport</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Consultez et acceptez les nouvelles demandes des clients.</p>
            </div>
        </div>

        <?php if (!$has_vehicles): ?>
            <div style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; padding: 15px 20px; border-radius: 6px; margin-top: 20px; color: #fcd34d;">
                <i class="fas fa-exclamation-triangle" style="margin-right: 10px;"></i>
                Vous devez avoir au moins un véhicule actif pour accepter des missions. <a href="vehicle_form.php" style="color: #fff; text-decoration: underline;">Ajouter un véhicule</a>
            </div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
            <div style="text-align: center; padding: 60px 20px; background: rgba(30, 41, 59, 0.5); border-radius: 12px; border: 1px dashed rgba(255, 255, 255, 0.1); margin-top: 20px;">
                <i class="fas fa-box-open" style="font-size: 60px; color: #475569; margin-bottom: 20px;"></i>
                <h3 style="color: #fff;">Aucune demande pour le moment</h3>
                <p style="color: #94a3b8;">Revenez plus tard pour vérifier les nouvelles opportunités.</p>
            </div>
        <?php else: ?>
            <div class="request-grid">
                <?php foreach ($requests as $req): ?>
                    <div class="request-card">
                        <div class="req-route">
                            <div class="req-location">
                                <div class="req-loc-title">Départ</div>
                                <div class="req-loc-value"><?php echo htmlspecialchars($req['pickup_location']); ?></div>
                            </div>
                            <i class="fas fa-arrow-right req-arrow"></i>
                            <div class="req-location" style="text-align: right;">
                                <div class="req-loc-title">Arrivée</div>
                                <div class="req-loc-value"><?php echo htmlspecialchars($req['destination']); ?></div>
                            </div>
                        </div>
                        
                        <div class="req-detail">
                            <span><i class="fas fa-calendar-day" style="color: #3b82f6;"></i> Date prèvue:</span>
                            <strong><?php echo date('d/m/Y H:i', strtotime($req['reservation_date'])); ?></strong>
                        </div>
                        <div class="req-detail">
                            <span><i class="fas fa-cubes" style="color: #a855f7;"></i> Type:</span>
                            <strong><?php echo htmlspecialchars($req['cargo_type']); ?></strong>
                        </div>
                        <div class="req-detail">
                            <span><i class="fas fa-weight-hanging" style="color: #ef4444;"></i> Poids / Vol:</span>
                            <strong><?php echo number_format($req['weight'], 2); ?> Kg / <?php echo number_format($req['volume'], 2); ?> m³</strong>
                        </div>
                        
                        <div class="price-badge">
                            <?php echo ($req['price'] > 0) ? number_format($req['price'], 2) . ' DA' : 'Prix à négocier'; ?>
                        </div>

                        <?php if ($has_vehicles): ?>
                        <form method="POST" action="" class="accept-form" onsubmit="return confirmAction(this, event);">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($req['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            
                            <?php if (!($req['price'] > 0)): ?>
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="font-size: 12px; color: #94a3b8;">Saisir le prix négocié (DA)</label>
                                <input type="number" name="negotiated_price" step="0.01" min="1" class="select-vehicle" placeholder="Prix convenu..." required>
                            </div>
                            <?php endif; ?>

                            <select name="vehicle_id" class="select-vehicle" required title="Choisissez un véhicule pour cette mission" style="margin-bottom: 10px;">
                                <option value="" disabled selected>-- Sélectionnez un véhicule assigné --</option>
                                <?php foreach ($active_vehicles as $v): ?>
                                    <option value="<?php echo htmlspecialchars($v['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($v['plate_number'] . ' (' . $v['capacity'] . 'T)', ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div class="btn-group">
                                <button type="submit" name="action" value="accept" class="btn btn-primary" onclick="this.form.actionVal='accept'; this.form.clickedBtn=this;">
                                    <i class="fas fa-check"></i> Accepter
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-outline" style="color:#ef4444; border-color: rgba(239, 68, 68, 0.3);" formnovalidate onclick="this.form.actionVal='reject'; this.form.clickedBtn=this;">
                                    <i class="fas fa-times"></i> Rejeter
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php include INC_PATH . 'toast.php'; ?>
<script src="<?php echo JS_URL; ?>dashboard.js"></script>
<script>
function confirmAction(form, event) {
    if (form.actionVal === 'reject') {
        if(!confirm("Êtes-vous sûr de vouloir rejeter cette demande ?")) {
            return false;
        }
    }
    
    // Add loading states
    if (form.clickedBtn) {
        form.clickedBtn.classList.add('loading');
        form.clickedBtn.innerHTML = '<i class="fas fa-spinner"></i> Patientez...';
    }
    
    return true;
}
</script>
</body>
</html>
