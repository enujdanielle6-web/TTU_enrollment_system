<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('subjects.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: subjects.php');
    exit;
}

verifyCsrfToken();

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $code = trim($_POST['subject_code'] ?? '');
    $name = trim($_POST['subject_name'] ?? '');
    $units = (int) ($_POST['units'] ?? 3);
    $type = trim($_POST['subject_type'] ?? 'Lecture');
    $desc = trim($_POST['description'] ?? '');
    $level = trim($_POST['education_level'] ?? 'College');

    if ($code === '' || $name === '') {
        $_SESSION['error_msg'] = 'Subject code and name are required.';
        header('Location: subjects.php');
        exit;
    }
    
    if ($units < 0 || $units > 12) {
        $_SESSION['error_msg'] = 'Subject units must be between 0 and 12.';
        header('Location: subjects.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO subjects (subject_code, subject_name, units, subject_type, description, education_level) VALUES (:code, :name, :units, :type, :desc, :level)');
        $stmt->execute(['code' => $code, 'name' => $name, 'units' => $units, 'type' => $type, 'desc' => $desc, 'level' => $level]);
        $_SESSION['success_msg'] = 'Subject added successfully.';
        logActivity((int)$_SESSION['user_id'], 'bi-journal-plus', 'Subject Added', "Added subject: " . strtoupper($code));
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
    $type = trim($_POST['subject_type'] ?? 'Lecture');
    $desc = trim($_POST['description'] ?? '');
    $level = trim($_POST['education_level'] ?? 'College');
    $status = (int) ($_POST['status'] ?? 1);

    if ($id <= 0 || $code === '' || $name === '') {
        $_SESSION['error_msg'] = 'Subject ID, code, and name are required.';
        header('Location: subjects.php');
        exit;
    }
    
    if ($units < 0 || $units > 12) {
        $_SESSION['error_msg'] = 'Subject units must be between 0 and 12.';
        header('Location: subjects.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare('UPDATE subjects SET subject_code = :code, subject_name = :name, units = :units, subject_type = :type, description = :desc, education_level = :level, status = :status WHERE id = :id');
        $stmt->execute(['code' => $code, 'name' => $name, 'units' => $units, 'type' => $type, 'desc' => $desc, 'level' => $level, 'status' => $status, 'id' => $id]);
        $_SESSION['success_msg'] = 'Subject updated successfully.';
        logActivity((int)$_SESSION['user_id'], 'bi-journal-text', 'Subject Updated', "Updated subject details for: " . strtoupper($code));
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_msg'] = 'Subject Code already exists.';
        } else {
            $_SESSION['error_msg'] = 'Failed to update subject.';
        }
    }
} elseif ($action === 'delete') {
    $id = (int) ($_POST['subject_id'] ?? 0);
    
    if ($id <= 0) {
        $_SESSION['error_msg'] = 'Invalid subject ID.';
        header('Location: subjects.php');
        exit;
    }
    
    try {
        // Only delete if it's not being used in any curriculum (foreign key constraint will catch this)
        $stmt = $pdo->prepare('DELETE FROM subjects WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $_SESSION['success_msg'] = 'Subject deleted successfully.';
        logActivity((int)$_SESSION['user_id'], 'bi-journal-minus', 'Subject Deleted', "Deleted subject ID: $id");
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_msg'] = 'Cannot delete subject because it is currently assigned to a curriculum or enrollment record.';
        } else {
            $_SESSION['error_msg'] = 'Failed to delete subject.';
        }
    }
}

header('Location: subjects.php');
exit;

