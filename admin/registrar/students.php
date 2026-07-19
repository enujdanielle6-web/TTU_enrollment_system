<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('students.view');

$pageTitle = 'Student Records - Administrator';

try {
    $stmt = $pdo->query('
        SELECT 
            a.id, 
            a.reference_number, 
            a.lrn,
            a.status, 
            a.academic_level,
            a.strand, 
            a.grade_level,
            a.gender,
            a.contact_number,
            u.first_name, 
            u.last_name,
            u.student_number
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        WHERE a.status IN ("approved", "enrolled")
        ORDER BY a.grade_level ASC, a.strand ASC, u.last_name ASC
    ');
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Admin student list failed: ' . $e->getMessage());
    $students = [];
}

// Fetch programs for filter
$programs = [];
try {
    $progStmt = $pdo->query('
        SELECT code, name FROM college_programs WHERE is_active = 1 
        UNION ALL 
        SELECT code, name FROM shs_strands WHERE is_active = 1 
        ORDER BY code ASC
    ');
    $programs = $progStmt->fetchAll();
} catch (PDOException $e) {
    error_log('Failed to fetch programs: ' . $e->getMessage());
}

require_once __DIR__ . '/../../components/header.php';
?>

<style>
/* Print-specific layout for official roster */
@media print {
    body { background-color: #fff !important; }
    .main-navbar, .no-print { display: none !important; }
    .island { border: none !important; box-shadow: none !important; padding: 0 !important; }
    .container-fluid { padding: 0 !important; }
    .table { width: 100% !important; border-collapse: collapse !important; }
    .table th, .table td { border: 1px solid #ddd !important; padding: 8px !important; }
    .badge { border: 1px solid #000; color: #000 !important; background: transparent !important; }
    
    /* Header for printed page */
    .print-header { display: block !important; text-align: center; margin-bottom: 20px; }
    .print-header h2 { margin: 0; font-size: 24px; font-weight: bold; }
    .print-header p { margin: 0; font-size: 14px; color: #555; }
}
.print-header { display: none; }
</style>

<?php require_once __DIR__ . '/../components/navbar.php'; ?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="print-header">
      <h2>Official Student Masterlist</h2>
      <p>Generated on <?= date('F j, Y') ?></p>
    </div>

    <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 no-print">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Student Records</h1>
        <p class="text-muted mb-0">Masterlist of officially enrolled and approved students.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="students_export.php" id="csvExportBtn" class="btn btn-outline-success fw-medium shadow-sm rounded-12 px-4">
          <i class="bi bi-file-earmark-spreadsheet-fill me-2"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-primary fw-medium shadow-sm rounded-12 px-4">
          <i class="bi bi-printer-fill me-2"></i> Print Masterlist
        </button>
      </div>
    </div>

    <!-- Filters Panel -->
    <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4" style="border-radius: 16px;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-body py-3">
        <div class="row g-3 align-items-center">
          <div class="col-md-auto fw-semibold text-muted small text-uppercase">
            <i class="bi bi-funnel-fill me-1"></i> Filter By:
          </div>
          <div class="col-md-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
              <input type="text" id="searchName" class="form-control form-control-sm" placeholder="Search name or ID...">
            </div>
          </div>
          <div class="col-md-2">
            <select id="filterLevel" class="form-select form-select-sm">
              <option value="all">All Levels</option>
              <option value="Senior High School">Senior High School</option>
              <option value="College">College</option>
            </select>
          </div>
          <div class="col-md-2">
            <select id="filterGrade" class="form-select form-select-sm">
              <option value="all">All Grades/Years</option>
              <option value="Grade 11">Grade 11</option>
              <option value="Grade 12">Grade 12</option>
              <option value="1st Year">1st Year</option>
              <option value="2nd Year">2nd Year</option>
              <option value="3rd Year">3rd Year</option>
              <option value="4th Year">4th Year</option>
            </select>
          </div>
          <div class="col-md-2">
            <select id="filterStrand" class="form-select form-select-sm">
              <option value="all">All Programs</option>
              <?php foreach ($programs as $prog): ?>
                <option value="<?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars(strtoupper($prog['code']), ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <select id="filterStatus" class="form-select form-select-sm">
              <option value="all">All Statuses</option>
              <option value="approved">Approved</option>
              <option value="enrolled">Enrolled</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table" id="studentsTable">
            <thead class="table-light text-uppercase small text-muted">
              <tr>
                <th scope="col" class="ps-4 py-3 fw-semibold">ID / LRN</th>
                <th scope="col" class="py-3 fw-semibold">Student Name</th>
                <th scope="col" class="py-3 fw-semibold">Level</th>
                <th scope="col" class="py-3 fw-semibold">Grade/Year</th>
                <th scope="col" class="py-3 fw-semibold">Program</th>
                <th scope="col" class="py-3 fw-semibold">Gender</th>
                <th scope="col" class="py-3 fw-semibold">Status</th>
                <th scope="col" class="pe-4 py-3 text-end fw-semibold no-print">Profile</th>
              </tr>
            </thead>
            <tbody class="border-top-0">
              <?php if (empty($students)): ?>
                <tr id="emptyRow">
                  <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-people fs-1 d-block mb-3"></i>
                    No enrolled students found in the system.
                  </td>
                </tr>
              <?php else: ?>
                <tr id="emptyRow" style="display: none;">
                  <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-search fs-1 d-block mb-3"></i>
                    No students match your selected filters.
                  </td>
                </tr>
                <?php foreach ($students as $student): ?>
                  <?php
                    $statusLabel = formatApplicationStatus($student['status']);
                    $badgeClass = getApplicationStatusBadgeClass($student['status']);
                    $idDisplay = !empty($student['student_number']) ? htmlspecialchars($student['student_number'], ENT_QUOTES, 'UTF-8') : (!empty($student['lrn']) ? htmlspecialchars($student['lrn'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($student['reference_number'], ENT_QUOTES, 'UTF-8'));
                  ?>
                  <tr class="student-row" data-level="<?= htmlspecialchars($student['academic_level'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-grade="<?= htmlspecialchars($student['grade_level'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-strand="<?= htmlspecialchars($student['strand'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-status="<?= htmlspecialchars($student['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-name="<?= htmlspecialchars(strtolower($student['last_name'] . ' ' . $student['first_name'] . ' ' . $idDisplay), ENT_QUOTES, 'UTF-8'); ?>">
                    <td class="ps-4 fw-medium text-dark small"><?= $idDisplay; ?></td>
                    <td>
                      <div class="fw-semibold text-dark"><?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </td>
                    <td><span class="text-muted small"><?= htmlspecialchars($student['academic_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><span class="text-muted small"><?= htmlspecialchars($student['grade_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><span class="text-muted small"><?= htmlspecialchars(strtoupper($student['strand'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><span class="text-muted small"><?= htmlspecialchars(ucfirst($student['gender'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td>
                      <span class="badge <?= $badgeClass; ?> px-2 py-1 rounded-pill small">
                        <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                    </td>
                    <td class="pe-4 text-end no-print">
                      <a href="../admissions/application_detail.php?id=<?= $student['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-person-vcard me-1"></i> View
                      </a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchName = document.getElementById('searchName');
    const filterLevel = document.getElementById('filterLevel');
    const filterGrade = document.getElementById('filterGrade');
    const filterStrand = document.getElementById('filterStrand');
    const filterStatus = document.getElementById('filterStatus');
    const csvExportBtn = document.getElementById('csvExportBtn');
    const rows = document.querySelectorAll('.student-row');
    const emptyRow = document.getElementById('emptyRow');
 
    function filterTable() {
        const query = searchName ? searchName.value.toLowerCase() : '';
        const level = filterLevel ? filterLevel.value : 'all';
        const grade = filterGrade ? filterGrade.value : 'all';
        const strand = filterStrand ? filterStrand.value : 'all';
        const status = filterStatus ? filterStatus.value : 'all';
        let visibleCount = 0;
 
        rows.forEach(row => {
            const rowData = row.dataset.name || '';
            const searchMatch = query === '' || rowData.includes(query);
            const levelMatch = level === 'all' || row.dataset.level === level;
            const gradeMatch = grade === 'all' || row.dataset.grade === grade;
            const strandMatch = strand === 'all' || row.dataset.strand.toLowerCase() === strand.toLowerCase();
            const statusMatch = status === 'all' || row.dataset.status === status;
 
            if (searchMatch && levelMatch && gradeMatch && strandMatch && statusMatch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
 
        if (emptyRow && rows.length > 0) {
            emptyRow.style.display = visibleCount === 0 ? '' : 'none';
        }

        // Dynamically build the CSV Export query string parameters matching active filters
        if (csvExportBtn) {
            csvExportBtn.href = `students_export.php?search=${encodeURIComponent(query)}&level=${encodeURIComponent(level)}&grade=${encodeURIComponent(grade)}&strand=${encodeURIComponent(strand)}&status=${encodeURIComponent(status)}`;
        }
    }
 
    if (searchName) searchName.addEventListener('input', filterTable);
    if (filterLevel) filterLevel.addEventListener('change', filterTable);
    if (filterGrade) filterGrade.addEventListener('change', filterTable);
    if (filterStrand) filterStrand.addEventListener('change', filterTable);
    if (filterStatus) filterStatus.addEventListener('change', filterTable);

    // Run filter immediately to set CSV Export link values on page load
    filterTable();
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

