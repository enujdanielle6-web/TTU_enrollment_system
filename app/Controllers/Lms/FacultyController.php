<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;

class FacultyController extends BaseController
{
    public function dashboard(Request $request, Response $response)
    {
        $lmsService = new \App\Services\LmsService();
        $facultyUserId = $_SESSION['user_id'] ?? 0;

        $faculty_courses = $lmsService->getFacultyCourses($facultyUserId);
        
        $pageTitle = 'Faculty Dashboard - TTU LMS';

        return $this->render('lms/faculty/dashboard', get_defined_vars());
    }

    public function course(Request $request, Response $response)
    {
        $lmsService = new \App\Services\LmsService();
        $facultyUserId = $_SESSION['user_id'] ?? 0;
        $courseId = (int)$request->input('id');

        if (!$lmsService->isFacultyAuthorizedForCourse($facultyUserId, $courseId)) {
            $response->setStatusCode(403);
            echo "403 Forbidden - You do not have access to this course.";
            exit;
        }

        $course = $lmsService->getCourseDetails($courseId);
        $modulesWithMaterials = $lmsService->getModulesWithMaterialsForCourse($courseId);

        $pageTitle = 'Course Management - ' . $course['subject_code'];

        return $this->render('lms/faculty/course', get_defined_vars());
    }

    public function createModule(Request $request, Response $response)
    {
        $lmsService = new \App\Services\LmsService();
        $facultyUserId = $_SESSION['user_id'] ?? 0;
        $data = $request->getBody();
        $courseId = (int)($data['lms_course_id'] ?? 0);

        if (!$lmsService->isFacultyAuthorizedForCourse($facultyUserId, $courseId)) {
            $response->setStatusCode(403);
            echo "403 Forbidden";
            exit;
        }

        $title = $data['title'] ?? 'New Module';
        $orderIndex = (int)($data['order_index'] ?? 1);
        
        $lmsService->createModule($courseId, $title, $orderIndex);
        
        $this->redirect('/sia/lms/faculty/course.php?id=' . $courseId);
    }

    public function uploadMaterial(Request $request, Response $response)
    {
        $lmsService = new \App\Services\LmsService();
        $facultyUserId = $_SESSION['user_id'] ?? 0;
        $data = $request->getBody();
        $courseId = (int)($data['lms_course_id'] ?? 0);
        $moduleId = (int)($data['lms_module_id'] ?? 0);

        if (!$lmsService->isFacultyAuthorizedForCourse($facultyUserId, $courseId)) {
            $response->setStatusCode(403);
            echo "403 Forbidden";
            exit;
        }

        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../../storage/lms_materials/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = time() . '_' . basename($_FILES['material_file']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['material_file']['tmp_name'], $targetPath)) {
                $materialData = [
                    'lms_module_id' => $moduleId,
                    'title' => $data['title'] ?? 'Untitled Material',
                    'file_path' => $fileName,
                    'file_type' => strtolower(pathinfo($fileName, PATHINFO_EXTENSION)),
                    'status' => 'published'
                ];
                $lmsService->createMaterial($materialData);
            }
        }
        
        $this->redirect('/sia/lms/faculty/course.php?id=' . $courseId);
    }

    public function profile(Request $request, Response $response)
    {
        $pageTitle = 'My Profile - TTU LMS';
        return $this->render('lms/faculty/profile', get_defined_vars());
    }

    public function messages(Request $request, Response $response)
    {
        $pageTitle = 'Messages & Forums - TTU LMS';
        return $this->render('lms/faculty/messages', get_defined_vars());
    }
}
