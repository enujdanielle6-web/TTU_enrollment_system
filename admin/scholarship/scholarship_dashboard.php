<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requirePermission('scholarships.manage');

$pageTitle = 'Scholarship Dashboard - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';

// Fetch Statistics
$stats = [
    'active_scholarships' => 0,
    'total_applications' => 0,
    'pending_review' => 0,
    'approved_applications' => 0
];

try {
    $stmt1 = $pdo->query('SELECT COUNT(*) FROM scholarships WHERE is_active = 1');
    $stats['active_scholarships'] = (int)$stmt1->fetchColumn();

    $stmt2 = $pdo->query('SELECT COUNT(*) FROM scholarship_applications');
    $stats['total_applications'] = (int)$stmt2->fetchColumn();

    $stmt3 = $pdo->query('SELECT COUNT(*) FROM scholarship_applications WHERE status IN ("pending", "under_review")');
    $stats['pending_review'] = (int)$stmt3->fetchColumn();

    $stmt4 = $pdo->query('SELECT COUNT(*) FROM scholarship_applications WHERE status = "approved"');
    $stats['approved_applications'] = (int)$stmt4->fetchColumn();

} catch (PDOException $e) {
    error_log('Scholarship dashboard fetch failed: ' . $e->getMessage());
}

?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Scholarship Dashboard</h1>
        <p class="text-muted mb-0">Overview of scholarship programs and applicant status.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="scholarships.php" class="btn btn-outline-primary fw-medium shadow-sm rounded-pill px-4">
          <i class="bi bi-gear-fill me-1"></i> Manage Types
        </a>
        <a href="scholarship_review.php" class="btn btn-primary fw-medium shadow-sm rounded-pill px-4">
          <i class="bi bi-inbox-fill me-1"></i> Review Applications
        </a>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-4">
      <div class="col-sm-6 col-lg-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-award fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= number_format($stats['active_scholarships']) ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Active Programs</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-info" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-file-earmark-text fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= number_format($stats['total_applications']) ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Total Applications</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-warning" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-hourglass-split fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= number_format($stats['pending_review']) ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Pending Review</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-success" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-check-circle-fill fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= number_format($stats['approved_applications']) ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Approved</p>
        </div>
      </div>
    </div>

    <!-- Quick Action / Info Panel -->
    <div class="island p-4 text-center">
      <i class="bi bi-lightbulb text-warning fs-1 mb-2 d-block"></i>
      <h5 class="fw-bold">Ready to process applications?</h5>
      <p class="text-muted">Navigate to the <strong>Review Applications</strong> page to approve or reject student scholarship requests. Approved scholarships will automatically deduct from the student's fee assessment.</p>
      <a href="scholarship_review.php" class="btn btn-primary mt-2 px-4 rounded-pill">Go to Review Queue</a>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

