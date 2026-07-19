<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('subjects.manage');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $code = trim($_POST['subject_code'] ?? '');
    $name = trim($_POST['subject_name'] ?? '');
    $units = (int) ($_POST['units'] ?? 3);
    $desc = trim($_POST['description'] ?? '');
    $level = trim($_POST['education_level'] ?? 'College');

    try {
        $stmt = $pdo->prepare('INSERT INTO subjects (subject_code, subject_name, units, description, education_level) VALUES (:code, :name, :units, :desc, :level)');
        $stmt->execute(['code' => $code, 'name' => $name, 'units' => $units, 'desc' => $desc, 'level' => $level]);
        $_SESSION['success_msg'] = 'Subject added successfully.';
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_msg'] = 'Subject Code already exists.';
        } else {
            $_SESSION['error_msg'] = 'Failed to add subject.';
        }
    }
} elseif ($action === 'edit') {
    $id = (int) ($_POST['subject_id'] ?? 0);
    $code = trim($_POST['subject_code'] ?? '');
    $name = trim($_POST['subject_name'] ?? '');
    $units = (int) ($_POST['units'] ?? 3);
    $desc = trim($_POST['description'] ?? '');
    $level = trim($_POST['education_level'] ?? 'College');
    $status = (int) ($_POST['status'] ?? 1);

    try {
        $stmt = $pdo->prepare('UPDATE subjects SET subject_code = :code, subject_name = :name, units = :units, description = :desc, education_level = :level, status = :status WHERE id = :id');
        $stmt->execute(['code' => $code, 'name' => $name, 'units' => $units, 'desc' => $desc, 'level' => $level, 'status' => $status, 'id' => $id]);
        $_SESSION['success_msg'] = 'Subject updated successfully.';
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_msg'] = 'Subject Code already exists.';
        } else {
            $_SESSION['error_msg'] = 'Failed to update subject.';
        }
    }
} elseif ($action === 'delete') {
    $id = (int) ($_POST['subject_id'] ?? 0);
    try {
        // Only delete if it's not being used in any curriculum (foreign key constraint will catch this)
        $stmt = $pdo->prepare('DELETE FROM subjects WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $_SESSION['success_msg'] = 'Subject deleted successfully.';
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_msg'] = 'Cannot delete subject because it is currently assigned to a curriculum.';
        } else {
            $_SESSION['error_msg'] = 'Failed to delete subject.';
        }
    }
}

header('Location: subjects.php');
exit;

