<?php
namespace App\Controllers\Admin\Registrar;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use PDOException;

class CollegeController extends BaseController
{
    public function programs(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
$pageTitle = 'College Programs - Administrator';

        return $this->render('admin/registrar/college_programs', get_defined_vars());
    }
    public function curriculum(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$pageTitle = 'College Curricula - Admin Portal';

// Fetch programs for dropdown
$programs = [];
try {
    $stmt = $pdo->query('SELECT id, code, name FROM college_programs WHERE is_active = 1 ORDER BY code ASC');
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Fetch curricula
$curricula = [];
try {
    $stmt = $pdo->query("
        SELECT cc.id, cc.program_id, cc.curriculum_name, cc.version, cc.effective_academic_year, cc.status, cc.description, cc.created_at,
               p.code as program_code,
               (SELECT COUNT(id) FROM college_curriculum_subjects WHERE curriculum_id = cc.id) as subject_count,
               (SELECT COALESCE(SUM(s.units), 0) FROM college_curriculum_subjects ccs JOIN subjects s ON ccs.subject_id = s.id WHERE ccs.curriculum_id = cc.id) as total_units,
               (SELECT COUNT(id) FROM users WHERE college_curriculum_id = cc.id) as student_count,
               (SELECT COUNT(id) FROM college_sections WHERE curriculum_id = cc.id) as section_count,
               (SELECT COUNT(id) FROM applications WHERE college_curriculum_id = cc.id) as application_count
        FROM college_curricula cc
        INNER JOIN college_programs p ON cc.program_id = p.id
        ORDER BY p.code ASC, cc.curriculum_name ASC
    ");
    $curricula = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Failed to fetch college curricula: ' . $e->getMessage());
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);


        return $this->render('admin/registrar/college_curriculum', get_defined_vars());
    }
    public function curriculumBuilder(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$currId = (int)($_GET['id'] ?? 0);
if ($currId <= 0) {
    $response->redirect("/sia/admin/registrar/college_curriculum.php");
    return;
}

// Fetch curriculum metadata with usage counts
try {
    $stmt = $pdo->prepare("
        SELECT cc.*, p.code as program_code, p.name as program_name,
               (SELECT COUNT(id) FROM users WHERE college_curriculum_id = cc.id) as student_count,
               (SELECT COUNT(id) FROM college_sections WHERE curriculum_id = cc.id) as section_count,
               (SELECT COUNT(id) FROM applications WHERE college_curriculum_id = cc.id) as application_count
        FROM college_curricula cc 
        INNER JOIN college_programs p ON cc.program_id = p.id 
        WHERE cc.id = ?
    ");
    $stmt->execute([$currId]);
    $curriculum = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching curriculum: " . $e->getMessage());
}

if (!$curriculum) {
    $response->redirect("/sia/admin/registrar/college_curriculum.php");
    return;
}

$curriculum['total_usage'] = (int)$curriculum['student_count'] + (int)$curriculum['section_count'] + (int)$curriculum['application_count'];

$pageTitle = htmlspecialchars($curriculum['curriculum_name']) . ' - Builder';

// Fetch all subjects for this curriculum
$subjects = [];
$totalUnits = 0;
$lectureUnits = 0;
$labUnits = 0;

try {
    $stmt = $pdo->prepare("
        SELECT c.id as mapping_id, c.year_level, c.semester, c.display_order,
               s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type
        FROM college_curriculum_subjects c
        INNER JOIN subjects s ON c.subject_id = s.id
        WHERE c.curriculum_id = ?
        ORDER BY c.year_level ASC, c.semester ASC, c.display_order ASC
    ");
    $stmt->execute([$currId]);
    $subjectsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($subjectsRaw as $row) {
        $yl = $row['year_level'];
        $sem = $row['semester'];
        
        if (!isset($subjects[$yl])) {
            $subjects[$yl] = [];
        }
        if (!isset($subjects[$yl][$sem])) {
            $subjects[$yl][$sem] = [];
        }
        
        $subjects[$yl][$sem][] = $row;
        
        $totalUnits += (int)$row['units'];
        if (stripos((string)$row['subject_type'], 'lab') !== false) {
            $labUnits += (int)$row['units'];
        } else {
            $lectureUnits += (int)$row['units'];
        }
    }
} catch (PDOException $e) {
    die("Error fetching subjects: " . $e->getMessage());
}

// Fetch global subjects for Add Modal
$globalSubjects = [];
try {
    $gstmt = $pdo->query("SELECT id, subject_code, subject_name, units, subject_type FROM subjects WHERE status = 1 AND education_level IN ('College', 'Both') ORDER BY subject_code ASC");
    $globalSubjects = $gstmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);


        return $this->render('admin/registrar/college_curriculum_builder', get_defined_vars());
    }
    
    public function processProgram(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        $action = $_POST['action'] ?? '';

try {
    if ($action === 'create_program') {
        $code = strtolower(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $careers = trim($_POST['careers'] ?? '');
        $customTuition = trim($_POST['custom_tuition'] ?? '');

        if ($code === '' || $name === '') {
            throw new Exception('Program code and name are required.');
        }

        // Check if code exists
        $stmt = $pdo->prepare('SELECT id FROM college_programs WHERE code = :code');
        $stmt->execute(['code' => $code]);
        if ($stmt->fetch()) {
            throw new Exception("A program with code '{$code}' already exists.");
        }

        $insertStmt = $pdo->prepare('INSERT INTO college_programs (code, name, description, icon, careers, custom_tuition, is_active) VALUES (:code, :name, :description, :icon, :careers, :custom_tuition, 1)');
        $insertStmt->execute([
            'code' => $code, 
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'icon' => $icon !== '' ? $icon : null,
            'careers' => $careers !== '' ? $careers : null,
            'custom_tuition' => $customTuition !== '' ? $customTuition : null
        ]);

        logActivity((int)$_SESSION['user_id'], 'bi-mortarboard', 'College Program Added', "Added college program: " . strtoupper($code));
        $_SESSION['success_msg'] = 'Program created successfully.';
    } 
    elseif ($action === 'update_program' || $action === 'update_landing_card') {
        $id = (int)($_POST['id'] ?? 0);
        $code = strtolower(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $careers = trim($_POST['careers'] ?? '');
        $customTuition = trim($_POST['custom_tuition'] ?? '');

        if ($id <= 0) {
            throw new Exception('Missing required information to update program.');
        }

        if ($code !== '' && $name !== '') {
            // Check duplicate code
            $stmt = $pdo->prepare('SELECT id FROM college_programs WHERE code = :code AND id != :id');
            $stmt->execute(['code' => $code, 'id' => $id]);
            if ($stmt->fetch()) {
                throw new Exception("The code '{$code}' is already used by another program.");
            }

            $updateStmt = $pdo->prepare('UPDATE college_programs SET code = :code, name = :name, description = :description, icon = :icon, careers = :careers, custom_tuition = :custom_tuition WHERE id = :id');
            $updateStmt->execute([
                'code' => $code, 
                'name' => $name, 
                'description' => $description !== '' ? $description : null,
                'icon' => $icon !== '' ? $icon : null,
                'careers' => $careers !== '' ? $careers : null,
                'custom_tuition' => $customTuition !== '' ? $customTuition : null,
                'id' => $id
            ]);
        } else {
            $updateStmt = $pdo->prepare('UPDATE college_programs SET description = :description, icon = :icon, careers = :careers, custom_tuition = :custom_tuition WHERE id = :id');
            $updateStmt->execute([
                'description' => $description !== '' ? $description : null,
                'icon' => $icon !== '' ? $icon : null,
                'careers' => $careers !== '' ? $careers : null,
                'custom_tuition' => $customTuition !== '' ? $customTuition : null,
                'id' => $id
            ]);
        }

        logActivity((int)$_SESSION['user_id'], 'bi-pencil', 'College Program Updated', "Updated details/card for program ID: " . $id);
        $_SESSION['success_msg'] = 'Program details & landing card updated successfully.';
    }
    elseif ($action === 'toggle_program') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE college_programs SET is_active = :status WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $id]);
            $_SESSION['success_msg'] = 'Program status updated successfully.';
        }
    }
    else {
        throw new Exception('Invalid action requested.');
    }
} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
}

$response->redirect("/sia/admin/registrar/college_programs.php");
return;
    }
    public function processCurriculum(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response->redirect("/sia/admin/registrar/college_curriculum.php");
            return;
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                  || isset($_POST['ajax']);

        $sendResponse = function(bool $success, string $message, array $data = [], ?string $redirectUrl = null) use ($isAjax, $response) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(array_merge([
                    'success' => $success,
                    'message' => $message
                ], $data));
                exit;
            }
            if ($success) {
                $_SESSION['success_msg'] = $message;
            } else {
                $_SESSION['error_msg'] = $message;
            }
            $response->redirect($redirectUrl ?: "/sia/admin/registrar/college_curriculum.php");
            return;
        };

        $action = $_POST['action'] ?? '';

        // ----------------------------------------------------
        // 1. CREATE CURRICULUM (Starts as DRAFT)
        // ----------------------------------------------------
        if ($action === 'create_curriculum') {
            $programId = (int)($_POST['program_id'] ?? 0);
            $name = trim($_POST['curriculum_name'] ?? '');
            $version = trim($_POST['version'] ?? '1.0');
            $ay = trim($_POST['effective_academic_year'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            if ($programId <= 0 || $name === '' || $version === '' || $ay === '') {
                $sendResponse(false, 'Program, Curriculum Name, Version, and Effective Academic Year are required.');
                return;
            }

            try {
                // Check if program exists
                $progCheck = $pdo->prepare("SELECT id FROM college_programs WHERE id = ?");
                $progCheck->execute([$programId]);
                if (!$progCheck->fetch()) {
                    $sendResponse(false, 'Selected program does not exist.');
                    return;
                }

                // Check version uniqueness for this program
                $verCheck = $pdo->prepare("SELECT id FROM college_curricula WHERE program_id = ? AND version = ?");
                $verCheck->execute([$programId, $version]);
                if ($verCheck->fetch()) {
                    $sendResponse(false, "Version '{$version}' already exists for this program. Please specify a unique version tag.");
                    return;
                }

                $stmt = $pdo->prepare("
                    INSERT INTO college_curricula (program_id, curriculum_name, version, effective_academic_year, description, status) 
                    VALUES (?, ?, ?, ?, ?, 'draft')
                ");
                $stmt->execute([$programId, $name, $version, $ay, $desc ?: null]);
                $newId = (int) $pdo->lastInsertId();

                logActivity((int)$_SESSION['user_id'], 'bi-journal-plus', 'Curriculum Created', "Created new draft curriculum '{$name}' (v{$version}) for program #{$programId}.");
                $sendResponse(true, "Curriculum created successfully in Draft status. You can now build its subject catalog.", ['curriculum_id' => $newId], "/sia/admin/registrar/college_curriculum_builder.php?id={$newId}");
                return;
            } catch (PDOException $e) {
                $sendResponse(false, "Failed to create curriculum: " . $e->getMessage());
                return;
            }
        } 

        // ----------------------------------------------------
        // 2. CLONE CURRICULUM (Atomic copy into new DRAFT)
        // ----------------------------------------------------
        elseif ($action === 'clone_curriculum') {
            $sourceId = (int)($_POST['source_curriculum_id'] ?? 0);
            $name = trim($_POST['curriculum_name'] ?? '');
            $version = trim($_POST['version'] ?? '');
            $ay = trim($_POST['effective_academic_year'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            if ($sourceId <= 0 || $name === '' || $version === '' || $ay === '') {
                $sendResponse(false, 'Source Curriculum, New Curriculum Name, Version Tag, and Academic Year are required to clone.');
                return;
            }

            try {
                $pdo->beginTransaction();

                // Lock source row for consistent snapshot
                $srcStmt = $pdo->prepare("SELECT * FROM college_curricula WHERE id = ? FOR UPDATE");
                $srcStmt->execute([$sourceId]);
                $src = $srcStmt->fetch(PDO::FETCH_ASSOC);

                if (!$src) {
                    $pdo->rollBack();
                    $sendResponse(false, 'Source curriculum not found.');
                    return;
                }

                // Check version uniqueness for the target program
                $checkStmt = $pdo->prepare("SELECT id FROM college_curricula WHERE program_id = ? AND version = ?");
                $checkStmt->execute([$src['program_id'], $version]);
                if ($checkStmt->fetch()) {
                    $pdo->rollBack();
                    $sendResponse(false, "Version '{$version}' already exists for this program. Please specify a unique version tag (e.g. 2.0).");
                    return;
                }

                // 1. Insert new Draft curriculum header (MUST be draft)
                $insCurr = $pdo->prepare("
                    INSERT INTO college_curricula (program_id, curriculum_name, version, effective_academic_year, description, status) 
                    VALUES (?, ?, ?, ?, ?, 'draft')
                ");
                $insCurr->execute([$src['program_id'], $name, $version, $ay, $desc ?: $src['description']]);
                $newCurrId = (int) $pdo->lastInsertId();

                // 2. Fetch and duplicate all mapped subjects
                $fetchSubs = $pdo->prepare("
                    SELECT subject_id, year_level, semester, display_order 
                    FROM college_curriculum_subjects 
                    WHERE curriculum_id = ? 
                    ORDER BY year_level ASC, semester ASC, display_order ASC
                ");
                $fetchSubs->execute([$sourceId]);
                $sourceSubs = $fetchSubs->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($sourceSubs)) {
                    $insSub = $pdo->prepare("
                        INSERT INTO college_curriculum_subjects (curriculum_id, subject_id, year_level, semester, display_order) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    foreach ($sourceSubs as $sub) {
                        $insSub->execute([$newCurrId, $sub['subject_id'], $sub['year_level'], $sub['semester'], $sub['display_order']]);
                    }
                }

                $pdo->commit();
                logActivity((int)$_SESSION['user_id'], 'bi-files', 'Curriculum Cloned', "Cloned curriculum '{$src['curriculum_name']}' (v{$src['version']}) into new Draft '{$name}' (v{$version}).");
                $sendResponse(true, "Curriculum successfully cloned into new Draft version (v{$version}). You can now customize its subjects.", ['curriculum_id' => $newCurrId], "/sia/admin/registrar/college_curriculum_builder.php?id={$newCurrId}");
                return;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $sendResponse(false, "Cloning failed: " . $e->getMessage());
                return;
            }
        }

        // ----------------------------------------------------
        // 3. ACTIVATE CURRICULUM (DRAFT -> ACTIVE only)
        // ----------------------------------------------------
        elseif ($action === 'activate_curriculum') {
            $id = (int)($_POST['curriculum_id'] ?? 0);
            $archivePrevious = isset($_POST['archive_previous']) && $_POST['archive_previous'] == '1';

            if ($id <= 0) {
                $sendResponse(false, 'Invalid Curriculum ID.');
                return;
            }

            try {
                $pdo->beginTransaction();

                $currStmt = $pdo->prepare("SELECT * FROM college_curricula WHERE id = ? FOR UPDATE");
                $currStmt->execute([$id]);
                $curr = $currStmt->fetch(PDO::FETCH_ASSOC);

                if (!$curr) {
                    $pdo->rollBack();
                    $sendResponse(false, 'Curriculum not found.');
                    return;
                }

                if ($curr['status'] !== 'draft') {
                    $pdo->rollBack();
                    $sendResponse(false, "Action Prohibited: Only 'Draft' curricula can be activated. This curriculum is currently " . strtoupper($curr['status']) . ".");
                    return;
                }

                // Verify curriculum has at least 1 mapped subject before activation
                $subCheck = $pdo->prepare("SELECT COUNT(*) FROM college_curriculum_subjects WHERE curriculum_id = ?");
                $subCheck->execute([$id]);
                $count = (int)$subCheck->fetchColumn();

                if ($count === 0) {
                    $pdo->rollBack();
                    $sendResponse(false, "Cannot activate an empty curriculum. Please add at least one subject in the Builder first.", [], "/sia/admin/registrar/college_curriculum_builder.php?id={$id}");
                    return;
                }

                if ($archivePrevious) {
                    // Archive previous active curricula for this program
                    $archStmt = $pdo->prepare("UPDATE college_curricula SET status = 'archived' WHERE program_id = ? AND status = 'active' AND id != ?");
                    $archStmt->execute([$curr['program_id'], $id]);
                }

                $actStmt = $pdo->prepare("UPDATE college_curricula SET status = 'active' WHERE id = ?");
                $actStmt->execute([$id]);

                $pdo->commit();
                logActivity((int)$_SESSION['user_id'], 'bi-check-circle', 'Curriculum Activated', "Activated curriculum '{$curr['curriculum_name']}' (v{$curr['version']}). Its structure is now locked for enrollment.");
                $sendResponse(true, "Curriculum '{$curr['curriculum_name']}' is now Active and structurally locked for official enrollment.", ['curriculum_id' => $id]);
                return;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $sendResponse(false, "Activation failed: " . $e->getMessage());
                return;
            }
        }

        // ----------------------------------------------------
        // 4. ARCHIVE CURRICULUM (ACTIVE -> ARCHIVED only)
        // ----------------------------------------------------
        elseif ($action === 'archive_curriculum') {
            $id = (int)($_POST['curriculum_id'] ?? 0);

            if ($id <= 0) {
                $sendResponse(false, 'Invalid Curriculum ID.');
                return;
            }

            try {
                $pdo->beginTransaction();

                $currStmt = $pdo->prepare("SELECT * FROM college_curricula WHERE id = ? FOR UPDATE");
                $currStmt->execute([$id]);
                $curr = $currStmt->fetch(PDO::FETCH_ASSOC);

                if (!$curr) {
                    $pdo->rollBack();
                    $sendResponse(false, 'Curriculum not found.');
                    return;
                }

                if ($curr['status'] !== 'active') {
                    $pdo->rollBack();
                    $sendResponse(false, "Action Prohibited: Only 'Active' curricula can be archived. Current status is " . strtoupper($curr['status']) . ".");
                    return;
                }

                $stmt = $pdo->prepare("UPDATE college_curricula SET status = 'archived' WHERE id = ?");
                $stmt->execute([$id]);

                $pdo->commit();
                logActivity((int)$_SESSION['user_id'], 'bi-archive', 'Curriculum Archived', "Archived curriculum '{$curr['curriculum_name']}' (v{$curr['version']}).");
                $sendResponse(true, "Curriculum '{$curr['curriculum_name']}' has been moved to Archived status.", ['curriculum_id' => $id]);
                return;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $sendResponse(false, "Failed to archive curriculum: " . $e->getMessage());
                return;
            }
        }

        // ----------------------------------------------------
        // 5. UPDATE CURRICULUM METADATA (DRAFT only)
        // ----------------------------------------------------
        elseif ($action === 'update_curriculum') {
            $id = (int)($_POST['curriculum_id'] ?? 0);
            $programId = (int)($_POST['program_id'] ?? 0);
            $name = trim($_POST['curriculum_name'] ?? '');
            $version = trim($_POST['version'] ?? '1.0');
            $ay = trim($_POST['effective_academic_year'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            if ($id <= 0 || $programId <= 0 || $name === '' || $version === '' || $ay === '') {
                $sendResponse(false, 'Invalid or missing data for updating curriculum.');
                return;
            }

            try {
                $currStmt = $pdo->prepare("SELECT * FROM college_curricula WHERE id = ?");
                $currStmt->execute([$id]);
                $curr = $currStmt->fetch(PDO::FETCH_ASSOC);

                if (!$curr) {
                    $sendResponse(false, 'Curriculum not found.');
                    return;
                }

                // Strict Guard: Metadata edits are only permitted in DRAFT state
                if ($curr['status'] !== 'draft') {
                    $sendResponse(false, "Action Denied: '{$curr['curriculum_name']}' is " . strtoupper($curr['status']) . " and cannot be modified. Create a new version instead.", [], "/sia/admin/registrar/college_curriculum.php");
                    return;
                }

                // Check version uniqueness for program if changed
                if ($curr['version'] !== $version || (int)$curr['program_id'] !== $programId) {
                    $verCheck = $pdo->prepare("SELECT id FROM college_curricula WHERE program_id = ? AND version = ? AND id != ?");
                    $verCheck->execute([$programId, $version, $id]);
                    if ($verCheck->fetch()) {
                        $sendResponse(false, "Version '{$version}' already exists for this program.");
                        return;
                    }
                }

                $stmt = $pdo->prepare("
                    UPDATE college_curricula 
                    SET program_id = ?, curriculum_name = ?, version = ?, effective_academic_year = ?, description = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$programId, $name, $version, $ay, $desc ?: null, $id]);
                $sendResponse(true, "Curriculum details updated successfully.", ['curriculum_id' => $id]);
                return;
            } catch (PDOException $e) {
                $sendResponse(false, "Failed to update curriculum: " . $e->getMessage());
                return;
            }
        } 

        // ----------------------------------------------------
        // 6. DELETE CURRICULUM (DRAFT & UNUSED only)
        // ----------------------------------------------------
        elseif ($action === 'delete_curriculum') {
            $id = (int)($_POST['curriculum_id'] ?? 0);
            
            if ($id <= 0) {
                $sendResponse(false, "Invalid Curriculum ID.");
                return;
            }
            
            try {
                $pdo->beginTransaction();

                $currStmt = $pdo->prepare("SELECT * FROM college_curricula WHERE id = ? FOR UPDATE");
                $currStmt->execute([$id]);
                $curr = $currStmt->fetch(PDO::FETCH_ASSOC);

                if (!$curr) {
                    $pdo->rollBack();
                    $sendResponse(false, 'Curriculum not found.');
                    return;
                }

                // Strict Guard: Prohibit deletion of Active or Archived curricula
                if ($curr['status'] !== 'draft') {
                    $pdo->rollBack();
                    $sendResponse(false, "Action Prohibited: Only 'Draft' curricula can be deleted. 'Active' and 'Archived' curricula are preserved permanently for student and historical records.");
                    return;
                }

                // Verify actual database usage
                $usersCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE college_curriculum_id = {$id}")->fetchColumn();
                $sectionsCount = (int)$pdo->query("SELECT COUNT(*) FROM college_sections WHERE curriculum_id = {$id}")->fetchColumn();
                $appsCount = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE college_curriculum_id = {$id}")->fetchColumn();

                if (($usersCount + $sectionsCount + $appsCount) > 0) {
                    $pdo->rollBack();
                    $sendResponse(false, "Cannot delete: This curriculum is referenced by existing database records ({$usersCount} students, {$sectionsCount} sections, {$appsCount} applications).");
                    return;
                }

                $stmt = $pdo->prepare("DELETE FROM college_curricula WHERE id = ?");
                $stmt->execute([$id]);

                $pdo->commit();
                logActivity((int)$_SESSION['user_id'], 'bi-trash', 'Curriculum Deleted', "Deleted unused draft curriculum '{$curr['curriculum_name']}'.");
                $sendResponse(true, "Draft curriculum deleted successfully.");
                return;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $sendResponse(false, "Failed to delete curriculum: " . $e->getMessage());
                return;
            }
        } 
        
        // ====================================================
        // 7. SUBJECT MUTATION GUARDS (DRAFT only)
        // ====================================================
        $currId = (int)($_POST['curriculum_id'] ?? 0);
        if ($currId > 0) {
            $lockCheck = $pdo->prepare("SELECT status, curriculum_name FROM college_curricula WHERE id = ?");
            $lockCheck->execute([$currId]);
            $currLock = $lockCheck->fetch(PDO::FETCH_ASSOC);

            if (!$currLock) {
                $sendResponse(false, "Curriculum not found.");
                return;
            }

            if ($currLock['status'] !== 'draft') {
                $sendResponse(false, "Action Denied: '{$currLock['curriculum_name']}' is " . strtoupper($currLock['status']) . " and structurally immutable. To add, edit, move, or remove subjects, clone this curriculum into a new Draft version.", [], "/sia/admin/registrar/college_curriculum_builder.php?id={$currId}");
                return;
            }
        }

        // 7a. Add Subject (Draft only)
        if ($action === 'add_subject') {
            $subjectId = (int)($_POST['subject_id'] ?? 0);
            $yearLevel = trim($_POST['year_level'] ?? '');
            $semester = trim($_POST['semester'] ?? '');
            
            if ($currId <= 0 || $subjectId <= 0 || $yearLevel === '' || $semester === '') {
                $sendResponse(false, "All fields are required to add a subject.", [], "/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
                return;
            }

            try {
                // Verify subject exists
                $subCheck = $pdo->prepare("SELECT id FROM subjects WHERE id = ?");
                $subCheck->execute([$subjectId]);
                if (!$subCheck->fetch()) {
                    $sendResponse(false, "Selected subject does not exist.", [], "/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
                    return;
                }

                // Get max display order
                $ordStmt = $pdo->prepare("SELECT MAX(display_order) FROM college_curriculum_subjects WHERE curriculum_id = ? AND year_level = ? AND semester = ?");
                $ordStmt->execute([$currId, $yearLevel, $semester]);
                $maxOrder = (int)$ordStmt->fetchColumn();

                $stmt = $pdo->prepare("
                    INSERT INTO college_curriculum_subjects (curriculum_id, subject_id, year_level, semester, display_order) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$currId, $subjectId, $yearLevel, $semester, $maxOrder + 1]);
                $sendResponse(true, "Subject added successfully.", [], "/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
                return;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $sendResponse(false, "Subject is already assigned to this year and semester.", [], "/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
                } else {
                    $sendResponse(false, "Failed to add subject: " . $e->getMessage(), [], "/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
                }
                return;
            }
        } 
        
        // 7b. Edit Subject Placement (Draft only)
        elseif ($action === 'edit_subject') {
            $subId = (int)($_POST['subject_mapping_id'] ?? 0);
            $yearLevel = trim($_POST['year_level'] ?? '');
            $semester = trim($_POST['semester'] ?? '');

            try {
                // Get max display order in new year/sem
                $ordStmt = $pdo->prepare("SELECT MAX(display_order) FROM college_curriculum_subjects WHERE curriculum_id = ? AND year_level = ? AND semester = ?");
                $ordStmt->execute([$currId, $yearLevel, $semester]);
                $maxOrder = (int)$ordStmt->fetchColumn();

                $stmt = $pdo->prepare("UPDATE college_curriculum_subjects SET year_level = ?, semester = ?, display_order = ? WHERE id = ?");
                $stmt->execute([$yearLevel, $semester, $maxOrder + 1, $subId]);
                $sendResponse(true, "Subject updated successfully.", [], "/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
                return;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $sendResponse(false, "Subject is already assigned to that year and semester.", [], "/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
                } else {
                    $sendResponse(false, "Failed to update subject: " . $e->getMessage(), [], "/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
                }
                return;
            }
        } 
        
        // 7c. Delete Subject from Curriculum (Draft only)
        elseif ($action === 'delete_subject') {
            $subId = (int)($_POST['subject_mapping_id'] ?? 0);

            try {
                $stmt = $pdo->prepare("DELETE FROM college_curriculum_subjects WHERE id = ?");
                $stmt->execute([$subId]);
                $sendResponse(true, "Subject removed from curriculum.", [], "/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
                return;
            } catch (PDOException $e) {
                $sendResponse(false, "Failed to remove subject: " . $e->getMessage(), [], "/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
                return;
            }
        } 
        
        // 7d. Move Subject Display Order (Draft only)
        elseif ($action === 'move_subject') {
            $subId = (int)($_POST['subject_mapping_id'] ?? 0);
            $direction = $_POST['direction'] ?? 'up';

            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("SELECT year_level, semester, display_order FROM college_curriculum_subjects WHERE id = ? FOR UPDATE");
                $stmt->execute([$subId]);
                $current = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($current) {
                    $yearLevel = $current['year_level'];
                    $semester = $current['semester'];
                    $currentOrder = (int)$current['display_order'];
                    
                    $swapStmt = null;
                    if ($direction === 'up') {
                        $swapStmt = $pdo->prepare("
                            SELECT id, display_order 
                            FROM college_curriculum_subjects 
                            WHERE curriculum_id = ? AND year_level = ? AND semester = ? AND display_order < ? 
                            ORDER BY display_order DESC LIMIT 1
                        ");
                    } else {
                        $swapStmt = $pdo->prepare("
                            SELECT id, display_order 
                            FROM college_curriculum_subjects 
                            WHERE curriculum_id = ? AND year_level = ? AND semester = ? AND display_order > ? 
                            ORDER BY display_order ASC LIMIT 1
                        ");
                    }
                    
                    $swapStmt->execute([$currId, $yearLevel, $semester, $currentOrder]);
                    $swapWith = $swapStmt->fetch(PDO::FETCH_ASSOC);

                    if ($swapWith) {
                        $update1 = $pdo->prepare("UPDATE college_curriculum_subjects SET display_order = ? WHERE id = ?");
                        $update1->execute([$swapWith['display_order'], $subId]);
                        
                        $update2 = $pdo->prepare("UPDATE college_curriculum_subjects SET display_order = ? WHERE id = ?");
                        $update2->execute([$currentOrder, $swapWith['id']]);
                    }
                }
                $pdo->commit();
                $sendResponse(true, "Order updated.", [], "/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
                return;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $sendResponse(false, "Failed to reorder subject: " . $e->getMessage(), [], "/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
                return;
            }
        }

        $sendResponse(false, "Invalid action requested.");
        return;
    }
}



