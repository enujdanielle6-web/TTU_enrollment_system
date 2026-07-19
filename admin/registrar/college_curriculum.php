<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('college_curriculum.manage');

$pageTitle = 'College Curricula - Admin Portal';

// Fetch programs for dropdown
$programs = [];
try {
    $stmt = $pdo->query('SELECT id, code, name FROM college_programs WHERE is_active = 1 ORDER BY code ASC');
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Fetch curricula
$curricula = [];
try {
    $stmt = $pdo->query("
        SELECT cc.id, cc.program_id, cc.curriculum_name, cc.version, cc.effective_academic_year, cc.status, cc.description, cc.created_at,
               p.code as program_code,
               (SELECT COUNT(id) FROM college_curriculum_subjects WHERE curriculum_id = cc.id) as subject_count
        FROM college_curricula cc
        INNER JOIN college_programs p ON cc.program_id = p.id
        ORDER BY p.code ASC, cc.curriculum_name ASC
    ");
    $curricula = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Failed to fetch college curricula: ' . $e->getMessage());
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

require_once __DIR__ . '/../../components/header.php';
?>
<?php require_once __DIR__ . '/../components/navbar.php'; ?>
<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    <div class="island island-hero mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">College Curricula Management</h1>
          <p class="text-muted mb-0">Manage academic curricula for college programs</p>
        </div>
        <div>
          <button class="btn btn-primary fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#createCurriculumModal">
            <i class="bi bi-plus-lg me-1"></i> Create Curriculum
          </button>
        </div>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4 mb-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light d-flex justify-content-between align-items-center">
        <div>
          <i class="bi bi-journal-bookmark-fill"></i>
          <h2 class="mb-0 d-inline-block">Curriculum Directory</h2>
        </div>
        <div>
          <div class="input-group shadow-sm" style="width: 250px;">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="tableSearch" class="form-control border-start-0" placeholder="Search curriculum...">
          </div>
        </div>
      </div>
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light text-muted small text-uppercase">
              <tr>
                <th class="ps-4">Program</th>
                <th>Curriculum Name</th>
                <th>Version</th>
                <th>Effective AY</th>
                <th>Status</th>
                <th>Subjects</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($curricula)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No curricula found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($curricula as $curr): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($curr['program_code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="fw-medium text-dark"><?= htmlspecialchars($curr['curriculum_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($curr['version'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($curr['effective_academic_year'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <?php if ($curr['status'] === 'active'): ?>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i>Active</span>
                      <?php elseif ($curr['status'] === 'draft'): ?>
                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-2"><i class="bi bi-pencil-fill me-1"></i>Draft</span>
                      <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td><span class="badge bg-secondary rounded-pill px-3"><?= $curr['subject_count'] ?></span></td>
                    <td class="text-end pe-4">
                      <a href="college_curriculum_builder.php?id=<?= $curr['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="bi bi-tools me-1"></i> Builder
                      </a>
                      <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 ms-1" 
                              onclick="openEditCurriculum(<?= $curr['id'] ?>, <?= $curr['program_id'] ?>, '<?= htmlspecialchars(addslashes($curr['curriculum_name']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($curr['version']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($curr['effective_academic_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($curr['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', '<?= $curr['status'] ?>')">
                        <i class="bi bi-pencil-fill me-1"></i> Edit
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 ms-1" 
                              data-bs-toggle="modal" 
                              data-bs-target="#deleteCurriculumModal"
                              onclick="setDeleteCurriculum(<?= $curr['id'] ?>, '<?= htmlspecialchars(addslashes($curr['curriculum_name']), ENT_QUOTES, 'UTF-8') ?>')"
                              title="Delete Curriculum">
                        <i class="bi bi-trash-fill"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              <tr id="noResultsRow" style="display: none;">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-search fs-1 d-block mb-3 text-secondary"></i>
                  No curricula match your search.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Create Curriculum Modal -->
<div class="modal fade" id="createCurriculumModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="college_curriculum_process.php" method="POST">
        <input type="hidden" name="action" value="create_curriculum">
        <div class="modal-header bg-light border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Create New Curriculum</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 pt-2">
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Program <span class="text-danger">*</span></label>
                <select class="form-select bg-light" name="program_id" required>
                <option value="" disabled selected>Select Program</option>
                <?php foreach ($programs as $prog): ?>
                    <option value="<?= $prog['id'] ?>"><?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Curriculum Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control bg-light" name="curriculum_name" placeholder="e.g. BSIT Curriculum 2025" required>
            </div>
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">Version <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" name="version" value="1.0" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">Effective AY</label>
                    <input type="text" class="form-control bg-light" name="effective_academic_year" placeholder="e.g. 2025-2026">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Status <span class="text-danger">*</span></label>
                <select class="form-select bg-light" name="status" required>
                    <option value="draft">Draft</option>
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Description</label>
                <textarea class="form-control bg-light" name="description" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 rounded-pill fw-medium shadow-sm">Create Curriculum</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Curriculum Modal -->
<div class="modal fade" id="editCurriculumModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="college_curriculum_process.php" method="POST">
        <input type="hidden" name="action" value="update_curriculum">
        <input type="hidden" name="curriculum_id" id="edit_curr_id" value="">
        <div class="modal-header bg-light border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-fill text-success me-2"></i>Edit Curriculum</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 pt-2">
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Program <span class="text-danger">*</span></label>
                <select class="form-select bg-light" name="program_id" id="edit_curr_program_id" required>
                <?php foreach ($programs as $prog): ?>
                    <option value="<?= $prog['id'] ?>"><?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Curriculum Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control bg-light" name="curriculum_name" id="edit_curr_name" required>
            </div>
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">Version <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" name="version" id="edit_curr_version" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">Effective AY</label>
                    <input type="text" class="form-control bg-light" name="effective_academic_year" id="edit_curr_ay">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Status <span class="text-danger">*</span></label>
                <select class="form-select bg-light" name="status" id="edit_curr_status" required>
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Description</label>
                <textarea class="form-control bg-light" name="description" id="edit_curr_description" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success px-4 rounded-pill fw-medium shadow-sm">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Curriculum Modal -->
<div class="modal fade" id="deleteCurriculumModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white border-bottom-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Curriculum</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <div class="mb-3">
          <i class="bi bi-trash text-danger" style="font-size: 3rem;"></i>
        </div>
        <h5 class="fw-bold text-dark mb-3">Are you sure?</h5>
        <p class="text-muted mb-0">This will permanently remove the curriculum <strong id="deleteCurrTitle" class="text-dark"></strong> and all its assigned subjects. This cannot be undone.</p>
      </div>
      <div class="modal-footer border-top-0 d-flex justify-content-center pb-4">
        <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
        <form action="college_curriculum_process.php" method="POST" class="d-inline">
            <input type="hidden" name="action" value="delete_curriculum">
            <input type="hidden" name="curriculum_id" id="deleteCurrId" value="">
            <button type="submit" class="btn btn-danger px-4 rounded-pill fw-medium shadow-sm">Yes, Delete Curriculum</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('tableSearch');
    const tableBody = document.querySelector('.custom-table tbody');
    const rows = tableBody.querySelectorAll('tr:not(#noResultsRow)');
    const noResultsRow = document.getElementById('noResultsRow');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleCount = 0;

            rows.forEach(row => {
                if (row.children.length === 1) return; // Skip empty message
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCount === 0 && rows.length > 0 && rows[0].children.length > 1) {
                noResultsRow.style.display = '';
            } else {
                noResultsRow.style.display = 'none';
            }
        });
    }
});

function openEditCurriculum(id, programId, name, version, ay, desc, status) {
    document.getElementById('edit_curr_id').value = id;
    document.getElementById('edit_curr_program_id').value = programId;
    document.getElementById('edit_curr_name').value = name;
    document.getElementById('edit_curr_version').value = version;
    document.getElementById('edit_curr_ay').value = ay;
    document.getElementById('edit_curr_description').value = desc;
    document.getElementById('edit_curr_status').value = status;
    new bootstrap.Modal(document.getElementById('editCurriculumModal')).show();
}

function setDeleteCurriculum(id, name) {
    document.getElementById('deleteCurrId').value = id;
    document.getElementById('deleteCurrTitle').textContent = name;
}
</script>
