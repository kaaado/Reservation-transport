<?php
require_once __DIR__ . '/../core/paths.php';
require_once __DIR__ . '/../includes/session_manager.php';
safe_session_start();
require_once CONF_PATH . 'database.php';

$error = null;
$success = null;
$valid_token = false;
$user_id = null;

if (!isset($_GET['token']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . URL_ROOT . "auth/login.php");
    exit();
}

$token = $_GET['token'] ?? ($_POST['token'] ?? '');

$database = new Database();
try {
    $pdo = $database->getConnection();
    
    // Validate token
    $cache_file = __DIR__ . '/../cache/password_resets.json';
    $tokens = file_exists($cache_file) ? json_decode(file_get_contents($cache_file), true) : [];
    
    if (isset($tokens[$token]) && $tokens[$token]['expiry'] > time()) {
        $valid_token = true;
        $user_id = $tokens[$token]['user_id'];
    } else {
        $error = "Le lien de réinitialisation est invalide ou a expiré.";
    }
    
    if ($valid_token && $_SERVER["REQUEST_METHOD"] == "POST") {
        $password = $_POST['password'];
        $confirm = $_POST['rewrite_password'];
        
        // Backend verification matching frontend
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $error = "Le mot de passe ne respecte pas les critères de sécurité.";
        } elseif ($password !== $confirm) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            // Update hash and clear token
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            
            if ($upd->execute([$hash, $user_id])) {
                unset($tokens[$token]);
                file_put_contents($cache_file, json_encode($tokens));
                
                $success = "Votre mot de passe a été réinitialisé avec succès !";
                $valid_token = false; // block the form
            } else {
                $error = "Erreur lors de la mise à jour.";
            }
        }
    }
    
} catch (Exception $e) {
    $error = "Erreur système : " . $e->getMessage();
}
?>
<?php include INC_PATH . 'header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 500px; flex-direction: column; text-align: center; padding: 40px;">
        
        <div style="font-size: 40px; color: #ff8c00; margin-bottom: 20px;">
            <i class="fas fa-lock"></i>
        </div>
        
        <h2 style="font-size: 24px; color: white; margin-bottom: 10px;">Réinitialisation</h2>
        <p style="color: #cbd5e1; font-size: 14px; margin-bottom: 30px;">
            Créez un nouveau mot de passe fort pour votre compte.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="text-align: left;">
                <i class="fas fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="text-align: left; background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.2); color: #22c55e;">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <a href="<?php echo URL_ROOT; ?>auth/login.php" class="btn-primary" style="margin-top: 20px; display: inline-block;">Retour à la connexion</a>
        <?php endif; ?>

        <?php if ($valid_token): ?>
            <form method="POST" action="" style="text-align: left;">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <input type="password" name="password" class="form-control" placeholder="Nouveau mot de passe" required>
                    <i class="fas fa-lock icon-left"></i>
                    <i class="fas fa-eye password-toggle" title="Afficher/Masquer"></i>
                </div>
                
                <div class="form-group" style="margin-bottom: 25px;">
                    <input type="password" name="rewrite_password" class="form-control" placeholder="Confirmer le nouveau mot de passe" required>
                    <i class="fas fa-check-circle icon-left"></i>
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%;">
                    Enregistrer le nouveau mot de passe <i class="fas fa-save" style="margin-left: 10px;"></i>
                </button>
            </form>
        <?php endif; ?>
        
    </div>
</div>

<script src="<?php echo JS_URL; ?>validator.js?v=<?php echo time(); ?>"></script>
<?php include INC_PATH . 'footer.php'; ?>
