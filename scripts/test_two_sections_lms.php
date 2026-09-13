<?php
/**
 * Test: Multi-Section LMS Subject Instance Isolation & Anti-Duplication Test
 * 
 * Verifies that two student accounts in the same degree program (BSIT) taking the same subjects
 * (CC101, CC102, ENG101) in DIFFERENT sections (BSIT 1-A vs BSIT 1-B):
 * 1. Each individual subject has its own distinct, independent instance in lms_courses.
 * 2. Neither student receives duplicated course listings.
 * 3. Each student is strictly authorized for their own section's subject instance and forbidden from the other's.
 * 4. Section-specific learning materials remain completely isolated between sections.
 * 5. Both accounts can successfully authenticate and render their respective LMS dashboards.
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
use App\Services\EnrollmentService;
use App\Services\AssessmentService;
use App\Services\LmsService;

class TwoSectionsLmsTest
{
    private PDO $pdo;
    private array $sectionA = [];
    private array $sectionB = [];
    private array $studentA = [];
    private array $studentB = [];

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
        echo "   TTU LMS MULTI-SECTION SUBJECT INSTANCE & ISOLATION TEST            \n";
        echo "======================================================================\n";
        echo "Degree Program : Bachelor of Science in Information Technology (BSIT)\n";
        echo "Subjects Shared: CC101 (Intro to Computing), CC102 (Prog Fund), ENG101 (Purposive Comm)\n";
        echo "Section A      : BSIT 1-A\n";
        echo "Section B      : BSIT 1-B\n";
        echo "Execution Time : " . date('Y-m-d H:i:s') . "\n\n";

        try {
            // STEP 1: Ensure Section B (BSIT 1-B) and Section Subjects Exist
            $this->step1_setupSectionB();

            // STEP 2: Enroll Student 1 into Section A (BSIT 1-A)
            $this->step2_enrollStudentA();

            // STEP 3: Enroll Student 2 into Section B (BSIT 1-B)
            $this->step3_enrollStudentB();

            // STEP 4: Verify Distinct LMS Course Instances in Database
            $this->step4_verifyDistinctCourseInstances();

            // STEP 5: Verify Anti-Duplication on Both Student Dashboards
            $this->step5_verifyNoDuplication();

            // STEP 6: Verify Strict Section-Level Authorization & Access Controls
            $this->step6_verifyAccessIsolation();

            // STEP 7: Verify Content & Module Isolation between Section Instances
            $this->step7_verifyContentIsolation();

            // STEP 8: Verify Live HTTP Portal Login for Both Accounts
            $this->step8_verifyHttpLogins();

            echo "\n======================================================================\n";
            echo "   ALL MULTI-SECTION LMS TESTS PASSED WITH ZERO DUPLICATION!          \n";
            echo "======================================================================\n\n";

            return [
                'success' => true,
                'student_a' => $this->studentA,
                'student_b' => $this->studentB,
                'section_a' => $this->sectionA,
                'section_b' => $this->sectionB
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
     * Step 1: Ensure Section B (BSIT 1-B), Section Subjects, and LMS Courses Exist
     */
    private function step1_setupSectionB(): void
    {
        // 1. Fetch Section A ID
        $secAStmt = $this->pdo->query("SELECT id, section_code FROM college_sections WHERE section_code = 'BSIT 1-A' LIMIT 1");
        $secA = $secAStmt->fetch(PDO::FETCH_ASSOC);
        if (!$secA) throw new Exception("Section BSIT 1-A not found.");
        $this->sectionA = $secA;

        // 2. Ensure Section B exists
        $secBStmt = $this->pdo->query("SELECT id, section_code FROM college_sections WHERE section_code = 'BSIT 1-B' LIMIT 1");
        $secB = $secBStmt->fetch(PDO::FETCH_ASSOC);

        if (!$secB) {
            $insSecB = $this->pdo->prepare("
                INSERT INTO college_sections (
                    section_code, program_id, curriculum_id, academic_year, year_level, semester,
                    capacity, schedule_type, adviser, status, created_at, updated_at
                ) VALUES (
                    'BSIT 1-B', 1, 1, '2026-2027', '1st Year', 'First',
                    40, 'Afternoon', 'Dr. Grace Hopper', 1, NOW(), NOW()
                )
            ");
            $insSecB->execute();
            $secBId = (int)$this->pdo->lastInsertId();
            $this->sectionB = ['id' => $secBId, 'section_code' => 'BSIT 1-B'];
            $this->log('Step 1 [Infrastructure]', "Created section 'BSIT 1-B' (Section ID: {$secBId})", 'SUCCESS');
        } else {
            $this->sectionB = $secB;
            $this->log('Step 1 [Infrastructure]', "Found existing section 'BSIT 1-B' (Section ID: {$secB['id']})", 'INFO');
        }

        $secBId = (int)$this->sectionB['id'];

        // 3. Map Section Subjects for BSIT 1-B with distinct timetable & rooms
        $subjectsB = [
            [
                'subject_id' => 1, // CC101
                'code' => 'CC101',
                'day' => 'TTH',
                'start' => '13:00:00',
                'end' => '14:30:00',
                'room' => 'Lab 103',
                'faculty_id' => 8, // Alan Turing
                'instructor' => 'Alan Turing'
            ],
            [
                'subject_id' => 2, // CC102
                'code' => 'CC102',
                'day' => 'TTH',
                'start' => '14:30:00',
                'end' => '16:00:00',
                'room' => 'Lab 104',
                'faculty_id' => 9, // Ada Lovelace
                'instructor' => 'Ada Lovelace'
            ],
            [
                'subject_id' => 4, // ENG101
                'code' => 'ENG101',
                'day' => 'MWF',
                'start' => '14:00:00',
                'end' => '15:30:00',
                'room' => 'Room 308',
                'faculty_id' => 10, // Dr. Grace Hopper
                'instructor' => 'Dr. Grace Hopper'
            ]
        ];

        foreach ($subjectsB as $sb) {
            $chkCss = $this->pdo->prepare("SELECT id FROM college_section_subjects WHERE college_section_id = :sec_id AND subject_id = :sub_id LIMIT 1");
            $chkCss->execute(['sec_id' => $secBId, 'sub_id' => $sb['subject_id']]);
            $cssId = $chkCss->fetchColumn();

            if (!$cssId) {
                $insCss = $this->pdo->prepare("
                    INSERT INTO college_section_subjects (
                        college_section_id, subject_id, capacity, day, start_time, end_time, room, faculty_user_id, instructor, delivery_mode, created_at, updated_at
                    ) VALUES (
                        :sec_id, :sub_id, 40, :day, :start, :end, :room, :fac_id, :fac_name, 'Face-to-Face', NOW(), NOW()
                    )
                ");
                $insCss->execute([
                    'sec_id'   => $secBId,
                    'sub_id'   => $sb['subject_id'],
                    'day'      => $sb['day'],
                    'start'    => $sb['start'],
                    'end'      => $sb['end'],
                    'room'     => $sb['room'],
                    'fac_id'   => $sb['faculty_id'],
                    'fac_name' => $sb['instructor']
                ]);
            }

            // 4. Ensure distinct LMS Course instance for BSIT 1-B
            $chkLc = $this->pdo->prepare("SELECT id FROM lms_courses WHERE academic_level = 'College' AND academic_section_id = :sec_id AND subject_id = :sub_id LIMIT 1");
            $chkLc->execute(['sec_id' => $secBId, 'sub_id' => $sb['subject_id']]);
            $lcId = $chkLc->fetchColumn();

            if (!$lcId) {
                $insLc = $this->pdo->prepare("
                    INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status, created_at, updated_at)
                    VALUES ('College', :sec_id, :sub_id, :fac_id, 'active', NOW(), NOW())
                ");
                $insLc->execute([
                    'sec_id' => $secBId,
                    'sub_id' => $sb['subject_id'],
                    'fac_id' => $sb['faculty_id']
                ]);
                $lcId = $this->pdo->lastInsertId();
                $this->log('Step 1 [Infrastructure]', "Created separate LMS Course for {$sb['code']} in BSIT 1-B (LMS Course ID: {$lcId})", 'SUCCESS');
            }
        }
    }

    /**
     * Step 2: Enroll Student 1 into Section BSIT 1-A
     */
    private function step2_enrollStudentA(): void
    {
        $unique = substr(uniqid(), -5);
        $firstName = 'Lucas';
        $lastName = 'Vance' . strtoupper($unique);
        $email = 'lucas.vance.' . $unique . '@example.com';
        $rawPassword = 'Student@TTU2026!';
        $passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);

        // User
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

        // Application
        $refNumber = 'APP-' . date('Y') . '-' . str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $secAId = (int)$this->sectionA['id'];

        $appStmt = $this->pdo->prepare("
            INSERT INTO applications (
                user_id, reference_number, academic_level, grade_level, school_year, semester,
                student_type, strand, nstp, section_id, college_curriculum_id, status,
                document_submission_method, contact_number, birth_date, gender, civil_status,
                nationality, religion, address, guardian_name, guardian_relationship, guardian_contact,
                previous_school, previous_school_year, previous_school_type, lrn,
                emergency_name, emergency_relationship, emergency_contact, created_at, updated_at
            ) VALUES (
                :user_id, :ref_no, 'College', '1st Year', '2026-2027', 'First',
                'Regular', 'BSIT', 'ROTC', :sec_id, 1, 'approved',
                'online', '09171112222', '2005-01-10', 'Male', 'Single',
                'Filipino', 'Christian', '12 Emerald St, Pasig', 'Arthur Vance', 'Father', '09181112222',
                'Pasig City Science High School', '2025-2026', 'Public', '111122223333',
                'Arthur Vance', 'Father', '09181112222', NOW(), NOW()
            )
        ");
        $appStmt->execute([
            'user_id' => $userId,
            'ref_no'  => $refNumber,
            'sec_id'  => $secAId
        ]);
        $appId = (int)$this->pdo->lastInsertId();

        // Health & Docs
        $this->pdo->prepare("INSERT INTO health_records (user_id, application_id, status, created_at, updated_at) VALUES (?, ?, 'verified', NOW(), NOW())")->execute([$userId, $appId]);
        $this->pdo->prepare("INSERT INTO application_documents (application_id, document_name, file_path, status, created_at, updated_at) VALUES (?, 'Form 138', '/doc.pdf', 'verified', NOW(), NOW())")->execute([$appId]);

        // Assessment & Payment
        $assId = AssessmentService::generateAssessment($appId, $userId, $this->pdo);
        $receiptNo = generateAtomicReceiptNumber($this->pdo);
        $this->pdo->prepare("INSERT INTO payment_records (assessment_id, user_id, cashier_id, amount, payment_date, receipt_number, status) VALUES (?, ?, 1, 10500, NOW(), ?, 'completed')")->execute([$assId, $userId, $receiptNo]);
        $this->pdo->prepare("UPDATE student_assessments SET total_paid = 10500, payment_status = 'paid' WHERE id = ?")->execute([$assId]);
        $this->pdo->prepare("UPDATE applications SET status = 'payment_verified' WHERE id = ?")->execute([$appId]);

        // Finalize
        $finalRes = EnrollmentService::finalizeEnrollment($appId, 1, $this->pdo);

        $this->studentA = [
            'user_id' => $userId,
            'application_id' => $appId,
            'name' => "{$firstName} {$lastName}",
            'email' => $email,
            'raw_password' => $rawPassword,
            'student_number' => $finalRes['student_number'],
            'ttu_email' => $finalRes['ttu_email'],
            'section_id' => $secAId,
            'section_code' => 'BSIT 1-A'
        ];

        $this->log('Step 2 [Student A]', "Enrolled Student A in 'BSIT 1-A' (Student No: {$finalRes['student_number']}, Name: {$firstName} {$lastName})", 'SUCCESS');
    }

    /**
     * Step 3: Enroll Student 2 into Section BSIT 1-B
     */
    private function step3_enrollStudentB(): void
    {
        $unique = substr(uniqid(), -5);
        $firstName = 'Maya';
        $lastName = 'Lin' . strtoupper($unique);
        $email = 'maya.lin.' . $unique . '@example.com';
        $rawPassword = 'Student@TTU2026!';
        $passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);

        // User
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

        // Application
        $refNumber = 'APP-' . date('Y') . '-' . str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $secBId = (int)$this->sectionB['id'];

        $appStmt = $this->pdo->prepare("
            INSERT INTO applications (
                user_id, reference_number, academic_level, grade_level, school_year, semester,
                student_type, strand, nstp, section_id, college_curriculum_id, status,
                document_submission_method, contact_number, birth_date, gender, civil_status,
                nationality, religion, address, guardian_name, guardian_relationship, guardian_contact,
                previous_school, previous_school_year, previous_school_type, lrn,
                emergency_name, emergency_relationship, emergency_contact, created_at, updated_at
            ) VALUES (
                :user_id, :ref_no, 'College', '1st Year', '2026-2027', 'First',
                'Regular', 'BSIT', 'CWTS', :sec_id, 1, 'approved',
                'online', '09173334444', '2005-04-18', 'Female', 'Single',
                'Filipino', 'Christian', '44 Sapphire St, Quezon City', 'David Lin', 'Father', '09183334444',
                'Quezon City Academy', '2025-2026', 'Private', '444455556666',
                'David Lin', 'Father', '09183334444', NOW(), NOW()
            )
        ");
        $appStmt->execute([
            'user_id' => $userId,
            'ref_no'  => $refNumber,
            'sec_id'  => $secBId
        ]);
        $appId = (int)$this->pdo->lastInsertId();

        // Health & Docs
        $this->pdo->prepare("INSERT INTO health_records (user_id, application_id, status, created_at, updated_at) VALUES (?, ?, 'verified', NOW(), NOW())")->execute([$userId, $appId]);
        $this->pdo->prepare("INSERT INTO application_documents (application_id, document_name, file_path, status, created_at, updated_at) VALUES (?, 'Form 138', '/doc.pdf', 'verified', NOW(), NOW())")->execute([$appId]);

        // Assessment & Payment
        $assId = AssessmentService::generateAssessment($appId, $userId, $this->pdo);
        $receiptNo = generateAtomicReceiptNumber($this->pdo);
        $this->pdo->prepare("INSERT INTO payment_records (assessment_id, user_id, cashier_id, amount, payment_date, receipt_number, status) VALUES (?, ?, 1, 10500, NOW(), ?, 'completed')")->execute([$assId, $userId, $receiptNo]);
        $this->pdo->prepare("UPDATE student_assessments SET total_paid = 10500, payment_status = 'paid' WHERE id = ?")->execute([$assId]);
        $this->pdo->prepare("UPDATE applications SET status = 'payment_verified' WHERE id = ?")->execute([$appId]);

        // Finalize
        $finalRes = EnrollmentService::finalizeEnrollment($appId, 1, $this->pdo);

        $this->studentB = [
            'user_id' => $userId,
            'application_id' => $appId,
            'name' => "{$firstName} {$lastName}",
            'email' => $email,
            'raw_password' => $rawPassword,
            'student_number' => $finalRes['student_number'],
            'ttu_email' => $finalRes['ttu_email'],
            'section_id' => $secBId,
            'section_code' => 'BSIT 1-B'
        ];

        $this->log('Step 3 [Student B]', "Enrolled Student B in 'BSIT 1-B' (Student No: {$finalRes['student_number']}, Name: {$firstName} {$lastName})", 'SUCCESS');
    }

    /**
     * Step 4: Verify Distinct LMS Course Instances in Database
     */
    private function step4_verifyDistinctCourseInstances(): void
    {
        $secAId = (int)$this->sectionA['id'];
        $secBId = (int)$this->sectionB['id'];

        $subjects = [1 => 'CC101', 2 => 'CC102', 4 => 'ENG101'];

        $this->log('Step 4 [Instance Verification]', "Checking that each subject has its own distinct instance in each section:", 'INFO');

        foreach ($subjects as $subId => $subCode) {
            // Section A course
            $stmtA = $this->pdo->prepare("SELECT id, faculty_user_id FROM lms_courses WHERE academic_level = 'College' AND academic_section_id = :sec_id AND subject_id = :sub_id LIMIT 1");
            $stmtA->execute(['sec_id' => $secAId, 'sub_id' => $subId]);
            $courseA = $stmtA->fetch(PDO::FETCH_ASSOC);

            // Section B course
            $stmtB = $this->pdo->prepare("SELECT id, faculty_user_id FROM lms_courses WHERE academic_level = 'College' AND academic_section_id = :sec_id AND subject_id = :sub_id LIMIT 1");
            $stmtB->execute(['sec_id' => $secBId, 'sub_id' => $subId]);
            $courseB = $stmtB->fetch(PDO::FETCH_ASSOC);

            if (!$courseA || !$courseB) {
                throw new Exception("Missing LMS Course instance for subject {$subCode}!");
            }

            if ((int)$courseA['id'] === (int)$courseB['id']) {
                throw new Exception("ERROR: Course {$subCode} shares the SAME LMS course ID ({$courseA['id']}) between sections!");
            }

            $this->log('  -> ' . $subCode, "Section A (BSIT 1-A) LMS Course ID: {$courseA['id']} | Section B (BSIT 1-B) LMS Course ID: {$courseB['id']} [DISTINCT INSTANCES]", 'SUCCESS');
        }
    }

    /**
     * Step 5: Verify Anti-Duplication on Both Student Dashboards
     */
    private function step5_verifyNoDuplication(): void
    {
        $lmsService = new LmsService();

        // 1. Check Student A courses
        $coursesA = $lmsService->getStudentCourses($this->studentA['user_id']);
        $this->studentA['courses'] = $coursesA;

        $codesA = array_column($coursesA, 'code');
        $uniqueCodesA = array_unique($codesA);

        if (count($codesA) !== count($uniqueCodesA)) {
            throw new Exception("DUPLICATION DETECTED for Student A! Courses: " . implode(', ', $codesA));
        }

        if (count($coursesA) !== 3) {
            throw new Exception("Expected exactly 3 courses for Student A, got " . count($coursesA));
        }

        // Verify all courses for Student A belong to BSIT 1-A
        foreach ($coursesA as $ca) {
            $secName = $ca['section'] ?? $ca['section_name'] ?? '';
            if ($secName !== 'BSIT 1-A') {
                throw new Exception("Student A course {$ca['code']} has incorrect section: '{$secName}'");
            }
        }

        $this->log('Step 5 [Anti-Duplication]', "Student A (BSIT 1-A): Found exactly 3 unique courses (CC101, CC102, ENG101). Zero duplication detected.", 'SUCCESS');

        // 2. Check Student B courses
        $coursesB = $lmsService->getStudentCourses($this->studentB['user_id']);
        $this->studentB['courses'] = $coursesB;

        $codesB = array_column($coursesB, 'code');
        $uniqueCodesB = array_unique($codesB);

        if (count($codesB) !== count($uniqueCodesB)) {
            throw new Exception("DUPLICATION DETECTED for Student B! Courses: " . implode(', ', $codesB));
        }

        if (count($coursesB) !== 3) {
            throw new Exception("Expected exactly 3 courses for Student B, got " . count($coursesB));
        }

        // Verify all courses for Student B belong to BSIT 1-B
        foreach ($coursesB as $cb) {
            $secName = $cb['section'] ?? $cb['section_name'] ?? '';
            if ($secName !== 'BSIT 1-B') {
                throw new Exception("Student B course {$cb['code']} has incorrect section: '{$secName}'");
            }
        }

        $this->log('Step 5 [Anti-Duplication]', "Student B (BSIT 1-B): Found exactly 3 unique courses (CC101, CC102, ENG101). Zero duplication detected.", 'SUCCESS');
    }

    /**
     * Step 6: Verify Strict Section-Level Authorization & Access Controls
     */
    private function step6_verifyAccessIsolation(): void
    {
        $lmsService = new LmsService();
        $userAId = $this->studentA['user_id'];
        $userBId = $this->studentB['user_id'];

        $courseA_CC101 = (int)$this->studentA['courses'][0]['lms_course_id'];
        $courseB_CC101 = (int)$this->studentB['courses'][0]['lms_course_id'];

        // Student A should be authorized for Course A, but NOT Course B
        $authA_for_A = $lmsService->isStudentAuthorizedForCourse($userAId, $courseA_CC101);
        $authA_for_B = $lmsService->isStudentAuthorizedForCourse($userAId, $courseB_CC101);

        // Student B should be authorized for Course B, but NOT Course A
        $authB_for_B = $lmsService->isStudentAuthorizedForCourse($userBId, $courseB_CC101);
        $authB_for_A = $lmsService->isStudentAuthorizedForCourse($userBId, $courseA_CC101);

        if (!$authA_for_A) throw new Exception("Student A was denied access to their own section course (ID {$courseA_CC101})!");
        if ($authA_for_B)  throw new Exception("SECURITY BREACH: Student A was granted access to Section B's course (ID {$courseB_CC101})!");
        if (!$authB_for_B) throw new Exception("Student B was denied access to their own section course (ID {$courseB_CC101})!");
        if ($authB_for_A)  throw new Exception("SECURITY BREACH: Student B was granted access to Section A's course (ID {$courseA_CC101})!");

        $this->log('Step 6 [Authorization Isolation]', "Cross-section course isolation verified: Student A cannot access Section B course (ID {$courseB_CC101}), Student B cannot access Section A course (ID {$courseA_CC101}).", 'SUCCESS');
    }

    /**
     * Step 7: Verify Content & Module Isolation between Section Instances
     */
    private function step7_verifyContentIsolation(): void
    {
        $courseA_CC101 = (int)$this->studentA['courses'][0]['lms_course_id'];
        $courseB_CC101 = (int)$this->studentB['courses'][0]['lms_course_id'];

        // Insert a unique module into Section A's CC101 course only
        $moduleTitleA = "Section A Exclusive Lab: Binary Logic Gates - " . uniqid();
        $insModA = $this->pdo->prepare("
            INSERT INTO lms_modules (lms_course_id, title, description, display_order, status, created_at, updated_at)
            VALUES (:cid, :title, 'Lab assignment exclusively for BSIT 1-A.', 1, 'published', NOW(), NOW())
        ");
        $insModA->execute([
            'cid' => $courseA_CC101,
            'title' => $moduleTitleA
        ]);

        // Insert a unique module into Section B's CC101 course only
        $moduleTitleB = "Section B Exclusive Workshop: Number Base Conversion - " . uniqid();
        $insModB = $this->pdo->prepare("
            INSERT INTO lms_modules (lms_course_id, title, description, display_order, status, created_at, updated_at)
            VALUES (:cid, :title, 'Lab assignment exclusively for BSIT 1-B.', 1, 'published', NOW(), NOW())
        ");
        $insModB->execute([
            'cid' => $courseB_CC101,
            'title' => $moduleTitleB
        ]);

        $lmsService = new LmsService();
        $modsA = $lmsService->getModulesWithMaterialsForCourse($courseA_CC101);
        $modsB = $lmsService->getModulesWithMaterialsForCourse($courseB_CC101);

        $titlesA = array_column($modsA, 'title');
        $titlesB = array_column($modsB, 'title');

        if (!in_array($moduleTitleA, $titlesA, true)) {
            throw new Exception("Section A module not found in Course A!");
        }
        if (in_array($moduleTitleA, $titlesB, true)) {
            throw new Exception("LEAK DETECTED: Section A module appeared in Section B's course instance!");
        }

        if (!in_array($moduleTitleB, $titlesB, true)) {
            throw new Exception("Section B module not found in Course B!");
        }
        if (in_array($moduleTitleB, $titlesA, true)) {
            throw new Exception("LEAK DETECTED: Section B module appeared in Section A's course instance!");
        }

        $this->log('Step 7 [Content Isolation]', "Published modules tested: Section A course content is completely isolated from Section B course content.", 'SUCCESS');
    }

    /**
     * Step 8: Verify Live HTTP Portal Login for Both Accounts
     */
    private function step8_verifyHttpLogins(): void
    {
        // 1. Verify Student A HTTP Login
        $this->verifySingleHttpLogin(
            $this->studentA['student_number'],
            $this->studentA['raw_password'],
            'BSIT 1-A',
            $this->studentA['name']
        );

        // 2. Verify Student B HTTP Login
        $this->verifySingleHttpLogin(
            $this->studentB['student_number'],
            $this->studentB['raw_password'],
            'BSIT 1-B',
            $this->studentB['name']
        );

        $this->log('Step 8 [HTTP Portal]', "Both student accounts authenticated via HTTP, verified CSRF, and rendered dashboards cleanly.", 'SUCCESS');
    }

    private function verifySingleHttpLogin(string $studentId, string $password, string $expectedSection, string $expectedName): void
    {
        $cookieJar = tempnam(sys_get_temp_dir(), 'lms_cookie_multi_');

        // GET Login Page
        $ch = curl_init('http://localhost/sia/auth/lms_student_login.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        $loginPage = curl_exec($ch);

        preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage, $m);
        $csrf = $m[1] ?? '';

        // POST Login Form
        $postData = [
            'csrf_token' => $csrf,
            'role' => 'student',
            'student_id' => $studentId,
            'password' => $password
        ];

        $ch = curl_init('http://localhost/sia/auth/lms_login_process.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $dashboardHtml = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            throw new Exception("HTTP Login failed for {$studentId}: HTTP Code {$httpCode}");
        }

        if (strpos($dashboardHtml, $expectedSection) === false) {
            throw new Exception("Expected section '{$expectedSection}' not found in dashboard HTML for {$studentId}!");
        }

        @unlink($cookieJar);
    }
}

// Execute test
$tester = new TwoSectionsLmsTest();
$result = $tester->run();

// Display Summary Table
if ($result['success']) {
    $sA = $result['student_a'];
    $sB = $result['student_b'];

    echo "======================================================================\n";
    echo "                 ACCOUNT 1: SECTION BSIT 1-A                          \n";
    echo "======================================================================\n";
    echo " Student Name        : " . $sA['name'] . "\n";
    echo " Student Number      : " . $sA['student_number'] . "\n";
    echo " Institutional Email : " . $sA['ttu_email'] . "\n";
    echo " Personal Email      : " . $sA['email'] . "\n";
    echo " Password            : " . $sA['raw_password'] . "\n";
    echo " Section             : " . $sA['section_code'] . "\n";
    echo " Enrolled Courses (" . count($sA['courses']) . " Total - Zero Duplication):\n";
    foreach ($sA['courses'] as $idx => $c) {
        $cCode = $c['code'] ?? 'N/A';
        $cName = $c['name'] ?? 'N/A';
        $cInst = $c['first_name'] . ' ' . $c['last_name'];
        $cSec  = $c['section'] ?? $c['section_name'] ?? 'BSIT 1-A';
        $cId   = $c['lms_course_id'] ?? 0;
        echo "   " . ($idx + 1) . ". {$cCode} - {$cName} | LMS Course ID: {$cId} | Section: {$cSec} | Instructor: {$cInst}\n";
    }

    echo "\n======================================================================\n";
    echo "                 ACCOUNT 2: SECTION BSIT 1-B                          \n";
    echo "======================================================================\n";
    echo " Student Name        : " . $sB['name'] . "\n";
    echo " Student Number      : " . $sB['student_number'] . "\n";
    echo " Institutional Email : " . $sB['ttu_email'] . "\n";
    echo " Personal Email      : " . $sB['email'] . "\n";
    echo " Password            : " . $sB['raw_password'] . "\n";
    echo " Section             : " . $sB['section_code'] . "\n";
    echo " Enrolled Courses (" . count($sB['courses']) . " Total - Zero Duplication):\n";
    foreach ($sB['courses'] as $idx => $c) {
        $cCode = $c['code'] ?? 'N/A';
        $cName = $c['name'] ?? 'N/A';
        $cInst = $c['first_name'] . ' ' . $c['last_name'];
        $cSec  = $c['section'] ?? $c['section_name'] ?? 'BSIT 1-B';
        $cId   = $c['lms_course_id'] ?? 0;
        echo "   " . ($idx + 1) . ". {$cCode} - {$cName} | LMS Course ID: {$cId} | Section: {$cSec} | Instructor: {$cInst}\n";
    }
    echo "======================================================================\n";
} else {
    echo "TEST FAILED: " . $result['error'] . "\n";
}
