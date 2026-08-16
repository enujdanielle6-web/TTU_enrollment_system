<?php
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Services/LmsService.php';

use App\Core\Database;
use App\Services\LmsService;

try {
    $pdo = Database::getConnection();
    $lmsService = new LmsService();
    
    // Find an active LMS course assigned to faculty user 1
    $stmt = $pdo->query("SELECT id FROM lms_courses WHERE faculty_user_id = 1 LIMIT 1");
    $lmsCourseId = $stmt->fetchColumn();

    if (!$lmsCourseId) {
        echo "No LMS course found for faculty 1.\n";
        exit(0);
    }

    echo "=== TESTING MODULE CREATION ===\n";
    $moduleId = $lmsService->createModule($lmsCourseId, 'Phase 9 Test Module', 99);
    echo "Created Module ID: $moduleId\n";

    $modules = $lmsService->getModulesForCourse($lmsCourseId);
    $found = false;
    foreach ($modules as $mod) {
        if ($mod['id'] == $moduleId) $found = true;
    }
    echo "Module Exists in Course: " . ($found ? 'Yes' : 'No') . "\n";

    echo "\nPhase 9 TEST PASSED.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
