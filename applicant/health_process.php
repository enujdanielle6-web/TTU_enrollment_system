<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: health_info.php');
    exit;
}

verifyCsrfToken();

$userId = (int) $_SESSION['user_id'];

// Get application
$appStmt = $pdo->prepare('SELECT id FROM applications WHERE user_id = :user_id LIMIT 1');
$appStmt->execute(['user_id' => $userId]);
$application = $appStmt->fetch();

if (!$application) {
    $_SESSION['error_msg'] = 'No active application found.';
    header('Location: health_info.php');
    exit;
}
$appId = (int) $application['id'];

// Retrieve form inputs
$heightRaw = trim($_POST['height'] ?? '');
$weightRaw = trim($_POST['weight'] ?? '');
$height = $heightRaw !== '' ? $heightRaw . ' cm' : '';
$weight = $weightRaw !== '' ? $weightRaw . ' kg' : '';
$bloodType = trim($_POST['blood_type'] ?? '');

$hasAllergies = isset($_POST['has_allergies']) ? 1 : 0;
$hasAsthma = isset($_POST['has_asthma']) ? 1 : 0;
$hasDiabetes = isset($_POST['has_diabetes']) ? 1 : 0;
$hasHypertension = isset($_POST['has_hypertension']) ? 1 : 0;
$hasHeartDisease = isset($_POST['has_heart_disease']) ? 1 : 0;
$hasPhysicalDisability = isset($_POST['has_physical_disability']) ? 1 : 0;
$hasExistingCondition = isset($_POST['has_existing_condition']) ? 1 : 0;
$hasPreviousSurgery = isset($_POST['has_previous_surgery']) ? 1 : 0;
$hasMaintenanceMedication = isset($_POST['has_maintenance_medication']) ? 1 : 0;
$hasHospitalized = isset($_POST['has_hospitalized']) ? 1 : 0;

$medicalConditions = trim($_POST['medical_conditions'] ?? '');
$allergiesDetails = trim($_POST['allergies_details'] ?? '');
$currentMedications = trim($_POST['current_medications'] ?? '');
$otherNotes = trim($_POST['other_notes'] ?? '');

$emergencyName = trim($_POST['emergency_name'] ?? '');
$emergencyRelationship = trim($_POST['emergency_relationship'] ?? '');
$emergencyContact = trim($_POST['emergency_contact'] ?? '');

if (empty($height) || empty($weight) || empty($bloodType) || empty($emergencyName) || empty($emergencyRelationship) || empty($emergencyContact)) {
    $_SESSION['error_msg'] = 'Please fill out all required fields.';
    header('Location: health_info.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if record exists
    $stmt = $pdo->prepare('SELECT id FROM health_records WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $existing = $stmt->fetch();

    if ($existing) {
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
                status = "pending",
                admin_remarks = NULL
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
            'emergency_name' => $emergencyName,
            'emergency_relationship' => $emergencyRelationship,
            'emergency_contact' => $emergencyContact,
            'id' => $existing['id']
        ]);
        
        $logTitle = 'Health Information Updated';
    } else {
        $ins = $pdo->prepare('
            INSERT INTO health_records (
                user_id, application_id, height, weight, blood_type,
                has_allergies, has_asthma, has_diabetes, has_hypertension, has_heart_disease,
                has_physical_disability, has_existing_condition, has_previous_surgery,
                has_maintenance_medication, has_hospitalized,
                medical_conditions, allergies_details, current_medications, other_notes,
                emergency_name, emergency_relationship, emergency_contact, status
            ) VALUES (
                :user_id, :app_id, :height, :weight, :blood_type,
                :has_allergies, :has_asthma, :has_diabetes, :has_hypertension, :has_heart_disease,
                :has_physical_disability, :has_existing_condition, :has_previous_surgery,
                :has_maintenance_medication, :has_hospitalized,
                :medical_conditions, :allergies_details, :current_medications, :other_notes,
                :emergency_name, :emergency_relationship, :emergency_contact, "pending"
            )
        ');
        $ins->execute([
            'user_id' => $userId,
            'app_id' => $appId,
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
            'emergency_name' => $emergencyName,
            'emergency_relationship' => $emergencyRelationship,
            'emergency_contact' => $emergencyContact
        ]);
        
        $logTitle = 'Health Information Submitted';
    }

    $logDesc = 'Your health information has been submitted successfully and is awaiting review.';
    $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, "bi-heart-pulse text-primary", :title, :desc)');
    $logStmt->execute([
        'user_id' => $userId,
        'title' => $logTitle,
        'desc' => $logDesc
    ]);

    $pdo->commit();
    $_SESSION['success_msg'] = 'Health information submitted successfully. Please proceed to the clinic for medical clearance.';

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Health Info Submit Failed: ' . $e->getMessage());
    $_SESSION['error_msg'] = 'A database error occurred while saving your health information.';
}

header('Location: health_info.php');
exit;
