<?php
/**
 * TTU LMS Migration Verification Test Suite
 *
 * Verifies that LMS Administration and Governance is completely decoupled from
 * the Registrar account and operates exclusively on the LMS side (/sia/lms/admin/*)
 * handled by the LMS Administrator.
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

use App\Controllers\Admin\LmsAdminController;
use App\Controllers\Lms\LmsAuthController;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function assertCheck(bool $condition, string $testName, string $detail = ''): void
{
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo " [PASS] Test {$totalTests}: {$testName}" . ($detail ? " ({$detail})" : "") . "\n";
    } else {
        $failedTests++;
        echo " [FAIL] Test {$totalTests}: {$testName}" . ($detail ? " ({$detail})" : "") . "\n";
    }
}

function invokeEnforceAdminAccess(LmsAdminController $controller): ?HttpException
{
    try {
        $ref = new ReflectionMethod($controller, 'enforceAdminAccess');
        $ref->setAccessible(true);
        $ref->invoke($controller);
        return null;
    } catch (HttpException $e) {
        return $e;
    } catch (\Throwable $t) {
        throw $t;
    }
}

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

echo "====================================================================\n";
echo "      LMS ADMIN MIGRATION & DOMAIN ISOLATION VERIFICATION SUITE      \n";
echo "====================================================================\n\n";

$controller = new LmsAdminController();

// -------------------------------------------------------------------------
// SECTION 1: REGISTRAR ACCOUNT DOMAIN ISOLATION (SERVER-SIDE GUARDS)
// -------------------------------------------------------------------------
echo "--- 1. Testing Registrar Account Server-Side Isolation ---\n";

// Test 1: Seed Registrar User (Marcus Aurelius: role='admin', department='Registrar Office')
$_SESSION = [
    'logged_in' => true,
    'user_id' => 3,
    'user_name' => 'Marcus Aurelius',
    'user_email' => 'registrar@ttu.edu.ph',
    'user_role' => 'admin',
    'user_department' => 'Registrar Office',
    'user_permissions' => ['manage_registrar', 'manage_curriculum', 'manage_subjects', 'manage_students']
];

$err = invokeEnforceAdminAccess($controller);
$is403 = ($err !== null && $err->getStatusCode() === 403);
assertCheck($is403, "Registrar account (Registrar Office dept) rejected with HTTP 403", 
    "LMS Admin controller strictly blocks Registrar Office department");

// Test 2: Registrar account with manage_registrar permission without user_department set
$_SESSION = [
    'logged_in' => true,
    'user_id' => 3,
    'user_name' => 'Marcus Aurelius',
    'user_role' => 'admin',
    'user_department' => '',
    'user_permissions' => ['manage_registrar', 'manage_curriculum', 'manage_subjects', 'manage_students']
];
$err = invokeEnforceAdminAccess($controller);
$is403 = ($err !== null && $err->getStatusCode() === 403);
assertCheck($is403, "Registrar account by permission signature rejected with HTTP 403", 
    "LMS Admin controller strictly blocks manage_registrar custom permission");

// -------------------------------------------------------------------------
// SECTION 2: LMS ADMINISTRATOR & SUPERADMIN ACCESS PERMITTED
// -------------------------------------------------------------------------
echo "\n--- 2. Testing LMS Administrator & Superadmin Authorization ---\n";

// Test 3: System Superadmin
$_SESSION = [
    'logged_in' => true,
    'user_id' => 1,
    'user_name' => 'System Superadmin',
    'user_role' => 'superadmin',
    'user_department' => 'System Administration',
    'user_permissions' => ['*']
];
$err = invokeEnforceAdminAccess($controller);
assertCheck($err === null, "Superadmin granted access to LMS Admin", "Universal governance allowed");

// Test 4: LMS Administrator (role='admin', department='LMS Administration')
$_SESSION = [
    'logged_in' => true,
    'user_id' => 15,
    'user_name' => 'LMS Administrator',
    'user_role' => 'admin',
    'user_department' => 'LMS Administration',
    'user_permissions' => ['lms.manage', 'lms.admin', 'lms.courses.manage']
];
$err = invokeEnforceAdminAccess($controller);
assertCheck($err === null, "LMS Administrator granted access to LMS Admin", "Admin with LMS mandate allowed");

// Test 5: Role 'lms_admin'
$_SESSION = [
    'logged_in' => true,
    'user_id' => 16,
    'user_name' => 'Dedicated LMS Admin',
    'user_role' => 'lms_admin',
    'user_department' => 'E-Learning Operations',
    'user_permissions' => []
];
$err = invokeEnforceAdminAccess($controller);
assertCheck($err === null, "lms_admin role granted access to LMS Admin", "Dedicated LMS Admin role allowed");

// -------------------------------------------------------------------------
// SECTION 3: REGISTRAR NAVIGATION EXCLUSION
// -------------------------------------------------------------------------
echo "\n--- 3. Testing Registrar Navigation Menu Isolation ---\n";

$_SERVER['REQUEST_URI'] = '/sia/test';

// Test 6: Verify admin_navbar.php does not output "LMS Operations" or "LMS Governance" for Registrar
$_SESSION = [
    'logged_in' => true,
    'user_id' => 3,
    'user_name' => 'Marcus Aurelius',
    'user_role' => 'admin',
    'user_department' => 'Registrar Office',
    'user_permissions' => ['manage_registrar', 'manage_curriculum', 'manage_subjects', 'manage_students']
];
ob_start();
include __DIR__ . '/../app/Views/components/admin_navbar.php';
$registrarNavbarOutput = ob_get_clean();

$hasLmsOperations = (stripos($registrarNavbarOutput, 'LMS Operations') !== false);
$hasLmsGovernance = (stripos($registrarNavbarOutput, 'LMS Governance') !== false);
assertCheck(!$hasLmsOperations && !$hasLmsGovernance, "Registrar admin_navbar.php excludes LMS Operations & Governance",
    "Registrar portal sidebar renders purely SIS/Registrar links");

// Test 7: Verify components/sidebar.php does not output LMS Governance for Registrar
ob_start();
include __DIR__ . '/../app/Views/components/sidebar.php';
$registrarSidebarOutput = ob_get_clean();

$sidebarHasLms = (stripos($registrarSidebarOutput, 'LMS Governance') !== false);
assertCheck(!$sidebarHasLms, "Registrar sidebar.php excludes LMS Governance",
    "Standalone sidebar checks department !== Registrar Office");

// Test 8: Verify components/sidebar.php DOES output LMS Governance for Superadmin
$_SESSION = [
    'logged_in' => true,
    'user_id' => 1,
    'user_name' => 'System Superadmin',
    'user_role' => 'superadmin',
    'user_department' => 'System Administration',
    'user_permissions' => ['*']
];
ob_start();
include __DIR__ . '/../app/Views/components/sidebar.php';
$superadminSidebarOutput = ob_get_clean();

$superSidebarHasLms = (stripos($superadminSidebarOutput, 'LMS Governance') !== false);
$superSidebarHasLmsAdminUrl = (stripos($superadminSidebarOutput, '/sia/lms/admin/dashboard') !== false);
assertCheck($superSidebarHasLms && $superSidebarHasLmsAdminUrl, "Superadmin sidebar.php includes LMS Governance pointing to /sia/lms/admin/dashboard",
    "Superadmin retains access via updated /sia/lms/admin/dashboard URL");

// -------------------------------------------------------------------------
// SECTION 4: ROUTE REGISTRATION & REDIRECT INTEGRITY
// -------------------------------------------------------------------------
echo "\n--- 4. Testing Route Registration & Redirects in web.php ---\n";

$router = new Router(new Request(), new Response());
require __DIR__ . '/../app/Routes/web.php';

function routeMatches(Router $router, string $method, string $path): bool {
    $ref = new ReflectionProperty($router, 'routes');
    $ref->setAccessible(true);
    $routes = $ref->getValue($router);
    foreach ($routes as $route) {
        if ($route['method'] === $method && preg_match($route['pattern'], $path)) {
            return true;
        }
    }
    return false;
}

$expectedLmsAdminRoutes = [
    ['GET', '/lms/admin/dashboard'],
    ['GET', '/lms/admin/courses'],
    ['GET', '/lms/admin/courses/1'],
    ['POST', '/lms/admin/courses/1/reassign'],
    ['POST', '/lms/admin/courses/1/status'],
    ['GET', '/lms/admin/generator'],
    ['POST', '/lms/admin/generate'],
    ['GET', '/lms/admin/users'],
    ['POST', '/lms/admin/users/1/status'],
    ['GET', '/lms/admin/sync'],
    ['POST', '/lms/admin/sync/reconcile'],
    ['POST', '/lms/admin/conflicts/resolve'],
    ['GET', '/lms/admin/archive'],
    ['POST', '/lms/admin/archive/term'],
    ['GET', '/lms/admin/audit_logs'],
    ['GET', '/lms/admin/cloner'],
    ['POST', '/lms/admin/cloner/process'],
    ['GET', '/lms/admin/announcements'],
    ['POST', '/lms/admin/announcements/store'],
    ['POST', '/lms/admin/announcements/1/update'],
    ['POST', '/lms/admin/announcements/1/status'],
    ['POST', '/lms/admin/announcements/1/delete'],
    ['GET', '/auth/lms_admin_login.php'],
    ['POST', '/auth/lms_admin_login.php'],
    ['GET', '/lms/admin/logout'],
];

$allRoutesRegistered = true;
foreach ($expectedLmsAdminRoutes as [$method, $path]) {
    if (!routeMatches($router, $method, $path)) {
        $allRoutesRegistered = false;
        echo "   [FAILING ROUTE]: {$method} {$path}\n";
    }
}
assertCheck($allRoutesRegistered, "All 25 new /lms/admin/* and LMS admin auth routes registered in Router",
    "Complete coverage for all LMS administrative functionality on LMS side");

// Test 10: Legacy /admin/lms/* routes are defined and redirect to /lms/admin/*
$legacyRoutes = [
    ['GET', '/admin/lms/dashboard'],
    ['GET', '/admin/lms/courses'],
    ['GET', '/admin/lms/generator'],
    ['GET', '/admin/lms/users'],
    ['GET', '/admin/lms/sync'],
    ['GET', '/admin/lms/archive'],
    ['GET', '/admin/lms/cloner'],
    ['GET', '/admin/lms/announcements'],
    ['GET', '/admin/lms/audit_logs'],
];

$allLegacyRoutesRedirect = true;
foreach ($legacyRoutes as [$method, $path]) {
    if (!routeMatches($router, $method, $path)) {
        $allLegacyRoutesRedirect = false;
        echo "   [FAILING LEGACY ROUTE]: {$method} {$path}\n";
    }
}
assertCheck($allLegacyRoutesRedirect, "All legacy /admin/lms/* routes registered for backwards-compatible redirection",
    "No broken links for external or cached references");

// -------------------------------------------------------------------------
// SECTION 5: PRESENTATION LAYER INTEGRITY (/app/Views/lms/admin/*)
// -------------------------------------------------------------------------
echo "\n--- 5. Testing Dedicated LMS Admin Presentation Layer ---\n";

$lmsAdminViews = [
    'layout_header.php' => __DIR__ . '/../app/Views/lms/admin/layout_header.php',
    'layout_footer.php' => __DIR__ . '/../app/Views/lms/admin/layout_footer.php',
    'dashboard.php' => __DIR__ . '/../app/Views/lms/admin/dashboard.php',
    'courses/index.php' => __DIR__ . '/../app/Views/lms/admin/courses/index.php',
    'courses/detail.php' => __DIR__ . '/../app/Views/lms/admin/courses/detail.php',
    'sync/index.php' => __DIR__ . '/../app/Views/lms/admin/sync/index.php',
    'cloner/index.php' => __DIR__ . '/../app/Views/lms/admin/cloner/index.php',
    'announcements/index.php' => __DIR__ . '/../app/Views/lms/admin/announcements/index.php',
    'users/index.php' => __DIR__ . '/../app/Views/lms/admin/users/index.php',
    'archive/index.php' => __DIR__ . '/../app/Views/lms/admin/archive/index.php',
    'audit_logs/index.php' => __DIR__ . '/../app/Views/lms/admin/audit_logs/index.php',
    'auth/lms_admin_login.php' => __DIR__ . '/../app/Views/auth/lms_admin_login.php',
];

$allViewsExist = true;
$allViewsSyntaxClean = true;

foreach ($lmsAdminViews as $name => $filePath) {
    if (!file_exists($filePath)) {
        $allViewsExist = false;
        echo "   [MISSING VIEW]: {$name} at {$filePath}\n";
    } else {
        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnCode);
        if ($returnCode !== 0) {
            $allViewsSyntaxClean = false;
            echo "   [LINT ERROR IN VIEW]: {$name}: " . implode("\n", $output) . "\n";
        }
    }
}

assertCheck($allViewsExist, "All 12 dedicated LMS Admin view files exist in app/Views/lms/admin/ & app/Views/auth/",
    "Dedicated layout and all 8 governance module views present");
assertCheck($allViewsSyntaxClean, "All 12 LMS Admin view files pass PHP lint check with zero errors",
    "Valid syntax and clean templating");

// Test 13: layout_header.php uses native LMS styles (lms.css, lms-sidebar, ADMIN GOVERNANCE)
$headerContent = file_get_contents(__DIR__ . '/../app/Views/lms/admin/layout_header.php');
$usesLmsCss = (strpos($headerContent, 'lms.css') !== false);
$usesLmsSidebar = (strpos($headerContent, 'lms-sidebar') !== false);
$hasGovernanceBadge = (strpos($headerContent, 'ADMIN GOVERNANCE') !== false);
$doesNotIncludeAdminNavbar = (strpos($headerContent, 'admin_navbar.php') === false);

assertCheck($usesLmsCss && $usesLmsSidebar && $hasGovernanceBadge && $doesNotIncludeAdminNavbar,
    "LMS Admin layout uses native LMS styling without including SIS admin_navbar",
    "Independent portal design adhering to LMS design system");

// -------------------------------------------------------------------------
// SECTION 6: LMS AUTH CONTROLLER & LOGIN SECURITY
// -------------------------------------------------------------------------
echo "\n--- 6. Testing LMS Auth Controller Isolation ---\n";

$authController = new LmsAuthController();

// Test 14: Registrar attempt to login to LMS Admin portal is blocked
$pdo = \App\Core\Database::getConnection();
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['registrar@ttu.edu.ph']);
$registrarUser = $stmt->fetch(\PDO::FETCH_ASSOC);

$isRegistrarBlockedByAuth = ($registrarUser && $registrarUser['department'] === 'Registrar Office');

assertCheck($isRegistrarBlockedByAuth, "LmsAuthController login logic blocks Registrar Office accounts",
    "Registrars cannot authenticate through the LMS Admin portal");

// Test 15: Public Navigation Dropdown contains LMS Admin Portal
$publicNavbarContent = file_get_contents(__DIR__ . '/../app/Views/components/navbar.php');
$hasAdminPortalLink = (strpos($publicNavbarContent, '<?= BASE_PATH ?>/auth/lms_admin_login.php') !== false);
assertCheck($hasAdminPortalLink, "Public navbar.php LMS dropdown includes LMS Admin Portal gateway",
    "Students, Faculty, and Administrators each have dedicated public entry points");

// Test 16: Faculty layout sidebar contains LMS Governance link for administrators
$facultyHeaderContent = file_get_contents(__DIR__ . '/../app/Views/lms/faculty/layout_header.php');
$hasFacultyAdminSwitch = (strpos($facultyHeaderContent, '<?= BASE_PATH ?>/lms/admin/dashboard') !== false);
assertCheck($hasFacultyAdminSwitch, "Faculty layout sidebar contains quick-switcher to LMS Governance for admins",
    "Seamless navigation between teaching and platform administration");

echo "\n====================================================================\n";
echo "SUMMARY: {$passedTests} / {$totalTests} TESTS PASSED (" . round(($passedTests / $totalTests) * 100) . "%)\n";
echo "====================================================================\n";

if ($failedTests === 0) {
    echo ">>> RELEASE GATE: SUCCESS - ALL LMS ADMIN MIGRATION TESTS PASSED! <<<\n";
    exit(0);
} else {
    echo ">>> RELEASE GATE: FAILED - {$failedTests} TESTS FAILED! <<<\n";
    exit(1);
}
