<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('*');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$action = $_POST['action'] ?? '';

verifyCsrfToken();

try {
    if ($action === 'create_user') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = trim($_POST['role'] ?? 'applicant');
        $department = trim($_POST['department'] ?? '');
        $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? json_encode($_POST['permissions']) : null;
        if ($department === '') $department = null;

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            throw new Exception('All fields are required to create a new user.');
        }

        $passErrors = [];
        if (!isPasswordStrong($password, $passErrors)) {
            throw new Exception(implode(' ', $passErrors));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format.');
        }

        if (!in_array($role, ['applicant', 'superadmin', 'admissions', 'scholarship', 'cashier'])) {
            throw new Exception('Invalid role specified.');
        }

        // Check if email exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            throw new Exception('A user with that email already exists.');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $insertStmt = $pdo->prepare('
            INSERT INTO users (first_name, last_name, email, password, role, department, permissions) 
            VALUES (:first, :last, :email, :pass, :role, :dept, :perms)
        ');
        $insertStmt->execute([
            'first' => $firstName,
            'last' => $lastName,
            'email' => $email,
            'pass' => $hashedPassword,
            'role' => $role,
            'dept' => $department,
            'perms' => $permissions
        ]);

        $newUserId = $pdo->lastInsertId();

        // Audit Log
        logActivity(
            (int)$_SESSION['user_id'], 
            'bi-person-plus-fill', 
            'User Created', 
            "Created a new $role account for $email (ID: $newUserId).",
            "User #$newUserId",
            null,
            ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email, 'role' => $role, 'department' => $department, 'permissions' => $permissions]
        );

        $_SESSION['success_msg'] = 'User account created successfully.';
    } 
    elseif ($action === 'update_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'applicant');
        $department = trim($_POST['department'] ?? '');
        $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? json_encode($_POST['permissions']) : null;
        if ($department === '') $department = null;
        $newPassword = $_POST['new_password'] ?? '';

        if ($userId <= 0 || $firstName === '' || $lastName === '' || $email === '') {
            throw new Exception('Missing required user information for update.');
        }

        // Check if email exists for another user
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $stmt->execute(['email' => $email, 'id' => $userId]);
        if ($stmt->fetch()) {
            throw new Exception('The requested email is already used by another account.');
        }

        $stmtOld = $pdo->prepare('SELECT first_name, last_name, email, role, department, permissions FROM users WHERE id = :id');
        $stmtOld->execute(['id' => $userId]);
        $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

        // Base update query
        $updateQuery = 'UPDATE users SET first_name = :first, last_name = :last, email = :email, role = :role, department = :dept, permissions = :perms';
        $params = [
            'first' => $firstName,
            'last' => $lastName,
            'email' => $email,
            'role' => $role,
            'dept' => $department,
            'perms' => $permissions,
            'id' => $userId
        ];

        $auditDesc = "Updated account details for user ID $userId.";

        // Update password if provided
        if ($newPassword !== '') {
            $passErrors = [];
            if (!isPasswordStrong($newPassword, $passErrors)) {
                throw new Exception(implode(' ', $passErrors));
            }
            $updateQuery .= ', password = :pass';
            $params['pass'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $auditDesc .= " (Password was reset).";
        }

        $updateQuery .= ' WHERE id = :id';

        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute($params);

        // Audit Log
        logActivity(
            (int)$_SESSION['user_id'], 
            'bi-person-gear', 
            'Permission Changed', 
            $auditDesc,
            "User #$userId",
            $oldData,
            ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email, 'role' => $role, 'department' => $department, 'permissions' => $permissions]
        );

        $_SESSION['success_msg'] = 'User account updated successfully.';
    } elseif ($action === 'toggle_status') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $currentStatus = (int)($_POST['current_status'] ?? 1);
        $newStatus = $currentStatus === 1 ? 0 : 1;

        if ($userId <= 0) {
            throw new Exception('Invalid user ID.');
        }

        // Prevent deactivating yourself
        if ($userId === (int)$_SESSION['user_id']) {
            throw new Exception('You cannot deactivate your own account.');
        }

        $stmt = $pdo->prepare('UPDATE users SET is_active = :status WHERE id = :id');
        $stmt->execute(['status' => $newStatus, 'id' => $userId]);

        $statusText = $newStatus === 1 ? 'Activated' : 'Deactivated';

        logActivity(
            (int)$_SESSION['user_id'], 
            $newStatus === 1 ? 'bi-unlock-fill' : 'bi-lock-fill', 
            "Account $statusText", 
            "User ID $userId was $statusText."
        );

        $_SESSION['success_msg'] = "User account $statusText successfully.";

    } elseif ($action === 'reset_password') {
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            throw new Exception('Invalid user ID.');
        }

        $newPassword = '@Admin123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('UPDATE users SET password = :pass WHERE id = :id');
        $stmt->execute(['pass' => $hashedPassword, 'id' => $userId]);

        logActivity(
            (int)$_SESSION['user_id'], 
            'bi-key-fill', 
            'Password Reset', 
            "Password for User ID $userId was reset to the default."
        );

        $_SESSION['success_msg'] = "Password reset successfully to default (@Admin123).";

    } else {
        throw new Exception('Invalid action requested.');
    }

} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
}

header('Location: users.php');
exit;

