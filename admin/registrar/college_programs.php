<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('programs.manage');

$pageTitle = 'College Programs - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';

// Fetch programs
$programs = [];
try {
    $stmt = $pdo->query('SELECT * FROM college_programs ORDER BY created_at ASC');
    $programs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('College programs fetch failed: ' . $e->getMessage());
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">College Programs</h1>
        <p class="text-muted mb-0">Manage the college degree programs available for applicant enrollment.</p>
      </div>
      <div>
        <button type="button" class="btn btn-primary fw-medium shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addProgramModal">
          <i class="bi bi-plus-circle-fill me-1"></i> Add Program
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
          <i class="bi bi-mortarboard-fill text-primary"></i>
          <h2 class="mb-0 text-dark d-inline-block">Degree Offerings</h2>
        </div>
        <div>
          <div class="input-group shadow-sm" style="width: 250px;">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="tableSearch" class="form-control border-start-0" placeholder="Search programs...">
          </div>
        </div>
      </div>
      
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Program Code</th>
                <th scope="col">Full Name / Description</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($programs)): ?>
                <tr>
                  <td colspan="4" class="text-center py-5 text-muted">
                    <i class="bi bi-x-circle fs-1 d-block mb-3 text-secondary"></i>
                    No college programs defined.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($programs as $prog): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark">
                      <?= htmlspecialchars(strtoupper($prog['code']), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                      <?= htmlspecialchars($prog['name'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                      <?php if ($prog['is_active']): ?>
                        <span class="badge bg-success rounded-pill px-3">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary rounded-pill px-3">Disabled</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <!-- Edit Button -->
                      <button class="btn btn-sm btn-outline-secondary rounded-pill edit-program-btn" 
                              data-id="<?= $prog['id'] ?>"
                              data-code="<?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?>"
                              data-name="<?= htmlspecialchars($prog['name'], ENT_QUOTES, 'UTF-8') ?>"
                              data-bs-toggle="modal" 
                              data-bs-target="#editProgramModal">
                        <i class="bi bi-pencil-square"></i> Edit
                      </button>

                      <!-- Toggle Status Form -->
                      <form action="college_program_process.php" method="POST" class="d-inline">
                        <input type="hidden" name="action" value="toggle_program">
                        <?= getCsrfInput() ?>
                        <input type="hidden" name="id" value="<?= $prog['id'] ?>">
                        <input type="hidden" name="status" value="<?= $prog['is_active'] ? '0' : '1' ?>">
                        <button type="submit" class="btn btn-sm <?= $prog['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?> rounded-pill" title="<?= $prog['is_active'] ? 'Disable' : 'Enable' ?>">
                          <i class="bi <?= $prog['is_active'] ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              <tr id="noResultsRow" style="display: none;">
                <td colspan="4" class="text-center py-5 text-muted">
                  <i class="bi bi-search fs-1 d-block mb-3 text-secondary"></i>
                  No programs match your search.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Add Program Modal -->
<div class="modal fade" id="addProgramModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Add New Program</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="college_program_process.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="create_program">
          <?= getCsrfInput() ?>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Program Code (e.g. BSIT, BSCS)</label>
            <input type="text" name="code" class="form-control bg-light" required pattern="[A-Za-z0-9\-]+" title="Alphanumeric and dashes only.">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Full Program Name</label>
            <input type="text" name="name" class="form-control bg-light" required placeholder="e.g. Bachelor of Science in Information Technology">
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Program</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Program Modal -->
<div class="modal fade" id="editProgramModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Edit Program</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="college_program_process.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="update_program">
          <?= getCsrfInput() ?>
          <input type="hidden" name="id" id="editProgramId">
          
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Program Code</label>
            <input type="text" name="code" id="editProgramCode" class="form-control bg-light" required pattern="[A-Za-z0-9\-]+">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Full Program Name</label>
            <input type="text" name="name" id="editProgramName" class="form-control bg-light" required>
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
    $('.edit-program-btn').on('click', function() {
      $('#editProgramId').val($(this).data('id'));
      $('#editProgramCode').val($(this).data('code'));
      $('#editProgramName').val($(this).data('name'));
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
