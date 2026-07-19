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

$sectionId = (int)($_GET['section_id'] ?? 0);

if ($sectionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid section ID']);
    exit;
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
            'start_time' => $r['start_time'],
            'end_time' => $r['end_time'],
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
