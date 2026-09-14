<?php
$pageTitle = 'User Activity History - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';

$userInitials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? 'A', 0, 1));
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip (Design System Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 fw-bold shadow-sm" style="width: 54px; height: 54px; font-size: 1.3rem; flex-shrink: 0;">
            <?= htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8') ?>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Activity History: <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8') ?></h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                UID #<?= (int)$user['id'] ?>
              </span>
              <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-person-badge me-1"></i> <?= ucfirst(htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8')) ?>
              </span>
              <?php if (!empty($user['department'])): ?>
                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                  <i class="bi bi-building me-1"></i> <?= htmlspecialchars($user['department'], ENT_QUOTES, 'UTF-8') ?>
                </span>
              <?php endif; ?>
              <span class="badge <?= ($user['status'] ?? 'active') === 'active' ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' ?> rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> <?= ucfirst(htmlspecialchars($user['status'] ?? 'active', ENT_QUOTES, 'UTF-8')) ?>
              </span>
            </div>
            <p class="text-muted small mb-0">
              <i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>
              <span class="mx-2 text-secondary">•</span>
              <i class="bi bi-calendar3 me-1"></i> Account Created <?= date('M j, Y', strtotime($user['created_at'])) ?>
            </p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="users.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>Back to Users</span>
          </a>
          <a href="audit_logs.php" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-medium d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-shield-shaded text-secondary"></i>
            <span>Global Audit Logs</span>
          </a>
        </div>
      </div>
    </div>

    <!-- User Activity KPI Metrics Row -->
    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
            <i class="bi bi-journal-check"></i>
          </div>
          <div>
            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Total Actions</div>
            <div class="h4 fw-bold text-dark mb-0"><?= number_format((int)($stats['total_actions'] ?? 0)) ?></div>
            <div class="text-muted small" style="font-size: 0.75rem;">Lifetime Logged Events</div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
            <i class="bi bi-lightning-charge-fill"></i>
          </div>
          <div>
            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Actions Today</div>
            <div class="h4 fw-bold text-success mb-0"><?= number_format((int)($stats['today_actions'] ?? 0)) ?></div>
            <div class="text-muted small" style="font-size: 0.75rem;">Recorded Since Midnight</div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
            <i class="bi bi-clock-history"></i>
          </div>
          <div>
            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Last Active</div>
            <div class="h6 fw-bold text-dark mb-0">
              <?= !empty($stats['last_active']) ? date('M j, h:i A', strtotime($stats['last_active'])) : 'Never' ?>
            </div>
            <div class="text-muted small" style="font-size: 0.75rem;">
              <?= !empty($stats['last_active']) ? date('Y', strtotime($stats['last_active'])) : 'No recorded activity' ?>
            </div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
            <i class="bi bi-shield-check"></i>
          </div>
          <div>
            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Account Security</div>
            <div class="h6 fw-bold text-dark mb-0"><?= ucfirst(htmlspecialchars($user['status'] ?? 'active', ENT_QUOTES, 'UTF-8')) ?></div>
            <div class="text-muted small" style="font-size: 0.75rem;">Verified Credentials</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Activity Log Dossier Card -->
    <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden mb-4">
      <div class="p-3.5 px-4 border-bottom d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2.5">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 36px; height: 36px; font-size: 1.15rem;">
            <i class="bi bi-clock-history"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0">Action Timeline</h2>
            <div class="text-muted small">Chronological audit ledger of administrative actions executed by this user</div>
          </div>
        </div>
        <div>
          <div class="input-group input-group-sm" style="max-width: 280px;">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="userActivitySearchInput" class="form-control bg-light border-start-0" placeholder="Filter this page...">
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table dashboard-table align-middle mb-0" id="userActivityTable">
          <thead class="bg-light">
            <tr>
              <th scope="col" class="ps-4" style="width: 180px;">Timestamp &amp; IP</th>
              <th scope="col" style="width: 220px;">Action</th>
              <th scope="col">Context / Description</th>
              <th scope="col" class="text-end pe-4" style="width: 120px;">Details</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr>
                <td colspan="4" class="text-center py-5 text-muted">
                  <div class="d-flex flex-column align-items-center justify-content-center">
                    <div class="d-flex align-items-center justify-content-center bg-light rounded-circle mb-3" style="width: 64px; height: 64px;">
                      <i class="bi bi-inbox fs-2 text-secondary"></i>
                    </div>
                    <div class="fw-semibold text-dark mb-1">No Activity Logs Found</div>
                    <div class="small text-muted">No recorded administrative actions found for this account.</div>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($logs as $log): ?>
                <tr class="activity-log-row align-middle">
                  <td class="ps-4">
                    <span class="d-block fw-semibold text-dark small"><?= date('M j, Y', strtotime($log['created_at'])) ?></span>
                    <span class="small text-muted" style="font-size: 0.75rem;"><?= date('h:i:s A', strtotime($log['created_at'])) ?></span>
                    <?php if (!empty($log['ip_address'])): ?>
                      <span class="d-block text-secondary mt-0.5" style="font-size: 0.7rem;"><i class="bi bi-globe me-1"></i><?= htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2.5">
                      <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 32px; height: 32px; font-size: 0.95rem; flex-shrink: 0;">
                        <i class="bi <?= htmlspecialchars($log['icon'] ?? 'bi-record-circle', ENT_QUOTES, 'UTF-8') ?>"></i>
                      </div>
                      <span class="fw-bold text-dark small"><?= htmlspecialchars($log['title'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                  </td>
                  <td>
                    <p class="mb-0 text-dark small lh-sm" style="max-width: 600px;">
                      <?= htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <?php if (!empty($log['affected_record'])): ?>
                      <div class="mt-1">
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">
                          <i class="bi bi-link-45deg me-1"></i><?= htmlspecialchars($log['affected_record'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td class="text-end pe-4">
                    <?php if (!empty($log['old_value']) || !empty($log['new_value'])): ?>
                      <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-3 py-1 small fw-medium" data-bs-toggle="modal" data-bs-target="#logModal<?= (int)$log['id'] ?>">
                        <i class="bi bi-file-earmark-diff me-1"></i> View Diff
                      </button>

                      <!-- Log Details Modal -->
                      <div class="modal fade" id="logModal<?= (int)$log['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                          <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden text-start">
                            <div class="modal-header border-bottom py-3 px-4">
                              <h5 class="modal-title fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-diff text-primary"></i>
                                <span>Change Details: <?= htmlspecialchars($log['title'], ENT_QUOTES, 'UTF-8') ?></span>
                              </h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                              <div class="row g-3">
                                <div class="col-md-6">
                                  <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Previous Value</div>
                                  <div class="bg-light border rounded-3 p-3 text-break" style="max-height: 280px; overflow-y: auto; font-family: monospace; font-size: 0.78rem;">
                                    <?php 
                                      $oldArr = json_decode((string)$log['old_value'], true);
                                      if (is_array($oldArr)) {
                                          echo '<pre class="mb-0 text-danger-emphasis">' . htmlspecialchars(json_encode($oldArr, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') . '</pre>';
                                      } else {
                                          echo '<span class="text-muted fst-italic">No prior data.</span>';
                                      }
                                    ?>
                                  </div>
                                </div>
                                <div class="col-md-6">
                                  <div class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">New Value</div>
                                  <div class="bg-light border rounded-3 p-3 text-break" style="max-height: 280px; overflow-y: auto; font-family: monospace; font-size: 0.78rem;">
                                    <?php 
                                      $newArr = json_decode((string)$log['new_value'], true);
                                      if (is_array($newArr)) {
                                          echo '<pre class="mb-0 text-success-emphasis">' . htmlspecialchars(json_encode($newArr, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') . '</pre>';
                                      } else {
                                          echo '<span class="text-muted fst-italic">No new data.</span>';
                                      }
                                    ?>
                                  </div>
                                </div>
                              </div>
                              <?php if (!empty($log['reason'])): ?>
                                <div class="mt-3 p-3 bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-3">
                                  <p class="mb-1 fw-bold small text-warning-emphasis"><i class="bi bi-chat-quote-fill me-1"></i>Reason for Change:</p>
                                  <p class="mb-0 small text-dark"><?= htmlspecialchars($log['reason'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                              <?php endif; ?>
                            </div>
                            <div class="modal-footer border-top bg-light py-2 px-4">
                              <button type="button" class="btn btn-light border rounded-pill px-4 fw-medium small" data-bs-dismiss="modal">Close</button>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php else: ?>
                      <span class="text-muted small fst-italic">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <?php if ($totalPages > 1): ?>
        <div class="p-3 px-4 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
          <span class="text-muted small">Showing page <?= (int)$page ?> of <?= (int)$totalPages ?> (<?= number_format((int)$totalLogs) ?> total events)</span>
          <nav aria-label="User Activity Pagination">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link rounded-start-pill px-3" href="?id=<?= (int)$userId ?>&amp;page=<?= (int)($page - 1) ?>">Previous</a>
              </li>
              
              <?php 
                $startP = max(1, $page - 2);
                $endP   = min($totalPages, $page + 2);
                for ($i = $startP; $i <= $endP; $i++): 
              ?>
                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                  <a class="page-link px-3" href="?id=<?= (int)$userId ?>&amp;page=<?= (int)$i ?>"><?= (int)$i ?></a>
                </li>
              <?php endfor; ?>
              
              <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link rounded-end-pill px-3" href="?id=<?= (int)$userId ?>&amp;page=<?= (int)($page + 1) ?>">Next</a>
              </li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>

    </div>
  </div>
</main>

<script>
  (function() {
    'use strict';
    
    // Client-side quick filter
    const searchInput = document.getElementById('userActivitySearchInput');
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#userActivityTable tbody tr.activity-log-row');
        rows.forEach(function(row) {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(query) ? '' : 'none';
        });
      });
    }
  })();
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
