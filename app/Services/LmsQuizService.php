<?php
namespace App\Services;

use App\Core\Database;
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
            INSERT INTO lms_questions (lms_quiz_id, question_text, question_type, points, display_order)
            VALUES (:qid, :text, :type, :points, :order)
        ");
        $stmt->execute([
            'qid' => $data['lms_quiz_id'],
            'text' => $data['question_text'],
            'type' => $data['question_type'],
            'points' => $data['points'] ?? 1.0,
            'order' => $data['display_order'] ?? 0
        ]);
        return (int)$this->pdo->lastInsertId();
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

    public function startAttempt(int $quizId, int $studentId): ?int
    {
        $quiz = $this->getQuiz($quizId);
        if (!$quiz) return null;

        // Check date availability
        $now = date('Y-m-d H:i:s');
        if ($quiz['start_date'] && $now < $quiz['start_date']) return null;
        if ($quiz['end_date'] && $now > $quiz['end_date']) return null;

        $attempts = $this->getStudentAttempts($quizId, $studentId);
        $attemptCount = count($attempts);

        if ($quiz['max_attempts'] !== null && $attemptCount >= $quiz['max_attempts']) {
            return null; // Max attempts reached
        }

        // Check if there is an in-progress attempt
        foreach ($attempts as $att) {
            if ($att['status'] === 'in_progress') {
                return (int)$att['id']; // Resume existing
            }
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

        $totalScore = 0;
        
        $questions = $this->getQuestions($attempt['lms_quiz_id'], true);
        
        $this->pdo->beginTransaction();
        try {
            $ansStmt = $this->pdo->prepare("
                INSERT INTO lms_quiz_answers (lms_quiz_attempt_id, lms_question_id, lms_question_choice_id, is_correct, points_awarded)
                VALUES (:att_id, :q_id, :c_id, :correct, :points)
            ");

            foreach ($questions as $q) {
                $qId = $q['id'];
                $selectedChoiceId = $studentAnswers[$qId] ?? null;
                
                $isCorrect = false;
                $pointsAwarded = 0.0;

                if ($selectedChoiceId) {
                    // Verify if this choice is the correct one for this question
                    foreach ($q['choices'] as $c) {
                        if ($c['id'] == $selectedChoiceId && $c['is_correct']) {
                            $isCorrect = true;
                            $pointsAwarded = $q['points'];
                            break;
                        }
                    }
                }

                $totalScore += $pointsAwarded;

                $ansStmt->execute([
                    'att_id' => $attemptId,
                    'q_id' => $qId,
                    'c_id' => $selectedChoiceId ?: null,
                    'correct' => $isCorrect ? 1 : 0,
                    'points' => $pointsAwarded
                ]);
            }

            // Mark attempt as graded
            $upd = $this->pdo->prepare("UPDATE lms_quiz_attempts SET submitted_at = CURRENT_TIMESTAMP, score = :score, status = 'graded' WHERE id = :id");
            $upd->execute(['score' => $totalScore, 'id' => $attemptId]);

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log("Quiz Submission Error: " . $e->getMessage());
            return false;
        }
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
}
