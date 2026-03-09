<?php
require_once __DIR__ . '/../core/paths.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Refusé - CargoConnect</title>
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

        .unauthorized-wrapper {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: radial-gradient(circle at center, #1e293b 0%, #0f172a 100%);
        }

        /* Animated Background Elements */
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
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.2);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            position: relative;
        }

        .icon-box i {
            font-size: 45px;
            color: var(--danger);
            animation: pulse-danger 2s infinite;
        }

        @keyframes pulse-danger {
            0% { transform: scale(1); filter: drop-shadow(0 0 0px rgba(239, 68, 68, 0)); }
            50% { transform: scale(1.1); filter: drop-shadow(0 0 15px rgba(239, 68, 68, 0.5)); }
            100% { transform: scale(1); filter: drop-shadow(0 0 0px rgba(239, 68, 68, 0)); }
        }

        h1 {
            color: white;
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }

        p {
            color: #94a3b8;
            font-size: 17px;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .btn-safe {
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

        .btn-safe:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(255, 140, 0, 0.5);
            filter: brightness(1.1);
        }

        .btn-safe i {
            font-size: 18px;
            transition: transform 0.3s;
        }

        .btn-safe:hover i {
            transform: translateX(-5px);
        }

        .security-footer {
            margin-top: 40px;
            font-size: 13px;
            color: #606a79ff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
    </style>
</head>
<body>

<div class="unauthorized-wrapper">
    <div class="decoration blob-red"></div>
    <div class="decoration blob-orange"></div>

    <div class="glass-card">
        <div class="icon-box">
            <i class="fas fa-user-shield"></i>
        </div>
        <h1>Accès Refusé</h1>
        <p>
            Vous n'avez pas les permissions nécessaires pour accéder à cette interface. 
            Votre tentative a été journalisée pour des raisons de sécurité.
        </p>

        <?php
        // Build direct URL_ROOT based redirection
        $redirect = URL_ROOT . 'index.php';
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'client') $redirect = URL_ROOT . 'client/dashboard.php';
            elseif ($_SESSION['role'] === 'transporter') $redirect = URL_ROOT . 'transporter/dashboard.php';
            elseif ($_SESSION['role'] === 'admin') $redirect = URL_ROOT . 'admin/dashboard.php';
        }
        ?>

        <a href="<?php echo htmlspecialchars($redirect); ?>" class="btn-safe">
            <i class="fas fa-arrow-left"></i> Retourner
        </a>

        <div class="security-footer">
            <i class="fas fa-lock"></i> Connexion sécurisée CargoConnect
        </div>
    </div>
</div>

</body>
</html>
