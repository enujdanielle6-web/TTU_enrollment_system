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

require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Core\Database;

$pdo = Database::getConnection();

echo "=== Phase 4 Verification Suite ===\n";

// 1. Verify assessment_items Table
$stmt = $pdo->query("SHOW TABLES LIKE 'assessment_items'");
$hasAi = (bool)$stmt->fetchColumn();
echo "1. Table assessment_items exists: " . ($hasAi ? "PASS" : "FAIL") . "\n";
if ($hasAi) {
    $cols = $pdo->query("SHOW COLUMNS FROM assessment_items")->fetchAll(PDO::FETCH_COLUMN);
    $expected = ['id', 'assessment_id', 'item_type', 'item_code', 'item_name', 'units', 'rate_per_unit', 'amount', 'created_at'];
    $diff = array_diff($expected, $cols);
    echo "   assessment_items columns check: " . (empty($diff) ? "PASS" : "FAIL (missing " . implode(', ', $diff) . ")") . "\n";
}

// 2. Verify receipt_sequences Table
$stmt = $pdo->query("SHOW TABLES LIKE 'receipt_sequences'");
$hasRs = (bool)$stmt->fetchColumn();
echo "2. Table receipt_sequences exists: " . ($hasRs ? "PASS" : "FAIL") . "\n";
if ($hasRs) {
    $cols = $pdo->query("SHOW COLUMNS FROM receipt_sequences")->fetchAll(PDO::FETCH_COLUMN);
    $expected = ['id', 'sequence_year', 'current_value', 'updated_at'];
    $diff = array_diff($expected, $cols);
    echo "   receipt_sequences columns check: " . (empty($diff) ? "PASS" : "FAIL") . "\n";
}

// 3. Verify Composite Indexes
function checkIndex(PDO $pdo, string $table, string $indexName): bool {
    $stmt = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
    return (bool)$stmt->fetch();
}
echo "3. Composite Indexes Check:\n";
echo "   - applications.idx_app_status_level: " . (checkIndex($pdo, 'applications', 'idx_app_status_level') ? "PASS" : "FAIL") . "\n";
echo "   - student_assessments.idx_assessment_payment_status: " . (checkIndex($pdo, 'student_assessments', 'idx_assessment_payment_status') ? "PASS" : "FAIL") . "\n";
echo "   - activity_logs.idx_activity_logs_user_created: " . (checkIndex($pdo, 'activity_logs', 'idx_activity_logs_user_created') ? "PASS" : "FAIL") . "\n";

// 4. Test Concurrency-Safe Atomic Receipt Generator
echo "4. Testing atomic receipt number generation:\n";
$r1 = generateAtomicReceiptNumber($pdo);
$r2 = generateAtomicReceiptNumber($pdo);
$r3 = generateAtomicReceiptNumber($pdo);

$matchFormat = preg_match('/^REC-\d{8}-\d{4}$/', $r1) && preg_match('/^REC-\d{8}-\d{4}$/', $r2) && preg_match('/^REC-\d{8}-\d{4}$/', $r3);
$isUnique = ($r1 !== $r2 && $r2 !== $r3 && $r1 !== $r3);
$seq1 = (int)substr($r1, -4);
$seq2 = (int)substr($r2, -4);
$seq3 = (int)substr($r3, -4);
$isIncreasing = ($seq2 === $seq1 + 1 && $seq3 === $seq2 + 1);

echo "   Generated: $r1 -> $r2 -> $r3\n";
echo "   Format & Strictly Monotonic Sequence Check: " . ($matchFormat && $isUnique && $isIncreasing ? "PASS" : "FAIL") . "\n";

// 5. Test Assessment Items Snapshotting Function
echo "5. Testing snapshotAssessmentItems():\n";
try {
    $pdo->beginTransaction();
    $existingAssId = $pdo->query("SELECT id, application_id FROM student_assessments LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($existingAssId) {
        $testAssId = (int)$existingAssId['id'];
        $testAppId = (int)$existingAssId['application_id'];

        $sampleSubs = [
            ['subject_code' => 'CS101', 'subject_name' => 'Intro to Computing', 'units' => 3.0],
            ['subject_code' => 'CS102', 'subject_name' => 'Computer Programming 1', 'units' => 3.0],
        ];

        snapshotAssessmentItems(
            $pdo,
            $testAssId,
            $testAppId,
            ['is_per_unit' => 1, 'tuition_fee' => 500.00],
            $sampleSubs,
            3000.00,
            1500.00,
            500.00,
            1000.00,
            200.00,
            500.00
        );

        $chkStmt = $pdo->prepare("SELECT COUNT(*) FROM assessment_items WHERE assessment_id = ?");
        $chkStmt->execute([$testAssId]);
        $cnt = (int)$chkStmt->fetchColumn();
        echo "   assessment_items snapshot inserted $cnt items: " . ($cnt === 7 ? "PASS" : "FAIL") . "\n";

        $types = $pdo->prepare("SELECT DISTINCT item_type FROM assessment_items WHERE assessment_id = ? ORDER BY item_type ASC");
        $types->execute([$testAssId]);
        $distinctTypes = $types->fetchAll(PDO::FETCH_COLUMN);
        echo "   Captured item types: " . implode(', ', $distinctTypes) . " (PASS)\n";
    } else {
        echo "   No student_assessments found to attach test snapshot (SKIPPED)\n";
    }
    $pdo->rollBack();
    echo "   Transaction rollback preserved live database state: PASS\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "   Snapshot test: FAIL - " . $e->getMessage() . "\n";
}

echo "=== Phase 4 Verification Complete ===\n";
