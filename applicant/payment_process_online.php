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
$amount = (float) ($_POST['amount'] ?? 0);
$paymentMethod = trim($_POST['payment_method'] ?? 'Online Payment');

if ($assessmentId <= 0 || $amount <= 0) {
    $_SESSION['assessment_error'] = 'Invalid payment details.';
    header('Location: assessment.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Lock assessment for update
    $stmt = $pdo->prepare('
        SELECT sa.id, sa.application_id, sa.net_amount, sa.total_paid 
        FROM student_assessments sa
        WHERE sa.id = :id AND sa.user_id = :user_id 
        FOR UPDATE
    ');
    $stmt->execute(['id' => $assessmentId, 'user_id' => $userId]);
    $assessment = $stmt->fetch();

    if (!$assessment) {
        throw new Exception('Assessment not found or access denied.');
    }

    $netAmount = (float)$assessment['net_amount'];
    $currentTotalPaid = (float)$assessment['total_paid'];
    $balance = $netAmount - $currentTotalPaid;

    if ($amount > $balance) {
        // Automatically adjust down if they overpay mockingly
        $amount = $balance;
    }

    if ($amount <= 0) {
        throw new Exception('Account is already fully paid.');
    }

    // Generate receipt number
    $receiptNumber = 'ONL-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

    // Insert payment record
    $insPay = $pdo->prepare('
        INSERT INTO payment_records (assessment_id, receipt_number, amount, payment_method, reference_number)
        VALUES (:ass_id, :receipt, :amount, :method, :ref)
    ');
    $insPay->execute([
        'ass_id' => $assessmentId,
        'receipt' => $receiptNumber,
        'amount' => $amount,
        'method' => $paymentMethod,
        'ref' => 'MOCK-' . time()
    ]);

    // Update assessment
    $newTotalPaid = $currentTotalPaid + $amount;
    $paymentStatus = ($newTotalPaid >= $netAmount) ? 'paid' : 'partial';

    $updAss = $pdo->prepare('UPDATE student_assessments SET total_paid = :paid, payment_status = :status WHERE id = :id');
    $updAss->execute([
        'paid' => $newTotalPaid,
        'status' => $paymentStatus,
        'id' => $assessmentId
    ]);

    // Check if fully paid, update application status to 'enrolled'
    $statusUpdated = false;
    if ($paymentStatus === 'paid') {
        $updApp = $pdo->prepare('UPDATE applications SET status = "enrolled" WHERE id = :id AND status != "enrolled"');
        $updApp->execute(['id' => $assessment['application_id']]);
        if ($updApp->rowCount() > 0) {
            $statusUpdated = true;
            
            // Generate Student Number if they don't have one
            $uStmt = $pdo->prepare('SELECT student_number FROM users WHERE id = :id LIMIT 1');
            $uStmt->execute(['id' => $userId]);
            if (empty($uStmt->fetchColumn())) {
                $newNumber = generateStudentNumber($pdo);
                $pdo->prepare('UPDATE users SET student_number = :sn WHERE id = :id')->execute(['sn' => $newNumber, 'id' => $userId]);
            }
        }
    }

    // Log activity
    $logMsg = "Successfully paid ₱" . number_format($amount, 2) . " via {$paymentMethod}. Receipt: {$receiptNumber}";
    if ($statusUpdated) {
        $logMsg .= " You are now officially enrolled!";
    }
    
    $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)')->execute([
        'user_id' => $userId,
        'icon' => 'bi-credit-card text-success',
        'title' => 'Online Payment Received',
        'description' => $logMsg
    ]);

    $pdo->commit();
    
    $_SESSION['assessment_success'] = 'Payment successful! ' . $logMsg;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Online payment processing failed: ' . $e->getMessage());
    $_SESSION['assessment_error'] = 'Payment failed: ' . $e->getMessage();
}

header('Location: assessment.php');
exit;
