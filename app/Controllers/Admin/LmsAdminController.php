<?php
namespace App\Controllers\Admin;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use Exception;

class LmsAdminController extends BaseController
{
    public function courseGenerator(Request $request, Response $response)
    {
        $pdo = Database::getConnection();

        // 1. Fetch unmapped college section subjects
        $college_stmt = $pdo->prepare("
            SELECT css.id, 'College' as academic_level, cs.id as section_id, cs.section_code, s.id as subject_id, s.subject_code, s.subject_name, css.instructor as old_instructor_string
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
            SELECT sss.id, 'SHS' as academic_level, ss.id as section_id, ss.section_code, s.id as subject_id, s.subject_code, s.subject_name, sss.instructor as old_instructor_string
            FROM shs_section_subjects sss
            JOIN shs_sections ss ON sss.shs_section_id = ss.id
            JOIN subjects s ON sss.subject_id = s.id
            LEFT JOIN lms_courses lc ON lc.academic_level = 'SHS' AND lc.academic_section_id = ss.id AND lc.subject_id = s.id
            WHERE lc.id IS NULL
        ");
        $shs_stmt->execute();
        $shs_courses = $shs_stmt->fetchAll(PDO::FETCH_ASSOC);

        $unmapped_courses = array_merge($college_courses, $shs_courses);

        // 3. Fetch all active faculty members
        $faculty_stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE role = 'faculty' AND is_active = 1 ORDER BY last_name ASC");
        $faculty_stmt->execute();
        $faculty_users = $faculty_stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'LMS Course Generator';
        require_once __DIR__ . '/../../Views/components/admin_navbar.php';

        return $this->render('admin/system/lms_course_generator', get_defined_vars());
    }

    public function generateLmsCourse(Request $request, Response $response)
    {
        if ($request->isPost()) {
            $data = $request->getBody();
            
            $academic_level = $data['academic_level'] ?? '';
            $section_id = filter_var($data['section_id'] ?? 0, FILTER_VALIDATE_INT);
            $subject_id = filter_var($data['subject_id'] ?? 0, FILTER_VALIDATE_INT);
            $faculty_user_id = filter_var($data['faculty_user_id'] ?? 0, FILTER_VALIDATE_INT);

            if (!in_array($academic_level, ['College', 'SHS']) || !$section_id || !$subject_id || !$faculty_user_id) {
                $_SESSION['error_message'] = 'Invalid form data provided.';
                $response->redirect('/sia/admin/lms/generator');
                return;
            }

            try {
                $pdo = Database::getConnection();

                // Check if already mapped
                $check = $pdo->prepare("SELECT id FROM lms_courses WHERE academic_level = :lvl AND academic_section_id = :sec AND subject_id = :sub LIMIT 1");
                $check->execute([
                    'lvl' => $academic_level,
                    'sec' => $section_id,
                    'sub' => $subject_id
                ]);
                
                if ($check->fetch()) {
                    $_SESSION['error_message'] = 'This course is already mapped and generated.';
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

                    $_SESSION['success_message'] = 'LMS Course generated successfully.';
                }

            } catch (Exception $e) {
                error_log("LMS Course Generation Error: " . $e->getMessage());
                $_SESSION['error_message'] = 'An error occurred while generating the course.';
            }

            $response->redirect('/sia/admin/lms/generator');
            return;
        }

        $response->redirect('/sia/admin/lms/generator');
    }
}
