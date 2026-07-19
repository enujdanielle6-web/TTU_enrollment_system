<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('fees.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: fees.php');
    exit;
}

verifyCsrfToken();

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create_fee_template') {
        $name = trim($_POST['name'] ?? '');
        $academicLevel = trim($_POST['academic_level'] ?? 'Senior High School');
        $gradeLevel = trim($_POST['grade_level'] ?? '');
        $strand = trim($_POST['strand'] ?? '');
        if ($strand === '') {
            $strand = null;
        }

        $tuition = (float)($_POST['tuition_fee'] ?? 0);
        $misc = (float)($_POST['miscellaneous_fee'] ?? 0);
        $reg = (float)($_POST['registration_fee'] ?? 0);
        $lab = (float)($_POST['laboratory_fee'] ?? 0);
        $other = (float)($_POST['other_fees'] ?? 0);

        if ($name === '' || $gradeLevel === '') {
            throw new Exception('Template Name and Grade Level are required.');
        }

        $totalAmount = $tuition + $misc + $reg + $lab + $other;

        $insertStmt = $pdo->prepare('
            INSERT INTO fee_templates 
            (name, academic_level, grade_level, strand, tuition_fee, miscellaneous_fee, registration_fee, laboratory_fee, other_fees, total_amount) 
            VALUES 
            (:name, :academic_level, :grade, :strand, :tuition, :misc, :reg, :lab, :other, :total)
        ');
        
        $insertStmt->execute([
            'name' => $name,
            'academic_level' => $academicLevel,
            'grade' => $gradeLevel,
            'strand' => $strand,
            'tuition' => $tuition,
            'misc' => $misc,
            'reg' => $reg,
            'lab' => $lab,
            'other' => $other,
            'total' => $totalAmount
        ]);

        $newId = $pdo->lastInsertId();

        logActivity(
            (int)$_SESSION['user_id'], 
            'bi-cash-stack', 
            'Fee Template Created', 
            "Created a new fee template: " . $name,
            "Fee Template #$newId",
            null,
            ['name' => $name, 'grade' => $gradeLevel, 'strand' => $strand, 'total' => $totalAmount]
        );
        $_SESSION['success_msg'] = 'Fee template created successfully.';
    } 
    elseif ($action === 'update_fee_template') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $academicLevel = trim($_POST['academic_level'] ?? 'Senior High School');
        $gradeLevel = trim($_POST['grade_level'] ?? '');
        $strand = trim($_POST['strand'] ?? '');
        if ($strand === '') {
            $strand = null;
        }

        $tuition = (float)($_POST['tuition_fee'] ?? 0);
        $misc = (float)($_POST['miscellaneous_fee'] ?? 0);
        $reg = (float)($_POST['registration_fee'] ?? 0);
        $lab = (float)($_POST['laboratory_fee'] ?? 0);
        $other = (float)($_POST['other_fees'] ?? 0);

        if ($id <= 0 || $name === '' || $gradeLevel === '') {
            throw new Exception('Missing required information to update fee template.');
        }

        $totalAmount = $tuition + $misc + $reg + $lab + $other;

        // Fetch old data
        $stmtOld = $pdo->prepare('SELECT * FROM fee_templates WHERE id = :id');
        $stmtOld->execute(['id' => $id]);
        $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

        $updateStmt = $pdo->prepare('
            UPDATE fee_templates 
            SET name = :name, 
                academic_level = :academic_level,
                grade_level = :grade, 
                strand = :strand, 
                tuition_fee = :tuition, 
                miscellaneous_fee = :misc, 
                registration_fee = :reg, 
                laboratory_fee = :lab, 
                other_fees = :other, 
                total_amount = :total
            WHERE id = :id
        ');
        
        $updateStmt->execute([
            'name' => $name,
            'academic_level' => $academicLevel,
            'grade' => $gradeLevel,
            'strand' => $strand,
            'tuition' => $tuition,
            'misc' => $misc,
            'reg' => $reg,
            'lab' => $lab,
            'other' => $other,
            'total' => $totalAmount,
            'id' => $id
        ]);

        logActivity(
            (int)$_SESSION['user_id'], 
            'bi-pencil', 
            'Fee Template Updated', 
            "Updated details for fee template: " . $name,
            "Fee Template #$id",
            $oldData,
            ['name' => $name, 'grade' => $gradeLevel, 'strand' => $strand, 'total' => $totalAmount]
        );
        $_SESSION['success_msg'] = 'Fee template details updated successfully.';
    }
    else {
        throw new Exception('Invalid action requested.');
    }
} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
}

header('Location: fees.php');
exit;

