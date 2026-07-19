<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

header('Content-Type: application/json');

$programCode = $_GET['program_code'] ?? '';
$yearLevel = $_GET['year_level'] ?? '';
$semester = $_GET['semester'] ?? '';

if (empty($programCode) || empty($yearLevel)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
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
        exit;
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

    $totalUnits = 0;
    foreach ($subjects as $sub) {
        $totalUnits += (int) $sub['units'];
    }

    echo json_encode([
        'success' => true,
        'subjects' => $subjects,
        'total_units' => $totalUnits
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
