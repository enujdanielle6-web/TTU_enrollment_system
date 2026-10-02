<?php
declare(strict_types=1);

/**
 * Migration: Phase 3 PayMongo Webhook & Reconciliation Schema Updates
 *
 * 1. Expands `payment_records.status` ENUM to include 'failed', 'cancelled', 'expired'
 *    in addition to 'pending', 'verified', 'rejected'.
 * 2. Adds index `idx_payment_records_payment_intent` on `payment_records(payment_intent_id)`
 *    to optimize server-to-server webhook reconciliation lookups.
 */

require_once __DIR__ . '/../../config/database.php';

echo "=== MIGRATION: PHASE 3 PAYMONGO WEBHOOK & RECONCILIATION ===\n";

try {
    // 1. Inspect existing columns
    $colStmt = $pdo->query("SHOW COLUMNS FROM payment_records WHERE Field='status'");
    $statusCol = $colStmt->fetch(PDO::FETCH_ASSOC);
    $type = strtolower($statusCol['Type'] ?? '');

    if (!str_contains($type, 'failed') || !str_contains($type, 'cancelled') || !str_contains($type, 'expired')) {
        echo "Updating payment_records.status ENUM values...\n";
        $pdo->exec("
            ALTER TABLE payment_records 
            MODIFY COLUMN `status` ENUM('pending', 'verified', 'rejected', 'failed', 'cancelled', 'expired') 
            NOT NULL DEFAULT 'pending'
        ");
        echo "  ✓ Updated payment_records.status ENUM definition.\n";
    } else {
        echo "  - payment_records.status already supports gateway states.\n";
    }

    // 2. Inspect indexes
    $idxStmt = $pdo->query("SHOW INDEX FROM payment_records");
    $indexes = $idxStmt->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_column($indexes, 'Key_name');

    if (!in_array('idx_payment_records_payment_intent', $indexNames, true)) {
        echo "Adding index 'idx_payment_records_payment_intent'...\n";
        $pdo->exec("ALTER TABLE payment_records ADD KEY `idx_payment_records_payment_intent` (`payment_intent_id`)");
        echo "  ✓ Added index 'idx_payment_records_payment_intent'.\n";
    } else {
        echo "  - Index 'idx_payment_records_payment_intent' already exists.\n";
    }

    echo "\n=== MIGRATION COMPLETED SUCCESSFULLY ===\n";
} catch (Exception $e) {
    echo "MIGRATION FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
