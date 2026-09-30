<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class LmsGradebookService
{
    private PDO $pdo;
    private LmsService $lmsService;
    private LmsQuizService $quizService;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->lmsService = new LmsService();
        $this->quizService = new LmsQuizService();
    }

    /**
     * Get enrolled students for a specific LMS Course.
     * Maps the LMS Course to its underlying section and fetches enrollments.
     */
    private function getEnrolledStudents(int $lmsCourseId): array
    {
        return $this->lmsService->getCourseRoster($lmsCourseId);
    }

    /**
     * Builds the full gradebook grid for a course.
     */
    public function getCourseGradebook(int $lmsCourseId): array
    {
        // 1. Get Assessments
        $assignments = $this->lmsService->getAssignmentsByCourse($lmsCourseId, true); // published only
        $quizzes = $this->quizService->getQuizzesByCourse($lmsCourseId, true); // published only
        
        // 2. Get Enrolled Students
        $students = $this->getEnrolledStudents($lmsCourseId);

        // Calculate Totals
        $maxAssignmentPoints = array_sum(array_column($assignments, 'max_score'));
        
        $maxQuizPoints = 0;
        $quizTotalPointsMap = [];
        foreach ($quizzes as $quiz) {
            $questions = $this->quizService->getQuestions($quiz['id']);
            $points = array_sum(array_column($questions, 'points'));
            $maxQuizPoints += $points;
            $quizTotalPointsMap[$quiz['id']] = $points;
        }

        $totalPossible = $maxAssignmentPoints + $maxQuizPoints;

        // 3. Pre-fetch all submissions and attempts for this course in bulk (eliminates N+1 queries)
        $submissionsByStudentAndAssignment = [];
        if (!empty($assignments)) {
            $assignmentIds = array_column($assignments, 'id');
            $placeholders = implode(',', array_fill(0, count($assignmentIds), '?'));
            $subStmt = $this->pdo->prepare("
                SELECT * FROM lms_submissions 
                WHERE assignment_id IN ($placeholders)
                ORDER BY submitted_at DESC
            ");
            $subStmt->execute($assignmentIds);
            while ($sub = $subStmt->fetch(PDO::FETCH_ASSOC)) {
                $sId = (int)$sub['student_id'];
                $aId = (int)$sub['assignment_id'];
                if (!isset($submissionsByStudentAndAssignment[$sId][$aId])) {
                    $submissionsByStudentAndAssignment[$sId][$aId] = $sub;
                }
            }
        }

        $attemptsByStudentAndQuiz = [];
        if (!empty($quizzes)) {
            $quizIds = array_column($quizzes, 'id');
            $qPlaceholders = implode(',', array_fill(0, count($quizIds), '?'));
            $attStmt = $this->pdo->prepare("
                SELECT * FROM lms_quiz_attempts 
                WHERE lms_quiz_id IN ($qPlaceholders)
                ORDER BY attempt_number ASC
            ");
            $attStmt->execute($quizIds);
            while ($att = $attStmt->fetch(PDO::FETCH_ASSOC)) {
                $sId = (int)$att['student_id'];
                $qId = (int)$att['lms_quiz_id'];
                $attemptsByStudentAndQuiz[$sId][$qId][] = $att;
            }
        }

        // 4. Build Grid in memory (O(1) lookups)
        $grid = [];
        foreach ($students as $student) {
            $studentId = (int)$student['id'];
            $studentTotal = 0;
            
            $studentData = [
                'student' => $student,
                'assignments' => [],
                'assignment_submissions' => [],
                'quizzes' => [],
                'quiz_attempts' => []
            ];

            // Assignments
            foreach ($assignments as $a) {
                $sub = $submissionsByStudentAndAssignment[$studentId][$a['id']] ?? null;
                $grade = ($sub && $sub['status'] === 'GRADED') ? (float)$sub['grade'] : null;
                $studentData['assignments'][$a['id']] = $grade;
                $studentData['assignment_submissions'][$a['id']] = $sub;
                if ($grade !== null) $studentTotal += $grade;
            }

            // Quizzes (Max score among graded attempts)
            foreach ($quizzes as $q) {
                $attempts = $attemptsByStudentAndQuiz[$studentId][$q['id']] ?? [];
                $bestScore = null;
                foreach ($attempts as $att) {
                    if ($att['status'] === 'graded') {
                        if ($bestScore === null || $att['score'] > $bestScore) {
                            $bestScore = (float)$att['score'];
                        }
                    }
                }
                $studentData['quizzes'][$q['id']] = $bestScore;
                $studentData['quiz_attempts'][$q['id']] = $attempts;
                if ($bestScore !== null) $studentTotal += $bestScore;
            }

            $studentData['total'] = $studentTotal;
            $studentData['percentage'] = $totalPossible > 0 ? ($studentTotal / $totalPossible) * 100 : 0;
            
            $grid[] = $studentData;
        }

        return [
            'assignments' => $assignments,
            'quizzes' => $quizzes,
            'quiz_max_points' => $quizTotalPointsMap,
            'max_assignment_points' => $maxAssignmentPoints,
            'max_quiz_points' => $maxQuizPoints,
            'total_possible' => $totalPossible,
            'grid' => $grid
        ];
    }

    /**
     * Builds personal gradebook for one student in O(1) student complexity.
     * Fetches only assessments and this student's records without calculating
     * or loading the entire class roster.
     */
    public function getStudentPersonalGradebook(int $lmsCourseId, int $studentId): array
    {
        // 1. Get Assessments for this course
        $assignments = $this->lmsService->getAssignmentsByCourse($lmsCourseId, true);
        $quizzes = $this->quizService->getQuizzesByCourse($lmsCourseId, true);

        // 2. Calculate Maximum Assessment Points
        $maxAssignmentPoints = array_sum(array_column($assignments, 'max_score'));
        
        $maxQuizPoints = 0;
        $quizTotalPointsMap = [];
        foreach ($quizzes as $quiz) {
            $questions = $this->quizService->getQuestions($quiz['id']);
            $points = array_sum(array_column($questions, 'points'));
            $maxQuizPoints += $points;
            $quizTotalPointsMap[$quiz['id']] = $points;
        }

        $totalPossible = $maxAssignmentPoints + $maxQuizPoints;

        // 3. Assemble Only This Student's Grades
        $studentTotal = 0;
        $studentData = [
            'student' => ['id' => $studentId],
            'assignments' => [],
            'assignment_submissions' => [],
            'quizzes' => [],
            'quiz_attempts' => []
        ];

        // Assignments
        foreach ($assignments as $a) {
            $sub = $this->lmsService->getStudentSubmission($a['id'], $studentId);
            $grade = ($sub && $sub['status'] === 'GRADED') ? (float)$sub['grade'] : null;
            $studentData['assignments'][$a['id']] = $grade;
            $studentData['assignment_submissions'][$a['id']] = $sub;
            if ($grade !== null) {
                $studentTotal += $grade;
            }
        }

        // Quizzes (Max score among graded attempts)
        foreach ($quizzes as $q) {
            $attempts = $this->quizService->getStudentAttempts($q['id'], $studentId);
            $bestScore = null;
            foreach ($attempts as $att) {
                if ($att['status'] === 'graded') {
                    if ($bestScore === null || $att['score'] > $bestScore) {
                        $bestScore = (float)$att['score'];
                    }
                }
            }
            $studentData['quizzes'][$q['id']] = $bestScore;
            $studentData['quiz_attempts'][$q['id']] = $attempts;
            if ($bestScore !== null) {
                $studentTotal += $bestScore;
            }
        }

        $studentData['total'] = $studentTotal;
        $studentData['percentage'] = $totalPossible > 0 ? ($studentTotal / $totalPossible) * 100 : 0;

        return [
            'assignments' => $assignments,
            'quizzes' => $quizzes,
            'quiz_max_points' => $quizTotalPointsMap,
            'max_assignment_points' => $maxAssignmentPoints,
            'max_quiz_points' => $maxQuizPoints,
            'total_possible' => $totalPossible,
            'my_grades' => $studentData
        ];
    }

    /**
     * Builds personal gradebook for one student (backward compatible wrapper).
     */
    public function getStudentGradebook(int $lmsCourseId, int $studentId): array
    {
        return $this->getStudentPersonalGradebook($lmsCourseId, $studentId);
    }
}
