<?php
$userName = $_SESSION['user_name'] ?? 'Applicant';
$userId = (int)($_SESSION['user_id'] ?? 0);

// Determine active page reliably from REQUEST_URI (supporting SPA & MPA)
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$currentPage = basename($requestPath);
if (empty($currentPage) || $currentPage === 'applicant') {
    $currentPage = 'dashboard.php';
}

// Compute initials for profile avatar circle
$firstInitial = substr($_SESSION['user_first_name'] ?? '', 0, 1);
$lastInitial = substr($_SESSION['user_last_name'] ?? '', 0, 1);
if (!$firstInitial && !$lastInitial) {
    $nameParts = preg_split('/\s+/', trim((string)$userName));
    $firstInitial = substr($nameParts[0] ?? 'A', 0, 1);
    $lastInitial = isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : '';
}
$userInitials = strtoupper($firstInitial . $lastInitial);
if (!$userInitials) {
    $userInitials = 'A';
}

// Ensure application status is known for Quick Actions in sidebar
$navAppStatus = null;
if (isset($application) && is_array($application) && isset($application['status'])) {
    $navAppStatus = $application['status'];
} elseif ($userId > 0) {
    try {
        $navPdo = \App\Core\Database::getConnection();
        if ($navPdo) {
            $navStmt = $navPdo->prepare('SELECT status FROM applications WHERE user_id = :user_id LIMIT 1');
            $navStmt->execute(['user_id' => $userId]);
            $navAppStatus = $navStmt->fetchColumn();
        }
    } catch (PDOException $e) {
        $navAppStatus = null;
    }
}
$hasApplication = ($navAppStatus !== false && $navAppStatus !== null);
$isEditable = $hasApplication && in_array($navAppStatus, ['pending', 'correction_required'], true);
$isApprovedOrEnrolled = $hasApplication && in_array($navAppStatus, ['approved', 'payment_verified', 'enrolled'], true);
?>

<style>
@media (min-width: 992px) {
  body {
    padding-left: 280px;
  }
  .app-sidebar {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    bottom: 0 !important;
    height: 100vh !important;
    width: 280px !important;
    transform: none !important;
    visibility: visible !important;
    z-index: 1040 !important;
  }
}

.app-sidebar {
  width: 280px;
  z-index: 1045;
  background-color: #ffffff;
  border-right: 1px solid #dee2e6;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
}

.app-sidebar #applicantSidebarNav {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 0, 0, 0.15) transparent;
}

.app-sidebar #applicantSidebarNav::-webkit-scrollbar {
  width: 5px;
}

.app-sidebar #applicantSidebarNav::-webkit-scrollbar-track {
  background: transparent;
}

.app-sidebar #applicantSidebarNav::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.15);
  border-radius: 4px;
}

.app-sidebar #applicantSidebarNav::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.25);
}

.app-sidebar .nav-link {
  color: #495057;
  font-weight: 500;
  padding: 0.72rem 1rem;
  border-radius: 8px;
  margin-bottom: 4px;
  transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
  display: flex;
  align-items: center;
  text-decoration: none;
  position: relative;
  overflow: hidden;
}

.app-sidebar .nav-link i {
  color: #6c757d;
  font-size: 1.15rem;
  transition: color 0.2s ease-in-out;
  width: 24px;
  text-align: center;
  flex-shrink: 0;
}

.app-sidebar .nav-link:hover {
  background-color: #f8f9fa;
  color: #0d6efd;
  transform: translateX(4px);
}

.app-sidebar .nav-link:hover i {
  color: #0d6efd;
}

.app-sidebar .nav-link.active {
  background: linear-gradient(90deg, rgba(13, 110, 253, 0.08) 0%, transparent 100%) !important;
  border-left: 3px solid #0d6efd !important;
  border-radius: 0 8px 8px 0 !important;
  color: #0d6efd !important;
  font-weight: 600 !important;
}

.app-sidebar .nav-link.active i {
  color: #0d6efd !important;
}

.app-sidebar .btn-outline-danger {
  border-color: #ea868f;
  color: #dc3545;
  transition: all 0.2s ease-in-out;
}

.app-sidebar .btn-outline-danger:hover {
  background-color: #dc3545;
  border-color: #dc3545;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(220, 53, 69, 0.25);
  transform: translateY(-1px);
}
</style>

<!-- Mobile Topbar -->
<nav class="navbar bg-white border-bottom sticky-top d-lg-none px-3 shadow-sm">
  <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark text-decoration-none" href="/sia/applicant/dashboard.php">
    <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 32px; width: auto; object-fit: contain;">
    Applicant Portal
  </a>
  <button class="btn btn-light border-0 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#applicantSidebar" aria-label="Toggle Navigation">
    <i class="bi bi-list fs-5"></i>
  </button>
</nav>

<!-- Sidebar -->
<aside class="offcanvas-lg offcanvas-start app-sidebar border-end bg-white fixed-top h-100 shadow-sm" tabindex="-1" id="applicantSidebar">
  
  <!-- Brand / Logo Area (Matches Admin Portal) -->
  <div class="p-4 border-bottom d-flex align-items-center justify-content-between flex-shrink-0">
    <a class="text-decoration-none d-flex align-items-center" href="/sia/applicant/dashboard.php" aria-label="Applicant portal home">
      <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 36px; width: auto; object-fit: contain;">
      <span class="text-dark fw-bold ms-3 fs-5 nav-text">Applicant Portal</span>
    </a>
    <button type="button" class="btn btn-sm btn-light d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#applicantSidebar" aria-label="Close">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <!-- Navigation Links -->
  <nav class="flex-grow-1 p-3 overflow-y-auto" id="applicantSidebarNav">
    
    <!-- Main Menu -->
    <div class="small text-muted fw-bold text-uppercase px-3 mb-2" style="letter-spacing: 0.05em; font-size: 0.72rem;">Main Menu</div>
    <div class="nav flex-column mb-3">
      <a class="nav-link d-flex align-items-center gap-3 <?= esc($currentPage === 'dashboard.php' ? 'active' : '') ?>" href="/sia/applicant/dashboard.php">
        <i class="bi bi-grid-1x2 fs-5"></i>
        <span class="nav-text">Dashboard</span>
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= esc($currentPage === 'scholarships.php' ? 'active' : '') ?>" href="/sia/applicant/scholarships.php">
        <i class="bi bi-award fs-5"></i>
        <span class="nav-text">Scholarships</span>
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= esc($currentPage === 'assessment.php' ? 'active' : '') ?>" href="/sia/applicant/assessment.php">
        <i class="bi bi-receipt fs-5"></i>
        <span class="nav-text">Assessment</span>
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= esc($currentPage === 'profile.php' ? 'active' : '') ?>" href="/sia/applicant/profile.php">
        <i class="bi bi-person-gear fs-5"></i>
        <span class="nav-text">Profile</span>
      </a>
    </div>

    <!-- Quick Actions -->
    <div class="small text-muted fw-bold text-uppercase px-3 mb-2 mt-2" style="letter-spacing: 0.05em; font-size: 0.72rem;">Quick Actions</div>
    <div class="nav flex-column mb-auto">
      <?php if ($hasApplication): ?>
        <a class="nav-link d-flex align-items-center gap-3 <?= esc($currentPage === 'status.php' ? 'active' : '') ?>" href="/sia/applicant/status.php">
          <i class="bi bi-compass fs-5"></i>
          <span class="nav-text">Track Status</span>
        </a>
        <a class="nav-link d-flex align-items-center gap-3 <?= esc($currentPage === 'documents.php' ? 'active' : '') ?>" href="/sia/applicant/documents.php">
          <i class="bi bi-upload fs-5"></i>
          <span class="nav-text">Manage Documents</span>
        </a>
      <?php else: ?>
        <?php
        $navGlobalStatus = 'open';
        if (isset($pdo) && function_exists('getSystemSetting')) {
            $navGlobalStatus = getSystemSetting($pdo, 'enrollment_status', 'open');
        }
        ?>
        <?php if ($navGlobalStatus === 'open'): ?>
          <a class="nav-link d-flex align-items-center gap-3 <?= esc($currentPage === 'enroll.php' ? 'active' : '') ?>" href="/sia/applicant/enroll.php">
            <i class="bi bi-pencil-square fs-5"></i>
            <span class="nav-text">Start Enrollment</span>
          </a>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($isEditable): ?>
        <a class="nav-link d-flex align-items-center gap-3 <?= esc($currentPage === 'enroll.php' ? 'active' : '') ?>" href="/sia/applicant/enroll.php">
          <i class="bi bi-pencil fs-5"></i>
          <span class="nav-text">Edit Application</span>
        </a>
      <?php endif; ?>

      <?php if ($isApprovedOrEnrolled): ?>
        <a class="nav-link d-flex align-items-center gap-3 <?= esc($currentPage === 'print_slip.php' ? 'active' : '') ?>" href="/sia/applicant/print_slip.php">
          <i class="bi bi-printer fs-5"></i>
          <span class="nav-text">Admission Slip</span>
        </a>
      <?php endif; ?>

      <?php if ($navAppStatus === 'enrolled'): ?>
        <a class="nav-link d-flex align-items-center gap-3 text-primary" href="/sia/auth/lms_student_login.php">
          <i class="bi bi-box-arrow-in-right fs-5 text-primary"></i>
          <span class="nav-text fw-semibold">Student LMS Portal</span>
        </a>
      <?php endif; ?>
    </div>

  </nav>

  <!-- User Profile & Sign Out Footer (Matches Admin Portal) -->
  <div class="p-3 border-top bg-light mt-auto flex-shrink-0">
    <div class="d-flex align-items-center gap-3 mb-3">
      <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0 shadow-sm" style="width: 42px; height: 42px; font-size: 0.95rem; background-color: #e7f1ff; color: #0d6efd;">
        <?= esc($userInitials) ?>
      </div>
      <div class="overflow-hidden nav-text user-profile-text">
        <span class="d-block fw-bold text-dark text-truncate" style="font-size: 0.9rem;" title="<?= esc($userName) ?>"><?= esc($userName) ?></span>
        <span class="text-muted text-truncate d-block" style="font-size: 0.75rem;">
          Applicant Account
        </span>
      </div>
    </div>
    <a class="btn btn-outline-danger w-100 btn-sm rounded-pill fw-medium shadow-sm py-2 d-flex align-items-center justify-content-center gap-2" href="/sia/auth/logout.php">
      <i class="bi bi-box-arrow-right"></i> <span class="nav-text">Sign Out</span>
    </a>
  </div>

</aside>
