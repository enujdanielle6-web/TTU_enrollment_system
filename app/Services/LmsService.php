<?php
namespace App\Services;

use App\Core\Database;
use App\Repositories\EnrollmentRepositoryInterface;
use App\Repositories\CollegeEnrollmentRepository;
use App\Repositories\ShsEnrollmentRepository;
use PDO;

class LmsService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Determines the appropriate repository based on the student's active application.
     */
    private function getStudentRepository(int $userId): ?EnrollmentRepositoryInterface
    {
        $stmt = $this->pdo->prepare("
            SELECT academic_level 
            FROM applications 
            WHERE user_id = :uid AND status = 'enrolled' 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute(['uid' => $userId]);
        $level = $stmt->fetchColumn();

        if ($level === 'College') {
            return new CollegeEnrollmentRepository();
        } elseif ($level === 'Senior High School') {
            return new ShsEnrollmentRepository();
        }

        return null;
    }

    public function getStudentCourses(int $userId): array
    {
        $repo = $this->getStudentRepository($userId);
        if (!$repo) {
            return [];
        }
        return $repo->getActiveStudentCourses($userId);
    }

    public function isStudentAuthorizedForCourse(int $userId, int $lmsCourseId): bool
    {
        $repo = $this->getStudentRepository($userId);
        if (!$repo) {
            return false;
        }
        return $repo->isStudentAuthorizedForCourse($userId, $lmsCourseId);
    }

    public function getFacultyCourses(int $facultyUserId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                lc.id as lms_course_id,
                lc.academic_level,
                s.subject_code as code, 
                s.subject_name as name,
                COALESCE(cs.section_code, ss.section_code) as section_name
            FROM lms_courses lc
            JOIN subjects s ON lc.subject_id = s.id
            LEFT JOIN college_sections cs ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id
            LEFT JOIN shs_sections ss ON lc.academic_level = 'SHS' AND lc.academic_section_id = ss.id
            WHERE lc.faculty_user_id = :fid AND lc.status = 'active'
        ");
        $stmt->execute(['fid' => $facultyUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCourseDetails(int $lmsCourseId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                lc.id as lms_course_id,
                lc.academic_level,
                lc.academic_section_id,
                lc.subject_id,
                s.subject_code, 
                s.subject_name,
                s.units,
                COALESCE(cs.section_code, ss.section_code) as section_code,
                u.first_name as instructor_first,
                u.last_name as instructor_last,
                u.email as instructor_email
            FROM lms_courses lc
            JOIN subjects s ON lc.subject_id = s.id
            LEFT JOIN college_sections cs ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id
            LEFT JOIN shs_sections ss ON lc.academic_level = 'SHS' AND lc.academic_section_id = ss.id
            JOIN users u ON lc.faculty_user_id = u.id
            WHERE lc.id = :lcid
        ");
        $stmt->execute(['lcid' => $lmsCourseId]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $course ?: null;
    }

    public function isFacultyAuthorizedForCourse(int $userId, int $lmsCourseId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM lms_courses 
            WHERE id = :lcid AND faculty_user_id = :uid AND status = 'active'
        ");
        $stmt->execute(['lcid' => $lmsCourseId, 'uid' => $userId]);
        return (bool)$stmt->fetchColumn();
    }

    public function getMaterialsByModule(int $moduleId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, file_name, mime_type, file_size, created_at 
            FROM lms_materials 
            WHERE lms_module_id = :mid
            ORDER BY created_at ASC
        ");
        $stmt->execute(['mid' => $moduleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getModulesWithMaterialsForCourse(int $lmsCourseId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, title, description, display_order, created_at
            FROM lms_modules
            WHERE lms_course_id = :lcid
            ORDER BY display_order ASC, created_at ASC
        ");
        $stmt->execute(['lcid' => $lmsCourseId]);
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($modules)) {
            return [];
        }

        $moduleIds = array_column($modules, 'id');
        $placeholders = implode(',', array_fill(0, count($moduleIds), '?'));

        $matStmt = $this->pdo->prepare("
            SELECT id, lms_module_id, file_name, file_path, mime_type, file_size, created_at
            FROM lms_materials
            WHERE lms_module_id IN ($placeholders)
            ORDER BY created_at ASC
        ");
        $matStmt->execute($moduleIds);
        $materials = $matStmt->fetchAll(PDO::FETCH_ASSOC);

        $materialsByModule = [];
        foreach ($materials as $mat) {
            $materialsByModule[$mat['lms_module_id']][] = $mat;
        }

        foreach ($modules as &$mod) {
            $mod['materials'] = $materialsByModule[$mod['id']] ?? [];
        }

        return $modules;
    }

    public function createModule(int $courseId, string $title, int $orderIndex = 0): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO lms_modules (lms_course_id, title, display_order)
            VALUES (:lcid, :title, :order)
        ");
        $stmt->execute([
            'lcid' => $courseId,
            'title' => $title,
            'order' => $orderIndex
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function createMaterial(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO lms_materials (lms_module_id, file_name, file_path, mime_type, file_size)
            VALUES (:mid, :name, :path, :mime, :size)
        ");
        $stmt->execute([
            'mid' => $data['lms_module_id'],
            'name' => $data['title'],
            'path' => $data['file_path'],
            'mime' => $data['file_type'] ?? 'application/octet-stream',
            'size' => $data['file_size'] ?? 0
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getMaterial(int $materialId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT mat.id, mat.lms_module_id, mat.file_name, mat.file_path, mat.mime_type, mat.file_size, mod.lms_course_id
            FROM lms_materials mat
            JOIN lms_modules mod ON mat.lms_module_id = mod.id
            WHERE mat.id = :mid
        ");
        $stmt->execute(['mid' => $materialId]);
        $material = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $material ?: null;
    }

    public function getAssignmentsByCourse(int $lmsCourseId, bool $publishedOnly = true): array
    {
        $sql = "SELECT * FROM lms_assignments WHERE lms_course_id = :lcid";
        if ($publishedOnly) {
            $sql .= " AND status = 'published'";
        }
        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['lcid' => $lmsCourseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAssignment(int $assignmentId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lms_assignments WHERE id = :id");
        $stmt->execute(['id' => $assignmentId]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        return $assignment ?: null;
    }

    public function createAssignment(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO lms_assignments (lms_course_id, lms_module_id, title, description, due_date, max_score, status)
            VALUES (:course, :module, :title, :desc, :due, :max, :status)
        ");
        $stmt->execute([
            'course' => $data['lms_course_id'],
            'module' => $data['lms_module_id'] ?? null,
            'title' => $data['title'],
            'desc' => $data['description'] ?? null,
            'due' => $data['due_date'] ?? null,
            'max' => $data['max_score'] ?? 100,
            'status' => $data['status'] ?? 'draft'
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateAssignment(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE lms_assignments 
            SET title = :title, description = :desc, due_date = :due, max_score = :max, status = :status
            WHERE id = :id
        ");
        return $stmt->execute([
            'title' => $data['title'],
            'desc' => $data['description'] ?? null,
            'due' => $data['due_date'] ?? null,
            'max' => $data['max_score'] ?? 100,
            'status' => $data['status'] ?? 'draft',
            'id' => $id
        ]);
    }

    public function getStudentSubmission(int $assignmentId, int $studentId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lms_submissions WHERE assignment_id = :aid AND student_id = :sid ORDER BY submitted_at DESC LIMIT 1");
        $stmt->execute(['aid' => $assignmentId, 'sid' => $studentId]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        return $submission ?: null;
    }

    public function getSubmissionById(int $submissionId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lms_submissions WHERE id = :id");
        $stmt->execute(['id' => $submissionId]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        return $submission ?: null;
    }

    public function submitAssignment(int $assignmentId, int $studentId, array $fileData, string $status = 'SUBMITTED'): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO lms_submissions (assignment_id, student_id, file_path, file_name, mime_type, file_size, status)
            VALUES (:aid, :sid, :path, :name, :mime, :size, :status)
        ");
        $stmt->execute([
            'aid' => $assignmentId,
            'sid' => $studentId,
            'path' => $fileData['file_path'],
            'name' => $fileData['file_name'],
            'mime' => $fileData['mime_type'] ?? null,
            'size' => $fileData['file_size'] ?? 0,
            'status' => $status
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getSubmissionsForAssignment(int $assignmentId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, u.first_name, u.last_name 
            FROM lms_submissions s
            JOIN users u ON s.student_id = u.id
            WHERE s.assignment_id = :aid
            ORDER BY s.submitted_at DESC
        ");
        $stmt->execute(['aid' => $assignmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function gradeSubmission(int $submissionId, int $graderId, float $grade, ?string $feedback): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE lms_submissions 
            SET grade = :grade, feedback = :feedback, status = 'GRADED', graded_at = CURRENT_TIMESTAMP, graded_by = :grader
            WHERE id = :id
        ");
        return $stmt->execute([
            'grade' => $grade,
            'feedback' => $feedback,
            'grader' => $graderId,
            'id' => $submissionId
        ]);
    }
}
