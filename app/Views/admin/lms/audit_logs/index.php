<?php
$pageTitle = 'LMS Administrative Audit Logs - TTU';
require_once __DIR__ . '/../../../components/header.php';
require_once __DIR__ . '/../../../components/admin_navbar.php';
require_once __DIR__ . '/../components/lms_header.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-dark bg-opacity-10 text-dark rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-shield-check"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">LMS Governance Audit Logs</h1>
              <span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                Total: <?= (int)$totalLogs ?> Audit Entries
              </span>
            </div>
            <p class="text-muted small mb-0">Comprehensive chronological audit trail of administrative course provisioning, faculty reassignments, status alterations, and synchronization runs.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="/sia/admin/lms/dashboard" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>LMS Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
      <form action="/sia/admin/lms/audit_logs" method="GET" class="row g-2 align-items-center">
        <div class="col-md-9">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control bg-light border-start-0 shadow-none" placeholder="Search by administrator name, action title, description, or resource..." value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary rounded-pill px-4 w-100">Filter</button>
          <a href="/sia/admin/lms/audit_logs" class="btn btn-light border rounded-pill px-3">Reset</a>
        </div>
      </form>
    </div>

    <!-- Audit Logs Table Card -->
    <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden mb-4">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light text-muted small text-uppercase">
            <tr>
              <th class="ps-4">Timestamp</th>
              <th>Administrator</th>
              <th>Action</th>
              <th>Affected Record</th>
              <th>Description / Reason</th>
              <th class="pe-4">Changes</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="bi bi-shield-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                  No LMS administrative audit entries found.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td class="ps-4 text-nowrap small text-muted">
                    <?= htmlspecialchars(date('M d, Y h:i A', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                  </td>
                  <td>
                    <div class="fw-bold text-dark">
                      <?= htmlspecialchars(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: 'System / CLI', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="text-muted small" style="font-size: 0.75rem;">
                      <?= htmlspecialchars($log['user_role'] ?? 'system', ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($log['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1">
                      <i class="bi <?= htmlspecialchars($log['icon'] ?? 'bi-info-circle', ENT_QUOTES, 'UTF-8') ?> me-1"></i>
                      <?= htmlspecialchars($log['title'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td>
                    <code class="small text-secondary"><?= htmlspecialchars($log['affected_record'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></code>
                  </td>
                  <td class="small text-muted" style="max-width: 320px;">
                    <div><?= htmlspecialchars($log['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if (!empty($log['reason'])): ?>
                      <div class="fst-italic text-secondary mt-0.5" style="font-size: 0.75rem;">Reason: <?= htmlspecialchars($log['reason'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="pe-4 small">
                    <?php 
                      $oldVal = !empty($log['old_value']) ? json_decode($log['old_value'], true) : null;
                      $newVal = !empty($log['new_value']) ? json_decode($log['new_value'], true) : null;
                    ?>
                    <?php if ($oldVal || $newVal): ?>
                      <div class="bg-light p-1.5 rounded-3 border font-monospace" style="font-size: 0.7rem; max-width: 200px; word-break: break-all;">
                        <?php if ($oldVal): ?>
                          <span class="text-danger">- <?= htmlspecialchars(json_encode($oldVal), ENT_QUOTES, 'UTF-8') ?></span><br>
                        <?php endif; ?>
                        <?php if ($newVal): ?>
                          <span class="text-success">+ <?= htmlspecialchars(json_encode($newVal), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <span class="text-muted fst-italic">—</span>
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
        <div class="p-3 border-top d-flex justify-content-between align-items-center">
          <span class="text-muted small">Showing Page <?= $currentPage ?> of <?= $totalPages ?></span>
          <ul class="pagination pagination-sm mb-0">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="/sia/admin/lms/audit_logs?page=<?= $p ?>&search=<?= urlencode($filters['search'] ?? '') ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../../../components/footer.php'; ?>
