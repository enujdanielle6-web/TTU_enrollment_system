<?php
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Services/LmsService.php';
require_once __DIR__ . '/../app/Repositories/EnrollmentRepositoryInterface.php';
require_once __DIR__ . '/../app/Repositories/CollegeEnrollmentRepository.php';
require_once __DIR__ . '/../app/Repositories/ShsEnrollmentRepository.php';

use App\Core\Database;
use App\Services\LmsService;

try {
    $pdo = Database::getConnection();
    $lmsService = new LmsService();
    
    // Find an active LMS course
    $stmt = $pdo->query("SELECT id FROM lms_courses LIMIT 1");
    $lmsCourseId = $stmt->fetchColumn();

    if (!$lmsCourseId) {
        echo "No LMS course found.\n";
        exit(1);
    }

    $assignmentId = $lmsService->createAssignment([
        'lms_course_id' => $lmsCourseId,
        'title' => 'Final Project Submission',
        'description' => 'Please upload your final project documentation here. Max 10MB.',
        'due_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
        'max_score' => 100,
        'status' => 'published'
    ]);

    echo "Created assignment ID $assignmentId for course $lmsCourseId\n";

    // Assume user 6 is a student
    $studentId = 6; 
    
    // Dummy submission file
    $targetDir = __DIR__ . '/../app/uploads/lms/submissions';
    $fileName = 'student6_submission_' . time() . '.pdf';
    $filePath = $targetDir . '/' . $fileName;
    file_put_contents($filePath, '%PDF-1.4 Dummy Submission by Student');

    $submissionId = $lmsService->submitAssignment($assignmentId, $studentId, [
        'file_name' => 'My_Final_Project.pdf',
        'file_path' => $fileName,
        'mime_type' => 'application/pdf',
        'file_size' => filesize($filePath)
    ]);

    echo "Created submission ID $submissionId for student $studentId\n";

    // Grade it
    // Assume user 1 is faculty
    $lmsService->gradeSubmission($submissionId, 1, 95.50, "Great job! Very thorough documentation.");
    
    echo "Graded submission ID $submissionId\n";
    echo "Done.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
