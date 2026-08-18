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

        <?php if (!$assessment): ?>
          <?php
             // Fetch application status if assessment doesn't exist
             $appStmt = $pdo->prepare('SELECT status FROM applications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1');
             $appStmt->execute(['user_id' => $userId]);
             $appStatus = $appStmt->fetchColumn();
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
                            <td class="text-end pe-4"><?= (int)$sub['units'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                      <tfoot class="table-light">
                        <tr>
                          <td colspan="2" class="text-end fw-bold text-dark">Total Units:</td>
                          <td class="text-end pe-4 fw-bold text-dark fs-5"><?= $totalUnits ?></td>
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
                        <?php if (!empty($assessment['is_per_unit']) && isset($totalUnits) && $totalUnits > 0): ?>
                          <?php $inferredCost = (float)$assessment['tuition_fee'] / $totalUnits; ?>
                          <small class="text-secondary d-block mt-1"><?= $totalUnits ?> units @ ₱<?= number_format($inferredCost, 2) ?>/unit</small>
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
                    <span class="badge <?= $statusBadge ?> px-3 py-1.5 rounded-pill fs-7 fw-semibold tracking-wide text-uppercase shadow-sm"><?= $statusLabel ?></span>
                  </div>
                  
                  <div class="text-center my-4">
                    <p class="text-muted small mb-1 text-uppercase fw-bold tracking-wide">Remaining Balance</p>
                    <h2 class="display-5 fw-bolder text-dark mb-1" style="letter-spacing: -1.5px;">₱<?= number_format($balance, 2) ?></h2>
                  </div>

                  <!-- Progress Bar -->
                  <div class="mb-4">
                    <div class="d-flex justify-content-between text-muted small fw-semibold mb-1">
                      <span>Payment Progress</span>
                      <span><?= $paidPercent ?>% Paid</span>
                    </div>
                    <div class="progress rounded-pill" style="height: 8px; background-color: #e9ecef;">
                      <div class="progress-bar bg-success rounded-pill" role="progressbar" style="width: <?= $paidPercent ?>%;" aria-valuenow="<?= $paidPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
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
</main>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-primary text-white border-0 py-3">
        <h5 class="modal-title fw-bold" id="paymentModalLabel"><i class="bi bi-wallet2 me-2"></i> Submit Proof of Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="payment_process.php" method="POST" enctype="multipart/form-data">
        <div class="modal-body p-4 bg-light">
          <input type="hidden" name="assessment_id" value="<?= $assessment['id'] ?>">
          <input type="hidden" name="action" value="submit_payment_proof">
          <?= getCsrfInput() ?>

          <!-- Payment Instructions -->
          <div class="alert alert-info border-0 rounded-3 mb-4 shadow-sm">
            <h6 class="fw-bold mb-2"><i class="bi bi-info-circle-fill me-1"></i> Payment Instructions</h6>
            <p class="small mb-2">Please transfer the amount to any of the following accounts and upload a screenshot of your successful transaction.</p>
            <ul class="small mb-0 list-unstyled fw-medium text-dark">
              <li><i class="bi bi-phone text-primary me-2"></i><strong>GCash:</strong> 0912 345 6789 (SIA Finance)</li>
              <li><i class="bi bi-phone text-primary me-2"></i><strong>Maya:</strong> 0998 765 4321 (SIA Finance)</li>
              <li><i class="bi bi-bank text-primary me-2"></i><strong>BDO:</strong> 0012 3456 7890 (SIA Academy)</li>
            </ul>
          </div>

          <div class="mb-3">
            <?php $minPayment = min(3000.0, (float)$allowablePayment); ?>
            <label class="form-label small fw-semibold text-dark">Amount Paid (₱)</label>
            <input type="number" step="0.01" min="<?= $minPayment ?>" max="<?= $allowablePayment ?>" name="amount" class="form-control bg-white" required placeholder="e.g. <?= number_format($allowablePayment, 2, '.', '') ?>" value="<?= number_format($allowablePayment, 2, '.', '') ?>">
            <div class="form-text" style="font-size: 0.7rem;">You can pay partially (Minimum: ₱<?= number_format($minPayment, 2) ?>). Allowable Payment: ₱<?= number_format($allowablePayment, 2) ?> <?php if($pendingAmount > 0) echo "(Pending: ₱" . number_format($pendingAmount, 2) . ")"; ?></div>
          </div>
          
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Payment Method Used</label>
            <select name="payment_method" class="form-select bg-white" required>
              <option value="GCash">GCash</option>
              <option value="Maya">Maya</option>
              <option value="Bank Transfer">Bank Transfer (BDO, BPI, etc.)</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Reference Number</label>
            <input type="text" name="reference_number" class="form-control bg-white" required placeholder="e.g. 100294828192">
          </div>

          <div class="mb-2">
            <label class="form-label small fw-semibold text-dark">Upload Screenshot / Receipt</label>
            <input type="file" name="proof_image" class="form-control bg-white" accept="image/png, image/jpeg, image/jpg" required>
            <div class="form-text" style="font-size: 0.7rem;">Accepted formats: JPG, PNG. Max size: 2MB.</div>
          </div>

        </div>
        <div class="modal-footer border-top-0 pt-0 bg-light">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold">Submit Payment</button>
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
document.addEventListener('DOMContentLoaded', function() {
    const proofInput = document.querySelector('input[name="proof_image"]');
    if (proofInput) {
        proofInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('The uploaded file exceeds the 2MB size limit. Please choose a smaller file.');
                    this.value = '';
                    return;
                }
                if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
                    alert('Invalid file format. Only JPG and PNG are allowed.');
                    this.value = '';
                    return;
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

