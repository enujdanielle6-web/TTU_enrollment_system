<?php
// Ensure session is started and user is verified in the parent file
$current_page = isset($current_page) ? $current_page : basename($_SERVER['PHP_SELF']);
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
    <link rel="stylesheet" href="/sia/public/css/lms.css?v=<?= filemtime(__DIR__ . '/../../../../public/css/lms.css') ?>">
</head>
<body class="lms-layout">

<!-- Sidebar -->
<aside class="lms-sidebar" id="lmsSidebar">
    <div class="lms-sidebar-logo text-primary d-flex align-items-center gap-2 lms-brand">
        <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 32px; width: auto; object-fit: contain;">
        <span class="nav-text">TTU LMS</span>
    </div>

    <div class="px-3 mb-4 mt-2 lms-search-container">
        <div class="input-group input-group-sm bg-light rounded-3 overflow-hidden border">
            <span class="input-group-text bg-transparent border-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control bg-transparent border-0 shadow-none nav-text" placeholder="Search...">
            <span class="input-group-text bg-transparent border-0 text-muted nav-text" style="font-size: 0.7rem;">⌘ K</span>
        </div>
    </div>

    <a href="/sia/lms/student/dashboard.php" class="lms-nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
        <i class="bi bi-grid-1x2-fill"></i>
        <span class="nav-text">Dashboard</span>
    </a>
    <a href="/sia/lms/student/my_courses.php" class="lms-nav-link <?= $current_page == 'my_courses.php' ? 'active' : '' ?>">
        <i class="bi bi-journal-bookmark-fill"></i>
        <span class="nav-text">My Courses</span>
    </a>
    <?php if (isset($course) && isset($course['lms_course_id'])): ?>
        <hr class="text-secondary mx-3 my-2">
        <div class="px-3 pb-2 text-muted small fw-bold text-uppercase">Course Menu</div>
        <a href="/sia/lms/student/course.php?id=<?= $course['lms_course_id'] ?>" class="lms-nav-link <?= strpos($current_page, 'course.php') !== false ? 'active' : '' ?>">
            <i class="bi bi-folder2-open"></i>
            <span class="nav-text">Modules & Materials</span>
        </a>
        <a href="/sia/lms/student/course/<?= $course['lms_course_id'] ?>/announcements" class="lms-nav-link <?= strpos($_SERVER['REQUEST_URI'], '/announcements') !== false ? 'active' : '' ?>">
            <i class="bi bi-megaphone"></i>
            <span class="nav-text">Announcements</span>
        </a>
        <a href="/sia/lms/student/course/<?= $course['lms_course_id'] ?>/assignments" class="lms-nav-link <?= strpos($_SERVER['REQUEST_URI'], '/assignments') !== false ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i>
            <span class="nav-text">Assignments</span>
        </a>
        <a href="/sia/lms/student/course/<?= $course['lms_course_id'] ?>/quizzes" class="lms-nav-link <?= strpos($_SERVER['REQUEST_URI'], '/quizzes') !== false ? 'active' : '' ?>">
            <i class="bi bi-pencil-square"></i>
            <span class="nav-text">Online Quizzes</span>
        </a>
        <a href="/sia/lms/student/course/<?= $course['lms_course_id'] ?>/gradebook" class="lms-nav-link <?= strpos($_SERVER['REQUEST_URI'], '/gradebook') !== false ? 'active' : '' ?>">
            <i class="bi bi-star-fill"></i>
            <span class="nav-text">Grades</span>
        </a>
        <a href="/sia/lms/student/course/<?= $course['lms_course_id'] ?>/attendance" class="lms-nav-link <?= strpos($_SERVER['REQUEST_URI'], '/attendance') !== false ? 'active' : '' ?>">
            <i class="bi bi-person-check-fill"></i>
            <span class="nav-text">Attendance</span>
        </a>
    <?php endif; ?>
    <hr class="text-secondary mx-3 my-2">
    <a href="/sia/lms/student/calendar" class="lms-nav-link <?= strpos($_SERVER['REQUEST_URI'], '/calendar') !== false ? 'active' : '' ?>">
        <i class="bi bi-calendar-event-fill"></i>
        <span class="nav-text">Calendar</span>
    </a>
    <a href="#" class="lms-nav-link">
        <i class="bi bi-chat-dots-fill"></i>
        <span class="nav-text">Messages / Forums</span>
    </a>
    <a href="#" class="lms-nav-link">
        <i class="bi bi-person-fill"></i>
        <span class="nav-text">Profile</span>
    </a>

    <div class="lms-sidebar-footer">
        <div class="d-flex align-items-center gap-2 mb-3 lms-user-info">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold lms-avatar" style="width: 32px; height: 32px; flex-shrink:0;">
                <?= substr($_SESSION['lms_name'] ?? 'S', 0, 1) ?>
            </div>
            <div class="nav-text" style="line-height: 1.2; overflow: hidden;">
                <span class="d-block fw-bold text-dark small text-truncate" style="max-width: 120px;" title="<?= htmlspecialchars($_SESSION['lms_name'] ?? 'Student') ?>">
                    <?= htmlspecialchars($_SESSION['lms_name'] ?? 'Student') ?>
                </span>
                <span class="d-block text-muted text-truncate" style="font-size: 0.7rem; max-width: 120px;" title="<?= htmlspecialchars($_SESSION['lms_email'] ?? 'student@ttu.edu.ph') ?>">
                    <?= htmlspecialchars($_SESSION['lms_email'] ?? 'student@ttu.edu.ph') ?>
                </span>
            </div>
            <i class="bi bi-bell text-muted ms-auto nav-text"></i>
        </div>
        
        <a href="/sia/auth/logout.php" class="btn btn-light w-100 text-danger fw-semibold d-flex align-items-center justify-content-center gap-2 border lms-logout-btn">
            <i class="bi bi-box-arrow-right"></i> <span class="nav-text">Sign out</span>
        </a>
    </div>
</aside>

<!-- Main Content Area -->
<div class="lms-main" id="spa-main">

