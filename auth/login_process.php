<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// 1. Verify CSRF Token
verifyCsrfToken();

$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
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
    header('Location: login.php');
    exit;
}

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

try {
    // Automatically soft-delete applicant accounts older than 3 days with no enrollment form
    $pdo->exec(
        "UPDATE users 
         SET is_active = 0 
         WHERE role = 'applicant' 
           AND is_active = 1 
           AND created_at < NOW() - INTERVAL 3 DAY 
           AND id NOT IN (SELECT user_id FROM applications)"
    );

    $statement = $pdo->prepare(
        'SELECT id, first_name, last_name, email, password, role, is_active, department, permissions
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        // Record failed attempt
        $logAttemptStmt = $pdo->prepare('INSERT INTO login_attempts (ip_address, email) VALUES (:ip, :email)');
        $logAttemptStmt->execute(['ip' => $ipAddress, 'email' => $email]);

        $_SESSION['login_errors'] = ['Invalid email or password.'];
        $_SESSION['login_old'] = ['email' => $email];

        header('Location: login.php');
        exit;
    }

    if ((int)$user['is_active'] !== 1) {
        $_SESSION['login_errors'] = ['Your account has been deactivated. Please contact the administrator.'];
        $_SESSION['login_old'] = ['email' => $email];
        header('Location: login.php');
        exit;
    }

    // 3. Clear failed attempts on successful login
    $clearAttemptsStmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = :ip OR email = :email');
    $clearAttemptsStmt->execute(['ip' => $ipAddress, 'email' => $email]);

    // Prune stale attempts older than 24 hours
    $pdo->exec('DELETE FROM login_attempts WHERE attempt_time < NOW() - INTERVAL 24 HOUR');

    // Prevent Session Fixation
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

    // Reset session validation parameters
    $_SESSION['user_ip'] = $ipAddress;
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['created_time'] = time();

    // Update last_login
    $updateLoginStmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
    $updateLoginStmt->execute(['id' => $user['id']]);

    // Admin Roles Activity Logging
    $adminRoles = ['superadmin', 'admin', 'admissions', 'scholarship', 'cashier'];
    if (in_array($user['role'], $adminRoles, true)) {
        $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, title, description, icon) VALUES (:uid, "Logged In", "Administrator logged into the system.", "bi-box-arrow-in-right")');
        $logStmt->execute(['uid' => $user['id']]);
    }

    // 4. Route to appropriate dashboard
    if (in_array($user['role'], $adminRoles, true)) {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../applicant/dashboard.php');
    }
    exit;

} catch (PDOException $exception) {
    error_log('Login failed: ' . $exception->getMessage());
    $_SESSION['login_errors'] = ['Login failed due to a database error. Please try again.'];
    $_SESSION['login_old'] = ['email' => $email];

    header('Location: login.php');
    exit;
}
