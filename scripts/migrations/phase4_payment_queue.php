<?php
declare(strict_types=1);

/**
 * Migration: Phase 4 Configurable Concurrent Payment Queue
 *
 * 1. Creates `payment_sessions` table for tracking concurrent payment sessions,
 *    queue positions, session tokens, expiration timestamps, and PayMongo association.
 * 2. Initializes default configuration entries in `system_settings` for:
 *    - `payment_max_concurrency` (Default: 100)
 *    - `payment_session_duration_minutes` (Default: 15)
 *    - `payment_queue_enabled` (Default: 1)
 */

require_once __DIR__ . '/../../config/database.php';

echo "=== MIGRATION: PHASE 4 CONFIGURABLE CONCURRENT PAYMENT QUEUE ===\n";

try {
    // 1. Create payment_sessions table if it doesn't exist
    echo "Creating 'payment_sessions' table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `payment_sessions` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `user_id` int(10) unsigned NOT NULL,
          `assessment_id` int(10) unsigned NOT NULL,
          `session_token` varchar(64) NOT NULL,
          `status` enum('waiting','active','completed','expired','abandoned','cancelled') NOT NULL DEFAULT 'waiting',
          `queue_number` bigint(20) unsigned NOT NULL DEFAULT 0,
          `payment_record_id` int(10) unsigned DEFAULT NULL,
          `checkout_session_id` varchar(150) DEFAULT NULL,
          `checkout_url` varchar(500) DEFAULT NULL,
          `expires_at` datetime DEFAULT NULL,
          `activated_at` datetime DEFAULT NULL,
          `last_heartbeat_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `completed_at` datetime DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_payment_sessions_token` (`session_token`),
          KEY `idx_payment_sessions_status_expires` (`status`, `expires_at`),
          KEY `idx_payment_sessions_status_queue` (`status`, `queue_number`),
          KEY `idx_payment_sessions_user_status` (`user_id`, `status`),
          KEY `idx_payment_sessions_assessment_status` (`assessment_id`, `status`),
          KEY `idx_payment_sessions_checkout_session` (`checkout_session_id`),
          CONSTRAINT `fk_payment_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_payment_sessions_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `student_assessments` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_payment_sessions_payment_record` FOREIGN KEY (`payment_record_id`) REFERENCES `payment_records` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "  ✓ 'payment_sessions' table verified / created.\n";

    // 2. Initialize system_settings configurations
    echo "Configuring system_settings for payment queue...\n";
    $settings = [
        'payment_max_concurrency'          => '100',
        'payment_session_duration_minutes' => '15',
        'payment_queue_enabled'            => '1',
    ];

    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value, created_at, updated_at)
        VALUES (:key, :val, NOW(), NOW())
        ON DUPLICATE KEY UPDATE setting_value = IF(setting_value IS NULL OR setting_value = '', VALUES(setting_value), setting_value)
    ");

    foreach ($settings as $key => $val) {
        $stmt->execute(['key' => $key, 'val' => $val]);
        echo "  ✓ Setting '{$key}' configured.\n";
    }

    echo "\n=== MIGRATION COMPLETED SUCCESSFULLY ===\n";
} catch (Exception $e) {
    echo "MIGRATION FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
