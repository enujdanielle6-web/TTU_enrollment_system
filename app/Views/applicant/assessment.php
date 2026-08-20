<?php
require_once __DIR__ . '/../components/header.php';
?>

<?php require_once __DIR__ . '/../components/applicant_navbar.php'; ?>

<main id="spa-main" class="py-5 bg-light min-vh-100">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-10">
        
        <div class="island island-hero mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 fade-in-up" style="animation-delay: 0.1s;">
          <div>
            <h1 class="h3 fw-bold text-dark mb-1">Financial Assessment & Payments</h1>
            <p class="text-muted mb-0">Review your fee breakdown and track your payment history.</p>
          </div>
        </div>

        <?php if (!empty($_SESSION['error_msg'])): ?>
          <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-4 border-0 p-3 mb-4 d-flex align-items-center gap-3" role="alert">
            <div class="bg-danger text-white rounded-circle p-2 flex-shrink-0 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
              <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            </div>
            <div class="flex-grow-1">
              <h6 class="fw-bold mb-0 text-danger">Payment Submission Error</h6>
              <div class="small"><?= htmlspecialchars($_SESSION['error_msg'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success_msg'])): ?>
          <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-4 border-0 p-3 mb-4 d-flex align-items-center gap-3" role="alert">
            <div class="bg-success text-white rounded-circle p-2 flex-shrink-0 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
              <i class="bi bi-check-circle-fill fs-5"></i>
            </div>
            <div class="flex-grow-1">
              <h6 class="fw-bold mb-0 text-success">Success</h6>
              <div class="small"><?= htmlspecialchars($_SESSION['success_msg'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>

        <?php if (!$assessment): ?>
          <?php
             $appStatus = $userAppStatus ?? null;
             if (!$appStatus && isset($pdo) && is_object($pdo)) {
                 $appStmt = $pdo->prepare('SELECT status FROM applications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1');
                 $appStmt->execute(['user_id' => $userId]);
                 $appStatus = $appStmt->fetchColumn();
             }
          ?>
          <div class="island text-center py-5 fade-in-up" style="animation-delay: 0.2s;">
            <div class="status-empty-icon mx-auto mb-3">
              <i class="bi bi-receipt text-muted" style="font-size: 3rem;"></i>
            </div>
            <h2 class="h4 mb-2 text-dark fw-bold">No Assessment Available</h2>
            <?php if ($appStatus === 'approved'): ?>
                <p class="text-muted mb-0">Your application has been approved. Your financial assessment is currently being prepared by the admission office and will be available shortly.</p>
            <?php elseif ($appStatus === 'rejected'): ?>
                <p class="text-muted mb-0">Your application was not approved. No financial assessment will be generated.</p>
            <?php else: ?>
                <p class="text-muted mb-0">Your financial assessment will be generated once your application is approved by the admission office.</p>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <?php
            $balance = (float)$assessment['net_amount'] - (float)$assessment['total_paid'];
            if ($balance < 0) $balance = 0;
            
            $pendingAmount = 0.0;
            if (!empty($payments)) {
                foreach ($payments as $p) {
                    if ($p['status'] === 'pending') {
                        $pendingAmount += (float)$p['amount'];
                    }
                }
            }
            $allowablePayment = $balance - $pendingAmount;
            if ($allowablePayment < 0) $allowablePayment = 0;

            $statusBadge = match($assessment['payment_status']) {
                'paid' => 'bg-success',
                'partial' => 'bg-warning text-dark',
                default => 'bg-danger'
            };
            $statusLabel = match($assessment['payment_status']) {
                'paid' => 'Fully Paid',
                'partial' => 'Partially Paid',
                default => 'Unpaid'
            };
          ?>
          <div class="row g-4">
            <!-- Left Column: Breakdown -->
            <div class="col-lg-7">
              <?php if ($assessment['academic_level'] === 'College' && !empty($enrolledSubjects)): ?>
              <div class="island mb-4 fade-in-up" style="animation-delay: 0.3s;">
                <div class="island-header fade-in-up" style="animation-delay: 0.4s;">
                  <i class="bi bi-journal-text"></i>
                  <h2>Curriculum Enrolled</h2>
                </div>
                <div class="island-body p-0 fade-in-up" style="animation-delay: 0.5s;">
                  <div class="table-responsive">
                    <table class="table table-hover mb-0">
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

              <div class="island minimal-card mb-4 fade-in-up" style="animation-delay: 0.6s;">
                <div class="island-header bg-transparent border-bottom px-4 pt-4 pb-3 fade-in-up" style="animation-delay: 0.7s;">
                  <h2 class="mb-0 fs-5 fw-bold text-dark"><i class="bi bi-receipt me-2 text-primary"></i>Fee Breakdown</h2>
                </div>
                <div class="island-body p-0 fade-in-up" style="animation-delay: 0.8s;">
                  <ul class="list-group list-group-flush border-0">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-bottom-dashed">
                      <div>
                        <span class="text-muted fw-medium">Tuition Fee</span>
                        <?php 
                          $calcTotalUnits = array_sum(array_column($enrolledSubjects ?? [], 'units'));
                          if (!empty($assessment['is_per_unit']) && $calcTotalUnits > 0): 
                            $inferredCost = (float)$assessment['tuition_fee'] / $calcTotalUnits; 
                        ?>
                          <small class="text-secondary d-block mt-1"><?= esc($calcTotalUnits) ?> units @ ₱<?= number_format($inferredCost, 2) ?>/unit</small>
                        <?php endif; ?>
                      </div>
                      <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['tuition_fee'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-bottom-dashed">
                      <span class="text-muted fw-medium">Miscellaneous Fee</span>
                      <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['miscellaneous_fee'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-bottom-dashed">
                      <span class="text-muted fw-medium">Registration Fee</span>
                      <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['registration_fee'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-bottom-dashed">
                      <span class="text-muted fw-medium">Laboratory Fee</span>
                      <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['laboratory_fee'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-bottom">
                      <span class="text-muted fw-medium">Other Fees</span>
                      <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['other_fees'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 bg-light border-bottom">
                      <span class="fw-bold text-dark text-uppercase small tracking-wide">Gross Amount</span>
                      <span class="fw-bold text-dark">₱<?= number_format((float)$assessment['total_amount'], 2) ?></span>
                    </li>
                    <?php if ((float)$assessment['discount_amount'] > 0): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 text-success border-bottom">
                      <span class="fw-medium"><i class="bi bi-tag-fill me-2"></i><?= htmlspecialchars($assessment['scholarship_name'] ?? 'Scholarship', ENT_QUOTES, 'UTF-8') ?></span>
                      <span class="fw-bold">- ₱<?= number_format((float)$assessment['discount_amount'], 2) ?></span>
                    </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-4 px-4 bg-primary text-white border-0 rounded-bottom">
                      <span class="fw-bold fs-5 text-uppercase tracking-wide">Net Payable</span>
                      <span class="fw-bold fs-4">₱<?= number_format((float)$assessment['net_amount'], 2) ?></span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Right Column: Summary & Payments -->
            <div class="col-lg-5">
              
              <?php
                $netPayable = (float)$assessment['net_amount'];
                $totalPaid = (float)$assessment['total_paid'];
                $paidPercent = $netPayable > 0 ? min(100, round(($totalPaid / $netPayable) * 100)) : 0;
              ?>
              <div class="island minimal-card mb-4 border-0 fade-in-up" style="animation-delay: 0.9s;">
                <div class="island-body p-4 p-md-5 fade-in-up" style="animation-delay: 1s;">
                  <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="text-muted small text-uppercase fw-bold tracking-wide">Financial Status</span>
                    <span class="badge <?= esc($statusBadge) ?> px-3 py-1.5 rounded-pill fs-7 fw-semibold tracking-wide text-uppercase shadow-sm"><?= esc($statusLabel) ?></span>
                  </div>
                  
                  <div class="text-center my-4">
                    <p class="text-muted small mb-1 text-uppercase fw-bold tracking-wide">Remaining Balance</p>
                    <h2 class="display-5 fw-bolder text-dark mb-1" style="letter-spacing: -1.5px;">₱<?= number_format($balance, 2) ?></h2>
                  </div>

                  <!-- Progress Bar -->
                  <div class="mb-4">
                    <div class="d-flex justify-content-between text-muted small fw-semibold mb-1">
                      <span>Payment Progress</span>
                      <span><?= esc($paidPercent) ?>% Paid</span>
                    </div>
                    <div class="progress rounded-pill" style="height: 8px; background-color: #e9ecef;">
                      <div class="progress-bar bg-success rounded-pill" role="progressbar" style="width: <?= esc($paidPercent) ?>%;" aria-valuenow="<?= esc($paidPercent) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                  </div>

                  <div class="row g-3 text-start border-top pt-4">
                    <div class="col-6">
                      <p class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;"><i class="bi bi-wallet2 text-primary me-1"></i> Total Assessed</p>
                      <p class="fw-bold text-dark mb-0 fs-5">₱<?= number_format($netPayable, 2) ?></p>
                    </div>
                    <div class="col-6 border-start ps-3 border-secondary border-opacity-10">
                      <p class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;"><i class="bi bi-check-circle-fill text-success me-1"></i> Total Paid</p>
                      <p class="fw-bold text-success mb-0 fs-5">₱<?= number_format($totalPaid, 2) ?></p>
                    </div>
                  </div>

                  <!-- Quick Action Note -->
                  <div class="mt-4 p-3 bg-light rounded-3 text-center">
                    <?php if ($balance > 0 && $allowablePayment > 0): ?>
                      <p class="text-muted small mb-3 fw-medium">
                        <i class="bi bi-info-circle-fill text-primary me-1"></i> Settle your outstanding balance at the campus cashier or upload your proof of payment online.
                      </p>
                      <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold w-100 py-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
                        <i class="bi bi-credit-card-2-front me-2"></i> Pay Online Now
                      </button>
                    <?php elseif ($balance > 0 && $allowablePayment <= 0): ?>
                      <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-0 d-flex align-items-center gap-3 text-start" style="background-color: #fff3cd; border-left: 4px solid #ffc107 !important;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background-color: #ffe898;">
                          <i class="bi bi-hourglass-split fs-5" style="color: #856404;"></i>
                        </div>
                        <div>
                          <h6 class="fw-bold mb-1" style="color: #664d03; letter-spacing: -0.5px;">Verification Pending</h6>
                          <p class="small mb-0" style="color: #403001; line-height: 1.4;">You have pending payments covering your entire balance. Please wait for the cashier to verify them.</p>
                        </div>
                      </div>
                    <?php else: ?>
                      <p class="text-success small mb-0 fw-bold">
                        <i class="bi bi-patch-check-fill me-1"></i> Your account is fully settled. You are now officially enrolled!
                      </p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Payment History -->
              <div class="island minimal-card fade-in-up" style="animation-delay: 1.1s;">
                <div class="island-header bg-transparent border-bottom px-4 pt-4 pb-3 fade-in-up" style="animation-delay: 1.2s;">
                  <h2 class="mb-0 fs-5 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Payment History</h2>
                </div>
                <div class="island-body p-0 fade-in-up" style="animation-delay: 1.3s;">
                  <div class="list-group list-group-flush border-0">
                    <?php if (empty($payments)): ?>
                      <div class="text-center py-5">
                        <i class="bi bi-wallet2 text-muted opacity-50 mb-3 d-block" style="font-size: 2.5rem;"></i>
                        <p class="text-muted mb-0 small fw-medium">No payments recorded yet.</p>
                      </div>
                    <?php else: ?>
                      <?php foreach ($payments as $payment): ?>
                        <div class="list-group-item py-4 px-4 border-bottom-dashed">
                          <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark fs-5">₱<?= number_format((float)$payment['amount'], 2) ?></span>
                            <?php if ($payment['status'] === 'pending'): ?>
                                <span class="badge rounded-pill px-3 py-1 fw-medium" style="background-color: #ffe898; color: #664d03; border: 1px solid #ffc107;"><i class="bi bi-hourglass-split me-1"></i> Pending Verification</span>
                            <?php elseif ($payment['status'] === 'rejected'): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1 fw-medium"><i class="bi bi-x-circle-fill me-1"></i> Rejected</span>
                            <?php else: ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 fw-medium"><i class="bi bi-check-circle-fill me-1"></i> Verified</span>
                            <?php endif; ?>
                          </div>
                          <div class="d-flex justify-content-between align-items-center text-muted small mt-2">
                            <span class="fw-medium"><i class="bi bi-calendar-event me-1"></i><?= date('M d, Y', strtotime($payment['payment_date'])) ?> &bull; <?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="font-monospace opacity-75">
                                <?php if ($payment['status'] === 'pending' || $payment['status'] === 'rejected'): ?>
                                    Ref: <?= htmlspecialchars((string)($payment['reference_number'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                                <?php else: ?>
                                    Receipt: <?= htmlspecialchars((string)($payment['receipt_number'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </span>
                          </div>
                          <?php if ($payment['status'] === 'rejected' && !empty($payment['remarks'])): ?>
                            <div class="mt-3 p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-3 small text-danger-emphasis">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Rejection Reason:</strong> <?= htmlspecialchars($payment['remarks'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-primary text-white border-0 py-3">
        <h5 class="modal-title fw-bold" id="paymentModalLabel"><i class="bi bi-wallet2 me-2"></i> Submit Proof of Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="paymentProofForm" action="payment_process.php" method="POST" enctype="multipart/form-data">
        <div class="modal-body p-4 bg-light">
          <input type="hidden" name="assessment_id" value="<?= esc($assessment['id'] ?? 0) ?>">
          <input type="hidden" name="action" value="submit_payment_proof">
          <?= getCsrfInput() ?>

          <div id="paymentFormAlert" class="alert alert-danger d-none rounded-3 py-2 px-3 small mb-3"></div>

          <!-- Payment Instructions -->
          <div class="alert alert-info border-0 rounded-3 mb-3 shadow-sm">
            <h6 class="fw-bold mb-2"><i class="bi bi-info-circle-fill me-1"></i> Payment Instructions</h6>
            <p class="small mb-2 text-muted">Please transfer the amount to any of the accounts below and upload a clear screenshot of your transaction.</p>
            <ul class="small mb-0 list-unstyled fw-medium text-dark">
              <li class="mb-1"><i class="bi bi-phone text-primary me-2"></i><strong>GCash:</strong> 0912 345 6789 <span class="text-muted">(SIA Finance)</span></li>
              <li class="mb-1"><i class="bi bi-phone text-primary me-2"></i><strong>Maya:</strong> 0998 765 4321 <span class="text-muted">(SIA Finance)</span></li>
              <li><i class="bi bi-bank text-primary me-2"></i><strong>BDO:</strong> 0012 3456 7890 <span class="text-muted">(SIA Academy)</span></li>
            </ul>
          </div>

          <div class="mb-3">
            <?php 
              $minPayment = min(500.0, (float)($allowablePayment ?? 0)); 
            ?>
            <label class="form-label small fw-semibold text-dark">Amount Paid (₱) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="<?= esc($minPayment) ?>" max="<?= esc($allowablePayment ?? 0) ?>" name="amount" id="payAmountInput" class="form-control bg-white" required placeholder="e.g. <?= number_format($allowablePayment ?? 0, 2, '.', '') ?>" value="<?= number_format($allowablePayment ?? 0, 2, '.', '') ?>">
            <div class="form-text" style="font-size: 0.72rem;">
              Minimum allowed: <strong>₱<?= number_format($minPayment, 2) ?></strong>. Max allowable: <strong>₱<?= number_format($allowablePayment ?? 0, 2) ?></strong>
              <?php if (($pendingAmount ?? 0) > 0): ?>
                <span class="text-warning d-block mt-0.5">(Pending verification: ₱<?= number_format($pendingAmount, 2) ?>)</span>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Payment Method Used <span class="text-danger">*</span></label>
            <select name="payment_method" id="payMethodInput" class="form-select bg-white" required>
              <option value="GCash">GCash</option>
              <option value="Maya">Maya</option>
              <option value="Bank Transfer">Bank Transfer (BDO / BPI / UnionBank)</option>
              <option value="Other">Other Electronic Payment</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Transaction Reference Number <span class="text-danger">*</span></label>
            <input type="text" name="reference_number" id="payRefInput" class="form-control bg-white" required placeholder="e.g. 100294828192" minlength="4" maxlength="100">
            <div class="form-text" style="font-size: 0.72rem;">Enter the exact reference or confirmation code from your receipt.</div>
          </div>

          <div class="mb-2">
            <label class="form-label small fw-semibold text-dark">Upload Receipt / Screenshot <span class="text-danger">*</span></label>
            <input type="file" name="proof_image" id="payFileInput" class="form-control bg-white" accept="image/png, image/jpeg, image/jpg, image/webp" required>
            <div class="form-text" style="font-size: 0.72rem;">Accepted formats: JPG, PNG, WEBP. Max file size: 5MB.</div>
            
            <!-- Live Preview -->
            <div id="proofPreviewBox" class="mt-2 p-2 bg-white rounded-3 border d-none">
              <div class="d-flex align-items-center gap-3">
                <img id="proofPreviewImg" src="" alt="Receipt Preview" class="rounded border" style="width: 55px; height: 55px; object-fit: cover;">
                <div class="flex-grow-1 text-truncate">
                  <div id="proofFileName" class="fw-semibold text-dark small text-truncate">file.jpg</div>
                  <div id="proofFileSize" class="text-muted" style="font-size: 0.7rem;">0 KB</div>
                </div>
                <button type="button" id="proofRemoveBtn" class="btn btn-outline-danger btn-sm rounded-circle p-1" style="width: 28px; height: 28px;" title="Remove image">
                  <i class="bi bi-x"></i>
                </button>
              </div>
            </div>
          </div>

        </div>
        <div class="modal-footer border-top-0 pt-0 bg-light">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="paySubmitBtn" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold">
            <span class="spinner-border spinner-border-sm me-1.5 d-none" id="paySubmitSpinner" role="status" aria-hidden="true"></span>
            <span id="paySubmitText">Submit Payment</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
/* Minimalist Premium Enhancements */
.minimal-card {
    background-color: #ffffff;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.minimal-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    transform: translateY(-2px);
}
.tracking-wide {
    letter-spacing: 0.06em;
}
.border-bottom-dashed {
    border-bottom: 1px dashed #e9ecef;
}
.island-header {
    border-bottom: none;
}
</style>

<script>
(function() {
    function initPaymentModal() {
        const form = document.getElementById('paymentProofForm');
        const fileInput = document.getElementById('payFileInput');
        const previewBox = document.getElementById('proofPreviewBox');
        const previewImg = document.getElementById('proofPreviewImg');
        const fileNameEl = document.getElementById('proofFileName');
        const fileSizeEl = document.getElementById('proofFileSize');
        const removeBtn = document.getElementById('proofRemoveBtn');
        const alertEl = document.getElementById('paymentFormAlert');
        const submitBtn = document.getElementById('paySubmitBtn');
        const spinner = document.getElementById('paySubmitSpinner');
        const submitText = document.getElementById('paySubmitText');
        const amountInput = document.getElementById('payAmountInput');

        if (!form) return;

        function showAlert(msg) {
            if (alertEl) {
                alertEl.textContent = msg;
                alertEl.classList.remove('d-none');
            }
        }

        function hideAlert() {
            if (alertEl) {
                alertEl.classList.add('d-none');
            }
        }

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                hideAlert();
                const file = this.files[0];
                if (!file) {
                    if (previewBox) previewBox.classList.add('d-none');
                    return;
                }

                // Size check: Max 5MB
                if (file.size > 5 * 1024 * 1024) {
                    showAlert('File exceeds the 5MB size limit. Please choose a smaller image.');
                    this.value = '';
                    if (previewBox) previewBox.classList.add('d-none');
                    return;
                }

                // Type check
                const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                if (!allowed.includes(file.type.toLowerCase()) && !file.name.match(/\.(jpg|jpeg|png|webp)$/i)) {
                    showAlert('Invalid file format. Only JPG, PNG, and WEBP images are allowed.');
                    this.value = '';
                    if (previewBox) previewBox.classList.add('d-none');
                    return;
                }

                // Show preview
                if (previewBox && previewImg) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        if (fileNameEl) fileNameEl.textContent = file.name;
                        if (fileSizeEl) fileSizeEl.textContent = (file.size / 1024).toFixed(1) + ' KB';
                        previewBox.classList.remove('d-none');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        if (removeBtn && fileInput) {
            removeBtn.addEventListener('click', function() {
                fileInput.value = '';
                if (previewBox) previewBox.classList.add('d-none');
                hideAlert();
            });
        }

        form.addEventListener('submit', function(e) {
            hideAlert();
            const amount = parseFloat(amountInput ? amountInput.value : 0);
            const minAmt = parseFloat(amountInput ? amountInput.min : 0);
            const maxAmt = parseFloat(amountInput ? amountInput.max : 0);

            if (isNaN(amount) || amount <= 0) {
                e.preventDefault();
                showAlert('Please enter a valid payment amount.');
                return;
            }

            if (minAmt > 0 && amount < minAmt) {
                e.preventDefault();
                showAlert('Minimum payment allowed is ₱' + minAmt.toFixed(2));
                return;
            }

            if (maxAmt > 0 && amount > maxAmt + 0.01) {
                e.preventDefault();
                showAlert('Amount cannot exceed your allowable balance of ₱' + maxAmt.toFixed(2));
                return;
            }

            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                e.preventDefault();
                showAlert('Please attach your proof of payment screenshot.');
                return;
            }

            // Show loading spinner on submit
            if (submitBtn) {
                submitBtn.disabled = true;
                if (spinner) spinner.classList.remove('d-none');
                if (submitText) submitText.textContent = 'Uploading...';
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPaymentModal);
    } else {
        initPaymentModal();
    }
    document.addEventListener('spa:navigated', initPaymentModal);
})();
</script>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

