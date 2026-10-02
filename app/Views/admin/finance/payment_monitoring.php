<?php
$pageTitle = 'Payment Queue & Gateway Monitor - Triple T University';
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
            <i class="bi bi-speedometer2"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Payment Queue & Gateway Monitor</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-lock me-1"></i> Cashier Operations
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-calendar-check text-primary me-1"></i> AY <?= esc($systemSettings['active_school_year'] ?? '2026–2027') ?>
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold" id="headerCapacityBadge">
                <i class="bi bi-cpu text-primary me-1"></i> <?= esc((string)$metrics['max_concurrency']) ?> Capacity
              </span>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold d-inline-flex align-items-center gap-1.5" id="livePollingStatusBadge">
                <span class="spinner-grow spinner-grow-sm" style="width: 0.5rem; height: 0.5rem;" role="status"></span>
                <span>Live Monitoring Active</span>
              </span>
            </div>
            <p class="text-muted small mb-0">Real-time throughput telemetry, dynamic concurrent slot allocation, student waiting lines, and PayMongo gateway reconciliation.</p>
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="cashier_payments.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-receipt-cutoff text-primary"></i>
            <span>Payment Ledger</span>
          </a>
          <a href="cashier_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-grid text-primary"></i>
            <span>Dashboard</span>
          </a>
          <button type="button" id="btnManualRefresh" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-repeat" id="btnManualRefreshIcon"></i>
            <span>Refresh Now</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Alert Notifications -->
    <div id="dynamicAlertBox" class="alert d-none shadow-sm rounded-12 fade-in-up mb-4" role="alert"></div>

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
      
      <!-- Card 1: Active Slots & Capacity Utilization -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.1s;">
          <div class="stat-card-glow bg-primary"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-cpu-fill"></i>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold" id="kpiCapacityPill">
              <?= esc((string)$metrics['max_concurrency']) ?> Max Slots
            </span>
          </div>
          <div class="stat-number-display mb-1 text-primary" style="font-size: 2.15rem;" id="kpiActiveSessions">
            <?= (int)$metrics['active_sessions'] ?> <span class="fs-6 text-muted fw-normal">/ <?= (int)$metrics['max_concurrency'] ?></span>
          </div>
          <h2 class="h6 fw-bold text-dark mb-1">Active Payment Sessions</h2>
          <p class="text-muted small mb-2" id="kpiAvailableSlotsText"><?= (int)$metrics['available_slots'] ?> slots currently available</p>
          <div class="progress mb-2" style="height: 6px;">
            <div class="progress-bar bg-primary" id="kpiUtilizationBar" role="progressbar" style="width: <?= min(100.0, (float)$metrics['utilization_percent']) ?>%;" aria-valuenow="<?= (float)$metrics['utilization_percent'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <div class="stat-card-footer">
            <span>Capacity Utilization</span>
            <span class="stat-card-action text-primary" id="kpiUtilizationPercent"><?= number_format((float)$metrics['utilization_percent'], 1) ?>%</span>
          </div>
        </div>
      </div>

      <!-- Card 2: Virtual Waiting Line -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.15s;">
          <div class="stat-card-glow bg-warning"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-people-fill"></i>
            </div>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold" id="kpiWaitingStatus">
              <?= (int)$metrics['waiting_sessions'] > 0 ? 'Queue Active' : 'No Backlog' ?>
            </span>
          </div>
          <div class="stat-number-display mb-1 text-dark" style="font-size: 2.15rem;" id="kpiWaitingSessions">
            <?= (int)$metrics['waiting_sessions'] ?>
          </div>
          <h2 class="h6 fw-bold text-dark mb-1">Students In Line</h2>
          <p class="text-muted small mb-0">Sequential FIFO waiting queue</p>
          <div class="stat-card-footer mt-auto">
            <span>Queue Status</span>
            <span class="stat-card-action text-warning" id="kpiWaitAction"><?= (int)$metrics['waiting_sessions'] > 0 ? 'Promoting' : 'Clear' ?> <i class="bi bi-arrow-right"></i></span>
          </div>
        </div>
      </div>

      <!-- Card 3: PayMongo Verified Collections -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold" id="kpiVerifiedCountPill">
              <?= (int)$payMongoStats['verified_count'] ?> Verified
            </span>
          </div>
          <div class="stat-number-display mb-1 text-success" style="font-size: 2.15rem;" id="kpiVerifiedCollections">
            ₱<?= number_format((float)$payMongoStats['verified_collections'], 2) ?>
          </div>
          <h2 class="h6 fw-bold text-dark mb-1">PayMongo Intake</h2>
          <p class="text-muted small mb-0">Reconciled via webhook ledger</p>
          <div class="stat-card-footer mt-auto">
            <span>Gateway Fees: <strong id="kpiGatewayFees">₱<?= number_format((float)$payMongoStats['total_gateway_fees'], 2) ?></strong></span>
            <span class="stat-card-action text-success">Audited <i class="bi bi-check2-all"></i></span>
          </div>
        </div>
      </div>

      <!-- Card 4: Session Lifecycle Health -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.25s;">
          <div class="stat-card-glow bg-info"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
              <i class="bi bi-shield-check"></i>
            </div>
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-activity me-1"></i> Health
            </span>
          </div>
          <div class="stat-number-display mb-1 text-dark" style="font-size: 2.15rem;" id="kpiCompletedSessions">
            <?= (int)$sessionCounts['completed'] ?>
          </div>
          <h2 class="h6 fw-bold text-dark mb-1">Completed Checkouts</h2>
          <p class="text-muted small mb-0">
            <span class="text-danger fw-semibold" id="kpiExpiredSessions"><?= (int)$sessionCounts['expired'] ?> Expired</span> &bull; 
            <span class="text-secondary fw-semibold" id="kpiCancelledSessions"><?= (int)$sessionCounts['cancelled'] ?> Cancelled</span>
          </p>
          <div class="stat-card-footer mt-auto">
            <span>Total Tracked Sessions</span>
            <span class="stat-card-action text-info" id="kpiTotalSessions"><?= (int)$sessionCounts['total'] ?> Sessions</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Capacity Governance & Queue Settings Card (Visible to Authorized Staff) -->
    <?php if ($canManageSettings): ?>
      <div class="card border-0 rounded-4 shadow-sm mb-4 fade-in-up" style="animation-delay: 0.28s;">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2.5">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
              <i class="bi bi-sliders fs-5"></i>
            </div>
            <div>
              <h5 class="h6 fw-bold text-dark mb-0">Queue Throughput & Capacity Governance</h5>
              <span class="text-muted small" style="font-size: 0.76rem;">Manage concurrent slots, reservation duration windows, and queue enforcement rules.</span>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <button type="button" id="btnRecycleSweep" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1.5 fw-medium d-inline-flex align-items-center gap-1.5">
              <i class="bi bi-arrow-clockwise"></i>
              <span>Recycle Expired Slots</span>
            </button>
          </div>
        </div>
        <div class="card-body p-4 bg-white">
          <form id="queueSettingsForm" action="queue_settings_process.php" method="POST">
            <?= getCsrfInput() ?>
            <div class="row g-4 align-items-end">
              
              <!-- Quick Capacity Preset Selector -->
              <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-bold text-dark mb-1">
                  Concurrent Checkout Capacity <span class="text-danger">*</span>
                </label>
                <div class="input-group mb-2">
                  <span class="input-group-text bg-light text-muted"><i class="bi bi-cpu"></i></span>
                  <input type="number" min="1" max="5000" class="form-control fw-semibold" id="inputMaxConcurrency" name="max_concurrency" value="<?= esc((string)$metrics['max_concurrency']) ?>" required>
                  <span class="input-group-text bg-light text-muted small">slots</span>
                </div>
                <div class="d-flex gap-1.5 flex-wrap">
                  <span class="text-muted extra-small me-1 mt-1">Quick Scale:</span>
                  <button type="button" class="btn btn-outline-primary btn-xs rounded-pill px-2.5 py-0.5 capacity-preset-btn" data-val="100">100</button>
                  <button type="button" class="btn btn-outline-primary btn-xs rounded-pill px-2.5 py-0.5 capacity-preset-btn" data-val="250">250</button>
                  <button type="button" class="btn btn-outline-primary btn-xs rounded-pill px-2.5 py-0.5 capacity-preset-btn" data-val="500">500</button>
                  <button type="button" class="btn btn-outline-primary btn-xs rounded-pill px-2.5 py-0.5 capacity-preset-btn" data-val="1000">1,000</button>
                </div>
              </div>

              <!-- Session Duration -->
              <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-bold text-dark mb-1">
                  Reservation Window <span class="text-danger">*</span>
                </label>
                <div class="input-group mb-2">
                  <span class="input-group-text bg-light text-muted"><i class="bi bi-clock-history"></i></span>
                  <input type="number" min="1" max="120" class="form-control fw-semibold" id="inputSessionDuration" name="session_duration_minutes" value="<?= esc((string)$metrics['session_duration_minutes']) ?>" required>
                  <span class="input-group-text bg-light text-muted small">minutes</span>
                </div>
                <div class="d-flex gap-1.5 flex-wrap">
                  <span class="text-muted extra-small me-1 mt-1">Presets:</span>
                  <button type="button" class="btn btn-outline-secondary btn-xs rounded-pill px-2 py-0.5 duration-preset-btn" data-val="10">10m</button>
                  <button type="button" class="btn btn-outline-secondary btn-xs rounded-pill px-2 py-0.5 duration-preset-btn" data-val="15">15m</button>
                  <button type="button" class="btn btn-outline-secondary btn-xs rounded-pill px-2 py-0.5 duration-preset-btn" data-val="20">20m</button>
                  <button type="button" class="btn btn-outline-secondary btn-xs rounded-pill px-2 py-0.5 duration-preset-btn" data-val="30">30m</button>
                </div>
              </div>

              <!-- Queue Enforcement Toggle -->
              <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-bold text-dark mb-1">Virtual Queue Status</label>
                <div class="p-2.5 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                  <div class="small fw-semibold text-dark" id="queueStatusToggleLabel">
                    <?= !empty($metrics['queue_enabled']) ? '<span class="text-success"><i class="bi bi-toggle-on fs-5 me-1"></i> Enabled (Active)</span>' : '<span class="text-secondary"><i class="bi bi-toggle-off fs-5 me-1"></i> Bypassed</span>' ?>
                  </div>
                  <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="inputQueueEnabled" name="queue_enabled" value="1" <?= !empty($metrics['queue_enabled']) ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>

              <!-- Submit Button -->
              <div class="col-lg-2 col-md-6 text-end">
                <button type="submit" id="btnSaveSettings" class="btn btn-primary rounded-pill px-4 py-2 w-100 fw-semibold shadow-sm d-inline-flex align-items-center justify-content-center gap-1.5">
                  <span class="spinner-border spinner-border-sm d-none" id="btnSaveSpinner" role="status" aria-hidden="true"></span>
                  <span id="btnSaveText"><i class="bi bi-check2-circle"></i> Save Settings</span>
                </button>
              </div>

            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <!-- Real-Time Monitoring Tabs Card -->
    <div class="card border-0 rounded-4 shadow-sm overflow-hidden mb-5 fade-in-up" style="animation-delay: 0.3s;">
      
      <!-- Card Tab Header -->
      <div class="card-header bg-white border-bottom p-3 px-4">
        <ul class="nav nav-pills card-header-pills" id="monitorTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill fw-semibold py-2 px-3.5 small d-flex align-items-center gap-2" id="tab-active-btn" data-bs-toggle="pill" data-bs-target="#tab-active" type="button" role="tab">
              <i class="bi bi-lightning-charge-fill text-warning"></i>
              <span>Active Checkout Sessions</span>
              <span class="badge bg-primary rounded-pill" id="badgeActiveTabCount"><?= count($activeSessions) ?></span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill fw-semibold py-2 px-3.5 small d-flex align-items-center gap-2" id="tab-waiting-btn" data-bs-toggle="pill" data-bs-target="#tab-waiting" type="button" role="tab">
              <i class="bi bi-people-fill text-primary"></i>
              <span>Virtual Waiting Line</span>
              <span class="badge bg-secondary rounded-pill" id="badgeWaitingTabCount"><?= count($waitingSessions) ?></span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill fw-semibold py-2 px-3.5 small d-flex align-items-center gap-2" id="tab-transactions-btn" data-bs-toggle="pill" data-bs-target="#tab-transactions" type="button" role="tab">
              <i class="bi bi-receipt text-success"></i>
              <span>PayMongo Gateway Transactions</span>
              <span class="badge bg-success bg-opacity-25 text-success-emphasis border border-success-subtle rounded-pill" id="badgeTransTabCount"><?= (int)$payMongoStats['total_transactions'] ?></span>
            </button>
          </li>
        </ul>
      </div>

      <div class="card-body p-0">
        <div class="tab-content" id="monitorTabsContent">

          <!-- TAB 1: Active Checkout Sessions -->
          <div class="tab-pane fade show active" id="tab-active" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 dashboard-table custom-table">
                <thead>
                  <tr>
                    <th scope="col" class="ps-4">Student / Applicant</th>
                    <th scope="col">Application Ref</th>
                    <th scope="col">Assessment Amount</th>
                    <th scope="col">Checkout Session ID</th>
                    <th scope="col">Time Remaining</th>
                    <th scope="col">Activated At</th>
                    <?php if ($canManageSettings): ?>
                      <th scope="col" class="text-end pe-4">Action</th>
                    <?php endif; ?>
                  </tr>
                </thead>
                <tbody id="activeSessionsTableBody">
                  <?php if (empty($activeSessions)): ?>
                    <tr>
                      <td colspan="<?= $canManageSettings ? 7 : 6 ?>" class="text-center py-5 text-muted">
                        <div class="d-flex flex-column align-items-center justify-content-center py-4">
                          <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 64px; height: 64px;">
                            <i class="bi bi-cup-hot fs-2 text-muted"></i>
                          </div>
                          <h3 class="h6 fw-bold text-dark mb-1">No Active Checkout Sessions</h3>
                          <p class="text-muted small mb-0">Slots are available. Active student reservations will display here with live ticking countdowns.</p>
                        </div>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($activeSessions as $s): 
                      $now = time();
                      $expires = strtotime($s['expires_at'] ?? '');
                      $remSec = max(0, $expires - $now);
                      $remMin = floor($remSec / 60);
                      $remS = $remSec % 60;
                      $timeDisplay = sprintf('%02d:%02d', $remMin, $remS);
                      $badgeColor = ($remSec < 120) ? 'danger' : (($remSec < 300) ? 'warning' : 'success');
                      $studentName = trim(($s['last_name'] ?? '') . ', ' . ($s['first_name'] ?? ''));
                      if ($studentName === ',' || $studentName === '') $studentName = 'Student #' . $s['user_id'];
                    ?>
                      <tr id="sessionRow-<?= esc($s['id']) ?>">
                        <td class="ps-4">
                          <div class="d-flex align-items-center gap-2.5">
                            <div class="applicant-avatar"><?= strtoupper(substr($s['first_name'] ?? 'S', 0, 1) . substr($s['last_name'] ?? 'T', 0, 1)) ?></div>
                            <div>
                              <span class="fw-bold text-dark d-block"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></span>
                              <span class="small text-muted font-monospace"><?= htmlspecialchars($s['student_number'] ?? $s['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span class="applicant-ref-badge"><i class="bi bi-file-earmark-text text-primary"></i> <?= esc($s['app_ref'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                          <span class="fw-bold text-dark">₱<?= number_format((float)$s['net_amount'], 2) ?></span>
                        </td>
                        <td>
                          <?php if (!empty($s['checkout_session_id'])): ?>
                            <span class="badge bg-light text-dark border font-monospace px-2 py-1 small"><?= esc(substr($s['checkout_session_id'], 0, 18)) ?>...</span>
                          <?php else: ?>
                            <span class="badge bg-light text-secondary border small">Awaiting Gateway Init</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <span class="badge bg-<?= $badgeColor ?> bg-opacity-10 text-<?= $badgeColor ?> border border-<?= $badgeColor ?> border-opacity-25 rounded-pill px-2.5 py-1 font-monospace fw-bold fs-7">
                            <i class="bi bi-clock me-1"></i> <?= $timeDisplay ?>
                          </span>
                        </td>
                        <td class="text-muted small">
                          <?= date('M d, g:i A', strtotime($s['activated_at'] ?? $s['created_at'])) ?>
                        </td>
                        <?php if ($canManageSettings): ?>
                          <td class="text-end pe-4">
                            <button type="button" class="btn btn-outline-danger btn-xs rounded-pill px-2.5 py-1 force-release-btn" data-token="<?= esc($s['session_token']) ?>">
                              <i class="bi bi-x-circle me-1"></i> Release Slot
                            </button>
                          </td>
                        <?php endif; ?>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- TAB 2: Virtual Waiting Line -->
          <div class="tab-pane fade" id="tab-waiting" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 dashboard-table custom-table">
                <thead>
                  <tr>
                    <th scope="col" class="ps-4">Queue Position</th>
                    <th scope="col">Queue Number</th>
                    <th scope="col">Student / Applicant</th>
                    <th scope="col">Application Ref</th>
                    <th scope="col">Assessment Amount</th>
                    <th scope="col">Entered Queue At</th>
                    <th scope="col" class="text-end pe-4">Last Connection Heartbeat</th>
                  </tr>
                </thead>
                <tbody id="waitingSessionsTableBody">
                  <?php if (empty($waitingSessions)): ?>
                    <tr>
                      <td colspan="7" class="text-center py-5 text-muted">
                        <div class="d-flex flex-column align-items-center justify-content-center py-4">
                          <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 64px; height: 64px;">
                            <i class="bi bi-check2-circle fs-2 text-success"></i>
                          </div>
                          <h3 class="h6 fw-bold text-dark mb-1">Waiting Line is Empty</h3>
                          <p class="text-muted small mb-0">All students are currently serviced immediately within active capacity.</p>
                        </div>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($waitingSessions as $idx => $ws): 
                      $studentName = trim(($ws['last_name'] ?? '') . ', ' . ($ws['first_name'] ?? ''));
                      if ($studentName === ',' || $studentName === '') $studentName = 'Student #' . $ws['user_id'];
                      $hbTime = strtotime($ws['last_heartbeat_at'] ?? $ws['created_at']);
                      $hbSecAgo = max(0, time() - $hbTime);
                    ?>
                      <tr>
                        <td class="ps-4">
                          <span class="badge bg-primary rounded-pill px-3 py-1.5 fw-bold fs-6">#<?= ($idx + 1) ?></span>
                        </td>
                        <td>
                          <span class="badge bg-light text-dark border font-monospace px-2 py-1">QN-<?= (int)$ws['queue_number'] ?></span>
                        </td>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <div class="applicant-avatar"><?= strtoupper(substr($ws['first_name'] ?? 'S', 0, 1) . substr($ws['last_name'] ?? 'T', 0, 1)) ?></div>
                            <div>
                              <span class="fw-bold text-dark d-block"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></span>
                              <span class="small text-muted font-monospace"><?= htmlspecialchars($ws['student_number'] ?? $ws['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span class="applicant-ref-badge"><i class="bi bi-file-earmark-text text-primary"></i> <?= esc($ws['app_ref'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                          <span class="fw-semibold text-dark">₱<?= number_format((float)$ws['net_amount'], 2) ?></span>
                        </td>
                        <td class="text-muted small">
                          <?= date('M d, g:i A', strtotime($ws['created_at'])) ?>
                        </td>
                        <td class="text-end pe-4">
                          <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small">
                            <i class="bi bi-activity text-primary me-1"></i> <?= $hbSecAgo ?>s ago
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- TAB 3: PayMongo Gateway Transactions -->
          <div class="tab-pane fade" id="tab-transactions" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 dashboard-table custom-table">
                <thead>
                  <tr>
                    <th scope="col" class="ps-4">Receipt / Status</th>
                    <th scope="col">Date & Time</th>
                    <th scope="col">Student / Applicant</th>
                    <th scope="col">Checkout Session ID</th>
                    <th scope="col">Payment Intent ID</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Gateway Fee</th>
                    <th scope="col" class="text-end pe-4">Action</th>
                  </tr>
                </thead>
                <tbody id="transactionsTableBody">
                  <?php if (empty($recentTransactions)): ?>
                    <tr>
                      <td colspan="8" class="text-center py-5 text-muted">
                        <div class="d-flex flex-column align-items-center justify-content-center py-4">
                          <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 64px; height: 64px;">
                            <i class="bi bi-wallet2 fs-2 text-muted"></i>
                          </div>
                          <h3 class="h6 fw-bold text-dark mb-1">No PayMongo Transactions Recorded Yet</h3>
                          <p class="text-muted small mb-0">Transactions processed through PayMongo (GCash, Maya, Cards) will display here automatically.</p>
                        </div>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($recentTransactions as $tx): 
                      $txStatus = strtolower($tx['status'] ?? 'pending');
                      $studentName = trim(($tx['student_last'] ?? '') . ', ' . ($tx['student_first'] ?? ''));
                      if ($studentName === ',' || $studentName === '') $studentName = 'Student #' . $tx['user_id'];
                      $badgeClass = match($txStatus) {
                        'verified' => 'bg-success bg-opacity-10 text-success border-success',
                        'pending'  => 'bg-warning bg-opacity-10 text-warning border-warning',
                        'failed'   => 'bg-danger bg-opacity-10 text-danger border-danger',
                        'expired'  => 'bg-secondary bg-opacity-10 text-secondary border-secondary',
                        default    => 'bg-light text-dark border-secondary'
                      };
                    ?>
                      <tr>
                        <td class="ps-4">
                          <?php if (!empty($tx['receipt_number']) && $txStatus === 'verified'): ?>
                            <span class="applicant-ref-badge"><i class="bi bi-receipt text-primary"></i> <?= esc($tx['receipt_number']) ?></span>
                          <?php else: ?>
                            <span class="badge <?= esc($badgeClass) ?> border border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold text-capitalize">
                              <?= esc($txStatus) ?>
                            </span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="text-dark small fw-medium"><?= date('M d, Y', strtotime($tx['created_at'])) ?></div>
                          <div class="extra-small text-muted"><?= date('g:i A', strtotime($tx['created_at'])) ?></div>
                        </td>
                        <td>
                          <span class="fw-bold text-dark d-block"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></span>
                          <span class="small text-muted font-monospace"><?= htmlspecialchars($tx['student_number'] ?? $tx['student_email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                          <?php if (!empty($tx['checkout_session_id'])): ?>
                            <span class="badge bg-light text-dark border font-monospace px-2 py-1 small"><?= esc(substr($tx['checkout_session_id'], 0, 16)) ?>...</span>
                          <?php else: ?>
                            <span class="text-muted small">&mdash;</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if (!empty($tx['payment_intent_id'])): ?>
                            <span class="badge bg-light text-dark border font-monospace px-2 py-1 small"><?= esc(substr($tx['payment_intent_id'], 0, 16)) ?>...</span>
                          <?php else: ?>
                            <span class="text-muted small">&mdash;</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <span class="fw-bold text-dark">₱<?= number_format((float)$tx['amount'], 2) ?></span>
                        </td>
                        <td>
                          <span class="text-muted small">₱<?= number_format((float)$tx['gateway_fee'], 2) ?></span>
                        </td>
                        <td class="text-end pe-4">
                          <?php if ($txStatus === 'verified' && !empty($tx['id'])): ?>
                            <a href="cashier_receipt.php?id=<?= (int)$tx['id'] ?>" class="btn btn-outline-primary btn-xs rounded-pill px-2.5 py-1" target="_blank">
                              <i class="bi bi-printer me-1"></i> Receipt
                            </a>
                          <?php else: ?>
                            <span class="badge bg-light text-muted border small"><?= esc($txStatus) ?></span>
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

<script>
(function() {
    let pollingInterval = null;
    let isPolling = false;

    // Elements
    const btnManualRefresh = document.getElementById('btnManualRefresh');
    const btnManualRefreshIcon = document.getElementById('btnManualRefreshIcon');
    const dynamicAlertBox = document.getElementById('dynamicAlertBox');

    function showAlert(msg, isSuccess = true) {
        if (!dynamicAlertBox) return;
        dynamicAlertBox.className = 'alert ' + (isSuccess ? 'alert-success' : 'alert-danger') + ' d-flex align-items-center shadow-sm rounded-12 fade-in-up mb-4';
        dynamicAlertBox.innerHTML = '<i class="bi ' + (isSuccess ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger') + ' fs-5 me-2.5"></i><div>' + msg + '</div>';
        dynamicAlertBox.classList.remove('d-none');
        setTimeout(() => dynamicAlertBox.classList.add('d-none'), 6000);
    }

    async function fetchTelemetryData() {
        if (isPolling) return;
        isPolling = true;

        try {
            const res = await fetch('/sia/admin/finance/payment_monitoring_data.php', {
                headers: { 'Accept': 'application/json' }
            });

            if (!res.ok) {
                const badge = document.getElementById('livePollingStatusBadge');
                if (badge) badge.className = 'badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold';
                return;
            }

            const json = await res.json();
            if (!json.success || !json.data) return;

            const d = json.data;
            const m = d.metrics;
            const sc = d.session_counts;
            const ps = d.paymongo_stats;

            // Update Header & KPI 1 (Active Sessions & Capacity)
            const capBadge = document.getElementById('headerCapacityBadge');
            if (capBadge) capBadge.innerHTML = '<i class="bi bi-cpu text-primary me-1"></i> ' + m.max_concurrency + ' Capacity';

            const kpiCapPill = document.getElementById('kpiCapacityPill');
            if (kpiCapPill) kpiCapPill.textContent = m.max_concurrency + ' Max Slots';

            const kpiActive = document.getElementById('kpiActiveSessions');
            if (kpiActive) kpiActive.innerHTML = m.active_sessions + ' <span class="fs-6 text-muted fw-normal">/ ' + m.max_concurrency + '</span>';

            const kpiAvail = document.getElementById('kpiAvailableSlotsText');
            if (kpiAvail) kpiAvail.textContent = m.available_slots + ' slots currently available';

            const kpiBar = document.getElementById('kpiUtilizationBar');
            if (kpiBar) {
                const util = Math.min(100, parseFloat(m.utilization_percent) || 0);
                kpiBar.style.width = util + '%';
            }

            const kpiUtilText = document.getElementById('kpiUtilizationPercent');
            if (kpiUtilText) kpiUtilText.textContent = (m.utilization_percent || 0) + '%';

            // KPI 2 (Waiting)
            const kpiWaiting = document.getElementById('kpiWaitingSessions');
            if (kpiWaiting) kpiWaiting.textContent = m.waiting_sessions;

            const kpiWaitingStatus = document.getElementById('kpiWaitingStatus');
            if (kpiWaitingStatus) kpiWaitingStatus.textContent = m.waiting_sessions > 0 ? 'Queue Active' : 'No Backlog';

            // KPI 3 (PayMongo Verified)
            const kpiVerCollections = document.getElementById('kpiVerifiedCollections');
            if (kpiVerCollections) kpiVerCollections.textContent = '₱' + Number(ps.verified_collections || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const kpiVerCount = document.getElementById('kpiVerifiedCountPill');
            if (kpiVerCount) kpiVerCount.textContent = (ps.verified_count || 0) + ' Verified';

            const kpiFees = document.getElementById('kpiGatewayFees');
            if (kpiFees) kpiFees.textContent = '₱' + Number(ps.total_gateway_fees || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // KPI 4 (Session Counts)
            const kpiCompleted = document.getElementById('kpiCompletedSessions');
            if (kpiCompleted) kpiCompleted.textContent = sc.completed || 0;

            const kpiExpired = document.getElementById('kpiExpiredSessions');
            if (kpiExpired) kpiExpired.textContent = (sc.expired || 0) + ' Expired';

            const kpiCancelled = document.getElementById('kpiCancelledSessions');
            if (kpiCancelled) kpiCancelled.textContent = (sc.cancelled || 0) + ' Cancelled';

            const kpiTotal = document.getElementById('kpiTotalSessions');
            if (kpiTotal) kpiTotal.textContent = (sc.total || 0) + ' Sessions';

            // Tab Badges
            const bActive = document.getElementById('badgeActiveTabCount');
            if (bActive) bActive.textContent = (d.active_sessions || []).length;

            const bWaiting = document.getElementById('badgeWaitingTabCount');
            if (bWaiting) bWaiting.textContent = (d.waiting_sessions || []).length;

            const bTrans = document.getElementById('badgeTransTabCount');
            if (bTrans) bTrans.textContent = ps.total_transactions || 0;

        } catch (err) {
            console.error('Telemetry fetch error:', err);
        } finally {
            isPolling = false;
        }
    }

    // Preset Buttons
    document.querySelectorAll('.capacity-preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = document.getElementById('inputMaxConcurrency');
            if (input) input.value = this.dataset.val;
        });
    });

    document.querySelectorAll('.duration-preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = document.getElementById('inputSessionDuration');
            if (input) input.value = this.dataset.val;
        });
    });

    // Form Submission (Queue Settings)
    const settingsForm = document.getElementById('queueSettingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveSettings');
            const spinner = document.getElementById('btnSaveSpinner');
            const text = document.getElementById('btnSaveText');

            if (btn) btn.disabled = true;
            if (spinner) spinner.classList.remove('d-none');
            if (text) text.textContent = 'Saving...';

            try {
                const formData = new FormData(settingsForm);
                const res = await fetch('/sia/admin/finance/queue_settings_process.php', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const json = await res.json();
                if (json.success) {
                    showAlert(json.message, true);
                    fetchTelemetryData();
                } else {
                    showAlert(json.message || 'Failed to update settings.', false);
                }
            } catch (err) {
                showAlert('Connection error while saving settings.', false);
            } finally {
                if (btn) btn.disabled = false;
                if (spinner) spinner.classList.add('d-none');
                if (text) text.innerHTML = '<i class="bi bi-check2-circle"></i> Save Settings';
            }
        });
    }

    // Recycle Stale Sweeper Button
    const btnRecycle = document.getElementById('btnRecycleSweep');
    if (btnRecycle) {
        btnRecycle.addEventListener('click', async function() {
            if (!confirm('Run maintenance sweep to recycle expired slots and promote waiting applicants?')) return;
            btnRecycle.disabled = true;

            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
            try {
                const res = await fetch('/sia/admin/finance/queue_action_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        action: 'recycle_stale',
                        csrf_token: csrfToken
                    })
                });

                const json = await res.json();
                if (json.success) {
                    showAlert(json.message, true);
                    fetchTelemetryData();
                } else {
                    showAlert(json.message, false);
                }
            } catch (err) {
                showAlert('Error running maintenance sweep.', false);
            } finally {
                btnRecycle.disabled = false;
            }
        });
    }

    // Force Release Buttons
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.force-release-btn');
        if (!btn) return;
        const token = btn.dataset.token;
        if (!token) return;

        if (!confirm('Are you sure you want to forcibly release this payment slot? The student reservation will be cancelled.')) return;

        btn.disabled = true;
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
        try {
            const res = await fetch('/sia/admin/finance/queue_action_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    action: 'force_release',
                    session_token: token,
                    csrf_token: csrfToken
                })
            });

            const json = await res.json();
            if (json.success) {
                showAlert(json.message, true);
                fetchTelemetryData();
            } else {
                showAlert(json.message, false);
            }
        } catch (err) {
            showAlert('Error releasing slot.', false);
        } finally {
            btn.disabled = false;
        }
    });

    // Manual Refresh
    if (btnManualRefresh) {
        btnManualRefresh.addEventListener('click', function() {
            if (btnManualRefreshIcon) btnManualRefreshIcon.classList.add('spin-animation');
            fetchTelemetryData().finally(() => {
                setTimeout(() => {
                    if (btnManualRefreshIcon) btnManualRefreshIcon.classList.remove('spin-animation');
                }, 500);
            });
        });
    }

    // Polling Interval every 3 seconds
    pollingInterval = setInterval(fetchTelemetryData, 3000);
})();
</script>

<style>
.spin-animation {
    display: inline-block;
    animation: spin 0.8s linear infinite;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}
.btn-xs {
    font-size: 0.72rem;
    padding: 0.15rem 0.5rem;
}
</style>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
