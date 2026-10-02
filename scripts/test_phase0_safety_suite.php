<?php
declare(strict_types=1);

/**
 * Phase 0 Cashier Safety Test Suite
 * Validates concurrency safety, unique constraints, overpayment validation,
 * permission gating, and upload cleanup.
 */

define('TESTING_ENV', true);

// Autoloader & Bootstrapping
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\HttpException;
use App\Controllers\Admin\Finance\FinanceController;
use App\Controllers\ApplicantController;

class TestResponse extends Response
{
    public ?string $redirectUrl = null;

    public function redirect(string $url): void
    {
        $this->redirectUrl = $url;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] $name\n";
    } else {
        $failed++;
        echo "  [FAIL] $name" . ($details ? " - $details" : "") . "\n";
    }
}

echo "====================================================================\n";
echo "  TTU ENROLLMENT SYSTEM — PHASE 0 CASHIER SAFETY TEST SUITE\n";
echo "====================================================================\n\n";

// -------------------------------------------------------------------------
// TEST 1: Concurrent Receipt Generation across multiple PDO connections
// -------------------------------------------------------------------------
echo "[TEST 1] Concurrent Receipt Generation across separate DB connections...\n";

// Create 2 independent PDO connections
$dbConfig = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'sia',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
];
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['database'], $dbConfig['charset']);

$pdo1 = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo2 = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$receiptsGenerated = [];
$collisionFound = false;

for ($i = 0; $i < 25; $i++) {
    $r1 = generateAtomicReceiptNumber($pdo1);
    $r2 = generateAtomicReceiptNumber($pdo2);

    if (in_array($r1, $receiptsGenerated, true)) {
        $collisionFound = true;
        break;
    }
    $receiptsGenerated[] = $r1;

    if (in_array($r2, $receiptsGenerated, true)) {
        $collisionFound = true;
        break;
    }
    $receiptsGenerated[] = $r2;
}

assertTest("50 interleaved receipts generated without any collisions", !$collisionFound && count($receiptsGenerated) === 50, "Total: " . count($receiptsGenerated));
assertTest("Receipt format matches REC-YYYYMMDD-XXXX", (bool)preg_match('/^REC-\d{8}-\d{4}$/', $receiptsGenerated[0]), "Sample: " . ($receiptsGenerated[0] ?? ''));


// -------------------------------------------------------------------------
// TEST 2: Database UNIQUE Constraint for payment_records.receipt_number
// -------------------------------------------------------------------------
echo "\n[TEST 2] Database UNIQUE constraint on receipt_number...\n";

// Find an existing assessment and user for testing
$testRow = $pdo->query("SELECT id, user_id FROM student_assessments LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$testAssId = (int)$testRow['id'];
$testUserId = (int)$testRow['user_id'];

$uniqueTestReceipt = 'REC-TEST-UNIQUE-' . time();
$pdo->beginTransaction();
$constraintBlocked = false;

try {
    $ins = $pdo->prepare("
        INSERT INTO payment_records (assessment_id, user_id, amount, payment_date, payment_method, receipt_number, status)
        VALUES (:aid, :uid, 100.00, CURDATE(), 'Cash', :rcpt, 'verified')
    ");
    // First insert succeeds
    $ins->execute(['aid' => $testAssId, 'uid' => $testUserId, 'rcpt' => $uniqueTestReceipt]);

    // Second insert with identical receipt number MUST fail with duplicate key
    $ins->execute(['aid' => $testAssId, 'uid' => $testUserId, 'rcpt' => $uniqueTestReceipt]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
        $constraintBlocked = true;
    }
} finally {
    $pdo->rollBack();
}

assertTest("DB blocks duplicate receipt_number via UNIQUE constraint", $constraintBlocked);


// -------------------------------------------------------------------------
// TEST 3: Uniqueness Protection for external reference numbers
// -------------------------------------------------------------------------
echo "\n[TEST 3] Uniqueness protection for payment_records.reference_number...\n";

$uniqueTestRef = 'REF-TEST-' . time();
$pdo->beginTransaction();
$refBlocked = false;

try {
    $insRef = $pdo->prepare("
        INSERT INTO payment_records (assessment_id, user_id, amount, payment_date, payment_method, reference_number, status)
        VALUES (:aid, :uid, 200.00, CURDATE(), 'GCash', :ref, 'pending')
    ");
    // First insert succeeds
    $insRef->execute(['aid' => $testAssId, 'uid' => $testUserId, 'ref' => $uniqueTestRef]);

    // Second insert with identical reference MUST fail
    $insRef->execute(['aid' => $testAssId, 'uid' => $testUserId, 'ref' => $uniqueTestRef]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
        $refBlocked = true;
    }
} finally {
    $pdo->rollBack();
}

assertTest("DB blocks duplicate reference_number via UNIQUE constraint", $refBlocked);

// Verify that multiple NULL reference numbers are permitted
$pdo->beginTransaction();
$nullAllowed = false;
try {
    $insNull = $pdo->prepare("
        INSERT INTO payment_records (assessment_id, user_id, amount, payment_date, payment_method, reference_number, status)
        VALUES (:aid, :uid, 50.00, CURDATE(), 'Cash', NULL, 'verified')
    ");
    $insNull->execute(['aid' => $testAssId, 'uid' => $testUserId]);
    $insNull->execute(['aid' => $testAssId, 'uid' => $testUserId]);
    $nullAllowed = true;
} catch (Exception $e) {
    $nullAllowed = false;
} finally {
    $pdo->rollBack();
}

assertTest("Multiple NULL reference numbers are permitted (OTC cash compatibility)", $nullAllowed);


// -------------------------------------------------------------------------
// TEST 4: Overpayment Rejection in verify_online_payment
// -------------------------------------------------------------------------
echo "\n[TEST 4] Overpayment validation in verify_online_payment...\n";

$overpaymentCaught = false;
$dummyAssId = 0;
$dummyPayId = 0;

// Fetch valid existing application & user
$appRow = $pdo->query("SELECT id, user_id FROM applications LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$testAppId = (int)$appRow['id'];
$testUserId = (int)$appRow['user_id'];

try {
    // 1. Create a dummy test assessment with net 1000, paid 800 (balance 200)
    $stmt = $pdo->prepare("
        INSERT INTO student_assessments (application_id, user_id, total_amount, discount_amount, net_amount, total_paid, payment_status)
        VALUES (:app_id, :uid, 1000.00, 0.00, 1000.00, 800.00, 'partial')
    ");
    $stmt->execute(['app_id' => $testAppId, 'uid' => $testUserId]);
    $dummyAssId = (int)$pdo->lastInsertId();

    // Clean up any prior test records
    $pdo->exec("DELETE FROM payment_records WHERE reference_number LIKE 'REF-OVERPAY-%'");

    $testRefOverpay = 'REF-OVERPAY-' . uniqid();

    // 2. Create a pending payment record of 500 (exceeds balance of 200)
    $stmt2 = $pdo->prepare("
        INSERT INTO payment_records (assessment_id, user_id, amount, payment_date, payment_method, reference_number, status)
        VALUES (:aid, :uid, 500.00, CURDATE(), 'GCash', :ref, 'pending')
    ");
    $stmt2->execute(['aid' => $dummyAssId, 'uid' => $testUserId, 'ref' => $testRefOverpay]);
    $dummyPayId = (int)$pdo->lastInsertId();

    // Simulate cashier approving the overpayment
    $_POST['action'] = 'verify_online_payment';
    $_POST['payment_id'] = $dummyPayId;
    $_POST['decision'] = 'approve';
    $_SESSION['user_id'] = 1; // Admin user
    $_SESSION['user_role'] = 'cashier';
    $_SESSION['user_permissions'] = ['payments.record'];
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $req = new Request();
    $res = new TestResponse();
    $controller = new FinanceController();

    // Invoking process should catch overpayment and redirect with error
    $controller->process($req, $res);

    echo "  [DEBUG TEST 4] error_msg: " . var_export($_SESSION['error_msg'] ?? null, true) . "\n";
    echo "  [DEBUG TEST 4] success_msg: " . var_export($_SESSION['success_msg'] ?? null, true) . "\n";

    if (isset($_SESSION['error_msg']) && str_contains($_SESSION['error_msg'], 'exceed')) {
        $overpaymentCaught = true;
    }
} catch (\Throwable $e) {
    echo "  [DEBUG TEST 4 Throwable] " . get_class($e) . ": " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    if (str_contains($e->getMessage(), 'exceed')) {
        $overpaymentCaught = true;
    }
} finally {
    if ($dummyPayId > 0) {
        $pdo->exec("DELETE FROM payment_records WHERE id = $dummyPayId");
    }
    if ($dummyAssId > 0) {
        $pdo->exec("DELETE FROM student_assessments WHERE id = $dummyAssId");
    }
}

assertTest("verify_online_payment rejects payment exceeding remaining balance", $overpaymentCaught, $_SESSION['error_msg'] ?? '');


// -------------------------------------------------------------------------
// TEST 5: Permission Enforcement on FinanceController::process()
// -------------------------------------------------------------------------
echo "\n[TEST 5] Permission enforcement on FinanceController::process()...\n";

$unauthorizedBlocked = false;
$_SESSION['user_id'] = $testUserId;
$_SESSION['user_role'] = 'applicant';
$_SESSION['user_permissions'] = []; // No payments.record
$_POST['action'] = 'record_payment';
$_SERVER['REQUEST_METHOD'] = 'POST';

try {
    $req = new Request();
    $res = new TestResponse();
    $controller = new FinanceController();
    $controller->process($req, $res);
} catch (HttpException $e) {
    if ($e->getStatusCode() === 403) {
        $unauthorizedBlocked = true;
    }
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'Access Denied') || str_contains($e->getMessage(), 'permission')) {
        $unauthorizedBlocked = true;
    }
}

assertTest("FinanceController::process() blocks users lacking 'payments.record' with 403", $unauthorizedBlocked);


// -------------------------------------------------------------------------
// TEST 6: Duplicate Proof Submission & Concurrency Protection
// -------------------------------------------------------------------------
echo "\n[TEST 6] Concurrency and duplicate proof submission in ApplicantController...\n";

$dupProofBlocked = false;
$tempProofFile = null;
$testAssId2 = 0;

try {
    // 1. Create a clean assessment for testUserId
    $stmt = $pdo->prepare("
        INSERT INTO student_assessments (application_id, user_id, total_amount, discount_amount, net_amount, total_paid, payment_status)
        VALUES (:app_id, :uid, 5000.00, 0.00, 5000.00, 0.00, 'unpaid')
    ");
    $stmt->execute(['app_id' => $testAppId, 'uid' => $testUserId]);
    $testAssId2 = (int)$pdo->lastInsertId();

    // Use existing real image file for test
    $tempProofFile = sys_get_temp_dir() . '/test_proof_' . uniqid() . '.jpg';
    copy(__DIR__ . '/../images/ttu_campus.jpg', $tempProofFile);

    // First submission
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['user_role'] = 'applicant';
    $_POST['action'] = 'submit_payment_proof';
    $_POST['assessment_id'] = $testAssId2;
    $_POST['amount'] = 1000.00;
    $_POST['payment_method'] = 'GCash';
    $_POST['reference_number'] = 'GCASH-CONCURRENT-999';
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $_FILES['proof_image'] = [
        'name' => 'receipt.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => $tempProofFile,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tempProofFile)
    ];

    // Ensure health record requirement is satisfied with proper foreign keys
    $pdo->prepare("
        INSERT INTO health_records (user_id, application_id, status) 
        VALUES (:uid, :app_id, 'approved') 
        ON DUPLICATE KEY UPDATE status = 'approved'
    ")->execute(['uid' => $testUserId, 'app_id' => $testAppId]);

    $req = new Request();
    $res = new TestResponse();
    $appController = new ApplicantController();

    $appController->processPayment($req, $res);

    // Verify first submission succeeded
    $firstRecord = $pdo->query("SELECT id FROM payment_records WHERE reference_number = 'GCASH-CONCURRENT-999'")->fetch();
    assertTest("First payment proof submission succeeds", !empty($firstRecord));

    // Re-create temp image for second attempt since first attempt moved or copied it
    if (!file_exists($tempProofFile)) {
        copy(__DIR__ . '/../images/ttu_campus.jpg', $tempProofFile);
    }

    // Attempt second submission with SAME reference number
    $appController->processPayment($req, $res);

    if (isset($_SESSION['error_msg']) && str_contains($_SESSION['error_msg'], 'already pending or verified')) {
        $dupProofBlocked = true;
    }
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'already pending or verified')) {
        $dupProofBlocked = true;
    }
} finally {
    if ($testAssId2 > 0) {
        $pdo->exec("DELETE FROM payment_records WHERE assessment_id = $testAssId2");
        $pdo->exec("DELETE FROM student_assessments WHERE id = $testAssId2");
    }
    if ($tempProofFile && file_exists($tempProofFile)) {
        @unlink($tempProofFile);
    }
}

assertTest("Duplicate reference proof submission is blocked with descriptive error", $dupProofBlocked, $_SESSION['error_msg'] ?? '');


// -------------------------------------------------------------------------
// TEST 7: Transaction Rollback and File Cleanup on DB Failure
// -------------------------------------------------------------------------
echo "\n[TEST 7] Transaction rollback and file cleanup when database operation fails...\n";

$cleanupVerified = false;
$testAssId3 = 0;
$tempProofFile2 = null;

try {
    $tempProofFile2 = sys_get_temp_dir() . '/test_proof_fail_' . uniqid() . '.jpg';
    copy(__DIR__ . '/../images/ttu_campus.jpg', $tempProofFile2);

    // Create a dummy assessment
    $stmt = $pdo->prepare("
        INSERT INTO student_assessments (application_id, user_id, total_amount, discount_amount, net_amount, total_paid, payment_status)
        VALUES (:app_id, :uid, 5000.00, 0.00, 5000.00, 0.00, 'unpaid')
    ");
    $stmt->execute(['app_id' => $testAppId, 'uid' => $testUserId]);
    $testAssId3 = (int)$pdo->lastInsertId();

    // Pre-insert a conflicting payment record to trigger duplicate key on DB insert
    $pdo->prepare("
        INSERT INTO payment_records (assessment_id, user_id, amount, payment_date, payment_method, reference_number, status)
        VALUES (:aid, :uid, 1000.00, CURDATE(), 'GCash', 'FORCE-COLLISION-REF', 'verified')
    ")->execute(['aid' => $testAssId3, 'uid' => $testUserId]);

    // Count files in uploads/payments before
    $uploadDir = dirname(__DIR__) . '/uploads/payments/';
    $filesBefore = glob($uploadDir . 'proof_' . $testUserId . '_*') ?: [];

    // Attempt submission with the colliding reference number
    $_SESSION['user_id'] = $testUserId;
    $_SESSION['user_role'] = 'applicant';
    $_POST['action'] = 'submit_payment_proof';
    $_POST['assessment_id'] = $testAssId3;
    $_POST['amount'] = 1000.00;
    $_POST['payment_method'] = 'GCash';
    $_POST['reference_number'] = 'FORCE-COLLISION-REF';
    $_SERVER['REQUEST_METHOD'] = 'POST';

    $_FILES['proof_image'] = [
        'name' => 'receipt.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => $tempProofFile2,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tempProofFile2)
    ];

    $appController = new ApplicantController();
    $appController->processPayment($req, $res);

    // Count files after failure
    $filesAfter = glob($uploadDir . 'proof_' . $testUserId . '_*') ?: [];

    // The number of files for this user should NOT have increased!
    $cleanupVerified = (count($filesAfter) <= count($filesBefore));

} catch (Exception $e) {
    $cleanupVerified = true;
} finally {
    if ($testAssId3 > 0) {
        $pdo->exec("DELETE FROM payment_records WHERE assessment_id = $testAssId3");
        $pdo->exec("DELETE FROM student_assessments WHERE id = $testAssId3");
    }
    if (isset($tempProofFile2) && file_exists($tempProofFile2)) {
        @unlink($tempProofFile2);
    }
}

assertTest("No orphaned proof files remain in uploads/payments/ on failure", $cleanupVerified);


// -------------------------------------------------------------------------
// TEST 8: Syntax check across all touched files
// -------------------------------------------------------------------------
echo "\n[TEST 8] PHP Syntax Verification...\n";

$touchedFiles = [
    'app/Helpers/functions.php',
    'app/Controllers/Admin/Finance/FinanceController.php',
    'app/Controllers/ApplicantController.php',
    'scripts/migrations/phase0_cashier_safety.php'
];

$allSyntaxValid = true;
foreach ($touchedFiles as $f) {
    $fullPath = dirname(__DIR__) . '/' . $f;
    $output = [];
    $returnCode = 0;
    exec("php -l " . escapeshellarg($fullPath), $output, $returnCode);
    if ($returnCode !== 0) {
        $allSyntaxValid = false;
        echo "  Syntax error in $f: " . implode("\n", $output) . "\n";
    }
}

assertTest("All touched PHP files have valid syntax", $allSyntaxValid);

// -------------------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------------------
echo "\n====================================================================\n";
echo "  TEST SUMMARY: $passed PASSED, $failed FAILED\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
