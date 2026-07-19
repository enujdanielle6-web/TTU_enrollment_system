<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requirePermission(['assessments.generate', 'payments.record']);

$pageTitle = 'Cashier Dashboard - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';

// Fetch assessments with balances
$assessments = [];
try {
    $stmt = $pdo->query('
        SELECT a.id, a.reference_number, u.first_name, u.last_name, u.email,
               sa.id as assessment_id, sa.net_amount, sa.total_paid, sa.payment_status
        FROM applications a
        INNER JOIN users u ON a.user_id = u.id
        INNER JOIN student_assessments sa ON a.id = sa.application_id
        ORDER BY 
            CASE sa.payment_status 
                WHEN "unpaid" THEN 1 
                WHEN "partial" THEN 2 
                ELSE 3 
            END ASC,
            sa.created_at DESC
    ');
    $assessments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Cashier dashboard fetch failed: ' . $e->getMessage());
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
$stats = [
    'outstanding_balances' => 0.0,
    'payments_today' => 0.0,
    'total_revenue' => 0.0
];

try {
    $stmtOut = $pdo->query('SELECT SUM(net_amount - total_paid) as outstanding FROM student_assessments WHERE payment_status IN ("unpaid", "partial")');
    $stats['outstanding_balances'] = (float)$stmtOut->fetchColumn();

    $stmtToday = $pdo->query('SELECT SUM(amount) FROM payment_records WHERE DATE(payment_date) = CURDATE()');
    $stats['payments_today'] = (float)$stmtToday->fetchColumn();

    $stmtRev = $pdo->query('SELECT SUM(amount) FROM payment_records');
    $stats['total_revenue'] = (float)$stmtRev->fetchColumn();
} catch (PDOException $e) {
    error_log("Cashier stats error: " . $e->getMessage());
}

?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Cashier Dashboard</h1>
        <p class="text-muted mb-0">Manage student accounts, record payments, and issue receipts.</p>
      </div>
      <div>
        <div class="input-group shadow-sm">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="tableSearch" class="form-control border-start-0" placeholder="Search accounts...">
        </div>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- Finance Statistics -->
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-danger" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-exclamation-circle fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1">₱<?= number_format($stats['outstanding_balances'], 2) ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Outstanding Balances</p>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-success" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-cash fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1">₱<?= number_format($stats['payments_today'], 2) ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Payments Today</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-graph-up fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1">₱<?= number_format($stats['total_revenue'], 2) ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Total Revenue</p>
        </div>
      </div>
    </div>

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light">
        <i class="bi bi-wallet2 text-primary"></i>
        <h2 class="mb-0 text-dark">Student Accounts</h2>
      </div>
      
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Reference No.</th>
                <th scope="col">Applicant Name</th>
                <th scope="col">Net Amount</th>
                <th scope="col">Total Paid</th>
                <th scope="col">Balance</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($assessments)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No student assessments found. Generate an assessment from the Application Review page first.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($assessments as $acc): 
                    $balance = (float)$acc['net_amount'] - (float)$acc['total_paid'];
                    if ($balance < 0) $balance = 0;
                ?>
                  <tr>
                    <td class="ps-4 fw-medium text-dark">
                      <?= htmlspecialchars($acc['reference_number'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="fw-bold text-dark">
                      <?= htmlspecialchars($acc['last_name'] . ', ' . $acc['first_name'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                      ₱<?= number_format((float)$acc['net_amount'], 2) ?>
                    </td>
                    <td class="text-success">
                      ₱<?= number_format((float)$acc['total_paid'], 2) ?>
                    </td>
                    <td class="fw-bold text-danger">
                      ₱<?= number_format($balance, 2) ?>
                    </td>
                    <td>
                      <?php 
                        $badgeClass = match($acc['payment_status']) {
                            'paid' => 'bg-success',
                            'partial' => 'bg-info text-dark',
                            default => 'bg-warning text-dark'
                        };
                      ?>
                      <span class="badge <?= $badgeClass ?> rounded-pill px-3"><?= ucfirst($acc['payment_status']) ?></span>
                    </td>
                    <td class="text-end pe-4">
                      <a href="cashier_assessment.php?id=<?= $acc['assessment_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium">
                        Manage <i class="bi bi-arrow-right ms-1"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              <tr id="noResultsRow" style="display: none;">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-search fs-1 d-block mb-3 text-secondary"></i>
                  No student accounts match your search.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('tableSearch');
    if(searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('.custom-table tbody tr');
            let visibleCount = 0;
            let hasDataRows = false;
            
            rows.forEach(row => {
                if(row.id === 'noResultsRow' || row.querySelector('td[colspan]')) return;
                hasDataRows = true;
                
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            const noResultsRow = document.getElementById('noResultsRow');
            if (noResultsRow) {
                noResultsRow.style.display = (visibleCount === 0 && hasDataRows) ? '' : 'none';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

