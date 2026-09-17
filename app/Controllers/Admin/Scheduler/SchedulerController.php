<?php
namespace App\Controllers\Admin\Scheduler;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use PDOException;
use Exception;

class SchedulerController extends BaseController
{
    public function dashboard(Request $request, Response $response)
    {
        $pageTitle = 'Scheduler Dashboard - Triple T University';
        $pdo = Database::getConnection();

        $stats = [
            'shs_sections' => 0,
            'college_sections' => 0,
            'total_sections' => 0,
            'shs_scheduled_subjects' => 0,
            'college_scheduled_subjects' => 0,
            'total_scheduled_subjects' => 0,
            'total_capacity' => 0,
            'total_enrolled_sections' => 0,
        ];

        $recentCollegeSections = [];
        $recentShsSections = [];
        $systemSettings = [];

        try {
            // Active Section counts
            $stmtShs = $pdo->query('SELECT COUNT(*) FROM shs_sections WHERE status = 1');
            $stats['shs_sections'] = (int) $stmtShs->fetchColumn();

            $stmtCol = $pdo->query('SELECT COUNT(*) FROM college_sections WHERE status = 1');
            $stats['college_sections'] = (int) $stmtCol->fetchColumn();

            $stats['total_sections'] = $stats['shs_sections'] + $stats['college_sections'];

            // Scheduled subject slots
            $stats['college_scheduled_subjects'] = (int) $pdo->query('SELECT COUNT(*) FROM college_section_subjects')->fetchColumn();
            $stats['shs_scheduled_subjects'] = (int) $pdo->query('SELECT COUNT(*) FROM shs_section_subjects')->fetchColumn();
            $stats['total_scheduled_subjects'] = $stats['college_scheduled_subjects'] + $stats['shs_scheduled_subjects'];

            // Capacities & Enrollment in sections
            $colCap = (int) $pdo->query('SELECT COALESCE(SUM(capacity), 0) FROM college_sections WHERE status = 1')->fetchColumn();
            $shsCap = (int) $pdo->query('SELECT COALESCE(SUM(capacity), 0) FROM shs_sections WHERE status = 1')->fetchColumn();
            $stats['total_capacity'] = $colCap + $shsCap;

            $colEnrolled = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE section_id IN (SELECT id FROM college_sections WHERE status = 1) AND status != 'rejected'")->fetchColumn();
            $shsEnrolled = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE section_id IN (SELECT id FROM shs_sections WHERE status = 1) AND status != 'rejected'")->fetchColumn();
            $stats['total_enrolled_sections'] = $colEnrolled + $shsEnrolled;

            // Fetch Active College Sections
            $stmtColSections = $pdo->query("
                SELECT 
                    s.*, 
                    p.code as program_code,
                    p.name as program_name,
                    c.version as curriculum_version,
                    (SELECT COUNT(*) FROM applications a WHERE a.section_id = s.id AND a.status != 'rejected') as current_enrollment,
                    (SELECT COUNT(*) FROM college_section_subjects css WHERE css.college_section_id = s.id) as subject_count
                FROM college_sections s
                INNER JOIN college_programs p ON p.id = s.program_id
                LEFT JOIN college_curricula c ON s.curriculum_id = c.id
                WHERE s.status = 1
                ORDER BY s.id DESC
                LIMIT 6
            ");
            $recentCollegeSections = $stmtColSections->fetchAll(PDO::FETCH_ASSOC);

            // Fetch Active SHS Sections
            $stmtShsSections = $pdo->query("
                SELECT 
                    s.*, 
                    p.code as program_code,
                    p.name as program_name,
                    (SELECT COUNT(*) FROM applications a WHERE a.section_id = s.id AND a.status != 'rejected') as current_enrollment,
                    (SELECT COUNT(*) FROM shs_section_subjects sss WHERE sss.shs_section_id = s.id) as subject_count
                FROM shs_sections s
                INNER JOIN shs_strands p ON p.id = s.strand_id
                WHERE s.status = 1
                ORDER BY s.id DESC
                LIMIT 6
            ");
            $recentShsSections = $stmtShsSections->fetchAll(PDO::FETCH_ASSOC);

            // System settings
            $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
            while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
                $systemSettings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            error_log('Scheduler dashboard data fetch failed: ' . $e->getMessage());
        }

        return $this->render('admin/scheduler/scheduler_dashboard', get_defined_vars());
    }

public function collegeSections(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

// Handle actions (Activate/Deactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['section_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['admin_error'] = 'Invalid CSRF token.';
    } else {
        $sectionId = (int)$_POST['section_id'];
        if ($_POST['action'] === 'delete_section') {
            try {
                $pdo->prepare('DELETE FROM college_section_subjects WHERE college_section_id = ?')->execute([$sectionId]);
                $stmtDel = $pdo->prepare('DELETE FROM college_sections WHERE id = ?');
                $stmtDel->execute([$sectionId]);
                
                if ($stmtDel->rowCount() > 0) {
                    logActivity((int)$_SESSION['user_id'], 'bi-trash', 'Section Deleted', "Deleted section ID #$sectionId", "Section #$sectionId");
                    $_SESSION['admin_success'] = 'Section deleted successfully.';
                } else {
                    $_SESSION['admin_error'] = 'Section not found or could not be deleted.';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['admin_error'] = 'Cannot delete section because it has enrolled students or pending applications.';
                } else {
                    $_SESSION['admin_error'] = 'Database error: ' . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'toggle_status') {
            // Fetch old status
            $stmtOld = $pdo->prepare('SELECT status, section_code FROM college_sections WHERE id = :id');
            $stmtOld->execute(['id' => $sectionId]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

            if ($oldData) {
                $stmt = $pdo->prepare('UPDATE college_sections SET status = IF(status=1, 0, 1) WHERE id = :id');
                $stmt->execute(['id' => $sectionId]);
                
                $newStatus = $oldData['status'] == 1 ? 0 : 1;
                $statusLabel = $newStatus == 1 ? 'Activated' : 'Deactivated';
                logActivity(
                    (int)$_SESSION['user_id'],
                    'bi-toggle-on',
                    'Section ' . $statusLabel,
                    "{$statusLabel} section " . $oldData['section_code'],
                    "Section #$sectionId",
                    ['status' => $oldData['status']],
                    ['status' => $newStatus]
                );

                $_SESSION['admin_success'] = 'Section status updated successfully.';
            }
        }
    }
    $response->redirect("/sia/admin/scheduler/college_sections.php");
    return;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_section') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['admin_error'] = 'Invalid CSRF token.';
    } else {
        $section_code = trim($_POST['section_code'] ?? '');
        $program_id = (int)($_POST['program_id'] ?? 0);
        $curriculum_id = (int)($_POST['curriculum_id'] ?? 0);
        $academic_year = trim($_POST['academic_year'] ?? '');
        $year_level = trim($_POST['year_level'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 40);
        $schedule_type = trim($_POST['schedule_type'] ?? 'Morning');
        $adviser = trim($_POST['adviser'] ?? '');
        
        if ($section_code && $program_id && $curriculum_id && $year_level) {
            try {
                $stmt = $pdo->prepare('INSERT INTO college_sections (section_code, program_id, curriculum_id, academic_year, year_level, semester, capacity, schedule_type, adviser, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
                $stmt->execute([$section_code, $program_id, $curriculum_id, $academic_year ?: null, $year_level, $semester ?: null, $capacity, $schedule_type, $adviser]);
                $newSectionId = (int)$pdo->lastInsertId();
                
                // Auto-import curriculum to college_section_subjects from the selected curriculum
                $currStmt = $pdo->prepare('
                    SELECT subject_id 
                    FROM college_curriculum_subjects 
                    WHERE curriculum_id = ? AND year_level = ? AND (semester = ? OR semester IS NULL OR semester = "")
                ');
                $currStmt->execute([$curriculum_id, $year_level, $semester ?: '']);
                $subjects = $currStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($subjects)) {
                    $insSub = $pdo->prepare("INSERT INTO college_section_subjects (college_section_id, subject_id, capacity, day, start_time, end_time) VALUES (?, ?, ?, 'TBA', '00:00:00', '00:00:00')");
                    foreach ($subjects as $sub) {
                        $insSub->execute([$newSectionId, $sub['subject_id'], $capacity]);
                    }
                }
                
                $_SESSION['admin_success'] = 'Section created successfully based on the selected curriculum.';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $_SESSION['admin_error'] = 'Section code already exists.';
                } else {
                    $_SESSION['admin_error'] = 'Database error: ' . $e->getMessage();
                }
            }
        } else {
            $_SESSION['admin_error'] = 'Please fill in all required fields.';
        }
    }
    $response->redirect("/sia/admin/scheduler/college_sections.php");
    return;
}

try {
    $query = "
        SELECT 
            s.*, 
            p.code as program_code,
            c.version as curriculum_version,
            (SELECT COUNT(*) FROM applications a WHERE a.section_id = s.id AND a.status != 'rejected') as current_enrollment
        FROM college_sections s
        INNER JOIN college_programs p ON p.id = s.program_id
        LEFT JOIN college_curricula c ON s.curriculum_id = c.id
        ORDER BY p.code ASC, s.year_level ASC, s.section_code ASC
    ";
    $stmt = $pdo->query($query);
    $college_sections = $stmt->fetchAll();
    
    // Fetch programs for Add Section modal
    $progStmt = $pdo->query("SELECT id, code, name FROM college_programs WHERE is_active = 1 ORDER BY code ASC");
    $programs = $progStmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching college_sections: ' . $e->getMessage());
    $college_sections = [];
    $programs = [];
}

$pageTitle = 'Section Management - Admin';

        return $this->render('admin/scheduler/college_sections', get_defined_vars());
    }

public function shsSections(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

// Handle actions (Activate/Deactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['section_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['admin_error'] = 'Invalid CSRF token.';
    } else {
        $sectionId = (int)$_POST['section_id'];
        if ($_POST['action'] === 'delete_section') {
            try {
                $pdo->prepare('DELETE FROM shs_section_subjects WHERE shs_section_id = ?')->execute([$sectionId]);
                $stmtDel = $pdo->prepare('DELETE FROM shs_sections WHERE id = ?');
                $stmtDel->execute([$sectionId]);
                
                if ($stmtDel->rowCount() > 0) {
                    logActivity((int)$_SESSION['user_id'], 'bi-trash', 'Section Deleted', "Deleted section ID #$sectionId", "Section #$sectionId");
                    $_SESSION['admin_success'] = 'Section deleted successfully.';
                } else {
                    $_SESSION['admin_error'] = 'Section not found or could not be deleted.';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['admin_error'] = 'Cannot delete section because it has enrolled students or pending applications.';
                } else {
                    $_SESSION['admin_error'] = 'Database error: ' . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'toggle_status') {
            // Fetch old status
            $stmtOld = $pdo->prepare('SELECT status, section_code FROM shs_sections WHERE id = :id');
            $stmtOld->execute(['id' => $sectionId]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

            if ($oldData) {
                $stmt = $pdo->prepare('UPDATE shs_sections SET status = IF(status=1, 0, 1) WHERE id = :id');
                $stmt->execute(['id' => $sectionId]);
                
                $newStatus = $oldData['status'] == 1 ? 0 : 1;
                $statusLabel = $newStatus == 1 ? 'Activated' : 'Deactivated';
                logActivity(
                    (int)$_SESSION['user_id'],
                    'bi-toggle-on',
                    'Section ' . $statusLabel,
                    "{$statusLabel} section " . $oldData['section_code'],
                    "Section #$sectionId",
                    ['status' => $oldData['status']],
                    ['status' => $newStatus]
                );

                $_SESSION['admin_success'] = 'Section status updated successfully.';
            }
        }
    }
    $response->redirect("/sia/admin/scheduler/shs_sections.php");
    return;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_section') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['admin_error'] = 'Invalid CSRF token.';
    } else {
        $section_code = trim($_POST['section_code'] ?? '');
        $strand_id = (int)($_POST['strand_id'] ?? 0);
        $curriculum_id = (int)($_POST['curriculum_id'] ?? 0);
        $grade_level = trim($_POST['grade_level'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 40);
        $schedule_type = trim($_POST['schedule_type'] ?? 'Morning');
        $adviser = trim($_POST['adviser'] ?? '');
        
        if ($section_code && $strand_id && $curriculum_id && $grade_level && $academic_year) {
            try {
                $stmt = $pdo->prepare('INSERT INTO shs_sections (section_code, strand_id, curriculum_id, grade_level, academic_year, capacity, schedule_type, adviser, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
                $stmt->execute([$section_code, $strand_id, $curriculum_id, $grade_level, $academic_year, $capacity, $schedule_type, $adviser]);
                $newSectionId = (int)$pdo->lastInsertId();
                
                // Auto-import curriculum to shs_section_subjects
                $currStmt = $pdo->prepare('
                    SELECT subject_id 
                    FROM shs_curriculum_subjects 
                    WHERE curriculum_id = ? AND grade_level = ? 
                ');
                $currStmt->execute([$curriculum_id, $grade_level]);
                $subjects = $currStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($subjects)) {
                    $insSub = $pdo->prepare("INSERT INTO shs_section_subjects (shs_section_id, subject_id, capacity, day, start_time, end_time) VALUES (?, ?, ?, 'TBA', '00:00:00', '00:00:00')");
                    foreach ($subjects as $sub) {
                        $insSub->execute([$newSectionId, $sub['subject_id'], $capacity]);
                    }
                }
                
                $_SESSION['admin_success'] = 'Section added and curriculum subjects imported successfully.';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $_SESSION['admin_error'] = 'Section code already exists.';
                } else {
                    $_SESSION['admin_error'] = 'Database error: ' . $e->getMessage();
                }
            }
        } else {
            $_SESSION['admin_error'] = 'Please fill in all required fields.';
        }
    }
    $response->redirect("/sia/admin/scheduler/shs_sections.php");
    return;
}

try {
    $query = "
        SELECT 
            s.*, 
            p.code as program_code,
            c.version as curriculum_version,
            (SELECT COUNT(*) FROM applications a WHERE a.section_id = s.id AND a.status != 'rejected') as current_enrollment
        FROM shs_sections s
        INNER JOIN shs_strands p ON p.id = s.strand_id
        LEFT JOIN shs_curricula c ON s.curriculum_id = c.id
        ORDER BY p.code ASC, s.grade_level ASC, s.section_code ASC
    ";
    $stmt = $pdo->query($query);
    $shs_sections = $stmt->fetchAll();
    
    // Fetch programs for Add Section modal
    $progStmt = $pdo->query("SELECT id, code, name FROM shs_strands WHERE is_active = 1 ORDER BY code ASC");
    $programs = $progStmt->fetchAll();

    // Fetch active & archived curricula for Add Section modal
    $currStmt = $pdo->query("SELECT id, strand_id, curriculum_name, version, effective_academic_year, status FROM shs_curricula WHERE status IN ('active', 'archived') ORDER BY strand_id ASC, version DESC");
    $curricula = $currStmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching shs_sections: ' . $e->getMessage());
    $shs_sections = [];
    $programs = [];
    $curricula = [];
}

$pageTitle = 'Section Management - Admin';

        return $this->render('admin/scheduler/shs_sections', get_defined_vars());
    }


    public function builder(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
        $type = $_GET['type'] ?? 'college';
        $sectionId = (int)($_GET['id'] ?? 0);

if ($type === 'shs') {
    requirePermission('shs_sections.manage');
} else {
    requirePermission('college_sections.manage');
}

if ($sectionId <= 0) {
    $_SESSION['admin_error'] = 'Invalid Section ID.';
    header("Location: " . ($type === 'shs' ? 'shs_sections.php' : 'college_sections.php'));
    return;
}

try {
    if ($type === 'shs') {
        $stmt = $pdo->prepare('
            SELECT s.id, s.section_code, s.capacity, s.schedule_type, p.code as program_code, "Senior High School" as category, s.grade_level as year_level, s.strand_id as program_id, s.curriculum_id
            FROM shs_sections s 
            JOIN shs_strands p ON s.strand_id = p.id 
            WHERE s.id = ?
        ');
        $stmt->execute([$sectionId]);
        $section = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$section) {
            $_SESSION['admin_error'] = 'Section not found.';
            $response->redirect("/sia/admin/scheduler/shs_sections.php");
            return;
        }

        if ($section['curriculum_id']) {
            // Auto-sync missing subjects from SHS Curriculum to Section Subjects
            $syncStmt = $pdo->prepare('
                INSERT INTO shs_section_subjects (shs_section_id, subject_id, capacity, day, start_time, end_time)
                SELECT ?, c.subject_id, ?, "TBA", "00:00:00", "00:00:00"
                FROM shs_curriculum_subjects c
                WHERE c.curriculum_id = ? AND c.grade_level = ?
                  AND NOT EXISTS (
                      SELECT 1 FROM shs_section_subjects ss 
                      WHERE ss.shs_section_id = ? AND ss.subject_id = c.subject_id
                  )
            ');
            $syncStmt->execute([$sectionId, $section['capacity'], $section['curriculum_id'], $section['year_level'], $sectionId]);

            // Auto-remove subjects that are no longer in Curriculum
            $delStmt = $pdo->prepare('
                DELETE FROM shs_section_subjects 
                WHERE shs_section_id = ? 
                  AND subject_id NOT IN (
                      SELECT subject_id FROM shs_curriculum_subjects 
                      WHERE curriculum_id = ? AND grade_level = ?
                  )
            ');
            $delStmt->execute([$sectionId, $section['curriculum_id'], $section['year_level']]);
        }

        $subStmt = $pdo->prepare('
            SELECT ss.id, ss.subject_id, ss.capacity, ss.day, ss.start_time, ss.end_time, ss.room, ss.instructor, ss.faculty_user_id, ss.delivery_mode, 
                   sub.subject_code, sub.subject_name, sub.units, c.semester
            FROM shs_section_subjects ss
            JOIN subjects sub ON ss.subject_id = sub.id
            LEFT JOIN shs_curriculum_subjects c ON c.subject_id = ss.subject_id AND c.curriculum_id = ? AND c.grade_level = ?
            WHERE ss.shs_section_id = ?
            ORDER BY c.semester ASC, sub.subject_code ASC
        ');
        $subStmt->execute([$section['curriculum_id'], $section['year_level'], $sectionId]);
        $subjects = $subStmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $stmt = $pdo->prepare('
            SELECT s.id, s.section_code, s.capacity, s.schedule_type, p.code as program_code, "College" as category, s.year_level, s.program_id, s.semester, s.curriculum_id
            FROM college_sections s 
            JOIN college_programs p ON s.program_id = p.id 
            WHERE s.id = ?
        ');
        $stmt->execute([$sectionId]);
        $section = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$section) {
            $_SESSION['admin_error'] = 'Section not found.';
            $response->redirect("/sia/admin/scheduler/college_sections.php");
            return;
        }

        if ($section['curriculum_id']) {
            // Auto-sync missing subjects from Curriculum to Section Subjects
            $syncStmt = $pdo->prepare('
                INSERT INTO college_section_subjects (college_section_id, subject_id, capacity, day, start_time, end_time)
                SELECT ?, ccs.subject_id, ?, "TBA", "00:00:00", "00:00:00"
                FROM college_curriculum_subjects ccs
                WHERE ccs.curriculum_id = ? AND ccs.year_level = ? AND (ccs.semester = ? OR ccs.semester IS NULL OR ccs.semester = "")
                  AND NOT EXISTS (
                      SELECT 1 FROM college_section_subjects css 
                      WHERE css.college_section_id = ? AND css.subject_id = ccs.subject_id
                  )
            ');
            $syncStmt->execute([$sectionId, $section['capacity'], $section['curriculum_id'], $section['year_level'], $section['semester'], $sectionId]);

            // Auto-remove subjects that are no longer in Curriculum
            $delStmt = $pdo->prepare('
                DELETE FROM college_section_subjects 
                WHERE college_section_id = ? 
                  AND subject_id NOT IN (
                      SELECT subject_id FROM college_curriculum_subjects 
                      WHERE curriculum_id = ? AND year_level = ? AND (semester = ? OR semester IS NULL OR semester = "")
                  )
            ');
            $delStmt->execute([$sectionId, $section['curriculum_id'], $section['year_level'], $section['semester']]);
        }

        // Fetch subjects using curriculum display_order
        $subStmt = $pdo->prepare('
            SELECT ss.id, ss.subject_id, ss.capacity, ss.day, ss.start_time, ss.end_time, ss.room, ss.instructor, ss.faculty_user_id, ss.delivery_mode, 
                   sub.subject_code, sub.subject_name, sub.units, ? as semester, ccs.display_order
            FROM college_section_subjects ss
            JOIN subjects sub ON ss.subject_id = sub.id
            LEFT JOIN college_curriculum_subjects ccs 
              ON ccs.subject_id = ss.subject_id 
             AND ccs.curriculum_id = ? 
             AND ccs.year_level = ? 
             AND (ccs.semester = ? OR ccs.semester = "" OR ccs.semester IS NULL)
            WHERE ss.college_section_id = ?
            ORDER BY ccs.display_order ASC, sub.subject_code ASC
        ');
        $subStmt->execute([$section['semester'], $section['curriculum_id'], $section['year_level'], $section['semester'], $sectionId]);
        $subjects = $subStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch active faculty catalog for schedule builder dropdown
    $facultyStmt = $pdo->query("
        SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
               COALESCE(u.employee_id, u.student_number) AS employee_id,
               fp.academic_rank
        FROM users u
        LEFT JOIN faculty_profiles fp ON fp.user_id = u.id
        WHERE u.role = 'faculty' AND u.is_active = 1
        ORDER BY u.last_name ASC, u.first_name ASC
    ");
    $facultyList = $facultyStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    $_SESSION['admin_error'] = 'Database error loading schedule builder.';
    header("Location: " . ($type === 'shs' ? 'shs_sections.php' : 'college_sections.php'));
    return;
}

$pageTitle = 'Schedule Builder - Admin';

        return $this->render('admin/scheduler/schedule_builder', get_defined_vars());
    }
    private function decomposeDays(?string $dayString): array
    {
        if (empty($dayString)) {
            return [];
        }
        $cleaned = strtoupper(trim($dayString));
        if ($cleaned === 'TBA' || empty($cleaned)) {
            return [];
        }
        $map = [
            'MONDAY' => ['M'],
            'TUESDAY' => ['T'],
            'WEDNESDAY' => ['W'],
            'THURSDAY' => ['TH'],
            'FRIDAY' => ['F'],
            'SATURDAY' => ['S'],
            'SUNDAY' => ['SU'],
            'MON' => ['M'],
            'TUE' => ['T'],
            'WED' => ['W'],
            'THU' => ['TH'],
            'FRI' => ['F'],
            'SAT' => ['S'],
            'SUN' => ['SU'],
            'M' => ['M'],
            'T' => ['T'],
            'W' => ['W'],
            'TH' => ['TH'],
            'F' => ['F'],
            'S' => ['S'],
            'SU' => ['SU']
        ];
        if (isset($map[$cleaned])) {
            return $map[$cleaned];
        }

        $days = [];
        if (str_contains($cleaned, 'TH')) {
            $days[] = 'TH';
            $cleaned = str_replace('TH', '', $cleaned);
        }
        if (str_contains($cleaned, 'SU')) {
            $days[] = 'SU';
            $cleaned = str_replace('SU', '', $cleaned);
        }
        for ($i = 0; $i < strlen($cleaned); $i++) {
            $char = $cleaned[$i];
            if (in_array($char, ['M', 'T', 'W', 'F', 'S'])) {
                $days[] = $char;
            }
        }
        return array_values(array_unique($days));
    }

    private function formatDays(array $days): string
    {
        $map = [
            'M' => 'Monday',
            'T' => 'Tuesday',
            'W' => 'Wednesday',
            'TH' => 'Thursday',
            'F' => 'Friday',
            'S' => 'Saturday',
            'SU' => 'Sunday'
        ];
        $formatted = [];
        foreach ($days as $d) {
            $formatted[] = $map[$d] ?? $d;
        }
        return implode(', ', $formatted);
    }

    public function process(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
            return;
        }
        
        $type = $_POST['type'] ?? 'college';
        $sectionId = (int)($_POST['section_id'] ?? 0);
        $schedules = json_decode($_POST['schedules'] ?? '[]', true);
        $deletedIds = json_decode($_POST['deleted_ids'] ?? '[]', true);
        
        if ($sectionId <= 0 || !is_array($schedules)) {
            echo json_encode(['success' => false, 'message' => 'Invalid payload.']);
            return;
        }

        if ($type === 'shs') {
            requirePermission('shs_sections.manage');
            $table = 'shs_section_subjects';
            $secIdCol = 'shs_section_id';
            $secTable = 'shs_sections';
            $academicLevel = 'SHS';
        } else {
            requirePermission('college_sections.manage');
            $table = 'college_section_subjects';
            $secIdCol = 'college_section_id';
            $secTable = 'college_sections';
            $academicLevel = 'College';
        }
        
        try {
            // PHASE 1: Pre-process, normalize times, and dual-resolve instructor/faculty
            $processedSchedules = [];
            $facultyCache = [];
            
            $uStmtById = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id = ?");
            $uStmtByName = $pdo->prepare("SELECT id FROM users WHERE role = 'faculty' AND (CONCAT(first_name, ' ', last_name) = ? OR TRIM(last_name) = ?) LIMIT 1");
            $subStmtById = $pdo->prepare("SELECT subject_code FROM subjects WHERE id = ?");

            foreach ($schedules as $sched) {
                $id = (int)($sched['id'] ?? 0);
                $subjectId = (int)($sched['subject_id'] ?? 0);
                $subjectCode = trim($sched['subject_code'] ?? '');
                if (empty($subjectCode) && $subjectId > 0) {
                    $subStmtById->execute([$subjectId]);
                    $subjectCode = $subStmtById->fetchColumn() ?: "Subject #{$subjectId}";
                }
                
                $day = !empty(trim($sched['day'] ?? '')) ? trim($sched['day']) : null;
                $start = !empty(trim($sched['start_time'] ?? '')) ? trim($sched['start_time']) : null;
                $end = !empty(trim($sched['end_time'] ?? '')) ? trim($sched['end_time']) : null;
                $room = !empty(trim($sched['room'] ?? '')) ? trim($sched['room']) : null;
                $facultyUserId = !empty($sched['faculty_user_id']) ? (int)$sched['faculty_user_id'] : null;
                $instructor = !empty(trim($sched['instructor'] ?? '')) ? trim($sched['instructor']) : null;
                $mode = trim($sched['delivery_mode'] ?? 'Face-to-Face');
                $semester = trim((string)($sched['semester'] ?? '1'));

                // Normalize times to HH:MM:SS
                if ($start) {
                    $start = strlen($start) === 5 ? $start . ':00' : $start;
                }
                if ($end) {
                    $end = strlen($end) === 5 ? $end . ':00' : $end;
                }

                // If any value is TBA or 00:00:00, treat as unassigned
                if ($day === 'TBA' || $start === '00:00:00' || $end === '00:00:00') {
                    $day = null;
                    $start = null;
                    $end = null;
                }

                // Dual-resolution: resolve name from faculty_user_id or vice versa
                if ($facultyUserId && (empty($instructor) || strtoupper($instructor) === 'TBA')) {
                    if (!isset($facultyCache['id_' . $facultyUserId])) {
                        $uStmtById->execute([$facultyUserId]);
                        $facultyCache['id_' . $facultyUserId] = $uStmtById->fetchColumn() ?: 'TBA';
                    }
                    $instructor = $facultyCache['id_' . $facultyUserId];
                } elseif (!$facultyUserId && !empty($instructor) && strtoupper($instructor) !== 'TBA') {
                    if (!isset($facultyCache['name_' . $instructor])) {
                        $uStmtByName->execute([$instructor, $instructor]);
                        $matchedId = $uStmtByName->fetchColumn();
                        $facultyCache['name_' . $instructor] = $matchedId ? (int)$matchedId : null;
                    }
                    $facultyUserId = $facultyCache['name_' . $instructor];
                }

                // Schedule completeness check
                if ($day || $start || $end) {
                    if (!$day || !$start || !$end) {
                        throw new Exception("Incomplete schedule for '{$subjectCode}'. Provide Day, Start Time, and End Time, or leave all blank (TBA).");
                    }
                    if ($start >= $end) {
                        throw new Exception("Start time must be strictly before end time for '{$subjectCode}' ({$start} - {$end}).");
                    }
                }

                $processedSchedules[] = [
                    'id' => $id,
                    'subject_id' => $subjectId,
                    'subject_code' => $subjectCode,
                    'day' => $day,
                    'start_time' => $start,
                    'end_time' => $end,
                    'room' => $room,
                    'faculty_user_id' => $facultyUserId,
                    'instructor' => $instructor,
                    'delivery_mode' => $mode,
                    'semester' => $semester,
                ];
            }

            // Filter active (scheduled) items for conflict checking
            $activeSchedules = array_values(array_filter($processedSchedules, function($s) {
                return !empty($s['day']) && !empty($s['start_time']) && !empty($s['end_time']);
            }));

            // PHASE 2: Intra-payload validation (Section timetable & internal resource collisions)
            $count = count($activeSchedules);
            for ($i = 0; $i < $count; $i++) {
                $s1 = $activeSchedules[$i];
                $days1 = $this->decomposeDays($s1['day']);

                for ($j = $i + 1; $j < $count; $j++) {
                    $s2 = $activeSchedules[$j];
                    $days2 = $this->decomposeDays($s2['day']);
                    $commonDays = array_intersect($days1, $days2);

                    if (empty($commonDays)) {
                        continue;
                    }

                    $timesOverlap = ($s1['start_time'] < $s2['end_time'] && $s1['end_time'] > $s2['start_time']);
                    if (!$timesOverlap) {
                        continue;
                    }

                    $commonDaysStr = $this->formatDays($commonDays);

                    // Validation 2A: Student Timetable Overlap (Same Section Cohort)
                    // For SHS, only conflict if in the same semester. For College, all subjects belong to the section's semester.
                    $sameSemester = ($type !== 'shs') || ($s1['semester'] === $s2['semester']);
                    if ($sameSemester) {
                        throw new Exception("Timetable Conflict: '{$s1['subject_code']}' ({$s1['start_time']} - {$s1['end_time']}) and '{$s2['subject_code']}' ({$s2['start_time']} - {$s2['end_time']}) overlap on {$commonDaysStr} for this section.");
                    }

                    // Validation 2B: Intra-Payload Room Collision (Physical rooms)
                    $room1 = trim($s1['room'] ?? '');
                    $room2 = trim($s2['room'] ?? '');
                    $isRoom1Valid = !empty($room1) && strtoupper($room1) !== 'TBA' && $s1['delivery_mode'] !== 'Online';
                    $isRoom2Valid = !empty($room2) && strtoupper($room2) !== 'TBA' && $s2['delivery_mode'] !== 'Online';
                    if ($isRoom1Valid && $isRoom2Valid && strcasecmp($room1, $room2) === 0) {
                        throw new Exception("Room Conflict: Room '{$room1}' is assigned to both '{$s1['subject_code']}' and '{$s2['subject_code']}' on {$commonDaysStr} at overlapping times ({$s1['start_time']}-{$s1['end_time']} vs {$s2['start_time']}-{$s2['end_time']}).");
                    }

                    // Validation 2C: Intra-Payload Faculty Collision
                    $fac1Id = $s1['faculty_user_id'];
                    $fac2Id = $s2['faculty_user_id'];
                    $inst1 = trim($s1['instructor'] ?? '');
                    $inst2 = trim($s2['instructor'] ?? '');
                    $sameFaculty = false;
                    $facLabel = '';

                    if ($fac1Id && $fac2Id && $fac1Id === $fac2Id) {
                        $sameFaculty = true;
                        $facLabel = $inst1 ?: "Faculty #{$fac1Id}";
                    } elseif (!empty($inst1) && !empty($inst2) && strtoupper($inst1) !== 'TBA' && strtoupper($inst2) !== 'TBA' && strcasecmp($inst1, $inst2) === 0) {
                        $sameFaculty = true;
                        $facLabel = $inst1;
                    }

                    if ($sameFaculty) {
                        throw new Exception("Instructor Conflict: {$facLabel} is assigned to both '{$s1['subject_code']}' and '{$s2['subject_code']}' on {$commonDaysStr} at overlapping times ({$s1['start_time']}-{$s1['end_time']} vs {$s2['start_time']}-{$s2['end_time']}).");
                    }
                }
            }

            // PHASE 3: Cross-Section and Cross-Academic-Level Database Validation
            // Check room conflicts against active sections in both College and SHS
            $collegeRoomSql = '
                SELECT cs.section_code, sub.subject_code, css.day, css.start_time, css.end_time, css.room
                FROM college_section_subjects css
                JOIN college_sections cs ON css.college_section_id = cs.id
                JOIN subjects sub ON css.subject_id = sub.id
                WHERE LOWER(TRIM(css.room)) = LOWER(?)
                  AND css.day IS NOT NULL AND css.day != "" AND css.day != "TBA"
                  AND css.start_time IS NOT NULL AND css.start_time != "00:00:00"
                  AND (css.delivery_mode IS NULL OR css.delivery_mode != "Online")
                  AND cs.status = 1
            ' . ($type === 'college' ? ' AND css.college_section_id != ?' : '');

            $shsRoomSql = '
                SELECT ss.section_code, sub.subject_code, sss.day, sss.start_time, sss.end_time, sss.room
                FROM shs_section_subjects sss
                JOIN shs_sections ss ON sss.shs_section_id = ss.id
                JOIN subjects sub ON sss.subject_id = sub.id
                WHERE LOWER(TRIM(sss.room)) = LOWER(?)
                  AND sss.day IS NOT NULL AND sss.day != "" AND sss.day != "TBA"
                  AND sss.start_time IS NOT NULL AND sss.start_time != "00:00:00"
                  AND (sss.delivery_mode IS NULL OR sss.delivery_mode != "Online")
                  AND ss.status = 1
            ' . ($type === 'shs' ? ' AND sss.shs_section_id != ?' : '');

            $stmtColRoom = $pdo->prepare($collegeRoomSql);
            $stmtShsRoom = $pdo->prepare($shsRoomSql);

            // Faculty checks across College and SHS
            $collegeFacIdSql = '
                SELECT cs.section_code, sub.subject_code, css.day, css.start_time, css.end_time
                FROM college_section_subjects css
                JOIN college_sections cs ON css.college_section_id = cs.id
                JOIN subjects sub ON css.subject_id = sub.id
                WHERE css.faculty_user_id = ?
                  AND css.day IS NOT NULL AND css.day != "" AND css.day != "TBA"
                  AND css.start_time IS NOT NULL AND css.start_time != "00:00:00"
                  AND cs.status = 1
            ' . ($type === 'college' ? ' AND css.college_section_id != ?' : '');

            $shsFacIdSql = '
                SELECT ss.section_code, sub.subject_code, sss.day, sss.start_time, sss.end_time
                FROM shs_section_subjects sss
                JOIN shs_sections ss ON sss.shs_section_id = ss.id
                JOIN subjects sub ON sss.subject_id = sub.id
                WHERE sss.faculty_user_id = ?
                  AND sss.day IS NOT NULL AND sss.day != "" AND sss.day != "TBA"
                  AND sss.start_time IS NOT NULL AND sss.start_time != "00:00:00"
                  AND ss.status = 1
            ' . ($type === 'shs' ? ' AND sss.shs_section_id != ?' : '');

            $stmtColFacId = $pdo->prepare($collegeFacIdSql);
            $stmtShsFacId = $pdo->prepare($shsFacIdSql);

            $collegeFacNameSql = '
                SELECT cs.section_code, sub.subject_code, css.day, css.start_time, css.end_time
                FROM college_section_subjects css
                JOIN college_sections cs ON css.college_section_id = cs.id
                JOIN subjects sub ON css.subject_id = sub.id
                WHERE LOWER(TRIM(css.instructor)) = LOWER(?)
                  AND css.day IS NOT NULL AND css.day != "" AND css.day != "TBA"
                  AND css.start_time IS NOT NULL AND css.start_time != "00:00:00"
                  AND cs.status = 1
            ' . ($type === 'college' ? ' AND css.college_section_id != ?' : '');

            $shsFacNameSql = '
                SELECT ss.section_code, sub.subject_code, sss.day, sss.start_time, sss.end_time
                FROM shs_section_subjects sss
                JOIN shs_sections ss ON sss.shs_section_id = ss.id
                JOIN subjects sub ON sss.subject_id = sub.id
                WHERE LOWER(TRIM(sss.instructor)) = LOWER(?)
                  AND sss.day IS NOT NULL AND sss.day != "" AND sss.day != "TBA"
                  AND sss.start_time IS NOT NULL AND sss.start_time != "00:00:00"
                  AND ss.status = 1
            ' . ($type === 'shs' ? ' AND sss.shs_section_id != ?' : '');

            $stmtColFacName = $pdo->prepare($collegeFacNameSql);
            $stmtShsFacName = $pdo->prepare($shsFacNameSql);

            foreach ($activeSchedules as $sched) {
                $days = $this->decomposeDays($sched['day']);
                $start = $sched['start_time'];
                $end = $sched['end_time'];
                $room = trim($sched['room'] ?? '');
                $mode = $sched['delivery_mode'];
                $facId = $sched['faculty_user_id'];
                $inst = trim($sched['instructor'] ?? '');

                // Check external room conflicts across College & SHS
                if (!empty($room) && strtoupper($room) !== 'TBA' && $mode !== 'Online') {
                    $colRoomParams = [$room];
                    if ($type === 'college') $colRoomParams[] = $sectionId;
                    $stmtColRoom->execute($colRoomParams);
                    $existingColRooms = $stmtColRoom->fetchAll(PDO::FETCH_ASSOC);

                    $shsRoomParams = [$room];
                    if ($type === 'shs') $shsRoomParams[] = $sectionId;
                    $stmtShsRoom->execute($shsRoomParams);
                    $existingShsRooms = $stmtShsRoom->fetchAll(PDO::FETCH_ASSOC);

                    $allExistingRooms = array_merge($existingColRooms, $existingShsRooms);
                    foreach ($allExistingRooms as $er) {
                        $exDays = $this->decomposeDays($er['day']);
                        $common = array_intersect($days, $exDays);
                        if (!empty($common) && ($start < $er['end_time'] && $end > $er['start_time'])) {
                            $commonStr = $this->formatDays($common);
                            throw new Exception("Room Conflict: Room '{$room}' is already booked by {$er['section_code']} ({$er['subject_code']}) on {$commonStr} ({$er['start_time']} - {$er['end_time']}).");
                        }
                    }
                }

                // Check external faculty conflicts across College & SHS
                if ($facId) {
                    $colFacParams = [$facId];
                    if ($type === 'college') $colFacParams[] = $sectionId;
                    $stmtColFacId->execute($colFacParams);
                    $existingColFac = $stmtColFacId->fetchAll(PDO::FETCH_ASSOC);

                    $shsFacParams = [$facId];
                    if ($type === 'shs') $shsFacParams[] = $sectionId;
                    $stmtShsFacId->execute($shsFacParams);
                    $existingShsFac = $stmtShsFacId->fetchAll(PDO::FETCH_ASSOC);

                    $allExistingFac = array_merge($existingColFac, $existingShsFac);
                    foreach ($allExistingFac as $ef) {
                        $exDays = $this->decomposeDays($ef['day']);
                        $common = array_intersect($days, $exDays);
                        if (!empty($common) && ($start < $ef['end_time'] && $end > $ef['start_time'])) {
                            $commonStr = $this->formatDays($common);
                            $facName = $inst ?: "Faculty #{$facId}";
                            throw new Exception("Instructor Conflict: {$facName} is already teaching {$ef['section_code']} ({$ef['subject_code']}) on {$commonStr} ({$ef['start_time']} - {$ef['end_time']}).");
                        }
                    }
                } elseif (!empty($inst) && strtoupper($inst) !== 'TBA') {
                    $colInstParams = [$inst];
                    if ($type === 'college') $colInstParams[] = $sectionId;
                    $stmtColFacName->execute($colInstParams);
                    $existingColInst = $stmtColFacName->fetchAll(PDO::FETCH_ASSOC);

                    $shsInstParams = [$inst];
                    if ($type === 'shs') $shsInstParams[] = $sectionId;
                    $stmtShsFacName->execute($shsInstParams);
                    $existingShsInst = $stmtShsFacName->fetchAll(PDO::FETCH_ASSOC);

                    $allExistingInst = array_merge($existingColInst, $existingShsInst);
                    foreach ($allExistingInst as $ei) {
                        $exDays = $this->decomposeDays($ei['day']);
                        $common = array_intersect($days, $exDays);
                        if (!empty($common) && ($start < $ei['end_time'] && $end > $ei['start_time'])) {
                            $commonStr = $this->formatDays($common);
                            throw new Exception("Instructor Conflict: {$inst} is already teaching {$ei['section_code']} ({$ei['subject_code']}) on {$commonStr} ({$ei['start_time']} - {$ei['end_time']}).");
                        }
                    }
                }
            }

            // PHASE 4: Atomic Database Persistence & LMS Sync
            $pdo->beginTransaction();
            
            $updateStmt = $pdo->prepare('
                UPDATE ' . $table . ' 
                SET day = ?, start_time = ?, end_time = ?, room = ?, faculty_user_id = ?, instructor = ?, delivery_mode = ?
                WHERE id = ? AND ' . $secIdCol . ' = ?
            ');
            
            $insertStmt = $pdo->prepare('
                INSERT INTO ' . $table . ' (' . $secIdCol . ', subject_id, day, start_time, end_time, room, faculty_user_id, instructor, delivery_mode)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');

            $lmsUpsertStmt = $pdo->prepare("
                INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status)
                VALUES (?, ?, ?, ?, 'active')
                ON DUPLICATE KEY UPDATE faculty_user_id = VALUES(faculty_user_id), status = 'active'
            ");
            
            if (is_array($deletedIds) && !empty($deletedIds)) {
                $delIn = str_repeat('?,', count($deletedIds) - 1) . '?';
                $delStmt = $pdo->prepare('DELETE FROM ' . $table . ' WHERE id IN (' . $delIn . ') AND ' . $secIdCol . ' = ?');
                $delParams = array_values($deletedIds);
                $delParams[] = $sectionId;
                $delStmt->execute($delParams);
            }
            
            foreach ($processedSchedules as $sched) {
                $id = $sched['id'];
                $subjectId = $sched['subject_id'];
                $day = $sched['day'];
                $start = $sched['start_time'];
                $end = $sched['end_time'];
                $room = $sched['room'];
                $facultyUserId = $sched['faculty_user_id'];
                $instructor = $sched['instructor'];
                $mode = $sched['delivery_mode'];

                if ($id <= 0) {
                    if ($subjectId <= 0) {
                        throw new Exception("Invalid subject ID for new schedule session.");
                    }
                    $insertStmt->execute([$sectionId, $subjectId, $day, $start, $end, $room, $facultyUserId, $instructor, $mode]);
                } else {
                    $updateStmt->execute([$day, $start, $end, $room, $facultyUserId, $instructor, $mode, $id, $sectionId]);
                }

                // Automated LMS Course Synchronization
                if ($facultyUserId && $subjectId > 0) {
                    $lmsUpsertStmt->execute([$academicLevel, $sectionId, $subjectId, $facultyUserId]);
                }
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Schedule saved and LMS courses synchronized successfully.']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}



