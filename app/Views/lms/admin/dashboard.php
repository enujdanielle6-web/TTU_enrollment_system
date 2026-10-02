<?php
$pageTitle = 'LMS Administration Dashboard - TTU';
require_once __DIR__ . '/layout_header.php';

$activeTermStr = htmlspecialchars(($stats['active_term']['academic_year'] ?? '2026-2027') . ' ' . ($stats['active_term']['semester'] ?? 'First') . ' Sem', ENT_QUOTES, 'UTF-8');
$adminName = htmlspecialchars($_SESSION['user_name'] ?? 'LMS Administrator', ENT_QUOTES, 'UTF-8');
$hasConflicts = !empty($conflictSummary['duplicate_groups']) || !empty($conflictSummary['orphan_courses']);
$unassignedCount = (int)($stats['unassigned_courses'] ?? 0);
$activeCoursesCount = (int)($stats['active_courses'] ?? 0);
$activeStudentsCount = (int)($stats['active_students'] ?? 0);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">

    <!-- Flash Alerts -->
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4 p-3 fade-in-up">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div class="small fw-semibold"><?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4 p-3 fade-in-up">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
        <div class="small fw-semibold"><?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- LMS Dashboard Hero Header Strip (Registrar Dashboard Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">LMS Governance Dashboard</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> LMS Administration
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-calendar-check text-primary me-1"></i> AY <?= esc($activeTermStr) ?>
              </span>
            </div>
            <p class="text-muted small mb-0">
              Leading institutional governance for <strong class="text-dark"><?= number_format($activeCoursesCount) ?> active course shells</strong> with <strong class="text-dark"><?= number_format($activeStudentsCount) ?> students enrolled</strong> this academic term.
            </p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <a href="<?= BASE_PATH ?>/lms/admin/sync" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-medium shadow-xs d-inline-flex align-items-center gap-1.5 hover-lift">
            <i class="bi bi-arrow-repeat"></i>
            <span>Sync Hub</span>
          </a>
          <a href="<?= BASE_PATH ?>/lms/admin/courses" class="btn btn-primary rounded-pill px-3.5 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2 hover-lift">
            <i class="bi bi-collection-fill"></i>
            <span>Course Catalog</span>
            <i class="bi bi-arrow-right small"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Executive KPI Metric Cards (Consistent 4-Column Grid matching Registrar Dashboard) -->
    <div class="row g-4 mb-4">
      
      <!-- Card 1: Active Course Shells -->
      <div class="col-sm-6 col-xl-3">
        <a href="<?= BASE_PATH ?>/lms/admin/courses" class="stat-card-kpi fade-in-up" style="animation-delay: 0.1s;">
          <div class="stat-card-glow bg-primary"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-journal-bookmark-fill"></i>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-check2-all me-1"></i> Live Shells
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($activeCoursesCount) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Active Course Shells</h2>
          <p class="text-muted small mb-0">Published instructional spaces</p>
          <div class="stat-card-footer">
            <span>Course catalog</span>
            <span class="stat-card-action text-primary">Manage Shells <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 2: Enrolled Students -->
      <div class="col-sm-6 col-xl-3">
        <a href="<?= BASE_PATH ?>/lms/admin/users?role=student" class="stat-card-kpi fade-in-up" style="animation-delay: 0.15s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-people-fill"></i>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-person-check-fill me-1"></i> Active Roster
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($activeStudentsCount) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Enrolled Students</h2>
          <p class="text-muted small mb-0">Active learners in course shells</p>
          <div class="stat-card-footer">
            <span>Access directory</span>
            <span class="stat-card-action text-success">User Access <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 3: TBA Shells / Faculty Allocation -->
      <div class="col-sm-6 col-xl-3">
        <a href="<?= BASE_PATH ?>/lms/admin/courses?faculty_filter=unassigned" class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow <?= $unassignedCount > 0 ? 'bg-warning' : 'bg-info' ?>"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper <?= $unassignedCount > 0 ? 'bg-warning bg-opacity-10 text-warning' : 'bg-info bg-opacity-10 text-info' ?>">
              <i class="bi <?= $unassignedCount > 0 ? 'bi-exclamation-triangle-fill' : 'bi-patch-check-fill' ?>"></i>
            </div>
            <?php if ($unassignedCount > 0): ?>
              <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-clock-history me-1"></i> Staffing Needed
              </span>
            <?php else: ?>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-check2-circle me-1"></i> 100% Staffed
              </span>
            <?php endif; ?>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($unassignedCount) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">TBA Course Shells</h2>
          <p class="text-muted small mb-0"><?= $unassignedCount > 0 ? 'Shells needing faculty binding' : 'All shells have assigned instructors' ?></p>
          <div class="stat-card-footer">
            <span>Faculty allocation</span>
            <span class="stat-card-action <?= $unassignedCount > 0 ? 'text-warning' : 'text-info' ?>">Assign Faculty <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 4: Timetable Sync Health -->
      <div class="col-sm-6 col-xl-3">
        <a href="<?= BASE_PATH ?>/lms/admin/sync" class="stat-card-kpi fade-in-up" style="animation-delay: 0.25s;">
          <div class="stat-card-glow <?= $hasConflicts ? 'bg-danger' : 'bg-info' ?>"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper <?= $hasConflicts ? 'bg-danger bg-opacity-10 text-danger' : 'bg-info bg-opacity-10 text-info' ?>">
              <i class="bi bi-arrow-repeat"></i>
            </div>
            <?php if ($hasConflicts): ?>
              <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-exclamation-octagon-fill me-1"></i> Action Required
              </span>
            <?php else: ?>
              <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> 100% In-Sync
              </span>
            <?php endif; ?>
          </div>
          <div class="stat-number-display mb-1"><?= $hasConflicts ? 'Alert' : '100%' ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Timetable Sync Health</h2>
          <p class="text-muted small mb-0"><?= $hasConflicts ? 'Discrepancies require reconciliation' : 'Deterministic alignment active' ?></p>
          <div class="stat-card-footer">
            <span>Diagnostics hub</span>
            <span class="stat-card-action <?= $hasConflicts ? 'text-danger' : 'text-info' ?>">Reconcile Hub <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

    </div>

    <!-- Quick Navigation Shortcuts (Admissions & Registrar Consistent Dossier Cards) -->
    <div class="row g-4 mb-4">
      
      <!-- Left Column: Instructional Operations -->
      <div class="col-md-6">
        <div class="dossier-card h-100 fade-in-up" style="animation-delay: 0.3s;">
          <div class="dossier-card-header">
            <div class="d-flex align-items-center gap-2.5">
              <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-journal-bookmark-fill"></i>
              </div>
              <div>
                <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Instructional Operations</h2>
                <span class="badge bg-light text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
                  <?= count($recentCourses) ?> Recent Shells
                </span>
              </div>
            </div>
          </div>
          
          <div class="p-4 pt-3">
            <div class="d-flex flex-column gap-3">
              <a href="<?= BASE_PATH ?>/lms/admin/courses" class="shortcut-item">
                <div class="icon-box"><i class="bi bi-collection-fill"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Course Catalog</span>
                  <span class="small text-muted d-block">Inspect syllabus, verify section rosters &amp; assign faculty</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="<?= BASE_PATH ?>/lms/admin/cloner" class="shortcut-item">
                <div class="icon-box"><i class="bi bi-copy"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Content &amp; Syllabus Cloner</span>
                  <span class="small text-muted d-block">Replicate modules, assignments &amp; quizzes into live shells</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="<?= BASE_PATH ?>/lms/admin/announcements" class="shortcut-item">
                <div class="icon-box"><i class="bi bi-megaphone-fill"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Platform Announcements</span>
                  <span class="small text-muted d-block">Broadcast emergency notices &amp; maintenance downtime</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="<?= BASE_PATH ?>/lms/admin/generator" class="shortcut-item">
                <div class="icon-box"><i class="bi bi-cpu-fill"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Course Shell Generator</span>
                  <span class="small text-muted d-block">Instantiate isolated shells for unmapped timetable offerings</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Right Column: Governance & System Controls -->
      <div class="col-md-6">
        <div class="dossier-card h-100 fade-in-up" style="animation-delay: 0.35s;">
          <div class="dossier-card-header">
            <div class="d-flex align-items-center gap-2.5">
              <div class="dossier-header-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-shield-lock-fill"></i>
              </div>
              <div>
                <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Governance &amp; Controls</h2>
                <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
                  Platform Security
                </span>
              </div>
            </div>
          </div>
          
          <div class="p-4 pt-3">
            <div class="d-flex flex-column gap-3">
              <a href="<?= BASE_PATH ?>/lms/admin/sync" class="shortcut-item shortcut-item-cyan">
                <div class="icon-box"><i class="bi bi-arrow-repeat"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Timetable Sync &amp; Conflicts</span>
                  <span class="small text-muted d-block">Reconcile offerings &amp; conservatively resolve duplicates</span>
                </div>
                <?php if ($hasConflicts): ?>
                  <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 small fw-bold">Conflicts Found</span>
                <?php else: ?>
                  <i class="bi bi-chevron-right text-muted small"></i>
                <?php endif; ?>
              </a>

              <a href="<?= BASE_PATH ?>/lms/admin/users" class="shortcut-item shortcut-item-cyan">
                <div class="icon-box"><i class="bi bi-person-badge-fill"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">User Access Governance</span>
                  <span class="small text-muted d-block">Manage platform status without affecting registrar identity</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="<?= BASE_PATH ?>/lms/admin/archive" class="shortcut-item shortcut-item-cyan">
                <div class="icon-box"><i class="bi bi-archive-fill"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Term Archival Console</span>
                  <span class="small text-muted d-block">Conclude terms and non-destructively preserve student records</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="<?= BASE_PATH ?>/lms/admin/audit_logs" class="shortcut-item shortcut-item-cyan">
                <div class="icon-box"><i class="bi bi-shield-check"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">LMS Audit Logs</span>
                  <span class="small text-muted d-block">Tamper-evident logs tracking administrative provisioning</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Active Course Shells & Governance Status Widgets -->
    <div class="row g-4 mb-4">
      
      <!-- =================================================================== -->
      <!-- LEFT COLUMN: ACTIVE COURSE SHELLS (Matching Dossier Card Styling)   -->
      <!-- =================================================================== -->
      <div class="col-lg-8">
        
        <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.4s;">
          <div class="dossier-card-header">
            <div class="d-flex align-items-center gap-2.5">
              <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-collection-fill"></i>
              </div>
              <div>
                <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Active Instructional Shells</h2>
                <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
                  <?= count($recentCourses) ?> Displayed
                </span>
              </div>
            </div>
            <div>
              <a href="<?= BASE_PATH ?>/lms/admin/courses" class="btn btn-sm btn-light border rounded-pill px-3 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-none">
                <i class="bi bi-list-ul me-1"></i>
                <span>Full Catalog</span>
                <i class="bi bi-chevron-right small text-muted"></i>
              </a>
            </div>
          </div>

          <div class="p-4 pt-3">
            <?php if (empty($recentCourses)): ?>
              <div class="text-center py-5">
                <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                  <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                    <i class="bi bi-folder-x fs-1 text-muted"></i>
                  </div>
                  <h3 class="h6 fw-bold text-dark mb-1">No Active Course Shells Found</h3>
                  <p class="small text-muted mb-3" style="max-width: 420px;">
                    No instructional shells have been provisioned for the current academic term yet. Run timetable reconciliation to automatically sync offerings.
                  </p>
                  <div>
                    <a href="<?= BASE_PATH ?>/lms/admin/sync" class="btn btn-primary rounded-pill px-4 fw-medium shadow-sm hover-lift">
                      <i class="bi bi-arrow-repeat me-1.5"></i> Run Reconciliation
                    </a>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <div class="row g-3">
                <?php 
                  $colorBands = ['card-band-blue', 'card-band-purple', 'card-band-emerald', 'card-band-cyan', 'card-band-amber', 'card-band-rose'];
                  foreach ($recentCourses as $idx => $course): 
                    $bandClass = $colorBands[$idx % count($colorBands)];
                    $facultyName = trim(($course['instructor_first'] ?? $course['faculty_first_name'] ?? '') . ' ' . ($course['instructor_last'] ?? $course['faculty_last_name'] ?? ''));
                    if (empty($facultyName)) {
                        $facultyName = $course['fallback_instructor_name'] ?? '';
                    }
                    $isTba = empty($facultyName) || $facultyName === 'Unassigned (TBA)';
                    $courseFaculty = $isTba ? 'Unassigned (TBA)' : $facultyName;
                    $facInitial = $isTba ? '?' : strtoupper(substr($courseFaculty, 0, 1));
                ?>
                  <div class="col-md-6">
                    <div class="course-card-premium shadow-sm h-100 d-flex flex-column">
                      <!-- Header Band -->
                      <div class="card-band <?= esc($bandClass) ?>">
                        <div class="d-flex align-items-center gap-1.5">
                          <span class="badge badge-frosted-solid rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                            <?= htmlspecialchars($course['subject_code']) ?>
                          </span>
                          <span class="badge badge-frosted-white rounded-pill px-2.5 py-0.5" style="font-size: 0.68rem;">
                            <?= htmlspecialchars($course['academic_level']) ?>
                          </span>
                        </div>
                        <span class="badge badge-frosted-dark rounded-pill px-2.5 py-0.5 small font-monospace" style="font-size: 0.65rem;">
                          #<?= (int)$course['lms_course_id'] ?>
                        </span>
                      </div>

                      <!-- Body Content -->
                      <div class="p-3.5 d-flex flex-column flex-grow-1">
                        <h4 class="course-card-title mb-2 fw-bold text-dark" title="<?= htmlspecialchars($course['subject_name']) ?>" style="font-size: 0.95rem; line-height: 1.35;">
                          <?= htmlspecialchars($course['subject_name']) ?>
                        </h4>

                        <!-- Cohort & Section Tag -->
                        <div class="d-flex align-items-center justify-content-between mb-3 text-muted small">
                          <span class="applicant-ref-badge font-monospace" style="font-size: 0.74rem;">
                            <i class="bi bi-diagram-2 text-primary me-1"></i><?= htmlspecialchars($course['section_code'] ?? 'No Section') ?>
                          </span>
                          <span class="badge badge-slate-subtle rounded-pill px-2.5 py-1" style="font-size: 0.74rem;">
                            <i class="bi bi-person-check text-success me-1"></i><?= (int)($course['enrolled_count'] ?? 0) ?> Enrolled
                          </span>
                        </div>

                        <!-- Faculty Avatar Strip -->
                        <div class="p-2.5 <?= $isTba ? 'bg-amber-subtle border border-warning border-opacity-25' : 'bg-light border' ?> rounded-3 d-flex align-items-center gap-2.5 mb-3" style="<?= $isTba ? 'background-color: #fffbeb !important; border: 1px dashed #fcd34d !important;' : '' ?>">
                          <div class="<?= $isTba ? 'bg-amber-100 text-amber-700' : 'bg-primary text-white' ?> rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 34px; height: 34px; font-size: 0.85rem; <?= $isTba ? 'background-color: #fef3c7; color: #92400e;' : '' ?>">
                            <?php if ($isTba): ?>
                              <i class="bi bi-person-dash" style="font-size: 1rem;"></i>
                            <?php else: ?>
                              <?= esc($facInitial) ?>
                            <?php endif; ?>
                          </div>
                          <div class="overflow-hidden min-w-0 flex-grow-1">
                            <div class="fw-semibold text-dark small text-truncate" title="<?= htmlspecialchars($courseFaculty) ?>">
                              <?= htmlspecialchars($courseFaculty) ?>
                            </div>
                            <div style="font-size: 0.72rem;">
                              <?= $isTba ? '<span class="text-amber-800 fw-semibold d-inline-flex align-items-center gap-1" style="color: #92400e;"><i class="bi bi-clock-history"></i> Awaiting Assignment</span>' : '<span class="text-muted d-inline-flex align-items-center gap-1"><i class="bi bi-patch-check-fill text-primary"></i> Assigned Faculty</span>' ?>
                            </div>
                          </div>
                        </div>

                        <!-- Card Actions Footer -->
                        <div class="mt-auto pt-2.5 border-top d-flex align-items-center justify-content-between">
                          <span class="badge <?= $course['status'] === 'active' ? 'badge-emerald-subtle' : 'badge-slate-subtle' ?> rounded-pill px-2.5 py-1 small">
                            <span class="pulse-dot <?= $course['status'] === 'active' ? 'pulse-dot-emerald' : '' ?>"></span>
                            <?= ucfirst(htmlspecialchars($course['status'])) ?> Term
                          </span>
                          <a href="<?= BASE_PATH ?>/lms/admin/courses/<?= (int)$course['lms_course_id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1 shadow-xs hover-lift">
                            <span>Inspect Shell</span>
                            <i class="bi bi-arrow-right-short fs-6"></i>
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick Generator Banner -->
        <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.45s;">
          <div class="p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4 shadow-xs" style="font-size: 1.4rem;">
                <i class="bi bi-cpu-fill"></i>
              </div>
              <div>
                <h3 class="h6 fw-bold text-dark mb-0.5">Need to provision specific timetable offerings?</h3>
                <p class="text-muted small mb-0">Use the manual course generator to deploy isolated shells for unmapped section-subject offerings.</p>
              </div>
            </div>
            <a href="<?= BASE_PATH ?>/lms/admin/generator" class="btn btn-outline-primary rounded-pill px-3.5 py-2 fw-semibold small shadow-xs hover-lift">
              <i class="bi bi-plus-circle me-1"></i> Open Generator &rarr;
            </a>
          </div>
        </div>

      </div>

      <!-- =================================================================== -->
      <!-- RIGHT COLUMN: GOVERNANCE WIDGETS & AUDIT ACTIVITY (Consistent)       -->
      <!-- =================================================================== -->
      <div class="col-lg-4">
        
        <!-- Widget 1: Operational Governance Dossier -->
        <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.4s;">
          <div class="dossier-card-header">
            <div class="d-flex align-items-center gap-2.5">
              <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-shield-lock-fill"></i>
              </div>
              <div>
                <h3 class="h6 fw-bold text-dark mb-0">LMS Governance Status</h3>
              </div>
            </div>
            <span class="badge badge-emerald-subtle rounded-pill px-2.5 py-0.5 small fw-bold"><span class="pulse-dot pulse-dot-emerald me-1"></span>Live</span>
          </div>

          <div class="p-4 pt-3">
            <div class="bg-light p-3 rounded-3 border mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small"><i class="bi bi-calendar3 me-1.5 text-primary"></i> Academic Year:</span>
                <strong class="text-dark small"><?= htmlspecialchars($stats['active_term']['academic_year'] ?? '2026-2027') ?></strong>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small"><i class="bi bi-flag me-1.5 text-primary"></i> Semester:</span>
                <strong class="text-dark small"><?= htmlspecialchars($stats['active_term']['semester'] ?? 'First') ?> Semester</strong>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small"><i class="bi bi-person-video3 me-1.5 text-primary"></i> Faculty Assigned:</span>
                <strong class="text-dark small"><?= (int)($stats['active_faculty'] ?? 0) ?> Members</strong>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small"><i class="bi bi-check2-square me-1.5 text-primary"></i> Work Items:</span>
                <strong class="text-dark small"><?= (int)($stats['total_assignments'] ?? 0) + (int)($stats['total_quizzes'] ?? 0) ?> Tasks</strong>
              </div>
            </div>

            <a href="<?= BASE_PATH ?>/lms/admin/sync" class="btn btn-primary rounded-pill w-100 py-2 small fw-semibold shadow-xs d-flex align-items-center justify-content-center gap-1.5 hover-lift">
              <i class="bi bi-arrow-repeat"></i>
              <span>Run Timetable Reconciliation</span>
            </a>
          </div>
        </div>

        <!-- Widget 2: Platform Announcements Live Broadcast -->
        <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.45s;">
          <div class="dossier-card-header">
            <div class="d-flex align-items-center gap-2.5">
              <div class="dossier-header-icon bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-broadcast"></i>
              </div>
              <div>
                <h3 class="h6 fw-bold text-dark mb-0">Active Platform Notices</h3>
              </div>
            </div>
            <a href="<?= BASE_PATH ?>/lms/admin/announcements" class="small fw-semibold text-primary text-decoration-none">Manage &rarr;</a>
          </div>

          <div class="p-4 pt-3">
            <?php if (empty($platformNotices)): ?>
              <div class="p-4 text-center text-muted bg-light rounded-3 border">
                <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
                <div class="fw-bold small text-dark">No Active Platform Alerts</div>
                <p class="mb-0 text-muted" style="font-size: 0.72rem;">All systems operational. No maintenance downtime or emergency advisories broadcast.</p>
              </div>
            <?php else: ?>
              <div class="d-flex flex-column gap-2 mb-3">
                <?php foreach (array_slice($platformNotices, 0, 3) as $not): 
                  $sev = $not['severity'] ?? 'info';
                  $icon = 'bi-info-circle-fill text-info';
                  if ($sev === 'warning') $icon = 'bi-exclamation-triangle-fill text-warning';
                  if ($sev === 'danger') $icon = 'bi-exclamation-octagon-fill text-danger';
                  if ($sev === 'success') $icon = 'bi-check-circle-fill text-success';
                ?>
                  <div class="p-2.5 bg-light rounded-3 border">
                    <div class="d-flex align-items-start gap-2">
                      <i class="bi <?= $icon ?> fs-6 mt-0.5"></i>
                      <div class="min-w-0 flex-grow-1">
                        <div class="fw-bold text-dark small text-truncate"><?= htmlspecialchars($not['title']) ?></div>
                        <div class="text-muted text-truncate" style="font-size: 0.7rem;"><?= htmlspecialchars($not['content']) ?></div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <a href="<?= BASE_PATH ?>/lms/admin/announcements" class="btn btn-outline-danger rounded-pill w-100 py-1.5 small fw-semibold mt-2 hover-lift">
              <i class="bi bi-plus-circle me-1"></i> Broadcast New Notice
            </a>
          </div>
        </div>

        <!-- Widget 3: Recent Administrative Audit Activity -->
        <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.5s;">
          <div class="dossier-card-header">
            <div class="d-flex align-items-center gap-2.5">
              <div class="dossier-header-icon bg-secondary bg-opacity-10 text-secondary">
                <i class="bi bi-clock-history"></i>
              </div>
              <div>
                <h6 class="fw-bold text-dark mb-0">LMS Audit Stream</h6>
              </div>
            </div>
            <a href="<?= BASE_PATH ?>/lms/admin/audit_logs" class="small fw-semibold text-primary text-decoration-none">Full Log &rarr;</a>
          </div>

          <div class="p-4 pt-3">
            <?php if (empty($stats['recent_logs'])): ?>
              <div class="p-3 text-center text-muted small bg-light rounded-3 border">No recent administrative actions recorded.</div>
            <?php else: ?>
              <div class="d-flex flex-column gap-2.5">
                <?php foreach (array_slice($stats['recent_logs'], 0, 5) as $log): 
                  $timeAgo = date('M d, h:i A', strtotime($log['created_at']));
                  $actor = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: 'Admin';
                ?>
                  <div class="d-flex align-items-start gap-2.5 pb-2 border-bottom">
                    <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-2 flex-shrink-0 mt-0.5" style="width: 28px; height: 28px; font-size: 0.85rem;">
                      <i class="bi <?= htmlspecialchars($log['icon'] ?? 'bi-activity') ?>"></i>
                    </div>
                    <div class="min-w-0 flex-grow-1">
                      <div class="fw-semibold text-dark small text-truncate" title="<?= htmlspecialchars($log['title'] ?? 'LMS Operation') ?>">
                        <?= htmlspecialchars($log['title'] ?? 'LMS Operation') ?>
                      </div>
                      <div class="d-flex align-items-center justify-content-between text-muted" style="font-size: 0.7rem;">
                        <span><?= htmlspecialchars($actor) ?></span>
                        <span><?= esc($timeAgo) ?></span>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>

    </div>

  </div>
</main>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
