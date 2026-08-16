<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsService;
use App\Services\LmsQuizService;

class FacultyQuizController extends BaseController
{
    private LmsService $lmsService;
    private LmsQuizService $quizService;

    public function __construct()
    {
        $this->lmsService = new LmsService();
        $this->quizService = new LmsQuizService();
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
        $quizzes = $this->quizService->getQuizzesByCourse($lmsCourseId, false);

        return $this->render('lms/faculty/quizzes/index', [
            'course' => $course,
            'quizzes' => $quizzes
        ]);
    }

    public function create(Request $request, Response $response, string $courseId)
    {
        $lmsCourseId = (int)$courseId;
        $this->authorizeFaculty($response, $lmsCourseId);

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        
        return $this->render('lms/faculty/quizzes/form', [
            'course' => $course,
            'quiz' => null
        ]);
    }

    public function store(Request $request, Response $response, string $courseId)
    {
        $lmsCourseId = (int)$courseId;
        $this->authorizeFaculty($response, $lmsCourseId);

        $data = $request->getBody();
        $data['lms_course_id'] = $lmsCourseId;

        $this->quizService->createQuiz($data);

        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/quizzes");
    }

    public function edit(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $quiz = $this->quizService->getQuiz($quizId);
        if (!$quiz || $quiz['lms_course_id'] != $lmsCourseId) {
            $this->notFound($response);
            return;
        }

        $course = $this->lmsService->getCourseDetails($lmsCourseId);

        return $this->render('lms/faculty/quizzes/form', [
            'course' => $course,
            'quiz' => $quiz
        ]);
    }

    public function update(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $quiz = $this->quizService->getQuiz($quizId);
        if (!$quiz || $quiz['lms_course_id'] != $lmsCourseId) {
            $this->notFound($response);
            return;
        }

        $data = $request->getBody();
        $this->quizService->updateQuiz($quizId, $data);

        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/quizzes");
    }

    public function questions(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $quiz = $this->quizService->getQuiz($quizId);
        if (!$quiz || $quiz['lms_course_id'] != $lmsCourseId) {
            $this->notFound($response);
            return;
        }

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        $questions = $this->quizService->getQuestions($quizId, true);

        // Calculate total points
        $totalPoints = 0;
        foreach ($questions as $q) {
            $totalPoints += $q['points'];
        }

        return $this->render('lms/faculty/quizzes/questions', [
            'course' => $course,
            'quiz' => $quiz,
            'questions' => $questions,
            'total_points' => $totalPoints
        ]);
    }

    public function storeQuestion(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $quiz = $this->quizService->getQuiz($quizId);
        if (!$quiz || $quiz['lms_course_id'] != $lmsCourseId) {
            $this->notFound($response);
            return;
        }

        $data = $request->getBody();
        $qData = [
            'lms_quiz_id' => $quizId,
            'question_text' => $data['question_text'],
            'question_type' => $data['question_type'],
            'points' => $data['points'] ?? 1
        ];

        $qId = $this->quizService->addQuestion($qData);

        if ($data['question_type'] === 'true_false') {
            $this->quizService->addChoice(['lms_question_id' => $qId, 'choice_text' => 'True', 'is_correct' => ($data['correct_tf'] === 'true'), 'display_order' => 1]);
            $this->quizService->addChoice(['lms_question_id' => $qId, 'choice_text' => 'False', 'is_correct' => ($data['correct_tf'] === 'false'), 'display_order' => 2]);
        } elseif ($data['question_type'] === 'multiple_choice') {
            $correctIndex = (int)$data['correct_mc'];
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($data['mc_choice_' . $i])) {
                    $this->quizService->addChoice([
                        'lms_question_id' => $qId,
                        'choice_text' => $data['mc_choice_' . $i],
                        'is_correct' => ($i === $correctIndex),
                        'display_order' => $i
                    ]);
                }
            }
        }

        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/questions");
    }

    public function results(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $quiz = $this->quizService->getQuiz($quizId);
        if (!$quiz || $quiz['lms_course_id'] != $lmsCourseId) {
            $this->notFound($response);
            return;
        }

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        $attempts = $this->quizService->getAllSubmissionsForQuiz($quizId);

        return $this->render('lms/faculty/quizzes/results', [
            'course' => $course,
            'quiz' => $quiz,
            'attempts' => $attempts
        ]);
    }
}
