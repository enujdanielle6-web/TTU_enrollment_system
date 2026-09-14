<?php
$pageTitle = 'Registrar Dashboard - Triple T University';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <style>
    .shortcut-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.95rem 1.15rem;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      background: #ffffff;
      text-decoration: none;
      transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .shortcut-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
      border-color: #0d6efd;
      background: #ffffff;
    }
    .shortcut-item .icon-box {
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      background: rgba(13, 110, 253, 0.08);
      color: #0d6efd;
      font-size: 1.2rem;
      flex-shrink: 0;
      transition: all 0.2s ease;
    }
    .shortcut-item:hover .icon-box {
      background: #0d6efd;
      color: #ffffff;
      transform: scale(1.05);
    }
    .shortcut-item-shs .icon-box {
      background: rgba(13, 202, 240, 0.1);
      color: #0891b2;
    }
    .shortcut-item-shs:hover .icon-box {
      background: #0891b2;
      color: #ffffff;
    }
    .shortcut-item-shs:hover {
      border-color: #0891b2;
    }
    .student-id-mono {
      font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      font-size: 0.825rem;
      letter-spacing: 0.2px;
    }
  </style>

  <div class="container-fluid px-lg-5">

    <!-- Registrar Dashboard Hero Header Strip (Admissions Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Registrar Dashboard</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> University Registrar
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-calendar-check text-primary me-1"></i> AY <?= esc($systemSettings['active_school_year'] ?? '2026–2027') ?>
              </span>
            </div>
            <p class="text-muted small mb-0">Manage student records, enrollment queues, and academic structures.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="students.php" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-people-fill"></i>
            <span>Student Masterlist</span>
            <i class="bi bi-arrow-right small"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Executive KPI Metric Cards (Consistent 4-Column Grid) -->
    <div class="row g-4 mb-4">
      
      <!-- Card 1: Total Enrolled -->
      <div class="col-sm-6 col-xl-3">
        <a href="students.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.1s;">
          <div class="stat-card-glow bg-primary"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-mortarboard"></i>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-check2-all me-1"></i> Active Enrollees
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['enrolled']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Total Enrolled</h2>
          <p class="text-muted small mb-0"><?= $stats['college_enrolled'] ?> College • <?= $stats['shs_enrolled'] ?> Senior High</p>
          <div class="stat-card-footer">
            <span>Official roster</span>
            <span class="stat-card-action text-primary">View Masterlist <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 2: Enrollment Queue -->
      <div class="col-sm-6 col-xl-3">
        <a href="college_enrollment_queue.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.15s;">
          <div class="stat-card-glow bg-warning"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-hourglass-split"></i>
            </div>
            <?php if (($stats['ready_to_enroll'] ?? 0) > 0): ?>
              <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-clock-history me-1"></i> Awaiting Action
              </span>
            <?php else: ?>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-check2-circle me-1"></i> Queue Clear
              </span>
            <?php endif; ?>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['ready_to_enroll']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Enrollment Queue</h2>
          <p class="text-muted small mb-0"><?= $stats['college_queue'] ?> College • <?= $stats['shs_queue'] ?> SHS waiting</p>
          <div class="stat-card-footer">
            <span>Matriculation gate</span>
            <span class="stat-card-action text-warning">Process Queue <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 3: Active Sections -->
      <div class="col-sm-6 col-xl-3">
        <a href="/sia/admin/scheduler/college_sections.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow bg-info"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
              <i class="bi bi-diagram-3-fill"></i>
            </div>
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-layers me-1"></i> Class Blocks
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['active_sections']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Active Sections</h2>
          <p class="text-muted small mb-0"><?= $stats['college_sections'] ?> College • <?= $stats['shs_sections'] ?> SHS blocks</p>
          <div class="stat-card-footer">
            <span>Timetable offerings</span>
            <span class="stat-card-action text-info">View Schedules <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 4: Registered Students with IDs -->
      <div class="col-sm-6 col-xl-3">
        <a href="students.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.25s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-patch-check-fill"></i>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-person-badge me-1"></i> Student IDs
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['total_students']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Registered Students</h2>
          <p class="text-muted small mb-0">Official Student IDs provisioned</p>
          <div class="stat-card-footer">
            <span>Official directory</span>
            <span class="stat-card-action text-success">Student Registry <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

    </div>

    <!-- Quick Navigation Shortcuts (Admissions Dossier Card Styling) -->
    <div class="row g-4 mb-4">
      
      <!-- College Shortcuts -->
      <div class="col-md-6">
        <div class="dossier-card h-100 fade-in-up" style="animation-delay: 0.3s;">
          <div class="dossier-card-header">
            <div class="d-flex align-items-center gap-2.5">
              <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-mortarboard-fill"></i>
              </div>
              <div>
                <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">College Shortcuts</h2>
                <span class="badge bg-light text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
                  <?= $stats['college_programs_count'] ?> Programs
                </span>
              </div>
            </div>
          </div>
          
          <div class="p-4 pt-3">
            <div class="d-flex flex-column gap-3">
              <a href="college_programs.php" class="shortcut-item">
                <div class="icon-box"><i class="bi bi-journal-bookmark"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Academic Programs</span>
                  <span class="small text-muted d-block">Manage college degree courses & program codes</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="college_curriculum.php" class="shortcut-item">
                <div class="icon-box"><i class="bi bi-diagram-3"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Curriculum Directory</span>
                  <span class="small text-muted d-block">Manage semester course plans & subject layouts</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="/sia/admin/scheduler/college_sections.php" class="shortcut-item">
                <div class="icon-box"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Sections & Schedules</span>
                  <span class="small text-muted d-block">Manage block sections & timetable schedules</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="college_enrollment_queue.php" class="shortcut-item">
                <div class="icon-box"><i class="bi bi-person-lines-fill"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Enrollment Queue</span>
                  <span class="small text-muted d-block">Process & finalize college enrollees</span>
                </div>
                <?php if (($stats['college_queue'] ?? 0) > 0): ?>
                  <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-bold"><?= $stats['college_queue'] ?> pending</span>
                <?php else: ?>
                  <i class="bi bi-chevron-right text-muted small"></i>
                <?php endif; ?>
              </a>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Senior High Shortcuts -->
      <div class="col-md-6">
        <div class="dossier-card h-100 fade-in-up" style="animation-delay: 0.35s;">
          <div class="dossier-card-header">
            <div class="d-flex align-items-center gap-2.5">
              <div class="dossier-header-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-building-fill"></i>
              </div>
              <div>
                <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Senior High Shortcuts</h2>
                <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
                  <?= $stats['shs_strands_count'] ?> Strands
                </span>
              </div>
            </div>
          </div>
          
          <div class="p-4 pt-3">
            <div class="d-flex flex-column gap-3">
              <a href="shs_strands.php" class="shortcut-item shortcut-item-shs">
                <div class="icon-box"><i class="bi bi-journal-text"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">SHS Strands</span>
                  <span class="small text-muted d-block">Manage senior high academic & TVL tracks</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="shs_curriculum.php" class="shortcut-item shortcut-item-shs">
                <div class="icon-box"><i class="bi bi-diagram-3"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Strand Curriculum</span>
                  <span class="small text-muted d-block">Manage Grade 11 & 12 subject layouts</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="/sia/admin/scheduler/shs_sections.php" class="shortcut-item shortcut-item-shs">
                <div class="icon-box"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Sections & Schedules</span>
                  <span class="small text-muted d-block">Manage track sections & class schedules</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="shs_enrollment_queue.php" class="shortcut-item shortcut-item-shs">
                <div class="icon-box"><i class="bi bi-person-lines-fill"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Enrollment Queue</span>
                  <span class="small text-muted d-block">Process & finalize senior high enrollees</span>
                </div>
                <?php if (($stats['shs_queue'] ?? 0) > 0): ?>
                  <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-bold"><?= $stats['shs_queue'] ?> pending</span>
                <?php else: ?>
                  <i class="bi bi-chevron-right text-muted small"></i>
                <?php endif; ?>
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Recently Enrolled Student Masterlist (Admissions Consistent Table) -->
    <div class="dossier-card fade-in-up" style="animation-delay: 0.4s;">
      <div class="dossier-card-header">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-people-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Recently Enrolled Students</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <i class="bi bi-activity text-primary me-1"></i><?= count($recentEnrolled) ?> Records
            </span>
          </div>
        </div>
        <div>
          <a href="students.php" class="btn btn-sm btn-light border rounded-pill px-3 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-none">
            <i class="bi bi-list-ul me-1"></i>
            <span>Full Masterlist</span>
            <i class="bi bi-chevron-right small text-muted"></i>
          </a>
        </div>
      </div>

      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table">
            <thead>
              <tr>
                <th class="ps-4">ID / Reference</th>
                <th>Student Name</th>
                <th>Academic Level</th>
                <th>Grade / Year</th>
                <th>Program / Strand</th>
                <th>Status</th>
                <th class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recentEnrolled)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                        <i class="bi bi-people fs-1 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">No Enrolled Students Found</h3>
                      <p class="small text-muted mb-0">Enrolled students will appear here once processed through the registrar queues.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($recentEnrolled as $student): ?>
                <?php
                  $firstName = trim($student['first_name'] ?? '');
                  $lastName = trim($student['last_name'] ?? '');
                  $fullName = trim($lastName . ', ' . $firstName);
                  if ($fullName === ', ') $fullName = 'Unknown Student';
                  
                  $initials = strtoupper(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1));
                  if ($initials === '') $initials = 'ST';

                  $idDisplay = !empty($student['student_number']) 
                      ? $student['student_number'] 
                      : (!empty($student['lrn']) ? $student['lrn'] : $student['reference_number']);
                  $isCollege = (($student['academic_level'] ?? '') === 'College');
                ?>
                <tr>
                  <td class="ps-4">
                    <div class="d-inline-flex align-items-center gap-1">
                      <span class="applicant-ref-badge">
                        <i class="bi bi-hash text-muted"></i><?= esc($idDisplay) ?>
                      </span>
                      <?php if (!empty($student['student_number'])): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-0.5 small" style="font-size: 0.68rem;">
                          <i class="bi bi-patch-check-fill me-0.5"></i>Official
                        </span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2.5">
                      <div class="applicant-avatar">
                        <?= esc($initials) ?>
                      </div>
                      <div>
                        <span class="fw-bold text-dark d-block"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($student['email'])): ?>
                          <span class="text-muted extra-small d-block"><?= htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td>
                    <?php if ($isCollege): ?>
                      <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                        College
                      </span>
                    <?php else: ?>
                      <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                        Senior High School
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="fw-semibold text-dark small">
                      <?= htmlspecialchars($student['grade_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-pill fw-semibold small d-inline-flex align-items-center gap-1">
                      <i class="bi bi-mortarboard text-primary"></i><?= htmlspecialchars(strtoupper($student['strand'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1.5 rounded-pill small fw-semibold d-inline-flex align-items-center gap-1">
                      <i class="bi bi-mortarboard-fill"></i> Enrolled
                    </span>
                  </td>
                  <td class="pe-4 text-end">
                    <a href="../admissions/application_detail.php?id=<?= esc($student['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium d-inline-flex align-items-center gap-1.5">
                      <i class="bi bi-person-lines-fill"></i> Profile
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

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
