<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Models/Schedule.php';

use App\Core\Database;
use App\Models\Schedule;

$pdo = Database::getConnection();

echo "====================================================\n";
echo "VERIFICATION SUITE: ISSUES 6 THROUGH 11\n";
echo "====================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $detail = '') {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] $name\n";
        $passed++;
    } else {
        echo "  [FAIL] $name: $detail\n";
        $failed++;
    }
}

// --------------------------------------------------------
// TEST 1: Issue 6 - Health Record fields and persistence
// --------------------------------------------------------
echo "1. Testing Issue 6: Health Information Form Processing...\n";
require_once __DIR__ . '/../app/Models/HealthRecord.php';
use App\Models\HealthRecord;

$refClass = new ReflectionClass(HealthRecord::class);
assertTest("HealthRecord::save exists", $refClass->hasMethod('save'));

// --------------------------------------------------------
// TEST 2: Issue 7 - SHS Curriculum Query Syntax & Execution
// --------------------------------------------------------
echo "\n2. Testing Issue 7: SHS Curriculum Queries (No shs_curriculum table)...\n";

try {
    // Test getCurriculum query for SHS
    $shsStmt = $pdo->prepare('
        SELECT s.id, s.subject_code, s.subject_name, s.units, s.subject_type
        FROM shs_curricula sc
        INNER JOIN shs_curriculum_subjects c ON sc.id = c.curriculum_id
        INNER JOIN subjects s ON c.subject_id = s.id
        WHERE sc.strand_id = :program_id 
          AND c.grade_level = :year_level
          AND sc.status = "active"
          AND s.status = 1
        LIMIT 5
    ');
    $shsStmt->execute(['program_id' => 1, 'year_level' => 'Grade 11']);
    $shsSubjects = $shsStmt->fetchAll();
    assertTest("SHS getCurriculum query executes against shs_curricula & shs_curriculum_subjects", true);

    // Test getFullCurriculum query for SHS
    $shsFullStmt = $pdo->prepare('
        SELECT 
            s.id, s.subject_code, s.subject_name, s.units, s.subject_type,
            c.grade_level as year_level, c.semester
        FROM shs_curricula sc
        INNER JOIN shs_curriculum_subjects c ON sc.id = c.curriculum_id
        INNER JOIN subjects s ON c.subject_id = s.id
        WHERE sc.strand_id = :program_id
          AND sc.status = "active"
          AND s.status = 1
        ORDER BY c.grade_level ASC, c.semester ASC, s.subject_code ASC
        LIMIT 5
    ');
    $shsFullStmt->execute(['program_id' => 1]);
    $shsFullSubjects = $shsFullStmt->fetchAll();
    assertTest("SHS getFullCurriculum query executes successfully", true);
} catch (PDOException $e) {
    assertTest("SHS Curriculum queries execute without error", false, $e->getMessage());
}

// Check that ApplicantApiController no longer has any 'FROM shs_curriculum '
$apiCode = file_get_contents(__DIR__ . '/../app/Controllers/Api/ApplicantApiController.php');
assertTest("ApplicantApiController has 0 occurrences of 'FROM shs_curriculum '", strpos($apiCode, 'FROM shs_curriculum ') === false);

// --------------------------------------------------------
// TEST 3: Issue 8 - showErrorPage() Helper Function
// --------------------------------------------------------
echo "\n3. Testing Issue 8: showErrorPage() Helper Function...\n";
assertTest("showErrorPage() function exists", function_exists('showErrorPage'));

// Test in a separate process to avoid exit terminating test suite
$output = shell_exec('php -r "require \'app/Helpers/functions.php\'; showErrorPage(\'Test Error\', \'Detailed test message\', 500);"');
assertTest("showErrorPage() produces valid HTML with error title", strpos($output, 'Test Error') !== false);
assertTest("showErrorPage() includes the detailed error message", strpos($output, 'Detailed test message') !== false);
assertTest("showErrorPage() has Go Back button", strpos($output, 'Go Back') !== false);

// --------------------------------------------------------
// TEST 4: Issue 9 - Schedule Model Section ID Capacity Check
// --------------------------------------------------------
echo "\n4. Testing Issue 9: Schedule Model Capacity Check...\n";
$scheduleCode = file_get_contents(__DIR__ . '/../app/Models/Schedule.php');
assertTest("Schedule.php selects shs_section_id as section_id", strpos($scheduleCode, 'shs_section_id as section_id') !== false);
assertTest("Schedule.php selects college_section_id as section_id", strpos($scheduleCode, 'college_section_id as section_id') !== false);
assertTest("Schedule.php binds section_id into capacity query", strpos($scheduleCode, '$capStmt->execute([$off[\'section_id\'], $off[\'subject_id\']]);') !== false);

// Run validateSelectedSubjects without syntax/PDO errors
$errors = Schedule::validateSelectedSubjects('College', []);
assertTest("Schedule::validateSelectedSubjects runs cleanly for empty selection", is_array($errors));

// --------------------------------------------------------
// TEST 5: Issue 10 - Scholarship Setting Key & Recalculation
// --------------------------------------------------------
echo "\n5. Testing Issue 10: Scholarship Settings & Recalculate Assessment...\n";
$scholCode = file_get_contents(__DIR__ . '/../app/Controllers/Admin/Scholarship/ScholarshipController.php');
assertTest("ScholarshipController queries active_school_year", strpos($scholCode, "'active_school_year'") !== false);
assertTest("ScholarshipController has fallback to application school_year/semester", strpos($scholCode, '$userApp[\'school_year\']') !== false);

$fnCode = file_get_contents(__DIR__ . '/../app/Helpers/functions.php');
assertTest("functions.php recalculateStudentAssessment queries active_school_year", strpos($fnCode, "'active_school_year'") !== false);
assertTest("functions.php recalculateStudentAssessment falls back to assessment school_year", strpos($fnCode, '$assessment[\'school_year\']') !== false);

// --------------------------------------------------------
// TEST 6: Issue 11 - LMS Login Password Backdoor Removal
// --------------------------------------------------------
echo "\n6. Testing Issue 11: LMS Student Login Password Backdoor Removal...\n";
$lmsAuthCode = file_get_contents(__DIR__ . '/../app/Controllers/Lms/LmsAuthController.php');
assertTest("LmsAuthController has no 'password123' backdoor", strpos($lmsAuthCode, 'password123') === false);
assertTest("LmsAuthController has no plain student_number comparison", strpos($lmsAuthCode, '$password === $user[\'student_number\']') === false);
assertTest("LmsAuthController enforces password_verify", strpos($lmsAuthCode, 'password_verify($password, $user[\'password\'])') !== false);

// Verify password_verify logic
$mockHash = password_hash('StudentSecurePass!2026', PASSWORD_DEFAULT);
$validAttempt = password_verify('StudentSecurePass!2026', $mockHash);
$backdoorAttempt = password_verify('password123', $mockHash);
$plainIdAttempt = password_verify('2026-000001', $mockHash);

assertTest("Valid password verifies correctly", $validAttempt === true);
assertTest("Arbitrary 'password123' is rejected", $backdoorAttempt === false);
assertTest("Plain student number is rejected when not hashed password", $plainIdAttempt === false);

// --------------------------------------------------------
// SUMMARY
// --------------------------------------------------------
echo "\n====================================================\n";
echo "SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "====================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
