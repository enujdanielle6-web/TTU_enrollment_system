<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('college_curriculum.manage');

$currId = (int)($_GET['id'] ?? 0);
if ($currId <= 0) {
    header('Location: college_curriculum.php');
    exit;
}

// Fetch curriculum metadata
try {
    $stmt = $pdo->prepare("
        SELECT cc.*, p.code as program_code, p.name as program_name 
        FROM college_curricula cc 
        INNER JOIN college_programs p ON cc.program_id = p.id 
        WHERE cc.id = ?
    ");
    $stmt->execute([$currId]);
    $curriculum = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching curriculum: " . $e->getMessage());
}

if (!$curriculum) {
    header('Location: college_curriculum.php');
    exit;
}

$pageTitle = htmlspecialchars($curriculum['curriculum_name']) . ' - Builder';

// Fetch all subjects for this curriculum
$subjects = [];
$totalUnits = 0;
$lectureUnits = 0;
$labUnits = 0;

try {
    $stmt = $pdo->prepare("
        SELECT c.id as mapping_id, c.year_level, c.semester, c.display_order,
               s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type
        FROM college_curriculum_subjects c
        INNER JOIN subjects s ON c.subject_id = s.id
        WHERE c.curriculum_id = ?
        ORDER BY c.year_level ASC, c.semester ASC, c.display_order ASC
    ");
    $stmt->execute([$currId]);
    $subjectsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($subjectsRaw as $row) {
        $yl = $row['year_level'];
        $sem = $row['semester'];
        
        if (!isset($subjects[$yl])) {
            $subjects[$yl] = [];
        }
        if (!isset($subjects[$yl][$sem])) {
            $subjects[$yl][$sem] = [];
        }
        
        $subjects[$yl][$sem][] = $row;
        
        $totalUnits += (int)$row['units'];
        if (stripos((string)$row['subject_type'], 'lab') !== false) {
            $labUnits += (int)$row['units'];
        } else {
            $lectureUnits += (int)$row['units'];
        }
    }
} catch (PDOException $e) {
    die("Error fetching subjects: " . $e->getMessage());
}

// Fetch global subjects for Add Modal
$globalSubjects = [];
try {
    $gstmt = $pdo->query("SELECT id, subject_code, subject_name, units, subject_type FROM subjects WHERE status = 1 AND education_level IN ('College', 'Both') ORDER BY subject_code ASC");
    $globalSubjects = $gstmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

require_once __DIR__ . '/../../components/header.php';
?>
<?php require_once __DIR__ . '/../components/navbar.php'; ?>
<style>
.subject-row { transition: background-color 0.2s; }
.subject-row:hover { background-color: #f8f9fa; }
</style>
<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Top Header -->
    <div class="island island-hero mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <a href="college_curriculum.php" class="btn btn-sm btn-light text-primary mb-2 fw-medium rounded-pill px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Curricula
          </a>
          <h1 class="h3 fw-bold text-dark mb-1"><?= htmlspecialchars($curriculum['curriculum_name']) ?></h1>
          <p class="text-muted mb-0">
            <?= htmlspecialchars($curriculum['program_code']) ?> | Version <?= htmlspecialchars($curriculum['version']) ?> | 
            <?php if ($curriculum['status'] === 'active'): ?>
                <span class="text-success fw-medium"><i class="bi bi-check-circle-fill me-1"></i>Active</span>
            <?php else: ?>
                <span class="text-secondary fw-medium"><i class="bi bi-circle me-1"></i><?= ucfirst($curriculum['status']) ?></span>
            <?php endif; ?>
          </p>
        </div>
        <div>
          <button class="btn btn-primary fw-medium shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
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

    <div class="row g-4">
        <!-- Sidebar Summary -->
        <div class="col-lg-3">
            <div class="island border-0 shadow-sm rounded-4 mb-4">
                <div class="island-header border-bottom border-light">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Curriculum Summary</h6>
                </div>
                <div class="island-body p-4">
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <span class="text-muted">Total Subjects</span>
                        <span class="fw-bold text-dark fs-5"><?= count($subjectsRaw) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <span class="text-muted">Total Units</span>
                        <span class="fw-bold text-primary fs-5"><?= $totalUnits ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                        <span class="text-muted">Lecture Units</span>
                        <span class="fw-bold text-dark"><?= $lectureUnits ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Lab Units</span>
                        <span class="fw-bold text-dark"><?= $labUnits ?></span>
                    </div>
                </div>
            </div>

            <div class="island border-0 shadow-sm rounded-4">
                <div class="island-body p-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="builderSearch" class="form-control border-start-0" placeholder="Filter subjects...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Builder Area -->
        <div class="col-lg-9">
            <?php if (empty($subjects)): ?>
                <div class="island border-0 shadow-sm rounded-4 text-center py-5">
                    <i class="bi bi-diagram-3 fs-1 text-muted d-block mb-3"></i>
                    <h5 class="fw-bold text-dark">Curriculum is Empty</h5>
                    <p class="text-muted">Start building this curriculum by adding subjects.</p>
                    <button class="btn btn-outline-primary fw-medium rounded-pill px-4 mt-2" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        Add First Subject
                    </button>
                </div>
            <?php else: ?>
                <?php 
                // Custom sort for year levels
                $yearLevels = array_keys($subjects);
                sort($yearLevels); 
                
                foreach ($yearLevels as $yl): 
                    $semesters = array_keys($subjects[$yl]);
                    sort($semesters);
                ?>
                    <div class="mb-5 curriculum-year-block">
                        <h4 class="fw-bold text-dark mb-3 border-bottom pb-2 border-2 border-primary d-inline-block"><?= htmlspecialchars($yl) ?></h4>
                        
                        <?php foreach ($semesters as $sem): ?>
                            <div class="island border-0 shadow-sm rounded-4 mb-4 curriculum-sem-block">
                                <div class="island-header bg-light border-bottom border-light d-flex justify-content-between align-items-center py-2">
                                    <h6 class="mb-0 fw-bold text-secondary text-uppercase tracking-wide small"><i class="bi bi-calendar-event me-2"></i><?= htmlspecialchars($sem) ?></h6>
                                    <span class="badge bg-secondary rounded-pill"><?= count($subjects[$yl][$sem]) ?> subjects</span>
                                </div>
                                <div class="island-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-borderless align-middle mb-0 builder-table">
                                            <thead class="border-bottom text-muted small text-uppercase">
                                                <tr>
                                                    <th class="ps-4" style="width: 15%">Code</th>
                                                    <th style="width: 40%">Title</th>
                                                    <th style="width: 10%">Type</th>
                                                    <th style="width: 10%">Units</th>
                                                    <th class="text-end pe-4" style="width: 25%">Manage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($subjects[$yl][$sem] as $index => $sub): ?>
                                                    <tr class="subject-row border-bottom border-light">
                                                        <td class="ps-4 fw-bold text-dark searchable-code"><?= htmlspecialchars($sub['subject_code']) ?></td>
                                                        <td class="searchable-name"><?= htmlspecialchars($sub['subject_name']) ?></td>
                                                        <td><span class="badge bg-light text-secondary border"><?= htmlspecialchars($sub['subject_type'] ?? 'Lecture') ?></span></td>
                                                        <td class="fw-medium text-success"><?= $sub['units'] ?></td>
                                                        <td class="text-end pe-4">
                                                            <div class="btn-group btn-group-sm me-2 shadow-sm rounded-pill">
                                                                <form action="college_curriculum_process.php" method="POST" class="d-inline m-0 p-0">
                                                                    <input type="hidden" name="action" value="move_subject">
                                                                    <input type="hidden" name="curriculum_id" value="<?= $currId ?>">
                                                                    <input type="hidden" name="subject_mapping_id" value="<?= $sub['mapping_id'] ?>">
                                                                    <input type="hidden" name="direction" value="up">
                                                                    <button type="submit" class="btn btn-light border-end py-1 px-2" title="Move Up" <?= $index === 0 ? 'disabled' : '' ?>><i class="bi bi-arrow-up text-secondary"></i></button>
                                                                </form>
                                                                <form action="college_curriculum_process.php" method="POST" class="d-inline m-0 p-0">
                                                                    <input type="hidden" name="action" value="move_subject">
                                                                    <input type="hidden" name="curriculum_id" value="<?= $currId ?>">
                                                                    <input type="hidden" name="subject_mapping_id" value="<?= $sub['mapping_id'] ?>">
                                                                    <input type="hidden" name="direction" value="down">
                                                                    <button type="submit" class="btn btn-light py-1 px-2" title="Move Down" <?= $index === count($subjects[$yl][$sem]) - 1 ? 'disabled' : '' ?>><i class="bi bi-arrow-down text-secondary"></i></button>
                                                                </form>
                                                            </div>
                                                            <button class="btn btn-sm btn-outline-primary rounded-circle px-2 py-1" title="Edit Assignment"
                                                                    onclick="openEditSubject(<?= $sub['mapping_id'] ?>, '<?= htmlspecialchars(addslashes($sub['subject_code']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($yl), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($sem), ENT_QUOTES, 'UTF-8') ?>')">
                                                                <i class="bi bi-pencil-fill"></i>
                                                            </button>
                                                            <form action="college_curriculum_process.php" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($sub['subject_code'])) ?> from this curriculum?');">
                                                                <input type="hidden" name="action" value="delete_subject">
                                                                <input type="hidden" name="curriculum_id" value="<?= $currId ?>">
                                                                <input type="hidden" name="subject_mapping_id" value="<?= $sub['mapping_id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle px-2 py-1 ms-1" title="Remove Subject">
                                                                    <i class="bi bi-trash-fill"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
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
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="college_curriculum_process.php" method="POST">
        <input type="hidden" name="action" value="add_subject">
        <input type="hidden" name="curriculum_id" value="<?= $currId ?>">
        <div class="modal-header bg-light border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Add Subject to Curriculum</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 pt-2">
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Subject <span class="text-danger">*</span></label>
                <select class="form-select bg-light" name="subject_id" required>
                    <option value="" disabled selected>Select from global subjects</option>
                    <?php foreach ($globalSubjects as $gsub): ?>
                        <option value="<?= $gsub['id'] ?>"><?= htmlspecialchars($gsub['subject_code'] . ' - ' . $gsub['subject_name']) ?> (<?= $gsub['units'] ?> units)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">Year Level <span class="text-danger">*</span></label>
                    <select class="form-select bg-light" name="year_level" required>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                        <option value="5th Year">5th Year</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold text-dark">Semester <span class="text-danger">*</span></label>
                    <select class="form-select bg-light" name="semester" required>
                        <option value="First">First Semester</option>
                        <option value="Second">Second Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 rounded-pill fw-medium shadow-sm">Add Subject</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="college_curriculum_process.php" method="POST">
        <input type="hidden" name="action" value="edit_subject">
        <input type="hidden" name="curriculum_id" value="<?= $currId ?>">
        <input type="hidden" name="subject_mapping_id" id="edit_sub_mapping_id" value="">
        
        <div class="modal-header bg-light border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-fill text-success me-2"></i>Move Subject</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 pt-2">
            <p class="mb-3 text-muted small">Update placement for <strong id="edit_sub_code" class="text-dark"></strong></p>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Year Level</label>
                <select class="form-select bg-light" name="year_level" id="edit_sub_yl" required>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
                    <option value="5th Year">5th Year</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Semester</label>
                <select class="form-select bg-light" name="semester" id="edit_sub_sem" required>
                    <option value="First">First Semester</option>
                    <option value="Second">Second Semester</option>
                    <option value="Summer">Summer</option>
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

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('builderSearch');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();
            const subjectRows = document.querySelectorAll('.subject-row');
            
            subjectRows.forEach(row => {
                const text = row.querySelector('.searchable-code').textContent.toLowerCase() + ' ' + 
                             row.querySelector('.searchable-name').textContent.toLowerCase();
                
                if (text.includes(term)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Hide empty year/sem blocks
            document.querySelectorAll('.curriculum-sem-block').forEach(semBlock => {
                const visibleRows = Array.from(semBlock.querySelectorAll('.subject-row')).filter(r => r.style.display !== 'none');
                if (visibleRows.length === 0 && term !== '') {
                    semBlock.style.display = 'none';
                } else {
                    semBlock.style.display = '';
                }
            });
            
            document.querySelectorAll('.curriculum-year-block').forEach(yearBlock => {
                const visibleSems = Array.from(yearBlock.querySelectorAll('.curriculum-sem-block')).filter(s => s.style.display !== 'none');
                if (visibleSems.length === 0 && term !== '') {
                    yearBlock.style.display = 'none';
                } else {
                    yearBlock.style.display = '';
                }
            });
        });
    }
});

function openEditSubject(mappingId, code, yl, sem) {
    document.getElementById('edit_sub_mapping_id').value = mappingId;
    document.getElementById('edit_sub_code').textContent = code;
    document.getElementById('edit_sub_yl').value = yl;
    document.getElementById('edit_sub_sem').value = sem;
    new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
}
</script>
