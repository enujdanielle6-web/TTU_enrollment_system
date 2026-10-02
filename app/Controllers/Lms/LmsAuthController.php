<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;

class LmsAuthController extends BaseController
{
    public function showAdminLogin(Request $request, Response $response)
    {
        $errors = $_SESSION['login_errors'] ?? [];
        $success = $_SESSION['login_success'] ?? null;
        $warning = $_SESSION['login_warning'] ?? null;
        $old = $_SESSION['login_old'] ?? [];
        unset($_SESSION['login_errors'], $_SESSION['login_success'], $_SESSION['login_warning'], $_SESSION['login_old']);

        $pageTitle = 'LMS Administrator Portal Login - Triple T University';

        return $this->render('auth/lms_admin_login', [
            'pageTitle' => $pageTitle,
            'errors' => $errors,
            'success' => $success,
            'warning' => $warning,
            'old' => $old
        ]);
    }

    public function showFacultyLogin(Request $request, Response $response)
    {
        $errors = $_SESSION['login_errors'] ?? [];
        $success = $_SESSION['login_success'] ?? null;
        $warning = $_SESSION['login_warning'] ?? null;
        $old = $_SESSION['login_old'] ?? [];
        unset($_SESSION['login_errors'], $_SESSION['login_success'], $_SESSION['login_warning'], $_SESSION['login_old']);

        $pageTitle = 'Faculty LMS Login - Triple T University';

        return $this->render('auth/lms_faculty_login', [
            'pageTitle' => $pageTitle,
            'errors' => $errors,
            'success' => $success,
            'warning' => $warning,
            'old' => $old
        ]);
    }
    public function showStudentLogin(Request $request, Response $response)
    {
        $errors = $_SESSION['login_errors'] ?? [];
        $success = $_SESSION['login_success'] ?? null;
        $warning = $_SESSION['login_warning'] ?? null;
        $old = $_SESSION['login_old'] ?? [];
        unset($_SESSION['login_errors'], $_SESSION['login_success'], $_SESSION['login_warning'], $_SESSION['login_old']);

        $pageTitle = 'Student LMS Login - Triple T University';

        return $this->render('auth/lms_student_login', [
            'pageTitle' => $pageTitle,
            'errors' => $errors,
            'success' => $success,
            'warning' => $warning,
            'old' => $old
        ]);
    }

    public function showLmsLogin(Request $request, Response $response)
    {
        return $response->redirect('/sia/auth/lms_student_login.php');
    }

    public function loginProcess(Request $request, Response $response)
    {
        $pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response->redirect("/sia/auth/../public/index.php");
    return;
}

$role = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';

// $pdo is provided by config/database.php

if ($role === 'student') {
    $student_id = trim((string)($_POST['student_id'] ?? ''));
    if (empty($student_id) || empty($password)) {
        echo "<script>alert('Please provide student ID or email and password.'); window.location.href='/sia/auth/lms_student_login.php';</script>";
        return;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE (student_number = :sid1 OR email = :sid2 OR ttu_email = :sid3) 
          AND is_active = 1 
        LIMIT 1
    ");
    $stmt->execute([
        'sid1' => $student_id,
        'sid2' => $student_id,
        'sid3' => $student_id
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $isPasswordValid = false;
    if ($user && password_verify($password, $user['password'])) {
        $isPasswordValid = true;
    }

    if ($user && $isPasswordValid) {
        if ($user['lms_status'] === 'suspended') {
            echo "<script>alert('Your LMS access has been suspended due to an academic or financial hold. Please contact the Registrar.'); window.location.href='/sia/auth/lms_student_login.php';</script>";
            return;
        }

        // Enforce strict enrollment gating: only officially finalized enrollees receive LMS access
        $enrStmt = $pdo->prepare("
            SELECT COUNT(*) FROM applications a
            WHERE a.user_id = :uid AND a.status = 'enrolled'
        ");
        $enrStmt->execute(['uid' => (int)$user['id']]);
        $enrolledCount = (int)$enrStmt->fetchColumn();

        // Check if user is an approved-but-not-enrolled applicant to provide clear guidance
        $apprStmt = $pdo->prepare("
            SELECT COUNT(*) FROM applications a
            WHERE a.user_id = :uid AND a.status = 'approved'
        ");
        $apprStmt->execute(['uid' => (int)$user['id']]);
        $approvedCount = (int)$apprStmt->fetchColumn();

        if ($enrolledCount > 0 && ($user['lms_status'] === 'active' || $user['role'] === 'student')) {
            // Success
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_first_name'] = $user['first_name'];
            $_SESSION['user_last_name'] = $user['last_name'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = 'student';
            $_SESSION['user_department'] = $user['department'] ?? 'None';
            $_SESSION['student_number'] = $user['student_number'];
            $_SESSION['lms_status'] = $user['lms_status'] ?? 'active';
            
            // Backward compatibility
            $_SESSION['lms_logged_in'] = true;
            $_SESSION['lms_user_id'] = (int)$user['id'];
            $_SESSION['lms_role'] = 'student';
            $_SESSION['lms_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['lms_email'] = $user['email'];
            $response->redirect("/sia/lms/student/dashboard.php");
            return;
        } elseif ($approvedCount > 0) {
            echo "<script>alert('Your application is approved, but official enrollment is not finalized. Please complete your cashier payment and registrar finalization to activate your LMS access.'); window.location.href='/sia/auth/lms_student_login.php';</script>";
            return;
        } else {
            echo "<script>alert('You are not officially enrolled yet. Only finalized enrollees can access the LMS. Please complete admissions registration.'); window.location.href='/sia/auth/lms_student_login.php';</script>";
            return;
        }
    } else {
        echo "<script>alert('Invalid Student ID / Email or Password.'); window.location.href='/sia/auth/lms_student_login.php';</script>";
        return;
    }
} elseif ($role === 'faculty') {
    $employee_id = trim((string)($_POST['employee_id'] ?? ''));
    if (empty($employee_id) || empty($password)) {
        echo "<script>alert('Please provide Employee ID / Email and password.'); window.location.href='/sia/auth/lms_faculty_login.php';</script>";
        return;
    }

    // Checking 'faculty', 'superadmin', or 'admin' roles with distinct parameter markers
    $stmt = $pdo->prepare("
        SELECT u.*, fp.academic_rank, fp.employment_type, fp.max_teaching_units 
        FROM users u 
        LEFT JOIN faculty_profiles fp ON fp.user_id = u.id 
        WHERE (u.employee_id = :eid1 OR u.student_number = :eid2 OR u.email = :eid3 OR u.ttu_email = :eid4) 
          AND (u.role = 'faculty' OR u.role IN ('superadmin', 'admin')) 
          AND u.is_active = 1 
        LIMIT 1
    ");
    $stmt->execute([
        'eid1' => $employee_id,
        'eid2' => $employee_id,
        'eid3' => $employee_id,
        'eid4' => $employee_id
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['lms_status'] === 'suspended') {
            echo "<script>alert('Your faculty LMS access is suspended. Please contact Academic Affairs.'); window.location.href='/sia/auth/lms_faculty_login.php';</script>";
            return;
        }

        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_first_name'] = $user['first_name'];
        $_SESSION['user_last_name'] = $user['last_name'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role']; // Retain admin/superadmin or faculty role
        $_SESSION['user_department'] = $user['department'] ?? 'None';
        $_SESSION['user_permissions'] = !empty($user['permissions']) ? json_decode($user['permissions'], true) : ['*'];
        $_SESSION['employee_id'] = $user['employee_id'] ?? ($user['student_number'] ?? 'EMP-' . $user['id']);
        $_SESSION['academic_rank'] = $user['academic_rank'] ?? ($user['role'] === 'superadmin' ? 'Super Administrator' : ($user['role'] === 'admin' ? 'Administrator' : 'Instructor I'));
        $_SESSION['lms_status'] = 'active';
        
        // Backward compatibility & LMS-specific session
        $_SESSION['lms_logged_in'] = true;
        $_SESSION['lms_user_id'] = (int)$user['id'];
        $_SESSION['lms_role'] = 'faculty'; // Grants faculty view access in LMS
        $_SESSION['lms_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['lms_email'] = $user['email'];

        $response->redirect("/sia/lms/faculty/dashboard.php");
        return;
    } else {
        echo "<script>alert('Invalid Employee ID / Email or Password.'); window.location.href='/sia/auth/lms_faculty_login.php';</script>";
        return;
    }
} elseif ($role === 'admin') {
    $employee_id = trim((string)($_POST['employee_id'] ?? ''));
    if (empty($employee_id) || empty($password)) {
        echo "<script>alert('Please provide Admin Email or ID and password.'); window.location.href='/sia/auth/lms_admin_login.php';</script>";
        return;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE (employee_id = :eid1 OR student_number = :eid2 OR email = :eid3 OR ttu_email = :eid4) 
          AND is_active = 1 
        LIMIT 1
    ");
    $stmt->execute([
        'eid1' => $employee_id,
        'eid2' => $employee_id,
        'eid3' => $employee_id,
        'eid4' => $employee_id
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Enforce: The Registrar account must NOT handle LMS Administration
        if (($user['department'] ?? '') === 'Registrar Office') {
            echo "<script>alert('Registrar accounts do not handle LMS Administration. LMS is governed by the LMS Administrator on the LMS side. Please log in via the University SIS portal.'); window.location.href='/sia/auth/lms_admin_login.php';</script>";
            return;
        }

        $perms = !empty($user['permissions']) ? json_decode($user['permissions'], true) : [];
        $isLmsAdmin = ($user['role'] === 'superadmin' || $user['role'] === 'lms_admin' || in_array('*', $perms, true) || in_array('lms.admin', $perms, true) || in_array('lms.manage', $perms, true));

        if (!$isLmsAdmin) {
            echo "<script>alert('Access Denied: This account does not possess LMS Administrative privileges.'); window.location.href='/sia/auth/lms_admin_login.php';</script>";
            return;
        }

        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_first_name'] = $user['first_name'];
        $_SESSION['user_last_name'] = $user['last_name'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_department'] = $user['department'] ?? 'LMS Administration';
        $_SESSION['user_permissions'] = $perms ?: ['*'];
        $_SESSION['lms_status'] = 'active';

        // LMS-specific session
        $_SESSION['lms_logged_in'] = true;
        $_SESSION['lms_user_id'] = (int)$user['id'];
        $_SESSION['lms_role'] = 'admin';
        $_SESSION['lms_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['lms_email'] = $user['email'];

        $response->redirect("/sia/lms/admin/dashboard");
        return;
    } else {
        echo "<script>alert('Invalid LMS Administrator credentials.'); window.location.href='/sia/auth/lms_admin_login.php';</script>";
        return;
    }
}

// Fallback
$response->redirect("/sia/auth/lms_student_login.php");
return;
    }

    public function logoutStudent(Request $request, Response $response)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
        }
        $response->redirect('/sia/auth/lms_student_login.php');
    }

    public function logoutFaculty(Request $request, Response $response)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
        }
        $response->redirect('/sia/auth/lms_faculty_login.php');
    }

    public function logoutAdmin(Request $request, Response $response)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
        }
        $response->redirect('/sia/auth/lms_admin_login.php');
    }
}



