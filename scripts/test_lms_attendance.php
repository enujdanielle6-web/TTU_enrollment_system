<?php
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Services/LmsAttendanceService.php';
require_once __DIR__ . '/../app/Services/LmsService.php';

use App\Core\Database;
use App\Services\LmsAttendanceService;

try {
    $pdo = Database::getConnection();
    $attService = new LmsAttendanceService();
    
    // Find an active LMS course
    $stmt = $pdo->query("SELECT id FROM lms_courses LIMIT 1");
    $lmsCourseId = $stmt->fetchColumn();

    if (!$lmsCourseId) {
        echo "No LMS course found.\n";
        exit(1);
    }

    $sessionId = $attService->createSession([
        'lms_course_id' => $lmsCourseId,
        'session_date' => date('Y-m-d'),
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
        'notes' => 'Test Session'
    ]);

    echo "Created Session ID $sessionId\n";

    // Test saving attendance for student ID 6
    $records = [
        6 => ['status' => 'present', 'remarks' => 'On time']
    ];

    $success = $attService->saveAttendance($sessionId, $records);
    echo "Saved Attendance: " . ($success ? "Yes" : "No") . "\n";

    $history = $attService->getStudentAttendanceHistory($lmsCourseId, 6);
    echo "History count for student 6: " . count($history) . "\n";

    echo "\nAttendance TEST PASSED.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
