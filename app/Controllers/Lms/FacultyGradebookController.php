<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsService;
use App\Services\LmsGradebookService;

class FacultyGradebookController extends BaseController
{
    private LmsService $lmsService;
    private LmsGradebookService $gradebookService;

    public function __construct()
    {
        $this->lmsService = new LmsService();
        $this->gradebookService = new LmsGradebookService();
    }

    private function authorizeFaculty(Response $response, int $lmsCourseId)
    {
        $userId = $_SESSION['user_id'] ?? 0;
        if (!$this->lmsService->isFacultyAuthorizedForCourse($userId, $lmsCourseId)) {
            $response->setStatusCode(403);
            echo "403 Forbidden - You do not have access to this course.";
            exit;
        }
    }

    public function index(Request $request, Response $response, string $courseId)
    {
        $lmsCourseId = (int)$courseId;
        $this->authorizeFaculty($response, $lmsCourseId);

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        $gradebook = $this->gradebookService->getCourseGradebook($lmsCourseId);

        return $this->render('lms/faculty/gradebook/index', [
            'course' => $course,
            'gradebook' => $gradebook
        ]);
    }
}
