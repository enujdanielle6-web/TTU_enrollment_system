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
        
        // Fetch enrolled students
        $pdo = Database::getConnection();
        $type = $course['academic_level'];
        $sectionId = $course['academic_section_id'];
        $subjectId = $course['subject_id'];

        if ($type === 'College') {
            $stmt = $pdo->prepare("
                SELECT u.id, u.student_number, u.first_name, u.last_name 
                FROM college_enrollments ce
                JOIN applications a ON ce.application_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE ce.college_section_id = :sec AND ce.subject_id = :sub
                ORDER BY u.last_name ASC, u.first_name ASC
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT u.id, u.student_number, u.first_name, u.last_name 
                FROM shs_enrollments se
                JOIN applications a ON se.application_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE se.shs_section_id = :sec AND se.subject_id = :sub
                ORDER BY u.last_name ASC, u.first_name ASC
            ");
        }
        $stmt->execute(['sec' => $sectionId, 'sub' => $subjectId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
