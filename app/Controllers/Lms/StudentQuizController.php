<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsService;
use App\Services\LmsQuizService;

class StudentQuizController extends BaseController
{
    private LmsService $lmsService;
    private LmsQuizService $quizService;

    public function __construct()
    {
        $this->lmsService = new LmsService();
        $this->quizService = new LmsQuizService();
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
        
        $quizzes = $this->quizService->getQuizzesByCourse($lmsCourseId, true);

        foreach ($quizzes as &$q) {
            $q['attempts'] = $this->quizService->getStudentAttempts($q['id'], $userId);
        }

        return $this->render('lms/student/quizzes/index', [
            'course' => $course,
            'quizzes' => $quizzes
        ]);
    }

    public function show(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $userId = $_SESSION['user_id'];
        
        $this->authorizeStudent($response, $lmsCourseId);

        $quiz = $this->quizService->getQuiz($quizId);
        if (!$quiz || $quiz['lms_course_id'] != $lmsCourseId || $quiz['status'] !== 'published') {
            $this->notFound($response);
            return;
        }

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        $attempts = $this->quizService->getStudentAttempts($quizId, $userId);

        $inProgressAttempt = null;
        foreach ($attempts as $att) {
            if ($att['status'] === 'in_progress') {
                $inProgressAttempt = $att;
                break;
            }
        }

        $canAttempt = true;
        $now = date('Y-m-d H:i:s');
        if ($quiz['start_date'] && $now < $quiz['start_date']) $canAttempt = false;
        if ($quiz['end_date'] && $now > $quiz['end_date']) $canAttempt = false;
        if ($quiz['max_attempts'] !== null && count($attempts) >= $quiz['max_attempts']) {
            if (!$inProgressAttempt) $canAttempt = false;
        }

        return $this->render('lms/student/quizzes/show', [
            'course' => $course,
            'quiz' => $quiz,
            'attempts' => $attempts,
            'in_progress' => $inProgressAttempt,
            'can_attempt' => $canAttempt
        ]);
    }

    public function start(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $userId = $_SESSION['user_id'];
        
        $this->authorizeStudent($response, $lmsCourseId);

        $attemptId = $this->quizService->startAttempt($quizId, $userId);

        if (!$attemptId) {
            $this->redirect("/sia/lms/student/course/{$lmsCourseId}/quizzes/{$quizId}");
            return;
        }

        $this->redirect("/sia/lms/student/course/{$lmsCourseId}/quizzes/{$quizId}/attempt/{$attemptId}");
    }

    public function attempt(Request $request, Response $response, string $courseId, string $quizId, string $attemptId)
    {
        $lmsCourseId = (int)$courseId;
        $qId = (int)$quizId;
        $aId = (int)$attemptId;
        $userId = $_SESSION['user_id'];
        
        $this->authorizeStudent($response, $lmsCourseId);

        $quiz = $this->quizService->getQuiz($qId);
        $attempt = $this->quizService->getAttempt($aId);

        if (!$attempt || $attempt['student_id'] != $userId || $attempt['lms_quiz_id'] != $qId) {
            $this->notFound($response);
            return;
        }

        if ($attempt['status'] !== 'in_progress') {
            $this->redirect("/sia/lms/student/course/{$lmsCourseId}/quizzes/{$qId}/result/{$aId}");
            return;
        }

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        $questions = $this->quizService->getQuestions($qId, true);

        return $this->render('lms/student/quizzes/attempt', [
            'course' => $course,
            'quiz' => $quiz,
            'attempt' => $attempt,
            'questions' => $questions
        ]);
    }

    public function submit(Request $request, Response $response, string $courseId, string $quizId, string $attemptId)
    {
        $lmsCourseId = (int)$courseId;
        $qId = (int)$quizId;
        $aId = (int)$attemptId;
        $userId = $_SESSION['user_id'];
        
        $this->authorizeStudent($response, $lmsCourseId);

        $attempt = $this->quizService->getAttempt($aId);
        if (!$attempt || $attempt['student_id'] != $userId) {
            $this->notFound($response);
            return;
        }

        $data = $request->getBody();
        $answers = $data['answers'] ?? []; // format: ['question_id' => 'choice_id']

        $success = $this->quizService->submitAttempt($aId, $answers);

        $this->redirect("/sia/lms/student/course/{$lmsCourseId}/quizzes/{$qId}/result/{$aId}");
    }

    public function result(Request $request, Response $response, string $courseId, string $quizId, string $attemptId)
    {
        $lmsCourseId = (int)$courseId;
        $qId = (int)$quizId;
        $aId = (int)$attemptId;
        $userId = $_SESSION['user_id'];
        
        $this->authorizeStudent($response, $lmsCourseId);

        $quiz = $this->quizService->getQuiz($qId);
        $attempt = $this->quizService->getAttempt($aId);

        if (!$attempt || $attempt['student_id'] != $userId || $attempt['lms_quiz_id'] != $qId || $attempt['status'] === 'in_progress') {
            $this->notFound($response);
            return;
        }

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        $questions = $this->quizService->getQuestions($qId, true);
        $details = $this->quizService->getAttemptDetails($aId);

        // Map answers
        $mappedAnswers = [];
        foreach ($details as $d) {
            $mappedAnswers[$d['lms_question_id']] = $d;
        }

        return $this->render('lms/student/quizzes/result', [
            'course' => $course,
            'quiz' => $quiz,
            'attempt' => $attempt,
            'questions' => $questions,
            'answers' => $mappedAnswers
        ]);
    }
}
