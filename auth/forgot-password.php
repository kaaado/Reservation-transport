<?php
require_once __DIR__ . '/../core/paths.php';
session_start();
require_once ROOT_PATH . 'vendor/autoload.php';
require_once CONF_PATH . 'database.php';
require_once __DIR__ . '/../core/email_queue.php';

// Load Env if exists
if (file_exists(ROOT_PATH . '.env')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
        $dotenv->load();
    } catch (\Exception $e) {
        // Silent fail
    }
}

$error = null;
$success = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $database = new Database();

    try {
        $pdo = $database->getConnection();

        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expiry = time() + 3600; // 1 hour

            $cache_file = __DIR__ . '/../cache/password_resets.json';
            if (!file_exists(dirname($cache_file))) {
                mkdir(dirname($cache_file), 0777, true);
            }
            $tokens = file_exists($cache_file) ? json_decode(file_get_contents($cache_file), true) : [];
            $tokens[$token] = [
                'user_id' => $user['id'],
                'expiry' => $expiry
            ];

            if (file_put_contents($cache_file, json_encode($tokens))) {

                $app_url = $_ENV['APP_URL'] ?? "http://" . $_SERVER['HTTP_HOST'] . "/Reservation-transport";
                $reset_link = $app_url . "/auth/reset-password.php?token=" . $token;

                $subject = 'Réinitialisation de votre mot de passe - CargoConnect';
                $body = "
                <div style='background: #f8fafc; padding: 40px; border-radius: 12px; color: #0f172a; margin-top: 30px; font-family: sans-serif;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h2 style='color: #ff8c00; margin: 0; font-size: 24px;'>CargoConnect</h2>
                    </div>
                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin-bottom: 20px;'>
                    <h3 style='margin-bottom: 15px;'>Bonjour " . htmlspecialchars($user['name']) . ",</h3>
                    <p style='color: #475569; line-height: 1.6; margin-bottom: 25px;'>
                        Nous avons reçu une demande de réinitialisation de votre mot de passe pour votre compte CargoConnect.
                        Ce lien expirera dans <strong>1 heure</strong>.
                    </p>
                    <div style='text-align: center; margin-bottom: 30px;'>
                        <a href='{$reset_link}' style='background: #ff8c00; color: white; text-decoration: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; display: inline-block;'>
                            Réinitialiser mon mot de passe
                        </a>
                    </div>
                    <p style='color: #94a3b8; font-size: 12px; text-align: center;'>
                        Si vous n'avez pas fait cette demande, vous pouvez ignorer cet e-mail.
                    </p>
                </div>";

                try {
                    enqueueEmail($email, $user['name'], $subject, $body);
                    $success = "Un lien de réinitialisation a été ajouté à la file d'attente. Vous devriez le recevoir très bientôt.";
                } catch (Exception $e) {
                    $error = "Erreur lors de l'ajout à la file d'attente.";
                }
            } else {
                $error = "Erreur lors de la génération du token.";
            }
        } else {
            // Security: We still display success so attackers can't guess emails
            $success = "Si cet email existe, un lien de réinitialisation a été envoyé.";
        }
    } catch (Exception $e) {
        $error = "Erreur système : " . $e->getMessage();
    }
}
?>
<?php include INC_PATH . 'header.php'; ?>

<a href="<?php echo URL_ROOT; ?>auth/login.php" class="btn-back" title="Retour"><i class="fas fa-arrow-left"></i></a>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 500px; flex-direction: column; text-align: center; padding: 40px;">

        <div style="font-size: 40px; color: #ff8c00; margin-bottom: 20px;">
            <i class="fas fa-key"></i>
        </div>

        <h2 style="font-size: 24px; color: white; margin-bottom: 10px;">Mot de passe oublié ?</h2>
        <p style="color: #cbd5e1; font-size: 14px; margin-bottom: 30px;">
            Entrez votre adresse email et nous vous enverrons des instructions pour réinitialiser votre mot de passe.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="text-align: left;">
                <i class="fas fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"
                style="text-align: left; background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.2); color: #22c55e;">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>

        <?php else: ?>

            <form method="POST" action="" style="text-align: left;">
                <div class="form-group" style="margin-bottom: 25px;">
                    <input type="email" name="email" class="form-control" placeholder="Entrez votre adresse email" required>
                    <i class="fas fa-envelope icon-left"></i>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%;">
                    Envoyer le lien de récupération <i class="fas fa-paper-plane" style="margin-left: 10px;"></i>
                </button>
            </form>

        <?php endif; ?>

        <div class="auth-footer"
            style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;">
            Rappelez-vous de votre mot de passe ? <a href="<?php echo URL_ROOT; ?>auth/login.php">Retour connexion</a>
        </div>

    </div>
</div>

<script src="<?php echo JS_URL; ?>validator.js?v=<?php echo time(); ?>"></script>
<?php include INC_PATH . 'footer.php'; ?>