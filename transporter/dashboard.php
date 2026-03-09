<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('transporter');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Transporteur - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    
    <!-- Security token for JavaScript Hooks -->
    <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token']); ?>">
</head>
<body>

<div class="dashboard-layout">
    <?php include INC_PATH . 'sidebar.php'; ?>
<main class="main-content">
        <?php include INC_PATH . 'topbar.php'; ?>
        
        <div class="page-header">
            <div>
                <h1>Aperçu Transporteur</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Gérez vos missions et véhicules.</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-truck"></i></div>
                <div class="stat-details">
                    <h3>Véhicules Actifs</h3>
                    <div class="num">0</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-list"></i></div>
                <div class="stat-details">
                    <h3>Demandes en Attente</h3>
                    <div class="num">0</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-wallet"></i></div>
                <div class="stat-details">
                    <h3>Revenus du mois</h3>
                    <div class="num">0 DA</div>
                </div>
            </div>
        </div>
        
        <div class="form-card" style="text-align: center;">
            <i class="fas fa-tools" style="font-size: 40px; color: #ff8c00; margin-bottom: 15px;"></i>
            <h3>Module Transporteur (Phase 3 à venir)</h3>
        </div>
    </main>
</div>

<script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
