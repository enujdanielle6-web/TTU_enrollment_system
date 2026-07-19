<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('college_sections.manage');

header('Content-Type: application/json');

$programId = (int)($_GET['program_id'] ?? 0);

if ($programId <= 0) {
    echo json_encode(['error' => 'Invalid program ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, curriculum_name, version, effective_academic_year 
        FROM college_curricula 
        WHERE program_id = ? AND status = 'active'
        ORDER BY curriculum_name ASC
    ");
    $stmt->execute([$programId]);
    $curricula = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($curricula);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
