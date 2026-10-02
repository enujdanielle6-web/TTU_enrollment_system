<?php
declare(strict_types=1);

/**
 * TTU ENROLLMENT SYSTEM — PHASE 3 PAYMONGO WEBHOOK & RECONCILIATION TEST SUITE
 *
 * Verifies:
 * 1. Cryptographic Paymongo-Signature HMAC verification (test and live signatures, replay attack window, tampered payload).
 * 2. Webhook Controller protocol handling (POST method enforcement, malformed JSON, missing signature, invalid signature).
 * 3. Successful webhook reconciliation (payment_records verification, atomic receipt generation, assessment balance updates).
 * 4. Application status transition to payment_verified (with strict Registrar enrollment gate preservation).
 * 5. Idempotent processing: repeated webhook deliveries process exactly once without duplicate receipts or balance increments.
 * 6. Alternative success event handling (payment.paid via payment_intent_id).
 * 7. Failed payment webhook handling (payment.failed -> status 'failed', zero balance impact).
 * 8. Cancelled / Expired payment webhook handling (checkout_session.expired -> status 'expired').
 * 9. Unknown transaction guard (non-existent session/intent returns 404).
 * 10. Overpayment & settled account protection during webhook reconciliation.
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

use App\Config\PayMongoConfig;
use App\Core\Request;
use App\Core\Response;
use App\Controllers\WebhookController;
use App\Repositories\PaymentRepository;
use App\Services\PaymentService;
use App\Services\PayMongoService;

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
echo "  TTU ENROLLMENT SYSTEM — PHASE 3 PAYMONGO WEBHOOK TEST SUITE       \n";
echo "====================================================================\n\n";

$testSecret = 'whsk_test_mock_secret_key_1234567890';
putenv("PAYMONGO_WEBHOOK_SECRET={$testSecret}");
$_ENV['PAYMONGO_WEBHOOK_SECRET'] = $testSecret;

$paymentRepo = new PaymentRepository($pdo);
$payMongoService = new PayMongoService(null, null, null);
$paymentService = new PaymentService($paymentRepo, $pdo);
$webhookController = new WebhookController($payMongoService, $paymentService);

// -----------------------------------------------------------------------------
// [TEST 1] Paymongo-Signature Cryptographic Verification
// -----------------------------------------------------------------------------
echo "[TEST 1] Paymongo-Signature Cryptographic Verification...\n";

$testPayload = json_encode(['data' => ['id' => 'evt_test_1', 'type' => 'event']], JSON_THROW_ON_ERROR);

// 1a. Valid test mode signature
$validHeaderTe = PayMongoService::generateSignatureHeader($testPayload, $testSecret, time(), false);
$isValidTe = $payMongoService->verifyWebhookSignature($testPayload, $validHeaderTe, $testSecret);
assertTest($isValidTe === true, "Valid testmode ('te') signature passes cryptographic verification");

// 1b. Valid live mode signature
$validHeaderLi = PayMongoService::generateSignatureHeader($testPayload, $testSecret, time(), true);
$isValidLi = $payMongoService->verifyWebhookSignature($testPayload, $validHeaderLi, $testSecret);
assertTest($isValidLi === true, "Valid livemode ('li') signature passes cryptographic verification");

// 1c. Tampered payload fails
$tamperedPayload = json_encode(['data' => ['id' => 'evt_tampered', 'type' => 'event']], JSON_THROW_ON_ERROR);
$isTamperedValid = $payMongoService->verifyWebhookSignature($tamperedPayload, $validHeaderTe, $testSecret);
assertTest($isTamperedValid === false, "Tampered request payload fails verification (HMAC mismatch)");

// 1d. Invalid secret fails
$wrongSecret = 'whsk_different_secret_key_999999';
$isWrongSecretValid = $payMongoService->verifyWebhookSignature($testPayload, $validHeaderTe, $wrongSecret);
assertTest($isWrongSecretValid === false, "Signature signed with wrong secret is rejected");

// 1e. Expired timestamp rejected (Replay attack defense)
$expiredTimestamp = time() - 3600; // 1 hour ago
$expiredHeader = PayMongoService::generateSignatureHeader($testPayload, $testSecret, $expiredTimestamp, false);
$isExpiredValid = $payMongoService->verifyWebhookSignature($testPayload, $expiredHeader, $testSecret, 300);
assertTest($isExpiredValid === false, "Expired timestamp rejected by replay attack defense window (300s)");

// 1f. Malformed header rejected
$isMalformedValid = $payMongoService->verifyWebhookSignature($testPayload, "invalid_header_format", $testSecret);
assertTest($isMalformedValid === false, "Malformed signature header string is rejected");

// -----------------------------------------------------------------------------
// [TEST 2] Webhook Controller Protocol & Security Rejections
// -----------------------------------------------------------------------------
echo "\n[TEST 2] Webhook Controller Protocol & Security Rejections...\n";

// 2a. Reject GET requests (Only POST allowed)
$reqGet = (new Request())->setMethod('GET');
$respGet = new Response();
$webhookController->handlePayMongo($reqGet, $respGet);
assertTest($respGet->statusCode === 405, "Rejects non-POST request with HTTP 405 Method Not Allowed");

// 2b. Reject empty payload
$reqEmpty = (new Request())->setMethod('POST')->setRawBody('');
$respEmpty = new Response();
$webhookController->handlePayMongo($reqEmpty, $respEmpty);
assertTest($respEmpty->statusCode === 400, "Rejects empty payload with HTTP 400 Bad Request");

// 2c. Reject malformed JSON
$reqBadJson = (new Request())->setMethod('POST')->setRawBody('{not_valid_json');
$respBadJson = new Response();
$webhookController->handlePayMongo($reqBadJson, $respBadJson);
assertTest($respBadJson->statusCode === 400, "Rejects malformed JSON with HTTP 400 Bad Request");

// 2d. Reject missing Paymongo-Signature header
$reqNoSig = (new Request())->setMethod('POST')->setRawBody($testPayload);
$respNoSig = new Response();
$webhookController->handlePayMongo($reqNoSig, $respNoSig);
assertTest($respNoSig->statusCode === 401, "Rejects missing Paymongo-Signature header with HTTP 401 Unauthorized");

// 2e. Reject forged/invalid signature
$reqBadSig = (new Request())
    ->setMethod('POST')
    ->setRawBody($testPayload)
    ->setHeader('Paymongo-Signature', 't=' . time() . ',te=bad_forged_hash');
$respBadSig = new Response();
$webhookController->handlePayMongo($reqBadSig, $respBadSig);
assertTest($respBadSig->statusCode === 401, "Rejects forged signature with HTTP 401 Unauthorized");

// -----------------------------------------------------------------------------
// SETUP FIXTURES FOR FINANCIAL & RECONCILIATION TESTS
// -----------------------------------------------------------------------------
$pdo->beginTransaction();

$uniq = bin2hex(random_bytes(3));

// Create test applicant
$stmtUser = $pdo->prepare("
    INSERT INTO users (first_name, last_name, email, password, role, is_active, email_verified, created_at, updated_at)
    VALUES ('Webhook', 'Student', :email, 'secret', 'applicant', 1, 1, NOW(), NOW())
");
$testEmail = 'webhook_test_' . time() . "_{$uniq}@example.com";
$stmtUser->execute(['email' => $testEmail]);
$testUserId = (int) $pdo->lastInsertId();

// Create test application
$stmtApp = $pdo->prepare("
    INSERT INTO applications (user_id, reference_number, academic_level, grade_level, status)
    VALUES (:uid, :ref, 'College', '1st Year', 'approved')
");
$appRef = 'APP-WH-' . time() . '-' . rand(100, 999);
$stmtApp->execute(['uid' => $testUserId, 'ref' => $appRef]);
$testAppId = (int) $pdo->lastInsertId();

// Create student assessment
$stmtAss = $pdo->prepare("
    INSERT INTO student_assessments (user_id, application_id, tuition_fee, total_amount, discount_amount, net_amount, total_paid, payment_status)
    VALUES (:uid, :app_id, 12000.00, 15000.00, 3000.00, 12000.00, 0.00, 'unpaid')
");
$stmtAss->execute([
    'uid'    => $testUserId,
    'app_id' => $testAppId,
]);
$testAssessmentId = (int) $pdo->lastInsertId();

$pdo->commit();

// -----------------------------------------------------------------------------
// [TEST 3] Successful Payment Reconciled via Webhook (checkout_session.payment.paid)
// -----------------------------------------------------------------------------
echo "\n[TEST 3] Successful Payment Reconciled via Webhook (checkout_session.payment.paid)...\n";

$sessionId1 = 'cs_test_wh_' . time() . '_' . bin2hex(random_bytes(4));
$intentId1 = 'pi_test_wh_' . time() . '_' . bin2hex(random_bytes(4));
$paymentAmount1 = 5000.00;

// Create pending payment in central ledger
$pendingPaymentId1 = $paymentRepo->insert([
    'assessment_id'       => $testAssessmentId,
    'user_id'             => $testUserId,
    'amount'              => $paymentAmount1,
    'payment_date'        => date('Y-m-d'),
    'payment_method'      => 'PayMongo',
    'gateway'             => 'paymongo',
    'status'              => 'pending',
    'checkout_session_id' => $sessionId1,
    'payment_intent_id'   => $intentId1,
    'reference_number'    => 'PM-REF-' . time() . '-1',
]);

// Build authentic PayMongo webhook event payload
$webhookEventPaid1 = [
    'data' => [
        'id'         => 'evt_test_paid_1_' . time(),
        'type'       => 'event',
        'attributes' => [
            'type'     => 'checkout_session.payment.paid',
            'livemode' => false,
            'data'     => [
                'id'         => $sessionId1,
                'type'       => 'checkout_session',
                'attributes' => [
                    'status'         => 'paid',
                    'payment_intent' => [
                        'id'         => $intentId1,
                        'type'       => 'payment_intent',
                        'attributes' => [
                            'status'   => 'succeeded',
                            'amount'   => 500000,
                            'currency' => 'PHP',
                        ],
                    ],
                    'payments'       => [
                        [
                            'id'         => 'pay_test_item_1',
                            'type'       => 'payment',
                            'attributes' => [
                                'amount' => 500000,
                                'fee'    => 12500, // ₱125.00 gateway fee
                                'status' => 'paid',
                            ],
                        ],
                    ],
                    'metadata'       => [
                        'assessment_id'     => (string) $testAssessmentId,
                        'user_id'           => (string) $testUserId,
                        'payment_record_id' => (string) $pendingPaymentId1,
                    ],
                ],
            ],
        ],
    ],
];

$rawPayload1 = json_encode($webhookEventPaid1, JSON_THROW_ON_ERROR);
$sigHeader1 = PayMongoService::generateSignatureHeader($rawPayload1, $testSecret, time(), false);

$req1 = (new Request())
    ->setMethod('POST')
    ->setRawBody($rawPayload1)
    ->setHeader('Paymongo-Signature', $sigHeader1);
$resp1 = new Response();

$webhookController->handlePayMongo($req1, $resp1);

assertTest($resp1->statusCode === 200, "Webhook controller returns HTTP 200 OK for valid payment event");
assertTest(($resp1->jsonData['status'] ?? '') === 'success', "Response indicates success");

// Inspect updated payment_record in central ledger
$verifiedRecord1 = $paymentRepo->findById($pendingPaymentId1);
assertTest($verifiedRecord1['status'] === 'verified', "Payment record status transitioned from 'pending' to 'verified'");
assertTest(!empty($verifiedRecord1['receipt_number']) && str_starts_with($verifiedRecord1['receipt_number'], 'REC-'), "Official atomic receipt number generated ({$verifiedRecord1['receipt_number']})");
assertTest((float) $verifiedRecord1['gateway_fee'] === 125.00, "Gateway fee of ₱125.00 recorded from payload");
assertTest(!empty($verifiedRecord1['raw_webhook_payload']), "Raw webhook payload archived in database");

// Inspect student assessment
$assStmt = $pdo->prepare("SELECT * FROM student_assessments WHERE id = :id");
$assStmt->execute(['id' => $testAssessmentId]);
$assRow1 = $assStmt->fetch(PDO::FETCH_ASSOC);
assertTest((float) $assRow1['total_paid'] === 5000.00, "Assessment total_paid incremented to ₱5,000.00");
assertTest($assRow1['payment_status'] === 'partial', "Assessment payment_status set to 'partial'");

// Inspect application status
$appStmt = $pdo->prepare("SELECT status FROM applications WHERE id = :id");
$appStmt->execute(['id' => $testAppId]);
$appStatus1 = $appStmt->fetchColumn();
assertTest($appStatus1 === 'payment_verified', "Application status transitioned to 'payment_verified'");
assertTest($appStatus1 !== 'enrolled', "Application is NOT marked 'enrolled' (Registrar gate strictly preserved)");

// -----------------------------------------------------------------------------
// [TEST 4] Idempotency & Duplicate Webhook Processing
// -----------------------------------------------------------------------------
echo "\n[TEST 4] Idempotency & Duplicate Webhook Processing...\n";

// Resend the exact same webhook event multiple times
for ($attempt = 1; $attempt <= 3; $attempt++) {
    $dupResp = new Response();
    $webhookController->handlePayMongo($req1, $dupResp);

    assertTest($dupResp->statusCode === 200, "Duplicate delivery attempt {$attempt} returns HTTP 200 OK");
    assertTest(($dupResp->jsonData['data']['status'] ?? '') === 'already_processed', "Duplicate delivery {$attempt} identified as 'already_processed'");
}

// Verify that the receipt number and financial balance were NOT changed or duplicated
$recheckRecord1 = $paymentRepo->findById($pendingPaymentId1);
assertTest($recheckRecord1['receipt_number'] === $verifiedRecord1['receipt_number'], "Receipt number remained identical across duplicate webhooks");

$assStmt->execute(['id' => $testAssessmentId]);
$assRowDup = $assStmt->fetch(PDO::FETCH_ASSOC);
assertTest((float) $assRowDup['total_paid'] === 5000.00, "Assessment total_paid remained ₱5,000.00 (NO duplicate balance inflation)");

// -----------------------------------------------------------------------------
// [TEST 5] Full Settlement via Direct 'payment.paid' Webhook Event
// -----------------------------------------------------------------------------
echo "\n[TEST 5] Full Settlement via Direct 'payment.paid' Webhook Event...\n";

$intentId2 = 'pi_test_direct_' . time() . '_' . bin2hex(random_bytes(4));
$paymentAmount2 = 7000.00; // Remaining balance: ₱12,000 - ₱5,000 = ₱7,000

$pendingPaymentId2 = $paymentRepo->insert([
    'assessment_id'       => $testAssessmentId,
    'user_id'             => $testUserId,
    'amount'              => $paymentAmount2,
    'payment_date'        => date('Y-m-d'),
    'payment_method'      => 'PayMongo',
    'gateway'             => 'paymongo',
    'status'              => 'pending',
    'payment_intent_id'   => $intentId2,
    'reference_number'    => 'PM-REF-' . time() . '-2',
]);

$webhookEventDirectPaid = [
    'data' => [
        'id'         => 'evt_test_direct_paid_' . time(),
        'type'       => 'event',
        'attributes' => [
            'type'     => 'payment.paid',
            'livemode' => false,
            'data'     => [
                'id'         => 'pay_test_direct_' . time(),
                'type'       => 'payment',
                'attributes' => [
                    'status'            => 'paid',
                    'amount'            => 700000,
                    'fee'               => 17500, // ₱175.00
                    'payment_intent_id' => $intentId2,
                    'metadata'          => [
                        'assessment_id'     => (string) $testAssessmentId,
                        'user_id'           => (string) $testUserId,
                        'payment_record_id' => (string) $pendingPaymentId2,
                    ],
                ],
            ],
        ],
    ],
];

$rawPayload2 = json_encode($webhookEventDirectPaid, JSON_THROW_ON_ERROR);
$sigHeader2 = PayMongoService::generateSignatureHeader($rawPayload2, $testSecret, time(), false);

$req2 = (new Request())
    ->setMethod('POST')
    ->setRawBody($rawPayload2)
    ->setHeader('Paymongo-Signature', $sigHeader2);
$resp2 = new Response();

$webhookController->handlePayMongo($req2, $resp2);

assertTest($resp2->statusCode === 200, "Direct payment.paid webhook handled with HTTP 200 OK");

$verifiedRecord2 = $paymentRepo->findById($pendingPaymentId2);
assertTest($verifiedRecord2['status'] === 'verified', "Second payment transitioned to 'verified'");
assertTest($verifiedRecord2['receipt_number'] !== $verifiedRecord1['receipt_number'], "Second unique atomic receipt generated ({$verifiedRecord2['receipt_number']})");

$assStmt->execute(['id' => $testAssessmentId]);
$assRowFull = $assStmt->fetch(PDO::FETCH_ASSOC);
assertTest((float) $assRowFull['total_paid'] === 12000.00, "Assessment total_paid reached ₱12,000.00 (Full tuition settlement)");
assertTest($assRowFull['payment_status'] === 'paid', "Assessment payment_status reached final 'paid' state");

// -----------------------------------------------------------------------------
// [TEST 6] Overpayment & Settled Account Protections
// -----------------------------------------------------------------------------
echo "\n[TEST 6] Overpayment & Settled Account Protections...\n";

// Attempt to confirm a payment when assessment balance is 0.00
$sessionIdOver = 'cs_test_over_' . time();
$pendingOverId = $paymentRepo->insert([
    'assessment_id'       => $testAssessmentId,
    'user_id'             => $testUserId,
    'amount'              => 500.00,
    'payment_date'        => date('Y-m-d'),
    'payment_method'      => 'PayMongo',
    'gateway'             => 'paymongo',
    'status'              => 'pending',
    'checkout_session_id' => $sessionIdOver,
    'reference_number'    => 'PM-REF-OVER-' . time(),
]);

$eventOver = [
    'data' => [
        'id'         => 'evt_over_' . time(),
        'type'       => 'event',
        'attributes' => [
            'type' => 'checkout_session.payment.paid',
            'data' => [
                'id'         => $sessionIdOver,
                'type'       => 'checkout_session',
                'attributes' => [
                    'status'   => 'paid',
                    'payments' => [['attributes' => ['amount' => 50000, 'fee' => 1250]]],
                ],
            ],
        ],
    ],
];
$rawOver = json_encode($eventOver, JSON_THROW_ON_ERROR);
$sigOver = PayMongoService::generateSignatureHeader($rawOver, $testSecret, time(), false);

$reqOver = (new Request())
    ->setMethod('POST')
    ->setRawBody($rawOver)
    ->setHeader('Paymongo-Signature', $sigOver);
$respOver = new Response();

$webhookController->handlePayMongo($reqOver, $respOver);

assertTest($respOver->statusCode === 422, "Webhook rejects payment on already settled assessment with HTTP 422");
assertTest(str_contains($respOver->jsonData['message'] ?? '', 'fully settled'), "Error message explicitly indicates account is fully settled");

// Confirm assessment total_paid was NOT inflated
$chkStmt = $pdo->prepare("SELECT total_paid FROM student_assessments WHERE id = :id");
$chkStmt->execute(['id' => $testAssessmentId]);
assertTest((float) $chkStmt->fetchColumn() === 12000.00, "Assessment total_paid remains uncorrupted at ₱12,000.00");

// -----------------------------------------------------------------------------
// [TEST 7] Failed Payment Webhook Handling (payment.failed)
// -----------------------------------------------------------------------------
echo "\n[TEST 7] Failed Payment Webhook Handling (payment.failed)...\n";

$intentIdFail = 'pi_test_fail_' . time();
$pendingFailId = $paymentRepo->insert([
    'assessment_id'       => $testAssessmentId,
    'user_id'             => $testUserId,
    'amount'              => 1000.00,
    'payment_date'        => date('Y-m-d'),
    'payment_method'      => 'PayMongo',
    'gateway'             => 'paymongo',
    'status'              => 'pending',
    'payment_intent_id'   => $intentIdFail,
    'reference_number'    => 'PM-REF-FAIL-' . time(),
]);

$eventFail = [
    'data' => [
        'id'         => 'evt_fail_' . time(),
        'type'       => 'event',
        'attributes' => [
            'type' => 'payment.failed',
            'data' => [
                'id'         => 'pay_failed_id',
                'type'       => 'payment',
                'attributes' => [
                    'status'            => 'failed',
                    'payment_intent_id' => $intentIdFail,
                ],
            ],
        ],
    ],
];
$rawFail = json_encode($eventFail, JSON_THROW_ON_ERROR);
$sigFail = PayMongoService::generateSignatureHeader($rawFail, $testSecret, time(), false);

$reqFail = (new Request())
    ->setMethod('POST')
    ->setRawBody($rawFail)
    ->setHeader('Paymongo-Signature', $sigFail);
$respFail = new Response();

$webhookController->handlePayMongo($reqFail, $respFail);

assertTest($respFail->statusCode === 200, "Failed payment webhook acknowledged with HTTP 200 OK");

$failRecord = $paymentRepo->findById($pendingFailId);
assertTest($failRecord['status'] === 'failed', "Payment record status transitioned to 'failed'");
assertTest($failRecord['receipt_number'] === null, "No receipt number issued for failed transaction");

// -----------------------------------------------------------------------------
// [TEST 8] Cancelled / Expired Payment Webhook Handling
// -----------------------------------------------------------------------------
echo "\n[TEST 8] Cancelled / Expired Payment Webhook Handling...\n";

$sessionIdExp = 'cs_test_expired_' . time();
$pendingExpId = $paymentRepo->insert([
    'assessment_id'       => $testAssessmentId,
    'user_id'             => $testUserId,
    'amount'              => 2000.00,
    'payment_date'        => date('Y-m-d'),
    'payment_method'      => 'PayMongo',
    'gateway'             => 'paymongo',
    'status'              => 'pending',
    'checkout_session_id' => $sessionIdExp,
    'reference_number'    => 'PM-REF-EXP-' . time(),
]);

$eventExp = [
    'data' => [
        'id'         => 'evt_exp_' . time(),
        'type'       => 'event',
        'attributes' => [
            'type' => 'checkout_session.expired',
            'data' => [
                'id'         => $sessionIdExp,
                'type'       => 'checkout_session',
                'attributes' => [
                    'status' => 'expired',
                ],
            ],
        ],
    ],
];
$rawExp = json_encode($eventExp, JSON_THROW_ON_ERROR);
$sigExp = PayMongoService::generateSignatureHeader($rawExp, $testSecret, time(), false);

$reqExp = (new Request())
    ->setMethod('POST')
    ->setRawBody($rawExp)
    ->setHeader('Paymongo-Signature', $sigExp);
$respExp = new Response();

$webhookController->handlePayMongo($reqExp, $respExp);

assertTest($respExp->statusCode === 200, "Expired session webhook acknowledged with HTTP 200 OK");

$expRecord = $paymentRepo->findById($pendingExpId);
assertTest($expRecord['status'] === 'expired', "Payment record status transitioned to 'expired'");
assertTest($expRecord['receipt_number'] === null, "No receipt issued for expired session");

// -----------------------------------------------------------------------------
// [TEST 9] Unknown Transaction Guard
// -----------------------------------------------------------------------------
echo "\n[TEST 9] Unknown Transaction Guard...\n";

$eventUnknown = [
    'data' => [
        'id'         => 'evt_unknown_' . time(),
        'type'       => 'event',
        'attributes' => [
            'type' => 'checkout_session.payment.paid',
            'data' => [
                'id'         => 'cs_non_existent_session_9999999',
                'type'       => 'checkout_session',
                'attributes' => [
                    'status'   => 'paid',
                    'payments' => [['attributes' => ['amount' => 100000, 'fee' => 2500]]],
                ],
            ],
        ],
    ],
];
$rawUnknown = json_encode($eventUnknown, JSON_THROW_ON_ERROR);
$sigUnknown = PayMongoService::generateSignatureHeader($rawUnknown, $testSecret, time(), false);

$reqUnknown = (new Request())
    ->setMethod('POST')
    ->setRawBody($rawUnknown)
    ->setHeader('Paymongo-Signature', $sigUnknown);
$respUnknown = new Response();

$webhookController->handlePayMongo($reqUnknown, $respUnknown);

assertTest($respUnknown->statusCode === 404, "Unknown checkout session returns HTTP 404 Not Found");
assertTest(str_contains($respUnknown->jsonData['message'] ?? '', 'not found'), "Response specifies transaction was not found");

// -----------------------------------------------------------------------------
// TEST SUMMARY
// -----------------------------------------------------------------------------
echo "\n====================================================================\n";
echo "  PHASE 3 TEST SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
