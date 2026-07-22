<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: assessment.php');
    exit;
}

verifyCsrfToken();

$userId = (int) $_SESSION['user_id'];
$assessmentId = (int) ($_POST['assessment_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($assessmentId <= 0 || !in_array($action, ['accept', 'cancel'])) {
    header('Location: assessment.php');
    exit;
}

try {
    // Verify assessment belongs to user
    $stmt = $pdo->prepare('SELECT id, application_id FROM student_assessments WHERE id = :id AND user_id = :user_id LIMIT 1');
    $stmt->execute(['id' => $assessmentId, 'user_id' => $userId]);
    $assessment = $stmt->fetch();

    if (!$assessment) {
        header('Location: assessment.php');
        exit;
    }

    if ($action === 'accept') {
        $upd = $pdo->prepare('UPDATE student_assessments SET preview_accepted = 1 WHERE id = :id');
        $upd->execute(['id' => $assessmentId]);
        
        $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)')->execute([
            'user_id' => $userId,
            'icon' => 'bi-check-circle-fill text-success',
            'title' => 'Assessment Accepted',
            'description' => 'You have accepted the financial assessment. You may now proceed with the payment.'
        ]);
        
        $_SESSION['assessment_success'] = 'Assessment accepted. You can now proceed to payment.';
        
    } elseif ($action === 'cancel') {
        // Find application
        $appId = $assessment['application_id'];
        
        // Update application status to rejected (cancelled by student)
        $upd = $pdo->prepare('UPDATE applications SET status = "rejected" WHERE id = :id');
        $upd->execute(['id' => $appId]);
        
        $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)')->execute([
            'user_id' => $userId,
            'icon' => 'bi-x-circle-fill text-danger',
            'title' => 'Application Cancelled',
            'description' => 'You have cancelled your enrollment application.'
        ]);
        
        $_SESSION['assessment_error'] = 'Your enrollment application has been cancelled.';
    }

} catch (Exception $e) {
    error_log('Assessment action failed: ' . $e->getMessage());
    $_SESSION['assessment_error'] = 'An error occurred while processing your request.';
}

header('Location: assessment.php');
exit;
