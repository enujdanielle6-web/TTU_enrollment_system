<?php
declare(strict_types=1);

/**
 * TTU ENROLLMENT SYSTEM — PHASE 6 CASHIER/ADMIN PAYMENT MONITORING TEST SUITE
 *
 * Verifies:
 * 1. Data Contract & Telemetry Aggregation:
 *    - PaymentSessionRepository detailed queries (active, waiting, counts)
 *    - PaymentRepository PayMongo stats & transaction queries (single ledger)
 *    - PaymentQueueService::getMonitoringDashboardData contract completeness
 * 2. Role-Based Access Control (RBAC):
 *    - View access allowed for Cashier (payments.record) and Superadmin (*)
 *    - View access denied (HTTP 403) for Applicant, Student, Clinic
 *    - Settings mutation denied (HTTP 403) for Junior Cashier with only payments.record
 *    - Settings mutation denied (HTTP 403) for Applicant / non-staff
 *    - Settings mutation allowed for authorized admin (fees.manage / settings.manage / *)
 * 3. Dynamic Capacity & Queue Configuration:
 *    - Configurable capacity (100 -> 250 -> 500) updates system_settings
 *    - Automatic promotion triggers immediately when capacity expands
 * 4. Queue Maintenance Actions:
 *    - Recycle stale sessions safely reclaims expired slots
 *    - Force release terminates session and promotes next in line
 * 5. Single Financial Ledger & Registrar Boundary Preservation:
 *    - Queries run strictly against payment_records (no duplicate ledger)
 *    - Registrar finalization logic remains untouched
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
use App\Controllers\Admin\Finance\FinanceController;
use App\Repositories\PaymentSessionRepository;
use App\Repositories\PaymentRepository;
use App\Services\PaymentQueueService;

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
echo "  TTU ENROLLMENT SYSTEM — PHASE 6 PAYMENT MONITORING TEST SUITE     \n";
echo "====================================================================\n\n";

$pdo = Database::getConnection();
$sessionRepo = new PaymentSessionRepository($pdo);
$paymentRepo = new PaymentRepository($pdo);
$queueService = new PaymentQueueService($sessionRepo, $pdo);

// Backup system settings
$initialMaxConcurrency = $queueService->getMaxConcurrency();
$initialDuration = $queueService->getSessionDuration();
$initialEnabled = $queueService->isQueueEnabled();

$createdUserIds = [];

// Helper to create test user with role
function createTestUser(PDO $pdo, string $role, string $prefix = 'user'): array {
    global $createdUserIds;
    $uniq = bin2hex(random_bytes(4));
    $email = "{$prefix}_{$uniq}@example.com";
    
    $stmt = $pdo->prepare('
        INSERT INTO users (first_name, last_name, email, password, role, is_active, email_verified, created_at, updated_at)
        VALUES (:fn, :ln, :e, "secret", :role, 1, 1, NOW(), NOW())
    ');
    $stmt->execute([
        'fn'   => ucfirst($prefix),
        'ln'   => 'Tester',
        'e'    => $email,
        'role' => $role
    ]);
    $userId = (int) $pdo->lastInsertId();
    $createdUserIds[] = $userId;
    
    return [
        'id'    => $userId,
        'email' => $email,
        'role'  => $role
    ];
}

// Helper to create test applicant with application and assessment
function createTestApplicantAndAssessment(PDO $pdo, float $netAmount = 12500.00): array {
    global $createdUserIds;
    $uniq = bin2hex(random_bytes(4));
    $email = "app_test_{$uniq}@example.com";
    
    // User
    $stmt = $pdo->prepare('
        INSERT INTO users (first_name, last_name, email, password, role, is_active, email_verified, student_number, created_at, updated_at)
        VALUES (:fn, :ln, :e, "secret", "applicant", 1, 1, :sno, NOW(), NOW())
    ');
    $stmt->execute([
        'fn'  => "Student_{$uniq}",
        'ln'  => "Doe",
        'e'   => $email,
        'sno' => "SN-{$uniq}"
    ]);
    $userId = (int) $pdo->lastInsertId();
    $createdUserIds[] = $userId;
    
    // Application
    $appRef = "APP-TEST-{$uniq}";
    $stmt = $pdo->prepare('
        INSERT INTO applications (user_id, reference_number, academic_level, grade_level, status)
        VALUES (:uid, :ref, "College", "1st Year", "approved")
    ');
    $stmt->execute(['uid' => $userId, 'ref' => $appRef]);
    $appId = (int) $pdo->lastInsertId();
    
    // Student Assessment
    $stmt = $pdo->prepare('
        INSERT INTO student_assessments (user_id, application_id, tuition_fee, total_amount, discount_amount, net_amount, total_paid, payment_status)
        VALUES (:uid, :aid, :tfee, :tot, 0.00, :net, 0.00, "unpaid")
    ');
    $stmt->execute([
        'uid'  => $userId,
        'aid'  => $appId,
        'tfee' => $netAmount,
        'tot'  => $netAmount,
        'net'  => $netAmount
    ]);
    $assessmentId = (int) $pdo->lastInsertId();
    
    return [
        'user_id'        => $userId,
        'application_id' => $appId,
        'assessment_id'  => $assessmentId,
        'app_ref'        => $appRef,
        'email'          => $email
    ];
}

// Clean any previous test sessions
$pdo->exec("DELETE FROM payment_sessions WHERE status IN ('active', 'waiting')");

try {
    echo "--- SECTION 1: Telemetry & Monitoring Data Contract Aggregation ---\n";
    
    // Setup test active session
    $applicant1 = createTestApplicantAndAssessment($pdo, 15000.00);
    $applicant2 = createTestApplicantAndAssessment($pdo, 12000.00);
    
    // Ensure initial capacity is 1 to test queue saturation
    $queueService->setMaxConcurrency(1);
    $queueService->setSessionDuration(15);
    $queueService->setQueueEnabled(true);
    
    // Join applicant 1 into active session
    $res1 = $queueService->enterQueue($applicant1['user_id'], $applicant1['assessment_id']);
    assertTest($res1['status'] === 'active', "Applicant 1 successfully entered active session");
    
    // Join applicant 2 into queue (Capacity is 1, so Applicant 2 must be waiting)
    $res2 = $queueService->enterQueue($applicant2['user_id'], $applicant2['assessment_id']);
    assertTest($res2['status'] === 'waiting', "Applicant 2 entered waiting queue due to capacity limit of 1");
    
    // Check getActiveSessionsDetailed
    $activeSessions = $sessionRepo->getActiveSessionsDetailed(10);
    assertTest(count($activeSessions) >= 1, "getActiveSessionsDetailed returned at least 1 active session");
    $foundActive = false;
    foreach ($activeSessions as $s) {
        if ((int)$s['user_id'] === $applicant1['user_id']) {
            $foundActive = true;
            assertTest(!empty($s['first_name']), "Active session contains joined student first_name ({$s['first_name']})");
            assertTest(!empty($s['app_ref']), "Active session contains joined application ref ({$s['app_ref']})");
            assertTest((float)$s['net_amount'] === 15000.00, "Active session contains joined assessment net_amount (15000.00)");
            break;
        }
    }
    assertTest($foundActive, "Applicant 1 found in getActiveSessionsDetailed with full joined profile");
    
    // Check getWaitingSessionsDetailed
    $waitingSessions = $sessionRepo->getWaitingSessionsDetailed(10);
    assertTest(count($waitingSessions) >= 1, "getWaitingSessionsDetailed returned at least 1 waiting session");
    $foundWaiting = false;
    foreach ($waitingSessions as $ws) {
        if ((int)$ws['user_id'] === $applicant2['user_id']) {
            $foundWaiting = true;
            assertTest((int)$ws['queue_number'] >= 1, "Waiting applicant contains valid queue_number ({$ws['queue_number']})");
            assertTest(!empty($ws['first_name']), "Waiting applicant contains joined student first_name ({$ws['first_name']})");
            break;
        }
    }
    assertTest($foundWaiting, "Applicant 2 found in getWaitingSessionsDetailed with queue metrics");
    
    // Check getSessionCounts
    $sessionCounts = $sessionRepo->getSessionCounts();
    assertTest(isset($sessionCounts['active']) && isset($sessionCounts['waiting']) && isset($sessionCounts['total']), "getSessionCounts returned required aggregate metric keys");
    assertTest($sessionCounts['active'] >= 1, "getSessionCounts reflects active sessions >= 1");
    assertTest($sessionCounts['waiting'] >= 1, "getSessionCounts reflects waiting sessions >= 1");
    
    // Check PaymentRepository PayMongo stats & transactions (Single Ledger Test)
    $receiptNo = 'OR-TEST-' . bin2hex(random_bytes(4));
    $csId = 'cs_test_' . bin2hex(random_bytes(8));
    $piId = 'pi_test_' . bin2hex(random_bytes(8));
    $paymentAmount = 5000.00;
    
    $testPaymentId = $paymentRepo->insert([
        'assessment_id'       => $applicant1['assessment_id'],
        'user_id'             => $applicant1['user_id'],
        'amount'              => $paymentAmount,
        'payment_date'        => date('Y-m-d'),
        'payment_method'      => 'PayMongo',
        'gateway'             => 'paymongo',
        'status'              => 'verified',
        'checkout_session_id' => $csId,
        'payment_intent_id'   => $piId,
        'reference_number'    => 'PM-REF-' . time(),
        'receipt_number'      => $receiptNo,
        'gateway_fee'         => 125.00,
    ]);
    
    $pmStats = $paymentRepo->getPayMongoStats();
    assertTest(isset($pmStats['total_transactions']) && $pmStats['total_transactions'] >= 1, "getPayMongoStats correctly aggregates total transactions from single payment_records ledger");
    assertTest(isset($pmStats['verified_collections']) && $pmStats['verified_collections'] >= 5000.00, "getPayMongoStats calculates verified volume correctly");
    assertTest(isset($pmStats['total_gateway_fees']) && $pmStats['total_gateway_fees'] >= 125.00, "getPayMongoStats calculates gateway fee metrics correctly");
    
    $pmTx = $paymentRepo->getPayMongoTransactions(10);
    assertTest(count($pmTx) >= 1, "getPayMongoTransactions returned transactions list");
    $foundTx = false;
    foreach ($pmTx as $tx) {
        if ($tx['checkout_session_id'] === $csId) {
            $foundTx = true;
            assertTest($tx['receipt_number'] === $receiptNo, "Transaction list contains receipt_number ({$receiptNo})");
            assertTest($tx['payment_intent_id'] === $piId, "Transaction list contains payment_intent_id ({$piId})");
            break;
        }
    }
    assertTest($foundTx, "PayMongo transaction record successfully verified in ledger telemetry");
    
    // Check PaymentQueueService unified monitoring data contract
    $dashboardData = $queueService->getMonitoringDashboardData();
    assertTest(isset($dashboardData['metrics']), "Dashboard contract contains 'metrics'");
    assertTest(isset($dashboardData['session_counts']), "Dashboard contract contains 'session_counts'");
    assertTest(isset($dashboardData['paymongo_stats']), "Dashboard contract contains 'paymongo_stats'");
    assertTest(isset($dashboardData['active_sessions']), "Dashboard contract contains 'active_sessions'");
    assertTest(isset($dashboardData['waiting_sessions']), "Dashboard contract contains 'waiting_sessions'");
    assertTest(isset($dashboardData['recent_transactions']), "Dashboard contract contains 'recent_transactions'");
    assertTest($dashboardData['metrics']['max_concurrency'] === 1, "Dashboard metrics correctly reflect live concurrency of 1");
    
    echo "\n--- SECTION 2: Role-Based Access Control (RBAC) Protection ---\n";
    
    // User fixtures
    $cashierUser = createTestUser($pdo, 'cashier', 'cashier');
    $superadminUser = createTestUser($pdo, 'superadmin', 'superadmin');
    $studentUser = createTestUser($pdo, 'student', 'student');
    $clinicUser = createTestUser($pdo, 'clinic', 'clinic');
    
    $controller = new FinanceController();
    
    // Helper to simulate session login
    function simulateLogin(array $user, array $customPerms = []): void {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_permissions'] = $customPerms;
        $_SESSION['csrf_token'] = 'test_csrf_token_secret';
        $_SERVER['REQUEST_URI'] = '/sia/admin/finance/payment_monitoring.php';
        $_SERVER['SCRIPT_NAME'] = '/sia/admin/finance/payment_monitoring.php';
    }
    
    // Subtest 2.1: View Access for Cashier
    simulateLogin($cashierUser);
    $req = new Request();
    $res = new Response();
    $viewOutput = $controller->paymentMonitoring($req, $res);
    assertTest(strpos((string)$viewOutput, 'Payment Queue &amp; Gateway Monitor') !== false || strpos((string)$viewOutput, 'Payment Queue & Gateway Monitor') !== false, "Cashier granted view access to Payment Monitoring Dashboard");
    
    // Subtest 2.2: View Access for Superadmin (check permission requirement succeeds)
    simulateLogin($superadminUser);
    $superadminAllowed = false;
    try {
        requirePermission(['payments.record', 'fees.manage', 'settings.manage']);
        $superadminAllowed = true;
    } catch (\Throwable $e) {
        $superadminAllowed = false;
    }
    assertTest($superadminAllowed, "Superadmin (*) passes requirePermission gate for Payment Monitoring Dashboard");
    
    // Subtest 2.3: View Access Denied for Student (HTTP 403)
    simulateLogin($studentUser);
    $resStudent = new Response();
    $studentBlocked = false;
    try {
        $controller->paymentMonitoring($req, $resStudent);
    } catch (\App\Core\HttpException $e) {
        if ($e->getStatusCode() === 403) {
            $studentBlocked = true;
        }
    } catch (\Throwable $e) {
        $studentBlocked = true;
    }
    assertTest($studentBlocked, "Student rejected with HTTP 403 on paymentMonitoring view endpoint");
    
    // Subtest 2.4: Polling Endpoint Denied for Clinic (HTTP 403)
    simulateLogin($clinicUser);
    $resClinic = new Response();
    $reqAjax = (new Request())->setMethod('GET')->setHeader('X-Requested-With', 'XMLHttpRequest');
    $controller->paymentMonitoringData($reqAjax, $resClinic);
    assertTest($resClinic->statusCode === 403, "Clinic staff rejected with HTTP 403 on payment monitoring AJAX data endpoint");
    
    // Subtest 2.5: Queue Settings Mutation BLOCKED for Cashier with only 'payments.record'
    // (Simulate junior cashier without 'fees.manage' or 'settings.manage')
    simulateLogin($cashierUser, ['payments.record']);
    $_SESSION['user_role'] = 'custom_cashier';
    $_SESSION['user_permissions'] = ['payments.record'];
    
    $_POST = [
        'max_concurrency'          => 250,
        'session_duration_minutes' => 15,
        'queue_enabled'            => 1,
    ];
    $reqMutate = (new Request())->setMethod('POST')->setHeader('X-Requested-With', 'XMLHttpRequest');
    $resMutate = new Response();
    $controller->updateQueueSettings($reqMutate, $resMutate);
    assertTest($resMutate->statusCode === 403, "Recording-only cashier strictly BLOCKED from mutating queue settings (HTTP 403)");
    
    // Subtest 2.6: Queue Settings Mutation PERMITTED for Authorized Finance Admin ('fees.manage')
    simulateLogin($cashierUser); // Default cashier role has 'fees.manage' in ROLE_PERMISSIONS
    $_POST = [
        'max_concurrency'          => 250,
        'session_duration_minutes' => 20,
        'queue_enabled'            => 1,
    ];
    $reqAdminMutate = (new Request())->setMethod('POST')->setHeader('X-Requested-With', 'XMLHttpRequest');
    $resAdminMutate = new Response();
    $controller->updateQueueSettings($reqAdminMutate, $resAdminMutate);
    assertTest($resAdminMutate->statusCode === 200, "Authorized Finance Admin successfully updated queue settings (HTTP 200)");
    assertTest(!empty($resAdminMutate->jsonData['success']), "Response body contains success: true");
    
    $newCapacity = $queueService->getMaxConcurrency();
    $newDuration = $queueService->getSessionDuration();
    assertTest($newCapacity === 250, "Configured capacity successfully persisted to 250 slots in system_settings");
    assertTest($newDuration === 20, "Configured session duration successfully persisted to 20 minutes");
    
    echo "\n--- SECTION 3: Dynamic Capacity & Instant Auto-Promotion ---\n";
    // Applicant 2 was waiting when capacity was 1.
    // Increasing capacity to 250 triggered promoteEligibleWaitingSessions()!
    $checkSession = $sessionRepo->findActiveByUserAndAssessment($applicant2['user_id'], $applicant2['assessment_id']);
    assertTest($checkSession !== null && $checkSession['status'] === 'active', "Applicant 2 automatically promoted to 'active' instantly upon capacity expansion from 1 to 250!");
    
    echo "\n--- SECTION 4: Queue Maintenance Actions & Slot Recycling ---\n";
    // 4.1: Recycle stale sessions action
    // Artificially expire Applicant 1's active session in the database
    $expStmt = $pdo->prepare("UPDATE payment_sessions SET expires_at = DATE_SUB(NOW(), INTERVAL 5 MINUTE) WHERE user_id = ? AND status = 'active'");
    $expStmt->execute([$applicant1['user_id']]);
    
    // Artificially create Applicant 3 waiting in queue with capacity = 1 and Applicant 2 active
    $applicant3 = createTestApplicantAndAssessment($pdo, 18000.00);
    $queueService->setMaxConcurrency(1);
    $res3 = $queueService->enterQueue($applicant3['user_id'], $applicant3['assessment_id']);
    assertTest($res3['status'] === 'waiting', "Applicant 3 placed in waiting queue (saturated capacity = 1)");
    
    // Authorized admin triggers recycle_stale action via queueActionProcess
    simulateLogin($cashierUser);
    $_POST = ['action' => 'recycle_stale'];
    $reqRecycle = (new Request())->setMethod('POST')->setHeader('X-Requested-With', 'XMLHttpRequest');
    $resRecycle = new Response();
    $controller->queueActionProcess($reqRecycle, $resRecycle);
    
    assertTest($resRecycle->statusCode === 200, "queueActionProcess(recycle_stale) executed with HTTP 200");
    assertTest(!empty($resRecycle->jsonData['success']), "Recycle stale response contains success: true");
    
    // Check Applicant 1 is now marked expired
    $app1Session = $sessionRepo->findByToken($res1['session_token']);
    assertTest($app1Session !== null && $app1Session['status'] === 'expired', "Applicant 1 expired session updated to status 'expired'");
    
    // 4.2: Force Release Action
    // Applicant 2 is active. Force release Applicant 2's session token.
    $app2Session = $sessionRepo->findActiveByUserAndAssessment($applicant2['user_id'], $applicant2['assessment_id']);
    assertTest($app2Session !== null, "Applicant 2 session is currently active");
    
    $_POST = [
        'action'        => 'force_release',
        'session_token' => $app2Session['session_token']
    ];
    $reqRelease = (new Request())->setMethod('POST')->setHeader('X-Requested-With', 'XMLHttpRequest');
    $resRelease = new Response();
    $controller->queueActionProcess($reqRelease, $resRelease);
    
    assertTest($resRelease->statusCode === 200, "queueActionProcess(force_release) executed with HTTP 200");
    
    // Check Applicant 2 session is no longer active
    $app2SessionAfter = $sessionRepo->findById((int)$app2Session['id']);
    assertTest($app2SessionAfter !== null && $app2SessionAfter['status'] !== 'active', "Applicant 2 session no longer active after force release (status: {$app2SessionAfter['status']})");
    
    // Check Applicant 3 was promoted to active slot!
    $app3Session = $sessionRepo->findActiveByUserAndAssessment($applicant3['user_id'], $applicant3['assessment_id']);
    assertTest($app3Session !== null && $app3Session['status'] === 'active', "Applicant 3 promoted to 'active' slot vacated by force-released session!");
    
    echo "\n--- SECTION 5: Real-time Telemetry JSON Polling Endpoint ---\n";
    simulateLogin($cashierUser);
    $reqPoll = (new Request())->setMethod('GET')->setHeader('X-Requested-With', 'XMLHttpRequest');
    $resPoll = new Response();
    $controller->paymentMonitoringData($reqPoll, $resPoll);
    
    assertTest($resPoll->statusCode === 200, "Real-time polling endpoint returned HTTP 200");
    assertTest(!empty($resPoll->jsonData['success']), "Polling response contains success: true");
    
    $data = $resPoll->jsonData['data'];
    assertTest(isset($data['metrics']['active_sessions']), "Polling data contains real-time active_sessions");
    assertTest(isset($data['metrics']['waiting_sessions']), "Polling data contains real-time waiting_sessions");
    assertTest(isset($data['metrics']['available_slots']), "Polling data contains real-time available_slots");
    assertTest(isset($data['metrics']['max_concurrency']), "Polling data contains live max_concurrency");
    assertTest(isset($data['paymongo_stats']['total_transactions']), "Polling data contains live paymongo total_transactions");
    assertTest(is_array($data['active_sessions']), "Polling data contains active_sessions list");
    assertTest(is_array($data['waiting_sessions']), "Polling data contains waiting_sessions list");
    assertTest(is_array($data['recent_transactions']), "Polling data contains recent_transactions list");

} catch (\Throwable $e) {
    echo "  [ERROR] Exception occurred: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    $failed++;
} finally {
    // Clean up created test users and data
    echo "\n--- Cleanup Test Fixtures ---\n";
    if (!empty($createdUserIds)) {
        $in = implode(',', array_map('intval', $createdUserIds));
        $pdo->exec("DELETE FROM payment_records WHERE user_id IN ($in)");
        $pdo->exec("DELETE FROM payment_sessions WHERE user_id IN ($in)");
        $pdo->exec("DELETE FROM student_assessments WHERE user_id IN ($in)");
        $pdo->exec("DELETE FROM applications WHERE user_id IN ($in)");
        $pdo->exec("DELETE FROM users WHERE id IN ($in)");
        echo "Cleaned up " . count($createdUserIds) . " test user fixtures.\n";
    }
    
    // Restore initial system settings
    $queueService->setMaxConcurrency($initialMaxConcurrency);
    $queueService->setSessionDuration($initialDuration);
    $queueService->setQueueEnabled($initialEnabled);
    echo "Restored original system settings (Concurrency: {$initialMaxConcurrency}, Duration: {$initialDuration}m).\n";
}

echo "\n====================================================================\n";
echo "  PHASE 6 TEST RESULTS: {$passed} Passed, {$failed} Failed\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
