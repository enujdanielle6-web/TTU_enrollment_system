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

        // Server-side check if already graded
        $existingSubmission = $this->lmsService->getStudentSubmission($assignmentId, $userId);
        if ($existingSubmission && $existingSubmission['status'] === 'GRADED') {
            $response->setStatusCode(403);
            echo "403 Forbidden - Assignment has already been graded and cannot be resubmitted.";
            exit;
        }

        // Handle File Upload
        if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
            $response->setStatusCode(400);
            echo "Upload error. Please try again.";
            exit;
        }

        $file = $_FILES['submission_file'];

        // File size cap: 25MB
        if ($file['size'] > 25 * 1024 * 1024) {
            $response->setStatusCode(400);
            echo "File exceeds maximum permitted size of 25MB.";
            exit;
        }
        
        // Prevent executable uploads
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $blacklist = ['php', 'phtml', 'phar', 'exe', 'bat', 'cmd', 'sh', 'py', 'js', 'vbs', 'html', 'htm'];
        if (in_array($ext, $blacklist, true)) {
            $response->setStatusCode(400);
            echo "Invalid file type. Executable files are strictly prohibited.";
            exit;
        }

        $canonicalDir = dirname(__DIR__, 3) . '/storage/uploads/lms/submissions/';
        if (!is_dir($canonicalDir)) {
            @mkdir($canonicalDir, 0775, true);
        }

        $uniqueName = 'sub_' . $assignmentId . '_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $targetPath = $canonicalDir . $uniqueName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $status = $existingSubmission ? 'RESUBMITTED' : 'SUBMITTED';

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $finfo ? finfo_file($finfo, $targetPath) : ($file['type'] ?? 'application/octet-stream');
            if ($finfo) finfo_close($finfo);

            $fileData = [
                'file_name' => basename($file['name']),
                'file_path' => $uniqueName, // stored relative to canonical submissions dir
                'mime_type' => $mimeType,
                'file_size' => (int)$file['size']
            ];

            $this->lmsService->submitAssignment($assignmentId, $userId, $fileData, $status);
            
            $this->redirect(BASE_PATH . "/lms/student/course/{$lmsCourseId}/assignments/{$assignmentId}");
        } else {
            $response->setStatusCode(500);
            echo "Failed to save uploaded submission file.";
            exit;
        }
    }
}
