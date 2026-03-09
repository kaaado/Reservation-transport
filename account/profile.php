<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once CONF_PATH . 'database.php';

$database = new Database();
$pdo = $database->getConnection();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch Profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$userInitials = strtoupper(substr(trim($user['name']), 0, 2));

function getRoleDashboard($role) {
    if ($role === 'client') return '/Reservation-transport/client/dashboard.php';
    if ($role === 'transporter') return '/Reservation-transport/transporter/dashboard.php';
    if ($role === 'admin') return '/Reservation-transport/admin/dashboard.php';
    return '<?php echo URL_ROOT; ?>auth/login.php';
}

$dashPath = getRoleDashboard($role);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token']); ?>">
    <style>
        .profile-banner {
            height: 250px;
            background: linear-gradient(135deg, rgba(255,140,0,0.8), rgba(239,68,68,0.8)), url('../assets/bg.jpg') center/cover;
            border-radius: 16px 16px 0 0;
            position: relative;
        }
        
        .profile-picture-wrapper {
            position: absolute;
            bottom: -60px;
            left: 40px;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 6px solid #0f172a;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }
        
        .profile-picture-wrapper span {
            font-size: 50px;
            font-weight: 700;
            color: #ff8c00;
            letter-spacing: 2px;
        }

        .profile-actions {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .profile-body {
            padding: 80px 40px 40px 40px;
            background: rgba(30, 41, 59, 0.4);
            border-radius: 0 0 16px 16px;
            border: 1px solid rgba(255,255,255,0.05);
            border-top: none;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 40px;
        }

        .profile-name {
            font-size: 32px;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }

        .profile-role {
            font-size: 16px;
            color: #ff8c00;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .profile- bio {
            color: #94a3b8;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
            max-width: 600px;
        }

        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-list li {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            color: #cbd5e1;
            font-size: 15px;
        }

        .info-list i {
            color: #64748b;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        
        .timeline-box {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .timeline-box h3 {
            font-size: 16px;
            color: white;
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 10px;
        }
        
        /* Mobile overrides carefully matching dashboard */
        @media (max-width: 991px) {
            .profile-body {
                grid-template-columns: 1fr;
                padding: 80px 20px 20px 20px;
            }
            .profile-picture-wrapper {
                left: 50%;
                transform: translateX(-50%);
            }
            .profile-name, .profile-role { text-align: center; }
        }
    </style>
</head>
<body>

<div class="dashboard-layout">
    
    <!-- Unified Sidebar -->
    <?php include INC_PATH . 'sidebar.php'; ?>
<main class="main-content">
        <?php include INC_PATH . 'topbar.php'; ?>
        
        <div style="max-width: 1200px; margin: 0 auto;">
            
            <div class="profile-banner">
                <div class="profile-picture-wrapper">
                    <span><?php echo $userInitials; ?></span>
                </div>
                <div class="profile-actions">
                    <a href="settings.php" class="btn-primary" style="background: rgba(15,23,42,0.8); backdrop-filter: blur(5px); border-color: rgba(255,255,255,0.1);"><i class="fas fa-pen"></i> Modifier le profil</a>
                </div>
            </div>

            <div class="profile-body">
                <div>
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <div class="profile-role"><i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($role); ?></div>
                    
                    <p class="profile-bio">
                        Membre actif de la plateforme CargoConnect. En tant que <?php echo htmlspecialchars($role); ?>, vous pouvez directement interagir avec vos réservations et opérations logistiques depuis votre espace dédié.
                    </p>

                    <h3 style="margin: 30px 0 15px 0; color: white; font-size: 18px;">Informations de Contact</h3>
                    <ul class="info-list">
                        <li><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></li>
                        <li><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone'] ?? 'Non renseigné'); ?></li>
                        <li><i class="fas fa-map-marker-alt"></i> Algérie</li>
                    </ul>
                </div>
                
                <div>
                    <div class="timeline-box">
                        <h3>À propos du compte</h3>
                        <ul class="info-list" style="margin-top: 15px;">
                            <li><i class="fas fa-calendar-alt"></i> <span>Rejoint le:<br><strong style="color:white;"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></strong></span></li>
                            <li><i class="fas fa-clock"></i> <span>Dernière connexion:<br><strong style="color:white;"><?php echo $user['last_login'] ? date('d M Y, H:i', strtotime($user['last_login'])) : 'Aujourd\'hui'; ?></strong></span></li>
                        </ul>
                    </div>
                </div>
            </div>
            
        </div>

    </main>
</div>

<script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
