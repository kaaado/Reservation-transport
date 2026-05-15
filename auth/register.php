<?php
require_once __DIR__ . '/../core/paths.php';
require_once __DIR__ . '/../includes/session_manager.php';
safe_session_start();
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
            <a href="<?php echo URL_ROOT; ?>auth/login.php" class="brand-logo">
                <div class="auth-logo-circle">
                    <img src="<?php echo ASSETS_URL; ?>logo.jpg" alt="CargoConnect Logo" loading="lazy">
                </div>
                <div>Cargo<span>Connect</span></div>
            </a>
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

            <form method="POST" action="" id="registerForm">
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
                    <select name="role" id="roleSelect" class="form-control" required>
                        <option value="" disabled <?php echo (!isset($_POST['role'])) ? 'selected' : ''; ?>>Vous êtes :</option>
                        <option value="client" <?php echo (isset($_POST['role']) && $_POST['role'] == 'client') ? 'selected' : ''; ?>>Client - Expéditeur</option>
                        <option value="transporter" <?php echo (isset($_POST['role']) && $_POST['role'] == 'transporter') ? 'selected' : ''; ?>>Chauffeur - Transporteur</option>
                    </select>
                    <i class="fas fa-user-tag icon-left"></i>
                </div>

                <!-- Terms & Conditions Checkbox -->
                <div class="form-group terms-group" id="termsGroup" style="display: none;">
                    <label class="checkbox-custom terms-checkbox">
                        <input type="checkbox" name="accept_terms" id="acceptTerms">
                        <span class="checkmark"></span>
                        J'accepte les <a href="javascript:void(0)" id="openTermsModal" class="terms-link">conditions d'utilisation</a>
                    </label>
                </div>
                
                <button type="submit" class="btn-primary" id="registerBtn" disabled>
                    Créer mon compte <i class="fas fa-user-plus"></i>
                </button>
                
                <div class="auth-footer">
                    Vous avez déjà un compte ? <a href="<?php echo URL_ROOT; ?>auth/login.php">Se connecter</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Terms & Conditions Modal -->
<div class="terms-modal-overlay" id="termsModalOverlay">
    <div class="terms-modal">
        <div class="terms-modal-header">
            <h3 id="termsModalTitle"><i class="fas fa-file-contract"></i> Conditions d'Utilisation</h3>
            <button class="terms-modal-close" id="closeTermsModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="terms-modal-body" id="termsModalBody">
            <!-- Content injected by JS based on role -->
        </div>
        <div class="terms-modal-footer">
            <button class="btn-terms-accept" id="acceptTermsBtn">J'ai lu et j'accepte <i class="fas fa-check"></i></button>
        </div>
    </div>
</div>

<script>
(function() {
    const roleSelect = document.getElementById('roleSelect');
    const termsGroup = document.getElementById('termsGroup');
    const acceptTerms = document.getElementById('acceptTerms');
    const registerBtn = document.getElementById('registerBtn');
    const openTermsModal = document.getElementById('openTermsModal');
    const closeTermsModal = document.getElementById('closeTermsModal');
    const termsModalOverlay = document.getElementById('termsModalOverlay');
    const termsModalTitle = document.getElementById('termsModalTitle');
    const termsModalBody = document.getElementById('termsModalBody');
    const acceptTermsBtn = document.getElementById('acceptTermsBtn');

    const transporterTerms = `
        <div class="contract-section">
            <h4><span class="section-num">01</span> Commissions de Service</h4>
            <p>La plateforme CargoConnect prélève une commission de <strong>20%</strong> sur le montant total de chaque mission complétée. Cette commission permet le fonctionnement des serveurs, le marketing et le support technique dont vous bénéficiez.</p>
        </div>
        <div class="contract-section">
            <h4><span class="section-num">02</span> Seuil de Blocage (Batch de 5)</h4>
            <p>Pour assurer la fluidité des paiements, un système de blocage est en place. Si un transporteur accumule <strong>5 commissions non réglées</strong>, son accès aux nouvelles missions est automatiquement suspendu jusqu'à régularisation du solde.</p>
        </div>
        <div class="contract-section">
            <h4><span class="section-num">03</span> Désactivation & Réactivation</h4>
            <p>En cas de non-conformité, l'administration peut désactiver vos véhicules. Une procédure de <strong>Demande de Réactivation</strong> est alors nécessaire pour ré-ouvrir vos droits d'exploitation sur le réseau.</p>
        </div>
        <div class="contract-section">
            <h4><span class="section-num">04</span> Négociation & Transparence</h4>
            <p>Le transporteur est libre de proposer ses propres tarifs sur les demandes "Négociables". Toutes les transactions et modifications de statuts de mission doivent être effectuées via la plateforme pour garantir la traçabilité.</p>
        </div>
        <div class="contract-section">
            <h4><span class="section-num">05</span> Responsabilité des Marchandises</h4>
            <p>Le transporteur est l'unique responsable de la protection et de l'intégrité des marchandises transportées. En cas de dommage, perte ou vol des produits du client, la responsabilité incombe entièrement au transporteur et non à la plateforme CargoConnect.</p>
        </div>
        <div class="contract-section">
            <h4><span class="section-num">06</span> Vérification des Véhicules</h4>
            <p>Tout véhicule ajouté au compte pour effectuer des transports doit faire l'objet d'une vérification réelle de son état et de sa conformité. Les missions ne peuvent être effectuées qu'avec des véhicules validés par l'administration.</p>
        </div>
        <div class="contract-notice">
            <p><i class="fas fa-info-circle"></i> En vous inscrivant comme transporteur, vous acceptez l'ensemble de ces conditions. Ce contrat reste consultable à tout moment depuis vos paramètres de compte.</p>
        </div>
    `;

    const clientTerms = `
        <div class="contract-section">
            <h4><span class="section-num">01</span> Utilisation de la Plateforme</h4>
            <p>En tant que client, vous utilisez CargoConnect pour publier des demandes de transport de marchandises. Vous vous engagez à fournir des <strong>informations exactes</strong> concernant la nature, le poids et le volume de vos cargaisons.</p>
        </div>
        <div class="contract-section">
            <h4><span class="section-num">02</span> Responsabilité des Marchandises</h4>
            <p>Le client est responsable de la <strong>conformité légale</strong> de ses marchandises. Tout produit illicite, dangereux ou non-déclaré entraînera la suspension immédiate du compte et des poursuites éventuelles.</p>
        </div>
        <div class="contract-section">
            <h4><span class="section-num">03</span> Tarification & Paiements</h4>
            <p>Les prix affichés sont soit <strong>fixes</strong>, soit <strong>négociables</strong> selon le choix du client. Le paiement de la prestation est dû une fois le transport confirmé. CargoConnect ne gère pas directement les paiements entre parties.</p>
        </div>
        <div class="contract-section">
            <h4><span class="section-num">04</span> Annulation & Litiges</h4>
            <p>Le client peut annuler une demande tant qu'elle est en statut <strong>"En attente"</strong>. Au-delà, toute annulation doit passer par le support. Les litiges sont traités par l'administration de la plateforme.</p>
        </div>
        <div class="contract-section">
            <h4><span class="section-num">05</span> Protection des Données</h4>
            <p>Vos données personnelles sont protégées conformément à la législation algérienne. Elles ne seront jamais partagées avec des tiers sans votre <strong>consentement explicite</strong>.</p>
        </div>
        <div class="contract-notice">
            <p><i class="fas fa-info-circle"></i> En vous inscrivant comme client, vous acceptez l'ensemble de ces conditions. Ce contrat reste consultable à tout moment depuis vos paramètres de compte.</p>
        </div>
    `;

    function updateTermsVisibility() {
        const role = roleSelect.value;
        if (role === 'client' || role === 'transporter') {
            termsGroup.style.display = 'block';
            acceptTerms.checked = false;
            registerBtn.disabled = true;
        } else {
            termsGroup.style.display = 'none';
            registerBtn.disabled = true;
        }
    }

    function updateSubmitState() {
        registerBtn.disabled = !acceptTerms.checked;
    }

    function openModal() {
        const role = roleSelect.value;
        if (role === 'transporter') {
            termsModalTitle.innerHTML = '<i class="fas fa-file-contract"></i> Contrat de Partenariat — Transporteur';
            termsModalBody.innerHTML = transporterTerms;
        } else {
            termsModalTitle.innerHTML = '<i class="fas fa-file-contract"></i> Conditions d\'Utilisation — Client';
            termsModalBody.innerHTML = clientTerms;
        }
        termsModalOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        termsModalOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    roleSelect.addEventListener('change', updateTermsVisibility);
    acceptTerms.addEventListener('change', updateSubmitState);
    openTermsModal.addEventListener('click', openModal);
    closeTermsModal.addEventListener('click', closeModal);
    termsModalOverlay.addEventListener('click', function(e) {
        if (e.target === termsModalOverlay) closeModal();
    });
    acceptTermsBtn.addEventListener('click', function() {
        acceptTerms.checked = true;
        updateSubmitState();
        closeModal();
    });

    // Init on load if role was pre-selected (POST back)
    if (roleSelect.value === 'client' || roleSelect.value === 'transporter') {
        termsGroup.style.display = 'block';
    }
})();
</script>

<script src="<?php echo JS_URL; ?>validator.js?v=<?php echo time(); ?>"></script>
<?php include INC_PATH . 'footer.php'; ?>

