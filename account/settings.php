<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once CONF_PATH . 'database.php';

$database = new Database();
$pdo = $database->getConnection();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$error = null;
$success = null;

// Fetch Profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

function getRoleDashboard($role) {
    if ($role === 'client') return '/Reservation-transport/client/dashboard.php';
    if ($role === 'transporter') return '/Reservation-transport/transporter/dashboard.php';
    if ($role === 'admin') return '/Reservation-transport/admin/dashboard.php';
    return '<?php echo URL_ROOT; ?>auth/login.php';
}
$dashPath = getRoleDashboard($role);

// Handle Form Submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF block.");
    }
    
    $action = $_POST['action'] ?? '';
    
    // 1. Update Profile
    if ($action === 'update_profile') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        $check = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR phone = ?) AND id != ?");
        $check->execute([$email, $phone, $user_id]);
        
        if ($check->rowCount() > 0) {
            $error = "Cet email ou ce numéro de téléphone est déjà utilisé par un autre compte.";
        } else {
            $upd = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            if ($upd->execute([$name, $email, $phone, $user_id])) {
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $success = "Profil mis à jour avec succès.";
                $user['name'] = $name;
                $user['email'] = $email;
                $user['phone'] = $phone;
            } else {
                $error = "Erreur lors de la mise à jour.";
            }
        }
    }
    
    // 2. Change Password
    if ($action === 'change_password') {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        if (!password_verify($current, $user['password_hash'])) {
            $error = "Mot de passe actuel incorrect.";
        } elseif (password_verify($new, $user['password_hash'])) {
            $error = "Le nouveau mot de passe ne peut pas être l'actuel. Veuillez en créer un nouveau.";
        } elseif (strlen($new) < 8) {
            $error = "Le nouveau mot de passe doit faire au moins 8 caractères.";
        } elseif ($new !== $confirm) {
            $error = "Les nouveaux mots de passe ne correspondent pas.";
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $upk = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($upk->execute([$hash, $user_id])) {
                $success = "Mot de passe modifié avec succès.";
            } else {
                $error = "Une erreur est survenue lors du changement de mot de passe.";
            }
        }
    }
    
    // 3. Delete Account
    if ($action === 'delete_account') {
        $verify = $_POST['verify_password'];
        
        if (password_verify($verify, $user['password_hash'])) {
            $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $del->execute([$user_id]);
            
            session_unset();
            session_destroy();
            header("Location: " . URL_ROOT . "auth/login.php");
            exit();
        } else {
            $error = "Mot de passe incorrect. Suppression annulée.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres du Compte - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
    <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token']); ?>">
    <style>
        .settings-container {
            display: flex;
            flex-direction: column;
            gap: 40px;
            max-width: 800px;
            margin: 0 auto; /* Ensure centering horizontally */
        }

        .settings-section {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .settings-header {
            padding: 20px 30px;
            background: rgba(15, 23, 42, 0.6);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .settings-header h2 {
            font-size: 18px;
            color: white;
            margin: 0;
            font-weight: 600;
        }

        .settings-header i {
            font-size: 20px;
            color: #ff8c00;
        }

        .settings-body {
            padding: 30px;
        }

        .danger-zone {
            border-color: rgba(239, 68, 68, 0.3);
        }

        .danger-zone .settings-header {
            background: rgba(239, 68, 68, 0.1);
        }
        
        .danger-zone .settings-header i {
            color: #ef4444;
        }
    </style>
</head>
<body>

<div class="dashboard-layout">
    
    <!-- Unified Sidebar -->
    <?php include INC_PATH . 'sidebar.php'; ?>
<main class="main-content">
        <?php include INC_PATH . 'topbar.php'; ?>
        <div class="page-header" style="max-width: 800px; margin: 0 auto 30px auto;">
            <div>
                <h1>Paramètres du Compte</h1>
                <p style="color: #94a3b8; margin-top: 5px;">Gérez vos informations, la sécurité et vos préférences.</p>
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

        <div class="settings-container">
            
            <!-- SECTION 1: PROFILE EDIT -->
            <div class="settings-section">
                <div class="settings-header">
                    <i class="fas fa-user-edit"></i>
                    <h2>Informations du Profil</h2>
                </div>
                <div class="settings-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-row" style="margin-bottom: 20px;">
                            <div class="form-group-dash">
                                <label>Nom complet</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="form-group-dash">
                                <label>Téléphone</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group-dash" style="margin-bottom: 25px;">
                            <label>Adresse Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div style="text-align: right;">
                            <button type="submit" class="btn-primary" style="width: auto; padding: 12px 25px;">
                                Enregistrer les modifications <i class="fas fa-save" style="margin-left: 5px;"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- SECTION 2: PASSWORD & SECURITY -->
            <div class="settings-section">
                <div class="settings-header">
                    <i class="fas fa-shield-alt"></i>
                    <h2>Sécurité & Mot de passe</h2>
                </div>
                <div class="settings-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group-dash" style="margin-bottom: 25px;">
                            <label>Mot de passe actuel</label>
                            <div style="position: relative; max-width: 400px;">
                                <input type="password" name="current_password" required placeholder="Saisissez votre mot de passe actuel" style="width: 100%; padding-right: 40px;">
                                <i class="fas fa-eye password-toggle" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); cursor: pointer; color:#94a3b8; padding: 10px;"></i>
                            </div>
                        </div>
                        
                        <div class="form-row" style="margin-bottom: 25px;">
                            <div class="form-group-dash">
                                <label>Nouveau mot de passe</label>
                                <div style="position: relative;">
                                    <input type="password" name="new_password" required minlength="8" placeholder="Au moins 8 caractères" style="width: 100%; padding-right: 40px;">
                                    <i class="fas fa-eye password-toggle" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); cursor: pointer; color:#94a3b8; padding: 10px;"></i>
                                </div>
                            </div>
                            <div class="form-group-dash">
                                <label>Re-taper le nouveau mot de passe</label>
                                <div style="position: relative;">
                                    <input type="password" name="confirm_password" required minlength="8" placeholder="Confirmez le mot de passe" style="width: 100%; padding-right: 40px;">
                                    <i class="fas fa-eye password-toggle" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); cursor: pointer; color:#94a3b8; padding: 10px;"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div style="text-align: right;">
                            <button type="submit" class="btn-primary" style="width: auto; padding: 12px 25px;">
                                Mettre à jour la sécurité <i class="fas fa-key" style="margin-left: 5px;"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- SECTION 3: DANGER ZONE -->
            <div class="settings-section danger-zone">
                <div class="settings-header">
                    <i class="fas fa-radiation"></i>
                    <h2 style="color: #ef4444;">Zone de Danger</h2>
                </div>
                <div class="settings-body">
                    <p style="color: #cbd5e1; font-size: 15px; margin-bottom: 25px; line-height: 1.6;">
                        <strong>Attention:</strong> La suppression de votre compte est irréversible. Toutes vos données personnelles, historique de réservations, et véhicules seront définitivement effacés de nos serveurs. Vous serez immédiatement déconnecté.
                    </p>
                    
                    <form id="delete-account-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="delete_account">
                        
                        <div class="form-row" style="align-items: flex-end;">
                            <div class="form-group-dash" style="margin: 0; flex: 1; max-width: 400px;">
                                <label style="color: #ef4444;">Confirmez en entrant votre mot de passe</label>
                                <div style="position: relative;">
                                    <input type="password" name="verify_password" id="delete-password" required style="width: 100%; border-color: rgba(239,68,68,0.5); background: rgba(0,0,0,0.2); padding-right:40px;">
                                    <i class="fas fa-eye password-toggle" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); cursor: pointer; color:#94a3b8; padding: 10px;"></i>
                                </div>
                            </div>
                            <div style="flex-shrink: 0;">
                                <button type="button" id="btn-delete-account" class="btn-primary" style="background: #ef4444; border-color: #ef4444; width: auto; padding: 12px 25px;">
                                    <i class="fas fa-trash-alt"></i> Supprimer mon compte
                                </button>
                            </div>
                        </div>
                    </form>

                    <script>
                    document.getElementById('btn-delete-account').addEventListener('click', function() {
                        const btn = this;
                        const passField = document.getElementById('delete-password');
                        
                        if (!passField.value) {
                            Swal.fire({
                                title: 'Attention',
                                text: 'Veuillez saisir votre mot de passe pour confirmer la suppression.',
                                icon: 'warning',
                                background: '#1e293b',
                                color: '#fff',
                                confirmButtonColor: '#ff8c00'
                            });
                            return;
                        }

                        // Start Loading UI on the button immediately
                        const oldHtml = btn.innerHTML;
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                        Swal.fire({
                            title: 'Êtes-vous sûr ?',
                            text: "Cette action est irréversible. Toutes vos données seront définitivement effacées.",
                            icon: 'warning',
                            showCancelButton: true,
                            background: '#1e293b',
                            color: '#fff',
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: 'Oui, supprimer définitivement',
                            cancelButtonText: 'Annuler',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Keep loading and submit
                                document.getElementById('delete-account-form').submit();
                            } else {
                                // Reset loading if canceled or closed
                                btn.disabled = false;
                                btn.innerHTML = oldHtml;
                            }
                        });
                    });
                    </script>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="<?php echo JS_URL; ?>validator.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
