<?php
// Ensure session is started and user is verified in the parent file
$current_page = isset($current_page) ? $current_page : basename($_SERVER['PHP_SELF'] ?? '');
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

$studentUserId = (int)($_SESSION['user_id'] ?? $_SESSION['lms_user_id'] ?? 0);
$professorUpdates = [];
if ($studentUserId > 0) {
    try {
        $professorUpdates = (new \App\Services\LmsService())->getStudentProfessorUpdates($studentUserId, 6);
    } catch (\Throwable $e) {
        $professorUpdates = [];
    }
}
$updateCount = count($professorUpdates);

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
    <div class="p-3 border-top bg-light mt-auto flex-shrink-0">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0 shadow-xs" style="width: 42px; height: 42px; background: rgba(13, 110, 253, 0.1); color: #0d6efd; font-size: 0.95rem;">
          <?= esc($initials) ?>
        </div>
        <div class="overflow-hidden nav-text user-profile-text flex-grow-1 min-w-0">
          <span class="d-block fw-bold text-dark text-truncate" style="font-size: 0.9rem;" title="<?= htmlspecialchars($studentFullName, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($studentFullName, ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="text-muted text-truncate d-block" style="font-size: 0.75rem;" title="<?= htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="flex-shrink-0 nav-text">
          <button type="button" id="sidebarNotificationBtn" class="btn btn-sm btn-white border shadow-xs rounded-circle text-muted p-0 d-flex align-items-center justify-content-center position-relative" style="width: 32px; height: 32px; background: #ffffff;" title="Recent Updates" aria-expanded="false">
            <i class="bi bi-bell"></i>
            <?php if ($updateCount > 0): ?>
              <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
            <?php endif; ?>
          </button>
        </div>
      </div>
      <a class="btn btn-outline-danger w-100 btn-sm rounded-pill fw-medium shadow-sm py-1.5 d-flex align-items-center justify-content-center gap-1.5" href="<?= BASE_PATH ?>/auth/lms_student_logout.php">
        <i class="bi bi-box-arrow-right"></i> <span class="nav-text">Sign Out</span>
      </a>
    </div>
  </aside>

  <!-- Professor Updates Dropup Panel -->
  <div class="lms-notification-panel shadow-lg" id="sidebarNotificationPanel">
      <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
          <div class="d-flex align-items-center gap-2">
              <div class="icon-box-sm bg-primary bg-opacity-10 text-primary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 0.5rem;">
                  <i class="bi bi-bell-fill"></i>
              </div>
              <div>
                  <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.88rem;">Professor Updates</h6>
                  <small class="text-muted" style="font-size: 0.7rem;">Notices, assignments & quizzes</small>
              </div>
          </div>
          <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1 fw-bold" style="font-size: 0.65rem;"><?= (int) $updateCount ?> New</span>
              <button type="button" class="btn btn-sm btn-light border-0 text-muted p-1 rounded-2" id="closeNotificationPanel" title="Close" aria-label="Close updates">
                  <i class="bi bi-x-lg" style="font-size: 0.75rem;"></i>
              </button>
          </div>
      </div>
  
      <div class="lms-notification-list">
          <?php if (empty($professorUpdates)): ?>
              <div class="p-4 text-center text-muted">
                  <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
                  <p class="mb-0 small fw-medium">All caught up!</p>
                  <small class="text-muted" style="font-size: 0.72rem;">No recent updates from your professors.</small>
              </div>
          <?php else: ?>
              <?php foreach ($professorUpdates as $up): 
                  $type = $up['type'] ?? 'announcement';
                  $iconClass = 'bi-megaphone-fill';
                  $badgeBg = 'bg-primary bg-opacity-10 text-primary';
                  if ($type === 'assignment') {
                      $iconClass = 'bi-journal-text';
                      $badgeBg = 'bg-success bg-opacity-10 text-success';
                  } elseif ($type === 'quiz') {
                      $iconClass = 'bi-pencil-square';
                      $badgeBg = 'bg-info bg-opacity-10 text-info';
                  }
                  $timeAgo = date('M d, h:i A', strtotime($up['created_at']));
              ?>
                  <a href="<?= htmlspecialchars($up['url']) ?>" class="lms-notification-item d-flex gap-2 p-3 border-bottom text-decoration-none">
                      <div class="icon-box-sm <?= esc($badgeBg) ?>" style="width: 34px; height: 34px; font-size: 0.95rem; border-radius: 0.55rem; flex-shrink: 0;">
                          <i class="bi <?= esc($iconClass) ?>"></i>
                      </div>
                      <div class="flex-grow-1 min-w-0" style="overflow: hidden;">
                          <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                              <span class="badge bg-light text-secondary border px-2 py-0 small flex-shrink-0" style="font-size: 0.65rem;">
                                  <?= htmlspecialchars($up['subject_code']) ?>
                              </span>
                              <span class="text-muted small text-nowrap flex-shrink-0" style="font-size: 0.68rem;"><?= esc($timeAgo) ?></span>
                          </div>
                          <div class="fw-bold text-dark small text-truncate" title="<?= htmlspecialchars($up['title']) ?>">
                              <?= htmlspecialchars($up['title']) ?>
                          </div>
                          <div class="text-muted text-truncate small mt-1" style="font-size: 0.72rem;">
                              <i class="bi bi-person me-1"></i><?= htmlspecialchars($up['professor_name']) ?>
                              <?php if (!empty($up['due_date'])): ?>
                                  <span class="text-danger ms-1">&bull; Due <?= date('M d', strtotime($up['due_date'])) ?></span>
                              <?php endif; ?>
                          </div>
                      </div>
                  </a>
              <?php endforeach; ?>
          <?php endif; ?>
      </div>
  
      <div class="p-2 bg-light border-top text-center">
          <a href="<?= BASE_PATH ?>/lms/student/calendar" class="small fw-semibold text-primary text-decoration-none">
              View Academic Calendar &rarr;
          </a>
      </div>
  </div>

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

