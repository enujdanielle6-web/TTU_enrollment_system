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
               (SELECT COUNT(id) FROM college_curriculum_subjects WHERE curriculum_id = cc.id) as subject_count
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

// Fetch curriculum metadata
try {
    $stmt = $pdo->prepare("
        SELECT cc.*, p.code as program_code, p.name as program_name 
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

        if ($code === '' || $name === '') {
            throw new Exception('Program code and name are required.');
        }

        // Check if code exists
        $stmt = $pdo->prepare('SELECT id FROM college_programs WHERE code = :code');
        $stmt->execute(['code' => $code]);
        if ($stmt->fetch()) {
            throw new Exception("A program with code '{$code}' already exists.");
        }

        $insertStmt = $pdo->prepare('INSERT INTO college_programs (code, name, is_active) VALUES (:code, :name, 1)');
        $insertStmt->execute(['code' => $code, 'name' => $name]);

        logActivity((int)$_SESSION['user_id'], 'bi-mortarboard', 'College Program Added', "Added college program: " . strtoupper($code));
        $_SESSION['success_msg'] = 'Program created successfully.';
    } 
    elseif ($action === 'update_program') {
        $id = (int)($_POST['id'] ?? 0);
        $code = strtolower(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');

        if ($id <= 0 || $code === '' || $name === '') {
            throw new Exception('Missing required information to update program.');
        }

        // Check duplicate code
        $stmt = $pdo->prepare('SELECT id FROM college_programs WHERE code = :code AND id != :id');
        $stmt->execute(['code' => $code, 'id' => $id]);
        if ($stmt->fetch()) {
            throw new Exception("The code '{$code}' is already used by another program.");
        }

        $updateStmt = $pdo->prepare('UPDATE college_programs SET code = :code, name = :name WHERE id = :id');
        $updateStmt->execute(['code' => $code, 'name' => $name, 'id' => $id]);

        logActivity((int)$_SESSION['user_id'], 'bi-pencil', 'College Program Updated', "Updated details for program: " . strtoupper($code));
        $_SESSION['success_msg'] = 'Program details updated successfully.';
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



$action = $_POST['action'] ?? '';

if ($action === 'create_curriculum') {
    $programId = (int)($_POST['program_id'] ?? 0);
    $name = trim($_POST['curriculum_name'] ?? '');
    $version = trim($_POST['version'] ?? '1.0');
    $ay = trim($_POST['effective_academic_year'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $desc = trim($_POST['description'] ?? '');

    if ($programId <= 0 || $name === '' || $ay === '') {
        $_SESSION['error_msg'] = 'Program, Curriculum Name, and Academic Year are required.';
        $response->redirect("/sia/admin/registrar/college_curriculum.php");
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO college_curricula (program_id, curriculum_name, version, effective_academic_year, description, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$programId, $name, $version, $ay, $desc ?: null, $status]);
        $_SESSION['success_msg'] = "Curriculum created successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Failed to create curriculum: " . $e->getMessage();
    }
    $response->redirect("/sia/admin/registrar/college_curriculum.php");
    return;
} elseif ($action === 'update_curriculum') {
    $id = (int)($_POST['curriculum_id'] ?? 0);
    $programId = (int)($_POST['program_id'] ?? 0);
    $name = trim($_POST['curriculum_name'] ?? '');
    $version = trim($_POST['version'] ?? '1.0');
    $ay = trim($_POST['effective_academic_year'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $desc = trim($_POST['description'] ?? '');

    if ($id <= 0 || $programId <= 0 || $name === '' || $ay === '') {
        $_SESSION['error_msg'] = 'Invalid or missing data for updating curriculum.';
        $response->redirect("/sia/admin/registrar/college_curriculum.php");
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE college_curricula SET program_id = ?, curriculum_name = ?, version = ?, effective_academic_year = ?, description = ?, status = ? WHERE id = ?");
        $stmt->execute([$programId, $name, $version, $ay, $desc ?: null, $status, $id]);
        $_SESSION['success_msg'] = "Curriculum updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Failed to update curriculum: " . $e->getMessage();
    }
    $response->redirect("/sia/admin/registrar/college_curriculum.php");
    return;
} elseif ($action === 'delete_curriculum') {
    $id = (int)($_POST['curriculum_id'] ?? 0);
    
    if ($id <= 0) {
        $_SESSION['error_msg'] = "Invalid Curriculum ID.";
        $response->redirect("/sia/admin/registrar/college_curriculum.php");
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM college_curricula WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success_msg'] = "Curriculum deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Failed to delete curriculum: " . $e->getMessage();
    }
    $response->redirect("/sia/admin/registrar/college_curriculum.php");
    return;
} elseif ($action === 'add_subject') {
    $currId = (int)($_POST['curriculum_id'] ?? 0);
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $yearLevel = trim($_POST['year_level'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    
    if ($currId <= 0 || $subjectId <= 0 || $yearLevel === '' || $semester === '') {
        $_SESSION['error_msg'] = "All fields are required to add a subject.";
        $response->redirect("/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
        return;
    }

    try {
        // Get max display order
        $ordStmt = $pdo->prepare("SELECT MAX(display_order) FROM college_curriculum_subjects WHERE curriculum_id = ? AND year_level = ? AND semester = ?");
        $ordStmt->execute([$currId, $yearLevel, $semester]);
        $maxOrder = (int)$ordStmt->fetchColumn();

        $stmt = $pdo->prepare("INSERT INTO college_curriculum_subjects (curriculum_id, subject_id, year_level, semester, display_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$currId, $subjectId, $yearLevel, $semester, $maxOrder + 1]);
        $_SESSION['success_msg'] = "Subject added successfully.";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_msg'] = "Subject is already assigned to this year and semester.";
        } else {
            $_SESSION['error_msg'] = "Failed to add subject: " . $e->getMessage();
        }
    }
    $response->redirect("/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
    return;
} elseif ($action === 'edit_subject') {
    $subId = (int)($_POST['subject_mapping_id'] ?? 0);
    $currId = (int)($_POST['curriculum_id'] ?? 0);
    $yearLevel = trim($_POST['year_level'] ?? '');
    $semester = trim($_POST['semester'] ?? '');

    try {
        // Get max display order in new year/sem
        $ordStmt = $pdo->prepare("SELECT MAX(display_order) FROM college_curriculum_subjects WHERE curriculum_id = ? AND year_level = ? AND semester = ?");
        $ordStmt->execute([$currId, $yearLevel, $semester]);
        $maxOrder = (int)$ordStmt->fetchColumn();

        $stmt = $pdo->prepare("UPDATE college_curriculum_subjects SET year_level = ?, semester = ?, display_order = ? WHERE id = ?");
        $stmt->execute([$yearLevel, $semester, $maxOrder + 1, $subId]);
        $_SESSION['success_msg'] = "Subject updated successfully.";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error_msg'] = "Subject is already assigned to that year and semester.";
        } else {
            $_SESSION['error_msg'] = "Failed to update subject: " . $e->getMessage();
        }
    }
    $response->redirect("/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
    return;
} elseif ($action === 'delete_subject') {
    $subId = (int)($_POST['subject_mapping_id'] ?? 0);
    $currId = (int)($_POST['curriculum_id'] ?? 0);

    try {
        $stmt = $pdo->prepare("DELETE FROM college_curriculum_subjects WHERE id = ?");
        $stmt->execute([$subId]);
        $_SESSION['success_msg'] = "Subject removed from curriculum.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Failed to remove subject: " . $e->getMessage();
    }
    $response->redirect("/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
    return;
} elseif ($action === 'move_subject') {
    $subId = (int)($_POST['subject_mapping_id'] ?? 0);
    $currId = (int)($_POST['curriculum_id'] ?? 0);
    $direction = $_POST['direction'] ?? 'up';

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT year_level, semester, display_order FROM college_curriculum_subjects WHERE id = ?");
        $stmt->execute([$subId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($current) {
            $yearLevel = $current['year_level'];
            $semester = $current['semester'];
            $currentOrder = (int)$current['display_order'];
            
            $swapStmt = null;
            if ($direction === 'up') {
                $swapStmt = $pdo->prepare("SELECT id, display_order FROM college_curriculum_subjects WHERE curriculum_id = ? AND year_level = ? AND semester = ? AND display_order < ? ORDER BY display_order DESC LIMIT 1");
            } else {
                $swapStmt = $pdo->prepare("SELECT id, display_order FROM college_curriculum_subjects WHERE curriculum_id = ? AND year_level = ? AND semester = ? AND display_order > ? ORDER BY display_order ASC LIMIT 1");
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
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Failed to reorder subject: " . $e->getMessage();
    }
    $response->redirect("/sia/admin/registrar/college_curriculum_builder.php?id=$currId");
    return;
}

$response->redirect("/sia/admin/registrar/college_curriculum.php");
return;
    }
}



