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

echo "Backfilling assessment_items for existing assessments...\n";

$stmt = $pdo->query("
    SELECT sa.*, a.academic_level, a.grade_level, a.strand, a.semester, a.section_id, ft.is_per_unit, ft.tuition_fee as template_tuition_rate
    FROM student_assessments sa
    JOIN applications a ON sa.application_id = a.id
    LEFT JOIN fee_templates ft ON sa.fee_template_id = ft.id
");
$assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$backfilled = 0;
foreach ($assessments as $ass) {
    $assId = (int)$ass['id'];
    $appId = (int)$ass['application_id'];

    // Check if items already exist
    $chk = $pdo->prepare("SELECT COUNT(*) FROM assessment_items WHERE assessment_id = ?");
    $chk->execute([$assId]);
    if ((int)$chk->fetchColumn() > 0) continue;

    // Fetch enrolled subjects
    $enrolled = [];
    if ($ass['academic_level'] === 'College') {
        $subStmt = $pdo->prepare("
            SELECT s.subject_code, s.subject_name, s.units 
            FROM college_enrollments ce 
            JOIN subjects s ON ce.subject_id = s.id 
            WHERE ce.application_id = :app_id
        ");
        $subStmt->execute(['app_id' => $appId]);
        $enrolled = $subStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $subStmt = $pdo->prepare("
            SELECT s.subject_code, s.subject_name, s.units 
            FROM shs_enrollments se 
            JOIN subjects s ON se.subject_id = s.id 
            WHERE se.application_id = :app_id
        ");
        $subStmt->execute(['app_id' => $appId]);
        $enrolled = $subStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $template = [
        'is_per_unit' => $ass['is_per_unit'] ?? 0,
        'tuition_fee' => $ass['template_tuition_rate'] ?? 0.00
    ];

    snapshotAssessmentItems(
        $pdo,
        $assId,
        $appId,
        $template,
        $enrolled,
        (float)$ass['tuition_fee'],
        (float)$ass['miscellaneous_fee'],
        (float)$ass['registration_fee'],
        (float)$ass['laboratory_fee'],
        (float)$ass['other_fees'],
        (float)$ass['discount_amount']
    );
    $backfilled++;
}

echo "Backfilled $backfilled existing assessments with snapshot items!\n";
