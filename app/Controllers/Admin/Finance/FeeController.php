<?php
namespace App\Controllers\Admin\Finance;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use PDOException;
use Exception;

class FeeController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$pageTitle = 'Fee Templates - Administrator';

        return $this->render('admin/finance/fees', get_defined_vars());
    }
    public function process(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response->redirect("/sia/admin/finance/fees.php");
    return;
}



$action = $_POST['action'] ?? '';

try {
    if ($action === 'create_fee_template') {
        $name = trim($_POST['name'] ?? '');
        $academicLevel = trim($_POST['academic_level'] ?? 'Senior High School');
        $gradeLevel = trim($_POST['grade_level'] ?? '');
        $strand = trim($_POST['strand'] ?? '');
        if ($strand === '') {
            throw new Exception('Academic Program is required.');
        }

        $semester = null;
        if ($academicLevel === 'College') {
            $semester = trim($_POST['semester'] ?? '');
            if ($semester === '') {
                throw new Exception('Semester is required for College fee templates.');
            }
        }

        $tuition = (float)($_POST['tuition_fee'] ?? 0);
        $misc = (float)($_POST['miscellaneous_fee'] ?? 0);
        $reg = (float)($_POST['registration_fee'] ?? 0);
        $lab = (float)($_POST['laboratory_fee'] ?? 0);
        $other = (float)($_POST['other_fees'] ?? 0);

        if ($name === '' || $gradeLevel === '') {
            throw new Exception('Template Name and Grade Level are required.');
        }

        if ($tuition < 0 || $misc < 0 || $reg < 0 || $lab < 0 || $other < 0) {
            throw new Exception('Fee amounts cannot be negative.');
        }

        $totalAmount = $misc + $reg + $lab + $other;

        $checkSql = 'SELECT id FROM fee_templates WHERE academic_level = :level AND grade_level = :grade AND (strand = :strand OR (strand IS NULL AND :strand_null IS NULL)) AND (semester = :sem OR (semester IS NULL AND :sem_null IS NULL)) LIMIT 1';
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            'level' => $academicLevel,
            'grade' => $gradeLevel,
            'strand' => $strand,
            'strand_null' => $strand,
            'sem' => $semester,
            'sem_null' => $semester
        ]);
        if ($checkStmt->fetch()) {
            throw new Exception('A fee template already exists for this level, program, and semester.');
        }

        $insertStmt = $pdo->prepare('
            INSERT INTO fee_templates 
            (name, academic_level, grade_level, strand, semester, is_per_unit, tuition_fee, miscellaneous_fee, registration_fee, laboratory_fee, other_fees, total_amount) 
            VALUES 
            (:name, :academic_level, :grade, :strand, :semester, 1, :tuition, :misc, :reg, :lab, :other, :total)
        ');
        
        $insertStmt->execute([
            'name' => $name,
            'academic_level' => $academicLevel,
            'grade' => $gradeLevel,
            'strand' => $strand,
            'semester' => $semester,
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
            throw new Exception('Academic Program is required.');
        }

        $semester = null;
        if ($academicLevel === 'College') {
            $semester = trim($_POST['semester'] ?? '');
            if ($semester === '') {
                throw new Exception('Semester is required for College fee templates.');
            }
        }

        $tuition = (float)($_POST['tuition_fee'] ?? 0);
        $misc = (float)($_POST['miscellaneous_fee'] ?? 0);
        $reg = (float)($_POST['registration_fee'] ?? 0);
        $lab = (float)($_POST['laboratory_fee'] ?? 0);
        $other = (float)($_POST['other_fees'] ?? 0);

        if ($id <= 0 || $name === '' || $gradeLevel === '') {
            throw new Exception('Missing required information to update fee template.');
        }
        
        if ($tuition < 0 || $misc < 0 || $reg < 0 || $lab < 0 || $other < 0) {
            throw new Exception('Fee amounts cannot be negative.');
        }

        $totalAmount = $misc + $reg + $lab + $other;

        $checkSql = 'SELECT id FROM fee_templates WHERE academic_level = :level AND grade_level = :grade AND (strand = :strand OR (strand IS NULL AND :strand_null IS NULL)) AND (semester = :sem OR (semester IS NULL AND :sem_null IS NULL)) AND id != :id LIMIT 1';
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            'level' => $academicLevel,
            'grade' => $gradeLevel,
            'strand' => $strand,
            'strand_null' => $strand,
            'sem' => $semester,
            'sem_null' => $semester,
            'id' => $id
        ]);
        if ($checkStmt->fetch()) {
            throw new Exception('A fee template already exists for this level, program, and semester.');
        }

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
                semester = :semester,
                is_per_unit = 1,
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
            'semester' => $semester,
            'tuition' => $tuition,
            'misc' => $misc,
            'reg' => $reg,
            'lab' => $lab,
            'other' => $other,
            'total' => $totalAmount,
            'id' => $id
        ]);

        // Auto-recalculate any UNPAID student assessments using this template
        $assStmt = $pdo->prepare('SELECT id, application_id, discount_amount FROM student_assessments WHERE fee_template_id = :id AND payment_status = "unpaid"');
        $assStmt->execute(['id' => $id]);
        $unpaidAssessments = $assStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($unpaidAssessments as $ua) {
            $uAppId = (int)$ua['application_id'];
            $calcTuition = $tuition;

            if ($academicLevel === 'College') {
                $unitsStmt = $pdo->prepare('
                    SELECT SUM(s.units) 
                    FROM college_enrollments ce 
                    JOIN subjects s ON ce.subject_id = s.id 
                    WHERE ce.application_id = :app_id
                ');
                $unitsStmt->execute(['app_id' => $uAppId]);
                $uUnits = (int)$unitsStmt->fetchColumn();

                if ($uUnits === 0) {
                    $secUnitsStmt = $pdo->prepare('
                        SELECT SUM(s.units) 
                        FROM applications a
                        JOIN college_section_subjects css ON css.college_section_id = a.section_id
                        JOIN subjects s ON css.subject_id = s.id
                        WHERE a.id = :app_id
                    ');
                    $secUnitsStmt->execute(['app_id' => $uAppId]);
                    $uUnits = (int)$secUnitsStmt->fetchColumn();
                }
                $calcTuition = $uUnits * $tuition;
            } elseif ($academicLevel === 'Senior High School') {
                $unitsStmt = $pdo->prepare('
                    SELECT SUM(s.units) 
                    FROM shs_enrollments se 
                    JOIN subjects s ON se.subject_id = s.id 
                    WHERE se.application_id = :app_id
                ');
                $unitsStmt->execute(['app_id' => $uAppId]);
                $uUnits = (int)$unitsStmt->fetchColumn();

                if ($uUnits === 0) {
                    $secUnitsStmt = $pdo->prepare('
                        SELECT SUM(s.units) 
                        FROM applications a
                        JOIN shs_section_subjects ss ON ss.shs_section_id = a.section_id
                        JOIN subjects s ON ss.subject_id = s.id
                        WHERE a.id = :app_id
                    ');
                    $secUnitsStmt->execute(['app_id' => $uAppId]);
                    $uUnits = (int)$secUnitsStmt->fetchColumn();
                }
                $calcTuition = $uUnits * $tuition;
            }

            $uTotal = $calcTuition + $misc + $reg + $lab + $other;
            $uDiscount = (float)$ua['discount_amount'];
            $uNet = max(0, $uTotal - $uDiscount);

            $updAssStmt = $pdo->prepare('
                UPDATE student_assessments 
                SET tuition_fee = :tuition,
                    miscellaneous_fee = :misc,
                    registration_fee = :reg,
                    laboratory_fee = :lab,
                    other_fees = :other,
                    total_amount = :total,
                    net_amount = :net
                WHERE id = :id
            ');
            $updAssStmt->execute([
                'tuition' => $calcTuition,
                'misc' => $misc,
                'reg' => $reg,
                'lab' => $lab,
                'other' => $other,
                'total' => $uTotal,
                'net' => $uNet,
                'id' => $ua['id']
            ]);
        }

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
    elseif ($action === 'delete_fee_template') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Invalid fee template ID.');
        }

        // Check if in use by student assessments
        $checkUsage = $pdo->prepare('SELECT id FROM student_assessments WHERE fee_template_id = :id LIMIT 1');
        $checkUsage->execute(['id' => $id]);
        if ($checkUsage->fetch()) {
            throw new Exception('Cannot delete this fee template because it is currently assigned to one or more student assessments.');
        }

        // Fetch old data for logging
        $stmtOld = $pdo->prepare('SELECT * FROM fee_templates WHERE id = :id');
        $stmtOld->execute(['id' => $id]);
        $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

        if (!$oldData) {
            throw new Exception('Fee template not found.');
        }

        $deleteStmt = $pdo->prepare('DELETE FROM fee_templates WHERE id = :id');
        $deleteStmt->execute(['id' => $id]);

        logActivity(
            (int)$_SESSION['user_id'], 
            'bi-trash', 
            'Fee Template Deleted', 
            "Deleted fee template: " . $oldData['name'],
            "Fee Template #$id",
            $oldData,
            null
        );
        $_SESSION['success_msg'] = 'Fee template deleted successfully.';
    }
    else {
        throw new Exception('Invalid action requested.');
    }
} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
}

$response->redirect("/sia/admin/finance/fees.php");
return;

    }
}



