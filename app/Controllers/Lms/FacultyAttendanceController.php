<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsService;
use App\Services\LmsAttendanceService;
use PDO;
use App\Core\Database;

class FacultyAttendanceController extends BaseController
{
    private LmsService $lmsService;
    private LmsAttendanceService $attendanceService;

    public function __construct()
    {
        $this->lmsService = new LmsService();
        $this->attendanceService = new LmsAttendanceService();
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
        $sessions = $this->attendanceService->getCourseSessions($lmsCourseId);

        return $this->render('lms/faculty/attendance/index', [
            'course' => $course,
            'sessions' => $sessions
        ]);
    }

    public function create(Request $request, Response $response, string $courseId)
    {
        $lmsCourseId = (int)$courseId;
        $this->authorizeFaculty($response, $lmsCourseId);

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        
        return $this->render('lms/faculty/attendance/form', [
            'course' => $course,
            'session' => null
        ]);
    }

    public function store(Request $request, Response $response, string $courseId)
    {
        $lmsCourseId = (int)$courseId;
        $this->authorizeFaculty($response, $lmsCourseId);

        $data = $request->getBody();
        $data['lms_course_id'] = $lmsCourseId;

        $sessionId = $this->attendanceService->createSession($data);

        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/attendance/{$sessionId}/edit");
    }

    public function edit(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $sessionId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $session = $this->attendanceService->getSessionDetails($sessionId);
        if (!$session || $session['lms_course_id'] != $lmsCourseId) {
            $this->notFound($response);
            return;
        }

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        
        $students = $this->lmsService->getCourseRoster($lmsCourseId);

        $records = $this->attendanceService->getSessionRecords($sessionId);

        return $this->render('lms/faculty/attendance/edit', [
            'course' => $course,
            'session' => $session,
            'students' => $students,
            'records' => $records
        ]);
    }

    public function update(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $sessionId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $session = $this->attendanceService->getSessionDetails($sessionId);
        if (!$session || $session['lms_course_id'] != $lmsCourseId) {
            $this->notFound($response);
            return;
        }

        $data = $request->getBody();
        $attendance = $data['attendance'] ?? []; // format [student_id => ['status' => 'present', 'remarks' => '...']]

        $this->attendanceService->saveAttendance($sessionId, $attendance);

        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/attendance");
    }
}
