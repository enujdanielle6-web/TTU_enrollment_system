<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission(['students.view', 'applications.view_queue', 'payments.record', 'scholarships.manage']);

$pageTitle = 'Enrollment Reports - Administrator';

try {
    // 1. Overall Pipeline Totals
    $pipelineStmt = $pdo->query('
        SELECT 
            COUNT(*) as total_apps,
            SUM(CASE WHEN status = "pending" OR status = "under_review" THEN 1 ELSE 0 END) as total_pending,
            SUM(CASE WHEN status = "correction_required" THEN 1 ELSE 0 END) as total_corrections,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as total_approved,
            SUM(CASE WHEN status = "enrolled" THEN 1 ELSE 0 END) as total_enrolled,
            SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as total_rejected
        FROM applications
    ');
    $pipeline = $pipelineStmt->fetch();
    
    // 2. Program Distribution (All applications)
    $strandStmt = $pdo->query('
        SELECT strand, COUNT(*) as count 
        FROM applications 
        WHERE strand IS NOT NULL AND strand != ""
        GROUP BY strand 
        ORDER BY count DESC
    ');
    $strandData = $strandStmt->fetchAll();

    // 3. Level/Grade Distribution (All applications)
    $gradeStmt = $pdo->query('
        SELECT grade_level, COUNT(*) as count 
        FROM applications 
        WHERE grade_level IS NOT NULL AND grade_level != ""
        GROUP BY grade_level 
        ORDER BY grade_level ASC
    ');
    $gradeData = $gradeStmt->fetchAll();
    
    // 4. Daily Enrollment Trend (Last 14 Days)
    $trendStmt = $pdo->query('
        SELECT DATE(created_at) as submit_date, COUNT(*) as count 
        FROM applications 
        WHERE created_at >= DATE(NOW()) - INTERVAL 14 DAY
        GROUP BY DATE(created_at)
        ORDER BY submit_date ASC
    ');
    $trendData = $trendStmt->fetchAll();

    // 5. Status Distribution
    $statusDistStmt = $pdo->query('
        SELECT status, COUNT(*) as count 
        FROM applications 
        GROUP BY status
    ');
    $statusDistData = $statusDistStmt->fetchAll();

    // 6. Financial Overview
    $finStmt = $pdo->query('
        SELECT 
            COALESCE(SUM(sa.total_amount), 0) as expected_revenue,
            COALESCE(SUM(sa.discount_amount), 0) as total_scholarships,
            COALESCE(SUM(sa.total_paid), 0) as collected_payments
        FROM student_assessments sa
    ');
    $finData = $finStmt->fetch();

    $pendingPaymentsStmt = $pdo->query('
        SELECT 
            COUNT(*) as pending_count,
            COALESCE(SUM(net_amount - total_paid), 0) as outstanding_balance
        FROM student_assessments 
        WHERE payment_status != "paid"
    ');
    $pendingFinData = $pendingPaymentsStmt->fetch();
    
    // 7. Scholarship Distribution
    $schDistStmt = $pdo->query('
        SELECT s.name, COUNT(*) as count, SUM(sa.discount_amount) as total_discount
        FROM student_assessments sa
        INNER JOIN scholarships s ON sa.scholarship_id = s.id
        GROUP BY s.id
        ORDER BY total_discount DESC
    ');
    $scholarshipData = $schDistStmt->fetchAll();
    
    // 8. Payment Methods
    $payMethodStmt = $pdo->query('
        SELECT payment_method, COUNT(*) as count, SUM(amount) as total_amount
        FROM payment_records
        GROUP BY payment_method
        ORDER BY total_amount DESC
    ');
    $paymentMethodData = $payMethodStmt->fetchAll();

    // 9. Medical Clearance Statistics
    $medicalStmt = $pdo->query('
        SELECT 
            COUNT(*) as total_records,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as total_pending,
            SUM(CASE WHEN status = "under_review" THEN 1 ELSE 0 END) as total_under_review,
            SUM(CASE WHEN status = "verified" THEN 1 ELSE 0 END) as total_verified,
            SUM(CASE WHEN status = "correction_required" THEN 1 ELSE 0 END) as total_corrections,
            SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as total_rejected
        FROM health_records
    ');
    $medicalData = $medicalStmt->fetch();

} catch (PDOException $e) {
    error_log('Admin reports fetch failed: ' . $e->getMessage());
    showErrorPage('Reports Generation Failed', 'A database error occurred while querying the analytics databases.');
}

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">Analytics & Reports</h1>
          <p class="text-muted mb-0">High-level statistical overview of the enrollment pipeline.</p>
        </div>
        <div class="dropdown">
          <button class="btn btn-primary fw-medium rounded-pill shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-download me-1"></i> Export Data
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0">
            <?php if (hasPermission('applications.view_queue')): ?>
            <li><a class="dropdown-item" href="reports_export.php?type=applications"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Applications</a></li>
            <?php endif; ?>
            <?php if (hasPermission('payments.record')): ?>
            <li><a class="dropdown-item" href="reports_export.php?type=payments"><i class="bi bi-receipt me-2 text-success"></i>Payment History</a></li>
            <li><a class="dropdown-item" href="reports_export.php?type=balances"><i class="bi bi-wallet2 me-2 text-danger"></i>Outstanding Balances</a></li>
            <?php endif; ?>
            <?php if (hasPermission(['scholarships.manage', 'payments.record'])): ?>
            <li><a class="dropdown-item" href="reports_export.php?type=scholarships"><i class="bi bi-award me-2 text-warning"></i>Granted Scholarships</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>

    <?php if (hasPermission(['scholarships.manage', 'payments.record'])): ?>
    <!-- Financial Overview Cards -->
    <div class="row g-4 mb-4">
      <?php if (hasPermission('payments.record')): ?>
      <div class="col-md-6 col-lg-3">
        <div class="island minimal-card text-center py-4 h-100">
          <div class="text-info mb-3">
            <i class="bi bi-cash-stack display-6"></i>
          </div>
          <h2 class="h3 fw-bold text-dark mb-1">₱<?= number_format((float)$finData['expected_revenue'], 2) ?></h2>
          <p class="text-muted small text-uppercase tracking-wide mb-0">Expected Revenue</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="island minimal-card text-center py-4 h-100">
          <div class="text-success mb-3">
            <i class="bi bi-bank display-6"></i>
          </div>
          <h2 class="h3 fw-bold text-dark mb-1">₱<?= number_format((float)$finData['collected_payments'], 2) ?></h2>
          <p class="text-muted small text-uppercase tracking-wide mb-0">Collected Revenue</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="island minimal-card text-center py-4 h-100">
          <div class="text-danger mb-3">
            <i class="bi bi-exclamation-triangle display-6"></i>
          </div>
          <h2 class="h3 fw-bold text-dark mb-1">₱<?= number_format((float)$pendingFinData['outstanding_balance'], 2) ?></h2>
          <p class="text-muted small text-uppercase tracking-wide mb-0">Outstanding (<?= $pendingFinData['pending_count'] ?>)</p>
        </div>
      </div>
      <?php endif; ?>
      <?php if (hasPermission(['scholarships.manage', 'payments.record'])): ?>
      <div class="col-md-6 col-lg-3">
        <div class="island minimal-card text-center py-4 h-100">
          <div class="text-warning mb-3">
            <i class="bi bi-award-fill display-6"></i>
          </div>
          <h2 class="h3 fw-bold text-dark mb-1">₱<?= number_format((float)$finData['total_scholarships'], 2) ?></h2>
          <p class="text-muted small text-uppercase tracking-wide mb-0">Scholarships</p>
        </div>
      </div>
      <?php endif; ?>
    </div>
    
    <div class="row g-4 mb-4">
      <?php if (hasPermission(['scholarships.manage', 'payments.record'])): ?>
      <div class="col-lg-6">
        <div class="island position-relative overflow-hidden border-0 shadow-sm h-100 rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header">
            <i class="bi bi-award text-warning"></i>
            <h2 class="mb-0 text-dark">Scholarship Distribution</h2>
          </div>
          <div class="island-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 custom-table">
                <thead class="table-light">
                  <tr>
                    <th scope="col" class="ps-4">Scholarship Name</th>
                    <th scope="col" class="text-center">Count</th>
                    <th scope="col" class="text-end pe-4">Total Discount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($scholarshipData)): ?>
                  <tr>
                    <td colspan="3" class="text-center py-5 text-muted">
                      <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                      No scholarships granted yet.
                    </td>
                  </tr>
                  <?php else: ?>
                    <?php foreach ($scholarshipData as $row): ?>
                    <tr>
                      <td class="ps-4 fw-medium"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="text-center"><?= $row['count'] ?></td>
                      <td class="text-end pe-4 fw-bold text-success">₱<?= number_format((float)$row['total_discount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <?php if (hasPermission('payments.record')): ?>
      <div class="col-lg-6">
        <div class="island position-relative overflow-hidden border-0 shadow-sm h-100 rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header">
            <i class="bi bi-wallet2 text-primary"></i>
            <h2 class="mb-0 text-dark">Payment Methods</h2>
          </div>
          <div class="island-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 custom-table">
                <thead class="table-light">
                  <tr>
                    <th scope="col" class="ps-4">Method</th>
                    <th scope="col" class="text-center">Transactions</th>
                    <th scope="col" class="text-end pe-4">Total Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($paymentMethodData)): ?>
                  <tr>
                    <td colspan="3" class="text-center py-5 text-muted">
                      <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                      No payments recorded yet.
                    </td>
                  </tr>
                  <?php else: ?>
                    <?php foreach ($paymentMethodData as $row): ?>
                    <tr>
                      <td class="ps-4 fw-medium"><?= htmlspecialchars($row['payment_method'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="text-center"><?= $row['count'] ?></td>
                      <td class="text-end pe-4 fw-bold text-success">₱<?= number_format((float)$row['total_amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <?php if (hasPermission('applications.view_queue')): ?>
    <!-- Medical Clearance Statistics -->
    <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light">
        <i class="bi bi-heart-pulse-fill text-danger"></i>
        <h2 class="mb-0 text-dark">Medical Clearance Statistics</h2>
      </div>
      <div class="island-body">
        <div class="row g-3 text-center">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="p-3 bg-light rounded-3 h-100">
                    <div class="fs-4 fw-bold text-dark mb-1"><?= $medicalData['total_records'] ?? 0 ?></div>
                    <div class="small fw-semibold text-muted text-uppercase">Total Submissions</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="p-3 bg-warning bg-opacity-10 rounded-3 h-100">
                    <div class="fs-4 fw-bold text-warning mb-1"><?= $medicalData['total_pending'] ?? 0 ?></div>
                    <div class="small fw-semibold text-warning text-uppercase">Pending Visit</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="p-3 bg-info bg-opacity-10 rounded-3 h-100">
                    <div class="fs-4 fw-bold text-info mb-1"><?= $medicalData['total_under_review'] ?? 0 ?></div>
                    <div class="small fw-semibold text-info text-uppercase">Under Review</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="p-3 bg-success bg-opacity-10 rounded-3 h-100">
                    <div class="fs-4 fw-bold text-success mb-1"><?= $medicalData['total_verified'] ?? 0 ?></div>
                    <div class="small fw-semibold text-success text-uppercase">Verified</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="p-3 bg-warning rounded-3 h-100 text-dark">
                    <div class="fs-4 fw-bold mb-1"><?= $medicalData['total_corrections'] ?? 0 ?></div>
                    <div class="small fw-semibold text-uppercase">Corrections</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="p-3 bg-danger bg-opacity-10 rounded-3 h-100">
                    <div class="fs-4 fw-bold text-danger mb-1"><?= $medicalData['total_rejected'] ?? 0 ?></div>
                    <div class="small fw-semibold text-danger text-uppercase">Rejected</div>
                </div>
            </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if (hasPermission('applications.view_queue')): ?>
    <!-- Admissions Overview Cards -->
    <div class="row g-4 mb-4">
      <div class="col-md-6 col-lg-3">
        <div class="island minimal-card text-center py-4 h-100">
          <div class="text-success mb-3">
            <i class="bi bi-patch-check-fill display-6"></i>
          </div>
          <h2 class="h2 fw-bold text-dark mb-1"><?= (int) $pipeline['total_enrolled'] ?></h2>
          <p class="text-muted small text-uppercase tracking-wide mb-0">Officially Enrolled</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="island minimal-card text-center py-4 h-100">
          <div class="text-primary mb-3">
            <i class="bi bi-person-check-fill display-6"></i>
          </div>
          <h2 class="h2 fw-bold text-dark mb-1"><?= (int) $pipeline['total_approved'] ?></h2>
          <p class="text-muted small text-uppercase tracking-wide mb-0">Approved Queue</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="island minimal-card text-center py-4 h-100">
          <div class="text-warning mb-3">
            <i class="bi bi-hourglass-split display-6"></i>
          </div>
          <h2 class="h2 fw-bold text-dark mb-1"><?= (int) $pipeline['total_pending'] ?></h2>
          <p class="text-muted small text-uppercase tracking-wide mb-0">Awaiting Action</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="island minimal-card text-center py-4 h-100">
          <div class="text-dark mb-3">
            <i class="bi bi-files display-6"></i>
          </div>
          <h2 class="h2 fw-bold text-dark mb-1"><?= (int) $pipeline['total_apps'] ?></h2>
          <p class="text-muted small text-uppercase tracking-wide mb-0">Total Submissions</p>
        </div>
      </div>
    </div>

    <!-- Charts Row 1: Line Chart (Submission Trend) -->
    <div class="row g-4 mb-4">
      <div class="col-12">
        <div class="island position-relative overflow-hidden border-0 shadow-sm" style="border-radius: 16px;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light">
            <i class="bi bi-graph-up text-primary"></i>
            <h2>14-Day Application Trend</h2>
          </div>
          <div class="island-body mt-2">
            <div style="position: relative; height: 320px; width: 100%;">
              <canvas id="trendChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row 2: Distribution breakdowns -->
    <div class="row g-4">
      
      <!-- Program Distribution -->
      <div class="col-md-6 col-lg-4">
        <div class="island position-relative overflow-hidden border-0 shadow-sm h-100" style="border-radius: 16px;">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header">
            <i class="bi bi-bar-chart text-info"></i>
            <h2>Program Distribution</h2>
          </div>
          <div class="island-body mt-2 text-center">
            <div style="position: relative; height: 260px; width: 100%;">
              <canvas id="strandChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Grade Demographics -->
      <div class="col-md-6 col-lg-4">
        <div class="island position-relative overflow-hidden border-0 shadow-sm h-100" style="border-radius: 16px;">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header">
            <i class="bi bi-pie-chart text-success"></i>
            <h2>Level/Grade Demographics</h2>
          </div>
          <div class="island-body mt-2 text-center">
            <div style="position: relative; height: 260px; width: 100%; display: inline-block;">
              <canvas id="gradeChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Status Distribution -->
      <div class="col-md-12 col-lg-4">
        <div class="island position-relative overflow-hidden border-0 shadow-sm h-100" style="border-radius: 16px;">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header">
            <i class="bi bi-diagram-3 text-warning"></i>
            <h2>Status Distribution</h2>
          </div>
          <div class="island-body mt-2 text-center">
            <div style="position: relative; height: 260px; width: 100%; display: inline-block;">
              <canvas id="statusChart"></canvas>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
</main>

<!-- Load Chart.js from secure CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Trend Chart Data
    <?php
      $trendLabels = [];
      $trendValues = [];
      foreach ($trendData as $data) {
          $trendLabels[] = date('M j', strtotime($data['submit_date']));
          $trendValues[] = (int) $data['count'];
      }
    ?>
    const trendLabels = <?= json_encode($trendLabels) ?>;
    const trendValues = <?= json_encode($trendValues) ?>;

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Submissions',
                data: trendValues,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.05)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
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
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // 2. Strand Chart Data
    <?php
      $strandLabels = [];
      $strandValues = [];
      foreach ($strandData as $data) {
          $strandLabels[] = strtoupper($data['strand']);
          $strandValues[] = (int) $data['count'];
      }
    ?>
    const strandLabels = <?= json_encode($strandLabels) ?>;
    const strandValues = <?= json_encode($strandValues) ?>;

    new Chart(document.getElementById('strandChart'), {
        type: 'bar',
        data: {
            labels: strandLabels,
            datasets: [{
                data: strandValues,
                backgroundColor: [
                    '#0d6efd', '#20c997', '#ffc107', '#0dcaf0', '#6610f2', '#fd7e14'
                ]
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
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // 3. Grade Chart Data
    <?php
      $gradeLabels = [];
      $gradeValues = [];
      foreach ($gradeData as $data) {
          $gradeLabels[] = $data['grade_level'];
          $gradeValues[] = (int) $data['count'];
      }
    ?>
    const gradeLabels = <?= json_encode($gradeLabels) ?>;
    const gradeValues = <?= json_encode($gradeValues) ?>;

    new Chart(document.getElementById('gradeChart'), {
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
            }
        }
    });

    // 4. Status Chart Data
    <?php
      $statusLabels = [];
      $statusValues = [];
      foreach ($statusDistData as $data) {
          $statusLabels[] = formatApplicationStatus($data['status']);
          $statusValues[] = (int) $data['count'];
      }
    ?>
    const statusLabels = <?= json_encode($statusLabels) ?>;
    const statusValues = <?= json_encode($statusValues) ?>;

    new Chart(document.getElementById('statusChart'), {
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
});
</script>
<?php endif; ?>

<style>
/* Minimalist UI Enhancements */
.island {
    background-color: #ffffff;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.minimal-card {
    border: 1px solid #e9ecef;
}
.minimal-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border-color: #dee2e6;
}

.tracking-wide {
    letter-spacing: 0.05em;
}

@media print {
  body { background-color: #fff !important; }
  .main-navbar, .btn { display: none !important; }
  .island { border: 1px solid #ddd !important; box-shadow: none !important; margin-bottom: 20px !important; }
  .container-fluid { padding: 0 !important; }
}
</style>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

