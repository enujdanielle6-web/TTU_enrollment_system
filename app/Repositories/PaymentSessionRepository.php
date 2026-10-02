<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

/**
 * Repository responsible for database persistence and querying of payment_sessions,
 * tracking concurrency slots, FIFO waiting queue positions, session tokens, and PayMongo linkages.
 */
class PaymentSessionRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Retrieve a payment session by its primary key, optionally with row lock.
     */
    public function findById(int $id, bool $lock = false): ?array
    {
        $sql = 'SELECT * FROM payment_sessions WHERE id = :id LIMIT 1' . ($lock ? ' FOR UPDATE' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Retrieve a payment session by its unique session token, optionally with row lock.
     */
    public function findByToken(string $token, bool $lock = false): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $sql = 'SELECT * FROM payment_sessions WHERE session_token = :token LIMIT 1' . ($lock ? ' FOR UPDATE' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Retrieve a payment session by PayMongo checkout session ID.
     */
    public function findByCheckoutSessionId(string $checkoutSessionId, bool $lock = false): ?array
    {
        $checkoutSessionId = trim($checkoutSessionId);
        if ($checkoutSessionId === '') {
            return null;
        }

        $sql = 'SELECT * FROM payment_sessions WHERE checkout_session_id = :cs_id LIMIT 1' . ($lock ? ' FOR UPDATE' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cs_id' => $checkoutSessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Find an active, non-expired payment session for a given user and assessment.
     */
    public function findActiveByUserAndAssessment(int $userId, int $assessmentId, bool $lock = false): ?array
    {
        $sql = '
            SELECT * FROM payment_sessions 
            WHERE user_id = :uid 
              AND assessment_id = :aid 
              AND status = "active" 
              AND expires_at > NOW() 
            ORDER BY id DESC 
            LIMIT 1
        ' . ($lock ? ' FOR UPDATE' : '');

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'aid' => $assessmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Find a pending waiting queue session for a given user and assessment.
     */
    public function findWaitingByUserAndAssessment(int $userId, int $assessmentId, bool $lock = false, int $heartbeatTimeoutMinutes = 5): ?array
    {
        $sql = '
            SELECT * FROM payment_sessions 
            WHERE user_id = :uid 
              AND assessment_id = :aid 
              AND status = "waiting" 
              AND last_heartbeat_at >= DATE_SUB(NOW(), INTERVAL :hb MINUTE)
            ORDER BY id DESC 
            LIMIT 1
        ' . ($lock ? ' FOR UPDATE' : '');

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':aid', $assessmentId, PDO::PARAM_INT);
        $stmt->bindValue(':hb', $heartbeatTimeoutMinutes, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Count the total number of currently active, non-expired payment slots.
     */
    public function countActiveSessions(bool $lock = false): int
    {
        $sql = 'SELECT COUNT(*) FROM payment_sessions WHERE status = "active" AND expires_at > NOW()' . ($lock ? ' FOR UPDATE' : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Count the total number of actively polling waiting sessions.
     */
    public function countWaitingSessions(int $heartbeatTimeoutMinutes = 5, bool $lock = false): int
    {
        $sql = '
            SELECT COUNT(*) FROM payment_sessions 
            WHERE status = "waiting" 
              AND last_heartbeat_at >= DATE_SUB(NOW(), INTERVAL :hb MINUTE)
        ' . ($lock ? ' FOR UPDATE' : '');

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':hb', $heartbeatTimeoutMinutes, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Calculate a student's real-time position in the FIFO waiting queue.
     * Position 1 means the student is next in line for promotion.
     */
    public function getWaitingPosition(int $queueNumber, int $heartbeatTimeoutMinutes = 5): int
    {
        $sql = '
            SELECT COUNT(*) FROM payment_sessions 
            WHERE status = "waiting" 
              AND queue_number < :qn 
              AND last_heartbeat_at >= DATE_SUB(NOW(), INTERVAL :hb MINUTE)
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':qn', $queueNumber, PDO::PARAM_INT);
        $stmt->bindValue(':hb', $heartbeatTimeoutMinutes, PDO::PARAM_INT);
        $stmt->execute();

        $ahead = (int) $stmt->fetchColumn();

        return $ahead + 1;
    }

    /**
     * Insert a new payment session into the database.
     */
    public function insertSession(array $data): int
    {
        // Atomically determine next queue number if not supplied
        $queueNumber = isset($data['queue_number']) ? (int) $data['queue_number'] : 0;
        if ($queueNumber <= 0) {
            $maxStmt = $this->pdo->query('SELECT COALESCE(MAX(queue_number), 0) + 1 FROM payment_sessions');
            $queueNumber = (int) $maxStmt->fetchColumn();
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO payment_sessions (
                user_id, assessment_id, session_token, status, queue_number,
                payment_record_id, checkout_session_id, checkout_url,
                expires_at, activated_at, last_heartbeat_at
            ) VALUES (
                :user_id, :assessment_id, :token, :status, :queue_number,
                :payment_record_id, :checkout_session_id, :checkout_url,
                :expires_at, :activated_at, NOW()
            )
        ');

        $stmt->execute([
            'user_id'             => (int) $data['user_id'],
            'assessment_id'       => (int) $data['assessment_id'],
            'token'               => (string) $data['session_token'],
            'status'              => (string) ($data['status'] ?? 'waiting'),
            'queue_number'        => $queueNumber,
            'payment_record_id'   => !empty($data['payment_record_id']) ? (int) $data['payment_record_id'] : null,
            'checkout_session_id' => !empty($data['checkout_session_id']) ? (string) $data['checkout_session_id'] : null,
            'checkout_url'        => !empty($data['checkout_url']) ? (string) $data['checkout_url'] : null,
            'expires_at'          => !empty($data['expires_at']) ? (string) $data['expires_at'] : null,
            'activated_at'        => !empty($data['activated_at']) ? (string) $data['activated_at'] : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Promote a waiting session to active payment status with a fixed duration window.
     */
    public function promoteSession(int $id, int $durationMinutes = 15): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE payment_sessions 
            SET status = "active",
                activated_at = NOW(),
                expires_at = DATE_ADD(NOW(), INTERVAL :dur MINUTE),
                last_heartbeat_at = NOW()
            WHERE id = :id AND status = "waiting"
        ');

        $stmt->bindValue(':dur', $durationMinutes, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Retrieve the next eligible waiting sessions in FIFO queue order under row lock.
     */
    public function getPromotableWaitingSessions(int $limit, int $heartbeatTimeoutMinutes = 5): array
    {
        if ($limit <= 0) {
            return [];
        }

        $sql = '
            SELECT * FROM payment_sessions 
            WHERE status = "waiting" 
              AND last_heartbeat_at >= DATE_SUB(NOW(), INTERVAL :hb MINUTE)
            ORDER BY queue_number ASC 
            LIMIT :lim 
            FOR UPDATE
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':hb', $heartbeatTimeoutMinutes, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark expired active sessions whose countdown timer has elapsed.
     * Returns the count of newly expired sessions.
     */
    public function expireStaleActiveSessions(): int
    {
        $stmt = $this->pdo->prepare('
            UPDATE payment_sessions 
            SET status = "expired" 
            WHERE status = "active" AND expires_at <= NOW()
        ');
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Mark stale waiting sessions as abandoned if no heartbeat was received within the timeout window.
     * Returns the count of newly abandoned sessions.
     */
    public function abandonStaleWaitingSessions(int $heartbeatTimeoutMinutes = 5): int
    {
        $stmt = $this->pdo->prepare('
            UPDATE payment_sessions 
            SET status = "abandoned" 
            WHERE status = "waiting" AND last_heartbeat_at < DATE_SUB(NOW(), INTERVAL :hb MINUTE)
        ');
        $stmt->bindValue(':hb', $heartbeatTimeoutMinutes, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Mark a session as completed upon verified payment capture.
     */
    public function completeSession(int $id, ?int $paymentRecordId = null): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE payment_sessions 
            SET status = "completed",
                completed_at = NOW(),
                payment_record_id = COALESCE(:pid, payment_record_id)
            WHERE id = :id
        ');

        return $stmt->execute([
            'pid' => $paymentRecordId,
            'id'  => $id,
        ]);
    }

    /**
     * Mark a session as cancelled by the user.
     */
    public function cancelSession(int $id): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE payment_sessions 
            SET status = "cancelled" 
            WHERE id = :id AND status IN ("waiting", "active")
        ');

        return $stmt->execute(['id' => $id]);
    }

    /**
     * Update heartbeat timestamp for an active or waiting session.
     */
    public function updateHeartbeat(int $id): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE payment_sessions 
            SET last_heartbeat_at = NOW() 
            WHERE id = :id
        ');

        return $stmt->execute(['id' => $id]);
    }

    /**
     * Associate PayMongo checkout session details with a payment session.
     */
    public function linkPayMongoCheckout(
        int $sessionId,
        string $checkoutSessionId,
        ?string $checkoutUrl = null,
        ?int $paymentRecordId = null
    ): bool {
        $stmt = $this->pdo->prepare('
            UPDATE payment_sessions 
            SET checkout_session_id = :cs_id,
                checkout_url = COALESCE(:checkout_url, checkout_url),
                payment_record_id = COALESCE(:pid, payment_record_id)
            WHERE id = :id
        ');

        return $stmt->execute([
            'cs_id'        => $checkoutSessionId,
            'checkout_url' => $checkoutUrl,
            'pid'          => $paymentRecordId,
            'id'           => $sessionId,
        ]);
    }

    /**
     * Retrieve active payment sessions with joined student, assessment, and application data.
     */
    public function getActiveSessionsDetailed(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare('
            SELECT ps.*, 
                   u.first_name, u.last_name, u.email, u.student_number,
                   a.id as application_id, a.reference_number as app_ref, a.academic_level,
                   sa.net_amount, sa.total_paid, sa.payment_status as assessment_payment_status
            FROM payment_sessions ps
            INNER JOIN users u ON ps.user_id = u.id
            INNER JOIN student_assessments sa ON ps.assessment_id = sa.id
            INNER JOIN applications a ON sa.application_id = a.id
            WHERE ps.status = "active" AND ps.expires_at > NOW()
            ORDER BY ps.expires_at ASC
            LIMIT :lim
        ');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve waiting queue sessions with joined student, assessment, and application data.
     */
    public function getWaitingSessionsDetailed(int $limit = 50, int $heartbeatTimeoutMinutes = 5): array
    {
        $stmt = $this->pdo->prepare('
            SELECT ps.*, 
                   u.first_name, u.last_name, u.email, u.student_number,
                   a.id as application_id, a.reference_number as app_ref, a.academic_level,
                   sa.net_amount, sa.total_paid, sa.payment_status as assessment_payment_status
            FROM payment_sessions ps
            INNER JOIN users u ON ps.user_id = u.id
            INNER JOIN student_assessments sa ON ps.assessment_id = sa.id
            INNER JOIN applications a ON sa.application_id = a.id
            WHERE ps.status = "waiting" AND ps.last_heartbeat_at >= DATE_SUB(NOW(), INTERVAL :hb MINUTE)
            ORDER BY ps.queue_number ASC
            LIMIT :lim
        ');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':hb', $heartbeatTimeoutMinutes, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve aggregate session status breakdown counts for the queue dashboard.
     */
    public function getSessionCounts(int $heartbeatTimeoutMinutes = 5): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                COUNT(CASE WHEN status = "active" AND expires_at > NOW() THEN 1 END) as active_count,
                COUNT(CASE WHEN status = "waiting" AND last_heartbeat_at >= DATE_SUB(NOW(), INTERVAL :hb MINUTE) THEN 1 END) as waiting_count,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_count,
                COUNT(CASE WHEN status = "expired" OR (status = "active" AND expires_at <= NOW()) THEN 1 END) as expired_count,
                COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_count,
                COUNT(CASE WHEN status = "abandoned" THEN 1 END) as abandoned_count,
                COUNT(*) as total_sessions
            FROM payment_sessions
        ');
        $stmt->bindValue(':hb', $heartbeatTimeoutMinutes, PDO::PARAM_INT);
        $stmt->execute();
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'active'    => (int) ($counts['active_count'] ?? 0),
            'waiting'   => (int) ($counts['waiting_count'] ?? 0),
            'completed' => (int) ($counts['completed_count'] ?? 0),
            'expired'   => (int) ($counts['expired_count'] ?? 0),
            'cancelled' => (int) ($counts['cancelled_count'] ?? 0),
            'abandoned' => (int) ($counts['abandoned_count'] ?? 0),
            'total'     => (int) ($counts['total_sessions'] ?? 0),
        ];
    }
}
