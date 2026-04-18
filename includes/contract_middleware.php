<?php
/**
 * Contract Middleware
 * Checks if a transporter has signed the partnership agreement.
 * Restructured with enhanced premium aesthetics and centered layout.
 */
function checkTransporterContract($pdo) {
    if ($_SESSION['role'] !== 'transporter') return;

    $user_id = $_SESSION['user_id'];
    
    // Always fetch fresh from DB to avoid session lag
    $stmt = $pdo->prepare("SELECT contract_signed_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $signed_at = $stmt->fetchColumn();

    if ($signed_at === null) {
        echo '
        <div class="modal-overlay active" id="contractMiddlewareModal" style="display: flex !important; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(20px); z-index: 999999; justify-content: center; align-items: center; padding: 20px;">
            <div class="modal-box" style="max-width: 850px; width: 100%; background: rgba(30, 41, 59, 0.95); border-radius: 30px; border: 1px solid rgba(255, 255, 255, 0.1); overflow: hidden; box-shadow: 0 40px 100px rgba(0,0,0,0.6); display: flex; flex-direction: column; animation: modalPop 0.5s cubic-bezier(0.16, 1, 0.3, 1);">
                
                <div style="display: flex; flex-direction: row; height: 100%; min-height: 550px;">
                    <!-- Left Sidebar Branding -->
                    <div style="width: 280px; background: linear-gradient(135deg, #ff8c00, #ea580c); padding: 40px; color: white; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; gap: 20px;">
                        <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 40px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <h2 style="font-size: 28px; font-weight: 800; line-height: 1.2; margin: 0;">Accord de Partenariat</h2>
                        <div style="width: 40px; height: 4px; background: white; border-radius: 2px; opacity: 0.5;"></div>
                        <p style="font-size: 14px; opacity: 0.9; line-height: 1.6;">Bienvenue dans le réseau CargoConnect. Finalisez votre contrat pour commencer votre activité.</p>
                    </div>

                    <!-- Right Content Area -->
                    <div style="flex: 1; padding: 50px; background: #1e293b; display: flex; flex-direction: column;">
                        <div style="margin-bottom: 30px;">
                            <h3 style="color: #fff; font-size: 22px; margin-bottom: 15px;">Termes & Conditions</h3>
                            <p style="color: #94a3b8; font-size: 14px;">Veuillez passer en revue les clauses essentielles ci-dessous avant de procéder à la signature numérique.</p>
                        </div>

                        <div style="flex: 1; overflow-y: auto; padding-right: 15px; margin-bottom: 30px; class=\'custom-scroll\'">
                            <style>
                                .contract-item { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); }
                                .contract-item i { color: #ff8c00; margin-right: 10px; width: 20px; text-align: center; }
                                .contract-item h4 { color: #f8fafc; font-size: 16px; margin-bottom: 8px; display: flex; align-items: center; }
                                .contract-item p { color: #94a3b8; font-size: 13px; line-height: 1.6; }
                            </style>

                            <div class="contract-item">
                                <h4><i class="fas fa-coins"></i> Commission de Service</h4>
                                <p>CargoConnect prélève une commission fixe de 20% sur chaque réservation. Ces frais sont déduits automatiquement après le paiement client.</p>
                            </div>

                            <div class="contract-item">
                                <h4><i class="fas fa-exclamation-triangle"></i> Seuil de Gouvernance</h4>
                                <p>L\'accumulation de 5 commissions non réglées entraîne une suspension automatique de votre accès aux nouvelles missions.</p>
                            </div>

                            <div class="contract-item">
                                <h4><i class="fas fa-truck-loading"></i> Responsabilités</h4>
                                <p>Le transporteur s\'engage à fournir des documents de transport valides et à maintenir l\'état sécuritaire de ses véhicules déclarés.</p>
                            </div>

                            <div class="contract-item">
                                <h4><i class="fas fa-comments-dollar"></i> Flexibilité de Prix</h4>
                                <p>Vous disposez de la pleine autonomie pour négocier vos tarifs avec les clients pour les missions marquées comme négociables.</p>
                            </div>
                        </div>

                        <!-- Action Zone -->
                        <div style="padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                            <label style="display: flex; align-items: center; gap: 15px; cursor: pointer; color: #fff; margin-bottom: 25px; user-select: none;">
                                <input type="checkbox" id="middlewareAcceptCheck" style="width: 22px; height: 22px; accent-color: #ff8c00; cursor: pointer;">
                                <span style="font-size: 14px; color: #cbd5e1;">J\'accepte les termes du contrat CargoConnect.</span>
                            </label>
                            
                            <button id="middlewareSignBtn" disabled style="width: 100%; background: #ff8c00; color: white; border: none; padding: 18px; border-radius: 14px; font-weight: 700; font-size: 16px; cursor: not-allowed; opacity: 0.5; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;">
                                <i class="fas fa-pen-nib"></i> Signer Numériquement
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        @keyframes modalPop {
            from { transform: scale(0.95) translateY(20px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }
        #contractMiddlewareModal ::-webkit-scrollbar { width: 5px; }
        #contractMiddlewareModal ::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); }
        #contractMiddlewareModal ::-webkit-scrollbar-thumb { background: rgba(255,140,0,0.3); border-radius: 10px; }
        </style>
        
        <script>
        document.getElementById("middlewareAcceptCheck").addEventListener("change", function() {
            const btn = document.getElementById("middlewareSignBtn");
            if (this.checked) {
                btn.disabled = false;
                btn.style.opacity = "1";
                btn.style.cursor = "pointer";
                btn.style.boxShadow = "0 10px 20px rgba(255, 140, 0, 0.2)";
            } else {
                btn.disabled = true;
                btn.style.opacity = "0.5";
                btn.style.cursor = "not-allowed";
                btn.style.boxShadow = "none";
            }
        });

        document.getElementById("middlewareSignBtn").addEventListener("click", async function() {
            const btn = this;
            btn.innerHTML = \'<i class="fas fa-circle-notch fa-spin"></i> Signature en cours...\';
            btn.disabled = true;

            const formData = new FormData();
            formData.append("csrf_token", "' . $_SESSION['csrf_token'] . '");
            
            try {
                const response = await fetch("' . URL_ROOT . 'api/accept_contract.php", {
                    method: "POST",
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    btn.innerHTML = \'<i class="fas fa-check"></i> Signé avec succès\';
                    btn.style.background = "#22c55e";
                    setTimeout(() => {
                        document.getElementById("contractMiddlewareModal").style.animation = "modalPop 0.4s reverse forwards";
                        setTimeout(() => {
                            document.getElementById("contractMiddlewareModal").style.display = "none";
                        }, 400);
                    }, 800);
                } else {
                    alert("Erreur: " + res.error);
                    btn.innerHTML = \'<i class="fas fa-pen-nib"></i> Signer Numériquement\';
                    btn.disabled = false;
                }
            } catch (e) {
                alert("Erreur de communication avec le serveur.");
                btn.innerHTML = \'<i class="fas fa-pen-nib"></i> Signer Numériquement\';
                btn.disabled = false;
            }
        });
        </script>';
    }
}
