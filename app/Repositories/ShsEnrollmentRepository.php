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
                s.units
            FROM shs_enrollments se
            JOIN applications a ON se.application_id = a.id
            JOIN subjects s ON se.subject_id = s.id
            JOIN shs_sections ss ON se.shs_section_id = ss.id
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
                    s.units
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

        $facStmt = $this->pdo->query("SELECT id FROM users WHERE role = 'faculty' ORDER BY id ASC LIMIT 1");
        $defaultFacultyId = (int)$facStmt->fetchColumn() ?: 18;

        $courses = [];
        foreach ($enrollments as $enr) {
            $secId = (int)$enr['shs_section_id'];
            $subId = (int)$enr['subject_id'];

            $lcStmt = $this->pdo->prepare("
                SELECT lc.id, lc.faculty_user_id, u.first_name, u.last_name
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
                $ins = $this->pdo->prepare("
                    INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status)
                    VALUES ('SHS', :sec_id, :sub_id, :fac_id, 'active')
                ");
                $ins->execute([
                    'sec_id' => $secId,
                    'sub_id' => $subId,
                    'fac_id' => $defaultFacultyId
                ]);
                $lmsCourseId = (int)$this->pdo->lastInsertId();
                $uStmt = $this->pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                $uStmt->execute([$defaultFacultyId]);
                $facUser = $uStmt->fetch(PDO::FETCH_ASSOC);
                $firstName = $facUser['first_name'] ?? 'Faculty';
                $lastName = $facUser['last_name'] ?? 'Instructor';
            } else {
                $lmsCourseId = (int)$lmsCourse['id'];
                $firstName = $lmsCourse['first_name'] ?? 'Faculty';
                $lastName = $lmsCourse['last_name'] ?? 'Instructor';
            }

            $courses[] = [
                'lms_course_id' => $lmsCourseId,
                'code' => $enr['code'],
                'name' => $enr['name'],
                'units' => $enr['units'],
                'section_name' => $enr['section_name'],
                'first_name' => $firstName,
                'last_name' => $lastName
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
