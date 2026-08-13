<?php
namespace App\Controllers\Admin;

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
        

$pageTitle = 'SHS Curriculum - Admin Portal';

// Fetch shs_strands with aggregate total subjects and units from shs_curriculum and subjects
$strandsData = [];
try {
    $stmt = $pdo->query('
        SELECT 
            p.id, 
            p.code, 
            p.name, 
            p.is_active,
            COUNT(c.id) as total_subjects,
            COALESCE(SUM(s.units), 0) as total_units
        FROM shs_strands p
        LEFT JOIN shs_curriculum c ON c.strand_id = p.id
        LEFT JOIN subjects s ON c.subject_id = s.id
        WHERE p.is_active = 1
        GROUP BY p.id, p.code, p.name, p.is_active
        ORDER BY p.code ASC
    ');
    $strandsData = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Failed to fetch shs_curriculum: ' . $e->getMessage());
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);


        return $this->render('admin/registrar/shs_curriculum', get_defined_vars());
    }
    public function curriculumBuilder(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$strandId = (int)($_GET['strand_id'] ?? 0);
if ($strandId <= 0) {
    $response->redirect("/sia/admin/registrar/shs_curriculum.php");
    return;
}

// Fetch strand metadata
try {
    $stmt = $pdo->prepare("SELECT * FROM shs_strands WHERE id = ?");
    $stmt->execute([$strandId]);
    $strand = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching strand: " . $e->getMessage());
}

if (!$strand) {
    $response->redirect("/sia/admin/registrar/shs_curriculum.php");
    return;
}

$pageTitle = htmlspecialchars($strand['name']) . ' - SHS Curriculum Builder';

// Initialize the curriculum structure for SHS
$subjects = [
    'Grade 11' => [
        'First' => [],
        'Second' => []
    ],
    'Grade 12' => [
        'First' => [],
        'Second' => []
    ]
];

$totalUnits = 0;
$totalSubjects = 0;
$lectureUnits = 0;
$labUnits = 0;

try {
    $stmt = $pdo->prepare("
        SELECT c.id as mapping_id, c.grade_level, c.semester,
               s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type
        FROM shs_curriculum c
        INNER JOIN subjects s ON c.subject_id = s.id
        WHERE c.strand_id = ?
        ORDER BY c.grade_level ASC, c.semester ASC, s.subject_code ASC
    ");
    $stmt->execute([$strandId]);
    $subjectsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($subjectsRaw as $row) {
        $gl = $row['grade_level'];
        $sem = $row['semester'];
        
        // Safety check to ensure it goes into a valid bucket
        if (!isset($subjects[$gl])) {
            $subjects[$gl] = [];
        }
        if (!isset($subjects[$gl][$sem])) {
            $subjects[$gl][$sem] = [];
        }
        
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

// Fetch global subjects for Add Modal
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

        if ($code === '' || $name === '') {
            throw new Exception('Strand code and name are required.');
        }

        // Check if code exists
        $stmt = $pdo->prepare('SELECT id FROM shs_strands WHERE code = :code');
        $stmt->execute(['code' => $code]);
        if ($stmt->fetch()) {
            throw new Exception("A strand with code '{$code}' already exists.");
        }

        $insertStmt = $pdo->prepare('INSERT INTO shs_strands (code, name, is_active) VALUES (:code, :name, 1)');
        $insertStmt->execute(['code' => $code, 'name' => $name]);

        logActivity((int)$_SESSION['user_id'], 'bi-mortarboard', 'SHS Strand Added', "Added SHS strand: " . strtoupper($code));
        $_SESSION['success_msg'] = 'Strand created successfully.';
    } 
    elseif ($action === 'update_strand') {
        $id = (int)($_POST['id'] ?? 0);
        $code = strtolower(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');

        if ($id <= 0 || $code === '' || $name === '') {
            throw new Exception('Missing required information to update strand.');
        }

        // Check duplicate code
        $stmt = $pdo->prepare('SELECT id FROM shs_strands WHERE code = :code AND id != :id');
        $stmt->execute(['code' => $code, 'id' => $id]);
        if ($stmt->fetch()) {
            throw new Exception("The code '{$code}' is already used by another strand.");
        }

        $updateStmt = $pdo->prepare('UPDATE shs_strands SET code = :code, name = :name WHERE id = :id');
        $updateStmt->execute(['code' => $code, 'name' => $name, 'id' => $id]);

        logActivity((int)$_SESSION['user_id'], 'bi-pencil', 'SHS Strand Updated', "Updated details for strand: " . strtoupper($code));
        $_SESSION['success_msg'] = 'Strand details updated successfully.';
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



$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $strandId = (int) ($_POST['strand_id'] ?? 0);
    $gradeLevel = trim($_POST['grade_level'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $subjectIds = $_POST['subject_ids'] ?? [];

    if ($strandId <= 0 || $gradeLevel === '' || $semester === '') {
        $_SESSION['error_msg'] = 'Strand, Grade Level, and Semester are required.';
        $response->redirect("/sia/admin/registrar/shs_curriculum_builder.php?strand_id=$strandId");
        return;
    }

    if (!is_array($subjectIds)) {
        $subjectIds = [$subjectIds];
    }

    $added = 0;
    $duplicates = 0;

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO shs_curriculum (strand_id, grade_level, semester, subject_id) VALUES (:strand, :gl, :sem, :subject)');
        
        foreach ($subjectIds as $subId) {
            $subId = (int) $subId;
            if ($subId <= 0) continue;

            try {
                $stmt->execute(['strand' => $strandId, 'gl' => $gradeLevel, 'sem' => $semester, 'subject' => $subId]);
                $added++;
            } catch (PDOException $e) {
                // Ignore duplicates gracefully
                if ($e->getCode() == 23000) {
                    $duplicates++;
                } else {
                    throw $e;
                }
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = 'An error occurred while compiling shs_curriculum: ' . $e->getMessage();
        $response->redirect("/sia/admin/registrar/shs_curriculum_builder.php?strand_id=$strandId");
        return;
    }

    if ($added > 0) {
        logActivity(
            (int)$_SESSION['user_id'], 
            'bi-book', 
            'SHS Curriculum Updated', 
            "Added $added subject(s) to SHS Curriculum (Strand ID: $strandId, Grade: $gradeLevel, Sem: $semester).",
            "SHS Curriculum Strand #$strandId",
            null,
            ['added_count' => $added, 'subject_ids' => $subjectIds, 'grade_level' => $gradeLevel, 'semester' => $semester]
        );
        $_SESSION['success_msg'] = "$added subject(s) added successfully." . ($duplicates > 0 ? " ($duplicates duplicates ignored)" : "");
    } else if ($duplicates > 0) {
        $_SESSION['error_msg'] = 'All selected subjects are already assigned to this semester.';
    } else {
        $_SESSION['error_msg'] = 'No subjects were selected or failed to add.';
    }
    
    $response->redirect("/sia/admin/registrar/shs_curriculum_builder.php?strand_id=$strandId");
    return;

} elseif ($action === 'delete_subject') {
    $strandId = (int) ($_POST['strand_id'] ?? 0);
    $mappingId = (int) ($_POST['mapping_id'] ?? 0);

    if ($mappingId <= 0) {
        $_SESSION['error_msg'] = 'Invalid curriculum mapping ID.';
        $response->redirect("/sia/admin/registrar/shs_curriculum_builder.php?strand_id=$strandId");
        return;
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM shs_curriculum WHERE id = :id');
        $stmt->execute(['id' => $mappingId]);
        
        if ($stmt->rowCount() > 0) {
            logActivity(
                (int)$_SESSION['user_id'], 
                'bi-trash', 
                'SHS Curriculum Subject Removed', 
                "Removed a subject mapping (ID: $mappingId) from SHS Curriculum (Strand ID: $strandId).",
                "SHS Curriculum Strand #$strandId",
                null,
                ['mapping_id' => $mappingId]
            );
            $_SESSION['success_msg'] = 'Subject successfully removed from the curriculum.';
        } else {
            $_SESSION['error_msg'] = 'Failed to remove subject or it was already removed.';
        }
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = 'Error removing subject: ' . $e->getMessage();
    }

    $response->redirect("/sia/admin/registrar/shs_curriculum_builder.php?strand_id=$strandId");
    return;
} else {
    $_SESSION['error_msg'] = 'Invalid action.';
    $response->redirect("/sia/admin/registrar/shs_curriculum.php");
    return;
}
    }
}



