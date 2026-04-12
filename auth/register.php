<?php
require_once __DIR__ . '/../core/paths.php';
session_start();
require_once CONF_PATH . 'database.php';
require_once FUNC_PATH . 'auth.php';

$error = null;
$success = null;

if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'client': header("Location: " . URL_ROOT . "client/dashboard.php"); break;
        case 'transporter': header("Location: " . URL_ROOT . "transporter/dashboard.php"); break;
        case 'admin': header("Location: " . URL_ROOT . "admin/dashboard.php"); break;
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    try {
        $pdo = $database->getConnection();
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        if (!preg_match('/^[a-zA-Z\s_\-]+$/', $name)) {
            $error = "Le nom ne doit contenir que des lettres, des espaces, des tirets ou des underscores.";
        } elseif (!preg_match('/^0[567][0-9]{8}$/', $phone)) {
            $error = "Le téléphone doit commencer par 05, 06 ou 07 et contenir exactement 10 chiffres.";
        } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $error = "Le mot de passe ne respecte pas les critères de sécurité.";
        } else {
            $result = registerUser($name, $email, $password, $phone, $role, $pdo);
            if (is_numeric($result)) {
                // Set session for pending user so they can upload ID
                $_SESSION['user_id'] = $result;
                $_SESSION['role'] = $role;
                $_SESSION['name'] = $name;
                $_SESSION['status'] = 'pending';
                
                // Redirect to pending approval page
                header("Location: " . URL_ROOT . "system/pending.php");
                exit();
            } else {
                $error = $result;
            }
        }
    } catch (Exception $e) {
        $error = "Erreur système : " . $e->getMessage();
    }
}
?>
<?php include INC_PATH . 'header.php'; ?>

<a href="<?php echo URL_ROOT; ?>auth/login.php" class="btn-back" title="Retour"><i class="fas fa-arrow-left"></i></a>

<div class="auth-wrapper">
    <div class="auth-card">
        
        <!-- Left Side: Branding -->
        <div class="auth-left">
            <a href="<?php echo URL_ROOT; ?>auth/login.php" class="brand-logo">Cargo<span>Connect</span></a>
            <h1 class="brand-tagline">Rejoignez le réseau logistique de demain.</h1>
            <p class="brand-desc">Que vous soyez client cherchant à expédier ou transporteur offrant vos services, CargoConnect facilite chacune de vos démarches.</p>
        </div>

        <!-- Right Side: Form -->
        <div class="auth-right">
            <h2>Créer un compte</h2>
            <p class="subtitle">Complétez les informations pour commencer</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" name="name" class="form-control" placeholder="Nom Complet" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    <i class="fas fa-user icon-left"></i>
                </div>
                
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Adresse email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <i class="fas fa-envelope icon-left"></i>
                </div>
                
                <div class="form-group">
                    <input type="text" name="phone" class="form-control" placeholder="Numéro de téléphone (ex: 05 xxx)" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    <i class="fas fa-phone icon-left"></i>
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="Créer un mot de passe (Min 8)" required>
                    <i class="fas fa-lock icon-left"></i>
                    <i class="fas fa-eye password-toggle" title="Afficher/Masquer"></i>
                </div>
                
                <div class="form-group select-wrapper">
                    <select name="role" class="form-control" required>
                        <option value="" disabled <?php echo (!isset($_POST['role'])) ? 'selected' : ''; ?>>Vous êtes :</option>
                        <option value="client" <?php echo (isset($_POST['role']) && $_POST['role'] == 'client') ? 'selected' : ''; ?>>Client - Expéditeur</option>
                        <option value="transporter" <?php echo (isset($_POST['role']) && $_POST['role'] == 'transporter') ? 'selected' : ''; ?>>Chauffeur - Transporteur</option>
                    </select>
                    <i class="fas fa-user-tag icon-left"></i>
                </div>
                
                <button type="submit" class="btn-primary">
                    Créer mon compte <i class="fas fa-user-plus"></i>
                </button>
                
                <div class="auth-footer">
                    Vous avez déjà un compte ? <a href="<?php echo URL_ROOT; ?>auth/login.php">Se connecter</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo JS_URL; ?>validator.js?v=<?php echo time(); ?>"></script>
<?php include INC_PATH . 'footer.php'; ?>
