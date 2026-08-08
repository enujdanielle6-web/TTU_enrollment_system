<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use Exception;

class StudentController extends BaseController
{
    public function dashboard(Request $request, Response $response)
    {
        $pdo = Database::getConnection();

        // Fetch user's enrolled subjects
        $enrolled_courses = [];
        try {
            $stmt = $pdo->prepare("
                SELECT s.id as subject_id, s.subject_code as code, s.subject_name as name, cs.section_code as section_name, s.units
                FROM college_enrollments ce
                JOIN applications a ON ce.application_id = a.id
                JOIN subjects s ON ce.subject_id = s.id
                LEFT JOIN college_sections cs ON ce.college_section_id = cs.id
                WHERE a.user_id = :uid AND a.status = 'enrolled'
            ");
            $stmt->execute(['uid' => $_SESSION['user_id'] ?? 0]);
            $enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("LMS Dashboard Error: " . $e->getMessage());
        }

        $pageTitle = 'Dashboard - TTU LMS';

        return $this->render('lms/student/dashboard', get_defined_vars());
    }

    public function course(Request $request, Response $response)
    {
        $pdo = Database::getConnection();

        $subject_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$subject_id) {
            $response->redirect("/sia/lms/student/dashboard.php");
            return;
        }

        // 1. Verify Enrollment & Fetch Subject Data
        try {
            // Strict check: Student must be enrolled in this subject_id
            $stmt = $pdo->prepare("
                SELECT s.id, s.subject_code, s.subject_name, s.units, cs.section_code 
                FROM college_enrollments ce
                JOIN applications a ON ce.application_id = a.id
                JOIN subjects s ON ce.subject_id = s.id
                LEFT JOIN college_sections cs ON ce.college_section_id = cs.id
                WHERE a.user_id = :uid AND a.status = 'enrolled' AND s.id = :sid
                LIMIT 1
            ");
            $stmt->execute(['uid' => $_SESSION['user_id'] ?? 0, 'sid' => $subject_id]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$course) {
                // Not enrolled or doesn't exist
                $response->redirect("/sia/lms/student/dashboard.php");
                return;
            }

            // 2. Fetch LMS Course Metadata (Welcome message, instructor info)
            $lms_stmt = $pdo->prepare("
                SELECT lc.welcome_message, lc.thumbnail_path, u.first_name, u.last_name, u.email
                FROM lms_courses lc
                LEFT JOIN users u ON lc.teacher_id = u.id
                WHERE lc.subject_id = :sid
                LIMIT 1
            ");
            $lms_stmt->execute(['sid' => $subject_id]);
            $lms_course = $lms_stmt->fetch(PDO::FETCH_ASSOC);

            $instructor_name = $lms_course && $lms_course['first_name'] ? $lms_course['first_name'] . ' ' . $lms_course['last_name'] : 'Instructor TBA';
            $instructor_email = $lms_course && $lms_course['email'] ? $lms_course['email'] : 'N/A';
            $welcome_message = $lms_course && $lms_course['welcome_message'] ? $lms_course['welcome_message'] : 'Welcome to ' . htmlspecialchars($course['subject_name']) . '! Your instructor will post materials soon.';
            $thumbnail_path = $lms_course && $lms_course['thumbnail_path'] ? $lms_course['thumbnail_path'] : '../../images/default_course.jpg';

            // 3. Fetch Modules (Just the structure for now)
            $mod_stmt = $pdo->prepare("
                SELECT m.id, m.title, m.description, m.sequence_order
                FROM lms_modules m
                JOIN lms_courses c ON m.lms_course_id = c.id
                WHERE c.subject_id = :sid AND m.is_published = 1
                ORDER BY m.sequence_order ASC
            ");
            $mod_stmt->execute(['sid' => $subject_id]);
            $modules = $mod_stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("LMS Course Error: " . $e->getMessage());
            $response->redirect("/sia/lms/student/dashboard.php");
            return;
        }

        $pageTitle = $course['subject_code'] . ' - TTU LMS';
        $current_page = 'my_courses.php'; // Highlight "My Courses" in sidebar

        return $this->render('lms/student/course', get_defined_vars());
    }

    public function myCourses(Request $request, Response $response)
    {
        $pdo = Database::getConnection();

        // Fetch user's enrolled subjects
        $enrolled_courses = [];
        try {
            $stmt = $pdo->prepare("
                SELECT s.id as subject_id, s.subject_code as code, s.subject_name as name, cs.section_code as section_name, s.units
                FROM college_enrollments ce
                JOIN applications a ON ce.application_id = a.id
                JOIN subjects s ON ce.subject_id = s.id
                LEFT JOIN college_sections cs ON ce.college_section_id = cs.id
                WHERE a.user_id = :uid AND a.status = 'enrolled'
            ");
            $stmt->execute(['uid' => $_SESSION['user_id'] ?? 0]);
            $enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("LMS My Courses Error: " . $e->getMessage());
        }

        $pageTitle = 'My Courses - TTU LMS';

        return $this->render('lms/student/my_courses', get_defined_vars());
    }
}
