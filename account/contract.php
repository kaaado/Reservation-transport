<?php
require_once __DIR__ . '/../core/paths.php';
require_once INC_PATH . 'auth_check.php';
require_once INC_PATH . 'role_gate.php';
// Accessible ONLY to admin and transporter
if (!in_array($_SESSION['role'], ['admin', 'transporter'])) {
    header("Location: " . URL_ROOT . "dashboard.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

require_once CONF_PATH . 'database.php';
$db = new Database();
$pdo = $db->getConnection();

// Fetch signing info for transporter
$signed_info = null;
if ($role === 'transporter') {
    $stmtS = $pdo->prepare("SELECT has_accepted_contract, contract_signed_at FROM users WHERE id = ?");
    $stmtS->execute([$user_id]);
    $signed_info = $stmtS->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrat de Partenariat - CargoConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>dashboard.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include INC_PATH . 'sidebar.php'; ?>
        <main class="main-content">
            <?php include INC_PATH . 'topbar.php'; ?>

            <div class="page-header">
                <div>
                    <h1>Contrat de Partenariat</h1>
                    <p style="color: #94a3b8; margin-top: 5px;">Consultez les termes officiels et les engagements du réseau CargoConnect.</p>
                </div>
            </div>

            <div class="details-card" style="max-width: 900px; margin: 0 auto; background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255,255,255,0.08); padding: 40px; border-radius: 20px;">
                <div style="text-align: center; margin-bottom: 40px;">
                    <i class="fas fa-file-contract" style="font-size: 60px; color: #ff8c00; margin-bottom: 20px;"></i>
                    <h2 style="font-size: 32px; color: #fff;">Conditions d'Utilisation Transporteur</h2>
                    <div style="width: 60px; height: 4px; background: #ff8c00; margin: 20px auto; border-radius: 2px;"></div>
                    
                    <?php if ($role === 'transporter' && $signed_info && $signed_info['has_accepted_contract']): ?>
                        <div style="margin-top: 20px; display: inline-flex; align-items: center; gap: 10px; padding: 10px 20px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 30px; color: #4ade80; font-size: 14px; font-weight: 500;">
                            <i class="fas fa-check-circle"></i>
                            Contrat signé le : <?php echo date('d/m/Y à H:i', strtotime($signed_info['contract_signed_at'])); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="contract-body" style="color: #cbd5e1; line-height: 1.8; font-size: 16px;">
                    <section style="margin-bottom: 40px;">
                        <h3 style="color: #fff; margin-bottom: 15px; display: flex; align-items: center; gap: 15px;">
                            <span style="width: 32px; height: 32px; background: rgba(255,140,0,0.2); color: #ff8c00; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">01</span>
                            Commissions de Service
                        </h3>
                        <p>La plateforme CargoConnect prélève une commission de <strong>20%</strong> sur le montant total de chaque mission complétée. Cette commission permet le fonctionnement des serveurs, le marketing et le support technique dont vous bénéficiez.</p>
                    </section>

                    <section style="margin-bottom: 40px;">
                        <h3 style="color: #fff; margin-bottom: 15px; display: flex; align-items: center; gap: 15px;">
                            <span style="width: 32px; height: 32px; background: rgba(255,140,0,0.2); color: #ff8c00; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">02</span>
                            Seuil de Blocage (Batch de 5)
                        </h3>
                        <p>Pour assurer la fluidité des paiements, un système de blocage est en place. Si un transporteur accumule <strong>5 commissions non réglées</strong>, son accès aux nouvelles missions est automatiquement suspendu jusqu'à régularisation du solde.</p>
                    </section>

                    <section style="margin-bottom: 40px;">
                        <h3 style="color: #fff; margin-bottom: 15px; display: flex; align-items: center; gap: 15px;">
                            <span style="width: 32px; height: 32px; background: rgba(255,140,0,0.2); color: #ff8c00; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">03</span>
                            Désactivation & Réactivation
                        </h3>
                        <p>En cas de non-conformité, l'administration peut désactiver vos véhicules. Une procédure de <strong>Demande de Réactivation</strong> est alors nécessaire pour ré-ouvrir vos droits d'exploitation sur le réseau.</p>
                    </section>

                    <section style="margin-bottom: 40px;">
                        <h3 style="color: #fff; margin-bottom: 15px; display: flex; align-items: center; gap: 15px;">
                            <span style="width: 32px; height: 32px; background: rgba(255,140,0,0.2); color: #ff8c00; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">04</span>
                            Négociation & Transparence
                        </h3>
                        <p>Le transporteur est libre de proposer ses propres tarifs sur les demandes "Négociables". Toutes les transactions et modifications de statuts de mission doivent être effectuées via la plateforme pour garantir la traçabilité.</p>
                    </section>

                    <div style="background: rgba(255,140,0,0.05); padding: 25px; border-radius: 12px; border: 1px solid rgba(255,140,0,0.2); text-align: center; margin-top: 50px;">
                        <p style="margin: 0; font-size: 14px; font-style: italic;">Ce contrat reste consultable à tout moment depuis vos paramètres de compte.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="<?php echo JS_URL; ?>dashboard.js"></script>
</body>
</html>
