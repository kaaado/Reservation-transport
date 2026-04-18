<?php
require_once __DIR__ . '/../core/paths.php';
require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'auth.php';

require_once INC_PATH . 'session_manager.php';
safe_session_start();

$database = new Database();
$pdo = $database->getConnection();
$error = null;

if (isset($_GET['error']) && $_GET['error'] === 'inactive') {
    $error = "Votre compte n'est pas actif ou a été suspendu par l'administration.";
}

// Helper to redirect based on role
function redirectUserByRole()
{
    switch ($_SESSION['role']) {
        case 'client':
            header("Location: " . URL_ROOT . "client/dashboard.php");
            break;
        case 'transporter':
            header("Location: " . URL_ROOT . "transporter/dashboard.php");
            break;
        case 'admin':
            header("Location: " . URL_ROOT . "admin/dashboard.php");
            break;
    }
    exit();
}

// 1. Check for persistent "Remember Me" session (Cookie check)
if (checkRememberMe($pdo)) {
    redirectUserByRole();
}

// 2. Already logged in via Session
if (isset($_SESSION['user_id'])) {
    redirectUserByRole();
}

// 3. Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);

        $result = loginUser($email, $password, $pdo, $remember);

        if ($result === true) {
            // Store simple email cookie for input field convenience (UX)
            setcookie('user_email', $email, time() + (86400 * 30), "/");
            redirectUserByRole();
        } elseif ($result === "pending_redirect") {
            header("Location: " . URL_ROOT . "system/pending.php");
            exit();
        } else {
            $error = $result;
        }
    } catch (Exception $e) {
        $error = "Erreur système : " . $e->getMessage();
    }
}
?>
<?php include INC_PATH . 'header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Left Side: Branding -->
        <div class="auth-left">
            <a href="<?php echo URL_ROOT; ?>auth/login.php" class="brand-logo">Cargo<span>Connect</span></a>
            <h1 class="brand-tagline">La nouvelle référence du transport de marchandises.</h1>
            <p class="brand-desc">Gérez vos expéditions, connectez avec des transporteurs fiables et suivez vos
                opérations en temps réel dans une interface repensée pour vous.</p>
        </div>

        <!-- Right Side: Form -->
        <div class="auth-right">
            <h2>Bon retour !</h2>
            <p class="subtitle">Connectez-vous pour accéder à votre espace</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Adresse email" required
                        value="<?php echo isset($_COOKIE['user_email']) ? htmlspecialchars($_COOKIE['user_email']) : ''; ?>">
                    <i class="fas fa-envelope icon-left"></i>
                </div>

                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
                    <i class="fas fa-lock icon-left"></i>
                    <i class="fas fa-eye password-toggle" title="Afficher/Masquer"></i>
                </div>

                <div class="form-options">
                    <label class="checkbox-custom">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                        Se souvenir de moi
                    </label>
                    <a href="<?php echo URL_ROOT; ?>auth/forgot-password.php" class="forgot-link">Mot de passe oublié
                        ?</a>
                </div>

                <button type="submit" class="btn-primary">
                    Se connecter <i class="fas fa-arrow-right"></i>
                </button>
                <div class="auth-footer">
                    Pas encore de compte ? <a href="<?php echo URL_ROOT; ?>auth/register.php">Inscrivez-vous</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo JS_URL; ?>validator.js?v=<?php echo time(); ?>"></script>
<?php include INC_PATH . 'footer.php'; ?>