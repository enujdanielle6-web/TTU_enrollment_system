<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('documents.verify');

$docId = (int) ($_GET['id'] ?? 0);

if ($docId <= 0) {
    http_response_code(400);
    exit('Invalid document ID.');
}

// Admins can view any document by ID, regardless of user
$stmt = $pdo->prepare('
    SELECT file_path, document_name 
    FROM application_documents 
    WHERE id = :doc_id 
    LIMIT 1
');
$stmt->execute(['doc_id' => $docId]);
$doc = $stmt->fetch();

if (!$doc || empty($doc['file_path'])) {
    http_response_code(404);
    exit('Document not found or access denied.');
}

$filePath = __DIR__ . '/../../uploads/documents/' . basename($doc['file_path']);

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File physically missing from server.');
}

$mimeType = mime_content_type($filePath);
if ($mimeType === false) {
    $mimeType = 'application/octet-stream';
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="' . basename($doc['file_path']) . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Clear output buffer to prevent corrupted binary files
if (ob_get_length()) {
    ob_clean();
}
flush();

readfile($filePath);
exit;

