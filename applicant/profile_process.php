<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';
$errors = [];

verifyCsrfToken();

try {
    if ($action === 'update_account') {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($firstName === '') {
            $errors[] = 'First name is required.';
        }

        if ($lastName === '') {
            $errors[] = 'Last name is required.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        if (empty($errors)) {
            // Check for duplicate emails
            $emailStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
            $emailStmt->execute(['email' => $email, 'id' => $userId]);
            if ($emailStmt->fetch()) {
                $errors[] = 'The email address is already in use by another account.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['profile_errors'] = $errors;
            $_SESSION['profile_old'] = ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email];
            header('Location: profile.php');
            exit;
        }

        // Perform update
        $updateStmt = $pdo->prepare('UPDATE users SET first_name = :fname, last_name = :lname, email = :email WHERE id = :id');
        $updateStmt->execute(['fname' => $firstName, 'lname' => $lastName, 'email' => $email, 'id' => $userId]);

        // Update session variables
        $_SESSION['user_first_name'] = $firstName;
        $_SESSION['user_last_name'] = $lastName;
        $_SESSION['user_name'] = $firstName . ' ' . $lastName;
        $_SESSION['user_email'] = $email;

        // Log activity
        logActivity($userId, 'bi-person-check', 'Account Updated', 'You updated your account details.');

        $_SESSION['profile_success'] = 'Account details updated successfully.';
        header('Location: profile.php');
        exit;
    }
    elseif ($action === 'update_contact') {
        $contactNumber = trim((string) ($_POST['contact_number'] ?? ''));

        if (!preg_match('/^09\d{9}$/', $contactNumber)) {
            $errors[] = 'Mobile number must be a valid 11-digit number starting with 09.';
        }

        // Verify application exists
        $appStmt = $pdo->prepare('SELECT id FROM applications WHERE user_id = :user_id LIMIT 1');
        $appStmt->execute(['user_id' => $userId]);
        $app = $appStmt->fetch();

        if (!$app) {
            $errors[] = 'No active application found to update contact information.';
        }

        if (!empty($errors)) {
            $_SESSION['profile_errors'] = $errors;
            $_SESSION['profile_old'] = ['contact_number' => $contactNumber];
            header('Location: profile.php');
            exit;
        }

        // Perform contact update
        $updateStmt = $pdo->prepare('UPDATE applications SET contact_number = :contact WHERE user_id = :user_id');
        $updateStmt->execute(['contact' => $contactNumber, 'user_id' => $userId]);

        // Log activity
        logActivity($userId, 'bi-telephone', 'Contact Updated', 'You updated your mobile contact number.');

        $_SESSION['profile_success'] = 'Contact number updated successfully.';
        header('Location: profile.php');
        exit;
    }
    elseif ($action === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($currentPassword === '') {
            $errors[] = 'Current password is required.';
        }
        isPasswordStrong($newPassword, $errors);
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            // Retrieve current password hash
            $passStmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
            $passStmt->execute(['id' => $userId]);
            $userPass = $passStmt->fetchColumn();

            if (!$userPass || !password_verify($currentPassword, $userPass)) {
                $errors[] = 'Your current password is incorrect.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['profile_errors'] = $errors;
            header('Location: profile.php');
            exit;
        }

        // Hash and save new password
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare('UPDATE users SET password = :pass WHERE id = :id');
        $updateStmt->execute(['pass' => $hashed, 'id' => $userId]);

        // Log activity
        logActivity($userId, 'bi-key', 'Password Changed', 'You updated your account password.');

        $_SESSION['profile_success'] = 'Password changed successfully.';
        header('Location: profile.php');
        exit;
    }
    else {
        $_SESSION['profile_errors'] = ['Invalid action requested.'];
        header('Location: profile.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Profile update failed: ' . $e->getMessage());
    $_SESSION['profile_errors'] = ['A database error occurred. Please try again later.'];
    header('Location: profile.php');
    exit;
}
