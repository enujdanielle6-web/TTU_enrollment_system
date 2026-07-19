<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: scholarships.php');
    exit;
}

verifyCsrfToken();

$userId = (int)$_SESSION['user_id'];
$scholarshipId = (int)($_POST['scholarship_id'] ?? 0);

if ($scholarshipId <= 0) {
    $_SESSION['error_msg'] = 'Invalid scholarship selection.';
    header('Location: scholarships.php');
    exit;
}

try {
    // Verify eligibility (Application must be approved/enrolled and have an assessment)
    $stmt = $pdo->prepare('
        SELECT a.id, a.status, sa.id as assessment_id 
        FROM applications a 
        LEFT JOIN student_assessments sa ON a.id = sa.application_id 
        WHERE a.user_id = :user_id LIMIT 1
    ');
    $stmt->execute(['user_id' => $userId]);
    $eligibility = $stmt->fetch();

    if (!$eligibility || !in_array($eligibility['status'], ['approved', 'enrolled'], true)) {
        throw new Exception('You are not eligible to apply for scholarships at this time.');
    }

    if (!$eligibility['assessment_id']) {
        throw new Exception('Your fee assessment has not been generated yet.');
    }

    // Check if scholarship exists and is active
    $scholStmt = $pdo->prepare('SELECT name FROM scholarships WHERE id = :id AND is_active = 1');
    $scholStmt->execute(['id' => $scholarshipId]);
    $scholarship = $scholStmt->fetch();

    if (!$scholarship) {
        throw new Exception('The selected scholarship is no longer available.');
    }

    // Check if already applied
    $checkAppStmt = $pdo->prepare('SELECT id FROM scholarship_applications WHERE user_id = :user_id AND scholarship_id = :schol_id LIMIT 1');
    $checkAppStmt->execute(['user_id' => $userId, 'schol_id' => $scholarshipId]);
    if ($checkAppStmt->fetch()) {
        throw new Exception('You have already applied for this scholarship.');
    }

    // Insert Application
    $insertStmt = $pdo->prepare('INSERT INTO scholarship_applications (user_id, scholarship_id, status) VALUES (:user_id, :schol_id, "pending")');
    $insertStmt->execute([
        'user_id' => $userId,
        'schol_id' => $scholarshipId
    ]);

    // Log Activity
    $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, "bi-award text-primary", :title, :description)');
    $logStmt->execute([
        'user_id' => $userId,
        'title' => 'Scholarship Application Submitted',
        'description' => 'You applied for the ' . $scholarship['name'] . ' scholarship.'
    ]);

    $_SESSION['success_msg'] = 'Your scholarship application has been submitted successfully.';
} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
}

header('Location: scholarships.php');
exit;
