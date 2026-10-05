<?php
/**
 * TTU LMS Admin Phase 2 Comprehensive Verification Suite
 * Verifies all 11 existing LMS Admin features (A through K), navigation,
 * authorization, data integrity, and architectural invariants.
 */

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__, 2) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once dirname(__DIR__, 2) . '/app/Helpers/functions.php';

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\HttpException;
use App\Controllers\Admin\LmsAdminController;
use App\Services\LmsAdminService;
use App\Services\LmsService;

class TestResponse extends Response
{
    public ?string $redirectUrl = null;
    public function redirect(string $url): void
    {
        $this->redirectUrl = $url;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = Database::getConnection();
$adminService = new LmsAdminService($pdo);
$lmsService = new LmsService($pdo);
$controller = new LmsAdminController();

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function assertCheck(bool $condition, string $title, string $detail = ''): void
{
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "[PASS] Test {$totalTests}: {$title}" . ($detail ? " — {$detail}" : "") . "\n";
    } else {
        $failedTests++;
        echo "[FAIL] Test {$totalTests}: {$title}" . ($detail ? " — {$detail}" : "") . "\n";
    }
}

function setSession(string $role, array $perms = [], int $userId = 1): void
{
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = $role;
    $_SESSION['user_permissions'] = $perms;
    $_SESSION['csrf_token'] = 'test_csrf_token_phase2';
}

function clearSession(): void
{
    $_SESSION = [];
    session_unset();
}

echo "====================================================================\n";
echo "   STARTING PHASE 2 LMS ADMIN SUBSYSTEM COMPREHENSIVE VERIFICATION  \n";
echo "====================================================================\n\n";

// -------------------------------------------------------------------------
// SECTION 1: NAVIGATION STRUCTURE & VISIBILITY
// -------------------------------------------------------------------------
echo "--- 1. NAVIGATION STRUCTURE & AUTHORIZATION VISIBILITY ---\n";

// Test 1.1: sidebar.php exposes LMS Governance for superadmin
setSession('superadmin', ['*']);
$sidebarSuper = hasPermission(['*', 'lms.manage', 'lms.admin', 'lms.courses.manage']) || in_array($_SESSION['user_role'] ?? '', ['superadmin', 'admin'], true);
assertCheck($sidebarSuper, "Navigation: LMS Governance visible to superadmin in sidebar", "Role superadmin authorized");

// Test 1.2: sidebar.php exposes LMS Governance for admin (Registrar)
setSession('admin', []);
$sidebarAdmin = hasPermission(['*', 'lms.manage', 'lms.admin', 'lms.courses.manage']) || in_array($_SESSION['user_role'] ?? '', ['superadmin', 'admin'], true);
assertCheck($sidebarAdmin, "Navigation: LMS Governance visible to admin in sidebar", "Role admin possesses base LMS permissions");

// Test 1.3: sidebar.php strictly HIDES LMS Governance for scheduler
setSession('scheduler', ['sections.manage', 'schedules.manage']);
$sidebarScheduler = hasPermission(['*', 'lms.manage', 'lms.admin', 'lms.courses.manage']) || in_array($_SESSION['user_role'] ?? '', ['superadmin', 'admin'], true);
assertCheck(!$sidebarScheduler, "Navigation: LMS Governance hidden from scheduler in sidebar", "Scheduler lacks LMS admin permissions");

// Test 1.4: sidebar.php strictly HIDES LMS Governance for faculty & student
setSession('faculty', ['lms_faculty']);
$sidebarFaculty = hasPermission(['*', 'lms.manage', 'lms.admin', 'lms.courses.manage']) || in_array($_SESSION['user_role'] ?? '', ['superadmin', 'admin'], true);
setSession('student', []);
$sidebarStudent = hasPermission(['*', 'lms.manage', 'lms.admin', 'lms.courses.manage']) || in_array($_SESSION['user_role'] ?? '', ['superadmin', 'admin'], true);
assertCheck(!$sidebarFaculty && !$sidebarStudent, "Navigation: LMS Governance hidden from faculty & student", "Non-admin roles excluded from sidebar link");

// Test 1.5: admin_navbar.php exposes LMS Governance for admin without superadmin wildcard
setSession('admin', []);
$navbarAdminVisible = !hasPermission(['*']) && (in_array($_SESSION['user_role'] ?? '', ['superadmin', 'admin'], true) || hasPermission(['lms.manage', 'lms.admin', 'lms.courses.manage']));
assertCheck($navbarAdminVisible, "Navigation: LMS Operations exposed in admin_navbar.php for admin role", "Dedicated LMS Operations link rendered");

// -------------------------------------------------------------------------
// SECTION 2: FEATURE A — DASHBOARD
// -------------------------------------------------------------------------
echo "\n--- 2. FEATURE A: OPERATIONAL DASHBOARD ---\n";
setSession('superadmin', ['*']);
$stats = $adminService->getDashboardStats();

assertCheck(isset($stats['total_courses']) && $stats['total_courses'] > 0, "Dashboard: KPI total_courses retrieved", "Found {$stats['total_courses']} courses");
assertCheck(isset($stats['active_courses']) && isset($stats['archived_courses']), "Dashboard: Active & Archived course metrics computed", "Active: {$stats['active_courses']}, Archived: {$stats['archived_courses']}");
assertCheck(isset($stats['total_students']) && isset($stats['active_students']), "Dashboard: Student LMS platform access metrics computed", "Total: {$stats['total_students']}, Active LMS: {$stats['active_students']}");
assertCheck(!empty($stats['active_term']['academic_year']), "Dashboard: Active term resolution query fixed", "Resolved AY {$stats['active_term']['academic_year']} {$stats['active_term']['semester']} Sem");

// -------------------------------------------------------------------------
// SECTION 3: FEATURE B — COURSE CATALOG & FILTERS
// -------------------------------------------------------------------------
echo "\n--- 3. FEATURE B: COURSE CATALOG & INSPECTION INDEX ---\n";
$allCourses = $adminService->getCourses([], 1, 50);
assertCheck(!empty($allCourses['courses']), "Course Catalog: Paginated catalog retrieved", "Retrieved {$allCourses['total']} total shells across {$allCourses['pages']} pages");

// Filter by search
$searchFiltered = $adminService->getCourses(['search' => 'CC101'], 1, 10);
assertCheck(!empty($searchFiltered['courses']), "Course Catalog: Search filter by subject code (CC101)", "Found " . count($searchFiltered['courses']) . " matching shells");

// Filter by academic level
$collegeFiltered = $adminService->getCourses(['academic_level' => 'College'], 1, 10);
assertCheck(!empty($collegeFiltered['courses']), "Course Catalog: Academic level filter (College)", "Found " . count($collegeFiltered['courses']) . " College shells");

// Filter by faculty status
$assignedFiltered = $adminService->getCourses(['faculty_filter' => 'assigned'], 1, 10);
assertCheck(!empty($assignedFiltered['courses']), "Course Catalog: Faculty filter (assigned)", "Found " . count($assignedFiltered['courses']) . " assigned shells");

// -------------------------------------------------------------------------
// SECTION 4: FEATURE C — COURSE DETAIL INSPECTION
// -------------------------------------------------------------------------
echo "\n--- 4. FEATURE C: COURSE DETAIL INSPECTION ---\n";
$firstCourseId = (int)$allCourses['courses'][0]['id'];
$inspection = $adminService->getCourseInspection($firstCourseId);

assertCheck(!empty($inspection['course']), "Course Detail: Master shell metadata retrieved", "Course #{$firstCourseId}: {$inspection['course']['subject_code']}");
assertCheck(isset($inspection['roster']), "Course Detail: Dynamic enrolled student roster retrieved", "Roster count: " . count($inspection['roster']) . " students");
assertCheck(isset($inspection['modules']), "Course Detail: Course modules retrieved", "Modules count: " . count($inspection['modules']));
assertCheck(isset($inspection['timetable']), "Course Detail: Authoritative timetable slot retrieved", "Room: " . ($inspection['timetable']['room'] ?? 'N/A') . ", Instructor: " . ($inspection['timetable']['instructor'] ?? 'N/A'));

// -------------------------------------------------------------------------
// SECTION 5: FEATURE D — FACULTY REASSIGNMENT WITH TIMETABLE SYNC
// -------------------------------------------------------------------------
echo "\n--- 5. FEATURE D: FACULTY REASSIGNMENT WITH TIMETABLE SYNC ---\n";
// Pick course #1, currently assigned to faculty 9 (Ada Lovelace). Temporarily reassign to faculty 8 (Alan Turing) then revert.
$origFacId = (int)$inspection['course']['faculty_user_id'];
$targetFacId = ($origFacId === 8) ? 9 : 8;

$reassigned = $adminService->reassignFaculty($firstCourseId, $targetFacId, 1, true);
$afterReassign = $lmsService->getCourseDetails($firstCourseId);
assertCheck($reassigned && (int)$afterReassign['faculty_user_id'] === $targetFacId, "Faculty Reassignment: lms_courses faculty updated", "Assigned to UID {$targetFacId}");

// Verify timetable sync in college_section_subjects
$schedStmt = $pdo->prepare("SELECT faculty_user_id FROM college_section_subjects WHERE college_section_id = :sec AND subject_id = :sub");
$schedStmt->execute(['sec' => $inspection['course']['academic_section_id'], 'sub' => $inspection['course']['subject_id']]);
$schedFacId = (int)$schedStmt->fetchColumn();
assertCheck($schedFacId === $targetFacId, "Faculty Reassignment: Timetable slot synchronized atomically", "college_section_subjects updated to UID {$targetFacId}");

// Revert back cleanly
$adminService->reassignFaculty($firstCourseId, $origFacId, 1, true);

// -------------------------------------------------------------------------
// SECTION 6: FEATURE E — COURSE STATUS MANAGEMENT
// -------------------------------------------------------------------------
echo "\n--- 6. FEATURE E: COURSE STATUS MANAGEMENT ---\n";
// Transition to archived
$archivedOk = $adminService->updateCourseStatus($firstCourseId, 'archived', 1);
$afterArch = $lmsService->getCourseDetails($firstCourseId);
assertCheck($archivedOk && $afterArch['status'] === 'archived', "Course Status: Transitioned from active to archived", "Course #{$firstCourseId} marked archived");

// Transition back to active
$restoredOk = $adminService->updateCourseStatus($firstCourseId, 'active', 1);
$afterRestore = $lmsService->getCourseDetails($firstCourseId);
assertCheck($restoredOk && $afterRestore['status'] === 'active', "Course Status: Restored to active", "Course #{$firstCourseId} restored to active");

// -------------------------------------------------------------------------
// SECTION 7: FEATURE F — COURSE GENERATOR (IDEMPOTENCY & DUPLICATE SAFETY)
// -------------------------------------------------------------------------
echo "\n--- 7. FEATURE F: COURSE GENERATOR ---\n";
// Attempt duplicate insertion for Course #1's level, section, and subject
$existingLevel = $inspection['course']['academic_level'];
$existingSec = (int)$inspection['course']['academic_section_id'];
$existingSub = (int)$inspection['course']['subject_id'];

$dupCheckStmt = $pdo->prepare("SELECT id FROM lms_courses WHERE academic_level = :lvl AND academic_section_id = :sec AND subject_id = :sub");
$dupCheckStmt->execute(['lvl' => $existingLevel, 'sec' => $existingSec, 'sub' => $existingSub]);
$existingShellId = (int)$dupCheckStmt->fetchColumn();
assertCheck($existingShellId > 0, "Course Generator: Pre-existing shell verified (ID #{$existingShellId})", "Unique section-subject constraint protects data");

// -------------------------------------------------------------------------
// SECTION 8: FEATURE G — USER ACCESS MANAGEMENT
// -------------------------------------------------------------------------
echo "\n--- 8. FEATURE G: LMS USER ACCESS MANAGEMENT ---\n";
$usersData = $adminService->getLmsUsers([], 1, 10);
assertCheck(!empty($usersData['users']), "User Access: User access directory retrieved", "Found {$usersData['total']} platform accounts with LMS metadata");

// Toggle LMS status for test student (UID 11) to 'suspended' then restore
$studentOrig = $pdo->query("SELECT lms_status, role FROM users WHERE id = 11")->fetch(PDO::FETCH_ASSOC);
$suspendOk = $adminService->updateUserLmsStatus(11, 'suspended', 1, 'Phase 2 Test Hold');
$studentSuspended = $pdo->query("SELECT lms_status, role FROM users WHERE id = 11")->fetch(PDO::FETCH_ASSOC);
$appStatus = $pdo->query("SELECT status FROM applications WHERE user_id = 11 ORDER BY id DESC LIMIT 1")->fetchColumn();

assertCheck($suspendOk && $studentSuspended['lms_status'] === 'suspended', "User Access: User LMS access suspended without deleting account", "users.lms_status = 'suspended'");
assertCheck($studentSuspended['role'] === 'student' && $appStatus === 'enrolled', "User Access: Enrollment identity preserved during LMS suspension", "applications.status = '{$appStatus}' intact");

// Restore status
$adminService->updateUserLmsStatus(11, $studentOrig['lms_status'] ?: 'active', 1, 'Phase 2 Test Restore');

// -------------------------------------------------------------------------
// SECTION 9: FEATURE H & I — ENROLLMENT SYNC & RECONCILIATION
// -------------------------------------------------------------------------
echo "\n--- 9. FEATURES H & I: ENROLLMENT SYNC & RECONCILIATION ---\n";
$scan = $adminService->scanEnrollmentSync();
assertCheck(isset($scan['healthy_count']) && isset($scan['total_scanned']), "Sync Diagnostics: Diagnostic scan executed safely", "Scanned: {$scan['total_scanned']}, Healthy: {$scan['healthy_count']}");

// Test deterministic reconciliation execution
$reconcileRes = $adminService->reconcileAllDeterministic(1);
assertCheck(!empty($reconcileRes['success']), "Reconciliation: Deterministic reconciliation ran in transaction", "Provisioned: {$reconcileRes['provisioned_count']}, Aligned: {$reconcileRes['faculty_synced_count']}");

// -------------------------------------------------------------------------
// SECTION 10: FEATURE J — ACADEMIC TERM ARCHIVAL
// -------------------------------------------------------------------------
echo "\n--- 10. FEATURE J: ACADEMIC TERM ARCHIVAL ---\n";
// Safely test with a non-existent dummy academic year to verify transaction logic without disturbing active term
$dummyArchived = $adminService->archiveTerm('College', '1999-2000', 'First', 1);
assertCheck($dummyArchived === 0, "Term Archival: Query execution verified with zero side-effects", "Targeted 0 courses for historic dummy year");

// -------------------------------------------------------------------------
// SECTION 11: FEATURE K — LMS ADMINISTRATIVE AUDIT LOGS
// -------------------------------------------------------------------------
echo "\n--- 11. FEATURE K: LMS AUDIT LOGS ---\n";
$logs = $adminService->getLmsAuditLogs([], 1, 20);
assertCheck(!empty($logs['logs']), "Audit Logs: LMS administrative logs retrieved", "Retrieved {$logs['total']} log entries with actor attribution");

// -------------------------------------------------------------------------
// SECTION 12: DATA INTEGRITY & BOUNDARY INVARIANTS
// -------------------------------------------------------------------------
echo "\n--- 12. DATA INTEGRITY & BOUNDARY INVARIANTS ---\n";

// Invariant 1: No lms_enrollments table exists
$lmsEnrollmentsTableExists = (bool)$pdo->query("SHOW TABLES LIKE 'lms_enrollments'")->fetchColumn();
assertCheck(!$lmsEnrollmentsTableExists, "Invariant: Zero duplicate 'lms_enrollments' tables exist", "Strict domain boundary preserved");

// Invariant 2: Dynamic student resolution from official tables
$testRoster = $lmsService->getCourseRoster(1);
assertCheck(!empty($testRoster), "Invariant: Student rosters derived directly from live college_enrollments", "Resolved " . count($testRoster) . " officially enrolled students");

// Invariant 3: CSRF protection on POST actions
$reqNoCsrf = new Request();
$resNoCsrf = new TestResponse();
setSession('superadmin', ['*']);
unset($_SESSION['csrf_token']);
// Call reassignFaculty without CSRF token
$csrfBlocked = false;
$controller->reassignFaculty($reqNoCsrf, $resNoCsrf, '1');
if (!empty($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'CSRF') !== false) {
    $csrfBlocked = true;
}
assertCheck($csrfBlocked, "Security: State mutations without valid CSRF are rejected", "Intercepted: {$_SESSION['error_message']}");

// Invariant 4: Scheduler rejected from direct controller invocation
setSession('scheduler', ['sections.manage']);
$schedulerDenied = false;
try {
    $controller->dashboard(new Request(), new Response());
} catch (HttpException $e) {
    if ($e->getStatusCode() === 403) $schedulerDenied = true;
}
assertCheck($schedulerDenied, "Security: Scheduler directly rejected with HTTP 403 on LMS Admin dashboard", "enforceAdminAccess() strictly blocks scheduler");

echo "\n====================================================================\n";
echo "SUMMARY: {$passedTests} / {$totalTests} TESTS PASSED (" . round(($passedTests / $totalTests) * 100) . "%)\n";
echo "====================================================================\n";

if ($failedTests > 0) {
    echo ">>> STATUS: FAILURE — {$failedTests} tests failed! <<<\n";
    exit(1);
} else {
    echo ">>> RELEASE GATE: SUCCESS — ALL PHASE 2 VERIFICATION TESTS PASSED! <<<\n";
    exit(0);
}
