<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN ttu_email VARCHAR(255) NULL UNIQUE AFTER email");
    echo "Added ttu_email column.\n";
} catch (Exception $e) {
    echo "ttu_email column might already exist: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN force_password_reset TINYINT(1) NOT NULL DEFAULT 0 AFTER password");
    echo "Added force_password_reset column.\n";
} catch (Exception $e) {
    echo "force_password_reset column might already exist: " . $e->getMessage() . "\n";
}
