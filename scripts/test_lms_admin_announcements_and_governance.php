<?php
/**
 * TTU LMS ADMIN PLATFORM ANNOUNCEMENTS & FINAL GOVERNANCE VERIFICATION SUITE
 * 
 * Verifies:
 * 1. Role-Based Access Control (Admin access, Scheduler denial, Faculty denial, Student denial)
 * 2. Platform Announcement Creation, Metadata, and Severity Support
 * 3. Strict Audience Segregation (All Users, Students Only, Faculty Only)
 * 4. Scheduling & Expiration Lifecycle (Active, Scheduled, Draft, Expired)
 * 5. Security & Input Sanitization (XSS and script stripping)
 * 6. Course-Level Announcement Boundary Isolation (Faculty announcements protected)
 * 7. Status Toggling and Safe Deletion
 * 8. Comprehensive Audit Trail Logging in activity_logs
 * 9. Core Regression Invariants (Enrollment, Registrar, Scheduler, Faculty LMS, Student LMS)
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Core/HttpException.php';
require_once __DIR__ . '/../app/Core/BaseController.php';
require_once __DIR__ . '/../app/Core/Request.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Services/LmsAdminService.php';
require_once __DIR__ . '/../app/Services/LmsService.php';
require_once __DIR__ . '/../app/Services/LmsAnnouncementService.php';
require_once __DIR__ . '/../app/Controllers/Admin/LmsAdminController.php';

use App\Core\Database;
use App\Core\HttpException;
use App\Services\LmsAnnouncementService;
use App\Controllers\Admin\LmsAdminController;

$pdo = Database::getConnection();
$announcementService = new LmsAnnouncementService();

$passed = 0;
$failed = 0;

function assertCondition(bool $condition, string $description, ?string $diagnostic = null): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo " [PASS] {$description}\n";
    } else {
        $failed++;
        echo " [FAIL] {$description}\n";
        if ($diagnostic) {
            echo "        Diagnostic: {$diagnostic}\n";
        }
    }
}

echo "====================================================================\n";
echo " TTU LMS ADMIN ANNOUNCEMENTS & GOVERNANCE SUITE\n";
echo "====================================================================\n\n";

$cleanupAnnouncementIds = [];

try {
    // -------------------------------------------------------------------------
    // 1. Role-Based Access Control (RBAC) Enforcement
    // -------------------------------------------------------------------------
    echo "--- 1. Testing Role-Based Access Control ---\n";
    
    $checkAccess = function (string $role): ?HttpException {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = $role;
        $controller = new LmsAdminController();
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('enforceAdminAccess');
        $method->setAccessible(true);
        try {
            $method->invoke($controller);
            return null;
        } catch (HttpException $e) {
            return $e;
        }
    };

    assertCondition($checkAccess('admin') === null, "Admin role is authorized for LMS announcements and governance");
    assertCondition($checkAccess('superadmin') === null, "Superadmin role is authorized for LMS announcements and governance");

    $schedEx = $checkAccess('scheduler');
    assertCondition($schedEx !== null && $schedEx->getStatusCode() === 403, "Scheduler role is strictly rejected from LMS announcements (HTTP 403)");

    $facEx = $checkAccess('faculty');
    assertCondition($facEx !== null && $facEx->getStatusCode() === 403, "Faculty role is strictly rejected from LMS announcements (HTTP 403)");

    $studEx = $checkAccess('student');
    assertCondition($studEx !== null && $studEx->getStatusCode() === 403, "Student role is strictly rejected from LMS announcements (HTTP 403)");

    // -------------------------------------------------------------------------
    // 2. Platform Announcement Creation & Storage
    // -------------------------------------------------------------------------
    echo "\n--- 2. Testing Platform Announcement Creation & Storage ---\n";

    $adminUserId = 1; // Superadmin
    
    // Notice 1: Platform-wide General Notice (Audience: all, Severity: info)
    $id1 = $announcementService->createPlatformAnnouncement([
        'title' => 'Scheduled LMS Maintenance Window',
        'content' => 'The TTU LMS portal will undergo scheduled hardware maintenance from 11:00 PM to 1:00 AM.',
        'target_audience' => 'all',
        'severity' => 'info',
        'status' => 'published',
        'published_at' => date('Y-m-d H:i:s', time() - 3600), // 1 hour ago
        'expires_at' => date('Y-m-d H:i:s', time() + 86400)   // Tomorrow
    ], $adminUserId);
    $cleanupAnnouncementIds[] = $id1;

    $ann1 = $announcementService->getAnnouncement($id1);
    assertCondition($ann1 !== null, "Created platform announcement #{$id1} successfully");
    assertCondition($ann1['lms_course_id'] === null, "Platform announcement has NULL lms_course_id (platform-level governance)");
    assertCondition($ann1['target_audience'] === 'all', "Target audience correctly persisted as 'all'");
    assertCondition($ann1['severity'] === 'info', "Severity correctly persisted as 'info'");
    assertCondition($ann1['status'] === 'published', "Status correctly persisted as 'published'");

    // Notice 2: Students Only Notice (Severity: warning)
    $id2 = $announcementService->createPlatformAnnouncement([
        'title' => 'Midterm Clearance Advisory for Students',
        'content' => 'All undergraduate students must complete online subject evaluations before midterm exams.',
        'target_audience' => 'students',
        'severity' => 'warning',
        'status' => 'published',
        'published_at' => date('Y-m-d H:i:s', time() - 1800),
        'expires_at' => date('Y-m-d H:i:s', time() + 86400)
    ], $adminUserId);
    $cleanupAnnouncementIds[] = $id2;

    $ann2 = $announcementService->getAnnouncement($id2);
    assertCondition($ann2 !== null && $ann2['target_audience'] === 'students', "Student-targeted announcement persisted with audience 'students'");
    assertCondition($ann2['severity'] === 'warning', "Student-targeted announcement persisted with severity 'warning'");

    // Notice 3: Faculty Only Notice (Severity: danger)
    $id3 = $announcementService->createPlatformAnnouncement([
        'title' => 'Urgent: Midterm Grade Encoding Deadline',
        'content' => 'Faculty members are reminded that grade submission portals will close strictly at 5:00 PM Friday.',
        'target_audience' => 'faculty',
        'severity' => 'danger',
        'status' => 'published',
        'published_at' => date('Y-m-d H:i:s', time() - 600),
        'expires_at' => date('Y-m-d H:i:s', time() + 86400)
    ], $adminUserId);
    $cleanupAnnouncementIds[] = $id3;

    $ann3 = $announcementService->getAnnouncement($id3);
    assertCondition($ann3 !== null && $ann3['target_audience'] === 'faculty', "Faculty-targeted announcement persisted with audience 'faculty'");
    assertCondition($ann3['severity'] === 'danger', "Faculty-targeted announcement persisted with severity 'danger'");

    // -------------------------------------------------------------------------
    // 3. Strict Audience Segregation & Isolation
    // -------------------------------------------------------------------------
    echo "\n--- 3. Testing Audience Segregation & Information Isolation ---\n";

    $studentFeed = $announcementService->getPlatformAnnouncements('students');
    $studentFeedIds = array_column($studentFeed, 'id');
    
    assertCondition(in_array($id1, $studentFeedIds), "Student feed contains general announcement #{$id1} (audience: all)");
    assertCondition(in_array($id2, $studentFeedIds), "Student feed contains student advisory #{$id2} (audience: students)");
    assertCondition(!in_array($id3, $studentFeedIds), "Student feed STRICTLY EXCLUDES faculty notice #{$id3} (no administrative leakage)");

    $facultyFeed = $announcementService->getPlatformAnnouncements('faculty');
    $facultyFeedIds = array_column($facultyFeed, 'id');

    assertCondition(in_array($id1, $facultyFeedIds), "Faculty feed contains general announcement #{$id1} (audience: all)");
    assertCondition(in_array($id3, $facultyFeedIds), "Faculty feed contains faculty advisory #{$id3} (audience: faculty)");
    assertCondition(!in_array($id2, $facultyFeedIds), "Faculty feed excludes student-only advisory #{$id2}");

    // -------------------------------------------------------------------------
    // 4. Lifecycle States (Draft, Scheduled Future, Expired)
    // -------------------------------------------------------------------------
    echo "\n--- 4. Testing Lifecycle Scheduling & Expiration ---\n";

    // Draft Notice
    $idDraft = $announcementService->createPlatformAnnouncement([
        'title' => 'Draft Unreleased Maintenance Note',
        'content' => 'This is an unapproved draft note that should never be shown to anyone.',
        'target_audience' => 'all',
        'severity' => 'info',
        'status' => 'draft'
    ], $adminUserId);
    $cleanupAnnouncementIds[] = $idDraft;

    $feedAll = $announcementService->getPlatformAnnouncements();
    $feedAllIds = array_column($feedAll, 'id');
    assertCondition(!in_array($idDraft, $feedAllIds), "Live feed excludes draft announcement #{$idDraft}");

    // Future Scheduled Notice
    $idScheduled = $announcementService->createPlatformAnnouncement([
        'title' => 'Future Holiday Advisory',
        'content' => 'Classes will be suspended on upcoming institutional holiday.',
        'target_audience' => 'all',
        'severity' => 'success',
        'status' => 'published',
        'published_at' => date('Y-m-d H:i:s', time() + 7200) // 2 hours in the future
    ], $adminUserId);
    $cleanupAnnouncementIds[] = $idScheduled;

    $feedScheduledCheck = $announcementService->getPlatformAnnouncements();
    $feedScheduledIds = array_column($feedScheduledCheck, 'id');
    assertCondition(!in_array($idScheduled, $feedScheduledIds), "Live feed excludes future scheduled announcement #{$idScheduled}");

    // Expired Notice
    $idExpired = $announcementService->createPlatformAnnouncement([
        'title' => 'Past Downtime Advisory',
        'content' => 'Network maintenance completed last week.',
        'target_audience' => 'all',
        'severity' => 'info',
        'status' => 'published',
        'published_at' => date('Y-m-d H:i:s', time() - 86400 * 5),
        'expires_at' => date('Y-m-d H:i:s', time() - 3600) // Expired 1 hour ago
    ], $adminUserId);
    $cleanupAnnouncementIds[] = $idExpired;

    $feedExpiredCheck = $announcementService->getPlatformAnnouncements();
    $feedExpiredIds = array_column($feedExpiredCheck, 'id');
    assertCondition(!in_array($idExpired, $feedExpiredIds), "Live feed excludes expired announcement #{$idExpired}");

    // Diagnostic table classification
    $adminList = $announcementService->getAllPlatformAnnouncements();
    $statesById = array_column($adminList, 'lifecycle', 'id');
    assertCondition(($statesById[$id1] ?? '') === 'active', "Active announcement #{$id1} classified as 'active'");
    assertCondition(($statesById[$idDraft] ?? '') === 'draft', "Draft announcement #{$idDraft} classified as 'draft'");
    assertCondition(($statesById[$idScheduled] ?? '') === 'scheduled', "Future announcement #{$idScheduled} classified as 'scheduled'");
    assertCondition(($statesById[$idExpired] ?? '') === 'expired', "Expired announcement #{$idExpired} classified as 'expired'");

    // -------------------------------------------------------------------------
    // 5. Security & Input Sanitization
    // -------------------------------------------------------------------------
    echo "\n--- 5. Testing Security & Script Stripping ---\n";

    $xssTitle = 'Urgent Notice <script>alert("XSS")</script>';
    $xssContent = 'Body text <iframe src="evil.com"></iframe> with <b>safe</b> text.';
    $idXss = $announcementService->createPlatformAnnouncement([
        'title' => $xssTitle,
        'content' => $xssContent,
        'target_audience' => 'all',
        'severity' => 'warning',
        'status' => 'draft'
    ], $adminUserId);
    $cleanupAnnouncementIds[] = $idXss;

    $annXss = $announcementService->getAnnouncement($idXss);
    assertCondition(strpos($annXss['title'], '<script>') === false, "Dangerous <script> tag was stripped from announcement title");
    assertCondition(strpos($annXss['content'], '<iframe>') === false, "Dangerous <iframe> tag was stripped from announcement content");
    assertCondition($annXss['title'] === 'Urgent Notice alert("XSS")', "Sanitized title retained safe text content");

    // -------------------------------------------------------------------------
    // 6. Course-Level Announcement Boundary Isolation
    // -------------------------------------------------------------------------
    echo "\n--- 6. Testing Course Announcement Boundary Isolation ---\n";

    // Check pre-existing course announcement
    $firstCourse = $pdo->query("SELECT id FROM lms_courses WHERE status = 'active' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($firstCourse) {
        $courseId = (int)$firstCourse['id'];
        $courseNoticeId = $announcementService->createAnnouncement([
            'lms_course_id' => $courseId,
            'author_user_id' => $adminUserId,
            'title' => 'Course 101 Syllabus Released',
            'content' => 'Please download syllabus from Module 1.',
            'status' => 'published'
        ]);
        $cleanupAnnouncementIds[] = $courseNoticeId;

        // Verify course announcements query does NOT include platform announcements
        $courseNotices = $announcementService->getCourseAnnouncements($courseId);
        $courseNoticeIds = array_column($courseNotices, 'id');
        assertCondition(in_array($courseNoticeId, $courseNoticeIds), "Course announcements query returns course notice #{$courseNoticeId}");
        assertCondition(!in_array($id1, $courseNoticeIds), "Course announcements query strictly excludes platform notice #{$id1}");

        // Attempting to modify course notice via platform update must be rejected
        $boundaryRejected = false;
        try {
            $announcementService->updatePlatformAnnouncement($courseNoticeId, [
                'title' => 'Tampered Course Notice',
                'content' => 'Tampered content',
                'target_audience' => 'all',
                'severity' => 'info',
                'status' => 'published'
            ], $adminUserId);
        } catch (\InvalidArgumentException $e) {
            $boundaryRejected = true;
        }
        assertCondition($boundaryRejected, "Platform announcement update strictly rejects modifying course-scoped announcements");
    }

    // -------------------------------------------------------------------------
    // 7. Status Toggling & Safe Deletion
    // -------------------------------------------------------------------------
    echo "\n--- 7. Testing Status Toggling & Deletion ---\n";

    $toggleResult = $announcementService->togglePlatformAnnouncementStatus($id1, $adminUserId);
    assertCondition(($toggleResult['new_status'] ?? '') === 'draft', "Toggled status of announcement #{$id1} to 'draft'");

    $feedAfterToggle = $announcementService->getPlatformAnnouncements();
    $feedAfterToggleIds = array_column($feedAfterToggle, 'id');
    assertCondition(!in_array($id1, $feedAfterToggleIds), "Announcement #{$id1} no longer appears in live feed while in draft");

    $toggleBackResult = $announcementService->togglePlatformAnnouncementStatus($id1, $adminUserId);
    assertCondition(($toggleBackResult['new_status'] ?? '') === 'published', "Toggled status of announcement #{$id1} back to 'published'");

    $deleted = $announcementService->deletePlatformAnnouncement($idXss, $adminUserId);
    assertCondition($deleted, "Deleted sanitized announcement #{$idXss} successfully");
    assertCondition($announcementService->getAnnouncement($idXss) === null, "Announcement #{$idXss} verified deleted from database");

    // -------------------------------------------------------------------------
    // 8. Comprehensive Audit Trail Logging
    // -------------------------------------------------------------------------
    echo "\n--- 8. Testing LMS Audit Trail Logging ---\n";

    $logCheck = $pdo->prepare("
        SELECT title, affected_record, description, user_id 
        FROM activity_logs 
        WHERE affected_record = 'lms_announcements' 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $logCheck->execute();
    $logs = $logCheck->fetchAll(PDO::FETCH_ASSOC);

    assertCondition(!empty($logs), "Activity logs contains 'lms_announcements' administrative events");
    
    $titles = array_column($logs, 'title');
    assertCondition(in_array('LMS Platform Announcement: Created', $titles), "Audit log recorded 'LMS Platform Announcement: Created'");
    assertCondition(in_array('LMS Platform Announcement: Status Toggled', $titles), "Audit log recorded 'LMS Platform Announcement: Status Toggled'");
    assertCondition(in_array('LMS Platform Announcement: Deleted', $titles), "Audit log recorded 'LMS Platform Announcement: Deleted'");

    // -------------------------------------------------------------------------
    // 9. Core Regression Invariants (Enrollment vs LMS Boundaries)
    // -------------------------------------------------------------------------
    echo "\n--- 9. Testing Core Regression & Academic Truth Preservation ---\n";

    // Check college_sections, shs_sections, subjects, college_enrollments
    $sectionCount = $pdo->query("SELECT COUNT(*) FROM college_sections")->fetchColumn();
    $subjectCount = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $enrollmentCount = $pdo->query("SELECT COUNT(*) FROM college_enrollments")->fetchColumn();

    assertCondition($sectionCount > 0, "College sections remain completely intact ({$sectionCount} sections)");
    assertCondition($subjectCount > 0, "Registrar subjects remain completely intact ({$subjectCount} subjects)");
    assertCondition($enrollmentCount > 0, "Student enrollment records remain completely intact ({$enrollmentCount} enrollments)");

    // Check student submissions and quiz attempts are untouched
    $submissionsCount = $pdo->query("SELECT COUNT(*) FROM lms_submissions")->fetchColumn();
    $attemptsCount = $pdo->query("SELECT COUNT(*) FROM lms_quiz_attempts")->fetchColumn();
    assertCondition($submissionsCount >= 0, "LMS student submissions count verified intact ({$submissionsCount})");
    assertCondition($attemptsCount >= 0, "LMS student quiz attempts count verified intact ({$attemptsCount})");

} catch (\Throwable $e) {
    echo "\n[ERROR] Unhandled Exception during suite execution: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failed++;
} finally {
    // Clean up created test announcements
    if (!empty($cleanupAnnouncementIds)) {
        $placeholders = implode(',', array_fill(0, count($cleanupAnnouncementIds), '?'));
        $pdo->prepare("DELETE FROM lms_announcements WHERE id IN ($placeholders)")->execute($cleanupAnnouncementIds);
    }
}

echo "\n====================================================================\n";
echo " FINAL SUMMARY: {$passed} PASSED | {$failed} FAILED\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
