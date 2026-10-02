<?php
/**
 * LMS UI Fix Verification Suite
 * Verifies that the white blob defect is eradicated and frosted badges/clean alerts are active.
 */

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\View;

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'System Superadmin';
$_SESSION['user_email'] = 'admin@ttu.edu.ph';
$_SESSION['user_role'] = 'superadmin';
$_SESSION['user_dept'] = 'Management Information Systems';

$testsPassed = 0;
$totalTests = 0;

function runTest($name, $condition, $detail = '') {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        $testsPassed++;
        echo " [PASS] Test $totalTests: $name\n";
    } else {
        echo " [FAIL] Test $totalTests: $name\n        Detail: $detail\n";
    }
}

echo "====================================================================\n";
echo "      LMS ADMIN UI & FROSTED BADGE VERIFICATION SUITE              \n";
echo "====================================================================\n\n";

// 1. Check main.css has frosted badges and fallback opacity utilities
$mainCss = file_get_contents(__DIR__ . '/../css/main.css');
runTest(
    "main.css defines .badge-frosted-white",
    strpos($mainCss, '.badge-frosted-white') !== false,
    "Missing .badge-frosted-white in css/main.css"
);
runTest(
    "main.css defines .badge-frosted-dark",
    strpos($mainCss, '.badge-frosted-dark') !== false,
    "Missing .badge-frosted-dark in css/main.css"
);
runTest(
    "main.css defines .badge-frosted-solid",
    strpos($mainCss, '.badge-frosted-solid') !== false,
    "Missing .badge-frosted-solid in css/main.css"
);
runTest(
    "main.css defines .bg-opacity-20 fallback utility",
    strpos($mainCss, '.bg-opacity-20') !== false,
    "Missing .bg-opacity-20 fallback in css/main.css"
);
runTest(
    "main.css defines .pulse-dot-green alias",
    strpos($mainCss, '.pulse-dot-green') !== false,
    "Missing .pulse-dot-green in css/main.css"
);

// 2. Check layout_header.php has frosted badges and refined sidebar tag
$layoutHeader = file_get_contents(__DIR__ . '/../app/Views/lms/admin/layout_header.php');
runTest(
    "layout_header.php defines inline frosted badge rules",
    strpos($layoutHeader, '.badge-frosted-white') !== false,
    "Missing .badge-frosted-white in layout_header.php"
);
runTest(
    "layout_header.php uses badge-indigo-subtle for ADMIN GOVERNANCE",
    strpos($layoutHeader, 'badge-indigo-subtle') !== false,
    "ADMIN GOVERNANCE does not use badge-indigo-subtle"
);
runTest(
    "layout_header.php uses badge-emerald-subtle for active term badge",
    strpos($layoutHeader, 'badge-emerald-subtle') !== false,
    "Active term badge does not use badge-emerald-subtle"
);

// 3. Check dashboard.php course cards
$dashboardView = file_get_contents(__DIR__ . '/../app/Views/lms/admin/dashboard.php');
runTest(
    "dashboard.php does NOT contain buggy 'bg-white bg-opacity-20 text-white' blob pattern",
    strpos($dashboardView, 'bg-white bg-opacity-20 text-white') === false,
    "Buggy blob pattern found in dashboard.php"
);
runTest(
    "dashboard.php uses .badge-frosted-solid for subject code",
    strpos($dashboardView, 'badge-frosted-solid') !== false,
    "Missing badge-frosted-solid in dashboard.php"
);
runTest(
    "dashboard.php uses .badge-frosted-white for academic level",
    strpos($dashboardView, 'badge-frosted-white') !== false,
    "Missing badge-frosted-white in dashboard.php"
);
runTest(
    "dashboard.php uses .badge-frosted-dark for course shell ID",
    strpos($dashboardView, 'badge-frosted-dark') !== false,
    "Missing badge-frosted-dark in dashboard.php"
);
runTest(
    "dashboard.php checks instructor_first for faculty name resolution",
    strpos($dashboardView, 'instructor_first') !== false,
    "Missing instructor_first check in dashboard.php"
);
runTest(
    "dashboard.php uses subtle Awaiting Assignment indicator instead of harsh alert",
    strpos($dashboardView, 'Awaiting Assignment') !== false,
    "Missing refined awaiting assignment state"
);

// 4. Check faculty dashboard.php
$facultyDashboard = file_get_contents(__DIR__ . '/../app/Views/lms/faculty/dashboard.php');
runTest(
    "faculty dashboard.php does NOT contain buggy 'bg-white bg-opacity-25 text-white'",
    strpos($facultyDashboard, 'bg-white bg-opacity-25 text-white') === false,
    "Buggy opacity pattern still found in faculty dashboard.php"
);
runTest(
    "faculty dashboard.php uses .badge-frosted-white",
    strpos($facultyDashboard, 'badge-frosted-white') !== false,
    "Missing badge-frosted-white in faculty dashboard.php"
);

echo "\n====================================================================\n";
echo "SUMMARY: $testsPassed / $totalTests TESTS PASSED (" . round(($testsPassed / $totalTests) * 100) . "%)\n";
echo "====================================================================\n";

if ($testsPassed === $totalTests) {
    echo ">>> UI FIX VERIFICATION SUCCESSFUL - ALL CRITICAL DEFECTS RESOLVED! <<<\n";
    exit(0);
} else {
    echo ">>> VERIFICATION FAILED <<<\n";
    exit(1);
}
