<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

$userId = (int) $_SESSION['user_id'];

$isAjax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

function respond($success, $message, $extra = []) {
    global $isAjax;
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
        exit;
    } else {
        if ($success) {
            $_SESSION['doc_success'] = $message;
        } else {
            $_SESSION['doc_error'] = $message;
        }
        header('Location: documents.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// 1. Validate CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    respond(false, 'Invalid CSRF token.');
}

$documentName = trim($_POST['document_name'] ?? '');
$file = $_FILES['document_file'] ?? null;

if ($documentName === '' || !$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
    respond(false, 'Please select a file to upload.');
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    respond(false, 'A server error occurred during file upload. Please try again.');
}

$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    respond(false, 'File exceeds the maximum limit of 5MB.');
}

// 2. Validate file extension explicitly
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];

if (!in_array($ext, $allowedExts, true)) {
    respond(false, 'Invalid file extension. Only PDF, JPG, JPEG, and PNG are allowed.');
}

// 3. Validate MIME type explicitly and correlate with extension
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

$mimeMap = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
];

if (!isset($mimeMap[$ext]) || $mimeType !== $mimeMap[$ext]) {
    respond(false, 'File content type mismatch. Uploaded file MIME type does not match its extension.');
}

try {
    // Get Application ID
    $appStmt = $pdo->prepare('SELECT id FROM applications WHERE user_id = :user_id LIMIT 1');
    $appStmt->execute(['user_id' => $userId]);
    $app = $appStmt->fetch();

    if (!$app) {
        respond(false, 'No active application found.');
    }
    
    $appId = (int) $app['id'];

    // Generate secure filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeDocName = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($documentName));
    $uniq = bin2hex(random_bytes(8));
    $newFilename = sprintf('app_%d_%s_%s.%s', $appId, $safeDocName, $uniq, $ext);
    $uploadDir = __DIR__ . '/../uploads/documents/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $targetPath = $uploadDir . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Check if document already exists to either update or insert
        $checkStmt = $pdo->prepare('SELECT id, file_path FROM application_documents WHERE application_id = :app_id AND document_name = :doc_name LIMIT 1');
        $checkStmt->execute(['app_id' => $appId, 'doc_name' => $documentName]);
        $existing = $checkStmt->fetch();

        $docId = 0;
        if ($existing) {
            // Remove old file if it exists
            if (!empty($existing['file_path'])) {
                $oldPath = $uploadDir . basename($existing['file_path']);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $updateStmt = $pdo->prepare('UPDATE application_documents SET file_path = :file_path, status = "pending" WHERE id = :id');
            $updateStmt->execute(['file_path' => $newFilename, 'id' => $existing['id']]);
            $docId = $existing['id'];
        } else {
            $insertStmt = $pdo->prepare('INSERT INTO application_documents (application_id, document_name, file_path, status) VALUES (:app_id, :doc_name, :file_path, "pending")');
            $insertStmt->execute([
                'app_id' => $appId,
                'doc_name' => $documentName,
                'file_path' => $newFilename
            ]);
            $docId = $pdo->lastInsertId();
        }
        
        // Log activity
        $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
        $logStmt->execute([
            'user_id' => $userId,
            'icon' => 'bi-cloud-arrow-up',
            'title' => 'Document Uploaded',
            'description' => "You successfully uploaded your {$documentName}."
        ]);

        respond(true, "{$documentName} uploaded successfully.", ['doc_id' => $docId]);
    } else {
        respond(false, 'Failed to move uploaded file. Check directory permissions.');
    }

} catch (PDOException $e) {
    error_log('Upload DB Error: ' . $e->getMessage());
    respond(false, 'A database error occurred.');
}
