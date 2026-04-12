<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once CONF_PATH . 'database.php';

$database = new Database();
$pdo = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get ID info
$stmt = $pdo->prepare("SELECT id_card_url, id_is_verified FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['id_card'])) {
    $file = $_FILES['id_card'];
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        $error = "Format de fichier non autorisé. (JPG, PNG, PDF)";
    } elseif ($file['size'] > 5000000) {
        $error = "Le fichier ne doit pas dépasser 5Mo.";
    } else {
        $uploadDir = __DIR__ . '/../uploads/ids/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = 'id_' . $user_id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
            $url = 'uploads/ids/' . $fileName;
            $upd = $pdo->prepare("UPDATE users SET id_card_url = ? WHERE id = ?");
            $upd->execute([$url, $user_id]);
            $success = "Votre pièce d'identité a été uploadée avec succès. Elle sera examinée prochainement.";
            $u['id_card_url'] = $url;
        } else {
            $error = "Erreur lors de l'upload.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte en attente - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <style>
        :root {
            --primary: #ff8c00;
            --danger: #ef4444;
            --bg-dark: #0f172a;
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-dark);
        }

        .suspended-wrapper {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: radial-gradient(circle at center, #1e293b 0%, #0f172a 100%);
        }

        .decoration {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            z-index: 1;
            opacity: 0.5;
        }

        .blob-red {
            width: 450px;
            height: 450px;
            background: var(--danger);
            top: -100px;
            right: -50px;
            animation: move 20s infinite alternate;
        }

        .blob-orange {
            width: 400px;
            height: 400px;
            background: var(--primary);
            bottom: -100px;
            left: -50px;
            animation: move 15s infinite alternate-reverse;
        }

        @keyframes move {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(100px, 50px) scale(1.1); }
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            padding: 60px 40px;
            text-align: center;
            max-width: 550px;
            width: 90%;
            z-index: 10;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeInScale 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes fadeInScale {
            0% { opacity: 0; transform: scale(0.9) translateY(20px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }

        .icon-box {
            width: 100px;
            height: 100px;
            background: rgba(255, 140, 0, 0.1);
            border: 2px solid rgba(255, 140, 0, 0.2);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }

        .icon-box i {
            font-size: 45px;
            color: var(--primary);
            animation: pulse 2s infinite alternate;
        }

        @keyframes pulse {
            from { transform: scale(1); opacity: 0.8; }
            to { transform: scale(1.1); opacity: 1; }
        }

        h1 {
            color: white;
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 15px;
        }

        p {
            color: #94a3b8;
            font-size: 17px;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .btn-login {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #ff8c00 0%, #ff5e00 100%);
            color: white;
            padding: 16px 35px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            box-shadow: 0 10px 20px -5px rgba(255, 140, 0, 0.4);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(255, 140, 0, 0.5);
            filter: brightness(1.1);
        }

        .security-footer {
            margin-top: 40px;
            font-size: 13px;
            color: #e8ecf1ff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .file-upload-wrapper {
            margin-top: 30px;
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 30px 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) inset;
        }
        .upload-icon-container {
            width: 60px;
            height: 60px;
            background: rgba(255, 140, 0, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--primary);
            font-size: 24px;
        }
        .file-upload-label {
            display: block;
            border: 2px dashed rgba(255, 140, 0, 0.4);
            border-radius: 16px;
            padding: 30px 20px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 140, 0, 0.02);
            position: relative;
        }
        .file-upload-label:hover {
            border-color: rgba(255, 140, 0, 0.8);
            background: rgba(255, 140, 0, 0.05);
            transform: translateY(-2px);
        }
        .file-upload-label input[type="file"] {
            display: none;
        }
        .btn-submit-id {
            background: linear-gradient(135deg, #ff8c00 0%, #ff5e00 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            width: 100%;
            margin-top: 20px;
            font-size: 15px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 140, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0.5;
            pointer-events: none;
        }
        .btn-submit-id.active {
            opacity: 1;
            pointer-events: auto;
        }
        .btn-submit-id.active:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 140, 0, 0.4);
        }
        #file-error {
            color: #ef4444;
            font-size: 13px;
            margin-top: 15px;
            display: none;
            align-items: center;
            justify-content: center;
            gap: 5px;
            background: rgba(239, 68, 68, 0.1);
            padding: 10px;
            border-radius: 8px;
        }
        .file-details {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
            padding: 12px;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 8px;
            color: #22c55e;
            font-size: 14px;
            font-weight: 500;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        .verified-badge {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            padding: 15px;
            border-radius: 12px;
            margin: 20px 0;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
    </style>
</head>
<body>

<div class="suspended-wrapper">
    <div class="decoration blob-red"></div>
    <div class="decoration blob-orange"></div>

    <div class="glass-card">
        <div class="icon-box">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <h1>En attente d'approbation</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <p>
            Votre compte a été créé avec succès, mais il doit d'abord être validé par un administrateur avant que vous puissiez y accéder. 
            Veuillez patienter, nous traiterons votre demande dans les plus brefs délais.
        </p>

        <?php if (!empty($u['id_card_url'])): ?>
            <div class="verified-badge">
                <i class="fas fa-check-circle" style="font-size: 24px; margin-bottom: 10px; display:block;"></i>
                Votre pièce d'identité a été récéptionnée.<br>
                <small>Veuillez attendre notre vérification.</small>
            </div>
        <?php else: ?>
            <div class="file-upload-wrapper">
                <h3 style="color: white; font-size: 18px; font-weight: 600; margin-bottom: 10px;">Vérification d'identité protégée</h3>
                <p style="font-size: 14px; color: #94a3b8; margin-bottom: 25px; line-height: 1.5;">Veuillez télécharger une copie de votre carte d'identité (Algérienne) pour accélérer la validation de votre accès.</p>
                
                <form method="POST" enctype="multipart/form-data" id="id-upload-form">
                    <label class="file-upload-label" id="drop-zone">
                        <div class="upload-icon-container">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <span style="display: block; color: white; font-weight: 600; font-size: 16px; margin-bottom: 5px;">Choisir un fichier</span>
                        <span style="display: block; font-size: 13px; color: #64748b;">(JPG, PNG ou PDF - Max 5Mo)</span>
                        <input type="file" name="id_card" id="id_card" accept=".jpg,.jpeg,.png,.pdf" required>
                    </label>
                    
                    <div id="file-details" class="file-details">
                        <i class="fas fa-file-alt"></i> <span id="file-name-display"></span>
                    </div>

                    <div id="file-error">
                        <i class="fas fa-exclamation-circle"></i> <span id="error-text"></span>
                    </div>

                    <button type="submit" class="btn-submit-id" id="btn-submit">
                        <i class="fas fa-paper-plane"></i> Soumettre la pièce d'identité
                    </button>
                </form>
            </div>

            <script>
                const fileInput = document.getElementById('id_card');
                const fileDetails = document.getElementById('file-details');
                const fileNameDisplay = document.getElementById('file-name-display');
                const btnSubmit = document.getElementById('btn-submit');
                const errorBox = document.getElementById('file-error');
                const errorText = document.getElementById('error-text');

                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    errorBox.style.display = 'none';
                    
                    if (file) {
                        // Validate size (5MB max)
                        if (file.size > 5 * 1024 * 1024) {
                            errorText.textContent = "Le fichier dépasse la taille maximale de 5Mo.";
                            errorBox.style.display = 'flex';
                            fileDetails.style.display = 'none';
                            btnSubmit.classList.remove('active');
                            fileInput.value = ''; // Reset
                            return;
                        }

                        // Validate extension
                        const ext = file.name.split('.').pop().toLowerCase();
                        const allowed = ['jpg', 'jpeg', 'png', 'pdf'];
                        if (!allowed.includes(ext)) {
                            errorText.textContent = "Format non autorisé. Seuls JPG, PNG et PDF sont acceptés.";
                            errorBox.style.display = 'flex';
                            fileDetails.style.display = 'none';
                            btnSubmit.classList.remove('active');
                            fileInput.value = ''; // Reset
                            return;
                        }

                        // Validation passed
                        fileNameDisplay.textContent = file.name;
                        fileDetails.style.display = 'flex';
                        btnSubmit.classList.add('active');
                    } else {
                        fileDetails.style.display = 'none';
                        btnSubmit.classList.remove('active');
                    }
                });
            </script>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <a href="<?php echo URL_ROOT; ?>logout.php" class="btn-login" style="background: rgba(255,255,255,0.1); box-shadow: none;">
                <i class="fas fa-sign-out-alt"></i> Se déconnecter
            </a>
        </div>

        <div class="security-footer">
            <i class="fas fa-shield-alt"></i> Centre de sécurité CargoConnect
        </div>
    </div>
</div>

</body>
</html>
