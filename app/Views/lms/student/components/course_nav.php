<?php
/**
 * Course Horizontal Sub-Navigation (Option A)
 * 
 * Provides unified tab navigation across student course views.
 * 
 * Variables:
 * @var array $course Course details
 * @var string $active_tab Current tab ('modules', 'announcements', 'assignments', 'quizzes', 'gradebook', 'attendance')
 * @var int|null $assignment_count
 * @var int|null $quiz_count
 * @var int|null $announcement_count
 */

$courseId = (int)($course['lms_course_id'] ?? $course['id'] ?? 0);
$activeTab = $active_tab ?? 'modules';
$assignCount = (int)($assignment_count ?? 0);
$quizCount = (int)($quiz_count ?? 0);
$annCount = (int)($announcement_count ?? 0);
?>

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
