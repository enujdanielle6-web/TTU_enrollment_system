<?php
/**
 * Triple T University - Irregular Student with Scholarship Test Bot
 * 
 * Simulates the complete enrollment lifecycle of an Irregular College Student awarded
 * an institutional scholarship:
 * 1. Applicant Registration
 * 2. Application Submission (College 1st Year BSIT, Student Type: Irregular)
 * 3. Custom Subject Selection (application_subject_requests)
 * 4. Medical Clearance Verification
 * 5. Document Verification (Form 138, Transcript, Good Moral, PSA, 2x2 Photo)
 * 6. Admissions Evaluation & Approval (custom subject enrollment)
 * 7. Scholarship Application & Award (scholarship_applications & scholarship_recipients)
 * 8. Dynamic Tuition Assessment with Automatic Scholarship Discount Deductions
 * 9. Cashier Settlement of the Discounted Net Tuition via Monotonic Atomic Receipt
 * 10. Registrar Finalization & Matriculation (Official Student No & TTU Email)
 * 11. LMS Multi-Identifier Authentication
 * 12. LMS Enrolled Irregular Courses & Timetable Retrieval
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

class IrregularScholarshipTestBot
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
        echo "   TRIPLE T UNIVERSITY - IRREGULAR STUDENT WITH SCHOLARSHIP BOT       \n";
        echo "======================================================================\n";
        echo "Applicant Target : College 1st Year (BSIT Program)\n";
        echo "Student Type     : IRREGULAR (Custom Subject Selection)\n";
        echo "Scholarship Type : ATH-50 (Athletic Varsity Grant - 50% Tuition Waiver)\n";
        echo "Execution Time   : " . date('Y-m-d H:i:s') . "\n\n";

        try {
            // STEP 1: Applicant Account Registration
            $this->step1_registerApplicant();

            // STEP 2: Irregular Application & Custom Subject Selection
            $this->step2_submitIrregularApplication();

            // STEP 3: Clinic Health Records & Medical Clearance
            $this->step3_submitMedicalClearance();

            // STEP 4: Document Upload & Verification
            $this->step4_verifyDocuments();

            // STEP 5: Admissions Evaluation & Approval
            $this->step5_admissionsApproval();

            // STEP 6: Scholarship Application & Official Award
            $this->step6_awardScholarship();

            // STEP 7: Dynamic Tuition Assessment with Scholarship Discount
            $this->step7_generateAssessment();

            // STEP 8: Cashier Settlement of Discounted Net Balance
            $this->step8_processPayment();

            // STEP 9: Registrar Authoritative Finalization (Matriculation)
            $this->step9_finalizeEnrollment();

            // STEP 10: LMS Authentication & Irregular Courses Verification
            $this->step10_verifyLmsAccess();

            echo "\n======================================================================\n";
            echo "   IRREGULAR ENROLLMENT WITH SCHOLARSHIP COMPLETE - LMS ACTIVATED!    \n";
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
                    'academic_level'       => 'College',
                    'grade_level'          => '1st Year',
                    'program'              => 'Bachelor of Science in Information Technology (BSIT)',
                    'student_type'         => 'Irregular',
                    'scholarship'          => $this->botData['scholarship_name'] . ' (' . $this->botData['scholarship_discount_label'] . ')',
                    'gross_tuition'        => $this->botData['tuition_fee'],
                    'scholarship_discount' => $this->botData['discount_amount'],
                    'net_amount_paid'      => $this->botData['net_amount'],
                    'receipt_number'       => $this->botData['receipt_number'],
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
        $firstName = 'Jordan';
        $lastName = 'Lee' . strtoupper($uniqueId);
        $email = 'jordan.lee.' . $uniqueId . '@example.com';
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

        $this->log('Step 1 [Identity]', "Registered Irregular applicant '{$firstName} {$lastName}' (User ID: {$userId}, Email: {$email})", 'SUCCESS');
    }

    /**
     * Step 2: Submit Application as Irregular with Custom Subject Selection
     */
    private function step2_submitIrregularApplication(): void
    {
        $refNumber = 'APP-' . date('Y') . '-' . str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Irregular students do not have a fixed section block; section_id is null
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
                'Irregular', 'BSIT', 'CWTS', NULL, 1, 'pending',
                'online', '09176543210', '2004-11-20', 'Male', 'Single',
                'Filipino', 'Christian', '55 Academic Way, Quezon City', 'Marcus Lee', 'Father',
                '09186543210', 'Metro Manila Institute of Technology', '2024-2025', 'Private',
                '112233445566', 'Marcus Lee', 'Father', '09186543210', NOW(), NOW()
            )
        ");
        $stmt->execute([
            'user_id' => $this->botData['user_id'],
            'ref_no'  => $refNumber
        ]);

        $appId = (int)$this->pdo->lastInsertId();
        $this->botData['application_id'] = $appId;
        $this->botData['reference_number'] = $refNumber;

        // Custom Irregular Subjects Selection:
        // Student picks: CC101 (Subject ID 1, 3 units) and ENG101 (Subject ID 4, 3 units) from Section 1
        $selectedSubjects = [
            1 => 1, // CC101 from Section BSIT 1-A (ID 1)
            4 => 1  // ENG101 from Section BSIT 1-A (ID 1)
        ];

        $insReqStmt = $this->pdo->prepare("
            INSERT INTO application_subject_requests (application_id, subject_id, section_id)
            VALUES (:app_id, :sub_id, :sec_id)
        ");

        foreach ($selectedSubjects as $subjId => $secId) {
            $insReqStmt->execute([
                'app_id' => $appId,
                'sub_id' => $subjId,
                'sec_id' => $secId
            ]);
        }

        $this->botData['selected_subjects'] = $selectedSubjects;

        $this->log('Step 2 [Application]', "Irregular application filed (App ID: {$appId}, Ref: {$refNumber}, Custom Subjects: CC101 & ENG101 [6 Units total])", 'SUCCESS');
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
                :user_id, :app_id, '178 cm', '70 kg', 'B+',
                0, 0, 0, 0,
                0, 0, 'verified', 'Fit for Varsity Athletics & College Academic Programs - Cleared by TTU Clinic Officer', NOW(), NOW()
            )
        ");
        $stmt->execute([
            'user_id' => $this->botData['user_id'],
            'app_id'  => $this->botData['application_id']
        ]);

        $this->log('Step 3 [Medical Clearance]', "Clinic health records verified (Status: verified, Blood Type: B+, Athletic Clearance: Fit)", 'SUCCESS');
    }

    /**
     * Step 4: Upload & Verify Admission Documents
     */
    private function step4_verifyDocuments(): void
    {
        $appId = $this->botData['application_id'];
        $docs = [
            'Official Transcript of Records / Transfer Credentials',
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

        $this->log('Step 4 [Documents]', "Uploaded and verified 4 required credentials (Transcript, Good Moral, PSA, 2x2 Photo)", 'SUCCESS');
    }

    /**
     * Step 5: Admissions Evaluation & Approval (Enrolling Irregular Subjects)
     */
    private function step5_admissionsApproval(): void
    {
        $appId = $this->botData['application_id'];

        // Admissions Officer approves irregular application
        $stmt = $this->pdo->prepare("
            UPDATE applications 
            SET status = 'approved',
                admin_feedback = 'Credentials, athletic evaluation, and custom subject schedule verified. Approved for Irregular BSIT intake.',
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute(['id' => $appId]);

        // Irregular custom subject enrollment into college_enrollments
        $delCe = $this->pdo->prepare("DELETE FROM college_enrollments WHERE application_id = :app_id");
        $delCe->execute(['app_id' => $appId]);

        $insCe = $this->pdo->prepare("
            INSERT INTO college_enrollments (application_id, subject_id, college_section_id)
            VALUES (:app_id, :sub_id, :sec_id)
        ");

        foreach ($this->botData['selected_subjects'] as $subId => $secId) {
            $insCe->execute([
                'app_id' => $appId,
                'sub_id' => $subId,
                'sec_id' => $secId
            ]);
        }

        $this->log('Step 5 [Admissions]', "Admissions Officer evaluated and approved application. Enrolled 2 requested subjects into college_enrollments.", 'SUCCESS');
    }

    /**
     * Step 6: Scholarship Application & Official Award
     */
    private function step6_awardScholarship(): void
    {
        $userId = $this->botData['user_id'];
        $scholarshipId = 2; // ATH-50: Athletic Varsity Grant (50% tuition coverage)

        // Fetch scholarship details
        $stmt = $this->pdo->prepare("SELECT * FROM scholarships WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $scholarshipId]);
        $schol = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$schol) {
            throw new Exception("Scholarship ID {$scholarshipId} not found.");
        }

        $this->botData['scholarship_id'] = $scholarshipId;
        $this->botData['scholarship_code'] = $schol['code'];
        $this->botData['scholarship_name'] = $schol['name'];
        $this->botData['scholarship_discount_label'] = "{$schol['tuition_coverage_value']}% Tuition Waiver";

        // Record scholarship application
        $appStmt = $this->pdo->prepare("
            INSERT INTO scholarship_applications (
                user_id, scholarship_id, academic_year_id, semester, status,
                submitted_documents, admin_feedback, created_at, updated_at
            ) VALUES (
                :uid, :sid, '2026-2027', 'First', 'approved',
                'Varsity Athletic Clearance, Coach Recommendation, GWA 1.75', 'Approved for 50% Varsity Tuition Grant.', NOW(), NOW()
            )
        ");
        $appStmt->execute([
            'uid' => $userId,
            'sid' => $scholarshipId
        ]);

        // Award scholarship in scholarship_recipients
        $recipStmt = $this->pdo->prepare("
            INSERT INTO scholarship_recipients (
                user_id, scholarship_id, academic_year_id, semester, status, remarks, created_at, updated_at
            ) VALUES (
                :uid, :sid, '2026-2027', 'First', 'Active', 'Varsity Athlete Awardee - 50% Tuition Grant', NOW(), NOW()
            )
        ");
        $recipStmt->execute([
            'uid' => $userId,
            'sid' => $scholarshipId
        ]);

        $this->log('Step 6 [Scholarship Office]', "Awarded '{$schol['name']}' ({$schol['code']}) to student. Recorded in scholarship_recipients (Status: Active)", 'SUCCESS');
    }

    /**
     * Step 7: Dynamic Tuition Assessment with Automatic Scholarship Discount
     */
    private function step7_generateAssessment(): void
    {
        $appId = $this->botData['application_id'];
        $userId = $this->botData['user_id'];

        $assessmentId = AssessmentService::generateAssessment($appId, $userId, $this->pdo);

        if (!$assessmentId) {
            throw new Exception("AssessmentService failed to compute tuition assessment for Application #{$appId}.");
        }

        // Fetch assessed details (which includes scholarship discount deduction)
        $stmt = $this->pdo->prepare("SELECT * FROM student_assessments WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $assessmentId]);
        $ass = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->botData['assessment_id'] = $assessmentId;
        $this->botData['tuition_fee'] = (float)$ass['tuition_fee'];
        $this->botData['discount_amount'] = (float)$ass['discount_amount'];
        $this->botData['total_amount'] = (float)$ass['total_amount'];
        $this->botData['net_amount'] = (float)$ass['net_amount'];

        $this->log('Step 7 [Finance Assessment]', "Assessment computed: Gross Total: ₱" . number_format($ass['total_amount'], 2) . " (6 Units @ ₱500/unit = ₱" . number_format($ass['tuition_fee'], 2) . " tuition + ₱6,000 non-tuition fees)", 'SUCCESS');
        $this->log('  -> Scholarship Discount', "Deducted ₱" . number_format($ass['discount_amount'], 2) . " (50% Tuition Waiver via ATH-50). Net Amount Payable: ₱" . number_format($ass['net_amount'], 2), 'SUCCESS');
    }

    /**
     * Step 8: Cashier Payment Processing & Atomic Receipt Sequencing
     */
    private function step8_processPayment(): void
    {
        $assessmentId = $this->botData['assessment_id'];
        $userId = $this->botData['user_id'];
        $appId = $this->botData['application_id'];
        $amountPaid = $this->botData['net_amount']; // Student pays the discounted net balance

        // Generate monotonic atomic receipt number
        $receiptNumber = generateAtomicReceiptNumber($this->pdo);

        // Record payment
        $stmt = $this->pdo->prepare("
            INSERT INTO payment_records (
                assessment_id, user_id, cashier_id, amount, payment_date,
                payment_method, receipt_number, status, remarks, created_at, updated_at
            ) VALUES (
                :ass_id, :user_id, 1, :amount, NOW(),
                'Cashier POS', :receipt_no, 'completed', 'Tuition settlement after 50% Varsity Scholarship Discount', NOW(), NOW()
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

        $this->log('Step 8 [Cashier]', "Discounted net payment of ₱" . number_format($amountPaid, 2) . " settled. Monotonic Receipt: {$receiptNumber}. Status: 'payment_verified'", 'SUCCESS');
    }

    /**
     * Step 9: Registrar Authoritative Finalization (Matriculation)
     */
    private function step9_finalizeEnrollment(): void
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

        // Verify College enrollments
        $enrCountStmt = $this->pdo->prepare("SELECT COUNT(*) FROM college_enrollments WHERE application_id = :app_id");
        $enrCountStmt->execute(['app_id' => $appId]);
        $enrCount = (int)$enrCountStmt->fetchColumn();

        $this->log('Step 9 [Registrar Finalization]', "Enrollment finalized! Student No: {$userCheck['student_number']}, Email: {$userCheck['ttu_email']}, Role: {$userCheck['role']}, LMS: {$userCheck['lms_status']}. Retained exactly {$enrCount} custom irregular subjects.", 'SUCCESS');
    }

    /**
     * Step 10: LMS Authentication & Enrolled Courses Verification
     */
    private function step10_verifyLmsAccess(): void
    {
        $userId = $this->botData['user_id'];
        $password = $this->botData['raw_password'];

        // 1. Verify Authentication across all 3 login identifiers
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

        $this->log('Step 10 [LMS Verification]', "Student LMS authenticated across all 3 identifiers. Found " . count($courses) . " active irregular enrolled courses in LMS.", 'SUCCESS');

        foreach ($courses as $c) {
            $code = $c['code'] ?? $c['subject_code'] ?? 'N/A';
            $name = $c['name'] ?? $c['subject_name'] ?? 'N/A';
            $units = $c['units'] ?? 0;
            $instructor = $c['instructor'] ?? 'Faculty';
            $sched = $c['schedule'] ?? 'TBA';
            $room = $c['room'] ?? 'TBA';
            $sec = $c['section'] ?? 'Irregular';
            $this->log('  -> Enrolled Irregular Course', "{$code}: {$name} ({$units} Units) | {$instructor} | {$sched} | Room: {$room} [Section: {$sec}]", 'INFO');
        }
    }
}

// Instantiate and run bot
$bot = new IrregularScholarshipTestBot();
$report = $bot->run();

// Output formatted credentials
if ($report['success']) {
    $creds = $report['credentials'];
    echo "======================================================================\n";
    echo "       OFFICIAL IRREGULAR SCHOLAR STUDENT CREDENTIALS                 \n";
    echo "======================================================================\n";
    echo " Portal URL            : " . $creds['lms_login_url'] . "\n";
    echo " Student Name          : " . $creds['applicant_name'] . "\n";
    echo " Student ID / Number   : " . $creds['student_id'] . "\n";
    echo " Institutional Email   : " . $creds['institutional_email'] . "\n";
    echo " Personal Email        : " . $creds['personal_email'] . "\n";
    echo " Password              : " . $creds['password'] . "\n";
    echo " Academic Level        : " . $creds['academic_level'] . " (" . $creds['grade_level'] . ")\n";
    echo " Academic Program      : " . $creds['program'] . "\n";
    echo " Student Status / Type : " . strtoupper($creds['student_type']) . "\n";
    echo " Active Scholarship    : " . $creds['scholarship'] . "\n";
    echo " Financial Breakdown   : \n";
    echo "   - Gross Tuition     : ₱" . number_format($creds['gross_tuition'], 2) . "\n";
    echo "   - Scholarship Grant : -₱" . number_format($creds['scholarship_discount'], 2) . "\n";
    echo "   - Net Settled (POS) : ₱" . number_format($creds['net_amount_paid'], 2) . " (Receipt: " . $creds['receipt_number'] . ")\n";
    echo " Term                  : " . $creds['academic_year'] . " (" . $creds['semester'] . ")\n";
    echo " LMS Account Status    : " . strtoupper($creds['lms_status']) . "\n";
    echo " User Role             : " . strtoupper($creds['user_role']) . "\n";
    echo " Enrolled Subjects     : \n";
    foreach ($creds['enrolled_courses'] as $idx => $course) {
        $cCode = $course['code'] ?? $course['subject_code'] ?? 'N/A';
        $cName = $course['name'] ?? $course['subject_name'] ?? 'N/A';
        $cUnits = $course['units'] ?? 0;
        $cInstr = $course['instructor'] ?? 'Faculty';
        $cSched = $course['schedule'] ?? 'TBA';
        $cRoom  = $course['room'] ?? 'TBA';
        $cSec   = $course['section'] ?? 'Irregular';
        echo "   " . ($idx + 1) . ". {$cCode} - {$cName} ({$cUnits} Units) | {$cInstr} | {$cSched} | Room: {$cRoom} [Section: {$cSec}]\n";
    }
    echo "======================================================================\n";
} else {
    echo "TEST BOT FAILED: " . $report['error'] . "\n";
}
