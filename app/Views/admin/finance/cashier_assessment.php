<?php
require_once __DIR__ . '/../../components/header.php';

require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="mb-4 d-flex align-items-center justify-content-between">
      <div>
        <a href="cashier_dashboard.php" class="btn btn-sm btn-light border shadow-sm rounded-pill px-3 fw-medium mb-2 text-dark"><i class="bi bi-arrow-left me-1"></i> Back to Accounts</a>
        <h1 class="h3 fw-bold text-dark mt-2 mb-1">
          Student Account Profile
        </h1>
        <p class="text-muted mb-0">Applicant: <span class="fw-medium text-dark"><?= htmlspecialchars($assessment['last_name'] . ', ' . $assessment['first_name'], ENT_QUOTES, 'UTF-8') ?></span> | Ref: <span class="fw-medium text-dark"><?= htmlspecialchars($assessment['reference_number'], ENT_QUOTES, 'UTF-8') ?></span></p>
      </div>
      <div>
        <?php if ($balance > 0): ?>
        <button type="button" class="btn btn-primary fw-medium shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
          <i class="bi bi-cash-coin me-1"></i> Record Payment
        </button>
        <?php else: ?>
        <button class="btn btn-success fw-medium shadow-sm rounded-pill px-4" disabled>
          <i class="bi bi-check-circle-fill me-1"></i> Fully Paid
        </button>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-4">
        <!-- Financial Summary -->
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4 fade-in-up" style="animation-delay: 0.1s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light fade-in-up" style="animation-delay: 0.2s;">
            <i class="bi bi-calculator-fill text-primary"></i>
            <h2>Assessment Summary</h2>
          </div>
          <div class="island-body p-0 fade-in-up" style="animation-delay: 0.3s;">
            <ul class="list-group list-group-flush rounded-bottom-4">
                <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                    <span class="text-muted fw-semibold">Original Total Fees</span>
                    <span class="fw-bold">₱<?= number_format($totalAmount, 2) ?></span>
                </li>
                <?php if ($discountAmount > 0): ?>
                <li class="list-group-item p-3 bg-success-light">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-success fw-bold"><i class="bi bi-award-fill"></i> Scholarship Discount</span>
                        <span class="text-success fw-bold">-₱<?= number_format($discountAmount, 2) ?></span>
                    </div>
                    <div class="small text-muted"><?= htmlspecialchars($assessment['scholarship_name'], ENT_QUOTES, 'UTF-8') ?></div>
                </li>
                <?php endif; ?>
                <li class="list-group-item d-flex justify-content-between align-items-center p-3 bg-light">
                    <span class="text-dark fw-bold">Final Amount Due (Net)</span>
                    <span class="fw-bold fs-5 text-primary">₱<?= number_format($netAmount, 2) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                    <span class="text-muted fw-semibold">Total Paid</span>
                    <span class="fw-bold text-success">₱<?= number_format($totalPaid, 2) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center p-3 border-top border-2 border-dark">
                    <span class="text-dark fw-bold text-uppercase">Remaining Balance</span>
                    <span class="fw-bold fs-4 <?= esc($balance > 0 ? 'text-danger' : 'text-success') ?>">₱<?= number_format($balance, 2) ?></span>
                </li>
            </ul>
          </div>
        </div>
        
        <!-- Fee Breakdown -->
        <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4 fade-in-up" style="animation-delay: 0.4s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light fade-in-up" style="animation-delay: 0.5s;">
            <i class="bi bi-list-nested text-secondary"></i>
            <h2>Fee Breakdown</h2>
          </div>
          <div class="island-body p-0 fade-in-up" style="animation-delay: 0.6s;">
             <ul class="list-group list-group-flush rounded-bottom-4 small">
                <li class="list-group-item d-flex justify-content-between p-3">
                    <div>
                      <span class="text-muted d-block">Tuition Fee</span>
                      <?php if ($assessment['academic_level'] === 'College' && isset($totalUnits) && $totalUnits > 0): ?>
                        <?php $inferredCost = (float)$assessment['tuition_fee'] / $totalUnits; ?>
                        <small class="text-secondary"><?= esc($totalUnits) ?> units @ ₱<?= number_format($inferredCost, 2) ?>/unit</small>
                      <?php endif; ?>
                    </div>
                    <span class="fw-medium">₱<?= number_format((float)$assessment['tuition_fee'], 2) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between p-3">
                    <span class="text-muted">Miscellaneous Fee</span>
                    <span class="fw-medium">₱<?= number_format((float)$assessment['miscellaneous_fee'], 2) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between p-3">
                    <span class="text-muted">Registration Fee</span>
                    <span class="fw-medium">₱<?= number_format((float)$assessment['registration_fee'], 2) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between p-3">
                    <span class="text-muted">Laboratory Fee</span>
                    <span class="fw-medium">₱<?= number_format((float)$assessment['laboratory_fee'], 2) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between p-3">
                    <span class="text-muted">Other Fees</span>
                    <span class="fw-medium">₱<?= number_format((float)$assessment['other_fees'], 2) ?></span>
                </li>
             </ul>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <?php if ($assessment['academic_level'] === 'College' && !empty($enrolledSubjects)): ?>
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4 fade-in-up" style="animation-delay: 0.7s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light fade-in-up" style="animation-delay: 0.8s;">
            <i class="bi bi-journal-text text-primary"></i>
            <h2 class="mb-0 text-dark">Curriculum Enrolled</h2>
          </div>
          <div class="island-body p-0 fade-in-up" style="animation-delay: 0.9s;">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 custom-table">
                <thead class="table-light text-muted small text-uppercase">
                  <tr>
                    <th class="ps-4">Subject Code</th>
                    <th>Subject Name</th>
                    <th class="text-end pe-4">Units</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $totalUnits = 0;
                  foreach ($enrolledSubjects as $sub): 
                    $totalUnits += (int)$sub['units'];
                  ?>
                    <tr>
                      <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($sub['subject_code'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars($sub['subject_name'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="text-end pe-4"><?= esc((int)$sub['units']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <td colspan="2" class="text-end fw-bold text-dark">Total Units:</td>
                    <td class="text-end pe-4 fw-bold text-dark fs-5"><?= esc($totalUnits) ?></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Payment History (Receipts) -->
        <div class="island position-relative overflow-hidden border-0 shadow-sm h-100 rounded-4 fade-in-up" style="animation-delay: 1s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light fade-in-up" style="animation-delay: 1.1s;">
            <i class="bi bi-receipt-cutoff text-primary"></i>
            <h2 class="mb-0 text-dark">Payment History & Receipts</h2>
          </div>
          
          <div class="island-body p-0 fade-in-up" style="animation-delay: 1.2s;">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 custom-table">
                <thead class="table-light">
                  <tr>
                    <th scope="col" class="ps-4">Receipt No.</th>
                    <th scope="col">Date</th>
                    <th scope="col">Method</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Processed By</th>
                    <th scope="col" class="text-end pe-4">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($payments)): ?>
                    <tr>
                      <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                        No payments have been recorded yet.
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                      <tr>
                        <td class="ps-4 fw-bold text-primary">
                          <?php if ($payment['status'] === 'pending'): ?>
                            <span class="badge bg-warning text-dark border rounded-pill"><i class="bi bi-hourglass-split"></i> Pending</span>
                          <?php elseif ($payment['status'] === 'rejected'): ?>
                            <span class="badge bg-danger border rounded-pill"><i class="bi bi-x-circle"></i> Rejected</span>
                          <?php else: ?>
                            <?= htmlspecialchars($payment['receipt_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?= date('M d, Y g:i A', strtotime($payment['created_at'])) ?>
                        </td>
                        <td>
                          <span class="badge bg-secondary rounded-pill px-3"><?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td class="fw-bold text-success">
                          ₱<?= number_format((float)$payment['amount'], 2) ?>
                        </td>
                        <td class="small text-muted">
                          <?php if ($payment['status'] === 'pending'): ?>
                            <span class="text-warning fw-bold">Needs Review</span>
                          <?php elseif ($payment['status'] === 'rejected'): ?>
                            <span class="text-danger fw-bold">Rejected</span>
                          <?php else: ?>
                            <?= htmlspecialchars($payment['cashier_last'] ?? 'System', ENT_QUOTES, 'UTF-8') ?>
                          <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                          <?php if ($payment['status'] === 'pending'): ?>
                            <a href="cashier_payments.php" class="btn btn-sm btn-warning rounded-pill px-3 fw-medium">
                              Verify
                            </a>
                          <?php elseif ($payment['status'] === 'rejected'): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-medium"
                                    onclick="showRejectReason('<?= htmlspecialchars(addslashes($payment['remarks'] ?? 'No reason provided.')) ?>')">
                              <i class="bi bi-info-circle me-1"></i> Reason
                            </button>
                          <?php else: ?>
                            <a href="cashier_receipt.php?id=<?= esc($payment['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium" target="_blank">
                              <i class="bi bi-printer"></i> View
                            </a>
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
    </div>
  </div>
</main>

<!-- Record Payment Modal -->
<?php if ($balance > 0): ?>
<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Record New Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="cashier_process.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="record_payment">
          <input type="hidden" name="assessment_id" value="<?= esc($assessmentId) ?>">
          <input type="hidden" name="user_id" value="<?= esc($assessment['user_id']) ?>">
          <input type="hidden" name="application_id" value="<?= esc($assessment['application_id']) ?>">
          <?= getCsrfInput() ?>
          
          <div class="alert alert-info py-2 small mb-3">
            Remaining Balance: <strong>₱<?= number_format($balance, 2) ?></strong>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Payment Amount (₱)</label>
            <input type="number" step="0.01" min="<?= esc(min(3000, $balance)) ?>" max="<?= esc($balance) ?>" name="amount" class="form-control form-control-lg bg-light text-success fw-bold" required value="<?= esc($balance) ?>">
            <div class="form-text small">Cannot exceed remaining balance.</div>
          </div>
          
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Payment Method</label>
            <select name="payment_method" class="form-select bg-light" required>
              <option value="Cash" selected>Cash</option>
              <option value="GCash">GCash</option>
              <option value="Bank Transfer">Bank Transfer</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">External Reference Number (Optional)</label>
            <input type="text" name="reference_number" class="form-control bg-light" placeholder="e.g. GCash Ref No., Check No.">
          </div>
          
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Confirm Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Custom Reason Modal -->
<div class="modal fade" id="customReasonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger bg-opacity-10 border-bottom-0 pb-0">
        <h6 class="modal-title text-danger fw-bold"><i class="bi bi-info-circle me-2"></i>Rejection Reason</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center pt-3 pb-4">
        <p class="text-dark fw-medium mb-0" id="customReasonText"></p>
      </div>
    </div>
  </div>
</div>

<script>
function showRejectReason(reason) {
    document.getElementById('customReasonText').textContent = reason || 'No reason provided.';
    const modal = new bootstrap.Modal(document.getElementById('customReasonModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>



