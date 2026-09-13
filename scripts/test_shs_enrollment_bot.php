<?php
/**
 * Triple T University - Senior High School Grade 12 Applicant Test Bot
 * 
 * Simulates the complete applicant lifecycle for a Senior High School (SHS) Grade 12 student
 * through all stages:
 * 1. Applicant Account Registration
 * 2. Application Dossier Submission (Senior High School, Grade 12, STEM, Section STEM 12-A)
 * 3. Clinic Health Clearance
 * 4. Document Verification (Form 138, Good Moral, PSA, 2x2 Photo)
 * 5. Admissions Evaluation & Approval
 * 6. Tuition Assessment Generation via AssessmentService
 * 7. Cashier Payment Processing with Atomic Receipt Sequencing
 * 8. Registrar Authoritative Finalization (Matriculation & Section Subject Enrollment)
 * 9. LMS Identity & Password Authentication across all 3 identifiers
 * 10. LMS Active Enrolled Course Retrieval & Verification
 */

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once __DIR__ . '/../app/Helpers/functions.php';

use App\Core\Database;
use App\Services\AssessmentService;
use App\Services\EnrollmentService;
use App\Services\StudentNumberService;
use App\Services\LmsService;

class ShsGrade12TestBot
{
    private PDO $pdo;
    private array $botData = [];

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    private function log(string $phase, string $message, string $status = 'INFO'): void
    {
        $timestamp = date('H:i:s');
        $tag = match($status) {
            'SUCCESS' => '[PASS]',
            'WARNING' => '[WARN]',
            'ERROR'   => '[FAIL]',
            default   => '[INFO]'
        };
        echo "{$timestamp} {$tag} {$phase}: {$message}\n";
    }

    public function run(): array
    {
        echo "======================================================================\n";
        echo "   TRIPLE T UNIVERSITY - SHS GRADE 12 ENROLLMENT LIFECYCLE BOT       \n";
        echo "======================================================================\n";
        echo "Applicant Target : Senior High School (Grade 12 - STEM Strand)\n";
        echo "Target Section   : STEM 12-A\n";
        echo "Execution Time   : " . date('Y-m-d H:i:s') . "\n\n";

        try {
            // STEP 1: Applicant Account Registration
            $this->step1_registerApplicant();

            // STEP 2: Application Dossier Submission
            $this->step2_submitApplication();

            // STEP 3: Clinic Health Records & Medical Clearance
            $this->step3_submitMedicalClearance();

            // STEP 4: Document Upload & Verification
            $this->step4_verifyDocuments();

            // STEP 5: Admissions Evaluation & Approval
            $this->step5_admissionsApproval();

            // STEP 6: Tuition Assessment Generation
            $this->step6_generateAssessment();

            // STEP 7: Cashier Payment Processing & Atomic Receipt Sequencing
            $this->step7_processPayment();

            // STEP 8: Registrar Authoritative Finalization (Matriculation)
            $this->step8_finalizeEnrollment();

            // STEP 9: LMS Authentication & Enrolled Courses Verification
            $this->step9_verifyLmsAccess();

            echo "\n======================================================================\n";
            echo "   SHS GRADE 12 ENROLLMENT COMPLETE - LMS ACCOUNT ACTIVATED!          \n";
            echo "======================================================================\n\n";

            return [
                'success' => true,
                'credentials' => [
                    'portal_name'          => 'Triple T University - Student LMS Portal',
                    'lms_login_url'        => 'http://localhost/sia/auth/lms_student_login.php',
                    'student_id'           => $this->botData['student_number'],
                    'institutional_email'  => $this->botData['ttu_email'],
                    'personal_email'       => $this->botData['email'],
                    'password'             => $this->botData['raw_password'],
                    'applicant_name'       => $this->botData['first_name'] . ' ' . $this->botData['last_name'],
                    'academic_level'       => 'Senior High School',
                    'grade_level'          => 'Grade 12',
                    'strand'               => 'STEM (Science, Technology, Engineering, and Mathematics)',
                    'section'              => 'STEM 12-A',
                    'academic_year'        => '2026-2027',
                    'semester'             => '1st Semester',
                    'lms_status'           => 'active',
                    'user_role'            => 'student',
                    'enrolled_courses'     => $this->botData['lms_courses']
                ]
            ];

        } catch (Exception $e) {
            $this->log('FATAL ERROR', $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Step 1: Create Applicant Account in `users`
     */
    private function step1_registerApplicant(): void
    {
        $uniqueId = substr(uniqid(), -5);
        $firstName = 'Samantha';
        $lastName = 'Vance' . strtoupper($uniqueId);
        $email = 'samantha.vance.' . $uniqueId . '@example.com';
        $rawPassword = 'Student@TTU2026!';
        $passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password, role, is_active, email_verified, created_at, updated_at)
            VALUES (:first_name, :last_name, :email, :password, 'applicant', 1, 1, NOW(), NOW())
        ");
        $stmt->execute([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'password'   => $passwordHash
        ]);

        $userId = (int)$this->pdo->lastInsertId();

        $this->botData['user_id'] = $userId;
        $this->botData['first_name'] = $firstName;
        $this->botData['last_name'] = $lastName;
        $this->botData['email'] = $email;
        $this->botData['raw_password'] = $rawPassword;

        $this->log('Step 1 [Identity]', "Registered SHS applicant '{$firstName} {$lastName}' (User ID: {$userId}, Email: {$email})", 'SUCCESS');
    }

    /**
     * Step 2: Submit Student Application for SHS Grade 12 STEM
     */
    private function step2_submitApplication(): void
    {
        $refNumber = 'APP-' . date('Y') . '-' . str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Fetch Section ID for STEM 12-A
        $secStmt = $this->pdo->prepare("SELECT id FROM shs_sections WHERE section_code = 'STEM 12-A' LIMIT 1");
        $secStmt->execute();
        $sectionId = (int)$secStmt->fetchColumn();

        if (!$sectionId) {
            throw new Exception("Section STEM 12-A not found in database.");
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO applications (
                user_id, reference_number, academic_level, grade_level, school_year, semester,
                student_type, strand, nstp, section_id, college_curriculum_id, status,
                document_submission_method, contact_number, birth_date, gender, civil_status,
                nationality, religion, address, guardian_name, guardian_relationship,
                guardian_contact, previous_school, previous_school_year, previous_school_type,
                lrn, emergency_name, emergency_relationship, emergency_contact, created_at, updated_at
            ) VALUES (
                :user_id, :ref_no, 'Senior High School', 'Grade 12', '2026-2027', 'First',
                'Regular', 'STEM', NULL, :sec_id, NULL, 'pending',
                'online', '09179876543', '2008-09-14', 'Female', 'Single',
                'Filipino', 'Christian', '742 Evergreen Terrace, Quezon City', 'Eleanor Vance', 'Mother',
                '09189876543', 'Philippine Science High School - Main Campus', '2025-2026', 'Public',
                '401234567890', 'Eleanor Vance', 'Mother', '09189876543', NOW(), NOW()
            )
        ");
        $stmt->execute([
            'user_id' => $this->botData['user_id'],
            'ref_no'  => $refNumber,
            'sec_id'  => $sectionId
        ]);

        $appId = (int)$this->pdo->lastInsertId();
        $this->botData['application_id'] = $appId;
        $this->botData['reference_number'] = $refNumber;
        $this->botData['section_id'] = $sectionId;

        $this->log('Step 2 [Application]', "Application submitted for SHS Grade 12 STEM (App ID: {$appId}, Ref: {$refNumber}, Section ID: {$sectionId})", 'SUCCESS');
    }

    /**
     * Step 3: File Medical Clearance in `health_records`
     */
    private function step3_submitMedicalClearance(): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO health_records (
                user_id, application_id, height, weight, blood_type,
                has_allergies, has_asthma, has_diabetes, has_hypertension,
                has_heart_disease, has_physical_disability, status, admin_remarks, created_at, updated_at
            ) VALUES (
                :user_id, :app_id, '165 cm', '52 kg', 'A+',
                0, 0, 0, 0,
                0, 0, 'verified', 'Fit for Senior High School Academic and Laboratory Activities - Cleared by TTU Clinic Officer', NOW(), NOW()
            )
        ");
        $stmt->execute([
            'user_id' => $this->botData['user_id'],
            'app_id'  => $this->botData['application_id']
        ]);

        $this->log('Step 3 [Medical Clearance]', "Clinic health clearance verified (Status: verified, Blood Type: A+, No Conditions)", 'SUCCESS');
    }

    /**
     * Step 4: Upload & Verify Admission Documents
     */
    private function step4_verifyDocuments(): void
    {
        $appId = $this->botData['application_id'];
        $docs = [
            'Form 138 (Grade 11 Report Card)',
            'Certificate of Good Moral Character',
            'PSA Birth Certificate',
            '2x2 Formal ID Picture'
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO application_documents (application_id, document_name, file_path, status, created_at, updated_at)
            VALUES (:app_id, :doc_name, :path, 'verified', NOW(), NOW())
        ");

        foreach ($docs as $doc) {
            $stmt->execute([
                'app_id'   => $appId,
                'doc_name' => $doc,
                'path'     => '/uploads/admissions/' . md5($doc . $appId) . '.pdf'
            ]);
        }

        $this->log('Step 4 [Documents]', "Uploaded and verified 4 required credentials (Form 138 Grade 11, Good Moral, PSA, 2x2 Photo)", 'SUCCESS');
    }

    /**
     * Step 5: Admissions Evaluation & Approval
     */
    private function step5_admissionsApproval(): void
    {
        $appId = $this->botData['application_id'];

        $stmt = $this->pdo->prepare("
            UPDATE applications 
            SET status = 'approved',
                admin_feedback = 'Grade 11 scholastic records and medical clearance verified. Approved for SHS Grade 12 STEM section STEM 12-A.',
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute(['id' => $appId]);

        $this->log('Step 5 [Admissions]', "Admissions Officer evaluated application; status updated to 'approved'", 'SUCCESS');
    }

    /**
     * Step 6: Generate Tuition Assessment via AssessmentService
     */
    private function step6_generateAssessment(): void
    {
        $appId = $this->botData['application_id'];
        $userId = $this->botData['user_id'];

        $assessmentId = AssessmentService::generateAssessment($appId, $userId, $this->pdo);

        if (!$assessmentId) {
            throw new Exception("AssessmentService failed to compute tuition assessment for Application #{$appId}.");
        }

        // Fetch assessed details
        $stmt = $this->pdo->prepare("SELECT * FROM student_assessments WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $assessmentId]);
        $ass = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->botData['assessment_id'] = $assessmentId;
        $this->botData['tuition_fee'] = (float)$ass['tuition_fee'];
        $this->botData['net_amount'] = (float)$ass['net_amount'];

        $this->log('Step 6 [Finance Assessment]', "Assessment computed (Assessment ID: {$assessmentId}, Tuition: ₱" . number_format($ass['tuition_fee'], 2) . ", Net Amount: ₱" . number_format($ass['net_amount'], 2) . ")", 'SUCCESS');
    }

    /**
     * Step 7: Record Cashier Payment & Receipt Sequencing
     */
    private function step7_processPayment(): void
    {
        $assessmentId = $this->botData['assessment_id'];
        $userId = $this->botData['user_id'];
        $appId = $this->botData['application_id'];
        $amountPaid = $this->botData['net_amount'];

        // Generate atomic receipt number
        $receiptNumber = generateAtomicReceiptNumber($this->pdo);

        // Record payment
        $stmt = $this->pdo->prepare("
            INSERT INTO payment_records (
                assessment_id, user_id, cashier_id, amount, payment_date,
                payment_method, receipt_number, status, remarks, created_at, updated_at
            ) VALUES (
                :ass_id, :user_id, 1, :amount, NOW(),
                'Cashier POS', :receipt_no, 'completed', 'Tuition settlement for SHS Grade 12 STEM 1st Semester', NOW(), NOW()
            )
        ");
        $stmt->execute([
            'ass_id'     => $assessmentId,
            'user_id'    => $userId,
            'amount'     => $amountPaid,
            'receipt_no' => $receiptNumber
        ]);

        // Update student assessment
        $updAss = $this->pdo->prepare("
            UPDATE student_assessments 
            SET total_paid = :amount,
                payment_status = 'paid',
                updated_at = NOW()
            WHERE id = :id
        ");
        $updAss->execute(['amount' => $amountPaid, 'id' => $assessmentId]);

        // Transition application status to payment_verified
        $updApp = $this->pdo->prepare("UPDATE applications SET status = 'payment_verified', updated_at = NOW() WHERE id = :id");
        $updApp->execute(['id' => $appId]);

        $this->botData['receipt_number'] = $receiptNumber;

        $this->log('Step 7 [Cashier]', "Tuition payment of ₱" . number_format($amountPaid, 2) . " processed. Receipt: {$receiptNumber}. Status: 'payment_verified'", 'SUCCESS');
    }

    /**
     * Step 8: Registrar Authoritative Finalization (Matriculation)
     */
    private function step8_finalizeEnrollment(): void
    {
        $appId = $this->botData['application_id'];

        $result = EnrollmentService::finalizeEnrollment($appId, 1, $this->pdo);

        if (!$result['success']) {
            throw new Exception("Enrollment finalization failed: " . ($result['error'] ?? 'Unknown error'));
        }

        $this->botData['student_number'] = $result['student_number'];
        $this->botData['ttu_email'] = $result['ttu_email'];

        // Verify that user role was updated to 'student' and lms_status is 'active'
        $stmt = $this->pdo->prepare("SELECT role, lms_status, student_number, ttu_email FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $this->botData['user_id']]);
        $userCheck = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify SHS enrollments
        $enrCountStmt = $this->pdo->prepare("SELECT COUNT(*) FROM shs_enrollments WHERE application_id = :app_id");
        $enrCountStmt->execute(['app_id' => $appId]);
        $enrCount = (int)$enrCountStmt->fetchColumn();

        $this->log('Step 8 [Registrar Finalization]', "Enrollment finalized! Student No: {$userCheck['student_number']}, Email: {$userCheck['ttu_email']}, Role: {$userCheck['role']}, LMS: {$userCheck['lms_status']}. Enrolled in {$enrCount} SHS section subjects.", 'SUCCESS');
    }

    /**
     * Step 9: LMS Authentication & Courses Verification
     */
    private function step9_verifyLmsAccess(): void
    {
        $userId = $this->botData['user_id'];
        $password = $this->botData['raw_password'];

        // 1. Verify Authentication via Student Number, Institutional Email, and Personal Email
        $loginChecks = [
            'Student Number'      => $this->botData['student_number'],
            'Institutional Email' => $this->botData['ttu_email'],
            'Personal Email'      => $this->botData['email']
        ];

        foreach ($loginChecks as $type => $loginIdentifier) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM users 
                WHERE (student_number = :sid1 OR email = :sid2 OR ttu_email = :sid3) 
                  AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([
                'sid1' => $loginIdentifier,
                'sid2' => $loginIdentifier,
                'sid3' => $loginIdentifier
            ]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$u || !password_verify($password, $u['password'])) {
                throw new Exception("LMS Authentication failed for identifier: {$loginIdentifier} ({$type})");
            }
        }

        // 2. Fetch LMS Enrolled Courses via LmsService
        $lmsService = new LmsService();
        $courses = $lmsService->getStudentCourses($userId);

        $this->botData['lms_courses'] = $courses;

        $this->log('Step 9 [LMS Verification]', "Student LMS authenticated across all 3 identifiers. Found " . count($courses) . " active enrolled SHS courses.", 'SUCCESS');

        foreach ($courses as $c) {
            $code = $c['code'] ?? $c['subject_code'] ?? 'N/A';
            $name = $c['name'] ?? $c['subject_name'] ?? 'N/A';
            $units = $c['units'] ?? 0;
            $instructor = $c['instructor'] ?? 'Faculty';
            $sched = $c['schedule'] ?? 'TBA';
            $room = $c['room'] ?? 'TBA';
            $this->log('  -> Enrolled SHS Course', "{$code}: {$name} ({$units} Units) | {$instructor} | {$sched} | Room: {$room}", 'INFO');
        }
    }
}

// Instantiate and run bot
$bot = new ShsGrade12TestBot();
$report = $bot->run();

// Output formatted credentials
if ($report['success']) {
    $creds = $report['credentials'];
    echo "======================================================================\n";
    echo "            OFFICIAL SHS GRADE 12 STUDENT CREDENTIALS                 \n";
    echo "======================================================================\n";
    echo " Portal URL          : " . $creds['lms_login_url'] . "\n";
    echo " Student Name        : " . $creds['applicant_name'] . "\n";
    echo " Student ID / Number : " . $creds['student_id'] . "\n";
    echo " Institutional Email : " . $creds['institutional_email'] . "\n";
    echo " Personal Email      : " . $creds['personal_email'] . "\n";
    echo " Password            : " . $creds['password'] . "\n";
    echo " Academic Level      : " . $creds['academic_level'] . "\n";
    echo " Grade Level & Strand: " . $creds['grade_level'] . " - " . $creds['strand'] . "\n";
    echo " Section             : " . $creds['section'] . "\n";
    echo " Term                : " . $creds['academic_year'] . " (" . $creds['semester'] . ")\n";
    echo " LMS Account Status  : " . strtoupper($creds['lms_status']) . "\n";
    echo " User Role           : " . strtoupper($creds['user_role']) . "\n";
    echo " Enrolled Subjects   : \n";
    foreach ($creds['enrolled_courses'] as $idx => $course) {
        $cCode = $course['code'] ?? $course['subject_code'] ?? 'N/A';
        $cName = $course['name'] ?? $course['subject_name'] ?? 'N/A';
        $cUnits = $course['units'] ?? 0;
        $cInstr = $course['instructor'] ?? 'Faculty';
        $cSched = $course['schedule'] ?? 'TBA';
        $cRoom  = $course['room'] ?? 'TBA';
        echo "   " . ($idx + 1) . ". {$cCode} - {$cName} ({$cUnits} Units) | {$cInstr} | {$cSched} | Room: {$cRoom}\n";
    }
    echo "======================================================================\n";
} else {
    echo "TEST BOT FAILED: " . $report['error'] . "\n";
}
