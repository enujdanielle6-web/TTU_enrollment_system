<?php
namespace App\Controllers\Admin\Registrar;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use PDOException;

class ShsController extends BaseController
{
    public function strands(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
$pageTitle = 'SHS Strands - Administrator';

        return $this->render('admin/registrar/shs_strands', get_defined_vars());
    }
        public function curriculum(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        $pageTitle = 'SHS Curriculum Management - Admin Portal';

        $curriculaData = [];
        try {
            $stmt = $pdo->query('
                SELECT 
                    c.id as curriculum_id,
                    c.strand_id,
                    c.curriculum_name,
                    c.version,
                    c.effective_academic_year,
                    c.status,
                    c.description,
                    c.created_at,
                    p.code as strand_code, 
                    p.name as strand_name,
                    (SELECT COUNT(id) FROM shs_curriculum_subjects WHERE curriculum_id = c.id) as total_subjects,
                    (SELECT COALESCE(SUM(s.units), 0) FROM shs_curriculum_subjects cs JOIN subjects s ON cs.subject_id = s.id WHERE cs.curriculum_id = c.id) as total_units,
                    (SELECT COUNT(id) FROM shs_sections WHERE curriculum_id = c.id) as section_count
                FROM shs_curricula c
                INNER JOIN shs_strands p ON c.strand_id = p.id
                ORDER BY p.code ASC, c.version DESC
            ');
            $curriculaData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Failed to fetch shs_curricula: ' . $e->getMessage());
        }

        $activeStrands = [];
        try {
            $activeStrands = $pdo->query('SELECT id, code, name FROM shs_strands WHERE is_active = 1 ORDER BY code ASC')->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}

        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        return $this->render('admin/registrar/shs_curriculum', get_defined_vars());
    }

    public function curriculumBuilder(Request $request, Response $response)
    {
        $pdo = Database::getConnection();

        $curriculumId = (int)($_GET['curriculum_id'] ?? $_GET['id'] ?? 0);
        if ($curriculumId <= 0) {
            $response->redirect("/sia/admin/registrar/shs_curriculum.php");
            return;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT c.*, p.code as strand_code, p.name as strand_name,
                       (SELECT COUNT(id) FROM shs_sections WHERE curriculum_id = c.id) as total_usage
                FROM shs_curricula c 
                INNER JOIN shs_strands p ON c.strand_id = p.id 
                WHERE c.id = ?
            ");
            $stmt->execute([$curriculumId]);
            $curriculum = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error fetching curriculum: " . $e->getMessage());
        }

        if (!$curriculum) {
            $response->redirect("/sia/admin/registrar/shs_curriculum.php");
            return;
        }

        $pageTitle = htmlspecialchars($curriculum['strand_code'] . ' v' . $curriculum['version']) . ' - SHS Curriculum Builder';

        $subjects = [
            'Grade 11' => [ 'First' => [], 'Second' => [] ],
            'Grade 12' => [ 'First' => [], 'Second' => [] ]
        ];

        $totalUnits = 0;
        $totalSubjects = 0;
        $lectureUnits = 0;
        $labUnits = 0;

        try {
            $stmt = $pdo->prepare("
                SELECT cs.id as mapping_id, cs.grade_level, cs.semester, cs.display_order,
                       s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type
                FROM shs_curriculum_subjects cs
                INNER JOIN subjects s ON cs.subject_id = s.id
                WHERE cs.curriculum_id = ?
                ORDER BY cs.grade_level ASC, cs.semester ASC, cs.display_order ASC, s.subject_code ASC
            ");
            $stmt->execute([$curriculumId]);
            $subjectsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($subjectsRaw as $row) {
                $gl = $row['grade_level'];
                $sem = $row['semester'];
                
                if (!isset($subjects[$gl])) $subjects[$gl] = [];
                if (!isset($subjects[$gl][$sem])) $subjects[$gl][$sem] = [];
                
                $subjects[$gl][$sem][] = $row;
                $totalUnits += (int)$row['units'];
                $totalSubjects++;
                
                if (stripos((string)$row['subject_type'], 'lab') !== false) {
                    $labUnits += (int)$row['units'];
                } else {
                    $lectureUnits += (int)$row['units'];
                }
            }
        } catch (PDOException $e) {
            die("Error fetching subjects: " . $e->getMessage());
        }

        $globalSubjects = [];
        try {
            $gstmt = $pdo->query("SELECT id, subject_code, subject_name, units, subject_type FROM subjects WHERE status = 1 AND education_level IN ('SHS', 'Both') ORDER BY subject_code ASC");
            $globalSubjects = $gstmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}

        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        return $this->render('admin/registrar/shs_curriculum_builder', get_defined_vars());
    }
    
    public function processStrand(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response->redirect("/sia/admin/registrar/shs_strands.php");
            return;
        }

        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'create_strand') {
                $code = strtolower(trim($_POST['code'] ?? ''));
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $icon = trim($_POST['icon'] ?? '');
                $careers = trim($_POST['careers'] ?? '');
                $customTuition = trim($_POST['custom_tuition'] ?? '');

                if ($code === '' || $name === '') {
                    throw new Exception('Strand code and name are required.');
                }

                // Check if code exists
                $stmt = $pdo->prepare('SELECT id FROM shs_strands WHERE code = :code');
                $stmt->execute(['code' => $code]);
                if ($stmt->fetch()) {
                    throw new Exception("A strand with code '{$code}' already exists.");
                }

                $insertStmt = $pdo->prepare('INSERT INTO shs_strands (code, name, description, icon, careers, custom_tuition, is_active) VALUES (:code, :name, :description, :icon, :careers, :custom_tuition, 1)');
                $insertStmt->execute([
                    'code' => $code, 
                    'name' => $name,
                    'description' => $description !== '' ? $description : null,
                    'icon' => $icon !== '' ? $icon : null,
                    'careers' => $careers !== '' ? $careers : null,
                    'custom_tuition' => $customTuition !== '' ? $customTuition : null
                ]);

                logActivity((int)$_SESSION['user_id'], 'bi-mortarboard', 'SHS Strand Added', "Added SHS strand: " . strtoupper($code));
                $_SESSION['success_msg'] = 'Strand created successfully.';
            } 
            elseif ($action === 'update_strand' || $action === 'update_landing_card') {
                $id = (int)($_POST['id'] ?? 0);
                $code = strtolower(trim($_POST['code'] ?? ''));
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $icon = trim($_POST['icon'] ?? '');
                $careers = trim($_POST['careers'] ?? '');
                $customTuition = trim($_POST['custom_tuition'] ?? '');

                if ($id <= 0) {
                    throw new Exception('Missing required information to update strand.');
                }

                if ($code !== '' && $name !== '') {
                    // Check duplicate code
                    $stmt = $pdo->prepare('SELECT id FROM shs_strands WHERE code = :code AND id != :id');
                    $stmt->execute(['code' => $code, 'id' => $id]);
                    if ($stmt->fetch()) {
                        throw new Exception("The code '{$code}' is already used by another strand.");
                    }

                    $updateStmt = $pdo->prepare('UPDATE shs_strands SET code = :code, name = :name, description = :description, icon = :icon, careers = :careers, custom_tuition = :custom_tuition WHERE id = :id');
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
                    $updateStmt = $pdo->prepare('UPDATE shs_strands SET description = :description, icon = :icon, careers = :careers, custom_tuition = :custom_tuition WHERE id = :id');
                    $updateStmt->execute([
                        'description' => $description !== '' ? $description : null,
                        'icon' => $icon !== '' ? $icon : null,
                        'careers' => $careers !== '' ? $careers : null,
                        'custom_tuition' => $customTuition !== '' ? $customTuition : null,
                        'id' => $id
                    ]);
                }

                logActivity((int)$_SESSION['user_id'], 'bi-pencil', 'SHS Strand Updated', "Updated details/card for strand ID: " . $id);
                $_SESSION['success_msg'] = 'Strand details & landing card updated successfully.';
            } 
            elseif ($action === 'toggle_strand') {
                $id = (int)($_POST['id'] ?? 0);
                $status = (int)($_POST['status'] ?? 0);

                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE shs_strands SET is_active = :status WHERE id = :id');
                    $stmt->execute(['status' => $status, 'id' => $id]);
                    $_SESSION['success_msg'] = 'Strand status updated successfully.';
                }
            } 
            else {
                throw new Exception('Invalid action requested.');
            }
        } catch (Exception $e) {
            $_SESSION['error_msg'] = $e->getMessage();
        }

        $response->redirect("/sia/admin/registrar/shs_strands.php");
        return;
    }

    public function processCurriculum(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response->redirect("/sia/admin/registrar/shs_curriculum.php");
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
            $response->redirect($redirectUrl ?: "/sia/admin/registrar/shs_curriculum.php");
            return;
        };

        $action = $_POST['action'] ?? '';

        // ----------------------------------------------------
        // 1. CREATE CURRICULUM (Starts strictly as DRAFT)
        // ----------------------------------------------------
        if ($action === 'create_curriculum') {
            $strandId = (int)($_POST['strand_id'] ?? 0);
            $name = trim($_POST['curriculum_name'] ?? '');
            $version = trim($_POST['version'] ?? '1.0');
            $ay = trim($_POST['effective_academic_year'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            if ($strandId <= 0 || $name === '' || $version === '' || $ay === '') {
                $sendResponse(false, 'Strand, Curriculum Name, Version, and Effective Academic Year are required.');
                return;
            }

            try {
                // Check if strand exists
                $strandCheck = $pdo->prepare("SELECT id FROM shs_strands WHERE id = ?");
                $strandCheck->execute([$strandId]);
                if (!$strandCheck->fetch()) {
                    $sendResponse(false, 'Selected strand does not exist.');
                    return;
                }

                // Check version uniqueness for this strand
                $verCheck = $pdo->prepare("SELECT id FROM shs_curricula WHERE strand_id = ? AND version = ?");
                $verCheck->execute([$strandId, $version]);
                if ($verCheck->fetch()) {
                    $sendResponse(false, "Version '{$version}' already exists for this strand. Please specify a unique version tag.");
                    return;
                }

                $stmt = $pdo->prepare("
                    INSERT INTO shs_curricula (strand_id, curriculum_name, version, effective_academic_year, description, status) 
                    VALUES (?, ?, ?, ?, ?, 'draft')
                ");
                $stmt->execute([$strandId, $name, $version, $ay, $desc ?: null]);
                $newId = (int) $pdo->lastInsertId();

                logActivity((int)$_SESSION['user_id'], 'bi-journal-plus', 'SHS Curriculum Created', "Created new draft SHS curriculum '{$name}' (v{$version}) for strand #{$strandId}.");
                $sendResponse(true, "SHS Curriculum created successfully in Draft status. You can now build its subject catalog.", ['curriculum_id' => $newId], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id={$newId}");
                return;
            } catch (PDOException $e) {
                $sendResponse(false, "Failed to create SHS curriculum: " . $e->getMessage());
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
                $srcStmt = $pdo->prepare("SELECT * FROM shs_curricula WHERE id = ? FOR UPDATE");
                $srcStmt->execute([$sourceId]);
                $src = $srcStmt->fetch(PDO::FETCH_ASSOC);

                if (!$src) {
                    $pdo->rollBack();
                    $sendResponse(false, 'Source curriculum not found.');
                    return;
                }

                // Check version uniqueness for the target strand
                $checkStmt = $pdo->prepare("SELECT id FROM shs_curricula WHERE strand_id = ? AND version = ?");
                $checkStmt->execute([$src['strand_id'], $version]);
                if ($checkStmt->fetch()) {
                    $pdo->rollBack();
                    $sendResponse(false, "Version '{$version}' already exists for this strand. Please specify a unique version tag (e.g. 2.0).");
                    return;
                }

                // 1. Insert new Draft curriculum header (MUST be draft)
                $insCurr = $pdo->prepare("
                    INSERT INTO shs_curricula (strand_id, curriculum_name, version, effective_academic_year, description, status) 
                    VALUES (?, ?, ?, ?, ?, 'draft')
                ");
                $insCurr->execute([$src['strand_id'], $name, $version, $ay, $desc ?: $src['description']]);
                $newCurrId = (int) $pdo->lastInsertId();

                // 2. Fetch and duplicate all mapped subjects
                $fetchSubs = $pdo->prepare("
                    SELECT subject_id, grade_level, semester, display_order 
                    FROM shs_curriculum_subjects 
                    WHERE curriculum_id = ? 
                    ORDER BY grade_level ASC, semester ASC, display_order ASC
                ");
                $fetchSubs->execute([$sourceId]);
                $sourceSubs = $fetchSubs->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($sourceSubs)) {
                    $insSub = $pdo->prepare("
                        INSERT INTO shs_curriculum_subjects (curriculum_id, subject_id, grade_level, semester, display_order) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    foreach ($sourceSubs as $sub) {
                        $insSub->execute([$newCurrId, $sub['subject_id'], $sub['grade_level'], $sub['semester'], $sub['display_order']]);
                    }
                }

                $pdo->commit();
                logActivity((int)$_SESSION['user_id'], 'bi-files', 'SHS Curriculum Cloned', "Cloned SHS curriculum '{$src['curriculum_name']}' (v{$src['version']}) into new Draft '{$name}' (v{$version}).");
                $sendResponse(true, "SHS Curriculum successfully cloned into new Draft version (v{$version}). You can now customize its subjects.", ['curriculum_id' => $newCurrId], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id={$newCurrId}");
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

                $currStmt = $pdo->prepare("SELECT * FROM shs_curricula WHERE id = ? FOR UPDATE");
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
                $subCheck = $pdo->prepare("SELECT COUNT(*) FROM shs_curriculum_subjects WHERE curriculum_id = ?");
                $subCheck->execute([$id]);
                $count = (int)$subCheck->fetchColumn();

                if ($count === 0) {
                    $pdo->rollBack();
                    $sendResponse(false, "Cannot activate an empty curriculum. Please add at least one subject in the Builder first.", [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id={$id}");
                    return;
                }

                if ($archivePrevious) {
                    // Archive previous active curricula for this strand
                    $archStmt = $pdo->prepare("UPDATE shs_curricula SET status = 'archived' WHERE strand_id = ? AND status = 'active' AND id != ?");
                    $archStmt->execute([$curr['strand_id'], $id]);
                }

                $actStmt = $pdo->prepare("UPDATE shs_curricula SET status = 'active' WHERE id = ?");
                $actStmt->execute([$id]);

                $pdo->commit();
                logActivity((int)$_SESSION['user_id'], 'bi-check-circle', 'SHS Curriculum Activated', "Activated SHS curriculum '{$curr['curriculum_name']}' (v{$curr['version']}). Its structure is now locked for enrollment.");
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

                $currStmt = $pdo->prepare("SELECT * FROM shs_curricula WHERE id = ? FOR UPDATE");
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

                $stmt = $pdo->prepare("UPDATE shs_curricula SET status = 'archived' WHERE id = ?");
                $stmt->execute([$id]);

                $pdo->commit();
                logActivity((int)$_SESSION['user_id'], 'bi-archive', 'SHS Curriculum Archived', "Archived SHS curriculum '{$curr['curriculum_name']}' (v{$curr['version']}).");
                $sendResponse(true, "Curriculum '{$curr['curriculum_name']}' has been moved to Archived status.", ['curriculum_id' => $id]);
                return;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $sendResponse(false, "Failed to archive SHS curriculum: " . $e->getMessage());
                return;
            }
        }

        // ----------------------------------------------------
        // 5. UPDATE CURRICULUM METADATA (DRAFT only)
        // ----------------------------------------------------
        elseif ($action === 'update_curriculum') {
            $id = (int)($_POST['curriculum_id'] ?? 0);
            $strandId = (int)($_POST['strand_id'] ?? 0);
            $name = trim($_POST['curriculum_name'] ?? '');
            $version = trim($_POST['version'] ?? '1.0');
            $ay = trim($_POST['effective_academic_year'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            if ($id <= 0 || $strandId <= 0 || $name === '' || $version === '' || $ay === '') {
                $sendResponse(false, 'Invalid or missing data for updating curriculum.');
                return;
            }

            try {
                $currStmt = $pdo->prepare("SELECT * FROM shs_curricula WHERE id = ?");
                $currStmt->execute([$id]);
                $curr = $currStmt->fetch(PDO::FETCH_ASSOC);

                if (!$curr) {
                    $sendResponse(false, 'Curriculum not found.');
                    return;
                }

                // Strict Guard: Metadata edits are only permitted in DRAFT state
                if ($curr['status'] !== 'draft') {
                    $sendResponse(false, "Action Denied: '{$curr['curriculum_name']}' is " . strtoupper($curr['status']) . " and cannot be modified. Create a new version instead.", [], "/sia/admin/registrar/shs_curriculum.php");
                    return;
                }

                // Check version uniqueness for strand if changed
                if ($curr['version'] !== $version || (int)$curr['strand_id'] !== $strandId) {
                    $verCheck = $pdo->prepare("SELECT id FROM shs_curricula WHERE strand_id = ? AND version = ? AND id != ?");
                    $verCheck->execute([$strandId, $version, $id]);
                    if ($verCheck->fetch()) {
                        $sendResponse(false, "Version '{$version}' already exists for this strand.");
                        return;
                    }
                }

                $stmt = $pdo->prepare("
                    UPDATE shs_curricula 
                    SET strand_id = ?, curriculum_name = ?, version = ?, effective_academic_year = ?, description = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$strandId, $name, $version, $ay, $desc ?: null, $id]);
                $sendResponse(true, "SHS Curriculum details updated successfully.", ['curriculum_id' => $id]);
                return;
            } catch (PDOException $e) {
                $sendResponse(false, "Failed to update SHS curriculum: " . $e->getMessage());
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

                $currStmt = $pdo->prepare("SELECT * FROM shs_curricula WHERE id = ? FOR UPDATE");
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
                $sectionsCount = (int)$pdo->query("SELECT COUNT(*) FROM shs_sections WHERE curriculum_id = {$id}")->fetchColumn();

                if ($sectionsCount > 0) {
                    $pdo->rollBack();
                    $sendResponse(false, "Cannot delete: This curriculum is referenced by {$sectionsCount} existing SHS section schedule(s).");
                    return;
                }

                $stmt = $pdo->prepare("DELETE FROM shs_curricula WHERE id = ?");
                $stmt->execute([$id]);

                $pdo->commit();
                logActivity((int)$_SESSION['user_id'], 'bi-trash', 'SHS Curriculum Deleted', "Deleted unused draft SHS curriculum '{$curr['curriculum_name']}'.");
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
            $lockCheck = $pdo->prepare("SELECT status, curriculum_name FROM shs_curricula WHERE id = ?");
            $lockCheck->execute([$currId]);
            $currLock = $lockCheck->fetch(PDO::FETCH_ASSOC);

            if (!$currLock) {
                $sendResponse(false, "Curriculum not found.");
                return;
            }

            if ($currLock['status'] !== 'draft') {
                $sendResponse(false, "Action Denied: '{$currLock['curriculum_name']}' is " . strtoupper($currLock['status']) . " and structurally immutable. To add, edit, move, or remove subjects, clone this curriculum into a new Draft version.", [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id={$currId}");
                return;
            }
        }

        // 7a. Add Subject(s) (Draft only)
        if ($action === 'add' || $action === 'add_subject') {
            $gradeLevel = trim($_POST['grade_level'] ?? '');
            $semester = trim($_POST['semester'] ?? '');
            $subjectIds = $_POST['subject_ids'] ?? ($_POST['subject_id'] ?? []);

            if ($currId <= 0 || $gradeLevel === '' || $semester === '') {
                $sendResponse(false, "Grade level and semester are required.", [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
                return;
            }

            if (!is_array($subjectIds)) {
                $subjectIds = [$subjectIds];
            }

            $added = 0;
            $duplicates = 0;

            try {
                $pdo->beginTransaction();

                // Get current max display order for this grade level & semester
                $ordStmt = $pdo->prepare("SELECT MAX(display_order) FROM shs_curriculum_subjects WHERE curriculum_id = ? AND grade_level = ? AND semester = ?");
                $ordStmt->execute([$currId, $gradeLevel, $semester]);
                $maxOrder = (int)$ordStmt->fetchColumn();

                $stmt = $pdo->prepare("INSERT INTO shs_curriculum_subjects (curriculum_id, grade_level, semester, subject_id, display_order) VALUES (:curriculum, :gl, :sem, :subject, :disp_ord)");

                foreach ($subjectIds as $subId) {
                    $subId = (int)$subId;
                    if ($subId <= 0) continue;

                    // Check if already mapped to this semester
                    $existsCheck = $pdo->prepare("SELECT id FROM shs_curriculum_subjects WHERE curriculum_id = ? AND grade_level = ? AND semester = ? AND subject_id = ?");
                    $existsCheck->execute([$currId, $gradeLevel, $semester, $subId]);
                    if ($existsCheck->fetch()) {
                        $duplicates++;
                        continue;
                    }

                    $maxOrder++;
                    $stmt->execute([
                        'curriculum' => $currId,
                        'gl' => $gradeLevel,
                        'sem' => $semester,
                        'subject' => $subId,
                        'disp_ord' => $maxOrder
                    ]);
                    $added++;
                }

                $pdo->commit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $sendResponse(false, "An error occurred while adding subjects: " . $e->getMessage(), [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
                return;
            }

            if ($added > 0) {
                logActivity((int)$_SESSION['user_id'], 'bi-book', 'SHS Curriculum Subjects Added', "Added $added subject(s) to SHS Curriculum #$currId ($gradeLevel, $semester).");
                $msg = "$added subject(s) added successfully." . ($duplicates > 0 ? " ($duplicates duplicates ignored)" : "");
                $sendResponse(true, $msg, [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
            } else if ($duplicates > 0) {
                $sendResponse(false, 'All selected subjects are already assigned to this semester.', [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
            } else {
                $sendResponse(false, 'No subjects were selected or failed to add.', [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
            }
            return;
        }

        // 7b. Edit Subject Placement (Draft only)
        elseif ($action === 'edit_subject') {
            $mappingId = (int)($_POST['mapping_id'] ?? 0);
            $gradeLevel = trim($_POST['grade_level'] ?? '');
            $semester = trim($_POST['semester'] ?? '');

            if ($currId <= 0 || $mappingId <= 0 || $gradeLevel === '' || $semester === '') {
                $sendResponse(false, "Invalid data for editing subject placement.", [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
                return;
            }

            try {
                $stmt = $pdo->prepare("UPDATE shs_curriculum_subjects SET grade_level = ?, semester = ? WHERE id = ? AND curriculum_id = ?");
                $stmt->execute([$gradeLevel, $semester, $mappingId, $currId]);
                $sendResponse(true, "Subject placement updated successfully.", [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
                return;
            } catch (PDOException $e) {
                $sendResponse(false, "Failed to update subject placement: " . $e->getMessage(), [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
                return;
            }
        }

        // 7c. Move Subject Order (Draft only)
        elseif ($action === 'move_subject') {
            $mappingId = (int)($_POST['mapping_id'] ?? 0);
            $direction = trim($_POST['direction'] ?? ''); // 'up' or 'down'

            if ($currId <= 0 || $mappingId <= 0 || !in_array($direction, ['up', 'down'])) {
                $sendResponse(false, "Invalid move parameters.", [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
                return;
            }

            try {
                $pdo->beginTransaction();

                // Get current subject details with lock
                $currSubStmt = $pdo->prepare("SELECT id, grade_level, semester, display_order FROM shs_curriculum_subjects WHERE id = ? AND curriculum_id = ? FOR UPDATE");
                $currSubStmt->execute([$mappingId, $currId]);
                $currSub = $currSubStmt->fetch(PDO::FETCH_ASSOC);

                if (!$currSub) {
                    $pdo->rollBack();
                    $sendResponse(false, "Subject mapping not found.", [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
                    return;
                }

                $gl = $currSub['grade_level'];
                $sem = $currSub['semester'];
                $currentOrder = (int)$currSub['display_order'];

                if ($direction === 'up') {
                    $neighborStmt = $pdo->prepare("
                        SELECT id, display_order FROM shs_curriculum_subjects 
                        WHERE curriculum_id = ? AND grade_level = ? AND semester = ? AND display_order < ? 
                        ORDER BY display_order DESC LIMIT 1 FOR UPDATE
                    ");
                    $neighborStmt->execute([$currId, $gl, $sem, $currentOrder]);
                } else {
                    $neighborStmt = $pdo->prepare("
                        SELECT id, display_order FROM shs_curriculum_subjects 
                        WHERE curriculum_id = ? AND grade_level = ? AND semester = ? AND display_order > ? 
                        ORDER BY display_order ASC LIMIT 1 FOR UPDATE
                    ");
                    $neighborStmt->execute([$currId, $gl, $sem, $currentOrder]);
                }

                $neighbor = $neighborStmt->fetch(PDO::FETCH_ASSOC);

                if ($neighbor) {
                    $swapOrder = (int)$neighbor['display_order'];
                    
                    // Swap orders
                    $swapStmt1 = $pdo->prepare("UPDATE shs_curriculum_subjects SET display_order = ? WHERE id = ?");
                    $swapStmt1->execute([$swapOrder, $mappingId]);

                    $swapStmt2 = $pdo->prepare("UPDATE shs_curriculum_subjects SET display_order = ? WHERE id = ?");
                    $swapStmt2->execute([$currentOrder, $neighbor['id']]);
                }

                $pdo->commit();
                $sendResponse(true, "Subject reordered successfully.", [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
                return;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $sendResponse(false, "Failed to reorder subject: " . $e->getMessage(), [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
                return;
            }
        }

        // 7d. Remove Subject (Draft only)
        elseif ($action === 'delete_subject') {
            $mappingId = (int) ($_POST['mapping_id'] ?? 0);

            if ($currId <= 0 || $mappingId <= 0) {
                $sendResponse(false, 'Invalid curriculum or subject mapping ID.', [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
                return;
            }

            try {
                $stmt = $pdo->prepare('DELETE FROM shs_curriculum_subjects WHERE id = :id AND curriculum_id = :curr_id');
                $stmt->execute(['id' => $mappingId, 'curr_id' => $currId]);
                
                if ($stmt->rowCount() > 0) {
                    logActivity(
                        (int)$_SESSION['user_id'], 
                        'bi-trash', 
                        'SHS Curriculum Subject Removed', 
                        "Removed a subject mapping (ID: $mappingId) from SHS Curriculum #$currId.",
                        "SHS Curriculum #$currId"
                    );
                    $sendResponse(true, 'Subject successfully removed from the curriculum.', [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
                } else {
                    $sendResponse(false, 'Failed to remove subject or it was already removed.', [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
                }
                return;
            } catch (PDOException $e) {
                $sendResponse(false, 'Error removing subject: ' . $e->getMessage(), [], "/sia/admin/registrar/shs_curriculum_builder.php?curriculum_id=$currId");
                return;
            }
        } 
        
        else {
            $sendResponse(false, 'Invalid action specified.');
            return;
        }
    }
}



