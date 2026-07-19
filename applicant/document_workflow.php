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

verifyCsrfToken();

$method = $_POST['submission_method'] ?? 'online';

if (!in_array($method, ['online', 'on_campus'], true)) {
    $_SESSION['doc_error'] = 'Invalid submission method.';
    header('Location: documents.php');
    exit;
}

try {
    $appStmt = $pdo->prepare('SELECT id, status FROM applications WHERE user_id = :user_id LIMIT 1');
    $appStmt->execute(['user_id' => $userId]);
    $app = $appStmt->fetch();

    if (!$app) {
        $_SESSION['doc_error'] = 'No active application found.';
        header('Location: documents.php');
        exit;
    }

    if (in_array($app['status'], ['approved', 'rejected', 'enrolled'], true)) {
        $_SESSION['doc_error'] = 'Your application is locked. You cannot change your submission method at this stage.';
        header('Location: documents.php');
        exit;
    }

    $updateStmt = $pdo->prepare('UPDATE applications SET document_submission_method = :method WHERE id = :id');
    $updateStmt->execute(['method' => $method, 'id' => $app['id']]);
    
    $methodLabel = $method === 'online' ? 'Online Upload' : 'On-Campus Verification';
    
    // Log activity for the applicant's dashboard timeline
    $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
    $logStmt->execute([
        'user_id' => $userId,
        'icon' => 'bi-gear-fill',
        'title' => 'Submission Workflow Updated',
        'description' => "You chose {$methodLabel} for your document requirements."
    ]);

    $_SESSION['doc_success'] = "Submission method successfully updated to {$methodLabel}.";

} catch (PDOException $e) {
    error_log('Workflow Update Error: ' . $e->getMessage());
    $_SESSION['doc_error'] = 'A database error occurred.';
}

header('Location: documents.php');
exit;
