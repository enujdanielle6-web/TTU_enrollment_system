<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsService;
use App\Services\LmsAnnouncementService;

class FacultyAnnouncementController extends BaseController
{
    private LmsService $lmsService;
    private LmsAnnouncementService $announcementService;

    public function __construct()
    {
        $this->lmsService = new LmsService();
        $this->announcementService = new LmsAnnouncementService();
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
        $announcements = $this->announcementService->getCourseAnnouncements($lmsCourseId, false);

        return $this->render('lms/faculty/announcements/index', [
            'course' => $course,
            'announcements' => $announcements
        ]);
    }

    public function create(Request $request, Response $response, string $courseId)
    {
        $lmsCourseId = (int)$courseId;
        $this->authorizeFaculty($response, $lmsCourseId);

        $course = $this->lmsService->getCourseDetails($lmsCourseId);

        return $this->render('lms/faculty/announcements/form', [
            'course' => $course,
            'announcement' => null
        ]);
    }

    public function store(Request $request, Response $response, string $courseId)
    {
        $lmsCourseId = (int)$courseId;
        $this->authorizeFaculty($response, $lmsCourseId);

        $data = $request->getBody();
        $data['lms_course_id'] = $lmsCourseId;
        $data['author_user_id'] = $_SESSION['user_id'];

        $this->announcementService->createAnnouncement($data);
        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/announcements");
    }

    public function edit(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $announcementId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $announcement = $this->announcementService->getAnnouncement($announcementId);
        if (!$announcement || $announcement['lms_course_id'] != $lmsCourseId) {
            $this->notFound($response);
            return;
        }

        $course = $this->lmsService->getCourseDetails($lmsCourseId);

        return $this->render('lms/faculty/announcements/form', [
            'course' => $course,
            'announcement' => $announcement
        ]);
    }

    public function update(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $announcementId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $announcement = $this->announcementService->getAnnouncement($announcementId);
        if (!$announcement || $announcement['lms_course_id'] != $lmsCourseId) {
            $this->notFound($response);
            return;
        }

        $data = $request->getBody();
        $this->announcementService->updateAnnouncement($announcementId, $data);
        
        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/announcements");
    }
}
