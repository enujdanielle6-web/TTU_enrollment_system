<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\PaymentSessionRepository;
use PDO;
use Exception;
use Throwable;

/**
 * Domain Service responsible for orchestrating the Configurable Concurrent Payment Queue.
 *
 * Guarantees:
 * 1. Multi-Student Concurrency: Supports high simultaneous payment throughput (e.g. 100, 250, 500 sessions).
 * 2. Safe Concurrent Allocation: Mutex-locked capacity evaluation eliminates double-allocation race conditions.
 * 3. FIFO Ordering: Waiting students are promoted in strictly sequential queue number order.
 * 4. Automatic Expiration & Slot Recycling: Stale reservations (15m default) and abandoned tabs (5m) are reclaimed.
 * 5. Duplicate Entry Prevention: Multiple tabs or page refreshes resolve to the student's existing active or waiting session.
 * 6. System Settings Governance: Capacity limits and durations are dynamically loaded from system_settings.
 */
class PaymentQueueService
{
    private PDO $pdo;
    private PaymentSessionRepository $repository;

    public function __construct(?PaymentSessionRepository $repository = null, ?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->repository = $repository ?? new PaymentSessionRepository($this->pdo);
    }

    /**
     * Enter the payment queue for a given assessment.
     *
     * If capacity is available, immediately reserves an active checkout slot.
     * If capacity is saturated, places the student in the waiting queue with a FIFO position.
     * If the student already has an active or waiting session, returns that session (refresh / multi-tab safe).
     *
     * @param int $userId Student user ID
     * @param int $assessmentId Target student assessment ID
     * @return array [
     *   'status'           => 'active'|'waiting',
     *   'session_token'    => string,
     *   'position'         => int|null (if waiting),
     *   'expires_at'       => string|null (if active),
     *   'seconds_remaining'=> int|null (if active),
     *   'is_existing'      => bool,
     *   'max_concurrency'  => int
     * ]
     * @throws Exception On database or validation failure
     */
    public function enterQueue(int $userId, int $assessmentId): array
    {
        if ($userId <= 0 || $assessmentId <= 0) {
            throw new Exception('Invalid user or assessment identifier.');
        }

        // Run proactive maintenance sweeps
        $this->repository->expireStaleActiveSessions();
        $this->repository->abandonStaleWaitingSessions();

        $isExternalTx = $this->pdo->inTransaction();
        if (!$isExternalTx) {
            $this->pdo->beginTransaction();
        }

        try {
            // 1. Acquire Concurrency Control Mutex Lock on system_settings
            // This serializes slot allocation checks without locking unrelated tables
            $stmtLock = $this->pdo->prepare('
                SELECT setting_value 
                FROM system_settings 
                WHERE setting_key = "payment_max_concurrency" 
                LIMIT 1 
                FOR UPDATE
            ');
            $stmtLock->execute();
            $settingValue = $stmtLock->fetchColumn();

            $maxConcurrency = ($settingValue !== false && is_numeric($settingValue)) 
                ? (int) $settingValue 
                : 100;
            if ($maxConcurrency <= 0) {
                $maxConcurrency = 100;
            }

            // Duration in minutes
            $durStmt = $this->pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = "payment_session_duration_minutes" LIMIT 1');
            $durStmt->execute();
            $durVal = $durStmt->fetchColumn();
            $durationMinutes = ($durVal !== false && is_numeric($durVal)) ? (int) $durVal : 15;
            if ($durationMinutes <= 0) {
                $durationMinutes = 15;
            }

            // 2. Duplicate Check A: Check for existing ACTIVE session (Refresh / Multiple Tab Safe)
            $existingActive = $this->repository->findActiveByUserAndAssessment($userId, $assessmentId, true);
            if ($existingActive !== null) {
                $this->repository->updateHeartbeat((int) $existingActive['id']);
                if (!$isExternalTx) {
                    $this->pdo->commit();
                }

                $remainingSec = max(0, strtotime($existingActive['expires_at']) - time());

                return [
                    'status'            => 'active',
                    'session_token'     => (string) $existingActive['session_token'],
                    'expires_at'        => (string) $existingActive['expires_at'],
                    'seconds_remaining' => $remainingSec,
                    'checkout_url'      => $existingActive['checkout_url'],
                    'is_existing'       => true,
                    'max_concurrency'   => $maxConcurrency,
                    'active_sessions'   => $this->repository->countActiveSessions(),
                    'available_slots'   => max(0, $maxConcurrency - $this->repository->countActiveSessions()),
                    'checkout_ready'    => true,
                    'payment_readiness' => 'ready',
                    'session_duration'  => $durationMinutes,
                    'message'           => 'Resumed active payment session.',
                ];
            }

            // 3. Duplicate Check B: Check for existing WAITING session
            $existingWaiting = $this->repository->findWaitingByUserAndAssessment($userId, $assessmentId, true);
            if ($existingWaiting !== null) {
                $this->repository->updateHeartbeat((int) $existingWaiting['id']);
                $position = $this->repository->getWaitingPosition((int) $existingWaiting['queue_number']);

                if (!$isExternalTx) {
                    $this->pdo->commit();
                }

                $activeCount = $this->repository->countActiveSessions();

                return [
                    'status'            => 'waiting',
                    'session_token'     => (string) $existingWaiting['session_token'],
                    'position'          => $position,
                    'queue_number'      => (int) $existingWaiting['queue_number'],
                    'is_existing'       => true,
                    'max_concurrency'   => $maxConcurrency,
                    'active_sessions'   => $activeCount,
                    'available_slots'   => max(0, $maxConcurrency - $activeCount),
                    'checkout_ready'    => false,
                    'payment_readiness' => 'waiting',
                    'session_duration'  => $durationMinutes,
                    'message'           => "You are currently #{$position} in line.",
                ];
            }

            // 4. Capacity Assessment: Count active sessions under lock
            $activeCount = $this->repository->countActiveSessions(true);

            // 5. Branch A: Capacity Available -> Allocate Active Slot Immediately
            if ($activeCount < $maxConcurrency) {
                $sessionToken = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + ($durationMinutes * 60));
                $activatedAt = date('Y-m-d H:i:s');

                $sessionId = $this->repository->insertSession([
                    'user_id'       => $userId,
                    'assessment_id' => $assessmentId,
                    'session_token' => $sessionToken,
                    'status'        => 'active',
                    'expires_at'    => $expiresAt,
                    'activated_at'  => $activatedAt,
                ]);

                if (!$isExternalTx) {
                    $this->pdo->commit();
                }

                $newActiveCount = $activeCount + 1;

                return [
                    'status'            => 'active',
                    'session_token'     => $sessionToken,
                    'session_id'        => $sessionId,
                    'expires_at'        => $expiresAt,
                    'seconds_remaining' => $durationMinutes * 60,
                    'checkout_url'      => null,
                    'is_existing'       => false,
                    'max_concurrency'   => $maxConcurrency,
                    'active_sessions'   => $newActiveCount,
                    'available_slots'   => max(0, $maxConcurrency - $newActiveCount),
                    'checkout_ready'    => true,
                    'payment_readiness' => 'ready',
                    'session_duration'  => $durationMinutes,
                    'message'           => "Active payment slot reserved for {$durationMinutes} minutes.",
                ];
            }

            // 6. Branch B: Capacity Reached -> Enter Waiting Queue
            $sessionToken = bin2hex(random_bytes(32));
            $sessionId = $this->repository->insertSession([
                'user_id'       => $userId,
                'assessment_id' => $assessmentId,
                'session_token' => $sessionToken,
                'status'        => 'waiting',
            ]);

            $session = $this->repository->findById($sessionId);
            $queueNumber = (int) ($session['queue_number'] ?? $sessionId);
            $position = $this->repository->getWaitingPosition($queueNumber);

            if (!$isExternalTx) {
                $this->pdo->commit();
            }

            return [
                'status'            => 'waiting',
                'session_token'     => $sessionToken,
                'session_id'        => $sessionId,
                'position'          => $position,
                'queue_number'      => $queueNumber,
                'is_existing'       => false,
                'max_concurrency'   => $maxConcurrency,
                'active_sessions'   => $activeCount,
                'available_slots'   => 0,
                'checkout_ready'    => false,
                'payment_readiness' => 'waiting',
                'session_duration'  => $durationMinutes,
                'message'           => "Capacity reached ({$maxConcurrency} active slots). Placed in queue at position #{$position}.",
            ];

        } catch (Exception $e) {
            if (!$isExternalTx && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Check real-time status of a payment session via its token.
     * Handles automatic promotion if slots have freed up, and updates the client heartbeat.
     *
     * @param string $sessionToken
     * @return array
     */
    public function checkStatus(string $sessionToken): array
    {
        $session = $this->repository->findByToken($sessionToken);
        if (!$session) {
            return [
                'status'  => 'not_found',
                'message' => 'Payment session not found or invalid token.',
            ];
        }

        $sessionId = (int) $session['id'];
        $this->repository->updateHeartbeat($sessionId);

        $maxConcurrency = $this->getMaxConcurrency();
        $activeSessions = $this->repository->countActiveSessions();
        $availableSlots = max(0, $maxConcurrency - $activeSessions);
        $durationMinutes = $this->getSessionDuration();

        // 1. Active Session
        if ($session['status'] === 'active') {
            $now = time();
            $expiresAt = strtotime($session['expires_at'] ?? '');

            if ($expiresAt <= $now) {
                $this->repository->expireStaleActiveSessions();
                return [
                    'status'            => 'expired',
                    'session_token'     => $sessionToken,
                    'checkout_ready'    => false,
                    'payment_readiness' => 'expired',
                    'max_concurrency'   => $maxConcurrency,
                    'active_sessions'   => $this->repository->countActiveSessions(),
                    'available_slots'   => max(0, $maxConcurrency - $this->repository->countActiveSessions()),
                    'session_duration'  => $durationMinutes,
                    'message'           => 'Your payment reservation slot has expired.',
                ];
            }

            $activatedAt = strtotime($session['activated_at'] ?? '');
            $isRecentlyPromoted = ($activatedAt > 0 && ($now - $activatedAt) <= 60);

            return [
                'status'            => 'active',
                'session_token'     => $sessionToken,
                'expires_at'        => $session['expires_at'],
                'seconds_remaining' => max(0, $expiresAt - $now),
                'checkout_url'      => $session['checkout_url'],
                'checkout_ready'    => true,
                'payment_readiness' => 'ready',
                'promoted'          => $isRecentlyPromoted,
                'max_concurrency'   => $maxConcurrency,
                'active_sessions'   => $activeSessions,
                'available_slots'   => $availableSlots,
                'session_duration'  => $durationMinutes,
            ];
        }

        // 2. Waiting Session (Attempt opportunistic promotion if slot is free)
        if ($session['status'] === 'waiting') {
            // Attempt promotion of waiting sessions
            $this->promoteEligibleWaitingSessions();

            // Reload session state
            $reloaded = $this->repository->findById($sessionId);
            if ($reloaded && $reloaded['status'] === 'active') {
                $expiresAt = strtotime($reloaded['expires_at'] ?? '');
                $updatedActive = $this->repository->countActiveSessions();
                return [
                    'status'            => 'active',
                    'session_token'     => $sessionToken,
                    'expires_at'        => $reloaded['expires_at'],
                    'seconds_remaining' => max(0, $expiresAt - time()),
                    'checkout_url'      => $reloaded['checkout_url'],
                    'checkout_ready'    => true,
                    'payment_readiness' => 'ready',
                    'promoted'          => true,
                    'max_concurrency'   => $maxConcurrency,
                    'active_sessions'   => $updatedActive,
                    'available_slots'   => max(0, $maxConcurrency - $updatedActive),
                    'session_duration'  => $durationMinutes,
                    'message'           => 'You have been promoted! A payment slot is now reserved for you.',
                ];
            }

            $position = $this->repository->getWaitingPosition((int) $session['queue_number']);
            $currentActive = $this->repository->countActiveSessions();

            return [
                'status'            => 'waiting',
                'session_token'     => $sessionToken,
                'position'          => $position,
                'queue_number'      => (int) $session['queue_number'],
                'checkout_ready'    => false,
                'payment_readiness' => 'waiting',
                'max_concurrency'   => $maxConcurrency,
                'active_sessions'   => $currentActive,
                'available_slots'   => max(0, $maxConcurrency - $currentActive),
                'session_duration'  => $durationMinutes,
                'message'           => "You are #{$position} in line.",
            ];
        }

        // 3. Completed Session
        if ($session['status'] === 'completed') {
            return [
                'status'            => 'completed',
                'session_token'     => $sessionToken,
                'payment_record_id' => $session['payment_record_id'],
                'checkout_ready'    => false,
                'payment_readiness' => 'completed',
                'max_concurrency'   => $maxConcurrency,
                'active_sessions'   => $activeSessions,
                'available_slots'   => $availableSlots,
                'message'           => 'Payment has been successfully completed and confirmed.',
            ];
        }

        // 4. Other terminal states (expired, cancelled, abandoned)
        return [
            'status'            => $session['status'],
            'session_token'     => $sessionToken,
            'checkout_ready'    => false,
            'payment_readiness' => $session['status'],
            'max_concurrency'   => $maxConcurrency,
            'active_sessions'   => $activeSessions,
            'available_slots'   => $availableSlots,
            'message'           => "Session is in {$session['status']} state.",
        ];
    }

    /**
     * Promotes eligible waiting students to active payment slots up to available capacity.
     * Safely executed within a transaction using row locks.
     *
     * @param int|null $maxPromotions Maximum number of sessions to promote in this pass (defaults to available capacity)
     * @return int Count of sessions promoted
     */
    public function promoteEligibleWaitingSessions(?int $maxPromotions = null): int
    {
        $this->repository->expireStaleActiveSessions();
        $this->repository->abandonStaleWaitingSessions();

        $isExternalTx = $this->pdo->inTransaction();
        if (!$isExternalTx) {
            $this->pdo->beginTransaction();
        }

        try {
            // Lock concurrency limit row in system_settings
            $stmtLock = $this->pdo->prepare('
                SELECT setting_value 
                FROM system_settings 
                WHERE setting_key = "payment_max_concurrency" 
                LIMIT 1 
                FOR UPDATE
            ');
            $stmtLock->execute();
            $settingValue = $stmtLock->fetchColumn();

            $maxConcurrency = ($settingValue !== false && is_numeric($settingValue)) 
                ? (int) $settingValue 
                : 100;
            if ($maxConcurrency <= 0) {
                $maxConcurrency = 100;
            }

            $activeCount = $this->repository->countActiveSessions(true);
            $availableSlots = max(0, $maxConcurrency - $activeCount);

            if ($maxPromotions !== null && $maxPromotions > 0) {
                $availableSlots = min($availableSlots, $maxPromotions);
            }

            if ($availableSlots <= 0) {
                if (!$isExternalTx) {
                    $this->pdo->commit();
                }
                return 0;
            }

            // Duration
            $durStmt = $this->pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = "payment_session_duration_minutes" LIMIT 1');
            $durStmt->execute();
            $durVal = $durStmt->fetchColumn();
            $durationMinutes = ($durVal !== false && is_numeric($durVal)) ? (int) $durVal : 15;
            if ($durationMinutes <= 0) {
                $durationMinutes = 15;
            }

            // Fetch top waiting sessions in strict FIFO queue order
            $waitingCandidates = $this->repository->getPromotableWaitingSessions($availableSlots);
            $promotedCount = 0;

            foreach ($waitingCandidates as $candidate) {
                $promoted = $this->repository->promoteSession((int) $candidate['id'], $durationMinutes);
                if ($promoted) {
                    $promotedCount++;
                }
            }

            if (!$isExternalTx) {
                $this->pdo->commit();
            }

            return $promotedCount;

        } catch (Exception $e) {
            if (!$isExternalTx && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Voluntarily release an active or waiting payment slot, immediately making capacity
     * available for the next student in the queue.
     *
     * @param string $sessionToken
     * @return bool True if cancelled/released
     */
    public function releaseSlot(string $sessionToken): bool
    {
        $session = $this->repository->findByToken($sessionToken);
        if (!$session) {
            return false;
        }

        $sessionId = (int) $session['id'];
        $wasActive = ($session['status'] === 'active');
        $success = $this->repository->cancelSession($sessionId);

        // If an active slot was released, immediately promote the next in line
        if ($success && $wasActive) {
            try {
                $this->promoteEligibleWaitingSessions(1);
            } catch (Throwable $e) {
                error_log('Error promoting waiting session after slot release: ' . $e->getMessage());
            }
        }

        return $success;
    }

    /**
     * Associates a PayMongo Checkout Session and payment record with an active payment session.
     */
    public function linkPayMongoCheckout(
        string $sessionToken,
        string $checkoutSessionId,
        ?string $checkoutUrl = null,
        ?int $paymentRecordId = null
    ): bool {
        $session = $this->repository->findByToken($sessionToken);
        if (!$session || $session['status'] !== 'active') {
            return false;
        }

        return $this->repository->linkPayMongoCheckout(
            (int) $session['id'],
            $checkoutSessionId,
            $checkoutUrl,
            $paymentRecordId
        );
    }

    /**
     * Marks a session completed upon verified payment webhook confirmation.
     * Immediately frees up the active slot and promotes the next waiting student.
     */
    public function completeSessionByCheckoutId(string $checkoutSessionId, ?int $paymentRecordId = null): bool
    {
        $session = $this->repository->findByCheckoutSessionId($checkoutSessionId);
        if (!$session) {
            return false;
        }

        $success = $this->repository->completeSession((int) $session['id'], $paymentRecordId);

        if ($success) {
            try {
                $this->promoteEligibleWaitingSessions(1);
            } catch (Throwable $e) {
                error_log('Error promoting waiting session after payment completion: ' . $e->getMessage());
            }
        }

        return $success;
    }

    /**
     * Retrieve global queue metrics for administrative telemetry and monitoring.
     */
    public function getQueueMetrics(): array
    {
        $this->repository->expireStaleActiveSessions();
        $this->repository->abandonStaleWaitingSessions();

        $maxConcurrency = $this->getMaxConcurrency();
        $activeCount = $this->repository->countActiveSessions();
        $waitingCount = $this->repository->countWaitingSessions();
        $availableSlots = max(0, $maxConcurrency - $activeCount);

        return [
            'max_concurrency'          => $maxConcurrency,
            'active_sessions'          => $activeCount,
            'available_slots'          => $availableSlots,
            'waiting_sessions'         => $waitingCount,
            'session_duration_minutes' => $this->getSessionDuration(),
            'queue_enabled'            => $this->isQueueEnabled(),
            'utilization_percent'      => $maxConcurrency > 0 ? round(($activeCount / $maxConcurrency) * 100, 1) : 0.0,
        ];
    }

    /**
     * Retrieve comprehensive monitoring telemetry for Cashier/Finance dashboard.
     */
    public function getMonitoringDashboardData(): array
    {
        $metrics = $this->getQueueMetrics();
        $sessionCounts = $this->repository->getSessionCounts();
        $activeSessions = $this->repository->getActiveSessionsDetailed(50);
        $waitingSessions = $this->repository->getWaitingSessionsDetailed(50);

        $paymentRepo = new \App\Repositories\PaymentRepository($this->pdo);
        $payMongoStats = $paymentRepo->getPayMongoStats();
        $recentTransactions = $paymentRepo->getPayMongoTransactions(20, 0);

        return [
            'metrics'             => $metrics,
            'session_counts'      => $sessionCounts,
            'active_sessions'     => $activeSessions,
            'waiting_sessions'    => $waitingSessions,
            'paymongo_stats'      => $payMongoStats,
            'recent_transactions' => $recentTransactions,
            'timestamp'           => date('Y-m-d H:i:s'),
        ];
    }

    // --- Configuration Getters and Setters using system_settings ---

    public function getMaxConcurrency(): int
    {
        $val = getSystemSetting($this->pdo, 'payment_max_concurrency', '100');
        return is_numeric($val) && (int) $val > 0 ? (int) $val : 100;
    }

    public function setMaxConcurrency(int $limit): bool
    {
        if ($limit <= 0) {
            throw new Exception('Max concurrency limit must be a positive integer.');
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO system_settings (setting_key, setting_value, created_at, updated_at) 
            VALUES ("payment_max_concurrency", :val, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ');

        return $stmt->execute(['val' => (string) $limit]);
    }

    public function getSessionDuration(): int
    {
        $val = getSystemSetting($this->pdo, 'payment_session_duration_minutes', '15');
        return is_numeric($val) && (int) $val > 0 ? (int) $val : 15;
    }

    public function setSessionDuration(int $minutes): bool
    {
        if ($minutes <= 0) {
            throw new Exception('Session duration must be greater than zero minutes.');
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO system_settings (setting_key, setting_value, created_at, updated_at) 
            VALUES ("payment_session_duration_minutes", :val, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ');

        return $stmt->execute(['val' => (string) $minutes]);
    }

    public function isQueueEnabled(): bool
    {
        $val = getSystemSetting($this->pdo, 'payment_queue_enabled', '1');
        return ($val === '1' || $val === 'true' || $val === true);
    }

    public function setQueueEnabled(bool $enabled): bool
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO system_settings (setting_key, setting_value, created_at, updated_at) 
            VALUES ("payment_queue_enabled", :val, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ');

        return $stmt->execute(['val' => $enabled ? '1' : '0']);
    }
}
