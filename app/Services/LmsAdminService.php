<?php
namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class LmsAdminService
{
    private PDO $pdo;
    private LmsService $lmsService;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->lmsService = new LmsService($this->pdo);
    }

    /**
     * Retrieves the currently active academic term.
     */
    public function getActiveTerm(): array
    {
        return [
            'academic_year' => '2026-2027',
            'semester' => 'First Semester'
        ];
    }

    /**
     * Retrieves aggregated KPI statistics for the LMS Admin Dashboard.
     */
    public function getDashboardStats(): array
    {
        // 1. Course Stats
        $courseStats = $this->pdo->query("
            SELECT 
                COUNT(*) as total_courses,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_courses,
                COUNT(CASE WHEN status = 'archived' THEN 1 END) as archived_courses,
                COUNT(CASE WHEN faculty_user_id IS NULL THEN 1 END) as unassigned_courses
            FROM lms_courses
        ")->fetch(PDO::FETCH_ASSOC) ?: [
            'total_courses' => 0,
            'active_courses' => 0,
            'archived_courses' => 0,
            'unassigned_courses' => 0
        ];

        // 2. User Stats
        $userStats = $this->pdo->query("
            SELECT 
                COUNT(CASE WHEN role = 'student' THEN 1 END) as total_students,
                COUNT(CASE WHEN role = 'student' AND lms_status = 'active' THEN 1 END) as active_students,
                COUNT(CASE WHEN role = 'student' AND lms_status = 'suspended' THEN 1 END) as suspended_students,
                COUNT(CASE WHEN role = 'faculty' THEN 1 END) as total_faculty,
                COUNT(CASE WHEN role = 'faculty' AND is_active = 1 THEN 1 END) as active_faculty
            FROM users
        ")->fetch(PDO::FETCH_ASSOC) ?: [
            'total_students' => 0,
            'active_students' => 0,
            'suspended_students' => 0,
            'total_faculty' => 0,
            'active_faculty' => 0
        ];

        // 3. Activity / Content Stats
        $contentStats = $this->pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM lms_assignments) as total_assignments,
                (SELECT COUNT(*) FROM lms_submissions) as total_submissions,
                (SELECT COUNT(*) FROM lms_quizzes) as total_quizzes,
                (SELECT COUNT(*) FROM lms_quiz_attempts) as total_quiz_attempts
        ")->fetch(PDO::FETCH_ASSOC) ?: [
            'total_assignments' => 0,
            'total_submissions' => 0,
            'total_quizzes' => 0,
            'total_quiz_attempts' => 0
        ];

        // 4. Current Academic Term
        $termStmt = $this->pdo->query("
            SELECT DISTINCT academic_year, semester 
            FROM college_sections 
            WHERE (status = 1 OR status = 'active') 
            ORDER BY academic_year DESC, semester ASC 
            LIMIT 1
        ");
        $activeTerm = $termStmt->fetch(PDO::FETCH_ASSOC) ?: ['academic_year' => '2026-2027', 'semester' => 'First'];

        // 5. Recent LMS Administrative Activity
        $recentLogsStmt = $this->pdo->prepare("
            SELECT al.*, u.first_name, u.last_name, u.role as user_role, u.email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.title LIKE '%LMS%' 
               OR al.affected_record LIKE '%lms%'
               OR al.description LIKE '%LMS%'
               OR al.affected_record LIKE '%course%'
            ORDER BY al.created_at DESC
            LIMIT 8
        ");
        $recentLogsStmt->execute();
        $recentLogs = $recentLogsStmt->fetchAll(PDO::FETCH_ASSOC);

        return array_merge($courseStats, $userStats, $contentStats, [
            'active_term' => $activeTerm,
            'recent_activity' => $recentLogs
        ]);
    }

    /**
     * Retrieves paginated LMS course catalog with rich metadata and filters.
     */
    public function getCourses(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(s.subject_code LIKE :s1 OR s.subject_name LIKE :s2 OR cs.section_code LIKE :s3 OR ss.section_code LIKE :s4 OR CONCAT(u.first_name, ' ', u.last_name) LIKE :s5)";
            $params['s1'] = $search;
            $params['s2'] = $search;
            $params['s3'] = $search;
            $params['s4'] = $search;
            $params['s5'] = $search;
        }

        if (!empty($filters['academic_level'])) {
            $where[] = "lc.academic_level = :lvl";
            $params['lvl'] = $filters['academic_level'];
        }

        if (!empty($filters['status'])) {
            $where[] = "lc.status = :st";
            $params['st'] = $filters['status'];
        }

        if (!empty($filters['faculty_filter'])) {
            if ($filters['faculty_filter'] === 'assigned') {
                $where[] = "lc.faculty_user_id IS NOT NULL";
            } elseif ($filters['faculty_filter'] === 'unassigned') {
                $where[] = "lc.faculty_user_id IS NULL";
            }
        }

        $whereClause = implode(" AND ", $where);

        // Count total matching
        $countStmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM lms_courses lc
            JOIN subjects s ON lc.subject_id = s.id
            LEFT JOIN college_sections cs ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id
            LEFT JOIN shs_sections ss ON (lc.academic_level = 'SHS' OR lc.academic_level = 'Senior High School') AND lc.academic_section_id = ss.id
            LEFT JOIN users u ON lc.faculty_user_id = u.id
            WHERE $whereClause
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Fetch page items with enrollment & module counts
        $query = "
            SELECT 
                lc.id,
                lc.id as lms_course_id,
                lc.academic_level,
                lc.academic_section_id,
                lc.subject_id,
                lc.faculty_user_id,
                lc.status,
                lc.created_at,
                lc.updated_at,
                s.subject_code,
                s.subject_name,
                s.units,
                COALESCE(cs.section_code, ss.section_code) as section_code,
                COALESCE(cs.academic_year, ss.academic_year) as academic_year,
                COALESCE(cs.semester, 'First') as semester,
                u.first_name as instructor_first,
                u.last_name as instructor_last,
                u.email as instructor_email,
                (SELECT COUNT(*) FROM lms_modules WHERE lms_course_id = lc.id) as modules_count,
                (SELECT COUNT(*) FROM lms_assignments WHERE lms_course_id = lc.id) as assignments_count,
                (SELECT COUNT(*) FROM lms_quizzes WHERE lms_course_id = lc.id) as quizzes_count,
                (
                    CASE 
                        WHEN lc.academic_level = 'College' THEN
                            (SELECT COUNT(DISTINCT a.user_id)
                             FROM college_enrollments ce
                             JOIN applications a ON ce.application_id = a.id
                             WHERE ce.college_section_id = lc.academic_section_id 
                               AND ce.subject_id = lc.subject_id 
                               AND ce.status = 'enrolled'
                               AND a.status = 'enrolled')
                        ELSE
                            (SELECT COUNT(DISTINCT a.user_id)
                             FROM shs_enrollments se
                             JOIN applications a ON se.application_id = a.id
                             WHERE se.shs_section_id = lc.academic_section_id 
                               AND se.subject_id = lc.subject_id 
                               AND se.status = 'enrolled'
                               AND a.status = 'enrolled')
                    END
                ) as enrolled_count
            FROM lms_courses lc
            JOIN subjects s ON lc.subject_id = s.id
            LEFT JOIN college_sections cs ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id
            LEFT JOIN shs_sections ss ON (lc.academic_level = 'SHS' OR lc.academic_level = 'Senior High School') AND lc.academic_section_id = ss.id
            LEFT JOIN users u ON lc.faculty_user_id = u.id
            WHERE $whereClause
            ORDER BY lc.created_at DESC, s.subject_code ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'courses' => $courses,
            'total' => $total,
            'pages' => (int)ceil($total / max(1, $limit)),
            'current_page' => $page
        ];
    }

    /**
     * Inspects a single LMS course shell in full depth.
     */
    public function getCourseInspection(int $courseId): ?array
    {
        $course = $this->lmsService->getCourseDetails($courseId);
        if (!$course) return null;

        // 1. Fetch timetable schedule if available
        $timetable = null;
        if ($course['academic_level'] === 'College') {
            $tStmt = $this->pdo->prepare("
                SELECT css.*, u.first_name as sched_fac_first, u.last_name as sched_fac_last
                FROM college_section_subjects css
                LEFT JOIN users u ON css.faculty_user_id = u.id
                WHERE css.college_section_id = :sec AND css.subject_id = :sub
                LIMIT 1
            ");
            $tStmt->execute(['sec' => $course['academic_section_id'], 'sub' => $course['subject_id']]);
            $timetable = $tStmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $tStmt = $this->pdo->prepare("
                SELECT sss.*, u.first_name as sched_fac_first, u.last_name as sched_fac_last
                FROM shs_section_subjects sss
                LEFT JOIN users u ON sss.faculty_user_id = u.id
                WHERE sss.shs_section_id = :sec AND sss.subject_id = :sub
                LIMIT 1
            ");
            $tStmt->execute(['sec' => $course['academic_section_id'], 'sub' => $course['subject_id']]);
            $timetable = $tStmt->fetch(PDO::FETCH_ASSOC);
        }

        // 2. Fetch Modules & Materials
        $modules = $this->lmsService->getModulesWithMaterialsForCourse($courseId);

        // 3. Fetch Assignments & Submission Counts
        $aStmt = $this->pdo->prepare("
            SELECT a.*, 
                (SELECT COUNT(*) FROM lms_submissions WHERE assignment_id = a.id) as submissions_count,
                (SELECT COUNT(*) FROM lms_submissions WHERE assignment_id = a.id AND status = 'GRADED') as graded_count
            FROM lms_assignments a
            WHERE a.lms_course_id = :cid
            ORDER BY a.created_at DESC
        ");
        $aStmt->execute(['cid' => $courseId]);
        $assignments = $aStmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Fetch Quizzes & Attempt Counts
        $qStmt = $this->pdo->prepare("
            SELECT q.*, 
                (SELECT COUNT(*) FROM lms_questions WHERE lms_quiz_id = q.id) as questions_count,
                (SELECT COUNT(*) FROM lms_quiz_attempts WHERE lms_quiz_id = q.id) as attempts_count
            FROM lms_quizzes q
            WHERE q.lms_course_id = :cid
            ORDER BY q.created_at DESC
        ");
        $qStmt->execute(['cid' => $courseId]);
        $quizzes = $qStmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Fetch Official Enrolled Roster
        $roster = $this->lmsService->getCourseRoster($courseId);

        // 6. Active Faculty list for reassignment options
        $facultyStmt = $this->pdo->query("SELECT id, first_name, last_name, email FROM users WHERE role = 'faculty' AND is_active = 1 ORDER BY last_name ASC");
        $availableFaculty = $facultyStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'course' => $course,
            'timetable' => $timetable,
            'modules' => $modules,
            'assignments' => $assignments,
            'quizzes' => $quizzes,
            'roster' => $roster,
            'available_faculty' => $availableFaculty
        ];
    }

    /**
     * Reassigns course instructor and synchronizes with authoritative timetable.
     */
    public function reassignFaculty(int $courseId, ?int $facultyUserId, ?int $adminUserId = null, bool $syncAuthoritativeSchedule = true): bool
    {
        $course = $this->lmsService->getCourseDetails($courseId);
        if (!$course) return false;

        $oldFacultyId = $course['faculty_user_id'] ? (int)$course['faculty_user_id'] : null;
        $facultyName = 'TBA';

        if ($facultyUserId !== null && $facultyUserId > 0) {
            $fStmt = $this->pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id = :uid AND role = 'faculty' AND is_active = 1");
            $fStmt->execute(['uid' => $facultyUserId]);
            $facUser = $fStmt->fetch(PDO::FETCH_ASSOC);
            if (!$facUser) {
                return false;
            }
            $facultyName = trim($facUser['first_name'] . ' ' . $facUser['last_name']);
        } else {
            $facultyUserId = null;
        }

        try {
            $this->pdo->beginTransaction();

            // 1. Update LMS Course
            $upStmt = $this->pdo->prepare("UPDATE lms_courses SET faculty_user_id = :fid, updated_at = NOW() WHERE id = :cid");
            $upStmt->execute(['fid' => $facultyUserId, 'cid' => $courseId]);

            // 2. Synchronize with Scheduling Timetable if requested
            if ($syncAuthoritativeSchedule) {
                if ($course['academic_level'] === 'College') {
                    $schedStmt = $this->pdo->prepare("
                        UPDATE college_section_subjects 
                        SET faculty_user_id = :fid, instructor = :iname, updated_at = NOW() 
                        WHERE college_section_id = :sec AND subject_id = :sub
                    ");
                    $schedStmt->execute([
                        'fid' => $facultyUserId,
                        'iname' => $facultyName,
                        'sec' => $course['academic_section_id'],
                        'sub' => $course['subject_id']
                    ]);
                } else {
                    $schedStmt = $this->pdo->prepare("
                        UPDATE shs_section_subjects 
                        SET faculty_user_id = :fid, instructor = :iname, updated_at = NOW() 
                        WHERE shs_section_id = :sec AND subject_id = :sub
                    ");
                    $schedStmt->execute([
                        'fid' => $facultyUserId,
                        'iname' => $facultyName,
                        'sec' => $course['academic_section_id'],
                        'sub' => $course['subject_id']
                    ]);
                }
            }

            $this->pdo->commit();

            // 3. Log administrative audit entry
            if (function_exists('logActivity')) {
                logActivity(
                    $adminUserId,
                    'bi-person-badge',
                    'LMS Faculty Reassigned',
                    "Reassigned Course #{$courseId} ({$course['subject_code']} - {$course['section_code']}) instructor to {$facultyName}.",
                    "lms_courses:{$courseId}",
                    ['faculty_user_id' => $oldFacultyId],
                    ['faculty_user_id' => $facultyUserId],
                    'LMS Governance Reassignment'
                );
            }

            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("LmsAdminService::reassignFaculty error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates course status between 'active' and 'archived'. Preserves all learning records.
     */
    public function updateCourseStatus(int $courseId, string $status, ?int $adminUserId = null): bool
    {
        if (!in_array($status, ['active', 'archived'], true)) {
            return false;
        }

        $course = $this->lmsService->getCourseDetails($courseId);
        if (!$course) return false;

        $oldStatus = $course['status'];
        if ($oldStatus === $status) return true;

        $stmt = $this->pdo->prepare("UPDATE lms_courses SET status = :st, updated_at = NOW() WHERE id = :cid");
        $res = $stmt->execute(['st' => $status, 'cid' => $courseId]);

        if ($res && function_exists('logActivity')) {
            logActivity(
                $adminUserId,
                'bi-archive',
                'LMS Course Status Updated',
                "Course #{$courseId} ({$course['subject_code']} - {$course['section_code']}) status transitioned from '{$oldStatus}' to '{$status}'.",
                "lms_courses:{$courseId}",
                ['status' => $oldStatus],
                ['status' => $status],
                'LMS Course Archival Lifecycle'
            );
        }

        return $res;
    }

    /**
     * Archives all LMS courses belonging to a specific academic term and level.
     */
    public function archiveTerm(string $academicLevel, string $academicYear, string $semester, ?int $adminUserId = null): int
    {
        try {
            $this->pdo->beginTransaction();

            if ($academicLevel === 'College') {
                $sql = "
                    UPDATE lms_courses lc
                    SET lc.status = 'archived', lc.updated_at = NOW()
                    WHERE lc.status = 'active'
                      AND lc.academic_level = 'College'
                      AND lc.academic_section_id IN (
                          SELECT id FROM college_sections WHERE academic_year = :ay AND semester = :sem
                      )
                ";
                $params = ['ay' => $academicYear, 'sem' => $semester];
            } else {
                $sql = "
                    UPDATE lms_courses lc
                    SET lc.status = 'archived', lc.updated_at = NOW()
                    WHERE lc.status = 'active'
                      AND (lc.academic_level = 'SHS' OR lc.academic_level = 'Senior High School')
                      AND lc.academic_section_id IN (
                          SELECT id FROM shs_sections WHERE academic_year = :ay
                      )
                ";
                $params = ['ay' => $academicYear];
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $archivedCount = $stmt->rowCount();
            $this->pdo->commit();

            if ($archivedCount > 0 && function_exists('logActivity')) {
                logActivity(
                    $adminUserId,
                    'bi-archive-fill',
                    'LMS Term Archived',
                    "Archived {$archivedCount} {$academicLevel} courses for Term {$academicYear} {$semester} Semester.",
                    "lms_terms:{$academicLevel}:{$academicYear}:{$semester}",
                    null,
                    ['archived_count' => $archivedCount],
                    'Term Rollover Archival'
                );
            }

            return $archivedCount;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("LmsAdminService::archiveTerm error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Retrieves paginated list of LMS users with their LMS participation status.
     */
    public function getLmsUsers(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $where = ["u.role IN ('student', 'faculty', 'admin', 'superadmin')"];
        $params = [];

        if (!empty($filters['role'])) {
            $where[] = "u.role = :role";
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['lms_status'])) {
            $where[] = "u.lms_status = :lst";
            $params['lst'] = $filters['lms_status'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(u.first_name LIKE :s1 OR u.last_name LIKE :s2 OR u.email LIKE :s3 OR u.student_number LIKE :s4 OR u.employee_id LIKE :s5)";
            $params['s1'] = $search;
            $params['s2'] = $search;
            $params['s3'] = $search;
            $params['s4'] = $search;
            $params['s5'] = $search;
        }

        $whereClause = implode(" AND ", $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM users u WHERE $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $query = "
            SELECT 
                u.id,
                u.student_number,
                u.employee_id,
                u.first_name,
                u.last_name,
                u.email,
                u.role,
                u.lms_status,
                u.is_active,
                u.created_at,
                (
                    CASE 
                        WHEN u.role = 'faculty' THEN 
                            (SELECT COUNT(*) FROM lms_courses WHERE faculty_user_id = u.id AND status = 'active')
                        WHEN u.role = 'student' THEN 
                            (SELECT COUNT(*) FROM college_enrollments ce JOIN applications a ON ce.application_id = a.id WHERE a.user_id = u.id AND ce.status = 'enrolled') +
                            (SELECT COUNT(*) FROM shs_enrollments se JOIN applications a ON se.application_id = a.id WHERE a.user_id = u.id AND se.status = 'enrolled')
                        ELSE 0
                    END
                ) as active_courses_count
            FROM users u
            WHERE $whereClause
            ORDER BY u.role ASC, u.last_name ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'users' => $users,
            'total' => $total,
            'pages' => (int)ceil($total / max(1, $limit)),
            'current_page' => $page
        ];
    }

    /**
     * Modifies user's LMS-specific status (active | suspended | inactive).
     * Does NOT alter official enrollment or delete the user.
     */
    public function updateUserLmsStatus(int $userId, string $lmsStatus, ?int $adminUserId = null, ?string $reason = null): bool
    {
        if (!in_array($lmsStatus, ['active', 'suspended', 'inactive'], true)) {
            return false;
        }

        $userStmt = $this->pdo->prepare("SELECT id, first_name, last_name, email, role, lms_status FROM users WHERE id = :uid");
        $userStmt->execute(['uid' => $userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return false;

        $oldStatus = $user['lms_status'];
        if ($oldStatus === $lmsStatus) return true;

        $stmt = $this->pdo->prepare("UPDATE users SET lms_status = :lst, updated_at = NOW() WHERE id = :uid");
        $res = $stmt->execute(['lst' => $lmsStatus, 'uid' => $userId]);

        if ($res && function_exists('logActivity')) {
            logActivity(
                $adminUserId,
                'bi-shield-lock',
                'LMS User Status Changed',
                "User #{$userId} ({$user['first_name']} {$user['last_name']}) LMS access updated from '{$oldStatus}' to '{$lmsStatus}'.",
                "users:{$userId}",
                ['lms_status' => $oldStatus],
                ['lms_status' => $lmsStatus],
                $reason ?? 'LMS Administrative Governance'
            );
        }

        return $res;
    }

    /**
     * Comprehensive Non-Destructive Enrollment & LMS Synchronization Scan.
     * Identifies:
     * - Missing Course Shells
     * - Faculty Mismatches
     * - Duplicate LMS Course Shells
     * - Orphan LMS Courses
     * - Healthy In-Sync Courses
     */
    public function scanEnrollmentSync(): array
    {
        // 1. Missing Courses (in timetable but no lms_courses row)
        $collegeMissing = $this->pdo->query("
            SELECT 
                'College' as academic_level,
                cs.id as section_id,
                cs.section_code,
                s.id as subject_id,
                s.subject_code,
                s.subject_name,
                css.faculty_user_id as timetable_faculty_id,
                css.instructor as timetable_instructor
            FROM college_section_subjects css
            JOIN college_sections cs ON css.college_section_id = cs.id
            JOIN subjects s ON css.subject_id = s.id
            LEFT JOIN lms_courses lc ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id AND lc.subject_id = s.id
            WHERE lc.id IS NULL
        ")->fetchAll(PDO::FETCH_ASSOC);

        $shsMissing = $this->pdo->query("
            SELECT 
                'SHS' as academic_level,
                ss.id as section_id,
                ss.section_code,
                s.id as subject_id,
                s.subject_code,
                s.subject_name,
                sss.faculty_user_id as timetable_faculty_id,
                sss.instructor as timetable_instructor
            FROM shs_section_subjects sss
            JOIN shs_sections ss ON sss.shs_section_id = ss.id
            JOIN subjects s ON sss.subject_id = s.id
            LEFT JOIN lms_courses lc ON (lc.academic_level = 'SHS' OR lc.academic_level = 'Senior High School') AND lc.academic_section_id = ss.id AND lc.subject_id = s.id
            WHERE lc.id IS NULL
        ")->fetchAll(PDO::FETCH_ASSOC);

        $missingCourses = array_merge($collegeMissing, $shsMissing);

        // 2. Faculty Mismatches (LMS course faculty != timetable faculty)
        $collegeMismatches = $this->pdo->query("
            SELECT 
                lc.id as lms_course_id,
                'College' as academic_level,
                cs.section_code,
                s.subject_code,
                s.subject_name,
                lc.faculty_user_id as lms_faculty_id,
                CONCAT(u_lms.first_name, ' ', u_lms.last_name) as lms_instructor,
                css.faculty_user_id as timetable_faculty_id,
                css.instructor as timetable_instructor
            FROM lms_courses lc
            JOIN college_sections cs ON lc.academic_section_id = cs.id
            JOIN subjects s ON lc.subject_id = s.id
            JOIN college_section_subjects css ON css.college_section_id = cs.id AND css.subject_id = s.id
            LEFT JOIN users u_lms ON lc.faculty_user_id = u_lms.id
            WHERE lc.academic_level = 'College'
              AND (
                  (lc.faculty_user_id IS NULL AND css.faculty_user_id IS NOT NULL) OR
                  (lc.faculty_user_id IS NOT NULL AND css.faculty_user_id IS NULL) OR
                  (lc.faculty_user_id != css.faculty_user_id)
              )
        ")->fetchAll(PDO::FETCH_ASSOC);

        $shsMismatches = $this->pdo->query("
            SELECT 
                lc.id as lms_course_id,
                'SHS' as academic_level,
                ss.section_code,
                s.subject_code,
                s.subject_name,
                lc.faculty_user_id as lms_faculty_id,
                CONCAT(u_lms.first_name, ' ', u_lms.last_name) as lms_instructor,
                sss.faculty_user_id as timetable_faculty_id,
                sss.instructor as timetable_instructor
            FROM lms_courses lc
            JOIN shs_sections ss ON lc.academic_section_id = ss.id
            JOIN subjects s ON lc.subject_id = s.id
            JOIN shs_section_subjects sss ON sss.shs_section_id = ss.id AND sss.subject_id = s.id
            LEFT JOIN users u_lms ON lc.faculty_user_id = u_lms.id
            WHERE (lc.academic_level = 'SHS' OR lc.academic_level = 'Senior High School')
              AND (
                  (lc.faculty_user_id IS NULL AND sss.faculty_user_id IS NOT NULL) OR
                  (lc.faculty_user_id IS NOT NULL AND sss.faculty_user_id IS NULL) OR
                  (lc.faculty_user_id != sss.faculty_user_id)
              )
        ")->fetchAll(PDO::FETCH_ASSOC);

        $facultyMismatches = array_merge($collegeMismatches, $shsMismatches);

        // 3. Duplicate Courses (multiple LMS course shells for same section & subject)
        $duplicates = $this->pdo->query("
            SELECT 
                academic_level,
                academic_section_id,
                subject_id,
                COUNT(*) as duplicate_count,
                GROUP_CONCAT(id ORDER BY id ASC) as course_ids
            FROM lms_courses
            GROUP BY academic_level, academic_section_id, subject_id
            HAVING COUNT(*) > 1
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 4. Orphan Courses (section or subject deleted from enrollment)
        $orphans = $this->pdo->query("
            SELECT 
                lc.id as lms_course_id,
                lc.academic_level,
                lc.academic_section_id,
                lc.subject_id,
                lc.created_at
            FROM lms_courses lc
            LEFT JOIN subjects s ON lc.subject_id = s.id
            LEFT JOIN college_sections cs ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id
            LEFT JOIN shs_sections ss ON (lc.academic_level = 'SHS' OR lc.academic_level = 'Senior High School') AND lc.academic_section_id = ss.id
            WHERE s.id IS NULL OR (cs.id IS NULL AND ss.id IS NULL)
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 5. Total healthy courses count
        $totalCourses = (int)$this->pdo->query("SELECT COUNT(*) FROM lms_courses")->fetchColumn();
        $unhealthyIds = array_unique(array_merge(
            array_column($facultyMismatches, 'lms_course_id'),
            array_column($orphans, 'lms_course_id')
        ));
        $healthyCount = max(0, $totalCourses - count($unhealthyIds));

        return [
            'missing_courses' => $missingCourses,
            'faculty_mismatches' => $facultyMismatches,
            'duplicates' => $duplicates,
            'orphans' => $orphans,
            'healthy_count' => $healthyCount,
            'total_scanned' => $totalCourses + count($missingCourses),
            'is_fully_synchronized' => (empty($missingCourses) && empty($facultyMismatches) && empty($duplicates) && empty($orphans))
        ];
    }

    /**
     * Executes deterministic, safe synchronization repairs:
     * - Auto-provisions missing courses.
     * - Synchronizes faculty from authoritative timetable where scheduled.
     * - Does NOT blindly delete duplicates or orphans (reports them).
     */
    public function reconcileAllDeterministic(?int $adminUserId = null): array
    {
        $scan = $this->scanEnrollmentSync();
        $provisionedCount = 0;
        $facultySyncedCount = 0;

        try {
            $this->pdo->beginTransaction();

            // 1. Provision Missing Courses
            $insStmt = $this->pdo->prepare("
                INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status)
                VALUES (:lvl, :sec, :sub, :fac, 'active')
            ");

            foreach ($scan['missing_courses'] as $m) {
                // Double-check existence to prevent race condition
                $chk = $this->pdo->prepare("SELECT id FROM lms_courses WHERE academic_level = :lvl AND academic_section_id = :sec AND subject_id = :sub LIMIT 1");
                $chk->execute(['lvl' => $m['academic_level'], 'sec' => $m['section_id'], 'sub' => $m['subject_id']]);
                if (!$chk->fetchColumn()) {
                    $insStmt->execute([
                        'lvl' => $m['academic_level'],
                        'sec' => $m['section_id'],
                        'sub' => $m['subject_id'],
                        'fac' => !empty($m['timetable_faculty_id']) ? (int)$m['timetable_faculty_id'] : null
                    ]);
                    $provisionedCount++;
                }
            }

            // 2. Synchronize Faculty Mismatches (aligning LMS course to timetable faculty)
            $syncFacStmt = $this->pdo->prepare("UPDATE lms_courses SET faculty_user_id = :fac, updated_at = NOW() WHERE id = :cid");
            foreach ($scan['faculty_mismatches'] as $f) {
                $targetFacId = !empty($f['timetable_faculty_id']) ? (int)$f['timetable_faculty_id'] : null;
                $syncFacStmt->execute(['fac' => $targetFacId, 'cid' => $f['lms_course_id']]);
                $facultySyncedCount++;
            }

            $this->pdo->commit();

            // 3. Audit Log
            if (($provisionedCount > 0 || $facultySyncedCount > 0) && function_exists('logActivity')) {
                logActivity(
                    $adminUserId,
                    'bi-arrow-repeat',
                    'LMS Enrollment Reconciled',
                    "Deterministic reconciliation completed: provisioned {$provisionedCount} missing shells, aligned {$facultySyncedCount} faculty assignments.",
                    "lms_reconciliation",
                    null,
                    ['provisioned' => $provisionedCount, 'faculty_synced' => $facultySyncedCount],
                    'LMS Governance Reconcile'
                );
            }

            return [
                'success' => true,
                'provisioned_count' => $provisionedCount,
                'faculty_synced_count' => $facultySyncedCount,
                'duplicates_flagged' => count($scan['duplicates']),
                'orphans_flagged' => count($scan['orphans'])
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("LmsAdminService::reconcileAllDeterministic error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves audit logs for LMS administrative actions with pagination.
     */
    public function getLmsAuditLogs(array $filters = [], int $page = 1, int $limit = 30): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $where = ["(
            al.title LIKE '%LMS%' 
            OR al.affected_record LIKE '%lms%' 
            OR al.description LIKE '%LMS%' 
            OR al.affected_record LIKE '%course%'
        )"];
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(al.title LIKE :s1 OR al.description LIKE :s2 OR al.affected_record LIKE :s3 OR CONCAT(u.first_name, ' ', u.last_name) LIKE :s4)";
            $params['s1'] = $search;
            $params['s2'] = $search;
            $params['s3'] = $search;
            $params['s4'] = $search;
        }

        $whereClause = implode(" AND ", $where);

        $countStmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE $whereClause
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $query = "
            SELECT al.*, u.first_name, u.last_name, u.email, u.role as user_role
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE $whereClause
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'logs' => $logs,
            'total' => $total,
            'pages' => (int)ceil($total / max(1, $limit)),
            'current_page' => $page
        ];
    }

    /**
     * Retrieves all courses formatted for template source/target selection.
     */
    public function getClonableCourses(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                lc.id,
                lc.academic_level,
                lc.academic_section_id,
                lc.subject_id,
                lc.status,
                s.subject_code,
                s.subject_name,
                COALESCE(cs.section_code, ss.section_code) as section_code,
                COALESCE(cs.academic_year, ss.academic_year) as academic_year,
                COALESCE(cs.semester, 'First') as semester,
                u.first_name as instructor_first,
                u.last_name as instructor_last,
                (SELECT COUNT(*) FROM lms_modules WHERE lms_course_id = lc.id) as modules_count,
                (SELECT COUNT(*) FROM lms_assignments WHERE lms_course_id = lc.id) as assignments_count,
                (SELECT COUNT(*) FROM lms_quizzes WHERE lms_course_id = lc.id) as quizzes_count
            FROM lms_courses lc
            JOIN subjects s ON lc.subject_id = s.id
            LEFT JOIN college_sections cs ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id
            LEFT JOIN shs_sections ss ON (lc.academic_level = 'SHS' OR lc.academic_level = 'Senior High School') AND lc.academic_section_id = ss.id
            LEFT JOIN users u ON lc.faculty_user_id = u.id
            ORDER BY lc.status ASC, lc.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Previews instructional content to be cloned from source course to target course,
     * including safety analysis, collision detection, and warnings.
     */
    public function getClonePreview(int $sourceCourseId, int $targetCourseId): array
    {
        $sourceCourse = $this->lmsService->getCourseDetails($sourceCourseId);
        $targetCourse = $this->lmsService->getCourseDetails($targetCourseId);

        if (!$sourceCourse) {
            throw new Exception("Source course #{$sourceCourseId} does not exist.");
        }
        if (!$targetCourse) {
            throw new Exception("Target course #{$targetCourseId} does not exist.");
        }

        // Source content hierarchy
        $sourceModules = $this->lmsService->getModulesWithMaterialsForCourse($sourceCourseId);
        $totalMaterials = 0;
        foreach ($sourceModules as $m) {
            $totalMaterials += count($m['materials'] ?? []);
        }

        $aStmt = $this->pdo->prepare("
            SELECT a.id, a.lms_module_id, a.title, a.description, a.max_score, a.status, m.title as module_title
            FROM lms_assignments a
            LEFT JOIN lms_modules m ON a.lms_module_id = m.id
            WHERE a.lms_course_id = :cid
            ORDER BY a.id ASC
        ");
        $aStmt->execute(['cid' => $sourceCourseId]);
        $sourceAssignments = $aStmt->fetchAll(PDO::FETCH_ASSOC);

        $qStmt = $this->pdo->prepare("
            SELECT q.id, q.title, q.description, q.time_limit, q.max_attempts, q.passing_score, q.status,
                   (SELECT COUNT(*) FROM lms_questions WHERE lms_quiz_id = q.id) as questions_count
            FROM lms_quizzes q
            WHERE q.lms_course_id = :cid
            ORDER BY q.id ASC
        ");
        $qStmt->execute(['cid' => $sourceCourseId]);
        $sourceQuizzes = $qStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch detailed questions & choices for quizzes
        foreach ($sourceQuizzes as &$quiz) {
            $quesStmt = $this->pdo->prepare("
                SELECT id, question_text, question_type, points, display_order
                FROM lms_questions
                WHERE lms_quiz_id = :qid
                ORDER BY display_order ASC, id ASC
            ");
            $quesStmt->execute(['qid' => $quiz['id']]);
            $quiz['questions'] = $quesStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Target content summary
        $tModCount = (int)$this->pdo->query("SELECT COUNT(*) FROM lms_modules WHERE lms_course_id = $targetCourseId")->fetchColumn();
        $tMatCount = (int)$this->pdo->query("
            SELECT COUNT(*) 
            FROM lms_materials lm 
            JOIN lms_modules m ON lm.lms_module_id = m.id 
            WHERE m.lms_course_id = $targetCourseId
        ")->fetchColumn();
        $tAssCount = (int)$this->pdo->query("SELECT COUNT(*) FROM lms_assignments WHERE lms_course_id = $targetCourseId")->fetchColumn();
        $tQuizCount = (int)$this->pdo->query("SELECT COUNT(*) FROM lms_quizzes WHERE lms_course_id = $targetCourseId")->fetchColumn();

        $targetHasContent = ($tModCount > 0 || $tAssCount > 0 || $tQuizCount > 0);

        // Safety & Compatibility checks
        $isSameCourse = ($sourceCourseId === $targetCourseId);
        $isSubjectMatch = ($sourceCourse['subject_id'] === $targetCourse['subject_id']);
        $sourceTotalItems = count($sourceModules) + count($sourceAssignments) + count($sourceQuizzes);

        $warnings = [];
        if ($isSameCourse) {
            $warnings[] = "Source and target course are identical. You cannot clone a course into itself.";
        }
        if (!$isSubjectMatch) {
            $warnings[] = "Subject Mismatch: Source course is '{$sourceCourse['subject_code']} - {$sourceCourse['subject_name']}' while Target course is '{$targetCourse['subject_code']} - {$targetCourse['subject_name']}'. Please ensure this curriculum structure is appropriate for the target subject.";
        }
        if ($targetHasContent) {
            $warnings[] = "Target course already contains {$tModCount} module(s), {$tMatCount} material(s), {$tAssCount} assignment(s), and {$tQuizCount} quiz(zes). To prevent duplication, Safe Mode (empty target only) will block execution. Use Append Mode if you deliberately intend to add these records.";
        }
        if ($sourceTotalItems === 0) {
            $warnings[] = "The source course contains no instructional modules, assignments, or quizzes to clone.";
        }

        return [
            'source_course' => $sourceCourse,
            'target_course' => $targetCourse,
            'source_content' => [
                'modules' => $sourceModules,
                'modules_count' => count($sourceModules),
                'materials_count' => $totalMaterials,
                'assignments' => $sourceAssignments,
                'assignments_count' => count($sourceAssignments),
                'quizzes' => $sourceQuizzes,
                'quizzes_count' => count($sourceQuizzes),
                'total_items' => $sourceTotalItems,
            ],
            'target_content' => [
                'modules_count' => $tModCount,
                'materials_count' => $tMatCount,
                'assignments_count' => $tAssCount,
                'quizzes_count' => $tQuizCount,
                'has_content' => $targetHasContent,
            ],
            'compatibility' => [
                'is_same_course' => $isSameCourse,
                'is_subject_match' => $isSubjectMatch,
                'can_clone_empty_only' => (!$isSameCourse && !$targetHasContent && $sourceTotalItems > 0),
                'can_clone_append' => (!$isSameCourse && $sourceTotalItems > 0),
                'warnings' => $warnings,
            ]
        ];
    }

    /**
     * Executes atomic, transaction-safe cloning of course instructional content.
     * strictly preserves:
     * - source course immutability
     * - student data isolation (zero submissions, quiz attempts, answers, or attendance records copied)
     * - clean academic boundaries (no enrollment or registrar alterations)
     * 
     * @param int $sourceCourseId
     * @param int $targetCourseId
     * @param int $adminUserId
     * @param array $options ['mode' => 'empty_only'|'append']
     * @return array Results with detailed record counts
     * @throws Exception
     */
    public function cloneCourseContent(int $sourceCourseId, int $targetCourseId, int $adminUserId, array $options = []): array
    {
        $mode = $options['mode'] ?? 'empty_only';

        if ($sourceCourseId <= 0 || $targetCourseId <= 0) {
            throw new Exception("Invalid course selection. Both source and target course IDs must be valid positive integers.");
        }

        if ($sourceCourseId === $targetCourseId) {
            throw new Exception("Identical source and target courses. A course cannot be cloned into itself.");
        }

        // Validate course existence
        $sStmt = $this->pdo->prepare("SELECT id, subject_id, status FROM lms_courses WHERE id = :id");
        $sStmt->execute(['id' => $sourceCourseId]);
        $sourceCourse = $sStmt->fetch(PDO::FETCH_ASSOC);
        if (!$sourceCourse) {
            throw new Exception("Source course #{$sourceCourseId} was not found.");
        }

        $tStmt = $this->pdo->prepare("SELECT id, subject_id, status FROM lms_courses WHERE id = :id");
        $tStmt->execute(['id' => $targetCourseId]);
        $targetCourse = $tStmt->fetch(PDO::FETCH_ASSOC);
        if (!$targetCourse) {
            throw new Exception("Target course #{$targetCourseId} was not found.");
        }

        // Check target content collision
        $tModCount = (int)$this->pdo->query("SELECT COUNT(*) FROM lms_modules WHERE lms_course_id = $targetCourseId")->fetchColumn();
        $tAssCount = (int)$this->pdo->query("SELECT COUNT(*) FROM lms_assignments WHERE lms_course_id = $targetCourseId")->fetchColumn();
        $tQuizCount = (int)$this->pdo->query("SELECT COUNT(*) FROM lms_quizzes WHERE lms_course_id = $targetCourseId")->fetchColumn();
        $targetHasContent = ($tModCount > 0 || $tAssCount > 0 || $tQuizCount > 0);

        if ($targetHasContent && $mode !== 'append') {
            throw new Exception("Target course already contains instructional content ({$tModCount} modules, {$tAssCount} assignments, {$tQuizCount} quizzes). Cloner aborted in Safe Mode to prevent duplicates.");
        }

        // Fetch source content
        $mStmt = $this->pdo->prepare("SELECT * FROM lms_modules WHERE lms_course_id = :sid ORDER BY display_order ASC, id ASC");
        $mStmt->execute(['sid' => $sourceCourseId]);
        $sourceModules = $mStmt->fetchAll(PDO::FETCH_ASSOC);

        $aStmt = $this->pdo->prepare("SELECT * FROM lms_assignments WHERE lms_course_id = :sid ORDER BY id ASC");
        $aStmt->execute(['sid' => $sourceCourseId]);
        $sourceAssignments = $aStmt->fetchAll(PDO::FETCH_ASSOC);

        $qStmt = $this->pdo->prepare("SELECT * FROM lms_quizzes WHERE lms_course_id = :sid ORDER BY id ASC");
        $qStmt->execute(['sid' => $sourceCourseId]);
        $sourceQuizzes = $qStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalSourceContent = count($sourceModules) + count($sourceAssignments) + count($sourceQuizzes);
        if ($totalSourceContent === 0) {
            throw new Exception("Source course contains no instructional modules, assignments, or quizzes to clone.");
        }

        // Determine display_order offset for modules if appending
        $orderOffset = 0;
        if ($mode === 'append' && $tModCount > 0) {
            $maxOrder = (int)$this->pdo->query("SELECT COALESCE(MAX(display_order), 0) FROM lms_modules WHERE lms_course_id = $targetCourseId")->fetchColumn();
            $orderOffset = $maxOrder + 1;
        }

        // BEGIN ATOMIC TRANSACTION
        $this->pdo->beginTransaction();

        $stats = [
            'modules' => 0,
            'materials' => 0,
            'assignments' => 0,
            'quizzes' => 0,
            'questions' => 0,
            'choices' => 0,
        ];

        $moduleIdMap = [];
        $quizIdMap = [];
        $questionIdMap = [];

        try {
            // 1. Clone Modules & Materials
            $insModStmt = $this->pdo->prepare("
                INSERT INTO lms_modules (lms_course_id, title, description, display_order, status)
                VALUES (:cid, :title, :desc, :dorder, :status)
            ");

            $selMatStmt = $this->pdo->prepare("
                SELECT file_name, file_path, mime_type, file_size
                FROM lms_materials
                WHERE lms_module_id = :mid
                ORDER BY id ASC
            ");

            $insMatStmt = $this->pdo->prepare("
                INSERT INTO lms_materials (lms_module_id, file_name, file_path, mime_type, file_size)
                VALUES (:mid, :fname, :fpath, :mtype, :fsize)
            ");

            foreach ($sourceModules as $sMod) {
                $newOrder = (int)$sMod['display_order'] + $orderOffset;
                $insModStmt->execute([
                    'cid' => $targetCourseId,
                    'title' => $sMod['title'],
                    'desc' => $sMod['description'],
                    'dorder' => $newOrder,
                    'status' => $sMod['status'] ?? 'published'
                ]);
                $newModuleId = (int)$this->pdo->lastInsertId();
                $moduleIdMap[$sMod['id']] = $newModuleId;
                $stats['modules']++;

                // Clone associated materials
                $selMatStmt->execute(['mid' => $sMod['id']]);
                $materials = $selMatStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($materials as $mat) {
                    $insMatStmt->execute([
                        'mid' => $newModuleId,
                        'fname' => $mat['file_name'],
                        'fpath' => $mat['file_path'],
                        'mtype' => $mat['mime_type'],
                        'fsize' => $mat['file_size']
                    ]);
                    $stats['materials']++;
                }
            }

            // 2. Clone Assignments (prompts & rubrics, nullifying due dates)
            $insAssStmt = $this->pdo->prepare("
                INSERT INTO lms_assignments (lms_course_id, lms_module_id, title, description, due_date, max_score, status, file_path)
                VALUES (:cid, :mid, :title, :desc, NULL, :score, :status, :fpath)
            ");

            foreach ($sourceAssignments as $sAss) {
                // Remap module ID if it was linked to a cloned module
                $mappedModId = null;
                if (!empty($sAss['lms_module_id']) && isset($moduleIdMap[$sAss['lms_module_id']])) {
                    $mappedModId = $moduleIdMap[$sAss['lms_module_id']];
                }

                $insAssStmt->execute([
                    'cid' => $targetCourseId,
                    'mid' => $mappedModId,
                    'title' => $sAss['title'],
                    'desc' => $sAss['description'],
                    'score' => $sAss['max_score'] ?? 100,
                    'status' => $sAss['status'] ?? 'draft',
                    'fpath' => $sAss['file_path'] ?? null
                ]);
                $stats['assignments']++;
            }

            // 3. Clone Quizzes, Questions & Choices (nullifying schedule dates)
            $insQuizStmt = $this->pdo->prepare("
                INSERT INTO lms_quizzes (lms_course_id, title, description, time_limit, max_attempts, passing_score, start_date, end_date, status)
                VALUES (:cid, :title, :desc, :tlimit, :matt, :pass, NULL, NULL, :status)
            ");

            $selQuesStmt = $this->pdo->prepare("
                SELECT * FROM lms_questions WHERE lms_quiz_id = :qid ORDER BY display_order ASC, id ASC
            ");

            $insQuesStmt = $this->pdo->prepare("
                INSERT INTO lms_questions (lms_quiz_id, question_text, question_type, points, case_sensitive, requires_manual_review, source_reference, display_order)
                VALUES (:qid, :qtext, :qtype, :pts, :cs, :review, :source, :dorder)
            ");

            $selChoiceStmt = $this->pdo->prepare("
                SELECT * FROM lms_question_choices WHERE lms_question_id = :qid ORDER BY display_order ASC, id ASC
            ");

            $insChoiceStmt = $this->pdo->prepare("
                INSERT INTO lms_question_choices (lms_question_id, choice_text, is_correct, display_order)
                VALUES (:qid, :ctext, :correct, :dorder)
            ");

            foreach ($sourceQuizzes as $sQuiz) {
                $insQuizStmt->execute([
                    'cid' => $targetCourseId,
                    'title' => $sQuiz['title'],
                    'desc' => $sQuiz['description'],
                    'tlimit' => $sQuiz['time_limit'] ?? null,
                    'matt' => $sQuiz['max_attempts'] ?? 1,
                    'pass' => $sQuiz['passing_score'] ?? null,
                    'status' => $sQuiz['status'] ?? 'draft'
                ]);
                $newQuizId = (int)$this->pdo->lastInsertId();
                $quizIdMap[$sQuiz['id']] = $newQuizId;
                $stats['quizzes']++;

                // Clone questions
                $selQuesStmt->execute(['qid' => $sQuiz['id']]);
                $sourceQuestions = $selQuesStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($sourceQuestions as $sQues) {
                    $insQuesStmt->execute([
                        'qid' => $newQuizId,
                        'qtext' => $sQues['question_text'],
                        'qtype' => $sQues['question_type'],
                        'pts' => $sQues['points'] ?? 1.0,
                        'cs' => (int)($sQues['case_sensitive'] ?? 0),
                        'review' => (int)($sQues['requires_manual_review'] ?? 0),
                        'source' => $sQues['source_reference'] ?? null,
                        'dorder' => $sQues['display_order'] ?? 0
                    ]);
                    $newQuesId = (int)$this->pdo->lastInsertId();
                    $questionIdMap[$sQues['id']] = $newQuesId;
                    $stats['questions']++;

                    // Clone choices
                    $selChoiceStmt->execute(['qid' => $sQues['id']]);
                    $sourceChoices = $selChoiceStmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($sourceChoices as $sChoice) {
                        $insChoiceStmt->execute([
                            'qid' => $newQuesId,
                            'ctext' => $sChoice['choice_text'],
                            'correct' => !empty($sChoice['is_correct']) ? 1 : 0,
                            'dorder' => $sChoice['display_order'] ?? 0
                        ]);
                        $stats['choices']++;
                    }
                }
            }

            // Commit atomic transaction
            $this->pdo->commit();

            // Log administrative activity in existing audit system
            if (function_exists('logActivity')) {
                $desc = sprintf(
                    "Cloned course content from Course #%d to Course #%d (%s mode): %d module(s), %d material(s), %d assignment(s), %d quiz(zes) [%d questions, %d choices].",
                    $sourceCourseId,
                    $targetCourseId,
                    $mode,
                    $stats['modules'],
                    $stats['materials'],
                    $stats['assignments'],
                    $stats['quizzes'],
                    $stats['questions'],
                    $stats['choices']
                );

                logActivity(
                    $adminUserId,
                    'bi-copy',
                    'LMS Course Content Cloned',
                    $desc,
                    "lms_courses:{$targetCourseId}",
                    null,
                    [
                        'source_course_id' => $sourceCourseId,
                        'target_course_id' => $targetCourseId,
                        'mode' => $mode,
                        'stats' => $stats
                    ],
                    'Course Syllabus / Template Cloner'
                );
            }

            return [
                'success' => true,
                'source_course_id' => $sourceCourseId,
                'target_course_id' => $targetCourseId,
                'mode' => $mode,
                'stats' => $stats
            ];

        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // Log failure in existing audit system
            if (function_exists('logActivity')) {
                logActivity(
                    $adminUserId,
                    'bi-exclamation-triangle',
                    'LMS Course Clone Failed',
                    "Failed cloning content from Course #{$sourceCourseId} to Course #{$targetCourseId}: " . $e->getMessage(),
                    "lms_courses:{$targetCourseId}",
                    null,
                    [
                        'source_course_id' => $sourceCourseId,
                        'target_course_id' => $targetCourseId,
                        'mode' => $mode,
                        'error' => $e->getMessage()
                    ],
                    'Course Syllabus / Template Cloner'
                );
            }

            throw new Exception("Course cloning transaction failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Gathers deep instructional and student artifact summary for an LMS course shell.
     */
    public function getCourseArtifactSummary(int $courseId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                lc.id,
                lc.academic_level,
                lc.academic_section_id,
                lc.subject_id,
                lc.faculty_user_id,
                lc.status,
                lc.created_at,
                lc.updated_at,
                s.id as subject_exists_id,
                s.subject_code,
                s.subject_name,
                COALESCE(cs.id, ss.id) as section_exists_id,
                COALESCE(cs.section_code, ss.section_code) as section_code,
                COALESCE(cs.academic_year, ss.academic_year) as academic_year,
                COALESCE(cs.semester, 'First') as semester,
                u.first_name,
                u.last_name,
                u.email as faculty_email,
                (SELECT COUNT(*) FROM lms_modules WHERE lms_course_id = lc.id) as modules_count,
                (SELECT COUNT(*) FROM lms_materials lm JOIN lms_modules m ON lm.lms_module_id = m.id WHERE m.lms_course_id = lc.id) as materials_count,
                (SELECT COUNT(*) FROM lms_assignments WHERE lms_course_id = lc.id) as assignments_count,
                (SELECT COUNT(*) FROM lms_quizzes WHERE lms_course_id = lc.id) as quizzes_count,
                (SELECT COUNT(*) FROM lms_submissions s JOIN lms_assignments a ON s.assignment_id = a.id WHERE a.lms_course_id = lc.id) as submissions_count,
                (SELECT COUNT(*) FROM lms_quiz_attempts qa JOIN lms_quizzes q ON qa.lms_quiz_id = q.id WHERE q.lms_course_id = lc.id) as quiz_attempts_count,
                (SELECT COUNT(*) FROM lms_quiz_answers ans JOIN lms_quiz_attempts qa ON ans.lms_quiz_attempt_id = qa.id JOIN lms_quizzes q ON qa.lms_quiz_id = q.id WHERE q.lms_course_id = lc.id) as quiz_answers_count,
                (SELECT COUNT(*) FROM lms_attendance_sessions WHERE lms_course_id = lc.id) as attendance_sessions_count,
                (SELECT COUNT(*) FROM lms_attendance_records ar JOIN lms_attendance_sessions s ON ar.lms_attendance_session_id = s.id WHERE s.lms_course_id = lc.id) as attendance_records_count,
                (
                    CASE 
                        WHEN lc.academic_level = 'College' THEN
                            (SELECT COUNT(DISTINCT a.user_id) 
                             FROM college_enrollments ce 
                             JOIN applications a ON ce.application_id = a.id 
                             WHERE ce.college_section_id = lc.academic_section_id 
                               AND ce.subject_id = lc.subject_id 
                               AND ce.status = 'enrolled' 
                               AND a.status = 'enrolled')
                        ELSE
                            (SELECT COUNT(DISTINCT a.user_id) 
                             FROM shs_enrollments se 
                             JOIN applications a ON se.application_id = a.id 
                             WHERE se.shs_section_id = lc.academic_section_id 
                               AND se.subject_id = lc.subject_id 
                               AND se.status = 'enrolled' 
                               AND a.status = 'enrolled')
                    END
                ) as roster_count
            FROM lms_courses lc
            LEFT JOIN subjects s ON lc.subject_id = s.id
            LEFT JOIN college_sections cs ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id
            LEFT JOIN shs_sections ss ON (lc.academic_level = 'SHS' OR lc.academic_level = 'Senior High School') AND lc.academic_section_id = ss.id
            LEFT JOIN users u ON lc.faculty_user_id = u.id
            WHERE lc.id = :id
        ");
        $stmt->execute(['id' => $courseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return [];

        $totalStudentArtifacts = (int)$row['submissions_count'] + (int)$row['quiz_attempts_count'] + (int)$row['attendance_records_count'];
        $totalInstructionalItems = (int)$row['modules_count'] + (int)$row['materials_count'] + (int)$row['assignments_count'] + (int)$row['quizzes_count'];

        $row['total_student_artifacts'] = $totalStudentArtifacts;
        $row['total_instructional_items'] = $totalInstructionalItems;
        $row['has_student_work'] = ($totalStudentArtifacts > 0);
        $row['is_empty_shell'] = ($totalStudentArtifacts === 0 && $totalInstructionalItems === 0 && (int)$row['roster_count'] === 0);

        return $row;
    }

    /**
     * Inspects the LMS course catalog for duplicate shells and orphan courses,
     * assembling rich forensic diagnostics and safe recommendation strategies.
     */
    public function getConflictDiagnostics(): array
    {
        // 1. Scan Duplicate Course Groups
        $dupGroups = $this->pdo->query("
            SELECT 
                academic_section_id,
                subject_id,
                COUNT(*) as duplicate_count,
                GROUP_CONCAT(id ORDER BY id ASC) as course_ids
            FROM lms_courses
            GROUP BY academic_section_id, subject_id
            HAVING COUNT(*) > 1
        ")->fetchAll(PDO::FETCH_ASSOC);

        $duplicateDossiers = [];
        $duplicateCourseIdSet = [];

        foreach ($dupGroups as $group) {
            $ids = array_map('intval', explode(',', $group['course_ids']));
            $shells = [];
            $hasMultipleWithWork = 0;

            foreach ($ids as $cid) {
                $duplicateCourseIdSet[$cid] = true;
                $summary = $this->getCourseArtifactSummary($cid);
                if (!empty($summary)) {
                    $shells[] = $summary;
                    if ($summary['has_student_work']) {
                        $hasMultipleWithWork++;
                    }
                }
            }

            $classification = 'Safe Archive Recommended';
            $recommendation = 'One shell is primary while redundant shell(s) are empty or inactive. Safely mark the redundant shell as Archived.';
            if ($hasMultipleWithWork > 1) {
                $classification = 'Manual Resolution Required';
                $recommendation = 'Multiple shells contain submitted student work or quiz attempts. Automatic merging is prohibited to protect student grades and avoid data corruption. Detailed academic review is required.';
            }

            $duplicateDossiers[] = [
                'section_id' => (int)$group['academic_section_id'],
                'subject_id' => (int)$group['subject_id'],
                'duplicate_count' => count($shells),
                'course_ids' => $group['course_ids'],
                'shells' => $shells,
                'classification' => $classification,
                'recommendation' => $recommendation,
                'multiple_have_student_work' => ($hasMultipleWithWork > 1)
            ];
        }

        // 2. Scan Orphan Courses (Subject missing, Section missing, or offering removed from timetable)
        $orphanQuery = "
            SELECT 
                lc.id as lms_course_id,
                lc.academic_level,
                lc.academic_section_id,
                lc.subject_id,
                lc.status,
                lc.created_at,
                s.id as subject_exists_id,
                s.subject_code,
                s.subject_name,
                COALESCE(cs.id, ss.id) as section_exists_id,
                COALESCE(cs.section_code, ss.section_code) as section_code,
                (
                    CASE 
                        WHEN lc.academic_level = 'College' THEN
                            (SELECT COUNT(*) FROM college_section_subjects css WHERE css.college_section_id = lc.academic_section_id AND css.subject_id = lc.subject_id)
                        ELSE
                            (SELECT COUNT(*) FROM shs_section_subjects sss WHERE sss.shs_section_id = lc.academic_section_id AND sss.subject_id = lc.subject_id)
                    END
                ) as timetable_offering_count
            FROM lms_courses lc
            LEFT JOIN subjects s ON lc.subject_id = s.id
            LEFT JOIN college_sections cs ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id
            LEFT JOIN shs_sections ss ON (lc.academic_level = 'SHS' OR lc.academic_level = 'Senior High School') AND lc.academic_section_id = ss.id
            WHERE s.id IS NULL 
               OR (cs.id IS NULL AND ss.id IS NULL)
               OR (
                   (CASE 
                       WHEN lc.academic_level = 'College' THEN
                           (SELECT COUNT(*) FROM college_section_subjects css WHERE css.college_section_id = lc.academic_section_id AND css.subject_id = lc.subject_id)
                       ELSE
                           (SELECT COUNT(*) FROM shs_section_subjects sss WHERE sss.shs_section_id = lc.academic_section_id AND sss.subject_id = lc.subject_id)
                    END) = 0
               )
        ";

        $orphanRows = $this->pdo->query($orphanQuery)->fetchAll(PDO::FETCH_ASSOC);
        $orphanDossiers = [];

        foreach ($orphanRows as $o) {
            $cid = (int)$o['lms_course_id'];
            
            // Skip courses that are already grouped as duplicates
            if (isset($duplicateCourseIdSet[$cid])) {
                continue;
            }

            $summary = $this->getCourseArtifactSummary($cid);
            if (empty($summary)) continue;

            $reason = '';
            $reasonCategory = '';
            if (empty($o['subject_exists_id'])) {
                $reason = "Subject ID #{$o['subject_id']} does not exist in registrar catalog (Deleted Subject).";
                $reasonCategory = 'missing_subject';
            } elseif (empty($o['section_exists_id'])) {
                $reason = "Section ID #{$o['academic_section_id']} does not exist in section directory (Deleted Section).";
                $reasonCategory = 'missing_section';
            } elseif ((int)$o['timetable_offering_count'] === 0) {
                $reason = "Course offering is not present in official timetable schedule (Delisted/Cancelled Offering).";
                $reasonCategory = 'delisted_timetable';
            }

            $recommendation = '';
            if ($summary['has_student_work']) {
                $recommendation = "Preserve historical academic records. Mark course as Archived. Never delete shells with student submissions or quiz attempts.";
            } elseif ($summary['total_instructional_items'] > 0) {
                $recommendation = "Shell contains instructional modules. Mark as Archived or use as Template Cloner source.";
            } else {
                $recommendation = "Empty shell detached from timetable with zero student work. Safe cleanup or archiving is permitted.";
            }

            $orphanDossiers[] = array_merge($summary, [
                'orphan_reason' => $reason,
                'orphan_category' => $reasonCategory,
                'recommendation' => $recommendation,
                'can_safe_delete' => $summary['is_empty_shell']
            ]);
        }

        // Available faculty for reassignment modal
        $availableFaculty = $this->pdo->query("
            SELECT id, first_name, last_name, email 
            FROM users 
            WHERE role = 'faculty' AND is_active = 1 
            ORDER BY last_name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'duplicate_groups' => $duplicateDossiers,
            'orphan_courses' => $orphanDossiers,
            'available_faculty' => $availableFaculty,
            'summary' => [
                'total_duplicate_groups' => count($duplicateDossiers),
                'total_duplicate_shells' => array_sum(array_column($duplicateDossiers, 'duplicate_count')),
                'total_orphans' => count($orphanDossiers),
                'total_conflicts' => count($duplicateDossiers) + count($orphanDossiers)
            ]
        ];
    }

    /**
     * Resolves an LMS course shell conflict using conservative, safe administrative actions.
     * Enforces strict safety gates:
     * - Automatic merge is explicitly rejected to prevent student grade corruption.
     * - Destruction deletion is strictly prohibited if any student work or instructional items exist.
     * - Every action logs to the institutional audit trail.
     */
    public function resolveConflict(string $action, int $courseId, int $adminUserId, array $options = []): array
    {
        // 1. Verify existence of course shell
        $stmt = $this->pdo->prepare("SELECT id, status, academic_level, academic_section_id, subject_id FROM lms_courses WHERE id = :id");
        $stmt->execute(['id' => $courseId]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$course) {
            throw new Exception("LMS course shell #{$courseId} was not found.");
        }

        $summary = $this->getCourseArtifactSummary($courseId);
        $notes = trim($options['notes'] ?? '');

        switch ($action) {
            case 'archive_duplicate':
            case 'archive_orphan':
            case 'archive_shell':
                // Safe preservation: transitions status to archived without modifying or deleting any content
                $upd = $this->pdo->prepare("UPDATE lms_courses SET status = 'archived', updated_at = NOW() WHERE id = :id");
                $upd->execute(['id' => $courseId]);

                if (function_exists('logActivity')) {
                    $desc = sprintf(
                        "Administrator archived %s course shell #%d to resolve conflict. Contains %d modules, %d student submissions, %d quiz attempts. Notes: %s",
                        ($action === 'archive_orphan' ? 'orphan' : 'duplicate'),
                        $courseId,
                        $summary['modules_count'],
                        $summary['submissions_count'],
                        $summary['quiz_attempts_count'],
                        $notes ?: 'None'
                    );

                    logActivity(
                        $adminUserId,
                        'bi-archive',
                        'LMS Course Shell Archived (Conflict Resolution)',
                        $desc,
                        "lms_courses:{$courseId}",
                        ['status' => $course['status']],
                        ['status' => 'archived'],
                        'LMS Conflict Resolution: Safe Archive'
                    );
                }

                return [
                    'success' => true,
                    'action' => $action,
                    'course_id' => $courseId,
                    'message' => "Course shell #{$courseId} has been safely marked as Archived. All instructional modules and student artifacts have been completely preserved."
                ];

            case 'reassign_faculty':
                $facultyId = !empty($options['faculty_user_id']) ? (int)$options['faculty_user_id'] : null;
                $res = $this->reassignFaculty($courseId, $facultyId, $adminUserId, false);
                return [
                    'success' => $res,
                    'action' => 'reassign_faculty',
                    'course_id' => $courseId,
                    'message' => "Course shell #{$courseId} faculty instructor was successfully updated."
                ];

            case 'flag_quarantine':
                // Flag for manual review
                if (function_exists('logActivity')) {
                    logActivity(
                        $adminUserId,
                        'bi-shield-exclamation',
                        'LMS Course Shell Flagged for Manual Review',
                        "Course shell #{$courseId} was flagged for manual administrative review. Conflict diagnostics: " . ($summary['has_student_work'] ? 'Contains student work' : 'No student work') . ". Notes: " . ($notes ?: 'Awaiting registrar/chair investigation'),
                        "lms_courses:{$courseId}",
                        null,
                        ['conflict_flag' => 'quarantined_for_review', 'notes' => $notes],
                        'LMS Conflict Resolution: Flag for Review'
                    );
                }

                return [
                    'success' => true,
                    'action' => 'flag_quarantine',
                    'course_id' => $courseId,
                    'message' => "Course shell #{$courseId} has been flagged for manual review and logged in the LMS audit trail."
                ];

            case 'delete_empty_shell':
                // STRICT SAFETY GATE: Only allowed if zero student artifacts and zero instructional items
                if (!$summary['is_empty_shell']) {
                    throw new Exception(
                        "Destructive cleanup blocked: Course shell #{$courseId} contains " .
                        "{$summary['total_instructional_items']} instructional item(s) and " .
                        "{$summary['total_student_artifacts']} student artifact(s). " .
                        "Destructive deletion is prohibited to protect academic records. Please use 'Mark as Archived' instead."
                    );
                }

                $this->pdo->beginTransaction();
                try {
                    $del = $this->pdo->prepare("DELETE FROM lms_courses WHERE id = :id");
                    $del->execute(['id' => $courseId]);
                    $this->pdo->commit();
                } catch (\Throwable $e) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    throw new Exception("Failed to delete empty course shell: " . $e->getMessage(), 0, $e);
                }

                if (function_exists('logActivity')) {
                    logActivity(
                        $adminUserId,
                        'bi-trash',
                        'LMS Empty Course Shell Removed (Conflict Resolution)',
                        "Deleted empty detached course shell #{$courseId} (0 modules, 0 student submissions, 0 attempts). Notes: " . ($notes ?: 'None'),
                        "lms_courses:{$courseId}",
                        null,
                        null,
                        'LMS Conflict Resolution: Empty Shell Cleanup'
                    );
                }

                return [
                    'success' => true,
                    'action' => 'delete_empty_shell',
                    'course_id' => $courseId,
                    'message' => "Empty course shell #{$courseId} was safely removed. No student or instructional records were affected."
                ];

            case 'merge_shells':
            case 'merge':
                // EXPLICITLY UNSUPPORTED BY DOMAIN INTEGRITY POLICY
                throw new Exception(
                    "Automatic course merging is unsupported by policy. Moving student submissions, quiz attempts, and grade records across course boundaries risks corrupting student gradebooks and transcripts. Please mark the secondary shell as Archived instead."
                );

            default:
                throw new Exception("Unrecognized resolution action '{$action}'.");
        }
    }
}
