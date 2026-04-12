<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('client');

require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'reservation.php';

$error = null;
$success = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Check
    if (!isset($_POST['csrf_token'])) {
        die("Invalid CSRF block.");
    }
    validateCSRF($_POST['csrf_token']);

    $database = new Database();
    $pdo = $database->getConnection();
    
    $pickup = trim($_POST['pickup']);
    $destination = trim($_POST['destination']);
    $cargoType = trim($_POST['cargo_type']);
    $weight = trim($_POST['weight']) === '' ? null : trim($_POST['weight']);
    $volume = trim($_POST['volume']) === '' ? null : trim($_POST['volume']);
    $date = trim($_POST['reservation_date']);
    $priceType = isset($_POST['price_type']) && $_POST['price_type'] === 'fixed' ? 'fixed' : 'negotiable';
    $price = null;
    if ($priceType === 'fixed' && !empty($_POST['price']) && is_numeric($_POST['price'])) {
        $price = (float)$_POST['price'];
    }
    
    $minDateTs = strtotime('+2 days');
    $givenDateTs = strtotime($date);
    
    if (empty($pickup) || empty($destination) || empty($date)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else if ($givenDateTs < $minDateTs) {
        $error = "La date d'expédition souhaitée doit être au moins 2 jours dans le futur.";
    } else if ($priceType === 'fixed' && ($price === null || $price <= 0)) {
        $error = "Le prix doit être supérieur à 0 pour un prix fixe.";
    } else {
        $result = createReservation($_SESSION['user_id'], $pickup, $destination, $cargoType, $weight, $volume, $date, $price, $priceType, $pdo);
        if ($result === true) {
            $success = "Votre demande de transport a été enregistrée avec succès. Un transporteur vous sera assigné prochainement.";
        } else {
            $error = "Une erreur s'est produite lors de l'enregistrement de votre demande : " . $result;
        }
    }
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demander un Transport - CargoConnect</title>
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
        <div class="page-header" style="max-width: 800px; margin: 0 auto 30px auto;">
            <div>
                <h1>Demander un transport</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Remplissez les détails ci-dessous pour planifier votre expédition.</p>
            </div>
            <a href="<?php echo $dashPath; ?>" class="btn-secondary" style="width: auto; padding: 10px 20px;"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="max-width: 800px; margin: 0 auto 20px auto;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="max-width: 800px; margin: 0 auto 20px auto;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="form-card" style="max-width: 800px; margin: 0 auto;">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                
                <h3 style="margin-bottom: 20px; font-size: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">Détails de l'itinéraire</h3>
                
                <div class="form-row">
                    <div class="form-group-dash">
                        <label>Lieu d'enlèvement (Départ) *</label>
                        <input type="text" name="pickup" required placeholder="Ex: Entrepôt A, Zone Industrielle Rouiba">
                    </div>
                    <div class="form-group-dash">
                        <label>Destination (Arrivée) *</label>
                        <input type="text" name="destination" required placeholder="Ex: Magasin Central, Alger Centre">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-dash">
                        <label>Date souhaitée *</label>
                        <input type="datetime-local" name="reservation_date" required min="<?php echo date('Y-m-d\TH:i', strtotime('+2 days')); ?>">
                    </div>
                    <div class="form-group-dash">
                        <label>Type de service *</label>
                        <select name="service_type" required>
                            <option value="transport">Transport de Marchandises</option>
                            <option value="moving">Déménagement</option>
                        </select>
                    </div>
                </div>

                <h3 style="margin-bottom: 20px; margin-top: 10px; font-size: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">Détails de la marchandise</h3>

                <div class="form-row">
                    <div class="form-group-dash">
                        <label>Nature de la marchandise *</label>
                        <input type="text" name="cargo_type" required placeholder="Ex: Matériaux de construction, Électroménager...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-dash">
                        <label>Poids estimé (en kg)</label>
                        <input type="number" step="0.01" name="weight" placeholder="Ex: 150.50">
                    </div>
                    <div class="form-group-dash">
                        <label>Volume estimé (en m³)</label>
                        <input type="number" step="0.01" name="volume" placeholder="Ex: 5.0">
                    </div>
                </div>

                <h3 style="margin-bottom: 20px; margin-top: 10px; font-size: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">Tarification</h3>

                <div class="form-row">
                    <div class="form-group-dash">
                        <label>Type de prix *</label>
                        <select name="price_type" id="price_type" required>
                            <option value="fixed">Prix fixe</option>
                            <option value="negotiable">Prix à négocier</option>
                        </select>
                    </div>
                    <div class="form-group-dash" id="price_container">
                        <label>Votre prix proposé (DA)</label>
                        <input type="number" step="0.01" name="price" id="price_input" placeholder="Ex: 5000">
                    </div>
                </div>

                <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-primary" style="width: auto; padding: 12px 30px; font-size: 16px;">
                        Soumettre la demande <i class="fas fa-paper-plane" style="margin-left: 8px;"></i>
                    </button>
                </div>

            </form>
        </div>

    </main>
</div>

<script src="<?php echo JS_URL; ?>dashboard.js"></script>
<script>
    document.getElementById('price_type').addEventListener('change', function() {
        if (this.value === 'negotiable') {
            document.getElementById('price_container').style.display = 'none';
        } else {
            document.getElementById('price_container').style.display = 'block';
        }
    });
</script>
</body>
</html>
