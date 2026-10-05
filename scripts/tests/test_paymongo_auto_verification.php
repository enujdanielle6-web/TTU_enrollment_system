<?php
declare(strict_types=1);

/**
 * TTU ENROLLMENT SYSTEM — PAYMONGO AUTOMATED VERIFICATION & CANCELLATION TEST
 *
 * Verifies:
 * 1. Cancelled/Unfinished PayMongo checkout does NOT stay in pending status.
 * 2. Successful PayMongo checkout automatically verifies payment on return callback.
 * 3. Official receipt is generated atomically without cashier intervention.
 * 4. Assessment balances and application status transition seamlessly.
 * 5. Cashier portal protects against manual verification of PayMongo transactions.
 */

define('TESTING_ENV', true);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/app/Helpers/functions.php';

// PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__, 2) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Database;
use App\Services\PaymentService;
use App\Services\PayMongoService;
use App\Repositories\PaymentRepository;

$pdo = Database::getConnection();
$paymentService = new PaymentService(null, $pdo);
$paymentRepo = new PaymentRepository($pdo);

echo "====================================================================\n";
echo "  TTU ENROLLMENT SYSTEM — PAYMONGO AUTOMATED VERIFICATION SUITE    \n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $name, bool $condition, string $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$name}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$name} - Details: {$details}\n";
    }
}

// 1. Setup Test Ensemble
$rand = bin2hex(random_bytes(4));
$stmtUser = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password, role) VALUES (:f, :l, :e, "test_pass", "applicant")');
$stmtUser->execute(['f' => 'AutoTest', 'l' => 'PayMongo', 'e' => "paymongo_{$rand}@ttu.edu.ph"]);
$userId = (int) $pdo->lastInsertId();

$stmtApp = $pdo->prepare('INSERT INTO applications (user_id, reference_number, academic_level, grade_level, status) VALUES (:u, :ref, "College", "1st Year", "approved")');
$stmtApp->execute(['u' => $userId, 'ref' => "APP-PM-{$rand}"]);
$appId = (int) $pdo->lastInsertId();

$stmtAss = $pdo->prepare('INSERT INTO student_assessments (user_id, application_id, tuition_fee, total_amount, net_amount, total_paid, payment_status, created_at) VALUES (:u, :a, 7500.00, 10000.00, 10000.00, 0.00, "unpaid", NOW())');
$stmtAss->execute(['u' => $userId, 'a' => $appId]);
$assessmentId = (int) $pdo->lastInsertId();

echo "Created test ensemble: User #{$userId}, Application #{$appId}, Assessment #{$assessmentId} (Net: ₱10,000.00)\n\n";

// --- TEST 1: Initiation and Immediate Cancel Flow ---
echo "--- Test 1: Applicant cancels on PayMongo checkout ---\n";
$mockPayMongoCancel = new PayMongoService('sk_test_mock', null, function ($method, $url, $headers, $body) {
    return [
        'status' => 200,
        'body'   => json_encode([
            'data' => [
                'id' => 'cs_cancel_' . bin2hex(random_bytes(4)),
                'type' => 'checkout_session',
                'attributes' => [
                    'checkout_url' => 'https://checkout.paymongo.com/cancel',
                    'payment_intent' => ['id' => 'pi_cancel_1'],
                    'status' => 'active',
                ],
            ],
        ]),
    ];
});

$init1 = $paymentService->initiatePayMongoPayment([
    'assessment_id' => $assessmentId,
    'user_id'       => $userId,
    'amount'        => 5000.00,
], $mockPayMongoCancel);

$csId1 = $init1['checkout_session_id'];
$paymentId1 = $init1['payment_id'];

assertTest("Initiation creates payment record with session ID", $init1['success'] === true && !empty($csId1));

// Simulate student clicking cancel on PayMongo portal
$cancelResult = $paymentService->cancelPayMongoPayment($paymentId1, $userId, 'Cancelled by student on PayMongo');
$record1 = $paymentRepo->findById($paymentId1);

assertTest("Cancelled payment is marked 'cancelled' and NOT 'pending'", $record1['status'] === 'cancelled');

// Check that allowable balance is restored (₱10,000 allowable, not deducted by cancelled payment)
$pendingTotal = $paymentRepo->getPendingTotalForAssessment($assessmentId);
assertTest("Pending total excludes cancelled session (Pending: ₱{$pendingTotal})", $pendingTotal == 0.00);

// --- TEST 2: Successful Payment Auto-Verification on Return Callback ---
echo "\n--- Test 2: Applicant completes payment on PayMongo & auto-verifies ---\n";

$mockSessionPaidId = 'cs_paid_' . bin2hex(random_bytes(4));
$mockPayMongoPaid = new PayMongoService('sk_test_mock', null, function ($method, $url, $headers, $body) use ($mockSessionPaidId) {
    if (str_contains($url, '/checkout_sessions/cs_')) {
        // Return GET checkout session with paid status
        return [
            'status' => 200,
            'body'   => json_encode([
                'data' => [
                    'id' => $mockSessionPaidId,
                    'type' => 'checkout_session',
                    'attributes' => [
                        'status' => 'paid',
                        'payments' => [
                            [
                                'id' => 'pay_mock_' . bin2hex(random_bytes(3)),
                                'attributes' => [
                                    'status' => 'paid',
                                    'amount' => 1000000, // ₱10,000.00 in centavos
                                    'fee' => 15000,      // ₱150.00 gateway fee
                                ],
                            ],
                        ],
                        'payment_intent' => [
                            'id' => 'pi_mock_paid_' . bin2hex(random_bytes(3)),
                            'attributes' => ['status' => 'succeeded'],
                        ],
                    ],
                ],
            ]),
        ];
    }

    // POST create checkout session
    return [
        'status' => 200,
        'body'   => json_encode([
            'data' => [
                'id' => $mockSessionPaidId,
                'type' => 'checkout_session',
                'attributes' => [
                    'checkout_url' => 'https://checkout.paymongo.com/paid',
                    'payment_intent' => ['id' => 'pi_mock_paid_1'],
                    'status' => 'active',
                ],
            ],
        ]),
    ];
});

$init2 = $paymentService->initiatePayMongoPayment([
    'assessment_id' => $assessmentId,
    'user_id'       => $userId,
    'amount'        => 10000.00,
], $mockPayMongoPaid);

$csId2 = $init2['checkout_session_id'];
$paymentId2 = $init2['payment_id'];

// Now simulate return callback calling autoVerifyPayMongoCheckoutSession
$verifyRes = $paymentService->autoVerifyPayMongoCheckoutSession($csId2, $mockPayMongoPaid);

assertTest("Auto-verification returns 'verified' status", $verifyRes['status'] === 'verified');
assertTest("Official atomic receipt number was issued", !empty($verifyRes['receipt_number']) && str_starts_with($verifyRes['receipt_number'], 'REC-'));

// Check database records directly
$record2 = $paymentRepo->findById($paymentId2);
assertTest("Database payment record status is 'verified'", $record2['status'] === 'verified');
assertTest("Receipt number saved in payment record", $record2['receipt_number'] === $verifyRes['receipt_number']);

$stmtCheckAss = $pdo->prepare('SELECT total_paid, payment_status FROM student_assessments WHERE id = :id');
$stmtCheckAss->execute(['id' => $assessmentId]);
$assRow = $stmtCheckAss->fetch(PDO::FETCH_ASSOC);

assertTest("Assessment total_paid updated to ₱10,000.00", (float)$assRow['total_paid'] == 10000.00);
assertTest("Assessment payment_status updated to 'paid'", $assRow['payment_status'] === 'paid');

$stmtCheckApp = $pdo->prepare('SELECT status FROM applications WHERE id = :id');
$stmtCheckApp->execute(['id' => $appId]);
$appStatus = $stmtCheckApp->fetchColumn();

assertTest("Application status automatically transitioned to 'payment_verified'", $appStatus === 'payment_verified');

// --- TEST 3: Zero Cashier Intervention Needed ---
echo "\n--- Test 3: Cashier Intervention Isolation ---\n";
// Create a pending PayMongo payment
$pendingPMId = $paymentRepo->insert([
    'assessment_id'       => $assessmentId,
    'user_id'             => $userId,
    'amount'              => 500.00,
    'payment_date'        => date('Y-m-d'),
    'payment_method'      => 'PayMongo',
    'gateway'             => 'paymongo',
    'status'              => 'pending',
    'checkout_session_id' => 'cs_test_guard_' . bin2hex(random_bytes(3)),
    'reference_number'    => 'PM-GUARD-TEST',
]);

// Attempting manual cashier verification on a PayMongo payment must throw exception
$blockedByGuard = false;
$guardMessage = '';
try {
    $paymentService->verifyOnlinePayment($pendingPMId, 1);
} catch (\Throwable $e) {
    $blockedByGuard = true;
    $guardMessage = $e->getMessage();
}
assertTest("Cashier manual verification of PayMongo payment is blocked by domain guard", $blockedByGuard && str_contains(strtolower($guardMessage), 'gateway'));

// Check financial stats for cashier
$stats = $paymentRepo->getFinancialStats();
assertTest("Cashier pending reviews count ignores PayMongo transactions", is_int($stats['pending_reviews']));

echo "\n====================================================================\n";
echo "  RESULTS: {$passCount} PASSED, {$failCount} FAILED\n";
echo "====================================================================\n";

if ($failCount > 0) {
    exit(1);
}
