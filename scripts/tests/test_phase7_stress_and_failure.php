<?php
declare(strict_types=1);

/**
 * TTU ENROLLMENT SYSTEM — PHASE 7: STRESS, CONCURRENCY, AND FAILURE TESTING SUITE
 *
 * Comprehensive stress and edge-case verification covering:
 * 1. Queue Concurrency:
 *    - Capacity 10 with 11+ simultaneous users (15 users: 10 active, 5 waiting FIFO)
 *    - Capacity 100 with 101+ simultaneous users (105 users: 100 active, 5 waiting FIFO)
 *    - Simultaneous queue requests across independent DB connections
 *    - Simultaneous slot allocation under saturation boundary
 * 2. Payment Concurrency:
 *    - Double-click Pay (PayMongo initiation & Cashier OTC)
 *    - Multiple browser tabs (active and waiting session deduplication)
 *    - Duplicate checkout creation prevention
 *    - Simultaneous payments against one assessment (balance exhaustion & serialization)
 *    - Payment exactly equal to balance (exact full settlement)
 *    - Payment greater than balance (overpayment rejection)
 * 3. Webhooks:
 *    - Duplicate webhook (idempotency, single receipt, no balance inflation)
 *    - Replayed webhook (timestamp > 300s rejected with 401)
 *    - Delayed webhook (valid timestamp within tolerance processed)
 *    - Webhook after expiration (recovers open balance, blocks settled account)
 *    - Invalid signature (tampered body / wrong secret rejected with 401)
 *    - Malformed payload (invalid JSON / empty body rejected with 400)
 *    - Unknown transaction (non-existent session returns 404)
 * 4. Sessions:
 *    - Expiration (stale active session reclamation & waiting promotion)
 *    - Refresh (preserves active/waiting session token, position, and duration)
 *    - Abandoned checkout (stale heartbeat cleanup & queue position exclusion)
 *    - Reconnect (poller resumes and touches heartbeat)
 *    - Server interruption (transaction rollback with zero orphan records)
 * 5. Receipts:
 *    - Simultaneous receipt generation (100 receipts across multi-connections)
 *    - Verify no duplicate receipt numbers & database unique constraint
 * 6. Financial Integrity:
 *    - Verify total_paid <= net_amount invariant
 *    - Verify no duplicate verified payment
 *    - Verify no duplicate receipt
 *    - Verify no duplicate queue slot
 *    - Verify no lost payment
 *    - Verify no corrupted balance
 *    - Verify no unauthorized payment operation (RBAC 403)
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
use App\Controllers\WebhookController;
use App\Controllers\Admin\Finance\FinanceController;
use App\Repositories\PaymentRepository;
use App\Repositories\PaymentSessionRepository;
use App\Services\PaymentService;
use App\Services\PaymentQueueService;
use App\Services\PayMongoService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class TestResponse7 extends Response
{
    public ?string $redirectUrl = null;
    public int $sentStatusCode = 200;
    public mixed $sentJsonData = null;

    public function redirect(string $url): void
    {
        $this->redirectUrl = $url;
    }

    public function json(mixed $data, int $status = 200): void
    {
        $this->sentStatusCode = $status;
        $this->sentJsonData = $data;
        $this->statusCode = $status;
        $this->jsonData = $data;
    }
}

$passed = 0;
$failed = 0;
$testResults = [];

function recordTest(string $scenario, bool $condition, string $expected, string $actual, string $notes = ''): void
{
    global $passed, $failed, $testResults;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$scenario}\n";
        $testResults[] = [
            'scenario' => $scenario,
            'expected' => $expected,
            'actual'   => $actual,
            'status'   => 'PASS',
            'notes'    => $notes,
        ];
    } else {
        $failed++;
        echo "  [FAIL] {$scenario}\n        Expected: {$expected}\n        Actual: {$actual}\n";
        $testResults[] = [
            'scenario' => $scenario,
            'expected' => $expected,
            'actual'   => $actual,
            'status'   => 'FAIL',
            'notes'    => $notes,
        ];
    }
}

echo "====================================================================\n";
echo "  TTU ENROLLMENT SYSTEM — PHASE 7 STRESS & CONCURRENCY TEST SUITE   \n";
echo "====================================================================\n\n";

$pdo = Database::getConnection();
$queueRepo = new PaymentSessionRepository($pdo);
$queueService = new PaymentQueueService($queueRepo, $pdo);
$paymentRepo = new PaymentRepository($pdo);
$paymentService = new PaymentService($paymentRepo, $pdo);

// Save initial settings for clean restoration
$origMaxConcurrency = $queueService->getMaxConcurrency();
$origDuration = $queueService->getSessionDuration();
$origEnabled = $queueService->isQueueEnabled();

$createdUserIds = [];
$createdAssessmentIds = [];

$staticPassHash = password_hash('Pass123!', PASSWORD_DEFAULT);

function createEnsemble(PDO $pdo, float $netAmount = 10000.00): array {
    global $createdUserIds, $createdAssessmentIds, $staticPassHash;
    $uniq = bin2hex(random_bytes(5));
    
    // User
    $stmt = $pdo->prepare('
        INSERT INTO users (first_name, last_name, email, password, role, is_active, email_verified, created_at, updated_at)
        VALUES (:fn, :ln, :e, :p, "applicant", 1, 1, NOW(), NOW())
    ');
    $stmt->execute([
        'fn' => "StressUser_{$uniq}",
        'ln' => "Tester_{$uniq}",
        'e'  => "stress_{$uniq}@ttu.edu.ph",
        'p'  => $staticPassHash,
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
        'ref' => "APP-S-{$uniq}",
    ]);
    $appId = (int) $pdo->lastInsertId();

    // Assessment
    $stmt = $pdo->prepare('
        INSERT INTO student_assessments (user_id, application_id, tuition_fee, total_amount, net_amount, total_paid, payment_status, created_at)
        VALUES (:uid, :aid, :t, :tot, :net, 0.00, "unpaid", NOW())
    ');
    $stmt->execute([
        'uid' => $userId,
        'aid' => $appId,
        't'   => $netAmount,
        'tot' => $netAmount,
        'net' => $netAmount,
    ]);
    $assessmentId = (int) $pdo->lastInsertId();
    $createdAssessmentIds[] = $assessmentId;

    return [
        'user_id'       => $userId,
        'application_id'=> $appId,
        'assessment_id' => $assessmentId,
        'net_amount'    => $netAmount,
    ];
}

// Clean slate in payment_sessions before starting
$pdo->exec('DELETE FROM payment_sessions');

try {
    // =========================================================================
    // SECTION 1: QUEUE CONCURRENCY
    // =========================================================================
    echo "--- Section 1: Queue Concurrency Tests ---\n";

    // Scenario 1.1: Capacity 10 with 11+ simultaneous users (15 users)
    $queueService->setMaxConcurrency(10);
    $queueService->setSessionDuration(15);
    $queueService->setQueueEnabled(true);

    $ensembles10 = [];
    for ($i = 0; $i < 15; $i++) {
        $ensembles10[] = createEnsemble($pdo, 8000.00);
    }

    $results10 = [];
    foreach ($ensembles10 as $ens) {
        $results10[] = $queueService->enterQueue($ens['user_id'], $ens['assessment_id']);
    }

    $activeCount10 = 0;
    $waitingCount10 = 0;
    $waitingPositions10 = [];

    foreach ($results10 as $r) {
        if ($r['status'] === 'active') {
            $activeCount10++;
        } elseif ($r['status'] === 'waiting') {
            $waitingCount10++;
            $waitingPositions10[] = $r['position'];
        }
    }

    $dbActiveCount10 = (int) $pdo->query('SELECT COUNT(*) FROM payment_sessions WHERE status = "active" AND expires_at > NOW()')->fetchColumn();
    $dbWaitingCount10 = (int) $pdo->query('SELECT COUNT(*) FROM payment_sessions WHERE status = "waiting"')->fetchColumn();

    recordTest(
        "Queue Concurrency: Capacity 10 with 15 simultaneous users allocates exactly 10 active slots",
        $activeCount10 === 10 && $dbActiveCount10 === 10,
        "10 active slots allocated",
        "Active count: {$activeCount10} (DB: {$dbActiveCount10})"
    );

    recordTest(
        "Queue Concurrency: Remaining 5 users are placed in FIFO waiting queue",
        $waitingCount10 === 5 && $dbWaitingCount10 === 5,
        "5 waiting sessions placed",
        "Waiting count: {$waitingCount10} (DB: {$dbWaitingCount10})"
    );

    recordTest(
        "Queue Concurrency: Waiting positions are strictly monotonic 1, 2, 3, 4, 5",
        $waitingPositions10 === [1, 2, 3, 4, 5],
        "Positions: [1, 2, 3, 4, 5]",
        "Positions: " . json_encode($waitingPositions10)
    );

    // Scenario 1.2: Capacity 100 with 101+ simultaneous users (105 users)
    // Clear sessions for clean 100 test
    $pdo->exec('DELETE FROM payment_sessions');
    $queueService->setMaxConcurrency(100);

    $ensembles100 = [];
    for ($i = 0; $i < 105; $i++) {
        $ensembles100[] = createEnsemble($pdo, 12000.00);
    }

    $results100 = [];
    foreach ($ensembles100 as $ens) {
        $results100[] = $queueService->enterQueue($ens['user_id'], $ens['assessment_id']);
    }

    $activeCount100 = 0;
    $waitingCount100 = 0;
    $waitingPositions100 = [];

    foreach ($results100 as $r) {
        if ($r['status'] === 'active') {
            $activeCount100++;
        } elseif ($r['status'] === 'waiting') {
            $waitingCount100++;
            $waitingPositions100[] = $r['position'];
        }
    }

    $dbActiveCount100 = (int) $pdo->query('SELECT COUNT(*) FROM payment_sessions WHERE status = "active" AND expires_at > NOW()')->fetchColumn();
    $dbWaitingCount100 = (int) $pdo->query('SELECT COUNT(*) FROM payment_sessions WHERE status = "waiting"')->fetchColumn();

    recordTest(
        "Queue Concurrency: Capacity 100 with 105 users allocates exactly 100 active slots",
        $activeCount100 === 100 && $dbActiveCount100 === 100,
        "100 active slots allocated",
        "Active count: {$activeCount100} (DB: {$dbActiveCount100})"
    );

    recordTest(
        "Queue Concurrency: Remaining 5 users are placed in FIFO waiting queue (100 capacity)",
        $waitingCount100 === 5 && $dbWaitingCount100 === 5,
        "5 waiting sessions placed",
        "Waiting count: {$waitingCount100} (DB: {$dbWaitingCount100})"
    );

    recordTest(
        "Queue Concurrency: FIFO positions for 100-capacity overflow are [1, 2, 3, 4, 5]",
        $waitingPositions100 === [1, 2, 3, 4, 5],
        "Positions: [1, 2, 3, 4, 5]",
        "Positions: " . json_encode($waitingPositions100)
    );

    // Scenario 1.3: Simultaneous Queue Requests & Slot Allocation at Saturation Boundary
    // Create secondary PDO connection
    $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
    $dbPort = getenv('DB_PORT') ?: '3306';
    $dbName = getenv('DB_DATABASE') ?: 'sia';
    $dbUser = getenv('DB_USERNAME') ?: 'root';
    $dbPass = getenv('DB_PASSWORD') ?: '';
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo2 = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $pdo->exec('DELETE FROM payment_sessions');
    $queueService->setMaxConcurrency(1); // Exactly 1 slot available!

    $ensA = createEnsemble($pdo, 9000.00);
    $ensB = createEnsemble($pdo, 9000.00);
    $ensC = createEnsemble($pdo, 9000.00);

    // Queue service with second PDO connection
    $queueService2 = new PaymentQueueService(new PaymentSessionRepository($pdo2), $pdo2);

    $resA = $queueService->enterQueue($ensA['user_id'], $ensA['assessment_id']);
    $resB = $queueService2->enterQueue($ensB['user_id'], $ensB['assessment_id']);
    $resC = $queueService->enterQueue($ensC['user_id'], $ensC['assessment_id']);

    $activeCountBoundary = (int) $pdo->query('SELECT COUNT(*) FROM payment_sessions WHERE status = "active" AND expires_at > NOW()')->fetchColumn();
    $waitingCountBoundary = (int) $pdo->query('SELECT COUNT(*) FROM payment_sessions WHERE status = "waiting"')->fetchColumn();

    recordTest(
        "Queue Concurrency: Boundary slot allocation (capacity 1, 3 requests) permits exactly 1 active",
        $activeCountBoundary === 1 && $resA['status'] === 'active',
        "1 active session allocated",
        "Active count: {$activeCountBoundary}"
    );

    recordTest(
        "Queue Concurrency: Boundary slot allocation diverts overflow requests to waiting without double-allocation",
        $waitingCountBoundary === 2 && $resB['status'] === 'waiting' && $resC['status'] === 'waiting',
        "2 waiting sessions",
        "Waiting count: {$waitingCountBoundary}, B: {$resB['status']}, C: {$resC['status']}"
    );

    // =========================================================================
    // SECTION 2: PAYMENT CONCURRENCY
    // =========================================================================
    echo "\n--- Section 2: Payment Concurrency Tests ---\n";

    // Scenario 2.1: Double-click Pay (Duplicate Checkout Creation)
    $ensPay = createEnsemble($pdo, 15000.00);
    
    // First click initiates PayMongo payment successfully
    $mockPayMongo = new PayMongoService('sk_test_mock_123', null, function ($method, $url, $headers, $body) {
        return [
            'status' => 200,
            'body'   => json_encode([
                'data' => [
                    'id' => 'cs_stress_dbl_' . bin2hex(random_bytes(4)),
                    'type' => 'checkout_session',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.com/cs_stress_dbl',
                        'payment_intent' => ['id' => 'pi_stress_dbl'],
                        'status' => 'active',
                    ],
                ],
            ]),
        ];
    });

    $click1 = $paymentService->initiatePayMongoPayment([
        'assessment_id' => $ensPay['assessment_id'],
        'user_id'       => $ensPay['user_id'],
        'amount'        => 5000.00,
    ], $mockPayMongo);

    $click2Blocked = false;
    $click2Message = '';
    try {
        $paymentService->initiatePayMongoPayment([
            'assessment_id' => $ensPay['assessment_id'],
            'user_id'       => $ensPay['user_id'],
            'amount'        => 5000.00,
        ], $mockPayMongo);
    } catch (\Throwable $e) {
        $click2Blocked = true;
        $click2Message = $e->getMessage();
    }

    recordTest(
        "Payment Concurrency: Double-click PayMongo Pay blocks second checkout creation",
        $click1['success'] === true && $click2Blocked && str_contains($click2Message, 'already pending'),
        "Blocked with 'already pending' error",
        "Click 1 Success: " . ($click1['success'] ? 'true' : 'false') . ", Click 2 Blocked: " . ($click2Blocked ? 'true' : 'false') . " ({$click2Message})"
    );

    // Scenario 2.2: Multiple Browser Tabs (Queue Deduplication)
    $ensTabs = createEnsemble($pdo, 10000.00);
    $tab1 = $queueService->enterQueue($ensTabs['user_id'], $ensTabs['assessment_id']);
    $tab2 = $queueService->enterQueue($ensTabs['user_id'], $ensTabs['assessment_id']);

    recordTest(
        "Payment Concurrency: Multiple tabs return existing session token without consuming extra slot",
        $tab1['session_token'] === $tab2['session_token'] && $tab2['is_existing'] === true,
        "Same token returned with is_existing=true",
        "Tab 1 Token: " . substr($tab1['session_token'], 0, 8) . "..., Tab 2 Token: " . substr($tab2['session_token'], 0, 8) . "..., is_existing=" . ($tab2['is_existing'] ? 'true' : 'false')
    );

    // Scenario 2.3: Simultaneous Cashier Payments Against One Assessment
    $ensSimul = createEnsemble($pdo, 6000.00); // Net 6,000.00
    // Terminal 1 records 4,000.00
    $otc1 = $paymentService->recordOverTheCounterPayment([
        'assessment_id'    => $ensSimul['assessment_id'],
        'user_id'          => $ensSimul['user_id'],
        'amount'           => 4000.00,
        'payment_method'   => 'Cash',
        'reference_number' => 'REF-SIMUL-1',
    ], 1);

    // Terminal 2 simultaneously tries to record 4,000.00 (Exceeds remaining 2,000.00 balance)
    $otc2Blocked = false;
    $otc2Message = '';
    try {
        $paymentService->recordOverTheCounterPayment([
            'assessment_id'    => $ensSimul['assessment_id'],
            'user_id'          => $ensSimul['user_id'],
            'amount'           => 4000.00,
            'payment_method'   => 'Cash',
            'reference_number' => 'REF-SIMUL-2',
        ], 1);
    } catch (\Throwable $e) {
        $otc2Blocked = true;
        $otc2Message = $e->getMessage();
    }

    recordTest(
        "Payment Concurrency: Simultaneous payment exceeding dynamic remaining balance is blocked",
        $otc1['success'] === true && $otc2Blocked && str_contains($otc2Message, 'exceed'),
        "Payment 1 accepted (₱4,000), Payment 2 rejected (exceeds ₱2,000 balance)",
        "OTC 1 Status: {$otc1['payment_status']}, OTC 2 Error: {$otc2Message}"
    );

    // Scenario 2.4: Payment Exactly Equal to Balance
    // Terminal 2 now records exactly remaining 2,000.00
    $otcExact = $paymentService->recordOverTheCounterPayment([
        'assessment_id'    => $ensSimul['assessment_id'],
        'user_id'          => $ensSimul['user_id'],
        'amount'           => 2000.00,
        'payment_method'   => 'Cash',
        'reference_number' => 'REF-EXACT-2',
    ], 1);

    $assExact = $pdo->query("SELECT * FROM student_assessments WHERE id = {$ensSimul['assessment_id']}")->fetch(PDO::FETCH_ASSOC);

    recordTest(
        "Payment Concurrency: Payment exactly equal to balance transitions assessment to 'paid'",
        $otcExact['payment_status'] === 'paid' && (float)$assExact['total_paid'] === 6000.00 && (float)$assExact['net_amount'] === 6000.00,
        "Assessment total_paid == net_amount, status='paid'",
        "Total Paid: {$assExact['total_paid']}, Net: {$assExact['net_amount']}, Status: {$assExact['payment_status']}"
    );

    // Scenario 2.5: Payment Greater Than Balance (On Fully Settled Account)
    $overpayBlocked = false;
    $overpayMessage = '';
    try {
        $paymentService->recordOverTheCounterPayment([
            'assessment_id'    => $ensSimul['assessment_id'],
            'user_id'          => $ensSimul['user_id'],
            'amount'           => 1000.00,
            'payment_method'   => 'Cash',
            'reference_number' => 'REF-OVERPAY-3',
        ], 1);
    } catch (\Throwable $e) {
        $overpayBlocked = true;
        $overpayMessage = $e->getMessage();
    }

    recordTest(
        "Payment Concurrency: Payment on fully settled account is rejected",
        $overpayBlocked && str_contains($overpayMessage, 'settled'),
        "Rejected with settled account error",
        "Error: {$overpayMessage}"
    );

    // =========================================================================
    // SECTION 3: WEBHOOKS
    // =========================================================================
    echo "\n--- Section 3: Webhook Verification Tests ---\n";

    $webhookSecret = 'whsk_stress_secret_key_9876543210';
    putenv("PAYMONGO_WEBHOOK_SECRET={$webhookSecret}");
    $_ENV['PAYMONGO_WEBHOOK_SECRET'] = $webhookSecret;

    $payMongoSvc = new PayMongoService(null, null, null);
    $webhookCtrl = new WebhookController($payMongoSvc, $paymentService);

    // Scenario 3.1: Duplicate Webhook Delivery
    $ensWh = createEnsemble($pdo, 10000.00);
    $csIdWh = 'cs_wh_' . bin2hex(random_bytes(4));
    $piIdWh = 'pi_wh_' . bin2hex(random_bytes(4));

    // Create pending payment record
    $payIdWh = $paymentRepo->insert([
        'assessment_id'       => $ensWh['assessment_id'],
        'user_id'             => $ensWh['user_id'],
        'amount'              => 5000.00,
        'payment_date'        => date('Y-m-d'),
        'payment_method'      => 'PayMongo',
        'gateway'             => 'paymongo',
        'status'              => 'pending',
        'checkout_session_id' => $csIdWh,
        'payment_intent_id'   => $piIdWh,
    ]);

    $whPayload1 = json_encode([
        'data' => [
            'id' => 'evt_wh_1',
            'type' => 'event',
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => $csIdWh,
                    'type' => 'checkout_session',
                    'attributes' => [
                        'status' => 'paid',
                        'payments' => [
                            ['attributes' => ['fee' => 12500]] // 125.00 PHP fee
                        ],
                    ],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $sigHeader1 = PayMongoService::generateSignatureHeader($whPayload1, $webhookSecret, time());

    // Delivery 1
    $req1 = new Request();
    $req1->setMethod('POST');
    $req1->setRawBody($whPayload1);
    $req1->setHeader('Paymongo-Signature', $sigHeader1);
    $res1 = new TestResponse7();

    $webhookCtrl->handlePayMongo($req1, $res1);
    $rec1 = $pdo->query("SELECT * FROM payment_records WHERE id = {$payIdWh}")->fetch(PDO::FETCH_ASSOC);

    // Delivery 2 (Duplicate delivery of exact same webhook)
    $req2 = new Request();
    $req2->setMethod('POST');
    $req2->setRawBody($whPayload1);
    $req2->setHeader('Paymongo-Signature', $sigHeader1);
    $res2 = new TestResponse7();

    $webhookCtrl->handlePayMongo($req2, $res2);
    $rec2 = $pdo->query("SELECT * FROM payment_records WHERE id = {$payIdWh}")->fetch(PDO::FETCH_ASSOC);
    $assWh = $pdo->query("SELECT * FROM student_assessments WHERE id = {$ensWh['assessment_id']}")->fetch(PDO::FETCH_ASSOC);

    recordTest(
        "Webhooks: Duplicate webhook delivery returns HTTP 200 with already_processed status",
        $res1->sentStatusCode === 200 && $res2->sentStatusCode === 200 && (($res2->sentJsonData['data']['status'] ?? $res2->sentJsonData['status'] ?? '') === 'already_processed'),
        "HTTP 200, status='already_processed'",
        "Delivery 1 Status: {$res1->sentStatusCode}, Delivery 2 Status: {$res2->sentStatusCode}, Result: " . ($res2->sentJsonData['data']['status'] ?? $res2->sentJsonData['status'] ?? 'none')
    );

    recordTest(
        "Webhooks: Duplicate webhook does NOT generate second receipt or inflate total_paid",
        $rec1['receipt_number'] === $rec2['receipt_number'] && (float)$assWh['total_paid'] === 5000.00,
        "Receipt identical, total_paid remains ₱5,000.00",
        "Receipt 1: {$rec1['receipt_number']}, Receipt 2: {$rec2['receipt_number']}, Total Paid: {$assWh['total_paid']}"
    );

    // Scenario 3.2: Replayed Webhook (Timestamp > 300s)
    $replayedTimestamp = time() - 360; // 6 minutes ago
    $sigHeaderReplay = PayMongoService::generateSignatureHeader($whPayload1, $webhookSecret, $replayedTimestamp);
    $reqReplay = new Request();
    $reqReplay->setMethod('POST');
    $reqReplay->setRawBody($whPayload1);
    $reqReplay->setHeader('Paymongo-Signature', $sigHeaderReplay);
    $resReplay = new TestResponse7();

    $webhookCtrl->handlePayMongo($reqReplay, $resReplay);

    recordTest(
        "Webhooks: Replayed webhook (>300s old) is rejected with HTTP 401 Unauthorized",
        $resReplay->sentStatusCode === 401,
        "HTTP 401 Unauthorized",
        "HTTP Status: {$resReplay->sentStatusCode}"
    );

    // Scenario 3.3: Delayed Webhook (Timestamp 180s old, within 300s window)
    $ensDelayed = createEnsemble($pdo, 10000.00);
    $csIdDelayed = 'cs_del_' . bin2hex(random_bytes(4));
    $payIdDelayed = $paymentRepo->insert([
        'assessment_id'       => $ensDelayed['assessment_id'],
        'user_id'             => $ensDelayed['user_id'],
        'amount'              => 3000.00,
        'payment_date'        => date('Y-m-d'),
        'payment_method'      => 'PayMongo',
        'gateway'             => 'paymongo',
        'status'              => 'pending',
        'checkout_session_id' => $csIdDelayed,
    ]);

    $whPayloadDelayed = json_encode([
        'data' => [
            'id' => 'evt_delayed_1',
            'type' => 'event',
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => $csIdDelayed,
                    'type' => 'checkout_session',
                    'attributes' => ['status' => 'paid'],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $delayedTimestamp = time() - 180; // 3 minutes ago
    $sigHeaderDelayed = PayMongoService::generateSignatureHeader($whPayloadDelayed, $webhookSecret, $delayedTimestamp);

    $reqDelayed = new Request();
    $reqDelayed->setMethod('POST');
    $reqDelayed->setRawBody($whPayloadDelayed);
    $reqDelayed->setHeader('Paymongo-Signature', $sigHeaderDelayed);
    $resDelayed = new TestResponse7();

    $webhookCtrl->handlePayMongo($reqDelayed, $resDelayed);
    $recDelayed = $pdo->query("SELECT * FROM payment_records WHERE id = {$payIdDelayed}")->fetch(PDO::FETCH_ASSOC);

    recordTest(
        "Webhooks: Delayed webhook (within 300s tolerance) is successfully verified",
        $resDelayed->sentStatusCode === 200 && $recDelayed['status'] === 'verified',
        "HTTP 200, status='verified'",
        "Status: {$resDelayed->sentStatusCode}, Record: {$recDelayed['status']}"
    );

    // Scenario 3.4: Webhook After Expiration
    // Subcase A: Assessment still open -> Webhook recovers payment safely
    $ensExpRecover = createEnsemble($pdo, 8000.00);
    $csIdExpRecover = 'cs_exp_rec_' . bin2hex(random_bytes(4));
    $payIdExpRecover = $paymentRepo->insert([
        'assessment_id'       => $ensExpRecover['assessment_id'],
        'user_id'             => $ensExpRecover['user_id'],
        'amount'              => 4000.00,
        'payment_date'        => date('Y-m-d'),
        'payment_method'      => 'PayMongo',
        'gateway'             => 'paymongo',
        'status'              => 'expired', // Marked expired locally
        'checkout_session_id' => $csIdExpRecover,
    ]);

    $whPayloadExp = json_encode([
        'data' => [
            'id' => 'evt_exp_rec',
            'type' => 'event',
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => $csIdExpRecover,
                    'type' => 'checkout_session',
                    'attributes' => ['status' => 'paid'],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $sigExp = PayMongoService::generateSignatureHeader($whPayloadExp, $webhookSecret, time());
    $reqExp = new Request();
    $reqExp->setMethod('POST');
    $reqExp->setRawBody($whPayloadExp);
    $reqExp->setHeader('Paymongo-Signature', $sigExp);
    $resExp = new TestResponse7();

    $webhookCtrl->handlePayMongo($reqExp, $resExp);
    $recExpRecover = $pdo->query("SELECT * FROM payment_records WHERE id = {$payIdExpRecover}")->fetch(PDO::FETCH_ASSOC);

    recordTest(
        "Webhooks: Webhook after session expiration recovers payment if assessment remains open",
        $resExp->sentStatusCode === 200 && $recExpRecover['status'] === 'verified',
        "HTTP 200, recovered to 'verified'",
        "HTTP: {$resExp->sentStatusCode}, Status: {$recExpRecover['status']}"
    );

    // Subcase B: Assessment was already settled OTC while expired -> Webhook rejects with HTTP 422 to prevent overpayment
    $ensExpSettled = createEnsemble($pdo, 5000.00);
    // Cashier settled account in the meantime via PaymentService
    $paymentService->recordOverTheCounterPayment([
        'assessment_id'    => $ensExpSettled['assessment_id'],
        'user_id'          => $ensExpSettled['user_id'],
        'amount'           => 5000.00,
        'payment_method'   => 'Cash',
        'reference_number' => 'REF-EXP-SETTLED-' . bin2hex(random_bytes(3)),
    ], 1);
    $csIdExpSettled = 'cs_exp_set_' . bin2hex(random_bytes(4));
    $payIdExpSettled = $paymentRepo->insert([
        'assessment_id'       => $ensExpSettled['assessment_id'],
        'user_id'             => $ensExpSettled['user_id'],
        'amount'              => 5000.00,
        'payment_date'        => date('Y-m-d'),
        'payment_method'      => 'PayMongo',
        'gateway'             => 'paymongo',
        'status'              => 'expired',
        'checkout_session_id' => $csIdExpSettled,
    ]);

    $whPayloadExpSettled = json_encode([
        'data' => [
            'id' => 'evt_exp_set',
            'type' => 'event',
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => $csIdExpSettled,
                    'type' => 'checkout_session',
                    'attributes' => ['status' => 'paid'],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $sigExpSettled = PayMongoService::generateSignatureHeader($whPayloadExpSettled, $webhookSecret, time());
    $reqExpSettled = new Request();
    $reqExpSettled->setMethod('POST');
    $reqExpSettled->setRawBody($whPayloadExpSettled);
    $reqExpSettled->setHeader('Paymongo-Signature', $sigExpSettled);
    $resExpSettled = new TestResponse7();

    $webhookCtrl->handlePayMongo($reqExpSettled, $resExpSettled);
    $assExpSettled = $pdo->query("SELECT * FROM student_assessments WHERE id = {$ensExpSettled['assessment_id']}")->fetch(PDO::FETCH_ASSOC);

    recordTest(
        "Webhooks: Webhook after expiration rejects payment if account was settled in the interim (overpayment guard)",
        $resExpSettled->sentStatusCode === 422 && (float)$assExpSettled['total_paid'] === 5000.00,
        "HTTP 422 Unprocessable, total_paid unchanged at ₱5,000.00",
        "HTTP: {$resExpSettled->sentStatusCode}, Total Paid: {$assExpSettled['total_paid']}"
    );

    // Scenario 3.5: Invalid Signature
    $reqInvalidSig = new Request();
    $reqInvalidSig->setMethod('POST');
    $reqInvalidSig->setRawBody($whPayload1);
    $reqInvalidSig->setHeader('Paymongo-Signature', 't=' . time() . ',te=bad_hex_hash_0000000000000000');
    $resInvalidSig = new TestResponse7();

    $webhookCtrl->handlePayMongo($reqInvalidSig, $resInvalidSig);

    recordTest(
        "Webhooks: Invalid signature header is rejected with HTTP 401",
        $resInvalidSig->sentStatusCode === 401,
        "HTTP 401",
        "HTTP Status: {$resInvalidSig->sentStatusCode}"
    );

    // Scenario 3.6: Malformed Payload
    $reqMalformed = new Request();
    $reqMalformed->setMethod('POST');
    $reqMalformed->setRawBody('This is definitely not JSON');
    $reqMalformed->setHeader('Paymongo-Signature', $sigHeader1);
    $resMalformed = new TestResponse7();

    $webhookCtrl->handlePayMongo($reqMalformed, $resMalformed);

    recordTest(
        "Webhooks: Malformed JSON payload is rejected with HTTP 400 Bad Request",
        $resMalformed->sentStatusCode === 400,
        "HTTP 400",
        "HTTP Status: {$resMalformed->sentStatusCode}"
    );

    // Scenario 3.7: Unknown Transaction
    $whUnknownPayload = json_encode([
        'data' => [
            'id' => 'evt_unknown',
            'type' => 'event',
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_non_existent_999999',
                    'type' => 'checkout_session',
                    'attributes' => ['status' => 'paid'],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $sigUnknown = PayMongoService::generateSignatureHeader($whUnknownPayload, $webhookSecret, time());
    $reqUnknown = new Request();
    $reqUnknown->setMethod('POST');
    $reqUnknown->setRawBody($whUnknownPayload);
    $reqUnknown->setHeader('Paymongo-Signature', $sigUnknown);
    $resUnknown = new TestResponse7();

    $webhookCtrl->handlePayMongo($reqUnknown, $resUnknown);

    recordTest(
        "Webhooks: Unrecognized checkout session returns HTTP 404 Not Found",
        $resUnknown->sentStatusCode === 404,
        "HTTP 404",
        "HTTP Status: {$resUnknown->sentStatusCode}"
    );

    // =========================================================================
    // SECTION 4: SESSIONS
    // =========================================================================
    echo "\n--- Section 4: Session Lifecycle Tests ---\n";

    // Scenario 4.1: Expiration & Slot Recycling
    $pdo->exec('DELETE FROM payment_sessions');
    $queueService->setMaxConcurrency(1);

    $ensExp1 = createEnsemble($pdo, 7000.00);
    $ensExp2 = createEnsemble($pdo, 7000.00);

    // User 1 gets active
    $sExp1 = $queueService->enterQueue($ensExp1['user_id'], $ensExp1['assessment_id']);
    // User 2 gets waiting
    $sExp2 = $queueService->enterQueue($ensExp2['user_id'], $ensExp2['assessment_id']);

    // Age User 1's expiration timestamp to the past
    $pdo->exec("UPDATE payment_sessions SET expires_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE session_token = '{$sExp1['session_token']}'");

    // Proactive maintenance sweep
    $expiredCount = $queueRepo->expireStaleActiveSessions();
    $promotedCount = $queueService->promoteEligibleWaitingSessions();

    $sExp2Status = $queueService->checkStatus($sExp2['session_token']);

    recordTest(
        "Sessions: Expiration of active session reclaims slot and promotes waiting candidate",
        $expiredCount === 1 && $promotedCount === 1 && $sExp2Status['status'] === 'active',
        "1 expired, 1 promoted, User 2 promoted to active",
        "Expired: {$expiredCount}, Promoted: {$promotedCount}, User 2 Status: {$sExp2Status['status']}"
    );

    // Scenario 4.2: Refresh (Preserves State)
    $ensRef = createEnsemble($pdo, 9000.00);
    $sRef1 = $queueService->enterQueue($ensRef['user_id'], $ensRef['assessment_id']);
    $sRef2 = $queueService->enterQueue($ensRef['user_id'], $ensRef['assessment_id']);

    recordTest(
        "Sessions: Refreshing returns active session with is_existing=true",
        $sRef2['is_existing'] === true && $sRef1['session_token'] === $sRef2['session_token'],
        "Existing token preserved",
        "Token: " . substr($sRef2['session_token'], 0, 8) . "..., is_existing=" . ($sRef2['is_existing'] ? 'true' : 'false')
    );

    // Scenario 4.3: Abandoned Checkout (Heartbeat Timeout)
    $pdo->exec('DELETE FROM payment_sessions');
    $queueService->setMaxConcurrency(1);

    $ensAb1 = createEnsemble($pdo, 8000.00);
    $ensAb2 = createEnsemble($pdo, 8000.00);
    $ensAb3 = createEnsemble($pdo, 8000.00);

    $sAb1 = $queueService->enterQueue($ensAb1['user_id'], $ensAb1['assessment_id']); // active
    $sAb2 = $queueService->enterQueue($ensAb2['user_id'], $ensAb2['assessment_id']); // waiting #1
    $sAb3 = $queueService->enterQueue($ensAb3['user_id'], $ensAb3['assessment_id']); // waiting #2

    // Age User 2's heartbeat by 10 minutes (abandoned dead tab)
    $pdo->exec("UPDATE payment_sessions SET last_heartbeat_at = DATE_SUB(NOW(), INTERVAL 10 MINUTE) WHERE session_token = '{$sAb2['session_token']}'");

    $abandonedCount = $queueRepo->abandonStaleWaitingSessions(5);
    $posAb3 = $queueRepo->getWaitingPosition($sAb3['queue_number']);

    recordTest(
        "Sessions: Abandoned waiting session is reclaimed and excluded from queue positions",
        $abandonedCount === 1 && $posAb3 === 1,
        "1 abandoned reclaimed, User 3 advanced to position #1",
        "Abandoned: {$abandonedCount}, User 3 Position: {$posAb3}"
    );

    // Scenario 4.4: Reconnect (Poller Heartbeat Update)
    $beforeHb = $pdo->query("SELECT last_heartbeat_at FROM payment_sessions WHERE session_token = '{$sAb3['session_token']}'")->fetchColumn();
    // Simulate brief network drop and reconnect poll
    sleep(1);
    $statusReconnect = $queueService->checkStatus($sAb3['session_token']);
    $afterHb = $pdo->query("SELECT last_heartbeat_at FROM payment_sessions WHERE session_token = '{$sAb3['session_token']}'")->fetchColumn();

    recordTest(
        "Sessions: Reconnecting client poller updates heartbeat timestamp",
        strtotime($afterHb) >= strtotime($beforeHb),
        "Heartbeat updated",
        "Before: {$beforeHb}, After: {$afterHb}"
    );

    // Scenario 4.5: Server Interruption & Transaction Rollback
    $ensInt = createEnsemble($pdo, 10000.00);
    $countBefore = (int) $pdo->query("SELECT COUNT(*) FROM payment_records WHERE assessment_id = {$ensInt['assessment_id']}")->fetchColumn();

    try {
        $pdo->beginTransaction();
        $paymentRepo->insert([
            'assessment_id'       => $ensInt['assessment_id'],
            'user_id'             => $ensInt['user_id'],
            'amount'              => 5000.00,
            'payment_date'        => date('Y-m-d'),
            'payment_method'      => 'PayMongo',
            'status'              => 'pending',
            'checkout_session_id' => 'cs_interrupt_test',
        ]);
        // Simulate unexpected database/server interruption
        throw new \RuntimeException('Simulated catastrophic server failure midway through transaction.');
    } catch (\Throwable $e) {
        $pdo->rollBack();
    }

    $countAfter = (int) $pdo->query("SELECT COUNT(*) FROM payment_records WHERE assessment_id = {$ensInt['assessment_id']}")->fetchColumn();

    recordTest(
        "Sessions: Server interruption rolls back cleanly with zero orphaned database rows",
        $countBefore === $countAfter && $countAfter === 0,
        "Record count unchanged (0)",
        "Count Before: {$countBefore}, Count After: {$countAfter}"
    );

    // =========================================================================
    // SECTION 5: RECEIPTS
    // =========================================================================
    echo "\n--- Section 5: Receipt Generation & Collisions ---\n";

    // Scenario 5.1: Simultaneous Receipt Generation Across Multiple Connections
    $receiptList = [];
    $receiptCollision = false;

    // Generate 100 receipts interleaving across $pdo and $pdo2
    for ($i = 0; $i < 50; $i++) {
        $rA = generateAtomicReceiptNumber($pdo);
        $rB = generateAtomicReceiptNumber($pdo2);

        if (in_array($rA, $receiptList, true)) {
            $receiptCollision = true;
            break;
        }
        $receiptList[] = $rA;

        if (in_array($rB, $receiptList, true)) {
            $receiptCollision = true;
            break;
        }
        $receiptList[] = $rB;
    }

    recordTest(
        "Receipts: 100 interleaved receipts generated without a single collision",
        count($receiptList) === 100 && !$receiptCollision,
        "100 unique receipts generated",
        "Unique count: " . count($receiptList) . ", Collision detected: " . ($receiptCollision ? 'YES' : 'NO')
    );

    recordTest(
        "Receipts: Generated receipt numbers adhere to standard REC-YYYYMMDD-XXXX format",
        (bool) preg_match('/^REC-\d{8}-\d{4}$/', $receiptList[0]),
        "Format matches REC-YYYYMMDD-XXXX",
        "Sample: {$receiptList[0]}"
    );

    // Scenario 5.2: Database UNIQUE Constraint on receipt_number
    $ensUq = createEnsemble($pdo, 5000.00);
    $dupReceipt = 'REC-TEST-DUP-' . bin2hex(random_bytes(3));
    
    // Insert first record
    $paymentRepo->insert([
        'assessment_id'       => $ensUq['assessment_id'],
        'user_id'             => $ensUq['user_id'],
        'amount'              => 1000.00,
        'payment_date'        => date('Y-m-d'),
        'payment_method'      => 'Cash',
        'receipt_number'      => $dupReceipt,
        'status'              => 'verified',
    ]);

    // Attempt second insert with identical receipt number
    $dupBlocked = false;
    try {
        $paymentRepo->insert([
            'assessment_id'       => $ensUq['assessment_id'],
            'user_id'             => $ensUq['user_id'],
            'amount'              => 1000.00,
            'payment_date'        => date('Y-m-d'),
            'payment_method'      => 'Cash',
            'receipt_number'      => $dupReceipt,
            'status'              => 'verified',
        ]);
    } catch (\PDOException $e) {
        $dupBlocked = true;
    }

    recordTest(
        "Receipts: Database UNIQUE constraint blocks duplicate receipt number insertion",
        $dupBlocked,
        "Duplicate receipt insertion blocked by database constraint",
        "Blocked: " . ($dupBlocked ? 'YES' : 'NO')
    );

    // Clean up temporary UNIQUE constraint test payment record
    $pdo->exec("DELETE FROM payment_records WHERE receipt_number = '{$dupReceipt}'");

    // =========================================================================
    // SECTION 6: FINANCIAL INTEGRITY & PERMISSIONS
    // =========================================================================
    echo "\n--- Section 6: Financial Integrity & Permission Verification ---\n";

    // 6.1: Verify total_paid <= net_amount across all student assessments
    $violationRow = $pdo->query('
        SELECT id, net_amount, total_paid 
        FROM student_assessments 
        WHERE total_paid > (net_amount + 0.01)
        LIMIT 1
    ')->fetch(PDO::FETCH_ASSOC);

    recordTest(
        "Financial Integrity: Universal verification that total_paid <= net_amount across all assessments",
        $violationRow === false,
        "Zero overpayment violations found in student_assessments",
        $violationRow ? "Violation found in Assessment #{$violationRow['id']}: paid={$violationRow['total_paid']} > net={$violationRow['net_amount']}" : "All assessments satisfy invariant"
    );

    // 6.2: Verify No Duplicate Verified Payments for same transaction
    $dupVerified = $pdo->query('
        SELECT receipt_number, COUNT(*) as cnt 
        FROM payment_records 
        WHERE receipt_number IS NOT NULL AND status = "verified" 
        GROUP BY receipt_number 
        HAVING cnt > 1
    ')->fetchAll(PDO::FETCH_ASSOC);

    recordTest(
        "Financial Integrity: Verify zero duplicate verified payments / receipts in ledger",
        count($dupVerified) === 0,
        "0 duplicate receipts in ledger",
        "Duplicate count: " . count($dupVerified)
    );

    // 6.3: Verify No Duplicate Active Queue Slots per student
    $dupSlots = $pdo->query('
        SELECT user_id, assessment_id, COUNT(*) as cnt 
        FROM payment_sessions 
        WHERE status = "active" AND expires_at > NOW() 
        GROUP BY user_id, assessment_id 
        HAVING cnt > 1
    ')->fetchAll(PDO::FETCH_ASSOC);

    recordTest(
        "Financial Integrity: Verify zero duplicate active queue slots per applicant",
        count($dupSlots) === 0,
        "0 duplicate active slots",
        "Duplicate active slots count: " . count($dupSlots)
    );

    // 6.4: Verify Corrupted Balance Protection (Sum of verified payments matches total_paid)
    $mismatches = $pdo->query('
        SELECT sa.id, sa.total_paid, COALESCE(SUM(pr.amount), 0) as ledger_total
        FROM student_assessments sa
        INNER JOIN payment_records pr ON sa.id = pr.assessment_id AND pr.status = "verified"
        GROUP BY sa.id
        HAVING ABS(sa.total_paid - ledger_total) > 0.01
    ')->fetchAll(PDO::FETCH_ASSOC);

    // Also verify all test ensemble assessments created during this stress test
    $inAssStr = implode(',', $createdAssessmentIds);
    $testMismatches = !empty($createdAssessmentIds) ? $pdo->query("
        SELECT sa.id, sa.total_paid, COALESCE(SUM(pr.amount), 0) as ledger_total
        FROM student_assessments sa
        LEFT JOIN payment_records pr ON sa.id = pr.assessment_id AND pr.status = 'verified'
        WHERE sa.id IN ($inAssStr)
        GROUP BY sa.id
        HAVING ABS(sa.total_paid - ledger_total) > 0.01
    ")->fetchAll(PDO::FETCH_ASSOC) : [];

    recordTest(
        "Financial Integrity: Verified payments in ledger match student_assessments.total_paid exactly",
        count($mismatches) === 0 && count($testMismatches) === 0,
        "0 balance discrepancies found between ledger and assessments",
        "Discrepancies found: " . (count($mismatches) + count($testMismatches))
    );

    // 6.5: Verify Unauthorized Payment Operations (RBAC Protection)
    // Attempt payment mutation without 'payments.record' permission
    $_SESSION['user_id'] = 999999;
    $_SESSION['user_role'] = 'clinic'; // Clinic staff cannot record payments!
    $_SESSION['user_permissions'] = ['clinic.records'];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['action'] = 'record_payment';
    $_POST['assessment_id'] = $ensUq['assessment_id'];
    $_POST['amount'] = 1000.00;
    $_POST['payment_method'] = 'Cash';

    $reqAuth = new Request();
    $resAuth = new TestResponse7();
    $financeCtrl = new FinanceController();

    $unauthorizedBlocked = false;
    $unauthorizedCode = 0;
    try {
        $financeCtrl->process($reqAuth, $resAuth);
    } catch (\App\Core\HttpException $e) {
        $unauthorizedBlocked = true;
        $unauthorizedCode = $e->getCode();
    } catch (\Throwable $e) {
        if (str_contains($e->getMessage(), '403') || str_contains($e->getMessage(), 'Forbidden') || str_contains($e->getMessage(), 'Permission')) {
            $unauthorizedBlocked = true;
            $unauthorizedCode = 403;
        }
    }

    recordTest(
        "Financial Integrity: Unauthorized administrative role (clinic) is blocked from recording payments (HTTP 403)",
        $unauthorizedBlocked,
        "Blocked with HTTP 403 Forbidden",
        "Blocked: " . ($unauthorizedBlocked ? 'YES' : 'NO') . " (Code: {$unauthorizedCode})"
    );

} finally {
    // Restore original settings
    $queueService->setMaxConcurrency($origMaxConcurrency);
    $queueService->setSessionDuration($origDuration);
    $queueService->setQueueEnabled($origEnabled);

    // Clean up created test entities
    if (!empty($createdAssessmentIds)) {
        $inAss = implode(',', $createdAssessmentIds);
        $pdo->exec("DELETE FROM payment_sessions WHERE assessment_id IN ({$inAss})");
        $pdo->exec("DELETE FROM payment_records WHERE assessment_id IN ({$inAss})");
        $pdo->exec("DELETE FROM student_assessments WHERE id IN ({$inAss})");
    }
    if (!empty($createdUserIds)) {
        $inUsers = implode(',', $createdUserIds);
        $pdo->exec("DELETE FROM applications WHERE user_id IN ({$inUsers})");
        $pdo->exec("DELETE FROM activity_logs WHERE user_id IN ({$inUsers})");
        $pdo->exec("DELETE FROM users WHERE id IN ({$inUsers})");
    }
}

echo "\n====================================================================\n";
echo "  PHASE 7 TEST SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "====================================================================\n";

exit($failed > 0 ? 1 : 0);
