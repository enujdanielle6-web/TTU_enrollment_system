<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: enroll.php');
    exit;
}

verifyCsrfToken();

$userId = (int) $_SESSION['user_id'];

// Extract inputs
$strand = trim((string) ($_POST['strand'] ?? ''));
$contactNumber = trim((string) ($_POST['contact_number'] ?? ''));
$birthDate = trim((string) ($_POST['birth_date'] ?? ''));
$gender = trim((string) ($_POST['gender'] ?? ''));

// New extractions
$middleName = trim((string) ($_POST['middle_name'] ?? ''));
$suffix = trim((string) ($_POST['suffix'] ?? ''));
$placeOfBirth = trim((string) ($_POST['place_of_birth'] ?? ''));
$civilStatus = trim((string) ($_POST['civil_status'] ?? ''));
$nationality = trim((string) ($_POST['nationality'] ?? ''));
$religion = trim((string) ($_POST['religion'] ?? ''));

$telephoneNumber = trim((string) ($_POST['telephone_number'] ?? ''));

$addressHouseNumber = trim((string) ($_POST['address_house_number'] ?? ''));
$addressStreet = trim((string) ($_POST['address_street'] ?? ''));
$addressBarangay = trim((string) ($_POST['address_barangay'] ?? ''));
$addressCity = trim((string) ($_POST['address_city'] ?? ''));
$addressProvince = trim((string) ($_POST['address_province'] ?? ''));
$addressZip = trim((string) ($_POST['address_zip'] ?? ''));
$address = trim("{$addressHouseNumber} {$addressStreet}, {$addressBarangay}, {$addressCity}, {$addressProvince} {$addressZip}", " ,");

$fatherName = trim((string) ($_POST['father_name'] ?? ''));
$fatherOccupation = trim((string) ($_POST['father_occupation'] ?? ''));
$fatherContact = trim((string) ($_POST['father_contact'] ?? ''));

$motherName = trim((string) ($_POST['mother_name'] ?? ''));
$motherOccupation = trim((string) ($_POST['mother_occupation'] ?? ''));
$motherContact = trim((string) ($_POST['mother_contact'] ?? ''));

$guardianName = trim((string) ($_POST['guardian_name'] ?? ''));
$guardianRelationship = trim((string) ($_POST['guardian_relationship'] ?? ''));
$guardianContact = trim((string) ($_POST['guardian_contact'] ?? ''));

$lastSchoolAttended = trim((string) ($_POST['last_school_attended'] ?? ''));
$lastSchoolAddress = trim((string) ($_POST['last_school_address'] ?? ''));
$previousSchoolLevel = trim((string) ($_POST['previous_school_level'] ?? ''));
$previousStrandCourse = trim((string) ($_POST['previous_strand_course'] ?? ''));
$academicYearFrom = trim((string) ($_POST['academic_year_from'] ?? ''));
$academicYearTo = trim((string) ($_POST['academic_year_to'] ?? ''));
$previousSchoolStatus = trim((string) ($_POST['previous_school_status'] ?? ''));
$lastSchoolYear = $academicYearTo; // Automatically inferred from academicYearTo
$lrn = trim((string) ($_POST['lrn'] ?? ''));

$academicLevel = trim((string) ($_POST['academic_level'] ?? ''));
$schoolYear = trim((string) ($_POST['school_year'] ?? ''));
$semester = trim((string) ($_POST['semester'] ?? ''));
$gradeLevel = trim((string) ($_POST['grade_level'] ?? ''));
$studentType = trim((string) ($_POST['student_type'] ?? ''));
$nstp = trim((string) ($_POST['nstp'] ?? ''));
$sectionId = trim((string) ($_POST['section_id'] ?? ''));
if ($sectionId === '') $sectionId = null;

$emergencyContactPerson = trim((string) ($_POST['emergency_contact_person'] ?? ''));
$emergencyContactRelationship = trim((string) ($_POST['emergency_contact_relationship'] ?? ''));
$emergencyContactNumber = trim((string) ($_POST['emergency_contact_number'] ?? ''));

$specialNeeds = trim((string) ($_POST['special_needs'] ?? ''));
$medicalConditions = trim((string) ($_POST['medical_conditions'] ?? ''));
$allergies = trim((string) ($_POST['allergies'] ?? ''));
$selectedSubjects = $_POST['selected_subjects'] ?? [];

$errors = [];
$validGenders = ['male', 'female', 'other'];
$validAcademicLevels = ['Senior High School', 'College'];

$validGradeLevels = [
    'Senior High School' => ['Grade 11', 'Grade 12'],
    'College' => ['1st Year', '2nd Year', '3rd Year', '4th Year']
];

// SHS Override
if ($academicLevel === 'Senior High School') {
    $semester = '';
    $studentType = 'Regular';
    $sectionId = null;
    $selectedSubjects = [];
}

// Fetch valid strands for the selected academic level
$validStrands = [];
if (in_array($academicLevel, $validAcademicLevels, true)) {
    try {
        if ($academicLevel === 'Senior High School') {
            $strStmt = $pdo->prepare('SELECT code FROM shs_strands WHERE is_active = 1');
        } else {
            $strStmt = $pdo->prepare('SELECT code FROM college_programs WHERE is_active = 1');
        }
        $strStmt->execute();
        $validStrands = $strStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $errors[] = 'Database error fetching programs.';
    }
}
$validGenders = ['male', 'female', 'other'];

$isUpdate = false;
$existingAppId = 0;
// Check if application already exists
try {
    $appStatement = $pdo->prepare('SELECT id, status FROM applications WHERE user_id = :user_id LIMIT 1');
    $appStatement->execute(['user_id' => $userId]);
    $existing = $appStatement->fetch();
    if ($existing) {
        if (!in_array($existing['status'], ['pending', 'correction_required'], true)) {
            header('Location: dashboard.php');
            exit;
        }
        $isUpdate = true;
        $existingAppId = (int) $existing['id'];
    }
} catch (PDOException $exception) {
    error_log('Database check failed in enroll_process.php: ' . $exception->getMessage());
    $errors[] = 'Database verification error. Please try again.';
}

// Validations
if ($studentType === 'Irregular') {
    if (empty($selectedSubjects)) {
        $errors[] = 'Irregular students must select at least one subject from the curriculum.';
    } else {
        // Validation: Conflict Detection & Capacity
        try {
            // Build the query to fetch all schedules for the selected (section_id, subject_id) pairs
            $conditions = [];
            $params = [];
            foreach ($selectedSubjects as $subId => $secId) {
                if (!empty($secId)) {
                    if ($academicLevel === 'Senior High School') {
                        $conditions[] = '(shs_section_id = ? AND subject_id = ?)';
                    } else {
                        $conditions[] = '(college_section_id = ? AND subject_id = ?)';
                    }
                    $params[] = $secId;
                    $params[] = $subId;
                }
            }
            
            $offerings = [];
            if (!empty($conditions)) {
                $where = implode(' OR ', $conditions);
                if ($academicLevel === 'Senior High School') {
                    // Note: for SHS, the dropdown uses shs_section_id
                    $offStmt = $pdo->prepare("SELECT id, subject_id, day, start_time, end_time, capacity FROM shs_section_subjects WHERE $where");
                } else {
                    $offStmt = $pdo->prepare("SELECT id, subject_id, day, start_time, end_time, capacity FROM college_section_subjects WHERE $where");
                }
                $offStmt->execute($params);
                $offerings = $offStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // 1. Capacity Check
            if ($academicLevel === 'Senior High School') {
                $capStmt = $pdo->prepare("SELECT COUNT(*) FROM shs_enrollments WHERE shs_section_id = ? AND subject_id = ?");
            } else {
                $capStmt = $pdo->prepare("SELECT COUNT(*) FROM college_enrollments WHERE college_section_id = ? AND subject_id = ?");
            }
            foreach ($offerings as $off) {
                // Here we fetch count per section and subject
                $capStmt->execute([$off['id'], $off['subject_id']]);
                $enrolledCount = (int)$capStmt->fetchColumn();
                if ($enrolledCount >= (int)$off['capacity']) {
                    $errors[] = "Schedule #{$off['id']} has reached its maximum capacity.";
                }
            }

            // 2. Duplicate Subject Check & Max Units
            $subjectIds = [];
            $totalUnits = 0;
            
            // Fetch units for each offering
            $unitStmt = $pdo->prepare("SELECT units FROM subjects WHERE id = ?");
            
            foreach ($offerings as $off) {
                if (in_array($off['subject_id'], $subjectIds)) {
                    $errors[] = "You cannot enroll in the same subject (Subject ID: {$off['subject_id']}) multiple times.";
                }
                $subjectIds[] = $off['subject_id'];
                
                $unitStmt->execute([$off['subject_id']]);
                $units = (int)$unitStmt->fetchColumn();
                $totalUnits += $units;
            }
            
            $maxAllowedUnits = 24; // Common maximum allowed units
            if ($totalUnits > $maxAllowedUnits) {
                $errors[] = "You have selected $totalUnits units, which exceeds the maximum allowed limit of $maxAllowedUnits units.";
            }

            // 3. Conflict Check (Time Overlap)
            // Group by day to check overlaps
            $scheduleByDay = [];
            foreach ($offerings as $off) {
                if (empty($off['day']) || $off['day'] === 'TBA') continue;
                $scheduleByDay[$off['day']][] = [
                    'start' => strtotime($off['start_time']),
                    'end' => strtotime($off['end_time']),
                    'offering_id' => $off['id']
                ];
            }
            foreach ($scheduleByDay as $day => $times) {
                // simple O(n^2) overlap check since max subjects per day is tiny
                for ($i = 0; $i < count($times); $i++) {
                    for ($j = $i + 1; $j < count($times); $j++) {
                        $t1 = $times[$i];
                        $t2 = $times[$j];
                        if (max($t1['start'], $t2['start']) < min($t1['end'], $t2['end'])) {
                            $errors[] = "Schedule conflict on $day between schedules #{$t1['offering_id']} and #{$t2['offering_id']}.";
                            break 2;
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error during schedule validation.';
        }
    }
}

if (!in_array($academicLevel, $validAcademicLevels, true)) {
    $errors[] = 'Please select a valid academic level.';
}

if (!isset($validGradeLevels[$academicLevel]) || !in_array($gradeLevel, $validGradeLevels[$academicLevel], true)) {
    $errors[] = 'Please select a valid grade/year level for the chosen academic level.';
}

$strandLower = strtolower($strand);
$validStrandsLower = array_map('strtolower', $validStrands);
if (!in_array($strandLower, $validStrandsLower, true)) {
    $errors[] = 'Please select a valid academic program.';
}
if ($contactNumber === '') {
    $errors[] = 'Contact number is required.';
}
if ($guardianName === '') {
    $errors[] = 'Guardian name is required.';
}
if ($guardianRelationship === '') {
    $errors[] = 'Guardian relationship is required.';
}
if ($guardianContact === '') {
    $errors[] = 'Guardian contact number is required.';
}
if ($birthDate === '' || strtotime($birthDate) === false) {
    $errors[] = 'A valid birth date is required.';
} elseif (strtotime($birthDate) > time()) {
    $errors[] = 'Birth date cannot be a future date.';
}
if (!in_array($gender, $validGenders, true)) {
    $errors[] = 'Please select a valid gender.';
}
if ($placeOfBirth === '') { $errors[] = 'Place of birth is required.'; }
if ($civilStatus === '') { $errors[] = 'Civil status is required.'; }
if ($nationality === '') { $errors[] = 'Nationality is required.'; }
if ($addressHouseNumber === '') { $errors[] = 'House number is required.'; }
if ($addressStreet === '') { $errors[] = 'Street address is required.'; }
if ($addressBarangay === '') { $errors[] = 'Barangay is required.'; }
if ($addressCity === '') { $errors[] = 'City/Municipality is required.'; }
if ($addressProvince === '') { $errors[] = 'Province is required.'; }
if ($addressZip === '') { $errors[] = 'ZIP Code is required.'; }
if ($lastSchoolAttended === '') { $errors[] = 'Last school attended is required.'; }
if ($lastSchoolAddress === '') { $errors[] = 'Last school address is required.'; }
if ($previousSchoolLevel === '') { $errors[] = 'Previous school level is required.'; }
if (in_array($previousSchoolLevel, ['Senior High School', 'College']) && $previousStrandCourse === '') {
    $errors[] = 'Strand/Course is required for the selected previous school level.';
}
if ($academicYearFrom === '' || $academicYearTo === '') {
    $errors[] = 'Academic Year Attended (From and To) is required.';
} elseif ((int)$academicYearTo < (int)$academicYearFrom) {
    $errors[] = 'Academic Year "To" cannot be earlier than "From".';
}
if ($previousSchoolStatus === '') { $errors[] = 'Previous school status is required.'; }
if ($lastSchoolYear === '') { $errors[] = 'Last school year graduated/attended is required.'; }
if ($schoolYear === '') { $errors[] = 'School year is required.'; }

if ($academicLevel === 'College') {
    if ($semester === '') { $errors[] = 'Semester is required for College.'; }
    if (!in_array($semester, ['First', 'Second', 'Summer'], true)) { $errors[] = 'Invalid semester selected.'; }
} else {
    $semester = ''; // Clear semester for SHS
}
if ($gradeLevel === '') { $errors[] = 'Grade level is required.'; }
if ($academicLevel === 'College' && $studentType === '') { $errors[] = 'Student type is required.'; }

if ($academicLevel === 'College' && $studentType === 'Regular' && $sectionId === null) {
    $errors[] = 'Please select an available section.';
}

// Ensure sectionId is only for College Regular
if ($academicLevel !== 'College' || $studentType !== 'Regular') {
    $sectionId = null;
}

if ($academicLevel === 'College' && $gradeLevel === '1st Year') {
    if ($nstp === '') { $errors[] = 'NSTP choice is required for First Year College.'; }
    elseif (!in_array($nstp, ['CWTS', 'ROTC', 'LTS'], true)) { $errors[] = 'Invalid NSTP choice.'; }
} else {
    $nstp = null; // Ensure null if not 1st Year College
}
if ($emergencyContactPerson === '') { $errors[] = 'Emergency contact person is required.'; }
if ($emergencyContactRelationship === '') { $errors[] = 'Emergency contact relationship is required.'; }
if ($emergencyContactNumber === '') { $errors[] = 'Emergency contact number is required.'; }

if ($guardianName === '' && $fatherName === '' && $motherName === '') {
    $errors[] = 'At least one parent or guardian must be provided.';
}

// Additional Security & Integrity Validations
if ($lrn !== '' && !preg_match('/^\d{12}$/', $lrn)) {
    $errors[] = 'Learner Reference Number (LRN) must be a 12-digit number.';
}
if ($contactNumber !== '' && !preg_match('/^(09\d{9}|(\+639)\d{9})$/', $contactNumber)) {
    $errors[] = 'Mobile contact number must be a valid 11-digit number starting with 09 (e.g., 09123456789).';
}
if ($guardianContact !== '' && !preg_match('/^(09\d{9}|(\+639)\d{9})$/', $guardianContact)) {
    $errors[] = 'Guardian contact number must be a valid 11-digit number starting with 09.';
}
if ($emergencyContactNumber !== '' && !preg_match('/^(09\d{9}|(\+639)\d{9})$/', $emergencyContactNumber)) {
    $errors[] = 'Emergency contact number must be a valid 11-digit number starting with 09.';
}

// Ensure the old session array contains everything for UX
$oldData = [
    'strand' => $strand,
    'contact_number' => $contactNumber,
    'birth_date' => $birthDate,
    'gender' => $gender,
    'address_street' => $addressStreet,
    'address_barangay' => $addressBarangay,
    'address_city' => $addressCity,
    'address_province' => $addressProvince,
    'address_zip' => $addressZip,
    'address_house_number' => $addressHouseNumber,
    'middle_name' => $middleName,
    'suffix' => $suffix,
    'place_of_birth' => $placeOfBirth,
    'civil_status' => $civilStatus,
    'nationality' => $nationality,
    'religion' => $religion,
    'telephone_number' => $telephoneNumber,
    'father_name' => $fatherName,
    'father_occupation' => $fatherOccupation,
    'father_contact' => $fatherContact,
    'mother_name' => $motherName,
    'mother_occupation' => $motherOccupation,
    'mother_contact' => $motherContact,
    'guardian_name' => $guardianName,
    'guardian_relationship' => $guardianRelationship,
    'guardian_contact' => $guardianContact,
    'last_school_attended' => $lastSchoolAttended,
    'last_school_address' => $lastSchoolAddress,
    'previous_school_level' => $previousSchoolLevel,
    'previous_strand_course' => $previousStrandCourse,
    'academic_year_from' => $academicYearFrom,
    'academic_year_to' => $academicYearTo,
    'previous_school_status' => $previousSchoolStatus,
    'last_school_year' => $lastSchoolYear,
    'lrn' => $lrn,
    'school_year' => $schoolYear,
    'semester' => $semester,
    'grade_level' => $gradeLevel,
    'student_type' => $studentType,
    'nstp' => $nstp,
    'emergency_contact_person' => $emergencyContactPerson,
    'emergency_contact_relationship' => $emergencyContactRelationship,
    'emergency_contact_number' => $emergencyContactNumber,
    'special_needs' => $specialNeeds,
    'medical_conditions' => $medicalConditions,
    'allergies' => $allergies,
    'section_id' => $sectionId,
    'selected_subjects' => []
];

if (!empty($selectedSubjects)) {
    $subjectIds = array_keys($selectedSubjects);
    $inQuery = implode(',', array_fill(0, count($subjectIds), '?'));
    $subStmt = $pdo->prepare("SELECT id, subject_code as code, subject_name as name, units FROM subjects WHERE id IN ($inQuery)");
    $subStmt->execute($subjectIds);
    $fetched = $subStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Attach the section_id to the oldData so the JS can rebuild the cart
    foreach ($fetched as &$f) {
        $f['section_id'] = $selectedSubjects[$f['id']] ?? null;
    }
    $oldData['selected_subjects'] = $fetched;
}

if (!empty($errors)) {
    $_SESSION['enroll_errors'] = $errors;
    $_SESSION['enroll_old'] = $oldData;
    header('Location: enroll.php');
    exit;
}

try {
    if ($isUpdate) {
        $newStatus = $existing['status'] === 'correction_required' ? 'under_review' : 'pending';
        
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

        $updateStmt->execute([
            'id' => $existingAppId,
            'status' => $newStatus,
            'academic_level' => $academicLevel,
            'grade_level' => $gradeLevel, 'school_year' => $schoolYear, 'semester' => $semester, 'strand' => $strand, 
            'contact_number' => $contactNumber, 'birth_date' => $birthDate, 'gender' => $gender, 'address' => $address, 
            'guardian_name' => $guardianName, 'guardian_contact' => $guardianContact, 'middle_name' => $middleName, 
            'suffix' => $suffix, 'place_of_birth' => $placeOfBirth, 'civil_status' => $civilStatus, 
            'nationality' => $nationality, 'religion' => $religion, 'telephone_number' => $telephoneNumber, 
            'address_house_number' => $addressHouseNumber, 'address_street' => $addressStreet, 
            'address_barangay' => $addressBarangay, 'address_city' => $addressCity, 'address_province' => $addressProvince, 
            'address_zip' => $addressZip, 'father_name' => $fatherName, 'father_occupation' => $fatherOccupation, 
            'father_contact' => $fatherContact, 'mother_name' => $motherName, 'mother_occupation' => $motherOccupation, 
            'mother_contact' => $motherContact, 'guardian_relationship' => $guardianRelationship, 
            'last_school_attended' => $lastSchoolAttended, 'last_school_address' => $lastSchoolAddress, 
            'last_school_year' => $lastSchoolYear, 'previous_school_level' => $previousSchoolLevel, 
            'previous_strand_course' => $previousStrandCourse, 'academic_year_from' => $academicYearFrom, 
            'academic_year_to' => $academicYearTo, 'previous_school_status' => $previousSchoolStatus, 
            'lrn' => $lrn, 'student_type' => $studentType, 'nstp' => $nstp, 'section_id' => $sectionId,
            'emergency_contact_person' => $emergencyContactPerson, 'emergency_contact_relationship' => $emergencyContactRelationship, 
            'emergency_contact_number' => $emergencyContactNumber, 'special_needs' => $specialNeeds, 
            'medical_conditions' => $medicalConditions, 'allergies' => $allergies
        ]);

        $logDesc = $existing['status'] === 'correction_required' 
            ? 'You successfully resubmitted your application with the requested corrections.' 
            : 'You successfully updated your application details.';

        $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
        $logStmt->execute([
            'user_id' => $userId,
            'icon' => 'bi-arrow-repeat text-info',
            'title' => 'Application Updated',
            'description' => $logDesc,
        ]);

        $applicationId = $existingAppId;

    } else {
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
            throw new RuntimeException('Failed to generate a unique reference number.');
        }

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
        
        $insertStmt->execute([
            'user_id' => $userId,
            'reference_number' => $referenceNumber,
            'status' => 'pending',
            'academic_level' => $academicLevel,
            'strand' => $strand,
            'contact_number' => $contactNumber,
            'birth_date' => $birthDate,
            'gender' => $gender,
            'address' => $address,
            'guardian_name' => $guardianName,
            'guardian_contact' => $guardianContact,
            'middle_name' => $middleName,
            'suffix' => $suffix,
            'place_of_birth' => $placeOfBirth,
            'civil_status' => $civilStatus,
            'nationality' => $nationality,
            'religion' => $religion,
            'telephone_number' => $telephoneNumber,
            'address_house_number' => $addressHouseNumber,
            'address_street' => $addressStreet,
            'address_barangay' => $addressBarangay,
            'address_city' => $addressCity,
            'address_province' => $addressProvince,
            'address_zip' => $addressZip,
            'father_name' => $fatherName,
            'father_occupation' => $fatherOccupation,
            'father_contact' => $fatherContact,
            'mother_name' => $motherName,
            'mother_occupation' => $motherOccupation,
            'mother_contact' => $motherContact,
            'guardian_relationship' => $guardianRelationship,
            'last_school_attended' => $lastSchoolAttended,
            'last_school_address' => $lastSchoolAddress,
            'last_school_year' => $lastSchoolYear,
            'previous_school_level' => $previousSchoolLevel,
            'previous_strand_course' => $previousStrandCourse,
            'academic_year_from' => $academicYearFrom,
            'academic_year_to' => $academicYearTo,
            'previous_school_status' => $previousSchoolStatus,
            'lrn' => $lrn,
            'school_year' => $schoolYear,
            'semester' => $semester,
            'grade_level' => $gradeLevel,
            'student_type' => $studentType,
            'nstp' => $nstp,
            'section_id' => $sectionId,
            'emergency_contact_person' => $emergencyContactPerson,
            'emergency_contact_relationship' => $emergencyContactRelationship,
            'emergency_contact_number' => $emergencyContactNumber,
            'special_needs' => $specialNeeds,
            'medical_conditions' => $medicalConditions,
            'allergies' => $allergies
        ]);

        $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
        $logStmt->execute([
            'user_id' => $userId,
            'icon' => 'bi-file-earmark-check',
            'title' => 'Application Submitted',
            'description' => 'You successfully completed the online enrollment application. Ref: ' . $referenceNumber,
        ]);
        
        $applicationId = $pdo->lastInsertId();
    }

    // Note: Curriculum-based subject generation has been moved to the Registrar's application_process.php.
    // The applicant simply registers their intent to enroll here.

    header('Location: status.php');
    exit;
} catch (Exception $exception) {
    error_log('Enrollment insertion failed: ' . $exception->getMessage());
    $_SESSION['enroll_errors'] = ['Application submission failed. Please try again.'];
    $_SESSION['enroll_old'] = $oldData;
    header('Location: enroll.php');
    exit;
}
