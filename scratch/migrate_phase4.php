<?php

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

// Load .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }
}

use App\Core\Database;

$pdo = Database::getConnection();

echo "Starting Phase 4 DB Migration...\n";

// 1. assessment_items
$pdo->exec("
    CREATE TABLE IF NOT EXISTS assessment_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        assessment_id INT UNSIGNED NOT NULL,
        item_type ENUM('tuition', 'miscellaneous', 'laboratory', 'registration', 'other', 'discount') NOT NULL,
        item_code VARCHAR(50) NULL,
        item_name VARCHAR(150) NOT NULL,
        units DECIMAL(4,2) NOT NULL DEFAULT 0.00,
        rate_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_assessment_id (assessment_id),
        CONSTRAINT fk_assessment_items_assessment FOREIGN KEY (assessment_id) REFERENCES student_assessments (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "1. assessment_items table created/verified.\n";

// 2. receipt_sequences
$pdo->exec("
    CREATE TABLE IF NOT EXISTS receipt_sequences (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        sequence_year INT UNSIGNED NOT NULL UNIQUE,
        current_value INT UNSIGNED NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "2. receipt_sequences table created/verified.\n";

// Seed receipt_sequences with current highest receipt number if any
$curYear = (int) date('Y');
$stmt = $pdo->query("SELECT receipt_number FROM payment_records WHERE receipt_number IS NOT NULL ORDER BY id DESC");
$maxSeq = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (preg_match('/REC-\d{8}-(\d+)/', $row['receipt_number'], $m)) {
        $val = (int)$m[1];
        if ($val > $maxSeq) $maxSeq = $val;
    }
}
$pdo->prepare("
    INSERT INTO receipt_sequences (sequence_year, current_value) 
    VALUES (:yr, :val) 
    ON DUPLICATE KEY UPDATE current_value = GREATEST(current_value, VALUES(current_value))
")->execute(['yr' => $curYear, 'val' => $maxSeq]);
echo "   receipt_sequences seeded for year $curYear with initial value $maxSeq.\n";

// 3. Add composite indexes
function addIndexIfNotExists(PDO $pdo, string $table, string $indexName, string $columns): void {
    $stmt = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `$table` ADD INDEX `$indexName` ($columns)");
        echo "   Added index $indexName on $table($columns).\n";
    } else {
        echo "   Index $indexName on $table already exists.\n";
    }
}

addIndexIfNotExists($pdo, 'applications', 'idx_app_status_level', 'status, academic_level');
addIndexIfNotExists($pdo, 'student_assessments', 'idx_assessment_payment_status', 'payment_status');
addIndexIfNotExists($pdo, 'activity_logs', 'idx_activity_logs_user_created', 'user_id, created_at');

echo "Phase 4 DB Migration Completed Successfully!\n";
