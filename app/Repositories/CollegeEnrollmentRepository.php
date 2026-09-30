<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class CollegeEnrollmentRepository implements EnrollmentRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function getActiveStudentCourses(int $userId): array
    {
        // 1. Fetch subjects enrolled via college_enrollments
        $stmt = $this->pdo->prepare("
            SELECT 
                ce.id as enrollment_id,
                a.id as application_id,
                ce.college_section_id,
                cs.section_code as section_name,
                s.id as subject_id,
                s.subject_code as code, 
                s.subject_name as name, 
                s.units,
                css.day,
                css.start_time,
                css.end_time,
                css.room,
                css.delivery_mode
            FROM college_enrollments ce
            JOIN applications a ON ce.application_id = a.id
            JOIN subjects s ON ce.subject_id = s.id
            JOIN college_sections cs ON ce.college_section_id = cs.id
            LEFT JOIN college_section_subjects css ON css.college_section_id = ce.college_section_id AND css.subject_id = ce.subject_id
            WHERE a.user_id = :uid 
              AND a.status = 'enrolled'
              AND ce.status = 'enrolled'
        ");
        $stmt->execute(['uid' => $userId]);
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fallback to section subjects if college_enrollments is empty but section is assigned
        if (empty($enrollments)) {
            $stmtSec = $this->pdo->prepare("
                SELECT 
                    a.id as application_id,
                    a.section_id as college_section_id,
                    cs.section_code as section_name,
                    s.id as subject_id,
                    s.subject_code as code, 
                    s.subject_name as name, 
                    s.units,
                    css.day,
                    css.start_time,
                    css.end_time,
                    css.room,
                    css.delivery_mode
                FROM applications a
                JOIN college_sections cs ON a.section_id = cs.id
                JOIN college_section_subjects css ON css.college_section_id = cs.id
                JOIN subjects s ON css.subject_id = s.id
                WHERE a.user_id = :uid AND a.status = 'enrolled'
            ");
            $stmtSec->execute(['uid' => $userId]);
            $enrollments = $stmtSec->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($enrollments)) {
            return [];
        }

        $courses = [];
        $seenCourseIds = [];
        foreach ($enrollments as $enr) {
            $secId = (int)$enr['college_section_id'];
            $subId = (int)$enr['subject_id'];

            // Query LMS Course with module, assignment, and quiz counts (Pure Read Operation)
            $lcStmt = $this->pdo->prepare("
                SELECT lc.id, lc.faculty_user_id, u.first_name, u.last_name,
                       (SELECT COUNT(*) FROM lms_modules m WHERE m.lms_course_id = lc.id AND m.status = 'published') as module_count,
                       (SELECT COUNT(*) FROM lms_assignments a WHERE a.lms_course_id = lc.id AND a.status = 'published') as assignment_count,
                       (SELECT COUNT(*) FROM lms_quizzes q WHERE q.lms_course_id = lc.id AND q.status = 'published') as quiz_count
                FROM lms_courses lc
                LEFT JOIN users u ON lc.faculty_user_id = u.id
                WHERE lc.academic_level = 'College' 
                  AND lc.academic_section_id = :sec_id 
                  AND lc.subject_id = :sub_id
                LIMIT 1
            ");
            $lcStmt->execute(['sec_id' => $secId, 'sub_id' => $subId]);
            $lmsCourse = $lcStmt->fetch(PDO::FETCH_ASSOC);

            if ($lmsCourse) {
                $lmsCourseId = (int)$lmsCourse['id'];
                $firstName = !empty($lmsCourse['first_name']) ? $lmsCourse['first_name'] : 'Instructor';
                $lastName = !empty($lmsCourse['last_name']) ? $lmsCourse['last_name'] : 'TBA';
                $moduleCount = (int)($lmsCourse['module_count'] ?? 0);
                $assignmentCount = (int)($lmsCourse['assignment_count'] ?? 0);
                $quizCount = (int)($lmsCourse['quiz_count'] ?? 0);
            } else {
                // Course shell not yet provisioned; read timetable instructor metadata for display without mutating DB
                $secSubStmt = $this->pdo->prepare("
                    SELECT css.instructor 
                    FROM college_section_subjects css
                    WHERE css.college_section_id = :sec_id AND css.subject_id = :sub_id
                    LIMIT 1
                ");
                $secSubStmt->execute(['sec_id' => $secId, 'sub_id' => $subId]);
                $instName = $secSubStmt->fetchColumn();

                $lmsCourseId = null;
                $firstName = (!empty($instName) && strtoupper($instName) !== 'TBA') ? $instName : 'Instructor';
                $lastName = (!empty($instName) && strtoupper($instName) !== 'TBA') ? '' : 'TBA';
                $moduleCount = 0;
                $assignmentCount = 0;
                $quizCount = 0;
            }

            $courseKey = $lmsCourseId ? (string)$lmsCourseId : "unmapped_{$secId}_{$subId}";
            if (isset($seenCourseIds[$courseKey])) {
                continue;
            }
            $seenCourseIds[$courseKey] = true;

            $courses[] = [
                'id' => $lmsCourseId,
                'lms_course_id' => $lmsCourseId,
                'subject_id' => $subId,
                'code' => $enr['code'],
                'name' => $enr['name'],
                'subject_code' => $enr['code'],
                'subject_name' => $enr['name'],
                'subject_title' => $enr['name'],
                'units' => $enr['units'],
                'section_name' => $enr['section_name'],
                'first_name' => $firstName,
                'last_name' => $lastName,
                'faculty_name' => trim($firstName . ' ' . $lastName),
                'day' => $enr['day'] ?? null,
                'start_time' => $enr['start_time'] ?? null,
                'end_time' => $enr['end_time'] ?? null,
                'schedule_days' => $enr['day'] ?? null,
                'schedule_time' => ($enr['start_time'] && $enr['end_time']) ? date('h:i A', strtotime($enr['start_time'])) . ' - ' . date('h:i A', strtotime($enr['end_time'])) : null,
                'room' => $enr['room'] ?? null,
                'delivery_mode' => $enr['delivery_mode'] ?? 'Face-to-Face',
                'module_count' => $moduleCount,
                'assignment_count' => $assignmentCount,
                'quiz_count' => $quizCount
            ];
        }

        return $courses;
    }

    public function isStudentAuthorizedForCourse(int $userId, int $lmsCourseId): bool
    {
        if ($lmsCourseId <= 0) {
            return false;
        }

        $courses = $this->getActiveStudentCourses($userId);
        foreach ($courses as $c) {
            if (!empty($c['lms_course_id']) && (int)$c['lms_course_id'] === $lmsCourseId) {
                return true;
            }
        }
        return false;
    }
}
