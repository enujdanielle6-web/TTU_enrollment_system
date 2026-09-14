<?php
$pageTitle = 'Scholarship Applications - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-inbox-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Scholarship Applications Review</h1>
              <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-hourglass-split me-1"></i> Review Queue
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-clock-history text-warning me-1"></i><?= $stats['pending'] ?? 0 ?> Awaiting Review
              </span>
            </div>
            <p class="text-muted small mb-0">Evaluate student financial aid applications, review uploaded verification documents, and authorize awards.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="scholarship_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-speedometer2 text-primary"></i>
            <span>Dashboard</span>
          </a>
          <a href="scholarships.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-award text-primary"></i>
            <span>Programs</span>
          </a>
        </div>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
        <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
        <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <!-- Scholarship Statistics Row (Consistent 4-Column Grid) -->
    <div class="row g-4 mb-4">
      
      <!-- Card 1: Pending Review -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.1s;">
          <div class="stat-card-glow bg-warning"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-hourglass-split"></i>
            </div>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-clock me-1"></i> Awaiting Action
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= esc($stats['pending'] ?? 0) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Pending Review</h2>
          <p class="text-muted small mb-0">Needs administrative evaluation</p>
          <div class="stat-card-footer">
            <span>Action Required</span>
            <span class="stat-card-action text-warning">Process Now</span>
          </div>
        </div>
      </div>

      <!-- Card 2: Approved -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.15s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-check-circle-fill"></i>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-award me-1"></i> Authorized
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= esc($stats['approved'] ?? 0) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Approved Applications</h2>
          <p class="text-muted small mb-0">Discount applied to assessment</p>
          <div class="stat-card-footer">
            <span>Authorized Grants</span>
            <span class="stat-card-action text-success">Fee Deductions</span>
          </div>
        </div>
      </div>

      <!-- Card 3: Rejected -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow bg-danger"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger">
              <i class="bi bi-x-circle-fill"></i>
            </div>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-dash-circle me-1"></i> Ineligible
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= esc($stats['rejected'] ?? 0) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Rejected Submissions</h2>
          <p class="text-muted small mb-0">Declined or incomplete documents</p>
          <div class="stat-card-footer">
            <span>Disqualified</span>
            <span class="stat-card-action text-danger">Audit Record</span>
          </div>
        </div>
      </div>

      <!-- Card 4: Total Applications -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.25s;">
          <div class="stat-card-glow bg-primary"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-inbox-fill"></i>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-collection me-1"></i> Cumulative
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= esc($stats['total'] ?? 0) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Total Submissions</h2>
          <p class="text-muted small mb-0">All terms scholarship submissions</p>
          <div class="stat-card-footer">
            <span>Lifecycle Count</span>
            <span class="stat-card-action text-primary">All Records</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Applications Queue Card (Dossier Card Styling) -->
    <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.3s;">
      <div class="dossier-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-warning bg-opacity-10 text-warning">
            <i class="bi bi-inbox-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Applications Review Queue</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <?= count($applications ?? []) ?> Records
            </span>
          </div>
        </div>
        
        <div class="d-flex align-items-center gap-2">
          <select id="statusFilter" class="form-select form-select-sm" style="width: 160px;">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="under_review">Under Review</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
          <div class="input-group input-group-sm" style="width: 220px;">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" id="appSearch" class="form-control form-control-sm border-start-0 ps-0" placeholder="Search applicant...">
          </div>
        </div>
      </div>
      
      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table">
            <thead>
              <tr>
                <th class="ps-4">Applicant</th>
                <th>Scholarship Type</th>
                <th>Term</th>
                <th>Status</th>
                <th>Date Applied</th>
                <th class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($applications)): ?>
                <tr>
                  <td colspan="6" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">No Applications Found</h3>
                      <p class="small text-muted mb-0">Student scholarship submissions will automatically appear here for review.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($applications as $app): 
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
                  <tr data-status="<?= esc($app['status']) ?>">
                    <td class="ps-4">
                      <div class="d-flex align-items-center gap-2.5">
                        <div class="applicant-avatar bg-primary bg-opacity-10 text-primary fw-bold" style="width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
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
                      <div class="fw-semibold text-dark"><?= htmlspecialchars($app['scholarship_name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="d-flex align-items-center gap-1.5 mt-0.5">
                        <span class="badge bg-light text-secondary border rounded-pill extra-small">
                          <?= htmlspecialchars($app['category'] ?? 'General') ?>
                        </span>
                        <?php if ($app['tuition_coverage_type'] === 'percentage'): ?>
                          <span class="text-success extra-small fw-medium"><?= number_format((float)$app['tuition_coverage_value'], 0) ?>% Tuition</span>
                        <?php elseif ($app['tuition_coverage_type'] === 'fixed'): ?>
                          <span class="text-success extra-small fw-medium">₱<?= number_format((float)$app['tuition_coverage_value'], 2) ?></span>
                        <?php else: ?>
                          <span class="text-success extra-small fw-medium">Full Tuition</span>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                        <?= htmlspecialchars($app['ay_name'] ?? 'Current Term', ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge <?= esc($statusClass) ?> border border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                        <?= esc($statusLabel) ?>
                      </span>
                    </td>
                    <td class="text-muted small">
                      <?= date('M d, Y', strtotime($app['created_at'])) ?>
                    </td>
                    <td class="text-end pe-4">
                      <a href="scholarship_detail.php?id=<?= esc($app['id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3 py-1 extra-small fw-medium d-inline-flex align-items-center gap-1 shadow-xs" title="Review Application">
                        <span>Review</span>
                        <i class="bi bi-arrow-right"></i>
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
    const searchInput = document.getElementById('appSearch');
    const statusFilter = document.getElementById('statusFilter');
    const rows = document.querySelectorAll('.dashboard-table tbody tr');

    function applyFilters() {
        const query = (searchInput ? searchInput.value : '').toLowerCase();
        const selectedStatus = statusFilter ? statusFilter.value.toLowerCase() : '';

        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();

            const matchesQuery = !query || rowText.includes(query);
            const matchesStatus = !selectedStatus || rowStatus === selectedStatus;

            row.style.display = (matchesQuery && matchesStatus) ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keyup', applyFilters);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }
});
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
