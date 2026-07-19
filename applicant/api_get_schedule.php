<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

header('Content-Type: application/json');

$sectionId = $_GET['section_id'] ?? 0;

if (!$sectionId) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameter.']);
    exit;
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
