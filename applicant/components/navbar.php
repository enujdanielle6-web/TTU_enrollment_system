<?php
$userName = $_SESSION['user_name'] ?? 'Applicant';
$userId = (int)($_SESSION['user_id'] ?? 0);
$currentPage = basename($_SERVER['PHP_SELF']);

// Ensure application status is known for Quick Actions in sidebar
$navAppStatus = null;
if (isset($application) && is_array($application) && isset($application['status'])) {
    $navAppStatus = $application['status'];
} elseif ($userId > 0) {
    try {
        global $pdo;
        if ($pdo) {
            $navStmt = $pdo->prepare('SELECT status FROM applications WHERE user_id = :user_id LIMIT 1');
            $navStmt->execute(['user_id' => $userId]);
            $navAppStatus = $navStmt->fetchColumn();
        }
    } catch (PDOException $e) {
        $navAppStatus = null;
    }
}
$hasApplication = ($navAppStatus !== false && $navAppStatus !== null);
$isEditable = $hasApplication && in_array($navAppStatus, ['pending', 'correction_required'], true);
$isApprovedOrEnrolled = $hasApplication && in_array($navAppStatus, ['approved', 'enrolled'], true);
?>

<style>
@media (min-width: 992px) {
  body {
    padding-left: 280px;
  }
  .app-sidebar {
    transform: none !important;
    visibility: visible !important;
    width: 280px !important;
  }
}
.app-sidebar {
    width: 280px;
    z-index: 1045;
}
.hover-bg-light { transition: background-color 0.2s; }
.hover-bg-light:hover { background-color: #f8f9fa; }
</style>

<!-- Mobile Top Nav -->
<nav class="navbar bg-white border-bottom sticky-top d-lg-none px-3">
  <a class="navbar-brand d-flex align-items-center fw-bold" href="dashboard.php">
    <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
      <i class="bi bi-mortarboard-fill"></i>
    </div>
    Applicant Portal
  </a>
  <button class="btn btn-light border" type="button" data-bs-toggle="offcanvas" data-bs-target="#applicantSidebar">
    <i class="bi bi-list"></i>
  </button>
</nav>

<!-- Sidebar -->
<div class="offcanvas-lg offcanvas-start app-sidebar border-end bg-white fixed-top h-100 shadow-sm" tabindex="-1" id="applicantSidebar">
  <div class="offcanvas-header border-bottom px-4 py-3">
    <a class="navbar-brand d-flex align-items-center fw-bold text-dark text-decoration-none" href="dashboard.php">
      <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
        <i class="bi bi-mortarboard-fill"></i>
      </div>
      TTU Admissions
    </a>
    <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#applicantSidebar"></button>
  </div>
  
  <div class="offcanvas-body d-flex flex-column p-4 overflow-y-auto">
    <!-- User info -->
    <div class="d-flex align-items-center mb-4 pb-4 border-bottom">
        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
            <i class="bi bi-person text-secondary fs-4"></i>
        </div>
        <div>
            <div class="small text-muted fw-semibold text-uppercase" style="letter-spacing: 0.05em;">Applicant</div>
            <div class="fw-bold text-dark text-truncate" style="max-width: 150px;"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>
    
    <!-- Navigation Links -->
    <div class="small text-muted fw-bold text-uppercase mb-2" style="letter-spacing: 0.05em;">Main Menu</div>
    <ul class="nav flex-column gap-2 mb-4">
      <li class="nav-item">
        <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center <?= $currentPage === 'dashboard.php' ? 'bg-primary text-white fw-medium shadow-sm' : 'text-dark hover-bg-light' ?>" href="dashboard.php">
          <i class="bi bi-grid-1x2 me-3 <?= $currentPage === 'dashboard.php' ? '' : 'text-muted' ?>"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center <?= $currentPage === 'scholarships.php' ? 'bg-primary text-white fw-medium shadow-sm' : 'text-dark hover-bg-light' ?>" href="scholarships.php">
          <i class="bi bi-award me-3 <?= $currentPage === 'scholarships.php' ? '' : 'text-muted' ?>"></i> Scholarships
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center <?= $currentPage === 'assessment.php' ? 'bg-primary text-white fw-medium shadow-sm' : 'text-dark hover-bg-light' ?>" href="assessment.php">
          <i class="bi bi-receipt me-3 <?= $currentPage === 'assessment.php' ? '' : 'text-muted' ?>"></i> Assessment
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center <?= $currentPage === 'profile.php' ? 'bg-primary text-white fw-medium shadow-sm' : 'text-dark hover-bg-light' ?>" href="profile.php">
          <i class="bi bi-person-gear me-3 <?= $currentPage === 'profile.php' ? '' : 'text-muted' ?>"></i> Profile
        </a>
      </li>
    </ul>

    <!-- Quick Actions -->
    <div class="small text-muted fw-bold text-uppercase mb-2" style="letter-spacing: 0.05em;">Quick Actions</div>
    <ul class="nav flex-column gap-2 mb-auto">
        <?php if ($hasApplication): ?>
            <li class="nav-item">
                <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center text-dark hover-bg-light" href="status.php">
                    <i class="bi bi-compass me-3 text-muted"></i> Track Status
                </a>
            </li>
        <?php else: ?>
            <?php
            $navGlobalStatus = 'open';
            if (isset($pdo) && function_exists('getSystemSetting')) {
                $navGlobalStatus = getSystemSetting($pdo, 'enrollment_status', 'open');
            }
            ?>
            <?php if ($navGlobalStatus === 'open'): ?>
            <li class="nav-item">
                <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center bg-primary bg-opacity-10 text-primary fw-medium" href="enroll.php">
                    <i class="bi bi-pencil-square me-3"></i> Start Enrollment
                </a>
            </li>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($hasApplication): ?>
            <li class="nav-item">
                <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center text-dark hover-bg-light" href="documents.php">
                    <i class="bi bi-upload me-3 text-muted"></i> Manage Documents
                </a>
            </li>
        <?php endif; ?>

        <?php if ($isEditable): ?>
            <li class="nav-item">
                <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center text-dark hover-bg-light" href="enroll.php">
                    <i class="bi bi-pencil me-3 text-muted"></i> Edit Application
                </a>
            </li>
        <?php endif; ?>

        <?php if ($isApprovedOrEnrolled): ?>
            <li class="nav-item">
                <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center text-dark hover-bg-light" href="print_slip.php" target="_blank">
                    <i class="bi bi-printer me-3 text-muted"></i> Admission Slip
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <!-- Logout -->
    <div class="mt-4 pt-4 border-top">
      <a class="btn btn-light border w-100 d-flex align-items-center justify-content-center text-danger fw-medium" href="../auth/logout.php">
        <i class="bi bi-box-arrow-right me-2"></i> Logout
      </a>
    </div>
  </div>
</div>
