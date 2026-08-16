<?php
namespace App\Controllers\Admin\System;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use PDOException;

class ReportController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
requirePermission(['students.view', 'applications.view_queue', 'payments.record', 'scholarships.manage']);

$pageTitle = 'Enrollment Reports - Administrator';

try {
    // 1. Overall Pipeline Totals
    $pipelineStmt = $pdo->query('
        SELECT 
            COUNT(*) as total_apps,
            SUM(CASE WHEN status = "pending" OR status = "under_review" THEN 1 ELSE 0 END) as total_pending,
            SUM(CASE WHEN status = "correction_required" THEN 1 ELSE 0 END) as total_corrections,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as total_approved,
            SUM(CASE WHEN status = "enrolled" THEN 1 ELSE 0 END) as total_enrolled,
            SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as total_rejected
        FROM applications
    ');
    $pipeline = $pipelineStmt->fetch();
    
    // 2. Program Distribution (All applications)
    $strandStmt = $pdo->query('
        SELECT strand, COUNT(*) as count 
        FROM applications 
        WHERE strand IS NOT NULL AND strand != ""
        GROUP BY strand 
        ORDER BY count DESC
    ');
    $strandData = $strandStmt->fetchAll();

    // 3. Level/Grade Distribution (All applications)
    $gradeStmt = $pdo->query('
        SELECT grade_level, COUNT(*) as count 
        FROM applications 
        WHERE grade_level IS NOT NULL AND grade_level != ""
        GROUP BY grade_level 
        ORDER BY grade_level ASC
    ');
    $gradeData = $gradeStmt->fetchAll();
    
    // 4. Daily Enrollment Trend (Last 14 Days)
    $trendStmt = $pdo->query('
        SELECT DATE(created_at) as submit_date, COUNT(*) as count 
        FROM applications 
        WHERE created_at >= DATE(NOW()) - INTERVAL 14 DAY
        GROUP BY DATE(created_at)
        ORDER BY submit_date ASC
    ');
    $trendData = $trendStmt->fetchAll();

    // 5. Status Distribution
    $statusDistStmt = $pdo->query('
        SELECT status, COUNT(*) as count 
        FROM applications 
        GROUP BY status
    ');
    $statusDistData = $statusDistStmt->fetchAll();

    // 6. Financial Overview
    $finStmt = $pdo->query('
        SELECT 
            COALESCE(SUM(sa.total_amount), 0) as expected_revenue,
            COALESCE(SUM(sa.discount_amount), 0) as total_scholarships,
            COALESCE(SUM(sa.total_paid), 0) as collected_payments
        FROM student_assessments sa
    ');
    $finData = $finStmt->fetch();

    $pendingPaymentsStmt = $pdo->query('
        SELECT 
            COUNT(*) as pending_count,
            COALESCE(SUM(net_amount - total_paid), 0) as outstanding_balance
        FROM student_assessments 
        WHERE payment_status != "paid"
    ');
    $pendingFinData = $pendingPaymentsStmt->fetch();
    
    // 7. Scholarship Distribution
    $schDistStmt = $pdo->query('
        SELECT s.name, COUNT(*) as count, SUM(sa.discount_amount) as total_discount
        FROM student_assessments sa
        INNER JOIN scholarships s ON sa.scholarship_id = s.id
        GROUP BY s.id
        ORDER BY total_discount DESC
    ');
    $scholarshipData = $schDistStmt->fetchAll();
    
    // 8. Payment Methods
    $payMethodStmt = $pdo->query('
        SELECT payment_method, COUNT(*) as count, SUM(amount) as total_amount
        FROM payment_records
        GROUP BY payment_method
        ORDER BY total_amount DESC
    ');
    $paymentMethodData = $payMethodStmt->fetchAll();

    // 9. Medical Clearance Statistics
    $medicalStmt = $pdo->query('
        SELECT 
            COUNT(*) as total_records,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as total_pending,
            SUM(CASE WHEN status = "under_review" THEN 1 ELSE 0 END) as total_under_review,
            SUM(CASE WHEN status = "verified" THEN 1 ELSE 0 END) as total_verified,
            SUM(CASE WHEN status = "correction_required" THEN 1 ELSE 0 END) as total_corrections,
            SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as total_rejected
        FROM health_records
    ');
    $medicalData = $medicalStmt->fetch();

} catch (PDOException $e) {
    error_log('Admin reports fetch failed: ' . $e->getMessage());
    showErrorPage('Reports Generation Failed', 'A database error occurred while querying the analytics databases.');
}


        return $this->render('admin/system/reports', get_defined_vars());
    }
    public function export(Request $request, Response $response)
    {
        $pdo = Database::getConnection();

$type = $_GET['type'] ?? '';

if (!in_array($type, ['applications', 'payments', 'scholarships', 'balances'])) {
    die('Invalid export type.');
}

$filename = 'ttu_export_' . $type . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

try {
    if ($type === 'applications') {
        requirePermission(['applications.view_queue', 'applications.view_details']);
        fputcsv($output, ['Reference Number', 'Name', 'Email', 'Strand', 'Grade Level', 'Status', 'Date Submitted']);
        $stmt = $pdo->query('
            SELECT a.reference_number, u.first_name, u.last_name, u.email, a.strand, a.grade_level, a.status, a.created_at
            FROM applications a
            INNER JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC
        ');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['reference_number'],
                $row['last_name'] . ', ' . $row['first_name'],
                $row['email'],
                $row['strand'],
                $row['grade_level'],
                strtoupper($row['status']),
                date('Y-m-d H:i:s', strtotime($row['created_at']))
            ]);
        }
    } elseif ($type === 'payments') {
        requirePermission('payments.record');
        fputcsv($output, ['Receipt Number', 'Date', 'Student Name', 'Method', 'Reference Number', 'Amount', 'Cashier']);
        $stmt = $pdo->query('
            SELECT pr.receipt_number, pr.created_at, u.first_name, u.last_name, pr.payment_method, pr.reference_number, pr.amount, c.first_name as c_first, c.last_name as c_last
            FROM payment_records pr
            INNER JOIN users u ON pr.user_id = u.id
            LEFT JOIN users c ON pr.cashier_id = c.id
            ORDER BY pr.created_at DESC
        ');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['receipt_number'],
                date('Y-m-d H:i:s', strtotime($row['created_at'])),
                $row['last_name'] . ', ' . $row['first_name'],
                $row['payment_method'],
                $row['reference_number'],
                number_format((float)$row['amount'], 2, '.', ''),
                $row['c_last'] . ', ' . $row['c_first']
            ]);
        }
    } elseif ($type === 'scholarships') {
        requirePermission('scholarships.manage');
        fputcsv($output, ['Student Name', 'Application Ref', 'Scholarship Name', 'Original Total', 'Discount Amount', 'Net Amount']);
        $stmt = $pdo->query('
            SELECT u.first_name, u.last_name, a.reference_number, s.name as scholarship_name, sa.total_amount, sa.discount_amount, sa.net_amount
            FROM student_assessments sa
            INNER JOIN users u ON sa.user_id = u.id
            INNER JOIN applications a ON sa.application_id = a.id
            INNER JOIN scholarships s ON sa.scholarship_id = s.id
            WHERE sa.scholarship_id IS NOT NULL
            ORDER BY sa.created_at DESC
        ');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['last_name'] . ', ' . $row['first_name'],
                $row['reference_number'],
                $row['scholarship_name'],
                number_format((float)$row['total_amount'], 2, '.', ''),
                number_format((float)$row['discount_amount'], 2, '.', ''),
                number_format((float)$row['net_amount'], 2, '.', '')
            ]);
        }
    } elseif ($type === 'balances') {
        requirePermission(['assessments.generate', 'payments.record']);
        fputcsv($output, ['Student Name', 'Application Ref', 'Net Amount', 'Total Paid', 'Remaining Balance', 'Status']);
        $stmt = $pdo->query('
            SELECT u.first_name, u.last_name, a.reference_number, sa.net_amount, sa.total_paid, sa.payment_status
            FROM student_assessments sa
            INNER JOIN users u ON sa.user_id = u.id
            INNER JOIN applications a ON sa.application_id = a.id
            WHERE sa.payment_status != "paid"
            ORDER BY sa.payment_status DESC, sa.created_at DESC
        ');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $balance = (float)$row['net_amount'] - (float)$row['total_paid'];
            fputcsv($output, [
                $row['last_name'] . ', ' . $row['first_name'],
                $row['reference_number'],
                number_format((float)$row['net_amount'], 2, '.', ''),
                number_format((float)$row['total_paid'], 2, '.', ''),
                number_format($balance > 0 ? $balance : 0, 2, '.', ''),
                strtoupper($row['payment_status'])
            ]);
        }
    }
} catch (PDOException $e) {
    error_log('CSV Export failed: ' . $e->getMessage());
    fputcsv($output, ['Error generating report']);
}

fclose($output);
return;

    }
}



