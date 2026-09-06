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

        // 1. Fetch Global KPI Stat Counts (Unfiltered totals across all records)
        $totalCount = 0;
        $collegeCount = 0;
        $shsCount = 0;
        $enrolledCount = 0;
        $approvedCount = 0;

        try {
            $statsStmt = $pdo->query('
                SELECT 
                    COUNT(*) as total_count,
                    COALESCE(SUM(CASE WHEN a.academic_level = "College" THEN 1 ELSE 0 END), 0) as college_count,
                    COALESCE(SUM(CASE WHEN a.academic_level = "Senior High School" THEN 1 ELSE 0 END), 0) as shs_count,
                    COALESCE(SUM(CASE WHEN a.status = "enrolled" THEN 1 ELSE 0 END), 0) as enrolled_count,
                    COALESCE(SUM(CASE WHEN a.status = "approved" THEN 1 ELSE 0 END), 0) as approved_count
                FROM applications a
                INNER JOIN users u ON u.id = a.user_id
                WHERE (u.role IN ("applicant", "student") OR a.id IS NOT NULL)
            ');
            if ($row = $statsStmt->fetch(PDO::FETCH_ASSOC)) {
                $totalCount = (int)($row['total_count'] ?? 0);
                $collegeCount = (int)($row['college_count'] ?? 0);
                $shsCount = (int)($row['shs_count'] ?? 0);
                $enrolledCount = (int)($row['enrolled_count'] ?? 0);
                $approvedCount = (int)($row['approved_count'] ?? 0);
            }
        } catch (PDOException $e) {
            error_log('Admin student KPI counts failed: ' . $e->getMessage());
        }

        // 2. Parse Server-Side Filtering & Pagination Parameters
        $search = trim($_GET['search'] ?? '');
        $level = trim($_GET['level'] ?? 'all');
        $grade = trim($_GET['grade'] ?? 'all');
        $strand = trim($_GET['strand'] ?? 'all');
        $status = trim($_GET['status'] ?? 'all');

        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        // 3. Build Dynamic WHERE Clauses
        $whereClauses = ['(u.role IN ("applicant", "student") OR a.id IS NOT NULL)'];
        $params = [];

        if ($search !== '') {
            $whereClauses[] = '(a.reference_number LIKE :s1 OR a.lrn LIKE :s2 OR u.student_number LIKE :s3 OR u.first_name LIKE :s4 OR u.last_name LIKE :s5 OR CONCAT(u.first_name, " ", u.last_name) LIKE :s6)';
            $params[':s1'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
            $params[':s4'] = '%' . $search . '%';
            $params[':s5'] = '%' . $search . '%';
            $params[':s6'] = '%' . $search . '%';
        }

        if ($level !== 'all' && $level !== '') {
            $whereClauses[] = 'a.academic_level = :level';
            $params[':level'] = $level;
        }

        if ($grade !== 'all' && $grade !== '') {
            $whereClauses[] = 'a.grade_level = :grade';
            $params[':grade'] = $grade;
        }

        if ($strand !== 'all' && $strand !== '') {
            $whereClauses[] = 'a.strand = :strand';
            $params[':strand'] = $strand;
        }

        if ($status !== 'all' && $status !== '') {
            $whereClauses[] = 'a.status = :status';
            $params[':status'] = $status;
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);

        // 4. Count Filtered Matching Records
        $totalFiltered = 0;
        try {
            $countStmt = $pdo->prepare("
                SELECT COUNT(a.id)
                FROM applications a
                INNER JOIN users u ON u.id = a.user_id
                $whereSQL
            ");
            $countStmt->execute($params);
            $totalFiltered = (int)$countStmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Admin student count query failed: ' . $e->getMessage());
        }

        $totalPages = max(1, (int)ceil($totalFiltered / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        // 5. Fetch Paginated Records
        $students = [];
        try {
            $stmt = $pdo->prepare("
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
                $whereSQL
                ORDER BY a.grade_level ASC, a.strand ASC, u.last_name ASC
                LIMIT :limit OFFSET :offset
            ");

            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Admin student list failed: ' . $e->getMessage());
            $students = [];
        }

        // Fetch programs for filter dropdown
        $programs = [];
        try {
            $progStmt = $pdo->query('
                SELECT code, name FROM college_programs WHERE is_active = 1 
                UNION ALL 
                SELECT code, name FROM shs_strands WHERE is_active = 1 
                ORDER BY code ASC
            ');
            $programs = $progStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Failed to fetch programs: ' . $e->getMessage());
        }

        $startRecord = $totalFiltered > 0 ? $offset + 1 : 0;
        $endRecord = min($offset + count($students), $totalFiltered);

        $filters = [
            'search' => $search,
            'level' => $level,
            'grade' => $grade,
            'strand' => $strand,
            'status' => $status,
            'per_page' => $perPage,
            'page' => $page
        ];

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

$whereClauses = ["(a.status = 'payment_verified' OR (a.status = 'approved' AND sa.payment_status IN ('partial', 'paid')))", "a.academic_level = 'College'"];
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

$whereClauses = ["(a.status = 'payment_verified' OR (a.status = 'approved' AND sa.payment_status IN ('partial', 'paid')))", "a.academic_level = 'Senior High School'"];
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
$levelFilter = trim($_GET['level'] ?? 'all');
$gradeFilter = trim($_GET['grade'] ?? 'all');
$strandFilter = trim($_GET['strand'] ?? 'all');
$statusFilter = trim($_GET['status'] ?? 'all');

$whereClauses = ['a.status IN ("approved", "enrolled")'];
$params = [];

if ($search !== '') {
    $whereClauses[] = '(a.reference_number LIKE :s1 OR a.lrn LIKE :s2 OR u.student_number LIKE :s3 OR u.first_name LIKE :s4 OR u.last_name LIKE :s5 OR CONCAT(u.first_name, " ", u.last_name) LIKE :s6)';
    $params[':s1'] = '%' . $search . '%';
    $params[':s2'] = '%' . $search . '%';
    $params[':s3'] = '%' . $search . '%';
    $params[':s4'] = '%' . $search . '%';
    $params[':s5'] = '%' . $search . '%';
    $params[':s6'] = '%' . $search . '%';
}

if ($levelFilter !== 'all' && $levelFilter !== '') {
    $whereClauses[] = 'a.academic_level = :level';
    $params[':level'] = $levelFilter;
}

if ($gradeFilter !== 'all' && $gradeFilter !== '') {
    $whereClauses[] = 'a.grade_level = :grade';
    $params[':grade'] = $gradeFilter;
}

if ($strandFilter !== 'all' && $strandFilter !== '') {
    $whereClauses[] = 'a.strand = :strand';
    $params[':strand'] = $strandFilter;
}

if ($statusFilter !== 'all' && $statusFilter !== '') {
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

    public function finalizeEnrollment(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        requirePermission('enrollment.finalize');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response->redirect("/sia/admin/registrar/college_enrollment_queue.php");
            return;
        }

        $appId = (int)($_POST['application_id'] ?? 0);
        if ($appId <= 0) {
            $_SESSION['admin_error'] = 'Invalid application ID.';
            $response->redirect("/sia/admin/registrar/college_enrollment_queue.php");
            return;
        }

        $result = \App\Services\EnrollmentService::finalizeEnrollment($appId, (int)($_SESSION['user_id'] ?? 0), $pdo);

        if ($result['success']) {
            $_SESSION['admin_success'] = $result['message'];
        } else {
            $_SESSION['admin_error'] = $result['error'];
        }

        $redirectUrl = ($result['academic_level'] ?? '') === 'Senior High School'
            ? '/sia/admin/registrar/shs_enrollment_queue.php'
            : '/sia/admin/registrar/college_enrollment_queue.php';
        $response->redirect($redirectUrl);
    }
}



