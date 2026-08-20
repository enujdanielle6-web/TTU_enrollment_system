<?php
namespace App\Controllers\Admin\Finance;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use PDOException;
use Exception;

class FinanceController extends BaseController
{
    public function dashboard(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
requirePermission(['assessments.generate', 'payments.record']);

$pageTitle = 'Cashier Dashboard - Administrator';

        return $this->render('admin/finance/cashier_dashboard', get_defined_vars());
    }
    public function assessment(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$assessmentId = (int) ($_GET['id'] ?? 0);

if ($assessmentId <= 0) {
    $response->redirect("/sia/admin/finance/cashier_dashboard.php");
    return;
}

try {
    // Fetch Assessment Details
    $stmt = $pdo->prepare('
        SELECT sa.*, 
               u.first_name, u.last_name, u.email,
               a.reference_number, a.academic_level, a.grade_level, a.strand,
               s.name as scholarship_name,
               ft.is_per_unit
        FROM student_assessments sa
        INNER JOIN users u ON sa.user_id = u.id
        INNER JOIN applications a ON sa.application_id = a.id
        LEFT JOIN scholarships s ON sa.scholarship_id = s.id
        LEFT JOIN fee_templates ft ON sa.fee_template_id = ft.id
        WHERE sa.id = :id LIMIT 1
    ');
    $stmt->execute(['id' => $assessmentId]);
    $assessment = $stmt->fetch();

    if (!$assessment) {
        $response->redirect("/sia/admin/finance/cashier_dashboard.php");
        return;
    }

    // Calculate balances
    $totalAmount = (float)$assessment['total_amount'];
    $discountAmount = (float)$assessment['discount_amount'];
    $netAmount = (float)$assessment['net_amount'];
    $totalPaid = (float)$assessment['total_paid'];
    $balance = $netAmount - $totalPaid;
    if ($balance < 0) $balance = 0;

    // Fetch Payment History (Receipts) for this assessment
    $payStmt = $pdo->prepare('
        SELECT pr.*, u.first_name as cashier_first, u.last_name as cashier_last
        FROM payment_records pr
        LEFT JOIN users u ON pr.cashier_id = u.id
        WHERE pr.assessment_id = :id
        ORDER BY pr.created_at DESC
    ');
    $payStmt->execute(['id' => $assessmentId]);
    $payments = $payStmt->fetchAll();

    // Fetch Enrolled Subjects
    $enrolledSubjects = [];
    if ($assessment['academic_level'] === 'College') {
        $subStmt = $pdo->prepare('
            SELECT s.subject_code, s.subject_name, s.units 
            FROM college_enrollments es
            JOIN subjects s ON es.subject_id = s.id
            WHERE es.application_id = :app_id
        ');
        $subStmt->execute(['app_id' => $assessment['application_id']]);
        $enrolledSubjects = $subStmt->fetchAll();
    } elseif ($assessment['academic_level'] === 'Senior High School') {
        $subStmt = $pdo->prepare('
            SELECT s.subject_code, s.subject_name, s.units 
            FROM shs_enrollments es
            JOIN subjects s ON es.subject_id = s.id
            WHERE es.application_id = :app_id
        ');
        $subStmt->execute(['app_id' => $assessment['application_id']]);
        $enrolledSubjects = $subStmt->fetchAll();
    }

} catch (PDOException $e) {
    error_log('Admin assessment fetch failed: ' . $e->getMessage());
    $_SESSION['error_msg'] = 'A database error occurred while querying details for this assessment.';
    $response->redirect("/sia/admin/finance/cashier_dashboard.php");
    return;
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

$pageTitle = 'Student Account - Cashier';

        return $this->render('admin/finance/cashier_assessment', get_defined_vars());
    }
    public function payments(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$pageTitle = 'Payment History - Administrator';

        return $this->render('admin/finance/cashier_payments', get_defined_vars());
    }
    public function receipt(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        requirePermission('receipts.print');

        $paymentId = (int)($_GET['id'] ?? 0);
        if ($paymentId <= 0) {
            $_SESSION['admin_error'] = 'Invalid Payment ID for receipt.';
            $response->redirect('/sia/admin/finance/cashier_payments.php');
            return;
        }

        $stmt = $pdo->prepare('
            SELECT p.*, a.reference_number as app_ref, u.student_number, u.first_name, u.last_name, u.email
            FROM payment_records p
            JOIN student_assessments sa ON p.assessment_id = sa.id
            JOIN applications a ON sa.application_id = a.id
            JOIN users u ON p.user_id = u.id
            WHERE p.id = :id
        ');
        $stmt->execute(['id' => $paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            $_SESSION['admin_error'] = 'Payment record not found.';
            $response->redirect('/sia/admin/finance/cashier_payments.php');
            return;
        }

        return $this->render('admin/finance/receipt', ['payment' => $payment, 'pageTitle' => 'Payment Receipt']);
    }

    public function process(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response->redirect("/sia/admin/finance/cashier_dashboard.php");
    return;
}



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

            // Auto-finalize enrollment upon initial/full payment confirmation
            if ($newStatus === 'paid' || $newStatus === 'partial') {
                finalizeStudentEnrollment($pdo, $userId, (int)$assessment['application_id']);
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
            $response->redirect("/sia/admin/finance/cashier_receipt.php?id=$paymentId");
            return;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } elseif ($action === 'verify_online_payment') {
        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $decision = $_POST['decision'] ?? 'approve';
        $remarks = trim($_POST['remarks'] ?? '');
        $cashierId = (int)$_SESSION['user_id'];

        if ($paymentId <= 0) {
            throw new Exception('Invalid payment ID provided.');
        }

        $pdo->beginTransaction();

        try {
            // Fetch Payment Record
            $payStmt = $pdo->prepare('SELECT * FROM payment_records WHERE id = :id FOR UPDATE');
            $payStmt->execute(['id' => $paymentId]);
            $payment = $payStmt->fetch();

            if (!$payment || $payment['status'] !== 'pending') {
                throw new Exception('Payment record not found or already processed.');
            }

            $assessmentId = (int)$payment['assessment_id'];
            $userId = (int)$payment['user_id'];
            $amount = (float)$payment['amount'];

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

            if ($decision === 'reject') {
                if (empty($remarks)) {
                    throw new Exception('A reason for rejection is required. Please provide a remark.');
                }
                
                // Update Payment Record
                $updPayStmt = $pdo->prepare('UPDATE payment_records SET status = "rejected", remarks = :remarks, cashier_id = :cashier WHERE id = :id');
                $updPayStmt->execute([
                    'remarks' => $remarks,
                    'cashier' => $cashierId,
                    'id' => $paymentId
                ]);

                // Log payment activity for student
                $studentLogDesc = "Your online payment of ₱" . number_format($amount, 2) . " was rejected by the cashier. Reason: " . htmlspecialchars($remarks, ENT_QUOTES, 'UTF-8') . " Please submit a valid proof of payment.";
                $logPayStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description) VALUES (:user_id, :ip_address, :affected_record, "bi-x-circle text-danger", "Payment Rejected", :desc)');
                $logPayStmt->execute(['user_id' => $userId, 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null, 'affected_record' => "Assessment #$assessmentId", 'desc' => $studentLogDesc]);

                // Admin log
                logActivity(
                    $cashierId, 
                    'bi-shield-x', 
                    'Online Payment Rejected', 
                    "Rejected online payment of ₱" . number_format($amount, 2) . " for Assessment #$assessmentId.",
                    "Payment Record #$paymentId",
                    ['status' => 'pending'],
                    ['status' => 'rejected']
                );

                $pdo->commit();
                
                $_SESSION['success_msg'] = "Online payment successfully rejected.";
                $response->redirect("/sia/admin/finance/cashier_payments.php");
                return;
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

            // Update Payment Record
            $updPayStmt = $pdo->prepare('UPDATE payment_records SET status = "verified", cashier_id = :cashier, receipt_number = :receipt WHERE id = :id');
            $updPayStmt->execute([
                'cashier' => $cashierId,
                'receipt' => $receiptNumber,
                'id' => $paymentId
            ]);

            // Update Assessment
            $newPaid = $currentPaid + $amount;
            $newStatus = ($newPaid >= $netAmount) ? 'paid' : 'partial';

            $updAssStmt = $pdo->prepare('UPDATE student_assessments SET total_paid = :paid, payment_status = :status WHERE id = :id');
            $updAssStmt->execute([
                'paid' => $newPaid,
                'status' => $newStatus,
                'id' => $assessmentId
            ]);

            // Auto-finalize enrollment upon payment verification
            if ($newStatus === 'paid' || $newStatus === 'partial') {
                finalizeStudentEnrollment($pdo, $userId, (int)$assessment['application_id']);
            }

            // Log payment activity for student
            $logPayStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description) VALUES (:user_id, :ip_address, :affected_record, "bi-receipt-cutoff text-primary", "Payment Verified", "Your online payment of ₱' . number_format($amount, 2) . ' was verified. Receipt No: ' . $receiptNumber . '")');
            $logPayStmt->execute(['user_id' => $userId, 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null, 'affected_record' => "Assessment #$assessmentId"]);

            // Admin log
            logActivity(
                $cashierId, 
                'bi-shield-check', 
                'Online Payment Verified', 
                "Verified online payment of ₱" . number_format($amount, 2) . " (Receipt: $receiptNumber) for Assessment #$assessmentId.",
                "Payment Record #$paymentId",
                ['status' => 'pending'],
                ['status' => 'verified']
            );

            $pdo->commit();
            
            $_SESSION['success_msg'] = "Online payment verified successfully! Receipt No: $receiptNumber";
            $response->redirect("/sia/admin/finance/cashier_payments.php");
            return;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = $e->getMessage();
            $response->redirect("/sia/admin/finance/cashier_payments.php");
            return;
        }
    } else {
        throw new Exception('Invalid action requested.');
    }
} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
    $id = $_POST['assessment_id'] ?? 0;
    if ($id > 0) {
        $response->redirect("/sia/admin/finance/cashier_assessment.php?id=$id");
    } else {
        $response->redirect("/sia/admin/finance/cashier_dashboard.php");
    }
    return;
}

    }
}



