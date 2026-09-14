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

        $pageTitle = 'Cashier Dashboard - Triple T University';

        // Load System Settings
        $systemSettings = [];
        try {
            $stmt = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
            $systemSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (PDOException $e) {
            error_log('Finance system settings fetch failed: ' . $e->getMessage());
        }

        // Compute Financial KPIs
        $stats = [
            'outstanding_balances' => 0.0,
            'payments_today'       => 0.0,
            'total_revenue'        => 0.0,
            'total_accounts'       => 0,
            'paid_accounts'        => 0,
            'partial_accounts'     => 0,
            'unpaid_accounts'      => 0,
            'pending_verifications'=> 0,
        ];

        try {
            $stmtOut = $pdo->query('SELECT COALESCE(SUM(net_amount - total_paid), 0) FROM student_assessments WHERE payment_status IN ("unpaid", "partial")');
            $stats['outstanding_balances'] = (float)$stmtOut->fetchColumn();

            $stmtToday = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payment_records WHERE DATE(payment_date) = CURDATE() AND status != "rejected"');
            $stats['payments_today'] = (float)$stmtToday->fetchColumn();

            $stmtRev = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payment_records WHERE status != "rejected"');
            $stats['total_revenue'] = (float)$stmtRev->fetchColumn();

            $stmtCounts = $pdo->query('
                SELECT 
                    COUNT(*) as total_accounts,
                    SUM(CASE WHEN payment_status = "paid" THEN 1 ELSE 0 END) as paid_count,
                    SUM(CASE WHEN payment_status = "partial" THEN 1 ELSE 0 END) as partial_count,
                    SUM(CASE WHEN payment_status = "unpaid" THEN 1 ELSE 0 END) as unpaid_count
                FROM student_assessments
            ')->fetch(PDO::FETCH_ASSOC);

            if ($stmtCounts) {
                $stats['total_accounts']   = (int)($stmtCounts['total_accounts'] ?? 0);
                $stats['paid_accounts']    = (int)($stmtCounts['paid_count'] ?? 0);
                $stats['partial_accounts'] = (int)($stmtCounts['partial_count'] ?? 0);
                $stats['unpaid_accounts']  = (int)($stmtCounts['unpaid_count'] ?? 0);
            }

            $stmtPending = $pdo->query('SELECT COUNT(*) FROM payment_records WHERE status = "pending"');
            $stats['pending_verifications'] = (int)$stmtPending->fetchColumn();
        } catch (PDOException $e) {
            error_log('Cashier stats error: ' . $e->getMessage());
        }

        // Fetch Assessments with Applicant & Student Details
        $assessments = [];
        try {
            $stmt = $pdo->query('
                SELECT sa.id as assessment_id, sa.net_amount, sa.total_paid, sa.payment_status, sa.created_at,
                       a.id as application_id, a.reference_number, a.academic_level, a.grade_level, a.strand,
                       u.first_name, u.last_name, u.email, u.student_number
                FROM student_assessments sa
                INNER JOIN applications a ON sa.application_id = a.id
                INNER JOIN users u ON a.user_id = u.id
                ORDER BY 
                    CASE sa.payment_status 
                        WHEN "unpaid" THEN 1 
                        WHEN "partial" THEN 2 
                        ELSE 3 
                    END ASC,
                    sa.created_at DESC
            ');
            $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Cashier dashboard fetch failed: ' . $e->getMessage());
        }

        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        return $this->render('admin/finance/cashier_dashboard', get_defined_vars());
    }

    public function assessment(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        requirePermission(['assessments.generate', 'payments.record']);

        $assessmentId = (int) ($_GET['id'] ?? 0);

        if ($assessmentId <= 0) {
            $response->redirect("/sia/admin/finance/cashier_dashboard.php");
            return;
        }

        try {
            $breakdown = \App\Services\AssessmentService::getAssessmentBreakdown($pdo, $assessmentId);
            if (!$breakdown) {
                $response->redirect("/sia/admin/finance/cashier_dashboard.php");
                return;
            }

            $assessment = $breakdown['assessment'];
            $payments = $breakdown['payments'];
            $assessmentItems = $breakdown['assessment_items'];
            $enrolledSubjects = $breakdown['enrolled_subjects'];

            // Calculate balances
            $totalAmount = (float)$assessment['total_amount'];
            $discountAmount = (float)$assessment['discount_amount'];
            $netAmount = (float)$assessment['net_amount'];
            $totalPaid = (float)$assessment['total_paid'];
            $balance = max(0, $netAmount - $totalPaid);

            // Load System Settings
            $stmt = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
            $systemSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

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
        requirePermission(['payments.record']);

        $pageTitle = 'Payment History & Ledger - Triple T University';

        // Load System Settings
        $systemSettings = [];
        try {
            $stmt = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
            $systemSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (PDOException $e) {
            error_log('Payment history system settings fetch failed: ' . $e->getMessage());
        }

        // Compute Payment Statistics
        $stats = [
            'total_collections' => 0.0,
            'today_collections' => 0.0,
            'pending_reviews'   => 0,
            'total_transactions'=> 0,
        ];

        try {
            $stmtTot = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payment_records WHERE status = "verified"');
            $stats['total_collections'] = (float)$stmtTot->fetchColumn();

            $stmtToday = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payment_records WHERE DATE(payment_date) = CURDATE() AND status = "verified"');
            $stats['today_collections'] = (float)$stmtToday->fetchColumn();

            $stmtPending = $pdo->query('SELECT COUNT(*) FROM payment_records WHERE status = "pending"');
            $stats['pending_reviews'] = (int)$stmtPending->fetchColumn();

            $stmtCount = $pdo->query('SELECT COUNT(*) FROM payment_records');
            $stats['total_transactions'] = (int)$stmtCount->fetchColumn();
        } catch (PDOException $e) {
            error_log('Payment stats error: ' . $e->getMessage());
        }

        // Fetch All Payments
        $payments = [];
        try {
            $stmt = $pdo->query('
                SELECT pr.*, 
                       u.first_name as student_first, u.last_name as student_last, u.student_number, u.email as student_email,
                       c.first_name as cashier_first, c.last_name as cashier_last,
                       a.reference_number as app_ref, a.academic_level, a.strand
                FROM payment_records pr
                INNER JOIN users u ON pr.user_id = u.id
                LEFT JOIN users c ON pr.cashier_id = c.id
                INNER JOIN student_assessments sa ON pr.assessment_id = sa.id
                INNER JOIN applications a ON sa.application_id = a.id
                ORDER BY pr.created_at DESC
            ');
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Cashier payments fetch failed: ' . $e->getMessage());
        }

        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

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

            // Generate Concurrency-Safe Atomic Receipt Number (Format: REC-YYYYMMDD-XXXX)
            $receiptNumber = generateAtomicReceiptNumber($pdo);

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

            // Transition application status to payment_verified so Registrar can validate & finalize
            if ($newStatus === 'paid' || $newStatus === 'partial') {
                $updApp = $pdo->prepare('UPDATE applications SET status = "payment_verified" WHERE id = :app_id AND status != "enrolled"');
                $updApp->execute(['app_id' => (int)$assessment['application_id']]);
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
        $redirectUrl = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : "/sia/admin/finance/cashier_payments.php";

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
                $response->redirect($redirectUrl);
                return;
            }

            // Generate Concurrency-Safe Atomic Receipt Number (Format: REC-YYYYMMDD-XXXX)
            $receiptNumber = generateAtomicReceiptNumber($pdo);

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

            // Transition application status to payment_verified so Registrar can validate & finalize
            if ($newStatus === 'paid' || $newStatus === 'partial') {
                $updApp = $pdo->prepare('UPDATE applications SET status = "payment_verified" WHERE id = :app_id AND status != "enrolled"');
                $updApp->execute(['app_id' => (int)$assessment['application_id']]);
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
            $response->redirect($redirectUrl);
            return;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = $e->getMessage();
            $response->redirect($redirectUrl);
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



