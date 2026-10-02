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

        <?php if (!empty($_SESSION['info_msg'])): ?>
          <div class="alert alert-info alert-dismissible fade show shadow-sm rounded-4 border-0 p-3 mb-4 d-flex align-items-center gap-3" role="alert">
            <div class="bg-primary text-white rounded-circle p-2 flex-shrink-0 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
              <i class="bi bi-info-circle-fill fs-5"></i>
            </div>
            <div class="flex-grow-1">
              <h6 class="fw-bold mb-0 text-primary">Notice</h6>
              <div class="small"><?= htmlspecialchars($_SESSION['info_msg'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['info_msg']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['warning_msg'])): ?>
          <div class="alert alert-warning alert-dismissible fade show shadow-sm rounded-4 border-0 p-3 mb-4 d-flex align-items-center gap-3" role="alert">
            <div class="bg-warning text-dark rounded-circle p-2 flex-shrink-0 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
              <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            </div>
            <div class="flex-grow-1">
              <h6 class="fw-bold mb-0 text-dark">Checkout Notice</h6>
              <div class="small"><?= htmlspecialchars($_SESSION['warning_msg'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
          <?php unset($_SESSION['warning_msg']); ?>
        <?php endif; ?>

        <?php if (!empty($activeOnlinePayment)): ?>
          <div class="alert alert-info border-0 shadow-sm rounded-4 p-3 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3" style="background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%); border-left: 5px solid #0284c7 !important;">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-primary text-white rounded-circle p-2 flex-shrink-0 d-inline-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="bi bi-credit-card-2-front fs-5"></i>
              </div>
              <div>
                <h6 class="fw-bold mb-1 text-dark">Ongoing PayMongo Checkout Session (₱<?= number_format((float)$activeOnlinePayment['amount'], 2) ?>)</h6>
                <p class="mb-0 small text-muted">
                  You initiated an online payment session. If you have finished paying, click <strong>Verify Status</strong>. You can also resume on PayMongo or cancel the session to unlock your balance.
                </p>
              </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
              <a href="/sia/applicant/payment_callback.php?session_id=<?= urlencode($activeOnlinePayment['checkout_session_id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold">
                <i class="bi bi-shield-check me-1"></i> Verify Status
              </a>
              <?php if (!empty($activeOnlinePayment['checkout_url'])): ?>
                <a href="<?= htmlspecialchars($activeOnlinePayment['checkout_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                  <i class="bi bi-box-arrow-up-right me-1"></i> Resume PayMongo
                </a>
              <?php endif; ?>
              <form action="/sia/applicant/payment_process.php" method="POST" class="d-inline m-0 p-0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="cancel_paymongo_session">
                <input type="hidden" name="payment_id" value="<?= esc($activeOnlinePayment['id']) ?>">
                <input type="hidden" name="session_id" value="<?= esc($activeOnlinePayment['checkout_session_id']) ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold" onclick="return confirm('Cancel this online checkout attempt and restore your assessment balance?')">
                  <i class="bi bi-x-circle me-1"></i> Cancel Session
                </button>
              </form>
            </div>
          </div>
        <?php endif; ?>

        <!-- Persistent Dynamic Payment Queue Session Banner -->
        <div id="queueStickyBanner" class="alert alert-primary border-0 rounded-4 shadow-sm p-3 mb-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 <?= empty($activeQueueSession) ? 'd-none' : '' ?>">
          <div class="d-flex align-items-center gap-3">
            <div id="stickyPulseIcon" class="bg-primary text-white rounded-circle p-2 flex-shrink-0 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
              <i class="bi bi-clock-history fs-5"></i>
            </div>
            <div>
              <h6 class="fw-bold mb-0 text-primary" id="stickyBannerTitle">
                <?= (!empty($activeQueueSession) && ($activeQueueSession['status'] ?? '') === 'waiting') ? 'You are in line in the Payment Queue' : 'Active Payment Reservation Slot' ?>
              </h6>
              <div class="small text-muted" id="stickyBannerSubtitle">
                <?php if (!empty($activeQueueSession) && ($activeQueueSession['status'] ?? '') === 'waiting'): ?>
                  Current Position: <strong id="stickyPositionDisplay">#<?= esc((string)($activeQueueSession['position'] ?? 1)) ?></strong> &bull; Live queue monitoring active
                <?php elseif (!empty($activeQueueSession) && ($activeQueueSession['status'] ?? '') === 'active'): ?>
                  Time Remaining: <span id="stickyTimer" class="font-monospace fw-bold text-success">--:--</span> &bull; Click to proceed to checkout
                <?php else: ?>
                  Status ready &bull; Reservation window open
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="flex-shrink-0">
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 py-2 fw-semibold shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#paymentModal">
              <span id="stickyBtnText">
                <?= (!empty($activeQueueSession) && ($activeQueueSession['status'] ?? '') === 'waiting') ? 'View Queue Status' : 'Open Checkout Window' ?>
              </span> <i class="bi bi-arrow-right ms-1"></i>
            </button>
          </div>
        </div>

        <?php if (($userAppStatus ?? '') === 'approved' && empty($healthStatus)): ?>
          <div class="alert alert-warning border-0 shadow-sm rounded-4 p-3.5 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3" style="background-color: #fff9e6; border-left: 5px solid #ffc107 !important;">
            <div class="d-flex align-items-start gap-3">
              <div class="bg-warning text-dark rounded-circle p-2 flex-shrink-0 mt-1" style="width:42px; height:42px; display:inline-flex; align-items:center; justify-content:center;">
                <i class="bi bi-heart-pulse-fill fs-5"></i>
              </div>
              <div>
                <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-exclamation-circle-fill text-warning me-1"></i> Action Required: Submit Health & Medical Information</h6>
                <p class="mb-0 small text-muted">
                  Your application is approved! You may review your financial assessment statement below, but please remember to submit your <strong>Health Information Form</strong> to complete your enrollment requirements.
                </p>
              </div>
            </div>
            <div class="flex-shrink-0 align-self-end align-self-md-center">
              <a href="health_info.php" class="btn btn-warning text-dark btn-sm rounded-pill px-4 py-2 fw-semibold shadow-sm text-nowrap">
                <i class="bi bi-pencil-square me-1"></i> Fill Up Health Form
              </a>
            </div>
          </div>
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
              <?php if (!empty($enrolledSubjects)): ?>
              <div class="island mb-4">
                <div class="island-header d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-journal-text"></i>
                    <h2 class="mb-0">Curriculum Subjects & Units</h2>
                  </div>
                  <?php if (!empty($assessmentItems)): ?>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle small"><i class="bi bi-shield-check me-1"></i>Official Assessed Snapshot</span>
                  <?php endif; ?>
                </div>
                <div class="island-body p-0">
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
                          <td colspan="2" class="text-end fw-bold text-dark">Total Enrolled Units:</td>
                          <td class="text-end pe-4 fw-bold text-dark fs-5"><?= esc($totalUnits) ?></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <div class="island minimal-card mb-4">
                <div class="island-header bg-transparent border-bottom px-4 pt-4 pb-3 d-flex justify-content-between align-items-center">
                  <h2 class="mb-0 fs-5 fw-bold text-dark"><i class="bi bi-receipt me-2 text-primary"></i>Fee Breakdown</h2>
                  <?php if (!empty($assessmentItems)): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle small"><i class="bi bi-lock-fill me-1"></i>Locked Snapshot</span>
                  <?php endif; ?>
                </div>
                <div class="island-body p-0">
                  <ul class="list-group list-group-flush border-0">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-bottom-dashed">
                      <div>
                        <span class="text-muted fw-medium">Tuition Fee</span>
                        <?php 
                          $calcTotalUnits = array_sum(array_column($enrolledSubjects ?? [], 'units'));
                          if (!empty($assessment['is_per_unit']) && $calcTotalUnits > 0): 
                            $unitRateDisplay = (float)($assessment['template_tuition_rate'] ?? 500.0);
                        ?>
                          <small class="text-secondary d-block mt-1"><?= esc($calcTotalUnits) ?> units @ ₱<?= number_format($unitRateDisplay, 2) ?>/unit</small>
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
        <h5 class="modal-title fw-bold" id="paymentModalLabel"><i class="bi bi-wallet2 me-2"></i> Payment Options</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4 bg-light">
        <!-- Navigation Pills between PayMongo and Manual Upload -->
        <ul class="nav nav-pills nav-fill mb-3 p-1 bg-white rounded-pill shadow-sm border" id="paymentTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill fw-semibold py-2 small" id="tab-paymongo-btn" data-bs-toggle="pill" data-bs-target="#tab-paymongo" type="button" role="tab" aria-selected="true">
              <i class="bi bi-lightning-charge-fill text-warning me-1"></i> PayMongo (GCash / Cards)
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill fw-semibold py-2 small text-muted" id="tab-manual-btn" data-bs-toggle="pill" data-bs-target="#tab-manual" type="button" role="tab" aria-selected="false">
              <i class="bi bi-cloud-arrow-up-fill me-1"></i> Manual Proof Upload
            </button>
          </li>
        </ul>

        <div class="tab-content" id="paymentTabsContent">
          <!-- TAB 1: PayMongo Online Checkout & High-Traffic Queue -->
          <div class="tab-pane fade show active" id="tab-paymongo" role="tabpanel">

            <!-- System Telemetry Badge Header -->
            <div class="card border-0 rounded-3 mb-3 bg-white shadow-sm p-3 border-start border-primary border-4">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <span class="text-uppercase text-muted fw-bold" style="font-size: 0.68rem; letter-spacing: 0.06em;">High-Traffic Payment Queue</span>
                  <div class="fw-bold text-dark small" id="queueCapacityLabel">
                    <i class="bi bi-cpu text-primary me-1"></i> <span id="queueCapacityText"><?= esc((string)($queueMetrics['max_concurrency'] ?? 100)) ?> simultaneous slots</span>
                  </div>
                </div>
                <div class="text-end">
                  <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small" id="queueActiveBadge">
                    <span class="spinner-grow spinner-grow-sm me-1" style="width: 0.45rem; height: 0.45rem;" role="status"></span>
                    <span id="queueActiveText"><?= esc((string)($queueMetrics['active_sessions'] ?? 0)) ?> / <?= esc((string)($queueMetrics['max_concurrency'] ?? 100)) ?> Active</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Queue Notification / Error Box -->
            <div id="queueAlertBox" class="alert alert-danger d-none rounded-3 py-2 px-3 small mb-3"></div>

            <!-- STATE 1: Enter / Join Queue -->
            <div id="queueViewJoin" class="<?= !empty($activeQueueSession) ? 'd-none' : '' ?>">
              <div class="card border-0 rounded-3 mb-3 shadow-sm bg-white p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="small fw-bold text-dark"><i class="bi bi-shield-check text-success me-1"></i> Supported Channels</span>
                  <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">Official PayMongo Gateway</span>
                </div>
                <div class="d-flex flex-wrap gap-1.5 align-items-center small">
                  <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-phone text-primary me-1"></i>GCash</span>
                  <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-wallet text-success me-1"></i>Maya</span>
                  <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-credit-card text-danger me-1"></i>Cards (Visa/MC)</span>
                  <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-car-front text-success me-1"></i>GrabPay</span>
                  <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-bank text-primary me-1"></i>Online Banking</span>
                </div>
              </div>

              <div class="mb-3">
                <?php 
                  $minPayment = min(500.0, (float)($allowablePayment ?? 0)); 
                  if ($minPayment < 100.0) $minPayment = min(100.0, (float)($allowablePayment ?? 0));
                ?>
                <label class="form-label small fw-semibold text-dark">Payment Amount (₱) <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-white fw-bold text-muted">₱</span>
                  <input type="number" step="0.01" min="100.00" max="<?= esc($allowablePayment ?? 0) ?>" id="queueJoinAmountInput" class="form-control bg-white fw-semibold" required placeholder="e.g. <?= number_format($allowablePayment ?? 0, 2, '.', '') ?>" value="<?= number_format($allowablePayment ?? 0, 2, '.', '') ?>">
                </div>
                <div class="form-text" style="font-size: 0.72rem;">
                  Min: <strong>₱100.00</strong>. Remaining Allowable: <strong>₱<?= number_format($allowablePayment ?? 0, 2) ?></strong>
                </div>
              </div>

              <div class="alert alert-info border-0 rounded-3 mb-3 p-2.5 small d-flex align-items-start gap-2 shadow-sm">
                <i class="bi bi-info-circle-fill text-primary flex-shrink-0 mt-0.5"></i>
                <div style="font-size: 0.75rem; line-height: 1.35;">
                  To protect against duplicate billing during peak hours, checkout slots are reserved sequentially. You will receive an exclusive <strong>15-minute checkout window</strong> as soon as you enter.
                </div>
              </div>

              <button type="button" id="btnJoinQueue" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold w-100 py-2.5">
                <span class="spinner-border spinner-border-sm me-1.5 d-none" id="btnJoinQueueSpinner" role="status" aria-hidden="true"></span>
                <span id="btnJoinQueueText"><i class="bi bi-shield-lock-fill me-1"></i> Reserve Checkout Slot & Pay</span>
              </button>
            </div>

            <!-- STATE 2: Waiting in Line -->
            <div id="queueViewWaiting" class="<?= (!empty($activeQueueSession) && ($activeQueueSession['status'] ?? '') === 'waiting') ? '' : 'd-none' ?>">
              <div class="card border-0 rounded-4 bg-white p-4 text-center shadow-sm mb-3">
                <div class="position-relative d-inline-block mx-auto mb-3">
                  <div class="spinner-grow text-primary" style="width: 4rem; height: 4rem;" role="status">
                    <span class="visually-hidden">Waiting in line...</span>
                  </div>
                  <div class="position-absolute top-50 start-50 translate-middle">
                    <i class="bi bi-people-fill text-white fs-4"></i>
                  </div>
                </div>

                <span class="text-uppercase text-muted fw-bold small tracking-wide">Your Position In Line</span>
                <div class="display-3 fw-bold text-primary my-1" id="uiQueuePosition">
                  #<?= esc((string)($activeQueueSession['position'] ?? 1)) ?>
                </div>
                <p class="text-muted small mb-3" id="uiQueueWaitingMsg">
                  All payment slots are currently in use. Please keep this screen open; your browser will automatically reserve your checkout slot the moment one opens.
                </p>

                <div class="p-2.5 rounded-3 bg-light border small text-muted d-flex align-items-center justify-content-between mb-3">
                  <span><i class="bi bi-arrow-repeat me-1 text-primary"></i> Live Connection</span>
                  <span class="badge bg-white text-secondary border fw-medium" id="uiQueuePollBadge">Checking every 2.5s</span>
                </div>

                <button type="button" id="btnLeaveWaitingQueue" class="btn btn-outline-secondary btn-sm rounded-pill px-4 py-1.5 align-self-center">
                  <i class="bi bi-x-circle me-1"></i> Leave Line & Return
                </button>
              </div>
            </div>

            <!-- STATE 3: Payment Slot Active / Ready to Checkout -->
            <div id="queueViewActive" class="<?= (!empty($activeQueueSession) && ($activeQueueSession['status'] ?? '') === 'active') ? '' : 'd-none' ?>">
              <div class="alert alert-success border-0 rounded-3 mb-3 p-3 shadow-sm d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2.5">
                  <div class="bg-success text-white rounded-circle p-1.5 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="bi bi-check-lg fs-5"></i>
                  </div>
                  <div>
                    <span class="badge bg-success bg-opacity-25 text-success-emphasis border border-success-subtle px-2 py-0.5 rounded-pill small fw-bold">Slot Reserved</span>
                    <div class="fw-bold text-dark small">Checkout Ready</div>
                  </div>
                </div>
                <div class="text-end">
                  <span class="text-muted small d-block" style="font-size: 0.7rem;">Time Remaining</span>
                  <span class="fs-5 fw-bold font-monospace text-success" id="uiActiveCountdown">--:--</span>
                </div>
              </div>

              <!-- Checkout Form -->
              <form id="payMongoActiveForm" action="payment_process.php" method="POST">
                <input type="hidden" name="assessment_id" value="<?= esc($assessment['id'] ?? 0) ?>">
                <input type="hidden" name="action" value="initiate_paymongo">
                <input type="hidden" name="session_token" id="activeSessionTokenInput" value="<?= esc($activeQueueSession['session_token'] ?? '') ?>">
                <?= getCsrfInput() ?>

                <div class="mb-3">
                  <label class="form-label small fw-semibold text-dark">Payment Amount (₱) <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-white fw-bold text-muted">₱</span>
                    <input type="number" step="0.01" min="100.00" max="<?= esc($allowablePayment ?? 0) ?>" name="amount" id="activePayAmountInput" class="form-control bg-white fw-semibold" required placeholder="e.g. <?= number_format($allowablePayment ?? 0, 2, '.', '') ?>" value="<?= number_format($allowablePayment ?? 0, 2, '.', '') ?>">
                  </div>
                  <div class="form-text" style="font-size: 0.72rem;">
                    Min: <strong>₱100.00</strong>. Remaining Allowable: <strong>₱<?= number_format($allowablePayment ?? 0, 2) ?></strong>
                  </div>
                </div>

                <div class="alert alert-info border-0 rounded-3 mb-3 p-2.5 small d-flex align-items-start gap-2 shadow-sm">
                  <i class="bi bi-shield-lock-fill text-primary flex-shrink-0 mt-0.5"></i>
                  <div style="font-size: 0.75rem; line-height: 1.35;">
                    Clicking below will advance you directly to the secure PayMongo payment page for GCash, Maya, GrabPay, or Cards.
                  </div>
                </div>

                <button type="submit" id="btnActiveProceed" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold w-100 py-2.5">
                  <span class="spinner-border spinner-border-sm me-1.5 d-none" id="btnActiveProceedSpinner" role="status" aria-hidden="true"></span>
                  <span id="btnActiveProceedText"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Proceed to PayMongo Checkout</span>
                </button>

                <div class="text-center mt-2">
                  <button type="button" id="btnActiveRelease" class="btn btn-link text-muted text-decoration-none small" style="font-size: 0.75rem;">
                    <i class="bi bi-x-circle me-1"></i> Cancel & Release Reserved Slot
                  </button>
                </div>
              </form>
            </div>

            <!-- STATE 4: Session Expired -->
            <div id="queueViewExpired" class="d-none">
              <div class="card border-0 rounded-4 bg-white p-4 text-center shadow-sm mb-3">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-3 d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                  <i class="bi bi-clock-history fs-3"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Reservation Expired</h6>
                <p class="text-muted small mb-3">
                  Your 15-minute checkout window has elapsed. To keep checkout slots open for other students, your slot was recycled. You may re-enter the queue at any time.
                </p>
                <button type="button" id="btnRejoinFromExpired" class="btn btn-primary rounded-pill px-4 py-2 small fw-semibold shadow-sm align-self-center">
                  <i class="bi bi-arrow-clockwise me-1"></i> Rejoin Payment Queue
                </button>
              </div>
            </div>

            <!-- STATE 5: Completed Payment -->
            <div id="queueViewCompleted" class="d-none">
              <div class="card border-0 rounded-4 bg-white p-4 text-center shadow-sm mb-3">
                <div class="bg-success text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                  <i class="bi bi-check-lg fs-3"></i>
                </div>
                <h5 class="fw-bold text-success mb-1">Payment Confirmed!</h5>
                <p class="text-muted small mb-3">
                  Your payment has been successfully recorded and verified. Your assessment balance has been updated.
                </p>
                <button type="button" class="btn btn-outline-success rounded-pill px-4 py-2 small fw-semibold" onclick="window.location.reload();">
                  <i class="bi bi-arrow-clockwise me-1"></i> Refresh Statement
                </button>
              </div>
            </div>

          </div>

          <!-- TAB 2: Manual Proof Upload -->
          <div class="tab-pane fade" id="tab-manual" role="tabpanel">
            <form id="paymentProofForm" action="payment_process.php" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="assessment_id" value="<?= esc($assessment['id'] ?? 0) ?>">
              <input type="hidden" name="action" value="submit_payment_proof">
              <?= getCsrfInput() ?>

              <div id="paymentFormAlert" class="alert alert-danger d-none rounded-3 py-2 px-3 small mb-3"></div>

              <!-- Payment Instructions -->
              <div class="alert alert-info border-0 rounded-3 mb-3 shadow-sm">
                <h6 class="fw-bold mb-2"><i class="bi bi-info-circle-fill me-1"></i> Bank Account Details</h6>
                <p class="small mb-2 text-muted">Please transfer to any of the accounts below and upload your receipt screenshot.</p>
                <ul class="small mb-0 list-unstyled fw-medium text-dark">
                  <li class="mb-1"><i class="bi bi-phone text-primary me-2"></i><strong>GCash:</strong> 0912 345 6789 <span class="text-muted">(SIA Finance)</span></li>
                  <li class="mb-1"><i class="bi bi-phone text-primary me-2"></i><strong>Maya:</strong> 0998 765 4321 <span class="text-muted">(SIA Finance)</span></li>
                  <li><i class="bi bi-bank text-primary me-2"></i><strong>BDO:</strong> 0012 3456 7890 <span class="text-muted">(SIA Academy)</span></li>
                </ul>
              </div>

              <div class="mb-3">
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

              <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Upload Receipt / Screenshot <span class="text-danger">*</span></label>
                <input type="file" name="proof_image" id="payFileInput" class="form-control bg-white" accept="image/png, image/jpeg, image/jpg, image/webp" required>
                <div class="form-text" style="font-size: 0.72rem;">Accepted formats: JPG, PNG, WEBP. Max file size: 5MB.</div>
                
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

              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="paySubmitBtn" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold">
                  <span class="spinner-border spinner-border-sm me-1.5 d-none" id="paySubmitSpinner" role="status" aria-hidden="true"></span>
                  <span id="paySubmitText">Upload Proof</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
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

        // --- High-Traffic Payment Queue & Online Checkout Controller ---
        let currentSessionToken = <?= json_encode((string)($activeQueueSession['session_token'] ?? '')) ?>;
        let currentStatus = <?= json_encode((string)($activeQueueSession['status'] ?? '')) ?>;
        let countdownSeconds = <?= json_encode((int)($activeQueueSession['seconds_remaining'] ?? 0)) ?>;
        let queuePosition = <?= json_encode((int)($activeQueueSession['position'] ?? 1)) ?>;
        let queueMaxCapacity = <?= json_encode((int)($queueMetrics['max_concurrency'] ?? 100)) ?>;
        let queueActiveSessions = <?= json_encode((int)($queueMetrics['active_sessions'] ?? 0)) ?>;

        const viewJoin = document.getElementById('queueViewJoin');
        const viewWaiting = document.getElementById('queueViewWaiting');
        const viewActive = document.getElementById('queueViewActive');
        const viewExpired = document.getElementById('queueViewExpired');
        const viewCompleted = document.getElementById('queueViewCompleted');

        const alertBox = document.getElementById('queueAlertBox');
        const capacityText = document.getElementById('queueCapacityText');
        const activeText = document.getElementById('queueActiveText');
        const activeBadge = document.getElementById('queueActiveBadge');

        const btnJoin = document.getElementById('btnJoinQueue');
        const btnJoinSpinner = document.getElementById('btnJoinQueueSpinner');
        const btnJoinText = document.getElementById('btnJoinQueueText');
        const joinAmountInput = document.getElementById('queueJoinAmountInput');

        const posDisplay = document.getElementById('uiQueuePosition');
        const pollBadge = document.getElementById('uiQueuePollBadge');
        const btnLeaveWaiting = document.getElementById('btnLeaveWaitingQueue');

        const countdownDisplay = document.getElementById('uiActiveCountdown');
        const activeForm = document.getElementById('payMongoActiveForm');
        const activeTokenInput = document.getElementById('activeSessionTokenInput');
        const activeAmountInput = document.getElementById('activePayAmountInput');
        const btnActiveProceed = document.getElementById('btnActiveProceed');
        const btnActiveSpinner = document.getElementById('btnActiveProceedSpinner');
        const btnActiveText = document.getElementById('btnActiveProceedText');
        const btnActiveRelease = document.getElementById('btnActiveRelease');

        const btnRejoinExpired = document.getElementById('btnRejoinFromExpired');

        // Sticky Banner Elements
        const stickyBanner = document.getElementById('queueStickyBanner');
        const stickyTitle = document.getElementById('stickyBannerTitle');
        const stickySubtitle = document.getElementById('stickyBannerSubtitle');
        const stickyBtnText = document.getElementById('stickyBtnText');
        const stickyTimer = document.getElementById('stickyTimer');

        let timerInterval = null;
        let pollInterval = null;

        function showQueueAlert(msg) {
            if (alertBox) {
                alertBox.textContent = msg;
                alertBox.classList.remove('d-none');
            }
        }

        function hideQueueAlert() {
            if (alertBox) {
                alertBox.classList.add('d-none');
            }
        }

        function switchQueueView(viewName) {
            if (viewJoin) viewJoin.classList.toggle('d-none', viewName !== 'join');
            if (viewWaiting) viewWaiting.classList.toggle('d-none', viewName !== 'waiting');
            if (viewActive) viewActive.classList.toggle('d-none', viewName !== 'active');
            if (viewExpired) viewExpired.classList.toggle('d-none', viewName !== 'expired');
            if (viewCompleted) viewCompleted.classList.toggle('d-none', viewName !== 'completed');
            updateStickyBanner(viewName);
        }

        function formatTimer(totalSec) {
            if (totalSec < 0) totalSec = 0;
            const m = Math.floor(totalSec / 60);
            const s = totalSec % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }

        function startActiveCountdown(seconds) {
            clearInterval(timerInterval);
            countdownSeconds = seconds;
            if (countdownDisplay) countdownDisplay.textContent = formatTimer(countdownSeconds);
            if (stickyTimer) stickyTimer.textContent = formatTimer(countdownSeconds);

            timerInterval = setInterval(() => {
                countdownSeconds--;
                const formatted = formatTimer(countdownSeconds);
                if (countdownDisplay) countdownDisplay.textContent = formatted;
                if (stickyTimer) stickyTimer.textContent = formatted;

                if (countdownSeconds <= 0) {
                    clearInterval(timerInterval);
                    currentStatus = 'expired';
                    switchQueueView('expired');
                }
            }, 1000);
        }

        function updateStickyBanner(state) {
            if (!stickyBanner) return;
            if (state === 'active') {
                stickyBanner.classList.remove('d-none');
                if (stickyTitle) stickyTitle.textContent = 'Active Payment Reservation Slot';
                if (stickySubtitle) stickySubtitle.innerHTML = 'Time Remaining: <span id="stickyTimer" class="font-monospace fw-bold text-success">' + formatTimer(countdownSeconds) + '</span> &bull; Click to proceed to checkout';
                if (stickyBtnText) stickyBtnText.textContent = 'Open Checkout Window';
            } else if (state === 'waiting') {
                stickyBanner.classList.remove('d-none');
                if (stickyTitle) stickyTitle.textContent = 'You are in line in the Payment Queue';
                if (stickySubtitle) stickySubtitle.innerHTML = 'Current Position: <strong id="stickyPositionDisplay">#' + queuePosition + '</strong> &bull; Live queue monitoring active';
                if (stickyBtnText) stickyBtnText.textContent = 'View Queue Status';
            } else {
                stickyBanner.classList.add('d-none');
            }
        }

        function updateTelemetry(active, max) {
            if (max !== undefined && capacityText) {
                capacityText.textContent = max + ' simultaneous slots';
            }
            if (active !== undefined && max !== undefined && activeText) {
                activeText.textContent = active + ' / ' + max + ' Active';
            }
        }

        async function pollQueueStatus() {
            if (!currentSessionToken || currentStatus !== 'waiting') {
                return;
            }

            try {
                const res = await fetch('/sia/applicant/payment_queue_status.php?token=' + encodeURIComponent(currentSessionToken), {
                    headers: { 'Accept': 'application/json' }
                });

                if (!res.ok) {
                    if (pollBadge) pollBadge.innerHTML = '<span class="text-warning"><i class="bi bi-wifi-off"></i> Reconnecting...</span>';
                    return;
                }

                const data = await res.json();
                if (pollBadge) pollBadge.innerHTML = 'Live &bull; Connected';

                if (data.max_concurrency && data.active_sessions !== undefined) {
                    updateTelemetry(data.active_sessions, data.max_concurrency);
                }

                if (data.status === 'active') {
                    // Promoted to active slot!
                    clearInterval(pollInterval);
                    currentStatus = 'active';
                    if (activeTokenInput) activeTokenInput.value = currentSessionToken;
                    startActiveCountdown(data.seconds_remaining || 900);
                    switchQueueView('active');
                } else if (data.status === 'waiting') {
                    queuePosition = data.position || queuePosition;
                    if (posDisplay) posDisplay.textContent = '#' + queuePosition;
                    const posEl = document.getElementById('stickyPositionDisplay');
                    if (posEl) posEl.textContent = '#' + queuePosition;
                } else if (data.status === 'expired') {
                    clearInterval(pollInterval);
                    currentStatus = 'expired';
                    switchQueueView('expired');
                } else if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    currentStatus = 'completed';
                    switchQueueView('completed');
                }
            } catch (err) {
                if (pollBadge) pollBadge.innerHTML = '<span class="text-warning"><i class="bi bi-wifi-off"></i> Reconnecting...</span>';
            }
        }

        function startWaitingPolling() {
            clearInterval(pollInterval);
            pollInterval = setInterval(pollQueueStatus, 2500);
            pollQueueStatus();
        }

        // Initialize state on render
        if (currentStatus === 'active') {
            startActiveCountdown(countdownSeconds > 0 ? countdownSeconds : 900);
            switchQueueView('active');
        } else if (currentStatus === 'waiting') {
            switchQueueView('waiting');
            startWaitingPolling();
        } else {
            switchQueueView('join');
        }

        // Join Queue Action
        if (btnJoin) {
            btnJoin.addEventListener('click', async function() {
                hideQueueAlert();
                btnJoin.disabled = true;
                if (btnJoinSpinner) btnJoinSpinner.classList.remove('d-none');
                if (btnJoinText) btnJoinText.textContent = 'Securing Queue Position...';

                try {
                    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
                    const res = await fetch('/sia/applicant/payment_queue_join.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new URLSearchParams({
                            csrf_token: csrfToken
                        })
                    });

                    const json = await res.json();

                    if (!json.success) {
                        showQueueAlert(json.message || 'Failed to enter payment queue.');
                        btnJoin.disabled = false;
                        if (btnJoinSpinner) btnJoinSpinner.classList.add('d-none');
                        if (btnJoinText) btnJoinText.innerHTML = '<i class="bi bi-shield-lock-fill me-1"></i> Reserve Checkout Slot & Pay';
                        return;
                    }

                    const entry = json.data;
                    currentSessionToken = entry.session_token;
                    currentStatus = entry.status;
                    if (activeTokenInput) activeTokenInput.value = currentSessionToken;

                    if (entry.max_concurrency && entry.active_sessions !== undefined) {
                        updateTelemetry(entry.active_sessions, entry.max_concurrency);
                    }

                    if (entry.status === 'active') {
                        // Immediately reserved active slot!
                        startActiveCountdown(entry.seconds_remaining || 900);
                        switchQueueView('active');
                    } else {
                        // Placed in waiting queue
                        queuePosition = entry.position || 1;
                        if (posDisplay) posDisplay.textContent = '#' + queuePosition;
                        switchQueueView('waiting');
                        startWaitingPolling();
                    }
                } catch (err) {
                    showQueueAlert('A connection error occurred. Please check your network and try again.');
                    btnJoin.disabled = false;
                    if (btnJoinSpinner) btnJoinSpinner.classList.add('d-none');
                    if (btnJoinText) btnJoinText.innerHTML = '<i class="bi bi-shield-lock-fill me-1"></i> Reserve Checkout Slot & Pay';
                }
            });
        }

        // Leave Waiting Queue Action
        if (btnLeaveWaiting) {
            btnLeaveWaiting.addEventListener('click', async function() {
                if (!confirm('Are you sure you want to leave the payment queue? You will forfeit your position in line.')) {
                    return;
                }
                btnLeaveWaiting.disabled = true;
                clearInterval(pollInterval);

                const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
                await fetch('/sia/applicant/payment_queue_leave.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        session_token: currentSessionToken,
                        csrf_token: csrfToken
                    })
                });

                currentSessionToken = '';
                currentStatus = '';
                btnLeaveWaiting.disabled = false;
                switchQueueView('join');
                if (btnJoin) {
                    btnJoin.disabled = false;
                    if (btnJoinSpinner) btnJoinSpinner.classList.add('d-none');
                    if (btnJoinText) btnJoinText.innerHTML = '<i class="bi bi-shield-lock-fill me-1"></i> Reserve Checkout Slot & Pay';
                }
            });
        }

        // Cancel / Release Active Slot Action
        if (btnActiveRelease) {
            btnActiveRelease.addEventListener('click', async function() {
                if (!confirm('Are you sure you want to cancel and release your reserved payment slot?')) {
                    return;
                }
                clearInterval(timerInterval);

                const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
                await fetch('/sia/applicant/payment_queue_leave.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        session_token: currentSessionToken,
                        csrf_token: csrfToken
                    })
                });

                currentSessionToken = '';
                currentStatus = '';
                switchQueueView('join');
                if (btnJoin) {
                    btnJoin.disabled = false;
                    if (btnJoinSpinner) btnJoinSpinner.classList.add('d-none');
                    if (btnJoinText) btnJoinText.innerHTML = '<i class="bi bi-shield-lock-fill me-1"></i> Reserve Checkout Slot & Pay';
                }
            });
        }

        // Rejoin from Expired Action
        if (btnRejoinExpired) {
            btnRejoinExpired.addEventListener('click', function() {
                switchQueueView('join');
                if (btnJoin) btnJoin.click();
            });
        }

        // Proceed to PayMongo Active Form Submission
        if (activeForm) {
            activeForm.addEventListener('submit', function(e) {
                const amt = parseFloat(activeAmountInput ? activeAmountInput.value : 0);
                const maxAmt = parseFloat(activeAmountInput ? activeAmountInput.max : 0);
                if (isNaN(amt) || amt < 100) {
                    e.preventDefault();
                    alert('Minimum PayMongo checkout amount is ₱100.00.');
                    return;
                }
                if (maxAmt > 0 && amt > maxAmt + 0.01) {
                    e.preventDefault();
                    alert('Payment amount cannot exceed remaining balance of ₱' + maxAmt.toFixed(2));
                    return;
                }
                if (btnActiveProceed) {
                    btnActiveProceed.disabled = true;
                    if (btnActiveSpinner) btnActiveSpinner.classList.remove('d-none');
                    if (btnActiveText) btnActiveText.textContent = 'Redirecting to PayMongo...';
                }
            });
        }
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

