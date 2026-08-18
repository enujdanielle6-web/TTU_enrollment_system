<?php
session_start();
$_SESSION['user_id'] = 2; // Assume a student ID
$_SESSION['lms_name'] = 'Test Student';
$_SESSION['lms_email'] = 'test@ttu.edu.ph';
$_SERVER['REQUEST_URI'] = '/sia/lms/student/course.php?id=13';

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Controllers/BaseController.php';

class TestController extends \App\Controllers\BaseController {
    public function r() {
        $lms_course_id = 13;
        $course = ['lms_course_id' => 13, 'subject_name' => 'IT Fundamentals', 'subject_code' => 'IT101', 'units' => 3, 'section_code' => 'A'];
        $instructor_name = "John Doe";
        $instructor_email = "john@ttu.edu.ph";
        $welcome_message = "Welcome";
        $modules = [];
        $current_page = 'course.php';
        return $this->render('lms/student/course', get_defined_vars());
    }
}

$html = (new TestController())->r();
if (strpos($html, 'Course Menu') !== false) {
    echo "SUCCESS: Course Menu is in HTML\n";
} else {
    echo "FAIL: Course Menu NOT in HTML\n";
}
file_put_contents(__DIR__ . '/test_output.html', $html);
