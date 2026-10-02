<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Repository responsible for low-level database persistence and query operations
 * on the payment_records table.
 */
class PaymentRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Retrieve a payment record by primary key, optionally with an exclusive row lock.
     */
    public function findById(int $id, bool $lock = false): ?array
    {
        $sql = 'SELECT * FROM payment_records WHERE id = :id' . ($lock ? ' FOR UPDATE' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Retrieve a payment record joined with student, cashier, assessment, and application data.
     */
    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT pr.*, 
                   u.first_name, u.last_name,
                   u.first_name as student_first, u.last_name as student_last, u.student_number, u.email as student_email,
                   c.first_name as cashier_first, c.last_name as cashier_last,
                   a.reference_number as app_ref, a.academic_level, a.grade_level, a.strand,
                   a.school_year as app_school_year, a.semester as app_semester,
                   sa.net_amount, sa.total_paid as assessment_total_paid, sa.payment_status as assessment_payment_status
            FROM payment_records pr
            INNER JOIN users u ON pr.user_id = u.id
            LEFT JOIN users c ON pr.cashier_id = c.id
            INNER JOIN student_assessments sa ON pr.assessment_id = sa.id
            INNER JOIN applications a ON sa.application_id = a.id
            WHERE pr.id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Find a payment record by its unique receipt number.
     */
    public function findByReceiptNumber(string $receiptNumber): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payment_records WHERE receipt_number = :receipt LIMIT 1');
        $stmt->execute(['receipt' => $receiptNumber]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Find a payment record by external reference number, optionally with row lock.
     */
    public function findByReferenceNumber(string $referenceNumber, bool $lock = false): ?array
    {
        $sql = 'SELECT * FROM payment_records WHERE reference_number = :ref LIMIT 1' . ($lock ? ' FOR UPDATE' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['ref' => $referenceNumber]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Check if a reference number has already been registered in a pending or verified status.
     */
    public function isReferenceTaken(string $referenceNumber, bool $lock = false): bool
    {
        $sql = 'SELECT id FROM payment_records WHERE reference_number = :ref AND status IN ("pending", "verified") LIMIT 1' . ($lock ? ' FOR UPDATE' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['ref' => $referenceNumber]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Calculate total unverified pending payments for a given assessment.
     */
    public function getPendingTotalForAssessment(int $assessmentId, bool $lock = false): float
    {
        $sql = 'SELECT COALESCE(SUM(amount), 0) FROM payment_records WHERE assessment_id = :id AND status = "pending"' . ($lock ? ' FOR UPDATE' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $assessmentId]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * Find a payment record by PayMongo checkout session ID.
     */
    public function findByCheckoutSessionId(string $sessionId, bool $lock = false): ?array
    {
        $sql = 'SELECT * FROM payment_records WHERE checkout_session_id = :session_id LIMIT 1' . ($lock ? ' FOR UPDATE' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['session_id' => $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Find a payment record by PayMongo payment intent ID.
     */
    public function findByPaymentIntentId(string $paymentIntentId, bool $lock = false): ?array
    {
        $sql = 'SELECT * FROM payment_records WHERE payment_intent_id = :pi_id LIMIT 1' . ($lock ? ' FOR UPDATE' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['pi_id' => $paymentIntentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Find an active pending PayMongo session for an assessment created recently.
     */
    public function findActivePayMongoSessionForAssessment(int $assessmentId, int $expiryMinutes = 30, bool $lock = false): ?array
    {
        $sql = '
            SELECT * FROM payment_records 
            WHERE assessment_id = :aid 
              AND gateway = "paymongo" 
              AND status = "pending" 
              AND created_at >= (NOW() - INTERVAL :expiry MINUTE)
            ORDER BY id DESC 
            LIMIT 1
        ' . ($lock ? ' FOR UPDATE' : '');

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':aid', $assessmentId, PDO::PARAM_INT);
        $stmt->bindValue(':expiry', $expiryMinutes, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Update PayMongo session details for an existing payment record.
     */
    public function updatePayMongoSession(int $id, string $checkoutSessionId, ?string $checkoutUrl = null, ?string $paymentIntentId = null): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE payment_records 
            SET checkout_session_id = :cs_id,
                checkout_url = COALESCE(:checkout_url, checkout_url),
                payment_intent_id = COALESCE(:pi_id, payment_intent_id),
                reference_number = COALESCE(reference_number, :ref_no)
            WHERE id = :id
        ');

        return $stmt->execute([
            'cs_id'        => $checkoutSessionId,
            'checkout_url' => $checkoutUrl,
            'pi_id'        => $paymentIntentId,
            'ref_no'       => $checkoutSessionId,
            'id'           => $id,
        ]);
    }

    /**
     * Insert a new payment record into the ledger.
     */
    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO payment_records (
                assessment_id, user_id, cashier_id, amount, payment_date,
                payment_method, receipt_number, reference_number, checkout_session_id,
                payment_intent_id, checkout_url, gateway, gateway_fee, proof_image,
                status, remarks, raw_webhook_payload
            ) VALUES (
                :assessment_id, :user_id, :cashier_id, :amount, :payment_date,
                :payment_method, :receipt_number, :reference_number, :checkout_session_id,
                :payment_intent_id, :checkout_url, :gateway, :gateway_fee, :proof_image,
                :status, :remarks, :raw_webhook_payload
            )
        ');

        $stmt->execute([
            'assessment_id'       => (int) $data['assessment_id'],
            'user_id'             => (int) $data['user_id'],
            'cashier_id'          => !empty($data['cashier_id']) ? (int) $data['cashier_id'] : null,
            'amount'              => (float) $data['amount'],
            'payment_date'        => $data['payment_date'] ?? date('Y-m-d'),
            'payment_method'      => (string) ($data['payment_method'] ?? 'PayMongo'),
            'receipt_number'      => !empty($data['receipt_number']) ? (string) $data['receipt_number'] : null,
            'reference_number'    => !empty($data['reference_number']) ? (string) $data['reference_number'] : null,
            'checkout_session_id' => !empty($data['checkout_session_id']) ? (string) $data['checkout_session_id'] : null,
            'payment_intent_id'   => !empty($data['payment_intent_id']) ? (string) $data['payment_intent_id'] : null,
            'checkout_url'        => !empty($data['checkout_url']) ? (string) $data['checkout_url'] : null,
            'gateway'             => !empty($data['gateway']) ? (string) $data['gateway'] : 'manual',
            'gateway_fee'         => isset($data['gateway_fee']) ? (float) $data['gateway_fee'] : 0.00,
            'proof_image'         => !empty($data['proof_image']) ? (string) $data['proof_image'] : null,
            'status'              => $data['status'] ?? 'pending',
            'remarks'             => !empty($data['remarks']) ? (string) $data['remarks'] : null,
            'raw_webhook_payload' => !empty($data['raw_webhook_payload']) ? (string) $data['raw_webhook_payload'] : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update the status, receipt number, cashier ID, and remarks of an existing payment record.
     */
    public function updateStatus(int $id, string $status, ?string $receiptNumber = null, ?int $cashierId = null, ?string $remarks = null): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE payment_records 
            SET status = :status,
                receipt_number = COALESCE(:receipt_number, receipt_number),
                cashier_id = COALESCE(:cashier_id, cashier_id),
                remarks = COALESCE(:remarks, remarks)
            WHERE id = :id
        ');

        return $stmt->execute([
            'status'         => $status,
            'receipt_number' => $receiptNumber,
            'cashier_id'     => $cashierId,
            'remarks'        => $remarks,
            'id'             => $id,
        ]);
    }

    /**
     * Updates a payment record upon receiving a verified PayMongo server webhook.
     * Records new status, receipt number, gateway processing fee, payment intent ID,
     * raw webhook payload, and audit remarks.
     */
    public function recordWebhookConfirmation(
        int $id,
        string $status,
        ?string $receiptNumber = null,
        ?float $gatewayFee = null,
        ?string $paymentIntentId = null,
        ?string $rawPayload = null,
        ?string $remarks = null
    ): bool {
        $stmt = $this->pdo->prepare('
            UPDATE payment_records 
            SET status = :status,
                receipt_number = COALESCE(:receipt_number, receipt_number),
                gateway_fee = COALESCE(:gateway_fee, gateway_fee),
                payment_intent_id = COALESCE(:pi_id, payment_intent_id),
                raw_webhook_payload = COALESCE(:raw_payload, raw_webhook_payload),
                remarks = COALESCE(:remarks, remarks)
            WHERE id = :id
        ');

        return $stmt->execute([
            'status'         => $status,
            'receipt_number' => $receiptNumber,
            'gateway_fee'    => $gatewayFee,
            'pi_id'          => $paymentIntentId,
            'raw_payload'    => $rawPayload,
            'remarks'        => $remarks,
            'id'             => $id,
        ]);
    }

    /**
     * Retrieve paginated payment records for the cashier ledger.
     */
    public function getPaginatedPayments(int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare('
            SELECT pr.*, 
                   u.first_name as student_first, u.last_name as student_last, u.student_number, u.email as student_email,
                   c.first_name as cashier_first, c.last_name as cashier_last,
                   a.reference_number as app_ref, a.academic_level, a.strand
            FROM payment_records pr
            INNER JOIN users u ON pr.user_id = u.id
            LEFT JOIN users c ON pr.cashier_id = c.id
            INNER JOIN student_assessments sa ON pr.assessment_id = sa.id
            INNER JOIN applications a ON sa.application_id = a.id
            ORDER BY pr.created_at DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the total count of payment records in the system.
     */
    public function countPayments(): int
    {
        $stmt = $this->pdo->query('
            SELECT COUNT(*)
            FROM payment_records pr
            INNER JOIN users u ON pr.user_id = u.id
            LEFT JOIN users c ON pr.cashier_id = c.id
            INNER JOIN student_assessments sa ON pr.assessment_id = sa.id
            INNER JOIN applications a ON sa.application_id = a.id
        ');

        return (int) $stmt->fetchColumn();
    }

    /**
     * Retrieve collection statistics for the cashier dashboard.
     */
    public function getFinancialStats(): array
    {
        $stmtTot = $this->pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payment_records WHERE status = "verified"');
        $totalCollections = (float) $stmtTot->fetchColumn();

        $stmtToday = $this->pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payment_records WHERE DATE(payment_date) = CURDATE() AND status = "verified"');
        $todayCollections = (float) $stmtToday->fetchColumn();

        $stmtPending = $this->pdo->query('SELECT COUNT(*) FROM payment_records WHERE status = "pending" AND gateway = "manual"');
        $pendingReviews = (int) $stmtPending->fetchColumn();

        $stmtCount = $this->pdo->query('SELECT COUNT(*) FROM payment_records');
        $totalTransactions = (int) $stmtCount->fetchColumn();

        return [
            'total_collections'  => $totalCollections,
            'today_collections'  => $todayCollections,
            'pending_reviews'    => $pendingReviews,
            'total_transactions' => $totalTransactions,
        ];
    }

    /**
     * Retrieve financial statistics specifically for PayMongo gateway transactions.
     */
    public function getPayMongoStats(): array
    {
        $stmt = $this->pdo->query('
            SELECT 
                COUNT(*) as total_paymongo_transactions,
                COALESCE(SUM(CASE WHEN status = "verified" THEN amount ELSE 0 END), 0) as verified_collections,
                COALESCE(SUM(CASE WHEN status = "verified" THEN gateway_fee ELSE 0 END), 0) as total_gateway_fees,
                COUNT(CASE WHEN status = "verified" THEN 1 END) as verified_count,
                COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_count,
                COUNT(CASE WHEN status = "expired" THEN 1 END) as expired_count,
                COUNT(CASE WHEN status = "rejected" THEN 1 END) as rejected_count
            FROM payment_records
            WHERE gateway = "paymongo"
        ');
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_transactions'   => (int) ($res['total_paymongo_transactions'] ?? 0),
            'verified_collections' => (float) ($res['verified_collections'] ?? 0.0),
            'total_gateway_fees'   => (float) ($res['total_gateway_fees'] ?? 0.0),
            'verified_count'       => (int) ($res['verified_count'] ?? 0),
            'pending_count'        => (int) ($res['pending_count'] ?? 0),
            'failed_count'         => (int) ($res['failed_count'] ?? 0),
            'expired_count'        => (int) ($res['expired_count'] ?? 0),
            'rejected_count'       => (int) ($res['rejected_count'] ?? 0),
        ];
    }

    /**
     * Retrieve paginated PayMongo gateway transactions with full student and assessment context.
     */
    public function getPayMongoTransactions(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare('
            SELECT pr.*, 
                   u.first_name as student_first, u.last_name as student_last, u.student_number, u.email as student_email,
                   a.reference_number as app_ref, a.academic_level,
                   sa.net_amount, sa.total_paid as assessment_total_paid, sa.payment_status as assessment_payment_status
            FROM payment_records pr
            INNER JOIN users u ON pr.user_id = u.id
            INNER JOIN student_assessments sa ON pr.assessment_id = sa.id
            INNER JOIN applications a ON sa.application_id = a.id
            WHERE pr.gateway = "paymongo"
            ORDER BY pr.created_at DESC
            LIMIT :lim OFFSET :off
        ');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
