<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('client');

require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'reservation.php';

$database = new Database();
$pdo = $database->getConnection();

$summary = getClientReservationsSummary($_SESSION['user_id'], $pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Client - CargoConnect</title>
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
                <h1>Aperçu de votre espace</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Bienvenue sur votre tableau de bord client.</p>
            </div>
            <a href="request_transport.php" class="btn-primary" style="width: auto; padding: 10px 20px;"><i class="fas fa-plus"></i> Nouvelle demande</a>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-box-open"></i></div>
                <div class="stat-details">
                    <h3>Total Réservations</h3>
                    <div class="num"><?php echo $summary['total'] ?? 0; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                <div class="stat-details">
                    <h3>En Attente</h3>
                    <div class="num"><?php echo $summary['pending'] ?? 0; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-truck-moving"></i></div>
                <div class="stat-details">
                    <h3>En Cours</h3>
                    <div class="num"><?php echo ($summary['accepted'] + $summary['in_progress']) ?? 0; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-details">
                    <h3>Terminées</h3>
                    <div class="num"><?php echo $summary['completed'] ?? 0; ?></div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2 style="margin-bottom: 20px; font-size: 20px;">Actions Rapides</h2>
        <div class="actions-grid">
            <div class="action-card">
                <i class="fas fa-map-location-dot"></i>
                <h3>Planifier un Transport</h3>
                <p>Besoin de déplacer de la marchandise ? Remplissez notre formulaire rapide pour trouver un transporteur.</p>
                <a href="request_transport.php" class="btn-primary">Commencer</a>
            </div>
            
            <div class="action-card">
                <i class="fas fa-clipboard-list"></i>
                <h3>Suivi des Expéditions</h3>
                <p>Consultez l'état de vos réservations, vérifiez les détails et suivez vos envois en cours.</p>
                <a href="reservations.php" class="btn-secondary" style="border-color: #ff8c00; color: #ff8c00;">Voir l'historique</a>
            </div>
        </div>

    </main>
</div>

<script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
