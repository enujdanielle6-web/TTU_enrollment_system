<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('medical.review');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: medical_clearance.php');
    exit;
}

verifyCsrfToken();

$recordId = (int)($_POST['record_id'] ?? 0);
$userId = (int)($_POST['user_id'] ?? 0);
$status = $_POST['status'] ?? '';
$adminRemarks = trim($_POST['admin_remarks'] ?? '');

// Physical Info
$height = isset($_POST['height']) && $_POST['height'] !== '' ? (float)$_POST['height'] : null;
$weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
$bloodType = $_POST['blood_type'] ?? null;

// Medical History Checkboxes
$hasAllergies = !empty($_POST['has_allergies']) ? 1 : 0;
$hasAsthma = !empty($_POST['has_asthma']) ? 1 : 0;
$hasDiabetes = !empty($_POST['has_diabetes']) ? 1 : 0;
$hasHypertension = !empty($_POST['has_hypertension']) ? 1 : 0;
$hasHeartDisease = !empty($_POST['has_heart_disease']) ? 1 : 0;
$hasPhysicalDisability = !empty($_POST['has_physical_disability']) ? 1 : 0;
$hasExistingCondition = !empty($_POST['has_existing_condition']) ? 1 : 0;
$hasPreviousSurgery = !empty($_POST['has_previous_surgery']) ? 1 : 0;
$hasMaintenanceMedication = !empty($_POST['has_maintenance_medication']) ? 1 : 0;
$hasHospitalized = !empty($_POST['has_hospitalized']) ? 1 : 0;

// Additional Details
$medicalConditions = trim($_POST['medical_conditions'] ?? '');
$allergiesDetails = trim($_POST['allergies_details'] ?? '');
$currentMedications = trim($_POST['current_medications'] ?? '');
$otherNotes = trim($_POST['other_notes'] ?? '');

// Emergency Contact
$emergencyName = trim($_POST['emergency_name'] ?? '');
$emergencyRelationship = trim($_POST['emergency_relationship'] ?? '');
$emergencyContact = trim($_POST['emergency_contact'] ?? '');

$validStatuses = ['pending', 'under_review', 'verified', 'correction_required', 'rejected'];

if ($recordId <= 0 || !in_array($status, $validStatuses, true)) {
    $_SESSION['error_msg'] = 'Invalid request parameters.';
    header('Location: medical_clearance.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Fetch old status to check if it changed
    $stmt = $pdo->prepare('SELECT status FROM health_records WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $recordId]);
    $oldStatus = $stmt->fetchColumn();

    if ($oldStatus === false) {
        throw new Exception('Record not found.');
    }

    // Update record
    $upd = $pdo->prepare('
        UPDATE health_records SET 
            height = :height,
            weight = :weight,
            blood_type = :blood_type,
            has_allergies = :has_allergies,
            has_asthma = :has_asthma,
            has_diabetes = :has_diabetes,
            has_hypertension = :has_hypertension,
            has_heart_disease = :has_heart_disease,
            has_physical_disability = :has_physical_disability,
            has_existing_condition = :has_existing_condition,
            has_previous_surgery = :has_previous_surgery,
            has_maintenance_medication = :has_maintenance_medication,
            has_hospitalized = :has_hospitalized,
            medical_conditions = :medical_conditions,
            allergies_details = :allergies_details,
            current_medications = :current_medications,
            other_notes = :other_notes,
            emergency_name = :emergency_name,
            emergency_relationship = :emergency_relationship,
            emergency_contact = :emergency_contact,
            status = :status, 
            admin_remarks = :remarks 
        WHERE id = :id
    ');
    
    $upd->execute([
        'height' => $height,
        'weight' => $weight,
        'blood_type' => $bloodType,
        'has_allergies' => $hasAllergies,
        'has_asthma' => $hasAsthma,
        'has_diabetes' => $hasDiabetes,
        'has_hypertension' => $hasHypertension,
        'has_heart_disease' => $hasHeartDisease,
        'has_physical_disability' => $hasPhysicalDisability,
        'has_existing_condition' => $hasExistingCondition,
        'has_previous_surgery' => $hasPreviousSurgery,
        'has_maintenance_medication' => $hasMaintenanceMedication,
        'has_hospitalized' => $hasHospitalized,
        'medical_conditions' => $medicalConditions !== '' ? $medicalConditions : null,
        'allergies_details' => $allergiesDetails !== '' ? $allergiesDetails : null,
        'current_medications' => $currentMedications !== '' ? $currentMedications : null,
        'other_notes' => $otherNotes !== '' ? $otherNotes : null,
        'emergency_name' => $emergencyName !== '' ? $emergencyName : null,
        'emergency_relationship' => $emergencyRelationship !== '' ? $emergencyRelationship : null,
        'emergency_contact' => $emergencyContact !== '' ? $emergencyContact : null,
        'status' => $status,
        'remarks' => $adminRemarks !== '' ? $adminRemarks : null,
        'id' => $recordId
    ]);

    // Log Activity if status changed
    if ($oldStatus !== $status) {
        $logIcon = match($status) {
            'verified' => 'bi-heart-pulse-fill text-success',
            'rejected' => 'bi-x-circle-fill text-danger',
            'correction_required' => 'bi-exclamation-triangle-fill text-warning',
            'under_review' => 'bi-search text-info',
            default => 'bi-info-circle-fill text-primary'
        };

        $statusTitle = formatApplicationStatus($status);
        $logTitle = "Medical Clearance: {$statusTitle}";
        
        $logDescription = $adminRemarks !== '' 
            ? "Clinic Remarks: " . $adminRemarks 
            : "Your medical clearance status has been updated to {$statusTitle}.";

        $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
        $logStmt->execute([
            'user_id' => $userId,
            'icon' => $logIcon,
            'title' => $logTitle,
            'description' => $logDescription
        ]);
    }

    $pdo->commit();
    $_SESSION['success_msg'] = 'Medical clearance successfully updated.';
    header("Location: medical_detail.php?id={$recordId}");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Medical Process Failed: ' . $e->getMessage());
    $_SESSION['error_msg'] = 'A database error occurred while updating the record.';
    header("Location: medical_detail.php?id={$recordId}");
    exit;
}
