<?php
// Ensure session is started and user is verified in the parent file
$current_page = isset($current_page) ? $current_page : basename($_SERVER['PHP_SELF'] ?? '');
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

$facultyUserId = (int)($_SESSION['user_id'] ?? $_SESSION['lms_user_id'] ?? 0);
$facultyActivity = [];
if ($facultyUserId > 0) {
    try {
        $facultyActivity = (new \App\Services\LmsService())->getFacultyRecentActivity($facultyUserId, 6);
    } catch (\Throwable $e) {
        $facultyActivity = [];
    }
}
$activityCount = count($facultyActivity);

$platformAnnouncements = [];
try {
    $platformAnnouncements = (new \App\Services\LmsAnnouncementService())->getPlatformAnnouncements('faculty');
} catch (\Throwable $e) {
    $platformAnnouncements = [];
}

$facultyName = $_SESSION['user_name'] ?? $_SESSION['lms_name'] ?? 'Faculty Member';
$facultyEmail = $_SESSION['user_email'] ?? $_SESSION['lms_email'] ?? 'faculty@ttu.edu.ph';
$facultyInitial = strtoupper(substr($facultyName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Faculty LMS Dashboard - TTU' ?></title>
    <!-- CSS & Fonts -->
    <link href="<?= BASE_PATH ?>/public/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/public/vendor/fonts/fonts.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/public/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <!-- Main Design System & Custom LMS CSS -->
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/main.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/public/css/lms.css?v=<?= esc(filemtime(__DIR__ . '/../../../../public/css/lms.css')) ?>">
</head>
<body class="lms-layout lms-faculty-layout">

<!-- Sidebar -->
<aside class="lms-sidebar" id="lmsSidebar">
    <!-- Brand Header -->
    <div class="lms-sidebar-brand d-flex align-items-center justify-content-between px-3 py-3 border-bottom">
        <a href="<?= BASE_PATH ?>/lms/faculty/dashboard.php" class="d-flex align-items-center gap-2 text-decoration-none">
            <div class="lms-brand-icon shadow-xs">
                <img src="<?= BASE_PATH ?>/images/TTU_LOGO.png" alt="TTU Logo" style="height: 28px; width: auto; object-fit: contain;">
            </div>
            <div class="nav-text">
                <span class="fw-bold text-dark d-block" style="font-size: 1.05rem; line-height: 1.15; letter-spacing: -0.01em;">TTU LMS</span>
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-0 fw-bold" style="font-size: 0.62rem; letter-spacing: 0.04em;">FACULTY PORTAL</span>
            </div>
        </a>
        <button id="sidebarToggle" class="lms-toggle-btn text-muted" type="button" title="Toggle Sidebar">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>
    </div>

    <!-- Quick Search -->
    <div class="px-3 my-3 lms-search-container">
        <div class="lms-search-box d-flex align-items-center gap-2 px-3 py-1.5 rounded-3">
            <i class="bi bi-search text-muted small"></i>
            <input type="search" class="form-control bg-transparent border-0 p-0 shadow-none nav-text small lms-sidebar-search" placeholder="Quick search..." aria-label="Search navigation" autocomplete="off">
            <kbd class="lms-kbd-shortcut nav-text" aria-hidden="true">⌘K</kbd>
        </div>
    </div>

    <!-- Navigation Menu Items -->
    <div class="lms-nav-menu flex-grow-1">
        <?php
        $isDashboardActive = ($current_page == 'dashboard.php' || (strpos($request_uri, '/dashboard') !== false && strpos($request_uri, '/course') === false));
        $isCalendarActive = (strpos($request_uri, '/calendar') !== false);
        $isMessagesActive = (strpos($request_uri, 'messages') !== false);
        $isProfileActive = (strpos($request_uri, 'profile') !== false);
        ?>

        <div class="lms-section-label">Teaching &amp; Classes</div>
        <a href="<?= BASE_PATH ?>/lms/faculty/dashboard.php" class="lms-nav-link <?= esc($isDashboardActive ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-grid-1x2-fill"></i>
            </div>
            <span class="nav-text">Dashboard</span>
        </a>

        <?php if (isset($course) && isset($course['lms_course_id'])): ?>
            <div class="lms-section-label mt-3"><?= htmlspecialchars($course['subject_code'] ?? 'Course') ?></div>
            <a href="<?= BASE_PATH ?>/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="lms-nav-link <?= esc(strpos($current_page, 'course.php') !== false ? 'active' : '') ?>">
                <div class="lms-nav-icon">
                    <i class="bi bi-folder2-open"></i>
                </div>
                <span class="nav-text">Modules &amp; Materials</span>
            </a>
            <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/announcements" class="lms-nav-link <?= esc(strpos($request_uri, '/announcements') !== false ? 'active' : '') ?>">
                <div class="lms-nav-icon">
                    <i class="bi bi-megaphone"></i>
                </div>
                <span class="nav-text">Announcements</span>
            </a>
            <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments" class="lms-nav-link <?= esc(strpos($request_uri, '/assignments') !== false ? 'active' : '') ?>">
                <div class="lms-nav-icon">
                    <i class="bi bi-journal-text"></i>
                </div>
                <span class="nav-text">Assignments</span>
            </a>
            <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="lms-nav-link <?= esc(strpos($request_uri, '/quizzes') !== false ? 'active' : '') ?>">
                <div class="lms-nav-icon">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <span class="nav-text">Online Quizzes</span>
            </a>
            <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/gradebook" class="lms-nav-link <?= esc(strpos($request_uri, '/gradebook') !== false ? 'active' : '') ?>">
                <div class="lms-nav-icon">
                    <i class="bi bi-star-fill"></i>
                </div>
                <span class="nav-text">Grades</span>
            </a>
            <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance" class="lms-nav-link <?= esc(strpos($request_uri, '/attendance') !== false ? 'active' : '') ?>">
                <div class="lms-nav-icon">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <span class="nav-text">Attendance</span>
            </a>
        <?php endif; ?>

        <div class="lms-section-label mt-3">Campus Life</div>
        <a href="<?= BASE_PATH ?>/lms/faculty/calendar" class="lms-nav-link <?= esc($isCalendarActive ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-calendar-event-fill"></i>
            </div>
            <span class="nav-text">Calendar</span>
        </a>
        <a href="<?= BASE_PATH ?>/lms/faculty/messages.php" class="lms-nav-link <?= esc($isMessagesActive ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-chat-dots-fill"></i>
            </div>
            <span class="nav-text">Messages / Forums</span>
        </a>

        <div class="lms-section-label mt-3">Preferences</div>
        <a href="<?= BASE_PATH ?>/lms/faculty/profile.php" class="lms-nav-link <?= esc($isProfileActive ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-person-fill"></i>
            </div>
            <span class="nav-text">Profile</span>
        </a>

        <?php if (in_array($_SESSION['user_role'] ?? '', ['superadmin', 'admin'], true) && ($_SESSION['user_department'] ?? '') !== 'Registrar Office'): ?>
            <div class="lms-section-label mt-3">Administration</div>
            <a href="<?= BASE_PATH ?>/lms/admin/dashboard" class="lms-nav-link text-primary fw-semibold">
                <div class="lms-nav-icon text-primary">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <span class="nav-text">LMS Governance</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Footer Profile & Actions -->
    <div class="lms-sidebar-footer">
        <div class="lms-user-card mb-2">
            <div class="d-flex align-items-center">
                <div class="position-relative flex-shrink-0" style="width: 36px; height: 36px;">
                    <div class="lms-avatar">
                        <?= esc($facultyInitial) ?>
                    </div>
                    <span class="lms-status-dot" title="Online"></span>
                </div>
                <div class="nav-text flex-grow-1 min-w-0 overflow-hidden" style="line-height: 1.25;">
                    <div class="fw-bold text-dark text-truncate small" title="<?= htmlspecialchars($facultyName) ?>">
                        <?= htmlspecialchars($facultyName) ?>
                    </div>
                    <div class="text-muted text-truncate" style="font-size: 0.7rem;" title="<?= htmlspecialchars($facultyEmail) ?>">
                        <?= htmlspecialchars($facultyEmail) ?>
                    </div>
                </div>
                <div class="position-relative flex-shrink-0">
                    <button type="button" id="sidebarNotificationBtn" class="lms-bell-btn text-muted border-0 bg-transparent" title="Recent Activity &amp; Submissions" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <?php if ($activityCount > 0): ?>
                            <span class="lms-notification-dot"></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
        </div>
        
        <a href="<?= BASE_PATH ?>/auth/lms_faculty_logout.php" class="lms-logout-btn text-decoration-none">
            <i class="bi bi-box-arrow-right"></i>
            <span class="nav-text">Sign out</span>
        </a>
    </div>
</aside>

<!-- Faculty Activity Dropup Panel -->
<div class="lms-notification-panel shadow-lg" id="sidebarNotificationPanel">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
        <div class="d-flex align-items-center gap-2">
            <div class="icon-box-sm bg-primary bg-opacity-10 text-primary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 0.5rem;">
                <i class="bi bi-bell-fill"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.88rem;">Faculty Activity</h6>
                <small class="text-muted" style="font-size: 0.7rem;">Student submissions &amp; notices</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1 fw-bold" style="font-size: 0.65rem;"><?= $activityCount ?> New</span>
            <button type="button" class="btn btn-sm btn-light border-0 text-muted p-1 rounded-2" id="closeNotificationPanel" title="Close">
                <i class="bi bi-x-lg" style="font-size: 0.75rem;"></i>
            </button>
        </div>
    </div>

    <div class="lms-notification-list">
        <?php if (empty($facultyActivity)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
                <p class="mb-0 small fw-medium">All caught up!</p>
                <small class="text-muted" style="font-size: 0.72rem;">No pending student submissions or alerts.</small>
            </div>
        <?php else: ?>
            <?php foreach ($facultyActivity as $act): 
                $type = $act['type'] ?? 'submission';
                $iconClass = 'bi-journal-check';
                $badgeBg = 'bg-success bg-opacity-10 text-success';
                if ($type === 'announcement') {
                    $iconClass = 'bi-megaphone-fill';
                    $badgeBg = 'bg-primary bg-opacity-10 text-primary';
                }
                $timeAgo = date('M d, h:i A', strtotime($act['created_at']));
            ?>
                <a href="<?= htmlspecialchars($act['url']) ?>" class="lms-notification-item d-flex gap-2 p-3 border-bottom text-decoration-none">
                    <div class="icon-box-sm <?= esc($badgeBg) ?>" style="width: 34px; height: 34px; font-size: 0.95rem; border-radius: 0.55rem; flex-shrink: 0;">
                        <i class="bi <?= esc($iconClass) ?>"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0" style="overflow: hidden;">
                        <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                            <span class="badge bg-light text-secondary border px-2 py-0 small flex-shrink-0" style="font-size: 0.65rem;">
                                <?= htmlspecialchars($act['subject_code']) ?>
                            </span>
                            <span class="text-muted small text-nowrap flex-shrink-0" style="font-size: 0.68rem;"><?= esc($timeAgo) ?></span>
                        </div>
                        <div class="fw-bold text-dark small text-truncate" title="<?= htmlspecialchars($act['title']) ?>">
                            <?= htmlspecialchars($act['title']) ?>
                        </div>
                        <div class="text-muted text-truncate small mt-1" style="font-size: 0.72rem;">
                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($act['student_name']) ?>
                            <?php if (!empty($act['status'])): ?>
                                <span class="badge bg-warning bg-opacity-10 text-dark ms-1"><?= htmlspecialchars($act['status']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="p-2 bg-light border-top text-center">
        <a href="<?= BASE_PATH ?>/lms/faculty/calendar" class="small fw-semibold text-primary text-decoration-none">
            View Academic Calendar &rarr;
        </a>
    </div>
</div>

<!-- Main Content Area -->
<div class="lms-main" id="spa-main">

<?php
$facultyModuleTitle = 'Dashboard';
if (strpos($request_uri, '/calendar') !== false) {
    $facultyModuleTitle = 'Calendar';
} elseif (strpos($request_uri, 'messages') !== false) {
    $facultyModuleTitle = 'Messages & Forums';
} elseif (strpos($request_uri, 'profile') !== false) {
    $facultyModuleTitle = 'Faculty Profile';
} elseif (isset($course) && !empty($course['subject_code'])) {
    $facultyModuleTitle = htmlspecialchars($course['subject_code']) . ' Management';
}
?>

<!-- Sleek LMS Faculty Topbar -->
<header class="lms-admin-topbar">
  <div class="container-fluid px-lg-4 d-flex align-items-center justify-content-between gap-3">
    <!-- Left: Mobile Toggle & Breadcrumbs -->
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-light border d-lg-none shadow-xs" type="button" onclick="document.getElementById('lmsSidebar').classList.toggle('show');" aria-label="Toggle Navigation">
        <i class="bi bi-list fs-5"></i>
      </button>
      <nav aria-label="Breadcrumb">
        <ul class="lms-breadcrumb d-flex align-items-center mb-0">
          <li>
            <a href="<?= BASE_PATH ?>/lms/faculty/dashboard.php">
              <i class="bi bi-mortarboard text-primary"></i>
              <span>Faculty Portal</span>
            </a>
          </li>
          <li class="separator"><i class="bi bi-chevron-right"></i></li>
          <li class="current"><?= esc($facultyModuleTitle) ?></li>
        </ul>
      </nav>
    </div>

    <!-- Right: Context Indicators (profile and sign out live in the sidebar footer) -->
    <div class="d-flex align-items-center gap-2.5">
      <!-- Active Term Badge -->
      <div class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1.5 small fw-semibold d-none d-md-inline-flex align-items-center gap-1.5 shadow-xs">
        <span class="pulse-dot-green"></span>
        <span>2026-2027 First Sem</span>
      </div>

      <!-- LMS Governance Switcher for Admins/Superadmin -->
      <?php if (in_array($_SESSION['user_role'] ?? '', ['superadmin', 'admin'], true) && ($_SESSION['user_department'] ?? '') !== 'Registrar Office'): ?>
      <a href="<?= BASE_PATH ?>/lms/admin/dashboard" class="btn btn-sm btn-light border rounded-pill px-3 py-1.5 small fw-semibold text-primary d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift" title="LMS Administration & Governance">
        <i class="bi bi-shield-lock-fill text-primary"></i>
        <span class="d-none d-sm-inline">LMS Governance</span>
      </a>
      <?php endif; ?>
    </div>
  </div>
</header>

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
