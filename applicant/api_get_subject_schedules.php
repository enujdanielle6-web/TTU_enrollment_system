<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

header('Content-Type: application/json');

$subjectId = (int)($_GET['subject_id'] ?? 0);
$level = $_GET['level'] ?? '';

if ($subjectId <= 0 || empty($level)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
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
