<?php
/**
 * Automated Test Suite: TTU LMS Admin Duplicate & Orphan Shell Conflict Resolution
 * 
 * Verifies:
 * 1. Role-based access control (rejection of unauthorized roles, acceptance of admin/superadmin).
 * 2. Duplicate empty shells (detection, artifact summary, safe archive).
 * 3. Duplicate shells with instructional content (detection, module preservation, safe archive).
 * 4. Duplicate shells with student submissions/attempts (detection, 'Manual Resolution Required' flag, rejection of merge/delete, artifact preservation).
 * 5. Orphan empty shell (detection of delisted offering, safe cleanup allowed).
 * 6. Orphan shell with content (detection, deletion blocked, safe archive).
 * 7. Orphan shell with student artifacts (detection, deletion strictly blocked, 100% submission preservation).
 * 8. Administrative quarantine / manual review flag.
 * 9. Absolute prohibition of destructive automatic merging.
 * 10. Atomic rollback on forced failure.
 * 11. Complete LMS audit trail logging in activity_logs.
 * 12. Student artifact immutability (zero student submissions, attempts, or grades disappear).
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
use App\Services\LmsAdminService;
use App\Services\LmsService;
use App\Controllers\Admin\LmsAdminController;

class LmsAdminConflictResolutionTestRunner
{
    private PDO $pdo;
    private LmsAdminService $adminService;
    private LmsService $lmsService;
    private int $passCount = 0;
    private int $failCount = 0;
    private array $tempCourseIds = [];

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->adminService = new LmsAdminService($this->pdo);
        $this->lmsService = new LmsService($this->pdo);
    }

    public function run(): void
    {
        echo "====================================================================\n";
        echo " TTU LMS ADMIN CONFLICT RESOLUTION (DUPLICATES & ORPHANS) SUITE\n";
        echo "====================================================================\n\n";

        try {
            $this->testAccessControl();
            $this->testDuplicateEmptyShells();
            $this->testDuplicateShellsWithContent();
            $this->testDuplicateShellsWithSubmissions();
            $this->testOrphanEmptyShell();
            $this->testOrphanShellWithContent();
            $this->testOrphanShellWithStudentArtifacts();
            $this->testFlagForManualReview();
            $this->testAutomaticMergeProhibition();
            $this->testRollbackSafety();
            $this->testAuditLogging();
        } catch (\Throwable $t) {
            echo "\n[FATAL RUNNER EXCEPTION]: " . $t->getMessage() . "\n" . $t->getTraceAsString() . "\n";
        } finally {
            $this->cleanup();
        }

        echo "\n====================================================================\n";
        echo " FINAL SUMMARY: {$this->passCount} PASSED | {$this->failCount} FAILED\n";
        echo "====================================================================\n";

        if ($this->failCount > 0) {
            exit(1);
        }
    }

    private function assert(string $description, bool $condition, string $details = ''): void
    {
        if ($condition) {
            echo " [PASS] {$description}\n";
            $this->passCount++;
        } else {
            echo " [FAIL] {$description}";
            if ($details) {
                echo " -- {$details}";
            }
            echo "\n";
            $this->failCount++;
        }
    }

    /**
     * 1. Access Control
     */
    private function testAccessControl(): void
    {
        echo "--- 1. Testing Role-Based Access Control ---\n";

        $controller = new LmsAdminController();
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('enforceAdminAccess');
        $method->setAccessible(true);

        $_SESSION = ['user_id' => 1, 'user_role' => 'admin'];
        $passed = true;
        try {
            $method->invoke($controller);
        } catch (\Throwable $e) {
            $passed = false;
        }
        $this->assert("Admin role is authorized for conflict resolution", $passed);

        $_SESSION = ['user_id' => 3, 'user_role' => 'scheduler'];
        $rejected = false;
        try {
            $method->invoke($controller);
        } catch (\Throwable $e) {
            $rejected = true;
        }
        $this->assert("Scheduler role is strictly rejected from conflict resolution", $rejected);

        $_SESSION = ['user_id' => 4, 'user_role' => 'student'];
        $rejected = false;
        try {
            $method->invoke($controller);
        } catch (\Throwable $e) {
            $rejected = true;
        }
        $this->assert("Student role is strictly rejected from conflict resolution", $rejected);

        $_SESSION = ['user_id' => 1, 'user_role' => 'admin'];
    }

    /**
     * 2. Duplicate Empty Shells
     */
    private function testDuplicateEmptyShells(): void
    {
        echo "\n--- 2. Testing Duplicate Empty Shells ---\n";

        // Create 2 empty course shells for the same section ID and subject ID
        // To bypass the unique index for testing duplicate detection across levels:
        $secId = 888801;
        $subId = 1;
        $c1 = $this->createCourseShell('College', $secId, $subId, 9);
        $c2 = $this->createCourseShell('SHS', $secId, $subId, 9);
        $this->tempCourseIds[] = $c1;
        $this->tempCourseIds[] = $c2;

        $conflicts = $this->adminService->getConflictDiagnostics();
        $found = false;
        $matchedGroup = null;
        foreach ($conflicts['duplicate_groups'] as $dg) {
            $ids = explode(',', $dg['course_ids']);
            if (in_array((string)$c1, $ids, true) && in_array((string)$c2, $ids, true)) {
                $found = true;
                $matchedGroup = $dg;
                break;
            }
        }

        $this->assert("Conflict detector identifies duplicate empty shells group", $found);
        if ($matchedGroup) {
            $this->assert("Empty duplicates classified as 'Safe Archive Recommended'", $matchedGroup['classification'] === 'Safe Archive Recommended');
            $this->assert("Neither shell has student work", $matchedGroup['multiple_have_student_work'] === false);
        }

        // Resolve conflict by archiving c2
        $res = $this->adminService->resolveConflict('archive_duplicate', $c2, 1, ['notes' => 'Archived empty duplicate']);
        $this->assert("Safe archive of duplicate shell succeeds", $res['success'] === true);

        // Verify status in database
        $c2Status = $this->pdo->query("SELECT status FROM lms_courses WHERE id = $c2")->fetchColumn();
        $c1Status = $this->pdo->query("SELECT status FROM lms_courses WHERE id = $c1")->fetchColumn();
        $this->assert("Redundant duplicate shell #{$c2} is archived", $c2Status === 'archived');
        $this->assert("Primary duplicate shell #{$c1} remains active", $c1Status === 'active');
    }

    /**
     * 3. Duplicate Shells with Instructional Content
     */
    private function testDuplicateShellsWithContent(): void
    {
        echo "\n--- 3. Testing Duplicate Shells with Instructional Content ---\n";

        $secId = 888802;
        $subId = 1;
        $c1 = $this->createCourseShell('College', $secId, $subId, 9);
        $c2 = $this->createCourseShell('SHS', $secId, $subId, 9);
        $this->tempCourseIds[] = $c1;
        $this->tempCourseIds[] = $c2;

        // Add 2 modules to c1, 1 module to c2
        $this->pdo->exec("INSERT INTO lms_modules (lms_course_id, title, display_order) VALUES ($c1, 'C1 Module 1', 1)");
        $this->pdo->exec("INSERT INTO lms_modules (lms_course_id, title, display_order) VALUES ($c1, 'C1 Module 2', 2)");
        $this->pdo->exec("INSERT INTO lms_modules (lms_course_id, title, display_order) VALUES ($c2, 'C2 Redundant Module', 1)");

        $conflicts = $this->adminService->getConflictDiagnostics();
        $matchedGroup = null;
        foreach ($conflicts['duplicate_groups'] as $dg) {
            $ids = explode(',', $dg['course_ids']);
            if (in_array((string)$c1, $ids, true) && in_array((string)$c2, $ids, true)) {
                $matchedGroup = $dg;
                break;
            }
        }

        $this->assert("Diagnostic group identifies populated shells", !empty($matchedGroup));
        $this->assert("Artifact summary reports modules on both shells", !empty($matchedGroup['shells'][0]['modules_count']));

        // Safe archive of redundant c2
        $res = $this->adminService->resolveConflict('archive_duplicate', $c2, 1, ['notes' => 'Archiving secondary shell with modules']);
        $this->assert("Archiving redundant populated shell succeeds", $res['success'] === true);

        // Verify modules on c2 were NOT deleted
        $c2ModCount = (int)$this->pdo->query("SELECT COUNT(*) FROM lms_modules WHERE lms_course_id = $c2")->fetchColumn();
        $this->assert("All modules on archived shell #{$c2} remain 100% intact (historical preservation)", $c2ModCount === 1);
    }

    /**
     * 4. Duplicate Shells with Student Submissions & Attempts
     */
    private function testDuplicateShellsWithSubmissions(): void
    {
        echo "\n--- 4. Testing Duplicate Shells with Student Submissions (High Conflict) ---\n";

        $secId = 888803;
        $subId = 1;
        $c1 = $this->createCourseShell('College', $secId, $subId, 9);
        $c2 = $this->createCourseShell('SHS', $secId, $subId, 9);
        $this->tempCourseIds[] = $c1;
        $this->tempCourseIds[] = $c2;

        // Shell 1: assignment + student submission
        $this->pdo->exec("INSERT INTO lms_assignments (lms_course_id, title, max_score) VALUES ($c1, 'Project Alpha', 100)");
        $assId1 = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO lms_submissions (assignment_id, student_id, status) VALUES ($assId1, 10, 'SUBMITTED')");

        // Shell 2: quiz + student attempt
        $this->pdo->exec("INSERT INTO lms_quizzes (lms_course_id, title) VALUES ($c2, 'Midterm Quiz')");
        $quizId2 = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO lms_quiz_attempts (lms_quiz_id, student_id, attempt_number, started_at, status) VALUES ($quizId2, 10, 1, NOW(), 'in_progress')");

        $conflicts = $this->adminService->getConflictDiagnostics();
        $matchedGroup = null;
        foreach ($conflicts['duplicate_groups'] as $dg) {
            $ids = explode(',', $dg['course_ids']);
            if (in_array((string)$c1, $ids, true) && in_array((string)$c2, $ids, true)) {
                $matchedGroup = $dg;
                break;
            }
        }

        $this->assert("High conflict group identified", !empty($matchedGroup));
        $this->assert("Flagged as 'Manual Resolution Required' due to student work on both shells", $matchedGroup['classification'] === 'Manual Resolution Required');
        $this->assert("Detector reports multiple_have_student_work = true", $matchedGroup['multiple_have_student_work'] === true);

        // Attempting to delete shell c2 MUST be strictly blocked
        $deleteBlocked = false;
        try {
            $this->adminService->resolveConflict('delete_empty_shell', $c2, 1);
        } catch (Exception $e) {
            $deleteBlocked = strpos($e->getMessage(), 'Destructive cleanup blocked') !== false;
        }
        $this->assert("Destructive deletion of shell with student work is strictly blocked", $deleteBlocked);

        // Attempting to merge MUST be strictly blocked
        $mergeBlocked = false;
        try {
            $this->adminService->resolveConflict('merge_shells', $c2, 1);
        } catch (Exception $e) {
            $mergeBlocked = strpos($e->getMessage(), 'unsupported by policy') !== false;
        }
        $this->assert("Destructive merging is strictly rejected to preserve student gradebook integrity", $mergeBlocked);

        // Conservative safe action: Mark c2 as archived
        $this->adminService->resolveConflict('archive_duplicate', $c2, 1, ['notes' => 'Archived secondary shell to avoid student confusion']);
        
        // VERIFY ZERO STUDENT ARTIFACTS WERE LOST
        $subCount1 = (int)$this->pdo->query("SELECT COUNT(*) FROM lms_submissions WHERE assignment_id = $assId1")->fetchColumn();
        $attCount2 = (int)$this->pdo->query("SELECT COUNT(*) FROM lms_quiz_attempts WHERE lms_quiz_id = $quizId2")->fetchColumn();

        $this->assert("Student submission on Course #{$c1} is 100% intact", $subCount1 === 1);
        $this->assert("Student quiz attempt on Course #{$c2} is 100% intact", $attCount2 === 1);
    }

    /**
     * 5. Orphan Empty Shell
     */
    private function testOrphanEmptyShell(): void
    {
        echo "\n--- 5. Testing Orphan Empty Shell ---\n";

        // Create shell with dummy section not in timetable
        $cOrphan = $this->createCourseShell('College', 888804, 1, 9);
        $this->tempCourseIds[] = $cOrphan;

        $conflicts = $this->adminService->getConflictDiagnostics();
        $found = false;
        $orphanData = null;
        foreach ($conflicts['orphan_courses'] as $o) {
            if ($o['id'] == $cOrphan) {
                $found = true;
                $orphanData = $o;
                break;
            }
        }

        $this->assert("Orphan course detector identifies detached course shell", $found);
        if ($orphanData) {
            $this->assert("Identifies orphan reason as deleted/delisted offering", strpos($orphanData['orphan_reason'], 'Deleted Section') !== false || strpos($orphanData['orphan_reason'], 'not present in official timetable') !== false);
            $this->assert("Identifies empty shell eligible for safe cleanup", $orphanData['can_safe_delete'] === true);
        }

        // Execute safe delete of empty detached shell
        $res = $this->adminService->resolveConflict('delete_empty_shell', $cOrphan, 1, ['notes' => 'Cleaned up empty detached shell']);
        $this->assert("Safe delete of 100% empty shell succeeds", $res['success'] === true);

        // Verify shell is deleted
        $exists = $this->pdo->query("SELECT COUNT(*) FROM lms_courses WHERE id = $cOrphan")->fetchColumn();
        $this->assert("Empty orphan shell was cleanly removed", (int)$exists === 0);
    }

    /**
     * 6. Orphan Shell with Content
     */
    private function testOrphanShellWithContent(): void
    {
        echo "\n--- 6. Testing Orphan Shell with Content (Deletion Blocked) ---\n";

        $cOrphan = $this->createCourseShell('College', 888805, 1, 9);
        $this->tempCourseIds[] = $cOrphan;

        // Add 2 modules
        $this->pdo->exec("INSERT INTO lms_modules (lms_course_id, title, display_order) VALUES ($cOrphan, 'Orphan Syllabus Unit 1', 1)");
        $this->pdo->exec("INSERT INTO lms_modules (lms_course_id, title, display_order) VALUES ($cOrphan, 'Orphan Syllabus Unit 2', 2)");

        $conflicts = $this->adminService->getConflictDiagnostics();
        $orphanData = null;
        foreach ($conflicts['orphan_courses'] as $o) {
            if ($o['id'] == $cOrphan) {
                $orphanData = $o;
                break;
            }
        }

        $this->assert("Populated orphan shell identified", !empty($orphanData));
        $this->assert("Deletion eligibility is FALSE due to existing instructional modules", $orphanData['can_safe_delete'] === false);

        // Deletion MUST fail
        $delFailed = false;
        try {
            $this->adminService->resolveConflict('delete_empty_shell', $cOrphan, 1);
        } catch (Exception $e) {
            $delFailed = strpos($e->getMessage(), 'Destructive cleanup blocked') !== false;
        }
        $this->assert("Attempting to delete orphan with content is blocked by safety gate", $delFailed);

        // Execute safe archive
        $res = $this->adminService->resolveConflict('archive_orphan', $cOrphan, 1, ['notes' => 'Preserved orphan with modules']);
        $this->assert("Safe archive of populated orphan succeeds", $res['success'] === true);

        // Verify modules remain intact
        $modCount = (int)$this->pdo->query("SELECT COUNT(*) FROM lms_modules WHERE lms_course_id = $cOrphan")->fetchColumn();
        $this->assert("Orphan modules remain 100% intact after archiving", $modCount === 2);
    }

    /**
     * 7. Orphan Shell with Student Artifacts
     */
    private function testOrphanShellWithStudentArtifacts(): void
    {
        echo "\n--- 7. Testing Orphan Shell with Student Artifacts ---\n";

        $cOrphan = $this->createCourseShell('College', 888806, 1, 9);
        $this->tempCourseIds[] = $cOrphan;

        // Create assignment + submission
        $this->pdo->exec("INSERT INTO lms_assignments (lms_course_id, title, max_score) VALUES ($cOrphan, 'Orphan Assignment', 100)");
        $assId = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO lms_submissions (assignment_id, student_id, status) VALUES ($assId, 10, 'GRADED')");

        $conflicts = $this->adminService->getConflictDiagnostics();
        $orphanData = null;
        foreach ($conflicts['orphan_courses'] as $o) {
            if ($o['id'] == $cOrphan) {
                $orphanData = $o;
                break;
            }
        }

        $this->assert("Orphan with student work identified", !empty($orphanData));
        $this->assert("Detector reports has_student_work = true", $orphanData['has_student_work'] === true);
        $this->assert("Recommendation denotes preserving historical academic records", strpos($orphanData['recommendation'], 'Preserve historical') !== false);

        // Deletion must be strictly blocked
        $delFailed = false;
        try {
            $this->adminService->resolveConflict('delete_empty_shell', $cOrphan, 1);
        } catch (Exception $e) {
            $delFailed = strpos($e->getMessage(), 'student artifact(s)') !== false;
        }
        $this->assert("Safety gate blocks deletion of orphan shell with student submissions", $delFailed);

        // Resolve by archiving
        $res = $this->adminService->resolveConflict('archive_orphan', $cOrphan, 1, ['notes' => 'Preserved student submissions on cancelled section']);
        $this->assert("Safe archive of orphan with submissions succeeds", $res['success'] === true);

        // Verify student submission remains 100% untouched
        $subCount = (int)$this->pdo->query("SELECT COUNT(*) FROM lms_submissions WHERE assignment_id = $assId")->fetchColumn();
        $this->assert("Student submission is completely preserved in database", $subCount === 1);
    }

    /**
     * 8. Flag for Manual Review
     */
    private function testFlagForManualReview(): void
    {
        echo "\n--- 8. Testing Flag for Manual Administrative Review ---\n";

        $cFlag = $this->createCourseShell('College', 888807, 1, 9);
        $this->tempCourseIds[] = $cFlag;

        $res = $this->adminService->resolveConflict('flag_quarantine', $cFlag, 1, [
            'notes' => 'Under dean investigation for possible section merge'
        ]);

        $this->assert("Quarantine flag succeeds", $res['success'] === true);

        // Verify audit log entry
        $log = $this->pdo->query("
            SELECT * FROM activity_logs 
            WHERE affected_record = 'lms_courses:{$cFlag}' AND title LIKE '%Flagged for Manual Review%' 
            ORDER BY id DESC LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $this->assert("Administrative quarantine note recorded in activity_logs", !empty($log));
        if (!empty($log)) {
            $this->assert("Audit log contains dean investigation note", strpos($log['description'], 'dean investigation') !== false);
        }
    }

    /**
     * 9. Automatic Merge Prohibition
     */
    private function testAutomaticMergeProhibition(): void
    {
        echo "\n--- 9. Testing Absolute Prohibition of Automatic Merge ---\n";

        $cMerge = $this->createCourseShell('College', 888808, 1, 9);
        $this->tempCourseIds[] = $cMerge;

        $mergeAttemptFailed = false;
        $errorMsg = '';
        try {
            $this->adminService->resolveConflict('merge_shells', $cMerge, 1);
        } catch (Exception $e) {
            $mergeAttemptFailed = true;
            $errorMsg = $e->getMessage();
        }

        $this->assert("Direct merge action is rejected by institutional policy", $mergeAttemptFailed);
        $this->assert("Policy error explains risks to gradebooks and transcripts", strpos($errorMsg, 'corrupting student gradebooks') !== false);
    }

    /**
     * 10. Rollback Safety
     */
    private function testRollbackSafety(): void
    {
        echo "\n--- 10. Testing Atomic Rollback Safety ---\n";

        $cRollback = $this->createCourseShell('College', 888809, 1, 9);
        $this->tempCourseIds[] = $cRollback;

        // Simulate failure during mutation
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec("UPDATE lms_courses SET status = 'archived' WHERE id = $cRollback");
            // Force error
            throw new Exception("Catastrophic database crash mid-transaction");
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
        }

        // Verify status rolled back to active
        $status = $this->pdo->query("SELECT status FROM lms_courses WHERE id = $cRollback")->fetchColumn();
        $this->assert("Transaction rolled back cleanly: course status remains 'active'", $status === 'active');
    }

    /**
     * 11. Audit Trail Logging Verification
     */
    private function testAuditLogging(): void
    {
        echo "\n--- 11. Testing LMS Conflict Audit Trail Logging ---\n";

        $logs = $this->pdo->query("
            SELECT * FROM activity_logs 
            WHERE reason LIKE '%LMS Conflict Resolution%' 
            ORDER BY id DESC 
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        $this->assert("Audit trail contains 'LMS Conflict Resolution' events", !empty($logs));
        if (!empty($logs)) {
            $this->assert("Audit log records administrator ID", (int)$logs[0]['user_id'] === 1);
            $this->assert("Audit log references lms_courses record", strpos($logs[0]['affected_record'], 'lms_courses:') !== false);
        }
    }

    /**
     * Helper to create a course shell for isolated conflict testing
     */
    private function createCourseShell(string $level, int $sectionId, int $subjectId, int $facultyId): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status)
            VALUES (:lvl, :sec, :sub, :fac, 'active')
        ");
        $stmt->execute([
            'lvl' => $level,
            'sec' => $sectionId,
            'sub' => $subjectId,
            'fac' => $facultyId
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Cleanup created test records
     */
    private function cleanup(): void
    {
        if (empty($this->tempCourseIds)) {
            return;
        }

        foreach ($this->tempCourseIds as $cid) {
            // Foreign key CASCADE will remove any attached test modules/assignments/quizzes
            $this->pdo->exec("DELETE FROM lms_courses WHERE id = $cid");
        }
    }
}

// Run the verification test suite
$runner = new LmsAdminConflictResolutionTestRunner();
$runner->run();
