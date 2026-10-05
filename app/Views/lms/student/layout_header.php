<?php
// Ensure session is started and user is verified in the parent file
$current_page = isset($current_page) ? $current_page : basename($_SERVER['PHP_SELF'] ?? '');
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

$studentUserId = (int)($_SESSION['user_id'] ?? $_SESSION['lms_user_id'] ?? 0);
$notifFeed = ['items' => [], 'unread' => 0];
if ($studentUserId > 0) {
    try {
        $notifFeed = (new \App\Services\LmsNotificationService())->getStudentFeed($studentUserId, 8);
    } catch (\Throwable $e) {
        $notifFeed = ['items' => [], 'unread' => 0];
    }
}
$updateCount = (int)$notifFeed['unread'];

$platformAnnouncements = [];
try {
    $platformAnnouncements = (new \App\Services\LmsAnnouncementService())->getPlatformAnnouncements('students');
} catch (\Throwable $e) {
    $platformAnnouncements = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'LMS Dashboard' ?></title>
    <!-- CSS & Fonts -->
    <link href="<?= BASE_PATH ?>/public/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/public/vendor/fonts/fonts.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/public/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <!-- Custom LMS CSS -->
    <link rel="stylesheet" href="<?= BASE_PATH ?>/public/css/lms.css?v=<?= esc(filemtime(__DIR__ . '/../../../../public/css/lms.css')) ?>">
</head>
<body class="lms-layout bg-light">

<div class="lms-wrapper d-flex min-vh-100">

  <!-- Mobile Backdrop Overlay -->
  <div id="sidebarBackdrop" class="sidebar-backdrop d-none d-lg-none" onclick="document.getElementById('lmsSidebar').classList.remove('show'); this.classList.add('d-none');"></div>

  <!-- Left Sidebar (Consistent with Registrar Dashboard) -->
  <aside class="lms-sidebar bg-white d-flex flex-column" id="lmsSidebar">
    
    <!-- Floating toggle button -->
    <button class="btn btn-primary rounded-circle position-absolute d-none d-lg-flex align-items-center justify-content-center shadow-sm sidebar-minimize-btn" id="sidebarMinimize" style="width: 28px; height: 28px; top: 28px; right: -14px; z-index: 1050; padding: 0;" aria-label="Toggle Sidebar Minimization" title="Toggle Sidebar">
      <i class="bi bi-chevron-left toggle-icon" style="font-size: 14px;"></i>
    </button>

    <!-- Brand / Logo Area -->
    <div class="p-4 border-bottom d-flex align-items-center justify-content-between flex-shrink-0">
      <a class="text-decoration-none d-flex align-items-center" href="<?= BASE_PATH ?>/lms/student/dashboard.php" aria-label="LMS Student Portal">
        <img src="<?= BASE_PATH ?>/images/TTU_LOGO.png" alt="TTU Logo" style="height: 36px; width: auto; object-fit: contain;">
        <div class="ms-3 nav-text">
          <span class="text-dark fw-bold fs-5 d-block" style="line-height: 1.15;">TTU LMS</span>
          <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-0 fw-semibold" style="font-size: 0.62rem; letter-spacing: 0.04em;">STUDENT PORTAL</span>
        </div>
      </a>
      <button type="button" class="btn btn-sm btn-light d-lg-none" id="sidebarClose" aria-label="Close navigation" onclick="document.getElementById('lmsSidebar').classList.remove('show'); document.getElementById('sidebarBackdrop').classList.add('d-none');">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- Quick Search -->
    <div class="px-3 my-2 lms-search-container flex-shrink-0">
        <div class="lms-search-box d-flex align-items-center gap-2 px-3 py-1.5 rounded-3">
            <i class="bi bi-search text-muted small"></i>
            <input type="search" class="form-control bg-transparent border-0 p-0 shadow-none nav-text small lms-sidebar-search" placeholder="Quick search..." aria-label="Search navigation" autocomplete="off">
            <kbd class="lms-kbd-shortcut nav-text" aria-hidden="true">⌘K</kbd>
        </div>
    </div>

    <!-- Navigation Menu Items -->
    <nav class="flex-grow-1 p-3 overflow-y-auto lms-nav-menu" id="lmsSidebarNav">
        <?php
        $isDashboardActive = ($current_page == 'dashboard.php' || strpos($request_uri, '/dashboard') !== false);
        $isCoursesActive = ($current_page == 'my_courses.php' || strpos($request_uri, 'my_courses') !== false || strpos($request_uri, '/course/') !== false || strpos($request_uri, 'course.php') !== false || strpos($request_uri, '/assignments') !== false);
        $isCalendarActive = (strpos($request_uri, '/calendar') !== false);
        $isMessagesActive = (strpos($request_uri, 'messages') !== false);
        $isProfileActive = (strpos($request_uri, 'profile') !== false);
        ?>

        <div class="sidebar-section-header">Academics</div>
        <a title="Dashboard" class="nav-link lms-nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($isDashboardActive ? 'active' : '') ?>" href="<?= BASE_PATH ?>/lms/student/dashboard.php">
            <i class="bi bi-grid-1x2 fs-5"></i> <span class="nav-text">Dashboard</span>
        </a>
        <a title="My Courses" class="nav-link lms-nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($isCoursesActive ? 'active' : '') ?>" href="<?= BASE_PATH ?>/lms/student/my_courses.php">
            <i class="bi bi-journal-bookmark fs-5"></i> <span class="nav-text">My Courses</span>
        </a>

        <div class="sidebar-section-header mt-3">Campus Life</div>
        <a title="Calendar" class="nav-link lms-nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($isCalendarActive ? 'active' : '') ?>" href="<?= BASE_PATH ?>/lms/student/calendar">
            <i class="bi bi-calendar-event fs-5"></i> <span class="nav-text">Calendar</span>
        </a>
        <a title="Messages / Forums" class="nav-link lms-nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($isMessagesActive ? 'active' : '') ?>" href="<?= BASE_PATH ?>/lms/student/messages.php">
            <i class="bi bi-chat-dots fs-5"></i> <span class="nav-text">Messages / Forums</span>
        </a>

        <div class="sidebar-section-header mt-3">Preferences</div>
        <a title="Profile" class="nav-link lms-nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($isProfileActive ? 'active' : '') ?>" href="<?= BASE_PATH ?>/lms/student/profile.php">
            <i class="bi bi-person fs-5"></i> <span class="nav-text">Profile</span>
        </a>
    </nav>

    <!-- User Profile Area (Consistent with Registrar Dashboard) -->
    <?php
    $studentFullName = $_SESSION['lms_name'] ?? $_SESSION['user_name'] ?? 'Student User';
    $nameParts = explode(' ', trim($studentFullName));
    $initials = strtoupper(substr($nameParts[0] ?? 'S', 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
    $studentEmail = $_SESSION['lms_email'] ?? $_SESSION['user_email'] ?? 'student@ttu.edu.ph';
    ?>
    <div class="lms-sidebar-footer">
        <div class="lms-user-card mb-2">
            <div class="d-flex align-items-center">
                <div class="position-relative flex-shrink-0" style="width: 36px; height: 36px;">
                    <div class="lms-avatar">
                        <?= esc($initials) ?>
                    </div>
                    <span class="lms-status-dot" title="Online"></span>
                </div>
                <div class="nav-text user-profile-text flex-grow-1 min-w-0 overflow-hidden" style="line-height: 1.25;">
                    <div class="fw-bold text-dark text-truncate small" title="<?= htmlspecialchars($studentFullName, ENT_QUOTES, 'UTF-8'); ?>">
                        <?= htmlspecialchars($studentFullName, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="text-muted text-truncate" style="font-size: 0.7rem;" title="<?= htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8'); ?>">
                        <?= htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
                <div class="position-relative flex-shrink-0">
                    <button type="button" id="sidebarNotificationBtn" class="lms-bell-btn text-muted border-0 bg-transparent" title="Recent Updates" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <span class="lms-notification-dot <?= $updateCount > 0 ? '' : 'd-none' ?>" data-notif-dot></span>
                    </button>
                </div>
            </div>
        </div>

        <a href="<?= BASE_PATH ?>/auth/lms_student_logout.php" class="lms-logout-btn text-decoration-none">
            <i class="bi bi-box-arrow-right"></i>
            <span class="nav-text">Sign out</span>
        </a>
    </div>
  </aside>

  <!-- Professor Updates Dropup Panel -->
  <?php
  $notifPortal = 'student';
  $notifTitle = 'Professor Updates';
  $notifSubtitle = 'Notices, assignments & quizzes';
  $notifEmptyText = 'No recent updates from your professors.';
  require __DIR__ . '/../components/notification_panel.php';
  ?>

  <!-- Main Content Area -->
  <div class="lms-main flex-grow-1 d-flex flex-column bg-light" id="spa-main">
    
    <!-- Mobile Topbar -->
    <div class="d-lg-none bg-white border-bottom shadow-sm p-3 d-flex align-items-center justify-content-between sticky-top mb-3">
      <div class="d-flex align-items-center gap-2">
        <span class="bg-primary-light text-primary border border-primary border-opacity-10 d-flex align-items-center justify-content-center rounded-circle" style="width: 32px; height: 32px; background: rgba(13, 110, 253, 0.1);">
          <i class="bi bi-mortarboard-fill fs-6 text-primary"></i>
        </span>
        <span class="fw-bold text-dark">TTU LMS</span>
      </div>
      <button class="btn btn-light border-0 shadow-sm" id="sidebarToggle" aria-label="Toggle Navigation">
        <i class="bi bi-list fs-5"></i>
      </button>
    </div>

<?php if (!empty($platformAnnouncements)): ?>
    <div class="px-3 pt-3">
        <?php foreach ($platformAnnouncements as $pAnn): 
            $pSev = $pAnn['severity'] ?? 'info';
            $pAlertClass = 'alert-primary';
            $pIcon = 'bi-info-circle-fill';
            if ($pSev === 'warning') {
                $pAlertClass = 'alert-warning';
                $pIcon = 'bi-exclamation-triangle-fill';
            } elseif ($pSev === 'danger') {
                $pAlertClass = 'alert-danger';
                $pIcon = 'bi-exclamation-octagon-fill';
            } elseif ($pSev === 'success') {
                $pAlertClass = 'alert-success';
                $pIcon = 'bi-check-circle-fill';
            }
        ?>
            <div class="alert <?= $pAlertClass ?> alert-dismissible fade show border-0 shadow-sm rounded-4 mb-3 d-flex align-items-start gap-3 p-3" role="alert">
                <div class="fs-4 flex-shrink-0 lh-1 mt-0.5">
                    <i class="bi <?= $pIcon ?>"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <strong class="fw-bold text-dark"><?= htmlspecialchars($pAnn['title']) ?></strong>
                        <span class="badge bg-white text-dark shadow-xs border rounded-pill px-2 py-0.5 small" style="font-size: 0.65rem;">LMS Notice</span>
                    </div>
                    <div class="small text-dark mb-0 opacity-90"><?= nl2br(htmlspecialchars($pAnn['content'])) ?></div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

