<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('enrollment.finalize');

$pageTitle = 'College Enrollment Queue - Registrar';

$search = trim($_GET['search'] ?? '');
$sortOrder = trim($_GET['sort'] ?? 'newest');

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClauses = ["a.status = 'approved'", "sa.payment_status IN ('partial', 'paid')", "a.academic_level = 'College'"];
$params = [];

if ($search !== '') {
    $whereClauses[] = '(a.reference_number LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);

$orderBy = 'a.created_at DESC';
if ($sortOrder === 'oldest') {
    $orderBy = 'a.created_at ASC';
}

$applications = [];
$totalApps = 0;

try {
    // Count total matching
    $countStmt = $pdo->prepare("
        SELECT COUNT(a.id) 
        FROM applications a 
        INNER JOIN users u ON u.id = a.user_id
        INNER JOIN student_assessments sa ON sa.application_id = a.id
        $whereSQL
    ");
    $countStmt->execute($params);
    $totalApps = (int) $countStmt->fetchColumn();

    // Fetch paginated
    $stmt = $pdo->prepare("
        SELECT 
            a.id, 
            a.reference_number, 
            a.status, 
            a.academic_level,
            a.strand, 
            a.grade_level,
            a.created_at, 
            u.first_name, 
            u.last_name,
            sa.payment_status,
            sa.total_paid
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        INNER JOIN student_assessments sa ON sa.application_id = a.id
        $whereSQL
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $applications = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Enrollment queue list query failed: ' . $e->getMessage());
}

$totalPages = ceil($totalApps / $limit);

$successMsg = $_SESSION['admin_success'] ?? '';
$errorMsg = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">College Enrollment Queue</h1>
        <p class="text-muted mb-0">Review approved applications with verified payments and finalize their official enrollment.</p>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- Filters Panel -->
    <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4" style="border-radius: 16px;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-body py-3">
        <form action="college_enrollment_queue.php" method="GET" class="row g-3 align-items-center">
          <div class="col-md-auto fw-semibold text-muted small text-uppercase">
            <i class="bi bi-funnel-fill me-1"></i> Filter By:
          </div>
          <div class="col-md-4">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
              <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, ref..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>
          <div class="col-md-2">
            <select name="sort" class="form-select form-select-sm bg-light">
              <option value="newest" <?= $sortOrder === 'newest' ? 'selected' : '' ?>>Newest First</option>
              <option value="oldest" <?= $sortOrder === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
            </select>
          </div>
          <div class="col-md-auto">
            <button type="submit" class="btn btn-sm btn-primary px-3 rounded-pill">Filter</button>
            <?php if ($search !== '' || $sortOrder !== 'newest'): ?>
              <a href="college_enrollment_queue.php" class="btn btn-sm btn-outline-secondary rounded-pill">Clear</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light text-uppercase small text-muted">
              <tr>
                <th scope="col" class="ps-4 py-3 fw-semibold">Reference No.</th>
                <th scope="col" class="py-3 fw-semibold">Applicant Name</th>
                <th scope="col" class="py-3 fw-semibold">Level / Program</th>
                <th scope="col" class="py-3 fw-semibold">Payment Status</th>
                <th scope="col" class="py-3 fw-semibold">Total Paid</th>
                <th scope="col" class="py-3 fw-semibold">Action Required</th>
                <th scope="col" class="pe-4 py-3 text-end fw-semibold">Action</th>
              </tr>
            </thead>
            <tbody class="border-top-0">
              <?php if (empty($applications)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No applications are currently awaiting enrollment finalization.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($applications as $app): ?>
                  <tr>
                    <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($app['reference_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <div class="fw-semibold text-dark"><?= htmlspecialchars($app['last_name'] . ', ' . $app['first_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </td>
                    <td>
                      <span class="text-muted small"><?= htmlspecialchars($app['academic_level'], ENT_QUOTES, 'UTF-8'); ?></span><br>
                      <span class="text-muted small"><?= htmlspecialchars(strtoupper($app['strand'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    </td>
                    <td>
                      <?php if ($app['payment_status'] === 'paid'): ?>
                        <span class="badge bg-success rounded-pill px-2 py-1 small">Fully Paid</span>
                      <?php else: ?>
                        <span class="badge bg-info text-dark rounded-pill px-2 py-1 small">Partially Paid</span>
                      <?php endif; ?>
                    </td>
                    <td><span class="fw-medium text-dark">₱<?= number_format((float)$app['total_paid'], 2) ?></span></td>
                    <td>
                      <span class="badge bg-warning text-dark rounded-pill px-2 py-1 small">Needs Finalization</span>
                    </td>
                    <td class="pe-4 text-end">
                      <a href="../admissions/application_detail.php?id=<?= $app['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                        Finalize <i class="bi bi-arrow-right-short"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination Footer -->
        <?php if ($totalPages > 1): ?>
        <div class="border-top border-light py-3 px-4 d-flex justify-content-end align-items-center">
            <nav aria-label="Review Pagination">
              <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                  <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sortOrder) ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sortOrder) ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                  <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sortOrder) ?>">Next</a>
                </li>
              </ul>
            </nav>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

