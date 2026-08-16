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
                lc.id as lms_course_id,
                s.subject_code as code, 
                s.subject_name as name, 
                s.units,
                ss.section_code as section_name, 
                u.first_name, 
                u.last_name
            FROM shs_enrollments se
            JOIN applications a ON se.application_id = a.id
            JOIN subjects s ON se.subject_id = s.id
            JOIN shs_sections ss ON se.shs_section_id = ss.id
            JOIN lms_courses lc ON lc.academic_level = 'SHS' 
                                AND lc.academic_section_id = ss.id 
                                AND lc.subject_id = s.id
            JOIN users u ON lc.faculty_user_id = u.id
            WHERE a.user_id = :uid 
              AND a.status = 'enrolled'
              AND lc.status = 'active'
        ");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isStudentAuthorizedForCourse(int $userId, int $lmsCourseId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1
            FROM shs_enrollments se
            JOIN applications a ON se.application_id = a.id
            JOIN shs_sections ss ON se.shs_section_id = ss.id
            JOIN lms_courses lc ON lc.academic_level = 'SHS' 
                                AND lc.academic_section_id = ss.id 
                                AND lc.subject_id = se.subject_id
            WHERE a.user_id = :uid 
              AND a.status = 'enrolled'
              AND lc.id = :lcid
            LIMIT 1
        ");
        $stmt->execute([
            'uid' => $userId,
            'lcid' => $lmsCourseId
        ]);
        return (bool)$stmt->fetchColumn();
    }
}
