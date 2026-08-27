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
        $success = $_SESSION['login_success'] ?? null;
        unset($_SESSION['login_errors'], $_SESSION['login_old'], $_SESSION['login_success']);

        return $this->render('auth/login', [
            'errors' => $errors,
            'old' => $old,
            'success' => $success
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
            $upd = $pdo->prepare("UPDATE users SET verification_code = :code, verification_code_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = :id");
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

        // Ensure email isn't already taken
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

        // Store registration details in session - account is NOT created in DB until OTP is verified
        $_SESSION['pending_registration'] = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => 'applicant',
            'code' => $code,
            'expires_at' => time() + (15 * 60)
        ];

        $_SESSION['pending_verification_email'] = $email;
        $_SESSION['pending_verification_name'] = "$firstName $lastName";
        unset($_SESSION['pending_verification_user_id']);

        // Send 6-digit OTP verification email with validation
        $mailError = null;
        $emailSent = sendVerificationCodeEmail($email, $firstName, $code, $mailError);

        if ($emailSent) {
            $_SESSION['verification_success'] = "A 6-digit verification code has been successfully sent to {$email}. Please check your inbox or spam folder.";
        } else {
            $_SESSION['verification_warning'] = "We attempted to send a verification code to {$email}, but encountered a temporary delivery issue. Please click 'Resend Code' if not received.";
            error_log("Email delivery warning for {$email}: {$mailError}");
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

        $email = $_SESSION['pending_registration']['email'] ?? ($_SESSION['pending_verification_email'] ?? ($_SESSION['user_email'] ?? ''));

        if (empty($email)) {
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

        // Scenario 1: New registration pending OTP verification (Account created only NOW)
        if (!empty($_SESSION['pending_registration'])) {
            $pending = $_SESSION['pending_registration'];
            $storedCode = (string)($pending['code'] ?? '');
            $expiresAt = (int)($pending['expires_at'] ?? 0);

            if ($storedCode !== $code || time() > $expiresAt) {
                $_SESSION['verify_errors'] = ['The verification code is incorrect or has expired. Please try again or click Resend Code.'];
                $response->redirect('/sia/auth/verify_email.php');
                return;
            }

            // Check if email was taken in the interim
            if (User::findByEmail($pending['email'])) {
                unset($_SESSION['pending_registration'], $_SESSION['pending_verification_email'], $_SESSION['pending_verification_name']);
                $_SESSION['register_errors'] = ['Email is already registered. Please log in.'];
                $response->redirect('/sia/auth/login.php');
                return;
            }

            // Create account in DB now that OTP is valid
            $pdo = \App\Core\Database::getConnection();
            $stmt = $pdo->prepare('
                INSERT INTO users (first_name, last_name, email, password, role, is_active, email_verified, verification_code, verification_code_expires_at, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, 1, 1, NULL, NULL, NOW(), NOW())
            ');
            $stmt->execute([
                $pending['first_name'],
                $pending['last_name'],
                $pending['email'],
                $pending['password'],
                $pending['role'] ?? 'applicant'
            ]);
            $userId = (int)$pdo->lastInsertId();

            // Automatically authenticate newly created user
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_first_name'] = $pending['first_name'];
            $_SESSION['user_last_name'] = $pending['last_name'];
            $_SESSION['user_name'] = $pending['first_name'] . ' ' . $pending['last_name'];
            $_SESSION['user_email'] = $pending['email'];
            $_SESSION['user_role'] = $pending['role'] ?? 'applicant';
            $_SESSION['user_department'] = 'None';
            $_SESSION['user_permissions'] = [];
            $_SESSION['logged_in'] = true;
            
            $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['created_time'] = time();

            unset(
                $_SESSION['pending_registration'],
                $_SESSION['pending_verification_user_id'],
                $_SESSION['pending_verification_email'],
                $_SESSION['pending_verification_name']
            );

            User::logActivity($userId, "Account Created & Verified", "Applicant completed email verification and account was created.", "bi-patch-check-fill");

            $response->redirect('/sia/applicant/dashboard.php');
            return;
        }

        // Scenario 2: Legacy unverified user logging in
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
            $_SESSION['register_errors'] = ['User record not found. Please register again.'];
            $response->redirect('/sia/auth/register.php');
            return;
        }

        $now = date('Y-m-d H:i:s');
        $storedCode = (string)($user['verification_code'] ?? '');
        $expiresAt = (string)($user['verification_code_expires_at'] ?? '');

        if ($storedCode !== $code || ($expiresAt !== '' && $expiresAt < $now)) {
            $_SESSION['verify_errors'] = ['The verification code is incorrect or has expired. Please try again or click Resend Code.'];
            $response->redirect('/sia/auth/verify_email.php');
            return;
        }

        // Mark as verified
        $upd = $pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires_at = NULL WHERE id = :id");
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
        // Scenario 1: Pending registration
        if (!empty($_SESSION['pending_registration'])) {
            $newCode = sprintf('%06d', random_int(100000, 999999));
            $_SESSION['pending_registration']['code'] = $newCode;
            $_SESSION['pending_registration']['expires_at'] = time() + (15 * 60);

            $email = $_SESSION['pending_registration']['email'];
            $firstName = $_SESSION['pending_registration']['first_name'];

            $mailError = null;
            $emailSent = sendVerificationCodeEmail($email, $firstName, $newCode, $mailError);

            if ($emailSent) {
                $_SESSION['verification_success'] = "A new 6-digit verification code has been successfully sent to {$email}.";
            } else {
                $_SESSION['verify_errors'] = ["Failed to send email to {$email}. " . ($mailError ?: 'Please try again in a few moments.')];
            }

            $response->redirect('/sia/auth/verify_email.php');
            return;
        }

        // Scenario 2: Legacy unverified user
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
        $upd = $pdo->prepare("UPDATE users SET verification_code = :code, verification_code_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = :id");
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

    public function showForgotPassword(Request $request, Response $response)
    {
        if (!empty($_SESSION['logged_in'])) {
            $userRole = $_SESSION['user_role'] ?? '';
            $adminRoles = ['superadmin', 'admin', 'admissions', 'scholarship', 'cashier', 'clinic', 'scheduler'];
            if (in_array($userRole, $adminRoles, true)) {
                $response->redirect('/sia/admin/dashboard.php');
            } elseif ($userRole === 'faculty') {
                $response->redirect('/sia/lms/faculty/dashboard.php');
            } elseif ($userRole === 'student') {
                $response->redirect('/sia/lms/student/dashboard.php');
            } else {
                $response->redirect('/sia/applicant/dashboard.php');
            }
            return;
        }

        $portal = $request->query('portal', 'applicant');
        if (!in_array($portal, ['faculty', 'student', 'applicant'], true)) {
            $portal = 'applicant';
        }

        $errors = $_SESSION['forgot_errors'] ?? [];
        $old = $_SESSION['forgot_old'] ?? [];
        $warning = $_SESSION['forgot_warning'] ?? null;
        unset($_SESSION['forgot_errors'], $_SESSION['forgot_old'], $_SESSION['forgot_warning']);

        return $this->render('auth/forgot_password', [
            'portal' => $portal,
            'errors' => $errors,
            'old' => $old,
            'warning' => $warning
        ]);
    }

    public function processForgotPassword(Request $request, Response $response)
    {
        $inputPortal = $request->input('portal', 'applicant');
        if ($inputPortal === 'faculty') {
            $portal = 'faculty';
        } elseif ($inputPortal === 'student') {
            $portal = 'student';
        } else {
            $portal = 'applicant';
        }

        $errors = [];
        $pdo = \App\Core\Database::getConnection();

        if ($portal === 'faculty') {
            $identifier = trim((string)$request->input('identifier', ''));
            if ($identifier === '') {
                $errors[] = 'Please enter your Employee ID or institutional TTU email.';
            }

            if (!empty($errors)) {
                $_SESSION['forgot_errors'] = $errors;
                $_SESSION['forgot_old'] = ['identifier' => $identifier];
                $response->redirect('/sia/auth/forgot_password.php?portal=faculty');
                return;
            }

            $stmt = $pdo->prepare("
                SELECT * FROM users 
                WHERE (student_number = :student_number OR email = :email) 
                  AND role = 'faculty' 
                  AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([
                'student_number' => $identifier,
                'email' => $identifier
            ]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                $_SESSION['forgot_errors'] = ["No active faculty account found matching '{$identifier}'. Please check your Employee ID or institutional email."];
                $_SESSION['forgot_old'] = ['identifier' => $identifier];
                $response->redirect('/sia/auth/forgot_password.php?portal=faculty');
                return;
            }
        } elseif ($portal === 'student') {
            $identifier = trim((string)$request->input('identifier', ''));
            if ($identifier === '') {
                $errors[] = 'Please enter your Student ID or institutional TTU email.';
            }

            if (!empty($errors)) {
                $_SESSION['forgot_errors'] = $errors;
                $_SESSION['forgot_old'] = ['identifier' => $identifier];
                $response->redirect('/sia/auth/forgot_password.php?portal=student');
                return;
            }

            $stmt = $pdo->prepare("
                SELECT * FROM users 
                WHERE (student_number = :student_number OR email = :email) 
                  AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([
                'student_number' => $identifier,
                'email' => $identifier
            ]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                $_SESSION['forgot_errors'] = ["No active student account found matching '{$identifier}'. Please check your Student ID or institutional email."];
                $_SESSION['forgot_old'] = ['identifier' => $identifier];
                $response->redirect('/sia/auth/forgot_password.php?portal=student');
                return;
            }
        } else {
            $email = trim((string)$request->input('email', ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email address is required.';
            }

            if (!empty($errors)) {
                $_SESSION['forgot_errors'] = $errors;
                $_SESSION['forgot_old'] = ['email' => $email];
                $response->redirect('/sia/auth/forgot_password.php?portal=applicant');
                return;
            }

            $stmt = $pdo->prepare("
                SELECT * FROM users 
                WHERE email = :email 
                  AND role = 'applicant' 
                  AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                $_SESSION['forgot_errors'] = ["No active applicant account found with email '{$email}'."];
                $_SESSION['forgot_old'] = ['email' => $email];
                $response->redirect('/sia/auth/forgot_password.php?portal=applicant');
                return;
            }
        }

        // Generate 6-digit OTP
        $code = sprintf('%06d', random_int(100000, 999999));
        $upd = $pdo->prepare("
            UPDATE users 
            SET reset_password_code = :code, 
                reset_password_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE) 
            WHERE id = :id
        ");
        $upd->execute([
            'code' => $code,
            'id' => (int)$user['id']
        ]);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $resetUrl = "{$scheme}://{$host}/sia/auth/reset_password.php?portal={$portal}&email=" . urlencode($user['email']) . "&code={$code}";

        $mailError = null;
        $recipientName = trim($user['first_name'] . ' ' . $user['last_name']);
        $emailSent = sendPasswordResetOtpEmail($user['email'], $recipientName, $code, $portal, $resetUrl, $mailError);

        $_SESSION['reset_user_id'] = (int)$user['id'];
        $_SESSION['reset_email'] = $user['email'];
        $_SESSION['reset_name'] = $recipientName;
        $_SESSION['reset_portal'] = $portal;

        if ($emailSent) {
            if ($portal === 'faculty' || $portal === 'student') {
                $_SESSION['reset_success'] = "A 6-digit password reset code has been sent to your institutional email: {$user['email']}.";
            } else {
                $_SESSION['reset_success'] = "A 6-digit password reset code has been sent to {$user['email']}.";
            }
        } else {
            $_SESSION['reset_warning'] = "We generated a password reset code for {$user['email']}, but encountered an email delivery issue. You may click 'Resend Code'.";
            error_log("Password reset email delivery issue for {$user['email']}: {$mailError}");
        }

        $response->redirect("/sia/auth/reset_password.php?portal={$portal}&email=" . urlencode($user['email']));
    }

    public function showResetPassword(Request $request, Response $response)
    {
        $portal = $request->query('portal') ?: ($_SESSION['reset_portal'] ?? 'applicant');
        if (!in_array($portal, ['faculty', 'student', 'applicant'], true)) {
            $portal = 'applicant';
        }

        $email = trim((string)($request->query('email') ?: ($_SESSION['reset_email'] ?? '')));
        $code = trim((string)$request->query('code', ''));

        if ($email === '' && empty($_SESSION['reset_user_id'])) {
            $response->redirect("/sia/auth/forgot_password.php?portal={$portal}");
            return;
        }

        $errors = $_SESSION['reset_errors'] ?? [];
        $success = $_SESSION['reset_success'] ?? null;
        $warning = $_SESSION['reset_warning'] ?? null;
        unset($_SESSION['reset_errors'], $_SESSION['reset_success'], $_SESSION['reset_warning']);

        return $this->render('auth/reset_password', [
            'portal' => $portal,
            'email' => $email,
            'code' => $code,
            'errors' => $errors,
            'success' => $success,
            'warning' => $warning
        ]);
    }

    public function processResetPassword(Request $request, Response $response)
    {
        $inputPortal = $request->input('portal', 'applicant');
        if ($inputPortal === 'faculty') {
            $portal = 'faculty';
        } elseif ($inputPortal === 'student') {
            $portal = 'student';
        } else {
            $portal = 'applicant';
        }

        $email = trim((string)$request->input('email', ''));
        $password = (string)$request->input('password', '');
        $confirmPassword = (string)$request->input('confirm_password', '');

        // Support full code or individual digit inputs
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

        $errors = [];

        if (strlen($code) !== 6) {
            $errors[] = 'Please enter the complete 6-digit verification code.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }

        if (!empty($errors)) {
            $_SESSION['reset_errors'] = $errors;
            $response->redirect("/sia/auth/reset_password.php?portal={$portal}&email=" . urlencode($email) . "&code=" . urlencode($code));
            return;
        }

        $pdo = \App\Core\Database::getConnection();
        if ($portal === 'faculty') {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = 'faculty' AND is_active = 1 LIMIT 1");
        } elseif ($portal === 'student') {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = 'applicant' AND is_active = 1 LIMIT 1");
        }
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['reset_errors'] = ['Account not found or inactive. Please request a new password reset.'];
            $response->redirect("/sia/auth/forgot_password.php?portal={$portal}");
            return;
        }

        $storedCode = (string)($user['reset_password_code'] ?? '');
        $expiresAt = (string)($user['reset_password_expires_at'] ?? '');
        $now = date('Y-m-d H:i:s');

        if ($storedCode === '' || $storedCode !== $code || ($expiresAt !== '' && $expiresAt < $now)) {
            $_SESSION['reset_errors'] = ['The 6-digit verification code is invalid or has expired. Please try again or click Resend Code.'];
            $response->redirect("/sia/auth/reset_password.php?portal={$portal}&email=" . urlencode($email));
            return;
        }

        // Hash and update password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("
            UPDATE users 
            SET password = :pwd, 
                reset_password_code = NULL, 
                reset_password_expires_at = NULL,
                email_verified = 1 
            WHERE id = :id
        ");
        $upd->execute([
            'pwd' => $passwordHash,
            'id' => (int)$user['id']
        ]);

        unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_name'], $_SESSION['reset_portal']);

        $_SESSION['login_success'] = 'Your password has been successfully reset! You can now log in with your new password.';

        if ($portal === 'faculty') {
            $response->redirect('/sia/auth/lms_faculty_login.php');
        } elseif ($portal === 'student') {
            $response->redirect('/sia/auth/lms_student_login.php');
        } else {
            $response->redirect('/sia/auth/login.php');
        }
    }

    public function resendResetOtp(Request $request, Response $response)
    {
        $inputPortal = $request->input('portal', 'applicant');
        if ($inputPortal === 'faculty') {
            $portal = 'faculty';
        } elseif ($inputPortal === 'student') {
            $portal = 'student';
        } else {
            $portal = 'applicant';
        }

        $email = trim((string)($request->input('email') ?: ($_SESSION['reset_email'] ?? '')));

        if ($email === '') {
            $response->redirect("/sia/auth/forgot_password.php?portal={$portal}");
            return;
        }

        $pdo = \App\Core\Database::getConnection();
        if ($portal === 'faculty') {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = 'faculty' AND is_active = 1 LIMIT 1");
        } elseif ($portal === 'student') {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = 'applicant' AND is_active = 1 LIMIT 1");
        }
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['reset_errors'] = ['User record not found. Please initiate password recovery again.'];
            $response->redirect("/sia/auth/forgot_password.php?portal={$portal}");
            return;
        }

        $newCode = sprintf('%06d', random_int(100000, 999999));
        $upd = $pdo->prepare("
            UPDATE users 
            SET reset_password_code = :code, 
                reset_password_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE) 
            WHERE id = :id
        ");
        $upd->execute([
            'code' => $newCode,
            'id' => (int)$user['id']
        ]);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $resetUrl = "{$scheme}://{$host}/sia/auth/reset_password.php?portal={$portal}&email=" . urlencode($user['email']) . "&code={$newCode}";

        $mailError = null;
        $recipientName = trim($user['first_name'] . ' ' . $user['last_name']);
        $emailSent = sendPasswordResetOtpEmail($user['email'], $recipientName, $newCode, $portal, $resetUrl, $mailError);

        if ($emailSent) {
            $_SESSION['reset_success'] = "A fresh 6-digit reset code has been sent to {$user['email']}.";
        } else {
            $_SESSION['reset_errors'] = ["Failed to send email to {$user['email']}. " . ($mailError ?: 'Please try again in a few moments.')];
        }

        $response->redirect("/sia/auth/reset_password.php?portal={$portal}&email=" . urlencode($user['email']));
    }
}



