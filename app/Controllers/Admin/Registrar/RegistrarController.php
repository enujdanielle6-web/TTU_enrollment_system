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

        // 1. Core Statistics
        $stats = [
            'enrolled' => 0,
            'college_enrolled' => 0,
            'shs_enrolled' => 0,
            'ready_to_enroll' => 0,
            'college_queue' => 0,
            'shs_queue' => 0,
            'active_sections' => 0,
            'college_sections' => 0,
            'shs_sections' => 0,
            'total_students' => 0,
            'subjects_count' => 0,
            'college_programs_count' => 0,
            'shs_strands_count' => 0
        ];

        try {
            // Enrolled counts
            $enrolledStmt = $pdo->query('
                SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(CASE WHEN academic_level = "College" THEN 1 ELSE 0 END), 0) as college,
                    COALESCE(SUM(CASE WHEN academic_level = "Senior High School" THEN 1 ELSE 0 END), 0) as shs
                FROM applications 
                WHERE status = "enrolled"
            ');
            if ($row = $enrolledStmt->fetch(PDO::FETCH_ASSOC)) {
                $stats['enrolled'] = (int)($row['total'] ?? 0);
                $stats['college_enrolled'] = (int)($row['college'] ?? 0);
                $stats['shs_enrolled'] = (int)($row['shs'] ?? 0);
            }

            // Ready to Enroll (Queue counts)
            $queueStmt = $pdo->query('
                SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(CASE WHEN a.academic_level = "College" THEN 1 ELSE 0 END), 0) as college_queue,
                    COALESCE(SUM(CASE WHEN a.academic_level = "Senior High School" THEN 1 ELSE 0 END), 0) as shs_queue
                FROM applications a 
                INNER JOIN student_assessments sa ON a.id = sa.application_id 
                WHERE (a.status = "payment_verified" OR (a.status = "approved" AND sa.payment_status IN ("partial", "paid")))
            ');
            if ($row = $queueStmt->fetch(PDO::FETCH_ASSOC)) {
                $stats['ready_to_enroll'] = (int)($row['total'] ?? 0);
                $stats['college_queue'] = (int)($row['college_queue'] ?? 0);
                $stats['shs_queue'] = (int)($row['shs_queue'] ?? 0);
            }

            // Active Sections
            $colSec = (int)$pdo->query('SELECT COUNT(*) FROM college_sections WHERE status = 1')->fetchColumn();
            $shsSec = (int)$pdo->query('SELECT COUNT(*) FROM shs_sections WHERE status = 1')->fetchColumn();
            $stats['college_sections'] = $colSec;
            $stats['shs_sections'] = $shsSec;
            $stats['active_sections'] = $colSec + $shsSec;

            // Total Enrolled Students with assigned Official Student Number
            $stats['total_students'] = (int)$pdo->query('
                SELECT COUNT(*) FROM applications a 
                INNER JOIN users u ON u.id = a.user_id 
                WHERE a.status = "enrolled" AND u.student_number IS NOT NULL AND u.student_number != ""
            ')->fetchColumn();

            // Academic Structure Counts
            $stats['subjects_count'] = (int)$pdo->query('SELECT COUNT(*) FROM subjects')->fetchColumn();
            $stats['college_programs_count'] = (int)$pdo->query('SELECT COUNT(*) FROM college_programs WHERE is_active = 1')->fetchColumn();
            $stats['shs_strands_count'] = (int)$pdo->query('SELECT COUNT(*) FROM shs_strands WHERE is_active = 1')->fetchColumn();

        } catch (PDOException $e) {
            error_log('Registrar dashboard stats failed: ' . $e->getMessage());
        }

        // 2. Fetch Recent Enrolled Students (Roster)
        $recentEnrolled = [];
        try {
            $stmtRecent = $pdo->prepare('
                SELECT 
                    a.id, 
                    a.reference_number, 
                    a.lrn, 
                    a.status,
                    a.academic_level, 
                    a.grade_level, 
                    a.strand, 
                    a.created_at,
                    u.first_name, 
                    u.last_name, 
                    u.student_number,
                    u.email,
                    a.contact_number
                FROM applications a
                INNER JOIN users u ON u.id = a.user_id
                WHERE a.status = "enrolled"
                ORDER BY a.id DESC
                LIMIT 5
            ');
            $stmtRecent->execute();
            $recentEnrolled = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Registrar recent enrolled query failed: ' . $e->getMessage());
        }

        // 3. Program / Strand Breakdown for Enrolled Students
        $programDistribution = [];
        try {
            $stmtDist = $pdo->query('
                SELECT 
                    a.academic_level,
                    a.strand as program_code,
                    COUNT(*) as student_count
                FROM applications a
                WHERE a.status = "enrolled"
                GROUP BY a.academic_level, a.strand
                ORDER BY student_count DESC
            ');
            $programDistribution = $stmtDist->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Registrar distribution query failed: ' . $e->getMessage());
        }

        // 4. System Settings
        $systemSettings = [];
        try {
            $settingsStmt = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
            $systemSettings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            error_log('Registrar system settings query failed: ' . $e->getMessage());
        }

        return $this->render('admin/registrar/dashboard', get_defined_vars());
    }
    public function students(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
        requirePermission('students.view');

        $pageTitle = 'Student Records - Administrator';

        // 1. Fetch Global KPI Stat Counts (Unfiltered totals across enrolled records)
        $totalCount = 0;
        $collegeCount = 0;
        $shsCount = 0;
        $officialIdCount = 0;
        $enrolledCount = 0;

        try {
            $statsStmt = $pdo->query('
                SELECT 
                    COUNT(*) as total_count,
                    COALESCE(SUM(CASE WHEN a.academic_level = "College" THEN 1 ELSE 0 END), 0) as college_count,
                    COALESCE(SUM(CASE WHEN a.academic_level = "Senior High School" THEN 1 ELSE 0 END), 0) as shs_count,
                    COALESCE(SUM(CASE WHEN u.student_number IS NOT NULL AND u.student_number != "" THEN 1 ELSE 0 END), 0) as official_id_count
                FROM applications a
                INNER JOIN users u ON u.id = a.user_id
                WHERE a.status = "enrolled"
            ');
            if ($row = $statsStmt->fetch(PDO::FETCH_ASSOC)) {
                $totalCount = (int)($row['total_count'] ?? 0);
                $collegeCount = (int)($row['college_count'] ?? 0);
                $shsCount = (int)($row['shs_count'] ?? 0);
                $officialIdCount = (int)($row['official_id_count'] ?? 0);
                $enrolledCount = $totalCount;
            }
        } catch (PDOException $e) {
            error_log('Admin student KPI counts failed: ' . $e->getMessage());
        }

        // 2. Parse Server-Side Filtering & Pagination Parameters
        $search = trim($_GET['search'] ?? '');
        $level = trim($_GET['level'] ?? 'all');
        $grade = trim($_GET['grade'] ?? 'all');
        $strand = trim($_GET['strand'] ?? 'all');

        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        // 3. Build Dynamic WHERE Clauses - Strictly Enrolled Students Only
        $whereClauses = ['a.status = "enrolled"'];
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
            a.student_type,
            a.created_at, 
            u.first_name, 
            u.last_name,
            u.email,
            sa.payment_status,
            sa.total_paid,
            sa.total_assessment,
            cs.section_code,
            (SELECT h.status FROM health_records h WHERE h.application_id = a.id LIMIT 1) as medical_status
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        INNER JOIN student_assessments sa ON sa.application_id = a.id
        LEFT JOIN college_sections cs ON cs.id = a.section_id
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
            a.student_type,
            a.created_at, 
            u.first_name, 
            u.last_name,
            u.email,
            sa.payment_status,
            sa.total_paid,
            sa.total_assessment,
            ss.section_code,
            (SELECT h.status FROM health_records h WHERE h.application_id = a.id LIMIT 1) as medical_status
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        INNER JOIN student_assessments sa ON sa.application_id = a.id
        LEFT JOIN shs_sections ss ON ss.id = a.section_id
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

$whereClauses = ['a.status = "enrolled"'];
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



