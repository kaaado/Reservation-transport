document.addEventListener("DOMContentLoaded", () => {
    // Shared Validation Logic
    
    // 1. Telephone: starts with 0, then 5,6,或7, then 8 digits. (10 total)
    const phoneRegex = /^0[567][0-9]{8}$/;
    // 2. Name: chars, spaces, _, -. No numbers
    const nameRegex = /^[a-zA-Z\s_\-]+$/;
    // 3. Email check
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    // Inject CSS for validation dynamically
    const style = document.createElement('style');
    style.innerHTML = `
        .val-error-msg {
            color: #ef4444;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        .val-error-input {
            border-color: #ef4444 !important;
            background: rgba(239, 68, 68, 0.05) !important;
        }
        .pass-policy {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 10px;
            font-size: 12px;
            color: #64748b;
            padding: 10px;
            background: rgba(15,23,42,0.5);
            border-radius: 8px;
            display: none;
        }
        .pass-policy.active { display: flex; }
        .pass-policy span { display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .pass-policy span i { font-size: 10px; width: 12px; }
        .pass-policy span.valid { color: #22c55e; }
        .pass-policy span.invalid { color: #ef4444; }
    `;
    document.head.appendChild(style);

    // Inject SweetAlert2 CDN
    const swalScript = document.createElement('script');
    swalScript.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    document.head.appendChild(swalScript);

    // Helper: Build error element safely outside the position container
    function ensureErrorDiv(input) {
        let container = input.closest('.form-group, .form-group-dash') || input.parentNode;
        
        let existingError = container.querySelector('.val-error-msg');
        if (existingError) {
            return existingError;
        }

        let div = document.createElement('div');
        div.className = 'val-error-msg';
        container.appendChild(div);
        return div;
    }

    // Bind Telephone Inputs
    document.querySelectorAll('input[name="phone"]').forEach(input => {
        // Strictly limit size natively in HTML API
        input.setAttribute('maxlength', '10');
        
        // Strictly prevent character typing (only numbers)
        input.addEventListener('keypress', (e) => {
            if (!/^[0-9]$/.test(e.key)) {
                e.preventDefault();
            }
        });

        input.addEventListener('input', (e) => {
            const err = ensureErrorDiv(e.target);
            if (!phoneRegex.test(e.target.value)) {
                e.target.classList.add('val-error-input');
                err.innerHTML = `<i class="fas fa-exclamation-circle"></i> Le téléphone doit commencer par 05, 06 ou 07 et contenir 10 chiffres (sans espaces ou spéciaux).`;
                err.style.display = 'block';
            } else {
                e.target.classList.remove('val-error-input');
                err.style.display = 'none';
            }
        });
    });

    // Bind Name Inputs
    document.querySelectorAll('input[name="name"]').forEach(input => {
        input.addEventListener('input', (e) => {
            const err = ensureErrorDiv(e.target);
            if (!nameRegex.test(e.target.value)) {
                e.target.classList.add('val-error-input');
                err.innerHTML = `<i class="fas fa-exclamation-circle"></i> Le nom ne doit contenir que des lettres, espaces, tirets ou underscores (pas de chiffres).`;
                err.style.display = 'block';
            } else {
                e.target.classList.remove('val-error-input');
                err.style.display = 'none';
            }
        });
    });

    // Bind Email Inputs
    document.querySelectorAll('input[name="email"]').forEach(input => {
        input.addEventListener('input', (e) => {
            const err = ensureErrorDiv(e.target);
            if (!emailRegex.test(e.target.value)) {
                e.target.classList.add('val-error-input');
                err.innerHTML = `<i class="fas fa-exclamation-circle"></i> Veuillez entrer une adresse e-mail valide.`;
                err.style.display = 'block';
            } else {
                e.target.classList.remove('val-error-input');
                err.style.display = 'none';
            }
        });
    });

    // Bind Password Inputs
    document.querySelectorAll('input[name="new_password"], input[name="password"]').forEach(input => {
        const path = window.location.pathname;
        const isFullPolicyPage = path.includes('register.php') || path.includes('settings.php') || path.includes('reset-password.php');
        
        if (!isFullPolicyPage) {
            // Just basic length check for login and others
            input.addEventListener('input', (e) => {
                const err = ensureErrorDiv(e.target);
                if (e.target.value.length > 0 && e.target.value.length < 8) {
                    e.target.classList.add('val-error-input');
                    err.innerHTML = `<i class="fas fa-exclamation-circle"></i> Le mot de passe doit contenir au moins 8 caractères.`;
                    err.style.display = 'block';
                } else {
                    e.target.classList.remove('val-error-input');
                    err.style.display = 'none';
                }
            });
            return;
        }

        // Create Policy UI directly after the input's bounding group so it doesn't break absolute positioning
        let policyDiv = document.createElement('div');
        policyDiv.className = 'pass-policy';
        policyDiv.innerHTML = `
            <span class="p-len"><i class="fas fa-times"></i> Minimum 8 caractères</span>
            <span class="p-maj"><i class="fas fa-times"></i> Au moins 1 majuscule</span>
            <span class="p-min"><i class="fas fa-times"></i> Au moins 1 minuscule</span>
            <span class="p-num"><i class="fas fa-times"></i> Au moins 1 chiffre</span>
            <span class="p-spc"><i class="fas fa-times"></i> Au moins 1 caractère spécial (!@#$%)</span>
        `;
        
        let container = input.closest('.form-group, .form-group-dash') || input.parentNode;
        container.appendChild(policyDiv);

        let passChanged = false;

        input.addEventListener('focus', () => { policyDiv.classList.add('active'); passChanged = true; });

        input.addEventListener('input', (e) => {
            const val = e.target.value;
            
            // Check lengths & constraints
            const isValidLen = val.length >= 8;
            const isValidMaj = /[A-Z]/.test(val);
            const isValidMin = /[a-z]/.test(val);
            const isValidNum = /[0-9]/.test(val);
            const isValidSpc = /[!@#$%^&*(),.?":{}|<>]/.test(val); // special char

            const updateSpan = (selector, isValid) => {
                const sp = policyDiv.querySelector(selector);
                if (sp) {
                    if (isValid) {
                        sp.classList.add('valid');
                        sp.classList.remove('invalid');
                    } else {
                        sp.classList.add('invalid');
                        sp.classList.remove('valid');
                    }
                    const icon = sp.querySelector('i');
                    if (icon) {
                        icon.className = `fas fa-${isValid ? 'check' : 'times'}`;
                    }
                }
            };

            updateSpan('.p-len', isValidLen);
            updateSpan('.p-maj', isValidMaj);
            updateSpan('.p-min', isValidMin);
            updateSpan('.p-num', isValidNum);
            updateSpan('.p-spc', isValidSpc);

            // Overall
            if(passChanged) {
                if(isValidLen && isValidMaj && isValidMin && isValidNum && isValidSpc) {
                    e.target.classList.remove('val-error-input');
                } else {
                    e.target.classList.add('val-error-input');
                }
            }
        });
    });

    // Check Password Match (Rewrite confirmation)
    const newPass = document.querySelector('input[name="new_password"], input[name="password"]');
    const confPass = document.querySelector('input[name="confirm_password"], input[name="rewrite_password"]');
    
    if (newPass && confPass) {
        confPass.addEventListener('input', (e) => {
            const err = ensureErrorDiv(e.target);
            if (e.target.value !== newPass.value) {
                e.target.classList.add('val-error-input');
                err.innerHTML = `<i class="fas fa-exclamation-circle"></i> Les mots de passe ne correspondent pas.`;
                err.style.display = 'block';
            } else {
                e.target.classList.remove('val-error-input');
                err.style.display = 'none';
            }
        });
    }

    // Global Form Submission: Validation + Loading State
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (evt) => {
            const btn = form.querySelector('button[type="submit"]');
            
            // Find inputs with error classes
            const errors = form.querySelectorAll('.val-error-input');
            if(errors.length > 0) {
                evt.preventDefault();
                
                // Show Custom Glassmorphism SweetAlert
                if(typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Formulaire invalide',
                        text: 'Veuillez corriger les erreurs en rouge avant de soumettre.',
                        icon: 'error',
                        background: '#1e293b',
                        color: '#f8fafc',
                        confirmButtonColor: '#ff8c00'
                    }).then(() => {
                        errors[0].focus();
                    });
                }
                return;
            }

            // If no errors, add loading state to primary button
            if (btn && !btn.classList.contains('no-loading')) {
                btn.dataset.oldhtml = btn.innerHTML;
                btn.classList.add('loading');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.style.pointerEvents = 'none';
            }
        });
    });

});
