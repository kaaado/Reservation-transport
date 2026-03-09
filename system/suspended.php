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
    <title>Compte Suspendu - CargoConnect</title>
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
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.2);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }

        .icon-box i {
            font-size: 45px;
            color: var(--danger);
            animation: shake 0.5s infinite alternate;
        }

        @keyframes shake {
            from { transform: rotate(-5deg); }
            to { transform: rotate(5deg); }
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
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
    </style>
</head>
<body>

<div class="suspended-wrapper">
    <div class="decoration blob-red"></div>
    <div class="decoration blob-orange"></div>

    <div class="glass-card">
        <div class="icon-box">
            <i class="fas fa-user-lock"></i>
        </div>
        <h1>Compte Suspendu</h1>
        <p>
            Votre accès à CargoConnect a été temporairement restreint par l'administration. 
            Si vous pensez qu'il s'agit d'une erreur, veuillez contacter le support.
        </p>

        <a href="<?php echo URL_ROOT; ?>auth/login.php" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Retour à la connexion
        </a>

        <div class="security-footer">
            <i class="fas fa-shield-alt"></i> Centre de sécurité CargoConnect
        </div>
    </div>
</div>

</body>
</html>
