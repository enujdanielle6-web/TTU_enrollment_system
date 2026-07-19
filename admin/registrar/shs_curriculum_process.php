<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('shs_curriculum.manage');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $strandId = (int) ($_POST['strand_id'] ?? 0);
    $gradeLevel = trim($_POST['grade_level'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $subjectIds = $_POST['subject_ids'] ?? [];

    if (!is_array($subjectIds)) {
        $subjectIds = [$subjectIds];
    }

    $added = 0;
    $duplicates = 0;

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO shs_curriculum (strand_id, grade_level, semester, subject_id) VALUES (:strand, :gl, :sem, :subject)');
        
        foreach ($subjectIds as $subId) {
            $subId = (int) $subId;
            if ($subId <= 0) continue;

            try {
                $stmt->execute(['strand' => $strandId, 'gl' => $gradeLevel, 'sem' => $semester, 'subject' => $subId]);
                $added++;
            } catch (PDOException $e) {
                // Ignore duplicates gracefully
                if ($e->getCode() == 23000) {
                    $duplicates++;
                } else {
                    throw $e;
                }
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = 'An error occurred while compiling shs_curriculum: ' . $e->getMessage();
        header("Location: shs_curriculum_builder.php?strand_id=$strandId");
        exit;
    }

    if ($added > 0) {
        logActivity(
            (int)$_SESSION['user_id'], 
            'bi-book', 
            'SHS Curriculum Updated', 
            "Added $added subject(s) to SHS Curriculum (Strand ID: $strandId, Grade: $gradeLevel, Sem: $semester).",
            "SHS Curriculum Strand #$strandId",
            null,
            ['added_count' => $added, 'subject_ids' => $subjectIds, 'grade_level' => $gradeLevel, 'semester' => $semester]
        );
        $_SESSION['success_msg'] = "$added subject(s) added successfully." . ($duplicates > 0 ? " ($duplicates duplicates ignored)" : "");
    } else if ($duplicates > 0) {
        $_SESSION['error_msg'] = 'All selected subjects are already assigned to this semester.';
    } else {
        $_SESSION['error_msg'] = 'No subjects were selected or failed to add.';
    }
    
    header("Location: shs_curriculum_builder.php?strand_id=$strandId");
    exit;

} elseif ($action === 'delete_subject') {
    $strandId = (int) ($_POST['strand_id'] ?? 0);
    $mappingId = (int) ($_POST['mapping_id'] ?? 0);

    if ($mappingId <= 0) {
        $_SESSION['error_msg'] = 'Invalid curriculum mapping ID.';
        header("Location: shs_curriculum_builder.php?strand_id=$strandId");
        exit;
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM shs_curriculum WHERE id = :id');
        $stmt->execute(['id' => $mappingId]);
        
        if ($stmt->rowCount() > 0) {
            logActivity(
                (int)$_SESSION['user_id'], 
                'bi-trash', 
                'SHS Curriculum Subject Removed', 
                "Removed a subject mapping (ID: $mappingId) from SHS Curriculum (Strand ID: $strandId).",
                "SHS Curriculum Strand #$strandId",
                null,
                ['mapping_id' => $mappingId]
            );
            $_SESSION['success_msg'] = 'Subject successfully removed from the curriculum.';
        } else {
            $_SESSION['error_msg'] = 'Failed to remove subject or it was already removed.';
        }
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = 'Error removing subject: ' . $e->getMessage();
    }

    header("Location: shs_curriculum_builder.php?strand_id=$strandId");
    exit;
} else {
    $_SESSION['error_msg'] = 'Invalid action.';
    header('Location: shs_curriculum.php');
    exit;
}
