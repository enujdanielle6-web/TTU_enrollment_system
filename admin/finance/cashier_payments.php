<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('payments.record');

$pageTitle = 'Payment History - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';

// Fetch all payments
$payments = [];
try {
    $stmt = $pdo->query('
        SELECT pr.*, 
               u.first_name as student_first, u.last_name as student_last,
               c.first_name as cashier_first, c.last_name as cashier_last,
               a.reference_number as app_ref
        FROM payment_records pr
        INNER JOIN users u ON pr.user_id = u.id
        LEFT JOIN users c ON pr.cashier_id = c.id
        INNER JOIN student_assessments sa ON pr.assessment_id = sa.id
        INNER JOIN applications a ON sa.application_id = a.id
        ORDER BY pr.created_at DESC
    ');
    $payments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Cashier payments fetch failed: ' . $e->getMessage());
}

?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Payment History</h1>
        <p class="text-muted mb-0">Global ledger of all recorded student payments.</p>
      </div>
      <div>
        <div class="input-group shadow-sm">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="tableSearch" class="form-control border-start-0" placeholder="Search payments...">
        </div>
      </div>
    </div>

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light">
        <i class="bi bi-receipt text-primary"></i>
        <h2 class="mb-0 text-dark">All Transactions</h2>
      </div>
      
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Receipt No.</th>
                <th scope="col">Date</th>
                <th scope="col">Student Name</th>
                <th scope="col">Method</th>
                <th scope="col">Amount</th>
                <th scope="col">Processed By</th>
                <th scope="col" class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($payments)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No payments have been recorded yet.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-primary">
                      <?= htmlspecialchars($payment['receipt_number'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                      <?= date('M d, Y g:i A', strtotime($payment['created_at'])) ?>
                    </td>
                    <td class="fw-bold text-dark">
                      <?= htmlspecialchars($payment['student_last'] . ', ' . $payment['student_first'], ENT_QUOTES, 'UTF-8') ?>
                      <div class="small text-muted fw-normal">Ref: <?= htmlspecialchars($payment['app_ref'], ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td>
                      <span class="badge bg-secondary rounded-pill px-3"><?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td class="fw-bold text-success">
                      ₱<?= number_format((float)$payment['amount'], 2) ?>
                    </td>
                    <td class="small text-muted">
                      <?= htmlspecialchars($payment['cashier_last'] ?? 'System', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="text-end pe-4">
                      <a href="cashier_receipt.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium" target="_blank">
                        <i class="bi bi-printer"></i> View
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              <tr id="noResultsRow" style="display: none;">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-search fs-1 d-block mb-3 text-secondary"></i>
                  No payments match your search.
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

