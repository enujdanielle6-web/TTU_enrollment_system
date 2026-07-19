<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';

if (!empty($_SESSION['logged_in'])) {
    $userId = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['user_role'] ?? '';
    $adminRoles = ['superadmin', 'admissions', 'scholarship', 'cashier'];
    
    if ($userId && in_array($role, $adminRoles, true)) {
        try {
            $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, title, description, icon) VALUES (:uid, "Logged Out", "Administrator logged out.", "bi-box-arrow-right")');
            $logStmt->execute(['uid' => $userId]);
        } catch (Exception $e) {
            error_log('Failed to log logout: ' . $e->getMessage());
        }
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

header('Location: login.php');
exit;
