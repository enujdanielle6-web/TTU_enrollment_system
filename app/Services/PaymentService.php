<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\PaymentRepository;
use PDO;
use Exception;

/**
 * Domain Service responsible for orchestrating payment processing, cashier over-the-counter
 * transactions, manual online proof verification and rejection, atomic receipt issuance,
 * assessment ledger mutations, application status transitions, and audit logging.
 */
class PaymentService
{
    private PDO $pdo;
    private PaymentRepository $paymentRepository;

    public function __construct(?PaymentRepository $paymentRepository = null, ?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->paymentRepository = $paymentRepository ?? new PaymentRepository($this->pdo);
    }

    /**
     * Records an over-the-counter (OTC) cash or bank payment entered by a cashier.
     *
     * @param array $data ['assessment_id', 'user_id', 'application_id' (optional), 'amount', 'payment_method', 'reference_number' (optional)]
     * @param int|null $cashierId User ID of cashier recording the payment
     * @return array Result metadata including payment_id, receipt_number, new balances, and payment_status
     * @throws Exception On validation failure, overpayment, or database error
     */
    public function recordOverTheCounterPayment(array $data, ?int $cashierId = null): array
    {
        $assessmentId = (int) ($data['assessment_id'] ?? 0);
        $userId = (int) ($data['user_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0.0);
        $method = trim((string) ($data['payment_method'] ?? ''));
        $refNo = trim((string) ($data['reference_number'] ?? ''));

        if ($assessmentId <= 0) {
            throw new Exception('Invalid assessment ID provided.');
        }

        if ($amount <= 0.0) {
            throw new Exception('Payment amount must be greater than ₱0.00.');
        }

        if (!in_array($method, ['Cash', 'GCash', 'Bank Transfer'], true)) {
            throw new Exception('Invalid payment method selected. Must be Cash, GCash, or Bank Transfer.');
        }

        $isExternalTx = $this->pdo->inTransaction();
        if (!$isExternalTx) {
            $this->pdo->beginTransaction();
        }

        try {
            // 1. Lock and fetch Student Assessment
            $assStmt = $this->pdo->prepare('SELECT * FROM student_assessments WHERE id = :id FOR UPDATE');
            $assStmt->execute(['id' => $assessmentId]);
            $assessment = $assStmt->fetch(PDO::FETCH_ASSOC);

            if (!$assessment) {
                throw new Exception('Assessment record not found.');
            }

            $applicationId = (int) ($data['application_id'] ?? $assessment['application_id']);
            $netAmount = (float) $assessment['net_amount'];
            $currentPaid = (float) $assessment['total_paid'];
            $balance = $netAmount - $currentPaid;

            if ($balance <= 0.0) {
                throw new Exception('This assessment has already been fully settled.');
            }

            if ($amount > ($balance + 0.0001)) {
                throw new Exception('Payment amount (₱' . number_format($amount, 2) . ') cannot exceed the remaining balance of ₱' . number_format(max(0, $balance), 2) . '.');
            }

            $minPayment = min(3000.0, $balance);
            if ($amount < $minPayment) {
                throw new Exception('Minimum payment amount is ₱' . number_format($minPayment, 2) . '.');
            }

            // 2. Generate Concurrency-Safe Atomic Receipt Number (REC-YYYYMMDD-XXXX)
            $receiptNumber = generateAtomicReceiptNumber($this->pdo);

            // 3. Persist Payment Record in Central Ledger via Repository
            $paymentId = $this->paymentRepository->insert([
                'assessment_id'    => $assessmentId,
                'user_id'          => $userId,
                'cashier_id'       => $cashierId,
                'amount'           => $amount,
                'payment_date'     => date('Y-m-d'),
                'payment_method'   => $method,
                'receipt_number'   => $receiptNumber,
                'reference_number' => $refNo !== '' ? $refNo : null,
                'status'           => 'verified',
            ]);

            // 4. Update Assessment Totals and Status
            $newPaid = $currentPaid + $amount;
            $newStatus = ($newPaid >= ($netAmount - 0.0001)) ? 'paid' : 'partial';

            $updAssStmt = $this->pdo->prepare('UPDATE student_assessments SET total_paid = :paid, payment_status = :status WHERE id = :id');
            $updAssStmt->execute([
                'paid'   => $newPaid,
                'status' => $newStatus,
                'id'     => $assessmentId,
            ]);

            // 5. Transition Application Status to 'payment_verified' (Decoupled Registrar Gate, ADR-008)
            if ($newStatus === 'paid' || $newStatus === 'partial') {
                $updApp = $this->pdo->prepare('UPDATE applications SET status = "payment_verified" WHERE id = :app_id AND status != "enrolled"');
                $updApp->execute(['app_id' => $applicationId]);
            }

            // 6. Record Student Activity Log
            $logPayStmt = $this->pdo->prepare('
                INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description) 
                VALUES (:user_id, :ip_address, :affected_record, "bi-receipt-cutoff text-primary", "Payment Received", :desc)
            ');
            $logPayStmt->execute([
                'user_id'         => $userId,
                'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? null,
                'affected_record' => "Assessment #$assessmentId",
                'desc'            => "A payment of ₱" . number_format($amount, 2) . " was successfully recorded. Receipt No: {$receiptNumber}",
            ]);

            // 7. Record Admin Audit Log
            if ($cashierId !== null) {
                logActivity(
                    $cashierId,
                    'bi-cash',
                    'Payment Recorded',
                    "Recorded payment of ₱" . number_format($amount, 2) . " (Receipt: $receiptNumber) for Assessment #$assessmentId.",
                    "Payment Record #$paymentId",
                    ['total_paid' => $currentPaid, 'payment_status' => $assessment['payment_status']],
                    ['total_paid' => $newPaid, 'payment_status' => $newStatus]
                );
            }

            if (!$isExternalTx) {
                $this->pdo->commit();
            }

            return [
                'success'        => true,
                'payment_id'     => $paymentId,
                'receipt_number' => $receiptNumber,
                'payment_status' => $newStatus,
                'total_paid'     => $newPaid,
                'balance'        => max(0.0, $netAmount - $newPaid),
                'message'        => "Payment recorded successfully. Receipt No: {$receiptNumber}",
            ];
        } catch (Exception $e) {
            if (!$isExternalTx && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Verifies and approves a pending online payment proof submitted by an applicant.
     *
     * @param int $paymentId Primary key of payment_records row
     * @param int $cashierId User ID of cashier approving the payment
     * @return array Result metadata including payment_id, receipt_number, new balances, and payment_status
     * @throws Exception On invalid state, overpayment, or failure
     */
    public function verifyOnlinePayment(int $paymentId, int $cashierId): array
    {
        if ($paymentId <= 0) {
            throw new Exception('Invalid payment ID provided.');
        }

        $isExternalTx = $this->pdo->inTransaction();
        if (!$isExternalTx) {
            $this->pdo->beginTransaction();
        }

        try {
            // 1. Lock and fetch Payment Record
            $payment = $this->paymentRepository->findById($paymentId, true);

            if (!$payment || $payment['status'] !== 'pending') {
                throw new Exception('Payment record not found or already processed.');
            }

            if (($payment['gateway'] ?? '') === 'paymongo') {
                throw new Exception('PayMongo online payments are verified automatically by the gateway and do not require cashier verification.');
            }

            $assessmentId = (int) $payment['assessment_id'];
            $userId = (int) $payment['user_id'];
            $amount = (float) $payment['amount'];

            // 2. Lock and fetch Student Assessment
            $assStmt = $this->pdo->prepare('SELECT * FROM student_assessments WHERE id = :id FOR UPDATE');
            $assStmt->execute(['id' => $assessmentId]);
            $assessment = $assStmt->fetch(PDO::FETCH_ASSOC);

            if (!$assessment) {
                throw new Exception('Associated assessment record not found.');
            }

            $netAmount = (float) $assessment['net_amount'];
            $currentPaid = (float) $assessment['total_paid'];
            $balance = $netAmount - $currentPaid;

            // 3. Enforce Overpayment & Settled Account Protections
            if ($balance <= 0.0) {
                throw new Exception('This assessment has already been fully paid.');
            }

            if ($amount > ($balance + 0.01)) {
                throw new Exception('Payment amount (₱' . number_format($amount, 2) . ') cannot exceed the remaining balance of ₱' . number_format(max(0, $balance), 2) . '.');
            }

            // 4. Generate Concurrency-Safe Atomic Receipt Number
            $receiptNumber = generateAtomicReceiptNumber($this->pdo);

            // 5. Update Payment Record via Repository
            $this->paymentRepository->updateStatus(
                $paymentId,
                'verified',
                $receiptNumber,
                $cashierId
            );

            // 6. Update Assessment Balance and Status
            $newPaid = $currentPaid + $amount;
            $newStatus = ($newPaid >= ($netAmount - 0.0001)) ? 'paid' : 'partial';

            $updAssStmt = $this->pdo->prepare('UPDATE student_assessments SET total_paid = :paid, payment_status = :status WHERE id = :id');
            $updAssStmt->execute([
                'paid'   => $newPaid,
                'status' => $newStatus,
                'id'     => $assessmentId,
            ]);

            // 7. Transition Application Status to 'payment_verified' (ADR-008)
            if ($newStatus === 'paid' || $newStatus === 'partial') {
                $updApp = $this->pdo->prepare('UPDATE applications SET status = "payment_verified" WHERE id = :app_id AND status != "enrolled"');
                $updApp->execute(['app_id' => (int) $assessment['application_id']]);
            }

            // 8. Record Activity Logs
            $logPayStmt = $this->pdo->prepare('
                INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description) 
                VALUES (:user_id, :ip_address, :affected_record, "bi-receipt-cutoff text-primary", "Payment Verified", :desc)
            ');
            $logPayStmt->execute([
                'user_id'         => $userId,
                'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? null,
                'affected_record' => "Assessment #$assessmentId",
                'desc'            => "Your online payment of ₱" . number_format($amount, 2) . " was verified. Receipt No: {$receiptNumber}",
            ]);

            logActivity(
                $cashierId,
                'bi-shield-check',
                'Online Payment Verified',
                "Verified online payment of ₱" . number_format($amount, 2) . " (Receipt: $receiptNumber) for Assessment #$assessmentId.",
                "Payment Record #$paymentId",
                ['status' => 'pending'],
                ['status' => 'verified']
            );

            if (!$isExternalTx) {
                $this->pdo->commit();
            }

            return [
                'success'        => true,
                'payment_id'     => $paymentId,
                'receipt_number' => $receiptNumber,
                'payment_status' => $newStatus,
                'total_paid'     => $newPaid,
                'balance'        => max(0.0, $netAmount - $newPaid),
                'message'        => "Online payment verified successfully! Receipt No: {$receiptNumber}",
            ];
        } catch (Exception $e) {
            if (!$isExternalTx && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Rejects an invalid or fraudulent online payment proof with a mandatory explanation.
     *
     * @param int $paymentId Primary key of payment_records row
     * @param int $cashierId User ID of cashier rejecting the payment
     * @param string $remarks Rejection explanation/reason
     * @return array Result array with success boolean and payment_id
     * @throws Exception On validation failure or database error
     */
    public function rejectOnlinePayment(int $paymentId, int $cashierId, string $remarks): array
    {
        $remarks = trim($remarks);
        if ($paymentId <= 0) {
            throw new Exception('Invalid payment ID provided.');
        }

        if (empty($remarks)) {
            throw new Exception('A reason for rejection is required. Please provide a remark.');
        }

        $isExternalTx = $this->pdo->inTransaction();
        if (!$isExternalTx) {
            $this->pdo->beginTransaction();
        }

        try {
            // 1. Lock and fetch Payment Record
            $payment = $this->paymentRepository->findById($paymentId, true);

            if (!$payment || $payment['status'] !== 'pending') {
                throw new Exception('Payment record not found or already processed.');
            }

            $assessmentId = (int) $payment['assessment_id'];
            $userId = (int) $payment['user_id'];
            $amount = (float) $payment['amount'];

            // 2. Update Payment Record to Rejected via Repository
            $this->paymentRepository->updateStatus(
                $paymentId,
                'rejected',
                null,
                $cashierId,
                $remarks
            );

            // 3. Record Student Activity Log
            $studentLogDesc = "Your online payment of ₱" . number_format($amount, 2) . " was rejected by the cashier. Reason: " . htmlspecialchars($remarks, ENT_QUOTES, 'UTF-8') . " Please submit a valid proof of payment.";
            $logPayStmt = $this->pdo->prepare('
                INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description) 
                VALUES (:user_id, :ip_address, :affected_record, "bi-x-circle text-danger", "Payment Rejected", :desc)
            ');
            $logPayStmt->execute([
                'user_id'         => $userId,
                'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? null,
                'affected_record' => "Assessment #$assessmentId",
                'desc'            => $studentLogDesc,
            ]);

            // 4. Record Admin Audit Log
            logActivity(
                $cashierId,
                'bi-shield-x',
                'Online Payment Rejected',
                "Rejected online payment of ₱" . number_format($amount, 2) . " for Assessment #$assessmentId.",
                "Payment Record #$paymentId",
                ['status' => 'pending'],
                ['status' => 'rejected']
            );

            if (!$isExternalTx) {
                $this->pdo->commit();
            }

            return [
                'success'    => true,
                'payment_id' => $paymentId,
            ];
        } catch (Exception $e) {
            if (!$isExternalTx && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Submits a new online payment proof for review, validating balances, duplicate references,
     * and inserting the pending record into payment_records under row locks.
     *
     * @param array $data ['assessment_id', 'user_id', 'amount', 'payment_method', 'reference_number', 'proof_image']
     * @return array Result array with success boolean and payment_id
     * @throws Exception On validation error, duplicate reference, or insufficient balance
     */
    public function submitPaymentProof(array $data): array
    {
        $assessmentId = (int) ($data['assessment_id'] ?? 0);
        $userId = (int) ($data['user_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0.0);
        $method = trim((string) ($data['payment_method'] ?? ''));
        $refNo = trim((string) ($data['reference_number'] ?? ''));
        $proofImage = trim((string) ($data['proof_image'] ?? ''));

        if ($assessmentId <= 0) {
            throw new Exception('Invalid assessment selected. Please refresh the page and try again.');
        }

        if ($amount <= 0.0) {
            throw new Exception('Please enter a valid payment amount greater than ₱0.00.');
        }

        if (empty($method)) {
            throw new Exception('Please select the payment method you used (GCash, Maya, or Bank Transfer).');
        }

        if (empty($refNo) || strlen($refNo) < 4) {
            throw new Exception('Please enter a valid transaction reference number (at least 4 characters).');
        }

        if (empty($proofImage)) {
            throw new Exception('Receipt proof image filename is required.');
        }

        $isExternalTx = $this->pdo->inTransaction();
        if (!$isExternalTx) {
            $this->pdo->beginTransaction();
        }

        try {
            // 1. Lock and verify Assessment belongs to applicant
            $assStmt = $this->pdo->prepare('
                SELECT sa.* 
                FROM student_assessments sa
                INNER JOIN applications a ON sa.application_id = a.id
                WHERE sa.id = :id AND a.user_id = :user_id
                FOR UPDATE
            ');
            $assStmt->execute(['id' => $assessmentId, 'user_id' => $userId]);
            $assessment = $assStmt->fetch(PDO::FETCH_ASSOC);

            if (!$assessment) {
                throw new Exception('Assessment record not found or you are not authorized to submit payment for this account.');
            }

            // 2. Lock & check for duplicate reference number across pending or verified records
            if ($this->paymentRepository->isReferenceTaken($refNo, true)) {
                throw new Exception("A payment submission with reference number '{$refNo}' is already pending or verified.");
            }

            // 3. Stale PayMongo session recovery & calculate pending amounts for this assessment
            $activePayMongo = $this->paymentRepository->findActivePayMongoSessionForAssessment($assessmentId, 30, true);
            if ($activePayMongo) {
                $csId = (string) ($activePayMongo['checkout_session_id'] ?? '');
                try {
                    $this->cancelPayMongoPayment((int)$activePayMongo['id'], $userId, 'Cancelled automatically to submit manual payment proof', $csId);
                } catch (\Throwable $e) {
                    error_log('Error cancelling abandoned PayMongo session for manual proof: ' . $e->getMessage());
                }
            }

            $pendingAmount = $this->paymentRepository->getPendingTotalForAssessment($assessmentId, true);
            $balance = (float) $assessment['net_amount'] - (float) $assessment['total_paid'] - $pendingAmount;

            if ($balance <= 0.0) {
                throw new Exception('You have fully settled your balance or already have pending payments covering your full assessment.');
            }

            if ($amount > ($balance + 0.01)) {
                throw new Exception('The payment amount (₱' . number_format($amount, 2) . ') exceeds your allowable remaining balance of ₱' . number_format($balance, 2) . '.');
            }

            $minPayment = min(500.0, $balance);
            if ($amount < $minPayment) {
                throw new Exception('The minimum payment allowed is ₱' . number_format($minPayment, 2) . '.');
            }

            // 4. Insert into payment_records as "pending" via Repository
            $paymentId = $this->paymentRepository->insert([
                'assessment_id'    => $assessmentId,
                'user_id'          => $userId,
                'amount'           => $amount,
                'payment_date'     => date('Y-m-d'),
                'payment_method'   => $method,
                'reference_number' => $refNo,
                'proof_image'      => $proofImage,
                'status'           => 'pending',
            ]);

            // 5. Log Activity
            logActivity(
                $userId,
                'bi-cloud-upload',
                'Payment Proof Uploaded',
                "Submitted proof of payment (Ref: $refNo, Method: $method) for ₱" . number_format($amount, 2)
            );

            if (!$isExternalTx) {
                $this->pdo->commit();
            }

            return [
                'success'    => true,
                'payment_id' => $paymentId,
            ];
        } catch (Exception $e) {
            if (!$isExternalTx && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Initiates a server-side PayMongo online checkout session, performing domain validations,
     * checking for in-flight duplicate sessions, creating a pending record in the central ledger,
     * and attaching PayMongo metadata.
     *
     * @param array $data ['assessment_id', 'user_id', 'amount', 'customer_name', 'customer_email', 'customer_phone', 'success_url', 'cancel_url']
     * @param PayMongoService|null $payMongoService Optional injected gateway service for mocking/testing
     * @return array ['success' => true, 'payment_id' => int, 'checkout_session_id' => string, 'checkout_url' => string, 'payment_intent_id' => ?string, 'status' => 'pending']
     * @throws Exception On validation, duplicate session, overpayment, configuration, or API error
     */
    public function initiatePayMongoPayment(array $data, ?PayMongoService $payMongoService = null): array
    {
        $assessmentId = (int) ($data['assessment_id'] ?? 0);
        $userId = (int) ($data['user_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0.0);

        if ($assessmentId <= 0) {
            throw new Exception('Invalid assessment selected. Please refresh the page and try again.');
        }

        if ($amount <= 0.0) {
            throw new Exception('Please enter a valid payment amount greater than ₱0.00.');
        }

        if ($amount < 100.0) {
            throw new Exception('Minimum payment amount for online checkout is ₱100.00.');
        }

        $isExternalTx = $this->pdo->inTransaction();
        if (!$isExternalTx) {
            $this->pdo->beginTransaction();
        }

        try {
            // 1. Lock and fetch Student Assessment
            $assStmt = $this->pdo->prepare('
                SELECT sa.*, a.user_id as app_user_id
                FROM student_assessments sa
                INNER JOIN applications a ON sa.application_id = a.id
                WHERE sa.id = :id AND a.user_id = :user_id
                FOR UPDATE
            ');
            $assStmt->execute(['id' => $assessmentId, 'user_id' => $userId]);
            $assessment = $assStmt->fetch(PDO::FETCH_ASSOC);

            if (!$assessment) {
                throw new Exception('Assessment record not found or you are not authorized to submit payment for this account.');
            }

            $netAmount = (float) $assessment['net_amount'];
            $currentPaid = (float) $assessment['total_paid'];
            $balance = $netAmount - $currentPaid;

            if ($balance <= 0.0001 || $assessment['payment_status'] === 'paid') {
                throw new Exception('This assessment has already been fully paid.');
            }

            // 2. Duplicate initiation protection & stale session recovery
            $activeSession = $this->paymentRepository->findActivePayMongoSessionForAssessment($assessmentId, 30, true);
            if ($activeSession !== null) {
                $csId = (string) ($activeSession['checkout_session_id'] ?? '');
                if ($csId !== '') {
                    try {
                        $verifyRes = $this->autoVerifyPayMongoCheckoutSession($csId, $payMongoService);
                        if ($verifyRes['status'] === 'verified') {
                            return [
                                'success'             => true,
                                'payment_id'          => $verifyRes['payment_id'],
                                'checkout_session_id' => $csId,
                                'checkout_url'        => $activeSession['checkout_url'],
                                'payment_intent_id'   => $activeSession['payment_intent_id'] ?? null,
                                'status'              => 'verified',
                                'already_verified'    => true,
                                'receipt_number'      => $verifyRes['receipt_number'] ?? null,
                                'message'             => 'Your previous payment was already completed and verified! Official Receipt No: ' . ($verifyRes['receipt_number'] ?? 'N/A'),
                            ];
                        }
                    } catch (\Throwable $e) {
                        // Continue to double-click / abandonment evaluation
                    }
                }

                $createdAt = strtotime((string) $activeSession['created_at']);
                if ((time() - $createdAt) < 15) {
                    throw new Exception('An online checkout session is already pending for this assessment. Please complete or cancel the existing session before initiating a new one.');
                }

                // Stale or abandoned previous session: cancel it and free the ledger
                try {
                    $this->cancelPayMongoPayment((int) $activeSession['id'], $userId, 'Superseded by new checkout initiation', $csId, $payMongoService);
                } catch (\Throwable $e) {
                    error_log('Error cancelling abandoned PayMongo session: ' . $e->getMessage());
                }
            }

            // 3. Calculate dynamic pending amounts for this assessment
            $pendingAmount = $this->paymentRepository->getPendingTotalForAssessment($assessmentId, true);
            $allowableRemaining = $balance - $pendingAmount;

            if ($allowableRemaining <= 0.0001) {
                throw new Exception('You already have pending payments covering your full assessment balance. Please wait for verification.');
            }

            if ($amount > ($allowableRemaining + 0.01)) {
                throw new Exception('The payment amount (₱' . number_format($amount, 2) . ') cannot exceed your allowable remaining balance of ₱' . number_format(max(0.0, $allowableRemaining), 2) . '.');
            }

            // 4. Create pending payment record in central ledger (payment_records)
            $paymentId = $this->paymentRepository->insert([
                'assessment_id'       => $assessmentId,
                'user_id'             => $userId,
                'amount'              => $amount,
                'payment_date'        => date('Y-m-d'),
                'payment_method'      => 'PayMongo',
                'gateway'             => 'paymongo',
                'status'              => 'pending',
                'checkout_session_id' => null,
                'reference_number'    => 'PM-INIT-' . $assessmentId . '-' . time() . '-' . bin2hex(random_bytes(3)),
            ]);

            // 5. Fetch user profile for billing details if not supplied
            $customerName = trim((string) ($data['customer_name'] ?? ''));
            $customerEmail = trim((string) ($data['customer_email'] ?? ''));
            $customerPhone = trim((string) ($data['customer_phone'] ?? ''));

            if ($customerName === '' || $customerEmail === '') {
                $userStmt = $this->pdo->prepare('SELECT first_name, last_name, email FROM users WHERE id = :uid LIMIT 1');
                $userStmt->execute(['uid' => $userId]);
                $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
                if ($userRow) {
                    if ($customerName === '') {
                        $customerName = trim($userRow['first_name'] . ' ' . $userRow['last_name']);
                    }
                    if ($customerEmail === '') {
                        $customerEmail = (string) $userRow['email'];
                    }
                }
            }

            // 6. Invoke PayMongo service to create checkout session
            $gatewayService = $payMongoService ?? new PayMongoService();
            $sessionResult = $gatewayService->createCheckoutSession([
                'amount'         => $amount,
                'description'    => "TTU Tuition Assessment #{$assessmentId}",
                'customer_name'  => $customerName !== '' ? $customerName : 'TTU Student',
                'customer_email' => $customerEmail !== '' ? $customerEmail : 'student@ttu.edu.ph',
                'customer_phone' => $customerPhone !== '' ? $customerPhone : '09123456789',
                'metadata'       => [
                    'assessment_id'     => (string) $assessmentId,
                    'user_id'           => (string) $userId,
                    'payment_record_id' => (string) $paymentId,
                ],
                'success_url'    => $data['success_url'] ?? null,
                'cancel_url'     => $data['cancel_url'] ?? null,
            ]);

            // 7. Link PayMongo session ID, URL, and intent to the payment record
            $this->paymentRepository->updatePayMongoSession(
                $paymentId,
                $sessionResult['checkout_session_id'],
                $sessionResult['checkout_url'],
                $sessionResult['payment_intent_id']
            );

            // 7b. Link PayMongo checkout details to concurrent payment_sessions queue record
            $sessionToken = trim((string) ($data['session_token'] ?? ''));
            if ($sessionToken !== '') {
                try {
                    $queueService = new PaymentQueueService(null, $this->pdo);
                    $queueService->linkPayMongoCheckout(
                        $sessionToken,
                        $sessionResult['checkout_session_id'],
                        $sessionResult['checkout_url'],
                        $paymentId
                    );
                } catch (Throwable $e) {
                    error_log('Non-fatal error linking queue session: ' . $e->getMessage());
                }
            }

            // 8. Log activity
            logActivity(
                $userId,
                'bi-credit-card',
                'Online Checkout Initiated',
                "Initiated PayMongo online payment of ₱" . number_format($amount, 2) . " (Session: {$sessionResult['checkout_session_id']})"
            );

            if (!$isExternalTx) {
                $this->pdo->commit();
            }

            return [
                'success'             => true,
                'payment_id'          => $paymentId,
                'checkout_session_id' => $sessionResult['checkout_session_id'],
                'checkout_url'        => $sessionResult['checkout_url'],
                'payment_intent_id'   => $sessionResult['payment_intent_id'],
                'status'              => 'pending',
                'message'             => 'PayMongo checkout session created successfully.',
            ];
        } catch (Exception $e) {
            if (!$isExternalTx && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Reconciles a cryptographically verified PayMongo server webhook notification.
     *
     * Core Invariants Enforced:
     * 1. Idempotency: Duplicate events for already verified transactions exit safely with HTTP 200 without duplicate credit.
     * 2. Transaction Integrity: Uses a PDO transaction with row-level locks on payment_records and student_assessments.
     * 3. Overpayment Prevention: Live assessment balances are checked under row lock before crediting.
     * 4. Atomic Official Receipt: Receipts are issued ONLY after server-side payment confirmation.
     * 5. Registrar Boundary: Preserves enrollment gate (only transitions application to 'payment_verified', never 'enrolled').
     *
     * @param array $eventPayload Decoded webhook JSON payload
     * @param string|null $rawPayload Byte-for-byte raw JSON string for audit storage
     * @return array Reconciliation result metadata
     * @throws Exception On domain validation error, overpayment, or database failure
     */
    public function processPayMongoWebhook(array $eventPayload, ?string $rawPayload = null): array
    {
        $isExternalTx = $this->pdo->inTransaction();
        if (!$isExternalTx) {
            $this->pdo->beginTransaction();
        }

        try {
            // 1. Extract Event Attributes
            $eventData = $eventPayload['data'] ?? [];
            $eventId = (string) ($eventData['id'] ?? 'unknown_evt');
            $eventType = (string) ($eventData['attributes']['type'] ?? '');
            $resourceData = (array) ($eventData['attributes']['data'] ?? []);
            $resourceType = (string) ($resourceData['type'] ?? '');
            $resourceId = (string) ($resourceData['id'] ?? '');
            $resourceAttrs = (array) ($resourceData['attributes'] ?? []);
            $resourceStatus = strtolower((string) ($resourceAttrs['status'] ?? ''));
            $metadata = (array) ($resourceAttrs['metadata'] ?? []);

            // 2. Extract Processing Fee
            $gatewayFee = 0.00;
            if (!empty($resourceAttrs['payments'][0]['attributes']['fee'])) {
                $gatewayFee = ((float) $resourceAttrs['payments'][0]['attributes']['fee']) / 100.0;
            } elseif (isset($resourceAttrs['fee'])) {
                $gatewayFee = ((float) $resourceAttrs['fee']) / 100.0;
            }

            // 3. Extract Payment Intent ID
            $paymentIntentId = null;
            if (isset($resourceAttrs['payment_intent']['id'])) {
                $paymentIntentId = (string) $resourceAttrs['payment_intent']['id'];
            } elseif (isset($resourceAttrs['payment_intent_id'])) {
                $paymentIntentId = (string) $resourceAttrs['payment_intent_id'];
            } elseif (is_string($resourceAttrs['payment_intent'] ?? null)) {
                $paymentIntentId = (string) $resourceAttrs['payment_intent'];
            }

            // 4. Locate Target Payment Record with Row Lock
            $payment = null;

            // Strategy A: Lookup by Checkout Session ID
            if ($resourceType === 'checkout_session' && $resourceId !== '') {
                $payment = $this->paymentRepository->findByCheckoutSessionId($resourceId, true);
            }

            // Strategy B: Lookup by Payment Intent ID
            if (!$payment && !empty($paymentIntentId)) {
                $payment = $this->paymentRepository->findByPaymentIntentId($paymentIntentId, true);
            }

            // Strategy C: Lookup by resourceId if resource is payment and matches payment_intent_id
            if (!$payment && $resourceType === 'payment' && $resourceId !== '') {
                $payment = $this->paymentRepository->findByPaymentIntentId($resourceId, true);
            }

            // Strategy D: Fallback to metadata payment_record_id
            if (!$payment && !empty($metadata['payment_record_id'])) {
                $recId = (int) $metadata['payment_record_id'];
                if ($recId > 0) {
                    $candidate = $this->paymentRepository->findById($recId, true);
                    if ($candidate && ($candidate['gateway'] === 'paymongo' || $candidate['payment_method'] === 'PayMongo')) {
                        $payment = $candidate;
                    }
                }
            }

            // 5. Unknown Transaction Guard
            if ($payment === null) {
                if (!$isExternalTx) {
                    $this->pdo->commit();
                }
                return [
                    'status'  => 'unknown_transaction',
                    'message' => "Payment record not found for {$resourceType} {$resourceId}.",
                ];
            }

            $paymentId = (int) $payment['id'];

            // 6. Idempotency Check: Already Processed Guard
            if ($payment['status'] === 'verified') {
                if (!$isExternalTx) {
                    $this->pdo->commit();
                }
                return [
                    'status'         => 'already_processed',
                    'payment_id'     => $paymentId,
                    'receipt_number' => $payment['receipt_number'],
                    'message'        => "Payment #{$paymentId} has already been verified and processed (Receipt: {$payment['receipt_number']}).",
                ];
            }

            // 7. Successful Payment Handling
            $isPaid = (
                $eventType === 'checkout_session.payment.paid' ||
                ($eventType === 'payment.paid' && in_array($resourceStatus, ['paid', 'succeeded', ''], true)) ||
                $resourceStatus === 'paid'
            );

            if ($isPaid) {
                $assessmentId = (int) $payment['assessment_id'];
                $userId = (int) $payment['user_id'];
                $paymentAmount = (float) $payment['amount'];

                // 7a. Lock and fetch Student Assessment
                $assStmt = $this->pdo->prepare('SELECT * FROM student_assessments WHERE id = :id LIMIT 1 FOR UPDATE');
                $assStmt->execute(['id' => $assessmentId]);
                $assessment = $assStmt->fetch(PDO::FETCH_ASSOC);

                if (!$assessment) {
                    throw new Exception("Associated student assessment #{$assessmentId} not found.");
                }

                $netAmount = (float) $assessment['net_amount'];
                $currentPaid = (float) $assessment['total_paid'];
                $balance = $netAmount - $currentPaid;

                // 7b. Enforce Overpayment and Settled Account Protections
                if ($balance <= 0.0001 || $assessment['payment_status'] === 'paid') {
                    throw new Exception("Assessment #{$assessmentId} has already been fully settled.");
                }

                if ($paymentAmount > ($balance + 0.01)) {
                    throw new Exception("Payment amount (₱" . number_format($paymentAmount, 2) . ") exceeds remaining balance of ₱" . number_format(max(0.0, $balance), 2) . ".");
                }

                // 7c. Generate Concurrency-Safe Official TTU Atomic Receipt Number
                $receiptNumber = generateAtomicReceiptNumber($this->pdo);

                // 7d. Update Payment Record via Repository
                $this->paymentRepository->recordWebhookConfirmation(
                    $paymentId,
                    'verified',
                    $receiptNumber,
                    $gatewayFee,
                    $paymentIntentId,
                    $rawPayload,
                    "Confirmed via PayMongo Webhook ({$eventType}, Event: {$eventId})"
                );

                // 7e. Update Assessment Balance and Status
                $newPaid = $currentPaid + $paymentAmount;
                $newStatus = ($newPaid >= ($netAmount - 0.0001)) ? 'paid' : 'partial';

                $updAssStmt = $this->pdo->prepare('UPDATE student_assessments SET total_paid = :paid, payment_status = :status WHERE id = :id');
                $updAssStmt->execute([
                    'paid'   => $newPaid,
                    'status' => $newStatus,
                    'id'     => $assessmentId,
                ]);

                // 7f. Transition Application Status to 'payment_verified' (Decoupled Registrar Gate, ADR-008)
                $applicationId = (int) $assessment['application_id'];
                if ($newStatus === 'paid' || $newStatus === 'partial') {
                    $updApp = $this->pdo->prepare('UPDATE applications SET status = "payment_verified" WHERE id = :app_id AND status != "enrolled"');
                    $updApp->execute(['app_id' => $applicationId]);
                }

                // 7g. Record Student and Administrative Activity Logs
                $logPayStmt = $this->pdo->prepare('
                    INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description) 
                    VALUES (:user_id, :ip_address, :affected_record, "bi-credit-card text-success", "Online Payment Confirmed", :desc)
                ');
                $logPayStmt->execute([
                    'user_id'         => $userId,
                    'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'affected_record' => "Assessment #{$assessmentId}",
                    'desc'            => "Your online payment of ₱" . number_format($paymentAmount, 2) . " was confirmed via PayMongo. Receipt No: {$receiptNumber}",
                ]);

                logActivity(
                    $userId,
                    'bi-shield-check',
                    'PayMongo Payment Reconciled',
                    "PayMongo confirmed payment of ₱" . number_format($paymentAmount, 2) . " (Receipt: {$receiptNumber}, Gateway Fee: ₱" . number_format($gatewayFee, 2) . ") for Assessment #{$assessmentId}.",
                    "Payment Record #{$paymentId}",
                    ['status' => 'pending'],
                    ['status' => 'verified', 'receipt_number' => $receiptNumber]
                );

                // 7h. Complete associated queue session and recycle concurrency slot
                try {
                    $queueService = new PaymentQueueService(null, $this->pdo);
                    $queueService->completeSessionByCheckoutId($resourceId, $paymentId);
                } catch (Throwable $e) {
                    error_log('Queue completion notification non-fatal error: ' . $e->getMessage());
                }

                if (!$isExternalTx) {
                    $this->pdo->commit();
                }

                return [
                    'status'         => 'verified',
                    'payment_id'     => $paymentId,
                    'receipt_number' => $receiptNumber,
                    'amount'         => $paymentAmount,
                    'gateway_fee'    => $gatewayFee,
                    'total_paid'     => $newPaid,
                    'balance'        => max(0.0, $netAmount - $newPaid),
                    'message'        => "Payment #{$paymentId} verified successfully. Receipt No: {$receiptNumber}",
                ];
            }

            // 8. Handle Payment Failure, Cancellation, or Expiration
            $isFailed = ($eventType === 'payment.failed' || $resourceStatus === 'failed');
            $isCancelled = ($resourceStatus === 'cancelled');
            $isExpired = ($eventType === 'checkout_session.expired' || $resourceStatus === 'expired');

            if ($isFailed || $isCancelled || $isExpired) {
                $newStatus = $isCancelled ? 'cancelled' : ($isExpired ? 'expired' : 'failed');

                if ($payment['status'] === 'pending') {
                    $remarks = "PayMongo transaction ended with status {$newStatus}. Event: {$eventId}";
                    $this->paymentRepository->recordWebhookConfirmation(
                        $paymentId,
                        $newStatus,
                        null,
                        $gatewayFee,
                        $paymentIntentId,
                        $rawPayload,
                        $remarks
                    );

                    logActivity(
                        (int) $payment['user_id'],
                        'bi-exclamation-triangle',
                        "PayMongo Payment " . ucfirst($newStatus),
                        "PayMongo transaction #{$paymentId} marked as {$newStatus} (Event: {$eventId}).",
                        "Payment Record #{$paymentId}",
                        ['status' => 'pending'],
                        ['status' => $newStatus]
                    );
                }

                if (!$isExternalTx) {
                    $this->pdo->commit();
                }

                return [
                    'status'     => $newStatus,
                    'payment_id' => $paymentId,
                    'message'    => "Payment #{$paymentId} marked as {$newStatus}.",
                ];
            }

            // 9. Unhandled Event Types
            if (!$isExternalTx) {
                $this->pdo->commit();
            }

            return [
                'status'     => 'ignored',
                'payment_id' => $paymentId,
                'message'    => "Event '{$eventType}' acknowledged but ignored for Payment #{$paymentId}.",
            ];

        } catch (Exception $e) {
            if (!$isExternalTx && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Automatically verifies a PayMongo checkout session by querying the PayMongo REST API directly.
     * Enforces atomic transaction, row-level locks, official receipt issuance, balance update,
     * application status transition, queue slot completion, and activity logging.
     *
     * @param string $sessionId PayMongo checkout session ID (cs_...)
     * @param PayMongoService|null $payMongoService Optional gateway service instance
     * @return array Verification outcome metadata
     */
    public function autoVerifyPayMongoCheckoutSession(string $sessionId, ?PayMongoService $payMongoService = null): array
    {
        $sessionId = trim($sessionId);
        if ($sessionId === '') {
            return [
                'status'  => 'error',
                'message' => 'Empty PayMongo checkout session ID provided.',
            ];
        }

        $isExternalTx = $this->pdo->inTransaction();
        if (!$isExternalTx) {
            $this->pdo->beginTransaction();
        }

        try {
            // 1. Locate Target Payment Record with Row Lock
            $payment = $this->paymentRepository->findByCheckoutSessionId($sessionId, true);

            if ($payment === null) {
                if (!$isExternalTx) {
                    $this->pdo->commit();
                }
                return [
                    'status'  => 'not_found',
                    'message' => "Payment record not found for session {$sessionId}.",
                ];
            }

            $paymentId = (int) $payment['id'];

            // 2. Idempotency Check: Already Processed Guard
            if ($payment['status'] === 'verified') {
                if (!$isExternalTx) {
                    $this->pdo->commit();
                }
                return [
                    'status'           => 'verified',
                    'already_verified' => true,
                    'payment_id'       => $paymentId,
                    'receipt_number'   => $payment['receipt_number'],
                    'amount'           => (float) $payment['amount'],
                    'message'          => "Payment #{$paymentId} has already been verified (Receipt: {$payment['receipt_number']}).",
                ];
            }

            if (in_array($payment['status'], ['cancelled', 'expired', 'failed'], true)) {
                if (!$isExternalTx) {
                    $this->pdo->commit();
                }
                return [
                    'status'     => $payment['status'],
                    'payment_id' => $paymentId,
                    'message'    => "Payment #{$paymentId} is marked as {$payment['status']}.",
                ];
            }

            // 3. Query PayMongo API for Live Status
            $gateway = $payMongoService ?? new PayMongoService();
            try {
                $sessionResponse = $gateway->getCheckoutSession($sessionId);
            } catch (\Throwable $apiEx) {
                error_log("PayMongo API query error for {$sessionId}: " . $apiEx->getMessage());
                if (!$isExternalTx) {
                    $this->pdo->commit();
                }
                return [
                    'status'  => 'api_error',
                    'message' => 'Could not retrieve PayMongo session status: ' . $apiEx->getMessage(),
                ];
            }

            $data = $sessionResponse['data'] ?? [];
            $attrs = $data['attributes'] ?? [];
            $sessionStatus = strtolower((string) ($attrs['status'] ?? ''));
            $payments = (array) ($attrs['payments'] ?? []);
            $paymentIntent = $attrs['payment_intent'] ?? null;
            $paymentIntentStatus = '';
            $paymentIntentId = null;

            if (is_array($paymentIntent)) {
                $paymentIntentId = (string) ($paymentIntent['id'] ?? '');
                $paymentIntentStatus = strtolower((string) ($paymentIntent['attributes']['status'] ?? ''));
            } elseif (is_string($paymentIntent)) {
                $paymentIntentId = $paymentIntent;
            }

            $gatewayFee = 0.00;
            $hasPaidPayment = false;
            foreach ($payments as $p) {
                $pStatus = strtolower((string) ($p['attributes']['status'] ?? ''));
                if ($pStatus === 'paid' || $pStatus === 'succeeded') {
                    $hasPaidPayment = true;
                    if (!empty($p['attributes']['fee'])) {
                        $gatewayFee = ((float) $p['attributes']['fee']) / 100.0;
                    }
                    break;
                }
            }

            $isPaid = ($sessionStatus === 'paid' || $hasPaidPayment || $paymentIntentStatus === 'succeeded');

            // 4. Case: Payment Successful on PayMongo -> Atomically Verify
            if ($isPaid) {
                $assessmentId = (int) $payment['assessment_id'];
                $userId = (int) $payment['user_id'];
                $paymentAmount = (float) $payment['amount'];

                // Lock and fetch Student Assessment
                $assStmt = $this->pdo->prepare('SELECT * FROM student_assessments WHERE id = :id LIMIT 1 FOR UPDATE');
                $assStmt->execute(['id' => $assessmentId]);
                $assessment = $assStmt->fetch(PDO::FETCH_ASSOC);

                if (!$assessment) {
                    throw new Exception("Associated student assessment #{$assessmentId} not found.");
                }

                $netAmount = (float) $assessment['net_amount'];
                $currentPaid = (float) $assessment['total_paid'];
                $balance = $netAmount - $currentPaid;

                if ($balance <= 0.0001 && $assessment['payment_status'] === 'paid') {
                    // Assessment already settled
                    if (!$isExternalTx) {
                        $this->pdo->commit();
                    }
                    return [
                        'status'         => 'already_settled',
                        'payment_id'     => $paymentId,
                        'message'        => "Assessment #{$assessmentId} has already been fully settled.",
                    ];
                }

                // Generate Concurrency-Safe Official Receipt
                $receiptNumber = generateAtomicReceiptNumber($this->pdo);

                // Update Payment Record
                $this->paymentRepository->recordWebhookConfirmation(
                    $paymentId,
                    'verified',
                    $receiptNumber,
                    $gatewayFee,
                    $paymentIntentId,
                    json_encode($sessionResponse),
                    "Confirmed via PayMongo API auto-verification (Session: {$sessionId})"
                );

                // Update Assessment
                $newPaid = $currentPaid + $paymentAmount;
                $newStatus = ($newPaid >= ($netAmount - 0.0001)) ? 'paid' : 'partial';

                $updAssStmt = $this->pdo->prepare('UPDATE student_assessments SET total_paid = :paid, payment_status = :status WHERE id = :id');
                $updAssStmt->execute([
                    'paid'   => $newPaid,
                    'status' => $newStatus,
                    'id'     => $assessmentId,
                ]);

                // Transition Application Status to payment_verified
                $applicationId = (int) $assessment['application_id'];
                if ($newStatus === 'paid' || $newStatus === 'partial') {
                    $updApp = $this->pdo->prepare('UPDATE applications SET status = "payment_verified" WHERE id = :app_id AND status != "enrolled"');
                    $updApp->execute(['app_id' => $applicationId]);
                }

                // Activity logs
                $logPayStmt = $this->pdo->prepare('
                    INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description) 
                    VALUES (:user_id, :ip_address, :affected_record, "bi-credit-card text-success", "Online Payment Confirmed", :desc)
                ');
                $logPayStmt->execute([
                    'user_id'         => $userId,
                    'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'affected_record' => "Assessment #{$assessmentId}",
                    'desc'            => "Your online payment of ₱" . number_format($paymentAmount, 2) . " was confirmed via PayMongo. Receipt No: {$receiptNumber}",
                ]);

                logActivity(
                    $userId,
                    'bi-shield-check',
                    'PayMongo Payment Auto-Verified',
                    "PayMongo verified payment of ₱" . number_format($paymentAmount, 2) . " (Receipt: {$receiptNumber}, Fee: ₱" . number_format($gatewayFee, 2) . ") for Assessment #{$assessmentId}.",
                    "Payment Record #{$paymentId}",
                    ['status' => 'pending'],
                    ['status' => 'verified', 'receipt_number' => $receiptNumber]
                );

                // Complete queue session if linked
                try {
                    $queueService = new PaymentQueueService(null, $this->pdo);
                    $queueService->completeSessionByCheckoutId($sessionId, $paymentId);
                } catch (\Throwable $e) {
                    error_log('Queue completion notification non-fatal error: ' . $e->getMessage());
                }

                if (!$isExternalTx) {
                    $this->pdo->commit();
                }

                return [
                    'status'         => 'verified',
                    'payment_id'     => $paymentId,
                    'receipt_number' => $receiptNumber,
                    'amount'         => $paymentAmount,
                    'gateway_fee'    => $gatewayFee,
                    'total_paid'     => $newPaid,
                    'balance'        => max(0.0, $netAmount - $newPaid),
                    'message'        => "Payment #{$paymentId} verified successfully! Receipt No: {$receiptNumber}",
                ];
            }

            // 5. Case: Expired on PayMongo
            if ($sessionStatus === 'expired') {
                $this->paymentRepository->updateStatus($paymentId, 'expired', null, null, 'PayMongo checkout session expired.');
                try {
                    $queueService = new PaymentQueueService(null, $this->pdo);
                    $queueService->completeSessionByCheckoutId($sessionId, $paymentId);
                } catch (\Throwable $e) {}

                if (!$isExternalTx) {
                    $this->pdo->commit();
                }

                return [
                    'status'     => 'expired',
                    'payment_id' => $paymentId,
                    'message'    => 'Online checkout session has expired.',
                ];
            }

            // 6. Case: Active but Unfinished / Returned Without Paying
            // Expire on PayMongo to prevent delayed charges and mark cancelled in local ledger
            try {
                $gateway->expireCheckoutSession($sessionId);
            } catch (\Throwable $e) {}

            $this->paymentRepository->updateStatus($paymentId, 'cancelled', null, null, 'Applicant returned without completing PayMongo payment.');
            try {
                $queueService = new PaymentQueueService(null, $this->pdo);
                $queueService->completeSessionByCheckoutId($sessionId, $paymentId);
            } catch (\Throwable $e) {}

            logActivity(
                (int) $payment['user_id'],
                'bi-x-circle',
                'Online Payment Cancelled',
                "PayMongo checkout session #{$sessionId} cancelled without payment.",
                "Payment Record #{$paymentId}",
                ['status' => 'pending'],
                ['status' => 'cancelled']
            );

            if (!$isExternalTx) {
                $this->pdo->commit();
            }

            return [
                'status'     => 'cancelled',
                'payment_id' => $paymentId,
                'message'    => 'Online checkout was not completed on PayMongo and has been cancelled.',
            ];

        } catch (Exception $e) {
            if (!$isExternalTx && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cancels an incomplete or abandoned PayMongo payment record, expires the session on PayMongo,
     * releases any queue session locks, and restores allowable balance.
     *
     * @param int $paymentId Primary key of payment_records row
     * @param int|null $userId Optional user ID for ownership check
     * @param string $reason Cancellation remarks
     * @param string|null $sessionId Optional PayMongo session ID if already known
     * @param PayMongoService|null $payMongoService Optional gateway instance
     * @return array Cancellation result
     */
    public function cancelPayMongoPayment(
        int $paymentId,
        ?int $userId = null,
        string $reason = 'Cancelled by applicant',
        ?string $sessionId = null,
        ?PayMongoService $payMongoService = null
    ): array {
        $isExternalTx = $this->pdo->inTransaction();
        if (!$isExternalTx) {
            $this->pdo->beginTransaction();
        }

        try {
            $payment = $this->paymentRepository->findById($paymentId, true);
            if (!$payment) {
                if (!$isExternalTx) $this->pdo->commit();
                return ['success' => false, 'message' => 'Payment record not found.'];
            }

            if ($userId !== null && (int)$payment['user_id'] !== $userId) {
                throw new Exception('Unauthorized to cancel this payment record.');
            }

            if ($payment['status'] === 'verified') {
                if (!$isExternalTx) $this->pdo->commit();
                return ['success' => false, 'message' => 'Cannot cancel an already verified payment.'];
            }

            $csId = $sessionId ?: ($payment['checkout_session_id'] ?? null);

            // Attempt to expire on PayMongo
            if (!empty($csId)) {
                try {
                    $gateway = $payMongoService ?? new PayMongoService();
                    $gateway->expireCheckoutSession($csId);
                } catch (\Throwable $e) {
                    error_log("Failed to expire PayMongo session {$csId}: " . $e->getMessage());
                }
            }

            // Update status in DB
            $this->paymentRepository->updateStatus($paymentId, 'cancelled', null, null, $reason);

            // Release queue session
            if (!empty($csId)) {
                try {
                    $queueService = new PaymentQueueService(null, $this->pdo);
                    $queueService->completeSessionByCheckoutId($csId, $paymentId);
                } catch (\Throwable $e) {}
            }

            // Log activity
            logActivity(
                (int)$payment['user_id'],
                'bi-x-circle',
                'PayMongo Payment Cancelled',
                "Payment #{$paymentId} cancelled: {$reason}",
                "Payment Record #{$paymentId}",
                ['status' => $payment['status']],
                ['status' => 'cancelled']
            );

            if (!$isExternalTx) {
                $this->pdo->commit();
            }

            return [
                'success'    => true,
                'payment_id' => $paymentId,
                'status'     => 'cancelled',
                'message'    => 'Payment session cancelled successfully.',
            ];

        } catch (Exception $e) {
            if (!$isExternalTx && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
