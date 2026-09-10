<?php
require_once __DIR__ . '/../../components/header.php';

// Global KPI counts passed from RegistrarController:
$totalCount = $totalCount ?? 0;
$collegeCount = $collegeCount ?? 0;
$shsCount = $shsCount ?? 0;
$officialIdCount = $officialIdCount ?? 0;
$programCount = count($programs ?? []);

// Pagination variables
$page = $page ?? 1;
$perPage = $perPage ?? 25;
$totalPages = $totalPages ?? 1;
$totalFiltered = $totalFiltered ?? count($students ?? []);
$startRecord = $startRecord ?? ($totalFiltered > 0 ? 1 : 0);
$endRecord = $endRecord ?? count($students ?? []);

// Active filter state
$curSearch = $filters['search'] ?? '';
$curLevel = $filters['level'] ?? 'all';
$curGrade = $filters['grade'] ?? 'all';
$curStrand = $filters['strand'] ?? 'all';

$buildPageUrl = function($p, $pp = null) use ($filters, $perPage) {
    $params = $filters;
    $params['page'] = $p;
    $params['per_page'] = $pp ?? $perPage;
    return 'students.php?' . http_build_query($params);
};

$activeSummaries = [];
if ($curLevel !== 'all' && $curLevel !== '') $activeSummaries[] = 'Level: ' . $curLevel;
if ($curGrade !== 'all' && $curGrade !== '') $activeSummaries[] = 'Grade: ' . $curGrade;
if ($curStrand !== 'all' && $curStrand !== '') $activeSummaries[] = 'Program: ' . strtoupper($curStrand);
if ($curSearch !== '') $activeSummaries[] = 'Search: "' . $curSearch . '"';
$activeScopeText = !empty($activeSummaries) ? implode(' • ', $activeSummaries) : 'All Enrolled Students • All Departments • All Programs';

$exportQuery = http_build_query([
    'search' => $curSearch,
    'level' => $curLevel,
    'grade' => $curGrade,
    'strand' => $curStrand
]);
?>

<?php require_once __DIR__ . '/../../components/admin_navbar.php'; ?>

<main class="py-5 bg-light min-vh-100">
  <style>
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
      .no-print, .no-print *, .admin-sidebar, #adminSidebar, .admin-main > .sticky-top, .sidebar-minimize-btn {
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

  <div class="container-fluid px-lg-5">
    
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
            <strong>Scope / Filter:</strong> <span id="printFilterScope"><?= htmlspecialchars($activeScopeText, ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <div class="col-4 text-end">
            <strong>Total Records:</strong> <span id="printRecordCount"><?= number_format($totalFiltered) ?></span> Active Students (Page <?= $page ?> of <?= $totalPages ?>)
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
          <?php $idx = $startRecord; foreach ($students as $student): ?>
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
          <p class="text-muted mb-0">Live roster of all officially enrolled students across academic departments.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <a href="students_export.php?<?= $exportQuery ?>" id="csvExportBtn" class="btn btn-outline-success fw-semibold shadow-sm rounded-pill px-4 py-2 d-inline-flex align-items-center">
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
              <span class="text-muted small fw-bold text-uppercase">Total Enrolled</span>
              <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-people-fill"></i>
              </div>
            </div>
            <div class="h3 fw-bold text-dark mb-0"><?= number_format($totalCount) ?></div>
            <div class="small text-muted mt-1">Enrolled masterlist records</div>
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
            <div class="small text-muted mt-1">Enrolled undergraduates</div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card p-3 p-lg-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted small fw-bold text-uppercase">Senior High</span>
              <div class="stat-icon-wrapper bg-secondary bg-opacity-10 text-secondary">
                <i class="bi bi-journal-bookmark-fill"></i>
              </div>
            </div>
            <div class="h3 fw-bold text-dark mb-0"><?= number_format($shsCount) ?></div>
            <div class="small text-muted mt-1">Enrolled Grades 11 & 12</div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card p-3 p-lg-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted small fw-bold text-uppercase">Official Student IDs</span>
              <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                <i class="bi bi-patch-check-fill"></i>
              </div>
            </div>
            <div class="h3 fw-bold text-dark mb-0"><?= number_format($officialIdCount) ?></div>
            <div class="small text-muted mt-1">Assigned institutional numbers</div>
          </div>
        </div>
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
        <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
        <div class="island-body p-3 p-lg-4">
          <form method="GET" action="students.php" id="filterForm">
            <input type="hidden" name="page" value="1">
            <input type="hidden" name="per_page" id="formPerPage" value="<?= esc($perPage) ?>">

            <div class="row g-3 align-items-center">
              
              <div class="col-12 col-md-4">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Search Student</label>
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                  <input type="text" name="search" id="searchName" class="form-control border-start-0 ps-0" placeholder="Name, Student No, or LRN..." value="<?= esc($curSearch) ?>">
                </div>
              </div>

              <div class="col-6 col-md-2">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Level</label>
                <select name="level" id="filterLevel" class="form-select" onchange="this.form.submit()">
                  <option value="all" <?= $curLevel === 'all' ? 'selected' : '' ?>>All Levels</option>
                  <option value="Senior High School" <?= $curLevel === 'Senior High School' ? 'selected' : '' ?>>Senior High School</option>
                  <option value="College" <?= $curLevel === 'College' ? 'selected' : '' ?>>College</option>
                </select>
              </div>

              <div class="col-6 col-md-2">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Grade / Year</label>
                <select name="grade" id="filterGrade" class="form-select" onchange="this.form.submit()">
                  <option value="all" <?= $curGrade === 'all' ? 'selected' : '' ?>>All Grades/Years</option>
                  <option value="Grade 11" <?= $curGrade === 'Grade 11' ? 'selected' : '' ?>>Grade 11</option>
                  <option value="Grade 12" <?= $curGrade === 'Grade 12' ? 'selected' : '' ?>>Grade 12</option>
                  <option value="1st Year" <?= $curGrade === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                  <option value="2nd Year" <?= $curGrade === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                  <option value="3rd Year" <?= $curGrade === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                  <option value="4th Year" <?= $curGrade === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                </select>
              </div>

              <div class="col-6 col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Program / Strand</label>
                <select name="strand" id="filterStrand" class="form-select" onchange="this.form.submit()">
                  <option value="all" <?= $curStrand === 'all' ? 'selected' : '' ?>>All Programs</option>
                  <?php foreach ($programs as $prog): ?>
                    <option value="<?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?>" <?= strtolower($curStrand) === strtolower($prog['code']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars(strtoupper($prog['code']), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-md-1 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-primary w-50" title="Apply Filters">
                  <i class="bi bi-funnel-fill"></i>
                </button>
                <a href="students.php" id="btnResetFilters" class="btn btn-outline-secondary w-50" title="Reset All Filters">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </a>
              </div>

            </div>
          </form>

          <!-- Active Filter Status Indicator -->
          <div class="d-flex align-items-center justify-content-between mt-3 pt-3 border-top small text-muted">
            <div>
              <i class="bi bi-funnel text-primary me-1"></i> Showing <strong id="visibleCount" class="text-dark"><?= $startRecord ?>–<?= $endRecord ?></strong> of <strong class="text-dark"><?= number_format($totalFiltered) ?></strong> students
              <?php if ($totalFiltered < $totalCount): ?>
                <span class="text-muted fst-italic ms-1">(filtered from <?= number_format($totalCount) ?> total)</span>
              <?php endif; ?>
            </div>
            <div id="filterSummaryText" class="text-truncate ps-2 fst-italic">
              <?= htmlspecialchars(!empty($activeSummaries) ? 'Active filters: ' . $activeScopeText : 'Showing all records', ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>

        </div>
      </div>

      <!-- Masterlist Records Table -->
      <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4">
        <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
        <div class="island-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 custom-table" id="studentsTable">
              <thead class="table-light text-muted small text-uppercase">
                <tr>
                  <th scope="col" class="ps-4 py-3 fw-semibold" style="width: 50px;">#</th>
                  <th scope="col" class="py-3 fw-semibold" style="width: 170px;">ID / Reference</th>
                  <th scope="col" class="py-3 fw-semibold">Student Name</th>
                  <th scope="col" class="py-3 fw-semibold" style="width: 140px;">Academic Level</th>
                  <th scope="col" class="py-3 fw-semibold" style="width: 120px;">Grade / Year</th>
                  <th scope="col" class="py-3 fw-semibold" style="width: 110px;">Program</th>
                  <th scope="col" class="py-3 fw-semibold" style="width: 90px;">Gender</th>
                  <th scope="col" class="py-3 fw-semibold" style="width: 130px;">Status</th>
                  <th scope="col" class="pe-4 py-3 text-end fw-semibold" style="width: 110px;">Actions</th>
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
                      <p class="small text-muted mb-0">There are no officially enrolled students currently in the masterlist database.</p>
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
                    $rowNum = $startRecord; 
                  ?>
                  <?php foreach ($students as $student): ?>
                    <?php
                      $statusLabel = formatApplicationStatus($student['status']);
                      $badgeClass = getApplicationStatusBadgeClass($student['status']);
                      $idDisplay = !empty($student['student_number']) ? htmlspecialchars($student['student_number'], ENT_QUOTES, 'UTF-8') : (!empty($student['lrn']) ? htmlspecialchars($student['lrn'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($student['reference_number'], ENT_QUOTES, 'UTF-8'));
                      
                      $fInitial = strtoupper(substr($student['first_name'] ?? 'S', 0, 1));
                      $lInitial = strtoupper(substr($student['last_name'] ?? 'N', 0, 1));

                      $isCollege = (($student['academic_level'] ?? '') === 'College');
                    ?>
                    <tr class="student-row" 
                        data-level="<?= htmlspecialchars($student['academic_level'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                        data-grade="<?= htmlspecialchars($student['grade_level'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                        data-strand="<?= htmlspecialchars($student['strand'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                        data-status="<?= htmlspecialchars($student['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                        data-name="<?= htmlspecialchars(strtolower($student['last_name'] . ' ' . $student['first_name'] . ' ' . $idDisplay), ENT_QUOTES, 'UTF-8'); ?>">
                      
                      <td class="ps-4 fw-semibold text-muted small"><?= $rowNum++ ?></td>

                      <td>
                        <div class="d-inline-flex align-items-center gap-1">
                          <span class="student-id-mono text-dark fw-bold"><?= esc($idDisplay) ?></span>
                          <?php if (!empty($student['student_number'])): ?>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-0.5 small ms-1" style="font-size: 0.7rem;">
                              <i class="bi bi-patch-check-fill me-0.5"></i>Official
                            </span>
                          <?php endif; ?>
                        </div>
                      </td>

                      <td>
                        <div class="d-flex align-items-center gap-2.5">
                          <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary fw-bold" style="width: 36px; height: 36px; font-size: 0.8rem; flex-shrink: 0;">
                            <?= $fInitial . $lInitial ?>
                          </div>
                          <div>
                            <div class="fw-semibold text-dark mb-0">
                              <?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <?php if (!empty($student['contact_number'])): ?>
                              <div class="text-muted small" style="font-size: 0.78rem;">
                                <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($student['contact_number'], ENT_QUOTES, 'UTF-8') ?>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>

                      <td>
                        <?php if ($isCollege): ?>
                          <span class="badge bg-light text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1">
                            College
                          </span>
                        <?php else: ?>
                          <span class="badge bg-light text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-1">
                            Senior High School
                          </span>
                        <?php endif; ?>
                      </td>

                      <td>
                        <span class="text-muted small">
                          <?= htmlspecialchars($student['grade_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>

                      <td>
                        <span class="badge bg-light text-dark border px-2 py-1 fw-semibold">
                          <?= htmlspecialchars(strtoupper($student['strand'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>

                      <td>
                        <span class="text-muted small"><?= htmlspecialchars(ucfirst($student['gender'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></span>
                      </td>

                      <td>
                        <span class="badge <?= esc($badgeClass) ?> px-2.5 py-1 rounded-pill small">
                          <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>

                      <td class="pe-4 text-end">
                        <a href="../admissions/application_detail.php?id=<?= esc($student['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                          Profile <i class="bi bi-arrow-right-short"></i>
                        </a>
                      </td>

                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Server-Side Pagination Bar -->
          <div class="d-flex flex-column flex-md-row align-items-center justify-content-between p-3 p-lg-4 border-top gap-3">
            <div class="d-flex align-items-center gap-3">
              <span class="small text-muted">
                Showing <strong class="text-dark"><?= $startRecord ?></strong> to <strong class="text-dark"><?= $endRecord ?></strong> of <strong class="text-dark"><?= number_format($totalFiltered) ?></strong> students
                <?php if ($totalFiltered < $totalCount): ?>
                  <span class="text-muted fst-italic">(filtered from <?= number_format($totalCount) ?> total)</span>
                <?php endif; ?>
              </span>
              <div class="d-flex align-items-center gap-2 border-start ps-3">
                <label for="perPageSelector" class="small text-muted text-nowrap mb-0">Records per page:</label>
                <select id="perPageSelector" class="form-select form-select-sm" style="width: 85px;" onchange="window.location.href = '<?= $buildPageUrl(1) ?>&per_page=' + this.value">
                  <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
                  <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
                  <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100</option>
                </select>
              </div>
            </div>

            <?php if ($totalPages > 1): ?>
              <nav aria-label="Student records pagination">
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $buildPageUrl(max(1, $page - 1)) ?>">Previous</a>
                  </li>

                  <?php
                    $startP = max(1, $page - 2);
                    $endP = min($totalPages, $page + 2);
                    if ($startP > 1): ?>
                      <li class="page-item">
                        <a class="page-link" href="<?= $buildPageUrl(1) ?>">1</a>
                      </li>
                      <?php if ($startP > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                      <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $startP; $p <= $endP; $p++): ?>
                      <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $buildPageUrl($p) ?>"><?= $p ?></a>
                      </li>
                    <?php endfor; ?>

                    <?php if ($endP < $totalPages): ?>
                      <?php if ($endP < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                      <?php endif; ?>
                      <li class="page-item">
                        <a class="page-link" href="<?= $buildPageUrl($totalPages) ?>"><?= $totalPages ?></a>
                      </li>
                    <?php endif; ?>

                  <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $buildPageUrl(min($totalPages, $page + 1)) ?>">Next</a>
                  </li>
                </ul>
              </nav>
            <?php endif; ?>
          </div>

        </div>
      </div>

    </div>
    <!-- ==================== END SCREEN UI ==================== -->

  </div>
</main>

<script>
function triggerMasterlistPrint() {
    window.print();
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>



