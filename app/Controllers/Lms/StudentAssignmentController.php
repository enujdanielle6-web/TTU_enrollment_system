<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsService;

class StudentAssignmentController extends BaseController
{
    private LmsService $lmsService;

    public function __construct()
    {
        $this->lmsService = new LmsService();
    }

    private function authorizeStudent(Response $response, int $lmsCourseId)
    {
        $userId = $_SESSION['user_id'] ?? 0;
        if (!$this->lmsService->isStudentAuthorizedForCourse($userId, $lmsCourseId)) {
            $response->setStatusCode(403);
            echo "403 Forbidden - You do not have access to this course.";
            exit;
        }
    }

    public function index(Request $request, Response $response, string $courseId)
    {
        $lmsCourseId = (int)$courseId;
        $userId = $_SESSION['user_id'];
        
        $this->authorizeStudent($response, $lmsCourseId);

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        
        $assignments = $this->lmsService->getAssignmentsByCourse($lmsCourseId, true);

        foreach ($assignments as &$a) {
            $a['submission'] = $this->lmsService->getStudentSubmission($a['id'], $userId);
        }

        return $this->render('lms/student/assignments/index', [
            'course' => $course,
            'assignments' => $assignments
        ]);
    }

    public function show(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $assignmentId = (int)$id;
        $userId = $_SESSION['user_id'];
        
        $this->authorizeStudent($response, $lmsCourseId);

        $assignment = $this->lmsService->getAssignment($assignmentId);
        if (!$assignment || $assignment['lms_course_id'] != $lmsCourseId || $assignment['status'] !== 'published') {
            $response->setStatusCode(404);
            echo "Assignment not found or not published.";
            exit;
        }

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        $submission = $this->lmsService->getStudentSubmission($assignmentId, $userId);

        return $this->render('lms/student/assignments/show', [
            'course' => $course,
            'assignment' => $assignment,
            'submission' => $submission
        ]);
    }

    public function submit(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $assignmentId = (int)$id;
        $userId = $_SESSION['user_id'];
        
        $this->authorizeStudent($response, $lmsCourseId);

        $assignment = $this->lmsService->getAssignment($assignmentId);
        if (!$assignment || $assignment['lms_course_id'] != $lmsCourseId || $assignment['status'] !== 'published') {
            $response->setStatusCode(404);
            echo "Assignment not found.";
            exit;
        }

        // Handle File Upload
        if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
            echo "Upload error. Please try again.";
            exit;
        }

        $file = $_FILES['submission_file'];
        
        // Prevent executable uploads
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['php', 'exe', 'sh', 'bat', 'js', 'html', 'phtml'])) {
            echo "Invalid file type.";
            exit;
        }

        $targetDir = realpath(__DIR__ . '/../../../app/uploads/lms/submissions');
        if (!$targetDir) {
            echo "System error: Upload directory not found.";
            exit;
        }

        $uniqueName = 'sub_' . $assignmentId . '_' . $userId . '_' . time() . '.' . $ext;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $uniqueName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            
            $existingSubmission = $this->lmsService->getStudentSubmission($assignmentId, $userId);
            $status = $existingSubmission ? 'RESUBMITTED' : 'SUBMITTED';

            $fileData = [
                'file_name' => basename($file['name']),
                'file_path' => $uniqueName, // we store relative to submissions dir
                'mime_type' => $file['type'],
                'file_size' => $file['size']
            ];

            $this->lmsService->submitAssignment($assignmentId, $userId, $fileData, $status);
            
            $this->redirect("/sia/lms/student/course/{$lmsCourseId}/assignments/{$assignmentId}");
        } else {
            echo "Failed to move uploaded file.";
            exit;
        }
    }
}
