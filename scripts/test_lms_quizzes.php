<?php
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Services/LmsQuizService.php';
require_once __DIR__ . '/../app/Services/LmsService.php';

use App\Core\Database;
use App\Services\LmsQuizService;

try {
    $pdo = Database::getConnection();
    $quizService = new LmsQuizService();
    
    // Find an active LMS course
    $stmt = $pdo->query("SELECT id FROM lms_courses LIMIT 1");
    $lmsCourseId = $stmt->fetchColumn();

    if (!$lmsCourseId) {
        echo "No LMS course found.\n";
        exit(1);
    }

    $quizId = $quizService->createQuiz([
        'lms_course_id' => $lmsCourseId,
        'title' => 'Test Quiz Server Grading',
        'description' => 'A test quiz.',
        'time_limit' => 30,
        'max_attempts' => 2,
        'passing_score' => 75,
        'status' => 'published'
    ]);

    echo "Created quiz ID $quizId\n";

    // Add True/False Question
    $q1Id = $quizService->addQuestion([
        'lms_quiz_id' => $quizId,
        'question_text' => 'The sky is blue.',
        'question_type' => 'true_false',
        'points' => 50,
        'display_order' => 1
    ]);
    $c1TrueId = $quizService->addChoice(['lms_question_id' => $q1Id, 'choice_text' => 'True', 'is_correct' => 1, 'display_order' => 1]);
    $c1FalseId = $quizService->addChoice(['lms_question_id' => $q1Id, 'choice_text' => 'False', 'is_correct' => 0, 'display_order' => 2]);

    // Add Multiple Choice Question
    $q2Id = $quizService->addQuestion([
        'lms_quiz_id' => $quizId,
        'question_text' => 'What is 2 + 2?',
        'question_type' => 'multiple_choice',
        'points' => 50,
        'display_order' => 2
    ]);
    $c2_3Id = $quizService->addChoice(['lms_question_id' => $q2Id, 'choice_text' => '3', 'is_correct' => 0, 'display_order' => 1]);
    $c2_4Id = $quizService->addChoice(['lms_question_id' => $q2Id, 'choice_text' => '4', 'is_correct' => 1, 'display_order' => 2]);
    $c2_5Id = $quizService->addChoice(['lms_question_id' => $q2Id, 'choice_text' => '5', 'is_correct' => 0, 'display_order' => 3]);

    // Assume user 6 is a student
    $studentId = 6; 
    
    // Attempt
    $attemptId = $quizService->startAttempt($quizId, $studentId);
    echo "Started attempt ID $attemptId\n";

    // Submit Answers (q1 correct, q2 correct)
    $answers = [
        $q1Id => $c1TrueId,
        $q2Id => $c2_4Id
    ];

    $success = $quizService->submitAttempt($attemptId, $answers);
    
    $attempt = $quizService->getAttempt($attemptId);
    echo "Submission success: " . ($success ? "Yes" : "No") . "\n";
    echo "Score: {$attempt['score']}\n";
    
    // Expected score 100
    if ($attempt['score'] == 100) {
        echo "Server side grading TEST PASSED.\n";
    } else {
        echo "Server side grading TEST FAILED.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
