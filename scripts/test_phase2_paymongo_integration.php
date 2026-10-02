<?php
declare(strict_types=1);

/**
 * Phase 2: PayMongo Checkout Integration Automated Test Suite
 *
 * Verifies:
 * 1. Valid checkout creation
 * 2. Correct association between TTU payment and PayMongo session
 * 3. Invalid amount rejection (zero, negative, below minimum ₱100)
 * 4. Already-paid assessment protection
 * 5. Amount greater than balance rejection
 * 6. Missing configuration handling
 * 7. PayMongo API failure handling and transaction rollback
 * 8. Duplicate initiation protection
 * 9. Return callback does NOT auto-verify or trust frontend return
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

use App\Config\PayMongoConfig;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Controllers\ApplicantController;
use App\Repositories\PaymentRepository;
use App\Services\PaymentService;
use App\Services\PayMongoService;
use App\Services\PayMongoApiException;

$pdo = Database::getConnection();
$repo = new PaymentRepository($pdo);
$service = new PaymentService($repo, $pdo);

$passCount = 0;
$failCount = 0;

function assertCondition(bool $condition, string $testName, string $details = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$testName}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$testName} - {$details}\n";
    }
}

echo "====================================================================\n";
echo "  TTU ENROLLMENT SYSTEM — PHASE 2 PAYMONGO INTEGRATION TEST SUITE    \n";
echo "====================================================================\n\n";

// Helper to seed a clean test applicant, application, and assessment
function createTestEnsemble(PDO $pdo, float $netAmount = 10000.00): array {
    $uniq = bin2hex(random_bytes(3));
    
    // User
    $stmt = $pdo->prepare('
        INSERT INTO users (first_name, last_name, email, password, role, is_active, email_verified, created_at, updated_at)
        VALUES (:fn, :ln, :e, :p, "applicant", 1, 1, NOW(), NOW())
    ');
    $stmt->execute([
        'fn' => "Student_{$uniq}",
        'ln' => "Tester_{$uniq}",
        'e'  => "student_{$uniq}@example.com",
        'p'  => password_hash('Pass123!', PASSWORD_DEFAULT),
    ]);
    $userId = (int) $pdo->lastInsertId();

    // Application
    $stmt = $pdo->prepare('
        INSERT INTO applications (user_id, reference_number, academic_level, grade_level, status)
        VALUES (:uid, :ref, "College", "1st Year", "approved")
    ');
    $stmt->execute([
        'uid' => $userId,
        'ref' => "APP-PM-{$uniq}",
    ]);
    $appId = (int) $pdo->lastInsertId();

    // Assessment
    $stmt = $pdo->prepare('
        INSERT INTO student_assessments (user_id, application_id, tuition_fee, total_amount, discount_amount, net_amount, total_paid, payment_status)
        VALUES (:uid, :aid, :tfee, :tot, 0.00, :net, 0.00, "unpaid")
    ');
    $stmt->execute([
        'uid'  => $userId,
        'aid'  => $appId,
        'tfee' => $netAmount,
        'tot'  => $netAmount,
        'net'  => $netAmount,
    ]);
    $assId = (int) $pdo->lastInsertId();

    return [
        'user_id'        => $userId,
        'application_id' => $appId,
        'assessment_id'  => $assId,
        'net_amount'     => $netAmount,
        'uniq'           => $uniq,
    ];
}

// -----------------------------------------------------------------------------
// TEST 1 & 2: Valid Checkout Creation & Session Association
// -----------------------------------------------------------------------------
echo "[TEST 1 & 2] Valid Checkout Creation and TTU Session Association...\n";
$ensemble1 = createTestEnsemble($pdo, 12000.00);

$mockSessionId = 'cs_test_' . bin2hex(random_bytes(8));
$mockIntentId = 'pi_test_' . bin2hex(random_bytes(8));
$mockCheckoutUrl = "https://checkout.paymongo.com/{$mockSessionId}";

$mockGateway = new PayMongoService(
    'sk_test_mock_secret_key',
    'https://api.paymongo.com/v1',
    function (string $method, string $url, array $headers, ?string $body) use ($mockSessionId, $mockCheckoutUrl, $mockIntentId) {
        return [
            'status' => 200,
            'body'   => json_encode([
                'data' => [
                    'id'         => $mockSessionId,
                    'type'       => 'checkout_session',
                    'attributes' => [
                        'checkout_url'   => $mockCheckoutUrl,
                        'status'         => 'active',
                        'payment_intent' => [
                            'id' => $mockIntentId,
                        ],
                        'metadata'       => [
                            'institution' => 'TTU',
                        ],
                    ],
                ],
            ]),
        ];
    }
);

try {
    $result = $service->initiatePayMongoPayment([
        'assessment_id' => $ensemble1['assessment_id'],
        'user_id'       => $ensemble1['user_id'],
        'amount'        => 3500.00,
    ], $mockGateway);

    assertCondition($result['success'] === true, 'PayMongo checkout creation succeeded');
    assertCondition($result['checkout_session_id'] === $mockSessionId, 'Returned correct PayMongo Checkout Session ID');
    assertCondition($result['checkout_url'] === $mockCheckoutUrl, 'Returned valid PayMongo Checkout URL');
    assertCondition($result['payment_intent_id'] === $mockIntentId, 'Captured associated Payment Intent ID');
    assertCondition($result['status'] === 'pending', 'Payment status returned as pending');

    // TEST 2: Verify database record association
    $record = $repo->findById($result['payment_id']);
    assertCondition($record !== null, 'Payment record persisted in central ledger (payment_records)');
    assertCondition($record['status'] === 'pending', 'Database status remains pending (not prematurely verified)');
    assertCondition($record['receipt_number'] === null, 'No official receipt generated prior to webhook verification');
    assertCondition($record['checkout_session_id'] === $mockSessionId, 'checkout_session_id correctly stored in database');
    assertCondition($record['payment_intent_id'] === $mockIntentId, 'payment_intent_id correctly stored in database');
    assertCondition($record['checkout_url'] === $mockCheckoutUrl, 'checkout_url correctly stored in database');
    assertCondition($record['gateway'] === 'paymongo', 'Gateway column set to paymongo');
    assertCondition((float)$record['amount'] === 3500.00, 'Ledger record amount matches requested amount');

    // Verify lookup by checkout session ID
    $lookedUp = $repo->findByCheckoutSessionId($mockSessionId);
    assertCondition($lookedUp !== null && (int)$lookedUp['id'] === $result['payment_id'], 'Payment record retrievable via findByCheckoutSessionId');
} catch (Throwable $e) {
    assertCondition(false, 'Valid checkout creation threw exception', $e->getMessage());
}

// -----------------------------------------------------------------------------
// TEST 3: Invalid Amount Protections
// -----------------------------------------------------------------------------
echo "\n[TEST 3] Invalid Amount Protections...\n";
$ensemble2 = createTestEnsemble($pdo, 8000.00);

// Case 3A: Zero amount
try {
    $service->initiatePayMongoPayment([
        'assessment_id' => $ensemble2['assessment_id'],
        'user_id'       => $ensemble2['user_id'],
        'amount'        => 0.00,
    ], $mockGateway);
    assertCondition(false, 'Blocked zero amount', 'Failed to throw exception');
} catch (Throwable $e) {
    assertCondition(str_contains($e->getMessage(), 'greater than ₱0.00'), 'Blocks zero payment amount');
}

// Case 3B: Negative amount
try {
    $service->initiatePayMongoPayment([
        'assessment_id' => $ensemble2['assessment_id'],
        'user_id'       => $ensemble2['user_id'],
        'amount'        => -500.00,
    ], $mockGateway);
    assertCondition(false, 'Blocked negative amount', 'Failed to throw exception');
} catch (Throwable $e) {
    assertCondition(str_contains($e->getMessage(), 'greater than ₱0.00'), 'Blocks negative payment amount');
}

// Case 3C: Below PayMongo ₱100.00 minimum
try {
    $service->initiatePayMongoPayment([
        'assessment_id' => $ensemble2['assessment_id'],
        'user_id'       => $ensemble2['user_id'],
        'amount'        => 50.00,
    ], $mockGateway);
    assertCondition(false, 'Blocked sub-₱100 amount', 'Failed to throw exception');
} catch (Throwable $e) {
    assertCondition(str_contains($e->getMessage(), '₱100.00'), 'Enforces PayMongo ₱100.00 minimum checkout rule');
}

// -----------------------------------------------------------------------------
// TEST 4: Already-Paid Assessment Protection
// -----------------------------------------------------------------------------
echo "\n[TEST 4] Already-Paid Assessment Protection...\n";
$ensemble3 = createTestEnsemble($pdo, 5000.00);
$pdo->prepare('UPDATE student_assessments SET total_paid = 5000.00, payment_status = "paid" WHERE id = ?')->execute([$ensemble3['assessment_id']]);

try {
    $service->initiatePayMongoPayment([
        'assessment_id' => $ensemble3['assessment_id'],
        'user_id'       => $ensemble3['user_id'],
        'amount'        => 1000.00,
    ], $mockGateway);
    assertCondition(false, 'Blocked already-paid assessment', 'Failed to throw exception');
} catch (Throwable $e) {
    assertCondition(str_contains($e->getMessage(), 'already been fully paid'), 'Rejects payment initiation on settled assessment');
}

// -----------------------------------------------------------------------------
// TEST 5: Amount Greater Than Balance Protection
// -----------------------------------------------------------------------------
echo "\n[TEST 5] Amount Greater Than Balance Protection...\n";
$ensemble4 = createTestEnsemble($pdo, 4000.00);

try {
    $service->initiatePayMongoPayment([
        'assessment_id' => $ensemble4['assessment_id'],
        'user_id'       => $ensemble4['user_id'],
        'amount'        => 5500.00,
    ], $mockGateway);
    assertCondition(false, 'Blocked overpayment', 'Failed to throw exception');
} catch (Throwable $e) {
    assertCondition(str_contains($e->getMessage(), 'cannot exceed your allowable remaining balance'), 'Rejects initiation exceeding allowable assessment balance');
}

// -----------------------------------------------------------------------------
// TEST 6: Missing Configuration Protection
// -----------------------------------------------------------------------------
echo "\n[TEST 6] Missing Configuration Protection...\n";
$currentEnvSecret = getenv('PAYMONGO_SECRET_KEY');

try {
    // Temporarily clear environment variable
    putenv('PAYMONGO_SECRET_KEY=');
    $_ENV['PAYMONGO_SECRET_KEY'] = '';
    $_SERVER['PAYMONGO_SECRET_KEY'] = '';

    PayMongoConfig::getSecretKey();
    assertCondition(false, 'Missing configuration check', 'Failed to throw RuntimeException');
} catch (RuntimeException $e) {
    assertCondition(str_contains($e->getMessage(), 'not configured'), 'Throws RuntimeException when PAYMONGO_SECRET_KEY is missing');
} finally {
    // Restore environment variable
    if ($currentEnvSecret !== false) {
        putenv("PAYMONGO_SECRET_KEY={$currentEnvSecret}");
        $_ENV['PAYMONGO_SECRET_KEY'] = $currentEnvSecret;
        $_SERVER['PAYMONGO_SECRET_KEY'] = $currentEnvSecret;
    }
}

// -----------------------------------------------------------------------------
// TEST 7: PayMongo API Failure & Transaction Rollback
// -----------------------------------------------------------------------------
echo "\n[TEST 7] PayMongo API Failure and Transaction Rollback...\n";
$ensemble5 = createTestEnsemble($pdo, 7000.00);

$initialCount = (int) $pdo->query('SELECT COUNT(*) FROM payment_records WHERE assessment_id = ' . $ensemble5['assessment_id'])->fetchColumn();

$failingGateway = new PayMongoService(
    'sk_test_mock_failing',
    'https://api.paymongo.com/v1',
    function (string $method, string $url, array $headers, ?string $body) {
        return [
            'status' => 401,
            'body'   => json_encode([
                'errors' => [
                    [
                        'code'   => 'unauthorized',
                        'detail' => 'The provided secret API key is invalid or deactivated.',
                    ],
                ],
            ]),
        ];
    }
);

try {
    $service->initiatePayMongoPayment([
        'assessment_id' => $ensemble5['assessment_id'],
        'user_id'       => $ensemble5['user_id'],
        'amount'        => 2000.00,
    ], $failingGateway);
    assertCondition(false, 'Handled API failure', 'Failed to throw PayMongoApiException');
} catch (PayMongoApiException $e) {
    assertCondition(str_contains($e->getMessage(), 'invalid or deactivated'), 'Catches PayMongoApiException with gateway error message');
    assertCondition($e->getHttpCode() === 401, 'Preserves HTTP 401 status code from gateway');

    // Verify rollback: no orphaned payment record created
    $afterCount = (int) $pdo->query('SELECT COUNT(*) FROM payment_records WHERE assessment_id = ' . $ensemble5['assessment_id'])->fetchColumn();
    assertCondition($initialCount === $afterCount, 'Transaction rolled back without leaving orphaned payment records');
}

// -----------------------------------------------------------------------------
// TEST 8: Duplicate Initiation Protection
// -----------------------------------------------------------------------------
echo "\n[TEST 8] Duplicate Initiation Protection...\n";
// Ensemble 1 already has an active pending PayMongo session from Test 1 ($mockSessionId)
try {
    $service->initiatePayMongoPayment([
        'assessment_id' => $ensemble1['assessment_id'],
        'user_id'       => $ensemble1['user_id'],
        'amount'        => 2000.00,
    ], $mockGateway);
    assertCondition(false, 'Blocked duplicate initiation', 'Failed to throw exception');
} catch (Throwable $e) {
    assertCondition(str_contains($e->getMessage(), 'already pending for this assessment'), 'Blocks concurrent/duplicate in-flight PayMongo sessions');
}

// -----------------------------------------------------------------------------
// TEST 9: Return Callback Does NOT Auto-Verify Payment
// -----------------------------------------------------------------------------
echo "\n[TEST 9] Return Callback Security (Does NOT Trust Frontend Return)...\n";
$req = new Request();
$res = new Response();
$controller = new ApplicantController();

// Simulate student returning from checkout with ?session_id=...
$_GET['session_id'] = $mockSessionId;
$_SESSION['user_id'] = $ensemble1['user_id'];

// Initial DB state check
$beforePay = $repo->findByCheckoutSessionId($mockSessionId);
$beforeAss = $pdo->query('SELECT total_paid, payment_status FROM student_assessments WHERE id = ' . $ensemble1['assessment_id'])->fetch(PDO::FETCH_ASSOC);
$beforeApp = $pdo->query('SELECT status FROM applications WHERE id = ' . $ensemble1['application_id'])->fetch(PDO::FETCH_ASSOC);

// Invoke callback
ob_start();
$controller->paymentCallback($req, $res);
ob_end_clean();

// Re-inspect DB state
$afterPay = $repo->findByCheckoutSessionId($mockSessionId);
$afterAss = $pdo->query('SELECT total_paid, payment_status FROM student_assessments WHERE id = ' . $ensemble1['assessment_id'])->fetch(PDO::FETCH_ASSOC);
$afterApp = $pdo->query('SELECT status FROM applications WHERE id = ' . $ensemble1['application_id'])->fetch(PDO::FETCH_ASSOC);

assertCondition($afterPay['status'] === 'pending', 'Payment record status remains pending upon client return');
assertCondition($afterPay['receipt_number'] === null, 'No receipt number issued merely because user returned');
assertCondition((float)$afterAss['total_paid'] === (float)$beforeAss['total_paid'], 'Assessment balance NOT modified by client return');
assertCondition($afterApp['status'] === $beforeApp['status'], 'Application status NOT transitioned by client return (Enrollment finalization protected)');

echo "\n====================================================================\n";
echo "  PHASE 2 TEST SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "====================================================================\n";

exit($failCount > 0 ? 1 : 0);
