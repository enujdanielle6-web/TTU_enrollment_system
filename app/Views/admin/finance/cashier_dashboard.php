<?php
$pageTitle = 'Cashier Dashboard - Triple T University';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">

    <!-- Dossier Hero Header Strip (Consistent Design System) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Cashier Dashboard</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-wallet2 me-1"></i> Cashier & Student Accounts
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-calendar-check text-primary me-1"></i> AY <?= esc($systemSettings['active_school_year'] ?? '2026–2027') ?>
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-person-check text-primary me-1"></i> <?= count($assessments) ?> Accounts
              </span>
              <?php if (!empty($stats['pending_verifications'])): ?>
                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                  <i class="bi bi-hourglass-split me-1"></i> <?= (int)$stats['pending_verifications'] ?> Pending Verification
                </span>
              <?php endif; ?>
            </div>
            <p class="text-muted small mb-0">Institutional student financial ledger, tuition assessments, collection auditing, and official receipts management.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="cashier_payments.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-receipt-cutoff text-primary"></i>
            <span>Payment Ledger</span>
            <?php if (!empty($stats['pending_verifications'])): ?>
              <span class="badge bg-warning text-dark rounded-pill px-2"><?= $stats['pending_verifications'] ?></span>
            <?php endif; ?>
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
      
      <!-- Card 1: Total Revenue -->
      <div class="col-sm-6 col-xl-3">
        <a href="cashier_payments.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.1s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-cash-stack"></i>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-shield-check me-1"></i> Collected
            </span>
          </div>
          <div class="stat-number-display mb-1" style="font-size: 2.15rem;">₱<?= number_format($stats['total_revenue'], 2) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Total Collections</h2>
          <p class="text-muted small mb-0">All-time revenue received</p>
          <div class="stat-card-footer">
            <span>Payment Ledger</span>
            <span class="stat-card-action text-success">View History <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 2: Outstanding Balances -->
      <div class="col-sm-6 col-xl-3">
        <a href="#accountsRoster" class="stat-card-kpi fade-in-up filter-trigger" data-filter="unpaid" style="animation-delay: 0.15s;">
          <div class="stat-card-glow bg-danger"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger">
              <i class="bi bi-exclamation-octagon-fill"></i>
            </div>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-clock-history me-1"></i> Receivables
            </span>
          </div>
          <div class="stat-number-display mb-1 text-danger" style="font-size: 2.15rem;">₱<?= number_format($stats['outstanding_balances'], 2) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Outstanding Balances</h2>
          <p class="text-muted small mb-0"><?= number_format($stats['partial_accounts'] + $stats['unpaid_accounts']) ?> accounts with dues</p>
          <div class="stat-card-footer">
            <span>Pending Accounts</span>
            <span class="stat-card-action text-danger">Filter Due <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 3: Payments Today -->
      <div class="col-sm-6 col-xl-3">
        <a href="cashier_payments.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow bg-primary"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-calendar-check-fill"></i>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-sun-fill me-1"></i> Today
            </span>
          </div>
          <div class="stat-number-display mb-1 text-primary" style="font-size: 2.15rem;">₱<?= number_format($stats['payments_today'], 2) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Payments Today</h2>
          <p class="text-muted small mb-0">Daily cashier register intake</p>
          <div class="stat-card-footer">
            <span>Daily Drawer</span>
            <span class="stat-card-action text-primary">View Ledger <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 4: Accounts Settled -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.25s;">
          <div class="stat-card-glow bg-info"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
              <i class="bi bi-people-fill"></i>
            </div>
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-pie-chart-fill me-1"></i> <?= $stats['total_accounts'] > 0 ? round(($stats['paid_accounts'] / $stats['total_accounts']) * 100) : 0 ?>% Cleared
            </span>
          </div>
          <div class="stat-number-display mb-1" style="font-size: 2.15rem;">
            <?= number_format($stats['paid_accounts']) ?><span class="text-muted fs-5 fw-normal"> / <?= number_format($stats['total_accounts']) ?></span>
          </div>
          <h2 class="h6 fw-bold text-dark mb-1">Assessed Accounts</h2>
          <p class="text-muted small mb-0"><?= $stats['paid_accounts'] ?> Paid • <?= $stats['partial_accounts'] ?> Partial</p>
          <div class="stat-card-footer">
            <span>Settlement Rate</span>
            <span class="stat-card-action text-info">View Roster <i class="bi bi-arrow-down"></i></span>
          </div>
        </div>
      </div>

    </div>

    <!-- Student Accounts Roster (Dossier Card Styling) -->
    <div class="dossier-card fade-in-up" id="accountsRoster" style="animation-delay: 0.3s;">
      <div class="dossier-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-wallet2"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Student Financial Accounts</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle" id="visibleBadge">
              <i class="bi bi-person-lines-fill text-primary me-1"></i><span id="accountCount"><?= count($assessments) ?></span> Records
            </span>
          </div>
        </div>

        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2">
          <!-- Status Filter Tabs -->
          <div class="btn-group p-1 bg-light border rounded-pill" role="group" aria-label="Status Filter">
            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold filter-tab-btn active" data-status="all">
              All <span class="badge bg-secondary rounded-pill ms-1"><?= count($assessments) ?></span>
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold filter-tab-btn" data-status="unpaid">
              Unpaid <span class="badge bg-warning text-dark rounded-pill ms-1"><?= $stats['unpaid_accounts'] ?></span>
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold filter-tab-btn" data-status="partial">
              Partial <span class="badge bg-info text-dark rounded-pill ms-1"><?= $stats['partial_accounts'] ?></span>
            </button>
            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold filter-tab-btn" data-status="paid">
              Paid <span class="badge bg-success rounded-pill ms-1"><?= $stats['paid_accounts'] ?></span>
            </button>
          </div>

          <!-- Real-Time Search -->
          <div class="input-group shadow-xs" style="min-width: 240px;">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" id="tableSearch" class="form-control border-start-0 ps-0" placeholder="Search reference, student..." autocomplete="off">
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
                <th scope="col" class="ps-4">Reference No.</th>
                <th scope="col">Student / Applicant</th>
                <th scope="col">Academic Program</th>
                <th scope="col">Net Assessment</th>
                <th scope="col">Total Paid</th>
                <th scope="col">Remaining Balance</th>
                <th scope="col">Payment Status</th>
                <th scope="col" class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody id="accountsTableBody">
              <?php if (empty($assessments)): ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 72px; height: 72px;">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">No Student Assessments Found</h3>
                      <p class="text-muted small mb-0">Assessments will appear here once generated from the Application Review console.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($assessments as $acc): 
                    $netAmount = (float)$acc['net_amount'];
                    $totalPaid = (float)$acc['total_paid'];
                    $balance = max(0, $netAmount - $totalPaid);
                    $pctPaid = ($netAmount > 0) ? min(100, round(($totalPaid / $netAmount) * 100)) : 100;

                    $firstName = trim($acc['first_name'] ?? '');
                    $lastName = trim($acc['last_name'] ?? '');
                    $fullName = trim($lastName . ', ' . $firstName);
                    if ($fullName === ',' || $fullName === '') {
                        $fullName = 'Unknown Applicant';
                    }

                    $firstInitial = mb_substr($firstName, 0, 1);
                    $lastInitial = mb_substr($lastName, 0, 1);
                    $initials = strtoupper($firstInitial . $lastInitial);
                    if ($initials === '') {
                        $initials = 'ST';
                    }

                    $statusRaw = strtolower($acc['payment_status']);
                    $statusBadge = match($statusRaw) {
                        'paid'    => 'bg-success bg-opacity-10 text-success border-success border-opacity-25',
                        'partial' => 'bg-info bg-opacity-10 text-info border-info border-opacity-25',
                        default   => 'bg-warning bg-opacity-10 text-warning border-warning border-opacity-25'
                    };
                    $statusIcon = match($statusRaw) {
                        'paid'    => 'bi-check-circle-fill',
                        'partial' => 'bi-hourglass-split',
                        default   => 'bi-exclamation-circle-fill'
                    };
                ?>
                  <tr class="account-row" 
                      data-status="<?= esc($statusRaw) ?>"
                      data-ref="<?= esc(strtolower($acc['reference_number'] ?? '')) ?>"
                      data-name="<?= esc(strtolower($fullName)) ?>"
                      data-program="<?= esc(strtolower(($acc['strand'] ?? '') . ' ' . ($acc['academic_level'] ?? ''))) ?>"
                      data-email="<?= esc(strtolower($acc['email'] ?? '')) ?>">
                    
                    <!-- Reference -->
                    <td class="ps-4">
                      <span class="applicant-ref-badge">
                        <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($acc['reference_number'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>

                    <!-- Student / Applicant -->
                    <td>
                      <div class="d-flex align-items-center gap-2.5">
                        <div class="applicant-avatar">
                          <?= esc($initials) ?>
                        </div>
                        <div>
                          <span class="fw-bold text-dark d-block"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></span>
                          <div class="small text-muted d-flex align-items-center gap-2">
                            <?php if (!empty($acc['student_number'])): ?>
                              <span><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($acc['student_number'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                              <span><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($acc['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </td>

                    <!-- Academic Program -->
                    <td>
                      <div class="d-flex flex-column gap-1">
                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill fw-semibold small d-inline-flex align-items-center gap-1 w-fit">
                          <i class="bi bi-mortarboard text-primary"></i><?= htmlspecialchars(strtoupper($acc['strand'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="extra-small text-muted">
                          <?= htmlspecialchars($acc['academic_level'] ?? 'College', ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars($acc['grade_level'] ?? '1st Year', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                      </div>
                    </td>

                    <!-- Net Amount -->
                    <td>
                      <span class="fw-semibold text-dark">₱<?= number_format($netAmount, 2) ?></span>
                      <div class="extra-small text-muted">Assessment Due</div>
                    </td>

                    <!-- Total Paid & Mini Progress -->
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-success">₱<?= number_format($totalPaid, 2) ?></span>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill extra-small px-2 py-0.5"><?= $pctPaid ?>%</span>
                      </div>
                      <div class="progress mt-1" style="height: 4px; width: 110px; background-color: #e2e8f0;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pctPaid ?>%;" aria-valuenow="<?= $pctPaid ?>" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                    </td>

                    <!-- Balance -->
                    <td>
                      <?php if ($balance <= 0): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1">
                          <i class="bi bi-check-circle-fill"></i> Settled
                        </span>
                      <?php else: ?>
                        <span class="fw-bold text-danger d-block">₱<?= number_format($balance, 2) ?></span>
                        <span class="extra-small text-danger fw-semibold">Pending Payment</span>
                      <?php endif; ?>
                    </td>

                    <!-- Status -->
                    <td>
                      <span class="badge <?= esc($statusBadge) ?> border rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1.5">
                        <i class="bi <?= esc($statusIcon) ?>"></i>
                        <span><?= ucfirst($acc['payment_status']) ?></span>
                      </span>
                    </td>

                    <!-- Action -->
                    <td class="text-end pe-4">
                      <a href="cashier_assessment.php?id=<?= esc($acc['assessment_id']) ?>" 
                         class="btn btn-sm <?= $balance > 0 ? 'btn-primary shadow-xs' : 'btn-outline-primary' ?> rounded-pill px-3 fw-medium d-inline-flex align-items-center gap-1.5">
                        <span><?= $balance > 0 ? 'Collect' : 'Manage' ?></span>
                        <i class="bi bi-arrow-right small"></i>
                      </a>
                    </td>

                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>

              <!-- No Matches State -->
              <tr id="noResultsRow" style="display: none;">
                <td colspan="8" class="text-center py-5 text-muted">
                  <div class="d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 64px; height: 64px;">
                      <i class="bi bi-search fs-2 text-muted"></i>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-1">No Matching Accounts</h3>
                    <p class="text-muted small mb-2">No student accounts matched your current filter and search query.</p>
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" id="resetFiltersBtn">
                      <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                    </button>
                  </div>
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
(function() {
    function initCashierDashboard() {
        const searchInput = document.getElementById('tableSearch');
        const clearBtn = document.getElementById('clearSearchBtn');
        const filterBtns = document.querySelectorAll('.filter-tab-btn');
        const rows = document.querySelectorAll('#accountsTableBody tr.account-row');
        const noResultsRow = document.getElementById('noResultsRow');
        const accountCountEl = document.getElementById('accountCount');
        const resetBtn = document.getElementById('resetFiltersBtn');
        const filterTriggers = document.querySelectorAll('.filter-trigger');

        let currentStatusFilter = 'all';
        let currentSearchQuery = '';

        function applyFilters() {
            let visibleCount = 0;
            const query = currentSearchQuery.trim().toLowerCase();

            rows.forEach(row => {
                const rowStatus = row.dataset.status || '';
                const rowRef = row.dataset.ref || '';
                const rowName = row.dataset.name || '';
                const rowProgram = row.dataset.program || '';
                const rowEmail = row.dataset.email || '';

                // Status condition
                const matchesStatus = (currentStatusFilter === 'all') || (rowStatus === currentStatusFilter);

                // Search condition
                let matchesSearch = true;
                if (query !== '') {
                    matchesSearch = rowRef.includes(query) ||
                                    rowName.includes(query) ||
                                    rowProgram.includes(query) ||
                                    rowEmail.includes(query);
                }

                if (matchesStatus && matchesSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (accountCountEl) {
                accountCountEl.textContent = visibleCount;
            }

            if (noResultsRow) {
                noResultsRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
            }
        }

        // Search Input
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                currentSearchQuery = this.value;
                if (clearBtn) {
                    clearBtn.classList.toggle('d-none', this.value === '');
                }
                applyFilters();
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (searchInput) {
                    searchInput.value = '';
                    currentSearchQuery = '';
                    clearBtn.classList.add('d-none');
                    searchInput.focus();
                    applyFilters();
                }
            });
        }

        // Filter Tabs
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => {
                    b.classList.remove('active');
                    b.classList.remove('btn-primary');
                });
                this.classList.add('active');
                currentStatusFilter = this.dataset.status || 'all';
                applyFilters();
            });
        });

        // Trigger filters from metric cards
        filterTriggers.forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                const targetFilter = this.dataset.filter;
                if (targetFilter) {
                    filterBtns.forEach(b => {
                        if (b.dataset.status === targetFilter) {
                            b.click();
                        }
                    });
                }
            });
        });

        // Reset button
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                if (searchInput) {
                    searchInput.value = '';
                    currentSearchQuery = '';
                }
                if (clearBtn) clearBtn.classList.add('d-none');
                filterBtns.forEach(b => {
                    if (b.dataset.status === 'all') {
                        b.click();
                    }
                });
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCashierDashboard);
    } else {
        initCashierDashboard();
    }
    document.addEventListener('spa:navigated', initCashierDashboard);
})();
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
