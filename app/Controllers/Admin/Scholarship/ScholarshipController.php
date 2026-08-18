<?php
namespace App\Controllers\Admin\Scholarship;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use PDOException;
use Exception;

class ScholarshipController extends BaseController
{
    public function dashboard(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        requirePermission('scholarships.manage');

        $pageTitle = 'Scholarship Dashboard - Administrator';
        return $this->render('admin/scholarship/scholarship_dashboard', get_defined_vars());
    }

    public function index(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        requirePermission('scholarships.manage');

        // Fetch scholarships
        $stmt = $pdo->query('SELECT * FROM scholarships ORDER BY name ASC');
        $scholarships = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch programs for dropdown
        $progStmt = $pdo->query('SELECT id, code, name FROM college_programs ORDER BY code ASC');
        $programs = $progStmt->fetchAll(PDO::FETCH_ASSOC);

        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        $pageTitle = 'Scholarships - Administrator';
        return $this->render('admin/scholarship/scholarships', get_defined_vars());
    }

    public function scholars(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        requirePermission('scholarships.manage');

        // Fetch recipients
        $stmt = $pdo->query('
            SELECT sr.*, 
                   u.first_name, u.last_name, u.student_number,
                   s.name as scholarship_name, s.code as scholarship_code, s.category,
                   sr.academic_year_id as ay_name
            FROM scholarship_recipients sr
            INNER JOIN users u ON sr.user_id = u.id
            INNER JOIN scholarships s ON sr.scholarship_id = s.id
            ORDER BY sr.created_at DESC
        ');
        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $successMsg = $_SESSION['success_msg'] ?? null;
        $errorMsg = $_SESSION['error_msg'] ?? null;
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        $pageTitle = 'Active Scholars - Administrator';
        return $this->render('admin/scholarship/scholars', get_defined_vars());
    }

    public function review(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        requirePermission('scholarship_applications.review');

        // Fetch applications
        $stmt = $pdo->query('
            SELECT sa.*, 
                   u.first_name, u.last_name, u.email,
                   s.name as scholarship_name, s.category,
                   sa.academic_year_id as ay_name
            FROM scholarship_applications sa
            INNER JOIN users u ON sa.user_id = u.id
            INNER JOIN scholarships s ON sa.scholarship_id = s.id
            ORDER BY sa.created_at DESC
        ');
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'Scholarship Applications - Administrator';
        return $this->render('admin/scholarship/scholarship_review', get_defined_vars());
    }

    public function detail(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        requirePermission('scholarship_applications.review');

        $appId = (int) ($_GET['id'] ?? 0);

        if ($appId <= 0) {
            $response->redirect("/sia/admin/scholarship/scholarship_review.php");
            return;
        }

        try {
            $stmt = $pdo->prepare('
                SELECT sa.*, 
                       u.first_name, u.last_name, u.email,
                       s.name as scholarship_name, s.category, s.description,
                       sa.academic_year_id as ay_name
                FROM scholarship_applications sa
                INNER JOIN users u ON sa.user_id = u.id
                INNER JOIN scholarships s ON sa.scholarship_id = s.id
                WHERE sa.id = :id LIMIT 1
            ');
            $stmt->execute(['id' => $appId]);
            $app = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$app) {
                $response->redirect("/sia/admin/scholarship/scholarship_review.php");
                return;
            }

        } catch (PDOException $e) {
            error_log('Admin scholarship detail fetch failed: ' . $e->getMessage());
            showErrorPage('Database Error', 'A database error occurred while querying details for this application.');
        }

        $successMsg = $_SESSION['success_msg'] ?? '';
        $errorMsg = $_SESSION['error_msg'] ?? '';
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);

        $badgeClass = match($app['status']) {
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'under_review' => 'bg-info',
            default => 'bg-warning text-dark'
        };
        $statusLabel = match($app['status']) {
            'under_review' => 'Under Review',
            default => ucfirst($app['status'])
        };

        $pageTitle = 'Review Scholarship Application - Admin';
        return $this->render('admin/scholarship/scholarship_detail', get_defined_vars());
    }

    public function process(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response->redirect("/sia/admin/scholarship/scholarships.php");
            return;
        }

        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'create_scholarship' || $action === 'update_scholarship') {
                $id = (int)($_POST['id'] ?? 0);
                
                $data = [
                    'name' => trim($_POST['name'] ?? ''),
                    'code' => trim($_POST['code'] ?? ''),
                    'category' => trim($_POST['category'] ?? 'School-Based'),
                    'provider' => trim($_POST['provider'] ?? ''),
                    'program_id' => !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null,
                    'year_level' => trim($_POST['year_level'] ?? ''),
                    'min_gwa' => !empty($_POST['min_gwa']) ? (float)$_POST['min_gwa'] : null,
                    'income_requirement' => !empty($_POST['income_requirement']) ? (float)$_POST['income_requirement'] : null,
                    'slots' => !empty($_POST['slots']) ? (int)$_POST['slots'] : null,
                    'tuition_coverage_type' => trim($_POST['tuition_coverage_type'] ?? 'fixed'),
                    'tuition_coverage_value' => (float)($_POST['tuition_coverage_value'] ?? 0),
                    'misc_coverage_type' => trim($_POST['misc_coverage_type'] ?? 'fixed'),
                    'misc_coverage_value' => (float)($_POST['misc_coverage_value'] ?? 0),
                    'stipend_amount' => (float)($_POST['stipend_amount'] ?? 0),
                    'book_allowance' => (float)($_POST['book_allowance'] ?? 0),
                    'description' => trim($_POST['description'] ?? ''),
                    'requirements' => trim($_POST['requirements'] ?? ''),
                    'application_start' => !empty($_POST['application_start']) ? $_POST['application_start'] : null,
                    'application_end' => !empty($_POST['application_end']) ? $_POST['application_end'] : null,
                    'status' => trim($_POST['status'] ?? 'Draft')
                ];

                if ($data['name'] === '' || $data['code'] === '') {
                    throw new Exception('Scholarship Name and Code are required.');
                }

                if ($action === 'create_scholarship') {
                    $stmt = $pdo->prepare('
                        INSERT INTO scholarships (
                            name, code, category, provider, program_id, year_level, min_gwa, income_requirement, slots,
                            tuition_coverage_type, tuition_coverage_value, misc_coverage_type, misc_coverage_value,
                            stipend_amount, book_allowance, description, requirements, application_start, application_end, status
                        ) VALUES (
                            :name, :code, :category, :provider, :program_id, :year_level, :min_gwa, :income_requirement, :slots,
                            :tuition_coverage_type, :tuition_coverage_value, :misc_coverage_type, :misc_coverage_value,
                            :stipend_amount, :book_allowance, :description, :requirements, :application_start, :application_end, :status
                        )
                    ');
                    $stmt->execute($data);
                    logActivity((int)$_SESSION['user_id'], 'bi-award', 'Scholarship Created', "Created a new scholarship: " . $data['name']);
                    $_SESSION['success_msg'] = 'Scholarship created successfully.';
                } else {
                    $data['id'] = $id;
                    $stmt = $pdo->prepare('
                        UPDATE scholarships SET
                            name = :name, code = :code, category = :category, provider = :provider, program_id = :program_id,
                            year_level = :year_level, min_gwa = :min_gwa, income_requirement = :income_requirement, slots = :slots,
                            tuition_coverage_type = :tuition_coverage_type, tuition_coverage_value = :tuition_coverage_value,
                            misc_coverage_type = :misc_coverage_type, misc_coverage_value = :misc_coverage_value,
                            stipend_amount = :stipend_amount, book_allowance = :book_allowance, description = :description,
                            requirements = :requirements, application_start = :application_start, application_end = :application_end,
                            status = :status
                        WHERE id = :id
                    ');
                    $stmt->execute($data);
                    logActivity((int)$_SESSION['user_id'], 'bi-pencil', 'Scholarship Updated', "Updated details for scholarship: " . $data['name']);
                    $_SESSION['success_msg'] = 'Scholarship updated successfully.';
                }

                $response->redirect("/sia/admin/scholarship/scholarships.php");
                return;
            }
            elseif ($action === 'process_application') {
                $appId = (int)($_POST['application_id'] ?? 0);
                $userId = (int)($_POST['user_id'] ?? 0);
                $scholarshipId = (int)($_POST['scholarship_id'] ?? 0);
                $status = $_POST['status'] ?? '';
                $feedback = trim($_POST['admin_feedback'] ?? '');

                if ($appId <= 0 || !in_array($status, ['pending', 'under_review', 'approved', 'rejected'])) {
                    throw new Exception('Invalid application data or status.');
                }

                // Fetch Scholarship details
                $scholStmt = $pdo->prepare('SELECT * FROM scholarships WHERE id = :id');
                $scholStmt->execute(['id' => $scholarshipId]);
                $scholarship = $scholStmt->fetch();

                if (!$scholarship) throw new Exception('Scholarship not found.');

                // Begin Transaction
                $pdo->beginTransaction();

                try {
                    // Update scholarship application status
                    $updAppStmt = $pdo->prepare('UPDATE scholarship_applications SET status = :status, admin_feedback = :feedback WHERE id = :id');
                    $updAppStmt->execute([
                        'status' => $status,
                        'feedback' => $feedback !== '' ? $feedback : null,
                        'id' => $appId
                    ]);

                    if ($status === 'approved') {
                        // Insert into scholarship_recipients
                        // In a real scenario, we should get the active academic year/semester from system_settings
                        $sysStmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('active_academic_year_id', 'active_semester')");
                        $settings = [];
                        foreach ($sysStmt->fetchAll() as $row) {
                            $settings[$row['setting_key']] = $row['setting_value'];
                        }
                        
                        if (!empty($settings['active_academic_year_id']) && !empty($settings['active_semester'])) {
                            // Check if recipient record already exists for this term
                            $chkRecip = $pdo->prepare('SELECT id FROM scholarship_recipients WHERE user_id = :uid AND scholarship_id = :sid AND academic_year_id = :ay AND semester = :sem');
                            $chkRecip->execute([
                                'uid' => $userId,
                                'sid' => $scholarshipId,
                                'ay' => $settings['active_academic_year_id'],
                                'sem' => $settings['active_semester']
                            ]);
                            if (!$chkRecip->fetch()) {
                                $insRecip = $pdo->prepare('INSERT INTO scholarship_recipients (user_id, scholarship_id, academic_year_id, semester, status) VALUES (:uid, :sid, :ay, :sem, "Active")');
                                $insRecip->execute([
                                    'uid' => $userId,
                                    'sid' => $scholarshipId,
                                    'ay' => $settings['active_academic_year_id'],
                                    'sem' => $settings['active_semester']
                                ]);
                                
                                // Recalculate assessment
                                recalculateStudentAssessment($userId, $pdo);
                            }
                        }
                    }

                    // Log activity
                    $pdo->commit();
                    $_SESSION['success_msg'] = 'Scholarship application processed successfully.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }

                $response->redirect("/sia/admin/scholarship/scholarship_detail.php?id=" . $appId);
                return;
            }
            elseif ($action === 'update_recipient_status') {
                $id = (int)($_POST['recipient_id'] ?? 0);
                $status = $_POST['status'] ?? '';
                $remarks = trim($_POST['remarks'] ?? '');

                if ($id <= 0 || !in_array($status, ['Active', 'Suspended', 'Terminated', 'Renewed'])) {
                    throw new Exception('Invalid status or recipient ID.');
                }

                $stmt = $pdo->prepare('UPDATE scholarship_recipients SET status = :status, remarks = :remarks WHERE id = :id');
                $stmt->execute([
                    'status' => $status,
                    'remarks' => $remarks !== '' ? $remarks : null,
                    'id' => $id
                ]);

                // Recalculate assessment for the user
                $uidStmt = $pdo->prepare('SELECT user_id FROM scholarship_recipients WHERE id = :id');
                $uidStmt->execute(['id' => $id]);
                if ($uid = $uidStmt->fetchColumn()) {
                    recalculateStudentAssessment($uid, $pdo);
                }

                $_SESSION['success_msg'] = 'Scholar status updated successfully.';
                $response->redirect("/sia/admin/scholarship/scholars.php");
                return;
            }
            else {
                throw new Exception('Invalid action requested.');
            }
        } catch (Exception $e) {
            $_SESSION['error_msg'] = $e->getMessage();
            $response->redirect("/sia/admin/scholarship/scholarships.php");
            return;
        }
    }
}
