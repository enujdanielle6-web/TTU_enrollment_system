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
      border-radius: 12px;
      background: #ffffff;
      text-decoration: none;
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .shortcut-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
      border-color: #3b82f6;
      background: #ffffff;
    }
    .shortcut-item .icon-box {
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
      background: rgba(37, 99, 235, 0.08);
      color: #2563eb;
      font-size: 1.15rem;
      flex-shrink: 0;
      transition: all 0.2s ease;
    }
    .shortcut-item:hover .icon-box {
      background: #2563eb;
      color: #ffffff;
      transform: scale(1.05);
    }
    .student-id-mono {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 0.82rem;
      letter-spacing: 0.3px;
    }
  </style>

  <div class="container-fluid px-lg-5">

    <!-- Hero Header -->
    <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 fade-in-up">
      <div>
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary bg-opacity-10 text-primary fw-semibold small mb-2">
          <i class="bi bi-mortarboard-fill"></i> Office of the University Registrar
        </div>
        <h1 class="h3 fw-bold text-dark mb-1">Registrar Dashboard</h1>
        <p class="text-muted mb-0">Manage student records, enrollment queues, and academic structures.</p>
      </div>
      <div>
        <div class="d-inline-flex align-items-center gap-2 px-3.5 py-2 rounded-pill bg-white border shadow-sm text-secondary small fw-semibold">
          <i class="bi bi-calendar-check text-primary"></i>
          <span>Academic Year <?= esc($systemSettings['active_school_year'] ?? '2026–2027') ?></span>
        </div>
      </div>
    </div>

    <!-- Academic Statistics -->
    <div class="row g-4 mb-4">
      <div class="col-6 col-lg-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4 bg-white fade-in-up" style="animation-delay: 0.1s;">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-mortarboard fs-4"></i>
            </div>
          </div>
          <div class="display-6 fw-bold text-dark mb-1"><?= number_format($stats['enrolled']) ?></div>
          <div class="text-muted small fw-semibold text-uppercase tracking-wide mb-1">Total Enrolled</div>
          <div class="small text-muted"><?= $stats['college_enrolled'] ?> College • <?= $stats['shs_enrolled'] ?> Senior High</div>
        </div>
      </div>
      
      <div class="col-6 col-lg-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4 bg-white fade-in-up" style="animation-delay: 0.2s;">
          <div class="position-absolute top-0 start-0 w-100 bg-warning" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-person-lines-fill fs-4"></i>
            </div>
          </div>
          <div class="display-6 fw-bold text-dark mb-1"><?= number_format($stats['ready_to_enroll']) ?></div>
          <div class="text-muted small fw-semibold text-uppercase tracking-wide mb-1">Enrollment Queue</div>
          <div class="small text-muted"><?= $stats['college_queue'] ?> College • <?= $stats['shs_queue'] ?> SHS waiting</div>
        </div>
      </div>

      <div class="col-6 col-lg-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4 bg-white fade-in-up" style="animation-delay: 0.3s;">
          <div class="position-absolute top-0 start-0 w-100 bg-info" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-diagram-3-fill fs-4"></i>
            </div>
          </div>
          <div class="display-6 fw-bold text-dark mb-1"><?= number_format($stats['active_sections']) ?></div>
          <div class="text-muted small fw-semibold text-uppercase tracking-wide mb-1">Active Sections</div>
          <div class="small text-muted"><?= $stats['college_sections'] ?> College • <?= $stats['shs_sections'] ?> SHS sections</div>
        </div>
      </div>

      <div class="col-6 col-lg-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4 bg-white fade-in-up" style="animation-delay: 0.4s;">
          <div class="position-absolute top-0 start-0 w-100 bg-success" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-people-fill fs-4"></i>
            </div>
          </div>
          <div class="display-6 fw-bold text-dark mb-1"><?= number_format($stats['total_students']) ?></div>
          <div class="text-muted small fw-semibold text-uppercase tracking-wide mb-1">Registered Students</div>
          <div class="small text-muted">Official Student IDs issued</div>
        </div>
      </div>
    </div>

    <!-- Quick Navigation Shortcuts (Balanced 2 Columns) -->
    <div class="row g-4 mb-4">
      
      <!-- College Shortcuts -->
      <div class="col-md-6">
        <div class="island h-100 border-0 shadow-sm rounded-4 fade-in-up" style="animation-delay: 0.5s;">
          <div class="island-header border-bottom p-4 pb-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-mortarboard-fill text-primary fs-5"></i>
              <h2 class="h5 fw-bold text-dark mb-0">College Shortcuts</h2>
            </div>
            <span class="badge bg-light text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small">
              <?= $stats['college_programs_count'] ?> Programs
            </span>
          </div>
          
          <div class="island-body p-4 pt-3">
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
                  <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 small fw-bold"><?= $stats['college_queue'] ?> pending</span>
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
        <div class="island h-100 border-0 shadow-sm rounded-4 fade-in-up" style="animation-delay: 0.6s;">
          <div class="island-header border-bottom p-4 pb-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-building text-primary fs-5"></i>
              <h2 class="h5 fw-bold text-dark mb-0">Senior High Shortcuts</h2>
            </div>
            <span class="badge bg-light text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-1 small">
              <?= $stats['shs_strands_count'] ?> Strands
            </span>
          </div>
          
          <div class="island-body p-4 pt-3">
            <div class="d-flex flex-column gap-3">
              <a href="shs_strands.php" class="shortcut-item">
                <div class="icon-box"><i class="bi bi-journal-text"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">SHS Strands</span>
                  <span class="small text-muted d-block">Manage senior high academic & TVL tracks</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="shs_curriculum.php" class="shortcut-item">
                <div class="icon-box"><i class="bi bi-diagram-3"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Strand Curriculum</span>
                  <span class="small text-muted d-block">Manage Grade 11 & 12 subject layouts</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="/sia/admin/scheduler/shs_sections.php" class="shortcut-item">
                <div class="icon-box"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Sections & Schedules</span>
                  <span class="small text-muted d-block">Manage track sections & class schedules</span>
                </div>
                <i class="bi bi-chevron-right text-muted small"></i>
              </a>

              <a href="shs_enrollment_queue.php" class="shortcut-item">
                <div class="icon-box"><i class="bi bi-person-lines-fill"></i></div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark d-block">Enrollment Queue</span>
                  <span class="small text-muted d-block">Process & finalize senior high enrollees</span>
                </div>
                <?php if (($stats['shs_queue'] ?? 0) > 0): ?>
                  <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 small fw-bold"><?= $stats['shs_queue'] ?> pending</span>
                <?php else: ?>
                  <i class="bi bi-chevron-right text-muted small"></i>
                <?php endif; ?>
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Live Enrolled Student Masterlist Preview -->
    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4 mb-4 fade-in-up" style="animation-delay: 0.7s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="p-4 border-bottom d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2.5">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 40px; height: 40px;">
            <i class="bi bi-people-fill fs-5"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0">Recently Enrolled Students</h2>
            <span class="small text-muted">Latest verified records officially active in the student masterlist</span>
          </div>
        </div>
        <a href="students.php" class="btn btn-sm btn-outline-primary rounded-pill px-3.5 py-1.5 fw-semibold">
          Open Full Masterlist <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 custom-table">
          <thead class="table-light text-muted small text-uppercase">
            <tr>
              <th scope="col" class="ps-4 py-3 fw-semibold" style="width: 50px;">#</th>
              <th scope="col" class="py-3 fw-semibold" style="width: 170px;">ID / Reference</th>
              <th scope="col" class="py-3 fw-semibold">Student Name</th>
              <th scope="col" class="py-3 fw-semibold" style="width: 150px;">Academic Level</th>
              <th scope="col" class="py-3 fw-semibold" style="width: 120px;">Grade / Year</th>
              <th scope="col" class="py-3 fw-semibold" style="width: 110px;">Program</th>
              <th scope="col" class="py-3 fw-semibold" style="width: 110px;">Status</th>
              <th scope="col" class="pe-4 py-3 text-end fw-semibold" style="width: 110px;">Action</th>
            </tr>
          </thead>
          <tbody class="border-top-0">
            <?php if (empty($recentEnrolled)): ?>
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                  <div class="d-inline-flex p-3 rounded-circle bg-light mb-2">
                    <i class="bi bi-people fs-2 text-secondary"></i>
                  </div>
                  <h6 class="fw-bold text-dark mb-1">No Enrolled Students Found</h6>
                  <p class="small text-muted mb-0">Enrolled students will appear here once processed through the registrar queues.</p>
                </td>
              </tr>
            <?php else: ?>
              <?php $rowNum = 1; foreach ($recentEnrolled as $student): ?>
                <?php
                  $idDisplay = !empty($student['student_number']) 
                      ? $student['student_number'] 
                      : (!empty($student['lrn']) ? $student['lrn'] : $student['reference_number']);
                  $fInitial = strtoupper(substr($student['first_name'] ?? 'S', 0, 1));
                  $lInitial = strtoupper(substr($student['last_name'] ?? 'N', 0, 1));
                  $isCollege = (($student['academic_level'] ?? '') === 'College');
                ?>
                <tr>
                  <td class="ps-4 fw-semibold text-muted small"><?= $rowNum++ ?></td>
                  <td>
                    <div class="d-inline-flex align-items-center gap-1">
                      <span class="student-id-mono text-dark fw-bold"><?= esc($idDisplay) ?></span>
                      <?php if (!empty($student['student_number'])): ?>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-0.5 small ms-1" style="font-size: 0.68rem;">
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
                          <?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name'], ENT_QUOTES, 'UTF-8') ?>
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
                    <span class="badge bg-success px-2.5 py-1 rounded-pill small">
                      Enrolled
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
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
