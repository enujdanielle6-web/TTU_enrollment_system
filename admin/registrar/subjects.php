<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('subjects.manage');

$pageTitle = 'Subjects - Admin Portal';

// Fetch all subjects
$subjects = [];
try {
    $stmt = $pdo->query('SELECT * FROM subjects ORDER BY subject_code ASC');
    $subjects = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Failed to fetch subjects: ' . $e->getMessage());
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

require_once __DIR__ . '/../../components/header.php';
?>
<?php require_once __DIR__ . '/../components/navbar.php'; ?>
<main class="py-5 bg-light min-vh-100 w-100" id="mainContent">
  <div class="container-fluid px-lg-5">
    <div class="island island-hero mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">Subjects Management</h1>
          <p class="text-muted mb-0">Add and manage academic subjects</p>
        </div>
        <div>
          <button class="btn btn-primary fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
            <i class="bi bi-plus-lg me-1"></i> Add Subject
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

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light d-flex justify-content-between align-items-center">
        <div>
          <i class="bi bi-journal-text"></i>
          <h2 class="mb-0 d-inline-block">All Subjects</h2>
        </div>
        <div class="d-flex gap-2">
          <select id="levelFilter" class="form-select shadow-sm" style="width: auto;">
            <option value="">All Levels</option>
            <option value="College">College</option>
            <option value="SHS">SHS</option>
            <option value="Both">Both</option>
          </select>
          <select id="statusFilter" class="form-select shadow-sm" style="width: auto;">
            <option value="">All Statuses</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
          <div class="input-group shadow-sm" style="width: 250px;">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="tableSearch" class="form-control border-start-0" placeholder="Search subjects...">
          </div>
        </div>
      </div>
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light text-muted small text-uppercase">
              <tr>
                <th class="ps-4">Subject Code</th>
                <th>Subject Name</th>
                <th>Units</th>
                <th>Level</th>
                <th>Status</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($subjects)): ?>
                <tr>
                  <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No subjects found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($subjects as $subject): ?>
                  <tr class="subject-row" data-level="<?= htmlspecialchars($subject['education_level'] ?? 'College', ENT_QUOTES, 'UTF-8') ?>" data-status="<?= $subject['status'] ?>">
                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($subject['subject_code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($subject['subject_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)$subject['units'] ?></td>
                    <td>
                      <?php if (($subject['education_level'] ?? 'College') === 'College'): ?>
                        <span class="badge bg-light text-primary border border-primary border-opacity-25 rounded-pill px-3">College</span>
                      <?php elseif (($subject['education_level'] ?? '') === 'SHS'): ?>
                        <span class="badge bg-light text-secondary border border-secondary border-opacity-25 rounded-pill px-3">SHS</span>
                      <?php else: ?>
                        <span class="badge bg-light text-dark border border-dark border-opacity-25 rounded-pill px-3">Both</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($subject['status']): ?>
                        <span class="badge bg-success rounded-pill px-3">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary rounded-pill px-3">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSubjectModal<?= $subject['id'] ?>">
                        <i class="bi bi-pencil"></i> Edit
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-danger ms-1" 
                              data-bs-toggle="modal" 
                              data-bs-target="#deleteSubjectModal"
                              onclick="setDeleteSubject(<?= $subject['id'] ?>, '<?= htmlspecialchars($subject['subject_code'], ENT_QUOTES, 'UTF-8') ?>')">
                        <i class="bi bi-trash-fill"></i> Delete
                      </button>
                    </td>
                  </tr>

                  <!-- Edit Subject Modal -->
                  <div class="modal fade" id="editSubjectModal<?= $subject['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <form action="subject_process.php" method="POST">
                          <input type="hidden" name="action" value="edit">
                          <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                          <div class="modal-header">
                            <h5 class="modal-title">Edit Subject</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
                            <div class="mb-3">
                              <label class="form-label">Subject Code <span class="text-danger">*</span></label>
                              <input type="text" class="form-control" name="subject_code" value="<?= htmlspecialchars($subject['subject_code'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                              <input type="text" class="form-control" name="subject_name" value="<?= htmlspecialchars($subject['subject_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Units <span class="text-danger">*</span></label>
                              <input type="number" class="form-control" name="units" value="<?= (int)$subject['units'] ?>" min="1" required>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Description</label>
                              <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($subject['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Education Level</label>
                              <select class="form-select" name="education_level">
                                <option value="College" <?= ($subject['education_level'] ?? 'College') === 'College' ? 'selected' : '' ?>>College</option>
                                <option value="SHS" <?= ($subject['education_level'] ?? '') === 'SHS' ? 'selected' : '' ?>>SHS</option>
                                <option value="Both" <?= ($subject['education_level'] ?? '') === 'Both' ? 'selected' : '' ?>>Both (College & SHS)</option>
                              </select>
                            </div>
                            <div class="mb-3">
                              <label class="form-label">Status</label>
                              <select class="form-select" name="status">
                                <option value="1" <?= $subject['status'] ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= !$subject['status'] ? 'selected' : '' ?>>Inactive</option>
                              </select>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
              <tr id="noResultsRow" style="display: none;">
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-search fs-1 d-block mb-3 text-secondary"></i>
                  No subjects match your search.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="subject_process.php" method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h5 class="modal-title">Add New Subject</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Subject Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="subject_code" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Subject Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="subject_name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Units <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="units" value="3" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Education Level</label>
            <select class="form-select" name="education_level">
              <option value="College" selected>College</option>
              <option value="SHS">SHS</option>
              <option value="Both">Both (College & SHS)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Subject</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Subject Modal -->
<div class="modal fade" id="deleteSubjectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white border-bottom-0 pb-3">
        <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Subject</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-4 text-center">
        <i class="bi bi-trash text-danger display-1 mb-3 d-block"></i>
        <h5 class="fw-bold mb-2">Are you absolutely sure?</h5>
        <p class="text-muted mb-0">This will permanently delete the subject <strong id="deleteSubjectCode" class="text-dark"></strong>. This cannot be undone.</p>
      </div>
      <div class="modal-footer bg-light border-top-0 pt-3 justify-content-center">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <form action="subject_process.php" method="POST" class="d-inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="subject_id" id="deleteSubjectId">
          <button type="submit" class="btn btn-danger rounded-pill px-4 shadow-sm fw-medium">Yes, Delete Subject</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('tableSearch');
    const levelFilter = document.getElementById('levelFilter');
    const statusFilter = document.getElementById('statusFilter');

    function filterTable() {
        const searchText = (searchInput ? searchInput.value.toLowerCase() : '');
        const levelValue = levelFilter ? levelFilter.value : '';
        const statusValue = statusFilter ? statusFilter.value : '';

        const rows = document.querySelectorAll('.table tbody tr.subject-row');
        const noResultsRow = document.getElementById('noResultsRow');
        let visibleCount = 0;

        rows.forEach(row => {
            const textContent = row.textContent.toLowerCase();
            const rowLevel = row.getAttribute('data-level');
            const rowStatus = row.getAttribute('data-status');

            const matchesSearch = textContent.includes(searchText);
            const matchesLevel = levelValue === '' || rowLevel === levelValue;
            const matchesStatus = statusValue === '' || rowStatus === statusValue;

            if (matchesSearch && matchesLevel && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (noResultsRow) {
            noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    if (searchInput) searchInput.addEventListener('keyup', filterTable);
    if (levelFilter) levelFilter.addEventListener('change', filterTable);
    if (statusFilter) statusFilter.addEventListener('change', filterTable);
});

function setDeleteSubject(id, code) {
    document.getElementById('deleteSubjectId').value = id;
    document.getElementById('deleteSubjectCode').textContent = code;
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

