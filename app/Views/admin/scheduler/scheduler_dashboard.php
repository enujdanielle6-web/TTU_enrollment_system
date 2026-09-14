<?php
$pageTitle = 'Scheduler Dashboard - Triple T University';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">

    <!-- Dossier Hero Header Strip (Admissions Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-calendar2-week-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Scheduler Dashboard</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> University Scheduler
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-calendar-check text-primary me-1"></i> AY <?= esc($systemSettings['active_school_year'] ?? '2026–2027') ?>
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-layers text-primary me-1"></i><?= number_format($stats['total_sections']) ?> Active Sections
              </span>
            </div>
            <p class="text-muted small mb-0">Class offerings, timetable scheduling, section allocations, and classroom capacity oversight.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="college_sections.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-mortarboard-fill text-primary"></i>
            <span>College Sections</span>
          </a>
          <a href="shs_sections.php" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-journal-bookmark-fill"></i>
            <span>SHS Sections</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Executive KPI Metric Cards (Consistent 4-Column Grid) -->
    <div class="row g-4 mb-4">
      
      <!-- Card 1: College Sections -->
      <div class="col-sm-6 col-xl-3">
        <a href="college_sections.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.1s;">
          <div class="stat-card-glow bg-primary"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-mortarboard-fill"></i>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-award me-1"></i> Degree Blocks
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['college_sections']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">College Sections</h2>
          <p class="text-muted small mb-0">Undergraduate class offerings</p>
          <div class="stat-card-footer">
            <span>Higher Education</span>
            <span class="stat-card-action text-primary">Manage College <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 2: SHS Sections -->
      <div class="col-sm-6 col-xl-3">
        <a href="shs_sections.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.15s;">
          <div class="stat-card-glow bg-info"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
              <i class="bi bi-journal-bookmark-fill"></i>
            </div>
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-bookmark-star me-1"></i> Basic Ed
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['shs_sections']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">SHS Sections</h2>
          <p class="text-muted small mb-0">Grades 11 & 12 tracks</p>
          <div class="stat-card-footer">
            <span>Secondary School</span>
            <span class="stat-card-action text-info">Manage SHS <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 3: Total Active Sections -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-diagram-3-fill"></i>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-check2-circle me-1"></i> Active Blocks
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['total_sections']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Total Active Sections</h2>
          <p class="text-muted small mb-0"><?= number_format($stats['total_capacity']) ?> seats allocated</p>
          <div class="stat-card-footer">
            <span>Capacity Roster</span>
            <span class="stat-card-action text-success"><?= number_format($stats['total_enrolled_sections']) ?> Enrolled</span>
          </div>
        </div>
      </div>

      <!-- Card 4: Scheduled Classes -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.25s;">
          <div class="stat-card-glow bg-warning"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-clock-history"></i>
            </div>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-calendar2-range me-1"></i> Class Slots
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['total_scheduled_subjects']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Scheduled Classes</h2>
          <p class="text-muted small mb-0"><?= $stats['college_scheduled_subjects'] ?> College • <?= $stats['shs_scheduled_subjects'] ?> SHS slots</p>
          <div class="stat-card-footer">
            <span>Timetable offerings</span>
            <span class="stat-card-action text-warning">Timetable Grid</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Active Class Sections Hubs (Dossier Card Styling) -->
    <div class="row g-4 mb-4">
      
      <!-- College Sections Hub -->
      <div class="col-lg-6">
        <div class="dossier-card h-100 fade-in-up" style="animation-delay: 0.3s;">
          <div class="dossier-card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2.5">
              <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-mortarboard-fill"></i>
              </div>
              <div>
                <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">College Sections</h2>
                <span class="badge bg-light text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold ms-2 align-middle">
                  <?= count($recentCollegeSections) ?> Active
                </span>
              </div>
            </div>
            <a href="college_sections.php" class="btn btn-sm btn-light border rounded-pill px-3 fw-medium text-dark d-inline-flex align-items-center gap-1">
              <span>View All</span>
              <i class="bi bi-chevron-right small text-muted"></i>
            </a>
          </div>

          <div class="p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 dashboard-table">
                <thead>
                  <tr>
                    <th class="ps-4">Section</th>
                    <th>Program & Year</th>
                    <th>Enrollment</th>
                    <th>Schedule</th>
                    <th class="text-end pe-4">Timetable</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentCollegeSections)): ?>
                    <tr>
                      <td colspan="5" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                          <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-inbox fs-2 text-muted"></i>
                          </div>
                          <h3 class="h6 fw-bold text-dark mb-1">No College Sections</h3>
                          <p class="small text-muted mb-0">Create new college sections in the Section Management console.</p>
                        </div>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($recentCollegeSections as $sec): 
                      $cap = max(1, (int)($sec['capacity'] ?? 40));
                      $enrolled = (int)($sec['current_enrollment'] ?? 0);
                      $percent = min(100, round(($enrolled / $cap) * 100));
                      $barColor = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-primary');
                    ?>
                      <tr>
                        <td class="ps-4">
                          <span class="applicant-ref-badge">
                            <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($sec['section_code'], ENT_QUOTES, 'UTF-8') ?>
                          </span>
                        </td>
                        <td>
                          <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-0.5 small fw-semibold">
                            <?= htmlspecialchars($sec['program_code'] ?? 'COL') ?>
                          </span>
                          <span class="small text-muted ms-1"><?= htmlspecialchars($sec['year_level'] ?? '') ?></span>
                        </td>
                        <td style="min-width: 140px;">
                          <div class="d-flex align-items-center justify-content-between small mb-1">
                            <span class="fw-bold text-dark"><?= $enrolled ?> / <?= $cap ?></span>
                            <span class="text-muted extra-small"><?= $percent ?>%</span>
                          </div>
                          <div class="progress" style="height: 5px; border-radius: 999px;">
                            <div class="progress-bar <?= $barColor ?>" style="width: <?= $percent ?>%"></div>
                          </div>
                        </td>
                        <td>
                          <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                            <i class="bi bi-clock me-1 text-muted"></i><?= htmlspecialchars($sec['schedule_type'] ?? 'Standard') ?>
                          </span>
                        </td>
                        <td class="text-end pe-4">
                          <a href="schedule_builder.php?id=<?= esc($sec['id']) ?>&type=college" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 extra-small fw-medium d-inline-flex align-items-center gap-1" title="Open Schedule Builder">
                            <i class="bi bi-calendar2-range"></i>
                            <span>Schedule</span>
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

      <!-- SHS Sections Hub -->
      <div class="col-lg-6">
        <div class="dossier-card h-100 fade-in-up" style="animation-delay: 0.35s;">
          <div class="dossier-card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2.5">
              <div class="dossier-header-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-journal-bookmark-fill"></i>
              </div>
              <div>
                <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Senior High Sections</h2>
                <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold ms-2 align-middle">
                  <?= count($recentShsSections) ?> Active
                </span>
              </div>
            </div>
            <a href="shs_sections.php" class="btn btn-sm btn-light border rounded-pill px-3 fw-medium text-dark d-inline-flex align-items-center gap-1">
              <span>View All</span>
              <i class="bi bi-chevron-right small text-muted"></i>
            </a>
          </div>

          <div class="p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 dashboard-table">
                <thead>
                  <tr>
                    <th class="ps-4">Section</th>
                    <th>Strand & Grade</th>
                    <th>Enrollment</th>
                    <th>Schedule</th>
                    <th class="text-end pe-4">Timetable</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentShsSections)): ?>
                    <tr>
                      <td colspan="5" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                          <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-inbox fs-2 text-muted"></i>
                          </div>
                          <h3 class="h6 fw-bold text-dark mb-1">No SHS Sections</h3>
                          <p class="small text-muted mb-0">Create new senior high sections in the Section Management console.</p>
                        </div>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($recentShsSections as $sec): 
                      $cap = max(1, (int)($sec['capacity'] ?? 40));
                      $enrolled = (int)($sec['current_enrollment'] ?? 0);
                      $percent = min(100, round(($enrolled / $cap) * 100));
                      $barColor = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-info');
                    ?>
                      <tr>
                        <td class="ps-4">
                          <span class="applicant-ref-badge">
                            <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($sec['section_code'], ENT_QUOTES, 'UTF-8') ?>
                          </span>
                        </td>
                        <td>
                          <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2 py-0.5 small fw-semibold">
                            <?= htmlspecialchars($sec['program_code'] ?? 'SHS') ?>
                          </span>
                          <span class="small text-muted ms-1"><?= htmlspecialchars($sec['grade_level'] ?? '') ?></span>
                        </td>
                        <td style="min-width: 140px;">
                          <div class="d-flex align-items-center justify-content-between small mb-1">
                            <span class="fw-bold text-dark"><?= $enrolled ?> / <?= $cap ?></span>
                            <span class="text-muted extra-small"><?= $percent ?>%</span>
                          </div>
                          <div class="progress" style="height: 5px; border-radius: 999px;">
                            <div class="progress-bar <?= $barColor ?>" style="width: <?= $percent ?>%"></div>
                          </div>
                        </td>
                        <td>
                          <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                            <i class="bi bi-clock me-1 text-muted"></i><?= htmlspecialchars($sec['schedule_type'] ?? 'Standard') ?>
                          </span>
                        </td>
                        <td class="text-end pe-4">
                          <a href="schedule_builder.php?id=<?= esc($sec['id']) ?>&type=shs" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 extra-small fw-medium d-inline-flex align-items-center gap-1" title="Open Schedule Builder">
                            <i class="bi bi-calendar2-range"></i>
                            <span>Schedule</span>
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

    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
