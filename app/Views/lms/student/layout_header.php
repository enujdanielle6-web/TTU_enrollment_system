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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'LMS Dashboard' ?></title>
    <!-- CSS & Fonts -->
    <link href="/sia/public/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/sia/public/vendor/fonts/fonts.css">
    <link rel="stylesheet" href="/sia/public/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <!-- Custom LMS CSS -->
    <link rel="stylesheet" href="/sia/public/css/lms.css?v=<?= esc(filemtime(__DIR__ . '/../../../../public/css/lms.css')) ?>">
</head>
<body class="lms-layout">

<!-- Sidebar -->
<aside class="lms-sidebar" id="lmsSidebar">
    <!-- Brand Header -->
    <div class="lms-sidebar-brand d-flex align-items-center justify-content-between px-3 py-3 border-bottom">
        <a href="/sia/lms/student/dashboard.php" class="d-flex align-items-center gap-2 text-decoration-none">
            <div class="lms-brand-icon shadow-xs">
                <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 28px; width: auto; object-fit: contain;">
            </div>
            <div class="nav-text">
                <span class="fw-bold text-dark d-block" style="font-size: 1.05rem; line-height: 1.15; letter-spacing: -0.01em;">TTU LMS</span>
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-0 fw-bold" style="font-size: 0.62rem; letter-spacing: 0.04em;">STUDENT PORTAL</span>
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
            <input type="text" class="form-control bg-transparent border-0 p-0 shadow-none nav-text small" placeholder="Quick search...">
            <span class="lms-kbd-shortcut nav-text">⌘K</span>
        </div>
    </div>

    <!-- Navigation Menu Items -->
    <div class="lms-nav-menu flex-grow-1">
        <?php
        $isDashboardActive = ($current_page == 'dashboard.php' || strpos($request_uri, '/dashboard') !== false);
        $isCoursesActive = ($current_page == 'my_courses.php' || strpos($request_uri, 'my_courses') !== false || strpos($request_uri, '/course/') !== false || strpos($request_uri, 'course.php') !== false);
        $isCalendarActive = (strpos($request_uri, '/calendar') !== false);
        $isMessagesActive = (strpos($request_uri, 'messages') !== false);
        $isProfileActive = (strpos($request_uri, 'profile') !== false);
        ?>

        <div class="lms-section-label">Academics</div>
        <a href="/sia/lms/student/dashboard.php" class="lms-nav-link <?= esc($isDashboardActive ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-grid-1x2-fill"></i>
            </div>
            <span class="nav-text">Dashboard</span>
        </a>
        <a href="/sia/lms/student/my_courses.php" class="lms-nav-link <?= esc($isCoursesActive ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-journal-bookmark-fill"></i>
            </div>
            <span class="nav-text">My Courses</span>
        </a>

        <div class="lms-section-label mt-3">Campus Life</div>
        <a href="/sia/lms/student/calendar" class="lms-nav-link <?= esc($isCalendarActive ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-calendar-event-fill"></i>
            </div>
            <span class="nav-text">Calendar</span>
        </a>
        <a href="/sia/lms/student/messages.php" class="lms-nav-link <?= esc($isMessagesActive ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-chat-dots-fill"></i>
            </div>
            <span class="nav-text">Messages / Forums</span>
        </a>

        <div class="lms-section-label mt-3">Preferences</div>
        <a href="/sia/lms/student/profile.php" class="lms-nav-link <?= esc($isProfileActive ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-person-fill"></i>
            </div>
            <span class="nav-text">Profile</span>
        </a>
    </div>

    <!-- Footer Profile & Actions -->
    <div class="lms-sidebar-footer">
        <div class="lms-user-card mb-2">
            <div class="d-flex align-items-center">
                <div class="position-relative flex-shrink-0" style="width: 36px; height: 36px;">
                    <div class="lms-avatar">
                        <?= esc(substr($_SESSION['lms_name'] ?? 'S', 0, 1)) ?>
                    </div>
                    <span class="lms-status-dot" title="Online"></span>
                </div>
                <div class="nav-text flex-grow-1 min-w-0 overflow-hidden" style="line-height: 1.25;">
                    <div class="fw-bold text-dark text-truncate small" title="<?= htmlspecialchars($_SESSION['lms_name'] ?? 'Student') ?>">
                        <?= htmlspecialchars($_SESSION['lms_name'] ?? 'Student') ?>
                    </div>
                    <div class="text-muted text-truncate" style="font-size: 0.7rem;" title="<?= htmlspecialchars($_SESSION['lms_email'] ?? 'student@ttu.edu.ph') ?>">
                        <?= htmlspecialchars($_SESSION['lms_email'] ?? 'student@ttu.edu.ph') ?>
                    </div>
                </div>
                <div class="position-relative flex-shrink-0">
                    <button type="button" id="sidebarNotificationBtn" class="lms-bell-btn text-muted border-0 bg-transparent" title="Recent Professor Updates" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <?php if ($updateCount > 0): ?>
                            <span class="lms-notification-dot"></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
        </div>
        
        <a href="/sia/auth/lms_student_logout.php" class="lms-logout-btn text-decoration-none">
            <i class="bi bi-box-arrow-right"></i>
            <span class="nav-text">Sign out</span>
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
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1 fw-bold" style="font-size: 0.65rem;"><?= $updateCount ?> New</span>
            <button type="button" class="btn btn-sm btn-light border-0 text-muted p-1 rounded-2" id="closeNotificationPanel" title="Close">
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
        <a href="/sia/lms/student/calendar" class="small fw-semibold text-primary text-decoration-none">
            View Academic Calendar &rarr;
        </a>
    </div>
</div>

<!-- Main Content Area -->
<div class="lms-main" id="spa-main">

