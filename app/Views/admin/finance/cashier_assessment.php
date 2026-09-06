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
          <div class="island-header border-bottom border-light fade-in-up d-flex justify-content-between align-items-center" style="animation-delay: 0.5s;">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-list-nested text-secondary"></i>
              <h2 class="mb-0">Fee Breakdown</h2>
            </div>
            <?php if (!empty($assessmentItems)): ?>
              <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle small"><i class="bi bi-lock-fill me-1"></i>Locked Snapshot</span>
            <?php endif; ?>
          </div>
          <div class="island-body p-0 fade-in-up" style="animation-delay: 0.6s;">
             <ul class="list-group list-group-flush rounded-bottom-4 small">
                <li class="list-group-item d-flex justify-content-between p-3">
                    <div>
                      <span class="text-muted d-block">Tuition Fee</span>
                      <?php 
                        $calcTotalUnits = array_sum(array_column($enrolledSubjects ?? [], 'units'));
                        if (!empty($assessment['is_per_unit']) && $calcTotalUnits > 0): 
                          $inferredCost = (float)$assessment['tuition_fee'] / $calcTotalUnits; 
                      ?>
                        <small class="text-secondary"><?= esc($calcTotalUnits) ?> units @ ₱<?= number_format($inferredCost, 2) ?>/unit</small>
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
        <?php if (!empty($enrolledSubjects)): ?>
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4 fade-in-up" style="animation-delay: 0.7s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light fade-in-up d-flex justify-content-between align-items-center" style="animation-delay: 0.8s;">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-journal-text text-primary"></i>
              <h2 class="mb-0 text-dark"><?= $assessment['academic_level'] === 'College' ? 'Curriculum Enrolled' : 'SHS Subjects Enrolled' ?></h2>
            </div>
            <?php if (!empty($assessmentItems)): ?>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle small"><i class="bi bi-shield-check me-1"></i>Snapshot Verified</span>
            <?php endif; ?>
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
                            <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 fw-semibold verify-btn shadow-sm"
                                    data-id="<?= esc($payment['id']) ?>"
                                    data-name="<?= htmlspecialchars($assessment['first_name'] . ' ' . $assessment['last_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-amount="<?= number_format((float)$payment['amount'], 2) ?>"
                                    data-method="<?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-ref="<?= htmlspecialchars($payment['reference_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>"
                                    data-img="/sia/uploads/payments/<?= htmlspecialchars($payment['proof_image'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-bs-toggle="modal" data-bs-target="#verifyModal">
                              <i class="bi bi-shield-check"></i> Verify
                            </button>
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

<!-- Verify Payment Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-white border-bottom py-3">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="verifyModalLabel">
          <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-25 text-warning-emphasis rounded-circle me-3" style="width: 36px; height: 36px;">
            <i class="bi bi-shield-check fs-5" style="color: #d97706;"></i>
          </div>
          Verify Proof of Payment
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
                <div class="p-3 bg-white border rounded-3 shadow-sm">
                  <p class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px;"><i class="bi bi-person me-1"></i> Student Name</p>
                  <p class="fw-bold text-dark mb-0 fs-6" id="verifyStudentName"></p>
                </div>
                <div class="p-3 bg-white border rounded-3 shadow-sm" style="border-color: #a3cfbb !important;">
                  <p class="text-success small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px;"><i class="bi bi-cash-stack me-1"></i> Amount Declared</p>
                  <p class="fw-bolder text-success mb-0 fs-3" style="letter-spacing: -1px;">₱<span id="verifyAmount"></span></p>
                </div>
                <div class="p-3 bg-white border rounded-3 shadow-sm">
                  <p class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px;"><i class="bi bi-credit-card me-1"></i> Method & Reference</p>
                  <div class="d-flex align-items-center gap-2 mt-2">
                    <span class="badge bg-light text-dark border px-2 py-1" id="verifyMethod"></span>
                    <span class="fw-medium text-secondary font-monospace small" id="verifyRef"></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-7">
              <div class="bg-light p-2 rounded-4 h-100 d-flex flex-column position-relative border" style="min-height: 380px;">
                
                <!-- Zoom Toolbar Header -->
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

                <!-- Zoomable / Pannable Viewport -->
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
            <textarea name="remarks" id="verifyRemarks" class="form-control border-secondary border-opacity-25 rounded-3 shadow-sm" rows="2" placeholder="e.g., Screenshot is blurry, Amount is incorrect, etc."></textarea>
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
            <button type="submit" name="decision" value="approve" class="btn btn-success rounded-pill px-4 shadow-sm fw-semibold"><i class="bi bi-check-circle me-1"></i> Approve Payment</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

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



