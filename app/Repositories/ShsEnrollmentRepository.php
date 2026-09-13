<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class ShsEnrollmentRepository implements EnrollmentRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function getActiveStudentCourses(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                se.id as enrollment_id,
                a.id as application_id,
                se.shs_section_id,
                ss.section_code as section_name,
                s.id as subject_id,
                s.subject_code as code, 
                s.subject_name as name, 
                s.units,
                sss.day,
                sss.start_time,
                sss.end_time,
                sss.room,
                sss.delivery_mode
            FROM shs_enrollments se
            JOIN applications a ON se.application_id = a.id
            JOIN subjects s ON se.subject_id = s.id
            JOIN shs_sections ss ON se.shs_section_id = ss.id
            LEFT JOIN shs_section_subjects sss ON sss.shs_section_id = se.shs_section_id AND sss.subject_id = se.subject_id
            WHERE a.user_id = :uid 
              AND a.status IN ('enrolled', 'approved')
        ");
        $stmt->execute(['uid' => $userId]);
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($enrollments)) {
            $stmtSec = $this->pdo->prepare("
                SELECT 
                    a.id as application_id,
                    a.section_id as shs_section_id,
                    ss.section_code as section_name,
                    s.id as subject_id,
                    s.subject_code as code, 
                    s.subject_name as name, 
                    s.units,
                    sss.day,
                    sss.start_time,
                    sss.end_time,
                    sss.room,
                    sss.delivery_mode
                FROM applications a
                JOIN shs_sections ss ON a.section_id = ss.id
                JOIN shs_section_subjects sss ON sss.shs_section_id = ss.id
                JOIN subjects s ON sss.subject_id = s.id
                WHERE a.user_id = :uid AND a.status IN ('enrolled', 'approved')
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
            $secId = (int)$enr['shs_section_id'];
            $subId = (int)$enr['subject_id'];

            $lcStmt = $this->pdo->prepare("
                SELECT lc.id, lc.faculty_user_id, u.first_name, u.last_name,
                       (SELECT COUNT(*) FROM lms_modules m WHERE m.lms_course_id = lc.id AND m.status = 'published') as module_count,
                       (SELECT COUNT(*) FROM lms_assignments a WHERE a.lms_course_id = lc.id AND a.status = 'published') as assignment_count,
                       (SELECT COUNT(*) FROM lms_quizzes q WHERE q.lms_course_id = lc.id AND q.status = 'published') as quiz_count
                FROM lms_courses lc
                LEFT JOIN users u ON lc.faculty_user_id = u.id
                WHERE lc.academic_level = 'SHS' 
                  AND lc.academic_section_id = :sec_id 
                  AND lc.subject_id = :sub_id
                LIMIT 1
            ");
            $lcStmt->execute(['sec_id' => $secId, 'sub_id' => $subId]);
            $lmsCourse = $lcStmt->fetch(PDO::FETCH_ASSOC);

            if (!$lmsCourse) {
                // Resolve assigned faculty strictly from section subject timetable
                $secSubStmt = $this->pdo->prepare("
                    SELECT sss.faculty_user_id, sss.instructor 
                    FROM shs_section_subjects sss
                    WHERE sss.shs_section_id = :sec_id AND sss.subject_id = :sub_id
                    LIMIT 1
                ");
                $secSubStmt->execute(['sec_id' => $secId, 'sub_id' => $subId]);
                $secSub = $secSubStmt->fetch(PDO::FETCH_ASSOC);
                $assignedFacultyId = !empty($secSub['faculty_user_id']) ? (int)$secSub['faculty_user_id'] : null;

                if (!$assignedFacultyId && !empty($secSub['instructor']) && strtoupper($secSub['instructor']) !== 'TBA') {
                    $fnStmt = $this->pdo->prepare("SELECT id FROM users WHERE role = 'faculty' AND (CONCAT(first_name, ' ', last_name) = ? OR last_name = ?) LIMIT 1");
                    $fnStmt->execute([$secSub['instructor'], $secSub['instructor']]);
                    $assignedFacultyId = (int)$fnStmt->fetchColumn() ?: null;
                }

                if ($assignedFacultyId) {
                    $ins = $this->pdo->prepare("
                        INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status)
                        VALUES ('SHS', :sec_id, :sub_id, :fac_id, 'active')
                        ON DUPLICATE KEY UPDATE faculty_user_id = VALUES(faculty_user_id)
                    ");
                    $ins->execute([
                        'sec_id' => $secId,
                        'sub_id' => $subId,
                        'fac_id' => $assignedFacultyId
                    ]);
                    $lmsCourseId = (int)$this->pdo->lastInsertId();
                    if ($lmsCourseId === 0) {
                        $lcStmt->execute(['sec_id' => $secId, 'sub_id' => $subId]);
                        $existing = $lcStmt->fetch(PDO::FETCH_ASSOC);
                        $lmsCourseId = (int)($existing['id'] ?? 0);
                    }
                    $uStmt = $this->pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                    $uStmt->execute([$assignedFacultyId]);
                    $facUser = $uStmt->fetch(PDO::FETCH_ASSOC);
                    $firstName = $facUser['first_name'] ?? 'Faculty';
                    $lastName = $facUser['last_name'] ?? 'Instructor';
                    $moduleCount = 0;
                    $assignmentCount = 0;
                    $quizCount = 0;
                } else {
                    continue;
                }
            } else {
                $lmsCourseId = (int)$lmsCourse['id'];
                $firstName = $lmsCourse['first_name'] ?? 'Faculty';
                $lastName = $lmsCourse['last_name'] ?? 'Instructor';
                $moduleCount = (int)($lmsCourse['module_count'] ?? 0);
                $assignmentCount = (int)($lmsCourse['assignment_count'] ?? 0);
                $quizCount = (int)($lmsCourse['quiz_count'] ?? 0);
            }

            if (isset($seenCourseIds[$lmsCourseId])) {
                continue;
            }
            $seenCourseIds[$lmsCourseId] = true;

            $courses[] = [
                'lms_course_id' => $lmsCourseId,
                'code' => $enr['code'],
                'name' => $enr['name'],
                'units' => $enr['units'],
                'section_name' => $enr['section_name'],
                'first_name' => $firstName,
                'last_name' => $lastName,
                'day' => $enr['day'] ?? null,
                'start_time' => $enr['start_time'] ?? null,
                'end_time' => $enr['end_time'] ?? null,
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
        $courses = $this->getActiveStudentCourses($userId);
        foreach ($courses as $c) {
            if ((int)$c['lms_course_id'] === $lmsCourseId) {
                return true;
            }
        }
        return false;
    }
}
