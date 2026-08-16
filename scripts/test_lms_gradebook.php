<?php
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Services/LmsGradebookService.php';
require_once __DIR__ . '/../app/Services/LmsService.php';
require_once __DIR__ . '/../app/Services/LmsQuizService.php';

use App\Core\Database;
use App\Services\LmsGradebookService;

try {
    $pdo = Database::getConnection();
    $gradebookService = new LmsGradebookService();
    
    // Find an active LMS course
    $stmt = $pdo->query("SELECT id FROM lms_courses LIMIT 1");
    $lmsCourseId = $stmt->fetchColumn();

    if (!$lmsCourseId) {
        echo "No LMS course found.\n";
        exit(1);
    }

    $gradebook = $gradebookService->getCourseGradebook($lmsCourseId);

    echo "=== GRADEBOOK REPORT ===\n";
    echo "Total Possible Points: {$gradebook['total_possible']}\n";
    echo "Total Assignments: " . count($gradebook['assignments']) . " (Max Pts: {$gradebook['max_assignment_points']})\n";
    echo "Total Quizzes: " . count($gradebook['quizzes']) . " (Max Pts: {$gradebook['max_quiz_points']})\n";
    echo "\nStudents in Grid: " . count($gradebook['grid']) . "\n";

    if (!empty($gradebook['grid'])) {
        $firstStudent = $gradebook['grid'][0];
        echo "First Student: " . $firstStudent['student']['first_name'] . " " . $firstStudent['student']['last_name'] . "\n";
        echo "Total Score: {$firstStudent['total']}\n";
        echo "Percentage: " . number_format($firstStudent['percentage'], 2) . "%\n";
    }

    echo "\nGradebook TEST PASSED.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
