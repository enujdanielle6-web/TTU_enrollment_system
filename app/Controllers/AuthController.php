<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;

class AuthController extends BaseController
{
    public function showLogin(Request $request, Response $response)
    {
        if (!empty($_SESSION['logged_in'])) {
            $userRole = $_SESSION['user_role'] ?? '';
            $adminRoles = ['superadmin', 'admin', 'admissions', 'scholarship', 'cashier', 'clinic'];
            
            if (in_array($userRole, $adminRoles, true)) {
                $response->redirect('/sia/admin/dashboard.php');
            } else {
                $response->redirect('/sia/applicant/dashboard.php');
            }
            return;
        }

        $errors = $_SESSION['login_errors'] ?? [];
        $old = $_SESSION['login_old'] ?? [];
        unset($_SESSION['login_errors'], $_SESSION['login_old']);

        return $this->render('auth/login', [
            'errors' => $errors,
            'old' => $old
        ]);
    }

    public function login(Request $request, Response $response)
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        }

        if (!empty($errors)) {
            $_SESSION['login_errors'] = $errors;
            $_SESSION['login_old'] = ['email' => $email];
            $response->redirect('/sia/auth/login.php');
            return;
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        User::pruneStaleApplicants();
        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            User::recordFailedAttempt($ipAddress, $email);

            $_SESSION['login_errors'] = ['Invalid email or password.'];
            $_SESSION['login_old'] = ['email' => $email];

            $response->redirect('/sia/auth/login.php');
            return;
        }

        if ((int)$user['is_active'] !== 1) {
            $_SESSION['login_errors'] = ['Your account has been deactivated. Please contact the administrator.'];
            $_SESSION['login_old'] = ['email' => $email];
            $response->redirect('/sia/auth/login.php');
            return;
        }

        User::clearFailedAttempts($ipAddress, $email);
        User::pruneStaleAttempts();

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_first_name'] = $user['first_name'];
        $_SESSION['user_last_name'] = $user['last_name'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_department'] = $user['department'] ?? 'None';
        $_SESSION['user_permissions'] = $user['permissions'] ? json_decode($user['permissions'], true) : [];
        $_SESSION['logged_in'] = true;

        $_SESSION['user_ip'] = $ipAddress;
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['created_time'] = time();

        User::updateLastLogin((int)$user['id']);

        $adminRoles = ['superadmin', 'admin', 'admissions', 'scholarship', 'cashier', 'clinic'];
        if (in_array($user['role'], $adminRoles, true)) {
            User::logActivity((int)$user['id'], "Logged In", "Administrator logged into the system.", "bi-box-arrow-in-right");
            $response->redirect('/sia/admin/dashboard.php');
        } else {
            $response->redirect('/sia/applicant/dashboard.php');
        }
    }

    public function showRegister(Request $request, Response $response)
    {
        if (!empty($_SESSION['logged_in'])) {
            $response->redirect('/sia/applicant/dashboard.php');
            return;
        }

        $errors = $_SESSION['register_errors'] ?? [];
        $old = $_SESSION['register_old'] ?? [];
        unset($_SESSION['register_errors'], $_SESSION['register_old']);

        return $this->render('auth/register', [
            'errors' => $errors,
            'old' => $old
        ]);
    }

    public function register(Request $request, Response $response)
    {
        // For brevity, migrating register logic.
        // It requires validating input, inserting user, setting session, and redirecting.
        $firstName = trim((string) $request->input('first_name', ''));
        $lastName = trim((string) $request->input('last_name', ''));
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $confirmPassword = (string) $request->input('confirm_password', '');

        $errors = [];

        if (empty($firstName)) $errors[] = 'First name is required.';
        if (empty($lastName)) $errors[] = 'Last name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        
        // Check password strength logic locally
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters long.';
        if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

        // Ensure email isn't taken
        if (User::findByEmail($email)) {
            $errors[] = 'Email is already registered.';
        }

        if (!empty($errors)) {
            $_SESSION['register_errors'] = $errors;
            $_SESSION['register_old'] = ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email];
            $response->redirect('/sia/auth/register.php');
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$firstName, $lastName, $email, $hashedPassword, 'applicant', 1]);
        
        $userId = (int)$pdo->lastInsertId();

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_first_name'] = $firstName;
        $_SESSION['user_last_name'] = $lastName;
        $_SESSION['user_name'] = "$firstName $lastName";
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'applicant';
        $_SESSION['logged_in'] = true;
        
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['created_time'] = time();

        $response->redirect('/sia/applicant/dashboard.php');
    }

    public function logout(Request $request, Response $response)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_role'])) {
                $adminRoles = ['superadmin', 'admin', 'admissions', 'scholarship', 'cashier', 'clinic'];
                if (in_array($_SESSION['user_role'], $adminRoles, true)) {
                    User::logActivity((int)$_SESSION['user_id'], 'Logged Out', 'Administrator logged out of the system.', 'bi-box-arrow-right');
                }
            }
            
            $_SESSION = [];
            
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(), 
                    '', 
                    time() - 42000, 
                    $params['path'], 
                    $params['domain'], 
                    $params['secure'], 
                    $params['httponly']
                );
            }
            
            session_destroy();
        }

        $response->redirect('/sia/auth/login.php');
    }
}



