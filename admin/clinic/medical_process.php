<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('medical.review');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: medical_clearance.php');
    exit;
}

verifyCsrfToken();

$recordId = (int)($_POST['record_id'] ?? 0);
$userId = (int)($_POST['user_id'] ?? 0);
$status = $_POST['status'] ?? '';
$adminRemarks = trim($_POST['admin_remarks'] ?? '');

$validStatuses = ['pending', 'under_review', 'verified', 'correction_required', 'rejected'];

if ($recordId <= 0 || !in_array($status, $validStatuses, true)) {
    $_SESSION['error_msg'] = 'Invalid request parameters.';
    header('Location: medical_clearance.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Fetch old status to check if it changed
    $stmt = $pdo->prepare('SELECT status FROM health_records WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $recordId]);
    $oldStatus = $stmt->fetchColumn();

    if ($oldStatus === false) {
        throw new Exception('Record not found.');
    }

    // Update record
    $upd = $pdo->prepare('UPDATE health_records SET status = :status, admin_remarks = :remarks WHERE id = :id');
    $upd->execute([
        'status' => $status,
        'remarks' => $adminRemarks !== '' ? $adminRemarks : null,
        'id' => $recordId
    ]);

    // Log Activity if status changed
    if ($oldStatus !== $status) {
        $logIcon = match($status) {
            'verified' => 'bi-heart-pulse-fill text-success',
            'rejected' => 'bi-x-circle-fill text-danger',
            'correction_required' => 'bi-exclamation-triangle-fill text-warning',
            'under_review' => 'bi-search text-info',
            default => 'bi-info-circle-fill text-primary'
        };

        $statusTitle = formatApplicationStatus($status);
        $logTitle = "Medical Clearance: {$statusTitle}";
        
        $logDescription = $adminRemarks !== '' 
            ? "Clinic Remarks: " . $adminRemarks 
            : "Your medical clearance status has been updated to {$statusTitle}.";

        $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
        $logStmt->execute([
            'user_id' => $userId,
            'icon' => $logIcon,
            'title' => $logTitle,
            'description' => $logDescription
        ]);
    }

    $pdo->commit();
    $_SESSION['success_msg'] = 'Medical clearance successfully updated.';
    header("Location: medical_detail.php?id={$recordId}");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Medical Process Failed: ' . $e->getMessage());
    $_SESSION['error_msg'] = 'A database error occurred while updating the record.';
    header("Location: medical_detail.php?id={$recordId}");
    exit;
}

