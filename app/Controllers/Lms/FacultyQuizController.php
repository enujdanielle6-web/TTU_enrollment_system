<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsService;
use App\Services\LmsQuizService;
use App\Services\Quiz\CourseContentExtractor;
use App\Services\Quiz\ExtractiveQuizGenerator;
use App\Services\Quiz\QuizCsvImporter;
use App\Services\Quiz\QuizGeneratorInterface;
use App\Services\Quiz\QuizQuestionValidator;

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

        $this->redirect(BASE_PATH . "/lms/faculty/course/{$lmsCourseId}/quizzes");
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

        $this->redirect(BASE_PATH . "/lms/faculty/course/{$lmsCourseId}/quizzes");
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
            'total_points' => $totalPoints,
            'flash' => $this->pullFlash(),
            'has_draft' => $this->getDraft($quizId, $lmsCourseId) !== null
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
        $type = QuizQuestionValidator::normalizeType($data['question_type'] ?? '');
        $draft = [
            'type' => $data['question_type'] ?? '',
            'question_text' => $data['question_text'] ?? '',
            'points' => $data['points'] ?? 1,
            'case_sensitive' => !empty($data['case_sensitive']),
            'manual_review' => !empty($data['manual_review']),
        ];
        if ($type === 'multiple_choice') {
            $draft['choices'] = [];
            for ($i = 1; $i <= QuizQuestionValidator::MAX_CHOICES; $i++) {
                $draft['choices'][] = (string)($data['mc_choice_' . $i] ?? '');
            }
            $correct = (int)($data['correct_mc'] ?? 0);
            $draft['correct_index'] = $correct >= 1 ? (string)($correct - 1) : null;
        } elseif ($type === 'true_false') {
            $draft['correct_tf'] = $data['correct_tf'] ?? '';
        } elseif ($type !== null) {
            $draft['accepted_answers'] = QuizQuestionValidator::splitAnswers((string)($data['accepted_answers'] ?? ''));
        }

        [$clean, $errors] = (new QuizQuestionValidator())->validate($draft);
        if (!empty($errors)) {
            $this->setFlash('danger', 'Question was not saved: ' . implode(' ', $errors));
        } else {
            try {
                $this->quizService->addValidatedQuestions($quizId, [$clean]);
                $this->setFlash('success', 'Question added.');
            } catch (\Throwable $e) {
                error_log('Quiz question save failed: ' . $e->getMessage());
                $this->setFlash('danger', $this->saveFailureMessage($e, 'The question could not be saved. Please try again.'));
            }
        }

        $this->redirect(BASE_PATH . "/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/questions");
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
            'attempts' => $attempts,
            'pending_review' => $this->quizService->countAnswersNeedingReview($quizId),
            'flash' => $this->pullFlash()
        ]);
    }

    public function delete(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $this->authorizeFaculty($response, $lmsCourseId);

        $quiz = $this->quizService->getQuiz($quizId);
        if (!$quiz || $quiz['lms_course_id'] != $lmsCourseId) {
            $this->notFound($response);
            return;
        }

        $this->quizService->deleteQuiz($quizId);
        $this->redirect(BASE_PATH . "/lms/faculty/course/{$lmsCourseId}/quizzes");
    }

    public function deleteQuestion(Request $request, Response $response, string $courseId, string $id, string $questionId)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $qId = (int)$questionId;
        $this->authorizeFaculty($response, $lmsCourseId);

        $quiz = $this->quizService->getQuiz($quizId);
        if (!$quiz || $quiz['lms_course_id'] != $lmsCourseId) {
            $this->notFound($response);
            return;
        }

        $question = $this->quizService->getQuestion($qId);
        if (!$question || (int)$question['lms_quiz_id'] !== $quizId) {
            $this->notFound($response);
            return;
        }

        $this->quizService->deleteQuestion($qId);
        $this->redirect(BASE_PATH . "/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/questions");
    }

    // --- QUESTION BUILDER: CSV IMPORT, CONTENT GENERATION, DRAFT REVIEW --- //

    private const DRAFT_SESSION_KEY = 'quiz_builder_drafts';
    private const FLASH_SESSION_KEY = 'quiz_builder_flash';
    private const MAX_DRAFT_QUESTIONS = 200;
    private const MAX_GENERATED_QUESTIONS = 50;

    /**
     * Loads a quiz after checking the faculty member owns the course and the quiz belongs to it.
     */
    private function loadOwnedQuiz(Response $response, int $lmsCourseId, int $quizId): array
    {
        $this->authorizeFaculty($response, $lmsCourseId);
        $quiz = $this->quizService->getQuiz($quizId);
        if (!$quiz || (int)$quiz['lms_course_id'] !== $lmsCourseId) {
            $this->notFound($response);
        }
        return $quiz;
    }

    private function setFlash(string $type, string $message): void
    {
        $_SESSION[self::FLASH_SESSION_KEY] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array
    {
        $flash = $_SESSION[self::FLASH_SESSION_KEY] ?? null;
        unset($_SESSION[self::FLASH_SESSION_KEY]);
        return $flash;
    }

    private function getDraft(int $quizId, int $lmsCourseId): ?array
    {
        $draft = $_SESSION[self::DRAFT_SESSION_KEY][$quizId] ?? null;
        if (!is_array($draft) || (int)($draft['course_id'] ?? 0) !== $lmsCourseId) {
            return null;
        }
        return $draft;
    }

    private function putDraft(int $quizId, int $lmsCourseId, array $draft): void
    {
        $draft['course_id'] = $lmsCourseId;
        $draft['updated_at'] = time();
        $_SESSION[self::DRAFT_SESSION_KEY][$quizId] = $draft;
    }

    private function clearDraft(int $quizId): void
    {
        unset($_SESSION[self::DRAFT_SESSION_KEY][$quizId]);
    }

    /**
     * Explains a failed save. The common cause is that the phase 11 migration has not been
     * applied, so MySQL rejects the new columns or question types.
     */
    private function saveFailureMessage(\Throwable $e, string $fallback): string
    {
        $message = $e->getMessage();
        $schemaMissing = str_contains($message, 'Unknown column')
            || (str_contains($message, 'question_type') && (str_contains($message, 'truncated') || str_contains($message, 'incorrect')));
        if ($schemaMissing) {
            return 'The database has not been updated for the new question types yet. Import database/migrations/lms_phase11_quiz_builder_schema.sql into the sia database (phpMyAdmin > Import), then save again. Nothing was saved, and your draft is still here.';
        }
        return $fallback;
    }

    private function generator(): QuizGeneratorInterface
    {
        // Only the built-in extractor is available: the project configures no AI or
        // question-generation service. New providers implement QuizGeneratorInterface.
        return new ExtractiveQuizGenerator();
    }

    public function csvTemplate(Request $request, Response $response, string $courseId)
    {
        $this->authorizeFaculty($response, (int)$courseId);

        $csv = QuizCsvImporter::templateCsv();
        if (!defined('TESTING_ENV')) {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="quiz_questions_template.csv"');
            header('Content-Length: ' . strlen($csv));
            header('X-Content-Type-Options: nosniff');
        }
        return $csv;
    }

    public function importCsv(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $this->loadOwnedQuiz($response, $lmsCourseId, $quizId);
        $questionsUrl = "/sia/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/questions";

        $importer = new QuizCsvImporter();
        $file = $_FILES['questions_csv'] ?? null;
        $uploadError = $importer->checkUpload($file);
        if ($uploadError !== null) {
            $this->setFlash('danger', $uploadError);
            $this->redirect($questionsUrl);
            return;
        }

        // The upload is only read from PHP's temporary file; it is never moved into a web-served folder.
        $parsed = $importer->parse((string)file_get_contents($file['tmp_name']));
        if (empty($parsed['questions'])) {
            $this->setFlash('danger', 'Nothing was imported. ' . implode(' ', $parsed['file_errors']));
            $this->redirect($questionsUrl);
            return;
        }

        $errors = [];
        foreach ($parsed['questions'] as $index => $question) {
            foreach ($parsed['row_errors'] as $rowError) {
                if ($rowError['row'] === $question['csv_row']) {
                    $errors[$index] = $rowError['errors'];
                }
            }
        }

        $this->putDraft($quizId, $lmsCourseId, [
            'source' => 'csv',
            'source_label' => 'CSV import: ' . basename((string)$file['name']),
            'questions' => $parsed['questions'],
            'errors' => $errors,
            'notices' => $parsed['file_errors'],
        ]);
        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/questions/review");
    }

    public function generateForm(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $quiz = $this->loadOwnedQuiz($response, $lmsCourseId, $quizId);

        $modules = $this->lmsService->getModulesWithMaterialsForCourse($lmsCourseId);
        foreach ($modules as &$module) {
            foreach ($module['materials'] as &$material) {
                $material['support'] = CourseContentExtractor::supportFor((string)($material['file_path'] ?: $material['file_name']));
            }
            unset($material);
        }
        unset($module);

        $generator = $this->generator();
        return $this->render('lms/faculty/quizzes/generate', [
            'course' => $this->lmsService->getCourseDetails($lmsCourseId),
            'quiz' => $quiz,
            'modules' => $modules,
            'generator' => ['label' => $generator->label(), 'description' => $generator->description()],
            'pdf_supported' => CourseContentExtractor::pdfSupported(),
            'flash' => $this->pullFlash(),
            'max_questions' => self::MAX_GENERATED_QUESTIONS
        ]);
    }

    public function generate(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $this->loadOwnedQuiz($response, $lmsCourseId, $quizId);
        $formUrl = "/sia/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/questions/generate";
        $data = $request->getBody();

        $counts = [];
        foreach (array_keys(QuizQuestionValidator::TYPES) as $type) {
            $value = $data['counts'][$type] ?? 0;
            $counts[$type] = is_numeric($value) ? max(0, min(self::MAX_GENERATED_QUESTIONS, (int)$value)) : 0;
        }
        $total = array_sum($counts);
        $points = is_numeric($data['points'] ?? null) ? round((float)$data['points'], 2) : 1.0;

        $problems = [];
        if ($total < 1 || $total > self::MAX_GENERATED_QUESTIONS) {
            $problems[] = 'Request between 1 and ' . self::MAX_GENERATED_QUESTIONS . ' questions in total.';
        }
        if ($points < QuizQuestionValidator::MIN_POINTS || $points > QuizQuestionValidator::MAX_POINTS) {
            $problems[] = 'Points per question must be between ' . QuizQuestionValidator::MIN_POINTS . ' and ' . QuizQuestionValidator::MAX_POINTS . '.';
        }

        // Only modules and materials that belong to this course are ever read.
        $selectedModules = array_map('intval', (array)($data['modules'] ?? []));
        $selectedMaterials = array_map('intval', (array)($data['materials'] ?? []));
        $modules = [];
        $materials = [];
        foreach ($this->lmsService->getModulesWithMaterialsForCourse($lmsCourseId) as $module) {
            if (in_array((int)$module['id'], $selectedModules, true)) {
                $modules[] = $module;
            }
            foreach ($module['materials'] as $material) {
                if (in_array((int)$material['id'], $selectedMaterials, true)) {
                    $material['module_title'] = $module['title'];
                    $material['resolved_path'] = $this->lmsService->resolveMaterialPath($material);
                    $materials[] = $material;
                }
            }
        }
        if (empty($modules) && empty($materials)) {
            $problems[] = 'Select at least one module or material from this course.';
        }

        if (!empty($problems)) {
            $this->setFlash('danger', implode(' ', $problems));
            $this->redirect($formUrl);
            return;
        }

        $content = (new CourseContentExtractor())->extract($modules, $materials);
        $notices = [];
        foreach ($content['skipped'] as $skip) {
            $notices[] = 'Skipped ' . $skip['label'] . ': ' . $skip['reason'] . '.';
        }
        if (empty($content['segments'])) {
            $this->setFlash('danger', 'No readable text was found in the selection. ' . implode(' ', $notices));
            $this->redirect($formUrl);
            return;
        }

        $generator = $this->generator();
        $result = $generator->generate($content['segments'], $counts, $points);
        $notices = array_merge($notices, $result['notices']);

        if (empty($result['questions'])) {
            $this->setFlash('warning', implode(' ', $notices));
            $this->redirect($formUrl);
            return;
        }

        $validator = new QuizQuestionValidator();
        $questions = [];
        $errors = [];
        foreach ($result['questions'] as $index => $question) {
            [$clean, $questionErrors] = $validator->validate($question);
            $questions[] = $clean;
            if (!empty($questionErrors)) {
                $errors[$index] = $questionErrors;
            }
        }

        $this->putDraft($quizId, $lmsCourseId, [
            'source' => 'generator',
            'source_label' => $generator->label(),
            'questions' => $questions,
            'errors' => $errors,
            'notices' => $notices,
        ]);
        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/questions/review");
    }

    public function reviewDraft(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $quiz = $this->loadOwnedQuiz($response, $lmsCourseId, $quizId);

        $draft = $this->getDraft($quizId, $lmsCourseId);
        if ($draft === null) {
            $this->setFlash('warning', 'There is no imported or generated draft to review.');
            $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/questions");
            return;
        }

        return $this->render('lms/faculty/quizzes/review', [
            'course' => $this->lmsService->getCourseDetails($lmsCourseId),
            'quiz' => $quiz,
            'draft' => $draft,
            'existing_count' => count($this->quizService->getQuestions($quizId)),
            'flash' => $this->pullFlash()
        ]);
    }

    public function saveDraft(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $quiz = $this->loadOwnedQuiz($response, $lmsCourseId, $quizId);
        $reviewUrl = "/sia/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/questions/review";

        $draft = $this->getDraft($quizId, $lmsCourseId);
        if ($draft === null) {
            $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/questions");
            return;
        }

        $data = $request->getBody();
        $posted = array_values(array_filter((array)($data['questions'] ?? []), 'is_array'));
        if (count($posted) > self::MAX_DRAFT_QUESTIONS) {
            $posted = array_slice($posted, 0, self::MAX_DRAFT_QUESTIONS);
        }

        $validator = new QuizQuestionValidator();
        $clean = [];
        $errors = [];
        foreach ($posted as $index => $item) {
            $answers = $item['accepted_answers'] ?? '';
            $item['accepted_answers'] = QuizQuestionValidator::splitAnswers(is_array($answers) ? implode("\n", array_map('strval', $answers)) : (string)$answers);
            $item['choices'] = array_map('strval', array_values((array)($item['choices'] ?? [])));
            [$question, $questionErrors] = $validator->validate($item);
            $clean[] = $question;
            if (!empty($questionErrors)) {
                $errors[$index] = $questionErrors;
            }
        }

        if (empty($clean)) {
            $this->clearDraft($quizId);
            $this->setFlash('warning', 'All draft questions were removed, so nothing was saved.');
            $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/questions");
            return;
        }

        if (!empty($errors)) {
            $draft['questions'] = $clean;
            $draft['errors'] = $errors;
            $this->putDraft($quizId, $lmsCourseId, $draft);
            $this->setFlash('danger', count($errors) . ' question(s) need fixing before anything can be saved.');
            $this->redirect($reviewUrl);
            return;
        }

        $publish = !empty($data['publish']) && $quiz['status'] !== 'published';
        try {
            $saved = $this->quizService->addValidatedQuestions($quizId, $clean, $publish);
        } catch (\Throwable $e) {
            error_log('Quiz draft save failed: ' . $e->getMessage());
            $this->setFlash('danger', $this->saveFailureMessage($e, 'The questions could not be saved. Nothing was changed; please try again.'));
            $this->redirect($reviewUrl);
            return;
        }

        $this->clearDraft($quizId);
        $this->setFlash('success', $saved . ' question(s) saved' . ($publish ? ' and the quiz is now published.' : '. The quiz status was not changed.'));
        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/questions");
    }

    public function discardDraft(Request $request, Response $response, string $courseId, string $id)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $this->loadOwnedQuiz($response, $lmsCourseId, $quizId);

        $this->clearDraft($quizId);
        $this->setFlash('success', 'Draft discarded. No questions were added.');
        $this->redirect("/sia/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/questions");
    }

    // --- MANUAL REVIEW OF TEXT ANSWERS --- //

    private function loadOwnedAttempt(Response $response, int $quizId, int $attemptId): array
    {
        $attempt = $this->quizService->getAttemptWithStudent($attemptId);
        if (!$attempt || (int)$attempt['lms_quiz_id'] !== $quizId || $attempt['status'] === 'in_progress') {
            $this->notFound($response);
        }
        return $attempt;
    }

    public function reviewAttempt(Request $request, Response $response, string $courseId, string $id, string $attemptId)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $quiz = $this->loadOwnedQuiz($response, $lmsCourseId, $quizId);
        $attempt = $this->loadOwnedAttempt($response, $quizId, (int)$attemptId);

        $answers = [];
        foreach ($this->quizService->getAttemptDetails((int)$attempt['id']) as $answer) {
            $answers[(int)$answer['lms_question_id']] = $answer;
        }

        return $this->render('lms/faculty/quizzes/attempt_review', [
            'course' => $this->lmsService->getCourseDetails($lmsCourseId),
            'quiz' => $quiz,
            'attempt' => $attempt,
            'questions' => $this->quizService->getQuestions($quizId, true),
            'answers' => $answers,
            'flash' => $this->pullFlash()
        ]);
    }

    public function saveAttemptReview(Request $request, Response $response, string $courseId, string $id, string $attemptId)
    {
        $lmsCourseId = (int)$courseId;
        $quizId = (int)$id;
        $this->loadOwnedQuiz($response, $lmsCourseId, $quizId);
        $attempt = $this->loadOwnedAttempt($response, $quizId, (int)$attemptId);
        $reviewUrl = "/sia/lms/faculty/course/{$lmsCourseId}/quizzes/{$quizId}/attempts/{$attempt['id']}/review";

        $questions = [];
        foreach ($this->quizService->getQuestions($quizId) as $q) {
            $questions[(int)$q['id']] = $q;
        }

        $data = $request->getBody();
        $decisions = [];
        $problems = [];
        foreach ((array)($data['awarded'] ?? []) as $questionId => $value) {
            $question = $questions[(int)$questionId] ?? null;
            if (!$question || !QuizQuestionValidator::isTextType($question['question_type'])) {
                continue;
            }
            if (!is_numeric($value) || (float)$value < 0 || (float)$value > (float)$question['points']) {
                $problems[] = 'Points for a question must be between 0 and ' . (float)$question['points'] . '.';
                continue;
            }
            $points = round((float)$value, 2);
            $decisions[(int)$questionId] = ['points' => $points, 'is_correct' => $points >= (float)$question['points']];
        }

        if (!empty($problems)) {
            $this->setFlash('danger', implode(' ', array_unique($problems)) . ' Nothing was saved.');
            $this->redirect($reviewUrl);
            return;
        }

        if ($this->quizService->reviewAttempt((int)$attempt['id'], $decisions, (int)($_SESSION['user_id'] ?? 0))) {
            $this->setFlash('success', 'Review saved and the attempt score was recalculated.');
        } else {
            $this->setFlash('danger', 'The review could not be saved. Please try again.');
        }
        $this->redirect($reviewUrl);
    }
}
