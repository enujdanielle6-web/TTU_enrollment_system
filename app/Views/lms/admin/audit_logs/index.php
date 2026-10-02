<?php
$pageTitle = 'LMS Administrative Audit Logs - TTU';
require_once __DIR__ . '/../layout_header.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-dark bg-opacity-10 text-dark rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-shield-check"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">LMS Governance Audit Logs</h1>
              <span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-journal-text me-1"></i> Total: <?= (int)$totalLogs ?> Audit Entries
              </span>
            </div>
            <p class="text-muted small mb-0">Chronological, tamper-evident audit trail tracking administrative course provisioning, faculty reassignments, status alterations, and timetable synchronization runs.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="<?= BASE_PATH ?>/lms/admin/dashboard" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>LMS Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Filter & Search Toolbar Card -->
    <div class="dossier-card mb-4 p-3.5 bg-white border-0 shadow-sm fade-in-up">
      <form action="<?= BASE_PATH ?>/lms/admin/audit_logs" method="GET" class="row g-2.5 align-items-center">
        <div class="col-md-9 col-lg-10">
          <div class="input-group">
            <span class="input-group-text bg-light border-0 text-muted rounded-start-pill ps-3">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" 
                   name="search" 
                   class="form-control bg-light border-0 shadow-none rounded-end-pill py-2" 
                   placeholder="Search by administrator name, email, action event, description, or target resource..." 
                   value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
        <div class="col-md-3 col-lg-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary rounded-pill px-4 w-100 fw-semibold shadow-xs hover-lift d-inline-flex align-items-center justify-content-center gap-1.5">
            <i class="bi bi-funnel"></i>
            <span>Filter</span>
          </button>
          <?php if (!empty($filters['search'])): ?>
            <a href="<?= BASE_PATH ?>/lms/admin/audit_logs" class="btn btn-light border rounded-pill px-3 fw-medium text-dark shadow-xs hover-lift" title="Clear Search">
              <i class="bi bi-x-lg"></i>
            </a>
          <?php endif; ?>
        </div>
      </form>

      <?php if (!empty($filters['search'])): ?>
        <div class="mt-2.5 pt-2 border-top d-flex align-items-center justify-content-between">
          <div class="small text-muted d-flex align-items-center gap-1.5">
            <span class="fw-medium text-dark">Active Search:</span>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1">
              "<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>"
            </span>
          </div>
          <a href="<?= BASE_PATH ?>/lms/admin/audit_logs" class="small text-decoration-none text-muted hover-underline">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Clear Filter
          </a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Audit Logs Dossier Card -->
    <div class="dossier-card mb-4 overflow-hidden border-0 shadow-sm fade-in-up">
      <div class="dossier-card-header bg-white border-bottom p-3.5 px-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
          <div class="d-flex align-items-center justify-content-center bg-dark bg-opacity-10 text-dark rounded-3" style="width: 34px; height: 34px; font-size: 1.1rem;">
            <i class="bi bi-clock-history"></i>
          </div>
          <div>
            <h2 class="h6 fw-bold text-dark mb-0">System Activity Records</h2>
            <div class="text-muted small" style="font-size: 0.75rem;">Recorded chronological mutations and institutional actions</div>
          </div>
        </div>
        <div class="text-muted small">
          Page <strong class="text-dark"><?= (int)$currentPage ?></strong> of <strong class="text-dark"><?= (int)$totalPages ?></strong>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 dashboard-table">
          <thead>
            <tr>
              <th scope="col" class="ps-4" style="width: 170px;">Timestamp</th>
              <th scope="col" style="width: 220px;">Administrator</th>
              <th scope="col" style="width: 210px;">Action Event</th>
              <th scope="col" style="width: 170px;">Target Resource</th>
              <th scope="col">Description / Justification</th>
              <th scope="col" class="pe-4" style="width: 230px;">State Mutation</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                  <div class="d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="d-flex align-items-center justify-content-center bg-light text-muted rounded-circle mb-3 shadow-xs" style="width: 68px; height: 68px; font-size: 1.8rem;">
                      <i class="bi bi-journal-x opacity-50"></i>
                    </div>
                    <div class="fw-bold text-dark fs-6">No Audit Records Found</div>
                    <p class="small text-muted mb-3" style="max-width: 380px;">No administrative audit entries matched your active search query.</p>
                    <?php if (!empty($filters['search'])): ?>
                      <a href="<?= BASE_PATH ?>/lms/admin/audit_logs" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-x-circle me-1"></i> Clear Search Query
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($logs as $log): ?>
                <?php
                  $adminName = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: 'System / Automated';
                  $initials = 'SYS';
                  if (!empty($log['first_name']) || !empty($log['last_name'])) {
                    $initials = strtoupper(substr($log['first_name'] ?? '', 0, 1) . substr($log['last_name'] ?? '', 0, 1));
                  }
                  
                  // Action badge color
                  $titleLower = strtolower($log['title'] ?? '');
                  $badgeClass = 'bg-primary bg-opacity-10 text-primary border-primary border-opacity-25';
                  if (strpos($titleLower, 'create') !== false || strpos($titleLower, 'provision') !== false || strpos($titleLower, 'generate') !== false || strpos($titleLower, 'clone') !== false) {
                    $badgeClass = 'bg-success bg-opacity-10 text-success border-success border-opacity-25';
                  } elseif (strpos($titleLower, 'reassign') !== false || strpos($titleLower, 'update') !== false || strpos($titleLower, 'modify') !== false) {
                    $badgeClass = 'bg-info bg-opacity-10 text-info border-info border-opacity-25';
                  } elseif (strpos($titleLower, 'archive') !== false || strpos($titleLower, 'delete') !== false || strpos($titleLower, 'deactivate') !== false) {
                    $badgeClass = 'bg-danger bg-opacity-10 text-danger border-danger border-opacity-25';
                  } elseif (strpos($titleLower, 'conflict') !== false || strpos($titleLower, 'reconcil') !== false) {
                    $badgeClass = 'bg-warning bg-opacity-10 text-warning border-warning border-opacity-25';
                  }
                ?>
                <tr>
                  <!-- Timestamp -->
                  <td class="ps-4 text-nowrap">
                    <div class="fw-semibold text-dark small">
                      <?= htmlspecialchars(date('M d, Y', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="text-muted font-monospace" style="font-size: 0.72rem;">
                      <i class="bi bi-clock me-1 opacity-75"></i><?= htmlspecialchars(date('h:i:s A', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  </td>

                  <!-- Administrator -->
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="applicant-avatar text-white fw-bold flex-shrink-0 shadow-xs" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.78rem;">
                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <div class="min-w-0">
                        <div class="fw-bold text-dark small text-truncate" style="max-width: 170px;">
                          <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="text-muted text-truncate" style="font-size: 0.72rem; max-width: 170px;">
                          <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-1.5 py-0 font-monospace text-uppercase" style="font-size: 0.62rem;">
                            <?= htmlspecialchars($log['user_role'] ?? 'system', ENT_QUOTES, 'UTF-8') ?>
                          </span>
                          <?php if (!empty($log['email'])): ?>
                            &bull; <?= htmlspecialchars($log['email'], ENT_QUOTES, 'UTF-8') ?>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Action Event -->
                  <td>
                    <span class="badge <?= $badgeClass ?> border rounded-pill px-2.5 py-1 text-wrap text-start d-inline-flex align-items-center gap-1.5">
                      <i class="bi <?= htmlspecialchars($log['icon'] ?? 'bi-shield-check', ENT_QUOTES, 'UTF-8') ?>"></i>
                      <span class="fw-semibold"><?= htmlspecialchars($log['title'], ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                  </td>

                  <!-- Target Resource -->
                  <td>
                    <span class="applicant-ref-badge font-monospace">
                      <i class="bi bi-box-seam me-1 text-primary"></i><?= htmlspecialchars($log['affected_record'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>

                  <!-- Description / Reason -->
                  <td class="small">
                    <div class="text-dark fw-medium" style="max-width: 360px; line-height: 1.4;">
                      <?= htmlspecialchars($log['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php if (!empty($log['reason'])): ?>
                      <div class="mt-1 d-inline-flex align-items-center gap-1 px-2 py-0.5 rounded-2 bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25" style="font-size: 0.72rem;">
                        <i class="bi bi-chat-quote-fill text-warning"></i>
                        <span class="text-muted fw-normal">Reason:</span>
                        <span class="fw-semibold"><?= htmlspecialchars($log['reason'], ENT_QUOTES, 'UTF-8') ?></span>
                      </div>
                    <?php endif; ?>
                  </td>

                  <!-- State Mutation -->
                  <td class="pe-4 small">
                    <?php 
                      $oldVal = !empty($log['old_value']) ? json_decode($log['old_value'], true) : null;
                      $newVal = !empty($log['new_value']) ? json_decode($log['new_value'], true) : null;
                    ?>
                    <?php if ($oldVal || $newVal): ?>
                      <div class="bg-light p-2 rounded-3 border font-monospace shadow-xs" style="font-size: 0.7rem; max-width: 220px; word-break: break-all; line-height: 1.35;">
                        <?php if ($oldVal): ?>
                          <div class="text-danger mb-0.5">
                            <span class="badge bg-danger bg-opacity-10 text-danger px-1 py-0 rounded" style="font-size: 0.6rem;">OLD</span>
                            <?= htmlspecialchars(json_encode($oldVal, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                        <?php endif; ?>
                        <?php if ($newVal): ?>
                          <div class="text-success">
                            <span class="badge bg-success bg-opacity-10 text-success px-1 py-0 rounded" style="font-size: 0.6rem;">NEW</span>
                            <?= htmlspecialchars(json_encode($newVal, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>
                          </div>
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
        <div class="p-3.5 px-4 border-top bg-light-subtle d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
          <span class="text-muted small">
            Showing Page <strong class="text-dark"><?= (int)$currentPage ?></strong> of <strong class="text-dark"><?= (int)$totalPages ?></strong>
            (<?= (int)$totalLogs ?> total entries)
          </span>
          <nav aria-label="Audit Log Pagination">
            <ul class="pagination pagination-sm mb-0 gap-1">
              <?php if ($currentPage > 1): ?>
                <li class="page-item">
                  <a class="page-link rounded-pill px-3 shadow-xs" href="<?= BASE_PATH ?>/lms/admin/audit_logs?page=<?= (int)($currentPage - 1) ?>&search=<?= urlencode($filters['search'] ?? '') ?>">
                    <i class="bi bi-chevron-left me-1"></i> Prev
                  </a>
                </li>
              <?php endif; ?>

              <?php 
                $startP = max(1, $currentPage - 2);
                $endP = min($totalPages, $currentPage + 2);
              ?>
              <?php for ($p = $startP; $p <= $endP; $p++): ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                  <a class="page-link rounded-2 <?= $p === $currentPage ? 'fw-bold' : '' ?>" href="<?= BASE_PATH ?>/lms/admin/audit_logs?page=<?= $p ?>&search=<?= urlencode($filters['search'] ?? '') ?>">
                    <?= $p ?>
                  </a>
                </li>
              <?php endfor; ?>

              <?php if ($currentPage < $totalPages): ?>
                <li class="page-item">
                  <a class="page-link rounded-pill px-3 shadow-xs" href="<?= BASE_PATH ?>/lms/admin/audit_logs?page=<?= (int)($currentPage + 1) ?>&search=<?= urlencode($filters['search'] ?? '') ?>">
                    Next <i class="bi bi-chevron-right ms-1"></i>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
