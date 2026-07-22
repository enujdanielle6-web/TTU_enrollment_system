<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission(['applications.review', 'enrollment.finalize']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: review.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    $_SESSION['admin_error'] = 'Security validation failed. Please try again.';
    header('Location: review.php');
    exit;
}

$appId = (int) ($_POST['application_id'] ?? 0);
$userId = (int) ($_POST['user_id'] ?? 0);
$status = $_POST['status'] ?? '';
$feedback = trim($_POST['feedback'] ?? '');
$action = $_POST['action'] ?? '';

if ($action === 'update_subjects') {
    $appId = (int) ($_POST['application_id'] ?? 0);
    $subjects = $_POST['subjects'] ?? [];
    
    if ($appId <= 0) {
        $_SESSION['admin_error'] = 'Invalid application ID.';
        header('Location: review.php');
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $oldAppStmt = $pdo->prepare('SELECT academic_level FROM applications WHERE id = :id');
        $oldAppStmt->execute(['id' => $appId]);
        $academicLevel = $oldAppStmt->fetchColumn();

        if ($academicLevel === 'Senior High School') {
            $delStmt = $pdo->prepare('DELETE FROM shs_enrollments WHERE application_id = :app_id');
            $delStmt->execute(['app_id' => $appId]);
            
            if (!empty($subjects)) {
                $insStmt = $pdo->prepare('INSERT INTO shs_enrollments (application_id, subject_id) VALUES (:app_id, :sub_id)');
                foreach ($subjects as $subId => $secSubId) {
                    // For irregulars we just insert subjects for now
                    $insStmt->execute(['app_id' => $appId, 'sub_id' => (int)$subId]);
                }
            }
        } else {
            $delStmt = $pdo->prepare('DELETE FROM college_enrollments WHERE application_id = :app_id');
            $delStmt->execute(['app_id' => $appId]);
            
            if (!empty($subjects)) {
                $insStmt = $pdo->prepare('INSERT INTO college_enrollments (application_id, subject_id, college_section_id) VALUES (:app_id, :sub_id, :sec_id)');
                foreach ($subjects as $subId => $secSubId) {
                    $secSubIdVal = !empty($secSubId) ? (int)$secSubId : null;
                    // Note: secSubId here corresponds to college_section_id for irregular schedule selection
                    $insStmt->execute(['app_id' => $appId, 'sub_id' => (int)$subId, 'sec_id' => $secSubIdVal]);
                }
            }
        }
        
        $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
        $logStmt->execute([
            'user_id' => $_SESSION['user_id'],
            'icon' => 'bi-pencil-square text-primary',
            'title' => 'Subjects Updated',
            'description' => "Registrar updated the enrolled subjects for Application #{$appId}."
        ]);
        
        $pdo->commit();
        $_SESSION['admin_success'] = 'Subjects have been updated successfully.';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['admin_error'] = 'Database error while updating subjects.';
    }
    
    header("Location: application_detail.php?id={$appId}");
    exit;
}

$internalNotes = isset($_POST['internal_notes']) ? trim((string)$_POST['internal_notes']) : null;

$validStatuses = ['pending', 'under_review', 'correction_required', 'approved', 'rejected', 'enrolled'];

if ($appId <= 0 || !in_array($status, $validStatuses, true)) {
    $_SESSION['admin_error'] = 'Invalid application ID or status.';
    header('Location: review.php');
    exit;
}

try {
    // 0.5. Verify payment requirements if changing to enrolled
    if ($status === 'enrolled') {
        if (!hasPermission('enrollment.finalize')) {
            $_SESSION['admin_error'] = 'You do not have permission to finalize enrollments.';
            header('Location: review.php');
            exit;
        }

        $assStmt = $pdo->prepare('SELECT payment_status FROM student_assessments WHERE application_id = :app_id LIMIT 1');
        $assStmt->execute(['app_id' => $appId]);
        $assessment = $assStmt->fetch();
        
        if (!$assessment || $assessment['payment_status'] === 'unpaid') {
            $_SESSION['admin_error'] = 'Cannot enroll student. Payment requirements have not been met (must be at least partially paid).';
            header('Location: review.php');
            exit;
        }
    }

    // Fetch old application state
    $oldAppStmt = $pdo->prepare('SELECT document_submission_method, academic_level, status, admin_feedback, internal_notes FROM applications WHERE id = :id');
    $oldAppStmt->execute(['id' => $appId]);
    $oldApp = $oldAppStmt->fetch(PDO::FETCH_ASSOC);

    // Enforce Business Rules (Tasks 2 & 5)
    
    // Task 5: Lock workflow after assessment
    if (in_array($status, ['pending', 'correction_required'], true)) {
        $assCheckStmt = $pdo->prepare('SELECT id FROM student_assessments WHERE application_id = :app_id LIMIT 1');
        $assCheckStmt->execute(['app_id' => $appId]);
        if ($assCheckStmt->fetch()) {
            $_SESSION['admin_error'] = 'Cannot change status to ' . formatApplicationStatus($status) . ': An assessment has already been generated.';
            header('Location: application_detail.php?id=' . $appId);
            exit;
        }
    }

    // Task 2: Prevent approval if there are outstanding document corrections
    if ($status === 'approved' && $oldApp['document_submission_method'] !== 'on_campus') {
        $docCheckStmt = $pdo->prepare('SELECT id, status FROM application_documents WHERE application_id = :app_id');
        $docCheckStmt->execute(['app_id' => $appId]);
        $docs = $docCheckStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hasUnverified = false;
        foreach ($docs as $doc) {
            $docId = $doc['id'];
            $newDocStatus = $_POST['doc_status'][$docId] ?? $doc['status'];
            if ($newDocStatus !== 'verified') {
                $hasUnverified = true;
                break;
            }
        }
        
        if (empty($docs) || $hasUnverified) {
            $_SESSION['admin_error'] = 'Cannot approve application: All documents must be uploaded and verified first.';
            header('Location: application_detail.php?id=' . $appId);
            exit;
        }

        // Scholarship Integration: Prevent approval if scholarship is pending
        $scholCheckStmt = $pdo->prepare('
            SELECT status FROM scholarship_applications 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC LIMIT 1
        ');
        $scholCheckStmt->execute(['user_id' => $userId]);
        $latestScholarship = $scholCheckStmt->fetch();
        
        if ($latestScholarship && in_array($latestScholarship['status'], ['pending', 'under_review'])) {
            $_SESSION['admin_error'] = 'Cannot approve application: The applicant has a pending scholarship application that must be processed first.';
            header('Location: application_detail.php?id=' . $appId);
            exit;
        }
    }

    // 0. Process Section Assignment
    $assignSectionId = (int)($_POST['assign_section'] ?? 0);
    if ($assignSectionId > 0) {
        $stmt = $pdo->prepare('UPDATE applications SET section_id = :section_id WHERE id = :id');
        $stmt->execute(['section_id' => $assignSectionId, 'id' => $appId]);

        if ($oldApp['academic_level'] === 'Senior High School') {
            $delStmt = $pdo->prepare('DELETE FROM shs_enrollments WHERE application_id = :app_id');
            $delStmt->execute(['app_id' => $appId]);

            $subStmt = $pdo->prepare('
                SELECT subject_id 
                FROM shs_section_subjects 
                WHERE shs_section_id = :section_id
            ');
            $subStmt->execute(['section_id' => $assignSectionId]);
            $subjectsToEnroll = $subStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($subjectsToEnroll)) {
                $insSubStmt = $pdo->prepare('INSERT INTO shs_enrollments (application_id, subject_id, shs_section_id) VALUES (:app_id, :sub_id, :sec_id)');
                foreach ($subjectsToEnroll as $row) {
                    $insSubStmt->execute([
                        'app_id' => $appId, 
                        'sub_id' => $row['subject_id'],
                        'sec_id' => $assignSectionId
                    ]);
                }
            }
            
            $secStmt = $pdo->prepare('SELECT section_code FROM shs_sections WHERE id = :id');
            $secStmt->execute(['id' => $assignSectionId]);
            $secCode = $secStmt->fetchColumn();
        } else {
            // Check if the student already has a permanently assigned curriculum
            $userCurrStmt = $pdo->prepare('SELECT college_curriculum_id FROM users WHERE id = :id');
            $userCurrStmt->execute(['id' => $userId]);
            $studentCurriculumId = $userCurrStmt->fetchColumn();

            // Retrieve Section details
            $secStmt = $pdo->prepare('SELECT curriculum_id, year_level, semester, section_code FROM college_sections WHERE id = :id');
            $secStmt->execute(['id' => $assignSectionId]);
            $sectionData = $secStmt->fetch(PDO::FETCH_ASSOC);
            $secCode = $sectionData['section_code'];
            $sectionCurriculumId = $sectionData['curriculum_id'];

            if (!$studentCurriculumId) {
                // Determine the Active Curriculum for the student's program
                $activeCurrStmt = $pdo->prepare('
                    SELECT cc.id 
                    FROM college_curricula cc 
                    JOIN college_programs cp ON cc.program_id = cp.id 
                    WHERE cp.code = :strand AND cc.status = "active" 
                    ORDER BY cc.created_at DESC LIMIT 1
                ');
                $activeCurrStmt->execute(['strand' => $oldApp['strand']]);
                $studentCurriculumId = $activeCurrStmt->fetchColumn();

                // Fallback to the section's curriculum if no active curriculum is explicitly flagged
                if (!$studentCurriculumId && $sectionCurriculumId) {
                    $studentCurriculumId = $sectionCurriculumId;
                }

                if ($studentCurriculumId) {
                    // Permanently assign it to the student
                    $pdo->prepare('UPDATE users SET college_curriculum_id = :curr_id WHERE id = :id')
                        ->execute(['curr_id' => $studentCurriculumId, 'id' => $userId]);
                }
            }

            if ($studentCurriculumId) {
                // Update application to lock in the curriculum reference for this specific enrollment instance
                $appUpdStmt = $pdo->prepare('UPDATE applications SET college_curriculum_id = :curr_id WHERE id = :id');
                $appUpdStmt->execute(['curr_id' => $studentCurriculumId, 'id' => $appId]);
            }

            $delStmt = $pdo->prepare('DELETE FROM college_enrollments WHERE application_id = :app_id');
            $delStmt->execute(['app_id' => $appId]);

            // Retrieve Curriculum Subjects matching the Section's Year Level and Semester
            // IMPORTANT: We use the STUDENT'S permanently assigned curriculum, NOT the section's curriculum
            $subStmt = $pdo->prepare('
                SELECT subject_id 
                FROM college_curriculum_subjects 
                WHERE curriculum_id = :curriculum_id 
                  AND year_level = :year_level 
                  AND (semester = :semester OR semester IS NULL OR semester = "")
            ');
            $subStmt->execute([
                'curriculum_id' => $studentCurriculumId,
                'year_level' => $sectionData['year_level'],
                'semester' => $sectionData['semester']
            ]);
            $subjectsToEnroll = $subStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($subjectsToEnroll)) {
                $insSubStmt = $pdo->prepare('INSERT INTO college_enrollments (application_id, subject_id, college_section_id) VALUES (:app_id, :sub_id, :sec_id)');
                foreach ($subjectsToEnroll as $row) {
                    $insSubStmt->execute([
                        'app_id' => $appId, 
                        'sub_id' => $row['subject_id'],
                        'sec_id' => $assignSectionId
                    ]);
                }
            }
        }

        $logDocStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
        $logDocStmt->execute([
            'user_id' => $userId,
            'icon' => 'bi-diagram-3-fill text-primary',
            'title' => 'Section Assigned',
            'description' => "You have been assigned to section {$secCode}."
        ]);
    }

    // 1. Update Application Status, Feedback, and Internal Notes
    $stmt = $pdo->prepare('UPDATE applications SET status = :status, admin_feedback = :admin_feedback, internal_notes = :internal_notes WHERE id = :id');
    $stmt->execute([
        'status' => $status,
        'admin_feedback' => $feedback !== '' ? $feedback : null,
        'internal_notes' => $internalNotes !== '' ? $internalNotes : null,
        'id' => $appId
    ]);

    $newApp = [
        'status' => $status,
        'admin_feedback' => $feedback !== '' ? $feedback : null,
        'internal_notes' => $internalNotes !== '' ? $internalNotes : null
    ];

    // 1.2 Generate Student Number if Enrolled
    if ($status === 'enrolled') {
        $uStmt = $pdo->prepare('SELECT student_number FROM users WHERE id = :id LIMIT 1');
        $uStmt->execute(['id' => $userId]);
        $existingNumber = $uStmt->fetchColumn();

        if (empty($existingNumber)) {
            $newNumber = generateStudentNumber($pdo);
            $updUser = $pdo->prepare('UPDATE users SET student_number = :student_number WHERE id = :id');
            $updUser->execute(['student_number' => $newNumber, 'id' => $userId]);
            
            // Log it
            $logDocStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
            $logDocStmt->execute([
                'user_id' => $userId,
                'icon' => 'bi-person-vcard-fill text-success',
                'title' => 'Student Number Assigned',
                'description' => "Your official student number is {$newNumber}."
            ]);
        }
    }

    // 1.3 Auto-create Health Record if Approved
    if ($status === 'approved') {
        $healthCheckStmt = $pdo->prepare('SELECT id FROM health_records WHERE application_id = :app_id LIMIT 1');
        $healthCheckStmt->execute(['app_id' => $appId]);
        if (!$healthCheckStmt->fetch()) {
            $healthInsertStmt = $pdo->prepare('INSERT INTO health_records (user_id, application_id, status) VALUES (:user_id, :app_id, "pending")');
            $healthInsertStmt->execute(['user_id' => $userId, 'app_id' => $appId]);
        }
    }

    // 1.5. Update Individual Document verification statuses and comments
    $docStatuses = $_POST['doc_status'] ?? [];
    $docFeedbacks = $_POST['doc_feedback'] ?? [];
    
    if (!empty($docStatuses)) {
        $docUpdateStmt = $pdo->prepare('UPDATE application_documents SET status = :status, feedback = :feedback WHERE id = :id AND application_id = :app_id');
        $docSelectStmt = $pdo->prepare('SELECT document_name, status FROM application_documents WHERE id = :id LIMIT 1');
        
        foreach ($docStatuses as $docId => $docStatus) {
            $docId = (int)$docId;
            $docFeedback = trim((string)($docFeedbacks[$docId] ?? ''));
            
            // Fetch old status to see if it changed (for timeline logs)
            $docSelectStmt->execute(['id' => $docId]);
            $oldDoc = $docSelectStmt->fetch();
            
            $docUpdateStmt->execute([
                'status' => $docStatus,
                'feedback' => $docFeedback !== '' ? $docFeedback : null,
                'id' => $docId,
                'app_id' => $appId
            ]);
            
            if ($oldDoc && $oldDoc['status'] !== $docStatus) {
                // Log status transition to student timeline
                $docName = $oldDoc['document_name'];
                $mappedState = match($docStatus) {
                    'verified' => 'Verified / Approved',
                    'rejected' => 'Rejected / Needs Reupload',
                    default => 'Pending Review'
                };
                
                $logDocStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
                $logDocStmt->execute([
                    'user_id' => $userId,
                    'icon' => $docStatus === 'verified' ? 'bi-file-earmark-check-fill text-success' : 'bi-file-earmark-x-fill text-danger',
                    'title' => "Document Audit: {$docName}",
                    'description' => "Audit status updated to '{$mappedState}'" . ($docFeedback !== '' ? ". Comment: {$docFeedback}" : "")
                ]);
            }
        }
    }

    // 2. Process Assessment Generation
    // Automatically generate assessment if status is 'approved'
    $generateAssessment = ($status === 'approved');
    
    if ($generateAssessment) {
        // Check if an assessment already exists
        $assCheckStmt = $pdo->prepare('SELECT id FROM student_assessments WHERE application_id = :app_id LIMIT 1');
        $assCheckStmt->execute(['app_id' => $appId]);
        
        if (!$assCheckStmt->fetch()) {
            // Fetch applicant details to find matching template
            $appStmt = $pdo->prepare('SELECT academic_level, grade_level, strand FROM applications WHERE id = :id LIMIT 1');
            $appStmt->execute(['id' => $appId]);
            $appData = $appStmt->fetch();

            if ($appData) {
                // Fetch the exact template matching grade level and strand
                $ftStmt = $pdo->prepare('SELECT * FROM fee_templates WHERE grade_level = :grade_level AND strand = :strand LIMIT 1');
                $ftStmt->execute([
                    'grade_level' => $appData['grade_level'],
                    'strand' => $appData['strand']
                ]);
                $template = $ftStmt->fetch();
                
                if ($template) {
                    $academicLevel = $appData['academic_level'];
                    $feeTemplateId = (int)$template['id'];


                $tuitionFee = (float)$template['tuition_fee'];

                // Dynamic Tuition Calculation for College Students
                if ($academicLevel === 'College') {
                    // Fetch configured cost per unit
                    $settingStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'college_cost_per_unit' LIMIT 1");
                    $costPerUnitStr = $settingStmt->fetchColumn();
                    $costPerUnit = $costPerUnitStr !== false ? (float)$costPerUnitStr : 500.00;

                    // Fetch total enrolled units
                    $unitsStmt = $pdo->prepare('
                        SELECT SUM(s.units) 
                        FROM college_enrollments ce 
                        JOIN subjects s ON ce.subject_id = s.id 
                        WHERE ce.application_id = :app_id
                    ');
                    $unitsStmt->execute(['app_id' => $appId]);
                    $totalUnits = (int)$unitsStmt->fetchColumn();

                    if ($totalUnits > 0) {
                        $tuitionFee = $totalUnits * $costPerUnit;
                    }
                }

                $miscFee = (float)$template['miscellaneous_fee'];
                $regFee = (float)$template['registration_fee'];
                $labFee = (float)$template['laboratory_fee'];
                $otherFees = (float)$template['other_fees'];
                
                $totalAmount = $tuitionFee + $miscFee + $regFee + $labFee + $otherFees;

                // Check for approved scholarship
                $discountAmount = 0.00;
                $scholarshipId = null;
                $scholCheck = $pdo->prepare('
                    SELECT sa.scholarship_id, s.discount_type, s.discount_value 
                    FROM scholarship_applications sa 
                    JOIN scholarships s ON sa.scholarship_id = s.id 
                    WHERE sa.user_id = :user_id AND sa.status = "approved" 
                    ORDER BY sa.created_at DESC LIMIT 1
                ');
                $scholCheck->execute(['user_id' => $userId]);
                $approvedSchol = $scholCheck->fetch();
                
                if ($approvedSchol) {
                    $scholarshipId = $approvedSchol['scholarship_id'];
                    $discountValue = (float)$approvedSchol['discount_value'];
                    if ($approvedSchol['discount_type'] === 'percentage') {
                        $discountAmount = $totalAmount * ($discountValue / 100);
                    } else {
                        $discountAmount = $discountValue;
                    }
                }

                $netAmount = max(0, $totalAmount - $discountAmount);

                $insertAssStmt = $pdo->prepare('
                    INSERT INTO student_assessments 
                    (user_id, application_id, fee_template_id, scholarship_id, tuition_fee, miscellaneous_fee, registration_fee, laboratory_fee, other_fees, total_amount, discount_amount, net_amount)
                    VALUES 
                    (:user_id, :app_id, :fee_id, :scholarship_id, :tuition, :misc, :reg, :lab, :other, :total_amount, :discount_amount, :net_amount)
                ');
                $insertAssStmt->execute([
                    'user_id' => $userId,
                    'app_id' => $appId,
                    'fee_id' => $feeTemplateId,
                    'scholarship_id' => $scholarshipId,
                    'tuition' => $tuitionFee,
                    'misc' => $miscFee,
                    'reg' => $regFee,
                    'lab' => $labFee,
                    'other' => $otherFees,
                    'total_amount' => $totalAmount,
                    'discount_amount' => $discountAmount,
                    'net_amount' => $netAmount
                ]);
                
                // If there's an approved scholarship, add it to student_scholarships if not exists
                if ($scholarshipId) {
                    $assessmentId = $pdo->lastInsertId();
                    $insertStuSchol = $pdo->prepare('INSERT INTO student_scholarships (assessment_id, scholarship_id) VALUES (:assessment_id, :scholarship_id)');
                    $insertStuSchol->execute(['assessment_id' => $assessmentId, 'scholarship_id' => $scholarshipId]);
                }
                
                // Log Assessment Generation
                $logDocStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
                $logDocStmt->execute([
                    'user_id' => $userId,
                    'icon' => 'bi-cash-stack text-success',
                    'title' => "Financial Assessment Generated",
                    'description' => "Your financial assessment has been generated and is ready for review."
                ]);
            }
        }
        }
    }

    // 3. Generate Timeline Log for the Applicant
    $logIcon = match($status) {
        'approved' => 'bi-check-circle-fill text-success',
        'rejected' => 'bi-x-circle-fill text-danger',
        'correction_required' => 'bi-exclamation-triangle-fill text-warning',
        'enrolled' => 'bi-mortarboard-fill text-success',
        'under_review' => 'bi-search text-info',
        default => 'bi-info-circle-fill text-primary'
    };

    $statusTitle = formatApplicationStatus($status);
    $logTitle = "Application Status: {$statusTitle}";
    
    $logDescription = $feedback !== '' 
        ? "Admin Feedback: " . $feedback 
        : getApplicationStatusMessage($status);

    $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description) VALUES (:user_id, :ip_address, :affected_record, :icon, :title, :description)');
    $logStmt->execute([
        'user_id' => $userId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'affected_record' => "Application #$appId",
        'icon' => $logIcon,
        'title' => $logTitle,
        'description' => $logDescription
    ]);

    // Audit Log for Admin
    if ($oldApp && $oldApp['status'] !== $status) {
        logActivity(
            (int)$_SESSION['user_id'],
            'bi-clipboard-check-fill',
            'Application ' . ucfirst($status),
            "Updated application status to {$statusTitle}.",
            "Application #$appId",
            $oldApp,
            $newApp,
            $feedback
        );
    }

    $_SESSION['admin_success'] = "Application successfully updated to '{$statusTitle}'.";

} catch (PDOException $e) {
    error_log('Admin action failed: ' . $e->getMessage());
    $_SESSION['admin_error'] = 'A database error occurred: ' . $e->getMessage();
}

header("Location: application_detail.php?id={$appId}");
exit;

