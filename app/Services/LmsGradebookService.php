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
        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        if (!$course) return [];

        $type = $course['academic_level'];
        $sectionId = $course['academic_section_id'];
        $subjectId = $course['subject_id'];

        if ($type === 'College') {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.student_number, u.first_name, u.last_name 
                FROM college_enrollments ce
                JOIN applications a ON ce.application_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE ce.college_section_id = :sec AND ce.subject_id = :sub
                ORDER BY u.last_name ASC, u.first_name ASC
            ");
        } else {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.student_number, u.first_name, u.last_name 
                FROM shs_enrollments se
                JOIN applications a ON se.application_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE se.shs_section_id = :sec AND se.subject_id = :sub
                ORDER BY u.last_name ASC, u.first_name ASC
            ");
        }
        
        $stmt->execute(['sec' => $sectionId, 'sub' => $subjectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        // 3. Build Grid
        $grid = [];
        foreach ($students as $student) {
            $studentId = $student['id'];
            $studentTotal = 0;
            
            $studentData = [
                'student' => $student,
                'assignments' => [],
                'quizzes' => []
            ];

            // Assignments
            foreach ($assignments as $a) {
                $sub = $this->lmsService->getStudentSubmission($a['id'], $studentId);
                $grade = ($sub && $sub['status'] === 'GRADED') ? (float)$sub['grade'] : null;
                $studentData['assignments'][$a['id']] = $grade;
                if ($grade !== null) $studentTotal += $grade;
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
     * Builds personal gradebook for one student.
     */
    public function getStudentGradebook(int $lmsCourseId, int $studentId): array
    {
        $gradebook = $this->getCourseGradebook($lmsCourseId);
        
        $myGridRow = null;
        foreach ($gradebook['grid'] as $row) {
            if ($row['student']['id'] == $studentId) {
                $myGridRow = $row;
                break;
            }
        }

        return [
            'assignments' => $gradebook['assignments'],
            'quizzes' => $gradebook['quizzes'],
            'quiz_max_points' => $gradebook['quiz_max_points'],
            'total_possible' => $gradebook['total_possible'],
            'my_grades' => $myGridRow
        ];
    }
}
