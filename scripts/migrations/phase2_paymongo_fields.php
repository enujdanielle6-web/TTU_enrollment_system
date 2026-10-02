<?php
declare(strict_types=1);

/**
 * Migration: Phase 2 PayMongo Integration Fields
 *
 * Adds gateway metadata and session association fields to `payment_records`
 * to link TTU payments with PayMongo Checkout Sessions and Payment Intents.
 */

require_once __DIR__ . '/../../config/database.php';

echo "=== MIGRATION: PHASE 2 PAYMONGO INTEGRATION FIELDS ===\n";

try {
    // 1. Inspect existing columns
    $colStmt = $pdo->query("SHOW COLUMNS FROM payment_records");
    $existingCols = $colStmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Add checkout_session_id
    if (!in_array('checkout_session_id', $existingCols, true)) {
        echo "Adding column 'checkout_session_id'...\n";
        $pdo->exec("ALTER TABLE payment_records ADD COLUMN `checkout_session_id` VARCHAR(150) DEFAULT NULL AFTER `reference_number`");
        echo "  ✓ Added 'checkout_session_id'\n";
    } else {
        echo "  - Column 'checkout_session_id' already exists.\n";
    }

    // 3. Add payment_intent_id
    if (!in_array('payment_intent_id', $existingCols, true)) {
        echo "Adding column 'payment_intent_id'...\n";
        $pdo->exec("ALTER TABLE payment_records ADD COLUMN `payment_intent_id` VARCHAR(150) DEFAULT NULL AFTER `checkout_session_id`");
        echo "  ✓ Added 'payment_intent_id'\n";
    } else {
        echo "  - Column 'payment_intent_id' already exists.\n";
    }

    // 4. Add checkout_url
    if (!in_array('checkout_url', $existingCols, true)) {
        echo "Adding column 'checkout_url'...\n";
        $pdo->exec("ALTER TABLE payment_records ADD COLUMN `checkout_url` VARCHAR(500) DEFAULT NULL AFTER `payment_intent_id`");
        echo "  ✓ Added 'checkout_url'\n";
    } else {
        echo "  - Column 'checkout_url' already exists.\n";
    }

    // 5. Add gateway
    if (!in_array('gateway', $existingCols, true)) {
        echo "Adding column 'gateway'...\n";
        $pdo->exec("ALTER TABLE payment_records ADD COLUMN `gateway` VARCHAR(50) NOT NULL DEFAULT 'manual' AFTER `checkout_url`");
        echo "  ✓ Added 'gateway'\n";
    } else {
        echo "  - Column 'gateway' already exists.\n";
    }

    // 6. Add gateway_fee
    if (!in_array('gateway_fee', $existingCols, true)) {
        echo "Adding column 'gateway_fee'...\n";
        $pdo->exec("ALTER TABLE payment_records ADD COLUMN `gateway_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `gateway`");
        echo "  ✓ Added 'gateway_fee'\n";
    } else {
        echo "  - Column 'gateway_fee' already exists.\n";
    }

    // 7. Add raw_webhook_payload
    if (!in_array('raw_webhook_payload', $existingCols, true)) {
        echo "Adding column 'raw_webhook_payload'...\n";
        $pdo->exec("ALTER TABLE payment_records ADD COLUMN `raw_webhook_payload` LONGTEXT DEFAULT NULL AFTER `remarks`");
        echo "  ✓ Added 'raw_webhook_payload'\n";
    } else {
        echo "  - Column 'raw_webhook_payload' already exists.\n";
    }

    // 8. Inspect and add indexes
    $indexes = $pdo->query("SHOW INDEX FROM payment_records")->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_column($indexes, 'Key_name');

    if (!in_array('uq_payment_records_checkout_session', $indexNames, true)) {
        echo "Adding UNIQUE index 'uq_payment_records_checkout_session'...\n";
        $pdo->exec("ALTER TABLE payment_records ADD UNIQUE KEY `uq_payment_records_checkout_session` (`checkout_session_id`)");
        echo "  ✓ Added unique key 'uq_payment_records_checkout_session'\n";
    } else {
        echo "  - Unique key 'uq_payment_records_checkout_session' already exists.\n";
    }

    if (!in_array('idx_payment_records_gateway', $indexNames, true)) {
        echo "Adding index 'idx_payment_records_gateway'...\n";
        $pdo->exec("ALTER TABLE payment_records ADD KEY `idx_payment_records_gateway` (`gateway`)");
        echo "  ✓ Added index 'idx_payment_records_gateway'\n";
    } else {
        echo "  - Index 'idx_payment_records_gateway' already exists.\n";
    }

    echo "\n=== MIGRATION COMPLETED SUCCESSFULLY ===\n";
} catch (Exception $e) {
    echo "MIGRATION FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
