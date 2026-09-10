<?php
/**
 * Unified Course Header & Horizontal Navigation (Option A)
 *
 * Variables expected:
 * @var array $course Course metadata
 * @var string $active_tab 'modules' | 'announcements' | 'assignments' | 'quizzes' | 'gradebook' | 'attendance'
 * @var int|null $assignment_count
 * @var int|null $quiz_count
 * @var int|null $announcement_count
 */

$courseId = (int)($course['lms_course_id'] ?? $course['id'] ?? 0);
$subjectCode = $course['subject_code'] ?? 'Course';
$subjectName = $course['subject_name'] ?? 'Course Overview';
$sectionCode = $course['section_code'] ?? 'Global Section';
$units = (int)($course['units'] ?? 0);

$instructorName = trim(($course['instructor_first'] ?? '') . ' ' . ($course['instructor_last'] ?? ''));
if (empty($instructorName)) {
    $instructorName = $instructor_name ?? 'Instructor TBA';
}
$instructorEmail = $course['instructor_email'] ?? ($instructor_email ?? 'N/A');

$activeTab = $active_tab ?? 'modules';

// Fetch badge counts if not already provided
if (!isset($assignment_count)) {
    try {
        $svc = new \App\Services\LmsService();
        $assignment_count = count($svc->getAssignmentsByCourse($courseId, true));
    } catch (\Throwable $e) {
        $assignment_count = 0;
    }
}

if (!isset($quiz_count)) {
    try {
        $qSvc = new \App\Services\LmsQuizService();
        $quiz_count = count($qSvc->getQuizzesByCourse($courseId, true));
    } catch (\Throwable $e) {
        $quiz_count = 0;
    }
}

if (!isset($announcement_count)) {
    try {
        $aSvc = new \App\Services\LmsAnnouncementService();
        $announcement_count = count($aSvc->getCourseAnnouncements($courseId, true));
    } catch (\Throwable $e) {
        $announcement_count = 0;
    }
}

$assignCount = (int)$assignment_count;
$quizCount = (int)$quiz_count;
$annCount = (int)$announcement_count;
?>

<!-- Top Utility Bar: Quick Return & Clean Breadcrumb -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 align-items-center">
            <li class="breadcrumb-item">
                <a href="/sia/lms/student/dashboard.php" class="text-decoration-none text-muted">
                    <i class="bi bi-grid-1x2 me-1"></i> Dashboard
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="/sia/lms/student/my_courses.php" class="text-decoration-none text-muted">My Courses</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">
                <?= htmlspecialchars($subjectCode) ?>
            </li>
        </ol>
    </nav>
    <a href="/sia/lms/student/my_courses.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Back to My Courses
    </a>
</div>

<!-- Course Banner -->
<div class="lms-banner mb-3 text-white p-4 p-md-5 rounded-4 shadow-sm position-relative overflow-hidden" style="background: linear-gradient(135deg, var(--lms-primary) 0%, #0a58ca 100%);">
    <div class="position-absolute" style="top: -50px; right: -50px; width: 250px; height: 250px; background: rgba(255,255,255,0.1); border-radius: 50%; filter: blur(20px);"></div>
    <div class="position-relative z-1 row align-items-center g-3">
        <div class="col-md-8">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-bold shadow-sm">
                    <?= htmlspecialchars($sectionCode) ?>
                </span>
                <?php if ($units > 0): ?>
                    <span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill fw-semibold">
                        <?= esc($units) ?> Academic Units
                    </span>
                <?php endif; ?>
            </div>
            <h1 class="display-6 fw-bold mb-2 text-white"><?= htmlspecialchars($subjectName) ?></h1>
            <p class="fs-6 mb-0 opacity-75 fw-semibold"><i class="bi bi-book me-1"></i> <?= htmlspecialchars($subjectCode) ?></p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="bg-white text-dark p-3 rounded-4 shadow-sm d-inline-block text-start">
                <p class="small text-muted fw-bold text-uppercase mb-1">Instructor</p>
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                        <?= esc(strtoupper(substr($instructorName, 0, 1))) ?>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($instructorName) ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($instructorEmail) ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Course Horizontal Navigation Bar (Option A) -->
<div class="course-nav-container mb-4">
    <a href="/sia/lms/student/course.php?id=<?= esc($courseId) ?>" 
       class="course-nav-link <?= esc($activeTab === 'modules' ? 'active' : '') ?>">
        <i class="bi bi-folder2-open"></i>
        <span>Modules &amp; Materials</span>
    </a>

    <a href="/sia/lms/student/course/<?= esc($courseId) ?>/announcements" 
       class="course-nav-link <?= esc($activeTab === 'announcements' ? 'active' : '') ?>">
        <i class="bi bi-megaphone"></i>
        <span>Announcements</span>
        <?php if ($annCount > 0): ?>
            <span class="badge rounded-pill course-nav-badge ms-1"><?= esc($annCount) ?></span>
        <?php endif; ?>
    </a>

    <a href="/sia/lms/student/course/<?= esc($courseId) ?>/assignments" 
       class="course-nav-link <?= esc($activeTab === 'assignments' ? 'active' : '') ?>">
        <i class="bi bi-journal-text"></i>
        <span>Assignments</span>
        <?php if ($assignCount > 0): ?>
            <span class="badge rounded-pill course-nav-badge ms-1"><?= esc($assignCount) ?></span>
        <?php endif; ?>
    </a>

    <a href="/sia/lms/student/course/<?= esc($courseId) ?>/quizzes" 
       class="course-nav-link <?= esc($activeTab === 'quizzes' ? 'active' : '') ?>">
        <i class="bi bi-pencil-square"></i>
        <span>Online Quizzes</span>
        <?php if ($quizCount > 0): ?>
            <span class="badge rounded-pill course-nav-badge ms-1"><?= esc($quizCount) ?></span>
        <?php endif; ?>
    </a>

    <a href="/sia/lms/student/course/<?= esc($courseId) ?>/gradebook" 
       class="course-nav-link <?= esc($activeTab === 'gradebook' ? 'active' : '') ?>">
        <i class="bi bi-star"></i>
        <span>Grades</span>
    </a>

    <a href="/sia/lms/student/course/<?= esc($courseId) ?>/attendance" 
       class="course-nav-link <?= esc($activeTab === 'attendance' ? 'active' : '') ?>">
        <i class="bi bi-person-check"></i>
        <span>Attendance</span>
    </a>
</div>
