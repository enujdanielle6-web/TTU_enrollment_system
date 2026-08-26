<?php
require_once __DIR__ . '/../../components/header.php';
?>
<?php require_once __DIR__ . '/../../components/admin_navbar.php'; ?>
<main class="py-5 bg-light min-vh-100" id="mainContent">
  <div class="container-fluid px-lg-5">
    <div class="island island-hero mb-4 fade-in-up" style="animation-delay: 0.1s;">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">Subjects Management</h1>
          <p class="text-muted mb-0">Master catalog of academic subjects across College and Senior High School</p>
        </div>
        <div>
          <button type="button" class="btn btn-primary fw-medium shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
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

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4 fade-in-up" style="animation-delay: 0.2s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light d-flex justify-content-between align-items-center fade-in-up" style="animation-delay: 0.3s;">
        <div>
          <i class="bi bi-journal-text"></i>
          <h2 class="mb-0 d-inline-block">Master Subject Catalog</h2>
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
      <div class="island-body p-0 fade-in-up" style="animation-delay: 0.4s;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light text-muted small text-uppercase">
              <tr>
                <th class="ps-4">Code</th>
                <th>Subject Name</th>
                <th>Units</th>
                <th>Type</th>
                <th>Level</th>
                <th>Status</th>
                <th>Usage References</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($subjects)): ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No subjects found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($subjects as $subject): 
                  $isLocked = !empty($subject['is_locked']);
                  $totalUsage = (int)($subject['total_usage'] ?? 0);
                  $isActive = ((int)$subject['status'] === 1);
                ?>
                  <tr class="subject-row" data-level="<?= htmlspecialchars($subject['education_level'] ?? 'College', ENT_QUOTES, 'UTF-8') ?>" data-status="<?= esc($subject['status']) ?>">
                    <td class="ps-4 fw-bold text-dark">
                      <?= htmlspecialchars($subject['subject_code'], ENT_QUOTES, 'UTF-8') ?>
                      <?php if ($isLocked): ?>
                        <i class="bi bi-shield-lock-fill text-muted ms-1" title="Structural attributes are locked due to existing academic usage"></i>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="fw-medium text-dark"><?= htmlspecialchars($subject['subject_name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <?php if (!empty($subject['description'])): ?>
                        <small class="text-muted text-truncate d-block" style="max-width: 280px;"><?= htmlspecialchars($subject['description'], ENT_QUOTES, 'UTF-8') ?></small>
                      <?php endif; ?>
                    </td>
                    <td><span class="fw-semibold text-primary"><?= esc((int)$subject['units']) ?></span></td>
                    <td>
                      <span class="badge bg-light text-secondary border"><?= htmlspecialchars($subject['subject_type'] ?: 'Lecture', ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
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
                      <?php if ($isActive): ?>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 fw-medium"><i class="bi bi-check-circle me-1"></i>Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-1 fw-medium"><i class="bi bi-dash-circle me-1"></i>Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($totalUsage > 0): ?>
                        <span class="badge bg-light text-dark border fw-normal"><?= htmlspecialchars($subject['usage_summary'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                      <?php else: ?>
                        <span class="badge bg-light text-muted border fw-normal">Unused</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <!-- Edit Button -->
                      <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 me-1" 
                              onclick="openEditSubjectModal(<?= esc($subject['id']) ?>, '<?= htmlspecialchars(addslashes($subject['subject_code']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($subject['subject_name']), ENT_QUOTES, 'UTF-8') ?>', <?= (int)$subject['units'] ?>, '<?= htmlspecialchars(addslashes($subject['subject_type'] ?? 'Lecture'), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($subject['education_level'] ?? 'College'), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($subject['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', <?= (int)$subject['status'] ?>, <?= $isLocked ? 'true' : 'false' ?>, <?= $totalUsage ?>)"
                              title="Edit Subject">
                        <i class="bi bi-pencil-fill me-1"></i> Edit
                      </button>

                      <!-- Toggle Status (Activate / Inactivate) -->
                      <form action="subject_process.php" method="POST" class="d-inline m-0 p-0">
                        <?= getCsrfInput() ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="subject_id" value="<?= esc($subject['id']) ?>">
                        <?php if ($isActive): ?>
                          <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-3 me-1" title="Inactivate (retire from future curriculum pickers)" onclick="return confirm('Retire <?= htmlspecialchars(addslashes($subject['subject_code'])) ?> from future curriculum pickers? Historical records will remain intact.');">
                            <i class="bi bi-pause-circle me-1"></i> Inactivate
                          </button>
                        <?php else: ?>
                          <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3 me-1" title="Reactivate for curriculum pickers">
                            <i class="bi bi-play-circle me-1"></i> Activate
                          </button>
                        <?php endif; ?>
                      </form>

                      <!-- Delete Button (Only enabled for Unused subjects) -->
                      <?php if ($totalUsage === 0): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteSubjectModal"
                                onclick="setDeleteSubject(<?= esc($subject['id']) ?>, '<?= htmlspecialchars($subject['subject_code'], ENT_QUOTES, 'UTF-8') ?>')"
                                title="Delete Unused Subject">
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              <tr id="noResultsRow" style="display: none;">
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="bi bi-search fs-1 d-block mb-3 text-secondary"></i>
                  No subjects match your search or filter criteria.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="subject_process.php" method="POST">
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" value="add">
        <div class="modal-header bg-primary text-white border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Add New Subject</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row mb-3">
            <div class="col-md-5">
              <label class="form-label small fw-semibold text-dark">Subject Code <span class="text-danger">*</span></label>
              <input type="text" class="form-control bg-light text-uppercase" name="subject_code" placeholder="e.g. CS101" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-dark">Units <span class="text-danger">*</span></label>
              <input type="number" class="form-control bg-light" name="units" value="3" min="0" max="12" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold text-dark">Type <span class="text-danger">*</span></label>
              <select class="form-select bg-light" name="subject_type">
                <option value="Lecture" selected>Lecture</option>
                <option value="Laboratory">Laboratory</option>
                <option value="PE">P.E.</option>
                <option value="NSTP">NSTP</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Subject Name / Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control bg-light" name="subject_name" placeholder="e.g. Introduction to Programming" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Education Level <span class="text-danger">*</span></label>
            <select class="form-select bg-light" name="education_level">
              <option value="College" selected>College</option>
              <option value="SHS">SHS (Senior High School)</option>
              <option value="Both">Both (College & SHS)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Description</label>
            <textarea class="form-control bg-light" name="description" rows="2" placeholder="Course syllabus overview..."></textarea>
          </div>
          <div class="alert alert-info py-2 px-3 small mb-0">
            <i class="bi bi-info-circle me-1"></i> New subjects start in <strong>Active</strong> status and become immediately selectable for curriculum builders.
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light rounded-pill px-4 fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 fw-medium shadow-sm">Add Subject</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Subject Modal (Unified & Guarded) -->
<div class="modal fade" id="editSubjectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="subject_process.php" method="POST" id="editSubjectForm">
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="subject_id" id="edit_subject_id" value="">
        
        <div class="modal-header bg-light border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-fill text-primary me-2"></i>Edit Subject</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 pt-2">
          <!-- Immutability Alert Banner -->
          <div id="editLockNotice" class="alert alert-warning py-2 px-3 small mb-3" style="display: none;">
            <i class="bi bi-shield-lock-fill me-1"></i> <strong>Structural fields are locked:</strong> This subject is referenced by active academic records. To change units, code, or type, create a new subject record.
          </div>

          <div class="row mb-3">
            <div class="col-md-5">
              <label class="form-label small fw-semibold text-dark">Subject Code <span class="text-danger">*</span></label>
              <input type="text" class="form-control text-uppercase" name="subject_code" id="edit_subject_code" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-dark">Units <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="units" id="edit_units" min="0" max="12" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold text-dark">Type <span class="text-danger">*</span></label>
              <select class="form-select" name="subject_type" id="edit_subject_type">
                <option value="Lecture">Lecture</option>
                <option value="Laboratory">Laboratory</option>
                <option value="PE">P.E.</option>
                <option value="NSTP">NSTP</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Subject Name / Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="subject_name" id="edit_subject_name" required>
            <small class="text-muted">Typo, spelling, or title capitalization adjustments remain permitted.</small>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-dark">Education Level</label>
              <select class="form-select" name="education_level" id="edit_education_level">
                <option value="College">College</option>
                <option value="SHS">SHS</option>
                <option value="Both">Both (College & SHS)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-dark">Catalog Status</label>
              <select class="form-select" name="status" id="edit_status">
                <option value="1">Active</option>
                <option value="0">Inactive (Retired)</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Description</label>
            <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light rounded-pill px-4 fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 fw-medium shadow-sm">Save Changes</button>
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
        <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Unused Subject</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-4 text-center">
        <i class="bi bi-trash text-danger display-1 mb-3 d-block"></i>
        <h5 class="fw-bold mb-2">Are you sure?</h5>
        <p class="text-muted mb-0">This will permanently delete the unused subject <strong id="deleteSubjectCode" class="text-dark"></strong> from the catalog. This action cannot be undone.</p>
      </div>
      <div class="modal-footer bg-light border-top-0 pt-3 justify-content-center">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <form action="subject_process.php" method="POST" class="d-inline">
          <?= getCsrfInput() ?>
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
        const searchText = (searchInput ? searchInput.value.toLowerCase().trim() : '');
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

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (levelFilter) levelFilter.addEventListener('change', filterTable);
    if (statusFilter) statusFilter.addEventListener('change', filterTable);
});

function openEditSubjectModal(id, code, name, units, type, level, desc, status, isLocked, totalUsage) {
    document.getElementById('edit_subject_id').value = id;
    document.getElementById('edit_subject_code').value = code;
    document.getElementById('edit_subject_name').value = name;
    document.getElementById('edit_units').value = units;
    document.getElementById('edit_subject_type').value = type;
    document.getElementById('edit_education_level').value = level;
    document.getElementById('edit_description').value = desc;
    document.getElementById('edit_status').value = status;

    const lockNotice = document.getElementById('editLockNotice');
    const codeInput = document.getElementById('edit_subject_code');
    const unitsInput = document.getElementById('edit_units');
    const typeSelect = document.getElementById('edit_subject_type');
    const levelSelect = document.getElementById('edit_education_level');

    if (isLocked) {
        lockNotice.style.display = 'block';
        codeInput.readOnly = true;
        codeInput.classList.add('bg-light');
        unitsInput.readOnly = true;
        unitsInput.classList.add('bg-light');
        typeSelect.disabled = true;
        levelSelect.disabled = true;
    } else {
        lockNotice.style.display = 'none';
        codeInput.readOnly = false;
        codeInput.classList.remove('bg-light');
        unitsInput.readOnly = false;
        unitsInput.classList.remove('bg-light');
        typeSelect.disabled = false;
        levelSelect.disabled = false;
    }

    new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
}

function setDeleteSubject(id, code) {
    document.getElementById('deleteSubjectId').value = id;
    document.getElementById('deleteSubjectCode').textContent = code;
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
