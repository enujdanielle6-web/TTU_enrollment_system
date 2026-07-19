<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

if ($action === 'save_schedule') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
        exit;
    }
    
    $type = $_POST['type'] ?? 'college';
    $sectionId = (int)($_POST['section_id'] ?? 0);
    $schedules = json_decode($_POST['schedules'] ?? '[]', true);
    $deletedIds = json_decode($_POST['deleted_ids'] ?? '[]', true);
    
    if ($sectionId <= 0 || !is_array($schedules)) {
        echo json_encode(['success' => false, 'message' => 'Invalid payload.']);
        exit;
    }

    if ($type === 'shs') {
        requirePermission('shs_sections.manage');
        $table = 'shs_section_subjects';
        $secIdCol = 'shs_section_id';
        $secTable = 'shs_sections';
    } else {
        requirePermission('college_sections.manage');
        $table = 'college_section_subjects';
        $secIdCol = 'college_section_id';
        $secTable = 'college_sections';
    }
    
    try {
        $pdo->beginTransaction();
        
        $rmConfStmt = $pdo->prepare('
            SELECT sec.section_code, sub.subject_code 
            FROM ' . $table . ' ss
            JOIN ' . $secTable . ' sec ON ss.' . $secIdCol . ' = sec.id
            JOIN subjects sub ON ss.subject_id = sub.id
            WHERE ss.room = ? AND ss.' . $secIdCol . ' != ? AND ss.day = ? AND ss.day IS NOT NULL
              AND (ss.start_time < ? AND ss.end_time > ?)
        ');
        
        $instConfStmt = $pdo->prepare('
            SELECT sec.section_code, sub.subject_code 
            FROM ' . $table . ' ss
            JOIN ' . $secTable . ' sec ON ss.' . $secIdCol . ' = sec.id
            JOIN subjects sub ON ss.subject_id = sub.id
            WHERE ss.instructor = ? AND ss.' . $secIdCol . ' != ? AND ss.day = ? AND ss.day IS NOT NULL
              AND (ss.start_time < ? AND ss.end_time > ?)
        ');
        
        $updateStmt = $pdo->prepare('
            UPDATE ' . $table . ' 
            SET day = ?, start_time = ?, end_time = ?, room = ?, instructor = ?, delivery_mode = ?
            WHERE id = ? AND ' . $secIdCol . ' = ?
        ');
        
        $insertStmt = $pdo->prepare('
            INSERT INTO ' . $table . ' (' . $secIdCol . ', subject_id, day, start_time, end_time, room, instructor, delivery_mode)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        if (is_array($deletedIds) && !empty($deletedIds)) {
            $delIn = str_repeat('?,', count($deletedIds) - 1) . '?';
            $delStmt = $pdo->prepare('DELETE FROM ' . $table . ' WHERE id IN (' . $delIn . ') AND ' . $secIdCol . ' = ?');
            $delParams = array_values($deletedIds);
            $delParams[] = $sectionId;
            $delStmt->execute($delParams);
        }
        
        foreach ($schedules as $sched) {
            $id = (int)$sched['id'];
            $subjectId = (int)($sched['subject_id'] ?? 0);
            $day = !empty(trim($sched['day'] ?? '')) ? trim($sched['day']) : null;
            $start = !empty(trim($sched['start_time'] ?? '')) ? trim($sched['start_time']) : null;
            $end = !empty(trim($sched['end_time'] ?? '')) ? trim($sched['end_time']) : null;
            $room = !empty(trim($sched['room'] ?? '')) ? trim($sched['room']) : null;
            $instructor = !empty(trim($sched['instructor'] ?? '')) ? trim($sched['instructor']) : null;
            $mode = trim($sched['delivery_mode'] ?? 'Face-to-Face');
            
            if ($day || $start || $end) {
                if (!$day || !$start || !$end) {
                    throw new Exception("Incomplete schedule. Provide Day, Start, End, or leave all blank.");
                }
                
                if ($room) {
                    $rmConfStmt->execute([$room, $sectionId, $day, $end, $start]);
                    if ($conflict = $rmConfStmt->fetch(PDO::FETCH_ASSOC)) {
                        throw new Exception("Room Conflict: Room {$room} is booked by {$conflict['section_code']} ({$conflict['subject_code']}) at this time.");
                    }
                }
                
                if ($instructor) {
                    $instConfStmt->execute([$instructor, $sectionId, $day, $end, $start]);
                    if ($conflict = $instConfStmt->fetch(PDO::FETCH_ASSOC)) {
                        throw new Exception("Instructor Conflict: {$instructor} is teaching {$conflict['section_code']} ({$conflict['subject_code']}) at this time.");
                    }
                }
            }
            
            if ($id <= 0) {
                if ($subjectId <= 0) {
                    throw new Exception("Invalid subject ID for new schedule session.");
                }
                $insertStmt->execute([$sectionId, $subjectId, $day, $start, $end, $room, $instructor, $mode]);
            } else {
                $updateStmt->execute([$day, $start, $end, $room, $instructor, $mode, $id, $sectionId]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Schedule saved successfully.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
