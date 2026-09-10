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

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    let app = {};
    if (form.dataset.app) {
        try {
            app = JSON.parse(form.dataset.app);
        } catch (err) {
            console.error('Failed to parse application details', err);
        }
    }

    const studentName = app.name || form.dataset.studentName || 'Applicant';
    const refNumber = app.ref_number || form.dataset.refNumber || '';
    const email = app.email || 'N/A';
    const academicLevel = app.academic_level || 'N/A';
    const program = app.program || 'N/A';
    const studentType = app.student_type || 'Regular';
    const section = app.section || 'Not Assigned';
    const paymentStatus = app.payment_status || 'Paid';
    const totalPaid = app.total_paid || '₱0.00';
    const totalAssessment = app.total_assessment || 'N/A';
    const medicalStatus = app.medical_status || 'Not Submitted';
    const dateApplied = app.date_applied || 'N/A';
    const appId = app.id || '';

    const isMedVerified = medicalStatus.toLowerCase() === 'verified';
    const isFullyPaid = paymentStatus.toLowerCase().includes('fully');

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '<div class="h4 fw-bold mb-0 text-dark">Review & Finalize Official Enrollment</div>',
            width: '640px',
            html: `
                <div class="text-start mt-2">
                    <!-- Student Identity Card -->
                    <div class="p-3 bg-light rounded-4 border mb-3">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <div class="fw-bold text-dark fs-5 mb-0">${escapeHtml(studentName)}</div>
                                <div class="text-muted small"><i class="bi bi-envelope me-1"></i>${escapeHtml(email)}</div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-3 py-2 rounded-pill fs-7">
                                <i class="bi bi-hash"></i>${escapeHtml(refNumber)}
                            </span>
                        </div>
                    </div>

                    <!-- Application Details Grid -->
                    <div class="card border rounded-4 shadow-none mb-3 overflow-hidden">
                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <span class="small fw-bold text-uppercase text-muted"><i class="bi bi-card-checklist me-1 text-primary"></i> Application Summary</span>
                            ${appId ? `<a href="../admissions/application_detail.php?id=${appId}" target="_blank" class="small text-decoration-none text-primary fw-medium"><i class="bi bi-box-arrow-up-right me-1"></i>Full Dossier</a>` : ''}
                        </div>
                        <div class="card-body p-3 bg-white">
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">LEVEL & PROGRAM</span>
                                    <span class="fw-semibold text-dark">${escapeHtml(academicLevel)} &bull; ${escapeHtml(program)}</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">STUDENT TYPE</span>
                                    <span class="fw-semibold text-dark">${escapeHtml(studentType)}</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">ASSIGNED SECTION</span>
                                    <span class="fw-semibold ${section !== 'Not Assigned' ? 'text-primary' : 'text-danger'}">
                                        <i class="bi bi-diagram-3 me-1"></i>${escapeHtml(section)}
                                    </span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">DATE APPLIED</span>
                                    <span class="fw-semibold text-dark">${escapeHtml(dateApplied)}</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">PAYMENT STATUS</span>
                                    <span class="badge ${isFullyPaid ? 'bg-success' : 'bg-info text-dark'} rounded-pill px-2 py-1">
                                        ${escapeHtml(paymentStatus)}
                                    </span>
                                    <span class="text-dark fw-medium ms-1">${escapeHtml(totalPaid)}</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">CLINIC CLEARANCE</span>
                                    <span class="badge ${isMedVerified ? 'bg-success' : 'bg-warning text-dark'} rounded-pill px-2 py-1">
                                        ${escapeHtml(medicalStatus)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Institutional Provisioning Notice -->
                    <div class="alert alert-success bg-success-subtle border-0 rounded-4 p-3 mb-0 text-success-emphasis small">
                        <div class="fw-bold mb-1"><i class="bi bi-shield-fill-check me-1"></i> Actions Executed Upon Finalization:</div>
                        <ul class="mb-0 ps-3">
                            <li>Generate official Student ID Number (<code>YYYY-XXXXXX</code>)</li>
                            <li>Provision institutional university email (<code>@ttu.edu.ph</code>)</li>
                            <li>Enroll in class section timetable & dispatch credentials</li>
                        </ul>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-mortarboard-fill me-1"></i> Yes, Finalize Official Enrollment',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            buttonsStyling: false,
            focusCancel: true,
            customClass: {
                popup: 'rounded-4 shadow-lg border-0 p-4',
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

