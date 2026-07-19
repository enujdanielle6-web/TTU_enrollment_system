<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('college_sections.manage');

// Handle actions (Activate/Deactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['section_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['admin_error'] = 'Invalid CSRF token.';
    } else {
        $sectionId = (int)$_POST['section_id'];
        if ($_POST['action'] === 'delete_section') {
            try {
                $pdo->prepare('DELETE FROM college_section_subjects WHERE college_section_id = ?')->execute([$sectionId]);
                $stmtDel = $pdo->prepare('DELETE FROM college_sections WHERE id = ?');
                $stmtDel->execute([$sectionId]);
                
                if ($stmtDel->rowCount() > 0) {
                    require_once __DIR__ . '/../../includes/functions.php';
                    logActivity((int)$_SESSION['user_id'], 'bi-trash', 'Section Deleted', "Deleted section ID #$sectionId", "Section #$sectionId");
                    $_SESSION['admin_success'] = 'Section deleted successfully.';
                } else {
                    $_SESSION['admin_error'] = 'Section not found or could not be deleted.';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['admin_error'] = 'Cannot delete section because it has enrolled students or pending applications.';
                } else {
                    $_SESSION['admin_error'] = 'Database error: ' . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'toggle_status') {
            // Fetch old status
            $stmtOld = $pdo->prepare('SELECT status, section_code FROM college_sections WHERE id = :id');
            $stmtOld->execute(['id' => $sectionId]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

            if ($oldData) {
                $stmt = $pdo->prepare('UPDATE college_sections SET status = IF(status=1, 0, 1) WHERE id = :id');
                $stmt->execute(['id' => $sectionId]);
                
                $newStatus = $oldData['status'] == 1 ? 0 : 1;
                $statusLabel = $newStatus == 1 ? 'Activated' : 'Deactivated';

                require_once __DIR__ . '/../../includes/functions.php';
                logActivity(
                    (int)$_SESSION['user_id'],
                    'bi-toggle-on',
                    'Section ' . $statusLabel,
                    "{$statusLabel} section " . $oldData['section_code'],
                    "Section #$sectionId",
                    ['status' => $oldData['status']],
                    ['status' => $newStatus]
                );

                $_SESSION['admin_success'] = 'Section status updated successfully.';
            }
        }
    }
    header('Location: college_sections.php');
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_section') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['admin_error'] = 'Invalid CSRF token.';
    } else {
        $section_code = trim($_POST['section_code'] ?? '');
        $program_id = (int)($_POST['program_id'] ?? 0);
        $curriculum_id = (int)($_POST['curriculum_id'] ?? 0);
        $academic_year = trim($_POST['academic_year'] ?? '');
        $year_level = trim($_POST['year_level'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 40);
        $schedule_type = trim($_POST['schedule_type'] ?? 'Morning');
        $adviser = trim($_POST['adviser'] ?? '');
        
        if ($section_code && $program_id && $curriculum_id && $year_level) {
            try {
                $stmt = $pdo->prepare('INSERT INTO college_sections (section_code, program_id, curriculum_id, academic_year, year_level, semester, capacity, schedule_type, adviser, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
                $stmt->execute([$section_code, $program_id, $curriculum_id, $academic_year ?: null, $year_level, $semester ?: null, $capacity, $schedule_type, $adviser]);
                $newSectionId = (int)$pdo->lastInsertId();
                
                // Auto-import curriculum to college_section_subjects from the selected curriculum
                $currStmt = $pdo->prepare('
                    SELECT subject_id 
                    FROM college_curriculum_subjects 
                    WHERE curriculum_id = ? AND year_level = ? AND (semester = ? OR semester IS NULL OR semester = "")
                ');
                $currStmt->execute([$curriculum_id, $year_level, $semester ?: '']);
                $subjects = $currStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($subjects)) {
                    $insSub = $pdo->prepare("INSERT INTO college_section_subjects (college_section_id, subject_id, capacity, day, start_time, end_time) VALUES (?, ?, ?, 'TBA', '00:00:00', '00:00:00')");
                    foreach ($subjects as $sub) {
                        $insSub->execute([$newSectionId, $sub['subject_id'], $capacity]);
                    }
                }
                
                $_SESSION['admin_success'] = 'Section created successfully based on the selected curriculum.';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $_SESSION['admin_error'] = 'Section code already exists.';
                } else {
                    $_SESSION['admin_error'] = 'Database error: ' . $e->getMessage();
                }
            }
        } else {
            $_SESSION['admin_error'] = 'Please fill in all required fields.';
        }
    }
    header('Location: college_sections.php');
    exit;
}

try {
    $query = "
        SELECT 
            s.*, 
            p.code as program_code,
            c.version as curriculum_version,
            (SELECT COUNT(*) FROM applications a WHERE a.section_id = s.id AND a.status != 'rejected') as current_enrollment
        FROM college_sections s
        INNER JOIN college_programs p ON p.id = s.program_id
        LEFT JOIN college_curricula c ON s.curriculum_id = c.id
        ORDER BY p.code ASC, s.year_level ASC, s.section_code ASC
    ";
    $stmt = $pdo->query($query);
    $college_sections = $stmt->fetchAll();
    
    // Fetch programs for Add Section modal
    $progStmt = $pdo->query("SELECT id, code, name FROM college_programs WHERE is_active = 1 ORDER BY code ASC");
    $programs = $progStmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching college_sections: ' . $e->getMessage());
    $college_sections = [];
    $programs = [];
}

$pageTitle = 'Section Management - Admin';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">Section Management</h1>
          <p class="text-muted mb-0">Manage student college_sections and monitoring capacity</p>
        </div>
        <div>
          <button type="button" class="btn btn-primary rounded-pill shadow-sm px-4 fw-medium" data-bs-toggle="modal" data-bs-target="#addSectionModal">
            <i class="bi bi-plus-circle me-1"></i> Add Section
          </button>
        </div>
      </div>
    </div>

    <?php if (isset($_SESSION['admin_success'])): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['admin_success'], ENT_QUOTES, 'UTF-8'); ?></div>
      <?php unset($_SESSION['admin_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['admin_error'])): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['admin_error'], ENT_QUOTES, 'UTF-8'); ?></div>
      <?php unset($_SESSION['admin_error']); ?>
    <?php endif; ?>

    <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom d-flex justify-content-between align-items-center p-3">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-list-ul me-2 text-primary"></i>All College Sections</h5>
        <div class="input-group shadow-sm" style="width: 250px;">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" id="sectionSearch" class="form-control border-start-0" placeholder="Search college_sections...">
        </div>
      </div>
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light text-muted small text-uppercase">
              <tr>
                <th class="ps-4">Section Code</th>
                <th>Program & Curriculum</th>
                <th>Yr & Sem</th>
                <th>Schedule</th>
                <th>Adviser</th>
                <th>Capacity</th>
                <th>Status</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($college_sections)): ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No college_sections found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($college_sections as $s): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($s['section_code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle"><?= htmlspecialchars($s['program_code'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($s['curriculum_version']): ?>
                            <div class="small text-muted mt-1">v<?= htmlspecialchars($s['curriculum_version'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                      <div><?= htmlspecialchars($s['year_level'], ENT_QUOTES, 'UTF-8') ?></div>
                      <?php if ($s['semester']): ?>
                      <div class="small text-muted"><?= htmlspecialchars($s['semester'], ENT_QUOTES, 'UTF-8') ?> Sem</div>
                      <?php endif; ?>
                    </td>
                    <td><i class="bi bi-clock me-1 text-muted"></i><?= htmlspecialchars($s['schedule_type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($s['adviser'] ?: 'Unassigned', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <?php 
                          $enrolled = (int)$s['current_enrollment'];
                          $cap = (int)$s['capacity'];
                          $pct = $cap > 0 ? ($enrolled / $cap) * 100 : 0;
                          $bg = $pct >= 100 ? 'bg-danger' : ($pct > 80 ? 'bg-warning' : 'bg-primary');
                        ?>
                        <div class="fw-medium <?= $pct >= 100 ? 'text-danger' : 'text-dark' ?>"><?= $enrolled ?>/<?= $cap ?></div>
                        <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                          <div class="progress-bar <?= $bg ?>" style="width: <?= min(100, $pct) ?>%"></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?php if ((int)$s['status'] === 1): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Active</span>
                      <?php else: ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <a href="schedule_builder.php?type=college&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill me-1" title="Manage Schedule">
                        <i class="bi bi-calendar-range"></i>
                      </a>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="section_id" value="<?= $s['id'] ?>">
                        <input type="hidden" name="action" value="toggle_status">
                        <button type="submit" class="btn btn-sm <?= (int)$s['status'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?> rounded-pill me-1" title="Toggle Status">
                          <i class="bi <?= (int)$s['status'] === 1 ? 'bi-lock' : 'bi-unlock' ?>"></i>
                        </button>
                      </form>
                      <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" 
                              data-bs-toggle="modal" data-bs-target="#deleteSectionModal" 
                              onclick="setDeleteSection(<?= $s['id'] ?>, '<?= htmlspecialchars($s['section_code'], ENT_QUOTES, 'UTF-8') ?>')" title="Delete Section">
                        <i class="bi bi-trash-fill"></i>
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

<!-- Add Section Modal -->
<div class="modal fade" id="addSectionModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="college_sections.php" class="needs-validation" novalidate id="addSectionForm">
        <div class="modal-header bg-primary text-white border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Create New Section</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 bg-light">
          <input type="hidden" name="action" value="add_section">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          
          <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">Academic Program <span class="text-danger">*</span></label>
                <select class="form-select" name="program_id" id="programSelect" required>
                  <option value="" selected disabled>Select a program...</option>
                  <?php foreach ($programs as $prog): ?>
                    <option value="<?= $prog['id'] ?>" data-category="College">
                      <?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Please select an academic program.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">Curriculum <span class="text-danger">*</span></label>
                <select class="form-select" name="curriculum_id" id="curriculumSelect" required disabled>
                  <option value="" selected disabled>Select program first...</option>
                </select>
                <div class="invalid-feedback">Please select a curriculum.</div>
              </div>
          </div>
          
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label text-muted small fw-bold">Year Level <span class="text-danger">*</span></label>
              <select class="form-select preview-trigger" name="year_level" id="yearLevelSelect" required disabled>
                <option value="" selected disabled>Select...</option>
                <option value="1st Year">1st Year</option>
                <option value="2nd Year">2nd Year</option>
                <option value="3rd Year">3rd Year</option>
                <option value="4th Year">4th Year</option>
                <option value="5th Year">5th Year</option>
              </select>
              <div class="invalid-feedback">Please select a year level.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted small fw-bold">Semester <span class="text-danger">*</span></label>
              <select class="form-select preview-trigger" name="semester" id="semesterSelect" required disabled>
                <option value="" selected disabled>Select...</option>
                <option value="First">First Semester</option>
                <option value="Second">Second Semester</option>
                <option value="Summer">Summer</option>
              </select>
              <div class="invalid-feedback">Please select a semester.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted small fw-bold">Academic Year <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="academic_year" placeholder="e.g. 2025-2026" required>
            </div>
          </div>
          
          <!-- Subject Preview Panel -->
          <div class="island border rounded-3 mb-4 d-none" id="subjectPreviewPanel">
              <div class="island-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                  <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-journal-bookmark me-2 text-primary"></i>Curriculum Subjects Preview</h6>
                  <span class="badge bg-primary rounded-pill" id="previewSubCount">0 Subjects</span>
              </div>
              <div class="island-body p-0">
                  <div class="table-responsive" style="max-height: 250px;">
                      <table class="table table-sm table-hover align-middle mb-0">
                          <thead class="table-light text-muted small sticky-top">
                              <tr>
                                  <th class="ps-3">Code</th>
                                  <th>Description</th>
                                  <th>Units</th>
                                  <th>Type</th>
                              </tr>
                          </thead>
                          <tbody id="previewSubjectsBody">
                              <!-- AJAX Content -->
                          </tbody>
                      </table>
                  </div>
              </div>
              <div class="island-footer bg-light border-top py-2 px-3 text-end text-muted small" id="previewSummary">
                  Total Units: 0
              </div>
          </div>

          <hr class="text-muted border-dashed mb-4">

          <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label text-muted small fw-bold">Section Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="section_code" required placeholder="e.g., BSIT-1A">
                <div class="invalid-feedback">Please provide a section code.</div>
              </div>
              <div class="col-md-4">
                <label class="form-label text-muted small fw-bold">Capacity <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="capacity" value="40" required min="1">
              </div>
              <div class="col-md-4">
                <label class="form-label text-muted small fw-bold">Schedule Type <span class="text-danger">*</span></label>
                <select class="form-select" name="schedule_type" required>
                  <option value="Morning" selected>Morning</option>
                  <option value="Afternoon">Afternoon</option>
                </select>
              </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Adviser (Optional)</label>
            <input type="text" class="form-control" name="adviser" placeholder="Name of section adviser">
          </div>
        </div>
        <div class="modal-footer bg-white border-top pt-3">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-medium">
            <i class="bi bi-save me-1"></i> Save Section
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Section Modal -->
<div class="modal fade" id="deleteSectionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white border-bottom-0 pb-3">
        <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Section</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-4 text-center">
        <i class="bi bi-trash text-danger display-1 mb-3 d-block"></i>
        <h5 class="fw-bold mb-2">Are you absolutely sure?</h5>
        <p class="text-muted mb-0">This will permanently delete the section <strong id="deleteSectionCode" class="text-dark"></strong> and its schedules. This cannot be undone.</p>
      </div>
      <div class="modal-footer bg-light border-top-0 pt-3 justify-content-center">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <form method="POST" class="d-inline">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="action" value="delete_section">
          <input type="hidden" name="section_id" id="deleteSectionId">
          <button type="submit" class="btn btn-danger rounded-pill px-4 shadow-sm fw-medium">Yes, Delete Section</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Form Validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Search Filter Logic
    const searchInput = document.getElementById('sectionSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('.custom-table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
    
    // Dynamic Loading Logic
    const programSelect = document.getElementById('programSelect');
    const curriculumSelect = document.getElementById('curriculumSelect');
    const yearLevelSelect = document.getElementById('yearLevelSelect');
    const semesterSelect = document.getElementById('semesterSelect');
    
    const previewPanel = document.getElementById('subjectPreviewPanel');
    const previewBody = document.getElementById('previewSubjectsBody');
    const previewCount = document.getElementById('previewSubCount');
    const previewSummary = document.getElementById('previewSummary');

    function loadPreview() {
        const currId = curriculumSelect.value;
        const yl = yearLevelSelect.value;
        const sem = semesterSelect.value;

        if (currId && yl && sem) {
            previewPanel.classList.remove('d-none');
            previewBody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted">Loading subjects...</td></tr>';
            
            fetch(`../ajax/get_curriculum_subjects_preview.php?curriculum_id=${currId}&year_level=${encodeURIComponent(yl)}&semester=${encodeURIComponent(sem)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        previewBody.innerHTML = `<tr><td colspan="4" class="text-center py-3 text-danger">${data.error}</td></tr>`;
                        return;
                    }

                    if (data.length === 0) {
                        previewBody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted">No subjects mapped in curriculum for this Year/Semester.</td></tr>';
                        previewCount.textContent = '0 Subjects';
                        previewSummary.textContent = 'Total Units: 0';
                        return;
                    }

                    let html = '';
                    let totalUnits = 0;
                    data.forEach(sub => {
                        html += `
                            <tr>
                                <td class="ps-3 fw-bold">${sub.subject_code}</td>
                                <td>${sub.subject_name}</td>
                                <td class="text-success fw-medium">${sub.units}</td>
                                <td><span class="badge bg-light text-secondary border">${sub.subject_type || 'Lecture'}</span></td>
                            </tr>
                        `;
                        totalUnits += parseInt(sub.units);
                    });

                    previewBody.innerHTML = html;
                    previewCount.textContent = `${data.length} Subjects`;
                    previewSummary.innerHTML = `<strong>Total Units: ${totalUnits}</strong>`;
                })
                .catch(err => {
                    previewBody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-danger">Failed to load subjects.</td></tr>';
                });
        } else {
            previewPanel.classList.add('d-none');
        }
    }

    if (programSelect) {
        programSelect.addEventListener('change', function() {
            const pid = this.value;
            
            curriculumSelect.innerHTML = '<option value="" selected disabled>Loading curricula...</option>';
            curriculumSelect.disabled = true;
            yearLevelSelect.disabled = true;
            semesterSelect.disabled = true;
            previewPanel.classList.add('d-none');
            
            fetch(`../ajax/get_curricula_by_program.php?program_id=${pid}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error || data.length === 0) {
                        curriculumSelect.innerHTML = '<option value="" selected disabled>No active curricula found</option>';
                    } else {
                        curriculumSelect.innerHTML = '<option value="" selected disabled>Select a curriculum...</option>';
                        data.forEach(c => {
                            curriculumSelect.innerHTML += `<option value="${c.id}">${c.curriculum_name} (v${c.version})</option>`;
                        });
                        curriculumSelect.disabled = false;
                    }
                });
        });
    }

    if (curriculumSelect) {
        curriculumSelect.addEventListener('change', function() {
            yearLevelSelect.disabled = false;
            semesterSelect.disabled = false;
            loadPreview();
        });
    }

    document.querySelectorAll('.preview-trigger').forEach(el => {
        el.addEventListener('change', loadPreview);
    });

});

function setDeleteSection(id, code) {
    document.getElementById('deleteSectionId').value = id;
    document.getElementById('deleteSectionCode').textContent = code;
}

</script>
</body>
</html>
