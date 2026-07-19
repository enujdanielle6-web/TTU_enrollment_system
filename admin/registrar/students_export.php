<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('students.view');

$search = trim($_GET['search'] ?? '');
$gradeFilter = trim($_GET['grade'] ?? 'all');
$strandFilter = trim($_GET['strand'] ?? 'all');
$statusFilter = trim($_GET['status'] ?? 'all');

$whereClauses = ['a.status IN ("approved", "enrolled")'];
$params = [];

if ($search !== '') {
    $whereClauses[] = '(a.reference_number LIKE :search OR a.lrn LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($gradeFilter !== 'all') {
    $whereClauses[] = 'a.grade_level = :grade';
    $params[':grade'] = $gradeFilter;
}

if ($strandFilter !== 'all') {
    $whereClauses[] = 'a.strand = :strand';
    $params[':strand'] = $strandFilter;
}

if ($statusFilter !== 'all') {
    $whereClauses[] = 'a.status = :status';
    $params[':status'] = $statusFilter;
}

$whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.reference_number, 
            a.lrn,
            a.status, 
            a.grade_level,
            a.strand,
            a.gender,
            a.contact_number,
            u.first_name, 
            u.last_name,
            u.email,
            a.created_at
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        $whereSQL
        ORDER BY a.grade_level ASC, a.strand ASC, u.last_name ASC
    ");
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ttu_student_records_' . date('Ymd_His') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        showErrorPage('Export Error', 'Unable to open system output stream for writing CSV.');
    }

    // UTF-8 BOM for Excel compliance
    fwrite($output, "\xEF\xBB\xBF");

    // Print headers
    fputcsv($output, [
        'Reference No.',
        'LRN / Student ID',
        'Last Name',
        'First Name',
        'Email',
        'Contact Number',
        'Gender',
        'Grade Level',
        'Strand/Program',
        'Status',
        'Submission Date'
    ]);

    // Print rows
    foreach ($students as $student) {
        fputcsv($output, [
            $student['reference_number'],
            $student['lrn'] ?: 'N/A',
            $student['last_name'],
            $student['first_name'],
            $student['email'],
            $student['contact_number'] ?: 'N/A',
            ucfirst($student['gender'] ?? 'N/A'),
            $student['grade_level'] ?: 'N/A',
            strtoupper($student['strand'] ?? 'N/A'),
            formatApplicationStatus($student['status']),
            date('Y-m-d H:i:s', strtotime($student['created_at']))
        ]);
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    error_log('CSV Export failed: ' . $e->getMessage());
    showErrorPage('Export Failed', 'A database error occurred while exporting the student masterlist CSV.');
}

