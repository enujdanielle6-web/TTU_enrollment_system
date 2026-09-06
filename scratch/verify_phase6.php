<?php
/**
 * Phase 6 Verification Script: UI/UX Hardening, Debouncing, Document Feedback & Masterlist Server-Side Pagination
 */

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Core/Database.php';

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

use App\Core\Database;

echo "=== PHASE 6 VERIFICATION TEST SUITE ===\n";
$testsPassed = 0;
$totalTests = 0;

function assertCondition($name, $condition) {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        echo "[PASS] $name\n";
        $testsPassed++;
    } else {
        echo "[FAIL] $name\n";
    }
}

// 1. Enrollment Wizard Debounce & Error Focus
$enrollContent = file_get_contents(__DIR__ . '/../app/Views/applicant/enroll.php');
assertCondition("enroll.php has form submission debouncing", strpos($enrollContent, 'submitBtn.disabled = true') !== false);
assertCondition("enroll.php has spinner loading state on submit", strpos($enrollContent, 'spinner-border') !== false);
assertCondition("enroll.php navigates to wizard step of first invalid field", strpos($enrollContent, 'window.goToWizardStep') !== false && strpos($enrollContent, 'scrollIntoView') !== false);

// 2. Cashier Payments Debouncing
$cashierContent = file_get_contents(__DIR__ . '/../app/Views/admin/finance/cashier_payments.php');
assertCondition("cashier_payments.php has approve form debounce", strpos($cashierContent, 'approvePaymentBtn') !== false && strpos($cashierContent, 'disabled = true') !== false);
assertCondition("cashier_payments.php has reject form debounce", strpos($cashierContent, 'rejectBtn.disabled = true') !== false);

// 3. Status View Document Feedback
$enrollControllerContent = file_get_contents(__DIR__ . '/../app/Controllers/EnrollController.php');
assertCondition("EnrollController queries application_documents", strpos($enrollControllerContent, 'FROM application_documents') !== false);
assertCondition("EnrollController passes documents to applicant/status", strpos($enrollControllerContent, "'documents' => \$documents") !== false);

$statusContent = file_get_contents(__DIR__ . '/../app/Views/applicant/status.php');
assertCondition("status.php has Required Documents Status card", strpos($statusContent, 'Required Documents Status') !== false);
assertCondition("status.php shows feedback for rejected documents", strpos($statusContent, 'Admissions Note') !== false || strpos($statusContent, 'doc[\'feedback\']') !== false);
assertCondition("status.php provides CTA to documents portal", strpos($statusContent, 'documents.php') !== false);

// 4. Registrar Students Controller & Server-Side Pagination
$registrarControllerContent = file_get_contents(__DIR__ . '/../app/Controllers/Admin/Registrar/RegistrarController.php');
assertCondition("RegistrarController has KPI counts query", strpos($registrarControllerContent, 'total_count') !== false && strpos($registrarControllerContent, 'college_count') !== false);
assertCondition("RegistrarController parses per_page and page", strpos($registrarControllerContent, '$perPage') !== false && strpos($registrarControllerContent, '$page') !== false);
assertCondition("RegistrarController executes LIMIT :limit OFFSET :offset", strpos($registrarControllerContent, 'LIMIT :limit OFFSET :offset') !== false);
assertCondition("RegistrarController filters by level, grade, strand, status, search", strpos($registrarControllerContent, '$whereClauses') !== false);

// 5. Database Query Execution for Registrar Masterlist
try {
    $pdo = Database::getConnection();
    
    // Test KPI Query
    $statsStmt = $pdo->query('
        SELECT 
            COUNT(*) as total_count,
            COALESCE(SUM(CASE WHEN a.academic_level = "College" THEN 1 ELSE 0 END), 0) as college_count,
            COALESCE(SUM(CASE WHEN a.academic_level = "Senior High School" THEN 1 ELSE 0 END), 0) as shs_count,
            COALESCE(SUM(CASE WHEN a.status = "enrolled" THEN 1 ELSE 0 END), 0) as enrolled_count,
            COALESCE(SUM(CASE WHEN a.status = "approved" THEN 1 ELSE 0 END), 0) as approved_count
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        WHERE (u.role IN ("applicant", "student") OR a.id IS NOT NULL)
    ');
    $kpi = $statsStmt->fetch(PDO::FETCH_ASSOC);
    assertCondition("KPI stats query runs without SQL errors", is_array($kpi) && isset($kpi['total_count']));

    // Test Dynamic Filter & Pagination Query
    $params = [
        ':s1' => '%2026%',
        ':s2' => '%2026%',
        ':s3' => '%2026%',
        ':s4' => '%2026%',
        ':s5' => '%2026%',
        ':s6' => '%2026%',
        ':limit' => 25,
        ':offset' => 0
    ];
    $stmt = $pdo->prepare('
        SELECT 
            a.id, 
            a.reference_number, 
            a.lrn,
            a.status, 
            a.academic_level,
            a.strand, 
            a.grade_level,
            a.gender,
            a.contact_number,
            u.first_name, 
            u.last_name,
            u.student_number
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        WHERE (u.role IN ("applicant", "student") OR a.id IS NOT NULL)
          AND (a.reference_number LIKE :s1 OR a.lrn LIKE :s2 OR u.student_number LIKE :s3 OR u.first_name LIKE :s4 OR u.last_name LIKE :s5 OR CONCAT(u.first_name, " ", u.last_name) LIKE :s6)
        ORDER BY a.grade_level ASC, a.strand ASC, u.last_name ASC
        LIMIT :limit OFFSET :offset
    ');
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $res = $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    assertCondition("Paginated masterlist query executes successfully", $res === true && is_array($rows));
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    assertCondition("Database execution", false);
}

// 6. View Pagination Component in students.php
$studentsViewContent = file_get_contents(__DIR__ . '/../app/Views/admin/registrar/students.php');
assertCondition("students.php has GET filterForm", strpos($studentsViewContent, '<form method="GET" action="students.php" id="filterForm">') !== false);
assertCondition("students.php has records per page selector", strpos($studentsViewContent, 'perPageSelector') !== false);
assertCondition("students.php has pagination nav links", strpos($studentsViewContent, 'aria-label="Student records pagination"') !== false);
assertCondition("students.php has CSV export link with query string", strpos($studentsViewContent, 'students_export.php?<?= $exportQuery ?>') !== false);

// 7. Route check for students_export.php
$routesContent = file_get_contents(__DIR__ . '/../app/Routes/web.php');
assertCondition("web.php registers GET for students_export.php", strpos($routesContent, "\$router->get('/admin/registrar/students_export.php'") !== false);

echo "\n=======================================\n";
echo "TEST RESULTS: $testsPassed / $totalTests PASSED\n";
echo "=======================================\n";

if ($testsPassed === $totalTests) {
    exit(0);
} else {
    exit(1);
}
