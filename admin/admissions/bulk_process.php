<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('documents.verify');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: review.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
$selectedApps = $_POST['selected_apps'] ?? [];
$bulkStatus = $_POST['bulk_status'] ?? '';

// 1. CSRF Verification
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    $_SESSION['admin_error'] = 'Security validation failed. Please try again.';
    header('Location: review.php');
    exit;
}

$validStatuses = ['pending', 'under_review', 'correction_required', 'approved', 'rejected', 'enrolled'];

if (empty($selectedApps) || !in_array($bulkStatus, $validStatuses, true)) {
    $_SESSION['admin_error'] = 'No applications selected or invalid target status.';
    header('Location: review.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $updateStmt = $pdo->prepare('UPDATE applications SET status = :status WHERE id = :id');
    $userStmt = $pdo->prepare('SELECT user_id, reference_number, status FROM applications WHERE id = :id LIMIT 1');
    $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description, old_value, new_value) VALUES (:user_id, :ip_address, :affected_record, :icon, :title, :description, :old_value, :new_value)');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    $logIcon = match($bulkStatus) {
        'approved' => 'bi-check-circle-fill text-success',
        'rejected' => 'bi-x-circle-fill text-danger',
        'correction_required' => 'bi-exclamation-triangle-fill text-warning',
        'enrolled' => 'bi-mortarboard-fill text-success',
        'under_review' => 'bi-search text-info',
        default => 'bi-info-circle-fill text-primary'
    };

    $statusTitle = formatApplicationStatus($bulkStatus);
    $logTitle = "Application Status: {$statusTitle}";
    $logDescription = getApplicationStatusMessage($bulkStatus);

    $processedCount = 0;

    foreach ($selectedApps as $appId) {
        $appId = (int)$appId;
        if ($appId <= 0) continue;

        // Fetch User ID
        $userStmt->execute(['id' => $appId]);
        $appInfo = $userStmt->fetch();
        if (!$appInfo) continue;

        $userId = (int)$appInfo['user_id'];

        // Update status
        $updateStmt->execute(['status' => $bulkStatus, 'id' => $appId]);

        // Add Log
        $logStmt->execute([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'affected_record' => "Application #$appId",
            'icon' => $logIcon,
            'title' => $logTitle,
            'description' => $logDescription,
            'old_value' => json_encode(['status' => $appInfo['status']]),
            'new_value' => json_encode(['status' => $bulkStatus])
        ]);

        $processedCount++;
    }

    $pdo->commit();

    // Audit Log for Admin
    logActivity(
        (int)$_SESSION['user_id'],
        'bi-layers-fill',
        'Bulk Status Update',
        "Bulk updated $processedCount applications to '$statusTitle'."
    );

    $_SESSION['admin_success'] = "Successfully updated $processedCount application(s) to '$statusTitle'.";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Bulk action failed: ' . $e->getMessage());
    $_SESSION['admin_error'] = 'An error occurred while bulk processing applications.';
}

header('Location: review.php');
exit;

