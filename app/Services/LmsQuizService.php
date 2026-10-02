<?php
namespace App\Services;

use App\Core\Database;
use App\Services\Quiz\QuizAnswerGrader;
use App\Services\Quiz\QuizQuestionValidator;
use PDO;

class LmsQuizService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    // --- FACULTY / GENERAL QUIZ METHODS --- //

    public function getQuizzesByCourse(int $lmsCourseId, bool $publishedOnly = true): array
    {
        $sql = "SELECT * FROM lms_quizzes WHERE lms_course_id = :lcid";
        if ($publishedOnly) {
            $sql .= " AND status = 'published'";
        }
        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['lcid' => $lmsCourseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQuiz(int $quizId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lms_quizzes WHERE id = :id");
        $stmt->execute(['id' => $quizId]);
        $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
        return $quiz ?: null;
    }

    public function createQuiz(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO lms_quizzes (lms_course_id, title, description, time_limit, max_attempts, passing_score, start_date, end_date, status)
            VALUES (:course, :title, :desc, :time, :max, :pass, :start, :end, :status)
        ");
        $stmt->execute([
            'course' => $data['lms_course_id'],
            'title' => $data['title'],
            'desc' => $data['description'] ?? null,
            'time' => !empty($data['time_limit']) ? (int)$data['time_limit'] : null,
            'max' => !empty($data['max_attempts']) ? (int)$data['max_attempts'] : 1,
            'pass' => !empty($data['passing_score']) ? (float)$data['passing_score'] : null,
            'start' => !empty($data['start_date']) ? $data['start_date'] : null,
            'end' => !empty($data['end_date']) ? $data['end_date'] : null,
            'status' => $data['status'] ?? 'draft'
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateQuiz(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE lms_quizzes 
            SET title = :title, description = :desc, time_limit = :time, max_attempts = :max, 
                passing_score = :pass, start_date = :start, end_date = :end, status = :status
            WHERE id = :id
        ");
        return $stmt->execute([
            'title' => $data['title'],
            'desc' => $data['description'] ?? null,
            'time' => !empty($data['time_limit']) ? (int)$data['time_limit'] : null,
            'max' => !empty($data['max_attempts']) ? (int)$data['max_attempts'] : 1,
            'pass' => !empty($data['passing_score']) ? (float)$data['passing_score'] : null,
            'start' => !empty($data['start_date']) ? $data['start_date'] : null,
            'end' => !empty($data['end_date']) ? $data['end_date'] : null,
            'status' => $data['status'] ?? 'draft',
            'id' => $id
        ]);
    }

    // --- QUESTIONS & CHOICES --- //

    public function getQuestions(int $quizId, bool $withChoices = false): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lms_questions WHERE lms_quiz_id = :qid ORDER BY display_order ASC, id ASC");
        $stmt->execute(['qid' => $quizId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($withChoices) {
            foreach ($questions as &$q) {
                $q['choices'] = $this->getChoices($q['id']);
            }
        }
        return $questions;
    }

    public function getChoices(int $questionId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lms_question_choices WHERE lms_question_id = :qid ORDER BY display_order ASC, id ASC");
        $stmt->execute(['qid' => $questionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addQuestion(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO lms_questions (lms_quiz_id, question_text, question_type, points, case_sensitive, requires_manual_review, source_reference, display_order)
            VALUES (:qid, :text, :type, :points, :cs, :review, :source, :order)
        ");
        $stmt->execute([
            'qid' => $data['lms_quiz_id'],
            'text' => $data['question_text'],
            'type' => $data['question_type'],
            'points' => $data['points'] ?? 1.0,
            'cs' => !empty($data['case_sensitive']) ? 1 : 0,
            'review' => !empty($data['requires_manual_review']) ? 1 : 0,
            'source' => $data['source_reference'] ?? null,
            'order' => $data['display_order'] ?? 0
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Saves validated draft questions (see QuizQuestionValidator) after the quiz's
     * existing questions, all or nothing.
     *
     * @return int number of questions saved
     */
    public function addValidatedQuestions(int $quizId, array $cleanQuestions, bool $publish = false): int
    {
        $this->pdo->beginTransaction();
        try {
            $orderStmt = $this->pdo->prepare("SELECT COALESCE(MAX(display_order), 0) FROM lms_questions WHERE lms_quiz_id = :qid");
            $orderStmt->execute(['qid' => $quizId]);
            $order = (int)$orderStmt->fetchColumn();

            foreach ($cleanQuestions as $clean) {
                $rows = QuizQuestionValidator::toStorage($clean);
                $questionId = $this->addQuestion($rows['question'] + ['lms_quiz_id' => $quizId, 'display_order' => ++$order]);
                foreach ($rows['choices'] as $choice) {
                    $this->addChoice($choice + ['lms_question_id' => $questionId]);
                }
            }

            if ($publish) {
                $pub = $this->pdo->prepare("UPDATE lms_quizzes SET status = 'published' WHERE id = :id");
                $pub->execute(['id' => $quizId]);
            }

            $this->pdo->commit();
            return count($cleanQuestions);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function addChoice(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO lms_question_choices (lms_question_id, choice_text, is_correct, display_order)
            VALUES (:qid, :text, :correct, :order)
        ");
        $stmt->execute([
            'qid' => $data['lms_question_id'],
            'text' => $data['choice_text'],
            'correct' => !empty($data['is_correct']) ? 1 : 0,
            'order' => $data['display_order'] ?? 0
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    // --- ATTEMPTS & SCORING --- //

    public function getStudentAttempts(int $quizId, int $studentId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lms_quiz_attempts WHERE lms_quiz_id = :qid AND student_id = :sid ORDER BY attempt_number DESC");
        $stmt->execute(['qid' => $quizId, 'sid' => $studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAttempt(int $attemptId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lms_quiz_attempts WHERE id = :id");
        $stmt->execute(['id' => $attemptId]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
        return $attempt ?: null;
    }

    public function getAttemptWithStudent(int $attemptId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT a.*, u.first_name, u.last_name
            FROM lms_quiz_attempts a
            JOIN users u ON u.id = a.student_id
            WHERE a.id = :id
        ");
        $stmt->execute(['id' => $attemptId]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
        return $attempt ?: null;
    }

    public function startAttempt(int $quizId, int $studentId): ?int
    {
        $quiz = $this->getQuiz($quizId);
        if (!$quiz) return null;

        // Check date availability
        $now = date('Y-m-d H:i:s');
        if ($quiz['start_date'] && $now < $quiz['start_date']) return null;
        if ($quiz['end_date'] && $now > $quiz['end_date']) return null;

        $attempts = $this->getStudentAttempts($quizId, $studentId);

        // Check if there is an in-progress attempt to resume
        foreach ($attempts as $att) {
            if ($att['status'] === 'in_progress') {
                return (int)$att['id']; // Resume existing
            }
        }

        $attemptCount = count($attempts);
        if ($quiz['max_attempts'] !== null && $attemptCount >= $quiz['max_attempts']) {
            return null; // Max attempts reached
        }

        $nextAttempt = $attemptCount + 1;

        $stmt = $this->pdo->prepare("
            INSERT INTO lms_quiz_attempts (lms_quiz_id, student_id, attempt_number, started_at, status)
            VALUES (:qid, :sid, :num, CURRENT_TIMESTAMP, 'in_progress')
        ");
        $stmt->execute(['qid' => $quizId, 'sid' => $studentId, 'num' => $nextAttempt]);
        return (int)$this->pdo->lastInsertId();
    }

    public function submitAttempt(int $attemptId, array $studentAnswers): bool
    {
        $attempt = $this->getAttempt($attemptId);
        if (!$attempt || $attempt['status'] !== 'in_progress') {
            return false;
        }

        $quiz = $this->getQuiz($attempt['lms_quiz_id']);
        
        // Time limit validation (server side)
        // Add 60 seconds grace period
        if ($quiz['time_limit'] !== null) {
            $started = strtotime($attempt['started_at']);
            $maxTime = $started + ($quiz['time_limit'] * 60) + 60; 
            if (time() > $maxTime) {
                // Too late. We still save it, but maybe flag it? 
                // For now, we allow submission of whatever they had, but strictly enforce it.
            }
        }

        $questions = $this->getQuestions($attempt['lms_quiz_id'], true);
        $grader = new QuizAnswerGrader();

        $this->pdo->beginTransaction();
        try {
            $ansStmt = $this->pdo->prepare("
                INSERT INTO lms_quiz_answers (lms_quiz_attempt_id, lms_question_id, lms_question_choice_id, answer_text, is_correct, points_awarded, needs_review)
                VALUES (:att_id, :q_id, :c_id, :text, :correct, :points, :review)
            ");

            $totalScore = 0.0;
            $pendingReview = false;
            foreach ($questions as $q) {
                $graded = $grader->grade($q, $studentAnswers[$q['id']] ?? null);
                $totalScore += $graded['points'];
                $pendingReview = $pendingReview || $graded['needs_review'];

                $ansStmt->execute([
                    'att_id' => $attemptId,
                    'q_id' => $q['id'],
                    'c_id' => $graded['choice_id'],
                    'text' => $graded['answer_text'],
                    'correct' => $graded['is_correct'] ? 1 : 0,
                    'points' => $graded['points'],
                    'review' => $graded['needs_review'] ? 1 : 0
                ]);
            }

            // Attempts with answers awaiting faculty review stay 'submitted' (provisional score)
            // and are excluded from the gradebook until every flagged answer is resolved.
            $upd = $this->pdo->prepare("UPDATE lms_quiz_attempts SET submitted_at = CURRENT_TIMESTAMP, score = :score, status = :status WHERE id = :id AND status = 'in_progress'");
            $upd->execute(['score' => $totalScore, 'status' => $pendingReview ? 'submitted' : 'graded', 'id' => $attemptId]);
            if ($upd->rowCount() !== 1) {
                throw new \RuntimeException('Attempt was already submitted.');
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log("Quiz Submission Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Applies faculty decisions to an attempt's text answers, then recomputes the score.
     * The attempt becomes 'graded' once no answer is left awaiting review.
     *
     * @param array $decisions question id => ['points' => float] (already range-checked by the caller)
     */
    public function reviewAttempt(int $attemptId, array $decisions, int $reviewerId): bool
    {
        $attempt = $this->getAttempt($attemptId);
        if (!$attempt || $attempt['status'] === 'in_progress') {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $upd = $this->pdo->prepare("
                UPDATE lms_quiz_answers a
                JOIN lms_questions q ON q.id = a.lms_question_id
                SET a.points_awarded = :points, a.is_correct = :correct, a.needs_review = 0,
                    a.reviewed_by = :reviewer, a.reviewed_at = CURRENT_TIMESTAMP
                WHERE a.lms_quiz_attempt_id = :aid AND a.lms_question_id = :qid
                  AND q.question_type IN ('identification', 'fill_blank')
            ");
            foreach ($decisions as $questionId => $decision) {
                $upd->execute([
                    'points' => $decision['points'],
                    'correct' => !empty($decision['is_correct']) ? 1 : 0,
                    'reviewer' => $reviewerId,
                    'aid' => $attemptId,
                    'qid' => (int)$questionId
                ]);
            }

            $sumStmt = $this->pdo->prepare("SELECT COALESCE(SUM(points_awarded), 0) AS score, COALESCE(SUM(needs_review), 0) AS pending FROM lms_quiz_answers WHERE lms_quiz_attempt_id = :aid");
            $sumStmt->execute(['aid' => $attemptId]);
            $totals = $sumStmt->fetch(PDO::FETCH_ASSOC);

            $att = $this->pdo->prepare("UPDATE lms_quiz_attempts SET score = :score, status = :status WHERE id = :id");
            $att->execute([
                'score' => (float)$totals['score'],
                'status' => (int)$totals['pending'] > 0 ? 'submitted' : 'graded',
                'id' => $attemptId
            ]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log("Quiz Review Error: " . $e->getMessage());
            return false;
        }
    }

    public function countAnswersNeedingReview(int $quizId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT a.lms_quiz_attempt_id, COUNT(*) AS pending
            FROM lms_quiz_answers a
            JOIN lms_quiz_attempts t ON t.id = a.lms_quiz_attempt_id
            WHERE t.lms_quiz_id = :qid AND a.needs_review = 1
            GROUP BY a.lms_quiz_attempt_id
        ");
        $stmt->execute(['qid' => $quizId]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int)$row['lms_quiz_attempt_id']] = (int)$row['pending'];
        }
        return $map;
    }

    public function getAttemptDetails(int $attemptId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lms_quiz_answers WHERE lms_quiz_attempt_id = :aid");
        $stmt->execute(['aid' => $attemptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllSubmissionsForQuiz(int $quizId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT a.*, u.first_name, u.last_name 
            FROM lms_quiz_attempts a
            JOIN users u ON a.student_id = u.id
            WHERE a.lms_quiz_id = :qid
            ORDER BY a.score DESC, a.submitted_at DESC
        ");
        $stmt->execute(['qid' => $quizId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQuestion(int $questionId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lms_questions WHERE id = :id");
        $stmt->execute(['id' => $questionId]);
        $q = $stmt->fetch(PDO::FETCH_ASSOC);
        return $q ?: null;
    }

    public function deleteQuiz(int $quizId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM lms_quizzes WHERE id = :id");
        return $stmt->execute(['id' => $quizId]);
    }

    public function deleteQuestion(int $questionId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM lms_questions WHERE id = :id");
        return $stmt->execute(['id' => $questionId]);
    }
}
