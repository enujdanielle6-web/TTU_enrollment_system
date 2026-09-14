<?php
$pageTitle = 'Payment History & Ledger - Triple T University';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip (Design System Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-receipt-cutoff"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Payment History & Ledger</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> Cashier Records
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-calendar-check text-primary me-1"></i> AY <?= esc($systemSettings['active_school_year'] ?? '2026–2027') ?>
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-receipt text-primary me-1"></i> <?= count($payments) ?> Transactions
              </span>
              <?php if (!empty($stats['pending_reviews'])): ?>
                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                  <i class="bi bi-hourglass-split me-1"></i> <?= (int)$stats['pending_reviews'] ?> Pending Review
                </span>
              <?php endif; ?>
            </div>
            <p class="text-muted small mb-0">Global audit trail of all student payments, cash drawer transactions, online bank deposits, and generated receipts.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="cashier_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>Back to Dashboard</span>
          </a>
          <a href="fees.php" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-sliders"></i>
            <span>Fee Templates</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (!empty($successMsg)): ?>
      <div class="alert alert-success d-flex align-items-center shadow-sm rounded-12 fade-in-up mb-4" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2.5 text-success"></i>
        <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-12 fade-in-up mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2.5 text-danger"></i>
        <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <!-- Executive KPI Metric Cards (Consistent 4-Column Grid) -->
    <div class="row g-4 mb-4">
      
      <!-- Card 1: Verified Collections -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.1s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-cash-coin"></i>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-shield-check me-1"></i> Verified
            </span>
          </div>
          <div class="stat-number-display mb-1" style="font-size: 2.15rem;">₱<?= number_format($stats['total_collections'], 2) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Total Verified</h2>
          <p class="text-muted small mb-0">Total fees successfully recorded</p>
          <div class="stat-card-footer">
            <span>Verified Intake</span>
            <span class="stat-card-action text-success">Approved Ledgers <i class="bi bi-check2"></i></span>
          </div>
        </div>
      </div>

      <!-- Card 2: Today's Intake -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.15s;">
          <div class="stat-card-glow bg-primary"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-calendar-check-fill"></i>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-sun-fill me-1"></i> Today
            </span>
          </div>
          <div class="stat-number-display mb-1 text-primary" style="font-size: 2.15rem;">₱<?= number_format($stats['today_collections'], 2) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Collections Today</h2>
          <p class="text-muted small mb-0"><?= date('M d, Y') ?> drawer receipts</p>
          <div class="stat-card-footer">
            <span>Daily Intake</span>
            <span class="stat-card-action text-primary">Active Drawer <i class="bi bi-arrow-right"></i></span>
          </div>
        </div>
      </div>

      <!-- Card 3: Pending Review -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up payment-filter-trigger" data-filter="pending" style="animation-delay: 0.2s; cursor: pointer;">
          <div class="stat-card-glow bg-warning"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-hourglass-split"></i>
            </div>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-clock me-1"></i> Action Needed
            </span>
          </div>
          <div class="stat-number-display mb-1 text-warning" style="font-size: 2.15rem;"><?= number_format($stats['pending_reviews']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Pending Verification</h2>
          <p class="text-muted small mb-0">Online slips awaiting cashier approval</p>
          <div class="stat-card-footer">
            <span>Online Queue</span>
            <span class="stat-card-action text-warning">Filter Pending <i class="bi bi-arrow-right"></i></span>
          </div>
        </div>
      </div>

      <!-- Card 4: Total Transactions -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up payment-filter-trigger" data-filter="all" style="animation-delay: 0.25s; cursor: pointer;">
          <div class="stat-card-glow bg-info"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
              <i class="bi bi-file-earmark-text-fill"></i>
            </div>
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-archive-fill me-1"></i> Ledger Size
            </span>
          </div>
          <div class="stat-number-display mb-1" style="font-size: 2.15rem;"><?= number_format($stats['total_transactions']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Total Transactions</h2>
          <p class="text-muted small mb-0">Logged payments & submissions</p>
          <div class="stat-card-footer">
            <span>Full Ledger</span>
            <span class="stat-card-action text-info">View All <i class="bi bi-arrow-down"></i></span>
          </div>
        </div>
      </div>

    </div>

    <!-- All Transactions Table (Dossier Card Styling) -->
    <div class="dossier-card fade-in-up" id="paymentsLedger" style="animation-delay: 0.3s;">
      <div class="dossier-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-receipt"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">All Transactions</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <i class="bi bi-activity text-primary me-1"></i><span id="paymentCount"><?= count($payments) ?></span> Entries
            </span>
          </div>
        </div>

        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
          <!-- Status Filter Tabs -->
          <div class="btn-group p-1 bg-light border rounded-pill" role="group" aria-label="Transaction Filter">
            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold filter-tab-btn active" data-status="all">
              All <span class="badge bg-secondary rounded-pill ms-1"><?= count($payments) ?></span>
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold filter-tab-btn" data-status="verified">
              Verified
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold filter-tab-btn" data-status="pending">
              Pending <?php if ($stats['pending_reviews'] > 0): ?><span class="badge bg-warning text-dark rounded-pill ms-1"><?= $stats['pending_reviews'] ?></span><?php endif; ?>
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold filter-tab-btn" data-status="rejected">
              Rejected
            </button>
          </div>

          <!-- Real-Time Search -->
          <div class="input-group shadow-xs" style="min-width: 240px;">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" id="tableSearch" class="form-control border-start-0 ps-0" placeholder="Search receipt, student..." autocomplete="off">
            <button class="btn btn-outline-secondary border-start-0 bg-white text-muted d-none" type="button" id="clearSearchBtn">
              <i class="bi bi-x-circle-fill"></i>
            </button>
          </div>
        </div>

      </div>
      
      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table custom-table">
            <thead>
              <tr>
                <th scope="col" class="ps-4">Receipt / Status</th>
                <th scope="col">Date & Time</th>
                <th scope="col">Student / Applicant</th>
                <th scope="col">Payment Method</th>
                <th scope="col">Amount</th>
                <th scope="col">Processed By</th>
                <th scope="col" class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody id="paymentsTableBody">
              <?php if (empty($payments)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 72px; height: 72px;">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">No Payments Recorded Yet</h3>
                      <p class="text-muted small mb-0">Transactions will appear here automatically once payments are collected or submitted online.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($payments as $payment): 
                    $pStatus = strtolower($payment['status'] ?? 'verified');
                    $studentFirst = trim($payment['student_first'] ?? '');
                    $studentLast = trim($payment['student_last'] ?? '');
                    $studentName = trim($studentLast . ', ' . $studentFirst);
                    if ($studentName === ',' || $studentName === '') {
                        $studentName = 'Unknown Student';
                    }

                    $firstInitial = mb_substr($studentFirst, 0, 1);
                    $lastInitial = mb_substr($studentLast, 0, 1);
                    $initials = strtoupper($firstInitial . $lastInitial);
                    if ($initials === '') $initials = 'ST';

                    $methodClass = match(strtolower($payment['payment_method'] ?? '')) {
                        'cash'          => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                        'gcash'         => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                        'bank transfer' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                        default         => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25'
                    };
                ?>
                  <tr class="payment-row"
                      data-status="<?= esc($pStatus) ?>"
                      data-receipt="<?= esc(strtolower($payment['receipt_number'] ?? '')) ?>"
                      data-name="<?= esc(strtolower($studentName)) ?>"
                      data-ref="<?= esc(strtolower($payment['app_ref'] ?? '')) ?>"
                      data-method="<?= esc(strtolower($payment['payment_method'] ?? '')) ?>"
                      data-cashier="<?= esc(strtolower(($payment['cashier_last'] ?? '') . ' ' . ($payment['cashier_first'] ?? ''))) ?>">
                    
                    <!-- Receipt / Status -->
                    <td class="ps-4">
                      <?php if ($pStatus === 'pending'): ?>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1.5">
                          <i class="bi bi-hourglass-split"></i> Pending Verification
                        </span>
                      <?php elseif ($pStatus === 'rejected'): ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1.5">
                          <i class="bi bi-x-circle-fill"></i> Rejected
                        </span>
                      <?php else: ?>
                        <span class="applicant-ref-badge">
                          <i class="bi bi-receipt text-primary"></i><?= htmlspecialchars($payment['receipt_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                      <?php endif; ?>
                    </td>

                    <!-- Date & Time -->
                    <td>
                      <div class="d-flex align-items-center gap-1.5 text-dark fw-medium">
                        <i class="bi bi-clock-history text-muted small"></i>
                        <span><?= date('M d, Y', strtotime($payment['created_at'])) ?></span>
                      </div>
                      <div class="extra-small text-muted"><?= date('g:i A', strtotime($payment['created_at'])) ?></div>
                    </td>

                    <!-- Student Name -->
                    <td>
                      <div class="d-flex align-items-center gap-2.5">
                        <div class="applicant-avatar">
                          <?= esc($initials) ?>
                        </div>
                        <div>
                          <span class="fw-bold text-dark d-block"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></span>
                          <div class="small text-muted d-flex align-items-center gap-2">
                            <span>Ref: <span class="font-monospace fw-medium text-secondary"><?= htmlspecialchars($payment['app_ref'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span></span>
                          </div>
                        </div>
                      </div>
                    </td>

                    <!-- Method -->
                    <td>
                      <span class="badge <?= esc($methodClass) ?> rounded-pill px-3 py-1 fw-semibold small d-inline-flex align-items-center gap-1">
                        <i class="bi <?= strtolower($payment['payment_method'] ?? '') === 'cash' ? 'bi-cash' : 'bi-phone' ?>"></i>
                        <?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>

                    <!-- Amount -->
                    <td>
                      <span class="fw-bold text-success fs-6">₱<?= number_format((float)$payment['amount'], 2) ?></span>
                    </td>

                    <!-- Processed By -->
                    <td>
                      <?php if ($pStatus === 'pending'): ?>
                        <span class="badge bg-light text-warning border rounded-pill px-2.5 py-0.5 small fw-semibold">Needs Review</span>
                      <?php elseif ($pStatus === 'rejected'): ?>
                        <span class="badge bg-light text-danger border rounded-pill px-2.5 py-0.5 small fw-semibold">Rejected</span>
                      <?php else: ?>
                        <span class="small fw-medium text-dark d-inline-flex align-items-center gap-1">
                          <i class="bi bi-person-check text-muted"></i>
                          <?= htmlspecialchars(($payment['cashier_last'] ?? 'System') . (!empty($payment['cashier_first']) ? ', ' . $payment['cashier_first'] : ''), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                      <?php endif; ?>
                    </td>

                    <!-- Action -->
                    <td class="text-end pe-4">
                      <?php if ($pStatus === 'pending'): ?>
                        <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 fw-semibold verify-btn shadow-xs d-inline-flex align-items-center gap-1.5"
                                data-id="<?= esc($payment['id']) ?>"
                                data-name="<?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?>"
                                data-amount="<?= number_format((float)$payment['amount'], 2) ?>"
                                data-method="<?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?>"
                                data-ref="<?= htmlspecialchars($payment['reference_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>"
                                data-img="/sia/uploads/payments/<?= htmlspecialchars($payment['proof_image'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-bs-toggle="modal" data-bs-target="#verifyModal">
                          <i class="bi bi-shield-check"></i>
                          <span>Verify</span>
                        </button>
                      <?php elseif ($pStatus === 'rejected'): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-medium d-inline-flex align-items-center gap-1.5"
                                onclick="showRejectReason('<?= htmlspecialchars(addslashes($payment['remarks'] ?? 'No reason provided.')) ?>')">
                          <i class="bi bi-info-circle"></i>
                          <span>Reason</span>
                        </button>
                      <?php else: ?>
                        <a href="cashier_receipt.php?id=<?= esc($payment['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium d-inline-flex align-items-center gap-1.5" target="_blank">
                          <i class="bi bi-printer"></i>
                          <span>Receipt</span>
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>

              <!-- No Results Row -->
              <tr id="noResultsRow" style="display: none;">
                <td colspan="7" class="text-center py-5 text-muted">
                  <div class="d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 64px; height: 64px;">
                      <i class="bi bi-search fs-2 text-muted"></i>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-1">No Matching Payments Found</h3>
                    <p class="text-muted small mb-0">Try changing your search term or selecting a different status filter.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

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

    function initCashierPayments() {
        const searchInput = document.getElementById('tableSearch');
        const clearBtn = document.getElementById('clearSearchBtn');
        const filterBtns = document.querySelectorAll('.filter-tab-btn');
        const rows = document.querySelectorAll('#paymentsTableBody tr.payment-row');
        const noResultsRow = document.getElementById('noResultsRow');
        const paymentCountEl = document.getElementById('paymentCount');
        const filterTriggers = document.querySelectorAll('.payment-filter-trigger');

        let currentStatusFilter = 'all';
        let currentSearchQuery = '';

        function applyPaymentFilters() {
            let visibleCount = 0;
            const query = currentSearchQuery.trim().toLowerCase();

            rows.forEach(row => {
                const rStatus = row.dataset.status || '';
                const rReceipt = row.dataset.receipt || '';
                const rName = row.dataset.name || '';
                const rRef = row.dataset.ref || '';
                const rMethod = row.dataset.method || '';
                const rCashier = row.dataset.cashier || '';

                const matchesStatus = (currentStatusFilter === 'all') || (rStatus === currentStatusFilter);
                let matchesSearch = true;
                if (query !== '') {
                    matchesSearch = rReceipt.includes(query) ||
                                    rName.includes(query) ||
                                    rRef.includes(query) ||
                                    rMethod.includes(query) ||
                                    rCashier.includes(query);
                }

                if (matchesStatus && matchesSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (paymentCountEl) paymentCountEl.textContent = visibleCount;
            if (noResultsRow) {
                noResultsRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                currentSearchQuery = this.value;
                if (clearBtn) {
                    clearBtn.classList.toggle('d-none', this.value === '');
                }
                applyPaymentFilters();
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (searchInput) {
                    searchInput.value = '';
                    currentSearchQuery = '';
                    clearBtn.classList.add('d-none');
                    searchInput.focus();
                    applyPaymentFilters();
                }
            });
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentStatusFilter = this.dataset.status || 'all';
                applyPaymentFilters();
            });
        });

        filterTriggers.forEach(trigger => {
            trigger.addEventListener('click', function() {
                const target = this.dataset.filter;
                if (target) {
                    filterBtns.forEach(b => {
                        if (b.dataset.status === target) {
                            b.click();
                        }
                    });
                }
            });
        });

        // Toolbar buttons
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

        // Wheel zoom inside viewport
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
        document.addEventListener('DOMContentLoaded', initCashierPayments);
    } else {
        initCashierPayments();
    }
    document.addEventListener('spa:navigated', initCashierPayments);
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
    const rejectBtn = document.querySelector('#rejectConfirmBox button.btn-danger');
    if (rejectBtn && !rejectBtn.disabled) {
        rejectBtn.disabled = true;
        rejectBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Rejecting...';
    }
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'decision';
    input.value = 'reject';
    form.appendChild(input);
    form.submit();
};

document.addEventListener('DOMContentLoaded', function() {
    const verifyForm = document.querySelector('#verifyModal form');
    if (verifyForm) {
        verifyForm.addEventListener('submit', function() {
            const approveBtn = document.getElementById('approvePaymentBtn');
            if (approveBtn && !approveBtn.disabled) {
                approveBtn.disabled = true;
                approveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
