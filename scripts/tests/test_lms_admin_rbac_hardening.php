<?php
/**
 * TTU LMS Admin RBAC Hardening Verification Suite
 * Tests server-side authorization enforcement across all required roles,
 * permissions, and attack vectors.
 */

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__, 2) . '/app/';

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

require_once dirname(__DIR__, 2) . '/app/Helpers/functions.php';

use App\Controllers\Admin\LmsAdminController;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\RoleMiddleware;

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function assertTest(bool $condition, string $testName, string $detail = ''): void
{
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "[PASS] Test {$totalTests}: {$testName}" . ($detail ? " ({$detail})" : "") . "\n";
    } else {
        $failedTests++;
        echo "[FAIL] Test {$totalTests}: {$testName}" . ($detail ? " ({$detail})" : "") . "\n";
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
    } catch (ReflectionException $re) {
        throw $re;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "====================================================================\n";
echo "      STARTING LMS ADMIN RBAC HARDENING SECURITY VERIFICATION       \n";
echo "====================================================================\n\n";

$controller = new LmsAdminController();

// -------------------------------------------------------------------------
// TEST 1: superadmin -> LMS Admin (PASS)
// -------------------------------------------------------------------------
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'superadmin';
$_SESSION['user_permissions'] = ['*'];

$err = invokeEnforceAdminAccess($controller);
assertTest($err === null, "superadmin -> LMS Admin", "Access authorized by superadmin role & wildcard");

// -------------------------------------------------------------------------
// TEST 2: admin -> LMS Admin (PASS)
// -------------------------------------------------------------------------
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 3;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_permissions'] = [];

$err = invokeEnforceAdminAccess($controller);
assertTest($err === null, "admin -> LMS Admin", "Access authorized by admin role & base permissions");

// -------------------------------------------------------------------------
// TEST 3: scheduler -> LMS Admin (DENIED WITH 403)
// Scheduler must NOT inherit LMS Admin privileges from sections.manage
// -------------------------------------------------------------------------
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 6;
$_SESSION['user_role'] = 'scheduler';
$_SESSION['user_permissions'] = ['sections.manage', 'schedules.manage'];

$err = invokeEnforceAdminAccess($controller);
$is403 = ($err !== null && $err->getStatusCode() === 403);
assertTest($is403, "scheduler -> LMS Admin [SECURITY CRITICAL]", "Server-side rejection: HTTP 403 Access Denied despite sections.manage");

// -------------------------------------------------------------------------
// TEST 4: registrar (department 'Registrar Office' / manage_registrar) -> LMS Admin (DENIED WITH 403)
// The Registrar account must NOT handle LMS Administration; handled on LMS side by LMS Admin
// -------------------------------------------------------------------------
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 3;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_department'] = 'Registrar Office';
$_SESSION['user_permissions'] = ['manage_registrar', 'manage_curriculum', 'enrollment.finalize'];

$err = invokeEnforceAdminAccess($controller);
$is403 = ($err !== null && $err->getStatusCode() === 403);
assertTest($is403, "registrar (Registrar Office) -> LMS Admin [DOMAIN ISOLATION]", "Server-side rejection: HTTP 403 Access Denied. Handled by LMS Admin on LMS side.");

// -------------------------------------------------------------------------
// TEST 4b: LMS Admin / IT Department Admin -> LMS Admin (PASS)
// -------------------------------------------------------------------------
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 14;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_department'] = 'LMS Administration';
$_SESSION['user_permissions'] = ['lms.manage', 'lms.admin'];

$err = invokeEnforceAdminAccess($controller);
assertTest($err === null, "LMS Admin (LMS Administration dept) -> LMS Admin", "Access authorized for LMS Administrator on LMS side");

// -------------------------------------------------------------------------
// TEST 5: faculty -> LMS Admin (DENIED WITH 403)
// -------------------------------------------------------------------------
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 8;
$_SESSION['user_role'] = 'faculty';
$_SESSION['user_permissions'] = ['lms_faculty', 'manage_courses'];

$err = invokeEnforceAdminAccess($controller);
$is403 = ($err !== null && $err->getStatusCode() === 403);
assertTest($is403, "faculty -> LMS Admin", "Server-side rejection: HTTP 403 Access Denied");

// -------------------------------------------------------------------------
// TEST 6: student -> LMS Admin (DENIED WITH 403)
// -------------------------------------------------------------------------
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 11;
$_SESSION['user_role'] = 'student';
$_SESSION['user_permissions'] = [];

$err = invokeEnforceAdminAccess($controller);
$is403 = ($err !== null && $err->getStatusCode() === 403);
assertTest($is403, "student -> LMS Admin", "Server-side rejection: HTTP 403 Access Denied");

// -------------------------------------------------------------------------
// TEST 7: Unauthorized direct URL access (unauthenticated)
// -------------------------------------------------------------------------
$_SESSION = [];
session_unset();

$err = invokeEnforceAdminAccess($controller);
$is403 = ($err !== null && $err->getStatusCode() === 403);
assertTest($is403, "unauthorized direct URL access (unauthenticated)", "Server-side rejection: HTTP 403 when session is empty");

// -------------------------------------------------------------------------
// TEST 8: Unauthorized POST request (CSRF + authorization rejection)
// -------------------------------------------------------------------------
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 6;
$_SESSION['user_role'] = 'scheduler';
$_SESSION['user_permissions'] = ['sections.manage'];
$_SESSION['csrf_token'] = 'valid_test_token_12345';

// Attempting POST action reassignFaculty as scheduler
$req = new Request();
$res = new Response();
$postBlocked = false;
try {
    $controller->reassignFaculty($req, $res, '1');
} catch (HttpException $e) {
    if ($e->getStatusCode() === 403) {
        $postBlocked = true;
    }
}
assertTest($postBlocked, "unauthorized POST request", "POST /admin/lms/courses/1/reassign aborted before CSRF check with HTTP 403");

// -------------------------------------------------------------------------
// TEST 9: Unauthorized mutation endpoints
// Verify that all state-changing endpoints enforce authorization before execution
// -------------------------------------------------------------------------
$mutationEndpoints = [
    'updateCourseStatus' => function() use ($controller, $req, $res) { $controller->updateCourseStatus($req, $res, '1'); },
    'updateUserStatus'   => function() use ($controller, $req, $res) { $controller->updateUserStatus($req, $res, '11'); },
    'reconcile'          => function() use ($controller, $req, $res) { $controller->reconcile($req, $res); },
    'processArchiveTerm' => function() use ($controller, $req, $res) { $controller->processArchiveTerm($req, $res); },
    'generateLmsCourse'  => function() use ($controller, $req, $res) { $controller->generateLmsCourse($req, $res); },
];

$allMutationsBlocked = true;
foreach ($mutationEndpoints as $action => $fn) {
    try {
        $fn();
        $allMutationsBlocked = false;
        echo "   [!] Mutation endpoint {$action} failed to block scheduler!\n";
    } catch (HttpException $e) {
        if ($e->getStatusCode() !== 403) {
            $allMutationsBlocked = false;
        }
    }
}
assertTest($allMutationsBlocked, "unauthorized mutation endpoints", "All 5 state-changing mutations strictly guarded by enforceAdminAccess");

// -------------------------------------------------------------------------
// TEST 10: Valid LMS permission without unrelated sections.manage permission
// E.g., user possessing explicit lms.manage but NOT sections.manage
// -------------------------------------------------------------------------
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 999;
$_SESSION['user_role'] = 'staff_auditor';
$_SESSION['user_permissions'] = ['lms.manage']; // Has lms.manage, lacks sections.manage

$err = invokeEnforceAdminAccess($controller);
assertTest($err === null, "valid LMS permission without sections.manage", "Access granted via granular lms.manage permission");

// Sub-check: lms.courses.manage permission without sections.manage
$_SESSION['user_permissions'] = ['lms.courses.manage'];
$errCourses = invokeEnforceAdminAccess($controller);
assertTest($errCourses === null, "valid lms.courses.manage without sections.manage", "Access granted via granular lms.courses.manage");

// -------------------------------------------------------------------------
// BONUS TEST 11: Unrelated staff roles (cashier, admissions, clinic) rejected
// -------------------------------------------------------------------------
$unrelatedRoles = ['cashier', 'admissions', 'clinic', 'scholarship'];
$allUnrelatedBlocked = true;
foreach ($unrelatedRoles as $role) {
    $_SESSION['user_role'] = $role;
    $_SESSION['user_permissions'] = ROLE_PERMISSIONS[$role] ?? [];
    $err = invokeEnforceAdminAccess($controller);
    if ($err === null || $err->getStatusCode() !== 403) {
        $allUnrelatedBlocked = false;
        echo "   [!] Unrelated role {$role} was not blocked!\n";
    }
}
assertTest($allUnrelatedBlocked, "unrelated staff roles rejected", "Cashier, Admissions, Clinic, Scholarship all receive HTTP 403");

// -------------------------------------------------------------------------
// BONUS TEST 12: Scheduler retains full access to its own scheduler permissions
// -------------------------------------------------------------------------
$_SESSION['user_role'] = 'scheduler';
$_SESSION['user_permissions'] = [];
$schedulerOwnsSections = hasPermission('sections.manage');
$schedulerOwnsSchedules = hasPermission('schedules.manage');
assertTest($schedulerOwnsSections && $schedulerOwnsSchedules, "normal Scheduler functionality preserved", "Scheduler retains sections.manage and schedules.manage");

echo "\n====================================================================\n";
echo "SUMMARY: {$passedTests} / {$totalTests} TESTS PASSED (" . round(($passedTests / $totalTests) * 100) . "%)\n";
echo "====================================================================\n";

if ($failedTests > 0) {
    echo ">>> STATUS: FAILURE - {$failedTests} tests failed! <<<\n";
    exit(1);
} else {
    echo ">>> RELEASE GATE: SUCCESS - ALL LMS ADMIN RBAC HARDENING TESTS PASSED! <<<\n";
    exit(0);
}
