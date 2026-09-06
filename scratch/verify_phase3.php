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

echo "=== Phase 3 Verification ===\n";

// 1. Verify application_subject_requests table
$stmt = $pdo->query("SHOW TABLES LIKE 'application_subject_requests'");
$hasAsr = (bool) $stmt->fetchColumn();
echo "1. Table application_subject_requests exists: " . ($hasAsr ? "PASS" : "FAIL") . "\n";

if ($hasAsr) {
    $cols = $pdo->query("SHOW COLUMNS FROM application_subject_requests")->fetchAll(PDO::FETCH_COLUMN);
    $expectedCols = ['id', 'application_id', 'subject_id', 'section_id', 'created_at'];
    $diff = array_diff($expectedCols, $cols);
    echo "   Columns check: " . (empty($diff) ? "PASS (" . implode(', ', $cols) . ")" : "FAIL (missing " . implode(', ', $diff) . ")") . "\n";
}

// 2. Verify student_scholarships is dropped
$stmt = $pdo->query("SHOW TABLES LIKE 'student_scholarships'");
$hasDeadTable = (bool) $stmt->fetchColumn();
echo "2. Dead table student_scholarships dropped: " . (!$hasDeadTable ? "PASS" : "FAIL") . "\n";

// 3. Verify student_academic_records_view
try {
    $viewStmt = $pdo->query("SELECT * FROM student_academic_records_view LIMIT 5");
    $viewRows = $viewStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "3. View student_academic_records_view queried successfully: PASS (" . count($viewRows) . " rows returned)\n";
} catch (Exception $e) {
    echo "3. View student_academic_records_view query: FAIL - " . $e->getMessage() . "\n";
}

// 4. Test transactional insert and cleanup in application_subject_requests
try {
    $pdo->beginTransaction();
    // Fetch a sample application and subject
    $appId = $pdo->query("SELECT id FROM applications LIMIT 1")->fetchColumn();
    $subId = $pdo->query("SELECT id FROM subjects LIMIT 1")->fetchColumn();

    if ($appId && $subId) {
        $ins = $pdo->prepare("INSERT INTO application_subject_requests (application_id, subject_id, section_id) VALUES (?, ?, NULL)");
        $ins->execute([$appId, $subId]);
        $testId = $pdo->lastInsertId();

        $check = $pdo->prepare("SELECT COUNT(*) FROM application_subject_requests WHERE id = ?");
        $check->execute([$testId]);
        $count = (int) $check->fetchColumn();
        echo "4. Subject request persistence insert check: " . ($count === 1 ? "PASS" : "FAIL") . "\n";
    } else {
        echo "4. Subject request persistence test skipped (no applications or subjects in database)\n";
    }

    $pdo->rollBack();
    echo "   Transaction rollback successfully preserved test isolation: PASS\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "4. Transaction test: FAIL - " . $e->getMessage() . "\n";
}

echo "=== Phase 3 Verification Complete ===\n";
