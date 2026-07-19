<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('college_curriculum.manage');

$action = $_POST['action'] ?? '';

if ($action === 'create_curriculum') {
    $programId = (int)($_POST['program_id'] ?? 0);
    $name = trim($_POST['curriculum_name'] ?? '');
    $version = trim($_POST['version'] ?? '1.0');
    $ay = trim($_POST['effective_academic_year'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $desc = trim($_POST['description'] ?? '');

    try {
        $stmt = $pdo->prepare("INSERT INTO college_curricula (program_id, curriculum_name, version, effective_academic_year, description, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$programId, $name, $version, $ay ?: null, $desc ?: null, $status]);
        $_SESSION['success_msg'] = "Curriculum created successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Failed to create curriculum: " . $e->getMessage();
    }
    header('Location: college_curriculum.php');
    exit;
} elseif ($action === 'update_curriculum') {
    $id = (int)($_POST['curriculum_id'] ?? 0);
    $programId = (int)($_POST['program_id'] ?? 0);
    $name = trim($_POST['curriculum_name'] ?? '');
    $version = trim($_POST['version'] ?? '1.0');
    $ay = trim($_POST['effective_academic_year'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $desc = trim($_POST['description'] ?? '');

    try {
        $stmt = $pdo->prepare("UPDATE college_curricula SET program_id = ?, curriculum_name = ?, version = ?, effective_academic_year = ?, description = ?, status = ? WHERE id = ?");
        $stmt->execute([$programId, $name, $version, $ay ?: null, $desc ?: null, $status, $id]);
        $_SESSION['success_msg'] = "Curriculum updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Failed to update curriculum: " . $e->getMessage();
    }
    header('Location: college_curriculum.php');
    exit;
} elseif ($action === 'delete_curriculum') {
    $id = (int)($_POST['curriculum_id'] ?? 0);
    try {
        $stmt = $pdo->prepare("DELETE FROM college_curricula WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success_msg'] = "Curriculum deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Failed to delete curriculum: " . $e->getMessage();
    }
    header('Location: college_curriculum.php');
    exit;
} elseif ($action === 'add_subject') {
    $currId = (int)($_POST['curriculum_id'] ?? 0);
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $yearLevel = trim($_POST['year_level'] ?? '');
    $semester = trim($_POST['semester'] ?? '');

    try {
        // Get max display order
        $ordStmt = $pdo->prepare("SELECT MAX(display_order) FROM college_curriculum_subjects WHERE curriculum_id = ? AND year_level = ? AND semester = ?");
        $ordStmt->execute([$currId, $yearLevel, $semester]);
        $maxOrder = (int)$ordStmt->fetchColumn();

        $stmt = $pdo->prepare("INSERT INTO college_curriculum_subjects (curriculum_id, subject_id, year_level, semester, display_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$currId, $subjectId, $yearLevel, $semester, $maxOrder + 1]);
        $_SESSION['success_msg'] = "Subject added successfully.";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_msg'] = "Subject is already assigned to this year and semester.";
        } else {
            $_SESSION['error_msg'] = "Failed to add subject: " . $e->getMessage();
        }
    }
    header("Location: college_curriculum_builder.php?id=$currId");
    exit;
} elseif ($action === 'edit_subject') {
    $subId = (int)($_POST['subject_mapping_id'] ?? 0);
    $currId = (int)($_POST['curriculum_id'] ?? 0);
    $yearLevel = trim($_POST['year_level'] ?? '');
    $semester = trim($_POST['semester'] ?? '');

    try {
        // Get max display order in new year/sem
        $ordStmt = $pdo->prepare("SELECT MAX(display_order) FROM college_curriculum_subjects WHERE curriculum_id = ? AND year_level = ? AND semester = ?");
        $ordStmt->execute([$currId, $yearLevel, $semester]);
        $maxOrder = (int)$ordStmt->fetchColumn();

        $stmt = $pdo->prepare("UPDATE college_curriculum_subjects SET year_level = ?, semester = ?, display_order = ? WHERE id = ?");
        $stmt->execute([$yearLevel, $semester, $maxOrder + 1, $subId]);
        $_SESSION['success_msg'] = "Subject updated successfully.";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_msg'] = "Subject is already assigned to that year and semester.";
        } else {
            $_SESSION['error_msg'] = "Failed to update subject: " . $e->getMessage();
        }
    }
    header("Location: college_curriculum_builder.php?id=$currId");
    exit;
} elseif ($action === 'delete_subject') {
    $subId = (int)($_POST['subject_mapping_id'] ?? 0);
    $currId = (int)($_POST['curriculum_id'] ?? 0);

    try {
        $stmt = $pdo->prepare("DELETE FROM college_curriculum_subjects WHERE id = ?");
        $stmt->execute([$subId]);
        $_SESSION['success_msg'] = "Subject removed from curriculum.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Failed to remove subject: " . $e->getMessage();
    }
    header("Location: college_curriculum_builder.php?id=$currId");
    exit;
} elseif ($action === 'move_subject') {
    $subId = (int)($_POST['subject_mapping_id'] ?? 0);
    $currId = (int)($_POST['curriculum_id'] ?? 0);
    $direction = $_POST['direction'] ?? 'up';

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT year_level, semester, display_order FROM college_curriculum_subjects WHERE id = ?");
        $stmt->execute([$subId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($current) {
            $yearLevel = $current['year_level'];
            $semester = $current['semester'];
            $currentOrder = (int)$current['display_order'];
            
            $swapStmt = null;
            if ($direction === 'up') {
                $swapStmt = $pdo->prepare("SELECT id, display_order FROM college_curriculum_subjects WHERE curriculum_id = ? AND year_level = ? AND semester = ? AND display_order < ? ORDER BY display_order DESC LIMIT 1");
            } else {
                $swapStmt = $pdo->prepare("SELECT id, display_order FROM college_curriculum_subjects WHERE curriculum_id = ? AND year_level = ? AND semester = ? AND display_order > ? ORDER BY display_order ASC LIMIT 1");
            }
            
            $swapStmt->execute([$currId, $yearLevel, $semester, $currentOrder]);
            $swapWith = $swapStmt->fetch(PDO::FETCH_ASSOC);

            if ($swapWith) {
                $update1 = $pdo->prepare("UPDATE college_curriculum_subjects SET display_order = ? WHERE id = ?");
                $update1->execute([$swapWith['display_order'], $subId]);
                
                $update2 = $pdo->prepare("UPDATE college_curriculum_subjects SET display_order = ? WHERE id = ?");
                $update2->execute([$currentOrder, $swapWith['id']]);
            }
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Failed to reorder subject: " . $e->getMessage();
    }
    header("Location: college_curriculum_builder.php?id=$currId");
    exit;
}

header('Location: college_curriculum.php');
exit;
