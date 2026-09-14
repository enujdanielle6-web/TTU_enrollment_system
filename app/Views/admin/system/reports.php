<?php
$pageTitle = 'Analytics & Institutional Reports - Administrator';
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
            <i class="bi bi-graph-up-arrow"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Analytics &amp; Institutional Reports</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-bar-chart-line-fill me-1"></i> Executive BI
              </span>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-clock-history me-1"></i> Real-Time Synced
              </span>
            </div>
            <p class="text-muted small mb-0">High-level statistical breakdown across enrollment admissions, finance ledgers, medical clearance, and academic demographics.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="sysadmin_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>Dashboard</span>
          </a>
          <div class="dropdown">
            <button class="btn btn-primary rounded-pill px-3.5 py-2 fw-semibold shadow-sm dropdown-toggle d-inline-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-download"></i>
              <span>Export Reports</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-1 py-1">
              <?php if (hasPermission('applications.view_queue')): ?>
                <li>
                  <a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="reports_export.php?type=applications">
                    <i class="bi bi-person-lines-fill text-primary"></i>
                    <span>Applications Ledger (CSV)</span>
                  </a>
                </li>
              <?php endif; ?>
              <?php if (hasPermission('payments.record')): ?>
                <li>
                  <a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="reports_export.php?type=payments">
                    <i class="bi bi-receipt text-success"></i>
                    <span>Payment Transactions (CSV)</span>
                  </a>
                </li>
                <li>
                  <a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="reports_export.php?type=balances">
                    <i class="bi bi-wallet2 text-danger"></i>
                    <span>Outstanding Balances (CSV)</span>
                  </a>
                </li>
              <?php endif; ?>
              <?php if (hasPermission(['scholarships.manage', 'payments.record'])): ?>
                <li>
                  <a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="reports_export.php?type=scholarships">
                    <i class="bi bi-award text-warning"></i>
                    <span>Granted Scholarships (CSV)</span>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <?php if (hasPermission(['scholarships.manage', 'payments.record'])): ?>
      <!-- Financial Overview Section -->
      <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-cash-coin text-success fs-5"></i>
        <h2 class="h6 fw-bold text-dark text-uppercase tracking-wider mb-0" style="font-size: 0.82rem; letter-spacing: 0.5px;">Institutional Financial Overview</h2>
      </div>

      <div class="row g-3 mb-4">
        <?php if (hasPermission('payments.record')): ?>
          <div class="col-sm-6 col-xl-3">
            <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
                <i class="bi bi-cash-stack"></i>
              </div>
              <div>
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Expected Revenue</div>
                <div class="h4 fw-bold text-dark mb-0">₱<?= number_format((float)($finData['expected_revenue'] ?? 0), 2) ?></div>
                <div class="text-muted small" style="font-size: 0.75rem;">Total Assessed Fees</div>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
                <i class="bi bi-bank"></i>
              </div>
              <div>
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Collected Revenue</div>
                <div class="h4 fw-bold text-success mb-0">₱<?= number_format((float)($finData['collected_payments'] ?? 0), 2) ?></div>
                <div class="text-muted small" style="font-size: 0.75rem;">Verified Cash &amp; Digital</div>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-xl-3">
            <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
                <i class="bi bi-exclamation-triangle"></i>
              </div>
              <div>
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Outstanding Receivables</div>
                <div class="h4 fw-bold text-danger mb-0">₱<?= number_format((float)($pendingFinData['outstanding_balance'] ?? 0), 2) ?></div>
                <div class="text-muted small" style="font-size: 0.75rem;"><?= esc($pendingFinData['pending_count'] ?? 0) ?> accounts unpaid</div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if (hasPermission(['scholarships.manage', 'payments.record'])): ?>
          <div class="col-sm-6 col-xl-3">
            <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
                <i class="bi bi-award-fill"></i>
              </div>
              <div>
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Scholarship Grants</div>
                <div class="h4 fw-bold text-dark mb-0">₱<?= number_format((float)($finData['total_scholarships'] ?? 0), 2) ?></div>
                <div class="text-muted small" style="font-size: 0.75rem;">Tuition Subsidies</div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Financial Distribution Tables Row -->
      <div class="row g-4 mb-4">
        <?php if (hasPermission(['scholarships.manage', 'payments.record'])): ?>
          <div class="col-lg-6">
            <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden h-100">
              <div class="p-3.5 px-4 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2.5">
                  <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-3" style="width: 34px; height: 34px; font-size: 1.1rem;">
                    <i class="bi bi-award"></i>
                  </div>
                  <h3 class="h6 fw-bold text-dark mb-0">Scholarship Distribution</h3>
                </div>
                <span class="badge bg-light text-secondary border rounded-pill px-2 py-0.5 small">
                  Active Awards
                </span>
              </div>
              <div class="table-responsive">
                <table class="table dashboard-table align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th scope="col" class="ps-4">Scholarship Name</th>
                      <th scope="col" class="text-center">Grantees</th>
                      <th scope="col" class="text-end pe-4">Total Subsidized</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($scholarshipData)): ?>
                      <tr>
                        <td colspan="3" class="text-center py-4 text-muted small">No active scholarships recorded.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($scholarshipData as $row): ?>
                        <tr>
                          <td class="ps-4 fw-medium text-dark"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                          <td class="text-center"><span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill"><?= esc($row['count']) ?></span></td>
                          <td class="text-end pe-4 fw-bold text-success">₱<?= number_format((float)$row['total_discount'], 2) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if (hasPermission('payments.record')): ?>
          <div class="col-lg-6">
            <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden h-100">
              <div class="p-3.5 px-4 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2.5">
                  <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 34px; height: 34px; font-size: 1.1rem;">
                    <i class="bi bi-wallet2"></i>
                  </div>
                  <h3 class="h6 fw-bold text-dark mb-0">Payment Method Breakdown</h3>
                </div>
                <span class="badge bg-light text-secondary border rounded-pill px-2 py-0.5 small">
                  Transaction Channels
                </span>
              </div>
              <div class="table-responsive">
                <table class="table dashboard-table align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th scope="col" class="ps-4">Method</th>
                      <th scope="col" class="text-center">Transactions</th>
                      <th scope="col" class="text-end pe-4">Total Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($paymentMethodData)): ?>
                      <tr>
                        <td colspan="3" class="text-center py-4 text-muted small">No payment transactions recorded yet.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($paymentMethodData as $row): ?>
                        <tr>
                          <td class="ps-4 fw-medium text-dark">
                            <span class="badge bg-secondary bg-opacity-10 text-dark border px-2.5 py-1 rounded-pill">
                              <?= htmlspecialchars($row['payment_method'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                          </td>
                          <td class="text-center"><span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill"><?= esc($row['count']) ?></span></td>
                          <td class="text-end pe-4 fw-bold text-success">₱<?= number_format((float)$row['total_amount'], 2) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (hasPermission('applications.view_queue')): ?>
      <!-- Medical Clearance Statistics -->
      <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden mb-4">
        <div class="p-3.5 px-4 border-bottom d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2.5">
            <div class="d-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-3" style="width: 34px; height: 34px; font-size: 1.1rem;">
              <i class="bi bi-heart-pulse-fill"></i>
            </div>
            <div>
              <h3 class="h6 fw-bold text-dark mb-0">University Clinic Medical Clearance Statistics</h3>
              <div class="text-muted small">Clinical health evaluation pipeline status for incoming students</div>
            </div>
          </div>
          <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
            Health Governance
          </span>
        </div>
        <div class="p-4">
          <div class="row g-3 text-center">
            <div class="col-6 col-md-4 col-lg-2">
              <div class="p-3 bg-light border rounded-3 h-100">
                <div class="fs-4 fw-bold text-dark mb-0"><?= esc($medicalData['total_records'] ?? 0) ?></div>
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Total Submissions</div>
              </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
              <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3 h-100">
                <div class="fs-4 fw-bold text-warning mb-0"><?= esc($medicalData['total_pending'] ?? 0) ?></div>
                <div class="text-warning small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Pending Visit</div>
              </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
              <div class="p-3 bg-info bg-opacity-10 border border-info border-opacity-25 rounded-3 h-100">
                <div class="fs-4 fw-bold text-info mb-0"><?= esc($medicalData['total_under_review'] ?? 0) ?></div>
                <div class="text-info small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Under Review</div>
              </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
              <div class="p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 h-100">
                <div class="fs-4 fw-bold text-success mb-0"><?= esc($medicalData['total_verified'] ?? 0) ?></div>
                <div class="text-success small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Verified / Cleared</div>
              </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
              <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3 h-100">
                <div class="fs-4 fw-bold text-dark mb-0"><?= esc($medicalData['total_corrections'] ?? 0) ?></div>
                <div class="text-dark small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Corrections</div>
              </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
              <div class="p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-3 h-100">
                <div class="fs-4 fw-bold text-danger mb-0"><?= esc($medicalData['total_rejected'] ?? 0) ?></div>
                <div class="text-danger small fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Rejected</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Admissions Pipeline Overview -->
      <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-people-fill text-primary fs-5"></i>
        <h2 class="h6 fw-bold text-dark text-uppercase tracking-wider mb-0" style="font-size: 0.82rem; letter-spacing: 0.5px;">Enrollment Admissions Pipeline</h2>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
              <i class="bi bi-patch-check-fill"></i>
            </div>
            <div>
              <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Officially Enrolled</div>
              <div class="h4 fw-bold text-success mb-0"><?= esc((int)($pipeline['total_enrolled'] ?? 0)) ?></div>
              <div class="text-muted small" style="font-size: 0.75rem;">Full Clearance Granted</div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-xl-3">
          <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
              <i class="bi bi-person-check-fill"></i>
            </div>
            <div>
              <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Approved Queue</div>
              <div class="h4 fw-bold text-primary mb-0"><?= esc((int)($pipeline['total_approved'] ?? 0)) ?></div>
              <div class="text-muted small" style="font-size: 0.75rem;">Ready For Assessment</div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-xl-3">
          <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
              <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
              <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Awaiting Review</div>
              <div class="h4 fw-bold text-warning mb-0"><?= esc((int)($pipeline['total_pending'] ?? 0)) ?></div>
              <div class="text-muted small" style="font-size: 0.75rem;">Pending Document Verification</div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-xl-3">
          <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
              <i class="bi bi-files"></i>
            </div>
            <div>
              <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Total Applications</div>
              <div class="h4 fw-bold text-dark mb-0"><?= esc((int)($pipeline['total_apps'] ?? 0)) ?></div>
              <div class="text-muted small" style="font-size: 0.75rem;">All Applicants Registered</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row 1: Line Chart (Submission Trend) -->
      <div class="row g-4 mb-4">
        <div class="col-12">
          <div class="dossier-card bg-white border rounded-4 shadow-sm p-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <div class="d-flex align-items-center gap-2.5">
                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 36px; height: 36px; font-size: 1.15rem;">
                  <i class="bi bi-graph-up"></i>
                </div>
                <div>
                  <h3 class="h6 fw-bold text-dark mb-0">14-Day Application Intake Velocity</h3>
                  <div class="text-muted small">Daily submission throughput registered across the applicant portal</div>
                </div>
              </div>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small">
                Last 14 Days
              </span>
            </div>
            <div style="position: relative; height: 300px; width: 100%;">
              <canvas id="trendChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row 2: Distribution breakdowns -->
      <div class="row g-4 mb-4">
        
        <!-- Program Distribution -->
        <div class="col-md-6 col-lg-4">
          <div class="dossier-card bg-white border rounded-4 shadow-sm p-4 h-100">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <div class="d-flex align-items-center gap-2.5">
                <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-3" style="width: 36px; height: 36px; font-size: 1.15rem;">
                  <i class="bi bi-bar-chart"></i>
                </div>
                <div>
                  <h3 class="h6 fw-bold text-dark mb-0">Program Strands</h3>
                  <div class="text-muted small">Applicant preferences</div>
                </div>
              </div>
            </div>
            <div style="position: relative; height: 260px; width: 100%;">
              <canvas id="strandChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Grade Demographics -->
        <div class="col-md-6 col-lg-4">
          <div class="dossier-card bg-white border rounded-4 shadow-sm p-4 h-100">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <div class="d-flex align-items-center gap-2.5">
                <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-3" style="width: 36px; height: 36px; font-size: 1.15rem;">
                  <i class="bi bi-pie-chart"></i>
                </div>
                <div>
                  <h3 class="h6 fw-bold text-dark mb-0">Grade Level Mix</h3>
                  <div class="text-muted small">SHS vs Higher Ed</div>
                </div>
              </div>
            </div>
            <div style="position: relative; height: 260px; width: 100%;">
              <canvas id="gradeChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Status Distribution -->
        <div class="col-md-12 col-lg-4">
          <div class="dossier-card bg-white border rounded-4 shadow-sm p-4 h-100">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <div class="d-flex align-items-center gap-2.5">
                <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-3" style="width: 36px; height: 36px; font-size: 1.15rem;">
                  <i class="bi bi-diagram-3"></i>
                </div>
                <div>
                  <h3 class="h6 fw-bold text-dark mb-0">Status Spread</h3>
                  <div class="text-muted small">Pipeline distribution</div>
                </div>
              </div>
            </div>
            <div style="position: relative; height: 260px; width: 100%;">
              <canvas id="statusChart"></canvas>
            </div>
          </div>
        </div>

      </div>
    <?php endif; ?>

  </div>
</main>

<?php if (hasPermission('applications.view_queue')): ?>
<!-- Load Chart.js from local vendor -->
<script src="/sia/public/vendor/chartjs/chart.umd.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Trend Chart Data
    <?php
      $trendLabels = [];
      $trendValues = [];
      if (!empty($trendData)) {
          foreach ($trendData as $data) {
              $trendLabels[] = date('M j', strtotime($data['submit_date']));
              $trendValues[] = (int) $data['count'];
          }
      }
    ?>
    const trendLabels = <?= json_encode($trendLabels) ?>;
    const trendValues = <?= json_encode($trendValues) ?>;

    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
      new Chart(trendCtx, {
          type: 'line',
          data: {
              labels: trendLabels,
              datasets: [{
                  label: 'Submissions',
                  data: trendValues,
                  borderColor: '#0d6efd',
                  backgroundColor: 'rgba(13, 110, 253, 0.08)',
                  borderWidth: 2.5,
                  pointBackgroundColor: '#0d6efd',
                  pointRadius: 3.5,
                  fill: true,
                  tension: 0.35
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: { display: false }
              },
              scales: {
                  y: {
                      beginAtZero: true,
                      ticks: { stepSize: 1, color: '#6c757d' },
                      grid: { color: 'rgba(0,0,0,0.04)' }
                  },
                  x: {
                      ticks: { color: '#6c757d' },
                      grid: { display: false }
                  }
              }
          }
      });
    }

    // 2. Strand Chart Data
    <?php
      $strandLabels = [];
      $strandValues = [];
      if (!empty($strandData)) {
          foreach ($strandData as $data) {
              $strandLabels[] = strtoupper($data['strand']);
              $strandValues[] = (int) $data['count'];
          }
      }
    ?>
    const strandLabels = <?= json_encode($strandLabels) ?>;
    const strandValues = <?= json_encode($strandValues) ?>;

    const strandCtx = document.getElementById('strandChart');
    if (strandCtx) {
      new Chart(strandCtx, {
          type: 'bar',
          data: {
              labels: strandLabels,
              datasets: [{
                  data: strandValues,
                  backgroundColor: [
                      '#0d6efd', '#20c997', '#ffc107', '#0dcaf0', '#6610f2', '#fd7e14'
                  ],
                  borderRadius: 6
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: { display: false }
              },
              scales: {
                  y: {
                      beginAtZero: true,
                      ticks: { stepSize: 1, color: '#6c757d' },
                      grid: { color: 'rgba(0,0,0,0.04)' }
                  },
                  x: {
                      ticks: { color: '#6c757d' },
                      grid: { display: false }
                  }
              }
          }
      });
    }

    // 3. Grade Chart Data
    <?php
      $gradeLabels = [];
      $gradeValues = [];
      if (!empty($gradeData)) {
          foreach ($gradeData as $data) {
              $gradeLabels[] = $data['grade_level'];
              $gradeValues[] = (int) $data['count'];
          }
      }
    ?>
    const gradeLabels = <?= json_encode($gradeLabels) ?>;
    const gradeValues = <?= json_encode($gradeValues) ?>;

    const gradeCtx = document.getElementById('gradeChart');
    if (gradeCtx) {
      new Chart(gradeCtx, {
          type: 'doughnut',
          data: {
              labels: gradeLabels,
              datasets: [{
                  data: gradeValues,
                  backgroundColor: ['#198754', '#0d6efd']
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: { position: 'bottom' }
              },
              cutout: '65%'
          }
      });
    }

    // 4. Status Chart Data
    <?php
      $statusLabels = [];
      $statusValues = [];
      if (!empty($statusDistData)) {
          foreach ($statusDistData as $data) {
              $statusLabels[] = formatApplicationStatus($data['status']);
              $statusValues[] = (int) $data['count'];
          }
      }
    ?>
    const statusLabels = <?= json_encode($statusLabels) ?>;
    const statusValues = <?= json_encode($statusValues) ?>;

    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
      new Chart(statusCtx, {
          type: 'pie',
          data: {
              labels: statusLabels,
              datasets: [{
                  data: statusValues,
                  backgroundColor: [
                      '#ffc107', '#0dcaf0', '#fd7e14', '#198754', '#dc3545', '#0d6efd'
                  ]
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: { position: 'bottom' }
              }
          }
      });
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
