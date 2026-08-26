<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\HealthRecord;
use App\Models\StudentAssessment;
use App\Models\ScholarshipApplication;
use App\Core\Database;

class ApplicantController extends BaseController
{
    public function dashboard(Request $request, Response $response)
    {
        // Require legacy procedural functions for complex UI components (Strangler Fig Pattern)
        $userId = (int) $_SESSION['user_id'];
        $fetchError = null;
        
        if (isset($_SESSION['error_msg'])) {
            $fetchError = $_SESSION['error_msg'];
            unset($_SESSION['error_msg']);
        }

        $application = null;
        $documents = [];
        $activities = [];
        $announcements = [];
        
        $user_first_name = '';
        $user_last_name = '';
        $user_email = '';
        $user_student_number = null;
        $user_created_at = '';
        
        try {
            $application = Application::findByUserId($userId);
            
            if ($application === null) {
                // If no application yet, get basic user details
                $pdo = Database::getConnection();
                $userStmt = $pdo->prepare('SELECT first_name, last_name, email, student_number, created_at FROM users WHERE id = :id LIMIT 1');
                $userStmt->execute(['id' => $userId]);
                $userBasic = $userStmt->fetch();
                if ($userBasic) {
                    $user_first_name = $userBasic['first_name'];
                    $user_last_name = $userBasic['last_name'];
                    $user_email = $userBasic['email'];
                    $user_student_number = $userBasic['student_number'] ?? null;
                    $user_created_at = $userBasic['created_at'];
                }
            } else {
                $user_first_name = $application['first_name'];
                $user_last_name = $application['last_name'];
                $user_email = $application['email'];
                $user_student_number = $application['student_number'] ?? null;
                $user_created_at = $application['user_created_at'];
                
                $documents = ApplicationDocument::findByApplicationId((int)$application['id']);
            }
            
            $activities = ActivityLog::findByUserId($userId);
            $announcements = Announcement::getActiveAnnouncements();
            
        } catch (\PDOException $exception) {
            error_log('Applicant dashboard fetch failed: ' . $exception->getMessage());
            $fetchError = 'Unable to load your dashboard. Please try again later.';
        }

        // Logic to format timeline steps exactly as in legacy dashboard
        $statusLabel = $application ? formatApplicationStatus($application['status']) : 'Not Submitted';
        $statusBadgeClass = $application ? getApplicationStatusBadgeClass($application['status']) : 'bg-secondary';
        
        $timestamps = getApplicationTimestamps($userId);
        
        $hasUploadedDocs = false;
        if ($application && ($application['document_submission_method'] ?? 'online') === 'online') {
            $hasUploadedDocs = ApplicationDocument::hasUploadedDocuments((int)$application['id']);
        }
        
        $timelineSteps = getApplicationTimelineSteps($application ? $application['status'] : 'not_started', $application['document_submission_method'] ?? 'online', $timestamps, $hasUploadedDocs);
        
        $completionPercentage = 10;
        $detailedChecklist = [];
        
        if ($application) {
            $completionPercentage += 20;
            $detailedChecklist = getDetailedChecklist((int)$application['id']);
            
            if ($application['document_submission_method'] === 'on_campus') {
                $completionPercentage += 30;
            } else {
                $uploadedCount = 0;
                $totalDocs = count($detailedChecklist) > 0 ? count($detailedChecklist) : 4;
                foreach ($detailedChecklist as $doc) {
                    if ($doc['status'] === 'Uploaded' || $doc['status'] === 'Verified') {
                        $uploadedCount++;
                    }
                }
                $completionPercentage += (int) (($uploadedCount / $totalDocs) * 30);
            }
        
            if ($application['status'] === 'approved') {
                $completionPercentage = 80;
            } elseif ($application['status'] === 'enrolled') {
                $completionPercentage = 100;
            }
            if ($completionPercentage > 100) $completionPercentage = 100;
        }

        $healthStatus = null;
        if ($application && in_array($application['status'], ['approved', 'enrolled'])) {
            $healthStatus = HealthRecord::getStatus($userId);
            if ($application['status'] === 'enrolled') {
                $completionPercentage = 100;
                foreach ($timelineSteps as &$step) {
                    if ($step['key'] !== 'correction') {
                        $step['state'] = 'completed';
                    }
                }
                unset($step);
            } elseif ($application['status'] === 'approved') {
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
                            $scholStatus = ScholarshipApplication::getStatus($userId);
                            if ($scholStatus) {
                                $step['state'] = 'completed';
                            }
                        }
                    }
                    if ($step['key'] === 'cashier') {
                        $assessment = StudentAssessment::findByApplicationId((int)$application['id']);
                        if ($assessment) {
                            $step['state'] = in_array($assessment['payment_status'], ['paid', 'partial']) ? 'completed' : 'active';
                            // Check inner scholarship step
                            foreach ($timelineSteps as &$innerStep) {
                                if ($innerStep['key'] === 'scholarship' && $innerStep['state'] !== 'completed') {
                                    $innerStep['state'] = 'completed';
                                }
                            }
                            if (in_array($assessment['payment_status'], ['paid', 'partial'])) {
                                foreach ($timelineSteps as &$enrolledStep) {
                                    if ($enrolledStep['key'] === 'enrolled') {
                                        $enrolledStep['state'] = 'completed';
                                    }
                                }
                                unset($enrolledStep);
                            }
                        }
                    }
                }
                unset($step);
            }
        }

        $pdo = Database::getConnection();
        $globalEnrollStatus = getSystemSetting($pdo, 'enrollment_status', 'open');
        $activeYr = getSystemSetting($pdo, 'active_school_year', '2026-2027');

        return $this->render('applicant/dashboard', [
            'userId' => $userId,
            'application' => $application,
            'fetchError' => $fetchError,
            'documents' => $documents,
            'activities' => $activities,
            'announcements' => $announcements,
            'user_first_name' => $user_first_name,
            'user_last_name' => $user_last_name,
            'user_email' => $user_email,
            'user_student_number' => $user_student_number,
            'user_created_at' => $user_created_at,
            'statusLabel' => $statusLabel,
            'statusBadgeClass' => $statusBadgeClass,
            'timelineSteps' => $timelineSteps,
            'completionPercentage' => $completionPercentage,
            'detailedChecklist' => $detailedChecklist,
            'healthStatus' => $healthStatus,
            'globalEnrollStatus' => $globalEnrollStatus,
            'activeYr' => $activeYr
        ]);
    }

    public function profile(Request $request, Response $response)
    {
        $userId = (int) $_SESSION['user_id'];
        
        $successMsg = $_SESSION['profile_success'] ?? '';
        $errors = $_SESSION['profile_errors'] ?? [];
        $old = $_SESSION['profile_old'] ?? [];
        unset($_SESSION['profile_success'], $_SESSION['profile_errors'], $_SESSION['profile_old']);
        
        $fetchError = null;
        $user = null;
        $application = null;
        
        try {
            $user = User::find($userId);
            
            if ($user) {
                $applications = Application::where('user_id', $userId);
                $application = !empty($applications) ? $applications[0] : null;
            }
        } catch (\PDOException $e) {
            error_log('Profile page fetch failed: ' . $e->getMessage());
            $fetchError = 'Unable to retrieve profile details at this time.';
        }
        
        if (!$user) {
            $response->redirect('/sia/auth/login.php');
            return;
        }

        return $this->render('applicant/profile', [
            'user' => $user,
            'application' => $application,
            'successMsg' => $successMsg,
            'errors' => $errors,
            'old' => $old,
            'fetchError' => $fetchError
        ]);
    }

    public function updateProfile(Request $request, Response $response)
    {
        $userId = (int) $_SESSION['user_id'];
        $action = $request->input('action');
        $pdo = Database::getConnection();

        $errors = [];
        $successMsg = '';
        
        try {
            if ($action === 'update_account') {
                $firstName = trim((string) $request->input('first_name', ''));
                $lastName = trim((string) $request->input('last_name', ''));
                $email = trim((string) $request->input('email', ''));

                if (empty($firstName)) $errors[] = 'First name is required.';
                if (empty($lastName)) $errors[] = 'Last name is required.';
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
                
                $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
                $checkStmt->execute(['email' => $email, 'id' => $userId]);
                if ($checkStmt->fetch()) {
                    $errors[] = 'Email is already taken by another account.';
                }

                if (empty($errors)) {
                    $updStmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?');
                    $updStmt->execute([$firstName, $lastName, $email, $userId]);
                    
                    $_SESSION['user_first_name'] = $firstName;
                    $_SESSION['user_last_name'] = $lastName;
                    $_SESSION['user_name'] = "$firstName $lastName";
                    $_SESSION['user_email'] = $email;
                    
                    $successMsg = 'Account details updated successfully.';
                    User::logActivity($userId, 'Profile Updated', 'Updated account information.', 'bi-person-check');
                } else {
                    $_SESSION['profile_old'] = ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email];
                }

            } elseif ($action === 'update_contact') {
                $contactNumber = trim((string) $request->input('contact_number', ''));
                if (!preg_match('/^09\d{9}$/', $contactNumber)) {
                    $errors[] = 'Must be a valid 11-digit mobile number starting with 09.';
                }
                
                if (empty($errors)) {
                    Application::updateContactDetails($userId, $contactNumber);
                    $successMsg = 'Contact information updated successfully.';
                    User::logActivity($userId, 'Contact Info Updated', 'Updated mobile number.', 'bi-telephone');
                } else {
                    $_SESSION['profile_old'] = ['contact_number' => $contactNumber];
                }
                
            } elseif ($action === 'change_password') {
                $currentPass = (string) $request->input('current_password', '');
                $newPass = (string) $request->input('new_password', '');
                $confPass = (string) $request->input('confirm_password', '');
                
                $passStmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
                $passStmt->execute([$userId]);
                $userRecord = $passStmt->fetch();
                
                if (!$userRecord || !password_verify($currentPass, $userRecord['password'])) {
                    $errors[] = 'Current password is incorrect.';
                }
                if (strlen($newPass) < 8) {
                    $errors[] = 'New password must be at least 8 characters.';
                }
                if ($newPass !== $confPass) {
                    $errors[] = 'Passwords do not match.';
                }

                if (empty($errors)) {
                    $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                    $updStmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                    $updStmt->execute([$hashed, $userId]);
                    $successMsg = 'Password changed successfully.';
                    User::logActivity($userId, 'Security Update', 'Changed account password.', 'bi-shield-lock');
                }
            } else {
                $errors[] = 'Invalid action requested.';
            }
        } catch (\PDOException $e) {
            error_log('Profile update failed: ' . $e->getMessage());
            $errors[] = 'A database error occurred. Please try again.';
        }

        if (!empty($errors)) {
            $_SESSION['profile_errors'] = $errors;
        } else {
            $_SESSION['profile_success'] = $successMsg;
        }

        $response->redirect('/sia/applicant/profile.php');
    }


    public function assessment(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$userId = (int) $_SESSION['user_id'];

// Check user application status and health status for notification banner
$appCheckStmt = $pdo->prepare('SELECT status FROM applications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1');
$appCheckStmt->execute(['user_id' => $userId]);
$userAppStatus = $appCheckStmt->fetchColumn();

$healthStatus = null;
if ($userAppStatus && in_array($userAppStatus, ['approved', 'enrolled'], true)) {
    $healthStatus = HealthRecord::getStatus($userId);
}

// Fetch the applicant's assessment
$assessment = null;
$payments = [];
try {
    $stmt = $pdo->prepare('
        SELECT sa.*, a.reference_number, a.academic_level, a.grade_level, a.strand, a.school_year, a.semester, s.name as scholarship_name, ft.is_per_unit, ft.tuition_fee as template_tuition_rate
        FROM student_assessments sa
        INNER JOIN applications a ON sa.application_id = a.id
        LEFT JOIN scholarships s ON sa.scholarship_id = s.id
        LEFT JOIN fee_templates ft ON sa.fee_template_id = ft.id
        WHERE sa.user_id = :user_id
        ORDER BY sa.created_at DESC LIMIT 1
    ');
    $stmt->execute(['user_id' => $userId]);
    $assessment = $stmt->fetch();

    if ($assessment) {
        $payStmt = $pdo->prepare('SELECT * FROM payment_records WHERE assessment_id = :assessment_id ORDER BY created_at DESC');
        $payStmt->execute(['assessment_id' => $assessment['id']]);
        $payments = $payStmt->fetchAll();
        
        // Fetch Enrolled / Curriculum Subjects
        $enrolledSubjects = [];
        if ($assessment['academic_level'] === 'College') {
            $subStmt = $pdo->prepare('
                SELECT s.subject_code, s.subject_name, s.units 
                FROM college_enrollments es
                JOIN subjects s ON es.subject_id = s.id
                WHERE es.application_id = :app_id
            ');
            $subStmt->execute(['app_id' => $assessment['application_id']]);
            $enrolledSubjects = $subStmt->fetchAll();

            if (empty($enrolledSubjects)) {
                $appSecStmt = $pdo->prepare('SELECT section_id FROM applications WHERE id = :app_id');
                $appSecStmt->execute(['app_id' => $assessment['application_id']]);
                $secId = $appSecStmt->fetchColumn();
                if ($secId) {
                    $secSubStmt = $pdo->prepare('
                        SELECT s.subject_code, s.subject_name, s.units
                        FROM college_section_subjects css
                        JOIN subjects s ON css.subject_id = s.id
                        WHERE css.college_section_id = :sec_id
                    ');
                    $secSubStmt->execute(['sec_id' => $secId]);
                    $enrolledSubjects = $secSubStmt->fetchAll();
                }
            }

            if (empty($enrolledSubjects)) {
                $currSubStmt = $pdo->prepare('
                    SELECT s.subject_code, s.subject_name, s.units
                    FROM college_curriculum_subjects ccs
                    JOIN subjects s ON ccs.subject_id = s.id
                    JOIN college_curricula cc ON ccs.curriculum_id = cc.id
                    JOIN college_programs p ON cc.program_id = p.id
                    WHERE p.code = :strand AND ccs.year_level = :year_level AND ccs.semester = :semester
                    ORDER BY ccs.display_order ASC
                ');
                $currSubStmt->execute([
                    'strand' => $assessment['strand'],
                    'year_level' => $assessment['grade_level'],
                    'semester' => $assessment['semester'] ?? 'First'
                ]);
                $enrolledSubjects = $currSubStmt->fetchAll();
            }
        } elseif ($assessment['academic_level'] === 'Senior High School') {
            $subStmt = $pdo->prepare('
                SELECT s.subject_code, s.subject_name, s.units 
                FROM shs_enrollments es
                JOIN subjects s ON es.subject_id = s.id
                WHERE es.application_id = :app_id
            ');
            $subStmt->execute(['app_id' => $assessment['application_id']]);
            $enrolledSubjects = $subStmt->fetchAll();

            if (empty($enrolledSubjects)) {
                $appSecStmt = $pdo->prepare('SELECT section_id FROM applications WHERE id = :app_id');
                $appSecStmt->execute(['app_id' => $assessment['application_id']]);
                $secId = $appSecStmt->fetchColumn();
                if ($secId) {
                    $secSubStmt = $pdo->prepare('
                        SELECT s.subject_code, s.subject_name, s.units
                        FROM shs_section_subjects ss
                        JOIN subjects s ON ss.subject_id = s.id
                        WHERE ss.shs_section_id = :sec_id
                    ');
                    $secSubStmt->execute(['sec_id' => $secId]);
                    $enrolledSubjects = $secSubStmt->fetchAll();
                }
            }

            if (empty($enrolledSubjects)) {
                $currSubStmt = $pdo->prepare('
                    SELECT s.subject_code, s.subject_name, s.units
                    FROM shs_curriculum_subjects scs
                    JOIN subjects s ON scs.subject_id = s.id
                    JOIN shs_curricula sc ON scs.curriculum_id = sc.id
                    JOIN shs_strands st ON sc.strand_id = st.id
                    WHERE st.code = :strand AND scs.grade_level = :grade_level AND scs.semester = :semester
                    ORDER BY scs.display_order ASC
                ');
                $currSubStmt->execute([
                    'strand' => $assessment['strand'],
                    'grade_level' => $assessment['grade_level'],
                    'semester' => $assessment['semester'] ?? 'First'
                ]);
                $enrolledSubjects = $currSubStmt->fetchAll();
            }
        }

        // Auto-sync dynamic tuition fee if rate per unit template is active
        if (!empty($assessment['is_per_unit']) && !empty($enrolledSubjects)) {
            $calcUnits = (int) array_sum(array_column($enrolledSubjects, 'units'));
            $unitRate = (float)($assessment['template_tuition_rate'] ?? 500.0);
            $calculatedTuition = $calcUnits * $unitRate;

            if (((float)$assessment['tuition_fee'] !== $calculatedTuition || (float)$assessment['total_amount'] <= 0) && $calculatedTuition > 0) {
                $calculatedTotal = $calculatedTuition + (float)$assessment['miscellaneous_fee'] + (float)$assessment['registration_fee'] + (float)$assessment['laboratory_fee'] + (float)$assessment['other_fees'];
                $calculatedNet = $calculatedTotal - (float)$assessment['discount_amount'];

                $syncStmt = $pdo->prepare('UPDATE student_assessments SET tuition_fee = :tuition, total_amount = :total, net_amount = :net WHERE id = :id');
                $syncStmt->execute([
                    'tuition' => $calculatedTuition,
                    'total' => $calculatedTotal,
                    'net' => $calculatedNet,
                    'id' => $assessment['id']
                ]);

                $assessment['tuition_fee'] = $calculatedTuition;
                $assessment['total_amount'] = $calculatedTotal;
                $assessment['net_amount'] = $calculatedNet;
            }
        }
    }
} catch (PDOException $e) {
    error_log('Applicant assessment fetch failed: ' . $e->getMessage());
}

$pageTitle = 'Financial Assessment - Applicant Portal';

        return $this->render('applicant/assessment', get_defined_vars());
    }
    public function processPayment(Request $request, Response $response)
    {
        $pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response->redirect("/sia/applicant/assessment.php");
    return;
}



$action = $_POST['action'] ?? '';
$userId = (int)$_SESSION['user_id'];

try {
    if ($action === 'submit_payment_proof') {
        $assessmentId = (int)($_POST['assessment_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $method = trim((string)($_POST['payment_method'] ?? ''));
        $refNo = trim((string)($_POST['reference_number'] ?? ''));

        if ($assessmentId <= 0) {
            throw new \Exception("Invalid assessment selected. Please refresh the page and try again.");
        }

        if ($amount <= 0) {
            throw new \Exception("Please enter a valid payment amount greater than ₱0.00.");
        }

        if (empty($method)) {
            throw new \Exception("Please select the payment method you used (GCash, Maya, or Bank Transfer).");
        }

        if (empty($refNo) || strlen($refNo) < 4) {
            throw new \Exception("Please enter a valid transaction reference number (at least 4 characters).");
        }

        // Enforce Health Information submission check
        $healthStatus = HealthRecord::getStatus($userId);
        if ($healthStatus === null) {
            throw new \Exception("Action Required: You must submit your Health Information before submitting payments.");
        }

        // Verify Assessment belongs to user
        $assStmt = $pdo->prepare('
            SELECT sa.* 
            FROM student_assessments sa
            INNER JOIN applications a ON sa.application_id = a.id
            WHERE sa.id = :id AND a.user_id = :user_id
        ');
        $assStmt->execute(['id' => $assessmentId, 'user_id' => $userId]);
        $assessment = $assStmt->fetch();

        if (!$assessment) {
            throw new \Exception("Assessment record not found or you are not authorized to submit payment for this account.");
        }

        $pendingStmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM payment_records WHERE assessment_id = :id AND status = "pending"');
        $pendingStmt->execute(['id' => $assessmentId]);
        $pendingAmount = (float)$pendingStmt->fetchColumn();

        $balance = (float)$assessment['net_amount'] - (float)$assessment['total_paid'] - $pendingAmount;
        
        if ($balance <= 0) {
            throw new \Exception("You have fully settled your balance or already have pending payments covering your full assessment.");
        }

        if ($amount > ($balance + 0.01)) {
            throw new \Exception("The payment amount (₱" . number_format($amount, 2) . ") exceeds your allowable remaining balance of ₱" . number_format($balance, 2) . ".");
        }

        $minPayment = min(500.0, $balance);
        if ($amount < $minPayment) {
            throw new \Exception("The minimum payment allowed is ₱" . number_format($minPayment, 2) . ".");
        }

        // Handle File Upload
        if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['proof_image']['error'] ?? UPLOAD_ERR_NO_FILE;
            $msg = match ($errCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "The uploaded screenshot exceeds the maximum allowed file size (5MB).",
                UPLOAD_ERR_PARTIAL => "The file was only partially uploaded. Please re-select your file and try again.",
                UPLOAD_ERR_NO_FILE => "Please attach a screenshot or photo of your payment receipt.",
                default => "Upload failed (Error Code: $errCode). Please try again."
            };
            throw new \Exception($msg);
        }

        $fileTmpPath = $_FILES['proof_image']['tmp_name'];
        $fileName = $_FILES['proof_image']['name'];
        $fileSize = (int)$_FILES['proof_image']['size'];
        $fileType = $_FILES['proof_image']['type'] ?? '';

        // Max 5MB limit
        if ($fileSize > 5 * 1024 * 1024) {
            throw new \Exception("The receipt image exceeds the 5MB size limit. Please upload a smaller image.");
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExts, true)) {
            throw new \Exception("Invalid file format (.$ext). Only JPG, PNG, and WEBP image screenshots are accepted.");
        }

        // MIME validation
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo ? finfo_file($finfo, $fileTmpPath) : $fileType;
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/webp'];
        if (!in_array($detectedMime, $allowedMimes, true) && !in_array($fileType, $allowedMimes, true)) {
            throw new \Exception("The selected file is not a valid image format. Please upload a clear JPG, PNG, or WEBP receipt screenshot.");
        }

        $newFileName = 'proof_' . $userId . '_' . time() . '.' . $ext;
        
        // Primary upload target: root uploads/payments/
        $uploadDir = __DIR__ . '/../../uploads/payments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $destPath = $uploadDir . $newFileName;
        if (!move_uploaded_file($fileTmpPath, $destPath)) {
            throw new \Exception("Server could not save the uploaded receipt image. Please check server permissions and try again.");
        }

        // Secondary sync: app/uploads/payments/ if it exists
        $altUploadDir = __DIR__ . '/../uploads/payments/';
        if (is_dir($altUploadDir)) {
            copy($destPath, $altUploadDir . $newFileName);
        }

        // Insert into payment_records as "pending"
        $stmt = $pdo->prepare('
            INSERT INTO payment_records (assessment_id, user_id, amount, payment_date, payment_method, reference_number, proof_image, status)
            VALUES (:ass_id, :user_id, :amount, CURDATE(), :method, :ref, :proof, "pending")
        ');
        
        $stmt->execute([
            'ass_id' => $assessmentId,
            'user_id' => $userId,
            'amount' => $amount,
            'method' => $method,
            'ref' => $refNo,
            'proof' => $newFileName
        ]);

        logActivity($userId, 'bi-cloud-upload', 'Payment Proof Uploaded', "Submitted proof of payment (Ref: $refNo, Method: $method) for ₱" . number_format($amount, 2));

        $_SESSION['success_msg'] = "Proof of payment for ₱" . number_format($amount, 2) . " (Ref: {$refNo}) was submitted successfully! The Cashier's office will review and verify your payment shortly.";
        $response->redirect("/sia/applicant/assessment.php");
        return;
    }
} catch (\Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
    $response->redirect("/sia/applicant/assessment.php");
    return;
}

$response->redirect("/sia/applicant/assessment.php");
return;
    }
    public function scholarships(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
        $userId = (int)$_SESSION['user_id'];
        $pageTitle = 'Scholarships - Applicant Portal';

        // Fetch user's application status
        $stmt = $pdo->prepare('
            SELECT a.status, a.id, a.college_curriculum_id, a.grade_level, c.program_id AS college_program_id
            FROM applications a 
            LEFT JOIN college_curricula c ON a.college_curriculum_id = c.id
            WHERE a.user_id = :user_id LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId]);
        $app = $stmt->fetch();

        $isApproved = ($app && in_array($app['status'], ['approved', 'enrolled'], true));
        $appId = $app ? $app['id'] : 0;
        $userProgramId = $app ? $app['college_program_id'] : null;
        $userYearLevel = $app ? $app['grade_level'] : null;

        if ($isApproved) {
            $healthStatus = HealthRecord::getStatus($userId);
            if ($healthStatus === null) {
                $_SESSION['error_msg'] = 'Action Required: You must submit your Health Information before applying for scholarships.';
                $response->redirect('/sia/applicant/health_info.php');
                return;
            }
        }

        // Check if an assessment exists
        $hasAssessment = false;
        $assessmentId = 0;
        if ($isApproved) {
            $assStmt = $pdo->prepare('SELECT id FROM student_assessments WHERE application_id = :app_id LIMIT 1');
            $assStmt->execute(['app_id' => $appId]);
            $assessment = $assStmt->fetch();
            if ($assessment) {
                $hasAssessment = true;
                $assessmentId = $assessment['id'];
            }
        }

        // Check medical clearance
        $isMedicalVerified = false;
        if ($isApproved) {
            $hStmt = $pdo->prepare('SELECT status FROM health_records WHERE user_id = :user_id LIMIT 1');
            $hStmt->execute(['user_id' => $userId]);
            $healthStatus = $hStmt->fetchColumn();
            if ($healthStatus === 'verified') {
                $isMedicalVerified = true;
            }
        }

        // Fetch active scholarships (Filtered by eligibility if possible)
        $activeScholarships = [];
        if ($isApproved && $isMedicalVerified && $hasAssessment) {
            $query = 'SELECT * FROM scholarships WHERE status = "Active"';
            $params = [];

            // We filter program if the scholarship requires a specific program
            if ($userProgramId) {
                $query .= ' AND (program_id IS NULL OR program_id = :prog_id)';
                $params['prog_id'] = $userProgramId;
            } else {
                $query .= ' AND program_id IS NULL';
            }

            // We filter year level if the scholarship requires it
            if ($userYearLevel) {
                $query .= ' AND (year_level IS NULL OR year_level = "" OR year_level = :yl)';
                $params['yl'] = $userYearLevel;
            }

            $query .= ' ORDER BY name ASC';
            
            $scholStmt = $pdo->prepare($query);
            $scholStmt->execute($params);
            $activeScholarships = $scholStmt->fetchAll();
        }

        // Fetch user's scholarship applications
        $myApplications = [];
        if ($isApproved && $isMedicalVerified && $hasAssessment) {
            $myAppStmt = $pdo->prepare('
                SELECT sa.*, s.name as scholarship_name, s.category, s.tuition_coverage_type, s.tuition_coverage_value 
                FROM scholarship_applications sa 
                JOIN scholarships s ON sa.scholarship_id = s.id 
                WHERE sa.user_id = :user_id 
                ORDER BY sa.created_at DESC
            ');
            $myAppStmt->execute(['user_id' => $userId]);
            $myApplications = $myAppStmt->fetchAll();
        }

        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        return $this->render('applicant/scholarships', get_defined_vars());
    }

    public function applyScholarship(Request $request, Response $response)
    {
        $pdo = Database::getConnection();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response->redirect("/sia/applicant/scholarships.php");
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $scholarshipId = (int)($_POST['scholarship_id'] ?? 0);

        if ($scholarshipId <= 0) {
            $_SESSION['error_msg'] = 'Invalid scholarship selection.';
            $response->redirect("/sia/applicant/scholarships.php");
            return;
        }

        try {
            // Verify eligibility
            $stmt = $pdo->prepare('
                SELECT a.id, a.status, sa.id as assessment_id 
                FROM applications a 
                LEFT JOIN student_assessments sa ON a.id = sa.application_id 
                WHERE a.user_id = :user_id LIMIT 1
            ');
            $stmt->execute(['user_id' => $userId]);
            $eligibility = $stmt->fetch();

            if (!$eligibility || !in_array($eligibility['status'], ['approved', 'enrolled'], true)) {
                throw new \Exception('You are not eligible to apply for scholarships at this time.');
            }

            if (!$eligibility['assessment_id']) {
                throw new \Exception('Your fee assessment has not been generated yet.');
            }

            // Check if scholarship exists and is active
            $scholStmt = $pdo->prepare('SELECT name FROM scholarships WHERE id = :id AND status = "Active"');
            $scholStmt->execute(['id' => $scholarshipId]);
            $scholarship = $scholStmt->fetch();

            if (!$scholarship) {
                throw new \Exception('The selected scholarship is no longer available.');
            }

            // Check if already applied
            $checkAppStmt = $pdo->prepare('SELECT id FROM scholarship_applications WHERE user_id = :user_id AND scholarship_id = :schol_id LIMIT 1');
            $checkAppStmt->execute(['user_id' => $userId, 'schol_id' => $scholarshipId]);
            if ($checkAppStmt->fetch()) {
                throw new \Exception('You have already applied for this scholarship.');
            }

            // Process uploaded documents
            $uploadedDocs = [];
            if (isset($_FILES['requirements']) && !empty($_FILES['requirements']['name'][0])) {
                $uploadDir = __DIR__ . '/../../uploads/scholarships/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                $maxSize = 5 * 1024 * 1024; // 5MB

                foreach ($_FILES['requirements']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['requirements']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileType = $_FILES['requirements']['type'][$key];
                        $fileSize = $_FILES['requirements']['size'][$key];
                        
                        if (!in_array($fileType, $allowedTypes)) {
                            throw new \Exception('Invalid file type for document: ' . $_FILES['requirements']['name'][$key]);
                        }
                        if ($fileSize > $maxSize) {
                            throw new \Exception('File too large: ' . $_FILES['requirements']['name'][$key]);
                        }

                        $ext = pathinfo($_FILES['requirements']['name'][$key], PATHINFO_EXTENSION);
                        $newName = 'schol_' . $userId . '_' . $scholarshipId . '_' . time() . '_' . $key . '.' . $ext;
                        
                        if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                            $uploadedDocs[] = [
                                'name' => htmlspecialchars($_FILES['requirements']['name'][$key], ENT_QUOTES, 'UTF-8'),
                                'url' => '/sia/uploads/scholarships/' . $newName
                            ];
                        }
                    }
                }
            }
            
            $submittedDocsJson = json_encode($uploadedDocs);

            // Insert Application
            $insertStmt = $pdo->prepare('INSERT INTO scholarship_applications (user_id, scholarship_id, status, submitted_documents) VALUES (:user_id, :schol_id, "pending", :docs)');
            $insertStmt->execute([
                'user_id' => $userId,
                'schol_id' => $scholarshipId,
                'docs' => $submittedDocsJson
            ]);

            // Log Activity
            $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, "bi-award text-primary", :title, :description)');
            $logStmt->execute([
                'user_id' => $userId,
                'title' => 'Scholarship Application Submitted',
                'description' => 'You applied for the ' . $scholarship['name'] . ' scholarship.'
            ]);

            $_SESSION['success_msg'] = 'Your scholarship application has been submitted successfully.';
        } catch (\Exception $e) {
            $_SESSION['error_msg'] = $e->getMessage();
        }

        $response->redirect("/sia/applicant/scholarships.php");
        return;
    }
    public function printSlip(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$userId = (int) $_SESSION['user_id'];

try {
    // 1. Fetch Application & User data
    $stmt = $pdo->prepare('
        SELECT a.*, u.first_name, u.last_name, u.email, u.student_number 
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        WHERE a.user_id = :user_id AND a.status IN ("approved", "enrolled")
        ORDER BY a.created_at DESC 
        LIMIT 1
    ');
    $stmt->execute(['user_id' => $userId]);
    $app = $stmt->fetch();

    if (!$app) {
        throw new \App\Core\HttpException(404, 'No active application found or your application is not yet approved.');
    }
    
    // Check Health Information gate
    $healthStatus = HealthRecord::getStatus($userId);
    if ($healthStatus === null) {
        $_SESSION['error_msg'] = 'Action Required: You must submit your Health Information before accessing your enrollment summary.';
        $response->redirect('/sia/applicant/health_info.php');
        return;
    }
    
    $appId = (int)$app['id'];

    // 2. Fetch Documents (Requirements)
    $docStmt = $pdo->prepare('SELECT * FROM application_documents WHERE application_id = :app_id');
    $docStmt->execute(['app_id' => $appId]);
    $documents = $docStmt->fetchAll();

    // 3. Fetch Assessment
    $assStmt = $pdo->prepare('SELECT * FROM student_assessments WHERE application_id = :app_id LIMIT 1');
    $assStmt->execute(['app_id' => $appId]);
    $assessment = $assStmt->fetch();

    // 4. Fetch Enrolled Subjects & Schedules
    if (($app['academic_level'] ?? '') === 'Senior High School') {
        $esStmt = $pdo->prepare('
            SELECT s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type, es.shs_section_id as section_id, sec.section_code
            FROM shs_enrollments es
            INNER JOIN subjects s ON s.id = es.subject_id
            LEFT JOIN shs_sections sec ON sec.id = es.shs_section_id
            WHERE es.application_id = :app_id
            ORDER BY s.subject_code ASC
        ');
        $esStmt->execute(['app_id' => $appId]);
        $enrolledSubjects = $esStmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($enrolledSubjects) && !empty($app['section_id'])) {
            $secSubStmt = $pdo->prepare('
                SELECT s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type, ss.shs_section_id as section_id, sec.section_code
                FROM shs_section_subjects ss
                INNER JOIN subjects s ON s.id = ss.subject_id
                LEFT JOIN shs_sections sec ON sec.id = ss.shs_section_id
                WHERE ss.shs_section_id = :sec_id
                ORDER BY s.subject_code ASC
            ');
            $secSubStmt->execute(['sec_id' => $app['section_id']]);
            $enrolledSubjects = $secSubStmt->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($enrolledSubjects)) {
                $insStmt = $pdo->prepare('INSERT IGNORE INTO shs_enrollments (application_id, subject_id, shs_section_id) VALUES (:app_id, :sub_id, :sec_id)');
                foreach ($enrolledSubjects as $sub) {
                    $insStmt->execute(['app_id' => $appId, 'sub_id' => $sub['subject_id'], 'sec_id' => $app['section_id']]);
                }
            }
        }
    } else {
        $esStmt = $pdo->prepare('
            SELECT s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type, es.college_section_id as section_id, sec.section_code
            FROM college_enrollments es
            INNER JOIN subjects s ON s.id = es.subject_id
            LEFT JOIN college_sections sec ON sec.id = es.college_section_id
            WHERE es.application_id = :app_id
            ORDER BY s.subject_code ASC
        ');
        $esStmt->execute(['app_id' => $appId]);
        $enrolledSubjects = $esStmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($enrolledSubjects) && !empty($app['section_id'])) {
            $secSubStmt = $pdo->prepare('
                SELECT s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type, css.college_section_id as section_id, sec.section_code
                FROM college_section_subjects css
                INNER JOIN subjects s ON s.id = css.subject_id
                LEFT JOIN college_sections sec ON sec.id = css.college_section_id
                WHERE css.college_section_id = :sec_id
                ORDER BY s.subject_code ASC
            ');
            $secSubStmt->execute(['sec_id' => $app['section_id']]);
            $enrolledSubjects = $secSubStmt->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($enrolledSubjects)) {
                $insStmt = $pdo->prepare('INSERT IGNORE INTO college_enrollments (application_id, subject_id, college_section_id) VALUES (:app_id, :sub_id, :sec_id)');
                foreach ($enrolledSubjects as $sub) {
                    $insStmt->execute(['app_id' => $appId, 'sub_id' => $sub['subject_id'], 'sec_id' => $app['section_id']]);
                }
            }
        }
    }
    
    // Attach schedules using a single query
    $sectionIds = array_unique(array_filter(array_map(function($sub) use ($app) {
        return $sub['section_id'] ?: ($app['section_id'] ?? null);
    }, $enrolledSubjects)));

    $allSchedules = [];
    if (!empty($sectionIds)) {
        $in = str_repeat('?,', count($sectionIds) - 1) . '?';
        if (($app['academic_level'] ?? '') === 'Senior High School') {
            $schedStmt = $pdo->prepare("SELECT shs_section_id as section_id, subject_id, day, start_time, end_time, room, instructor FROM shs_section_subjects WHERE shs_section_id IN ($in)");
        } else {
            $schedStmt = $pdo->prepare("SELECT college_section_id as section_id, subject_id, day, start_time, end_time, room, instructor FROM college_section_subjects WHERE college_section_id IN ($in)");
        }
        $schedStmt->execute(array_values($sectionIds));
        $allSchedules = $schedStmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    foreach ($enrolledSubjects as &$sub) {
        $sub['schedules'] = [];
        $targetSecId = $sub['section_id'] ?: ($app['section_id'] ?? null);
        foreach ($allSchedules as $sc) {
            if ($sc['section_id'] == $targetSecId && $sc['subject_id'] == $sub['subject_id']) {
                $sub['schedules'][] = $sc;
            }
        }
    }

} catch (\PDOException $e) {
    error_log("Enrollment summary error: " . $e->getMessage());
    throw new \App\Core\HttpException(500, 'A database error occurred while fetching your enrollment summary.');
}

$pageTitle = 'Enrollment Summary - ' . htmlspecialchars((string)($app['reference_number'] ?? ''), ENT_QUOTES, 'UTF-8');

        return $this->render('applicant/print_slip', get_defined_vars());
    }
}





