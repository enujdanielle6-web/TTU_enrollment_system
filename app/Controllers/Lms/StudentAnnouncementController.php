<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsService;
use App\Services\LmsAnnouncementService;

class StudentAnnouncementController extends BaseController
{
    private LmsService $lmsService;
    private LmsAnnouncementService $announcementService;

    public function __construct()
    {
        $this->lmsService = new LmsService();
        $this->announcementService = new LmsAnnouncementService();
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
        $this->authorizeStudent($response, $lmsCourseId);

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        $announcements = $this->announcementService->getCourseAnnouncements($lmsCourseId, true); // true = published only

        return $this->render('lms/student/announcements/index', [
            'course' => $course,
            'announcements' => $announcements
        ]);
    }
}
