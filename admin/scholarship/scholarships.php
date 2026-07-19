<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('scholarships.manage');

$pageTitle = 'Scholarship Types - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';

// Fetch scholarships
$scholarships = [];
try {
    $stmt = $pdo->query('SELECT * FROM scholarships ORDER BY discount_value DESC, name ASC');
    $scholarships = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Scholarships fetch failed: ' . $e->getMessage());
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Scholarship Types</h1>
        <p class="text-muted mb-0">Manage the scholarships and financial aids available to students.</p>
      </div>
      <div>
        <button type="button" class="btn btn-primary fw-medium shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addScholarshipModal">
          <i class="bi bi-plus-circle-fill me-1"></i> Add Scholarship
        </button>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="island position-relative overflow-hidden border-0 shadow-sm" style="border-radius: 16px;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light">
        <i class="bi bi-award text-primary"></i>
        <h2 class="mb-0 text-dark">Existing Scholarship Types</h2>
      </div>
      
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Scholarship Name</th>
                <th scope="col">Discount Type</th>
                <th scope="col">Value</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($scholarships)): ?>
                <tr>
                  <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-award fs-1 d-block mb-3 text-secondary"></i>
                    No scholarships defined.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($scholarships as $scholarship): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark">
                      <?= htmlspecialchars($scholarship['name'], ENT_QUOTES, 'UTF-8') ?>
                      <?php if ($scholarship['description']): ?>
                        <div class="text-muted fw-normal small"><?= htmlspecialchars($scholarship['description'], ENT_QUOTES, 'UTF-8') ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?= ucfirst(htmlspecialchars($scholarship['discount_type'], ENT_QUOTES, 'UTF-8')) ?>
                    </td>
                    <td class="fw-bold text-success">
                      <?php if ($scholarship['discount_type'] === 'percentage'): ?>
                        <?= number_format((float)$scholarship['discount_value'], 0) ?>%
                      <?php else: ?>
                        ₱<?= number_format((float)$scholarship['discount_value'], 2) ?>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($scholarship['is_active']): ?>
                        <span class="badge bg-success rounded-pill px-3">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary rounded-pill px-3">Disabled</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <!-- Edit Button -->
                      <button class="btn btn-sm btn-outline-secondary rounded-pill edit-scholarship-btn" 
                              data-id="<?= $scholarship['id'] ?>"
                              data-name="<?= htmlspecialchars($scholarship['name'], ENT_QUOTES, 'UTF-8') ?>"
                              data-type="<?= htmlspecialchars($scholarship['discount_type'], ENT_QUOTES, 'UTF-8') ?>"
                              data-value="<?= $scholarship['discount_value'] ?>"
                              data-desc="<?= htmlspecialchars($scholarship['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-req="<?= htmlspecialchars($scholarship['requirements'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-bs-toggle="modal" 
                              data-bs-target="#editScholarshipModal">
                        <i class="bi bi-pencil-square"></i> Edit
                      </button>

                      <!-- Toggle Status Form -->
                      <form action="scholarship_process.php" method="POST" class="d-inline">
                        <input type="hidden" name="action" value="toggle_scholarship">
                        <?= getCsrfInput() ?>
                        <input type="hidden" name="id" value="<?= $scholarship['id'] ?>">
                        <input type="hidden" name="status" value="<?= $scholarship['is_active'] ? '0' : '1' ?>">
                        <button type="submit" class="btn btn-sm <?= $scholarship['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?> rounded-pill" title="<?= $scholarship['is_active'] ? 'Disable' : 'Enable' ?>">
                          <i class="bi <?= $scholarship['is_active'] ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                        </button>
                      </form>
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

<!-- Add Scholarship Modal -->
<div class="modal fade" id="addScholarshipModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Add New Scholarship</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="scholarship_process.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="create_scholarship">
          <?= getCsrfInput() ?>
          
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Scholarship Name</label>
            <input type="text" name="name" class="form-control bg-light" required placeholder="e.g. Academic Excellence 50%">
          </div>
          
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-dark">Discount Type</label>
              <select name="discount_type" class="form-select bg-light" required>
                <option value="percentage">Percentage (%)</option>
                <option value="fixed">Fixed Amount (₱)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-dark">Discount Value</label>
              <input type="number" step="0.01" min="0" name="discount_value" class="form-control bg-light" required placeholder="e.g. 50">
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Description</label>
            <textarea name="description" class="form-control bg-light" rows="3" placeholder="Brief description of the scholarship."></textarea>
          </div>
          
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Requirements</label>
            <textarea name="requirements" class="form-control bg-light" rows="4" placeholder="List the requirements (e.g. 1. Income Tax Return)"></textarea>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Scholarship</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Scholarship Modal -->
<div class="modal fade" id="editScholarshipModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Edit Scholarship</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="scholarship_process.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="update_scholarship">
          <?= getCsrfInput() ?>
          <input type="hidden" name="id" id="editScholarshipId">
          
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Scholarship Name</label>
            <input type="text" name="name" id="editScholarshipName" class="form-control bg-light" required>
          </div>
          
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-dark">Discount Type</label>
              <select name="discount_type" id="editScholarshipType" class="form-select bg-light" required>
                <option value="percentage">Percentage (%)</option>
                <option value="fixed">Fixed Amount (₱)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-dark">Discount Value</label>
              <input type="number" step="0.01" min="0" name="discount_value" id="editScholarshipValue" class="form-control bg-light" required>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Description</label>
            <textarea name="description" id="editScholarshipDesc" class="form-control bg-light" rows="3"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Requirements</label>
            <textarea name="requirements" id="editScholarshipReq" class="form-control bg-light" rows="4"></textarea>
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
    $('.edit-scholarship-btn').on('click', function() {
      $('#editScholarshipId').val($(this).data('id'));
      $('#editScholarshipName').val($(this).data('name'));
      $('#editScholarshipType').val($(this).data('type'));
      $('#editScholarshipValue').val($(this).data('value'));
      $('#editScholarshipDesc').val($(this).data('desc'));
      $('#editScholarshipReq').val($(this).data('req'));
    });
  });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

