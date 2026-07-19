<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('shs_sections.manage');

// Handle actions (Activate/Deactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['section_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['admin_error'] = 'Invalid CSRF token.';
    } else {
        $sectionId = (int)$_POST['section_id'];
        if ($_POST['action'] === 'delete_section') {
            try {
                $pdo->prepare('DELETE FROM shs_section_subjects WHERE shs_section_id = ?')->execute([$sectionId]);
                $stmtDel = $pdo->prepare('DELETE FROM shs_sections WHERE id = ?');
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
            $stmtOld = $pdo->prepare('SELECT status, section_code FROM shs_sections WHERE id = :id');
            $stmtOld->execute(['id' => $sectionId]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

            if ($oldData) {
                $stmt = $pdo->prepare('UPDATE shs_sections SET status = IF(status=1, 0, 1) WHERE id = :id');
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
    header('Location: shs_sections.php');
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_section') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['admin_error'] = 'Invalid CSRF token.';
    } else {
        $section_code = trim($_POST['section_code'] ?? '');
        $strand_id = (int)($_POST['strand_id'] ?? 0);
        $grade_level = trim($_POST['grade_level'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 40);
        $schedule_type = trim($_POST['schedule_type'] ?? 'Morning');
        $adviser = trim($_POST['adviser'] ?? '');
        
        if ($section_code && $strand_id && $grade_level && $academic_year) {
            try {
                $stmt = $pdo->prepare('INSERT INTO shs_sections (section_code, strand_id, grade_level, academic_year, capacity, schedule_type, adviser, status) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
                $stmt->execute([$section_code, $strand_id, $grade_level, $academic_year, $capacity, $schedule_type, $adviser]);
                $newSectionId = (int)$pdo->lastInsertId();
                
                // Auto-import curriculum to shs_section_subjects
                $currStmt = $pdo->prepare('
                    SELECT subject_id 
                    FROM shs_curriculum 
                    WHERE strand_id = ? AND grade_level = ? 
                ');
                $currStmt->execute([$strand_id, $grade_level]);
                $subjects = $currStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($subjects)) {
                    $insSub = $pdo->prepare("INSERT INTO shs_section_subjects (shs_section_id, subject_id, capacity, day, start_time, end_time) VALUES (?, ?, ?, 'TBA', '00:00:00', '00:00:00')");
                    foreach ($subjects as $sub) {
                        $insSub->execute([$newSectionId, $sub['subject_id'], $capacity]);
                    }
                }
                
                $_SESSION['admin_success'] = 'Section added and curriculum subjects imported successfully.';
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
    header('Location: shs_sections.php');
    exit;
}

try {
    $query = "
        SELECT 
            s.*, 
            p.code as program_code,
            (SELECT COUNT(*) FROM applications a WHERE a.section_id = s.id AND a.status != 'rejected') as current_enrollment
        FROM shs_sections s
        INNER JOIN shs_strands p ON p.id = s.strand_id
        ORDER BY p.code ASC, s.grade_level ASC, s.section_code ASC
    ";
    $stmt = $pdo->query($query);
    $shs_sections = $stmt->fetchAll();
    
    // Fetch programs for Add Section modal
    $progStmt = $pdo->query("SELECT id, code, name FROM shs_strands WHERE is_active = 1 ORDER BY code ASC");
    $programs = $progStmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching shs_sections: ' . $e->getMessage());
    $shs_sections = [];
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
          <p class="text-muted mb-0">Manage student shs_sections and monitoring capacity</p>
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
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-list-ul me-2 text-primary"></i>All SHS Sections</h5>
        <div class="input-group shadow-sm" style="width: 250px;">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" id="sectionSearch" class="form-control border-start-0" placeholder="Search shs_sections...">
        </div>
      </div>
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light text-muted small text-uppercase">
              <tr>
                <th class="ps-4">Section Code</th>
                <th>Program</th>
                <th>Grade Level</th>
                <th>Academic Year</th>
                <th>Schedule</th>
                <th>Adviser</th>
                <th>Capacity</th>
                <th>Status</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($shs_sections)): ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No shs_sections found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($shs_sections as $s): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($s['section_code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle"><?= htmlspecialchars($s['program_code'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td>
                      <div class="fw-bold"><?= htmlspecialchars($s['grade_level'], ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['academic_year'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
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
                      <a href="schedule_builder.php?type=shs&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill me-1" title="Manage Schedule">
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
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="shs_sections.php" class="needs-validation" novalidate>
        <div class="modal-header bg-primary text-white border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Add New Section</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 bg-light">
          <input type="hidden" name="action" value="add_section">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Section Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="section_code" required placeholder="e.g., BSIT-1A">
            <div class="invalid-feedback">Please provide a section code.</div>
          </div>
          
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Academic Program <span class="text-danger">*</span></label>
            <select class="form-select" name="strand_id" id="programSelect" required>
              <option value="" selected disabled>Select a program...</option>
              <?php foreach ($programs as $prog): ?>
                <option value="<?= $prog['id'] ?>" data-category="Senior High School">
                  <?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Please select an academic program.</div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label text-muted small fw-bold">Grade Level <span class="text-danger">*</span></label>
              <select class="form-select" name="grade_level" required>
                <option value="Grade 11">Grade 11</option>
                <option value="Grade 12">Grade 12</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label text-muted small fw-bold">Academic Year <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="academic_year" required placeholder="e.g. 2026-2027" pattern="\d{4}-\d{4}">
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Capacity <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="capacity" value="40" required min="1">
            <div class="invalid-feedback">Please specify a valid capacity (minimum 1).</div>
          </div>
          
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Schedule Type <span class="text-danger">*</span></label>
            <select class="form-select" name="schedule_type" required>
              <option value="Morning" selected>Morning</option>
              <option value="Afternoon">Afternoon</option>
            </select>
            <div class="invalid-feedback">Please select a schedule type.</div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
    

});

function setDeleteSection(id, code) {
    document.getElementById('deleteSectionId').value = id;
    document.getElementById('deleteSectionCode').textContent = code;
}

</script>
</body>
</html>

