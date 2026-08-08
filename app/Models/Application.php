<?php

namespace App\Models;

use App\Core\Database;

class Application
{
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

        $data['reference_number'] = $referenceNumber;

        $insertQuery = '
            INSERT INTO applications (
                user_id, reference_number, status, academic_level, grade_level, school_year, semester, strand, contact_number, birth_date, gender, address, 
                guardian_name, guardian_contact, middle_name, suffix, place_of_birth, civil_status, 
                nationality, religion, telephone_number, address_house_number, address_street, address_barangay, address_city, 
                address_province, address_zip, father_name, father_occupation, father_contact, 
                mother_name, mother_occupation, mother_contact, guardian_relationship, last_school_attended, 
                last_school_address, last_school_year, previous_school_level, previous_strand_course, 
                academic_year_from, academic_year_to, previous_school_status, lrn, student_type, nstp, section_id, emergency_contact_person, 
                emergency_contact_relationship, emergency_contact_number, special_needs, medical_conditions, allergies
             ) VALUES (
                :user_id, :reference_number, :status, :academic_level, :grade_level, :school_year, :semester, :strand, :contact_number, :birth_date, :gender, :address, 
                :guardian_name, :guardian_contact, :middle_name, :suffix, :place_of_birth, :civil_status, 
                :nationality, :religion, :telephone_number, :address_house_number, :address_street, :address_barangay, :address_city, 
                :address_province, :address_zip, :father_name, :father_occupation, :father_contact, 
                :mother_name, :mother_occupation, :mother_contact, :guardian_relationship, :last_school_attended, 
                :last_school_address, :last_school_year, :previous_school_level, :previous_strand_course, 
                :academic_year_from, :academic_year_to, :previous_school_status, :lrn, :student_type, :nstp, :section_id, :emergency_contact_person, 
                :emergency_contact_relationship, :emergency_contact_number, :special_needs, :medical_conditions, :allergies
             )';
        
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute($data);
        
        return (int) $pdo->lastInsertId();
    }

    public static function updateFull(array $data): void
    {
        $pdo = Database::getConnection();
        
        $updateQuery = '
            UPDATE applications SET 
                status = :status, 
                academic_level = :academic_level,
                grade_level = :grade_level, 
                school_year = :school_year, 
                semester = :semester,
                strand = :strand, 
                contact_number = :contact_number, birth_date = :birth_date, gender = :gender, address = :address, 
                guardian_name = :guardian_name, guardian_contact = :guardian_contact, middle_name = :middle_name, 
                suffix = :suffix, place_of_birth = :place_of_birth, civil_status = :civil_status, 
                nationality = :nationality, religion = :religion, telephone_number = :telephone_number, 
                address_house_number = :address_house_number, address_street = :address_street, 
                address_barangay = :address_barangay, address_city = :address_city, address_province = :address_province, 
                address_zip = :address_zip, father_name = :father_name, father_occupation = :father_occupation, 
                father_contact = :father_contact, mother_name = :mother_name, mother_occupation = :mother_occupation, 
                mother_contact = :mother_contact, guardian_relationship = :guardian_relationship, 
                last_school_attended = :last_school_attended, last_school_address = :last_school_address, 
                last_school_year = :last_school_year, previous_school_level = :previous_school_level, 
                previous_strand_course = :previous_strand_course, academic_year_from = :academic_year_from, 
                academic_year_to = :academic_year_to, previous_school_status = :previous_school_status, 
                lrn = :lrn, student_type = :student_type, nstp = :nstp, section_id = :section_id,
                emergency_contact_person = :emergency_contact_person, 
                emergency_contact_relationship = :emergency_contact_relationship, 
                emergency_contact_number = :emergency_contact_number, special_needs = :special_needs, 
                medical_conditions = :medical_conditions, allergies = :allergies
            WHERE id = :id
        ';
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute($data);
    }
}
