<?php
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Services/LmsService.php';
require_once __DIR__ . '/../app/Services/LmsCalendarService.php';
require_once __DIR__ . '/../app/Services/LmsAnnouncementService.php';

use App\Core\Database;
use App\Services\LmsCalendarService;
use App\Services\LmsAnnouncementService;

try {
    $pdo = Database::getConnection();
    $calService = new LmsCalendarService();
    $annService = new LmsAnnouncementService();
    
    // Find an active LMS course
    $stmt = $pdo->query("SELECT id FROM lms_courses LIMIT 1");
    $lmsCourseId = $stmt->fetchColumn();

    if (!$lmsCourseId) {
        echo "No LMS course found.\n";
        exit(1);
    }

    echo "=== TESTING ANNOUNCEMENTS ===\n";
    $annId = $annService->createAnnouncement([
        'lms_course_id' => $lmsCourseId,
        'author_user_id' => 1, // Assume admin/faculty is 1
        'title' => 'Test Announcement',
        'content' => 'Welcome to Phase 8!',
        'status' => 'published'
    ]);
    echo "Created Announcement ID: $annId\n";

    $anns = $annService->getCourseAnnouncements($lmsCourseId, true);
    echo "Published Announcements Count: " . count($anns) . "\n";


    echo "\n=== TESTING CALENDAR ===\n";
    // Student ID 6 (our test student)
    $events = $calService->getStudentCalendarEvents(6, date('m'), date('Y'));
    echo "Total Calendar Events for Student 6 this month: " . count($events) . "\n";
    foreach ($events as $ev) {
        echo "- {$ev['date']} {$ev['time']}: [{$ev['type']}] {$ev['title']}\n";
    }

    echo "\nPhase 8 TEST PASSED.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
