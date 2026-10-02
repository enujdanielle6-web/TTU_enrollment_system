<?php
namespace App\Controllers\Admin;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\HttpException;
use App\Services\LmsAdminService;
use App\Services\LmsService;
use App\Services\LmsAnnouncementService;
use PDO;
use Exception;

class LmsAdminController extends BaseController
{
    private LmsAdminService $adminService;
    private LmsService $lmsService;
    private LmsAnnouncementService $announcementService;

    public function __construct()
    {
        $this->adminService = new LmsAdminService();
        $this->lmsService = new LmsService();
        $this->announcementService = new LmsAnnouncementService();
    }

    /**
     * Enforces strict administrative authorization for all LMS Admin endpoints.
     * Only superadmin, LMS admin, or accounts with explicit LMS administrative permissions
     * (lms.manage, lms.admin, lms.courses.manage) may access LMS Admin.
     * Registrar Office, Scheduler, and unprivileged roles are strictly denied.
     */
    private function enforceAdminAccess(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $userRole = $_SESSION['user_role'] ?? '';
        $userDept = $_SESSION['user_department'] ?? '';
        $customPerms = $_SESSION['user_permissions'] ?? [];

        // 1. Superadmin has universal administrative governance
        if ($userRole === 'superadmin') {
            return;
        }

        // 2. Strict Domain Isolation: The Registrar account must NOT handle LMS Administration
        $isRegistrarPerm = is_array($customPerms) && (in_array('manage_registrar', $customPerms, true) || in_array('manage_students', $customPerms, true));
        $hasExplicitLmsAdmin = is_array($customPerms) && (in_array('lms.admin', $customPerms, true) || in_array('lms_admin', $customPerms, true));

        if ($userDept === 'Registrar Office' || ($isRegistrarPerm && !$hasExplicitLmsAdmin)) {
            throw new HttpException(403, "Access Denied. Registrar accounts cannot manage LMS Governance. LMS operations must be handled by an LMS Administrator on the LMS side.");
        }

        // 3. Role lms_admin or general admin with LMS mandate
        if ($userRole === 'lms_admin') {
            return;
        }

        // 4. General admin (outside of Registrar Office) has institutional LMS management authority
        if ($userRole === 'admin') {
            return;
        }

        // 5. Explicit LMS administrative privileges or wildcard
        if (function_exists('hasPermission') && (
            hasPermission('*') ||
            hasPermission('lms.manage') ||
            hasPermission('lms.admin') ||
            hasPermission('lms.courses.manage')
        )) {
            return;
        }

        if (function_exists('requirePermission')) {
            requirePermission(['lms.manage', 'lms.admin', 'lms.courses.manage']);
        } else {
            throw new HttpException(403, "Access Denied. LMS Administrative access required.");
        }
    }

    /**
     * Verifies CSRF token on state-changing requests.
     */
    private function validateCsrfToken(Request $request): bool
    {
        $token = $request->input('csrf_token') ?? '';
        return (!empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token));
    }

    /**
     * LMS Admin Operational Dashboard.
     */
    public function dashboard(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        $stats = $this->adminService->getDashboardStats();
        $catalogResult = $this->adminService->getCourses(['status' => 'active'], 1, 6);
        $recentCourses = $catalogResult['courses'] ?? [];
        $conflictSummary = $this->adminService->getConflictDiagnostics();
        $platformNotices = $this->announcementService->getPlatformAnnouncements(null, true);
        $pageTitle = 'LMS Administration Dashboard - TTU';

        return $this->render('lms/admin/dashboard', get_defined_vars());
    }

    /**
     * LMS Course Catalog & Inspection Index.
     */
    public function courses(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        $page = max(1, (int)$request->input('page', 1));
        $filters = [
            'search' => trim((string)$request->input('search', '')),
            'academic_level' => trim((string)$request->input('academic_level', '')),
            'status' => trim((string)$request->input('status', '')),
            'faculty_filter' => trim((string)$request->input('faculty_filter', ''))
        ];

        $catalog = $this->adminService->getCourses($filters, $page, 15);
        $courses = $catalog['courses'];
        $totalPages = $catalog['pages'];
        $totalCourses = $catalog['total'];
        $currentPage = $catalog['current_page'];

        $pageTitle = 'LMS Course Catalog - Administrator';

        return $this->render('lms/admin/courses/index', get_defined_vars());
    }

    /**
     * Deep inspection of a single LMS Course Shell.
     */
    public function courseDetail(Request $request, Response $response, string $id)
    {
        $this->enforceAdminAccess();

        $courseId = (int)$id;
        $inspection = $this->adminService->getCourseInspection($courseId);

        if (!$inspection) {
            $_SESSION['error_message'] = "Course #{$courseId} not found.";
            $response->redirect('/sia/lms/admin/courses');
            return;
        }

        $course = $inspection['course'];
        $timetable = $inspection['timetable'];
        $modules = $inspection['modules'];
        $assignments = $inspection['assignments'];
        $quizzes = $inspection['quizzes'];
        $roster = $inspection['roster'];
        $availableFaculty = $inspection['available_faculty'];

        $pageTitle = "Course #{$courseId} Inspection - {$course['subject_code']}";

        return $this->render('lms/admin/courses/detail', get_defined_vars());
    }

    /**
     * Reassigns course instructor and synchronizes with authoritative schedule.
     */
    public function reassignFaculty(Request $request, Response $response, string $id)
    {
        $this->enforceAdminAccess();

        if (!$this->validateCsrfToken($request)) {
            $_SESSION['error_message'] = 'Invalid or expired CSRF token.';
            $response->redirect("/sia/lms/admin/courses/{$id}");
            return;
        }

        $courseId = (int)$id;
        $facultyUserId = $request->input('faculty_user_id') !== '' ? (int)$request->input('faculty_user_id') : null;
        $syncAuthoritative = !empty($request->input('sync_schedule', 1));
        $adminUserId = (int)($_SESSION['user_id'] ?? 0);

        $success = $this->adminService->reassignFaculty($courseId, $facultyUserId, $adminUserId, $syncAuthoritative);

        if ($success) {
            $_SESSION['success_message'] = "Course #{$courseId} instructor successfully updated and synchronized.";
        } else {
            $_SESSION['error_message'] = "Failed to reassign course instructor.";
        }

        $response->redirect("/sia/lms/admin/courses/{$courseId}");
    }

    /**
     * Updates course status (active | archived).
     */
    public function updateCourseStatus(Request $request, Response $response, string $id)
    {
        $this->enforceAdminAccess();

        if (!$this->validateCsrfToken($request)) {
            $_SESSION['error_message'] = 'Invalid or expired CSRF token.';
            $response->redirect("/sia/lms/admin/courses/{$id}");
            return;
        }

        $courseId = (int)$id;
        $status = (string)$request->input('status', 'active');
        $adminUserId = (int)($_SESSION['user_id'] ?? 0);

        $success = $this->adminService->updateCourseStatus($courseId, $status, $adminUserId);

        if ($success) {
            $_SESSION['success_message'] = "Course #{$courseId} status changed to '{$status}'.";
        } else {
            $_SESSION['error_message'] = "Failed to update course status.";
        }

        $response->redirect("/sia/lms/admin/courses/{$courseId}");
    }

    /**
     * LMS User Management Index.
     */
    public function users(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        $page = max(1, (int)$request->input('page', 1));
        $filters = [
            'role' => trim((string)$request->input('role', '')),
            'lms_status' => trim((string)$request->input('lms_status', '')),
            'search' => trim((string)$request->input('search', ''))
        ];

        $data = $this->adminService->getLmsUsers($filters, $page, 20);
        $users = $data['users'];
        $totalPages = $data['pages'];
        $totalUsers = $data['total'];
        $currentPage = $data['current_page'];

        $pageTitle = 'LMS User Access Management - Administrator';

        return $this->render('lms/admin/users/index', get_defined_vars());
    }

    /**
     * Modifies LMS-specific user status (active | suspended | inactive).
     */
    public function updateUserStatus(Request $request, Response $response, string $id)
    {
        $this->enforceAdminAccess();

        if (!$this->validateCsrfToken($request)) {
            $_SESSION['error_message'] = 'Invalid or expired CSRF token.';
            $response->redirect('/sia/lms/admin/users');
            return;
        }

        $userId = (int)$id;
        $status = (string)$request->input('lms_status', 'active');
        $reason = (string)$request->input('reason', '');
        $adminUserId = (int)($_SESSION['user_id'] ?? 0);

        $success = $this->adminService->updateUserLmsStatus($userId, $status, $adminUserId, $reason);

        if ($success) {
            $_SESSION['success_message'] = "User #{$userId} LMS status updated to '{$status}'.";
        } else {
            $_SESSION['error_message'] = "Failed to update user LMS status.";
        }

        $response->redirect('/sia/lms/admin/users');
    }

    /**
     * Enrollment Synchronization & Reconciliation Diagnostic View.
     */
    public function sync(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        $syncReport = $this->adminService->scanEnrollmentSync();
        $conflictReport = $this->adminService->getConflictDiagnostics();
        $pageTitle = 'Enrollment ↔ LMS Synchronization & Conflict Resolution - Administrator';

        return $this->render('lms/admin/sync/index', get_defined_vars());
    }

    /**
     * Executes safe, deterministic reconciliation of detected synchronization discrepancies.
     */
    public function reconcile(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        if (!$this->validateCsrfToken($request)) {
            $_SESSION['error_message'] = 'Invalid or expired CSRF token.';
            $response->redirect('/sia/lms/admin/sync');
            return;
        }

        $adminUserId = (int)($_SESSION['user_id'] ?? 0);
        $result = $this->adminService->reconcileAllDeterministic($adminUserId);

        if (!empty($result['success'])) {
            $_SESSION['success_message'] = "Reconciliation completed: Provisioned {$result['provisioned_count']} missing shells, Aligned {$result['faculty_synced_count']} faculty assignments.";
        } else {
            $_SESSION['error_message'] = "Reconciliation encountered an error: " . ($result['error'] ?? 'Unknown error');
        }

        $response->redirect('/sia/lms/admin/sync');
    }

    /**
     * Resolves an LMS course shell conflict (duplicate or orphan) using safe administrative actions.
     */
    public function resolveConflict(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        if (!$this->validateCsrfToken($request)) {
            $_SESSION['error_message'] = 'Security validation failed: Invalid or expired CSRF token.';
            $response->redirect('/sia/lms/admin/sync');
            return;
        }

        $action = (string)$request->input('resolution_action', '');
        $courseId = (int)$request->input('course_id', 0);
        $notes = trim((string)$request->input('notes', ''));
        $facultyId = $request->input('faculty_user_id') ? (int)$request->input('faculty_user_id') : null;
        $adminUserId = (int)($_SESSION['user_id'] ?? 0);

        if ($courseId <= 0 || empty($action)) {
            $_SESSION['error_message'] = 'Invalid conflict resolution request. Missing course ID or action.';
            $response->redirect('/sia/lms/admin/sync');
            return;
        }

        try {
            $result = $this->adminService->resolveConflict($action, $courseId, $adminUserId, [
                'notes' => $notes,
                'faculty_user_id' => $facultyId
            ]);
            $_SESSION['success_message'] = $result['message'];
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        $response->redirect('/sia/lms/admin/sync');
    }

    /**
     * Academic Term Archival View.
     */
    public function archive(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        $filters = ['status' => 'archived'];
        $page = max(1, (int)$request->input('page', 1));
        $data = $this->adminService->getCourses($filters, $page, 15);
        $archivedCourses = $data['courses'];
        $totalPages = $data['pages'];
        $totalArchived = $data['total'];
        $currentPage = $data['current_page'];

        // Get available academic years & semesters
        $pdo = Database::getConnection();
        $terms = $pdo->query("SELECT DISTINCT academic_year, semester FROM college_sections ORDER BY academic_year DESC, semester ASC")->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'LMS Course & Term Archival - Administrator';

        return $this->render('lms/admin/archive/index', get_defined_vars());
    }

    /**
     * Processes bulk archival of an entire academic term.
     */
    public function processArchiveTerm(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        if (!$this->validateCsrfToken($request)) {
            $_SESSION['error_message'] = 'Invalid or expired CSRF token.';
            $response->redirect('/sia/lms/admin/archive');
            return;
        }

        $academicLevel = (string)$request->input('academic_level', 'College');
        $academicYear = (string)$request->input('academic_year', '');
        $semester = (string)$request->input('semester', '');
        $adminUserId = (int)($_SESSION['user_id'] ?? 0);

        if (empty($academicYear) || empty($semester)) {
            $_SESSION['error_message'] = 'Please select a valid academic year and semester.';
            $response->redirect('/sia/lms/admin/archive');
            return;
        }

        $count = $this->adminService->archiveTerm($academicLevel, $academicYear, $semester, $adminUserId);

        if ($count > 0) {
            $_SESSION['success_message'] = "Successfully archived {$count} {$academicLevel} courses for {$academicYear} {$semester} Semester.";
        } else {
            $_SESSION['error_message'] = "No active courses found matching the selected term.";
        }

        $response->redirect('/sia/lms/admin/archive');
    }

    /**
     * LMS Administrative Audit Logs View.
     */
    public function auditLogs(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        $page = max(1, (int)$request->input('page', 1));
        $filters = ['search' => trim((string)$request->input('search', ''))];

        $data = $this->adminService->getLmsAuditLogs($filters, $page, 25);
        $logs = $data['logs'];
        $totalPages = $data['pages'];
        $totalLogs = $data['total'];
        $currentPage = $data['current_page'];

        $pageTitle = 'LMS Administrative Audit Logs - TTU';

        return $this->render('lms/admin/audit_logs/index', get_defined_vars());
    }

    // =========================================================================
    // EXISTING COURSE SHELL GENERATOR (PRESERVED & HARDENED)
    // =========================================================================

    public function courseGenerator(Request $request, Response $response)
    {
        $this->enforceAdminAccess();
        $pdo = Database::getConnection();

        // 1. Fetch unmapped college section subjects
        $college_stmt = $pdo->prepare("
            SELECT css.id, 'College' as academic_level, cs.id as section_id, cs.section_code, s.id as subject_id, s.subject_code, s.subject_name, css.instructor as old_instructor_string, css.faculty_user_id as timetable_faculty_id
            FROM college_section_subjects css
            JOIN college_sections cs ON css.college_section_id = cs.id
            JOIN subjects s ON css.subject_id = s.id
            LEFT JOIN lms_courses lc ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id AND lc.subject_id = s.id
            WHERE lc.id IS NULL
        ");
        $college_stmt->execute();
        $college_courses = $college_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Fetch unmapped shs section subjects
        $shs_stmt = $pdo->prepare("
            SELECT sss.id, 'SHS' as academic_level, ss.id as section_id, ss.section_code, s.id as subject_id, s.subject_code, s.subject_name, sss.instructor as old_instructor_string, sss.faculty_user_id as timetable_faculty_id
            FROM shs_section_subjects sss
            JOIN shs_sections ss ON sss.shs_section_id = ss.id
            JOIN subjects s ON sss.subject_id = s.id
            LEFT JOIN lms_courses lc ON (lc.academic_level = 'SHS' OR lc.academic_level = 'Senior High School') AND lc.academic_section_id = ss.id AND lc.subject_id = s.id
            WHERE lc.id IS NULL
        ");
        $shs_stmt->execute();
        $shs_courses = $shs_stmt->fetchAll(PDO::FETCH_ASSOC);

        $unmapped_courses = array_merge($college_courses, $shs_courses);

        // 3. Fetch all active faculty members
        $faculty_stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE role = 'faculty' AND is_active = 1 ORDER BY last_name ASC");
        $faculty_stmt->execute();
        $faculty_users = $faculty_stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'LMS Course Generator - Administrator';

        return $this->render('admin/system/lms_course_generator', get_defined_vars());
    }

    public function generateLmsCourse(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        if ($request->isPost()) {
            if (!$this->validateCsrfToken($request)) {
                $_SESSION['error_message'] = 'Invalid or expired CSRF token.';
                $response->redirect('/sia/lms/admin/generator');
                return;
            }

            $data = $request->getBody();
            $academic_level = $data['academic_level'] ?? '';
            $section_id = filter_var($data['section_id'] ?? 0, FILTER_VALIDATE_INT);
            $subject_id = filter_var($data['subject_id'] ?? 0, FILTER_VALIDATE_INT);
            $faculty_user_id = filter_var($data['faculty_user_id'] ?? 0, FILTER_VALIDATE_INT) ?: null;

            if (!in_array($academic_level, ['College', 'SHS']) || !$section_id || !$subject_id) {
                $_SESSION['error_message'] = 'Invalid form data provided.';
                $response->redirect('/sia/lms/admin/generator');
                return;
            }

            try {
                $pdo = Database::getConnection();

                // Idempotency check: prevent duplicate course shells
                $check = $pdo->prepare("SELECT id FROM lms_courses WHERE academic_level = :lvl AND academic_section_id = :sec AND subject_id = :sub LIMIT 1");
                $check->execute([
                    'lvl' => $academic_level,
                    'sec' => $section_id,
                    'sub' => $subject_id
                ]);
                
                $existingId = $check->fetchColumn();
                if ($existingId) {
                    $_SESSION['error_message'] = "This course is already mapped and generated (Course #{$existingId}).";
                } else {
                    $insert = $pdo->prepare("
                        INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status)
                        VALUES (:lvl, :sec, :sub, :fac, 'active')
                    ");
                    $insert->execute([
                        'lvl' => $academic_level,
                        'sec' => $section_id,
                        'sub' => $subject_id,
                        'fac' => $faculty_user_id
                    ]);

                    $newCourseId = (int)$pdo->lastInsertId();

                    if (function_exists('logActivity')) {
                        $adminUserId = (int)($_SESSION['user_id'] ?? 0);
                        logActivity(
                            $adminUserId,
                            'bi-mortarboard',
                            'LMS Course Created',
                            "Admin provisioned LMS course shell #{$newCourseId} ({$academic_level} Sec: {$section_id}, Subj: {$subject_id}).",
                            "lms_courses:{$newCourseId}",
                            null,
                            ['lms_course_id' => $newCourseId, 'faculty_user_id' => $faculty_user_id],
                            'Manual Course Provisioning'
                        );
                    }

                    $_SESSION['success_message'] = 'LMS Course generated successfully.';
                }

            } catch (Exception $e) {
                error_log("LMS Course Generation Error: " . $e->getMessage());
                $_SESSION['error_message'] = 'An error occurred while generating the course.';
            }

            $response->redirect('/sia/lms/admin/generator');
            return;
        }

        $response->redirect('/sia/lms/admin/generator');
    }

    /**
     * Renders the LMS Admin Course Content / Syllabus Template Cloner view.
     */
    public function templateCloner(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        $sourceCourseId = (int)$request->input('source_id', 0);
        $targetCourseId = (int)$request->input('target_id', 0);

        $courses = $this->adminService->getClonableCourses();
        $preview = null;

        if ($sourceCourseId > 0 && $targetCourseId > 0) {
            try {
                $preview = $this->adminService->getClonePreview($sourceCourseId, $targetCourseId);
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        return $this->render('lms/admin/cloner/index', compact('courses', 'sourceCourseId', 'targetCourseId', 'preview'));
    }

    /**
     * Processes execution of the course content cloning workflow.
     */
    public function processCloneContent(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        if (!$this->validateCsrfToken($request)) {
            $_SESSION['error_message'] = 'Security validation failed (invalid CSRF token). Please try again.';
            $response->redirect('/sia/lms/admin/cloner');
            return;
        }

        $sourceCourseId = (int)$request->input('source_course_id', 0);
        $targetCourseId = (int)$request->input('target_course_id', 0);
        $mode = (string)$request->input('clone_mode', 'empty_only');
        $adminUserId = (int)($_SESSION['user_id'] ?? 0);

        if ($sourceCourseId <= 0 || $targetCourseId <= 0) {
            $_SESSION['error_message'] = 'Please select both a valid source course and a target course.';
            $response->redirect('/sia/lms/admin/cloner');
            return;
        }

        if ($sourceCourseId === $targetCourseId) {
            $_SESSION['error_message'] = 'Source course and target course cannot be identical.';
            $response->redirect("/sia/lms/admin/cloner?source_id={$sourceCourseId}&target_id={$targetCourseId}");
            return;
        }

        try {
            $result = $this->adminService->cloneCourseContent($sourceCourseId, $targetCourseId, $adminUserId, [
                'mode' => $mode
            ]);

            $stats = $result['stats'];
            $_SESSION['success_message'] = sprintf(
                "Instructional content successfully cloned into Course #%d! Copied: %d module(s), %d material(s), %d assignment(s), and %d quiz(zes) [%d questions, %d choices].",
                $targetCourseId,
                $stats['modules'],
                $stats['materials'],
                $stats['assignments'],
                $stats['quizzes'],
                $stats['questions'],
                $stats['choices']
            );

            $response->redirect("/sia/lms/admin/courses/{$targetCourseId}");
            return;
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            $response->redirect("/sia/lms/admin/cloner?source_id={$sourceCourseId}&target_id={$targetCourseId}");
            return;
        }
    }

    // =========================================================================
    // LMS PLATFORM ANNOUNCEMENTS (GOVERNANCE & BROADCASTING)
    // =========================================================================

    /**
     * Lists all platform announcements with filters.
     */
    public function announcements(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        $filters = [
            'status' => $request->input('status', ''),
            'audience' => $request->input('audience', ''),
            'severity' => $request->input('severity', ''),
            'search' => $request->input('search', '')
        ];

        $announcements = $this->announcementService->getAllPlatformAnnouncements($filters);

        // Summary counts across all platform notices
        $all = $this->announcementService->getAllPlatformAnnouncements();
        $summary = [
            'total' => count($all),
            'active' => 0,
            'draft' => 0,
            'scheduled' => 0,
            'expired' => 0
        ];
        foreach ($all as $item) {
            $lifecycle = $item['lifecycle'] ?? 'draft';
            if (isset($summary[$lifecycle])) {
                $summary[$lifecycle]++;
            }
        }

        return $this->render('lms/admin/announcements/index', [
            'announcements' => $announcements,
            'filters' => $filters,
            'summary' => $summary,
            'activeTerm' => $this->adminService->getActiveTerm()
        ]);
    }

    /**
     * Stores a new platform announcement.
     */
    public function storeAnnouncement(Request $request, Response $response)
    {
        $this->enforceAdminAccess();

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!function_exists('validateCsrfToken') || !validateCsrfToken($csrfToken)) {
            $_SESSION['error_message'] = 'Security validation failed: Invalid or expired CSRF token.';
            $response->redirect('/sia/lms/admin/announcements');
            return;
        }

        $adminUserId = (int)($_SESSION['user_id'] ?? 0);
        $data = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'target_audience' => $_POST['target_audience'] ?? 'all',
            'severity' => $_POST['severity'] ?? 'info',
            'status' => $_POST['status'] ?? 'draft',
            'published_at' => !empty($_POST['published_at']) ? $_POST['published_at'] : null,
            'expires_at' => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null
        ];

        try {
            $id = $this->announcementService->createPlatformAnnouncement($data, $adminUserId);
            $_SESSION['success_message'] = "Platform announcement #{$id} created successfully (" . ucfirst($data['status']) . ").";
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Failed to create platform announcement: " . $e->getMessage();
        }

        $response->redirect('/sia/lms/admin/announcements');
    }

    /**
     * Updates an existing platform announcement.
     */
    public function updateAnnouncement(Request $request, Response $response, string $id)
    {
        $this->enforceAdminAccess();

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!function_exists('validateCsrfToken') || !validateCsrfToken($csrfToken)) {
            $_SESSION['error_message'] = 'Security validation failed: Invalid or expired CSRF token.';
            $response->redirect('/sia/lms/admin/announcements');
            return;
        }

        $adminUserId = (int)($_SESSION['user_id'] ?? 0);
        $data = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'target_audience' => $_POST['target_audience'] ?? 'all',
            'severity' => $_POST['severity'] ?? 'info',
            'status' => $_POST['status'] ?? 'draft',
            'published_at' => !empty($_POST['published_at']) ? $_POST['published_at'] : null,
            'expires_at' => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null
        ];

        try {
            $this->announcementService->updatePlatformAnnouncement((int)$id, $data, $adminUserId);
            $_SESSION['success_message'] = "Platform announcement #{$id} updated successfully.";
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Failed to update platform announcement: " . $e->getMessage();
        }

        $response->redirect('/sia/lms/admin/announcements');
    }

    /**
     * Toggles announcement status between draft and published.
     */
    public function toggleAnnouncementStatus(Request $request, Response $response, string $id)
    {
        $this->enforceAdminAccess();

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!function_exists('validateCsrfToken') || !validateCsrfToken($csrfToken)) {
            $_SESSION['error_message'] = 'Security validation failed: Invalid or expired CSRF token.';
            $response->redirect('/sia/lms/admin/announcements');
            return;
        }

        $adminUserId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $res = $this->announcementService->togglePlatformAnnouncementStatus((int)$id, $adminUserId);
            $newStatus = ucfirst($res['new_status']);
            $_SESSION['success_message'] = "Platform announcement #{$id} is now {$newStatus}.";
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Failed to toggle announcement status: " . $e->getMessage();
        }

        $response->redirect('/sia/lms/admin/announcements');
    }

    /**
     * Deletes a platform announcement.
     */
    public function deleteAnnouncement(Request $request, Response $response, string $id)
    {
        $this->enforceAdminAccess();

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!function_exists('validateCsrfToken') || !validateCsrfToken($csrfToken)) {
            $_SESSION['error_message'] = 'Security validation failed: Invalid or expired CSRF token.';
            $response->redirect('/sia/lms/admin/announcements');
            return;
        }

        $adminUserId = (int)($_SESSION['user_id'] ?? 0);

        try {
            $this->announcementService->deletePlatformAnnouncement((int)$id, $adminUserId);
            $_SESSION['success_message'] = "Platform announcement #{$id} deleted successfully.";
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Failed to delete announcement: " . $e->getMessage();
        }

        $response->redirect('/sia/lms/admin/announcements');
    }
}
