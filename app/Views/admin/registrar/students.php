<?php
require_once __DIR__ . '/../../components/header.php';

$totalCount = count($students);
$collegeCount = 0;
$shsCount = 0;
$enrolledCount = 0;
$approvedCount = 0;

foreach ($students as $s) {
    if (($s['academic_level'] ?? '') === 'College') {
        $collegeCount++;
    } elseif (($s['academic_level'] ?? '') === 'Senior High School') {
        $shsCount++;
    }
    if (($s['status'] ?? '') === 'enrolled') {
        $enrolledCount++;
    } elseif (($s['status'] ?? '') === 'approved') {
        $approvedCount++;
    }
}
$programCount = count($programs);
?>

<style>
/* --- Screen Styling --- */
.stat-card {
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 16px;
    background: #ffffff;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
}
.stat-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
}
.avatar-initials {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.student-id-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
    font-weight: 600;
}
.custom-table th {
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: #64748b;
    font-weight: 700;
    padding-top: 0.85rem;
    padding-bottom: 0.85rem;
}
.custom-table td {
    padding-top: 0.85rem;
    padding-bottom: 0.85rem;
}

/* --- Print-Specific Layout for Official Academic Masterlist --- */
@media screen {
    .print-only { display: none !important; }
}

@media print {
    @page {
        size: landscape;
        margin: 12mm 12mm 15mm 12mm;
    }
    *, ::after, ::before {
        text-shadow: none !important;
        box-shadow: none !important;
    }
    body {
        background: #ffffff !important;
        color: #0f172a !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
        font-size: 9.5pt !important;
        line-height: 1.3 !important;
        padding: 0 !important;
        margin: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .no-print, .no-print * {
        display: none !important;
    }
    .print-only {
        display: block !important;
    }
    main {
        padding: 0 !important;
        background: transparent !important;
    }
    .container-fluid {
        padding: 0 !important;
        max-width: 100% !important;
    }
    .island {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        background: transparent !important;
    }

    /* Print Header & Letterhead */
    .official-letterhead {
        border-bottom: 3px double #1e293b;
        padding-bottom: 12px;
        margin-bottom: 15px;
    }
    .university-crest {
        width: 60px;
        height: 60px;
    }
    .print-meta-grid {
        background-color: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px;
        padding: 10px 14px;
        margin-bottom: 15px;
        font-size: 8.5pt;
    }

    /* Print Table Styling */
    .print-table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin-bottom: 20px !important;
        font-size: 8.5pt !important;
    }
    .print-table thead th {
        background-color: #0f172a !important;
        color: #ffffff !important;
        border: 1px solid #0f172a !important;
        padding: 6px 8px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        font-size: 8pt !important;
        letter-spacing: 0.5px !important;
    }
    .print-table tbody td {
        border: 1px solid #cbd5e1 !important;
        padding: 5px 8px !important;
        vertical-align: middle !important;
    }
    .print-table tbody tr:nth-child(even) td {
        background-color: #f8fafc !important;
    }
    .print-badge {
        font-size: 7.5pt !important;
        padding: 2px 6px !important;
        border: 1px solid #475569 !important;
        border-radius: 4px !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        display: inline-block !important;
    }

    /* Print Signatory Section */
    .print-signatories {
        margin-top: 30px;
        page-break-inside: avoid;
    }
    .signature-line {
        border-bottom: 1.5px solid #0f172a;
        width: 80%;
        margin-top: 40px;
        margin-bottom: 5px;
    }
    .seal-box {
        width: 100px;
        height: 60px;
        border: 1px dashed #94a3b8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 7pt;
        color: #64748b;
        margin: 0 auto;
        text-transform: uppercase;
    }

    /* Footer & Page Numbering */
    .print-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        border-top: 1px solid #e2e8f0;
        padding-top: 6px;
        font-size: 7.5pt;
        color: #64748b;
    }
}
</style>

<div class="no-print">
  <?php require_once __DIR__ . '/../../components/admin_navbar.php'; ?>
</div>

<main class="py-4 py-lg-5 bg-light min-vh-100">
  <div class="container-fluid px-3 px-lg-5">
    
    <!-- ==================== OFFICIAL PRINT-ONLY DOCUMENT LAYOUT ==================== -->
    <div class="print-only">
      <!-- Institutional Letterhead -->
      <div class="official-letterhead text-center">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div style="width: 70px;">
            <img src="/sia/public/images/logo.png" alt="TTU Logo" class="university-crest" onerror="this.style.display='none'">
          </div>
          <div class="flex-grow-1 text-center px-3">
            <div style="font-size: 9pt; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; color: #475569;">Republic of the Philippines</div>
            <div style="font-size: 16pt; font-weight: 800; letter-spacing: 0.5px; color: #0f172a; margin: 2px 0;">TRIPLE T UNIVERSITY</div>
            <div style="font-size: 10.5pt; font-weight: 700; color: #0284c7; letter-spacing: 0.5px; text-transform: uppercase;">Office of the University Registrar & Admissions</div>
            <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">Main Campus, University Parkway, Manila, Philippines • Email: registrar@ttu.edu.ph • Web: www.ttu.edu.ph</div>
          </div>
          <div style="width: 70px; text-align: right;">
            <div style="font-size: 7pt; font-weight: 700; border: 1px solid #0f172a; padding: 4px; border-radius: 4px; display: inline-block; text-align: center;">
              OFFICIAL<br>RECORD
            </div>
          </div>
        </div>
      </div>

      <!-- Document Title & Meta Grid -->
      <div class="text-center mb-3">
        <h4 style="font-size: 13pt; font-weight: 800; text-transform: uppercase; margin: 0; color: #0f172a; letter-spacing: 0.5px;">Official Student Enrollment Masterlist</h4>
        <div style="font-size: 9pt; font-weight: 600; color: #475569;">Academic Year 2026–2027 • First Semester</div>
      </div>

      <div class="print-meta-grid">
        <div class="row g-2">
          <div class="col-4">
            <strong>Date Generated:</strong> <?= date('F j, Y — h:i A') ?>
          </div>
          <div class="col-4 text-center">
            <strong>Scope / Filter:</strong> <span id="printFilterScope">All Departments • All Programs • All Statuses</span>
          </div>
          <div class="col-4 text-end">
            <strong>Total Records:</strong> <span id="printRecordCount"><?= $totalCount ?></span> Active Students
          </div>
        </div>
      </div>

      <!-- Printable Table -->
      <table class="print-table">
        <thead>
          <tr>
            <th style="width: 4%; text-align: center;">#</th>
            <th style="width: 14%;">Student / LRN ID</th>
            <th style="width: 25%;">Student Name</th>
            <th style="width: 12%;">Department</th>
            <th style="width: 11%;">Grade / Year</th>
            <th style="width: 14%;">Program / Strand</th>
            <th style="width: 8%; text-align: center;">Gender</th>
            <th style="width: 12%; text-align: center;">Status</th>
          </tr>
        </thead>
        <tbody id="printTableBody">
          <?php $idx = 1; foreach ($students as $student): ?>
            <?php
              $idDisplay = !empty($student['student_number']) ? $student['student_number'] : (!empty($student['lrn']) ? $student['lrn'] : $student['reference_number']);
              $fullName = $student['last_name'] . ', ' . $student['first_name'];
            ?>
            <tr class="print-row" data-name="<?= htmlspecialchars(strtolower($fullName . ' ' . $idDisplay), ENT_QUOTES, 'UTF-8') ?>" data-level="<?= htmlspecialchars($student['academic_level'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-grade="<?= htmlspecialchars($student['grade_level'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-strand="<?= htmlspecialchars($student['strand'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-status="<?= htmlspecialchars($student['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              <td style="text-align: center; font-weight: 600; color: #475569;"><?= $idx++ ?></td>
              <td style="font-family: monospace; font-weight: 600;"><?= esc($idDisplay) ?></td>
              <td style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($student['academic_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($student['grade_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
              <td style="font-weight: 600;"><?= htmlspecialchars(strtoupper($student['strand'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
              <td style="text-align: center;"><?= htmlspecialchars(ucfirst($student['gender'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
              <td style="text-align: center;">
                <span class="print-badge"><?= strtoupper(formatApplicationStatus($student['status'])) ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Official Signatories Certification -->
      <div class="print-signatories">
        <div class="row text-center">
          <div class="col-4">
            <div style="font-size: 8pt; color: #64748b; text-transform: uppercase;">Prepared & Verified By:</div>
            <div class="signature-line mx-auto"></div>
            <div style="font-size: 9pt; font-weight: 700; text-transform: uppercase; color: #0f172a;">Registrar Records Officer</div>
            <div style="font-size: 7.5pt; color: #64748b;">Office of the University Registrar</div>
          </div>
          <div class="col-4">
            <div style="font-size: 8pt; color: #64748b; text-transform: uppercase;">Certified Correct:</div>
            <div class="signature-line mx-auto"></div>
            <div style="font-size: 9pt; font-weight: 700; text-transform: uppercase; color: #0f172a;">Dr. Eleanor V. Santos, Ed.D.</div>
            <div style="font-size: 7.5pt; color: #64748b;">University Registrar</div>
          </div>
          <div class="col-4">
            <div style="font-size: 8pt; color: #64748b; text-transform: uppercase;">Noted & Approved:</div>
            <div class="signature-line mx-auto"></div>
            <div style="font-size: 9pt; font-weight: 700; text-transform: uppercase; color: #0f172a;">Dr. Arthur M. Dela Cruz, Ph.D.</div>
            <div style="font-size: 7.5pt; color: #64748b;">VP for Academic Affairs</div>
          </div>
        </div>
      </div>

      <!-- Print Security Footer -->
      <div class="print-footer d-flex justify-content-between">
        <div>
          <strong>DOCUMENT SECURITY HASH:</strong> TTU-REG-ML-<?= strtoupper(substr(md5(date('YmdHis') . 'masterlist'), 0, 12)) ?>
        </div>
        <div>
          CONFIDENTIAL • Triple T University Official Document
        </div>
      </div>
    </div>
    <!-- ==================== END PRINT-ONLY DOCUMENT ==================== -->

    <!-- ==================== SCREEN UI ==================== -->
    <div class="no-print">
      
      <!-- Hero Header -->
      <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 fade-in-up">
        <div>
          <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary bg-opacity-10 text-primary fw-semibold small mb-2">
            <i class="bi bi-mortarboard-fill"></i> Office of the University Registrar
          </div>
          <h1 class="h3 fw-bold text-dark mb-1">Official Student Masterlist</h1>
          <p class="text-muted mb-0">Live roster of all officially enrolled and approved students across academic departments.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <a href="students_export.php" id="csvExportBtn" class="btn btn-outline-success fw-semibold shadow-sm rounded-pill px-4 py-2 d-inline-flex align-items-center">
            <i class="bi bi-file-earmark-excel-fill me-2 fs-5"></i> Export CSV
          </a>
          <button type="button" onclick="triggerMasterlistPrint()" class="btn btn-primary fw-semibold shadow-sm rounded-pill px-4 py-2 d-inline-flex align-items-center">
            <i class="bi bi-printer-fill me-2 fs-5"></i> Print Masterlist
          </button>
        </div>
      </div>

      <!-- Quick KPI Stat Cards -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
          <div class="stat-card p-3 p-lg-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted small fw-bold text-uppercase">Total Students</span>
              <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-people-fill"></i>
              </div>
            </div>
            <div class="h3 fw-bold text-dark mb-0"><?= number_format($totalCount) ?></div>
            <div class="small text-muted mt-1">Official masterlist records</div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card p-3 p-lg-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted small fw-bold text-uppercase">College Dept</span>
              <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                <i class="bi bi-mortarboard"></i>
              </div>
            </div>
            <div class="h3 fw-bold text-dark mb-0"><?= number_format($collegeCount) ?></div>
            <div class="small text-muted mt-1">Undergraduate students</div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card p-3 p-lg-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted small fw-bold text-uppercase">Senior High</span>
              <div class="stat-icon-wrapper bg-purple bg-opacity-10 text-purple" style="background: rgba(147, 51, 234, 0.1); color: #9333ea;">
                <i class="bi bi-journal-bookmark-fill"></i>
              </div>
            </div>
            <div class="h3 fw-bold text-dark mb-0"><?= number_format($shsCount) ?></div>
            <div class="small text-muted mt-1">Grades 11 & 12 students</div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card p-3 p-lg-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted small fw-bold text-uppercase">Officially Enrolled</span>
              <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                <i class="bi bi-patch-check-fill"></i>
              </div>
            </div>
            <div class="h3 fw-bold text-dark mb-0"><?= number_format($enrolledCount) ?></div>
            <div class="small text-muted mt-1"><?= number_format($approvedCount) ?> approved for enrollment</div>
          </div>
        </div>
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
        <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 3px;"></div>
        <div class="island-body p-3 p-lg-4">
          <div class="row g-3 align-items-center">
            
            <div class="col-12 col-md-3">
              <label class="form-label small fw-bold text-muted text-uppercase mb-1">Search Student</label>
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="searchName" class="form-control border-start-0 ps-0" placeholder="Name, Student No, or LRN...">
              </div>
            </div>

            <div class="col-6 col-md-2">
              <label class="form-label small fw-bold text-muted text-uppercase mb-1">Level</label>
              <select id="filterLevel" class="form-select">
                <option value="all">All Levels</option>
                <option value="Senior High School">Senior High School</option>
                <option value="College">College</option>
              </select>
            </div>

            <div class="col-6 col-md-2">
              <label class="form-label small fw-bold text-muted text-uppercase mb-1">Grade / Year</label>
              <select id="filterGrade" class="form-select">
                <option value="all">All Grades/Years</option>
                <option value="Grade 11">Grade 11</option>
                <option value="Grade 12">Grade 12</option>
                <option value="1st Year">1st Year</option>
                <option value="2nd Year">2nd Year</option>
                <option value="3rd Year">3rd Year</option>
                <option value="4th Year">4th Year</option>
              </select>
            </div>

            <div class="col-6 col-md-2">
              <label class="form-label small fw-bold text-muted text-uppercase mb-1">Program / Strand</label>
              <select id="filterStrand" class="form-select">
                <option value="all">All Programs</option>
                <?php foreach ($programs as $prog): ?>
                  <option value="<?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars(strtoupper($prog['code']), ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-6 col-md-2">
              <label class="form-label small fw-bold text-muted text-uppercase mb-1">Status</label>
              <select id="filterStatus" class="form-select">
                <option value="all">All Statuses</option>
                <option value="enrolled">Enrolled</option>
                <option value="approved">Approved</option>
              </select>
            </div>

            <div class="col-12 col-md-1 d-flex align-items-end">
              <button type="button" id="btnResetFilters" class="btn btn-outline-secondary w-100" title="Reset All Filters">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
            </div>

          </div>

          <!-- Active Filter Status Indicator -->
          <div class="d-flex align-items-center justify-content-between mt-3 pt-3 border-top small text-muted">
            <div>
              <i class="bi bi-funnel text-primary me-1"></i> Showing <strong id="visibleCount" class="text-dark"><?= $totalCount ?></strong> of <?= $totalCount ?> students
            </div>
            <div id="filterSummaryText" class="text-truncate ps-2 fst-italic">
              Showing all records
            </div>
          </div>

        </div>
      </div>

      <!-- Masterlist Records Table -->
      <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4">
        <div class="island-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 custom-table" id="studentsTable">
              <thead class="bg-light border-bottom">
                <tr>
                  <th scope="col" class="ps-4 py-3" style="width: 5%;">#</th>
                  <th scope="col" class="py-3" style="width: 16%;">ID / Reference</th>
                  <th scope="col" class="py-3" style="width: 25%;">Student Name</th>
                  <th scope="col" class="py-3" style="width: 14%;">Academic Level</th>
                  <th scope="col" class="py-3" style="width: 12%;">Grade/Year</th>
                  <th scope="col" class="py-3" style="width: 10%;">Program</th>
                  <th scope="col" class="py-3" style="width: 8%;">Gender</th>
                  <th scope="col" class="py-3" style="width: 10%;">Status</th>
                  <th scope="col" class="pe-4 py-3 text-end" style="width: 10%;">Actions</th>
                </tr>
              </thead>
              <tbody class="border-top-0">
                <?php if (empty($students)): ?>
                  <tr id="emptyRow">
                    <td colspan="9" class="text-center py-5 text-muted">
                      <div class="d-inline-flex p-4 rounded-circle bg-light mb-3">
                        <i class="bi bi-people fs-1 text-secondary"></i>
                      </div>
                      <h5 class="fw-bold text-dark mb-1">No Students Found</h5>
                      <p class="small text-muted mb-0">There are no approved or enrolled students currently in the masterlist database.</p>
                    </td>
                  </tr>
                <?php else: ?>
                  <tr id="emptyRow" style="display: none;">
                    <td colspan="9" class="text-center py-5 text-muted">
                      <div class="d-inline-flex p-4 rounded-circle bg-light mb-3">
                        <i class="bi bi-search fs-1 text-secondary"></i>
                      </div>
                      <h5 class="fw-bold text-dark mb-1">No Matching Records</h5>
                      <p class="small text-muted mb-0">No students match your search criteria. Try modifying your filter options.</p>
                    </td>
                  </tr>
                  <?php 
                    $rowNum = 1; 
                    $avatarColors = [
                      'bg-primary bg-opacity-10 text-primary',
                      'bg-success bg-opacity-10 text-success',
                      'bg-info bg-opacity-10 text-info',
                      'bg-warning bg-opacity-10 text-warning-emphasis',
                      'bg-danger bg-opacity-10 text-danger',
                    ];
                  ?>
                  <?php foreach ($students as $student): ?>
                    <?php
                      $statusLabel = formatApplicationStatus($student['status']);
                      $badgeClass = getApplicationStatusBadgeClass($student['status']);
                      $idDisplay = !empty($student['student_number']) ? htmlspecialchars($student['student_number'], ENT_QUOTES, 'UTF-8') : (!empty($student['lrn']) ? htmlspecialchars($student['lrn'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($student['reference_number'], ENT_QUOTES, 'UTF-8'));
                      
                      $fInitial = strtoupper(substr($student['first_name'] ?? 'S', 0, 1));
                      $lInitial = strtoupper(substr($student['last_name'] ?? 'N', 0, 1));
                      $colorIdx = (ord($fInitial) + ord($lInitial)) % count($avatarColors);
                      $avatarColorClass = $avatarColors[$colorIdx];
                    ?>
                    <tr class="student-row" 
                        data-level="<?= htmlspecialchars($student['academic_level'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                        data-grade="<?= htmlspecialchars($student['grade_level'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                        data-strand="<?= htmlspecialchars($student['strand'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                        data-status="<?= htmlspecialchars($student['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                        data-name="<?= htmlspecialchars(strtolower($student['last_name'] . ' ' . $student['first_name'] . ' ' . $idDisplay), ENT_QUOTES, 'UTF-8'); ?>">
                      
                      <td class="ps-4 fw-semibold text-muted small"><?= $rowNum++ ?></td>

                      <td>
                        <span class="student-id-mono text-dark"><?= esc($idDisplay) ?></span>
                        <?php if (!empty($student['student_number'])): ?>
                          <span class="badge bg-primary bg-opacity-10 text-primary ms-1" style="font-size: 0.65rem;">Official</span>
                        <?php endif; ?>
                      </td>

                      <td>
                        <div class="d-flex align-items-center gap-2.5">
                          <div class="avatar-initials <?= $avatarColorClass ?>">
                            <?= $fInitial . $lInitial ?>
                          </div>
                          <div>
                            <div class="fw-bold text-dark mb-0">
                              <?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <?php if (!empty($student['contact_number'])): ?>
                              <div class="small text-muted" style="font-size: 0.78rem;">
                                <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($student['contact_number'], ENT_QUOTES, 'UTF-8') ?>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>

                      <td>
                        <span class="badge <?= ($student['academic_level'] ?? '') === 'College' ? 'bg-info bg-opacity-10 text-info border border-info border-opacity-25' : 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' ?> rounded-pill px-2.5 py-1">
                          <?= htmlspecialchars($student['academic_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>

                      <td>
                        <span class="text-dark fw-medium small">
                          <?= htmlspecialchars($student['grade_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>

                      <td>
                        <span class="badge bg-light text-dark border px-2 py-1 fw-semibold">
                          <?= htmlspecialchars(strtoupper($student['strand'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>

                      <td>
                        <?php $gender = strtolower($student['gender'] ?? ''); ?>
                        <span class="text-muted small">
                          <?php if ($gender === 'male'): ?>
                            <i class="bi bi-gender-male text-primary me-1"></i> Male
                          <?php elseif ($gender === 'female'): ?>
                            <i class="bi bi-gender-female text-danger me-1"></i> Female
                          <?php else: ?>
                            <?= htmlspecialchars(ucfirst($student['gender'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                          <?php endif; ?>
                        </span>
                      </td>

                      <td>
                        <span class="badge <?= esc($badgeClass) ?> px-2.5 py-1.5 rounded-pill fw-semibold">
                          <i class="bi <?= ($student['status'] ?? '') === 'enrolled' ? 'bi-patch-check-fill' : 'bi-check2-circle' ?> me-1"></i>
                          <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>

                      <td class="pe-4 text-end">
                        <a href="../admissions/application_detail.php?id=<?= esc($student['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                          <i class="bi bi-person-badge me-1"></i> Profile
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
    <!-- ==================== END SCREEN UI ==================== -->

  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchName = document.getElementById('searchName');
    const filterLevel = document.getElementById('filterLevel');
    const filterGrade = document.getElementById('filterGrade');
    const filterStrand = document.getElementById('filterStrand');
    const filterStatus = document.getElementById('filterStatus');
    const btnResetFilters = document.getElementById('btnResetFilters');
    const csvExportBtn = document.getElementById('csvExportBtn');
    
    const rows = document.querySelectorAll('.student-row');
    const printRows = document.querySelectorAll('.print-row');
    const emptyRow = document.getElementById('emptyRow');
    const visibleCountEl = document.getElementById('visibleCount');
    const filterSummaryText = document.getElementById('filterSummaryText');
    
    const printFilterScope = document.getElementById('printFilterScope');
    const printRecordCount = document.getElementById('printRecordCount');
 
    function filterTable() {
        const query = searchName ? searchName.value.toLowerCase().trim() : '';
        const level = filterLevel ? filterLevel.value : 'all';
        const grade = filterGrade ? filterGrade.value : 'all';
        const strand = filterStrand ? filterStrand.value : 'all';
        const status = filterStatus ? filterStatus.value : 'all';
        let visibleCount = 0;
 
        // Filter Screen Rows
        rows.forEach(row => {
            const rowData = row.dataset.name || '';
            const searchMatch = query === '' || rowData.includes(query);
            const levelMatch = level === 'all' || row.dataset.level === level;
            const gradeMatch = grade === 'all' || row.dataset.grade === grade;
            const strandMatch = strand === 'all' || (row.dataset.strand || '').toLowerCase() === strand.toLowerCase();
            const statusMatch = status === 'all' || row.dataset.status === status;
 
            if (searchMatch && levelMatch && gradeMatch && strandMatch && statusMatch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Synchronize Print Rows
        let printIdx = 1;
        printRows.forEach(pRow => {
            const rowData = pRow.dataset.name || '';
            const searchMatch = query === '' || rowData.includes(query);
            const levelMatch = level === 'all' || pRow.dataset.level === level;
            const gradeMatch = grade === 'all' || pRow.dataset.grade === grade;
            const strandMatch = strand === 'all' || (pRow.dataset.strand || '').toLowerCase() === strand.toLowerCase();
            const statusMatch = status === 'all' || pRow.dataset.status === status;

            if (searchMatch && levelMatch && gradeMatch && strandMatch && statusMatch) {
                pRow.style.display = '';
                const numCell = pRow.querySelector('td:first-child');
                if (numCell) numCell.textContent = printIdx++;
            } else {
                pRow.style.display = 'none';
            }
        });
 
        if (emptyRow && rows.length > 0) {
            emptyRow.style.display = visibleCount === 0 ? '' : 'none';
        }

        if (visibleCountEl) {
            visibleCountEl.textContent = visibleCount;
        }
        if (printRecordCount) {
            printRecordCount.textContent = visibleCount;
        }

        // Summary Scope Description
        const scopeParts = [];
        if (level !== 'all') scopeParts.push('Level: ' + level);
        if (grade !== 'all') scopeParts.push('Grade/Year: ' + grade);
        if (strand !== 'all') scopeParts.push('Program: ' + strand.toUpperCase());
        if (status !== 'all') scopeParts.push('Status: ' + status.toUpperCase());
        if (query !== '') scopeParts.push('Search: "' + query + '"');

        const scopeString = scopeParts.length > 0 ? scopeParts.join(' • ') : 'All Departments • All Programs • All Statuses';
        
        if (filterSummaryText) {
            filterSummaryText.textContent = scopeParts.length > 0 ? 'Active filters: ' + scopeString : 'Showing all records';
        }
        if (printFilterScope) {
            printFilterScope.textContent = scopeString;
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

    if (btnResetFilters) {
        btnResetFilters.addEventListener('click', function() {
            if (searchName) searchName.value = '';
            if (filterLevel) filterLevel.value = 'all';
            if (filterGrade) filterGrade.value = 'all';
            if (filterStrand) filterStrand.value = 'all';
            if (filterStatus) filterStatus.value = 'all';
            filterTable();
        });
    }

    // Run filter immediately to set CSV Export link values on page load
    filterTable();
});

function triggerMasterlistPrint() {
    window.print();
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>



