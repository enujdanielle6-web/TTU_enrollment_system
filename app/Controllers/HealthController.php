<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\HealthRecord;
use App\Core\Database;

class HealthController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        $userId = (int) $_SESSION['user_id'];
        $pdo = Database::getConnection();

        $appStmt = $pdo->prepare('SELECT * FROM applications WHERE user_id = :user_id LIMIT 1');
        $appStmt->execute(['user_id' => $userId]);
        $application = $appStmt->fetch();

        if (!$application) {
            $_SESSION['error_msg'] = 'No active application found.';
            $response->redirect('/sia/applicant/dashboard.php');
            return;
        }

        if (!in_array($application['status'], ['approved', 'enrolled'], true)) {
            $_SESSION['error_msg'] = 'You can only submit health information once your application is approved.';
            $response->redirect('/sia/applicant/status.php');
            return;
        }

        $stmt = $pdo->prepare('SELECT * FROM health_records WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $healthRecord = $stmt->fetch();
        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        return $this->render('applicant/health_info', [
            'application' => $application,
            'healthRecord' => $healthRecord,
            'successMsg' => $successMsg,
            'errorMsg' => $errorMsg
        ]);
    }

    public function process(Request $request, Response $response)
    {
        $userId = (int) $_SESSION['user_id'];
        $pdo = Database::getConnection();

        $appStmt = $pdo->prepare('SELECT id FROM applications WHERE user_id = :user_id LIMIT 1');
        $appStmt->execute(['user_id' => $userId]);
        $application = $appStmt->fetch();

        if (!$application) {
            $_SESSION['error_msg'] = 'No active application found.';
            $response->redirect('/sia/applicant/health_info.php');
            return;
        }
        $appId = (int) $application['id'];

        $heightRaw = trim((string) $request->input('height', ''));
        $weightRaw = trim((string) $request->input('weight', ''));
        $height = $heightRaw !== '' ? $heightRaw . ' cm' : '';
        $weight = $weightRaw !== '' ? $weightRaw . ' kg' : '';
        $bloodType = trim((string) $request->input('blood_type', ''));

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

        $medicalConditions = trim((string) $request->input('medical_conditions', ''));
        $allergiesDetails = trim((string) $request->input('allergies_details', ''));
        $currentMedications = trim((string) $request->input('current_medications', ''));
        $otherNotes = trim((string) $request->input('other_notes', ''));

        $emergencyName = trim((string) $request->input('emergency_name', ''));
        $emergencyRelationship = trim((string) $request->input('emergency_relationship', ''));
        $emergencyContact = trim((string) $request->input('emergency_contact', ''));

        if (empty($height) || empty($weight) || empty($bloodType) || empty($emergencyName) || empty($emergencyRelationship) || empty($emergencyContact)) {
            $_SESSION['error_msg'] = 'Please fill out all required fields.';
            $response->redirect('/sia/applicant/health_info.php');
            return;
        }

        if (!preg_match('/^(09\d{9}|(\+639)\d{9})$/', $emergencyContact)) {
            $_SESSION['error_msg'] = 'Emergency contact number must be a valid 11-digit number starting with 09.';
            $response->redirect('/sia/applicant/health_info.php');
            return;
        }

        try {
            $pdo->beginTransaction();

            $data = [
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
            ];

            HealthRecord::save($userId, $appId, $data);

            $stmt = $pdo->prepare('SELECT id FROM health_records WHERE user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            $isUpdate = $stmt->fetch() !== false;

            $logTitle = $isUpdate ? 'Health Information Updated' : 'Health Information Submitted';
            $logDesc = 'Your health information has been submitted successfully and is awaiting review.';
            User::logActivity($userId, $logTitle, $logDesc, 'bi-heart-pulse text-primary');

            $pdo->commit();
            $_SESSION['success_msg'] = 'Health information submitted successfully. Please proceed to the clinic for medical clearance.';
            $response->redirect('/sia/applicant/health_info.php');
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Health Info Submit Failed: ' . $e->getMessage());
            $_SESSION['error_msg'] = 'A database error occurred while saving your health information.';
            $response->redirect('/sia/applicant/health_info.php');
        }
    }
}



