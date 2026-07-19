<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('*');

$pageTitle = 'Audit Logs - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Search filtering
$searchQuery = trim($_GET['search'] ?? '');

$logs = [];
$totalLogs = 0;

try {
    if ($searchQuery !== '') {
        // Count with search
        $countStmt = $pdo->prepare('
            SELECT COUNT(al.id) FROM activity_logs al
            JOIN users u ON al.user_id = u.id
            WHERE u.email LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR al.title LIKE :search
        ');
        $countStmt->execute(['search' => '%' . $searchQuery . '%']);
        $totalLogs = (int) $countStmt->fetchColumn();

        // Fetch logs with search
        $stmt = $pdo->prepare('
            SELECT al.*, u.first_name, u.last_name, u.email, u.role, u.department 
            FROM activity_logs al
            JOIN users u ON al.user_id = u.id
            WHERE u.email LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR al.title LIKE :search OR al.affected_record LIKE :search
            ORDER BY al.created_at DESC 
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':search', '%' . $searchQuery . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();
    } else {
        // Count all
        $totalLogs = (int) $pdo->query('SELECT COUNT(id) FROM activity_logs')->fetchColumn();

        // Fetch all paginated
        $stmt = $pdo->prepare('
            SELECT al.*, u.first_name, u.last_name, u.email, u.role, u.department 
            FROM activity_logs al
            JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC 
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log('Audit logs fetch failed: ' . $e->getMessage());
}

$totalPages = ceil($totalLogs / $limit);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex justify-content-between align-items-end">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">System Audit Logs</h1>
        <p class="text-muted mb-0">Monitor applicant activities, form submissions, and administrative changes.</p>
      </div>
      <div>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
      </div>
    </div>

    <div class="island position-relative overflow-hidden border-0 shadow-sm" style="border-radius: 16px;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center">
          <i class="bi bi-shield-lock-fill text-primary"></i>
          <h2 class="mb-0 text-dark">Activity Record</h2>
        </div>
        
        <!-- Search Form -->
        <form action="audit_logs.php" method="GET" class="d-flex gap-2">
          <div class="input-group input-group-sm" style="max-width: 300px;">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by name, email, or action..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn btn-primary px-3" type="submit">Filter</button>
          </div>
          <?php if ($searchQuery !== ''): ?>
            <a href="audit_logs.php" class="btn btn-sm btn-outline-secondary">Clear</a>
          <?php endif; ?>
        </form>
      </div>
      
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
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
                  <td colspan="4" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No audit logs found matching your criteria.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($logs as $log): ?>
                  <tr>
                    <td class="ps-4 text-nowrap">
                      <span class="d-block fw-semibold text-dark small"><?= date('M j, Y', strtotime($log['created_at'])) ?></span>
                      <span class="text-muted" style="font-size: 0.75rem;"><?= date('g:i:s A', strtotime($log['created_at'])) ?></span>
                      <?php if ($log['ip_address']): ?>
                        <span class="d-block text-secondary mt-1" style="font-size: 0.65rem;"><i class="bi bi-globe me-1"></i><?= htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8') ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; font-size: 0.85rem;">
                          <?= strtoupper(substr($log['first_name'], 0, 1) . substr($log['last_name'], 0, 1)) ?>
                        </div>
                        <div>
                          <p class="mb-0 fw-semibold text-dark small"><?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name'], ENT_QUOTES, 'UTF-8') ?></p>
                          <p class="mb-0 text-muted" style="font-size: 0.75rem;">
                            <?= htmlspecialchars($log['email'], ENT_QUOTES, 'UTF-8') ?>
                            <?php
                              $logBadge = match($log['role']) {
                                  'superadmin' => 'bg-danger',
                                  'admissions' => 'bg-primary',
                                  'scholarship' => 'bg-success',
                                  'cashier' => 'bg-info text-dark',
                                  default => 'bg-secondary'
                              };
                            ?>
                            <span class="badge <?= $logBadge ?> ms-1" style="font-size: 0.6rem;"><?= strtoupper($log['role']) ?></span>
                            <?php if (!empty($log['department'])): ?>
                              <span class="badge bg-light text-dark border border-secondary-subtle ms-1" style="font-size: 0.6rem;"><i class="bi bi-building me-1"></i><?= htmlspecialchars(ucfirst($log['department']), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= htmlspecialchars($log['icon'], ENT_QUOTES, 'UTF-8') ?> text-info"></i>
                        <span class="fw-medium text-dark small"><?= htmlspecialchars($log['title'], ENT_QUOTES, 'UTF-8') ?></span>
                      </div>
                    </td>
                    <td>
                      <span class="text-muted small"><?= htmlspecialchars($log['description'] ?? 'No additional context provided.', ENT_QUOTES, 'UTF-8') ?></span>
                      <?php if ($log['affected_record']): ?>
                        <div class="mt-1">
                          <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle" style="font-size: 0.65rem;">
                            <i class="bi bi-link-45deg me-1"></i><?= htmlspecialchars($log['affected_record'], ENT_QUOTES, 'UTF-8') ?>
                          </span>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <?php if ($log['old_value'] || $log['new_value']): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#logModal<?= $log['id'] ?>">
                          View
                        </button>

                        <!-- Log Details Modal -->
                        <div class="modal fade" id="logModal<?= $log['id'] ?>" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content text-start">
                              <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-file-earmark-diff text-primary me-2"></i>Change Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body pt-3 pb-4">
                                <div class="row g-3">
                                  <div class="col-md-6">
                                    <h6 class="text-muted small fw-bold mb-2">PREVIOUS VALUE</h6>
                                    <div class="bg-light border rounded-3 p-3 text-break" style="max-height: 300px; overflow-y: auto; font-size: 0.85rem;">
                                      <?php 
                                        $oldArr = json_decode((string)$log['old_value'], true);
                                        if (is_array($oldArr) && !empty($oldArr)) {
                                            echo '<ul class="list-unstyled mb-0">';
                                            foreach ($oldArr as $k => $v) {
                                                $val = is_scalar($v) ? htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') : htmlspecialchars(json_encode($v), ENT_QUOTES, 'UTF-8');
                                                echo '<li class="mb-1"><span class="text-muted fw-bold">' . htmlspecialchars(ucwords(str_replace('_', ' ', $k))) . ':</span> <span class="text-danger-emphasis">' . $val . '</span></li>';
                                            }
                                            echo '</ul>';
                                        } else {
                                            echo '<span class="text-muted fst-italic">No prior data.</span>';
                                        }
                                      ?>
                                    </div>
                                  </div>
                                  <div class="col-md-6">
                                    <h6 class="text-muted small fw-bold mb-2">NEW VALUE</h6>
                                    <div class="bg-light border rounded-3 p-3 text-break" style="max-height: 300px; overflow-y: auto; font-size: 0.85rem;">
                                      <?php 
                                        $newArr = json_decode((string)$log['new_value'], true);
                                        if (is_array($newArr) && !empty($newArr)) {
                                            echo '<ul class="list-unstyled mb-0">';
                                            foreach ($newArr as $k => $v) {
                                                $val = is_scalar($v) ? htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') : htmlspecialchars(json_encode($v), ENT_QUOTES, 'UTF-8');
                                                echo '<li class="mb-1"><span class="text-muted fw-bold">' . htmlspecialchars(ucwords(str_replace('_', ' ', $k))) . ':</span> <span class="text-success-emphasis fw-medium">' . $val . '</span></li>';
                                            }
                                            echo '</ul>';
                                        } else {
                                            echo '<span class="text-muted fst-italic">No new data.</span>';
                                        }
                                      ?>
                                    </div>
                                  </div>
                                </div>
                                <?php if ($log['reason']): ?>
                                  <div class="mt-4 p-3 bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-3">
                                    <p class="mb-1 fw-bold small"><i class="bi bi-chat-quote-fill me-1"></i>Reason for Change:</p>
                                    <p class="mb-0 small"><?= htmlspecialchars($log['reason'], ENT_QUOTES, 'UTF-8') ?></p>
                                  </div>
                                <?php endif; ?>
                              </div>
                            </div>
                          </div>
                        </div>
                      <?php else: ?>
                        <span class="text-muted small italic">N/A</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Pagination Controls -->
      <?php if ($totalPages > 1): ?>
        <div class="island-body border-top border-light py-3 d-flex justify-content-between align-items-center">
          <span class="text-muted small">Showing page <?= $page ?> of <?= $totalPages ?> (<?= $totalLogs ?> total entries)</span>
          <nav aria-label="Audit Log Pagination">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchQuery) ?>" tabindex="-1" aria-disabled="true">Previous</a>
              </li>
              
              <?php
              $startPage = max(1, $page - 2);
              $endPage = min($totalPages, $page + 2);
              
              if ($startPage > 1) {
                  echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
              }
              
              for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchQuery) ?>"><?= $i ?></a>
                </li>
              <?php endfor; 
              
              if ($endPage < $totalPages) {
                  echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
              }
              ?>
              
              <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchQuery) ?>">Next</a>
              </li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>

    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

