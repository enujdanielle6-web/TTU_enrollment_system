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
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $enrolled_courses = $lmsService->getStudentCourses($userId);
        $upcoming_deadlines = $lmsService->getStudentUpcomingDeadlines($userId, 4);
        $recent_announcements = $lmsService->getStudentAnnouncements($userId, 3);
        $next_event = $lmsService->getStudentNextEvent($userId);
        $streak_count = $lmsService->getStudentStreak($userId);
        
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

        // 4. Badge Counts for Course Navigation Tabs
        $announcementService = new \App\Services\LmsAnnouncementService();
        $quizService = new \App\Services\LmsQuizService();

        try {
            $assignment_count = count($lmsService->getAssignmentsByCourse($lms_course_id, true));
        } catch (\Throwable $e) {
            $assignment_count = 0;
        }

        try {
            $quiz_count = count($quizService->getQuizzesByCourse($lms_course_id, true));
        } catch (\Throwable $e) {
            $quiz_count = 0;
        }

        try {
            $announcement_count = count($announcementService->getCourseAnnouncements($lms_course_id, true));
        } catch (\Throwable $e) {
            $announcement_count = 0;
        }

        $pageTitle = $course['subject_code'] . ' - TTU LMS';
        $current_page = 'my_courses.php'; // Highlight "My Courses" in sidebar

        return $this->render('lms/student/course', get_defined_vars());
    }

    public function myCourses(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        $lmsService = new \App\Services\LmsService();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $enrolled_courses = $lmsService->getStudentCourses($userId);
        
        // Fetch active academic profile details
        $stmtApp = $pdo->prepare("
            SELECT a.academic_level, a.grade_level, a.school_year, a.semester, a.student_type, a.strand,
                   COALESCE(cs.section_code, ss.section_code) as section_code
            FROM applications a
            LEFT JOIN college_sections cs ON a.section_id = cs.id AND (a.academic_level = 'College' OR a.academic_level IS NULL)
            LEFT JOIN shs_sections ss ON a.section_id = ss.id AND (a.academic_level = 'SHS' OR a.academic_level = 'Senior High School')
            WHERE a.user_id = :uid AND a.status IN ('enrolled', 'approved')
            ORDER BY a.id DESC LIMIT 1
        ");
        $stmtApp->execute(['uid' => $userId]);
        $student_meta = $stmtApp->fetch(PDO::FETCH_ASSOC) ?: [];

        $total_units = array_sum(array_column($enrolled_courses, 'units'));
        $total_courses = count($enrolled_courses);

        $pageTitle = 'My Courses - TTU LMS';

        return $this->render('lms/student/my_courses', get_defined_vars());
    }

    public function profile(Request $request, Response $response)
    {
        $pageTitle = 'My Profile - TTU LMS';
        return $this->render('lms/student/profile', get_defined_vars());
    }

    public function messages(Request $request, Response $response)
    {
        $pageTitle = 'Messages & Forums - TTU LMS';
        return $this->render('lms/student/messages', get_defined_vars());
    }
}
