<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('medical.review');

$pageTitle = 'Clinic Dashboard - Triple T University';
require_once __DIR__ . '/../../components/header.php';

$stats = [
    'pending_medical' => 0,
    'total_verified' => 0
];

$recent_records = [];

try {
    // 1. Pending Medical Reviews
    $medicalStmt = $pdo->query('SELECT COUNT(*) FROM health_records WHERE status IN ("pending", "under_review", "correction_required")');
    $stats['pending_medical'] = (int) $medicalStmt->fetchColumn();

    // 2. Verified Records
    $verifiedStmt = $pdo->query('SELECT COUNT(*) FROM health_records WHERE status = "verified"');
    $stats['total_verified'] = (int) $verifiedStmt->fetchColumn();

    // 3. Recent Health Records
    $recentStmt = $pdo->query('
        SELECT h.id, h.status, h.created_at, a.reference_number, u.first_name, u.last_name
        FROM health_records h
        INNER JOIN applications a ON h.application_id = a.id
        INNER JOIN users u ON h.user_id = u.id
        ORDER BY h.created_at DESC LIMIT 8
    ');
    $recent_records = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Clinic dashboard stats failed: ' . $e->getMessage());
}

?>

<?php require_once __DIR__ . '/../components/navbar.php'; ?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    <div class="island island-hero mb-4 fade-in-up" style="animation-delay: 0.1s;">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">University Clinic Dashboard</h1>
          <p class="text-muted mb-0">Overview of pending and recent student medical clearances.</p>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <a href="medical_clearance.php" class="text-decoration-none">
            <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4 fade-in-up" style="animation-delay: 0.2s;">
              <div class="position-absolute top-0 start-0 w-100 bg-danger" style="height: 4px;"></div>
              <div class="mb-3 d-flex justify-content-center">
                <div class="d-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle" style="width: 56px; height: 56px;">
                  <i class="bi bi-heart-pulse fs-4"></i>
                </div>
              </div>
              <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['pending_medical'] ?></h2>
              <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Pending Clearances</p>
            </div>
        </a>
      </div>
      
      <div class="col-md-6">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4 fade-in-up" style="animation-delay: 0.3s;">
          <div class="position-absolute top-0 start-0 w-100 bg-success" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-shield-check fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['total_verified'] ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Total Verified</p>
        </div>
      </div>
    </div>

    <!-- Recent Records -->
    <div class="island h-100 fade-in-up" style="animation-delay: 0.5s;">
      <div class="island-header border-bottom pb-2 fade-in-up" style="animation-delay: 0.6s;">
        <i class="bi bi-list-task text-primary"></i>
        <h2>Recent Submissions</h2>
      </div>
      <div class="island-body p-0 fade-in-up" style="animation-delay: 0.7s;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light text-muted small text-uppercase">
              <tr>
                <th class="ps-4">Reference</th>
                <th>Name</th>
                <th>Date Submitted</th>
                <th>Status</th>
                <th class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody class="border-top-0">
              <?php if (empty($recent_records)): ?>
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                  No recent health records found.
                </td>
              </tr>
              <?php else: ?>
                <?php foreach ($recent_records as $rec): ?>
                <tr>
                  <td class="ps-4 fw-medium text-dark"><?= htmlspecialchars($rec['reference_number'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><span class="text-muted small"><?= date('M j, Y g:i A', strtotime($rec['created_at'])) ?></span></td>
                  <td>
                    <?php
                      $statusBadge = match($rec['status']) {
                          'pending', 'under_review' => '<span class="badge bg-warning text-dark rounded-pill fw-semibold"><i class="bi bi-hourglass-split me-1"></i> Under Review</span>',
                          'verified' => '<span class="badge bg-success rounded-pill fw-semibold"><i class="bi bi-check-circle me-1"></i> Verified</span>',
                          'correction_required' => '<span class="badge bg-info text-dark rounded-pill fw-semibold"><i class="bi bi-arrow-return-left me-1"></i> Needs Correction</span>',
                          'rejected' => '<span class="badge bg-danger rounded-pill fw-semibold"><i class="bi bi-x-circle me-1"></i> Rejected</span>',
                          default => '<span class="badge bg-secondary rounded-pill">' . ucfirst($rec['status']) . '</span>'
                      };
                      echo $statusBadge;
                    ?>
                  </td>
                  <td class="text-end pe-4">
                    <a href="medical_detail.php?id=<?= $rec['id'] ?>" class="btn btn-sm btn-light border-0 shadow-sm rounded-pill px-3 fw-medium text-primary hover-lift">
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

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
