<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('college_sections.manage');

header('Content-Type: application/json');

$currId = (int)($_GET['curriculum_id'] ?? 0);
$yearLevel = trim($_GET['year_level'] ?? '');
$semester = trim($_GET['semester'] ?? '');

if ($currId <= 0 || empty($yearLevel) || empty($semester)) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.display_order, s.subject_code, s.subject_name, s.units, s.subject_type
        FROM college_curriculum_subjects c
        INNER JOIN subjects s ON c.subject_id = s.id
        WHERE c.curriculum_id = ? AND c.year_level = ? AND c.semester = ?
        ORDER BY c.display_order ASC, s.subject_code ASC
    ");
    $stmt->execute([$currId, $yearLevel, $semester]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($subjects);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
