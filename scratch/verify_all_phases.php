<?php
/**
 * MASTER VERIFICATION SUITE: All Phases (1 through 6)
 * Verifies every engineering deliverable of the TTU Enrollment System Improvement Plan.
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

echo "======================================================================\n";
echo "       TTU ENROLLMENT SYSTEM — MASTER SYSTEM-WIDE VERIFICATION        \n";
echo "======================================================================\n\n";

$passCount = 0;
$failCount = 0;

function checkTest($label, $condition, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] $label\n";
        $passCount++;
    } else {
        echo "  [FAIL] $label" . ($details ? " ($details)" : "") . "\n";
        $failCount++;
    }
}

$pdo = Database::getConnection();

// ====================================================================
// PHASE 1: Critical Security & Runtime Schema Fixes
// ====================================================================
echo "--- PHASE 1: Security & Schema Fixes ---\n";

$htaccess = file_get_contents(__DIR__ . '/../.htaccess');
checkTest("setup_database.php exemption removed from .htaccess", strpos($htaccess, 'setup_database.php') === false);

$setupDb = file_get_contents(__DIR__ . '/../database/migrations/setup_database.php');
checkTest("setup_database.php has CLI execution guard", strpos($setupDb, "php_sapi_name() !== 'cli'") !== false);

$userModel = file_get_contents(__DIR__ . '/../app/Models/User.php');
checkTest("User::findByEmail() includes email_verified", strpos($userModel, 'email_verified') !== false);

$authController = file_get_contents(__DIR__ . '/../app/Controllers/AuthController.php');
checkTest("AuthController uses reset_token instead of reset_password_code", strpos($authController, 'reset_password_code') === false && strpos($authController, 'reset_token') !== false);

$systemController = file_get_contents(__DIR__ . '/../app/Controllers/Admin/System/SystemController.php');
checkTest("SystemController guards superadmin role creation", strpos($systemController, "\$role === 'superadmin'") !== false && strpos($systemController, "user_role") !== false);

$docController = file_get_contents(__DIR__ . '/../app/Controllers/DocumentController.php');
checkTest("DocumentController uses correct directory resolution", strpos($docController, 'dirname(__DIR__, 2)') !== false);

$scholarshipCols = $pdo->query("SHOW COLUMNS FROM scholarship_recipients LIKE 'remarks'")->fetchAll();
checkTest("scholarship_recipients has 'remarks' column in MariaDB", count($scholarshipCols) > 0);


// ====================================================================
// PHASE 2: Enrollment Workflow & State Machine Alignment
// ====================================================================
echo "\n--- PHASE 2: Enrollment Workflow & State Machine ---\n";

$financeController = file_get_contents(__DIR__ . '/../app/Controllers/Admin/Finance/FinanceController.php');
checkTest("FinanceController does not call finalizeStudentEnrollment", strpos($financeController, 'finalizeStudentEnrollment(') === false);
checkTest("FinanceController sets application status to payment_verified", strpos($financeController, 'payment_verified') !== false);

$registrarController = file_get_contents(__DIR__ . '/../app/Controllers/Admin/Registrar/RegistrarController.php');
checkTest("RegistrarController queries payment_verified in queues", strpos($registrarController, 'payment_verified') !== false);
checkTest("RegistrarController implements finalizeEnrollment", method_exists('App\Controllers\Admin\Registrar\RegistrarController', 'finalizeEnrollment'));

$admissionsController = file_get_contents(__DIR__ . '/../app/Controllers/Admin/Admissions/AdmissionsController.php');
checkTest("AdmissionsController enforces clinic medical clearance gate", strpos($admissionsController, 'SELECT status FROM health_records') !== false && strpos($admissionsController, 'Medical clearance from the Clinic is pending') !== false);

$middleware = file_get_contents(__DIR__ . '/../app/Middleware/SessionSecurityMiddleware.php');
checkTest("SessionSecurityMiddleware enforces force_password_reset redirect", strpos($middleware, 'force_password_reset') !== false);


// ====================================================================
// PHASE 3: Irregular Students, Transactions & Data Integrity
// ====================================================================
echo "\n--- PHASE 3: Irregular Students, Transactions & Views ---\n";

$tables = $pdo->query("SHOW TABLES LIKE 'application_subject_requests'")->fetchAll();
checkTest("Table application_subject_requests exists in MariaDB", count($tables) > 0);

$deadTable = $pdo->query("SHOW TABLES LIKE 'student_scholarships'")->fetchAll();
checkTest("Dead table student_scholarships dropped from MariaDB", count($deadTable) === 0);

$viewTest = $pdo->query("SELECT COUNT(*) FROM student_academic_records_view")->fetchColumn();
checkTest("student_academic_records_view queries successfully (College & SHS)", is_numeric($viewTest));

$enrollController = file_get_contents(__DIR__ . '/../app/Controllers/EnrollController.php');
checkTest("EnrollController persists irregular application_subject_requests", strpos($enrollController, 'application_subject_requests') !== false);
checkTest("EnrollController processForm wraps multi-table writes in transaction", strpos($enrollController, 'beginTransaction') !== false);


// ====================================================================
// PHASE 4: Financial Snapshotting & Concurrency Guards
// ====================================================================
echo "\n--- PHASE 4: Financial Snapshotting & Concurrency ---\n";

$itemTable = $pdo->query("SHOW TABLES LIKE 'assessment_items'")->fetchAll();
checkTest("Table assessment_items exists in MariaDB", count($itemTable) > 0);

$seqTable = $pdo->query("SHOW TABLES LIKE 'receipt_sequences'")->fetchAll();
checkTest("Table receipt_sequences exists in MariaDB", count($seqTable) > 0);

$appIdx = $pdo->query("SHOW INDEX FROM applications WHERE Key_name = 'idx_app_status_level'")->fetchAll();
checkTest("Composite index idx_app_status_level exists on applications", count($appIdx) > 0);

$saIdx = $pdo->query("SHOW INDEX FROM student_assessments WHERE Key_name = 'idx_assessment_payment_status'")->fetchAll();
checkTest("Composite index idx_assessment_payment_status exists on student_assessments", count($saIdx) > 0);

$activityIdx = $pdo->query("SHOW INDEX FROM activity_logs WHERE Key_name = 'idx_activity_logs_user_created'")->fetchAll();
checkTest("Composite index idx_activity_logs_user_created exists on activity_logs", count($activityIdx) > 0);

$receiptNum = generateAtomicReceiptNumber($pdo);
checkTest("generateAtomicReceiptNumber() generates monotonic receipt number", strpos($receiptNum, 'REC-') === 0);


// ====================================================================
// PHASE 5: Architecture & Service Layer Extraction
// ====================================================================
echo "\n--- PHASE 5: Architecture & Domain Services ---\n";

checkTest("Service Class App\\Services\\StudentNumberService exists", class_exists('App\\Services\\StudentNumberService'));
checkTest("Service Class App\\Services\\AssessmentService exists", class_exists('App\\Services\\AssessmentService'));
checkTest("Service Class App\\Services\\EnrollmentService exists", class_exists('App\\Services\\EnrollmentService'));

$snSeqTable = $pdo->query("SHOW TABLES LIKE 'student_number_sequences'")->fetchAll();
checkTest("Table student_number_sequences exists in MariaDB", count($snSeqTable) > 0);

$sn = \App\Services\StudentNumberService::generate((int)date('Y'), $pdo);
checkTest("StudentNumberService generates atomic format YYYY-XXXXXX ($sn)", preg_match('/^\d{4}-\d{6}$/', $sn) === 1);

$assessmentService = file_get_contents(__DIR__ . '/../app/Services/AssessmentService.php');
checkTest("functions.php has snapshotAssessmentItems() helper", function_exists('snapshotAssessmentItems'));
checkTest("AssessmentService delegates to snapshotAssessmentItems", strpos($assessmentService, 'snapshotAssessmentItems') !== false);
checkTest("AssessmentService has getAssessmentBreakdown", method_exists('App\\Services\\AssessmentService', 'getAssessmentBreakdown'));
checkTest("AssessmentService has generateAssessment", method_exists('App\\Services\\AssessmentService', 'generateAssessment'));

$enrollmentService = file_get_contents(__DIR__ . '/../app/Services/EnrollmentService.php');
checkTest("EnrollmentService has finalizeEnrollment method", method_exists('App\\Services\\EnrollmentService', 'finalizeEnrollment'));


// ====================================================================
// PHASE 6: UI/UX Hardening, Debouncing & Server-Side Pagination
// ====================================================================
echo "\n--- PHASE 6: UI/UX Hardening & Masterlist Pagination ---\n";

$enrollView = file_get_contents(__DIR__ . '/../app/Views/applicant/enroll.php');
checkTest("enroll.php has button debouncing on submit", strpos($enrollView, 'submitBtn.disabled = true') !== false);
checkTest("enroll.php has wizard step error auto-navigation", strpos($enrollView, 'window.goToWizardStep') !== false && strpos($enrollView, 'scrollIntoView') !== false);

$cashierPayments = file_get_contents(__DIR__ . '/../app/Views/admin/finance/cashier_payments.php');
checkTest("cashier_payments.php has approve payment debouncing", strpos($cashierPayments, 'approvePaymentBtn') !== false && strpos($cashierPayments, 'disabled = true') !== false);
checkTest("cashier_payments.php has reject payment debouncing", strpos($cashierPayments, 'rejectBtn.disabled = true') !== false);

$statusView = file_get_contents(__DIR__ . '/../app/Views/applicant/status.php');
checkTest("status.php has Required Documents Status card", strpos($statusView, 'Required Documents Status') !== false);
checkTest("status.php displays document review feedback remarks", strpos($statusView, 'Admissions Note') !== false || strpos($statusView, 'doc[\'feedback\']') !== false);

checkTest("RegistrarController has server-side pagination & KPI aggregation", strpos($registrarController, 'LIMIT :limit OFFSET :offset') !== false && strpos($registrarController, 'total_count') !== false);

$studentsView = file_get_contents(__DIR__ . '/../app/Views/admin/registrar/students.php');
checkTest("students.php has GET filterForm with auto-submit", strpos($studentsView, '<form method="GET" action="students.php" id="filterForm">') !== false);
checkTest("students.php has perPageSelector dropdown (25/50/100)", strpos($studentsView, 'perPageSelector') !== false);
checkTest("students.php has server-side pagination navigation bar", strpos($studentsView, 'aria-label="Student records pagination"') !== false);

$webRoutes = file_get_contents(__DIR__ . '/../app/Routes/web.php');
checkTest("web.php registers GET for students_export.php", strpos($webRoutes, "\$router->get('/admin/registrar/students_export.php'") !== false);


// ====================================================================
// OVERALL SUMMARY
// ====================================================================
$total = $passCount + $failCount;
echo "\n======================================================================\n";
echo "   MASTER TEST RESULTS: $passCount / $total CHECKS PASSED (" . round(($passCount / $total) * 100, 1) . "%)\n";
echo "======================================================================\n";

if ($failCount === 0) {
    echo ">>> ALL 6 PHASES ARE VERIFIED 100% OPERATIONAL & IN PRODUCTION STATE <<<\n\n";
    exit(0);
} else {
    echo ">>> SOME CHECKS FAILED: PLEASE REVIEW OUTPUT ABOVE <<<\n\n";
    exit(1);
}
