<?php

namespace App\Controllers\Admin;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\User;
use PDO;
use PDOException;
use Exception;
use finfo;

class AdmissionsController extends BaseController
{
    public function index(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
        $stats = [
            'pending_apps' => 0,
            'pending_docs' => 0,
            'pending_medical' => 0,
        ];
        $recent_apps = [];

        try {
            $stmtApps = $pdo->query('SELECT COUNT(*) FROM applications WHERE status IN ("pending", "under_review")');
            $stats['pending_apps'] = (int) $stmtApps->fetchColumn();

            $stmtDocs = $pdo->query('SELECT COUNT(*) FROM application_documents WHERE status = "pending"');
            $stats['pending_docs'] = (int) $stmtDocs->fetchColumn();

            if (hasPermission('medical.review')) {
                $medicalStmt = $pdo->query('SELECT COUNT(*) FROM health_records WHERE status IN ("pending", "under_review", "correction_required")');
                $stats['pending_medical'] = (int) $medicalStmt->fetchColumn();
            }

            $recentAppsStmt = $pdo->query('
                SELECT a.id, a.reference_number, a.status, a.strand, a.created_at, u.first_name, u.last_name 
                FROM applications a 
                INNER JOIN users u ON u.id = a.user_id 
                ORDER BY a.created_at DESC LIMIT 8
            ');
            $recent_apps = $recentAppsStmt->fetchAll();

        } catch (PDOException $e) {
            error_log('Admissions dashboard stats failed: ' . $e->getMessage());
        }

        return $this->render('admin/admissions/dashboard', [
            'stats' => $stats,
            'recent_apps' => $recent_apps
        ]);
    }

public function review(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
        $search = trim((string) $request->input('search', ''));
        $statusFilter = trim((string) $request->input('status', 'all'));
        $strandFilter = trim((string) $request->input('strand', 'all'));
        $gradeFilter = trim((string) $request->input('grade', 'all'));
        $levelFilter = trim((string) $request->input('level', 'all'));
        
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
        
        $sortOrder = trim((string) $request->input('sort', 'newest'));
        $page = $request->input('page') ? max(1, (int)$request->input('page')) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $whereClauses = [];
        $params = [];
        
        if ($search !== '') {
            $whereClauses[] = '(a.reference_number LIKE :search OR CONCAT(u.first_name, " ", u.last_name) LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        
        if ($statusFilter !== 'all') {
            $whereClauses[] = 'a.status = :status';
            $params[':status'] = $statusFilter;
        }
        
        if ($strandFilter !== 'all') {
            $whereClauses[] = 'a.strand = :strand';
            $params[':strand'] = $strandFilter;
        }
        
        if ($gradeFilter !== 'all') {
            $whereClauses[] = 'a.grade_level = :grade';
            $params[':grade'] = $gradeFilter;
        }
        
        if ($levelFilter !== 'all') {
            $whereClauses[] = 'a.academic_level = :level';
            $params[':level'] = $levelFilter;
        }
        
        $whereSql = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        
        $orderSql = 'ORDER BY a.created_at DESC';
        if ($sortOrder === 'oldest') {
            $orderSql = 'ORDER BY a.created_at ASC';
        } elseif ($sortOrder === 'name_asc') {
            $orderSql = 'ORDER BY u.last_name ASC, u.first_name ASC';
        } elseif ($sortOrder === 'name_desc') {
            $orderSql = 'ORDER BY u.last_name DESC, u.first_name DESC';
        }
        
        $applications = [];
        $totalCount = 0;
        
        try {
            $countSql = "SELECT COUNT(*) FROM applications a INNER JOIN users u ON u.id = a.user_id $whereSql";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalCount = (int) $countStmt->fetchColumn();
        
            $sql = "
                SELECT a.id, a.reference_number, a.status, a.strand, a.grade_level, a.academic_level, a.created_at, a.document_submission_method,
                       u.first_name, u.last_name, u.email,
                       (SELECT COUNT(*) FROM application_documents d WHERE d.application_id = a.id AND d.status = 'pending') as pending_docs,
                       (SELECT h.status FROM health_records h WHERE h.application_id = a.id LIMIT 1) as medical_status
                FROM applications a
                INNER JOIN users u ON u.id = a.user_id
                $whereSql
                $orderSql
                LIMIT $limit OFFSET $offset
            ";
        
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $applications = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Failed to fetch applications: ' . $e->getMessage());
        }
        
        $totalPages = ceil($totalCount / $limit);

        return $this->render('admin/admissions/review', [
            'programs' => $programs,
            'applications' => $applications,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
            'page' => $page,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'strandFilter' => $strandFilter,
            'gradeFilter' => $gradeFilter,
            'levelFilter' => $levelFilter,
            'sortOrder' => $sortOrder
        ]);
    }

public function detail(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
        $appId = (int) $request->input('id', 0);
        
        if ($appId <= 0) {
            $response->redirect('/sia/admin/admissions/review.php');
            return;
        }
        
        try {
            $stmt = $pdo->prepare('
                SELECT a.*, u.first_name, u.last_name, u.email, u.student_number, u.created_at as user_registered_at, 
                       cc_app.curriculum_name as assigned_curriculum_version,
                       u.college_curriculum_id as user_curriculum_id,
                       cc_user.curriculum_name as user_curriculum_version
                FROM applications a
                INNER JOIN users u ON u.id = a.user_id
                LEFT JOIN college_curricula cc_app ON cc_app.id = a.college_curriculum_id
                LEFT JOIN college_curricula cc_user ON cc_user.id = u.college_curriculum_id
                WHERE a.id = :id LIMIT 1
            ');
            $stmt->execute(['id' => $appId]);
            $app = $stmt->fetch();
        
            if (!$app) {
                $response->redirect('/sia/admin/admissions/review.php');
                return;
            }
        
            $docStmt = $pdo->prepare('SELECT * FROM application_documents WHERE application_id = :app_id');
            $docStmt->execute(['app_id' => $appId]);
            $documents = $docStmt->fetchAll();
        
            $assStmt = $pdo->prepare('SELECT * FROM student_assessments WHERE application_id = :app_id LIMIT 1');
            $assStmt->execute(['app_id' => $appId]);
            $assessment = $assStmt->fetch();
        
            if ($app['academic_level'] === 'Senior High School') {
                $esStmt = $pdo->prepare('
                    SELECT s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type, es.shs_section_id as section_id, sec.section_code
                    FROM shs_enrollments es
                    INNER JOIN subjects s ON s.id = es.subject_id
                    LEFT JOIN shs_sections sec ON sec.id = es.shs_section_id
                    WHERE es.application_id = :app_id
                    ORDER BY s.subject_code ASC
                ');
            } else {
                $esStmt = $pdo->prepare('
                    SELECT s.id as subject_id, s.subject_code, s.subject_name, s.units, s.subject_type, es.college_section_id as section_id, sec.section_code
                    FROM college_enrollments es
                    INNER JOIN subjects s ON s.id = es.subject_id
                    LEFT JOIN college_sections sec ON sec.id = es.college_section_id
                    WHERE es.application_id = :app_id
                    ORDER BY s.subject_code ASC
                ');
            }
            $esStmt->execute(['app_id' => $appId]);
            $enrolledSubjects = $esStmt->fetchAll();
            
            $sectionIds = array_unique(array_filter(array_map(function($sub) use ($app) {
                return $sub['section_id'] ?: $app['section_id'];
            }, $enrolledSubjects)));
        
            if (!empty($sectionIds)) {
                $in = str_repeat('?,', count($sectionIds) - 1) . '?';
                if ($app['academic_level'] === 'Senior High School') {
                    $schedStmt = $pdo->prepare("SELECT shs_section_id as section_id, subject_id, day, start_time, end_time, room FROM shs_section_subjects WHERE shs_section_id IN ($in)");
                } else {
                    $schedStmt = $pdo->prepare("SELECT college_section_id as section_id, subject_id, day, start_time, end_time, room FROM college_section_subjects WHERE college_section_id IN ($in)");
                }
                $schedStmt->execute(array_values($sectionIds));
                $allSchedules = $schedStmt->fetchAll(PDO::FETCH_ASSOC);
        
                foreach ($enrolledSubjects as &$sub) {
                    $sub['schedule_text'] = '';
                    $targetSecId = $sub['section_id'] ?: $app['section_id'];
                    $texts = [];
                    foreach ($allSchedules as $sc) {
                        if ($sc['section_id'] == $targetSecId && $sc['subject_id'] == $sub['subject_id']) {
                            $st = date('h:i A', strtotime($sc['start_time']));
                            $et = date('h:i A', strtotime($sc['end_time']));
                            $texts[] = "{$sc['day']} {$st}-{$et} ({$sc['room']})";
                        }
                    }
                    $sub['schedule_text'] = implode('<br>', $texts);
                }
            }
        
            $feeTemplates = [];
            if (!$assessment) {
                $ftStmt = $pdo->query('SELECT * FROM fee_templates ORDER BY grade_level ASC, strand ASC');
                $feeTemplates = $ftStmt->fetchAll();
            }
        
            if ($app['academic_level'] === 'Senior High School') {
                $secStmt = $pdo->prepare('
                    SELECT s.id, s.section_code, s.capacity, s.schedule_type,
                           (SELECT COUNT(*) FROM applications a WHERE a.section_id = s.id AND a.status != \'rejected\') as current_enrollment
                    FROM shs_sections s
                    INNER JOIN shs_strands p ON p.id = s.strand_id
                    WHERE p.code = :strand AND s.grade_level = :year_level AND s.status = 1
                    ORDER BY s.section_code ASC
                ');
                $secStmt->execute(['strand' => $app['strand'], 'year_level' => $app['grade_level']]);
                $availableSections = $secStmt->fetchAll();
            } else {
                $secStmt = $pdo->prepare('
                    SELECT s.id, s.section_code, s.capacity, s.schedule_type,
                           (SELECT COUNT(*) FROM applications a WHERE a.section_id = s.id AND a.status != \'rejected\') as current_enrollment
                    FROM college_sections s
                    INNER JOIN college_programs p ON p.id = s.program_id
                    WHERE p.code = :strand AND s.year_level = :year_level AND s.status = 1
                    ORDER BY s.section_code ASC
                ');
                $secStmt->execute(['strand' => $app['strand'], 'year_level' => $app['grade_level']]);
                $availableSections = $secStmt->fetchAll();
            }
            
            // Medical
            $medicalStmt = $pdo->prepare('SELECT * FROM health_records WHERE application_id = :app_id LIMIT 1');
            $medicalStmt->execute(['app_id' => $appId]);
            $health = $medicalStmt->fetch();
        
        } catch (PDOException $e) {
            error_log('Admin detail fetch failed: ' . $e->getMessage());
            die('Database Error');
        }

        $allSubjects = [];
        if (($app['student_type'] ?? '') === 'Irregular') {
            $stmtAllSubs = $pdo->query("SELECT id, subject_code, subject_name, units FROM subjects WHERE status = 1 ORDER BY subject_code ASC");
            $allSubjects = $stmtAllSubs->fetchAll(PDO::FETCH_ASSOC);
        }

        return $this->render('admin/admissions/detail', [
            'app' => $app,
            'documents' => $documents,
            'assessment' => $assessment,
            'enrolledSubjects' => $enrolledSubjects,
            'feeTemplates' => $feeTemplates,
            'availableSections' => $availableSections,
            'health' => $health,
            'allSubjects' => $allSubjects
        ]);
    }

public function process(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    $_SESSION['admin_error'] = 'Security validation failed. Please try again.';
    $response->redirect("/sia/admin/admissions/review.php");
    return;
}

$appId = (int) ($_POST['application_id'] ?? 0);
$userId = (int) ($_POST['user_id'] ?? 0);
$status = $_POST['status'] ?? '';
$feedback = trim($_POST['feedback'] ?? '');
$action = $_POST['action'] ?? '';

if ($action === 'update_subjects') {
    $appId = (int) ($_POST['application_id'] ?? 0);
    $subjects = $_POST['subjects'] ?? [];
    
    if ($appId <= 0) {
        $_SESSION['admin_error'] = 'Invalid application ID.';
        $response->redirect("/sia/admin/admissions/review.php");
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        $oldAppStmt = $pdo->prepare('SELECT academic_level FROM applications WHERE id = :id');
        $oldAppStmt->execute(['id' => $appId]);
        $academicLevel = $oldAppStmt->fetchColumn();

        if ($academicLevel === 'Senior High School') {
            $delStmt = $pdo->prepare('DELETE FROM shs_enrollments WHERE application_id = :app_id');
            $delStmt->execute(['app_id' => $appId]);
            
            if (!empty($subjects)) {
                $insStmt = $pdo->prepare('INSERT INTO shs_enrollments (application_id, subject_id) VALUES (:app_id, :sub_id)');
                foreach ($subjects as $subId => $secSubId) {
                    // For irregulars we just insert subjects for now
                    $insStmt->execute(['app_id' => $appId, 'sub_id' => (int)$subId]);
                }
            }
        } else {
            $delStmt = $pdo->prepare('DELETE FROM college_enrollments WHERE application_id = :app_id');
            $delStmt->execute(['app_id' => $appId]);
            
            if (!empty($subjects)) {
                $insStmt = $pdo->prepare('INSERT INTO college_enrollments (application_id, subject_id, college_section_id) VALUES (:app_id, :sub_id, :sec_id)');
                foreach ($subjects as $subId => $secSubId) {
                    $secSubIdVal = !empty($secSubId) ? (int)$secSubId : null;
                    // Note: secSubId here corresponds to college_section_id for irregular schedule selection
                    $insStmt->execute(['app_id' => $appId, 'sub_id' => (int)$subId, 'sec_id' => $secSubIdVal]);
                }
            }
        }
        
        $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
        $logStmt->execute([
            'user_id' => $_SESSION['user_id'],
            'icon' => 'bi-pencil-square text-primary',
            'title' => 'Subjects Updated',
            'description' => "Registrar updated the enrolled subjects for Application #{$appId}."
        ]);
        
        $pdo->commit();
        $_SESSION['admin_success'] = 'Subjects have been updated successfully.';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['admin_error'] = 'Database error while updating subjects.';
    }
    
    $response->redirect("/sia/admin/admissions/application_detail.php?id={$appId}");
    return;
}

$internalNotes = isset($_POST['internal_notes']) ? trim((string)$_POST['internal_notes']) : null;

$validStatuses = ['pending', 'under_review', 'correction_required', 'approved', 'rejected', 'enrolled'];

if ($appId <= 0 || !in_array($status, $validStatuses, true)) {
    $_SESSION['admin_error'] = 'Invalid application ID or status.';
    $response->redirect("/sia/admin/admissions/review.php");
    return;
}

try {
    // 0.5. Verify payment requirements if changing to enrolled
    if ($status === 'enrolled') {
        if (!hasPermission('enrollment.finalize')) {
            $_SESSION['admin_error'] = 'You do not have permission to finalize enrollments.';
            $response->redirect("/sia/admin/admissions/review.php");
            return;
        }

        $assStmt = $pdo->prepare('SELECT payment_status FROM student_assessments WHERE application_id = :app_id LIMIT 1');
        $assStmt->execute(['app_id' => $appId]);
        $assessment = $assStmt->fetch();
        
        if (!$assessment || $assessment['payment_status'] === 'unpaid') {
            $_SESSION['admin_error'] = 'Cannot enroll student. Payment requirements have not been met (must be at least partially paid).';
            $response->redirect("/sia/admin/admissions/review.php");
            return;
        }
    }

    // 0.6. Verify document requirements if changing to approved or enrolled
    if ($status === 'approved' || $status === 'enrolled') {
        $docMethodStmt = $pdo->prepare('SELECT document_submission_method FROM applications WHERE id = :id');
        $docMethodStmt->execute(['id' => $appId]);
        $docMethod = $docMethodStmt->fetchColumn();

        if ($docMethod === 'online') {
            $docsStmt = $pdo->prepare('SELECT id, status FROM application_documents WHERE application_id = :id');
            $docsStmt->execute(['id' => $appId]);
            $currentDocs = $docsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($currentDocs)) {
                $statusText = $status === 'enrolled' ? 'enroll' : 'approve';
                $_SESSION['admin_error'] = "Cannot {$statusText} applicant. No documents have been uploaded yet.";
                $response->redirect("/sia/admin/admissions/application_detail.php?id={$appId}");
                return;
            }

            $docStatuses = $_POST['doc_status'] ?? [];
            foreach ($currentDocs as $doc) {
                $finalStatus = $docStatuses[$doc['id']] ?? $doc['status'];
                if ($finalStatus !== 'verified') {
                    $statusText = $status === 'enrolled' ? 'enroll' : 'approve';
                    $_SESSION['admin_error'] = "Cannot {$statusText} applicant. All submitted documents must be verified first.";
                    $response->redirect("/sia/admin/admissions/application_detail.php?id={$appId}");
                    return;
                }
            }
        }
    }

    // Fetch old application state
    $oldAppStmt = $pdo->prepare('SELECT academic_level, status, admin_feedback, internal_notes, strand FROM applications WHERE id = :id');
    $oldAppStmt->execute(['id' => $appId]);
    $oldApp = $oldAppStmt->fetch(PDO::FETCH_ASSOC);

    // Validate Status Transitions (Prevent reverting from Approved or Enrolled)
    if ($oldApp['status'] === 'enrolled' && $status !== 'enrolled') {
        $_SESSION['admin_error'] = 'Cannot change status. The applicant is already Officially Enrolled.';
        $response->redirect("/sia/admin/admissions/application_detail.php?id={$appId}");
        return;
    }
    
    if ($oldApp['status'] === 'approved' && !in_array($status, ['approved', 'enrolled'])) {
        $_SESSION['admin_error'] = 'Cannot revert status. An approved application can only proceed to Officially Enrolled.';
        $response->redirect("/sia/admin/admissions/application_detail.php?id={$appId}");
        return;
    }

    // 0. Process Section Assignment
    $assignSectionId = (int)($_POST['assign_section'] ?? 0);
    if ($assignSectionId > 0) {
        $stmt = $pdo->prepare('UPDATE applications SET section_id = :section_id WHERE id = :id');
        $stmt->execute(['section_id' => $assignSectionId, 'id' => $appId]);

        if ($oldApp['academic_level'] === 'Senior High School') {
            $delStmt = $pdo->prepare('DELETE FROM shs_enrollments WHERE application_id = :app_id');
            $delStmt->execute(['app_id' => $appId]);

            $subStmt = $pdo->prepare('
                SELECT subject_id 
                FROM shs_section_subjects 
                WHERE shs_section_id = :section_id
            ');
            $subStmt->execute(['section_id' => $assignSectionId]);
            $subjectsToEnroll = $subStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($subjectsToEnroll)) {
                $insSubStmt = $pdo->prepare('INSERT INTO shs_enrollments (application_id, subject_id, shs_section_id) VALUES (:app_id, :sub_id, :sec_id)');
                foreach ($subjectsToEnroll as $row) {
                    $insSubStmt->execute([
                        'app_id' => $appId, 
                        'sub_id' => $row['subject_id'],
                        'sec_id' => $assignSectionId
                    ]);
                }
            }
            
            $secStmt = $pdo->prepare('SELECT section_code FROM shs_sections WHERE id = :id');
            $secStmt->execute(['id' => $assignSectionId]);
            $secCode = $secStmt->fetchColumn();
        } else {
            // Check if the student already has a permanently assigned curriculum
            $userCurrStmt = $pdo->prepare('SELECT college_curriculum_id FROM users WHERE id = :id');
            $userCurrStmt->execute(['id' => $userId]);
            $studentCurriculumId = $userCurrStmt->fetchColumn();

            // Retrieve Section details
            $secStmt = $pdo->prepare('SELECT curriculum_id, year_level, semester, section_code FROM college_sections WHERE id = :id');
            $secStmt->execute(['id' => $assignSectionId]);
            $sectionData = $secStmt->fetch(PDO::FETCH_ASSOC);
            $secCode = $sectionData['section_code'];
            $sectionCurriculumId = $sectionData['curriculum_id'];

            if (!$studentCurriculumId) {
                // Determine the Active Curriculum for the student's program
                $activeCurrStmt = $pdo->prepare('
                    SELECT cc.id 
                    FROM college_curricula cc 
                    JOIN college_programs cp ON cc.program_id = cp.id 
                    WHERE cp.code = :strand AND cc.status = "active" 
                    ORDER BY cc.created_at DESC LIMIT 1
                ');
                $activeCurrStmt->execute(['strand' => $oldApp['strand']]);
                $studentCurriculumId = $activeCurrStmt->fetchColumn();

                // Fallback to the section's curriculum if no active curriculum is explicitly flagged
                if (!$studentCurriculumId && $sectionCurriculumId) {
                    $studentCurriculumId = $sectionCurriculumId;
                }

                if ($studentCurriculumId) {
                    // Permanently assign it to the student
                    $pdo->prepare('UPDATE users SET college_curriculum_id = :curr_id WHERE id = :id')
                        ->execute(['curr_id' => $studentCurriculumId, 'id' => $userId]);
                }
            }

            if ($studentCurriculumId) {
                // Update application to lock in the curriculum reference for this specific enrollment instance
                $appUpdStmt = $pdo->prepare('UPDATE applications SET college_curriculum_id = :curr_id WHERE id = :id');
                $appUpdStmt->execute(['curr_id' => $studentCurriculumId, 'id' => $appId]);
            }

            $delStmt = $pdo->prepare('DELETE FROM college_enrollments WHERE application_id = :app_id');
            $delStmt->execute(['app_id' => $appId]);

            // Retrieve Curriculum Subjects matching the Section's Year Level and Semester
            // IMPORTANT: We use the STUDENT'S permanently assigned curriculum, NOT the section's curriculum
            $subStmt = $pdo->prepare('
                SELECT subject_id 
                FROM college_curriculum_subjects 
                WHERE curriculum_id = :curriculum_id 
                  AND year_level = :year_level 
                  AND (semester = :semester OR semester IS NULL OR semester = "")
            ');
            $subStmt->execute([
                'curriculum_id' => $studentCurriculumId,
                'year_level' => $sectionData['year_level'],
                'semester' => $sectionData['semester']
            ]);
            $subjectsToEnroll = $subStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($subjectsToEnroll)) {
                $insSubStmt = $pdo->prepare('INSERT INTO college_enrollments (application_id, subject_id, college_section_id) VALUES (:app_id, :sub_id, :sec_id)');
                foreach ($subjectsToEnroll as $row) {
                    $insSubStmt->execute([
                        'app_id' => $appId, 
                        'sub_id' => $row['subject_id'],
                        'sec_id' => $assignSectionId
                    ]);
                }
            }
        }

        $logDocStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
        $logDocStmt->execute([
            'user_id' => $userId,
            'icon' => 'bi-diagram-3-fill text-primary',
            'title' => 'Section Assigned',
            'description' => "You have been assigned to section {$secCode}."
        ]);
    }

    // 1. Update Application Status, Feedback, and Internal Notes
    $stmt = $pdo->prepare('UPDATE applications SET status = :status, admin_feedback = :admin_feedback, internal_notes = :internal_notes WHERE id = :id');
    $stmt->execute([
        'status' => $status,
        'admin_feedback' => $feedback !== '' ? $feedback : null,
        'internal_notes' => $internalNotes !== '' ? $internalNotes : null,
        'id' => $appId
    ]);

    $newApp = [
        'status' => $status,
        'admin_feedback' => $feedback !== '' ? $feedback : null,
        'internal_notes' => $internalNotes !== '' ? $internalNotes : null
    ];

    // 1.2 Generate Student Number if Enrolled
    if ($status === 'enrolled') {
        $uStmt = $pdo->prepare('SELECT student_number FROM users WHERE id = :id LIMIT 1');
        $uStmt->execute(['id' => $userId]);
        $existingNumber = $uStmt->fetchColumn();

        if (empty($existingNumber)) {
            $existingNumber = generateStudentNumber($pdo);
            $updUser = $pdo->prepare('UPDATE users SET student_number = :student_number WHERE id = :id');
            $updUser->execute(['student_number' => $existingNumber, 'id' => $userId]);
            
            // Log it
            $logDocStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
            $logDocStmt->execute([
                'user_id' => $userId,
                'icon' => 'bi-person-vcard-fill text-success',
                'title' => 'Student Number Assigned',
                'description' => "Your official student number is {$existingNumber}."
            ]);
        }

        // 1.3 GENERATE CREDENTIALS & SEND EMAIL
        $uStmt = $pdo->prepare('SELECT first_name, last_name, email, ttu_email FROM users WHERE id = :id LIMIT 1');
        $uStmt->execute(['id' => $userId]);
        $userRow = $uStmt->fetch(PDO::FETCH_ASSOC);

        if (empty($userRow['ttu_email'])) {
            $cleanFirst = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $userRow['first_name']));
            $cleanLast = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $userRow['last_name']));
            $ttuEmail = $cleanFirst . '.' . $cleanLast . '@ttu.edu.ph';
            
            // Check for duplicates in ttu_email
            $checkEmail = $pdo->prepare('SELECT COUNT(*) FROM users WHERE ttu_email = :email');
            $counter = 1;
            while(true) {
                $checkEmail->execute(['email' => $ttuEmail]);
                if ($checkEmail->fetchColumn() == 0) break;
                $ttuEmail = $cleanFirst . '.' . $cleanLast . $counter . '@ttu.edu.ph';
                $counter++;
            }

            $tempPassword = $existingNumber;
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

            $updCreds = $pdo->prepare('UPDATE users SET ttu_email = :ttu_email, password = :pwd, force_password_reset = 1 WHERE id = :id');
            $updCreds->execute([
                'ttu_email' => $ttuEmail,
                'pwd' => $hashedPassword,
                'id' => $userId
            ]);

            // SEND EMAIL VIA PHPMAILER
            $autoloadPath = __DIR__ . '/../../../vendor/autoload.php';
            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = getenv('SMTP_USERNAME');
                    $mail->Password   = getenv('SMTP_PASSWORD');
                    
                    // Handle TLS vs SSL
                    $enc = getenv('SMTP_ENCRYPTION') ?: 'tls';
                    if (strtolower($enc) === 'ssl') {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    } else {
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    }
                    
                    $mail->Port       = getenv('SMTP_PORT') ?: 587;

                    $mail->setFrom(getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@ttu.edu.ph', getenv('MAIL_FROM_NAME') ?: 'Triple T University');
                    $mail->addAddress($userRow['email'], $userRow['first_name'] . ' ' . $userRow['last_name']);

                    // Embed Logo
                    $mail->addEmbeddedImage(__DIR__ . '/../../../../images/TTU_LOGO.png', 'ttu_logo');

                    $mail->isHTML(true);
                    $mail->Subject = 'Welcome to Triple T University - Enrollment Finalized';
                    
                    ob_start();
                    extract([
                        'firstName' => $userRow['first_name'],
                        'ttuEmail' => $ttuEmail,
                        'studentNumber' => $existingNumber,
                        'tempPassword' => $tempPassword
                    ]);
                    require __DIR__ . '/../../Views/emails/welcome_credentials.php';
                    $mail->Body = ob_get_clean();

                    $mail->send();
                } catch (\Exception $e) {
                    error_log('Mailer Error: ' . $mail->ErrorInfo);
                }
            }
        }
    }

    // 1.5. Update Individual Document verification statuses and comments
    $docStatuses = $_POST['doc_status'] ?? [];
    $docFeedbacks = $_POST['doc_feedback'] ?? [];
    
    if (!empty($docStatuses)) {
        $docUpdateStmt = $pdo->prepare('UPDATE application_documents SET status = :status, feedback = :feedback WHERE id = :id AND application_id = :app_id');
        $docSelectStmt = $pdo->prepare('SELECT document_name, status FROM application_documents WHERE id = :id LIMIT 1');
        
        foreach ($docStatuses as $docId => $docStatus) {
            $docId = (int)$docId;
            $docFeedback = trim((string)($docFeedbacks[$docId] ?? ''));
            
            // Fetch old status to see if it changed (for timeline logs)
            $docSelectStmt->execute(['id' => $docId]);
            $oldDoc = $docSelectStmt->fetch();
            
            $docUpdateStmt->execute([
                'status' => $docStatus,
                'feedback' => $docFeedback !== '' ? $docFeedback : null,
                'id' => $docId,
                'app_id' => $appId
            ]);
            
            if ($oldDoc && $oldDoc['status'] !== $docStatus) {
                // Log status transition to student timeline
                $docName = $oldDoc['document_name'];
                $mappedState = match($docStatus) {
                    'verified' => 'Verified / Approved',
                    'rejected' => 'Rejected / Needs Reupload',
                    default => 'Pending Review'
                };
                
                $logDocStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
                $logDocStmt->execute([
                    'user_id' => $userId,
                    'icon' => $docStatus === 'verified' ? 'bi-file-earmark-check-fill text-success' : 'bi-file-earmark-x-fill text-danger',
                    'title' => "Document Audit: {$docName}",
                    'description' => "Audit status updated to '{$mappedState}'" . ($docFeedback !== '' ? ". Comment: {$docFeedback}" : "")
                ]);
            }
        }
    }

    // 2. Process Assessment Generation (Automatic)
    $generateAssessment = ($status === 'approved' || $status === 'enrolled');
    
    if ($generateAssessment) {
        // Check if an assessment already exists
        $assCheckStmt = $pdo->prepare('SELECT id FROM student_assessments WHERE application_id = :app_id LIMIT 1');
        $assCheckStmt->execute(['app_id' => $appId]);
        
        if (!$assCheckStmt->fetch()) {
            // Fetch applicant details to find matching template
            $appStmt = $pdo->prepare('SELECT academic_level, grade_level, strand FROM applications WHERE id = :id LIMIT 1');
            $appStmt->execute(['id' => $appId]);
            $appData = $appStmt->fetch();

            if ($appData) {
                // Fetch the exact template matching grade level and strand
                $ftStmt = $pdo->prepare('SELECT * FROM fee_templates WHERE grade_level = :grade_level AND strand = :strand LIMIT 1');
                $ftStmt->execute([
                    'grade_level' => $appData['grade_level'],
                    'strand' => $appData['strand']
                ]);
                $template = $ftStmt->fetch();
                
                if ($template) {
                    $academicLevel = $appData['academic_level'];
                    $feeTemplateId = (int)$template['id'];


                $tuitionFee = (float)$template['tuition_fee'];

                // Dynamic Tuition Calculation for College Students
                if ($academicLevel === 'College') {
                    // Fetch configured cost per unit
                    $settingStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'college_cost_per_unit' LIMIT 1");
                    $costPerUnitStr = $settingStmt->fetchColumn();
                    $costPerUnit = $costPerUnitStr !== false ? (float)$costPerUnitStr : 500.00;

                    // Fetch total enrolled units
                    $unitsStmt = $pdo->prepare('
                        SELECT SUM(s.units) 
                        FROM college_enrollments ce 
                        JOIN subjects s ON ce.subject_id = s.id 
                        WHERE ce.application_id = :app_id
                    ');
                    $unitsStmt->execute(['app_id' => $appId]);
                    $totalUnits = (int)$unitsStmt->fetchColumn();

                    if ($totalUnits > 0) {
                        $tuitionFee = $totalUnits * $costPerUnit;
                    }
                }

                $miscFee = (float)$template['miscellaneous_fee'];
                $regFee = (float)$template['registration_fee'];
                $labFee = (float)$template['laboratory_fee'];
                $otherFees = (float)$template['other_fees'];
                
                $totalAmount = $tuitionFee + $miscFee + $regFee + $labFee + $otherFees;

                $insertAssStmt = $pdo->prepare('
                    INSERT INTO student_assessments 
                    (user_id, application_id, fee_template_id, tuition_fee, miscellaneous_fee, registration_fee, laboratory_fee, other_fees, total_amount, discount_amount, net_amount)
                    VALUES 
                    (:user_id, :app_id, :fee_id, :tuition, :misc, :reg, :lab, :other, :total_amount, 0, :net_amount)
                ');
                $insertAssStmt->execute([
                    'user_id' => $userId,
                    'app_id' => $appId,
                    'fee_id' => $feeTemplateId,
                    'tuition' => $tuitionFee,
                    'misc' => $miscFee,
                    'reg' => $regFee,
                    'lab' => $labFee,
                    'other' => $otherFees,
                    'total_amount' => $totalAmount,
                    'net_amount' => $totalAmount
                ]);
                
                // Log Assessment Generation
                $logDocStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, icon, title, description) VALUES (:user_id, :icon, :title, :description)');
                $logDocStmt->execute([
                    'user_id' => $userId,
                    'icon' => 'bi-cash-stack text-success',
                    'title' => "Financial Assessment Generated",
                    'description' => "Your financial assessment has been generated and is ready for review."
                ]);
            }
        }
        }
    }

    // 3. Generate Timeline Log for the Applicant
    $logIcon = match($status) {
        'approved' => 'bi-check-circle-fill text-success',
        'rejected' => 'bi-x-circle-fill text-danger',
        'correction_required' => 'bi-exclamation-triangle-fill text-warning',
        'enrolled' => 'bi-mortarboard-fill text-success',
        'under_review' => 'bi-search text-info',
        default => 'bi-info-circle-fill text-primary'
    };

    $statusTitle = formatApplicationStatus($status);
    $logTitle = "Application Status: {$statusTitle}";
    
    $logDescription = $feedback !== '' 
        ? "Admin Feedback: " . $feedback 
        : getApplicationStatusMessage($status);

    $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description) VALUES (:user_id, :ip_address, :affected_record, :icon, :title, :description)');
    $logStmt->execute([
        'user_id' => $userId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'affected_record' => "Application #$appId",
        'icon' => $logIcon,
        'title' => $logTitle,
        'description' => $logDescription
    ]);

    // Audit Log for Admin
    if ($oldApp && $oldApp['status'] !== $status) {
        logActivity(
            (int)$_SESSION['user_id'],
            'bi-clipboard-check-fill',
            'Application ' . ucfirst($status),
            "Updated application status to {$statusTitle}.",
            "Application #$appId",
            $oldApp,
            $newApp,
            $feedback
        );
    }

    $_SESSION['admin_success'] = "Application successfully updated to '{$statusTitle}'.";

} catch (PDOException $e) {
    error_log('Admin action failed: ' . $e->getMessage());
    $_SESSION['admin_error'] = 'A database error occurred: ' . $e->getMessage();
}

$response->redirect("/sia/admin/admissions/application_detail.php?id={$appId}");
return;


    }

    public function bulkProcess(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$csrfToken = $_POST['csrf_token'] ?? '';
$selectedApps = $_POST['selected_apps'] ?? [];
$bulkStatus = $_POST['bulk_status'] ?? '';

// 1. CSRF Verification
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    $_SESSION['admin_error'] = 'Security validation failed. Please try again.';
    $response->redirect("/sia/admin/admissions/review.php");
    return;
}

$validStatuses = ['pending', 'under_review', 'correction_required', 'approved', 'rejected', 'enrolled'];

if (empty($selectedApps) || !in_array($bulkStatus, $validStatuses, true)) {
    $_SESSION['admin_error'] = 'No applications selected or invalid target status.';
    $response->redirect("/sia/admin/admissions/review.php");
    return;
}

try {
    $pdo->beginTransaction();

    $updateStmt = $pdo->prepare('UPDATE applications SET status = :status WHERE id = :id');
    $userStmt = $pdo->prepare('SELECT user_id, reference_number, status FROM applications WHERE id = :id LIMIT 1');
    $logStmt = $pdo->prepare('INSERT INTO activity_logs (user_id, ip_address, affected_record, icon, title, description, old_value, new_value) VALUES (:user_id, :ip_address, :affected_record, :icon, :title, :description, :old_value, :new_value)');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    $logIcon = match($bulkStatus) {
        'approved' => 'bi-check-circle-fill text-success',
        'rejected' => 'bi-x-circle-fill text-danger',
        'correction_required' => 'bi-exclamation-triangle-fill text-warning',
        'enrolled' => 'bi-mortarboard-fill text-success',
        'under_review' => 'bi-search text-info',
        default => 'bi-info-circle-fill text-primary'
    };

    $statusTitle = formatApplicationStatus($bulkStatus);
    $logTitle = "Application Status: {$statusTitle}";
    $logDescription = getApplicationStatusMessage($bulkStatus);

    $processedCount = 0;

    foreach ($selectedApps as $appId) {
        $appId = (int)$appId;
        if ($appId <= 0) continue;

        // Fetch User ID
        $userStmt->execute(['id' => $appId]);
        $appInfo = $userStmt->fetch();
        if (!$appInfo) continue;

        $userId = (int)$appInfo['user_id'];

        // Update status
        $updateStmt->execute(['status' => $bulkStatus, 'id' => $appId]);

        // Add Log
        $logStmt->execute([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'affected_record' => "Application #$appId",
            'icon' => $logIcon,
            'title' => $logTitle,
            'description' => $logDescription,
            'old_value' => json_encode(['status' => $appInfo['status']]),
            'new_value' => json_encode(['status' => $bulkStatus])
        ]);

        $processedCount++;
    }

    $pdo->commit();

    // Audit Log for Admin
    logActivity(
        (int)$_SESSION['user_id'],
        'bi-layers-fill',
        'Bulk Status Update',
        "Bulk updated $processedCount applications to '$statusTitle'."
    );

    $_SESSION['admin_success'] = "Successfully updated $processedCount application(s) to '$statusTitle'.";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Bulk action failed: ' . $e->getMessage());
    $_SESSION['admin_error'] = 'An error occurred while bulk processing applications.';
}

$response->redirect("/sia/admin/admissions/review.php");
return;


    }

    public function viewDocument(Request $request, Response $response)
    {
        $docId = (int) $request->input('id', 0);
        $pdo = Database::getConnection();

        if ($docId <= 0) {
            $response->setStatusCode(400);
            echo "Invalid document ID.";
            return;
        }

        try {
            $stmt = $pdo->prepare('SELECT file_path FROM application_documents WHERE id = :id');
            $stmt->execute(['id' => $docId]);
            $document = $stmt->fetch();

            if (!$document) {
                $response->setStatusCode(404);
                echo "Document record not found.";
                return;
            }

            $filepath = __DIR__ . '/../../../uploads/documents/' . basename($document['file_path']);

            if (!file_exists($filepath)) {
                $response->setStatusCode(404);
                echo "File not found on server.";
                return;
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($filepath);

            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($filepath));
            header('Content-Disposition: inline; filename="' . basename($document['file_path']) . '"');
            
            readfile($filepath);
            exit;
        } catch (PDOException $e) {
            $response->setStatusCode(500);
            echo "Database error.";
            return;
        }
    }
}
