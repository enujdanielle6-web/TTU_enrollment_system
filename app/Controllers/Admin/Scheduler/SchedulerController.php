<?php
namespace App\Controllers\Admin\Scheduler;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use PDOException;

class SchedulerController extends BaseController
{
    public function dashboard(Request $request, Response $response)
    {
        $pageTitle = 'Scheduler Dashboard';
        $pdo = Database::getConnection();
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
            (SELECT COUNT(*) FROM applications a WHERE a.section_id = s.id AND a.status != 'rejected') as current_enrollment
        FROM shs_sections s
        INNER JOIN shs_strands p ON p.id = s.strand_id
        ORDER BY p.code ASC, s.grade_level ASC, s.section_code ASC
    ";
    $stmt = $pdo->query($query);
    $shs_sections = $stmt->fetchAll();
    
    // Fetch programs for Add Section modal
    $progStmt = $pdo->query("SELECT id, code, name FROM shs_strands WHERE is_active = 1 ORDER BY code ASC");
    $programs = $progStmt->fetchAll();

    // Fetch active curricula for Add Section modal
    $currStmt = $pdo->query("SELECT id, strand_id, curriculum_name, version, effective_academic_year FROM shs_curricula WHERE status = 'active' ORDER BY strand_id ASC, version DESC");
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
            SELECT ss.id, ss.subject_id, ss.capacity, ss.day, ss.start_time, ss.end_time, ss.room, ss.instructor, ss.delivery_mode, 
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
            SELECT ss.id, ss.subject_id, ss.capacity, ss.day, ss.start_time, ss.end_time, ss.room, ss.instructor, ss.delivery_mode, 
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

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    $_SESSION['admin_error'] = 'Database error loading schedule builder.';
    header("Location: " . ($type === 'shs' ? 'shs_sections.php' : 'college_sections.php'));
    return;
}

$pageTitle = 'Schedule Builder - Admin';

        return $this->render('admin/scheduler/schedule_builder', get_defined_vars());
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
    } else {
        requirePermission('college_sections.manage');
        $table = 'college_section_subjects';
        $secIdCol = 'college_section_id';
        $secTable = 'college_sections';
    }
    
    try {
        $pdo->beginTransaction();
        
        $rmConfStmt = $pdo->prepare('
            SELECT sec.section_code, sub.subject_code 
            FROM ' . $table . ' ss
            JOIN ' . $secTable . ' sec ON ss.' . $secIdCol . ' = sec.id
            JOIN subjects sub ON ss.subject_id = sub.id
            WHERE ss.room = ? AND ss.' . $secIdCol . ' != ? AND ss.day = ? AND ss.day IS NOT NULL
              AND (ss.start_time < ? AND ss.end_time > ?)
        ');
        
        $instConfStmt = $pdo->prepare('
            SELECT sec.section_code, sub.subject_code 
            FROM ' . $table . ' ss
            JOIN ' . $secTable . ' sec ON ss.' . $secIdCol . ' = sec.id
            JOIN subjects sub ON ss.subject_id = sub.id
            WHERE ss.instructor = ? AND ss.' . $secIdCol . ' != ? AND ss.day = ? AND ss.day IS NOT NULL
              AND (ss.start_time < ? AND ss.end_time > ?)
        ');
        
        $updateStmt = $pdo->prepare('
            UPDATE ' . $table . ' 
            SET day = ?, start_time = ?, end_time = ?, room = ?, instructor = ?, delivery_mode = ?
            WHERE id = ? AND ' . $secIdCol . ' = ?
        ');
        
        $insertStmt = $pdo->prepare('
            INSERT INTO ' . $table . ' (' . $secIdCol . ', subject_id, day, start_time, end_time, room, instructor, delivery_mode)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        if (is_array($deletedIds) && !empty($deletedIds)) {
            $delIn = str_repeat('?,', count($deletedIds) - 1) . '?';
            $delStmt = $pdo->prepare('DELETE FROM ' . $table . ' WHERE id IN (' . $delIn . ') AND ' . $secIdCol . ' = ?');
            $delParams = array_values($deletedIds);
            $delParams[] = $sectionId;
            $delStmt->execute($delParams);
        }
        
        foreach ($schedules as $sched) {
            $id = (int)$sched['id'];
            $subjectId = (int)($sched['subject_id'] ?? 0);
            $day = !empty(trim($sched['day'] ?? '')) ? trim($sched['day']) : null;
            $start = !empty(trim($sched['start_time'] ?? '')) ? trim($sched['start_time']) : null;
            $end = !empty(trim($sched['end_time'] ?? '')) ? trim($sched['end_time']) : null;
            $room = !empty(trim($sched['room'] ?? '')) ? trim($sched['room']) : null;
            $instructor = !empty(trim($sched['instructor'] ?? '')) ? trim($sched['instructor']) : null;
            $mode = trim($sched['delivery_mode'] ?? 'Face-to-Face');
            
            if ($day || $start || $end) {
                if (!$day || !$start || !$end) {
                    throw new Exception("Incomplete schedule. Provide Day, Start, End, or leave all blank.");
                }
                
                if ($room) {
                    $rmConfStmt->execute([$room, $sectionId, $day, $end, $start]);
                    if ($conflict = $rmConfStmt->fetch(PDO::FETCH_ASSOC)) {
                        throw new Exception("Room Conflict: Room {$room} is booked by {$conflict['section_code']} ({$conflict['subject_code']}) at this time.");
                    }
                }
                
                if ($instructor) {
                    $instConfStmt->execute([$instructor, $sectionId, $day, $end, $start]);
                    if ($conflict = $instConfStmt->fetch(PDO::FETCH_ASSOC)) {
                        throw new Exception("Instructor Conflict: {$instructor} is teaching {$conflict['section_code']} ({$conflict['subject_code']}) at this time.");
                    }
                }
            }
            
            if ($id <= 0) {
                if ($subjectId <= 0) {
                    throw new Exception("Invalid subject ID for new schedule session.");
                }
                $insertStmt->execute([$sectionId, $subjectId, $day, $start, $end, $room, $instructor, $mode]);
            } else {
                $updateStmt->execute([$day, $start, $end, $room, $instructor, $mode, $id, $sectionId]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Schedule saved successfully.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
}



