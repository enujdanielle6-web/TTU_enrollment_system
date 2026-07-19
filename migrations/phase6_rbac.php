<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

echo "Starting Phase 6 RBAC Migration...\n";

try {
    // Check if is_active exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    if (!$stmt->fetch()) {
        echo "Adding is_active column to users...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER student_number");
    }

    // Check if last_login exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'");
    if (!$stmt->fetch()) {
        echo "Adding last_login column to users...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER is_active");
    }

    echo "Phase 6 Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
