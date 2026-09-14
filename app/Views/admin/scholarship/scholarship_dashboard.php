<?php
$pageTitle = 'Scholarship Dashboard - Triple T University';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">

    <!-- Dossier Hero Header Strip (Design System Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-award-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Scholarship Dashboard</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> Scholarships & Financial Aid
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-calendar-check text-primary me-1"></i> AY <?= esc($systemSettings['active_school_year'] ?? '2026–2027') ?>
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-patch-check text-primary me-1"></i><?= number_format($stats['active_scholarships']) ?> Active Programs
              </span>
            </div>
            <p class="text-muted small mb-0">Institutional scholarship programs, financial grants, applicant evaluations, and tuition discount management.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="scholarships.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-gear-fill text-primary"></i>
            <span>Manage Types</span>
          </a>
          <a href="scholarship_review.php" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-inbox-fill"></i>
            <span>Review Applications</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Executive KPI Metric Cards (Consistent 4-Column Grid) -->
    <div class="row g-4 mb-4">
      
      <!-- Card 1: Active Programs -->
      <div class="col-sm-6 col-xl-3">
        <a href="scholarships.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.1s;">
          <div class="stat-card-glow bg-primary"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-award-fill"></i>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-bookmark-star me-1"></i> Aid & Grants
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['active_scholarships']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Active Programs</h2>
          <p class="text-muted small mb-0"><?= number_format($stats['total_slots']) ?> Total capacity slots</p>
          <div class="stat-card-footer">
            <span>Programs Catalog</span>
            <span class="stat-card-action text-primary">Manage Types <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 2: Active Scholars -->
      <div class="col-sm-6 col-xl-3">
        <a href="scholars.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.15s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-people-fill"></i>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-check-circle-fill me-1"></i> Awardees
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['active_scholars']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Active Scholars</h2>
          <p class="text-muted small mb-0">Enrolled grant recipients</p>
          <div class="stat-card-footer">
            <span>Recipient Registry</span>
            <span class="stat-card-action text-success">View Scholars <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 3: Pending Review -->
      <div class="col-sm-6 col-xl-3">
        <a href="scholarship_review.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow bg-warning"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-hourglass-split"></i>
            </div>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-clock-history me-1"></i> Needs Action
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['pending_review']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Pending Review</h2>
          <p class="text-muted small mb-0">Applications requiring evaluation</p>
          <div class="stat-card-footer">
            <span>Review Queue</span>
            <span class="stat-card-action text-warning">Process Queue <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 4: Total Applications -->
      <div class="col-sm-6 col-xl-3">
        <a href="scholarship_review.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.25s;">
          <div class="stat-card-glow bg-info"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
              <i class="bi bi-file-earmark-check-fill"></i>
            </div>
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-inbox me-1"></i> Total Volume
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['total_applications']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Total Applications</h2>
          <p class="text-muted small mb-0"><?= number_format($stats['approved_applications']) ?> Approved grants</p>
          <div class="stat-card-footer">
            <span>Application Logs</span>
            <span class="stat-card-action text-info">View Records <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

    </div>

    <!-- Active Scholarship Hubs (Dossier Card Styling) -->
    <div class="row g-4 mb-4">
      
      <!-- Scholarship Programs Hub -->
      <div class="col-lg-6">
        <div class="dossier-card h-100 fade-in-up" style="animation-delay: 0.3s;">
          <div class="dossier-card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2.5">
              <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-award-fill"></i>
              </div>
              <div>
                <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Scholarship Programs</h2>
                <span class="badge bg-light text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold ms-2 align-middle">
                  <?= count($recentScholarships) ?> Active
                </span>
              </div>
            </div>
            <a href="scholarships.php" class="btn btn-sm btn-light border rounded-pill px-3 fw-medium text-dark d-inline-flex align-items-center gap-1">
              <span>Manage All</span>
              <i class="bi bi-chevron-right small text-muted"></i>
            </a>
          </div>

          <div class="p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 dashboard-table">
                <thead>
                  <tr>
                    <th class="ps-4">Program</th>
                    <th>Category</th>
                    <th>Coverage</th>
                    <th>Beneficiaries / Slots</th>
                    <th class="text-end pe-4">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentScholarships)): ?>
                    <tr>
                      <td colspan="5" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                          <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-inbox fs-2 text-muted"></i>
                          </div>
                          <h3 class="h6 fw-bold text-dark mb-1">No Scholarship Programs</h3>
                          <p class="small text-muted mb-0">Use "Manage Types" to configure available scholarships.</p>
                        </div>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($recentScholarships as $prog): 
                      $slots = (int)($prog['slots'] ?? 0);
                      $recipients = (int)($prog['recipient_count'] ?? 0);
                      $percent = $slots > 0 ? min(100, round(($recipients / $slots) * 100)) : 0;
                      $barColor = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-primary');
                    ?>
                      <tr>
                        <td class="ps-4">
                          <div class="fw-bold text-dark"><?= htmlspecialchars($prog['name'], ENT_QUOTES, 'UTF-8') ?></div>
                          <span class="applicant-ref-badge extra-small mt-1">
                            <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?>
                          </span>
                        </td>
                        <td>
                          <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                            <?= htmlspecialchars($prog['category'] ?? 'School-Based') ?>
                          </span>
                        </td>
                        <td>
                          <span class="fw-bold text-success">
                            <?php if ($prog['tuition_coverage_type'] === 'percentage'): ?>
                              <?= number_format((float)$prog['tuition_coverage_value'], 0) ?>% Tuition
                            <?php elseif ($prog['tuition_coverage_type'] === 'fixed'): ?>
                              ₱<?= number_format((float)$prog['tuition_coverage_value'], 2) ?>
                            <?php else: ?>
                              Full Tuition
                            <?php endif; ?>
                          </span>
                        </td>
                        <td style="min-width: 130px;">
                          <div class="d-flex align-items-center justify-content-between small mb-1">
                            <span class="fw-bold text-dark"><?= $recipients ?> / <?= $slots > 0 ? $slots : '&infin;' ?></span>
                            <?php if ($slots > 0): ?>
                              <span class="text-muted extra-small"><?= $percent ?>%</span>
                            <?php endif; ?>
                          </div>
                          <?php if ($slots > 0): ?>
                            <div class="progress" style="height: 5px; border-radius: 999px;">
                              <div class="progress-bar <?= $barColor ?>" style="width: <?= $percent ?>%"></div>
                            </div>
                          <?php else: ?>
                            <span class="extra-small text-muted">Open Capacity</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                          <?php if ($prog['status'] === 'Active'): ?>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                              <i class="bi bi-check-circle-fill me-1"></i>Active
                            </span>
                          <?php else: ?>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                              <?= htmlspecialchars($prog['status'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                          <?php endif; ?>
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

      <!-- Recent Applications & Review Queue Hub -->
      <div class="col-lg-6">
        <div class="dossier-card h-100 fade-in-up" style="animation-delay: 0.35s;">
          <div class="dossier-card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2.5">
              <div class="dossier-header-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-inbox-fill"></i>
              </div>
              <div>
                <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Applications Queue</h2>
                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold ms-2 align-middle">
                  <?= $stats['pending_review'] ?> Awaiting Action
                </span>
              </div>
            </div>
            <a href="scholarship_review.php" class="btn btn-sm btn-light border rounded-pill px-3 fw-medium text-dark d-inline-flex align-items-center gap-1">
              <span>Full Queue</span>
              <i class="bi bi-chevron-right small text-muted"></i>
            </a>
          </div>

          <div class="p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 dashboard-table">
                <thead>
                  <tr>
                    <th class="ps-4">Applicant</th>
                    <th>Scholarship</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentApplications)): ?>
                    <tr>
                      <td colspan="4" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                          <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-inbox fs-2 text-muted"></i>
                          </div>
                          <h3 class="h6 fw-bold text-dark mb-1">No Applications Filed</h3>
                          <p class="small text-muted mb-0">Student scholarship submissions will automatically queue here.</p>
                        </div>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($recentApplications as $app): 
                      $fLetter = !empty($app['first_name']) ? strtoupper(substr($app['first_name'], 0, 1)) : 'S';
                      $lLetter = !empty($app['last_name']) ? strtoupper(substr($app['last_name'], 0, 1)) : 'A';
                      $statusClass = match($app['status']) {
                        'approved' => 'bg-success bg-opacity-10 text-success border-success',
                        'rejected' => 'bg-danger bg-opacity-10 text-danger border-danger',
                        'under_review' => 'bg-info bg-opacity-10 text-info border-info',
                        default => 'bg-warning bg-opacity-10 text-warning border-warning'
                      };
                      $statusLabel = match($app['status']) {
                        'under_review' => 'Under Review',
                        default => ucfirst($app['status'])
                      };
                    ?>
                      <tr>
                        <td class="ps-4">
                          <div class="d-flex align-items-center gap-2.5">
                            <div class="applicant-avatar bg-primary bg-opacity-10 text-primary fw-bold" style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                              <?= esc($fLetter . $lLetter) ?>
                            </div>
                            <div>
                              <div class="fw-bold text-dark"><?= htmlspecialchars($app['last_name'] . ', ' . $app['first_name'], ENT_QUOTES, 'UTF-8') ?></div>
                              <span class="applicant-ref-badge extra-small">
                                <i class="bi bi-person-badge text-muted me-1"></i><?= htmlspecialchars($app['student_number'] ?? $app['email'], ENT_QUOTES, 'UTF-8') ?>
                              </span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <div class="fw-semibold text-dark small"><?= htmlspecialchars($app['scholarship_name'], ENT_QUOTES, 'UTF-8') ?></div>
                          <span class="badge bg-light text-secondary border rounded-pill extra-small">
                            <?= htmlspecialchars($app['category'] ?? 'General') ?>
                          </span>
                        </td>
                        <td>
                          <span class="badge <?= esc($statusClass) ?> border border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                            <?= esc($statusLabel) ?>
                          </span>
                        </td>
                        <td class="text-end pe-4">
                          <a href="scholarship_detail.php?id=<?= esc($app['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 extra-small fw-medium d-inline-flex align-items-center gap-1" title="Review Application">
                            <i class="bi bi-eye"></i>
                            <span>Review</span>
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
