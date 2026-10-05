<?php
declare(strict_types=1);

/**
 * TTU ENROLLMENT SYSTEM — PHASE 5 STUDENT PAYMENT QUEUE & ONLINE UI TEST SUITE
 *
 * Verifies:
 * 1. View & Controller Data Contract (queueMetrics & activeQueueSession preloading).
 * 2. paymentQueueJoin endpoint validation (auth, assessment, health requirement).
 * 3. paymentQueueJoin already-paid / settled guard (HTTP 422).
 * 4. paymentQueueJoin active slot allocation under capacity.
 * 5. paymentQueueJoin waiting queue placement under saturation.
 * 6. paymentQueueJoin refresh / multi-tab idempotency (is_existing = true).
 * 7. paymentQueueStatus live polling & telemetry contract.
 * 8. Automatic promotion on status poll when slot frees up.
 * 9. Session expiration handling & UI state transition.
 * 10. paymentQueueLeave voluntary exit & slot reallocation.
 * 11. End-to-end simulated student payment flow:
 *     Join Queue -> Active Slot -> Initiate Checkout -> Webhook Verification -> Settled Balance.
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
use App\Core\Request;
use App\Core\Response;
use App\Controllers\ApplicantController;
use App\Repositories\PaymentSessionRepository;
use App\Repositories\PaymentRepository;
use App\Services\PaymentQueueService;
use App\Services\PaymentService;

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $description): void
{
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$description}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$description}\n";
        $failed++;
    }
}

echo "====================================================================\n";
echo "  TTU ENROLLMENT SYSTEM — PHASE 5 STUDENT PAYMENT UI TEST SUITE     \n";
echo "====================================================================\n\n";

$pdo = Database::getConnection();
$repo = new PaymentSessionRepository($pdo);
$queueService = new PaymentQueueService($repo, $pdo);

// Save initial system_settings to restore after tests
$initialMaxConcurrency = $queueService->getMaxConcurrency();
$initialDuration = $queueService->getSessionDuration();
$initialEnabled = $queueService->isQueueEnabled();

$createdUserIds = [];

// Helper to create test student, application, assessment, and optional health record
function createTestApplicantWithAssessment(PDO $pdo, float $netAmount = 12500.00, bool $withHealth = true, string $paymentStatus = 'unpaid', float $totalPaid = 0.00): array {
    global $createdUserIds;
    $uniq = bin2hex(random_bytes(4));

    // User
    $stmt = $pdo->prepare('
        INSERT INTO users (first_name, last_name, email, password, role, is_active, email_verified, created_at, updated_at)
        VALUES (:fn, :ln, :e, :p, "applicant", 1, 1, NOW(), NOW())
    ');
    $stmt->execute([
        'fn' => "StudentUI_{$uniq}",
        'ln' => "Tester_{$uniq}",
        'e'  => "student_ui_{$uniq}@example.com",
        'p'  => password_hash('Pass123!', PASSWORD_DEFAULT),
    ]);
    $userId = (int) $pdo->lastInsertId();
    $createdUserIds[] = $userId;

    // Application
    $stmt = $pdo->prepare('
        INSERT INTO applications (user_id, reference_number, academic_level, grade_level, status)
        VALUES (:uid, :ref, "College", "1st Year", "approved")
    ');
    $stmt->execute([
        'uid' => $userId,
        'ref' => "APP-UI-{$uniq}",
    ]);
    $appId = (int) $pdo->lastInsertId();

    // Assessment
    $stmt = $pdo->prepare('
        INSERT INTO student_assessments (user_id, application_id, tuition_fee, total_amount, discount_amount, net_amount, total_paid, payment_status)
        VALUES (:uid, :aid, :tfee, :tot, 0.00, :net, :paid, :pstat)
    ');
    $stmt->execute([
        'uid'   => $userId,
        'aid'   => $appId,
        'tfee'  => $netAmount,
        'tot'   => $netAmount,
        'net'   => $netAmount,
        'paid'  => $totalPaid,
        'pstat' => $paymentStatus,
    ]);
    $assId = (int) $pdo->lastInsertId();

    // Health Record
    if ($withHealth) {
        $stmt = $pdo->prepare('
            INSERT INTO health_records (user_id, application_id, blood_type, emergency_name, emergency_contact, status, created_at, updated_at)
            VALUES (:uid, :aid, "O+", "Parent", "09123456789", "completed", NOW(), NOW())
        ');
        $stmt->execute(['uid' => $userId, 'aid' => $appId]);
    }

    return [
        'user_id'        => $userId,
        'application_id' => $appId,
        'assessment_id'  => $assId,
        'net_amount'     => $netAmount,
        'total_paid'     => $totalPaid,
        'payment_status' => $paymentStatus,
    ];
}

// Clean any previous test sessions
$pdo->exec("DELETE FROM payment_sessions WHERE status IN ('active', 'waiting')");

try {
    // -------------------------------------------------------------------------
    // SECTION 1: View Pre-loading & Metrics Contract
    // -------------------------------------------------------------------------
    echo "--- Section 1: Server Pre-load Contract & Telemetry ---\n";

    $queueService->setMaxConcurrency(150);
    $queueService->setSessionDuration(15);
    $queueService->setQueueEnabled(true);

    $metrics = $queueService->getQueueMetrics();
    assertTest($metrics['max_concurrency'] === 150, "Telemetry correctly reports configured capacity (150)");
    assertTest($metrics['active_sessions'] === 0, "Telemetry correctly reports initial 0 active sessions");
    assertTest($metrics['available_slots'] === 150, "Telemetry correctly reports 150 available slots");

    // Test preload query used in ApplicantController::assessment
    $student1 = createTestApplicantWithAssessment($pdo);
    
    // Check with no session
    $stmt = $pdo->prepare('
        SELECT * FROM payment_sessions
        WHERE user_id = :uid AND assessment_id = :aid
          AND status IN ("active", "waiting")
        ORDER BY id DESC LIMIT 1
    ');
    $stmt->execute(['uid' => $student1['user_id'], 'aid' => $student1['assessment_id']]);
    $preloaded = $stmt->fetch(PDO::FETCH_ASSOC);
    assertTest($preloaded === false, "Assessment initial view preloads null session when applicant has not joined");

    // Seed an active session for student 1
    $joinResult1 = $queueService->enterQueue($student1['user_id'], $student1['assessment_id']);
    $stmt->execute(['uid' => $student1['user_id'], 'aid' => $student1['assessment_id']]);
    $preloadedActive = $stmt->fetch(PDO::FETCH_ASSOC);
    assertTest($preloadedActive !== false && $preloadedActive['status'] === 'active', "Assessment view preloads active session immediately on page refresh");

    // -------------------------------------------------------------------------
    // SECTION 2: Payment Queue Join Endpoint Security & Guards
    // -------------------------------------------------------------------------
    echo "\n--- Section 2: Queue Join Endpoint Guards & Validation ---\n";

    $controller = new ApplicantController();

    // Guard 1: Unauthorized check (no session user_id)
    $_SESSION = [];
    $mockRequest = new Request();
    $mockResponse = new Response();

    $controller->paymentQueueJoin($mockRequest, $mockResponse);
    $joinAuthCheck = $mockResponse->jsonData;
    assertTest($mockResponse->statusCode === 401, "paymentQueueJoin rejects unauthenticated request with HTTP 401");
    assertTest(isset($joinAuthCheck['success']) && $joinAuthCheck['success'] === false, "Response body contains success: false");

    // Guard 2: Missing Health Record
    $studentNoHealth = createTestApplicantWithAssessment($pdo, 10000.00, false);
    $_SESSION['user_id'] = $studentNoHealth['user_id'];
    $mockResponse = new Response();

    $controller->paymentQueueJoin($mockRequest, $mockResponse);
    $healthCheck = $mockResponse->jsonData;
    assertTest($mockResponse->statusCode === 403, "paymentQueueJoin rejects applicant without health record with HTTP 403");
    assertTest(isset($healthCheck['health_required']) && $healthCheck['health_required'] === true, "Response specifies health_required: true");

    // Guard 3: Already-Paid / Settled Assessment
    $studentPaid = createTestApplicantWithAssessment($pdo, 10000.00, true, 'paid', 10000.00);
    $_SESSION['user_id'] = $studentPaid['user_id'];
    $mockResponse = new Response();

    $controller->paymentQueueJoin($mockRequest, $mockResponse);
    $paidCheck = $mockResponse->jsonData;
    assertTest($mockResponse->statusCode === 422, "paymentQueueJoin rejects already-settled assessment with HTTP 422");
    assertTest(isset($paidCheck['already_paid']) && $paidCheck['already_paid'] === true, "Response specifies already_paid: true");

    // -------------------------------------------------------------------------
    // SECTION 3: Dynamic Queue Allocation (Active vs Waiting)
    // -------------------------------------------------------------------------
    echo "\n--- Section 3: Dynamic Queue Allocation Under Configured Capacity ---\n";

    // Set capacity to 2 for deterministic testing
    $queueService->setMaxConcurrency(2);
    $pdo->exec("DELETE FROM payment_sessions WHERE status IN ('active', 'waiting')");

    $studentA = createTestApplicantWithAssessment($pdo);
    $studentB = createTestApplicantWithAssessment($pdo);
    $studentC = createTestApplicantWithAssessment($pdo);

    // Student A joins via AJAX endpoint
    $_SESSION['user_id'] = $studentA['user_id'];
    $mockResponse = new Response();
    $controller->paymentQueueJoin($mockRequest, $mockResponse);
    $resA = $mockResponse->jsonData;

    assertTest($mockResponse->statusCode === 200, "Student A joins successfully with HTTP 200");
    assertTest($resA['data']['status'] === 'active', "Student A allocated ACTIVE reservation slot");
    assertTest($resA['data']['checkout_ready'] === true, "Student A marked checkout_ready = true");
    assertTest($resA['data']['payment_readiness'] === 'ready', "Student A payment_readiness = 'ready'");
    assertTest($resA['data']['max_concurrency'] === 2, "Returned max_concurrency matches configured limit (2)");
    assertTest($resA['data']['active_sessions'] === 1, "Returned active_sessions = 1");
    assertTest($resA['data']['available_slots'] === 1, "Returned available_slots = 1");
    assertTest(!empty($_SESSION['payment_session_token']), "Session token securely stored in PHP session");

    // Student B joins via AJAX endpoint (takes 2nd and last active slot)
    $_SESSION['user_id'] = $studentB['user_id'];
    $mockResponse = new Response();
    $controller->paymentQueueJoin($mockRequest, $mockResponse);
    $resB = $mockResponse->jsonData;

    assertTest($resB['data']['status'] === 'active', "Student B allocated 2nd simultaneous ACTIVE slot (Capacity 2/2)");
    assertTest($resB['data']['active_sessions'] === 2, "Returned active_sessions = 2");
    assertTest($resB['data']['available_slots'] === 0, "Returned available_slots = 0");

    // Student C joins via AJAX endpoint (capacity saturated -> placed in line)
    $_SESSION['user_id'] = $studentC['user_id'];
    $mockResponse = new Response();
    $controller->paymentQueueJoin($mockRequest, $mockResponse);
    $resC = $mockResponse->jsonData;

    assertTest($resC['data']['status'] === 'waiting', "Student C placed in WAITING queue due to capacity saturation");
    assertTest($resC['data']['position'] === 1, "Student C assigned queue position #1");
    assertTest($resC['data']['checkout_ready'] === false, "Student C marked checkout_ready = false");
    assertTest($resC['data']['payment_readiness'] === 'waiting', "Student C payment_readiness = 'waiting'");

    // -------------------------------------------------------------------------
    // SECTION 4: Refresh / Reconnect Idempotency
    // -------------------------------------------------------------------------
    echo "\n--- Section 4: Refresh & Reconnect Idempotency ---\n";

    // Student C refreshes page and re-calls paymentQueueJoin
    $_SESSION['user_id'] = $studentC['user_id'];
    $mockResponse = new Response();
    $controller->paymentQueueJoin($mockRequest, $mockResponse);
    $resCRefresh = $mockResponse->jsonData;

    assertTest($resCRefresh['data']['is_existing'] === true, "Student C refresh returns existing session (is_existing = true)");
    assertTest($resCRefresh['data']['session_token'] === $resC['data']['session_token'], "Student C refresh retains identical session token");
    assertTest($resCRefresh['data']['position'] === 1, "Student C refresh retains exact position #1");

    // Active Student A refreshes page and re-calls paymentQueueJoin
    $_SESSION['user_id'] = $studentA['user_id'];
    $mockResponse = new Response();
    $controller->paymentQueueJoin($mockRequest, $mockResponse);
    $resARefresh = $mockResponse->jsonData;

    assertTest($resARefresh['data']['is_existing'] === true, "Student A refresh returns existing active session");
    assertTest($resARefresh['data']['session_token'] === $resA['data']['session_token'], "Student A refresh retains identical token without burning an extra slot");

    // -------------------------------------------------------------------------
    // SECTION 5: Real-time Polling & Automatic Promotion
    // -------------------------------------------------------------------------
    echo "\n--- Section 5: Real-Time Polling & Automatic Promotion ---\n";

    // Student C polls paymentQueueStatus while waiting
    $_GET['token'] = $resC['data']['session_token'];
    $mockResponse = new Response();
    $controller->paymentQueueStatus($mockRequest, $mockResponse);
    $pollDataC = $mockResponse->jsonData;

    assertTest($pollDataC['status'] === 'waiting', "Poll returns status = 'waiting' while slots remain full");
    assertTest($pollDataC['position'] === 1, "Poll returns position = 1");
    assertTest($pollDataC['active_sessions'] === 2, "Poll returns current active sessions count (2)");
    assertTest($pollDataC['max_concurrency'] === 2, "Poll returns configured max concurrency (2)");

    // Student A leaves the queue or completes checkout -> slot becomes vacant
    $queueService->releaseSlot($resA['data']['session_token']);

    // Student C polls again -> should be automatically promoted to ACTIVE!
    $mockResponse = new Response();
    $controller->paymentQueueStatus($mockRequest, $mockResponse);
    $pollDataCPromoted = $mockResponse->jsonData;

    assertTest($pollDataCPromoted['status'] === 'active', "Student C automatically PROMOTED to 'active' on poll");
    assertTest($pollDataCPromoted['checkout_ready'] === true, "Promoted Student C checkout_ready = true");
    assertTest($pollDataCPromoted['payment_readiness'] === 'ready', "Promoted Student C payment_readiness = 'ready'");
    assertTest($pollDataCPromoted['seconds_remaining'] > 850, "Promoted Student C received fresh 15-minute countdown");
    assertTest(isset($pollDataCPromoted['promoted']) && $pollDataCPromoted['promoted'] === true, "Response signals promoted = true flag to UI");

    // -------------------------------------------------------------------------
    // SECTION 6: Session Expiration & UI Transition
    // -------------------------------------------------------------------------
    echo "\n--- Section 6: Session Expiration & UI Transition ---\n";

    // Artificially expire Student B's active session
    $stmt = $pdo->prepare('UPDATE payment_sessions SET expires_at = DATE_SUB(NOW(), INTERVAL 5 SECOND) WHERE session_token = :t');
    $stmt->execute(['t' => $resB['data']['session_token']]);

    // Student B polls status
    $_GET['token'] = $resB['data']['session_token'];
    $mockResponse = new Response();
    $controller->paymentQueueStatus($mockRequest, $mockResponse);
    $pollDataBExpired = $mockResponse->jsonData;

    assertTest($pollDataBExpired['status'] === 'expired', "Expired session returns status = 'expired' to trigger UI expiration card");
    assertTest($pollDataBExpired['checkout_ready'] === false, "Expired session has checkout_ready = false");

    // -------------------------------------------------------------------------
    // SECTION 7: Voluntary Slot Release Endpoint
    // -------------------------------------------------------------------------
    echo "\n--- Section 7: Voluntary Slot Release Endpoint (paymentQueueLeave) ---\n";

    // Student C voluntarily releases active slot via paymentQueueLeave
    $_POST['session_token'] = $resC['data']['session_token'];
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
    $mockResponse = new Response();
    $controller->paymentQueueLeave($mockRequest, $mockResponse);
    $leaveData = $mockResponse->jsonData;

    assertTest($leaveData['success'] === true, "paymentQueueLeave returns success: true via AJAX");
    
    // Verify in DB that status changed to cancelled
    $checkSession = $repo->findByToken($resC['data']['session_token']);
    assertTest($checkSession['status'] === 'cancelled', "Database confirms session marked as 'cancelled'");

    // -------------------------------------------------------------------------
    // SECTION 8: Full End-to-End Student Flow with PayMongo & Confirmation
    // -------------------------------------------------------------------------
    echo "\n--- Section 8: End-to-End Simulated Student Flow ---\n";

    // 1. Assessment: Student E views assessment
    $studentE = createTestApplicantWithAssessment($pdo, 5000.00, true);
    $_SESSION['user_id'] = $studentE['user_id'];

    // 2. Pay Online & Join Queue
    $mockResponse = new Response();
    $controller->paymentQueueJoin($mockRequest, $mockResponse);
    $eJoin = $mockResponse->jsonData;
    assertTest($eJoin['data']['status'] === 'active', "E2E: Student E enters queue and obtains active slot");
    $eToken = $eJoin['data']['session_token'];

    // 3. Initiate PayMongo Checkout
    $paymentService = new PaymentService();
    $checkoutResult = $paymentService->initiatePayMongoPayment([
        'assessment_id' => $studentE['assessment_id'],
        'user_id'       => $studentE['user_id'],
        'amount'        => 5000.00,
        'session_token' => $eToken,
    ]);

    assertTest(!empty($checkoutResult['checkout_url']), "E2E: PayMongo checkout URL successfully generated");
    assertTest(!empty($checkoutResult['checkout_session_id']), "E2E: PayMongo checkout session ID obtained");

    // Verify session record linked to checkout session
    $eSession = $repo->findByToken($eToken);
    assertTest($eSession['checkout_session_id'] === $checkoutResult['checkout_session_id'], "E2E: payment_sessions linked to checkout_session_id");

    // 4. Simulate PayMongo Webhook Payment Confirmation
    $paymentRepo = new PaymentRepository();
    $paymentRecord = $paymentRepo->findByCheckoutSessionId($checkoutResult['checkout_session_id']);
    assertTest(!empty($paymentRecord), "E2E: payment_records entry created in 'pending' status");

    // Process confirmation via webhook handler logic
    $payMongoService = new \App\Services\PayMongoService();
    $webhookSecret = \App\Config\PayMongoConfig::getWebhookSecret() ?: 'whsk_test_mock_secret_key_12345';
    $payloadData = [
        'data' => [
            'id' => 'evt_' . bin2hex(random_bytes(12)),
            'type' => 'event',
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => $checkoutResult['checkout_session_id'],
                    'type' => 'checkout_session',
                    'attributes' => [
                        'payment_intent' => [
                            'id' => 'pi_' . bin2hex(random_bytes(10)),
                            'attributes' => [
                                'status' => 'paid',
                                'amount' => 500000, // 5000.00 in centavos
                                'currency' => 'PHP',
                                'payments' => [
                                    [
                                        'id' => 'pay_' . bin2hex(random_bytes(10)),
                                        'attributes' => [
                                            'status' => 'paid',
                                            'source' => [
                                                'type' => 'gcash'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    $payloadJson = json_encode($payloadData);
    $sigString = \App\Services\PayMongoService::generateSignatureHeader($payloadJson, $webhookSecret, time(), false);

    // Route webhook directly through controller logic
    $webhookController = new \App\Controllers\WebhookController($payMongoService, $paymentService);
    $mockReq = (new Request())
        ->setMethod('POST')
        ->setHeader('Paymongo-Signature', $sigString)
        ->setRawBody($payloadJson);
    $mockRes = new Response();

    $webhookController->handlePayMongo($mockReq, $mockRes);
    $whData = $mockRes->jsonData;

    assertTest($mockRes->statusCode === 200, "E2E: Webhook successfully processed with HTTP 200");
    assertTest(isset($whData['status']) && $whData['status'] === 'success' && ($whData['data']['status'] ?? '') === 'verified', "E2E: Webhook confirmed payment (status = 'success', data.status = 'verified')");

    // 5. Verification: Check ledger, assessment, application, and queue session
    $verifiedRecord = $paymentRepo->findByCheckoutSessionId($checkoutResult['checkout_session_id']);
    assertTest($verifiedRecord['status'] === 'verified', "E2E: payment_records updated to 'verified'");
    assertTest(!empty($verifiedRecord['receipt_number']), "E2E: Official receipt number generated ({$verifiedRecord['receipt_number']})");

    $stmt = $pdo->prepare('SELECT * FROM student_assessments WHERE id = :aid');
    $stmt->execute(['aid' => $studentE['assessment_id']]);
    $updatedAssessment = $stmt->fetch(PDO::FETCH_ASSOC);
    assertTest((float)$updatedAssessment['total_paid'] === 5000.00, "E2E: student_assessments total_paid updated to 5000.00");
    assertTest($updatedAssessment['payment_status'] === 'paid', "E2E: student_assessments payment_status updated to 'paid'");

    // Application status verification
    $stmt = $pdo->prepare('SELECT status FROM applications WHERE id = :aid');
    $stmt->execute(['aid' => $studentE['application_id']]);
    $updatedApp = $stmt->fetch(PDO::FETCH_ASSOC);
    assertTest($updatedApp['status'] === 'payment_verified', "E2E: Application progressed to 'payment_verified' (Registrar gate preserved)");

    // Queue session marked completed
    $completedSession = $repo->findByToken($eToken);
    assertTest($completedSession['status'] === 'completed', "E2E: Queue session marked 'completed' and slot released");

    // 6. Post-Payment Guard: Student cannot re-join queue for settled assessment
    $mockResponse = new Response();
    $controller->paymentQueueJoin($mockRequest, $mockResponse);
    $postSettled = $mockResponse->jsonData;
    assertTest($mockResponse->statusCode === 422, "E2E: Post-payment guard rejects re-joining queue with HTTP 422");
    assertTest($postSettled['already_paid'] === true, "E2E: Post-payment guard specifies already_paid: true");

} catch (Throwable $e) {
    echo "  [EXCEPTION] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failed++;
} finally {
    // Restore initial settings
    $queueService->setMaxConcurrency($initialMaxConcurrency);
    $queueService->setSessionDuration($initialDuration);
    $queueService->setQueueEnabled($initialEnabled);

    // Clean up test data
    if (!empty($createdUserIds)) {
        $inClause = implode(',', array_map('intval', $createdUserIds));
        $pdo->exec("DELETE FROM payment_sessions WHERE user_id IN ({$inClause})");
        $pdo->exec("DELETE FROM payment_records WHERE user_id IN ({$inClause})");
        $pdo->exec("DELETE FROM health_records WHERE user_id IN ({$inClause})");
        $pdo->exec("DELETE FROM student_assessments WHERE user_id IN ({$inClause})");
        $pdo->exec("DELETE FROM applications WHERE user_id IN ({$inClause})");
        $pdo->exec("DELETE FROM users WHERE id IN ({$inClause})");
    }
}

echo "\n====================================================================\n";
echo "  PHASE 5 TEST SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
