<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

try {
    echo "Starting Phase 3 Scholarship Module Migration...\n";

    // Begin transaction
    $pdo->beginTransaction();

    // Deactivate old scholarships
    echo "Deactivating old scholarships...\n";
    $pdo->exec("UPDATE scholarships SET is_active = 0");

    echo "Inserting requested scholarship types...\n";
    
    $scholarships = [
        ['Academic Scholarship (25%)', 'percentage', 25.00, 'Partial 25% academic tuition discount.'],
        ['Academic Scholarship (50%)', 'percentage', 50.00, 'Half 50% academic tuition discount.'],
        ['Academic Scholarship (75%)', 'percentage', 75.00, 'Significant 75% academic tuition discount.'],
        ['Academic Scholarship (100%)', 'percentage', 100.00, 'Full 100% academic tuition discount.'],
        
        ['Athletic Scholarship (50%)', 'percentage', 50.00, 'Half 50% tuition discount for athletes.'],
        ['Athletic Scholarship (100%)', 'percentage', 100.00, 'Full 100% tuition discount for athletes.'],
        
        ['Financial Assistance (25%)', 'percentage', 25.00, '25% tuition discount for financial assistance.'],
        ['Financial Assistance (50%)', 'percentage', 50.00, '50% tuition discount for financial assistance.'],
        ['Financial Assistance (Custom fixed amount)', 'fixed', 5000.00, 'Fixed amount financial assistance.'],
        
        ['Full Scholarship (100%)', 'percentage', 100.00, 'Full 100% tuition discount including miscellaneous.']
    ];

    $stmt = $pdo->prepare("INSERT INTO scholarships (name, discount_type, discount_value, description, is_active) VALUES (?, ?, ?, ?, 1)");

    foreach ($scholarships as $s) {
        $stmt->execute($s);
    }

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
