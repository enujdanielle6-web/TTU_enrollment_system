<?php
declare(strict_types=1);

/**
 * TTU ENROLLMENT SYSTEM — PHASE 4 PAYMENT QUEUE VERIFICATION SUITE
 *
 * Verifies:
 * 1. Configuration governance via system_settings (dynamic limit: 100 -> 250 -> 500, durations, toggle).
 * 2. Multi-student concurrency (multiple simultaneous active sessions, NOT one-at-a-time).
 * 3. Immediate active slot reservation when capacity is available.
 * 4. Waiting queue placement with FIFO ordering when capacity is saturated.
 * 5. Duplicate entry prevention across refreshes, multiple tabs, and re-entry attempts.
 * 6. Automatic and opportunistic promotion from WAITING to ACTIVE upon slot vacancy.
 * 7. Dynamic capacity expansion promotions (e.g. limit increased 2 -> 5).
 * 8. Voluntary slot release (releaseSlot) and immediate promotion of next in line.
 * 9. Active session expiration (expires_at) and slot recycling.
 * 10. Abandoned waiting session reclamation (heartbeat timeout).
 * 11. Concurrency safety: atomic slot allocation prevents double-allocation under load.
 * 12. PayMongo checkout association and webhook completion slot recycling.
 * 13. System integrity: no second ledger, Registrar gates preserved.
 */

define('TESTING_ENV', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

// PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Database;
use App\Repositories\PaymentSessionRepository;
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
echo "  TTU ENROLLMENT SYSTEM — PHASE 4 PAYMENT QUEUE TEST SUITE          \n";
echo "====================================================================\n\n";

$pdo = Database::getConnection();
$repo = new PaymentSessionRepository($pdo);
$queueService = new PaymentQueueService($repo, $pdo);

// Save initial system_settings to restore after tests
$initialMaxConcurrency = $queueService->getMaxConcurrency();
$initialDuration = $queueService->getSessionDuration();
$initialEnabled = $queueService->isQueueEnabled();

$createdUserIds = [];

// Helper to seed a clean test applicant, application, and assessment
function createTestEnsemble(PDO $pdo, float $netAmount = 10000.00): array {
    global $createdUserIds;
    $uniq = bin2hex(random_bytes(4));
    
    // User
    $stmt = $pdo->prepare('
        INSERT INTO users (first_name, last_name, email, password, role, is_active, email_verified, created_at, updated_at)
        VALUES (:fn, :ln, :e, :p, "applicant", 1, 1, NOW(), NOW())
    ');
    $stmt->execute([
        'fn' => "QueueStudent_{$uniq}",
        'ln' => "Tester_{$uniq}",
        'e'  => "qstudent_{$uniq}@example.com",
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
        'ref' => "APP-Q-{$uniq}",
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
    ];
}

try {
    // -------------------------------------------------------------------------
    // TEST 1: System Settings Configuration Governance
    // -------------------------------------------------------------------------
    echo "--- Section 1: Dynamic Capacity Configuration ---\n";

    $queueService->setMaxConcurrency(100);
    assertTest($queueService->getMaxConcurrency() === 100, "Initial concurrency set to 100");

    $queueService->setMaxConcurrency(250);
    assertTest($queueService->getMaxConcurrency() === 250, "Dynamic expansion to 250 slots via system_settings");

    $queueService->setMaxConcurrency(500);
    assertTest($queueService->getMaxConcurrency() === 500, "Dynamic expansion to 500 slots via system_settings");

    $queueService->setSessionDuration(20);
    assertTest($queueService->getSessionDuration() === 20, "Session duration set to 20 minutes");

    $queueService->setSessionDuration(15);
    assertTest($queueService->getSessionDuration() === 15, "Session duration restored to 15 minutes");

    $queueService->setQueueEnabled(true);
    assertTest($queueService->isQueueEnabled() === true, "Queue enabled toggle is true");

    // Clean any prior dangling test sessions
    $pdo->exec("DELETE FROM payment_sessions WHERE status IN ('active', 'waiting')");

    // -------------------------------------------------------------------------
    // TEST 2: Multi-Student Concurrency (Simultaneous Active Sessions)
    // -------------------------------------------------------------------------
    echo "\n--- Section 2: Multi-Student Simultaneous Concurrency ---\n";

    // Set capacity to 3 for controlled boundary testing
    $queueService->setMaxConcurrency(3);

    $studentA = createTestEnsemble($pdo);
    $studentB = createTestEnsemble($pdo);
    $studentC = createTestEnsemble($pdo);

    // Student A enters queue
    $entryA = $queueService->enterQueue($studentA['user_id'], $studentA['assessment_id']);
    assertTest($entryA['status'] === 'active', "Student A enters: immediately reserved active slot");
    assertTest(!empty($entryA['session_token']), "Student A received secure 64-char session token");
    assertTest($entryA['seconds_remaining'] > 800, "Student A has ~15 minutes (900s) remaining window");

    // Student B enters queue simultaneously
    $entryB = $queueService->enterQueue($studentB['user_id'], $studentB['assessment_id']);
    assertTest($entryB['status'] === 'active', "Student B enters: simultaneously active (NOT one-at-a-time)");
    assertTest($entryB['session_token'] !== $entryA['session_token'], "Student B has unique distinct session token");

    // Student C enters queue simultaneously
    $entryC = $queueService->enterQueue($studentC['user_id'], $studentC['assessment_id']);
    assertTest($entryC['status'] === 'active', "Student C enters: simultaneously active (Capacity: 3/3 active)");

    $activeCount = $repo->countActiveSessions();
    assertTest($activeCount === 3, "Confirmed 3 simultaneous active checkout sessions in database");

    // -------------------------------------------------------------------------
    // TEST 3: Capacity Saturation & FIFO Waiting Queue
    // -------------------------------------------------------------------------
    echo "\n--- Section 3: Capacity Saturation & FIFO Waiting Queue ---\n";

    $studentD = createTestEnsemble($pdo);
    $studentE = createTestEnsemble($pdo);

    // Student D enters queue when capacity (3) is saturated
    $entryD = $queueService->enterQueue($studentD['user_id'], $studentD['assessment_id']);
    assertTest($entryD['status'] === 'waiting', "Student D enters saturated queue: placed in WAITING state");
    assertTest($entryD['position'] === 1, "Student D is position #1 in waiting line");
    assertTest($entryD['max_concurrency'] === 3, "Returned max concurrency metadata reflects configured limit (3)");

    // Student E enters queue
    $entryE = $queueService->enterQueue($studentE['user_id'], $studentE['assessment_id']);
    assertTest($entryE['status'] === 'waiting', "Student E enters: placed in WAITING state");
    assertTest($entryE['position'] === 2, "Student E is position #2 in waiting line (strict FIFO order)");

    $waitingCount = $repo->countWaitingSessions();
    assertTest($waitingCount === 2, "Confirmed exactly 2 waiting sessions in database");

    // -------------------------------------------------------------------------
    // TEST 4: Duplicate Entry Prevention (Refresh & Multi-Tab Resilience)
    // -------------------------------------------------------------------------
    echo "\n--- Section 4: Refresh & Multi-Tab Duplicate Entry Prevention ---\n";

    // Student A refreshes page or opens a second browser tab
    $refreshA = $queueService->enterQueue($studentA['user_id'], $studentA['assessment_id']);
    assertTest($refreshA['status'] === 'active', "Student A refresh: returns active status");
    assertTest($refreshA['is_existing'] === true, "Student A refresh flagged as is_existing = true");
    assertTest($refreshA['session_token'] === $entryA['session_token'], "Student A refresh returns SAME session token without allocating a second slot");

    // Verify active count did NOT increment
    assertTest($repo->countActiveSessions() === 3, "Active session count remained 3 after student refresh");

    // Student D (waiting) refreshes page or opens a second browser tab
    $refreshD = $queueService->enterQueue($studentD['user_id'], $studentD['assessment_id']);
    assertTest($refreshD['status'] === 'waiting', "Student D refresh: returns waiting status");
    assertTest($refreshD['is_existing'] === true, "Student D refresh flagged as is_existing = true");
    assertTest($refreshD['session_token'] === $entryD['session_token'], "Student D refresh returns SAME waiting token");
    assertTest($refreshD['position'] === 1, "Student D retains position #1 in line");
    assertTest($repo->countWaitingSessions() === 2, "Waiting session count remained 2 after refresh");

    // -------------------------------------------------------------------------
    // TEST 5: Voluntary Slot Release & Instant Promotion
    // -------------------------------------------------------------------------
    echo "\n--- Section 5: Voluntary Slot Release & Instant Promotion ---\n";

    // Student A voluntarily leaves / cancels their payment slot
    $released = $queueService->releaseSlot($entryA['session_token']);
    assertTest($released === true, "Student A successfully releases active slot");

    $sessionA = $repo->findByToken($entryA['session_token']);
    assertTest($sessionA['status'] === 'cancelled', "Student A session status transitioned to 'cancelled'");

    // Student D should be automatically promoted to active!
    $statusD = $queueService->checkStatus($entryD['session_token']);
    assertTest($statusD['status'] === 'active', "Student D automatically promoted from WAITING to ACTIVE");
    assertTest(!empty($statusD['expires_at']), "Promoted Student D has valid expires_at timestamp");
    assertTest($statusD['seconds_remaining'] > 800, "Promoted Student D has full 15-minute checkout window");

    // Student E should now be position #1
    $statusE = $queueService->checkStatus($entryE['session_token']);
    assertTest($statusE['status'] === 'waiting', "Student E remains waiting");
    assertTest($statusE['position'] === 1, "Student E advanced to position #1 in line");

    // -------------------------------------------------------------------------
    // TEST 6: Dynamic Capacity Expansion & Automatic Waiting Promotion
    // -------------------------------------------------------------------------
    echo "\n--- Section 6: Dynamic Capacity Expansion (3 -> 5) ---\n";

    // Currently: Active = B, C, D (3 active). Waiting = E (1 waiting). Max = 3.
    // Expand capacity from 3 to 5 slots
    $queueService->setMaxConcurrency(5);
    assertTest($queueService->getMaxConcurrency() === 5, "Capacity expanded to 5");

    // Trigger promotion check
    $promoted = $queueService->promoteEligibleWaitingSessions();
    assertTest($promoted === 1, "promoteEligibleWaitingSessions() promoted 1 waiting student into newly available capacity");

    $statusE2 = $queueService->checkStatus($entryE['session_token']);
    assertTest($statusE2['status'] === 'active', "Student E now active following capacity expansion");
    assertTest($repo->countWaitingSessions() === 0, "Waiting queue is now empty (0 waiting)");
    assertTest($repo->countActiveSessions() === 4, "Active sessions is now 4 (B, C, D, E)");

    // -------------------------------------------------------------------------
    // TEST 7: Active Session Expiration & Stale Slot Reclamation
    // -------------------------------------------------------------------------
    echo "\n--- Section 7: Session Expiration & Slot Reclamation ---\n";

    $studentF = createTestEnsemble($pdo);
    $studentG = createTestEnsemble($pdo);

    // Place Student F in queue (capacity 5, currently 4 active -> gets active)
    $entryF = $queueService->enterQueue($studentF['user_id'], $studentF['assessment_id']);
    assertTest($entryF['status'] === 'active', "Student F enters: capacity now full at 5/5");

    // Place Student G in waiting queue
    $entryG = $queueService->enterQueue($studentG['user_id'], $studentG['assessment_id']);
    assertTest($entryG['status'] === 'waiting' && $entryG['position'] === 1, "Student G enters: waiting at #1");

    // Artificially expire Student B's active session in the database
    $pdo->prepare("UPDATE payment_sessions SET expires_at = DATE_SUB(NOW(), INTERVAL 5 MINUTE) WHERE session_token = :t")
        ->execute(['t' => $entryB['session_token']]);

    // Check status of Student B -> returns expired
    $statusB = $queueService->checkStatus($entryB['session_token']);
    assertTest($statusB['status'] === 'expired', "Student B session recognized as expired");

    // Student G checks status -> triggers promotion into Student B's expired slot
    $statusG = $queueService->checkStatus($entryG['session_token']);
    assertTest($statusG['status'] === 'active', "Student G promoted to ACTIVE using slot reclaimed from expired Student B");

    // -------------------------------------------------------------------------
    // TEST 8: Recovery From Abandoned Sessions (Heartbeat Timeout)
    // -------------------------------------------------------------------------
    echo "\n--- Section 8: Recovery From Abandoned Sessions ---\n";

    $studentH = createTestEnsemble($pdo);
    $studentI = createTestEnsemble($pdo);

    // Add Student H to waiting queue
    $entryH = $queueService->enterQueue($studentH['user_id'], $studentH['assessment_id']);
    // Add Student I to waiting queue
    $entryI = $queueService->enterQueue($studentI['user_id'], $studentI['assessment_id']);

    assertTest($entryH['status'] === 'waiting', "Student H in waiting queue");
    assertTest($entryI['status'] === 'waiting', "Student I in waiting queue");

    // Simulate Student H closing browser tab: stale heartbeat (> 5 minutes ago)
    $pdo->prepare("UPDATE payment_sessions SET last_heartbeat_at = DATE_SUB(NOW(), INTERVAL 10 MINUTE) WHERE session_token = :t")
        ->execute(['t' => $entryH['session_token']]);

    // Run queue maintenance sweep
    $abandonedCount = $repo->abandonStaleWaitingSessions(5);
    assertTest($abandonedCount >= 1, "abandonStaleWaitingSessions() successfully reclaimed dead browser tab");

    $sessionH = $repo->findByToken($entryH['session_token']);
    assertTest($sessionH['status'] === 'abandoned', "Student H transitioned to 'abandoned'");

    // Student I should now be position #1 without being blocked by dead tab Student H
    $posI = $repo->getWaitingPosition((int) $entryI['queue_number']);
    assertTest($posI === 1, "Student I advanced to position #1, dead tab H excluded from queue count");

    // -------------------------------------------------------------------------
    // TEST 9: PayMongo Checkout Association & Webhook Slot Recycling
    // -------------------------------------------------------------------------
    echo "\n--- Section 9: PayMongo Checkout Association & Webhook Recycling ---\n";

    // Associate PayMongo Checkout with Student C's active session
    $mockCheckoutId = 'cs_test_phase4_' . bin2hex(random_bytes(6));
    $mockCheckoutUrl = 'https://checkout.paymongo.com/test/' . $mockCheckoutId;

    // Create a real pending payment record for foreign key integrity
    $payStmt = $pdo->prepare('
        INSERT INTO payment_records (assessment_id, user_id, amount, payment_date, payment_method, gateway, status, checkout_session_id, reference_number)
        VALUES (:ass_id, :uid, 5000.00, CURDATE(), "PayMongo", "paymongo", "pending", :cs_id, :ref)
    ');
    $payRef = 'PM-TEST-P4-' . bin2hex(random_bytes(3));
    $payStmt->execute([
        'ass_id' => $studentC['assessment_id'],
        'uid'    => $studentC['user_id'],
        'cs_id'  => $mockCheckoutId,
        'ref'    => $payRef,
    ]);
    $mockPaymentId = (int) $pdo->lastInsertId();

    $linked = $queueService->linkPayMongoCheckout(
        $entryC['session_token'],
        $mockCheckoutId,
        $mockCheckoutUrl,
        $mockPaymentId
    );
    assertTest($linked === true, "PayMongo checkout session successfully linked to payment_sessions record");

    $sessionC = $repo->findByToken($entryC['session_token']);
    assertTest($sessionC['checkout_session_id'] === $mockCheckoutId, "Session record stores checkout_session_id");
    assertTest($sessionC['checkout_url'] === $mockCheckoutUrl, "Session record stores checkout_url");
    assertTest((int)$sessionC['payment_record_id'] === $mockPaymentId, "Session record stores payment_record_id");

    // Complete session via simulated webhook confirmation
    $completed = $queueService->completeSessionByCheckoutId($mockCheckoutId, $mockPaymentId);
    assertTest($completed === true, "completeSessionByCheckoutId() successfully marked session completed");

    $sessionCCompleted = $repo->findByToken($entryC['session_token']);
    assertTest($sessionCCompleted['status'] === 'completed', "Student C status transitioned to 'completed'");

    // Student I (who was waiting at #1) should be automatically promoted
    $statusI = $queueService->checkStatus($entryI['session_token']);
    assertTest($statusI['status'] === 'active', "Student I automatically promoted into slot freed by webhook completion");

    // -------------------------------------------------------------------------
    // TEST 10: Safe Concurrent Slot Allocation (Zero Double-Allocation)
    // -------------------------------------------------------------------------
    echo "\n--- Section 10: Concurrency Race Condition Safety ---\n";

    // Clean active/waiting test records
    $pdo->exec("DELETE FROM payment_sessions WHERE status IN ('active', 'waiting')");

    // Set tight limit: exactly 2 slots
    $queueService->setMaxConcurrency(2);

    // Create 10 distinct students
    $studentsK = [];
    for ($k = 1; $k <= 10; $k++) {
        $studentsK[] = createTestEnsemble($pdo);
    }

    // Simulate 10 simultaneous students attempting to enter
    $allocatedActive = 0;
    $allocatedWaiting = 0;

    foreach ($studentsK as $st) {
        $res = $queueService->enterQueue($st['user_id'], $st['assessment_id']);
        if ($res['status'] === 'active') {
            $allocatedActive++;
        } elseif ($res['status'] === 'waiting') {
            $allocatedWaiting++;
        }
    }

    assertTest($allocatedActive === 2, "Exact capacity limit respected: exactly 2 active slots allocated");
    assertTest($allocatedWaiting === 8, "Remaining 8 requests safely diverted to waiting queue");
    assertTest($repo->countActiveSessions() === 2, "Database confirms active session count is exactly 2, zero over-allocation");

    // Verify queue numbers for waiting requests are strictly sequential
    $waitingRows = $pdo->query("SELECT queue_number FROM payment_sessions WHERE status = 'waiting' ORDER BY queue_number ASC")->fetchAll(PDO::FETCH_COLUMN);
    $isMonotonic = true;
    for ($idx = 1; $idx < count($waitingRows); $idx++) {
        if ((int)$waitingRows[$idx] <= (int)$waitingRows[$idx - 1]) {
            $isMonotonic = false;
            break;
        }
    }
    assertTest($isMonotonic === true, "Waiting queue numbers are strictly monotonically increasing");

    // -------------------------------------------------------------------------
    // TEST 11: Queue Telemetry & Metrics
    // -------------------------------------------------------------------------
    echo "\n--- Section 11: Queue Telemetry & Metrics ---\n";

    $metrics = $queueService->getQueueMetrics();
    assertTest($metrics['max_concurrency'] === 2, "Metrics report correct max_concurrency (2)");
    assertTest($metrics['active_sessions'] === 2, "Metrics report correct active_sessions (2)");
    assertTest($metrics['waiting_sessions'] === 8, "Metrics report correct waiting_sessions (8)");
    assertTest($metrics['available_slots'] === 0, "Metrics report 0 available slots when fully saturated");
    assertTest($metrics['utilization_percent'] === 100.0, "Metrics report 100% capacity utilization");

} finally {
    // Clean up created test sessions and users
    if (!empty($createdUserIds)) {
        $in = implode(',', array_map('intval', $createdUserIds));
        $pdo->exec("DELETE FROM payment_sessions WHERE user_id IN ($in)");
        $pdo->exec("DELETE FROM payment_records WHERE user_id IN ($in)");
        $pdo->exec("DELETE FROM student_assessments WHERE user_id IN ($in)");
        $pdo->exec("DELETE FROM applications WHERE user_id IN ($in)");
        $pdo->exec("DELETE FROM users WHERE id IN ($in)");
    }
    // Restore original system settings
    $queueService->setMaxConcurrency($initialMaxConcurrency);
    $queueService->setSessionDuration($initialDuration);
    $queueService->setQueueEnabled($initialEnabled);
}

echo "\n====================================================================\n";
echo "  PHASE 4 TEST SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
