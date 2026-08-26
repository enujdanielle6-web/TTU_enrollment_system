<?php
require_once __DIR__ . '/../../components/header.php';
?>
<?php require_once __DIR__ . '/../../components/admin_navbar.php'; ?>
<style>
.subject-row { transition: background-color 0.2s; }
.subject-row:hover { background-color: #f8f9fa; }
</style>
<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Top Header -->
    <div class="island island-hero mb-4 fade-in-up" style="animation-delay: 0.1s;">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <a href="shs_curriculum.php" class="btn btn-sm btn-light text-primary mb-2 fw-medium rounded-pill px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Curricula
          </a>
          <h1 class="h3 fw-bold text-dark mb-1"><?= htmlspecialchars($curriculum['curriculum_name']) ?></h1>
          <p class="text-muted mb-0">
            <?= htmlspecialchars($curriculum['strand_code']) ?> (<?= htmlspecialchars($curriculum['strand_name']) ?>) | Version <?= htmlspecialchars($curriculum['version']) ?> | Effective AY: <?= htmlspecialchars($curriculum['effective_academic_year'] ?? 'N/A') ?> | 
            <?php if ($curriculum['status'] === 'active'): ?>
                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 fw-medium"><i class="bi bi-shield-check me-1"></i>Active - Immutable</span>
            <?php elseif ($curriculum['status'] === 'draft'): ?>
                <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-1 fw-medium"><i class="bi bi-pencil-fill me-1"></i>Draft - Editable</span>
            <?php else: ?>
                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-1 fw-medium"><i class="bi bi-archive-fill me-1"></i>Archived - Read Only</span>
            <?php endif; ?>
          </p>
        </div>
        <div class="d-flex gap-2">
          <?php if ($curriculum['status'] === 'draft'): ?>
            <button class="btn btn-primary fw-medium shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addSubjectModal" onclick="openAddSubjectModalGlobal()">
              <i class="bi bi-plus-lg me-1"></i> Add Subject
            </button>
            <?php if (!empty($subjectsRaw)): ?>
              <button type="button" class="btn btn-success fw-medium shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#builderActivateModal">
                <i class="bi bi-check-circle me-1"></i> Activate Curriculum
              </button>
            <?php endif; ?>
          <?php elseif ($curriculum['status'] === 'active'): ?>
            <button class="btn btn-primary fw-medium shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#builderCloneModal">
              <i class="bi bi-files me-1"></i> Create New Version
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($curriculum['status'] === 'active'): ?>
      <div class="alert alert-warning border-0 shadow-sm rounded-12 p-3 mb-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
          <i class="bi bi-shield-lock-fill fs-3 text-warning"></i>
          <div>
            <h6 class="fw-bold mb-1 text-dark">Curriculum Structure is Locked (Active)</h6>
            <p class="mb-0 text-muted small">This curriculum is active and its academic structure is locked. Create a new version to make changes.</p>
          </div>
        </div>
        <button class="btn btn-sm btn-primary rounded-pill px-4 fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#builderCloneModal">
          <i class="bi bi-files me-1"></i> Create New Version
        </button>
      </div>
    <?php elseif ($curriculum['status'] === 'archived'): ?>
      <div class="alert alert-secondary border-0 shadow-sm rounded-12 p-3 mb-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
          <i class="bi bi-archive-fill fs-3 text-secondary"></i>
          <div>
            <h6 class="fw-bold mb-1 text-dark">Historical Curriculum (Archived)</h6>
            <p class="mb-0 text-muted small">This curriculum is archived and read-only for historical records.</p>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Sidebar Summary -->
        <div class="col-lg-3">
            <div class="island border-0 shadow-sm rounded-4 mb-4 fade-in-up" style="animation-delay: 0.2s;">
                <div class="island-header border-bottom border-light fade-in-up" style="animation-delay: 0.3s;">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Curriculum Summary</h6>
                </div>
                <div class="island-body p-4 fade-in-up" style="animation-delay: 0.4s;">
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <span class="text-muted">Total Subjects</span>
                        <span class="fw-bold text-dark fs-5"><?= esc($totalSubjects) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <span class="text-muted">Total Units</span>
                        <span class="fw-bold text-primary fs-5"><?= esc($totalUnits) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <span class="text-muted">Lecture Units</span>
                        <span class="fw-bold text-dark"><?= esc($lectureUnits) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <span class="text-muted">Lab Units</span>
                        <span class="fw-bold text-dark"><?= esc($labUnits) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Section Usage</span>
                        <span class="badge bg-light text-dark border"><?= (int)($curriculum['total_usage'] ?? 0) ?> sections</span>
                    </div>
                </div>
            </div>

            <div class="island border-0 shadow-sm rounded-4 fade-in-up" style="animation-delay: 0.5s;">
                <div class="island-body p-3 fade-in-up" style="animation-delay: 0.6s;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="builderSearch" class="form-control border-start-0" placeholder="Filter subjects...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Builder Area -->
        <div class="col-lg-9">
            <?php if (empty($subjectsRaw)): ?>
                <div class="island border-0 shadow-sm rounded-4 text-center py-5 fade-in-up" style="animation-delay: 0.7s;">
                    <i class="bi bi-diagram-3 fs-1 text-muted d-block mb-3"></i>
                    <h5 class="fw-bold text-dark">Curriculum is Empty</h5>
                    <p class="text-muted">Start building this SHS curriculum by adding subjects.</p>
                    <?php if ($curriculum['status'] === 'draft'): ?>
                      <button class="btn btn-outline-primary fw-medium rounded-pill px-4 mt-2" data-bs-toggle="modal" data-bs-target="#addSubjectModal" onclick="openAddSubjectModalGlobal()">
                          Add First Subject
                      </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach (['Grade 11', 'Grade 12'] as $gl): ?>
                    <div class="mb-5 curriculum-year-block">
                        <h4 class="fw-bold text-dark mb-3 border-bottom pb-2 border-2 border-primary d-inline-block"><?= htmlspecialchars($gl) ?></h4>
                        
                        <?php foreach (['First', 'Second'] as $sem): 
                            $semSubjects = $subjects[$gl][$sem] ?? [];
                            $subCount = count($semSubjects);
                        ?>
                            <div class="island border-0 shadow-sm rounded-4 mb-4 curriculum-sem-block fade-in-up" style="animation-delay: 0.7s;">
                                <div class="island-header bg-light border-bottom border-light d-flex justify-content-between align-items-center py-2 fade-in-up" style="animation-delay: 0.8s;">
                                    <h6 class="mb-0 fw-bold text-secondary text-uppercase tracking-wide small"><i class="bi bi-calendar-event me-2"></i><?= htmlspecialchars($sem) ?> Semester</h6>
                                    <div>
                                        <span class="badge bg-secondary rounded-pill me-2"><?= $subCount ?> subjects</span>
                                    </div>
                                </div>
                                <div class="island-body p-0 fade-in-up" style="animation-delay: 0.9s;">
                                    <div class="table-responsive">
                                        <table class="table table-borderless align-middle mb-0 builder-table">
                                            <thead class="border-bottom text-muted small text-uppercase">
                                                <tr>
                                                    <th class="ps-4" style="width: 15%">Code</th>
                                                    <th style="<?= $curriculum['status'] === 'draft' ? 'width: 45%' : 'width: 65%' ?>">Title</th>
                                                    <th style="width: 10%">Type</th>
                                                    <th style="width: 10%">Units</th>
                                                    <?php if ($curriculum['status'] === 'draft'): ?>
                                                        <th class="text-end pe-4" style="width: 20%">Manage</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($semSubjects)): ?>
                                                    <tr>
                                                        <td colspan="<?= $curriculum['status'] === 'draft' ? '5' : '4' ?>" class="text-center py-4 text-muted small fst-italic">No subjects added to this semester yet.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($semSubjects as $idx => $sub): ?>
                                                        <tr class="subject-row border-bottom border-light">
                                                            <td class="ps-4 fw-bold text-dark searchable-code"><?= htmlspecialchars($sub['subject_code']) ?></td>
                                                            <td class="searchable-name"><?= htmlspecialchars($sub['subject_name']) ?></td>
                                                            <td><span class="badge bg-light text-secondary border"><?= htmlspecialchars($sub['subject_type'] ?? 'Lecture') ?></span></td>
                                                            <td class="fw-medium text-success"><?= esc($sub['units']) ?></td>
                                                            <?php if ($curriculum['status'] === 'draft'): ?>
                                                                <td class="text-end pe-4">
                                                                    <!-- Move Up -->
                                                                    <?php if ($idx > 0): ?>
                                                                        <form action="shs_curriculum_process.php" method="POST" class="d-inline m-0 p-0">
                                                                            <?= getCsrfInput() ?>
                                                                            <input type="hidden" name="action" value="move_subject">
                                                                            <input type="hidden" name="curriculum_id" value="<?= esc($curriculumId) ?>">
                                                                            <input type="hidden" name="mapping_id" value="<?= esc($sub['mapping_id']) ?>">
                                                                            <input type="hidden" name="direction" value="up">
                                                                            <button type="submit" class="btn btn-sm btn-light border rounded-circle px-2 py-1" title="Move Up">
                                                                                <i class="bi bi-arrow-up"></i>
                                                                            </button>
                                                                        </form>
                                                                    <?php endif; ?>

                                                                    <!-- Move Down -->
                                                                    <?php if ($idx < ($subCount - 1)): ?>
                                                                        <form action="shs_curriculum_process.php" method="POST" class="d-inline m-0 p-0">
                                                                            <?= getCsrfInput() ?>
                                                                            <input type="hidden" name="action" value="move_subject">
                                                                            <input type="hidden" name="curriculum_id" value="<?= esc($curriculumId) ?>">
                                                                            <input type="hidden" name="mapping_id" value="<?= esc($sub['mapping_id']) ?>">
                                                                            <input type="hidden" name="direction" value="down">
                                                                            <button type="submit" class="btn btn-sm btn-light border rounded-circle px-2 py-1 ms-1" title="Move Down">
                                                                                <i class="bi bi-arrow-down"></i>
                                                                            </button>
                                                                        </form>
                                                                    <?php endif; ?>

                                                                    <!-- Edit Placement -->
                                                                    <button type="button" class="btn btn-sm btn-light border rounded-circle px-2 py-1 ms-1" 
                                                                            onclick="openEditSubject(<?= esc($sub['mapping_id']) ?>, '<?= htmlspecialchars(addslashes($sub['subject_code'])) ?>', '<?= esc($gl) ?>', '<?= esc($sem) ?>')" 
                                                                            title="Edit Grade/Sem Placement">
                                                                        <i class="bi bi-pencil-fill text-muted"></i>
                                                                    </button>

                                                                    <!-- Delete Subject -->
                                                                    <form action="shs_curriculum_process.php" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($sub['subject_code'])) ?> from this curriculum?');">
                                                                        <?= getCsrfInput() ?>
                                                                        <input type="hidden" name="action" value="delete_subject">
                                                                        <input type="hidden" name="curriculum_id" value="<?= esc($curriculumId) ?>">
                                                                        <input type="hidden" name="mapping_id" value="<?= esc($sub['mapping_id']) ?>">
                                                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle px-2 py-1 ms-1" title="Remove Subject">
                                                                            <i class="bi bi-trash-fill"></i>
                                                                        </button>
                                                                    </form>
                                                                </td>
                                                            <?php endif; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
  </div>
</main>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="shs_curriculum_process.php" method="POST">
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" value="add_subject">
        <input type="hidden" name="curriculum_id" value="<?= esc($curriculumId) ?>">
        
        <div class="modal-header bg-light border-bottom-0 pb-3">
          <div>
            <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Add Subjects to Curriculum</h5>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0">
          <div class="p-3 bg-light border-bottom shadow-sm z-index-1 position-relative">
             <div class="row g-3 mb-3">
                 <div class="col-6">
                     <label class="form-label small fw-semibold text-dark">Grade Level <span class="text-danger">*</span></label>
                     <select class="form-select bg-white" name="grade_level" id="addGradeLevel" required>
                         <option value="" disabled selected>Select Grade</option>
                         <option value="Grade 11">Grade 11</option>
                         <option value="Grade 12">Grade 12</option>
                     </select>
                 </div>
                 <div class="col-6">
                     <label class="form-label small fw-semibold text-dark">Semester <span class="text-danger">*</span></label>
                     <select class="form-select bg-white" name="semester" id="addSemester" required>
                         <option value="" disabled selected>Select Semester</option>
                         <option value="First">First Semester</option>
                         <option value="Second">Second Semester</option>
                     </select>
                 </div>
             </div>
             <div class="input-group input-group-sm rounded-pill overflow-hidden border bg-white">
                <span class="input-group-text bg-transparent border-0 pe-1"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="modalSubjectSearch" class="form-control border-0 shadow-none" placeholder="Search subjects...">
             </div>
          </div>
          <div class="subject-list-container" style="max-height: 400px; overflow-y: auto;">
             <div class="list-group list-group-flush" id="modalSubjectList">
                <?php foreach ($globalSubjects as $sub): ?>
                  <label class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 global-subject-item border-bottom">
                     <input class="form-check-input flex-shrink-0 fs-5 mt-0" type="checkbox" name="subject_ids[]" value="<?= esc($sub['id']) ?>">
                     <div class="w-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="fw-bold text-dark modal-sub-code"><?= htmlspecialchars($sub['subject_code']) ?></div>
                            <span class="badge bg-light text-secondary border"><?= htmlspecialchars($sub['subject_type'] ?? 'Lecture') ?></span>
                        </div>
                        <div class="modal-sub-name text-muted small"><?= htmlspecialchars($sub['subject_name']) ?></div>
                        <div class="small fw-medium text-success mt-1"><?= esc($sub['units']) ?> units</div>
                     </div>
                  </label>
                <?php endforeach; ?>
             </div>
          </div>
        </div>
        <div class="modal-footer bg-light border-top-0 pt-3">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-medium"><i class="bi bi-plus-lg me-1"></i> Add Selected Subjects</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="shs_curriculum_process.php" method="POST">
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" value="edit_subject">
        <input type="hidden" name="curriculum_id" value="<?= esc($curriculumId) ?>">
        <input type="hidden" name="mapping_id" id="edit_sub_mapping_id" value="">
        <div class="modal-header bg-light border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-fill text-primary me-2"></i>Edit Subject Placement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 pt-2">
            <div class="alert alert-light border py-2 px-3 small mb-3">
              Subject: <strong id="edit_sub_code" class="text-primary"></strong>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Grade Level</label>
                <select class="form-select bg-light" name="grade_level" id="edit_sub_gl" required>
                    <option value="Grade 11">Grade 11</option>
                    <option value="Grade 12">Grade 12</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Semester</label>
                <select class="form-select bg-light" name="semester" id="edit_sub_sem" required>
                    <option value="First">First Semester</option>
                    <option value="Second">Second Semester</option>
                </select>
            </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light rounded-pill px-3 fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success rounded-pill px-3 fw-medium shadow-sm">Save Move</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Builder Clone Modal -->
<div class="modal fade" id="builderCloneModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="shs_curriculum_process.php" method="POST">
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" value="clone_curriculum">
        <input type="hidden" name="source_curriculum_id" value="<?= esc($curriculumId) ?>">
        <div class="modal-header bg-primary text-white border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold"><i class="bi bi-files me-2"></i>Clone to New Version</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 pt-3">
            <div class="alert alert-light border py-2 px-3 small mb-3">
              Cloning source: <strong class="text-primary"><?= htmlspecialchars($curriculum['curriculum_name']) ?> (v<?= htmlspecialchars($curriculum['version']) ?>)</strong>
            </div>
            <?php
              $curVer = (float)$curriculum['version'];
              $nextVer = $curVer > 0 ? number_format($curVer + 1.0, 1) : '2.0';
              $suggestedName = preg_replace('/\bv\d+(\.\d+)?\b/i', '', $curriculum['curriculum_name']);
              $suggestedName = trim($suggestedName) . ' (v' . $nextVer . ')';
            ?>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">New Curriculum Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control bg-light" name="curriculum_name" value="<?= htmlspecialchars($suggestedName) ?>" required>
            </div>
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">New Version Tag <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" name="version" value="<?= htmlspecialchars($nextVer) ?>" placeholder="e.g. 2.0" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">Effective Academic Year <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" name="effective_academic_year" value="<?= htmlspecialchars($curriculum['effective_academic_year'] ?? '') ?>" placeholder="e.g. 2027-2028" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Description</label>
                <textarea class="form-control bg-light" name="description" rows="2" placeholder="Notes regarding this revision..."><?= htmlspecialchars($curriculum['description'] ?? '') ?></textarea>
            </div>
            <p class="small text-muted mb-0">
              <i class="bi bi-check2-all text-success me-1"></i> All <?= count($subjectsRaw) ?> subjects will be duplicated into the new <strong>Draft</strong> version where you can freely add, reassign, or remove subjects.
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

<!-- Builder Activate Modal -->
<div class="modal fade" id="builderActivateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="shs_curriculum_process.php" method="POST">
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" value="activate_curriculum">
        <input type="hidden" name="curriculum_id" value="<?= esc($curriculumId) ?>">
        <div class="modal-header bg-success text-white border-bottom-0">
          <h5 class="modal-title fw-bold"><i class="bi bi-shield-check me-2"></i>Activate Curriculum</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 text-center">
            <i class="bi bi-shield-lock-fill text-success fs-1 d-block mb-3"></i>
            <h5 class="fw-bold text-dark mb-2">Activate <?= htmlspecialchars($curriculum['curriculum_name']) ?>?</h5>
            <p class="text-muted small">
              Activating this curriculum will <strong>lock its academic structure</strong> for official SHS student enrollment and class scheduling. You will not be able to add, reassign, or remove subjects directly once active.
            </p>
            <div class="form-check text-start d-inline-block bg-light p-3 rounded-12 border mt-2">
              <input class="form-check-input" type="checkbox" name="archive_previous" value="1" id="builderArchivePrevious" checked>
              <label class="form-check-label small fw-semibold text-dark" for="builderArchivePrevious">
                Archive previous active versions of this strand
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

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
<script>
function openAddSubjectModalGlobal() {
    document.getElementById('addGradeLevel').value = '';
    document.getElementById('addSemester').value = '';
    
    // Clear checkboxes and search
    document.getElementById('modalSubjectSearch').value = '';
    const items = document.querySelectorAll('.global-subject-item');
    items.forEach(item => {
        item.style.display = 'flex';
        item.querySelector('input[type="checkbox"]').checked = false;
    });
}

function openEditSubject(mappingId, code, gl, sem) {
    document.getElementById('edit_sub_mapping_id').value = mappingId;
    document.getElementById('edit_sub_code').textContent = code;
    document.getElementById('edit_sub_gl').value = gl;
    document.getElementById('edit_sub_sem').value = sem;
    new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Builder search
    const builderSearch = document.getElementById('builderSearch');
    if (builderSearch) {
        builderSearch.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.builder-table .subject-row');
            
            rows.forEach(row => {
                const code = row.querySelector('.searchable-code').textContent.toLowerCase();
                const name = row.querySelector('.searchable-name').textContent.toLowerCase();
                
                if (code.includes(filter) || name.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Hide empty sem blocks
            document.querySelectorAll('.curriculum-sem-block').forEach(semBlock => {
                const visibleRows = Array.from(semBlock.querySelectorAll('.subject-row')).filter(r => r.style.display !== 'none');
                if (visibleRows.length === 0 && filter !== '') {
                    semBlock.style.display = 'none';
                } else {
                    semBlock.style.display = '';
                }
            });

            document.querySelectorAll('.curriculum-year-block').forEach(yearBlock => {
                const visibleSems = Array.from(yearBlock.querySelectorAll('.curriculum-sem-block')).filter(s => s.style.display !== 'none');
                if (visibleSems.length === 0 && filter !== '') {
                    yearBlock.style.display = 'none';
                } else {
                    yearBlock.style.display = '';
                }
            });
        });
    }

    // Modal subject search
    const modalSearch = document.getElementById('modalSubjectSearch');
    if (modalSearch) {
        modalSearch.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase().trim();
            const items = document.querySelectorAll('.global-subject-item');
            
            items.forEach(item => {
                const code = item.querySelector('.modal-sub-code').textContent.toLowerCase();
                const name = item.querySelector('.modal-sub-name').textContent.toLowerCase();
                
                if (code.includes(filter) || name.includes(filter)) {
                    item.style.display = 'flex';
                    item.classList.add('d-flex');
                } else {
                    item.style.display = 'none';
                    item.classList.remove('d-flex');
                }
            });
        });
    }
});
</script>
