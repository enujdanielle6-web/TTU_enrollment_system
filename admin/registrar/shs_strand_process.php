<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('programs.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: shs_strands.php');
    exit;
}

verifyCsrfToken();

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create_strand') {
        $code = strtolower(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');

        if ($code === '' || $name === '') {
            throw new Exception('Strand code and name are required.');
        }

        // Check if code exists
        $stmt = $pdo->prepare('SELECT id FROM shs_strands WHERE code = :code');
        $stmt->execute(['code' => $code]);
        if ($stmt->fetch()) {
            throw new Exception("A strand with code '{$code}' already exists.");
        }

        $insertStmt = $pdo->prepare('INSERT INTO shs_strands (code, name, is_active) VALUES (:code, :name, 1)');
        $insertStmt->execute(['code' => $code, 'name' => $name]);

        logActivity((int)$_SESSION['user_id'], 'bi-mortarboard', 'SHS Strand Added', "Added SHS strand: " . strtoupper($code));
        $_SESSION['success_msg'] = 'Strand created successfully.';
    } 
    elseif ($action === 'update_strand') {
        $id = (int)($_POST['id'] ?? 0);
        $code = strtolower(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');

        if ($id <= 0 || $code === '' || $name === '') {
            throw new Exception('Missing required information to update strand.');
        }

        // Check duplicate code
        $stmt = $pdo->prepare('SELECT id FROM shs_strands WHERE code = :code AND id != :id');
        $stmt->execute(['code' => $code, 'id' => $id]);
        if ($stmt->fetch()) {
            throw new Exception("The code '{$code}' is already used by another strand.");
        }

        $updateStmt = $pdo->prepare('UPDATE shs_strands SET code = :code, name = :name WHERE id = :id');
        $updateStmt->execute(['code' => $code, 'name' => $name, 'id' => $id]);

        logActivity((int)$_SESSION['user_id'], 'bi-pencil', 'SHS Strand Updated', "Updated details for strand: " . strtoupper($code));
        $_SESSION['success_msg'] = 'Strand details updated successfully.';
    }
    elseif ($action === 'toggle_strand') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE shs_strands SET is_active = :status WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $id]);
            $_SESSION['success_msg'] = 'Strand status updated successfully.';
        }
    }
    else {
        throw new Exception('Invalid action requested.');
    }
} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
}

header('Location: shs_strands.php');
exit;
