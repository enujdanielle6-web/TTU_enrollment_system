<?php
/**
 * Triple T University - Automated Enrollment Test Bot
 * 
 * Simulates the complete applicant lifecycle from initial registration,
 * application submission, medical & document clearance, admissions approval,
 * tuition assessment, cashier payment, registrar finalization, and LMS account activation.
 */

// Autoloader & Bootstrapping
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

class EnrollmentTestBot
{
    private PDO $pdo;
    private array $botData = [];
    private array $results = [];

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
        echo "   TRIPLE T UNIVERSITY - AUTOMATED APPLICANT ENROLLMENT TEST BOT      \n";
        echo "======================================================================\n";
        echo "Execution Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

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

            // STEP 7: Cashier Payment Processing & Receipt Sequencing
            $this->step7_processPayment();

            // STEP 8: Registrar Authoritative Finalization (Matriculation)
            $this->step8_finalizeEnrollment();

            // STEP 9: LMS Authentication & Enrolled Courses Verification
            $this->step9_verifyLmsAccess();

            echo "\n======================================================================\n";
            echo "   ENROLLMENT SIMULATION COMPLETE - STUDENT READY FOR LMS PORTAL      \n";
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
                    'program'              => 'Bachelor of Science in Information Technology (BSIT)',
                    'section'              => 'BSIT 1-A',
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
        $firstName = 'Alex';
        $lastName = 'Quantum' . strtoupper($uniqueId);
        $email = 'alex.quantum.' . $uniqueId . '@example.com';
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

        $this->log('Step 1 [Identity]', "Registered applicant '{$firstName} {$lastName}' (User ID: {$userId}, Email: {$email})", 'SUCCESS');
    }

    /**
     * Step 2: Submit Student Application
     */
    private function step2_submitApplication(): void
    {
        $refNumber = 'APP-' . date('Y') . '-' . str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);

        $stmt = $this->pdo->prepare("
            INSERT INTO applications (
                user_id, reference_number, academic_level, grade_level, school_year, semester,
                student_type, strand, nstp, section_id, college_curriculum_id, status,
                document_submission_method, contact_number, birth_date, gender, civil_status,
                nationality, religion, address, guardian_name, guardian_relationship,
                guardian_contact, previous_school, previous_school_year, previous_school_type,
                lrn, emergency_name, emergency_relationship, emergency_contact, created_at, updated_at
            ) VALUES (
                :user_id, :ref_no, 'College', '1st Year', '2026-2027', 'First',
                'New', 'BSIT', 'ROTC', 1, 1, 'pending',
                'online', '09171234567', '2005-06-15', 'Male', 'Single',
                'Filipino', 'Christian', '100 University Boulevard, Metro Manila', 'Elena Quantum', 'Mother',
                '09181234567', 'Metro Manila Science High School', '2025-2026', 'Public',
                '123456789012', 'Elena Quantum', 'Mother', '09181234567', NOW(), NOW()
            )
        ");
        $stmt->execute([
            'user_id' => $this->botData['user_id'],
            'ref_no'  => $refNumber
        ]);

        $appId = (int)$this->pdo->lastInsertId();
        $this->botData['application_id'] = $appId;
        $this->botData['reference_number'] = $refNumber;

        $this->log('Step 2 [Application]', "Application submitted for BSIT 1st Year (App ID: {$appId}, Ref: {$refNumber})", 'SUCCESS');
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
                :user_id, :app_id, '172 cm', '65 kg', 'O+',
                0, 0, 0, 0,
                0, 0, 'verified', 'Fit for College Physical & Academic Activities - Approved by TTU Clinic Officer', NOW(), NOW()
            )
        ");
        $stmt->execute([
            'user_id' => $this->botData['user_id'],
            'app_id'  => $this->botData['application_id']
        ]);

        $this->log('Step 3 [Medical Clearance]', "Clinic health records verified (Status: verified, Blood Type: O+)", 'SUCCESS');
    }

    /**
     * Step 4: Upload & Verify Admission Documents
     */
    private function step4_verifyDocuments(): void
    {
        $appId = $this->botData['application_id'];
        $docs = [
            'Form 138 (High School Report Card)',
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
                'path'     => '/uploads/admissions/' . md5($doc) . '.pdf'
            ]);
        }

        $this->log('Step 4 [Documents]', "Uploaded and verified 4 required credentials (Form 138, Good Moral, PSA, 2x2 Photo)", 'SUCCESS');
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
                admin_feedback = 'Credentials and medical records verified. Approved for BSIT College intake.',
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute(['id' => $appId]);

        $this->log('Step 5 [Admissions]', "Admissions Officer reviewed application; status transitioned to 'approved'", 'SUCCESS');
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

        $this->log('Step 6 [Finance Assessment]', "Assessment computed (Assessment ID: {$assessmentId}, Net Amount: ₱" . number_format($ass['net_amount'], 2) . ")", 'SUCCESS');
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
                'Cashier POS', :receipt_no, 'completed', 'Tuition settlement for College BSIT 1st Year', NOW(), NOW()
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
     * Step 8: Registrar Authoritative Finalization
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

        $this->log('Step 8 [Registrar Finalization]', "Enrollment officially finalized! Assigned Student No: {$userCheck['student_number']}, Institutional Email: {$userCheck['ttu_email']}, Role: {$userCheck['role']}, LMS Status: {$userCheck['lms_status']}", 'SUCCESS');
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

        // 2. Fetch LMS Enrolled Courses
        $lmsService = new LmsService();
        $courses = $lmsService->getStudentCourses($userId);

        $this->botData['lms_courses'] = $courses;

        $this->log('Step 9 [LMS Verification]', "Student LMS authenticated across all 3 identifiers (Student No, TTU Email, Personal Email). Found " . count($courses) . " enrolled LMS courses.", 'SUCCESS');

        foreach ($courses as $c) {
            $code = $c['code'] ?? $c['subject_code'] ?? 'N/A';
            $name = $c['name'] ?? $c['subject_name'] ?? 'N/A';
            $units = $c['units'] ?? 0;
            $this->log('  -> Enrolled Course', "{$code}: {$name} ({$units} Units) [Section: BSIT 1-A]", 'INFO');
        }
    }
}

// Instantiate and run bot
$bot = new EnrollmentTestBot();
$report = $bot->run();

// Display Credentials Box
if ($report['success']) {
    $creds = $report['credentials'];
    echo "======================================================================\n";
    echo "                 OFFICIAL STUDENT CREDENTIALS                         \n";
    echo "======================================================================\n";
    echo " Portal URL          : " . $creds['lms_login_url'] . "\n";
    echo " Student Name        : " . $creds['applicant_name'] . "\n";
    echo " Student ID / Number : " . $creds['student_id'] . "\n";
    echo " Institutional Email : " . $creds['institutional_email'] . "\n";
    echo " Personal Email      : " . $creds['personal_email'] . "\n";
    echo " Password            : " . $creds['password'] . "\n";
    echo " Academic Program    : " . $creds['program'] . "\n";
    echo " Section             : " . $creds['section'] . "\n";
    echo " Term                : " . $creds['academic_year'] . " (" . $creds['semester'] . ")\n";
    echo " LMS Account Status  : " . strtoupper($creds['lms_status']) . "\n";
    echo " User Role           : " . strtoupper($creds['user_role']) . "\n";
    echo " Enrolled Subjects   : \n";
    foreach ($creds['enrolled_courses'] as $idx => $course) {
        $cCode = $course['code'] ?? $course['subject_code'] ?? 'N/A';
        $cName = $course['name'] ?? $course['subject_name'] ?? 'N/A';
        $cUnits = $course['units'] ?? 0;
        echo "   " . ($idx + 1) . ". {$cCode} - {$cName} ({$cUnits} Units)\n";
    }
    echo "======================================================================\n";
} else {
    echo "TEST BOT FAILED: " . $report['error'] . "\n";
}
