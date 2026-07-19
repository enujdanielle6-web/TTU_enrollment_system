<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: documents.php');
    exit;
}

// 1. Validate CSRF Token
verifyCsrfToken();

$documentName = trim($_POST['document_name'] ?? '');
$file = $_FILES['document_file'] ?? null;

if ($documentName === '' || !$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
    $_SESSION['doc_error'] = 'Please select a file to upload.';
    header('Location: documents.php');
    exit;
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['doc_error'] = 'A server error occurred during file upload. Please try again.';
    header('Location: documents.php');
    exit;
}

$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    $_SESSION['doc_error'] = 'File exceeds the maximum limit of 5MB.';
    header('Location: documents.php');
    exit;
}

// 2. Validate file extension explicitly
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];

if (!in_array($ext, $allowedExts, true)) {
    $_SESSION['doc_error'] = 'Invalid file extension. Only PDF, JPG, JPEG, and PNG are allowed.';
    header('Location: documents.php');
    exit;
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
    $_SESSION['doc_error'] = 'File content type mismatch. Uploaded file MIME type does not match its extension.';
    header('Location: documents.php');
    exit;
}

try {
    // Get Application ID
    $appStmt = $pdo->prepare('SELECT id FROM applications WHERE user_id = :user_id LIMIT 1');
    $appStmt->execute(['user_id' => $userId]);
    $app = $appStmt->fetch();

    if (!$app) {
        $_SESSION['doc_error'] = 'No active application found.';
        header('Location: documents.php');
        exit;
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
        } else {
            $insertStmt = $pdo->prepare('INSERT INTO application_documents (application_id, document_name, file_path, status) VALUES (:app_id, :doc_name, :file_path, "pending")');
            $insertStmt->execute([
                'app_id' => $appId,
                'doc_name' => $documentName,
                'file_path' => $newFilename
            ]);
        }
        
        // Log activity
        $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
        $logStmt->execute([
            'user_id' => $userId,
            'icon' => 'bi-cloud-arrow-up',
            'title' => 'Document Uploaded',
            'description' => "You successfully uploaded your {$documentName}."
        ]);

        $_SESSION['doc_success'] = "{$documentName} uploaded successfully.";
    } else {
        $_SESSION['doc_error'] = 'Failed to move uploaded file. Check directory permissions.';
    }

} catch (PDOException $e) {
    error_log('Upload DB Error: ' . $e->getMessage());
    $_SESSION['doc_error'] = 'A database error occurred.';
}

header('Location: documents.php');
exit;
