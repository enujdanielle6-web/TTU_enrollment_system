<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$firstName = trim((string) ($_POST['first_name'] ?? ''));
$lastName = trim((string) ($_POST['last_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');
$csrfToken = $_POST['csrf_token'] ?? '';

$errors = [];

verifyCsrfToken();

if ($firstName === '') {
    $errors[] = 'First name is required.';
}

if ($lastName === '') {
    $errors[] = 'Last name is required.';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

isPasswordStrong($password, $errors);

if ($password !== $confirmPassword) {
    $errors[] = 'Password and confirm password do not match.';
}

if (!empty($errors)) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_old'] = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
    ];

    header('Location: register.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $emailCheck->execute(['email' => $email]);

    if ($emailCheck->fetch()) {
        $_SESSION['register_errors'] = ['Email address is already registered.'];
        $_SESSION['register_old'] = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ];

        header('Location: register.php');
        exit;
    }

    $createUser = $pdo->prepare(
        'INSERT INTO users (first_name, last_name, email, password, role)
         VALUES (:first_name, :last_name, :email, :password, :role)'
    );

    $createUser->execute([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'applicant',
    ]);

    $newUserId = (int) $pdo->lastInsertId();

    $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
    $logStmt->execute([
        'user_id' => $newUserId,
        'icon' => 'bi-shield-check',
        'title' => 'Portal Access Granted',
        'description' => 'Your account was successfully registered inside the Online Enrollment System.',
    ]);

    header('Location: login.php');
    exit;
} catch (PDOException $exception) {
    error_log('Registration failed: ' . $exception->getMessage());
    $_SESSION['register_errors'] = ['Registration failed. Please try again.'];
    $_SESSION['register_old'] = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
    ];

    header('Location: register.php');
    exit;
}
