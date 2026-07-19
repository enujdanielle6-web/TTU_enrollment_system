<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('programs.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: college_programs.php');
    exit;
}

verifyCsrfToken();

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create_program') {
        $code = strtolower(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');

        if ($code === '' || $name === '') {
            throw new Exception('Program code and name are required.');
        }

        // Check if code exists
        $stmt = $pdo->prepare('SELECT id FROM college_programs WHERE code = :code');
        $stmt->execute(['code' => $code]);
        if ($stmt->fetch()) {
            throw new Exception("A program with code '{$code}' already exists.");
        }

        $insertStmt = $pdo->prepare('INSERT INTO college_programs (code, name, is_active) VALUES (:code, :name, 1)');
        $insertStmt->execute(['code' => $code, 'name' => $name]);

        logActivity((int)$_SESSION['user_id'], 'bi-mortarboard', 'College Program Added', "Added college program: " . strtoupper($code));
        $_SESSION['success_msg'] = 'Program created successfully.';
    } 
    elseif ($action === 'update_program') {
        $id = (int)($_POST['id'] ?? 0);
        $code = strtolower(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');

        if ($id <= 0 || $code === '' || $name === '') {
            throw new Exception('Missing required information to update program.');
        }

        // Check duplicate code
        $stmt = $pdo->prepare('SELECT id FROM college_programs WHERE code = :code AND id != :id');
        $stmt->execute(['code' => $code, 'id' => $id]);
        if ($stmt->fetch()) {
            throw new Exception("The code '{$code}' is already used by another program.");
        }

        $updateStmt = $pdo->prepare('UPDATE college_programs SET code = :code, name = :name WHERE id = :id');
        $updateStmt->execute(['code' => $code, 'name' => $name, 'id' => $id]);

        logActivity((int)$_SESSION['user_id'], 'bi-pencil', 'College Program Updated', "Updated details for program: " . strtoupper($code));
        $_SESSION['success_msg'] = 'Program details updated successfully.';
    }
    elseif ($action === 'toggle_program') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE college_programs SET is_active = :status WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $id]);
            $_SESSION['success_msg'] = 'Program status updated successfully.';
        }
    }
    else {
        throw new Exception('Invalid action requested.');
    }
} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
}

header('Location: college_programs.php');
exit;
