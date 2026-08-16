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
        $stmt = $this->pdo->prepare("
            SELECT 
                lc.id as lms_course_id,
                s.subject_code as code, 
                s.subject_name as name, 
                s.units,
                cs.section_code as section_name, 
                u.first_name, 
                u.last_name
            FROM college_enrollments ce
            JOIN applications a ON ce.application_id = a.id
            JOIN subjects s ON ce.subject_id = s.id
            JOIN college_sections cs ON ce.college_section_id = cs.id
            JOIN lms_courses lc ON lc.academic_level = 'College' 
                                AND lc.academic_section_id = cs.id 
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
            FROM college_enrollments ce
            JOIN applications a ON ce.application_id = a.id
            JOIN college_sections cs ON ce.college_section_id = cs.id
            JOIN lms_courses lc ON lc.academic_level = 'College' 
                                AND lc.academic_section_id = cs.id 
                                AND lc.subject_id = ce.subject_id
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
