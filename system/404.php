<?php
require_once __DIR__ . '/../core/paths.php';
require_once __DIR__ . '/../includes/session_manager.php';
safe_session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Introuvable - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <style>
        :root {
            --primary: #ff8c00;
            --info: #38bdf8;
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

        .error-wrapper {
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
            opacity: 0.4;
        }

        .blob-blue {
            width: 450px;
            height: 450px;
            background: var(--info);
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

        .error-code {
            font-size: 120px;
            font-weight: 900;
            color: var(--info);
            margin: 0;
            line-height: 1;
            letter-spacing: -5px;
            filter: drop-shadow(0 0 20px rgba(56, 189, 248, 0.3));
        }

        h1 {
            color: white;
            font-size: 32px;
            font-weight: 800;
            margin-top: 10px;
            margin-bottom: 15px;
        }

        p {
            color: #94a3b8;
            font-size: 17px;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .btn-home {
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

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(255, 140, 0, 0.5);
            filter: brightness(1.1);
        }

        .btn-home i {
            font-size: 18px;
        }
    </style>
</head>
<body>

<div class="error-wrapper">
    <div class="decoration blob-blue"></div>
    <div class="decoration blob-orange"></div>

    <div class="glass-card">
        <div class="error-code">404</div>
        <h1>Itinéraire Perdu</h1>
        <p>
            Désolé, la destination que vous cherchez n'existe pas ou l'expédition a été déplacée. 
            Veuillez vérifier l'adresse ou retourner au point de départ.
        </p>

        <?php
        $redirect = URL_ROOT . 'index.php';
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'client') $redirect = URL_ROOT . 'client/dashboard.php';
            elseif ($_SESSION['role'] === 'transporter') $redirect = URL_ROOT . 'transporter/dashboard.php';
            elseif ($_SESSION['role'] === 'admin') $redirect = URL_ROOT . 'admin/dashboard.php';
        }
        ?>

        <a href="<?php echo htmlspecialchars($redirect); ?>" class="btn-home">
            <i class="fas fa-home"></i> Retour à l'accueil
        </a>
    </div>
</div>

</body>
</html>
