<?php
namespace App\Controllers\Api;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;

class ApplicantApiController extends BaseController
{
    public function getCurriculum(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
$programCode = $_GET['program_code'] ?? '';
$yearLevel = $_GET['year_level'] ?? '';
$semester = $_GET['semester'] ?? '';

if (empty($programCode) || empty($yearLevel)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    return;
}

try {
    // 1. Find program/strand ID based on Academic Level (derived implicitly from dropdown values, we can check category)
    // First check SHS
    $progStmt = $pdo->prepare('SELECT id, \'Senior High School\' as category FROM shs_strands WHERE code = :code LIMIT 1');
    $progStmt->execute(['code' => $programCode]);
    $programData = $progStmt->fetch(PDO::FETCH_ASSOC);

    if (!$programData) {
        $progStmt = $pdo->prepare('SELECT id, \'College\' as category FROM college_programs WHERE code = :code LIMIT 1');
        $progStmt->execute(['code' => $programCode]);
        $programData = $progStmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$programData) {
        echo json_encode(['success' => true, 'subjects' => [], 'total_units' => 0]);
        return;
    }

    $programId = $programData['id'];

    if ($programData['category'] === 'Senior High School') {
        $query = '
            SELECT s.id, s.subject_code, s.subject_name, s.units, s.subject_type
            FROM shs_curriculum c
            INNER JOIN subjects s ON c.subject_id = s.id
            WHERE c.strand_id = :program_id 
              AND c.grade_level = :year_level
              AND s.status = 1
        ';
    } else {
        $query = '
            SELECT s.id, s.subject_code, s.subject_name, s.units, s.subject_type
            FROM college_curricula cc
            INNER JOIN college_curriculum_subjects c ON cc.id = c.curriculum_id
            INNER JOIN subjects s ON c.subject_id = s.id
            WHERE cc.program_id = :program_id 
              AND c.year_level = :year_level
              AND cc.status = "active"
              AND s.status = 1
        ';
    }
    
    $params = [
        'program_id' => $programId,
        'year_level' => $yearLevel
    ];

    if (!empty($semester)) {
        $query .= ' AND (c.semester = :semester OR c.semester IS NULL OR c.semester = "")';
        $params['semester'] = $semester;
    }

    $query .= ' ORDER BY s.subject_code ASC';

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nstpChoice = strtoupper(trim($_GET['nstp'] ?? ''));
    $trackNames = [
        'CWTS' => 'Civic Welfare Training Service',
        'ROTC' => 'Reserve Officers\' Training Corps',
        'LTS' => 'Literacy Training Service'
    ];

    $totalUnits = 0;
    foreach ($subjects as &$sub) {
        $totalUnits += (int) $sub['units'];
        if ((strtoupper($sub['subject_type']) === 'NSTP' || stripos($sub['subject_code'], 'NSTP') !== false) && !empty($nstpChoice)) {
            if (isset($trackNames[$nstpChoice])) {
                $semNum = (stripos($sub['subject_code'], '102') !== false || stripos($sub['subject_code'], '2') !== false) ? '2' : '1';
                $sub['subject_code'] = "NSTP {$semNum} ({$nstpChoice})";
                $sub['subject_name'] = "{$trackNames[$nstpChoice]} {$semNum}";
            }
        }
    }
    unset($sub);

    echo json_encode([
        'success' => true,
        'subjects' => $subjects,
        'total_units' => $totalUnits
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
    }
    public function getFullCurriculum(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    return;
}

$programCode = $_GET['program_code'] ?? '';

if ($programCode === '') {
    echo json_encode(['success' => false, 'message' => 'Missing program code']);
    return;
}

try {
    // Find program ID based on Academic Level (derived implicitly from dropdown values, check category)
    $progStmt = $pdo->prepare('SELECT id, \'Senior High School\' as category FROM shs_strands WHERE code = :code LIMIT 1');
    $progStmt->execute(['code' => $programCode]);
    $programData = $progStmt->fetch(PDO::FETCH_ASSOC);

    if (!$programData) {
        $progStmt = $pdo->prepare('SELECT id, \'College\' as category FROM college_programs WHERE code = :code LIMIT 1');
        $progStmt->execute(['code' => $programCode]);
        $programData = $progStmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$programData) {
        echo json_encode(['success' => false, 'message' => 'Program not found']);
        return;
    }

    $programId = $programData['id'];

    if ($programData['category'] === 'Senior High School') {
        $query = '
            SELECT 
                s.id, s.subject_code, s.subject_name, s.units, s.subject_type,
                c.grade_level as year_level, c.semester
            FROM shs_curriculum c
            INNER JOIN subjects s ON c.subject_id = s.id
            WHERE c.strand_id = :program_id
              AND s.status = 1
            ORDER BY c.grade_level ASC, c.semester ASC, s.subject_code ASC
        ';
    } else {
        $query = '
            SELECT 
                s.id, s.subject_code, s.subject_name, s.units, s.subject_type,
                c.year_level, c.semester
            FROM college_curricula cc
            INNER JOIN college_curriculum_subjects c ON cc.id = c.curriculum_id
            INNER JOIN subjects s ON c.subject_id = s.id
            WHERE cc.program_id = :program_id
              AND cc.status = "active"
              AND s.status = 1
            ORDER BY c.year_level ASC, c.semester ASC, s.subject_code ASC
        ';
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute(['program_id' => $programId]);
    $results = $stmt->fetchAll();

    $nstpChoice = strtoupper(trim($_GET['nstp'] ?? ''));
    $trackNames = [
        'CWTS' => 'Civic Welfare Training Service',
        'ROTC' => 'Reserve Officers\' Training Corps',
        'LTS' => 'Literacy Training Service'
    ];

    // Group the curriculum
    $curriculum = [];
    foreach ($results as $row) {
        $year = $row['year_level'] ?: 'Unassigned Year';
        $semester = $row['semester'] ?: 'Unassigned Semester';
        
        if (!isset($curriculum[$year])) {
            $curriculum[$year] = [];
        }
        if (!isset($curriculum[$year][$semester])) {
            $curriculum[$year][$semester] = [];
        }

        $subCode = $row['subject_code'];
        $subName = $row['subject_name'];
        if ((strtoupper($row['subject_type']) === 'NSTP' || stripos($subCode, 'NSTP') !== false) && !empty($nstpChoice)) {
            if (isset($trackNames[$nstpChoice])) {
                $semNum = (stripos($subCode, '102') !== false || stripos($subCode, '2') !== false) ? '2' : '1';
                $subCode = "NSTP {$semNum} ({$nstpChoice})";
                $subName = "{$trackNames[$nstpChoice]} {$semNum}";
            }
        }
        
        $curriculum[$year][$semester][] = [
            'id' => (int) $row['id'],
            'subject_code' => $subCode,
            'subject_name' => $subName,
            'units' => (int) $row['units'],
            'subject_type' => $row['subject_type']
        ];
    }

    echo json_encode([
        'success' => true,
        'curriculum' => $curriculum
    ]);

} catch (PDOException $e) {
    error_log('API Curriculum Fetch Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
    }
    public function getSchedule(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
$sectionId = $_GET['section_id'] ?? 0;

if (!$sectionId) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameter.']);
    return;
}

try {
    $query = "
        SELECT 
            ss.day, 
            DATE_FORMAT(ss.start_time, '%l:%i %p') as start_time_f, 
            DATE_FORMAT(ss.end_time, '%l:%i %p') as end_time_f, 
            ss.room, 
            ss.instructor,
            s.subject_code,
            s.subject_name
        FROM college_section_subjects ss
        INNER JOIN subjects s ON s.id = ss.subject_id
        WHERE ss.college_section_id = :section_id
        ORDER BY 
            FIELD(ss.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
            ss.start_time ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['section_id' => $sectionId]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'schedules' => $schedules
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
    }
    public function getSections(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
$programCode = $_GET['program_code'] ?? '';
$yearLevel = $_GET['year_level'] ?? '';
$semester = $_GET['semester'] ?? '';

if (empty($programCode) || empty($yearLevel)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    return;
}

try {
    // Find program ID
    $progStmt = $pdo->prepare('SELECT id, \'Senior High School\' as category FROM shs_strands WHERE code = :code LIMIT 1');
    $progStmt->execute(['code' => $programCode]);
    $program = $progStmt->fetch(PDO::FETCH_ASSOC);

    if (!$program) {
        $progStmt = $pdo->prepare('SELECT id, \'College\' as category FROM college_programs WHERE code = :code LIMIT 1');
        $progStmt->execute(['code' => $programCode]);
        $program = $progStmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$program) {
        echo json_encode(['success' => true, 'sections' => []]);
        return;
    }

    if ($program['category'] === 'Senior High School') {
        $query = "
            SELECT s.id, s.section_code, s.schedule_type, s.capacity,
                   (SELECT COUNT(*) FROM shs_enrollments e WHERE e.shs_section_id = s.id) as current_enrollment
            FROM shs_sections s
            WHERE s.strand_id = :program_id
              AND s.grade_level = :year_level
              AND s.status = 1
        ";
    } else {
        $query = "
            SELECT s.id, s.section_code, s.schedule_type, s.capacity,
                   (SELECT COUNT(*) FROM college_enrollments e WHERE e.college_section_id = s.id) as current_enrollment
            FROM college_sections s
            WHERE s.program_id = :program_id
              AND s.year_level = :year_level
              AND s.status = 1
        ";
    }

    $params = [
        'program_id' => $program['id'],
        'year_level' => $yearLevel
    ];

    if ($program['category'] === 'College' && !empty($semester)) {
        $query .= " AND s.semester = :semester";
        $params['semester'] = $semester;
    }

    $query .= " ORDER BY s.section_code ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate remaining slots
    foreach ($sections as &$sec) {
        $sec['remaining_slots'] = max(0, (int)$sec['capacity'] - (int)$sec['current_enrollment']);
        $sec['is_full'] = $sec['remaining_slots'] <= 0;
    }

    echo json_encode([
        'success' => true,
        'sections' => $sections
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
    }
    public function getSectionSubjects(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    return;
}

$sectionId = (int)($_GET['section_id'] ?? 0);

if ($sectionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid section ID']);
    return;
}

try {
    $stmt = $pdo->prepare('
        SELECT ss.id as section_subject_id, ss.subject_id as id, sub.subject_code as code, sub.subject_name as name, sub.units,
               ss.day, ss.start_time, ss.end_time, ss.room
        FROM college_section_subjects ss
        INNER JOIN subjects sub ON sub.id = ss.subject_id
        WHERE ss.college_section_id = :section_id
        
        UNION ALL 
        
        SELECT ss.id as section_subject_id, ss.subject_id as id, sub.subject_code as code, sub.subject_name as name, sub.units,
               ss.day, ss.start_time, ss.end_time, ss.room
        FROM shs_section_subjects ss
        INNER JOIN subjects sub ON sub.id = ss.subject_id
        WHERE ss.shs_section_id = :section_id
        
        ORDER BY code ASC, day ASC, start_time ASC
    ');
    $stmt->execute(['section_id' => $sectionId]);
    $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $secStmt = $pdo->prepare('
        SELECT section_code FROM college_sections WHERE id = :section_id 
        UNION ALL 
        SELECT section_code FROM shs_sections WHERE id = :section_id
    ');
    $secStmt->execute(['section_id' => $sectionId]);
    $sectionCode = $secStmt->fetchColumn();

    $subjects = [];
    foreach ($raw as $r) {
        $sid = $r['id'];
        if (!isset($subjects[$sid])) {
            $subjects[$sid] = [
                'id' => (int)$sid,
                'code' => $r['code'],
                'name' => $r['name'],
                'units' => (int)$r['units'],
                'section_id' => (int)$r['section_subject_id'], // Map to section_subject_id for frontend to use
                'section_code' => $sectionCode,
                'schedules' => [],
                'schedule_text' => ''
            ];
        }
        $subjects[$sid]['schedules'][] = [
            'day' => $r['day'],
            'start_time_raw' => $r['start_time'],
            'end_time_raw' => $r['end_time'],
            'start_time' => date('h:i A', strtotime($r['start_time'])),
            'end_time' => date('h:i A', strtotime($r['end_time'])),
            'room' => $r['room']
        ];
    }
    
    foreach ($subjects as &$sub) {
        $texts = [];
        foreach ($sub['schedules'] as $s) {
            $st = date('h:i A', strtotime($s['start_time']));
            $et = date('h:i A', strtotime($s['end_time']));
            $texts[] = "{$s['day']} {$st}-{$et} ({$s['room']})";
        }
        $sub['schedule_text'] = implode('<br>', $texts);
    }
    
    echo json_encode(['success' => true, 'subjects' => array_values($subjects)]);
} catch (PDOException $e) {
    error_log('Failed to fetch section subjects: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
    }
    public function getSubjectSchedules(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
$subjectId = (int)($_GET['subject_id'] ?? 0);
$level = $_GET['level'] ?? '';

if ($subjectId <= 0 || empty($level)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    return;
}

try {
    if ($level === 'Senior High School') {
        $query = "
            SELECT 
                s.id as section_id,
                s.section_code,
                s.capacity,
                ss.day,
                ss.start_time,
                ss.end_time,
                ss.room,
                ss.instructor,
                (
                    SELECT COUNT(*) 
                    FROM shs_enrollments e 
                    WHERE e.shs_section_id = s.id
                ) as total_enrollments
            FROM shs_section_subjects ss
            JOIN shs_sections s ON ss.shs_section_id = s.id
            WHERE ss.subject_id = :subj_id
              AND s.status = 1
            ORDER BY s.section_code ASC, ss.day ASC, ss.start_time ASC
        ";
    } else {
        $query = "
            SELECT 
                s.id as section_id,
                s.section_code,
                s.capacity,
                ss.day,
                ss.start_time,
                ss.end_time,
                ss.room,
                ss.instructor,
                (
                    SELECT COUNT(*) 
                    FROM college_enrollments e 
                    WHERE e.college_section_id = s.id
                ) as total_enrollments
            FROM college_section_subjects ss
            JOIN college_sections s ON ss.college_section_id = s.id
            WHERE ss.subject_id = :subj_id
              AND s.status = 1
            ORDER BY s.section_code ASC, ss.day ASC, ss.start_time ASC
        ";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute(['subj_id' => $subjectId]);
    $schedulesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $grouped = [];
    foreach ($schedulesRaw as $row) {
        $secId = $row['section_id'];
        if (!isset($grouped[$secId])) {
            $remaining = max(0, (int)$row['capacity'] - (int)$row['total_enrollments']);
            
            $grouped[$secId] = [
                'section_id' => $secId,
                'section_code' => $row['section_code'],
                'remaining_slots' => $remaining,
                'is_full' => $remaining <= 0,
                'schedules' => []
            ];
        }
        $grouped[$secId]['schedules'][] = [
            'day' => $row['day'],
            'start_time_raw' => $row['start_time'],
            'end_time_raw' => $row['end_time'],
            'start_time' => date('h:i A', strtotime($row['start_time'])),
            'end_time' => date('h:i A', strtotime($row['end_time'])),
            'room' => $row['room'],
            'instructor' => $row['instructor']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'sections' => array_values($grouped)
    ]);

} catch (PDOException $e) {
    error_log('Error in api_get_subject_schedules.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
    }
}




