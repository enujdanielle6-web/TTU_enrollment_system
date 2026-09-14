<?php
$pageTitle = 'System Audit Logs - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip (Design System Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-shield-check"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Security & Audit Logs</h1>
              <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-shaded me-1"></i> Audit Trail
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-calendar-check text-primary me-1"></i> AY <?= esc($systemSettings['active_school_year'] ?? '2026–2027') ?>
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-journal-text text-primary me-1"></i> <?= number_format($totalLogs) ?> Events
              </span>
            </div>
            <p class="text-muted small mb-0">Immutable chronological ledger of authentication attempts, administrative operations, enrollment changes, and critical security overrides.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="sysadmin_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>Dashboard</span>
          </a>
          <a href="users.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-people text-primary"></i>
            <span>User Directory</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Executive KPI Metric Cards (Consistent 3-Column Grid) -->
    <div class="row g-4 mb-4">
      
      <!-- Card 1: Total Audit Events -->
      <div class="col-md-4">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.1s;">
          <div class="stat-card-glow bg-warning"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-journal-text"></i>
            </div>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-database me-1"></i> Log Volume
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['total_events']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Total Audit Events</h2>
          <p class="text-muted small mb-0">Immutable records in persistent storage</p>
          <div class="stat-card-footer">
            <span>Storage Scope</span>
            <span class="stat-card-action text-warning">Full Ledger <i class="bi bi-check2"></i></span>
          </div>
        </div>
      </div>

      <!-- Card 2: Events Today -->
      <div class="col-md-4">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.15s;">
          <div class="stat-card-glow bg-primary"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-calendar-check"></i>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-sun-fill me-1"></i> Daily Activity
            </span>
          </div>
          <div class="stat-number-display mb-1 text-primary"><?= number_format($stats['events_today']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Events Logged Today</h2>
          <p class="text-muted small mb-0">Recorded on <?= date('M d, Y') ?></p>
          <div class="stat-card-footer">
            <span>Daily Intake</span>
            <span class="stat-card-action text-primary">Active Sessions <i class="bi bi-activity"></i></span>
          </div>
        </div>
      </div>

      <!-- Card 3: Unique Users Tracked -->
      <div class="col-md-4">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow bg-info"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
              <i class="bi bi-people-fill"></i>
            </div>
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-fingerprint me-1"></i> Actors
            </span>
          </div>
          <div class="stat-number-display mb-1 text-info"><?= number_format($stats['unique_users']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Active User Accounts</h2>
          <p class="text-muted small mb-0">Unique actors in audit history</p>
          <div class="stat-card-footer">
            <span>Identity Trace</span>
            <span class="stat-card-action text-info">Audited Users <i class="bi bi-arrow-right"></i></span>
          </div>
        </div>
      </div>

    </div>

    <!-- Activity Record (Dossier Card Styling) -->
    <div class="dossier-card fade-in-up" id="auditCard" style="animation-delay: 0.25s;">
      <div class="dossier-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-warning bg-opacity-10 text-warning">
            <i class="bi bi-shield-lock-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Audit Activity Records</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <i class="bi bi-clock-history text-primary me-1"></i><?= number_format($totalLogs) ?> Entries
            </span>
          </div>
        </div>
        
        <!-- Search Form -->
        <form action="audit_logs.php" method="GET" class="d-flex gap-2 align-items-center">
          <div class="input-group input-group-sm shadow-xs" style="min-width: 260px;">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search actor, action, record..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <?php if ($searchQuery !== ''): ?>
            <a href="audit_logs.php" class="btn btn-sm btn-light border rounded-pill px-3">Clear</a>
          <?php endif; ?>
        </form>

      </div>
      
      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table custom-table">
            <thead>
              <tr>
                <th scope="col" class="ps-4">Timestamp & IP</th>
                <th scope="col">User Details</th>
                <th scope="col">Action Title</th>
                <th scope="col">Context / Description</th>
                <th scope="col" class="text-end pe-4">Details</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($logs)): ?>
                <tr>
                  <td colspan="5" class="text-center py-5 text-muted">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 64px; height: 64px;">
                        <i class="bi bi-inbox fs-2 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">No Audit Logs Found</h3>
                      <p class="text-muted small mb-0">No records match your search filter criteria.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($logs as $log): 
                    $fName = trim($log['first_name'] ?? '');
                    $lName = trim($log['last_name'] ?? '');
                    $fullName = trim($fName . ' ' . $lName);
                    if ($fullName === '') $fullName = 'System Process';

                    $initials = strtoupper(mb_substr($fName, 0, 1) . mb_substr($lName, 0, 1));
                    if ($initials === '') $initials = 'SY';

                    $logBadge = match($log['role']) {
                        'superadmin'  => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                        'admin'       => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                        'admissions'  => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                        'scholarship' => 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25',
                        'cashier'     => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                        default       => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25'
                    };
                ?>
                  <tr>
                    <!-- Timestamp & IP -->
                    <td class="ps-4 text-nowrap">
                      <div class="d-flex align-items-center gap-1.5 text-dark fw-medium small">
                        <i class="bi bi-clock-history text-muted extra-small"></i>
                        <span><?= date('M j, Y', strtotime($log['created_at'])) ?></span>
                      </div>
                      <div class="extra-small text-muted"><?= date('g:i:s A', strtotime($log['created_at'])) ?></div>
                      <?php if (!empty($log['ip_address'])): ?>
                        <div class="extra-small text-secondary font-monospace mt-1">
                          <i class="bi bi-globe me-1"></i><?= htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                      <?php endif; ?>
                    </td>

                    <!-- Actor / User Details -->
                    <td>
                      <div class="d-flex align-items-center gap-2.5">
                        <div class="applicant-avatar" style="width: 36px; height: 36px; font-size: 0.8rem;">
                          <?= esc($initials) ?>
                        </div>
                        <div>
                          <span class="d-block fw-bold text-dark small"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></span>
                          <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <span class="extra-small text-muted"><?= htmlspecialchars($log['email'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge <?= esc($logBadge) ?> rounded-pill extra-small px-2 py-0.5"><?= strtoupper($log['role']) ?></span>
                            <?php if (!empty($log['department'])): ?>
                              <span class="badge bg-light text-secondary border rounded-pill extra-small px-2 py-0.5"><?= htmlspecialchars(ucfirst($log['department']), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </td>

                    <!-- Action Title -->
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 28px; height: 28px; font-size: 0.85rem;">
                          <i class="bi <?= htmlspecialchars($log['icon'] ?? 'bi-activity', ENT_QUOTES, 'UTF-8') ?>"></i>
                        </div>
                        <div>
                          <span class="fw-bold text-dark small d-block"><?= htmlspecialchars($log['title'], ENT_QUOTES, 'UTF-8') ?></span>
                          <?php if (!empty($log['affected_record'])): ?>
                            <span class="applicant-ref-badge" style="font-size: 0.72rem; padding: 0.15rem 0.45rem;">
                              <?= htmlspecialchars($log['affected_record'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </td>

                    <!-- Context / Description -->
                    <td>
                      <p class="mb-0 text-dark small" style="max-width: 380px;"><?= htmlspecialchars($log['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    </td>

                    <!-- Details Action -->
                    <td class="text-end pe-4">
                      <?php if (!empty($log['old_value']) || !empty($log['new_value'])): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-xs d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#logModal<?= esc($log['id']) ?>">
                          <i class="bi bi-file-earmark-diff"></i>
                          <span>Diff</span>
                        </button>
                      <?php else: ?>
                        <span class="text-muted extra-small fst-italic">Standard Event</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Pagination Controls in Card Footer -->
      <?php if ($totalPages > 1): ?>
        <div class="dossier-card-footer border-top border-light py-3 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
          <span class="text-muted small">Showing page <strong><?= esc($page) ?></strong> of <strong><?= esc($totalPages) ?></strong> (<?= number_format($totalLogs) ?> events)</span>
          <nav aria-label="Audit Pagination">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item <?= esc(($page <= 1) ? 'disabled' : '') ?>">
                <a class="page-link rounded-start-pill" href="?page=<?= esc($page - 1) ?>&search=<?= esc(urlencode($searchQuery)) ?>">
                  <i class="bi bi-chevron-left me-1"></i> Prev
                </a>
              </li>
              
              <?php
              $startPage = max(1, $page - 2);
              $endPage = min($totalPages, $page + 2);
              if ($startPage > 1) {
                  echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
              }
              for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?= esc(($i === $page) ? 'active' : '') ?>">
                  <a class="page-link" href="?page=<?= esc($i) ?>&search=<?= esc(urlencode($searchQuery)) ?>"><?= esc($i) ?></a>
                </li>
              <?php endfor; 
              if ($endPage < $totalPages) {
                  echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
              }
              ?>
              
              <li class="page-item <?= esc(($page >= $totalPages) ? 'disabled' : '') ?>">
                <a class="page-link rounded-end-pill" href="?page=<?= esc($page + 1) ?>&search=<?= esc(urlencode($searchQuery)) ?>">
                  Next <i class="bi bi-chevron-right ms-1"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>

    </div>

  </div>
</main>

<!-- Modals for Log Change Diffs -->
<?php foreach ($logs as $log): ?>
  <?php if (!empty($log['old_value']) || !empty($log['new_value'])): ?>
    <div class="modal fade" id="logModal<?= esc($log['id']) ?>" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden text-start">
          <div class="modal-header bg-white border-bottom py-3">
            <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
              <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle me-3" style="width: 36px; height: 36px;">
                <i class="bi bi-file-earmark-diff fs-5"></i>
              </div>
              Audit Change Comparison
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4 bg-light">
            <div class="row g-3">
              <div class="col-md-6">
                <h6 class="text-muted small fw-bold mb-2 text-uppercase" style="letter-spacing: 0.5px;">Previous State</h6>
                <div class="bg-white border rounded-3 p-3 text-break shadow-xs" style="max-height: 300px; overflow-y: auto; font-size: 0.85rem;">
                  <?php 
                    $oldArr = json_decode((string)$log['old_value'], true);
                    if (is_array($oldArr) && !empty($oldArr)) {
                        echo '<ul class="list-unstyled mb-0">';
                        foreach ($oldArr as $k => $v) {
                            $val = is_scalar($v) ? htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') : htmlspecialchars(json_encode($v), ENT_QUOTES, 'UTF-8');
                            echo '<li class="mb-1.5"><span class="text-muted fw-bold">' . htmlspecialchars(ucwords(str_replace('_', ' ', $k))) . ':</span> <span class="text-danger-emphasis fw-medium">' . $val . '</span></li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<span class="text-muted fst-italic">No prior state recorded.</span>';
                    }
                  ?>
                </div>
              </div>
              <div class="col-md-6">
                <h6 class="text-muted small fw-bold mb-2 text-uppercase" style="letter-spacing: 0.5px;">Updated State</h6>
                <div class="bg-white border rounded-3 p-3 text-break shadow-xs" style="max-height: 300px; overflow-y: auto; font-size: 0.85rem;">
                  <?php 
                    $newArr = json_decode((string)$log['new_value'], true);
                    if (is_array($newArr) && !empty($newArr)) {
                        echo '<ul class="list-unstyled mb-0">';
                        foreach ($newArr as $k => $v) {
                            $val = is_scalar($v) ? htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') : htmlspecialchars(json_encode($v), ENT_QUOTES, 'UTF-8');
                            echo '<li class="mb-1.5"><span class="text-muted fw-bold">' . htmlspecialchars(ucwords(str_replace('_', ' ', $k))) . ':</span> <span class="text-success fw-medium">' . $val . '</span></li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<span class="text-muted fst-italic">No updated state recorded.</span>';
                    }
                  ?>
                </div>
              </div>
            </div>
            <?php if (!empty($log['reason'])): ?>
              <div class="mt-4 p-3 bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-3">
                <p class="mb-1 fw-bold small text-warning"><i class="bi bi-chat-quote-fill me-1"></i>Reason Provided:</p>
                <p class="mb-0 small"><?= htmlspecialchars($log['reason'], ENT_QUOTES, 'UTF-8') ?></p>
              </div>
            <?php endif; ?>
          </div>
          <div class="modal-footer border-top-0 pt-0 bg-light">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
