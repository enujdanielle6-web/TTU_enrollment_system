<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('fees.manage');

$pageTitle = 'Fee Templates - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';

// Fetch fee templates
$templates = [];
try {
    $stmt = $pdo->query('SELECT * FROM fee_templates ORDER BY grade_level ASC, strand ASC');
    $templates = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fee templates fetch failed: ' . $e->getMessage());
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Fee Templates</h1>
        <p class="text-muted mb-0">Manage the financial fee structures assigned to students upon enrollment.</p>
      </div>
      <div>
        <button type="button" class="btn btn-primary fw-medium shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addFeeTemplateModal">
          <i class="bi bi-plus-circle-fill me-1"></i> Add Fee Template
        </button>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light d-flex justify-content-between align-items-center">
        <div>
          <i class="bi bi-cash-stack text-primary"></i>
          <h2 class="mb-0 text-dark d-inline-block">Existing Fee Templates</h2>
        </div>
        <div>
          <div class="input-group shadow-sm" style="width: 250px;">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="tableSearch" class="form-control border-start-0" placeholder="Search templates...">
          </div>
        </div>
      </div>
      
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Template Name</th>
                <th scope="col">Academic Level</th>
                <th scope="col">Grade Level</th>
                <th scope="col">Program</th>
                <th scope="col">Total Amount</th>
                <th scope="col" class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($templates)): ?>
                <tr>
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-cash fs-1 d-block mb-3 text-secondary"></i>
                    No fee templates defined.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($templates as $template): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark">
                      <?= htmlspecialchars($template['name'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                      <?= htmlspecialchars($template['academic_level'] ?? 'Senior High School', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                      <?= htmlspecialchars($template['grade_level'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                      <?= htmlspecialchars($template['strand'] ?? 'All', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="fw-bold text-success">
                      ₱<?= number_format((float)$template['total_amount'], 2) ?>
                    </td>
                    <td class="text-end pe-4">
                      <!-- Edit Button -->
                      <button class="btn btn-sm btn-outline-secondary rounded-pill edit-template-btn" 
                              data-id="<?= $template['id'] ?>"
                              data-name="<?= htmlspecialchars($template['name'], ENT_QUOTES, 'UTF-8') ?>"
                              data-level="<?= htmlspecialchars($template['academic_level'] ?? 'Senior High School', ENT_QUOTES, 'UTF-8') ?>"
                              data-grade="<?= htmlspecialchars($template['grade_level'], ENT_QUOTES, 'UTF-8') ?>"
                              data-strand="<?= htmlspecialchars($template['strand'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-tuition="<?= $template['tuition_fee'] ?>"
                              data-misc="<?= $template['miscellaneous_fee'] ?>"
                              data-reg="<?= $template['registration_fee'] ?>"
                              data-lab="<?= $template['laboratory_fee'] ?>"
                              data-other="<?= $template['other_fees'] ?>"
                              data-bs-toggle="modal" 
                              data-bs-target="#editFeeTemplateModal">
                        <i class="bi bi-pencil-square"></i> Edit
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              <tr id="noResultsRow" style="display: none;">
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="bi bi-search fs-1 d-block mb-3 text-secondary"></i>
                  No templates match your search.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Add Fee Template Modal -->
<div class="modal fade" id="addFeeTemplateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Add New Fee Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="fee_process.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="create_fee_template">
          <?= getCsrfInput() ?>
          
          <div class="row g-3 mb-3">
            <div class="col-md-12">
              <label class="form-label small fw-semibold text-dark">Template Name</label>
              <input type="text" name="name" class="form-control bg-light" required placeholder="e.g. Grade 11 STEM Fees">
            </div>
          </div>
          
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-dark">Academic Level</label>
              <select name="academic_level" class="form-select bg-light" required>
                <option value="Senior High School">Senior High School</option>
                <option value="College">College</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-dark">Grade/Year Level</label>
              <select name="grade_level" class="form-select bg-light" required>
                <option value="" disabled selected>Select Level</option>
                <option value="Grade 11">Grade 11</option>
                <option value="Grade 12">Grade 12</option>
                <option value="1st Year">1st Year</option>
                <option value="2nd Year">2nd Year</option>
                <option value="3rd Year">3rd Year</option>
                <option value="4th Year">4th Year</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-dark">Academic Program (Optional)</label>
              <select name="strand" class="form-select bg-light">
                <option value="">Applies to All Strands</option>
                <?php
                  $strands = $pdo->query('
                    SELECT code, name FROM college_programs WHERE is_active = 1 
                    UNION ALL 
                    SELECT code, name FROM shs_strands WHERE is_active = 1 
                    ORDER BY code ASC
                  ')->fetchAll();
                  foreach ($strands as $strand) {
                      echo '<option value="' . htmlspecialchars($strand['code'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($strand['code'] . ' - ' . $strand['name'], ENT_QUOTES, 'UTF-8') . '</option>';
                  }
                ?>
              </select>
            </div>
          </div>
          
          <hr class="my-4">
          <h6 class="fw-bold mb-3">Fee Breakdown</h6>

          <div class="row g-3">
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-semibold text-dark">Tuition Fee</label>
              <div class="input-group">
                <span class="input-group-text bg-light">₱</span>
                <input type="number" step="0.01" min="0" name="tuition_fee" class="form-control bg-light" required value="0.00">
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-semibold text-dark">Miscellaneous Fee</label>
              <div class="input-group">
                <span class="input-group-text bg-light">₱</span>
                <input type="number" step="0.01" min="0" name="miscellaneous_fee" class="form-control bg-light" required value="0.00">
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-semibold text-dark">Registration Fee</label>
              <div class="input-group">
                <span class="input-group-text bg-light">₱</span>
                <input type="number" step="0.01" min="0" name="registration_fee" class="form-control bg-light" required value="0.00">
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-semibold text-dark">Laboratory Fee</label>
              <div class="input-group">
                <span class="input-group-text bg-light">₱</span>
                <input type="number" step="0.01" min="0" name="laboratory_fee" class="form-control bg-light" required value="0.00">
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-semibold text-dark">Other Fees</label>
              <div class="input-group">
                <span class="input-group-text bg-light">₱</span>
                <input type="number" step="0.01" min="0" name="other_fees" class="form-control bg-light" required value="0.00">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Template</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Fee Template Modal -->
<div class="modal fade" id="editFeeTemplateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Edit Fee Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="fee_process.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="update_fee_template">
          <?= getCsrfInput() ?>
          <input type="hidden" name="id" id="editTemplateId">
          
          <div class="row g-3 mb-3">
            <div class="col-md-12">
              <label class="form-label small fw-semibold text-dark">Template Name</label>
              <input type="text" name="name" id="editTemplateName" class="form-control bg-light" required>
            </div>
          </div>
          
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-dark">Academic Level</label>
              <select name="academic_level" id="editTemplateLevel" class="form-select bg-light" required>
                <option value="Senior High School">Senior High School</option>
                <option value="College">College</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-dark">Grade/Year Level</label>
              <select name="grade_level" id="editTemplateGrade" class="form-select bg-light" required>
                <option value="Grade 11">Grade 11</option>
                <option value="Grade 12">Grade 12</option>
                <option value="1st Year">1st Year</option>
                <option value="2nd Year">2nd Year</option>
                <option value="3rd Year">3rd Year</option>
                <option value="4th Year">4th Year</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-dark">Academic Program (Optional)</label>
              <select name="strand" id="editTemplateStrand" class="form-select bg-light">
                <option value="">Applies to All Strands</option>
                <?php
                  foreach ($strands as $strand) {
                      echo '<option value="' . htmlspecialchars($strand['code'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($strand['code'] . ' - ' . $strand['name'], ENT_QUOTES, 'UTF-8') . '</option>';
                  }
                ?>
              </select>
            </div>
          </div>
          
          <hr class="my-4">
          <h6 class="fw-bold mb-3">Fee Breakdown</h6>

          <div class="row g-3">
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-semibold text-dark">Tuition Fee</label>
              <div class="input-group">
                <span class="input-group-text bg-light">₱</span>
                <input type="number" step="0.01" min="0" name="tuition_fee" id="editTemplateTuition" class="form-control bg-light" required>
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-semibold text-dark">Miscellaneous Fee</label>
              <div class="input-group">
                <span class="input-group-text bg-light">₱</span>
                <input type="number" step="0.01" min="0" name="miscellaneous_fee" id="editTemplateMisc" class="form-control bg-light" required>
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-semibold text-dark">Registration Fee</label>
              <div class="input-group">
                <span class="input-group-text bg-light">₱</span>
                <input type="number" step="0.01" min="0" name="registration_fee" id="editTemplateReg" class="form-control bg-light" required>
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-semibold text-dark">Laboratory Fee</label>
              <div class="input-group">
                <span class="input-group-text bg-light">₱</span>
                <input type="number" step="0.01" min="0" name="laboratory_fee" id="editTemplateLab" class="form-control bg-light" required>
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-semibold text-dark">Other Fees</label>
              <div class="input-group">
                <span class="input-group-text bg-light">₱</span>
                <input type="number" step="0.01" min="0" name="other_fees" id="editTemplateOther" class="form-control bg-light" required>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
  $(document).ready(function() {
    $('.edit-template-btn').on('click', function() {
      $('#editTemplateId').val($(this).data('id'));
      $('#editTemplateName').val($(this).data('name'));
      $('#editTemplateLevel').val($(this).data('level'));
      $('#editTemplateGrade').val($(this).data('grade'));
      $('#editTemplateStrand').val($(this).data('strand'));
      $('#editTemplateTuition').val($(this).data('tuition'));
      $('#editTemplateMisc').val($(this).data('misc'));
      $('#editTemplateReg').val($(this).data('reg'));
      $('#editTemplateLab').val($(this).data('lab'));
      $('#editTemplateOther').val($(this).data('other'));
    });

    const searchInput = document.getElementById('tableSearch');
    if(searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('.custom-table tbody tr');
            let visibleCount = 0;
            let hasDataRows = false;
            
            rows.forEach(row => {
                if(row.id === 'noResultsRow' || row.querySelector('td[colspan]')) return;
                hasDataRows = true;
                
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            const noResultsRow = document.getElementById('noResultsRow');
            if (noResultsRow) {
                noResultsRow.style.display = (visibleCount === 0 && hasDataRows) ? '' : 'none';
            }
        });
    }
  });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

