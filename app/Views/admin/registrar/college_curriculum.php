<?php
require_once __DIR__ . '/../../components/header.php';
?>
<?php require_once __DIR__ . '/../../components/admin_navbar.php'; ?>
<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    <div class="island island-hero mb-4 fade-in-up" style="animation-delay: 0.1s;">
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

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4 mb-4 fade-in-up" style="animation-delay: 0.2s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light d-flex justify-content-between align-items-center fade-in-up" style="animation-delay: 0.3s;">
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
      <div class="island-body p-0 fade-in-up" style="animation-delay: 0.4s;">
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
                <th>Total Units</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($curricula)): ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No curricula found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($curricula as $curr): 
                  $totalUsage = (int)($curr['student_count'] ?? 0) + (int)($curr['section_count'] ?? 0) + (int)($curr['application_count'] ?? 0);
                ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($curr['program_code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <div class="fw-medium text-dark"><?= htmlspecialchars($curr['curriculum_name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <?php if ($totalUsage > 0): ?>
                        <small class="text-muted"><i class="bi bi-people me-1"></i><?= (int)$curr['student_count'] ?> students, <?= (int)$curr['section_count'] ?> sections</small>
                      <?php else: ?>
                        <small class="text-muted">Unused</small>
                      <?php endif; ?>
                    </td>
                    <td><span class="badge bg-light text-dark border">v<?= htmlspecialchars($curr['version'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($curr['effective_academic_year'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <?php if ($curr['status'] === 'active'): ?>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2" title="This curriculum is active and its academic structure is locked. Create a new version to make changes.">
                          <i class="bi bi-shield-check me-1"></i>Active
                        </span>
                      <?php elseif ($curr['status'] === 'draft'): ?>
                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-2">
                          <i class="bi bi-pencil-fill me-1"></i>Draft
                        </span>
                      <?php elseif ($curr['status'] === 'archived'): ?>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2">
                          <i class="bi bi-archive-fill me-1"></i>Archived
                        </span>
                      <?php else: ?>
                        <span class="badge bg-dark bg-opacity-10 text-muted rounded-pill px-3 py-2">
                          <i class="bi bi-x-circle me-1"></i><?= htmlspecialchars(ucfirst($curr['status']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                      <?php endif; ?>
                    </td>
                    <td><span class="badge bg-secondary rounded-pill px-3"><?= esc($curr['subject_count']) ?></span></td>
                    <td><span class="fw-semibold text-primary"><?= esc($curr['total_units'] ?? 0) ?> units</span></td>
                    <td class="text-end pe-4">
                      <?php if ($curr['status'] === 'draft'): ?>
                        <!-- DRAFT ACTIONS: Edit, Manage Subjects, Activate, Delete -->
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 me-1" 
                                onclick="openEditCurriculum(<?= esc($curr['id']) ?>, <?= esc($curr['program_id']) ?>, '<?= htmlspecialchars(addslashes($curr['curriculum_name']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($curr['version']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($curr['effective_academic_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($curr['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')"
                                title="Edit Draft Details">
                          <i class="bi bi-pencil-fill me-1"></i> Edit
                        </button>
                        <a href="college_curriculum_builder.php?id=<?= esc($curr['id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3 me-1" title="Manage Subjects in Builder">
                          <i class="bi bi-tools me-1"></i> Manage Subjects
                        </a>
                        <button type="button" class="btn btn-sm btn-success rounded-pill px-3 me-1" 
                                onclick="openActivateModal(<?= esc($curr['id']) ?>, '<?= htmlspecialchars(addslashes($curr['curriculum_name']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($curr['version']), ENT_QUOTES, 'UTF-8') ?>')" title="Activate curriculum for official enrollment">
                          <i class="bi bi-check-circle me-1"></i> Activate
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteCurriculumModal"
                                onclick="setDeleteCurriculum(<?= esc($curr['id']) ?>, '<?= htmlspecialchars(addslashes($curr['curriculum_name']), ENT_QUOTES, 'UTF-8') ?>')"
                                title="Delete Unused Draft">
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      <?php elseif ($curr['status'] === 'active'): ?>
                        <!-- ACTIVE ACTIONS: View, Create New Version, Archive -->
                        <a href="college_curriculum_builder.php?id=<?= esc($curr['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" title="View curriculum catalog (Read-only)">
                          <i class="bi bi-eye me-1"></i> View
                        </a>
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 me-1" 
                                onclick="openCloneModal(<?= esc($curr['id']) ?>, '<?= htmlspecialchars(addslashes($curr['curriculum_name']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($curr['version']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($curr['effective_academic_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')" title="Create a new draft version by cloning this active curriculum">
                          <i class="bi bi-files me-1"></i> Create New Version
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" 
                                onclick="openArchiveModal(<?= esc($curr['id']) ?>, '<?= htmlspecialchars(addslashes($curr['curriculum_name']), ENT_QUOTES, 'UTF-8') ?>')" title="Retire and archive curriculum">
                          <i class="bi bi-archive me-1"></i> Archive
                        </button>
                      <?php else: /* ARCHIVED / INACTIVE */ ?>
                        <!-- ARCHIVED ACTIONS: View only -->
                        <a href="college_curriculum_builder.php?id=<?= esc($curr['id']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="View historical curriculum (Read-only)">
                          <i class="bi bi-eye me-1"></i> View
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              <tr id="noResultsRow" style="display: none;">
                <td colspan="8" class="text-center py-5 text-muted">
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
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" value="create_curriculum">
        <div class="modal-header bg-light border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Create New Draft Curriculum</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 pt-2">
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Program <span class="text-danger">*</span></label>
                <select class="form-select bg-light" name="program_id" required>
                <option value="" disabled selected>Select Program</option>
                <?php foreach ($programs as $prog): ?>
                    <option value="<?= esc($prog['id']) ?>"><?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($prog['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Curriculum Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control bg-light" name="curriculum_name" placeholder="e.g. BSIT 2026 Standard Curriculum" required>
            </div>
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">Version Tag <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" name="version" value="1.0" placeholder="1.0" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">Effective AY <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" name="effective_academic_year" placeholder="2026-2027" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Description</label>
                <textarea class="form-control bg-light" name="description" rows="2" placeholder="Curriculum overview and objectives..."></textarea>
            </div>
            <div class="alert alert-info py-2 px-3 small mb-0">
              <i class="bi bi-info-circle me-1"></i> New curricula start in <strong>Draft</strong> status. You can customize the subject tree before activating it for enrollment.
            </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 rounded-pill fw-medium shadow-sm">Create Draft</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Clone Curriculum Modal -->
<div class="modal fade" id="cloneCurriculumModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="college_curriculum_process.php" method="POST">
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" value="clone_curriculum">
        <input type="hidden" name="source_curriculum_id" id="clone_source_id" value="">
        <div class="modal-header bg-primary text-white border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold"><i class="bi bi-files me-2"></i>Clone to New Version</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 pt-3">
            <div class="alert alert-light border py-2 px-3 small mb-3">
              Cloning source: <strong id="clone_source_label" class="text-primary"></strong>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">New Curriculum Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control bg-light" name="curriculum_name" id="clone_curr_name" required>
            </div>
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">New Version Tag <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" name="version" id="clone_curr_version" placeholder="e.g. 2.0" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">Effective Academic Year <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" name="effective_academic_year" id="clone_curr_ay" placeholder="e.g. 2027-2028" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Description</label>
                <textarea class="form-control bg-light" name="description" id="clone_curr_desc" rows="2" placeholder="Notes regarding this revision..."></textarea>
            </div>
            <p class="small text-muted mb-0">
              <i class="bi bi-check2-all text-success me-1"></i> All subjects from the source curriculum will be duplicated into the new <strong>Draft</strong> version.
            </p>
        </div>
        <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 rounded-pill fw-medium shadow-sm">Clone into Draft</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Activate Curriculum Modal -->
<div class="modal fade" id="activateCurriculumModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="college_curriculum_process.php" method="POST">
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" value="activate_curriculum">
        <input type="hidden" name="curriculum_id" id="activate_curr_id" value="">
        <div class="modal-header bg-success text-white border-bottom-0">
          <h5 class="modal-title fw-bold"><i class="bi bi-check-circle-fill me-2"></i>Activate Curriculum</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 text-center">
            <i class="bi bi-shield-lock-fill text-success fs-1 d-block mb-3"></i>
            <h5 class="fw-bold text-dark mb-2">Activate <span id="activate_curr_title"></span>?</h5>
            <p class="text-muted small">
              Activating this curriculum will <strong>lock its subject structure</strong> so that students and section timetables can safely rely on it.
            </p>
            <div class="form-check text-start d-inline-block bg-light p-3 rounded-12 border mt-2">
              <input class="form-check-input" type="checkbox" name="archive_previous" value="1" id="archivePreviousCheck" checked>
              <label class="form-check-label small fw-semibold text-dark" for="archivePreviousCheck">
                Archive previous active versions of this program
              </label>
            </div>
        </div>
        <div class="modal-footer border-top-0 d-flex justify-content-center pb-4">
          <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success px-4 rounded-pill fw-medium shadow-sm">Yes, Activate Curriculum</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Archive Curriculum Modal -->
<div class="modal fade" id="archiveCurriculumModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="college_curriculum_process.php" method="POST">
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" value="archive_curriculum">
        <input type="hidden" name="curriculum_id" id="archive_curr_id" value="">
        <div class="modal-header bg-secondary text-white border-bottom-0">
          <h5 class="modal-title fw-bold"><i class="bi bi-archive-fill me-2"></i>Archive Curriculum</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 text-center">
            <i class="bi bi-archive text-secondary fs-1 d-block mb-3"></i>
            <h5 class="fw-bold text-dark mb-2">Archive <span id="archive_curr_title"></span>?</h5>
            <p class="text-muted small mb-0">
              Archived curricula are retired from new freshman admissions, but will be preserved permanently for existing students and transcripts.
            </p>
        </div>
        <div class="modal-footer border-top-0 d-flex justify-content-center pb-4">
          <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-secondary px-4 rounded-pill fw-medium shadow-sm">Archive Curriculum</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Draft Curriculum Modal -->
<div class="modal fade" id="editCurriculumModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="college_curriculum_process.php" method="POST">
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" value="update_curriculum">
        <input type="hidden" name="curriculum_id" id="edit_curr_id" value="">
        <div class="modal-header bg-light border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-fill text-primary me-2"></i>Edit Draft Curriculum</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 pt-2">
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Program <span class="text-danger">*</span></label>
                <select class="form-select bg-light" name="program_id" id="edit_curr_program_id" required>
                <?php foreach ($programs as $prog): ?>
                    <option value="<?= esc($prog['id']) ?>"><?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($prog['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Curriculum Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control bg-light" name="curriculum_name" id="edit_curr_name" required>
            </div>
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">Version Tag <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" name="version" id="edit_curr_version" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">Effective AY <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" name="effective_academic_year" id="edit_curr_ay" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Description</label>
                <textarea class="form-control bg-light" name="description" id="edit_curr_description" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 rounded-pill fw-medium shadow-sm">Save Changes</button>
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
        <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Draft Curriculum</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <div class="mb-3">
          <i class="bi bi-trash text-danger" style="font-size: 3rem;"></i>
        </div>
        <h5 class="fw-bold text-dark mb-3">Are you sure?</h5>
        <p class="text-muted mb-0">This will permanently remove the unused draft curriculum <strong id="deleteCurrTitle" class="text-dark"></strong> and its draft subject mappings. This cannot be undone.</p>
      </div>
      <div class="modal-footer border-top-0 d-flex justify-content-center pb-4">
        <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
        <form action="college_curriculum_process.php" method="POST" class="d-inline">
            <?= getCsrfInput() ?>
            <input type="hidden" name="action" value="delete_curriculum">
            <input type="hidden" name="curriculum_id" id="deleteCurrId" value="">
            <button type="submit" class="btn btn-danger px-4 rounded-pill fw-medium shadow-sm">Yes, Delete Draft</button>
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
                if (row.children.length === 1) return;
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

function openEditCurriculum(id, programId, name, version, ay, desc) {
    document.getElementById('edit_curr_id').value = id;
    document.getElementById('edit_curr_program_id').value = programId;
    document.getElementById('edit_curr_name').value = name;
    document.getElementById('edit_curr_version').value = version;
    document.getElementById('edit_curr_ay').value = ay;
    document.getElementById('edit_curr_description').value = desc;
    new bootstrap.Modal(document.getElementById('editCurriculumModal')).show();
}

function openCloneModal(id, name, version, ay) {
    document.getElementById('clone_source_id').value = id;
    document.getElementById('clone_source_label').textContent = name + ' (v' + version + ')';
    
    // Propose incremented version
    let nextVersion = '2.0';
    let floatVer = parseFloat(version);
    if (!isNaN(floatVer)) {
        nextVersion = (floatVer + 1.0).toFixed(1);
    }
    
    document.getElementById('clone_curr_name').value = name.replace(/\bv\d+(\.\d+)?\b/gi, '').trim() + ' (v' + nextVersion + ')';
    document.getElementById('clone_curr_version').value = nextVersion;
    document.getElementById('clone_curr_ay').value = ay;
    document.getElementById('clone_curr_desc').value = 'Revision based on ' + name + ' v' + version;
    new bootstrap.Modal(document.getElementById('cloneCurriculumModal')).show();
}

function openActivateModal(id, name, version) {
    document.getElementById('activate_curr_id').value = id;
    document.getElementById('activate_curr_title').textContent = name + ' (v' + version + ')';
    new bootstrap.Modal(document.getElementById('activateCurriculumModal')).show();
}

function openArchiveModal(id, name) {
    document.getElementById('archive_curr_id').value = id;
    document.getElementById('archive_curr_title').textContent = name;
    new bootstrap.Modal(document.getElementById('archiveCurriculumModal')).show();
}

function setDeleteCurriculum(id, name) {
    document.getElementById('deleteCurrId').value = id;
    document.getElementById('deleteCurrTitle').textContent = name;
}
</script>

