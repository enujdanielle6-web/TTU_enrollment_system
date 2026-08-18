<?php
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 fade-in-up" style="animation-delay: 0.1s;">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Scholarships & Grants</h1>
        <p class="text-muted mb-0">Manage institutional, government, and private scholarship programs.</p>
      </div>
      <div>
        <button type="button" class="btn btn-primary fw-medium shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#scholarshipModal" onclick="openCreateModal()">
          <i class="bi bi-plus-circle-fill me-1"></i> Create Scholarship
        </button>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="island position-relative overflow-hidden border-0 shadow-sm fade-in-up" style="border-radius: 16px; animation-delay: 0.2s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light fade-in-up" style="animation-delay: 0.3s;">
        <i class="bi bi-award text-primary"></i>
        <h2 class="mb-0 text-dark">Active Scholarship Programs</h2>
      </div>
      
      <div class="island-body p-0 fade-in-up" style="animation-delay: 0.4s;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Code</th>
                <th scope="col">Scholarship Name</th>
                <th scope="col">Category</th>
                <th scope="col">Tuition Coverage</th>
                <th scope="col">Slots</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($scholarships)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-award fs-1 d-block mb-3 text-secondary"></i>
                    No scholarships defined.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($scholarships as $scholarship): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark">
                      <?= htmlspecialchars($scholarship['code'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="fw-bold text-dark">
                      <?= htmlspecialchars($scholarship['name'], ENT_QUOTES, 'UTF-8') ?>
                      <?php if ($scholarship['provider']): ?>
                        <div class="text-muted fw-normal small"><i class="bi bi-building me-1"></i><?= htmlspecialchars($scholarship['provider'], ENT_QUOTES, 'UTF-8') ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border"><i class="bi bi-tag-fill me-1 text-primary"></i><?= htmlspecialchars($scholarship['category'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td class="fw-bold text-success">
                      <?php if ($scholarship['tuition_coverage_type'] === 'percentage'): ?>
                        <?= number_format((float)$scholarship['tuition_coverage_value'], 0) ?>%
                      <?php elseif ($scholarship['tuition_coverage_type'] === 'fixed'): ?>
                        ₱<?= number_format((float)$scholarship['tuition_coverage_value'], 2) ?>
                      <?php else: ?>
                        Full Tuition
                      <?php endif; ?>
                    </td>
                    <td>
                      <?= esc($scholarship['slots'] ? (int)$scholarship['slots'] : '<span class="text-muted">Unlimited</span>') ?>
                    </td>
                    <td>
                      <?php 
                        $statusClass = match($scholarship['status']) {
                            'Active' => 'bg-success',
                            'Draft' => 'bg-warning text-dark',
                            'Closed' => 'bg-secondary',
                            'Suspended' => 'bg-danger',
                            default => 'bg-light text-dark'
                        };
                      ?>
                      <span class="badge <?= esc($statusClass) ?> rounded-pill px-3"><?= htmlspecialchars($scholarship['status'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td class="text-end pe-4">
                      <button class="btn btn-sm btn-outline-secondary rounded-pill edit-scholarship-btn" 
                              data-scholarship='<?= json_encode($scholarship, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
                              title="Edit Scholarship">
                        <i class="bi bi-pencil-fill"></i>
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
    <div class="modal-content border-0 shadow-lg">
      <form action="scholarship_process.php" method="POST">
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" id="modal_action" value="create_scholarship">
        <input type="hidden" name="id" id="modal_id" value="">
        
        <div class="modal-header bg-light border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold text-dark" id="modal_title"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Create New Scholarship</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body p-4 pt-2">
            
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
        <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 rounded-pill fw-medium shadow-sm" id="modal_submit_btn">Save Scholarship</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
<script>
function openCreateModal() {
    document.getElementById('modal_action').value = 'create_scholarship';
    document.getElementById('modal_id').value = '';
    document.getElementById('modal_title').innerHTML = '<i class="bi bi-plus-circle-fill text-primary me-2"></i>Create New Scholarship';
    document.getElementById('modal_submit_btn').innerHTML = 'Create Scholarship';
    document.getElementById('modal_submit_btn').className = 'btn btn-primary px-4 rounded-pill fw-medium shadow-sm';
    
    // Clear forms
    const fields = ['s_code', 's_name', 's_provider', 's_slots', 's_min_gwa', 's_income', 's_description', 's_requirements', 's_start', 's_end'];
    fields.forEach(f => document.getElementById(f).value = '');
    
    document.getElementById('s_category').value = 'School-Based';
    document.getElementById('s_status').value = 'Draft';
    document.getElementById('s_tuition_type').value = 'fixed';
    document.getElementById('s_tuition_value').value = '0.00';
    document.getElementById('s_misc_type').value = 'fixed';
    document.getElementById('s_misc_value').value = '0.00';
    document.getElementById('s_stipend').value = '0.00';
    document.getElementById('s_book').value = '0.00';
    document.getElementById('s_program').value = '';
}

document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-scholarship-btn');
    const modal = new bootstrap.Modal(document.getElementById('scholarshipModal'));

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.getAttribute('data-scholarship'));
            
            document.getElementById('modal_action').value = 'update_scholarship';
            document.getElementById('modal_id').value = data.id;
            document.getElementById('modal_title').innerHTML = '<i class="bi bi-pencil-fill text-success me-2"></i>Edit Scholarship';
            document.getElementById('modal_submit_btn').innerHTML = 'Save Changes';
            document.getElementById('modal_submit_btn').className = 'btn btn-success px-4 rounded-pill fw-medium shadow-sm';
            
            // Populate
            document.getElementById('s_code').value = data.code || '';
            document.getElementById('s_name').value = data.name || '';
            document.getElementById('s_category').value = data.category || 'School-Based';
            document.getElementById('s_provider').value = data.provider || '';
            document.getElementById('s_status').value = data.status || 'Draft';
            document.getElementById('s_slots').value = data.slots || '';
            
            document.getElementById('s_tuition_type').value = data.tuition_coverage_type || 'fixed';
            document.getElementById('s_tuition_value').value = data.tuition_coverage_value || '0.00';
            document.getElementById('s_misc_type').value = data.misc_coverage_type || 'fixed';
            document.getElementById('s_misc_value').value = data.misc_coverage_value || '0.00';
            document.getElementById('s_stipend').value = data.stipend_amount || '0.00';
            document.getElementById('s_book').value = data.book_allowance || '0.00';
            
            document.getElementById('s_program').value = data.program_id || '';
            document.getElementById('s_min_gwa').value = data.min_gwa || '';
            document.getElementById('s_income').value = data.income_requirement || '';
            
            document.getElementById('s_start').value = data.application_start || '';
            document.getElementById('s_end').value = data.application_end || '';
            document.getElementById('s_description').value = data.description || '';
            document.getElementById('s_requirements').value = data.requirements || '';
            
            modal.show();
        });
    });
});
</script>
</body>
</html>
