<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

$docId = (int) ($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user_id'];

if ($docId <= 0) {
    http_response_code(400);
    exit('Invalid document ID.');
}

// Securely ensure the document belongs to the currently logged-in applicant
$stmt = $pdo->prepare('
    SELECT d.file_path, d.document_name 
    FROM application_documents d
    INNER JOIN applications a ON a.id = d.application_id
    WHERE d.id = :doc_id AND a.user_id = :user_id
    LIMIT 1
');
$stmt->execute(['doc_id' => $docId, 'user_id' => $userId]);
$doc = $stmt->fetch();

if (!$doc || empty($doc['file_path'])) {
    http_response_code(404);
    exit('Document not found or access denied.');
}

$filePath = __DIR__ . '/../uploads/documents/' . basename($doc['file_path']);

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File physically missing from server.');
}

$mimeType = mime_content_type($filePath);
if ($mimeType === false) {
    $mimeType = 'application/octet-stream';
}

// Output headers to serve the file directly to the browser
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
