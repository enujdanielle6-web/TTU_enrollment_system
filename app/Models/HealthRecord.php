<?php

namespace App\Models;

use App\Core\Database;

class HealthRecord
{
    public static function getStatus(int $userId): ?string
    {
        $pdo = Database::getConnection();
        $hStmt = $pdo->prepare('SELECT status FROM health_records WHERE user_id = :user_id LIMIT 1');
        $hStmt->execute(['user_id' => $userId]);
        $status = $hStmt->fetchColumn();
        return $status !== false ? (string)$status : null;
    }

    public static function save(int $userId, int $appId, array $data): void
    {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare('SELECT id FROM health_records WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $upd = $pdo->prepare('
                UPDATE health_records SET
                    height = :height, weight = :weight, blood_type = :blood_type,
                    has_allergies = :has_allergies, has_asthma = :has_asthma, has_diabetes = :has_diabetes,
                    has_hypertension = :has_hypertension, has_heart_disease = :has_heart_disease,
                    has_physical_disability = :has_physical_disability, has_existing_condition = :has_existing_condition,
                    has_previous_surgery = :has_previous_surgery, has_maintenance_medication = :has_maintenance_medication,
                    has_hospitalized = :has_hospitalized, medical_conditions = :medical_conditions,
                    allergies_details = :allergies_details, current_medications = :current_medications,
                    other_notes = :other_notes, emergency_name = :emergency_name,
                    emergency_relationship = :emergency_relationship, emergency_contact = :emergency_contact,
                    status = "pending", admin_remarks = NULL
                WHERE id = :id
            ');
            $data['id'] = $existing['id'];
            $upd->execute($data);
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
            $data['user_id'] = $userId;
            $data['app_id'] = $appId;
            $ins->execute($data);
        }
    }
}
