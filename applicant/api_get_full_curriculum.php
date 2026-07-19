<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$programCode = $_GET['program_code'] ?? '';

if ($programCode === '') {
    echo json_encode(['success' => false, 'message' => 'Missing program code']);
    exit;
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
        exit;
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
        
        $curriculum[$year][$semester][] = [
            'id' => (int) $row['id'],
            'subject_code' => $row['subject_code'],
            'subject_name' => $row['subject_name'],
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
