<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('applications.view_queue');

$pageTitle = 'Admissions Dashboard - Triple T University';
require_once __DIR__ . '/../../components/header.php';

$stats = [
    'pending_apps' => 0,
    'pending_docs' => 0,
    'pending_medical' => 0,
];

$recent_apps = [];

try {
    // 1. Pending Apps
    $stmtApps = $pdo->query('SELECT COUNT(*) FROM applications WHERE status IN ("pending", "under_review")');
    $stats['pending_apps'] = (int) $stmtApps->fetchColumn();

    // 2. Pending Documents
    $stmtDocs = $pdo->query('SELECT COUNT(*) FROM application_documents WHERE status = "pending"');
    $stats['pending_docs'] = (int) $stmtDocs->fetchColumn();

    // 3. Pending Medical
    if (hasPermission('medical.review')) {
        $medicalStmt = $pdo->query('SELECT COUNT(*) FROM health_records WHERE status IN ("pending", "under_review", "correction_required")');
        $stats['pending_medical'] = (int) $medicalStmt->fetchColumn();
    }

    // 4. Recent Applicants
    $recentAppsStmt = $pdo->query('
        SELECT a.id, a.reference_number, a.status, a.strand, a.created_at, u.first_name, u.last_name 
        FROM applications a 
        INNER JOIN users u ON u.id = a.user_id 
        ORDER BY a.created_at DESC LIMIT 8
    ');
    $recent_apps = $recentAppsStmt->fetchAll();

} catch (PDOException $e) {
    error_log('Admissions dashboard stats failed: ' . $e->getMessage());
}

?>

<?php require_once __DIR__ . '/../components/navbar.php'; ?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    <div class="island island-hero mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">Admissions Dashboard</h1>
          <p class="text-muted mb-0">Overview of pending applications, documents, and medical clearances.</p>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <a href="review.php" class="text-decoration-none">
            <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
              <div class="position-absolute top-0 start-0 w-100 bg-warning" style="height: 4px;"></div>
              <div class="mb-3 d-flex justify-content-center">
                <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle" style="width: 56px; height: 56px;">
                  <i class="bi bi-hourglass-split fs-4"></i>
                </div>
              </div>
              <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['pending_apps'] ?></h2>
              <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Pending Apps</p>
            </div>
        </a>
      </div>
      
      <div class="col-md-4">
        <a href="review.php" class="text-decoration-none">
            <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
              <div class="position-absolute top-0 start-0 w-100 bg-info" style="height: 4px;"></div>
              <div class="mb-3 d-flex justify-content-center">
                <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-circle" style="width: 56px; height: 56px;">
                  <i class="bi bi-file-earmark-text fs-4"></i>
                </div>
              </div>
              <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['pending_docs'] ?></h2>
              <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Pending Documents</p>
            </div>
        </a>
      </div>
      
      <?php if (hasPermission('medical.review')): ?>
      <div class="col-md-4">
        <a href="medical_clearance.php" class="text-decoration-none">
            <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
              <div class="position-absolute top-0 start-0 w-100 bg-danger" style="height: 4px;"></div>
              <div class="mb-3 d-flex justify-content-center">
                <div class="d-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle" style="width: 56px; height: 56px;">
                  <i class="bi bi-heart-pulse fs-4"></i>
                </div>
              </div>
              <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['pending_medical'] ?></h2>
              <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Medical Queue</p>
            </div>
        </a>
      </div>
      <?php endif; ?>
    </div>

    <!-- Recent Applicants -->
    <div class="island h-100">
      <div class="island-header border-bottom pb-2">
        <i class="bi bi-list-task text-primary"></i>
        <h2>Recent Applicants</h2>
      </div>
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light text-muted small text-uppercase">
              <tr>
                <th class="ps-4">Reference</th>
                <th>Name</th>
                <th>Program</th>
                <th>Date Submitted</th>
                <th>Status</th>
                <th class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody class="border-top-0">
              <?php if (empty($recent_apps)): ?>
              <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                  No recent applications found.
                </td>
              </tr>
              <?php else: ?>
                <?php foreach ($recent_apps as $app): ?>
                <tr>
                  <td class="ps-4 fw-medium text-dark"><?= htmlspecialchars($app['reference_number'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars(strtoupper($app['strand'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><span class="text-muted small"><?= date('M j, Y g:i A', strtotime($app['created_at'])) ?></span></td>
                  <td>
                    <?php
                      $statusBadge = match($app['status']) {
                          'approved', 'verified', 'enrolled' => 'bg-success',
                          'rejected', 'failed' => 'bg-danger',
                          'pending', 'correction_required' => 'bg-warning text-dark',
                          'under_review' => 'bg-info text-dark',
                          default => 'bg-secondary'
                      };
                    ?>
                    <span class="badge <?= $statusBadge ?> px-2 py-1 rounded-pill small">
                      <?= htmlspecialchars(ucwords(str_replace('_', ' ', $app['status'])), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td class="text-end pe-4">
                    <a href="application_detail.php?id=<?= $app['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">View</a>
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

