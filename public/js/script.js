document.addEventListener('DOMContentLoaded', () => {
    
    // Abstract Password Visibility Toggle Feature
    const togglePasswordElements = document.querySelectorAll('.password-toggle');
    
    togglePasswordElements.forEach(item => {
        item.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    });

    // UX Feature: Loading state interceptor on primary submit buttons
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            if (btn) {
                // Prevent button collapsing
                const width = btn.offsetWidth;
                btn.style.width = width + 'px';
                if (!btn.dataset.oldhtml) btn.dataset.oldhtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.classList.add('loading');
                
                // Disable inputs to prevent interaction while loading
                const formElements = this.querySelectorAll('input, select, textarea');
                formElements.forEach(el => {
                    el.style.pointerEvents = 'none';
                    el.classList.add('locked');
                });
                
                // Prevent further clicks
                btn.style.pointerEvents = 'none';
                this.classList.add('form-loading');
            }
        });
    });
});
