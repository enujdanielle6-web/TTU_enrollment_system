<?php

namespace App\Models;

use App\Core\Database;

class Application extends BaseModel
{
    protected static string $table = 'applications';

    public static function findByUserId(int $userId)
    {
        $pdo = Database::getConnection();
        $statement = $pdo->prepare(
            'SELECT
                a.id,
                a.reference_number,
                a.status,
                a.academic_level,
                a.semester,
                a.nstp,
                a.grade_level,
                a.school_year,
                a.strand,
                a.contact_number,
                a.birth_date,
                a.gender,
                a.address,
                a.guardian_name,
                a.guardian_contact,
                a.document_submission_method,
                a.created_at,
                a.updated_at,
                u.first_name,
                u.last_name,
                u.email,
                u.student_number,
                u.created_at as user_created_at
             FROM applications a
             INNER JOIN users u ON u.id = a.user_id
             WHERE a.user_id = :user_id
             ORDER BY a.created_at DESC
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetch() ?: null;
    }

    public static function updateContactDetails(int $userId, string $contactNumber): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE applications SET contact_number = :contact_number WHERE user_id = :user_id');
        $stmt->execute([
            'contact_number' => $contactNumber,
            'user_id' => $userId
        ]);
    }
    public static function createFull(array $data): int
    {
        $pdo = Database::getConnection();
        
        // Generate unique reference number in format SIA-YYYY-XXXXXX
        $year = date('Y');
        $prefix = "SIA-{$year}-";
        $referenceNumber = '';
        $attempts = 0;
        
        while ($attempts < 10) {
            $randNumber = str_pad((string) rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $referenceNumber = $prefix . $randNumber;
            
            $checkStmt = $pdo->prepare('SELECT id FROM applications WHERE reference_number = :ref LIMIT 1');
            $checkStmt->execute(['ref' => $referenceNumber]);
            if (!$checkStmt->fetch()) {
                break;
            }
            $attempts++;
        }
        
        if ($referenceNumber === '') {
            throw new \RuntimeException('Failed to generate a unique reference number.');
        }

        $prevSchoolYear = '';
        if (!empty($data['academic_year_from']) || !empty($data['academic_year_to'])) {
            $prevSchoolYear = trim(($data['academic_year_from'] ?? '') . ' - ' . ($data['academic_year_to'] ?? ''), ' -');
        } elseif (!empty($data['last_school_year'])) {
            $prevSchoolYear = $data['last_school_year'];
        } elseif (!empty($data['previous_school_year'])) {
            $prevSchoolYear = $data['previous_school_year'];
        }

        $params = [
            'user_id' => (int)$data['user_id'],
            'reference_number' => $referenceNumber,
            'status' => $data['status'] ?? 'pending',
            'academic_level' => $data['academic_level'] ?? null,
            'grade_level' => $data['grade_level'] ?? '',
            'school_year' => $data['school_year'] ?? '',
            'semester' => $data['semester'] ?? null,
            'student_type' => $data['student_type'] ?? 'Regular',
            'strand' => $data['strand'] ?? null,
            'nstp' => $data['nstp'] ?? null,
            'section_id' => !empty($data['section_id']) ? (int)$data['section_id'] : null,
            'contact_number' => $data['contact_number'] ?? '',
            'telephone_number' => $data['telephone_number'] ?? null,
            'birth_date' => $data['birth_date'] ?? date('Y-m-d'),
            'gender' => $data['gender'] ?? 'male',
            'civil_status' => $data['civil_status'] ?? 'Single',
            'nationality' => $data['nationality'] ?? 'Filipino',
            'religion' => $data['religion'] ?? null,
            'place_of_birth' => $data['place_of_birth'] ?? '',
            'address_house_number' => $data['address_house_number'] ?? null,
            'address_street' => $data['address_street'] ?? null,
            'address_barangay' => $data['address_barangay'] ?? null,
            'address_city' => $data['address_city'] ?? '',
            'address_province' => $data['address_province'] ?? '',
            'address_zip' => $data['address_zip'] ?? null,
            'address' => $data['address'] ?? '',
            'guardian_name' => $data['guardian_name'] ?? ($data['father_name'] ?? ($data['mother_name'] ?? '')),
            'guardian_relationship' => $data['guardian_relationship'] ?? 'Guardian',
            'guardian_contact' => $data['guardian_contact'] ?? ($data['father_contact'] ?? ($data['mother_contact'] ?? '')),
            'previous_school' => $data['last_school_attended'] ?? ($data['previous_school'] ?? ''),
            'previous_school_year' => $prevSchoolYear,
            'previous_school_type' => $data['previous_school_level'] ?? ($data['previous_school_type'] ?? 'Senior High School'),
            'lrn' => $data['lrn'] ?? null,
            'emergency_name' => $data['emergency_contact_person'] ?? ($data['emergency_name'] ?? null),
            'emergency_relationship' => $data['emergency_contact_relationship'] ?? ($data['emergency_relationship'] ?? null),
            'emergency_contact' => $data['emergency_contact_number'] ?? ($data['emergency_contact'] ?? null),
        ];

        $insertQuery = '
            INSERT INTO applications (
                user_id, reference_number, status, academic_level, grade_level, school_year, semester, student_type, strand, nstp, section_id,
                contact_number, telephone_number, birth_date, gender, civil_status, nationality, religion, place_of_birth,
                address_house_number, address_street, address_barangay, address_city, address_province, address_zip, address,
                guardian_name, guardian_relationship, guardian_contact,
                previous_school, previous_school_year, previous_school_type, lrn,
                emergency_name, emergency_relationship, emergency_contact
            ) VALUES (
                :user_id, :reference_number, :status, :academic_level, :grade_level, :school_year, :semester, :student_type, :strand, :nstp, :section_id,
                :contact_number, :telephone_number, :birth_date, :gender, :civil_status, :nationality, :religion, :place_of_birth,
                :address_house_number, :address_street, :address_barangay, :address_city, :address_province, :address_zip, :address,
                :guardian_name, :guardian_relationship, :guardian_contact,
                :previous_school, :previous_school_year, :previous_school_type, :lrn,
                :emergency_name, :emergency_relationship, :emergency_contact
            )';
        
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute($params);
        
        $appId = (int) $pdo->lastInsertId();

        // Also save health record if provided
        if (!empty($data['special_needs']) || !empty($data['medical_conditions']) || !empty($data['allergies'])) {
            try {
                HealthRecord::save((int)$data['user_id'], $appId, [
                    'height' => null,
                    'weight' => null,
                    'blood_type' => null,
                    'has_allergies' => !empty($data['allergies']) ? 1 : 0,
                    'has_asthma' => 0,
                    'has_diabetes' => 0,
                    'has_hypertension' => 0,
                    'has_heart_disease' => 0,
                    'has_physical_disability' => !empty($data['special_needs']) ? 1 : 0,
                    'has_existing_condition' => !empty($data['medical_conditions']) ? 1 : 0,
                    'has_previous_surgery' => 0,
                    'has_maintenance_medication' => 0,
                    'has_hospitalized' => 0,
                    'medical_conditions' => $data['medical_conditions'] ?? null,
                    'allergies_details' => $data['allergies'] ?? null,
                    'current_medications' => null,
                    'other_notes' => $data['special_needs'] ?? null,
                    'emergency_name' => $params['emergency_name'] ?? 'Emergency Contact',
                    'emergency_relationship' => $params['emergency_relationship'] ?? 'Guardian',
                    'emergency_contact' => $params['emergency_contact'] ?? $params['contact_number']
                ]);
            } catch (\Exception $e) {
                error_log('Health record auto-save non-fatal error: ' . $e->getMessage());
            }
        }

        return $appId;
    }

    public static function updateFull(array $data): void
    {
        $pdo = Database::getConnection();
        
        $prevSchoolYear = '';
        if (!empty($data['academic_year_from']) || !empty($data['academic_year_to'])) {
            $prevSchoolYear = trim(($data['academic_year_from'] ?? '') . ' - ' . ($data['academic_year_to'] ?? ''), ' -');
        } elseif (!empty($data['last_school_year'])) {
            $prevSchoolYear = $data['last_school_year'];
        } elseif (!empty($data['previous_school_year'])) {
            $prevSchoolYear = $data['previous_school_year'];
        }

        $params = [
            'id' => (int)$data['id'],
            'status' => $data['status'] ?? 'pending',
            'academic_level' => $data['academic_level'] ?? null,
            'grade_level' => $data['grade_level'] ?? '',
            'school_year' => $data['school_year'] ?? '',
            'semester' => $data['semester'] ?? null,
            'student_type' => $data['student_type'] ?? 'Regular',
            'strand' => $data['strand'] ?? null,
            'nstp' => $data['nstp'] ?? null,
            'section_id' => !empty($data['section_id']) ? (int)$data['section_id'] : null,
            'contact_number' => $data['contact_number'] ?? '',
            'telephone_number' => $data['telephone_number'] ?? null,
            'birth_date' => $data['birth_date'] ?? date('Y-m-d'),
            'gender' => $data['gender'] ?? 'male',
            'civil_status' => $data['civil_status'] ?? 'Single',
            'nationality' => $data['nationality'] ?? 'Filipino',
            'religion' => $data['religion'] ?? null,
            'place_of_birth' => $data['place_of_birth'] ?? '',
            'address_house_number' => $data['address_house_number'] ?? null,
            'address_street' => $data['address_street'] ?? null,
            'address_barangay' => $data['address_barangay'] ?? null,
            'address_city' => $data['address_city'] ?? '',
            'address_province' => $data['address_province'] ?? '',
            'address_zip' => $data['address_zip'] ?? null,
            'address' => $data['address'] ?? '',
            'guardian_name' => $data['guardian_name'] ?? ($data['father_name'] ?? ($data['mother_name'] ?? '')),
            'guardian_relationship' => $data['guardian_relationship'] ?? 'Guardian',
            'guardian_contact' => $data['guardian_contact'] ?? ($data['father_contact'] ?? ($data['mother_contact'] ?? '')),
            'previous_school' => $data['last_school_attended'] ?? ($data['previous_school'] ?? ''),
            'previous_school_year' => $prevSchoolYear,
            'previous_school_type' => $data['previous_school_level'] ?? ($data['previous_school_type'] ?? 'Senior High School'),
            'lrn' => $data['lrn'] ?? null,
            'emergency_name' => $data['emergency_contact_person'] ?? ($data['emergency_name'] ?? null),
            'emergency_relationship' => $data['emergency_contact_relationship'] ?? ($data['emergency_relationship'] ?? null),
            'emergency_contact' => $data['emergency_contact_number'] ?? ($data['emergency_contact'] ?? null),
        ];

        $updateQuery = '
            UPDATE applications SET 
                status = :status, 
                academic_level = :academic_level,
                grade_level = :grade_level, 
                school_year = :school_year, 
                semester = :semester,
                student_type = :student_type,
                strand = :strand, 
                nstp = :nstp,
                section_id = :section_id,
                contact_number = :contact_number, 
                telephone_number = :telephone_number,
                birth_date = :birth_date, 
                gender = :gender, 
                civil_status = :civil_status, 
                nationality = :nationality, 
                religion = :religion, 
                place_of_birth = :place_of_birth, 
                address_house_number = :address_house_number, 
                address_street = :address_street, 
                address_barangay = :address_barangay, 
                address_city = :address_city, 
                address_province = :address_province, 
                address_zip = :address_zip, 
                address = :address, 
                guardian_name = :guardian_name, 
                guardian_relationship = :guardian_relationship, 
                guardian_contact = :guardian_contact, 
                previous_school = :previous_school, 
                previous_school_year = :previous_school_year, 
                previous_school_type = :previous_school_type, 
                lrn = :lrn, 
                emergency_name = :emergency_name, 
                emergency_relationship = :emergency_relationship, 
                emergency_contact = :emergency_contact
            WHERE id = :id
        ';
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute($params);
    }
}
