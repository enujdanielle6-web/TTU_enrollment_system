<?php
namespace App\Controllers\Admin\Registrar;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use PDOException;
use Exception;

class SubjectController extends BaseController
{
    /**
     * Calculates comprehensive usage metrics for a subject across all database consumers.
     */
    public static function getSubjectUsageDetails(PDO $pdo, int $subjectId): array
    {
        // 1. Draft Curricula
        $draftStmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM college_curriculum_subjects ccs JOIN college_curricula cc ON ccs.curriculum_id = cc.id WHERE ccs.subject_id = ? AND cc.status = 'draft') +
                (SELECT COUNT(*) FROM shs_curriculum_subjects scs JOIN shs_curricula sc ON scs.curriculum_id = sc.id WHERE scs.subject_id = ? AND sc.status = 'draft')
        ");
        $draftStmt->execute([$subjectId, $subjectId]);
        $draftCount = (int)$draftStmt->fetchColumn();

        // 2. Active Curricula
        $activeStmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM college_curriculum_subjects ccs JOIN college_curricula cc ON ccs.curriculum_id = cc.id WHERE ccs.subject_id = ? AND cc.status = 'active') +
                (SELECT COUNT(*) FROM shs_curriculum_subjects scs JOIN shs_curricula sc ON scs.curriculum_id = sc.id WHERE scs.subject_id = ? AND sc.status = 'active')
        ");
        $activeStmt->execute([$subjectId, $subjectId]);
        $activeCount = (int)$activeStmt->fetchColumn();

        // 3. Archived Curricula
        $archivedStmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM college_curriculum_subjects ccs JOIN college_curricula cc ON ccs.curriculum_id = cc.id WHERE ccs.subject_id = ? AND cc.status = 'archived') +
                (SELECT COUNT(*) FROM shs_curriculum_subjects scs JOIN shs_curricula sc ON scs.curriculum_id = sc.id WHERE scs.subject_id = ? AND sc.status = 'archived')
        ");
        $archivedStmt->execute([$subjectId, $subjectId]);
        $archivedCount = (int)$archivedStmt->fetchColumn();

        // 4. Section Timetables
        $sectionStmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM college_section_subjects WHERE subject_id = ?) +
                (SELECT COUNT(*) FROM shs_section_subjects WHERE subject_id = ?)
        ");
        $sectionStmt->execute([$subjectId, $subjectId]);
        $sectionCount = (int)$sectionStmt->fetchColumn();

        // 5. Official Student Enrollments
        $enrollStmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM college_enrollments WHERE subject_id = ?) +
                (SELECT COUNT(*) FROM shs_enrollments WHERE subject_id = ?)
        ");
        $enrollStmt->execute([$subjectId, $subjectId]);
        $enrollmentCount = (int)$enrollStmt->fetchColumn();

        // 6. LMS Courses
        $lmsStmt = $pdo->prepare("SELECT COUNT(*) FROM lms_courses WHERE subject_id = ?");
        $lmsStmt->execute([$subjectId]);
        $lmsCount = (int)$lmsStmt->fetchColumn();

        $totalUsage = $draftCount + $activeCount + $archivedCount + $sectionCount + $enrollmentCount + $lmsCount;
        $isLocked = ($activeCount + $archivedCount + $sectionCount + $enrollmentCount + $lmsCount) > 0;

        $level = 'Level 0 (Unused)';
        if ($isLocked) {
            if ($enrollmentCount > 0 || $lmsCount > 0) {
                $level = 'Level 3 (Historical/Operational)';
            } else {
                $level = 'Level 2 (Active Structural)';
            }
        } elseif ($draftCount > 0) {
            $level = 'Level 1 (Draft-Only)';
        }

        return [
            'draft_curricula_count' => $draftCount,
            'active_curricula_count' => $activeCount,
            'archived_curricula_count' => $archivedCount,
            'section_count' => $sectionCount,
            'enrollment_count' => $enrollmentCount,
            'lms_course_count' => $lmsCount,
            'total_usage' => $totalUsage,
            'is_locked' => $isLocked,
            'level' => $level
        ];
    }

    public function index(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        $pageTitle = 'Subjects - Admin Portal';

        $subjects = [];
        try {
            $stmt = $pdo->query('
                SELECT 
                    s.*,
                    (SELECT COUNT(*) FROM college_curriculum_subjects ccs JOIN college_curricula cc ON ccs.curriculum_id = cc.id WHERE ccs.subject_id = s.id AND cc.status = \'draft\') +
                    (SELECT COUNT(*) FROM shs_curriculum_subjects scs JOIN shs_curricula sc ON scs.curriculum_id = sc.id WHERE scs.subject_id = s.id AND sc.status = \'draft\') as draft_count,
                    
                    (SELECT COUNT(*) FROM college_curriculum_subjects ccs JOIN college_curricula cc ON ccs.curriculum_id = cc.id WHERE ccs.subject_id = s.id AND cc.status = \'active\') +
                    (SELECT COUNT(*) FROM shs_curriculum_subjects scs JOIN shs_curricula sc ON scs.curriculum_id = sc.id WHERE scs.subject_id = s.id AND sc.status = \'active\') as active_count,

                    (SELECT COUNT(*) FROM college_curriculum_subjects ccs JOIN college_curricula cc ON ccs.curriculum_id = cc.id WHERE ccs.subject_id = s.id AND cc.status = \'archived\') +
                    (SELECT COUNT(*) FROM shs_curriculum_subjects scs JOIN shs_curricula sc ON scs.curriculum_id = sc.id WHERE scs.subject_id = s.id AND sc.status = \'archived\') as archived_count,

                    (SELECT COUNT(*) FROM college_section_subjects WHERE subject_id = s.id) +
                    (SELECT COUNT(*) FROM shs_section_subjects WHERE subject_id = s.id) as section_count,

                    (SELECT COUNT(*) FROM college_enrollments WHERE subject_id = s.id) +
                    (SELECT COUNT(*) FROM shs_enrollments WHERE subject_id = s.id) as enrollment_count,

                    (SELECT COUNT(*) FROM lms_courses WHERE subject_id = s.id) as lms_count
                FROM subjects s 
                ORDER BY s.subject_code ASC
            ');
            $subjectsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($subjectsRaw as $sub) {
                $draft = (int)$sub['draft_count'];
                $active = (int)$sub['active_count'];
                $archived = (int)$sub['archived_count'];
                $section = (int)$sub['section_count'];
                $enroll = (int)$sub['enrollment_count'];
                $lms = (int)$sub['lms_count'];

                $total = $draft + $active + $archived + $section + $enroll + $lms;
                $isLocked = ($active + $archived + $section + $enroll + $lms) > 0;

                $usageParts = [];
                $currTotal = $draft + $active + $archived;
                if ($currTotal > 0) $usageParts[] = $currTotal . ' Curricula';
                if ($section > 0) $usageParts[] = $section . ' Sections';
                if ($enroll > 0) $usageParts[] = $enroll . ' Enrollments';
                if ($lms > 0) $usageParts[] = $lms . ' LMS';

                $sub['total_usage'] = $total;
                $sub['is_locked'] = $isLocked;
                $sub['usage_summary'] = !empty($usageParts) ? implode(' • ', $usageParts) : 'Unused';
                $subjects[] = $sub;
            }
        } catch (PDOException $e) {
            error_log('Failed to fetch subjects: ' . $e->getMessage());
        }

        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        return $this->render('admin/registrar/subjects', get_defined_vars());
    }

    public function process(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response->redirect("/sia/admin/registrar/subjects.php");
            return;
        }

        $action = $_POST['action'] ?? '';

        // ----------------------------------------------------
        // 1. ADD SUBJECT (Starts Active)
        // ----------------------------------------------------
        if ($action === 'add') {
            $code = strtoupper(trim($_POST['subject_code'] ?? ''));
            $name = trim($_POST['subject_name'] ?? '');
            $units = (int) ($_POST['units'] ?? 3);
            $type = trim($_POST['subject_type'] ?? 'Lecture');
            $desc = trim($_POST['description'] ?? '');
            $level = trim($_POST['education_level'] ?? 'College');

            if ($code === '' || $name === '') {
                $_SESSION['error_msg'] = 'Subject code and name are required.';
                $response->redirect("/sia/admin/registrar/subjects.php");
                return;
            }
            
            if ($units < 0 || $units > 12) {
                $_SESSION['error_msg'] = 'Subject units must be between 0 and 12.';
                $response->redirect("/sia/admin/registrar/subjects.php");
                return;
            }

            try {
                $stmt = $pdo->prepare('
                    INSERT INTO subjects (subject_code, subject_name, units, subject_type, description, education_level, status) 
                    VALUES (:code, :name, :units, :type, :desc, :level, 1)
                ');
                $stmt->execute([
                    'code' => $code,
                    'name' => $name,
                    'units' => $units,
                    'type' => $type,
                    'desc' => $desc ?: null,
                    'level' => $level
                ]);
                $_SESSION['success_msg'] = "Subject '{$code}' added successfully to the catalog.";
                logActivity((int)$_SESSION['user_id'], 'bi-journal-plus', 'Subject Added', "Added subject: " . strtoupper($code));
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['error_msg'] = "Subject Code '{$code}' already exists.";
                } else {
                    $_SESSION['error_msg'] = 'Failed to add subject: ' . $e->getMessage();
                }
            }
        } 

        // ----------------------------------------------------
        // 2. EDIT SUBJECT (Guarded Immutability)
        // ----------------------------------------------------
        elseif ($action === 'edit') {
            $id = (int) ($_POST['subject_id'] ?? 0);
            $code = strtoupper(trim($_POST['subject_code'] ?? ''));
            $name = trim($_POST['subject_name'] ?? '');
            $units = (int) ($_POST['units'] ?? 3);
            $type = trim($_POST['subject_type'] ?? 'Lecture');
            $desc = trim($_POST['description'] ?? '');
            $level = trim($_POST['education_level'] ?? 'College');
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

            if ($id <= 0 || $code === '' || $name === '') {
                $_SESSION['error_msg'] = 'Subject ID, code, and name are required.';
                $response->redirect("/sia/admin/registrar/subjects.php");
                return;
            }
            
            if ($units < 0 || $units > 12) {
                $_SESSION['error_msg'] = 'Subject units must be between 0 and 12.';
                $response->redirect("/sia/admin/registrar/subjects.php");
                return;
            }

            try {
                // Fetch current subject data
                $fetchStmt = $pdo->prepare('SELECT * FROM subjects WHERE id = ?');
                $fetchStmt->execute([$id]);
                $currentSubject = $fetchStmt->fetch(PDO::FETCH_ASSOC);

                if (!$currentSubject) {
                    $_SESSION['error_msg'] = 'Subject not found.';
                    $response->redirect("/sia/admin/registrar/subjects.php");
                    return;
                }

                $usage = self::getSubjectUsageDetails($pdo, $id);

                // Immutability Guard: If locked by active/archived curricula, sections, enrollments, or LMS
                if ($usage['is_locked']) {
                    $codeChanged = ($currentSubject['subject_code'] !== $code);
                    $unitsChanged = ((int)$currentSubject['units'] !== $units);
                    $typeChanged = ($currentSubject['subject_type'] !== $type);
                    $levelChanged = ($currentSubject['education_level'] !== $level);

                    if ($codeChanged || $unitsChanged || $typeChanged || $levelChanged) {
                        $_SESSION['error_msg'] = "Action Denied: Structural fields (Code, Units, Type, Level) are locked because '{$currentSubject['subject_code']}' is in active or historical use ({$usage['total_usage']} references). To change structural specifications, create a new subject record and retire this subject by setting its status to Inactive.";
                        $response->redirect("/sia/admin/registrar/subjects.php");
                        return;
                    }

                    // Allow editing only non-structural fields (Name, Description, Status)
                    $stmt = $pdo->prepare('
                        UPDATE subjects 
                        SET subject_name = :name, description = :desc, status = :status 
                        WHERE id = :id
                    ');
                    $stmt->execute([
                        'name' => $name,
                        'desc' => $desc ?: null,
                        'status' => $status,
                        'id' => $id
                    ]);
                } else {
                    // Level 0 (Unused) or Level 1 (Draft-Only): Allow updating all fields
                    $stmt = $pdo->prepare('
                        UPDATE subjects 
                        SET subject_code = :code, subject_name = :name, units = :units, subject_type = :type, description = :desc, education_level = :level, status = :status 
                        WHERE id = :id
                    ');
                    $stmt->execute([
                        'code' => $code,
                        'name' => $name,
                        'units' => $units,
                        'type' => $type,
                        'desc' => $desc ?: null,
                        'level' => $level,
                        'status' => $status,
                        'id' => $id
                    ]);
                }

                $_SESSION['success_msg'] = "Subject '{$code}' updated successfully.";
                logActivity((int)$_SESSION['user_id'], 'bi-journal-text', 'Subject Updated', "Updated subject details for: " . strtoupper($code));
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['error_msg'] = "Subject Code '{$code}' already exists.";
                } else {
                    $_SESSION['error_msg'] = 'Failed to update subject: ' . $e->getMessage();
                }
            }
        } 

        // ----------------------------------------------------
        // 3. DELETE SUBJECT (Zero References Only)
        // ----------------------------------------------------
        elseif ($action === 'delete') {
            $id = (int) ($_POST['subject_id'] ?? 0);
            
            if ($id <= 0) {
                $_SESSION['error_msg'] = 'Invalid subject ID.';
                $response->redirect("/sia/admin/registrar/subjects.php");
                return;
            }
            
            try {
                $fetchStmt = $pdo->prepare('SELECT * FROM subjects WHERE id = ?');
                $fetchStmt->execute([$id]);
                $currentSubject = $fetchStmt->fetch(PDO::FETCH_ASSOC);

                if (!$currentSubject) {
                    $_SESSION['error_msg'] = 'Subject not found.';
                    $response->redirect("/sia/admin/registrar/subjects.php");
                    return;
                }

                $usage = self::getSubjectUsageDetails($pdo, $id);

                // Absolute Rule: Total usage must be exactly 0
                if ($usage['total_usage'] > 0) {
                    $_SESSION['error_msg'] = "Cannot delete subject: '{$currentSubject['subject_code']}' is referenced by {$usage['total_usage']} record(s) (Curricula, Sections, Enrollments, or LMS). Set the subject to Inactive to retire it.";
                    $response->redirect("/sia/admin/registrar/subjects.php");
                    return;
                }

                // Delete unused subject (protected by DB RESTRICT as well)
                $stmt = $pdo->prepare('DELETE FROM subjects WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $_SESSION['success_msg'] = "Unused subject '{$currentSubject['subject_code']}' deleted successfully.";
                logActivity((int)$_SESSION['user_id'], 'bi-journal-minus', 'Subject Deleted', "Deleted unused subject ID: $id ({$currentSubject['subject_code']})");
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Failed to delete subject: ' . $e->getMessage();
            }
        }

        // ----------------------------------------------------
        // 4. TOGGLE STATUS (Active <-> Inactive)
        // ----------------------------------------------------
        elseif ($action === 'toggle_status') {
            $id = (int) ($_POST['subject_id'] ?? 0);
            
            if ($id <= 0) {
                $_SESSION['error_msg'] = 'Invalid subject ID.';
                $response->redirect("/sia/admin/registrar/subjects.php");
                return;
            }

            try {
                $fetchStmt = $pdo->prepare('SELECT * FROM subjects WHERE id = ?');
                $fetchStmt->execute([$id]);
                $currentSubject = $fetchStmt->fetch(PDO::FETCH_ASSOC);

                if (!$currentSubject) {
                    $_SESSION['error_msg'] = 'Subject not found.';
                    $response->redirect("/sia/admin/registrar/subjects.php");
                    return;
                }

                $newStatus = ((int)$currentSubject['status'] === 1) ? 0 : 1;
                $statusLabel = ($newStatus === 1) ? 'Active' : 'Inactive';

                $stmt = $pdo->prepare('UPDATE subjects SET status = ? WHERE id = ?');
                $stmt->execute([$newStatus, $id]);

                $_SESSION['success_msg'] = "Subject '{$currentSubject['subject_code']}' is now {$statusLabel}.";
                logActivity((int)$_SESSION['user_id'], 'bi-toggle-on', 'Subject Status Changed', "Changed status of {$currentSubject['subject_code']} to {$statusLabel}");
            } catch (PDOException $e) {
                $_SESSION['error_msg'] = 'Failed to update subject status: ' . $e->getMessage();
            }
        }

        $response->redirect("/sia/admin/registrar/subjects.php");
        return;
    }
}
