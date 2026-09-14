<?php
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-award-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Scholarships & Grants Catalog</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> Aid Programs
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-layers text-primary me-1"></i><?= count($scholarships ?? []) ?> Programs
              </span>
            </div>
            <p class="text-muted small mb-0">Configure institutional, government, and private grant types, qualification criteria, and coverage.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="scholarship_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-speedometer2 text-primary"></i>
            <span>Dashboard</span>
          </a>
          <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#scholarshipModal" onclick="openCreateModal()">
            <i class="bi bi-plus-lg"></i>
            <span>Create Scholarship</span>
          </button>
        </div>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
        <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
        <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <!-- Scholarship Programs Catalog Card (Dossier Card Styling) -->
    <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.15s;">
      <div class="dossier-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-award-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Configured Programs</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <?= count($scholarships ?? []) ?> Records
            </span>
          </div>
        </div>
        <div class="input-group input-group-sm" style="width: 240px;">
          <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
          <input type="text" id="programSearch" class="form-control form-control-sm border-start-0 ps-0" placeholder="Search programs...">
        </div>
      </div>
      
      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table">
            <thead>
              <tr>
                <th class="ps-4">Code</th>
                <th>Scholarship Name</th>
                <th>Category</th>
                <th>Tuition Coverage</th>
                <th>Slots</th>
                <th>Status</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($scholarships)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                        <i class="bi bi-award fs-1 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">No Scholarships Defined</h3>
                      <p class="small text-muted mb-0">Use the "Create Scholarship" button above to add new programs.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($scholarships as $scholarship): ?>
                  <tr>
                    <td class="ps-4">
                      <span class="applicant-ref-badge fw-bold text-dark">
                        <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($scholarship['code'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>
                    <td>
                      <div class="fw-bold text-dark"><?= htmlspecialchars($scholarship['name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <?php if ($scholarship['provider']): ?>
                        <div class="text-muted small mt-0.5"><i class="bi bi-building me-1"></i><?= htmlspecialchars($scholarship['provider'], ENT_QUOTES, 'UTF-8') ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                        <i class="bi bi-tag-fill me-1 text-primary"></i><?= htmlspecialchars($scholarship['category'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>
                    <td class="fw-bold text-success">
                      <?php if ($scholarship['tuition_coverage_type'] === 'percentage'): ?>
                        <?= number_format((float)$scholarship['tuition_coverage_value'], 0) ?>% Tuition
                      <?php elseif ($scholarship['tuition_coverage_type'] === 'fixed'): ?>
                        ₱<?= number_format((float)$scholarship['tuition_coverage_value'], 2) ?>
                      <?php else: ?>
                        Full Tuition
                      <?php endif; ?>
                    </td>
                    <td>
                      <?= !empty($scholarship['slots']) ? '<span class="badge bg-light text-dark border">' . esc((int)$scholarship['slots']) . ' slots</span>' : '<span class="text-muted small">Unlimited</span>' ?>
                    </td>
                    <td>
                      <?php 
                        $statusClass = match($scholarship['status']) {
                            'Active' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                            'Draft' => 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25',
                            'Closed' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                            'Suspended' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                            default => 'bg-light text-dark border'
                        };
                      ?>
                      <span class="badge <?= esc($statusClass) ?> rounded-pill px-2.5 py-1 small fw-semibold"><?= htmlspecialchars($scholarship['status'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td class="text-end pe-4">
                      <button type="button" 
                              class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 extra-small fw-medium d-inline-flex align-items-center gap-1 edit-scholarship-btn" 
                              data-bs-toggle="modal" 
                              data-bs-target="#scholarshipModal"
                              data-scholarship="<?= htmlspecialchars(json_encode($scholarship), ENT_QUOTES, 'UTF-8') ?>"
                              title="Edit Scholarship">
                        <i class="bi bi-pencil-fill"></i>
                        <span>Edit</span>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Unified Create/Edit Scholarship Modal -->
<div class="modal fade" id="scholarshipModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <form action="scholarship_process.php" method="POST" class="modal-content border-0 shadow-lg" id="scholarshipForm" style="max-height: 90vh;">
      <?= getCsrfInput() ?>
      <input type="hidden" name="action" id="modal_action" value="create_scholarship">
      <input type="hidden" name="id" id="modal_id" value="">
      
      <div class="modal-header bg-light border-bottom py-3 px-4">
        <h5 class="modal-title fw-bold text-dark" id="modal_title"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Create New Scholarship</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      
      <div class="modal-body p-4" style="overflow-y: auto;">
            
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">1. Basic Information</h6>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-dark">Scholarship Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" name="code" id="s_code" placeholder="e.g. CHED-2025" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-dark">Scholarship Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" name="name" id="s_name" placeholder="e.g. CHED Merit Scholarship" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Category <span class="text-danger">*</span></label>
                    <select class="form-select bg-light" name="category" id="s_category" required>
                        <option value="School-Based">School-Based</option>
                        <option value="Government">Government</option>
                        <option value="Department-Based">Department-Based</option>
                        <option value="Private">Private/External</option>
                        <option value="Special">Special Eligibility</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Provider/Sponsor</label>
                    <input type="text" class="form-control bg-light" name="provider" id="s_provider" placeholder="e.g. CHED, DOST, SM Foundation">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Status <span class="text-danger">*</span></label>
                    <select class="form-select bg-light" name="status" id="s_status" required>
                        <option value="Draft">Draft</option>
                        <option value="Active">Active</option>
                        <option value="Closed">Closed</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Available Slots</label>
                    <input type="number" class="form-control bg-light" name="slots" id="s_slots" placeholder="Leave empty for unlimited">
                </div>
            </div>

            <h6 class="fw-bold text-success mb-3 border-bottom pb-2">2. Financial Benefits</h6>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-dark">Tuition Coverage Type <span class="text-danger">*</span></label>
                    <select class="form-select bg-light" name="tuition_coverage_type" id="s_tuition_type" required>
                        <option value="full">Full Tuition</option>
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount (₱)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-dark">Tuition Value</label>
                    <input type="number" step="0.01" class="form-control bg-light" name="tuition_coverage_value" id="s_tuition_value" value="0.00">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-dark">Misc Coverage Type <span class="text-danger">*</span></label>
                    <select class="form-select bg-light" name="misc_coverage_type" id="s_misc_type" required>
                        <option value="full">Full Misc</option>
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount (₱)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-dark">Misc Value</label>
                    <input type="number" step="0.01" class="form-control bg-light" name="misc_coverage_value" id="s_misc_value" value="0.00">
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Stipend Amount (per semester)</label>
                    <input type="number" step="0.01" class="form-control bg-light" name="stipend_amount" id="s_stipend" value="0.00">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Book/Other Allowance</label>
                    <input type="number" step="0.01" class="form-control bg-light" name="book_allowance" id="s_book" value="0.00">
                </div>
            </div>

            <h6 class="fw-bold text-info mb-3 border-bottom pb-2">3. Eligibility & Restrictions</h6>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Program Restriction</label>
                    <select class="form-select bg-light" name="program_id" id="s_program">
                        <option value="">-- No Restriction --</option>
                        <?php foreach ($programs as $prog): ?>
                            <option value="<?= esc($prog['id']) ?>"><?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Minimum GWA</label>
                    <input type="number" step="0.01" max="5.0" class="form-control bg-light" name="min_gwa" id="s_min_gwa" placeholder="e.g. 1.75">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-dark">Maximum Income (₱)</label>
                    <input type="number" step="0.01" class="form-control bg-light" name="income_requirement" id="s_income" placeholder="e.g. 300000">
                </div>
            </div>
            
            <h6 class="fw-bold text-warning text-dark mb-3 border-bottom pb-2">4. Application Details</h6>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Application Start Date</label>
                    <input type="date" class="form-control bg-light" name="application_start" id="s_start">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark">Application Deadline</label>
                    <input type="date" class="form-control bg-light" name="application_end" id="s_end">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Description</label>
                <textarea class="form-control bg-light" name="description" id="s_description" rows="2" placeholder="Brief overview of the scholarship..."></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Required Documents / Requirements</label>
                <textarea class="form-control bg-light" name="requirements" id="s_requirements" rows="3" placeholder="List required documents (e.g. ITR, Certificate of Good Moral, Grades...)"></textarea>
            </div>
            
        </div>
        <div class="modal-footer bg-light border-top py-3 px-4">
          <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 rounded-pill fw-medium shadow-sm" id="modal_submit_btn">Save Scholarship</button>
        </div>
    </form>
  </div>
</div>

<script>
window.openCreateModal = function() {
    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val !== undefined && val !== null ? val : '';
    };

    setVal('modal_action', 'create_scholarship');
    setVal('modal_id', '');
    
    const titleEl = document.getElementById('modal_title');
    if (titleEl) {
        titleEl.innerHTML = '<i class="bi bi-plus-circle-fill text-primary me-2"></i>Create New Scholarship';
    }
    const submitBtn = document.getElementById('modal_submit_btn');
    if (submitBtn) {
        submitBtn.innerHTML = 'Create Scholarship';
        submitBtn.className = 'btn btn-primary px-4 rounded-pill fw-medium shadow-sm';
    }
    
    // Clear forms
    const fields = ['s_code', 's_name', 's_provider', 's_slots', 's_min_gwa', 's_income', 's_description', 's_requirements', 's_start', 's_end'];
    fields.forEach(f => setVal(f, ''));
    
    setVal('s_category', 'School-Based');
    setVal('s_status', 'Draft');
    setVal('s_tuition_type', 'fixed');
    setVal('s_tuition_value', '0.00');
    setVal('s_misc_type', 'fixed');
    setVal('s_misc_value', '0.00');
    setVal('s_stipend', '0.00');
    setVal('s_book', '0.00');
    setVal('s_program', '');
};

function populateEditScholarshipModal(data) {
    if (!data) return;

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = (val !== null && val !== undefined) ? val : '';
    };

    setVal('modal_action', 'update_scholarship');
    setVal('modal_id', data.id);
    
    const titleEl = document.getElementById('modal_title');
    if (titleEl) {
        titleEl.innerHTML = '<i class="bi bi-pencil-fill text-primary me-2"></i>Edit Scholarship';
    }
    const submitBtn = document.getElementById('modal_submit_btn');
    if (submitBtn) {
        submitBtn.innerHTML = 'Save Changes';
        submitBtn.className = 'btn btn-primary px-4 rounded-pill fw-medium shadow-sm';
    }
    
    // Populate form inputs
    setVal('s_code', data.code);
    setVal('s_name', data.name);
    setVal('s_category', data.category || 'School-Based');
    setVal('s_provider', data.provider);
    setVal('s_status', data.status || 'Draft');
    setVal('s_slots', data.slots);
    
    setVal('s_tuition_type', data.tuition_coverage_type || 'fixed');
    setVal('s_tuition_value', data.tuition_coverage_value || '0.00');
    setVal('s_misc_type', data.misc_coverage_type || 'fixed');
    setVal('s_misc_value', data.misc_coverage_value || '0.00');
    setVal('s_stipend', data.stipend_amount || '0.00');
    setVal('s_book', data.book_allowance || '0.00');
    
    setVal('s_program', data.program_id);
    setVal('s_min_gwa', data.min_gwa);
    setVal('s_income', data.income_requirement);
    
    setVal('s_start', data.application_start);
    setVal('s_end', data.application_end);
    setVal('s_description', data.description);
    setVal('s_requirements', data.requirements);

    // Explicitly show modal if not auto-opened
    const modalEl = document.getElementById('scholarshipModal');
    if (modalEl && window.bootstrap && bootstrap.Modal) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.show();
    }
}

// Global click event delegation for edit buttons (works across SPA navigation and normal page loads)
if (!window.__scholarshipEditHandlerAttached__) {
    window.__scholarshipEditHandlerAttached__ = true;
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.edit-scholarship-btn');
        if (!btn) return;

        try {
            const raw = btn.getAttribute('data-scholarship');
            if (raw) {
                const data = JSON.parse(raw);
                populateEditScholarshipModal(data);
            }
        } catch (err) {
            console.error('Failed to parse scholarship data:', err);
        }
    });

    // Client-side search logic
    document.addEventListener('keyup', function(e) {
        if (e.target && e.target.id === 'programSearch') {
            const filter = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.dashboard-table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
