<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('*');

$userId = (int)($_GET['id'] ?? 0);

if ($userId <= 0) {
    header('Location: users.php');
    exit;
}

// Fetch user details
$stmtUser = $pdo->prepare('SELECT first_name, last_name, email, role, department FROM users WHERE id = :id');
$stmtUser->execute(['id' => $userId]);
$user = $stmtUser->fetch();

if (!$user) {
    header('Location: users.php');
    exit;
}

$pageTitle = 'User Activity History - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$logs = [];
$totalLogs = 0;

try {
    // Count total logs for this user
    $countStmt = $pdo->prepare('SELECT COUNT(id) FROM activity_logs WHERE user_id = :user_id');
    $countStmt->execute(['user_id' => $userId]);
    $totalLogs = (int) $countStmt->fetchColumn();

    // Fetch logs paginated
    $stmt = $pdo->prepare('
        SELECT * 
        FROM activity_logs 
        WHERE user_id = :user_id
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ');
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('User activity fetch failed: ' . $e->getMessage());
}

$totalPages = ceil($totalLogs / $limit);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex justify-content-between align-items-end">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Activity History: <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0">
          Email: <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?> | 
          Role: <span class="badge bg-secondary"><?= ucfirst($user['role']) ?></span> | 
          Department: <?= htmlspecialchars($user['department'] ?? 'None', ENT_QUOTES, 'UTF-8') ?>
        </p>
      </div>
      <div>
        <a href="users.php" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Users
        </a>
      </div>
    </div>

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light">
        <i class="bi bi-clock-history text-primary"></i>
        <h2 class="mb-0 text-dark">Action Timeline</h2>
      </div>
      
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Timestamp & IP</th>
                <th scope="col">Action</th>
                <th scope="col" class="w-50">Context / Description</th>
                <th scope="col" class="text-end pe-4">Details</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($logs)): ?>
                <tr>
                  <td colspan="3" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No activity logs found for this user.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($logs as $log): ?>
                  <tr>
                    <td class="ps-4">
                      <span class="d-block fw-medium text-dark"><?= date('M j, Y', strtotime($log['created_at'])) ?></span>
                      <span class="small text-muted"><?= date('h:i:s A', strtotime($log['created_at'])) ?></span>
                      <?php if ($log['ip_address']): ?>
                        <span class="d-block text-secondary mt-1" style="font-size: 0.65rem;"><i class="bi bi-globe me-1"></i><?= htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8') ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 36px; height: 36px;">
                          <i class="bi <?= htmlspecialchars($log['icon'] ?? 'bi-record-circle', ENT_QUOTES, 'UTF-8') ?>"></i>
                        </div>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($log['title'], ENT_QUOTES, 'UTF-8') ?></span>
                      </div>
                    </td>
                    <td>
                      <p class="mb-0 text-muted small lh-sm" style="max-width: 600px;">
                        <?= htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8') ?>
                      </p>
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
                                    <div class="bg-light border rounded-3 p-3 text-break" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.8rem;">
                                      <?php 
                                        $oldArr = json_decode((string)$log['old_value'], true);
                                        if (is_array($oldArr)) {
                                            echo '<pre class="mb-0 text-danger-emphasis">' . htmlspecialchars(json_encode($oldArr, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') . '</pre>';
                                        } else {
                                            echo '<span class="text-muted italic">No prior data.</span>';
                                        }
                                      ?>
                                    </div>
                                  </div>
                                  <div class="col-md-6">
                                    <h6 class="text-muted small fw-bold mb-2">NEW VALUE</h6>
                                    <div class="bg-light border rounded-3 p-3 text-break" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.8rem;">
                                      <?php 
                                        $newArr = json_decode((string)$log['new_value'], true);
                                        if (is_array($newArr)) {
                                            echo '<pre class="mb-0 text-success-emphasis">' . htmlspecialchars(json_encode($newArr, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') . '</pre>';
                                        } else {
                                            echo '<span class="text-muted italic">No new data.</span>';
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
          <span class="text-muted small">Showing page <?= $page ?> of <?= $totalPages ?></span>
          <nav aria-label="Activity Pagination">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?id=<?= $userId ?>&page=<?= $page - 1 ?>">Previous</a>
              </li>
              
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?id=<?= $userId ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              
              <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?id=<?= $userId ?>&page=<?= $page + 1 ?>">Next</a>
              </li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>

    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

