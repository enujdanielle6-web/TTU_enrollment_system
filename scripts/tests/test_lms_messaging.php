<?php
// Comprehensive verification script for LMS Messaging (Phase 10)
define('TESTING_ENV', true);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__, 2) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once dirname(__DIR__, 2) . '/app/Helpers/functions.php';

session_start();

$pdo = \App\Core\Database::getConnection();

echo "===============================================\n";
echo "       TTU LMS MESSAGING VERIFICATION SUITE    \n";
echo "===============================================\n\n";

// 1. Verify Database Schema
echo "[Test 1] Checking Database Tables...\n";
$tables = ['lms_threads', 'lms_thread_participants', 'lms_messages'];
foreach ($tables as $t) {
    $count = $pdo->query("SHOW TABLES LIKE '{$t}'")->rowCount();
    if ($count === 0) {
        throw new Exception("Table {$t} is missing!");
    }
    echo "  ✔ Table {$t} exists.\n";
}

// 2. Test Contact Lookup
echo "\n[Test 2] Testing Contact Queries...\n";
$msgService = new \App\Services\LmsMessageService();
$facultyContacts = $msgService->getFacultyContacts(8); // Alan Turing
echo "  ✔ Faculty Alan Turing contacts found: " . count($facultyContacts) . " students.\n";
if (empty($facultyContacts)) {
    throw new Exception("Expected at least 1 student contact for Alan Turing!");
}

$studentContacts = $msgService->getStudentContacts(252); // Enuj Catli
echo "  ✔ Student Enuj Catli contacts found: " . count($studentContacts) . " instructors.\n";
if (empty($studentContacts)) {
    throw new Exception("Expected at least 1 instructor contact for Enuj Catli!");
}

// 3. Test Thread Creation via Controller
echo "\n[Test 3] Testing Thread Creation (Faculty -> Student)...\n";
$_SESSION['user_id'] = 8;
$_SESSION['user_name'] = 'Alan Turing';
$_SESSION['user_role'] = 'faculty';
$_SESSION['csrf_token'] = 'test_token_123';

$facultyCtrl = new \App\Controllers\Lms\FacultyController();
$req = new \App\Core\Request();
$res = new \App\Core\Response();

// Simulate POST body
$_POST = [
    'csrf_token' => 'test_token_123',
    'recipient_id' => 252,
    'lms_course_id' => 2,
    'subject' => 'Midterm Review Consultation',
    'body' => 'Hello Enuj, please review the recursion problems in Module 2.'
];

$reflection = new ReflectionClass($req);
$property = $reflection->getProperty('data');
$property->setAccessible(true);
$property->setValue($req, $_POST);

$facultyCtrl->sendMessage($req, $res);
$redirectUrl = $facultyCtrl->redirectUrl ?: $res->redirectUrl;
echo "  ✔ Message sent successfully! Redirect: {$redirectUrl}\n";
parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $queryParams);
$threadId = (int)($queryParams['thread_id'] ?? 0);
if ($threadId <= 0) {
    // If query string didn't have thread_id, fetch latest thread from DB
    $latest = $pdo->query("SELECT id FROM lms_threads ORDER BY id DESC LIMIT 1")->fetchColumn();
    $threadId = (int)$latest;
}
if ($threadId <= 0) {
    throw new Exception("Thread ID was not returned properly!");
}
echo "  ✔ Created Thread ID: {$threadId}\n";

// 4. Test Student Thread Retrieval & Unread Tracking
echo "\n[Test 4] Testing Student Thread View & Unread Count...\n";
$_SESSION['user_id'] = 252;
$_SESSION['user_name'] = 'Enuj Catli';
$_SESSION['user_role'] = 'student';

$studentThreads = $msgService->getUserThreads(252);
echo "  ✔ Enuj Catli has " . count($studentThreads) . " conversation threads.\n";
$targetThread = null;
foreach ($studentThreads as $st) {
    if ((int)$st['id'] === $threadId) {
        $targetThread = $st;
        break;
    }
}
if (!$targetThread) {
    throw new Exception("Student cannot see newly created thread {$threadId}!");
}
echo "  ✔ Found target thread. Unread count before viewing: " . $targetThread['unread_count'] . "\n";
if ($targetThread['unread_count'] != 1) {
    throw new Exception("Expected unread_count to be 1, got {$targetThread['unread_count']}");
}

// Read thread messages
$messages = $msgService->getThreadMessages($threadId, 252);
echo "  ✔ Thread read! Messages count: " . count($messages) . "\n";
$studentThreadsAfter = $msgService->getUserThreads(252);
$unreadAfter = 0;
foreach ($studentThreadsAfter as $st) {
    if ((int)$st['id'] === $threadId) {
        $unreadAfter = $st['unread_count'];
    }
}
echo "  ✔ Unread count after reading: {$unreadAfter}\n";
if ($unreadAfter != 0) {
    throw new Exception("Expected unread count to be 0 after reading!");
}

// 5. Test Student Reply
echo "\n[Test 5] Testing Student Reply (Student -> Faculty)...\n";
$studentCtrl = new \App\Controllers\Lms\StudentController();
$studentReq = new \App\Core\Request();
$studentRes = new \App\Core\Response();

$_POST = [
    'csrf_token' => 'test_token_123',
    'thread_id' => $threadId,
    'body' => 'I have completed the practice problems! Thank you professor.'
];
$property->setValue($studentReq, $_POST);
$studentCtrl->sendMessage($studentReq, $studentRes);
echo "  ✔ Student reply posted! Redirect: {$studentRes->redirectUrl}\n";

// Verify messages in thread now = 2
$allMsgs = $msgService->getThreadMessages($threadId, 8);
echo "  ✔ Faculty Alan Turing reads thread. Total messages: " . count($allMsgs) . "\n";
if (count($allMsgs) !== 2) {
    throw new Exception("Expected 2 messages in thread, found " . count($allMsgs));
}

// 6. Test Security / Authorization Check (Non-participant user ID 10 Dr. Grace Hopper)
echo "\n[Test 6] Testing Security Authorization (IDOR Defense)...\n";
$isPart = $msgService->isParticipant($threadId, 10);
if ($isPart) {
    throw new Exception("User 10 should NOT be a participant in thread {$threadId}!");
}
$unauthMsgs = $msgService->getThreadMessages($threadId, 10);
if (!empty($unauthMsgs)) {
    throw new Exception("Unauthorized user was able to fetch messages!");
}
echo "  ✔ Non-participant user successfully blocked from viewing private thread messages.\n";

// 7. Render Views to verify no PHP warnings / errors
echo "\n[Test 7] Testing View Rendering for Faculty & Student...\n";
chdir(dirname(__DIR__, 2) . '/');

// Test Faculty View Render
$_SESSION['user_id'] = 8;
$_SESSION['user_name'] = 'Alan Turing';
$_SESSION['user_role'] = 'faculty';
$_GET['thread_id'] = $threadId;

ob_start();
$threads = $msgService->getUserThreads(8);
$contacts = $msgService->getFacultyContacts(8);
$activeThreadId = $threadId;
$activeThread = $msgService->getThread($threadId, 8);
$activeMessages = $msgService->getThreadMessages($threadId, 8);
$pageTitle = 'Messages & Forums - TTU LMS';
include dirname(__DIR__, 2) . '/app/Views/lms/faculty/messages.php';
$facultyHtml = ob_get_clean();

echo "  ✔ Faculty messages view rendered successfully (" . strlen($facultyHtml) . " bytes).\n";
if (strpos($facultyHtml, 'Midterm Review Consultation') === false) {
    throw new Exception("Subject not found in rendered faculty HTML!");
}

// Test Student View Render
$_SESSION['user_id'] = 252;
$_SESSION['user_name'] = 'Enuj Catli';
$_SESSION['user_role'] = 'student';
$_GET['thread_id'] = $threadId;

ob_start();
$threads = $msgService->getUserThreads(252);
$contacts = $msgService->getStudentContacts(252);
$activeThreadId = $threadId;
$activeThread = $msgService->getThread($threadId, 252);
$activeMessages = $msgService->getThreadMessages($threadId, 252);
$pageTitle = 'Messages & Forums - TTU LMS';
include dirname(__DIR__, 2) . '/app/Views/lms/student/messages.php';
$studentHtml = ob_get_clean();

echo "  ✔ Student messages view rendered successfully (" . strlen($studentHtml) . " bytes).\n";
if (strpos($studentHtml, 'Midterm Review Consultation') === false) {
    throw new Exception("Subject not found in rendered student HTML!");
}

echo "\n===============================================\n";
echo "      ALL 7 MESSAGING TESTS PASSED 100%!      \n";
echo "===============================================\n";
