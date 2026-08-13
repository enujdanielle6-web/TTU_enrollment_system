<?php
require_once __DIR__ . '/../../components/header.php';

$stats = [
    'shs_sections' => 0,
    'college_sections' => 0,
    'total_sections' => 0
];

try {
    // Active SHS Sections
    $stmtShsSections = $pdo->query('SELECT COUNT(*) FROM shs_sections WHERE status = 1');
    $stats['shs_sections'] = (int) $stmtShsSections->fetchColumn();

    // Active College Sections
    $stmtColSections = $pdo->query('SELECT COUNT(*) FROM college_sections WHERE status = 1');
    $stats['college_sections'] = (int) $stmtColSections->fetchColumn();

    $stats['total_sections'] = $stats['shs_sections'] + $stats['college_sections'];

} catch (PDOException $e) {
    error_log('Scheduler dashboard stats failed: ' . $e->getMessage());
}

require_once __DIR__ . '/../../components/admin_navbar.php'; 
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    <div class="island island-hero mb-4 fade-in-up" style="animation-delay: 0.1s;">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">Scheduler Dashboard</h1>
          <p class="text-muted mb-0">Manage SHS and College sections and schedules.</p>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <a href="shs_sections.php" class="text-decoration-none">
            <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4 fade-in-up" style="animation-delay: 0.2s;">
              <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
              <div class="mb-3 d-flex justify-content-center">
                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 56px; height: 56px;">
                  <i class="bi bi-diagram-3-fill fs-4"></i>
                </div>
              </div>
              <h2 class="display-6 fw-bold text-dark mb-1"><?= number_format($stats['shs_sections']) ?></h2>
              <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">SHS Sections</p>
            </div>
        </a>
      </div>
      
      <div class="col-md-4">
        <a href="college_sections.php" class="text-decoration-none">
            <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4 fade-in-up" style="animation-delay: 0.3s;">
              <div class="position-absolute top-0 start-0 w-100 bg-success" style="height: 4px;"></div>
              <div class="mb-3 d-flex justify-content-center">
                <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 56px; height: 56px;">
                  <i class="bi bi-diagram-3-fill fs-4"></i>
                </div>
              </div>
              <h2 class="display-6 fw-bold text-dark mb-1"><?= number_format($stats['college_sections']) ?></h2>
              <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">College Sections</p>
            </div>
        </a>
      </div>

      <div class="col-md-4">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4 fade-in-up" style="animation-delay: 0.4s;">
          <div class="position-absolute top-0 start-0 w-100 bg-info" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-calendar-week fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= number_format($stats['total_sections']) ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Total Active Sections</p>
        </div>
      </div>
    </div>
  </div>
</main>
<?php require_once __DIR__ . '/../../components/footer.php'; ?>
