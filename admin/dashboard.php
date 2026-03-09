<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
enforceRole('admin');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Administrateur - CargoConnect</title>
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
                <h1>Aperçu Système Administrateur</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Supervisez l'intégralité de la plateforme.</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                <div class="stat-details">
                    <h3>Clients Inscrits</h3>
                    <div class="num">0</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-truck-ramp-box"></i></div>
                <div class="stat-details">
                    <h3>Transporteurs Validés</h3>
                    <div class="num">0</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-chart-pie"></i></div>
                <div class="stat-details">
                    <h3>Volume Total Transporté</h3>
                    <div class="num">0 t</div>
                </div>
            </div>
        </div>
        
        <div class="form-card" style="text-align: center;">
            <i class="fas fa-tools" style="font-size: 40px; color: #ff8c00; margin-bottom: 15px;"></i>
            <h3>Module Administrateur (Phase 3 à venir)</h3>
        </div>
    </main>
</div>

<script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
