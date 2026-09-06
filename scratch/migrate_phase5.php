<?php
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Core/Database.php';

// Load .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim(trim($value), '"\'');
        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

$pdo = App\Core\Database::getConnection();

echo "Starting Phase 5 Database Migrations...\n";

// 1. Create student_number_sequences table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `student_number_sequences` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `sequence_year` int(10) unsigned NOT NULL UNIQUE,
      `current_value` int(10) unsigned NOT NULL DEFAULT 0,
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "1. Table student_number_sequences created/verified.\n";

// 2. Seed student_number_sequences with current highest sequence
$curYear = (int)date('Y');
$prefix = $curYear . '-%';

$stmt = $pdo->prepare("SELECT student_number FROM users WHERE student_number LIKE :prefix ORDER BY student_number DESC LIMIT 1");
$stmt->execute(['prefix' => $prefix]);
$lastSN = $stmt->fetchColumn();

$maxSeq = 0;
if ($lastSN && preg_match('/^(\d{4})-(\d+)$/', $lastSN, $matches)) {
    $maxSeq = (int)$matches[2];
}

$seedStmt = $pdo->prepare("
    INSERT INTO student_number_sequences (sequence_year, current_value) 
    VALUES (:year, :val)
    ON DUPLICATE KEY UPDATE current_value = GREATEST(current_value, :val_update)
");
$seedStmt->execute([
    'year' => $curYear,
    'val' => $maxSeq,
    'val_update' => $maxSeq
]);

// 3. Add reason column to activity_logs table
$pdo->exec("
    ALTER TABLE `activity_logs` ADD COLUMN IF NOT EXISTS `reason` TEXT NULL AFTER `new_value`;
");
echo "3. Column reason added/verified on activity_logs table.\n";

echo "Phase 5 DB migration complete.\n";
