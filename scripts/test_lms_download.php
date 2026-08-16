<?php
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    
    // Find an active LMS course
    $stmt = $pdo->query("SELECT id FROM lms_courses LIMIT 1");
    $lmsCourseId = $stmt->fetchColumn();

    if (!$lmsCourseId) {
        echo "No LMS course found.\n";
        exit(1);
    }

    // Create a dummy module
    $insertModule = $pdo->prepare("INSERT INTO lms_modules (lms_course_id, title, description, display_order) VALUES (:cid, 'Test Module', 'A module to test downloads', 1)");
    $insertModule->execute(['cid' => $lmsCourseId]);
    $moduleId = $pdo->lastInsertId();

    // Create a dummy file in app/uploads/lms/
    $targetDir = __DIR__ . '/../app/uploads/lms';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $fileName = 'test_document_' . time() . '.pdf';
    $filePath = $targetDir . '/' . $fileName;
    file_put_contents($filePath, '%PDF-1.4 Dummy PDF Content for Testing LMS Downloads');

    // Insert material record
    $insertMaterial = $pdo->prepare("INSERT INTO lms_materials (lms_module_id, file_name, file_path, mime_type, file_size) VALUES (:mid, 'Test Secure Document.pdf', :path, 'application/pdf', :size)");
    $insertMaterial->execute([
        'mid' => $moduleId,
        'path' => $fileName,
        'size' => filesize($filePath)
    ]);
    
    $materialId = $pdo->lastInsertId();
    
    echo "Successfully created test module (ID: $moduleId) and material (ID: $materialId) for lms_course_id: $lmsCourseId.\n";
    echo "File stored at: $filePath\n";
    echo "Test URL: http://localhost/sia/lms/download/material/$materialId\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
