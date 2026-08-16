<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsService;

class FacultyAssignmentController extends BaseController
{
    private LmsService $lmsService;

    public function __construct()
    {
        $this->lmsService = new LmsService();
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
        $assignments = $this->lmsService->getAssignmentsByCourse($lmsCourseId, false);

        return $this->render('lms/faculty/assignments/index', [
            'course' => $course,
            'assignments' => $assignments
        ]);
    }

    public function create(Request $request, Response $response, string $courseId)
    {
        $lmsCourseId = (int)$courseId;
        $this->authorizeFaculty($response, $lmsCourseId);

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        
        return $this->render('lms/faculty/assignments/form', [
            'course' => $course,
            'assignment' => null
        ]);
    }

    public function store(Request $request, Response $response, string $courseId)
    {
        $lmsCourseId = (int)$courseId;
        $this->authorizeFaculty($response, $lmsCourseId);

        $data = $request->getBody();
        $data['lms_course_id'] = $lmsCourseId;
        $data['due_date'] = !empty($data['due_date']) ? $data['due_date'] : null;

        $this->lmsService->createAssignment($data);

        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/assignments");
    }

    public function edit(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $assignmentId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $assignment = $this->lmsService->getAssignment($assignmentId);
        if (!$assignment || $assignment['lms_course_id'] != $lmsCourseId) {
            $response->setStatusCode(404);
            echo "Assignment not found.";
            exit;
        }

        $course = $this->lmsService->getCourseDetails($lmsCourseId);

        return $this->render('lms/faculty/assignments/form', [
            'course' => $course,
            'assignment' => $assignment
        ]);
    }

    public function update(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $assignmentId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $assignment = $this->lmsService->getAssignment($assignmentId);
        if (!$assignment || $assignment['lms_course_id'] != $lmsCourseId) {
            $response->setStatusCode(404);
            echo "Assignment not found.";
            exit;
        }

        $data = $request->getBody();
        $data['due_date'] = !empty($data['due_date']) ? $data['due_date'] : null;

        $this->lmsService->updateAssignment($assignmentId, $data);

        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/assignments");
    }

    public function submissions(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $assignmentId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $assignment = $this->lmsService->getAssignment($assignmentId);
        if (!$assignment || $assignment['lms_course_id'] != $lmsCourseId) {
            $response->setStatusCode(404);
            echo "Assignment not found.";
            exit;
        }

        $submissions = $this->lmsService->getSubmissionsForAssignment($assignmentId);
        $course = $this->lmsService->getCourseDetails($lmsCourseId);

        return $this->render('lms/faculty/assignments/submissions', [
            'course' => $course,
            'assignment' => $assignment,
            'submissions' => $submissions
        ]);
    }

    public function grade(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $assignmentId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $data = $request->getBody();
        $submissionId = (int)$data['submission_id'];
        $grade = (float)$data['grade'];
        $feedback = $data['feedback'] ?? null;
        $graderId = $_SESSION['user_id'];

        $this->lmsService->gradeSubmission($submissionId, $graderId, $grade, $feedback);

        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/assignments/{$assignmentId}/submissions");
    }
}
