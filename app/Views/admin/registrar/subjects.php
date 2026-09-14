<?php
require_once __DIR__ . '/../../components/header.php';
?>
<?php require_once __DIR__ . '/../../components/admin_navbar.php'; ?>
<main class="py-5 bg-light min-vh-100" id="mainContent">
  <div class="container-fluid px-lg-5">
    <!-- Dossier Hero Header Strip (Admissions Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-journal-text"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Subjects Management</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> University Registrar
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-collection text-primary me-1"></i> Global Catalog
              </span>
            </div>
            <p class="text-muted small mb-0">Master catalog of academic subjects across College and Senior High School.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
            <i class="bi bi-plus-lg"></i>
            <span>Add Subject</span>
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

    <!-- Master Subject Catalog Card (Dossier Card Styling) -->
    <div class="dossier-card fade-in-up" style="animation-delay: 0.15s;">
      <div class="dossier-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-collection-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Master Subject Catalog</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <?= count($subjects ?? []) ?> Subjects
            </span>
          </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <select id="levelFilter" class="form-select form-select-sm bg-light" style="width: auto;">
            <option value="">All Levels</option>
            <option value="College">College</option>
            <option value="SHS">SHS</option>
            <option value="Both">Both</option>
          </select>
          <select id="statusFilter" class="form-select form-select-sm bg-light" style="width: auto;">
            <option value="">All Statuses</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
          <div class="input-group input-group-sm" style="width: 220px;">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" id="tableSearch" class="form-control form-control-sm border-start-0 ps-0" placeholder="Search subjects...">
          </div>
        </div>
      </div>
      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table">
            <thead>
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
                  <td colspan="8" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">No Subjects Found</h3>
                      <p class="small text-muted mb-0">Use the "Add Subject" button above to add subjects to the master catalog.</p>
                    </div>
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
                      <span class="applicant-ref-badge">
                        <?= htmlspecialchars($subject['subject_code'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($isLocked): ?>
                          <i class="bi bi-shield-lock-fill text-muted ms-1" title="Structural attributes are locked due to existing academic usage"></i>
                        <?php endif; ?>
                      </span>
                    </td>
                    <td>
                      <div class="fw-bold text-dark"><?= htmlspecialchars($subject['subject_name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <?php if (!empty($subject['description'])): ?>
                        <small class="text-muted text-truncate d-block" style="max-width: 280px;"><?= htmlspecialchars($subject['description'], ENT_QUOTES, 'UTF-8') ?></small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-bold">
                        <?= esc((int)$subject['units']) ?> <?= ((int)$subject['units'] === 1) ? 'unit' : 'units' ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold"><?= htmlspecialchars($subject['subject_type'] ?: 'Lecture', ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td>
                      <?php if (($subject['education_level'] ?? 'College') === 'College'): ?>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">College</span>
                      <?php elseif (($subject['education_level'] ?? '') === 'SHS'): ?>
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">SHS</span>
                      <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">Both</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($isActive): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1">
                          <i class="bi bi-check-circle-fill"></i> Active
                        </span>
                      <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1">
                          <i class="bi bi-dash-circle"></i> Inactive
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($totalUsage > 0): ?>
                        <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small fw-normal"><?= htmlspecialchars($subject['usage_summary'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                      <?php else: ?>
                        <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 small fw-normal">Unused</span>
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
                          <button type="button" 
                                  class="btn btn-sm btn-outline-warning rounded-pill px-3 me-1" 
                                  title="Inactivate (retire from future curriculum pickers)"
                                  data-subject-id="<?= esc($subject['id']) ?>"
                                  data-subject-code="<?= htmlspecialchars($subject['subject_code'], ENT_QUOTES, 'UTF-8') ?>"
                                  data-subject-name="<?= htmlspecialchars($subject['subject_name'], ENT_QUOTES, 'UTF-8') ?>"
                                  data-subject-usage="<?= htmlspecialchars($subject['usage_summary'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                  onclick="confirmInactivate(this)">
                            <i class="bi bi-pause-circle me-1"></i> Inactivate
                          </button>
                        <?php else: ?>
                          <button type="button" 
                                  class="btn btn-sm btn-outline-success rounded-pill px-3 me-1" 
                                  title="Reactivate for curriculum pickers"
                                  data-subject-id="<?= esc($subject['id']) ?>"
                                  data-subject-code="<?= htmlspecialchars($subject['subject_code'], ENT_QUOTES, 'UTF-8') ?>"
                                  data-subject-name="<?= htmlspecialchars($subject['subject_name'], ENT_QUOTES, 'UTF-8') ?>"
                                  onclick="confirmActivate(this)">
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
                <td colspan="8" class="text-center py-5">
                  <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                      <i class="bi bi-search fs-1 text-muted"></i>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-1">No Matching Subjects</h3>
                    <p class="small text-muted mb-0">No subjects match your search or filter criteria.</p>
                  </div>
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

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function confirmInactivate(button) {
    const form = button.closest('form');
    if (!form) return;

    const code = button.dataset.subjectCode || 'Subject';
    const name = button.dataset.subjectName || '';
    const usage = (button.dataset.subjectUsage || '').trim();

    if (typeof Swal === 'undefined') {
        form.submit();
        return;
    }

    const hasUsage = usage !== '' && usage !== 'Unused';
    const usageBadge = hasUsage 
        ? `<div class="p-3 mt-2 rounded-3 bg-light border text-start small">
             <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Current Academic References</div>
             <div class="text-dark fw-bold mt-1"><i class="bi bi-diagram-3-fill text-primary me-1"></i>${escapeHtml(usage)}</div>
           </div>`
        : `<div class="p-2 px-3 mt-2 rounded-3 bg-light border text-start small text-muted">
             <i class="bi bi-info-circle me-1 text-secondary"></i> Currently unused in any curriculum or section.
           </div>`;

    Swal.fire({
        title: '<div class="fs-4 fw-bold text-dark pt-1">Inactivate Subject?</div>',
        html: `
            <div class="text-center mb-3">
                <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-50 rounded-pill px-3 py-1 small fw-semibold mb-2 d-inline-block">
                    <i class="bi bi-pause-circle-fill text-warning me-1"></i> Status Change: Inactive
                </span>
                <h4 class="fw-bold text-dark mb-1">${escapeHtml(code)}</h4>
                <div class="text-muted small">${escapeHtml(name)}</div>
            </div>

            ${usageBadge}

            <div class="alert alert-warning text-start small mt-3 mb-0 p-3 rounded-3 d-flex gap-2 align-items-start border-warning border-opacity-25" style="background-color: #fffbeb;">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0 mt-0"></i>
                <div class="text-secondary" style="font-size: 0.82rem; line-height: 1.45;">
                    Retiring this subject will remove it from future curriculum builders and schedule section pickers. <strong>All historical student enrollments, grades, and existing curricula will remain completely intact.</strong>
                </div>
            </div>
        `,
        icon: 'warning',
        iconColor: '#f59e0b',
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-pause-circle me-1"></i> Yes, Inactivate Subject',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusCancel: true,
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-4 shadow-lg border-0 p-4',
            title: 'p-0 m-0',
            htmlContainer: 'p-0 mt-3 text-start',
            actions: 'gap-2 mt-4',
            confirmButton: 'btn btn-warning rounded-pill px-4 py-2 fw-semibold text-dark shadow-sm',
            cancelButton: 'btn btn-light rounded-pill px-4 py-2 fw-medium border text-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Inactivating...';
            form.submit();
        }
    });
}

function confirmActivate(button) {
    const form = button.closest('form');
    if (!form) return;

    const code = button.dataset.subjectCode || 'Subject';
    const name = button.dataset.subjectName || '';

    if (typeof Swal === 'undefined') {
        form.submit();
        return;
    }

    Swal.fire({
        title: '<div class="fs-4 fw-bold text-dark pt-1">Reactivate Subject?</div>',
        html: `
            <div class="text-center mb-3">
                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-50 rounded-pill px-3 py-1 small fw-semibold mb-2 d-inline-block">
                    <i class="bi bi-play-circle-fill text-success me-1"></i> Status Change: Active
                </span>
                <h4 class="fw-bold text-dark mb-1">${escapeHtml(code)}</h4>
                <div class="text-muted small">${escapeHtml(name)}</div>
            </div>
            <p class="text-muted small text-center mb-0">
                This subject will be restored to active status and will become immediately selectable in curriculum builders and section subject pickers.
            </p>
        `,
        icon: 'question',
        iconColor: '#10b981',
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-play-circle me-1"></i> Yes, Activate',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-4 shadow-lg border-0 p-4',
            title: 'p-0 m-0',
            htmlContainer: 'p-0 mt-3 text-start',
            actions: 'gap-2 mt-4',
            confirmButton: 'btn btn-success rounded-pill px-4 py-2 fw-semibold shadow-sm',
            cancelButton: 'btn btn-light rounded-pill px-4 py-2 fw-medium border text-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Activating...';
            form.submit();
        }
    });
}

window.confirmInactivate = confirmInactivate;
window.confirmActivate = confirmActivate;

<?php if (!empty($successMsg)): ?>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: <?= json_encode($successMsg) ?>,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        });
    }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
