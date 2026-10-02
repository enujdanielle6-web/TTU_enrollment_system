<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

echo "=== MIGRATION: PHASE 0 CASHIER SAFETY CONSTRAINTS ===\n";

try {
    // Check existing indexes
    $indexes = $pdo->query("SHOW INDEX FROM payment_records")->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = array_column($indexes, 'Key_name');

    // 1. Add UNIQUE constraint for receipt_number if not already present
    if (!in_array('uq_payment_records_receipt_number', $indexNames, true)) {
        echo "Adding UNIQUE constraint for receipt_number...\n";
        $pdo->exec("
            ALTER TABLE payment_records 
            ADD UNIQUE KEY `uq_payment_records_receipt_number` (`receipt_number`)
        ");
        echo "  ✓ Unique key 'uq_payment_records_receipt_number' added.\n";
    } else {
        echo "  - Unique key 'uq_payment_records_receipt_number' already exists.\n";
    }

    // 2. Add UNIQUE constraint for reference_number if not already present
    if (!in_array('uq_payment_records_reference_number', $indexNames, true)) {
        echo "Adding UNIQUE constraint for reference_number...\n";
        $pdo->exec("
            ALTER TABLE payment_records 
            ADD UNIQUE KEY `uq_payment_records_reference_number` (`reference_number`)
        ");
        echo "  ✓ Unique key 'uq_payment_records_reference_number' added.\n";
    } else {
        echo "  - Unique key 'uq_payment_records_reference_number' already exists.\n";
    }

    echo "\n=== MIGRATION COMPLETED SUCCESSFULLY ===\n";
} catch (Exception $e) {
    echo "MIGRATION FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
