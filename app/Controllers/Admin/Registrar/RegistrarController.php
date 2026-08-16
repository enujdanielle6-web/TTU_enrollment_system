<?php
namespace App\Controllers\Admin\Registrar;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use PDOException;

class RegistrarController extends BaseController
{
    public function dashboard(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
requirePermission(['students.view', 'programs.manage']);

$pageTitle = 'Registrar Dashboard - Triple T University';

        return $this->render('admin/registrar/dashboard', get_defined_vars());
    }
    public function students(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
requirePermission('students.view');

$pageTitle = 'Student Records - Administrator';

try {
    $stmt = $pdo->query('
        SELECT 
            a.id, 
            a.reference_number, 
            a.lrn,
            a.status, 
            a.academic_level,
            a.strand, 
            a.grade_level,
            a.gender,
            a.contact_number,
            u.first_name, 
            u.last_name,
            u.student_number
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        WHERE a.status IN ("approved", "enrolled")
        ORDER BY a.grade_level ASC, a.strand ASC, u.last_name ASC
    ');
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Admin student list failed: ' . $e->getMessage());
    $students = [];
}

// Fetch programs for filter
$programs = [];
try {
    $progStmt = $pdo->query('
        SELECT code, name FROM college_programs WHERE is_active = 1 
        UNION ALL 
        SELECT code, name FROM shs_strands WHERE is_active = 1 
        ORDER BY code ASC
    ');
    $programs = $progStmt->fetchAll();
} catch (PDOException $e) {
    error_log('Failed to fetch programs: ' . $e->getMessage());
}


        return $this->render('admin/registrar/students', get_defined_vars());
    }
    public function collegeQueue(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
requirePermission('enrollment.finalize');

$pageTitle = 'College Enrollment Queue - Registrar';

$search = trim($_GET['search'] ?? '');
$sortOrder = trim($_GET['sort'] ?? 'newest');

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClauses = ["a.status = 'approved'", "sa.payment_status IN ('partial', 'paid')", "a.academic_level = 'College'"];
$params = [];

if ($search !== '') {
    $whereClauses[] = '(a.reference_number LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);

$orderBy = 'a.created_at DESC';
if ($sortOrder === 'oldest') {
    $orderBy = 'a.created_at ASC';
}

$applications = [];
$totalApps = 0;

try {
    // Count total matching
    $countStmt = $pdo->prepare("
        SELECT COUNT(a.id) 
        FROM applications a 
        INNER JOIN users u ON u.id = a.user_id
        INNER JOIN student_assessments sa ON sa.application_id = a.id
        $whereSQL
    ");
    $countStmt->execute($params);
    $totalApps = (int) $countStmt->fetchColumn();

    // Fetch paginated
    $stmt = $pdo->prepare("
        SELECT 
            a.id, 
            a.reference_number, 
            a.status, 
            a.academic_level,
            a.strand, 
            a.grade_level,
            a.created_at, 
            u.first_name, 
            u.last_name,
            sa.payment_status,
            sa.total_paid
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        INNER JOIN student_assessments sa ON sa.application_id = a.id
        $whereSQL
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $applications = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Enrollment queue list query failed: ' . $e->getMessage());
}

$totalPages = ceil($totalApps / $limit);

$successMsg = $_SESSION['admin_success'] ?? '';
$errorMsg = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


        return $this->render('admin/registrar/college_queue', get_defined_vars());
    }
    public function shsQueue(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
requirePermission('enrollment.finalize');

$pageTitle = 'SHS Enrollment Queue - Registrar';

$search = trim($_GET['search'] ?? '');
$sortOrder = trim($_GET['sort'] ?? 'newest');

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClauses = ["a.status = 'approved'", "sa.payment_status IN ('partial', 'paid')", "a.academic_level = 'Senior High School'"];
$params = [];

if ($search !== '') {
    $whereClauses[] = '(a.reference_number LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);

$orderBy = 'a.created_at DESC';
if ($sortOrder === 'oldest') {
    $orderBy = 'a.created_at ASC';
}

$applications = [];
$totalApps = 0;

try {
    // Count total matching
    $countStmt = $pdo->prepare("
        SELECT COUNT(a.id) 
        FROM applications a 
        INNER JOIN users u ON u.id = a.user_id
        INNER JOIN student_assessments sa ON sa.application_id = a.id
        $whereSQL
    ");
    $countStmt->execute($params);
    $totalApps = (int) $countStmt->fetchColumn();

    // Fetch paginated
    $stmt = $pdo->prepare("
        SELECT 
            a.id, 
            a.reference_number, 
            a.status, 
            a.academic_level,
            a.strand, 
            a.grade_level,
            a.created_at, 
            u.first_name, 
            u.last_name,
            sa.payment_status,
            sa.total_paid
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        INNER JOIN student_assessments sa ON sa.application_id = a.id
        $whereSQL
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $applications = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Enrollment queue list query failed: ' . $e->getMessage());
}

$totalPages = ceil($totalApps / $limit);

$successMsg = $_SESSION['admin_success'] ?? '';
$errorMsg = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


        return $this->render('admin/registrar/shs_queue', get_defined_vars());
    }
    public function exportStudents(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
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
    return;

} catch (PDOException $e) {
    error_log('CSV Export failed: ' . $e->getMessage());
    showErrorPage('Export Failed', 'A database error occurred while exporting the student masterlist CSV.');
}

    }
}



