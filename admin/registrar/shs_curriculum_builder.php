<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('shs_curriculum.manage');

$strandId = (int)($_GET['strand_id'] ?? 0);
if ($strandId <= 0) {
    header('Location: shs_curriculum.php');
    exit;
}

// Fetch strand metadata
try {
    $stmt = $pdo->prepare("SELECT * FROM shs_strands WHERE id = ?");
    $stmt->execute([$strandId]);
    $strand = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching strand: " . $e->getMessage());
}

if (!$strand) {
    header('Location: shs_curriculum.php');
    exit;
}

$pageTitle = htmlspecialchars($strand['name']) . ' - SHS Curriculum Builder';

// Initialize the curriculum structure for SHS
$subjects = [
    'Grade 11' => [
        'First' => [],
        'Second' => []
    ],
    'Grade 12' => [
        'First' => [],
        'Second' => []
    ]
];

$totalUnits = 0;
$totalSubjects = 0;
$lectureUnits = 0;
$labUnits = 0;

try {
    $stmt = $pdo->prepare("
        SELECT c.id as mapping_id, c.grade_level, c.semester,
               s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type
        FROM shs_curriculum c
        INNER JOIN subjects s ON c.subject_id = s.id
        WHERE c.strand_id = ?
        ORDER BY c.grade_level ASC, c.semester ASC, s.subject_code ASC
    ");
    $stmt->execute([$strandId]);
    $subjectsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($subjectsRaw as $row) {
        $gl = $row['grade_level'];
        $sem = $row['semester'];
        
        // Safety check to ensure it goes into a valid bucket
        if (!isset($subjects[$gl])) {
            $subjects[$gl] = [];
        }
        if (!isset($subjects[$gl][$sem])) {
            $subjects[$gl][$sem] = [];
        }
        
        $subjects[$gl][$sem][] = $row;
        $totalUnits += (int)$row['units'];
        $totalSubjects++;
        
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
    $gstmt = $pdo->query("SELECT id, subject_code, subject_name, units, subject_type FROM subjects WHERE status = 1 AND education_level IN ('SHS', 'Both') ORDER BY subject_code ASC");
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
          <a href="shs_curriculum.php" class="btn btn-sm btn-light text-primary mb-2 fw-medium rounded-pill px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Curricula
          </a>
          <h1 class="h3 fw-bold text-dark mb-1"><?= htmlspecialchars($strand['code']) ?> Curriculum</h1>
          <p class="text-muted mb-0">
            <?= htmlspecialchars($strand['name']) ?> | 
            <?php if ($strand['is_active'] == 1): ?>
                <span class="text-success fw-medium"><i class="bi bi-check-circle-fill me-1"></i>Active</span>
            <?php else: ?>
                <span class="text-secondary fw-medium"><i class="bi bi-circle me-1"></i>Inactive</span>
            <?php endif; ?>
          </p>
        </div>
        <div>
          <button class="btn btn-primary fw-medium shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addSubjectModal" onclick="openAddSubjectModalGlobal()">
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
                        <span class="fw-bold text-dark fs-5"><?= $totalSubjects ?></span>
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
            <?php foreach (['Grade 11', 'Grade 12'] as $gl): ?>
                <div class="mb-5 curriculum-year-block">
                    <h4 class="fw-bold text-dark mb-3 border-bottom pb-2 border-2 border-primary d-inline-block"><?= htmlspecialchars($gl) ?></h4>
                    
                    <?php foreach (['First', 'Second'] as $sem): ?>
                        <div class="island border-0 shadow-sm rounded-4 mb-4 curriculum-sem-block">
                            <div class="island-header bg-light border-bottom border-light d-flex justify-content-between align-items-center py-2">
                                <h6 class="mb-0 fw-bold text-secondary text-uppercase tracking-wide small"><i class="bi bi-calendar-event me-2"></i><?= htmlspecialchars($sem) ?> Semester</h6>
                                <div>
                                    <span class="badge bg-secondary rounded-pill me-2"><?= count($subjects[$gl][$sem] ?? []) ?> subjects</span>
                                </div>
                            </div>
                            <div class="island-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-borderless align-middle mb-0 builder-table">
                                        <thead class="border-bottom text-muted small text-uppercase">
                                            <tr>
                                                <th class="ps-4" style="width: 20%">Code</th>
                                                <th style="width: 45%">Title</th>
                                                <th style="width: 10%">Type</th>
                                                <th style="width: 10%">Units</th>
                                                <th class="text-end pe-4" style="width: 15%">Manage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($subjects[$gl][$sem])): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-4 text-muted small fst-italic">No subjects added to this semester yet.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($subjects[$gl][$sem] as $sub): ?>
                                                    <tr class="subject-row border-bottom border-light">
                                                        <td class="ps-4 fw-bold text-dark searchable-code"><?= htmlspecialchars($sub['subject_code']) ?></td>
                                                        <td class="searchable-name"><?= htmlspecialchars($sub['subject_name']) ?></td>
                                                        <td><span class="badge bg-light text-secondary border"><?= htmlspecialchars($sub['subject_type'] ?? 'Lecture') ?></span></td>
                                                        <td class="fw-medium text-success"><?= $sub['units'] ?></td>
                                                        <td class="text-end pe-4">
                                                            <form action="shs_curriculum_process.php" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($sub['subject_code'])) ?> from this curriculum?');">
                                                                <input type="hidden" name="action" value="delete_subject">
                                                                <input type="hidden" name="strand_id" value="<?= $strandId ?>">
                                                                <input type="hidden" name="mapping_id" value="<?= $sub['mapping_id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle px-2 py-1 ms-1" title="Remove Subject">
                                                                    <i class="bi bi-trash-fill"></i>
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
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
  </div>
</main>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="shs_curriculum_process.php" method="POST">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="strand_id" value="<?= $strandId ?>">
        
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
                     <input class="form-check-input flex-shrink-0 fs-5 mt-0" type="checkbox" name="subject_ids[]" value="<?= $sub['id'] ?>">
                     <div class="w-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="fw-bold text-dark modal-sub-code"><?= htmlspecialchars($sub['subject_code']) ?></div>
                            <span class="badge bg-light text-secondary border"><?= htmlspecialchars($sub['subject_type'] ?? 'Lecture') ?></span>
                        </div>
                        <div class="modal-sub-name text-muted small"><?= htmlspecialchars($sub['subject_name']) ?></div>
                        <div class="small fw-medium text-success mt-1"><?= $sub['units'] ?> units</div>
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

document.addEventListener('DOMContentLoaded', function() {
    // Builder search
    const builderSearch = document.getElementById('builderSearch');
    if (builderSearch) {
        builderSearch.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
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
        });
    }

    // Modal subject search
    const modalSearch = document.getElementById('modalSubjectSearch');
    if (modalSearch) {
        modalSearch.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
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

<?php require_once __DIR__ . '/../components/footer.php'; ?>
