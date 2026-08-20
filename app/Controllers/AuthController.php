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
            $adminRoles = ['superadmin', 'admin', 'admissions', 'scholarship', 'cashier', 'clinic', 'scheduler'];
            
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

        // Check if applicant email is verified
        if ($user['role'] === 'applicant' && (int)($user['email_verified'] ?? 1) === 0) {
            $newCode = sprintf('%06d', random_int(100000, 999999));
            $pdo = \App\Core\Database::getConnection();
            $upd = $pdo->prepare("UPDATE users SET verification_code = :code, verification_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = :id");
            $upd->execute([
                'code' => $newCode,
                'id' => (int)$user['id']
            ]);

            $mailError = null;
            $emailSent = sendVerificationCodeEmail($user['email'], $user['first_name'], $newCode, $mailError);

            $_SESSION['pending_verification_user_id'] = (int)$user['id'];
            $_SESSION['pending_verification_email'] = $user['email'];
            $_SESSION['pending_verification_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            if ($emailSent) {
                $_SESSION['verification_success'] = "Please verify your email address before logging in. A 6-digit verification code has been successfully sent to {$user['email']}.";
            } else {
                $_SESSION['verification_warning'] = "Please verify your email address. We attempted to send a new code to {$user['email']}, but encountered a delivery issue. You may click 'Resend Code'.";
            }

            $response->redirect('/sia/auth/verify_email.php');
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

        $adminRoles = ['superadmin', 'admin', 'admissions', 'scholarship', 'cashier', 'clinic', 'scheduler'];
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
        $firstName = trim((string) $request->input('first_name', ''));
        $lastName = trim((string) $request->input('last_name', ''));
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $confirmPassword = (string) $request->input('confirm_password', '');

        $errors = [];

        if (empty($firstName)) $errors[] = 'First name is required.';
        if (empty($lastName)) $errors[] = 'Last name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        
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
        $code = sprintf('%06d', random_int(100000, 999999));

        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare('
            INSERT INTO users (first_name, last_name, email, password, role, is_active, email_verified, verification_code, verification_expires_at) 
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
        ');
        $stmt->execute([$firstName, $lastName, $email, $hashedPassword, 'applicant', 1, $code]);
        $userId = (int)$pdo->lastInsertId();

        // Send 6-digit OTP verification email with validation
        $mailError = null;
        $emailSent = sendVerificationCodeEmail($email, $firstName, $code, $mailError);

        $_SESSION['pending_verification_user_id'] = $userId;
        $_SESSION['pending_verification_email'] = $email;
        $_SESSION['pending_verification_name'] = "$firstName $lastName";
        
        if ($emailSent) {
            $_SESSION['verification_success'] = "A 6-digit verification code has been successfully sent to {$email}. Please check your inbox or spam folder.";
        } else {
            $_SESSION['verification_warning'] = "Account registered, but there was a temporary delivery issue sending the verification email to {$email}. Please click 'Resend Code' if not received.";
            error_log("Email delivery warning for user ID {$userId}: {$mailError}");
        }

        $response->redirect('/sia/auth/verify_email.php');
    }

    public function showVerifyEmail(Request $request, Response $response)
    {
        if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
            $pdo = \App\Core\Database::getConnection();
            $stmt = $pdo->prepare("SELECT email_verified FROM users WHERE id = ?");
            $stmt->execute([(int)$_SESSION['user_id']]);
            $isVerified = (int)$stmt->fetchColumn();
            if ($isVerified === 1) {
                $response->redirect('/sia/applicant/dashboard.php');
                return;
            }
        }

        $userId = $_SESSION['pending_verification_user_id'] ?? ($_SESSION['user_id'] ?? null);
        $email = $_SESSION['pending_verification_email'] ?? ($_SESSION['user_email'] ?? '');

        if (!$userId) {
            $response->redirect('/sia/auth/register.php');
            return;
        }

        $errors = $_SESSION['verify_errors'] ?? [];
        $success = $_SESSION['verification_success'] ?? null;
        $warning = $_SESSION['verification_warning'] ?? null;
        unset($_SESSION['verify_errors'], $_SESSION['verification_success'], $_SESSION['verification_warning']);

        return $this->render('auth/verify_email', [
            'email' => $email,
            'errors' => $errors,
            'success' => $success,
            'warning' => $warning
        ]);
    }

    public function processVerifyEmail(Request $request, Response $response)
    {
        $userId = (int)($_SESSION['pending_verification_user_id'] ?? ($_SESSION['user_id'] ?? 0));
        if ($userId <= 0) {
            $response->redirect('/sia/auth/register.php');
            return;
        }

        $code = trim((string)$request->input('code', ''));
        if (empty($code)) {
            $d1 = (string)$request->input('digit_1', '');
            $d2 = (string)$request->input('digit_2', '');
            $d3 = (string)$request->input('digit_3', '');
            $d4 = (string)$request->input('digit_4', '');
            $d5 = (string)$request->input('digit_5', '');
            $d6 = (string)$request->input('digit_6', '');
            $code = $d1 . $d2 . $d3 . $d4 . $d5 . $d6;
        }
        $code = preg_replace('/\D/', '', $code);

        if (strlen($code) !== 6) {
            $_SESSION['verify_errors'] = ['Please enter the complete 6-digit verification code.'];
            $response->redirect('/sia/auth/verify_email.php');
            return;
        }

        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['register_errors'] = ['User record not found. Please register again.'];
            $response->redirect('/sia/auth/register.php');
            return;
        }

        $now = date('Y-m-d H:i:s');
        $storedCode = (string)($user['verification_code'] ?? '');
        $expiresAt = (string)($user['verification_expires_at'] ?? '');

        if ($storedCode !== $code || ($expiresAt !== '' && $expiresAt < $now)) {
            $_SESSION['verify_errors'] = ['The verification code is incorrect or has expired. Please try again or click Resend Code.'];
            $response->redirect('/sia/auth/verify_email.php');
            return;
        }

        // Mark as verified
        $upd = $pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_expires_at = NULL WHERE id = :id");
        $upd->execute(['id' => $userId]);

        // Automatically authenticate user
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_first_name'] = $user['first_name'];
        $_SESSION['user_last_name'] = $user['last_name'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = 'applicant';
        $_SESSION['user_department'] = $user['department'] ?? 'None';
        $_SESSION['user_permissions'] = [];
        $_SESSION['logged_in'] = true;
        
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['created_time'] = time();

        unset(
            $_SESSION['pending_verification_user_id'],
            $_SESSION['pending_verification_email'],
            $_SESSION['pending_verification_name']
        );

        User::logActivity($userId, "Email Verified", "Applicant successfully verified email address.", "bi-patch-check-fill");

        $response->redirect('/sia/applicant/dashboard.php');
    }

    public function resendVerification(Request $request, Response $response)
    {
        $userId = (int)($_SESSION['pending_verification_user_id'] ?? ($_SESSION['user_id'] ?? 0));
        if ($userId <= 0) {
            $response->redirect('/sia/auth/register.php');
            return;
        }

        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            $response->redirect('/sia/auth/register.php');
            return;
        }

        $newCode = sprintf('%06d', random_int(100000, 999999));
        $upd = $pdo->prepare("UPDATE users SET verification_code = :code, verification_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = :id");
        $upd->execute([
            'code' => $newCode,
            'id' => $userId
        ]);

        $mailError = null;
        $emailSent = sendVerificationCodeEmail($user['email'], $user['first_name'], $newCode, $mailError);

        if ($emailSent) {
            $_SESSION['verification_success'] = "A new 6-digit verification code has been successfully sent to {$user['email']}.";
        } else {
            $_SESSION['verify_errors'] = ["Failed to send email to {$user['email']}. " . ($mailError ?: 'Please try again in a few moments.')];
        }

        $response->redirect('/sia/auth/verify_email.php');
    }

    public function logout(Request $request, Response $response)
    {
        $redirectUrl = '/sia/auth/login.php';

        if (session_status() === PHP_SESSION_ACTIVE) {
            $userRole = $_SESSION['user_role'] ?? ($_SESSION['lms_role'] ?? '');
            $fromParam = $request->input('from', '');
            $isLms = !empty($_SESSION['lms_logged_in']) || $userRole === 'student' || $userRole === 'faculty' || strpos((string)$fromParam, 'lms') !== false;

            if ($userRole === 'faculty' || $fromParam === 'lms_faculty') {
                $redirectUrl = '/sia/auth/lms_faculty_login.php';
            } elseif ($isLms || $userRole === 'student' || $fromParam === 'lms_student') {
                $redirectUrl = '/sia/auth/lms_student_login.php';
            }

            if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_role'])) {
                $adminRoles = ['superadmin', 'admin', 'admissions', 'scholarship', 'cashier', 'clinic', 'scheduler'];
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

        $response->redirect($redirectUrl);
    }
}



