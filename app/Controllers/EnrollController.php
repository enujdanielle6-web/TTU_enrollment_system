<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Application;
use App\Models\Schedule;
use App\Core\Database;

class EnrollController extends BaseController
{
    public function showForm(Request $request, Response $response)
    {
        $userId = (int) $_SESSION['user_id'];
        
        $pdo = Database::getConnection();
        $globalEnrollStatus = getSystemSetting($pdo, 'enrollment_status', 'open');
        $activeYr = getSystemSetting($pdo, 'active_school_year', '2026-2027');
        $activeSem = getSystemSetting($pdo, 'active_semester', 'First');
        
        $userStmt = $pdo->prepare('SELECT first_name, last_name, email, created_at FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();
        
        if (!$user) {
            $response->redirect('/sia/auth/login.php');
            return;
        }

        $appStatement = $pdo->prepare('SELECT status FROM applications WHERE user_id = :user_id LIMIT 1');
        $appStatement->execute(['user_id' => $userId]);
        $existingStatus = $appStatement->fetchColumn();

        if ($existingStatus && !in_array($existingStatus, ['pending', 'correction_required'], true)) {
            $response->redirect('/sia/applicant/dashboard.php');
            return;
        }
        
        $errors = $_SESSION['enroll_errors'] ?? [];
        $old = $_SESSION['enroll_old'] ?? [];
        unset($_SESSION['enroll_errors'], $_SESSION['enroll_old']);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // We pass empty arrays for dropdowns because they are fetched dynamically via APIs in the frontend.
        return $this->render('applicant/enroll', [
            'userId' => $userId,
            'user' => $user,
            'globalEnrollStatus' => $globalEnrollStatus,
            'activeYr' => $activeYr,
            'activeSem' => $activeSem,
            'existingStatus' => $existingStatus,
            'errors' => $errors,
            'old' => $old
        ]);
    }

    public function processForm(Request $request, Response $response)
    {
        $userId = (int) $_SESSION['user_id'];
        $pdo = Database::getConnection();

        $academicLevel = trim((string) $request->input('academic_level', ''));
        $schoolYear = trim((string) $request->input('school_year', ''));
        $semester = trim((string) $request->input('semester', ''));
        $gradeLevel = trim((string) $request->input('grade_level', ''));
        $studentType = trim((string) $request->input('student_type', ''));
        $strand = trim((string) $request->input('strand', ''));
        $nstp = trim((string) $request->input('nstp', ''));
        $sectionId = trim((string) $request->input('section_id', ''));
        if ($sectionId === '') $sectionId = null;

        $selectedSubjects = $_POST['selected_subjects'] ?? [];

        $errors = [];
        $validAcademicLevels = ['Senior High School', 'College'];
        $validGradeLevels = [
            'Senior High School' => ['Grade 11', 'Grade 12'],
            'College' => ['1st Year', '2nd Year', '3rd Year', '4th Year']
        ];

        if ($academicLevel === 'Senior High School') {
            $semester = '';
            $studentType = 'Regular';
            $sectionId = null;
            $selectedSubjects = [];
        }

        $validStrands = [];
        if (in_array($academicLevel, $validAcademicLevels, true)) {
            try {
                if ($academicLevel === 'Senior High School') {
                    $strStmt = $pdo->prepare('SELECT code FROM shs_strands WHERE is_active = 1');
                } else {
                    $strStmt = $pdo->prepare('SELECT code FROM college_programs WHERE is_active = 1');
                }
                $strStmt->execute();
                $validStrands = $strStmt->fetchAll(\PDO::FETCH_COLUMN);
            } catch (\PDOException $e) {
                $errors[] = 'Database error fetching programs.';
            }
        }

        $isUpdate = false;
        $existingAppId = 0;
        try {
            $appStatement = $pdo->prepare('SELECT id, status FROM applications WHERE user_id = :user_id LIMIT 1');
            $appStatement->execute(['user_id' => $userId]);
            $existing = $appStatement->fetch();
            if ($existing) {
                if (!in_array($existing['status'], ['pending', 'correction_required'], true)) {
                    $response->redirect('/sia/applicant/dashboard.php');
                    return;
                }
                $isUpdate = true;
                $existingAppId = (int) $existing['id'];
            }
        } catch (\PDOException $exception) {
            $errors[] = 'Database verification error. Please try again.';
        }

        if ($studentType === 'Irregular') {
            if (empty($selectedSubjects)) {
                $errors[] = 'Irregular students must select at least one subject from the curriculum.';
            } else {
                $scheduleErrors = Schedule::validateSelectedSubjects($academicLevel, $selectedSubjects);
                $errors = array_merge($errors, $scheduleErrors);
            }
        }

        if (!in_array($academicLevel, $validAcademicLevels, true)) {
            $errors[] = 'Please select a valid academic level.';
        }
        if (!isset($validGradeLevels[$academicLevel]) || !in_array($gradeLevel, $validGradeLevels[$academicLevel], true)) {
            $errors[] = 'Please select a valid grade/year level for the chosen academic level.';
        }
        if (!in_array(strtolower($strand), array_map('strtolower', $validStrands), true)) {
            $errors[] = 'Please select a valid academic program.';
        }

        $contactNumber = trim((string) $request->input('contact_number', ''));
        $birthDate = trim((string) $request->input('birth_date', ''));
        $gender = trim((string) $request->input('gender', ''));
        $middleName = trim((string) $request->input('middle_name', ''));
        $suffix = trim((string) $request->input('suffix', ''));
        $placeOfBirth = trim((string) $request->input('place_of_birth', ''));
        $civilStatus = trim((string) $request->input('civil_status', ''));
        $nationality = trim((string) $request->input('nationality', ''));
        $religion = trim((string) $request->input('religion', ''));
        $telephoneNumber = trim((string) $request->input('telephone_number', ''));
        
        $addressHouseNumber = trim((string) $request->input('address_house_number', ''));
        $addressStreet = trim((string) $request->input('address_street', ''));
        $addressBarangay = trim((string) $request->input('address_barangay', ''));
        $addressCity = trim((string) $request->input('address_city', ''));
        $addressProvince = trim((string) $request->input('address_province', ''));
        $addressZip = trim((string) $request->input('address_zip', ''));
        $address = trim("{$addressHouseNumber} {$addressStreet}, {$addressBarangay}, {$addressCity}, {$addressProvince} {$addressZip}", " ,");

        $fatherName = trim((string) $request->input('father_name', ''));
        $fatherOccupation = trim((string) $request->input('father_occupation', ''));
        $fatherContact = trim((string) $request->input('father_contact', ''));

        $motherName = trim((string) $request->input('mother_name', ''));
        $motherOccupation = trim((string) $request->input('mother_occupation', ''));
        $motherContact = trim((string) $request->input('mother_contact', ''));

        $guardianName = trim((string) $request->input('guardian_name', ''));
        $guardianRelationship = trim((string) $request->input('guardian_relationship', ''));
        $guardianContact = trim((string) $request->input('guardian_contact', ''));

        $lastSchoolAttended = trim((string) $request->input('last_school_attended', ''));
        $lastSchoolAddress = trim((string) $request->input('last_school_address', ''));
        $previousSchoolLevel = trim((string) $request->input('previous_school_level', ''));
        $previousStrandCourse = trim((string) $request->input('previous_strand_course', ''));
        $academicYearFrom = trim((string) $request->input('academic_year_from', ''));
        $academicYearTo = trim((string) $request->input('academic_year_to', ''));
        $previousSchoolStatus = trim((string) $request->input('previous_school_status', ''));
        $lastSchoolYear = $academicYearTo; 
        $lrn = trim((string) $request->input('lrn', ''));

        $emergencyContactPerson = trim((string) $request->input('emergency_contact_person', ''));
        $emergencyContactRelationship = trim((string) $request->input('emergency_contact_relationship', ''));
        $emergencyContactNumber = trim((string) $request->input('emergency_contact_number', ''));

        $specialNeeds = trim((string) $request->input('special_needs', ''));
        $medicalConditions = trim((string) $request->input('medical_conditions', ''));
        $allergies = trim((string) $request->input('allergies', ''));

        // Basic Validations
        if ($contactNumber === '') $errors[] = 'Contact number is required.';
        if ($guardianName === '') $errors[] = 'Guardian name is required.';
        if ($guardianRelationship === '') $errors[] = 'Guardian relationship is required.';
        if ($guardianContact === '') $errors[] = 'Guardian contact number is required.';
        if ($birthDate === '' || strtotime($birthDate) === false) $errors[] = 'A valid birth date is required.';
        if ($placeOfBirth === '') $errors[] = 'Place of birth is required.';
        if ($civilStatus === '') $errors[] = 'Civil status is required.';
        if ($nationality === '') $errors[] = 'Nationality is required.';
        if ($addressHouseNumber === '') $errors[] = 'House number is required.';
        if ($addressStreet === '') $errors[] = 'Street address is required.';
        if ($addressBarangay === '') $errors[] = 'Barangay is required.';
        if ($addressCity === '') $errors[] = 'City/Municipality is required.';
        if ($addressProvince === '') $errors[] = 'Province is required.';
        if ($addressZip === '') $errors[] = 'ZIP Code is required.';
        if ($lastSchoolAttended === '') $errors[] = 'Last school attended is required.';
        if ($lastSchoolAddress === '') $errors[] = 'Last school address is required.';
        if ($previousSchoolLevel === '') $errors[] = 'Previous school level is required.';
        if (in_array($previousSchoolLevel, ['Senior High School', 'College']) && $previousStrandCourse === '') {
            $errors[] = 'Strand/Course is required for the selected previous school level.';
        }
        if ($academicYearFrom === '' || $academicYearTo === '') {
            $errors[] = 'Academic Year Attended (From and To) is required.';
        }
        if ($previousSchoolStatus === '') $errors[] = 'Previous school status is required.';
        
        if ($academicLevel === 'College' && $semester === '') $errors[] = 'Semester is required for College.';
        if ($academicLevel === 'College' && $studentType === 'Regular' && $sectionId === null) $errors[] = 'Please select an available section.';
        if ($academicLevel === 'College' && $gradeLevel === '1st Year' && $nstp === '') $errors[] = 'NSTP choice is required for First Year College.';
        
        if ($emergencyContactPerson === '') $errors[] = 'Emergency contact person is required.';
        if ($emergencyContactRelationship === '') $errors[] = 'Emergency contact relationship is required.';
        if ($emergencyContactNumber === '') $errors[] = 'Emergency contact number is required.';

        if ($guardianName === '' && $fatherName === '' && $motherName === '') {
            $errors[] = 'At least one parent or guardian must be provided.';
        }
        
        if ($lrn !== '' && !preg_match('/^\d{12}$/', $lrn)) $errors[] = 'Learner Reference Number (LRN) must be a 12-digit number.';
        if ($contactNumber !== '' && !preg_match('/^(09\d{9}|(\+639)\d{9})$/', $contactNumber)) $errors[] = 'Mobile contact number must be a valid 11-digit number starting with 09.';
        
        $oldData = array_merge($_POST, ['address' => $address]);
        
        if (!empty($selectedSubjects)) {
            $subjectIds = array_keys($selectedSubjects);
            $inQuery = implode(',', array_fill(0, count($subjectIds), '?'));
            $subStmt = $pdo->prepare("SELECT id, subject_code as code, subject_name as name, units FROM subjects WHERE id IN ($inQuery)");
            $subStmt->execute($subjectIds);
            $fetched = $subStmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($fetched as &$f) {
                $f['section_id'] = $selectedSubjects[$f['id']] ?? null;
            }
            $oldData['selected_subjects'] = $fetched;
        }

        if (!empty($errors)) {
            $_SESSION['enroll_errors'] = $errors;
            $_SESSION['enroll_old'] = $oldData;
            $response->redirect('/sia/applicant/enroll.php');
            return;
        }

        $appData = [
            'academic_level' => $academicLevel, 'grade_level' => $gradeLevel, 'school_year' => $schoolYear, 'semester' => $semester, 'strand' => $strand, 
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
        ];

        try {
            if ($isUpdate) {
                $appData['status'] = $existing['status'] === 'correction_required' ? 'under_review' : 'pending';
                $appData['id'] = $existingAppId;
                Application::updateFull($appData);

                $logDesc = $existing['status'] === 'correction_required' 
                    ? 'You successfully resubmitted your application with the requested corrections.' 
                    : 'You successfully updated your application details.';
                
                User::logActivity($userId, 'Application Updated', $logDesc, 'bi-arrow-repeat text-info');
            } else {
                $appData['user_id'] = $userId;
                $appData['status'] = 'pending';
                Application::createFull($appData);
                
                User::logActivity($userId, 'Application Submitted', 'You successfully completed the online enrollment application.', 'bi-file-earmark-check');
            }

            $response->redirect('/sia/applicant/status.php');
        } catch (\Exception $exception) {
            error_log('Enrollment insertion failed: ' . $exception->getMessage());
            $_SESSION['enroll_errors'] = ['Application submission failed. Please try again.'];
            $_SESSION['enroll_old'] = $oldData;
            $response->redirect('/sia/applicant/enroll.php');
        }
    }

    public function status(Request $request, Response $response)
    {
        $userId = (int) $_SESSION['user_id'];
        $pdo = Database::getConnection();

        $appStmt = $pdo->prepare('SELECT
            a.id,
            a.reference_number,
            a.status,
            a.grade_level,
            a.school_year,
            a.strand,
            a.document_submission_method,
            a.admin_feedback,
            a.created_at,
            a.updated_at,
            u.first_name,
            u.last_name,
            u.email
         FROM applications a
         INNER JOIN users u ON u.id = a.user_id
         WHERE a.user_id = :user_id
         ORDER BY a.created_at DESC
         LIMIT 1');
        $appStmt->execute(['user_id' => $userId]);
        $application = $appStmt->fetch() ?: null;

        if (!$application) {
            $response->redirect('/sia/applicant/dashboard.php');
            return;
        }

        $timestamps = getApplicationTimestamps($userId);
        $docMethod = $application['document_submission_method'] ?? 'online';
        $hasUploadedDocs = false;
        if ($application && $docMethod === 'online') {
            $hasUploadedDocs = \App\Models\ApplicationDocument::hasUploadedDocuments((int)$application['id']);
        }

        $timelineSteps = $application ? getApplicationTimelineSteps($application['status'], $docMethod, $timestamps, $hasUploadedDocs) : [];

        $healthStatus = null;
        if ($application && in_array($application['status'], ['approved', 'enrolled'])) {
            $healthStatus = \App\Models\HealthRecord::getStatus($userId);
            if ($application['status'] === 'approved') {
                foreach ($timelineSteps as &$step) {
                    if ($step['key'] === 'health_info') {
                        $step['state'] = $healthStatus ? 'completed' : 'active';
                    }
                    if ($step['key'] === 'medical_clearance') {
                        $step['state'] = ($healthStatus === 'verified') ? 'completed' : ($healthStatus ? 'active' : 'pending');
                    }
                    if ($step['key'] === 'scholarship') {
                        if ($healthStatus === 'verified') {
                            $step['state'] = 'active';
                            $scholStatus = \App\Models\ScholarshipApplication::getStatus($userId);
                            if ($scholStatus) {
                                $step['state'] = 'completed';
                            }
                        }
                    }
                    if ($step['key'] === 'cashier') {
                        $assessment = \App\Models\StudentAssessment::findByApplicationId((int)$application['id']);
                        if ($assessment) {
                            $step['state'] = in_array($assessment['payment_status'], ['paid', 'partial']) ? 'completed' : 'active';
                            foreach ($timelineSteps as &$innerStep) {
                                if ($innerStep['key'] === 'scholarship' && $innerStep['state'] !== 'completed') {
                                    $innerStep['state'] = 'completed';
                                }
                            }
                        }
                    }
                }
            }
        }

        $statusLabel = $application ? formatApplicationStatus($application['status']) : '';
        $statusBadgeClass = $application ? getApplicationStatusBadgeClass($application['status']) : '';
        $statusMessage = $application ? getApplicationStatusMessage($application['status']) : '';
        $adminFeedback = $application ? ($application['admin_feedback'] ?? null) : null;

        return $this->render('applicant/status', [
            'application' => $application,
            'timelineSteps' => $timelineSteps,
            'statusLabel' => $statusLabel,
            'statusBadgeClass' => $statusBadgeClass,
            'statusMessage' => $statusMessage,
            'adminFeedback' => $adminFeedback,
            'docMethod' => $docMethod
        ]);
    }
}



