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
        $lmsService = new \App\Services\LmsService();
        $userId = $_SESSION['user_id'] ?? 0;

        $enrolled_courses = $lmsService->getStudentCourses($userId);
        
        $pageTitle = 'Dashboard - TTU LMS';

        return $this->render('lms/student/dashboard', get_defined_vars());
    }

    public function course(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        $lmsService = new \App\Services\LmsService();
        $userId = $_SESSION['user_id'] ?? 0;

        $lms_course_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$lms_course_id) {
            $response->redirect("/sia/lms/student/dashboard.php");
            return;
        }

        // 1. Verify Enrollment via Service
        if (!$lmsService->isStudentAuthorizedForCourse($userId, $lms_course_id)) {
            $response->redirect("/sia/lms/student/dashboard.php");
            return;
        }

        // 2. Fetch Course Details
        $course = $lmsService->getCourseDetails($lms_course_id);
        if (!$course) {
            $response->redirect("/sia/lms/student/dashboard.php");
            return;
        }

        // LMS Course Metadata mapping for View
        $course['subject_name'] = $course['subject_name'] ?? 'Unknown Course';
        $course['subject_code'] = $course['subject_code'] ?? 'N/A';
        $course['section_code'] = $course['section_code'] ?? 'Global Section';
        
        $instructor_name = trim(($course['instructor_first'] ?? '') . ' ' . ($course['instructor_last'] ?? ''));
        if (empty($instructor_name)) {
            $instructor_name = 'Instructor TBA';
        }
        $instructor_email = $course['instructor_email'] ?? 'N/A';
        $welcome_message = 'Welcome to ' . htmlspecialchars($course['subject_name']) . '! Your instructor will post materials soon.';

        // 3. Fetch Modules and Materials
        try {
            $modules = $lmsService->getModulesWithMaterialsForCourse($lms_course_id);
        } catch (Exception $e) {
            error_log("LMS Course Modules Error: " . $e->getMessage());
            $modules = [];
        }

        $pageTitle = $course['subject_code'] . ' - TTU LMS';
        $current_page = 'my_courses.php'; // Highlight "My Courses" in sidebar

        return $this->render('lms/student/course', get_defined_vars());
    }

    public function myCourses(Request $request, Response $response)
    {
        $lmsService = new \App\Services\LmsService();
        $userId = $_SESSION['user_id'] ?? 0;

        $enrolled_courses = $lmsService->getStudentCourses($userId);
        
        $pageTitle = 'My Courses - TTU LMS';

        return $this->render('lms/student/my_courses', get_defined_vars());
    }
}
