<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsService;
use App\Services\LmsGradebookService;

class StudentGradebookController extends BaseController
{
    private LmsService $lmsService;
    private LmsGradebookService $gradebookService;

    public function __construct()
    {
        $this->lmsService = new LmsService();
        $this->gradebookService = new LmsGradebookService();
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
        $gradebookData = $this->gradebookService->getStudentGradebook($lmsCourseId, $userId);

        return $this->render('lms/student/gradebook/index', [
            'course' => $course,
            'data' => $gradebookData
        ]);
    }
}
