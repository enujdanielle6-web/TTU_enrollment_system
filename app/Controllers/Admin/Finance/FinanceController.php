<?php
namespace App\Controllers\Admin\Finance;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Services\PaymentService;
use App\Repositories\PaymentRepository;
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
        $page = max(1, (int)($request->query('page') ?? ($_GET['page'] ?? 1)));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $total_items = 0;
        $assessments = [];
        try {
            $total_items = (int)$pdo->query('
                SELECT COUNT(*)
                FROM student_assessments sa
                INNER JOIN applications a ON sa.application_id = a.id
                INNER JOIN users u ON a.user_id = u.id
            ')->fetchColumn();

            $stmt = $pdo->prepare('
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
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Cashier dashboard fetch failed: ' . $e->getMessage());
        }
        $total_pages = max(1, ceil($total_items / $limit));

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
            $response->redirect(BASE_PATH . "/admin/finance/cashier_dashboard.php");
            return;
        }

        try {
            $breakdown = \App\Services\AssessmentService::getAssessmentBreakdown($pdo, $assessmentId);
            if (!$breakdown) {
                $response->redirect(BASE_PATH . "/admin/finance/cashier_dashboard.php");
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
            $response->redirect(BASE_PATH . "/admin/finance/cashier_dashboard.php");
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

        $paymentRepo = new PaymentRepository($pdo);

        // Compute Payment Statistics via PaymentRepository
        $stats = [
            'total_collections' => 0.0,
            'today_collections' => 0.0,
            'pending_reviews'   => 0,
            'total_transactions'=> 0,
        ];

        try {
            $stats = $paymentRepo->getFinancialStats();
        } catch (PDOException $e) {
            error_log('Payment stats error: ' . $e->getMessage());
        }

        // Fetch All Payments via PaymentRepository
        $page = max(1, (int)($request->query('page') ?? ($_GET['page'] ?? 1)));
        $limit = 15;
        $offset = ($page - 1) * $limit;
        $total_items = 0;
        $payments = [];
        try {
            $total_items = $paymentRepo->countPayments();
            $payments = $paymentRepo->getPaginatedPayments($limit, $offset);
        } catch (PDOException $e) {
            error_log('Cashier payments fetch failed: ' . $e->getMessage());
        }
        $total_pages = max(1, ceil($total_items / $limit));

        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        return $this->render('admin/finance/cashier_payments', get_defined_vars());
    }
    public function receipt(Request $request, Response $response)
    {
        $pdo = Database::getConnection();

        $paymentId = (int)($_GET['id'] ?? 0);
        if ($paymentId <= 0) {
            $_SESSION['admin_error'] = 'Invalid Payment ID for receipt.';
            $response->redirect(BASE_PATH . '/admin/finance/cashier_payments.php');
            return;
        }

        $paymentRepo = new PaymentRepository($pdo);
        $payment = $paymentRepo->findWithDetails($paymentId);

        if (!$payment) {
            $_SESSION['admin_error'] = 'Payment record not found.';
            $response->redirect(BASE_PATH . '/admin/finance/cashier_payments.php');
            return;
        }

        // Allow permission if user has receipts.print OR if user is the student who owns this payment record
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        $isOwner = ($currentUserId > 0 && (int)$payment['user_id'] === $currentUserId);
        if (!hasPermission('receipts.print') && !$isOwner) {
            requirePermission('receipts.print');
        }

        // Fetch line items for this assessment
        $items = [];
        $assessment = null;
        if (!empty($payment['assessment_id'])) {
            $stmt = $pdo->prepare('SELECT * FROM assessment_items WHERE assessment_id = :aid ORDER BY id ASC');
            $stmt->execute(['aid' => (int)$payment['assessment_id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $stmtAss = $pdo->prepare('SELECT * FROM student_assessments WHERE id = :aid LIMIT 1');
            $stmtAss->execute(['aid' => (int)$payment['assessment_id']]);
            $assessment = $stmtAss->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        // Fetch system settings for institutional header
        $systemSettings = [];
        try {
            $stmtSys = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
            $systemSettings = $stmtSys->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (\Exception $e) {
            // fallback
        }

        return $this->render('admin/finance/receipt', [
            'payment'        => $payment,
            'items'          => $items,
            'assessment'     => $assessment,
            'systemSettings' => $systemSettings,
            'pageTitle'      => 'Official Receipt - ' . ($payment['receipt_number'] ?? 'OR'),
        ]);
    }

    public function process(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response->redirect(BASE_PATH . "/admin/finance/cashier_dashboard.php");
    return;
}

requirePermission('payments.record');

$action = $_POST['action'] ?? '';
$paymentService = new PaymentService();

try {
    if ($action === 'record_payment') {
        $assessmentId = (int)($_POST['assessment_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        $appId = (int)($_POST['application_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $method = trim($_POST['payment_method'] ?? '');
        $refNo = trim($_POST['reference_number'] ?? '');
        $cashierId = (int)$_SESSION['user_id'];

        $result = $paymentService->recordOverTheCounterPayment([
            'assessment_id'    => $assessmentId,
            'user_id'          => $userId,
            'application_id'   => $appId,
            'amount'           => $amount,
            'payment_method'   => $method,
            'reference_number' => $refNo,
        ], $cashierId);

        $_SESSION['success_msg'] = $result['message'];
        $response->redirect(BASE_PATH . "/admin/finance/cashier_receipt.php?id=" . $result['payment_id']);
        return;
    } elseif ($action === 'verify_online_payment') {
        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $decision = $_POST['decision'] ?? 'approve';
        $remarks = trim($_POST['remarks'] ?? '');
        $cashierId = (int)$_SESSION['user_id'];
        $redirectUrl = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : BASE_PATH . "/admin/finance/cashier_payments.php";

        try {
            if ($decision === 'reject') {
                $paymentService->rejectOnlinePayment($paymentId, $cashierId, $remarks);
                $_SESSION['success_msg'] = "Online payment successfully rejected.";
                $response->redirect($redirectUrl);
                return;
            }

            $result = $paymentService->verifyOnlinePayment($paymentId, $cashierId);
            $_SESSION['success_msg'] = $result['message'];
            $response->redirect($redirectUrl);
            return;
        } catch (Exception $e) {
            $_SESSION['error_msg'] = $e->getMessage();
            $response->redirect($redirectUrl);
            return;
        }
    } elseif ($action === 'reconcile_paymongo') {
        $sessionId = trim((string)($_POST['session_id'] ?? ''));
        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $redirectUrl = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : BASE_PATH . "/admin/finance/cashier_payments.php";

        try {
            if ($sessionId === '' && $paymentId > 0) {
                $pRow = $paymentRepo->findById($paymentId);
                $sessionId = (string)($pRow['checkout_session_id'] ?? '');
            }

            if ($sessionId === '') {
                throw new Exception('Missing PayMongo checkout session ID to reconcile.');
            }

            $result = $paymentService->autoVerifyPayMongoCheckoutSession($sessionId);
            if ($result['status'] === 'verified') {
                $_SESSION['success_msg'] = "PayMongo payment verified! Receipt: " . ($result['receipt_number'] ?? 'N/A');
            } elseif (in_array($result['status'], ['cancelled', 'expired'], true)) {
                $_SESSION['warning_msg'] = "Gateway reported session is unpaid or expired. Record marked as {$result['status']}.";
            } else {
                $_SESSION['info_msg'] = $result['message'] ?? 'Reconciliation complete.';
            }
            $response->redirect($redirectUrl);
            return;
        } catch (Exception $e) {
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
        $response->redirect(BASE_PATH . "/admin/finance/cashier_assessment.php?id=$id");
    } else {
        $response->redirect(BASE_PATH . "/admin/finance/cashier_dashboard.php");
    }
    return;
}
    }

    /**
     * Renders the real-time Payment Queue and PayMongo Gateway Monitoring Dashboard.
     */
    public function paymentMonitoring(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        requirePermission(['payments.record', 'fees.manage', 'settings.manage']);

        $pageTitle = 'Payment Queue & Gateway Monitor - Triple T University';

        // Load System Settings for Header Information
        $systemSettings = [];
        try {
            $stmt = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
            $systemSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (PDOException $e) {
            error_log('Monitoring system settings fetch failed: ' . $e->getMessage());
        }

        $queueService = new \App\Services\PaymentQueueService(null, $pdo);
        $monitoringData = $queueService->getMonitoringDashboardData();

        $metrics = $monitoringData['metrics'];
        $sessionCounts = $monitoringData['session_counts'];
        $activeSessions = $monitoringData['active_sessions'];
        $waitingSessions = $monitoringData['waiting_sessions'];
        $payMongoStats = $monitoringData['paymongo_stats'];
        $recentTransactions = $monitoringData['recent_transactions'];

        // Determine if current user has permission to configure queue capacity/settings
        $canManageSettings = hasPermission(['fees.manage', 'settings.manage']);

        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        return $this->render('admin/finance/payment_monitoring', get_defined_vars());
    }

    /**
     * AJAX endpoint for real-time polling of queue metrics and gateway telemetry.
     */
    public function paymentMonitoringData(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        if (!hasPermission(['payments.record', 'fees.manage', 'settings.manage'])) {
            $response->json(['success' => false, 'message' => 'Access Denied: Insufficient permissions.'], 403);
            return;
        }

        $queueService = new \App\Services\PaymentQueueService(null, $pdo);
        $data = $queueService->getMonitoringDashboardData();

        $response->json([
            'success' => true,
            'data'    => $data,
        ], 200);
        return;
    }

    /**
     * Handles administrative queue configuration updates (Capacity, Window Duration, Toggle).
     * Strictly gated by 'fees.manage' or 'settings.manage' permissions.
     */
    public function updateQueueSettings(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        $isAjax = $request->isAjax();

        if (!hasPermission(['fees.manage', 'settings.manage'])) {
            if ($isAjax) {
                $response->json(['success' => false, 'message' => 'Access Denied: You do not have permission to modify payment queue configuration.'], 403);
                return;
            }
            $_SESSION['error_msg'] = 'Access Denied: You do not have permission to modify payment queue configuration.';
            $response->redirect(BASE_PATH . '/admin/finance/payment_monitoring.php');
            return;
        }

        try {
            $queueService = new \App\Services\PaymentQueueService(null, $pdo);

            $newCapacity = (int)($request->input('max_concurrency') ?? 100);
            $newDuration = (int)($request->input('session_duration_minutes') ?? 15);
            $queueEnabled = (bool)($request->input('queue_enabled') !== null ? (int)$request->input('queue_enabled') : 1);

            if ($newCapacity < 1) {
                throw new Exception('Configured capacity must be at least 1 slot.');
            }
            if ($newDuration < 1 || $newDuration > 120) {
                throw new Exception('Session duration must be between 1 and 120 minutes.');
            }

            $oldCapacity = $queueService->getMaxConcurrency();
            $queueService->setMaxConcurrency($newCapacity);
            $queueService->setSessionDuration($newDuration);
            $queueService->setQueueEnabled($queueEnabled);

            // If capacity expanded, immediately promote eligible waiting students
            $promoted = 0;
            if ($newCapacity > $oldCapacity) {
                $promoted = $queueService->promoteEligibleWaitingSessions();
            }

            // Record audit log
            $userId = (int)($_SESSION['user_id'] ?? 0);
            logActivity(
                $userId,
                'bi-sliders',
                'Payment Queue Settings Updated',
                "Updated payment queue: Capacity={$newCapacity} (was {$oldCapacity}), Duration={$newDuration}m, Enabled=" . ($queueEnabled ? 'Yes' : 'No') . ($promoted > 0 ? " ({$promoted} waiting students promoted)" : ""),
                'system_settings'
            );

            $msg = "Payment queue settings updated successfully. Capacity: {$newCapacity} slots." . ($promoted > 0 ? " Promoted {$promoted} waiting student(s)." : "");

            if ($isAjax) {
                $response->json([
                    'success'  => true,
                    'message'  => $msg,
                    'promoted' => $promoted,
                    'metrics'  => $queueService->getQueueMetrics(),
                ], 200);
                return;
            }

            $_SESSION['success_msg'] = $msg;
            $response->redirect(BASE_PATH . '/admin/finance/payment_monitoring.php');
            return;

        } catch (Exception $e) {
            if ($isAjax) {
                $response->json(['success' => false, 'message' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['error_msg'] = $e->getMessage();
            $response->redirect(BASE_PATH . '/admin/finance/payment_monitoring.php');
            return;
        }
    }

    /**
     * Executes administrative queue maintenance actions (Recycle stale sessions, force release).
     */
    public function queueActionProcess(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        $isAjax = $request->isAjax();

        if (!hasPermission(['fees.manage', 'settings.manage'])) {
            if ($isAjax) {
                $response->json(['success' => false, 'message' => 'Access Denied: Insufficient administrative permissions.'], 403);
                return;
            }
            $_SESSION['error_msg'] = 'Access Denied: Insufficient administrative permissions.';
            $response->redirect(BASE_PATH . '/admin/finance/payment_monitoring.php');
            return;
        }

        $action = trim((string)($request->input('action') ?? ''));
        $queueService = new \App\Services\PaymentQueueService(null, $pdo);

        try {
            if ($action === 'recycle_stale') {
                $repo = new \App\Repositories\PaymentSessionRepository($pdo);
                $expiredCount = $repo->expireStaleActiveSessions();
                $abandonedCount = $repo->abandonStaleWaitingSessions();
                $promotedCount = $queueService->promoteEligibleWaitingSessions();

                $msg = "Queue sweep completed: {$expiredCount} expired active slot(s) recycled, {$abandonedCount} dead waiting session(s) reclaimed, {$promotedCount} student(s) promoted.";

                if ($isAjax) {
                    $response->json(['success' => true, 'message' => $msg], 200);
                    return;
                }
                $_SESSION['success_msg'] = $msg;
                $response->redirect(BASE_PATH . '/admin/finance/payment_monitoring.php');
                return;

            } elseif ($action === 'force_release') {
                $token = trim((string)($request->input('session_token') ?? ''));
                if ($token === '') {
                    throw new Exception('Session token is required.');
                }

                $released = $queueService->releaseSlot($token);
                if (!$released) {
                    throw new Exception('Session not found or already terminated.');
                }

                $msg = "Session successfully released and slot made available.";
                if ($isAjax) {
                    $response->json(['success' => true, 'message' => $msg], 200);
                    return;
                }
                $_SESSION['success_msg'] = $msg;
                $response->redirect(BASE_PATH . '/admin/finance/payment_monitoring.php');
                return;

            } else {
                throw new Exception('Unknown queue action.');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $response->json(['success' => false, 'message' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['error_msg'] = $e->getMessage();
            $response->redirect(BASE_PATH . '/admin/finance/payment_monitoring.php');
            return;
        }
    }
}



