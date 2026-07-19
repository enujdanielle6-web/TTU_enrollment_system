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
        exit;
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
