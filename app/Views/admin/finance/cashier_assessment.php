<?php
$pageTitle = 'Student Account - Cashier';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';

$studentFirst = trim($assessment['first_name'] ?? '');
$studentLast = trim($assessment['last_name'] ?? '');
$studentFullName = trim($studentLast . ', ' . $studentFirst);
if ($studentFullName === ',' || $studentFullName === '') {
    $studentFullName = 'Unknown Student';
}

$firstInitial = mb_substr($studentFirst, 0, 1);
$lastInitial = mb_substr($studentLast, 0, 1);
$initials = strtoupper($firstInitial . $lastInitial);
if ($initials === '') $initials = 'ST';

$paymentStatusRaw = strtolower($assessment['payment_status'] ?? 'unpaid');
$statusBadgeClass = match($paymentStatusRaw) {
    'paid'    => 'bg-success bg-opacity-10 text-success border-success border-opacity-25',
    'partial' => 'bg-info bg-opacity-10 text-info border-info border-opacity-25',
    default   => 'bg-warning bg-opacity-10 text-warning border-warning border-opacity-25'
};
$statusIconClass = match($paymentStatusRaw) {
    'paid'    => 'bi-check-circle-fill',
    'partial' => 'bi-hourglass-split',
    default   => 'bi-exclamation-circle-fill'
};
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip (Design System Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="applicant-avatar rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.35rem;">
            <?= esc($initials) ?>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0"><?= htmlspecialchars($studentFullName, ENT_QUOTES, 'UTF-8') ?></h1>
              <span class="applicant-ref-badge">
                <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($assessment['reference_number'], ENT_QUOTES, 'UTF-8') ?>
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-mortarboard text-primary me-1"></i><?= htmlspecialchars(strtoupper($assessment['strand'] ?? $assessment['academic_level']), ENT_QUOTES, 'UTF-8') ?>
              </span>
              <span class="badge <?= esc($statusBadgeClass) ?> border rounded-pill px-2.5 py-0.5 small fw-semibold d-inline-flex align-items-center gap-1">
                <i class="bi <?= esc($statusIconClass) ?>"></i>
                <span><?= ucfirst($paymentStatusRaw) ?></span>
              </span>
            </div>
            <p class="text-muted small mb-0">Student Account Profile • Level: <span class="fw-medium text-dark"><?= htmlspecialchars($assessment['academic_level'], ENT_QUOTES, 'UTF-8') ?></span> (<?= htmlspecialchars($assessment['grade_level'] ?? '1st Year', ENT_QUOTES, 'UTF-8') ?>)</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="cashier_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>Back to Accounts</span>
          </a>
          <?php if ($balance > 0): ?>
            <button type="button" class="btn btn-primary fw-medium shadow-sm rounded-pill px-4 d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
              <i class="bi bi-cash-coin"></i>
              <span>Record Payment</span>
            </button>
          <?php else: ?>
            <button class="btn btn-success fw-medium shadow-sm rounded-pill px-4 d-inline-flex align-items-center gap-2" disabled>
              <i class="bi bi-check-circle-fill"></i>
              <span>Fully Paid</span>
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Flash Messages -->
    <?php if ($successMsg): ?>
      <div class="alert alert-success d-flex align-items-center shadow-sm rounded-12 mb-4 fade-in-up" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2.5 text-success"></i>
        <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-12 mb-4 fade-in-up" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2.5 text-danger"></i>
        <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      
      <!-- Left Column: Financial Assessment & Breakdown -->
      <div class="col-lg-4">
        
        <!-- Financial Assessment Summary -->
        <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.1s;">
          <div class="dossier-card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-calculator-fill"></i>
              </div>
              <h2 class="h6 fw-bold text-dark mb-0">Assessment Summary</h2>
            </div>
            <span class="badge <?= esc($statusBadgeClass) ?> border rounded-pill px-2.5 py-0.5 small fw-semibold">
              <?= ucfirst($paymentStatusRaw) ?>
            </span>
          </div>
          <div class="p-0">
            <ul class="list-group list-group-flush rounded-bottom-4">
              <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                <span class="text-muted fw-semibold">Gross Assessed Fees</span>
                <span class="fw-bold text-dark">₱<?= number_format($totalAmount, 2) ?></span>
              </li>
              <?php if ($discountAmount > 0): ?>
                <li class="list-group-item p-3 bg-success bg-opacity-10 border-top-0">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-success fw-bold"><i class="bi bi-award-fill me-1"></i> Scholarship Grant</span>
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
                <span class="text-muted fw-semibold">Total Paid to Date</span>
                <span class="fw-bold text-success">₱<?= number_format($totalPaid, 2) ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center p-3 border-top border-2 border-dark">
                <div>
                  <span class="text-dark fw-bold text-uppercase d-block" style="letter-spacing: 0.5px;">Remaining Balance</span>
                  <span class="extra-small text-muted"><?= $balance <= 0 ? 'Account settled in full' : 'Outstanding receivable' ?></span>
                </div>
                <span class="fw-bold fs-4 <?= esc($balance > 0 ? 'text-danger' : 'text-success') ?>">₱<?= number_format($balance, 2) ?></span>
              </li>
            </ul>
          </div>
        </div>
        
        <!-- Fee Itemization -->
        <div class="dossier-card fade-in-up" style="animation-delay: 0.15s;">
          <div class="dossier-card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <div class="dossier-header-icon bg-secondary bg-opacity-10 text-secondary">
                <i class="bi bi-list-nested"></i>
              </div>
              <h2 class="h6 fw-bold text-dark mb-0">Fee Breakdown</h2>
            </div>
            <?php if (!empty($assessmentItems)): ?>
              <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle small fw-semibold">
                <i class="bi bi-lock-fill me-1"></i>Snapshot Locked
              </span>
            <?php endif; ?>
          </div>
          <div class="p-0">
            <ul class="list-group list-group-flush rounded-bottom-4 small">
              <li class="list-group-item d-flex justify-content-between p-3">
                <div>
                  <span class="text-muted d-block fw-semibold">Tuition Fee</span>
                  <?php 
                    $calcTotalUnits = array_sum(array_column($enrolledSubjects ?? [], 'units'));
                    if (!empty($assessment['is_per_unit']) && $calcTotalUnits > 0): 
                      $inferredCost = (float)$assessment['tuition_fee'] / $calcTotalUnits; 
                  ?>
                    <small class="text-secondary"><?= esc($calcTotalUnits) ?> units @ ₱<?= number_format($inferredCost, 2) ?>/unit</small>
                  <?php endif; ?>
                </div>
                <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['tuition_fee'], 2) ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between p-3">
                <span class="text-muted fw-semibold">Miscellaneous Fee</span>
                <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['miscellaneous_fee'], 2) ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between p-3">
                <span class="text-muted fw-semibold">Registration Fee</span>
                <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['registration_fee'], 2) ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between p-3">
                <span class="text-muted fw-semibold">Laboratory Fee</span>
                <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['laboratory_fee'], 2) ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between p-3">
                <span class="text-muted fw-semibold">Other Institution Fees</span>
                <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['other_fees'], 2) ?></span>
              </li>
            </ul>
          </div>
        </div>

      </div>

      <!-- Right Column: Subjects & Payments History -->
      <div class="col-lg-8">
        
        <!-- Enrolled Curriculum Subjects -->
        <?php if (!empty($enrolledSubjects)): ?>
        <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.2s;">
          <div class="dossier-card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-journal-text"></i>
              </div>
              <h2 class="h6 fw-bold text-dark mb-0"><?= $assessment['academic_level'] === 'College' ? 'Curriculum Enrolled' : 'SHS Subjects Enrolled' ?></h2>
            </div>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-check2-circle text-primary me-1"></i><?= count($enrolledSubjects) ?> Subjects
            </span>
          </div>
          <div class="p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 dashboard-table">
                <thead>
                  <tr>
                    <th scope="col" class="ps-4">Subject Code</th>
                    <th scope="col">Subject Description</th>
                    <th scope="col" class="text-end pe-4">Units</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $totalUnits = 0;
                  foreach ($enrolledSubjects as $sub): 
                    $totalUnits += (int)$sub['units'];
                  ?>
                    <tr>
                      <td class="ps-4">
                        <span class="applicant-ref-badge">
                          <?= htmlspecialchars($sub['subject_code'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                      </td>
                      <td class="fw-semibold text-dark"><?= htmlspecialchars($sub['subject_name'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="text-end pe-4 fw-bold text-dark"><?= esc((int)$sub['units']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <td colspan="2" class="text-end fw-bold text-dark">Total Registered Units:</td>
                    <td class="text-end pe-4 fw-bold text-primary fs-6"><?= esc($totalUnits) ?></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Payment History (Receipts) -->
        <div class="dossier-card fade-in-up" style="animation-delay: 0.25s;">
          <div class="dossier-card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <div class="dossier-header-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-receipt"></i>
              </div>
              <h2 class="h6 fw-bold text-dark mb-0">Transaction History & Official Receipts</h2>
            </div>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-cash me-1 text-success"></i><?= count($payments) ?> Records
            </span>
          </div>
          
          <div class="p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 dashboard-table">
                <thead>
                  <tr>
                    <th scope="col" class="ps-4">Receipt / Status</th>
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
                        <div class="d-flex flex-column align-items-center justify-content-center py-4">
                          <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 64px; height: 64px;">
                            <i class="bi bi-inbox fs-2 text-muted"></i>
                          </div>
                          <h3 class="h6 fw-bold text-dark mb-1">No Payments Logged</h3>
                          <p class="text-muted small mb-0">Record a new payment to generate the first official receipt.</p>
                        </div>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($payments as $payment): 
                        $pStatus = strtolower($payment['status'] ?? 'verified');
                    ?>
                      <tr>
                        <td class="ps-4">
                          <?php if ($pStatus === 'pending'): ?>
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                              <i class="bi bi-hourglass-split me-1"></i> Pending
                            </span>
                          <?php elseif ($pStatus === 'rejected'): ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                              <i class="bi bi-x-circle-fill me-1"></i> Rejected
                            </span>
                          <?php else: ?>
                            <span class="applicant-ref-badge">
                              <i class="bi bi-receipt text-primary"></i><?= htmlspecialchars($payment['receipt_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="fw-medium text-dark"><?= date('M d, Y', strtotime($payment['created_at'])) ?></div>
                          <div class="extra-small text-muted"><?= date('g:i A', strtotime($payment['created_at'])) ?></div>
                        </td>
                        <td>
                          <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small fw-semibold">
                            <?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?>
                          </span>
                        </td>
                        <td>
                          <span class="fw-bold text-success fs-6">₱<?= number_format((float)$payment['amount'], 2) ?></span>
                        </td>
                        <td>
                          <?php if ($pStatus === 'pending'): ?>
                            <span class="badge bg-light text-warning border rounded-pill px-2 py-0.5 small fw-semibold">Needs Review</span>
                          <?php elseif ($pStatus === 'rejected'): ?>
                            <span class="badge bg-light text-danger border rounded-pill px-2 py-0.5 small fw-semibold">Rejected</span>
                          <?php else: ?>
                            <span class="small text-dark fw-medium">
                              <?= htmlspecialchars($payment['cashier_last'] ?? 'System', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                          <?php if ($pStatus === 'pending'): ?>
                            <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 fw-semibold verify-btn shadow-xs d-inline-flex align-items-center gap-1"
                                    data-id="<?= esc($payment['id']) ?>"
                                    data-name="<?= htmlspecialchars($studentFullName, ENT_QUOTES, 'UTF-8') ?>"
                                    data-amount="<?= number_format((float)$payment['amount'], 2) ?>"
                                    data-method="<?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-ref="<?= htmlspecialchars($payment['reference_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>"
                                    data-img="/sia/uploads/payments/<?= htmlspecialchars($payment['proof_image'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-bs-toggle="modal" data-bs-target="#verifyModal">
                              <i class="bi bi-shield-check"></i> Verify
                            </button>
                          <?php elseif ($pStatus === 'rejected'): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-medium d-inline-flex align-items-center gap-1"
                                    onclick="showRejectReason('<?= htmlspecialchars(addslashes($payment['remarks'] ?? 'No reason provided.')) ?>')">
                              <i class="bi bi-info-circle"></i> Reason
                            </button>
                          <?php else: ?>
                            <a href="cashier_receipt.php?id=<?= esc($payment['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium d-inline-flex align-items-center gap-1" target="_blank">
                              <i class="bi bi-printer"></i> Receipt
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

  <!-- Record Payment Modal -->
  <?php if ($balance > 0): ?>
  <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-labelledby="recordPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-header bg-white border-bottom py-3">
          <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="recordPaymentModalLabel">
            <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle me-3" style="width: 36px; height: 36px;">
              <i class="bi bi-cash-coin fs-5"></i>
            </div>
            Record Student Payment
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="cashier_process.php" method="POST">
          <div class="modal-body p-4">
            <input type="hidden" name="action" value="record_payment">
            <input type="hidden" name="assessment_id" value="<?= esc($assessmentId) ?>">
            <input type="hidden" name="user_id" value="<?= esc($assessment['user_id']) ?>">
            <input type="hidden" name="application_id" value="<?= esc($assessment['application_id']) ?>">
            <?= getCsrfInput() ?>
            
            <div class="p-3 bg-light rounded-3 border mb-3 d-flex justify-content-between align-items-center">
              <div>
                <span class="text-muted small fw-semibold d-block">Outstanding Balance</span>
                <span class="fw-bold fs-4 text-danger">₱<?= number_format($balance, 2) ?></span>
              </div>
              <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                Dues Pending
              </span>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Payment Amount (₱) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-white fw-bold">₱</span>
                <input type="number" step="0.01" min="<?= esc(min(3000, $balance)) ?>" max="<?= esc($balance) ?>" name="amount" class="form-control form-control-lg bg-light text-success fw-bold" required value="<?= esc($balance) ?>">
              </div>
              <div class="form-text extra-small text-muted mt-1">
                Minimum payment: ₱<?= number_format(min(3000, $balance), 2) ?> • Maximum: ₱<?= number_format($balance, 2) ?>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label small fw-bold text-dark">Payment Method <span class="text-danger">*</span></label>
              <select name="payment_method" class="form-select bg-light" required>
                <option value="Cash" selected>Cash (Over-the-Counter)</option>
                <option value="GCash">GCash / E-Wallet</option>
                <option value="Bank Transfer">Bank Deposit / Transfer</option>
              </select>
            </div>

            <div class="mb-2">
              <label class="form-label small fw-bold text-dark">Reference / Transaction Number (Optional)</label>
              <input type="text" name="reference_number" class="form-control bg-light" placeholder="e.g., GCash Ref, Bank Deposit Slip No.">
            </div>
            
          </div>
          <div class="modal-footer border-top-0 pt-0 bg-light d-flex justify-content-between">
            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold">
              <i class="bi bi-check-circle me-1"></i> Confirm & Issue Receipt
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Verify Payment Modal -->
  <div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-header bg-white border-bottom py-3">
          <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="verifyModalLabel">
            <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-25 text-warning-emphasis rounded-circle me-3" style="width: 36px; height: 36px;">
              <i class="bi bi-shield-check fs-5" style="color: #d97706;"></i>
            </div>
            Verify Online Proof of Payment
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="cashier_process.php" method="POST">
          <div class="modal-body p-4 bg-light">
            <input type="hidden" name="action" value="verify_online_payment">
            <input type="hidden" name="payment_id" id="verifyPaymentId" value="">
            <input type="hidden" name="redirect_to" value="/sia/admin/finance/cashier_assessment.php?id=<?= esc($assessmentId) ?>">
            <?= getCsrfInput() ?>

            <div class="row g-4">
              <div class="col-md-5">
                <div class="d-flex flex-column gap-3 h-100">
                  <div class="p-3 bg-white border rounded-3 shadow-xs">
                    <p class="text-muted extra-small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px;"><i class="bi bi-person me-1"></i> Student Name</p>
                    <p class="fw-bold text-dark mb-0 fs-6" id="verifyStudentName"></p>
                  </div>
                  <div class="p-3 bg-white border rounded-3 shadow-xs" style="border-color: #a3cfbb !important;">
                    <p class="text-success extra-small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px;"><i class="bi bi-cash-stack me-1"></i> Amount Declared</p>
                    <p class="fw-bolder text-success mb-0 fs-3" style="letter-spacing: -1px;">₱<span id="verifyAmount"></span></p>
                  </div>
                  <div class="p-3 bg-white border rounded-3 shadow-xs">
                    <p class="text-muted extra-small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px;"><i class="bi bi-credit-card me-1"></i> Method & Reference</p>
                    <div class="d-flex align-items-center gap-2 mt-2">
                      <span class="badge bg-light text-dark border px-2 py-1" id="verifyMethod"></span>
                      <span class="fw-medium text-secondary font-monospace small" id="verifyRef"></span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-7">
                <div class="bg-light p-2 rounded-4 h-100 d-flex flex-column position-relative border" style="min-height: 380px;">
                  
                  <div class="d-flex align-items-center justify-content-between px-2 py-1 mb-2 bg-white rounded-3 border shadow-xs">
                    <span class="text-dark small fw-bold">
                      <i class="bi bi-image text-primary me-1"></i> Receipt Screenshot
                    </span>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Zoom controls">
                      <button type="button" class="btn btn-outline-secondary py-0 px-2" id="zoomOutBtn" title="Zoom Out (Scroll Down)">
                        <i class="bi bi-zoom-out"></i>
                      </button>
                      <button type="button" class="btn btn-outline-secondary py-0 px-2 fw-semibold font-monospace" id="zoomResetBtn" title="Reset Zoom">
                        <span id="zoomLevelText" style="font-size: 0.72rem;">100%</span>
                      </button>
                      <button type="button" class="btn btn-outline-secondary py-0 px-2" id="zoomInBtn" title="Zoom In (Scroll Up)">
                        <i class="bi bi-zoom-in"></i>
                      </button>
                      <button type="button" class="btn btn-primary py-0 px-2" id="openLightboxBtn" title="Open Fullscreen Lightbox">
                        <i class="bi bi-arrows-fullscreen"></i>
                      </button>
                    </div>
                  </div>

                  <div id="imageViewport" class="flex-grow-1 position-relative overflow-hidden d-flex align-items-center justify-content-center bg-dark bg-opacity-10 rounded-3" style="min-height: 320px; max-height: 380px; cursor: zoom-in; user-select: none;">
                    <img id="verifyImage" src="" alt="Proof of Payment" class="img-fluid rounded shadow-sm" style="max-height: 310px; max-width: 100%; object-fit: contain; transform-origin: center center; transition: transform 0.12s ease-out;">
                    
                    <div class="position-absolute bottom-0 start-0 m-2 px-2 py-1 bg-dark bg-opacity-75 text-white rounded small" style="font-size: 0.68rem; pointer-events: none;">
                      <i class="bi bi-mouse me-1"></i> Scroll to zoom • Drag to pan • Click to toggle
                    </div>
                  </div>

                </div>
              </div>
            </div>
            
            <div class="mt-4 pt-3 border-top border-light">
              <label for="verifyRemarks" class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing: 0.5px;"><i class="bi bi-chat-left-text me-1"></i>Remarks / Reason for Rejection</label>
              <textarea name="remarks" id="verifyRemarks" class="form-control border-secondary border-opacity-25 rounded-3 shadow-sm" rows="2" placeholder="e.g., Screenshot is blurry, Amount does not match deposit, etc."></textarea>
              <div id="remarksFeedback" class="text-danger small fw-semibold mt-1 d-none">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Please provide a reason for rejection in the Remarks field before proceeding.
              </div>
            </div>

            <!-- Inline Rejection Confirmation Box -->
            <div id="rejectConfirmBox" class="alert alert-danger border-0 shadow-sm rounded-3 mt-3 mb-0 d-none">
              <div class="d-flex align-items-start gap-3">
                <i class="bi bi-exclamation-octagon-fill fs-4 text-danger flex-shrink-0 mt-1"></i>
                <div class="flex-grow-1">
                  <h6 class="fw-bold mb-1 text-danger">Confirm Payment Rejection</h6>
                  <p class="small text-dark mb-2">Are you sure you want to <strong>REJECT</strong> this payment? Reason: <span id="rejectReasonDisplay" class="fw-bold text-danger"></span></p>
                  <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold" onclick="submitRejection()"><i class="bi bi-x-circle me-1"></i> Yes, Reject It</button>
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="cancelRejectionPrompt()">Cancel</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top-0 pt-0 bg-light d-flex justify-content-between">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            <div>
              <button type="button" class="btn btn-outline-danger rounded-pill px-4 shadow-sm fw-semibold me-2" onclick="confirmReject(this)"><i class="bi bi-x-circle me-1"></i> Reject Payment</button>
              <button type="submit" id="approvePaymentBtn" name="decision" value="approve" class="btn btn-success rounded-pill px-4 shadow-sm fw-semibold"><i class="bi bi-check-circle me-1"></i> Approve Payment</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Custom Reason Modal -->
  <div class="modal fade" id="customReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content border-0 shadow rounded-4">
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

  <!-- Fullscreen Image Lightbox Modal -->
  <div class="modal fade" id="imageLightboxModal" tabindex="-1" aria-hidden="true" style="z-index: 1080;">
    <div class="modal-dialog modal-fullscreen modal-dialog-centered p-0 m-0">
      <div class="modal-content bg-dark bg-opacity-95 border-0 rounded-0">
        <div class="modal-header border-0 pb-0 position-absolute top-0 end-0 z-3 p-3">
          <div class="d-flex gap-2">
            <a id="lightboxNewTabBtn" href="#" target="_blank" class="btn btn-outline-light btn-sm rounded-pill px-3 shadow">
              <i class="bi bi-box-arrow-up-right me-1"></i> Open in New Tab
            </a>
            <button type="button" class="btn-close btn-close-white shadow" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
        </div>
        <div class="modal-body p-0 d-flex align-items-center justify-content-center position-relative overflow-hidden" id="lightboxViewport" style="cursor: zoom-in;">
          <img id="lightboxImage" src="" alt="Full Receipt" class="img-fluid shadow-lg" style="max-height: 90vh; max-width: 92vw; object-fit: contain; transition: transform 0.15s ease-out;">
        </div>
      </div>
    </div>
  </div>

</main>

<script>
(function() {
    let zoomScale = 1.0;
    let translateX = 0;
    let translateY = 0;
    let isDragging = false;
    let startX = 0;
    let startY = 0;

    function applyZoom() {
        const img = document.getElementById('verifyImage');
        const text = document.getElementById('zoomLevelText');
        const viewport = document.getElementById('imageViewport');
        if (!img) return;

        img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${zoomScale})`;
        if (text) text.textContent = Math.round(zoomScale * 100) + '%';
        if (viewport) {
            viewport.style.cursor = zoomScale > 1.05 ? (isDragging ? 'grabbing' : 'grab') : 'zoom-in';
        }
    }

    function resetZoom() {
        zoomScale = 1.0;
        translateX = 0;
        translateY = 0;
        isDragging = false;
        applyZoom();
    }

    function changeZoom(delta) {
        zoomScale = Math.min(4.0, Math.max(0.6, zoomScale + delta));
        if (zoomScale <= 1.0) {
            translateX = 0;
            translateY = 0;
        }
        applyZoom();
    }

    function initVerifyControls() {
        const zIn = document.getElementById('zoomInBtn');
        const zOut = document.getElementById('zoomOutBtn');
        const zReset = document.getElementById('zoomResetBtn');
        const openLb = document.getElementById('openLightboxBtn');
        const viewport = document.getElementById('imageViewport');
        const img = document.getElementById('verifyImage');

        if (zIn) zIn.onclick = () => changeZoom(0.3);
        if (zOut) zOut.onclick = () => changeZoom(-0.3);
        if (zReset) zReset.onclick = () => resetZoom();

        if (openLb) {
            openLb.onclick = () => {
                const src = img ? img.src : '';
                if (!src) return;
                const lbImg = document.getElementById('lightboxImage');
                const lbTab = document.getElementById('lightboxNewTabBtn');
                if (lbImg) lbImg.src = src;
                if (lbTab) lbTab.href = src;
                const lbModal = new bootstrap.Modal(document.getElementById('imageLightboxModal'));
                lbModal.show();
            };
        }

        if (viewport) {
            viewport.onwheel = (e) => {
                e.preventDefault();
                changeZoom(e.deltaY < 0 ? 0.2 : -0.2);
            };

            viewport.onclick = (e) => {
                if (isDragging) return;
                if (zoomScale <= 1.05) {
                    zoomScale = 2.2;
                } else {
                    resetZoom();
                }
                applyZoom();
            };

            viewport.onmousedown = (e) => {
                if (zoomScale <= 1.05) return;
                isDragging = true;
                startX = e.clientX - translateX;
                startY = e.clientY - translateY;
                viewport.style.cursor = 'grabbing';
                e.preventDefault();
            };

            window.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                translateX = e.clientX - startX;
                translateY = e.clientY - startY;
                applyZoom();
            });

            window.addEventListener('mouseup', () => {
                if (isDragging) {
                    isDragging = false;
                    applyZoom();
                }
            });
        }
    }

    // Direct event delegation for verify buttons
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.verify-btn');
        if (btn) {
            const id = btn.getAttribute('data-id') || '';
            const name = btn.getAttribute('data-name') || '';
            const amount = btn.getAttribute('data-amount') || '';
            const method = btn.getAttribute('data-method') || '';
            const ref = btn.getAttribute('data-ref') || '';
            const img = btn.getAttribute('data-img') || '';

            const pidInput = document.getElementById('verifyPaymentId');
            const nameEl = document.getElementById('verifyStudentName');
            const amtEl = document.getElementById('verifyAmount');
            const methEl = document.getElementById('verifyMethod');
            const refEl = document.getElementById('verifyRef');
            const imgEl = document.getElementById('verifyImage');
            const remarksEl = document.getElementById('verifyRemarks');
            const feedbackEl = document.getElementById('remarksFeedback');
            const confirmBox = document.getElementById('rejectConfirmBox');

            if (pidInput) pidInput.value = id;
            if (nameEl) nameEl.textContent = name;
            if (amtEl) amtEl.textContent = amount;
            if (methEl) methEl.textContent = method;
            if (refEl) refEl.textContent = ref;
            if (remarksEl) {
                remarksEl.value = '';
                remarksEl.classList.remove('is-invalid');
            }
            if (feedbackEl) feedbackEl.classList.add('d-none');
            if (confirmBox) confirmBox.classList.add('d-none');
            
            resetZoom();

            if (imgEl) {
                imgEl.src = img;
                imgEl.onerror = function() {
                    if (img && img.includes('/sia/uploads/payments/')) {
                        this.src = img.replace('/sia/uploads/payments/', '/sia/app/uploads/payments/');
                    }
                };
            }
        }
    });

    document.addEventListener('input', function(e) {
        if (e.target && e.target.id === 'verifyRemarks') {
            if (e.target.value.trim() !== '') {
                e.target.classList.remove('is-invalid');
                const feedbackEl = document.getElementById('remarksFeedback');
                if (feedbackEl) feedbackEl.classList.add('d-none');
            }
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVerifyControls);
    } else {
        initVerifyControls();
    }
    document.addEventListener('spa:navigated', initVerifyControls);
})();

window.showRejectReason = function(reason) {
    document.getElementById('customReasonText').textContent = reason || 'No reason provided.';
    const modalEl = document.getElementById('customReasonModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) modal = new bootstrap.Modal(modalEl);
    modal.show();
};

window.confirmReject = function(btn) {
    const remarksEl = document.getElementById('verifyRemarks');
    const feedbackEl = document.getElementById('remarksFeedback');
    const confirmBox = document.getElementById('rejectConfirmBox');
    const reasonDisplay = document.getElementById('rejectReasonDisplay');
    const remarks = remarksEl ? remarksEl.value.trim() : '';

    if (remarks === '') {
        if (remarksEl) {
            remarksEl.classList.add('is-invalid');
            remarksEl.focus();
        }
        if (feedbackEl) feedbackEl.classList.remove('d-none');
        if (confirmBox) confirmBox.classList.add('d-none');
        return;
    }
    
    if (remarksEl) remarksEl.classList.remove('is-invalid');
    if (feedbackEl) feedbackEl.classList.add('d-none');
    if (reasonDisplay) reasonDisplay.textContent = remarks;
    if (confirmBox) {
        confirmBox.classList.remove('d-none');
        confirmBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
};

window.cancelRejectionPrompt = function() {
    const confirmBox = document.getElementById('rejectConfirmBox');
    if (confirmBox) confirmBox.classList.add('d-none');
};

window.submitRejection = function() {
    const form = document.querySelector('#verifyModal form');
    if (!form) return;
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'decision';
    input.value = 'reject';
    form.appendChild(input);
    form.submit();
};
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
