<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('scholarship_applications.review');

$pageTitle = 'Scholarship Applications - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';

// Fetch scholarship applications
$applications = [];
try {
    $stmt = $pdo->query('
        SELECT sa.*, 
               u.first_name, u.last_name, u.email,
               s.name as scholarship_name, s.discount_type, s.discount_value
        FROM scholarship_applications sa
        INNER JOIN users u ON sa.user_id = u.id
        INNER JOIN scholarships s ON sa.scholarship_id = s.id
        ORDER BY 
            CASE sa.status 
                WHEN "pending" THEN 1 
                WHEN "under_review" THEN 2 
                ELSE 3 
            END ASC,
            sa.created_at DESC
    ');
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Scholarship apps fetch failed: ' . $e->getMessage());
}

$stats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'total' => 0
];
foreach ($applications as $app) {
    $stats['total']++;
    if ($app['status'] === 'pending' || $app['status'] === 'under_review') {
        $stats['pending']++;
    } elseif ($app['status'] === 'approved') {
        $stats['approved']++;
    } elseif ($app['status'] === 'rejected') {
        $stats['rejected']++;
    }
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4">
      <h1 class="h3 fw-bold text-dark mb-1">Scholarship Applications</h1>
      <p class="text-muted mb-0">Review and process student applications for financial aid.</p>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- Scholarship Statistics Row -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4 mb-4">
      <div class="col">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-warning" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-hourglass-split fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['pending'] ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Pending Apps</p>
        </div>
      </div>
      
      <div class="col">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-success" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-award fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['approved'] ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Approved</p>
        </div>
      </div>
      
      <div class="col">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-danger" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-x-circle fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['rejected'] ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Rejected</p>
        </div>
      </div>

      <div class="col">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-inbox fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['total'] ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Total Apps</p>
        </div>
      </div>
    </div>

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light">
        <i class="bi bi-inbox-fill text-primary"></i>
        <h2 class="mb-0 text-dark">Applications Queue</h2>
      </div>
      
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Applicant Name</th>
                <th scope="col">Email Address</th>
                <th scope="col">Scholarship Type</th>
                <th scope="col">Status</th>
                <th scope="col">Date Applied</th>
                <th scope="col" class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($applications)): ?>
                <tr>
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-award fs-1 d-block mb-3 text-secondary"></i>
                    No scholarship applications found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($applications as $app): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark">
                      <?= htmlspecialchars($app['last_name'] . ', ' . $app['first_name'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                      <?= htmlspecialchars($app['email'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                      <?= htmlspecialchars($app['scholarship_name'], ENT_QUOTES, 'UTF-8') ?>
                      <span class="d-block small text-muted">
                        <?php if ($app['discount_type'] === 'percentage'): ?>
                          <?= number_format((float)$app['discount_value'], 0) ?>% Discount
                        <?php else: ?>
                          ₱<?= number_format((float)$app['discount_value'], 2) ?>
                        <?php endif; ?>
                      </span>
                    </td>
                    <td>
                      <?php 
                        $badgeClass = match($app['status']) {
                            'approved' => 'bg-success',
                            'rejected' => 'bg-danger',
                            'under_review' => 'bg-info',
                            default => 'bg-warning text-dark'
                        };
                        $statusLabel = match($app['status']) {
                            'under_review' => 'Under Review',
                            default => ucfirst($app['status'])
                        };
                      ?>
                      <span class="badge <?= $badgeClass ?> rounded-pill px-3"><?= $statusLabel ?></span>
                    </td>
                    <td>
                      <?= date('M d, Y', strtotime($app['created_at'])) ?>
                    </td>
                    <td class="text-end pe-4">
                      <a href="scholarship_detail.php?id=<?= $app['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium">
                        Review <i class="bi bi-arrow-right ms-1"></i>
                      </a>
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
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

