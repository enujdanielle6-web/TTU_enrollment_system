<?php
/**
 * Phase 5 Verification Script: Service Layer Extraction & Architecture
 */

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Core/Database.php';

// Autoloader test
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

// Load .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim(trim($value), '"\'');
        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

$pdo = App\Core\Database::getConnection();

echo "====================================================\n";
echo "TTU ENROLLMENT SYSTEM - PHASE 5 VERIFICATION SUITE\n";
echo "====================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $title, bool $condition) {
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] $title\n";
        $passCount++;
    } else {
        echo "[FAIL] $title\n";
        $failCount++;
    }
}

// ----------------------------------------------------
// 1. Service Classes Existence & Autoloading
// ----------------------------------------------------
echo "--- 1. Service Autoloading & Existence ---\n";
assertTest("Class App\\Services\\StudentNumberService loaded", class_exists('App\\Services\\StudentNumberService'));
assertTest("Class App\\Services\\AssessmentService loaded", class_exists('App\\Services\\AssessmentService'));
assertTest("Class App\\Services\\EnrollmentService loaded", class_exists('App\\Services\\EnrollmentService'));

// ----------------------------------------------------
// 2. StudentNumberService & Sequence Generation
// ----------------------------------------------------
echo "\n--- 2. StudentNumberService Verification ---\n";

$tableCheck = $pdo->query("SHOW TABLES LIKE 'student_number_sequences'")->fetch();
assertTest("student_number_sequences table exists in MariaDB", !empty($tableCheck));

$curYear = (int)date('Y');
$currentSeq = App\Services\StudentNumberService::getCurrentSequence($curYear, $pdo);
echo "   Current allocated sequence for $curYear: $currentSeq\n";

$generatedNum = App\Services\StudentNumberService::generate($curYear, $pdo);
$newSeq = App\Services\StudentNumberService::getCurrentSequence($curYear, $pdo);
echo "   Generated Student Number: $generatedNum (New Seq: $newSeq)\n";

assertTest("Student number matches format YYYY-XXXXXX", (bool)preg_match('/^\d{4}-\d{6}$/', $generatedNum));
assertTest("Atomic sequence incremented exactly by 1", $newSeq === ($currentSeq + 1));

// Test legacy helper delegation
$helperNum = generateStudentNumber($pdo);
$afterHelperSeq = App\Services\StudentNumberService::getCurrentSequence($curYear, $pdo);
assertTest("generateStudentNumber() delegates to StudentNumberService", $afterHelperSeq === ($newSeq + 1));
echo "   generateStudentNumber() returned: $helperNum (Sequence: $afterHelperSeq)\n";

// ----------------------------------------------------
// 3. AssessmentService Verification
// ----------------------------------------------------
echo "\n--- 3. AssessmentService Verification ---\n";

// Find an existing assessment
$sampleAss = $pdo->query("SELECT id, user_id, application_id FROM student_assessments LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if ($sampleAss) {
    $aid = (int)$sampleAss['id'];
    $uid = (int)$sampleAss['user_id'];
    echo "   Testing with existing Assessment ID #$aid (User #$uid)...\n";

    $breakdownById = App\Services\AssessmentService::getAssessmentBreakdown($pdo, $aid);
    assertTest("getAssessmentBreakdown by ID returns non-empty array", !empty($breakdownById));
    assertTest("Breakdown contains 'assessment' key", isset($breakdownById['assessment']) && is_array($breakdownById['assessment']));
    assertTest("Breakdown contains 'payments' key", isset($breakdownById['payments']) && is_array($breakdownById['payments']));
    assertTest("Breakdown contains 'assessment_items' snapshot items", isset($breakdownById['assessment_items']) && is_array($breakdownById['assessment_items']));
    assertTest("Breakdown contains 'enrolled_subjects'", isset($breakdownById['enrolled_subjects']) && is_array($breakdownById['enrolled_subjects']));
    assertTest("Breakdown contains 'total_units' numeric value", isset($breakdownById['total_units']) && is_numeric($breakdownById['total_units']));

    $breakdownByUser = App\Services\AssessmentService::getAssessmentBreakdown($pdo, null, $uid);
    assertTest("getAssessmentBreakdown by User ID returns valid breakdown", !empty($breakdownByUser) && $breakdownByUser['assessment']['id'] == $aid);
} else {
    echo "   [INFO] No existing assessment found to test breakdown.\n";
}

// ----------------------------------------------------
// 4. EnrollmentService State Machine & Guard Verification
// ----------------------------------------------------
echo "\n--- 4. EnrollmentService Validation Guards ---\n";

// Test 4.1: Non-existent application
$fakeResult = App\Services\EnrollmentService::finalizeEnrollment(999999, 1, $pdo);
assertTest("Rejects non-existent application", $fakeResult['success'] === false && strpos($fakeResult['error'], 'not found') !== false);

// Test 4.2: Payment not verified check
// Find an application with status != 'payment_verified' and payment_status = 'unpaid'
$unpaidApp = $pdo->query("
    SELECT a.id, a.status 
    FROM applications a 
    JOIN student_assessments sa ON sa.application_id = a.id 
    WHERE a.status NOT IN ('payment_verified', 'enrolled') 
      AND sa.payment_status = 'unpaid' 
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if ($unpaidApp) {
    $unpaidResult = App\Services\EnrollmentService::finalizeEnrollment((int)$unpaidApp['id'], 1, $pdo);
    assertTest("Rejects finalization when payment is not verified", $unpaidResult['success'] === false && strpos($unpaidResult['error'], 'Tuition payment has not been verified') !== false);
} else {
    echo "   [INFO] No unpaid applicant found for payment guard test.\n";
}

// Test 4.3: Already enrolled check
$enrolledApp = $pdo->query("SELECT id FROM applications WHERE status = 'enrolled' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($enrolledApp) {
    $enrolledResult = App\Services\EnrollmentService::finalizeEnrollment((int)$enrolledApp['id'], 1, $pdo);
    assertTest("Rejects finalization when already enrolled", $enrolledResult['success'] === false && strpos($enrolledResult['error'], 'already officially enrolled') !== false);
} else {
    echo "   [INFO] No enrolled applicant found for already-enrolled guard test.\n";
}

// Pre-cleanup in case of previous run
$pdo->exec("DELETE FROM users WHERE email LIKE 'testphase5%'");

$testEmail = 'testphase5_' . time() . '_' . rand(100, 999) . '@example.com';
$testRef = 'APP-TEST-' . time();

$pdo->beginTransaction();
try {
    $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, password, role, is_active, created_at, updated_at) 
        VALUES ('TestPhase5', 'Student', :email, 'dummy_hash', 'student', 1, NOW(), NOW())
    ")->execute(['email' => $testEmail]);
    $testUserId = (int)$pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO applications (user_id, reference_number, academic_level, grade_level, strand, semester, status, created_at, updated_at) 
        VALUES (:uid, :ref, 'College', '1st Year', 'BSIT', 'First', 'payment_verified', NOW(), NOW())
    ")->execute(['uid' => $testUserId, 'ref' => $testRef]);
    $testAppId = (int)$pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO student_assessments (user_id, application_id, tuition_fee, total_amount, net_amount, total_paid, payment_status, created_at, updated_at) 
        VALUES (:uid, :aid, 15000, 20000, 20000, 20000, 'paid', NOW(), NOW())
    ")->execute(['uid' => $testUserId, 'aid' => $testAppId]);

    // Test EnrollmentService::finalizeEnrollment
    $finalizeResult = App\Services\EnrollmentService::finalizeEnrollment($testAppId, 1, $pdo);
    assertTest("EnrollmentService successfully finalizes verified applicant", $finalizeResult['success'] === true);
    assertTest("Institutional TTU email generated", !empty($finalizeResult['ttu_email']) && strpos($finalizeResult['ttu_email'], '@ttu.edu.ph') !== false);
    assertTest("Student number generated", !empty($finalizeResult['student_number']) && preg_match('/^\d{4}-\d{6}$/', $finalizeResult['student_number']));

    // Check database state for the user
    $chkUser = $pdo->query("SELECT student_number, ttu_email, force_password_reset FROM users WHERE id = $testUserId")->fetch(PDO::FETCH_ASSOC);
    assertTest("User record assigned student_number", $chkUser['student_number'] === $finalizeResult['student_number']);
    assertTest("User record assigned ttu_email", $chkUser['ttu_email'] === $finalizeResult['ttu_email']);
    assertTest("User record has force_password_reset = 1", (int)$chkUser['force_password_reset'] === 1);

    // Check application status
    $chkApp = $pdo->query("SELECT status FROM applications WHERE id = $testAppId")->fetch(PDO::FETCH_ASSOC);
    assertTest("Application status transitioned to 'enrolled'", $chkApp['status'] === 'enrolled');

    // Rollback test transaction if active, and explicitly clean up test records
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $pdo->exec("DELETE FROM applications WHERE id = $testAppId");
    $pdo->exec("DELETE FROM users WHERE id = $testUserId");
    $pdo->exec("DELETE FROM student_assessments WHERE application_id = $testAppId");
    echo "   [CLEANUP] Simulated test enrollment data cleaned up successfully.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "   [ERROR] Simulation failed: " . $e->getMessage() . "\n";
    $failCount++;
}

echo "\n====================================================\n";
echo "VERIFICATION SUMMARY: $passCount PASSED, $failCount FAILED\n";
echo "====================================================\n";

if ($failCount === 0) {
    echo ">>> PHASE 5 IMPLEMENTATION IS 100% COMPLETE & VERIFIED <<<\n";
    exit(0);
} else {
    echo ">>> SOME CHECKS FAILED <<<\n";
    exit(1);
}
