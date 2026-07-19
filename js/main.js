document.addEventListener('DOMContentLoaded', function() {
    // 1. Auto-show spinner on form submissions
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // For the backup restore execute, let the custom confirmation modal handle the spinner
            if (form.id === 'restoreForm') {
                return;
            }

            // If the form has novalidate (custom validation) or needs-validation (Bootstrap),
            // only show the spinner if the form actually passes validation
            if (!form.checkValidity()) {
                return;
            }

            // Do not show spinner for AJAX forms
            if (form.classList.contains('ajax-form') || form.classList.contains('no-spinner')) {
                return;
            }

            // If another event listener (like an inline confirm()) cancelled the submission, do not show spinner
            if (e.defaultPrevented) {
                return;
            }

            // Create and show a full screen popup overlay spinner
            let overlay = document.getElementById('global-submit-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'global-submit-overlay';
                overlay.innerHTML = `
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-3 fw-bold text-dark" style="font-size: 1.1rem; letter-spacing: 0.5px;">Processing...</div>
                `;
                document.body.appendChild(overlay);
            }
            overlay.style.display = 'flex';
            if (form.dataset.isSubmitting === 'true') {
                e.preventDefault();
                return;
            }
            form.dataset.isSubmitting = 'true';
            
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                // Just add a class to make it look disabled, without actually disabling it
                submitBtn.classList.add('opacity-50', 'pe-none');
            }
        });
    });

    // 2. Alert auto-fade out helpers (optional visual polish)
    const alertList = document.querySelectorAll('.alert-dismissible');
    alertList.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    });
});
