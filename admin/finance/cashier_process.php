<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission(['assessments.generate', 'payments.record']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cashier_dashboard.php');
    exit;
}

verifyCsrfToken();

$action = $_POST['action'] ?? '';

try {
    if ($action === 'record_payment') {
        $assessmentId = (int)($_POST['assessment_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        $appId = (int)($_POST['application_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $method = trim($_POST['payment_method'] ?? '');
        $refNo = trim($_POST['reference_number'] ?? '');
        $cashierId = (int)$_SESSION['user_id'];

        if ($assessmentId <= 0 || !in_array($method, ['Cash', 'GCash', 'Bank Transfer'])) {
            throw new Exception('Invalid payment details provided.');
        }

        $pdo->beginTransaction();

        try {
            // Fetch Assessment
            $assStmt = $pdo->prepare('SELECT * FROM student_assessments WHERE id = :id FOR UPDATE');
            $assStmt->execute(['id' => $assessmentId]);
            $assessment = $assStmt->fetch();

            if (!$assessment) {
                throw new Exception('Assessment not found.');
            }

            $netAmount = (float)$assessment['net_amount'];
            $currentPaid = (float)$assessment['total_paid'];
            $balance = $netAmount - $currentPaid;

            if ($amount > $balance) {
                throw new Exception('Payment amount cannot exceed the remaining balance.');
            }

            $minPayment = min(3000, $balance);
            if ($amount < $minPayment) {
                throw new Exception('Minimum payment amount is ₱' . number_format($minPayment, 2) . '.');
            }

            // Generate Receipt Number (Format: REC-YYYYMMDD-XXXX)
            $datePrefix = date('Ymd');
            $receiptStmt = $pdo->query("SELECT receipt_number FROM payment_records WHERE receipt_number LIKE 'REC-$datePrefix-%' ORDER BY id DESC LIMIT 1");
            $lastReceipt = $receiptStmt->fetch();
            $nextNum = 1;
            if ($lastReceipt) {
                $parts = explode('-', $lastReceipt['receipt_number']);
                $nextNum = (int)end($parts) + 1;
            }
            $receiptNumber = sprintf("REC-%s-%04d", $datePrefix, $nextNum);

            // Record Payment
            $insertPayStmt = $pdo->prepare('
                INSERT INTO payment_records (assessment_id, user_id, cashier_id, amount, payment_date, payment_method, receipt_number, reference_number, status)
                VALUES (:ass_id, :user_id, :cashier_id, :amount, CURDATE(), :method, :receipt, :ref, "verified")
            ');
            $insertPayStmt->execute([
                'ass_id' => $assessmentId,
                'user_id' => $userId,
                'cashier_id' => $cashierId,
                'amount' => $amount,
                'method' => $method,
                'receipt' => $receiptNumber,
                'ref' => $refNo !== '' ? $refNo : null
            ]);
            
            $paymentId = $pdo->lastInsertId();

            // Update Assessment
            $newPaid = $currentPaid + $amount;
            $newStatus = ($newPaid >= $netAmount) ? 'paid' : 'partial';

            $updAssStmt = $pdo->prepare('UPDATE student_assessments SET total_paid = :paid, payment_status = :status WHERE id = :id');
            $updAssStmt->execute([
                'paid' => $newPaid,
                'status' => $newStatus,
                'id' => $assessmentId
            ]);

            // Removed auto-enrollment logic. Registrar must now explicitly finalize enrollment.
            if ($currentPaid == 0 && ($newStatus === 'paid' || $newStatus === 'partial')) {
                // This is their first payment, meaning they are now ready for final enrollment.
                $logAppStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description) VALUES (:user_id, :ip_address, :affected_record, "bi-hourglass-split text-warning", "Awaiting Enrollment Finalization", "Your payment has been verified. Your application has been forwarded to the Registrar for final enrollment processing.")');
                $logAppStmt->execute(['user_id' => $userId, 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null, 'affected_record' => "Application #$appId"]);
            }

            // Log payment activity for student
            $logPayStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description) VALUES (:user_id, :ip_address, :affected_record, "bi-receipt-cutoff text-primary", "Payment Received", "A payment of ₱' . number_format($amount, 2) . ' was successfully recorded. Receipt No: ' . $receiptNumber . '")');
            $logPayStmt->execute(['user_id' => $userId, 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null, 'affected_record' => "Assessment #$assessmentId"]);

            // Admin log
            logActivity(
                (int)$_SESSION['user_id'], 
                'bi-cash', 
                'Payment Recorded', 
                "Recorded payment of ₱" . number_format($amount, 2) . " (Receipt: $receiptNumber) for Assessment #$assessmentId.",
                "Payment Record #$paymentId",
                ['total_paid' => $currentPaid, 'payment_status' => $assessment['payment_status']],
                ['total_paid' => $newPaid, 'payment_status' => $newStatus]
            );

            $pdo->commit();
            
            $_SESSION['success_msg'] = "Payment recorded successfully. Receipt No: $receiptNumber";
            header("Location: cashier_receipt.php?id=$paymentId");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } else {
        throw new Exception('Invalid action requested.');
    }
} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
    $id = $_POST['assessment_id'] ?? 0;
    if ($id > 0) {
        header("Location: cashier_assessment.php?id=$id");
    } else {
        header("Location: cashier_dashboard.php");
    }
    exit;
}

