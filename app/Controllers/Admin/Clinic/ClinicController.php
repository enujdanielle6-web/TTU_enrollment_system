<?php
namespace App\Controllers\Admin\Clinic;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use PDOException;

class ClinicController extends BaseController
{
    public function dashboard(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
        requirePermission('medical.review');

        $pageTitle = 'Clinic Dashboard - Triple T University';

        // 1. Core Health Record Statistics
        try {
            $statsStmt = $pdo->query('
                SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END), 0) as pending,
                    COALESCE(SUM(CASE WHEN status = "verified" THEN 1 ELSE 0 END), 0) as verified,
                    COALESCE(SUM(CASE WHEN status = "correction_required" THEN 1 ELSE 0 END), 0) as correction_required,
                    COALESCE(SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END), 0) as rejected
                FROM health_records
            ');
            $rawStats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('Clinic dashboard stats query failed: ' . $e->getMessage());
            $rawStats = [];
        }
        
        $total = (int)($rawStats['total'] ?? 0);
        $verified = (int)($rawStats['verified'] ?? 0);
        $pending = (int)($rawStats['pending'] ?? 0);
        $correction = (int)($rawStats['correction_required'] ?? 0);
        $rejected = (int)($rawStats['rejected'] ?? 0);
        $clearanceRate = $total > 0 ? (int)round(($verified / $total) * 100) : 100;

        $stats = [
            'total' => $total,
            'pending' => $pending,
            'pending_medical' => $pending,
            'verified' => $verified,
            'total_verified' => $verified,
            'correction_required' => $correction,
            'rejected' => $rejected,
            'clearance_rate' => $clearanceRate
        ];

        // 2. Declared Medical Condition Triage
        try {
            $condStmt = $pdo->query('
                SELECT 
                    COALESCE(SUM(CASE WHEN has_allergies = 1 THEN 1 ELSE 0 END), 0) as allergies,
                    COALESCE(SUM(CASE WHEN has_asthma = 1 THEN 1 ELSE 0 END), 0) as asthma,
                    COALESCE(SUM(CASE WHEN has_hypertension = 1 THEN 1 ELSE 0 END), 0) as hypertension,
                    COALESCE(SUM(CASE WHEN has_diabetes = 1 THEN 1 ELSE 0 END), 0) as diabetes,
                    COALESCE(SUM(CASE WHEN has_heart_disease = 1 THEN 1 ELSE 0 END), 0) as heart_disease,
                    COALESCE(SUM(CASE WHEN has_physical_disability = 1 THEN 1 ELSE 0 END), 0) as physical_disability,
                    COALESCE(SUM(CASE WHEN has_maintenance_medication = 1 THEN 1 ELSE 0 END), 0) as maintenance_medication
                FROM health_records
            ');
            $conditionStats = $condStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('Clinic condition stats query failed: ' . $e->getMessage());
            $conditionStats = [];
        }

        // 3. Recent Health Submissions (Show only 5 on dashboard, or full if ?all=1)
        $showAll = (isset($_GET['all']) && $_GET['all'] === '1');
        $limit = $showAll ? 100 : 5;
        try {
            $recentStmt = $pdo->prepare('
                SELECT h.id, h.status, h.created_at, h.updated_at, h.blood_type,
                       h.has_allergies, h.has_asthma, h.has_diabetes, h.has_hypertension,
                       h.has_heart_disease, h.has_physical_disability, h.has_existing_condition,
                       h.emergency_name, h.emergency_relationship, h.emergency_contact,
                       a.reference_number, a.academic_level, a.strand,
                       u.first_name, u.last_name, u.email
                FROM health_records h
                INNER JOIN applications a ON h.application_id = a.id
                INNER JOIN users u ON h.user_id = u.id
                ORDER BY h.created_at DESC LIMIT :limit
            ');
            $recentStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $recentStmt->execute();
            $recent_records = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Clinic recent submissions query failed: ' . $e->getMessage());
            $recent_records = [];
        }

        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        return $this->render('admin/clinic/clinic_dashboard', get_defined_vars());
    }
    public function index(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
requirePermission('medical.review');

$pageTitle = 'Medical Clearance - Administrator';

$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, (int)($request->input('page', 1)));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClauses = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereClauses[] = 'h.status = :status';
    $params['status'] = $statusFilter;
}

$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

$totalCount = 0;
$totalPages = 1;

try {
    $countQuery = "
        SELECT COUNT(*) 
        FROM health_records h
        INNER JOIN users u ON h.user_id = u.id
        INNER JOIN applications a ON h.application_id = a.id
        $whereSql
    ";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalCount / $limit));

    $query = "
        SELECT h.id, h.status, h.updated_at,
               u.first_name, u.last_name,
               a.reference_number, a.academic_level, a.strand
        FROM health_records h
        INNER JOIN users u ON h.user_id = u.id
        INNER JOIN applications a ON h.application_id = a.id
        $whereSql
        ORDER BY h.updated_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($query);
    foreach ($params as $k => $v) {
        $stmt->bindValue(':' . $k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Medical clearance fetch failed: ' . $e->getMessage());
    $records = [];
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

return $this->render('admin/clinic/medical_clearance', get_defined_vars());
}
    public function detail(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
requirePermission('medical.review');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $response->redirect("/sia/admin/clinic/medical_clearance.php");
    return;
}

try {
    $stmt = $pdo->prepare('
        SELECT h.*, 
               u.first_name, u.last_name, u.email, 
               a.reference_number, a.academic_level, a.strand, a.school_year, a.contact_number
        FROM health_records h
        INNER JOIN users u ON h.user_id = u.id
        INNER JOIN applications a ON h.application_id = a.id
        WHERE h.id = :id
    ');
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch();

    if (!$record) {
        $_SESSION['error_msg'] = 'Health record not found.';
        $response->redirect("/sia/admin/clinic/medical_clearance.php");
        return;
    }
} catch (PDOException $e) {
    error_log('Medical detail fetch failed: ' . $e->getMessage());
    $_SESSION['error_msg'] = 'A database error occurred.';
    $response->redirect("/sia/admin/clinic/medical_clearance.php");
    return;
}

$pageTitle = 'Medical Clearance Detail - Administrator';
$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);


        return $this->render('admin/clinic/medical_detail', get_defined_vars());
    }
    public function process(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        requirePermission('medical.review');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response->redirect("/sia/admin/clinic/medical_clearance.php");
            return;
        }



$recordId = (int)($_POST['record_id'] ?? 0);
$userId = (int)($_POST['user_id'] ?? 0);
$status = $_POST['status'] ?? '';
$adminRemarks = trim($_POST['admin_remarks'] ?? '');

$validStatuses = ['pending', 'verified', 'correction_required', 'rejected'];

if ($recordId <= 0 || !in_array($status, $validStatuses, true)) {
    $_SESSION['error_msg'] = 'Invalid request parameters.';
    $response->redirect("/sia/admin/clinic/medical_clearance.php");
    return;
}

try {
    $pdo->beginTransaction();

    // Fetch old status to check if it changed
    $stmt = $pdo->prepare('SELECT status FROM health_records WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $recordId]);
    $oldStatus = $stmt->fetchColumn();

    if ($oldStatus === false) {
        throw new \Exception('Record not found.');
    }
    
    if ($oldStatus === 'verified') {
        throw new \Exception('Cannot modify a verified medical clearance record.');
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
    $response->redirect("/sia/admin/clinic/medical_detail.php?id={$recordId}");
    return;

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Medical Process Failed: ' . $e->getMessage());
    $_SESSION['error_msg'] = 'A database error occurred while updating the record.';
    $response->redirect("/sia/admin/clinic/medical_detail.php?id={$recordId}");
    return;
}

    }
}



