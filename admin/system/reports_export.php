<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission(['students.view', 'applications.view_queue', 'payments.record', 'scholarships.manage']);

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
exit;

