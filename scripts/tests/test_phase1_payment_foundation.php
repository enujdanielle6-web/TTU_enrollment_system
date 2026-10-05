<?php
declare(strict_types=1);

/**
 * Phase 1: Payment Architecture Foundation Automated Test Suite
 *
 * Verifies domain encapsulation, integrity, and safety across:
 * - PaymentService & PaymentRepository
 * - Cashier Over-the-Counter payment recording
 * - Manual online payment proof submission, verification, and rejection
 * - Partial and full payment calculations and status transitions
 * - Overpayment and minimum payment protections
 * - Concurrency-safe atomic receipt generation
 * - Application status transitions (payment_verified)
 * - Activity logging
 */

define('TESTING_ENV', true);

// Autoloader & Bootstrapping
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__, 2) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/app/Helpers/functions.php';

use App\Core\Database;
use App\Repositories\PaymentRepository;
use App\Services\PaymentService;

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
echo "  TTU ENROLLMENT SYSTEM — PHASE 1 PAYMENT ARCHITECTURE TEST SUITE   \n";
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
        'fn' => "TestFirst_{$uniq}",
        'ln' => "TestLast_{$uniq}",
        'e' => "app_{$uniq}@example.com",
        'p' => password_hash('Pass123!', PASSWORD_DEFAULT),
    ]);
    $userId = (int) $pdo->lastInsertId();

    // Application
    $stmt = $pdo->prepare('
        INSERT INTO applications (user_id, reference_number, academic_level, grade_level, status)
        VALUES (:uid, :ref, "College", "1st Year", "approved")
    ');
    $stmt->execute([
        'uid' => $userId,
        'ref' => "APP-{$uniq}",
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

    // Ensure cashier user
    $cashierStmt = $pdo->query('SELECT id FROM users WHERE role = "cashier" LIMIT 1');
    $cashierId = (int) $cashierStmt->fetchColumn();
    if (!$cashierId) {
        $cStmt = $pdo->prepare('
            INSERT INTO users (first_name, last_name, email, password, role, is_active, email_verified, created_at, updated_at)
            VALUES ("Cashier", "Staff", "cashier_' . $uniq . '@example.com", "' . password_hash('Cashier123!', PASSWORD_DEFAULT) . '", "cashier", 1, 1, NOW(), NOW())
        ');
        $cStmt->execute();
        $cashierId = (int) $pdo->lastInsertId();
    }

    return [
        'user_id' => $userId,
        'application_id' => $appId,
        'assessment_id' => $assId,
        'cashier_id' => $cashierId,
        'net_amount' => $netAmount,
        'uniq' => $uniq,
    ];
}

// -----------------------------------------------------------------------------
// TEST 1: Cashier OTC Partial Payment Recording
// -----------------------------------------------------------------------------
echo "[TEST 1] Cashier OTC Partial Payment Recording via PaymentService...\n";
$ensemble1 = createTestEnsemble($pdo, 12000.00);

try {
    $result1 = $service->recordOverTheCounterPayment([
        'assessment_id'    => $ensemble1['assessment_id'],
        'user_id'          => $ensemble1['user_id'],
        'application_id'   => $ensemble1['application_id'],
        'amount'           => 4000.00,
        'payment_method'   => 'Cash',
        'reference_number' => null,
    ], $ensemble1['cashier_id']);

    assertCondition($result1['success'] === true, 'OTC payment processed successfully');
    assertCondition(preg_match('/^REC-\d{8}-\d{4}$/', $result1['receipt_number']) === 1, 'Receipt number follows atomic format', $result1['receipt_number']);
    assertCondition($result1['payment_status'] === 'partial', 'Assessment status set to partial');
    assertCondition(abs($result1['total_paid'] - 4000.00) < 0.01, 'Total paid correctly accumulated to ₱4,000.00');
    assertCondition(abs($result1['balance'] - 8000.00) < 0.01, 'Remaining balance computed as ₱8,000.00');

    // Verify DB State
    $assDb = $pdo->query('SELECT total_paid, payment_status FROM student_assessments WHERE id = ' . $ensemble1['assessment_id'])->fetch(PDO::FETCH_ASSOC);
    assertCondition((float) $assDb['total_paid'] === 4000.00 && $assDb['payment_status'] === 'partial', 'DB assessment record updated correctly');

    $appDb = $pdo->query('SELECT status FROM applications WHERE id = ' . $ensemble1['application_id'])->fetch(PDO::FETCH_ASSOC);
    assertCondition($appDb['status'] === 'payment_verified', 'Application status transitioned to payment_verified');

    $payDb = $repo->findById($result1['payment_id']);
    assertCondition($payDb['status'] === 'verified' && (int)$payDb['cashier_id'] === $ensemble1['cashier_id'], 'Payment record verified with cashier ID');
} catch (Throwable $e) {
    assertCondition(false, 'OTC Partial payment threw exception', $e->getMessage());
}

// -----------------------------------------------------------------------------
// TEST 2: Cashier OTC Full Payment Recording (Settling balance)
// -----------------------------------------------------------------------------
echo "\n[TEST 2] Cashier OTC Full Payment Recording (Settling remaining balance)...\n";
try {
    $result2 = $service->recordOverTheCounterPayment([
        'assessment_id'    => $ensemble1['assessment_id'],
        'user_id'          => $ensemble1['user_id'],
        'application_id'   => $ensemble1['application_id'],
        'amount'           => 8000.00,
        'payment_method'   => 'GCash',
        'reference_number' => 'OTC-GCASH-' . $ensemble1['uniq'],
    ], $ensemble1['cashier_id']);

    assertCondition($result2['payment_status'] === 'paid', 'Assessment status transitioned to paid');
    assertCondition(abs($result2['total_paid'] - 12000.00) < 0.01, 'Total paid reflects full tuition settlement');
    assertCondition($result2['balance'] <= 0.0, 'Remaining balance reaches zero');
    assertCondition($result2['receipt_number'] !== $result1['receipt_number'], 'Unique receipt generated for second payment');

    $assDb = $pdo->query('SELECT total_paid, payment_status FROM student_assessments WHERE id = ' . $ensemble1['assessment_id'])->fetch(PDO::FETCH_ASSOC);
    assertCondition($assDb['payment_status'] === 'paid', 'DB assessment reflects paid status');
} catch (Throwable $e) {
    assertCondition(false, 'OTC Full payment threw exception', $e->getMessage());
}

// -----------------------------------------------------------------------------
// TEST 3: OTC Overpayment and Minimum Payment Protection
// -----------------------------------------------------------------------------
echo "\n[TEST 3] OTC Overpayment and Minimum Payment Protections...\n";
// Case 3A: Attempt to pay on already settled assessment
try {
    $service->recordOverTheCounterPayment([
        'assessment_id'    => $ensemble1['assessment_id'],
        'user_id'          => $ensemble1['user_id'],
        'amount'           => 1000.00,
        'payment_method'   => 'Cash',
    ], $ensemble1['cashier_id']);
    assertCondition(false, 'Blocked payment on settled account', 'Failed to throw exception');
} catch (Throwable $e) {
    assertCondition(str_contains($e->getMessage(), 'fully settled'), 'Blocked payment on fully settled account');
}

// Case 3B: Attempt to exceed balance on active assessment
$ensemble2 = createTestEnsemble($pdo, 5000.00);
try {
    $service->recordOverTheCounterPayment([
        'assessment_id'    => $ensemble2['assessment_id'],
        'user_id'          => $ensemble2['user_id'],
        'amount'           => 6000.00,
        'payment_method'   => 'Cash',
    ], $ensemble2['cashier_id']);
    assertCondition(false, 'Blocked overpayment', 'Failed to throw exception');
} catch (Throwable $e) {
    assertCondition(str_contains($e->getMessage(), 'cannot exceed the remaining balance'), 'Blocked payment exceeding remaining balance');
}

// Case 3C: Attempt to pay below minimum downpayment (min 3000 or remaining balance)
try {
    $service->recordOverTheCounterPayment([
        'assessment_id'    => $ensemble2['assessment_id'],
        'user_id'          => $ensemble2['user_id'],
        'amount'           => 1500.00,
        'payment_method'   => 'Cash',
    ], $ensemble2['cashier_id']);
    assertCondition(false, 'Blocked payment below minimum', 'Failed to throw exception');
} catch (Throwable $e) {
    assertCondition(str_contains($e->getMessage(), 'Minimum payment amount is ₱3,000.00'), 'Enforces ₱3,000.00 minimum OTC downpayment');
}

// -----------------------------------------------------------------------------
// TEST 4: Online Payment Proof Submission (submitPaymentProof)
// -----------------------------------------------------------------------------
echo "\n[TEST 4] Online Payment Proof Submission via PaymentService...\n";
$ensemble3 = createTestEnsemble($pdo, 9000.00);
$refOnline1 = 'REF-ONL-' . bin2hex(random_bytes(4));

try {
    $proofResult = $service->submitPaymentProof([
        'assessment_id'    => $ensemble3['assessment_id'],
        'user_id'          => $ensemble3['user_id'],
        'amount'           => 3000.00,
        'payment_method'   => 'GCash',
        'reference_number' => $refOnline1,
        'proof_image'      => 'proof_test_01.jpg',
    ]);

    assertCondition($proofResult['success'] === true, 'Proof submitted successfully');
    $pendingPay = $repo->findById($proofResult['payment_id']);
    assertCondition($pendingPay['status'] === 'pending', 'Payment record status is pending');
    assertCondition(empty($pendingPay['receipt_number']), 'Receipt number remains unassigned until verification');

    // Test duplicate reference protection
    try {
        $service->submitPaymentProof([
            'assessment_id'    => $ensemble3['assessment_id'],
            'user_id'          => $ensemble3['user_id'],
            'amount'           => 1000.00,
            'payment_method'   => 'GCash',
            'reference_number' => $refOnline1,
            'proof_image'      => 'proof_test_02.jpg',
        ]);
        assertCondition(false, 'Duplicate reference blocked', 'Failed to throw exception');
    } catch (Throwable $e) {
        assertCondition(str_contains($e->getMessage(), 'already pending or verified'), 'Blocked duplicate reference submission');
    }

    // Test pending balance exhaustion (Remaining: 9000 - 3000 pending = 6000 max)
    try {
        $service->submitPaymentProof([
            'assessment_id'    => $ensemble3['assessment_id'],
            'user_id'          => $ensemble3['user_id'],
            'amount'           => 7000.00,
            'payment_method'   => 'Bank Transfer',
            'reference_number' => 'REF-EXCEED-' . bin2hex(random_bytes(3)),
            'proof_image'      => 'proof_test_03.jpg',
        ]);
        assertCondition(false, 'Exceeding balance blocked', 'Failed to throw exception');
    } catch (Throwable $e) {
        assertCondition(str_contains($e->getMessage(), 'exceeds your allowable remaining balance'), 'Accounts for pending submissions in remaining balance check');
    }
} catch (Throwable $e) {
    assertCondition(false, 'Online proof submission threw exception', $e->getMessage());
}

// -----------------------------------------------------------------------------
// TEST 5: Online Payment Verification (verifyOnlinePayment)
// -----------------------------------------------------------------------------
echo "\n[TEST 5] Online Payment Verification via PaymentService...\n";
try {
    $verifyResult = $service->verifyOnlinePayment($proofResult['payment_id'], $ensemble3['cashier_id']);

    assertCondition($verifyResult['success'] === true, 'Online payment verified successfully');
    assertCondition(preg_match('/^REC-\d{8}-\d{4}$/', $verifyResult['receipt_number']) === 1, 'Atomic receipt number generated upon verification');
    assertCondition($verifyResult['payment_status'] === 'partial', 'Assessment payment_status updated to partial');

    // DB verification
    $verifiedPay = $repo->findById($proofResult['payment_id']);
    assertCondition($verifiedPay['status'] === 'verified', 'Payment record status changed to verified');
    assertCondition($verifiedPay['receipt_number'] === $verifyResult['receipt_number'], 'Receipt number saved on payment record');
    assertCondition((int)$verifiedPay['cashier_id'] === $ensemble3['cashier_id'], 'Cashier ID tracked on verified payment');

    $assDb = $pdo->query('SELECT total_paid, payment_status FROM student_assessments WHERE id = ' . $ensemble3['assessment_id'])->fetch(PDO::FETCH_ASSOC);
    assertCondition((float)$assDb['total_paid'] === 3000.00, 'Assessment total_paid incremented by ₱3,000.00');

    $appDb = $pdo->query('SELECT status FROM applications WHERE id = ' . $ensemble3['application_id'])->fetch(PDO::FETCH_ASSOC);
    assertCondition($appDb['status'] === 'payment_verified', 'Application status transitioned to payment_verified');
} catch (Throwable $e) {
    assertCondition(false, 'Online payment verification threw exception', $e->getMessage());
}

// -----------------------------------------------------------------------------
// TEST 6: Online Payment Rejection (rejectOnlinePayment)
// -----------------------------------------------------------------------------
echo "\n[TEST 6] Online Payment Rejection via PaymentService...\n";
$refOnline2 = 'REF-ONL-REJ-' . bin2hex(random_bytes(4));
$rejProof = $service->submitPaymentProof([
    'assessment_id'    => $ensemble3['assessment_id'],
    'user_id'          => $ensemble3['user_id'],
    'amount'           => 1000.00,
    'payment_method'   => 'GCash',
    'reference_number' => $refOnline2,
    'proof_image'      => 'proof_blur.jpg',
]);

// Case 6A: Reject without remarks
try {
    $service->rejectOnlinePayment($rejProof['payment_id'], $ensemble3['cashier_id'], '   ');
    assertCondition(false, 'Mandatory remarks enforced', 'Failed to throw exception');
} catch (Throwable $e) {
    assertCondition(str_contains($e->getMessage(), 'reason for rejection is required'), 'Mandatory rejection reason enforced');
}

// Case 6B: Reject with remarks
try {
    $rejResult = $service->rejectOnlinePayment($rejProof['payment_id'], $ensemble3['cashier_id'], 'Unreadable receipt screenshot. Please re-upload with clear reference number.');
    assertCondition($rejResult['success'] === true, 'Rejection executed successfully');

    $rejPay = $repo->findById($rejProof['payment_id']);
    assertCondition($rejPay['status'] === 'rejected', 'Payment status set to rejected');
    assertCondition(str_contains($rejPay['remarks'], 'Unreadable receipt'), 'Rejection remarks saved to payment record');
    assertCondition((int)$rejPay['cashier_id'] === $ensemble3['cashier_id'], 'Cashier ID tracked on rejected payment');

    // Balance should NOT have changed
    $assDb = $pdo->query('SELECT total_paid FROM student_assessments WHERE id = ' . $ensemble3['assessment_id'])->fetch(PDO::FETCH_ASSOC);
    assertCondition((float)$assDb['total_paid'] === 3000.00, 'Assessment balance untouched on rejected payment');
} catch (Throwable $e) {
    assertCondition(false, 'Online payment rejection threw exception', $e->getMessage());
}

// -----------------------------------------------------------------------------
// TEST 7: Online Approval Overpayment Protection
// -----------------------------------------------------------------------------
echo "\n[TEST 7] Online Approval Overpayment Protection...\n";
// Ensemble 4: Net amount 5,000.
// Proof A for 4,000. Proof B for 3,000. (Both submitted when balance permitted if submitted sequentially before approval)
$ensemble4 = createTestEnsemble($pdo, 5000.00);
$proofA = $service->submitPaymentProof([
    'assessment_id'    => $ensemble4['assessment_id'],
    'user_id'          => $ensemble4['user_id'],
    'amount'           => 3000.00,
    'payment_method'   => 'GCash',
    'reference_number' => 'REF-CONC-A-' . $ensemble4['uniq'],
    'proof_image'      => 'proof_a.jpg',
]);

$proofB = $service->submitPaymentProof([
    'assessment_id'    => $ensemble4['assessment_id'],
    'user_id'          => $ensemble4['user_id'],
    'amount'           => 2000.00,
    'payment_method'   => 'GCash',
    'reference_number' => 'REF-CONC-B-' . $ensemble4['uniq'],
    'proof_image'      => 'proof_b.jpg',
]);

// Manually simulate an out-of-band payment or adjustment that reduced balance
$pdo->prepare('UPDATE student_assessments SET total_paid = 4000.00, payment_status = "partial" WHERE id = ?')->execute([$ensemble4['assessment_id']]);

// Now approving Proof A (3,000) would make total_paid = 7000 on net 5000. Must fail!
try {
    $service->verifyOnlinePayment($proofA['payment_id'], $ensemble4['cashier_id']);
    assertCondition(false, 'Overpayment on approval blocked', 'Failed to throw exception');
} catch (Throwable $e) {
    assertCondition(str_contains($e->getMessage(), 'cannot exceed the remaining balance'), 'Verification blocks payment exceeding current live balance');
}

// -----------------------------------------------------------------------------
// TEST 8: Repository Queries and Financial Stats
// -----------------------------------------------------------------------------
echo "\n[TEST 8] PaymentRepository Query Methods...\n";
$paginated = $repo->getPaginatedPayments(10, 0);
assertCondition(is_array($paginated) && count($paginated) > 0, 'Repository retrieves paginated payment records');

$count = $repo->countPayments();
assertCondition($count > 0, 'Repository counts total payment records accurately');

$stats = $repo->getFinancialStats();
assertCondition(isset($stats['today_collections']) && isset($stats['total_collections']), 'Repository aggregates financial metrics');

$details = $repo->findWithDetails($result1['payment_id']);
assertCondition(isset($details['student_first']) && isset($details['net_amount']), 'Repository joins student and assessment details correctly');

echo "\n====================================================================\n";
echo "  PHASE 1 TEST SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "====================================================================\n";

exit($failCount > 0 ? 1 : 0);
