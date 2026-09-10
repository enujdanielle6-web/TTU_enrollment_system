document.addEventListener('DOMContentLoaded', function () {
    // 1. Auto-show spinner on form submissions
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
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

    // 3. Sidebar Minimize Toggle & Tooltips
    const sidebar = document.getElementById('adminSidebar');
    const minimizeBtn = document.getElementById('sidebarMinimize');
    
    // Initialize tooltips for sidebar
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('#adminSidebar [data-sidebar-tooltip="true"]'));
    const tooltips = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover',
            boundary: document.body
        });
    });

    function updateTooltips() {
        if (!sidebar) return;
        if (sidebar.classList.contains('minimized')) {
            tooltips.forEach(t => t.enable());
        } else {
            tooltips.forEach(t => t.disable());
            // Also hide any currently showing tooltips
            tooltips.forEach(t => t.hide());
        }
    }
    
    // Check LocalStorage for saved state
    if (sidebar && localStorage.getItem('sidebarMinimized') === 'true') {
        sidebar.classList.add('minimized');
    }

    // Set initial tooltip state
    updateTooltips();

    if (minimizeBtn && sidebar) {
        minimizeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('minimized');
            
            updateTooltips();
            
            // Save state
            if (sidebar.classList.contains('minimized')) {
                localStorage.setItem('sidebarMinimized', 'true');
            } else {
                localStorage.setItem('sidebarMinimized', 'false');
            }
        });
    }
});

// 4. Custom SweetAlert2 for Enrollment Finalization
document.addEventListener('submit', function (e) {
    const form = e.target.closest('.form-finalize');
    if (!form) return;

    if (form.dataset.confirmed === 'true') {
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    const studentName = form.dataset.studentName || 'this student';
    const refNumber = form.dataset.refNumber || '';

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Finalize Official Enrollment?',
            html: `
                <div class="text-start p-2">
                    <p class="mb-2 text-muted">Are you sure you want to finalize official enrollment for:</p>
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="fw-bold text-dark fs-6">${escapeHtml(studentName)}</div>
                        <div class="text-muted small font-monospace mt-1"><i class="bi bi-hash"></i>${escapeHtml(refNumber)}</div>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0 rounded-3 border-0 bg-info-subtle text-info-emphasis">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        This will assign their official student number, provision their institutional <code>@ttu.edu.ph</code> email, and send welcome credentials.
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-mortarboard-fill me-1"></i> Yes, Finalize Enrollment',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            buttonsStyling: false,
            focusCancel: true,
            customClass: {
                popup: 'rounded-4 shadow-lg border-0 p-3',
                confirmButton: 'btn btn-success rounded-pill px-4 py-2 fw-semibold shadow-sm',
                cancelButton: 'btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold me-2'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.dataset.confirmed = 'true';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }
        });
    } else {
        if (confirm(`Finalize official enrollment for ${studentName} (${refNumber})?\n\nThis will generate their student number, create institutional email, and send welcome credentials.`)) {
            form.dataset.confirmed = 'true';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }
    }
}, true);
