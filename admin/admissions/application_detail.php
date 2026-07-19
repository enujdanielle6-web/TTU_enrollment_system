<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('applications.view_details');

$appId = (int) ($_GET['id'] ?? 0);

if ($appId <= 0) {
    header('Location: review.php');
    exit;
}

try {
    // Fetch Application & User data
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
        header('Location: review.php');
        exit;
    }

    // Fetch Documents
    $docStmt = $pdo->prepare('SELECT * FROM application_documents WHERE application_id = :app_id');
    $docStmt->execute(['app_id' => $appId]);
    $documents = $docStmt->fetchAll();

    // Check for existing assessment
    $assStmt = $pdo->prepare('SELECT * FROM student_assessments WHERE application_id = :app_id LIMIT 1');
    $assStmt->execute(['app_id' => $appId]);
    $assessment = $assStmt->fetch();

    // Fetch Enrolled Subjects based on academic level
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
    
    // Attach schedules using a single query to avoid N+1 problem
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

    // Fetch fee templates
    $feeTemplates = [];
    if (!$assessment) {
        $ftStmt = $pdo->query('SELECT * FROM fee_templates ORDER BY grade_level ASC, strand ASC');
        $feeTemplates = $ftStmt->fetchAll();
    }

    // Fetch matching active sections for this applicant
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

} catch (PDOException $e) {
    error_log('Admin detail fetch failed: ' . $e->getMessage());
    showErrorPage('Database Error', 'A database error occurred while querying details for this application.');
}

$successMsg = $_SESSION['admin_success'] ?? '';
$errorMsg = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$statusLabel = formatApplicationStatus($app['status']);
$badgeClass = getApplicationStatusBadgeClass($app['status']);

$pageTitle = 'Review Application ' . htmlspecialchars($app['reference_number'], ENT_QUOTES, 'UTF-8') . ' - Admin';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="mb-4 d-flex align-items-center justify-content-between">
      <div>
        <?php 
          $backUrl = 'javascript:history.back()';
          if (isset($_SERVER['HTTP_REFERER']) && str_contains($_SERVER['HTTP_REFERER'], '/admin/')) {
              $backUrl = htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES, 'UTF-8');
          }
        ?>
        <a href="<?= $backUrl ?>" class="text-decoration-none text-muted small fw-medium"><i class="bi bi-arrow-left"></i> Back to List</a>
        <h1 class="h3 fw-bold text-dark mt-2 mb-1">
          Review Application 
          <span class="badge <?= $badgeClass ?> ms-2 fs-6 align-middle"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </h1>
        <p class="text-muted mb-0">Ref: <span class="fw-medium text-dark"><?= htmlspecialchars($app['reference_number'], ENT_QUOTES, 'UTF-8') ?></span></p>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form action="application_process.php" method="POST">
      <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
      <input type="hidden" name="user_id" value="<?= $app['user_id'] ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      
      <div class="row g-4">
        
        <!-- Left Column: Application Details -->
        <div class="col-lg-8">
        
        <!-- Personal Information -->
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light">
            <i class="bi bi-person-vcard-fill"></i>
            <h2>Personal Information</h2>
          </div>
          <div class="island-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="text-muted small fw-semibold text-uppercase">Full Name</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['last_name'] . ', ' . $app['first_name'], ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small fw-semibold text-uppercase">Email Address</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['email'], ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Student Number</label>
                <div class="fw-medium text-dark"><?= $app['student_number'] ? htmlspecialchars($app['student_number'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">Not Assigned</span>' ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Date of Birth</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['birth_date'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Gender</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars(ucfirst($app['gender'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Contact No.</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['contact_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-12">
                <label class="text-muted small fw-semibold text-uppercase">Address</label>
                <div class="fw-medium text-dark">
                  <?php 
                    $fullAddress = trim(($app['address_house_number'] ?? '') . ' ' . ($app['address'] ?? ''));
                    echo htmlspecialchars($fullAddress !== '' ? $fullAddress : 'N/A', ENT_QUOTES, 'UTF-8');
                  ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Academic Information -->
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light">
            <i class="bi bi-mortarboard-fill"></i>
            <h2>Enrollment Details</h2>
          </div>
          <div class="island-body">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">Academic Level</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['academic_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">Student Type</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $app['student_type'] ?? 'N/A')), ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">Grade/Year Level</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['grade_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">School Year</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['school_year'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">Semester</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['semester'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <?php if (($app['academic_level'] ?? '') === 'College' && ($app['grade_level'] ?? '') === '1st Year'): ?>
              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">NSTP Choice</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['nstp'] ?? 'Not Selected', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <?php endif; ?>
              <div class="col-md-6">
                <label class="text-muted small fw-semibold text-uppercase">Selected Program</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars(getStrandLabel($app['strand']), ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small fw-semibold text-uppercase">Application Curriculum</label>
                <div class="fw-medium text-dark"><?= $app['assigned_curriculum_version'] ? htmlspecialchars($app['assigned_curriculum_version'], ENT_QUOTES, 'UTF-8') : '<span class="text-warning fst-italic">Pending Assignment</span>' ?></div>
              </div>
              <div class="col-12 mt-2">
                <div class="p-2 bg-success bg-opacity-10 rounded border border-success border-opacity-25">
                  <label class="text-success small fw-semibold text-uppercase"><i class="bi bi-file-earmark-lock2-fill"></i> Official Student Curriculum</label>
                  <div class="fw-bold text-success-emphasis"><?= $app['user_curriculum_version'] ? htmlspecialchars($app['user_curriculum_version'], ENT_QUOTES, 'UTF-8') : '<span class="text-warning fst-italic">Pending First Enrollment</span>' ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <?php if (!empty($enrolledSubjects)): ?>
        <!-- Enrolled Subjects -->
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light d-flex justify-content-between align-items-center">
            <div>
              <i class="bi bi-journal-text"></i>
              <h2>Enrolled Subjects</h2>
            </div>
            <?php if (($app['student_type'] ?? '') === 'Irregular' && $app['status'] !== 'approved' && $app['status'] !== 'rejected'): ?>
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium" data-bs-toggle="modal" data-bs-target="#editSubjectsModal">
              <i class="bi bi-pencil-square"></i> Edit Subjects
            </button>
            <?php endif; ?>
          </div>
          <div class="island-body p-0 mt-2">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 custom-table">
                <thead class="table-light text-muted small text-uppercase">
                  <tr>
                    <th class="ps-4">Subject Code</th>
                    <th>Subject Name</th>
                    <th>Schedule</th>
                    <th>Type</th>
                    <th class="text-end pe-4">Units</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $totalUnits = 0;
                  foreach ($enrolledSubjects as $sub): 
                      $totalUnits += (int)$sub['units'];
                  ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($sub['subject_code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($sub['subject_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-primary small" style="font-size: 0.8rem;">
                        <?php if ($sub['section_code']): ?>
                            <span class="badge bg-secondary mb-1"><?= htmlspecialchars($sub['section_code'], ENT_QUOTES, 'UTF-8') ?></span><br>
                        <?php endif; ?>
                        <?= $sub['schedule_text'] ?: '<span class="text-muted fst-italic">No schedule</span>' ?>
                    </td>
                    <td>
                      <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle">
                        <?= htmlspecialchars($sub['subject_type'] ?? 'Subject', ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>
                    <td class="text-end pe-4"><?= htmlspecialchars((string)$sub['units'], ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <td colspan="3" class="text-end fw-bold text-dark">Total Units:</td>
                    <td class="text-end pe-4 fw-bold text-dark fs-5"><?= $totalUnits ?></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Educational History -->
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light">
            <i class="bi bi-building"></i>
            <h2>Educational History</h2>
          </div>
          <div class="island-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="text-muted small fw-semibold text-uppercase">Learner Reference Number (LRN)</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['lrn'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small fw-semibold text-uppercase">Previous School Name</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['last_school_attended'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-12">
                <label class="text-muted small fw-semibold text-uppercase">Previous School Address</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['last_school_address'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>

              <div class="col-12"><hr class="my-1 border-light"></div>

              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">Previous Level</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['previous_school_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">Strand / Course</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['previous_strand_course'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">Academic Year</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars(($app['academic_year_from'] && $app['academic_year_to']) ? $app['academic_year_from'] . ' - ' . $app['academic_year_to'] : 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">Status</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['previous_school_status'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">Year Graduated/Attended</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['last_school_year'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Family Background -->
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light">
            <i class="bi bi-people-fill"></i>
            <h2>Family Background</h2>
          </div>
          <div class="island-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Father's Name</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['father_name'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Father's Occupation</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['father_occupation'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Father's Contact</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['father_contact'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              
              <div class="col-12"><hr class="my-1 border-light"></div>

              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Mother's Name</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['mother_name'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Mother's Occupation</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['mother_occupation'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Mother's Contact</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['mother_contact'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>

              <div class="col-12"><hr class="my-1 border-light"></div>

              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Guardian's Name</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['guardian_name'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Relationship</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['guardian_relationship'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Guardian's Contact</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['guardian_contact'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Emergency & Medical Information -->
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light">
            <i class="bi bi-heart-pulse-fill"></i>
            <h2>Emergency & Medical Info</h2>
          </div>
          <div class="island-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Emergency Contact</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['emergency_contact_person'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Relationship</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['emergency_contact_relationship'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Contact Number</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['emergency_contact_number'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              
              <div class="col-12"><hr class="my-1 border-light"></div>

              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Medical Conditions</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['medical_conditions'] ?: 'None', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Allergies</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['allergies'] ?: 'None', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Special Needs</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['special_needs'] ?: 'None', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Documents -->
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light">
            <i class="bi bi-folder-fill"></i>
            <h2>Uploaded Documents</h2>
          </div>
          <div class="island-body">
            <?php if ($app['document_submission_method'] === 'on_campus'): ?>
               <div class="alert alert-secondary bg-light border-0 text-center py-4 mb-0">
                 <i class="bi bi-building fs-2 text-muted mb-2 d-block"></i>
                 <span class="fw-medium text-dark">On-Campus Submission</span>
                 <p class="text-muted small mb-0 mt-1">The applicant elected to present physical documents.</p>
               </div>
            <?php elseif (empty($documents)): ?>
               <p class="text-muted mb-0 small">No documents have been uploaded yet.</p>
            <?php else: ?>
               <ul class="list-group list-group-flush border rounded-3">
                 <?php foreach ($documents as $doc): ?>
                   <li class="list-group-item py-3">
                     <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                       <div>
                         <i class="bi bi-file-earmark-pdf text-danger me-2 fs-5"></i>
                         <span class="fw-semibold text-dark"><?= htmlspecialchars($doc['document_name'], ENT_QUOTES, 'UTF-8') ?></span>
                         <div class="small text-muted mt-1">Uploaded: <?= date('M j, Y g:i A', strtotime($doc['created_at'])) ?></div>
                       </div>
                       <div>
                         <a href="document_view.php?id=<?= $doc['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                           <i class="bi bi-eye"></i> View
                         </a>
                       </div>
                     </div>
                     
                     <div class="row g-2 mt-2 align-items-center bg-light p-2 rounded-3 border">
                       <div class="col-md-4">
                         <label class="small text-muted fw-bold mb-1">Verify Status</label>
                         <select name="doc_status[<?= $doc['id'] ?>]" class="form-select form-select-sm">
                           <option value="pending" <?= $doc['status'] === 'pending' ? 'selected' : '' ?>>Pending / Awaiting</option>
                           <option value="verified" <?= $doc['status'] === 'verified' ? 'selected' : '' ?>>Verified / Approved</option>
                           <option value="rejected" <?= $doc['status'] === 'rejected' ? 'selected' : '' ?>>Rejected / Needs Reupload</option>
                         </select>
                       </div>
                       <div class="col-md-8">
                         <label class="small text-muted fw-bold mb-1">Feedback Comment</label>
                         <input type="text" name="doc_feedback[<?= $doc['id'] ?>]" class="form-control form-control-sm" placeholder="e.g. Please upload clear copy of LRN card..." value="<?= htmlspecialchars($doc['feedback'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                       </div>
                     </div>
                   </li>
                 <?php endforeach; ?>
               </ul>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- Right Column: Administrative Action -->
      <div class="col-lg-4">
        
        <div class="island border-primary border-top border-4 sticky-top" style="top: 80px;">
          <div class="island-header bg-primary-light">
            <i class="bi bi-shield-lock-fill text-primary"></i>
            <h2 class="text-primary">Admin Action Panel</h2>
          </div>
          <div class="island-body">
            
              <?php if (hasPermission('enrollment.finalize') && empty($app['section_id'])): ?>
              <div class="mb-3 p-3 bg-white rounded border border-primary">
                <label for="assign_section" class="form-label fw-semibold small text-primary"><i class="bi bi-diagram-3 text-primary"></i> Assign Section</label>
                <select name="assign_section" id="assign_section" class="form-select form-select-sm">
                  <option value="">Do not assign section yet</option>
                  <?php foreach ($availableSections as $sec): 
                          $remaining = (int)$sec['capacity'] - (int)$sec['current_enrollment'];
                          $isFull = $remaining <= 0;
                  ?>
                    <option value="<?= $sec['id'] ?>" <?= $isFull ? 'disabled' : '' ?>>
                      <?= htmlspecialchars($sec['section_code'], ENT_QUOTES, 'UTF-8') ?> 
                      (<?= $sec['schedule_type'] ?> | <?= $remaining ?> slots left) <?= $isFull ? '[FULL]' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text" style="font-size: 0.7rem;">When assigned, the system will automatically retrieve the curriculum subjects and schedule for this student.</div>
                <div id="enrollmentSummaryPreview"></div>
              </div>
              <?php endif; ?>

              <?php if (!empty($app['section_id'])): ?>
              <div class="mb-3">
                 <label class="form-label fw-semibold small text-dark">Assigned Section</label>
                 <?php 
                    $currSec = array_filter($availableSections, fn($s) => $s['id'] == $app['section_id']);
                    $currSec = reset($currSec);
                 ?>
                 <div class="form-control form-control-sm bg-light text-muted">
                    <?= $currSec ? htmlspecialchars($currSec['section_code'], ENT_QUOTES, 'UTF-8') : 'Unknown Section ID: ' . $app['section_id'] ?>
                 </div>
              </div>
              <?php endif; ?>

              <div class="mb-3">
                <label for="status" class="form-label fw-semibold small text-dark">Update Status</label>
                <select name="status" id="status" class="form-select form-select-sm" required>
                  <option value="pending" <?= $app['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="under_review" <?= $app['status'] === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                  <option value="correction_required" <?= $app['status'] === 'correction_required' ? 'selected' : '' ?>>Correction Required</option>
                  <option value="approved" <?= $app['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                  <option value="rejected" <?= $app['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                  <?php if (hasPermission('enrollment.finalize') || $app['status'] === 'enrolled'): ?>
                  <option value="enrolled" <?= $app['status'] === 'enrolled' ? 'selected' : '' ?>>Officially Enrolled</option>
                  <?php endif; ?>
                </select>
              </div>

              <div class="mb-3">
                <label for="feedback" class="form-label fw-semibold small text-dark">Applicant Feedback (Visible to Student)</label>
                <textarea name="feedback" id="feedback" rows="3" class="form-control form-control-sm" placeholder="e.g. Please upload a clearer copy of your birth certificate."><?= htmlspecialchars($app['admin_feedback'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                <div class="form-text" style="font-size: 0.7rem;">Feedback will be recorded in the applicant's activity timeline and displayed on their dashboard.</div>
              </div>

              <div class="mb-4">
                <label for="internal_notes" class="form-label fw-semibold small text-dark">Internal Admin Notes (Admin Only)</label>
                <textarea name="internal_notes" id="internal_notes" rows="3" class="form-control form-control-sm" placeholder="Internal notes about the application..."><?= htmlspecialchars($app['internal_notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                <div class="form-text" style="font-size: 0.7rem;">These notes are only visible to system administrators.</div>
              </div>

              <?php if (!$assessment): ?>
              <div class="mb-4 p-3 bg-white rounded border border-warning">
                <div class="form-check form-switch mb-2">
                  <input class="form-check-input" type="checkbox" role="switch" id="generate_assessment" name="generate_assessment" value="1" <?= $app['status'] === 'approved' ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold small text-dark" for="generate_assessment"><i class="bi bi-cash-stack text-warning"></i> Auto-Generate Assessment</label>
                </div>
                <div class="form-text" style="font-size: 0.7rem;">The system will automatically assign the correct fee template for <strong><?= htmlspecialchars($app['grade_level'] ?? '', ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($app['strand'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>. This is required before they can apply for scholarships.</div>
              </div>
              <?php else: ?>
              <div class="mb-4 p-3 bg-white rounded border border-success">
                <div class="fw-semibold small text-dark"><i class="bi bi-check-circle-fill text-success"></i> Assessment Generated</div>
                <div class="small text-muted mt-1">
                  Net Amount: <strong>₱<?= number_format((float)$assessment['net_amount'], 2) ?></strong><br>
                  Discount: ₱<?= number_format((float)$assessment['discount_amount'], 2) ?>
                </div>
              </div>
              <?php endif; ?>

              <div class="d-grid">
                <button type="submit" class="btn btn-primary fw-medium rounded-pill shadow-sm">
                  <i class="bi bi-save2 me-1"></i> Save Changes
                </button>
              </div>

          </div>
        </div>

      </div>

      </div>
    </form>
  </div>
</div>

<?php if (($app['student_type'] ?? '') === 'Irregular'): 
    // Fetch all active subjects to show in the dropdown
    $stmtAllSubs = $pdo->query("SELECT id, subject_code, subject_name, units FROM subjects WHERE status = 1 ORDER BY subject_code ASC");
    $allSubjects = $stmtAllSubs->fetchAll(PDO::FETCH_ASSOC);
    
    // Get currently enrolled subject IDs
    $currentSubIds = array_column($enrolledSubjects, 'id');
?>
<!-- Edit Subjects Modal -->
<div class="modal fade" id="editSubjectsModal" tabindex="-1" aria-labelledby="editSubjectsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content border-0 shadow" method="POST" action="application_process.php">
      <input type="hidden" name="action" value="update_subjects">
      <input type="hidden" name="application_id" value="<?= $applicationId ?>">
      
      <div class="modal-header border-bottom-0 bg-primary bg-opacity-10">
        <h5 class="modal-title fw-bold text-primary-emphasis" id="editSubjectsModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Irregular Subjects</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 bg-light">
        <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-warning-emphasis d-flex align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
          <div>You are modifying the subjects for an <strong>Irregular Student</strong>. These changes will overwrite their current subject list.</div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-bold text-dark">Add a Subject</label>
          <div class="input-group">
            <select class="form-select" id="subjectSelect">
              <option value="" disabled selected>Select a subject to add...</option>
              <?php foreach ($allSubjects as $sub): ?>
                <option value="<?= $sub['id'] ?>" data-code="<?= htmlspecialchars($sub['subject_code'], ENT_QUOTES) ?>" data-name="<?= htmlspecialchars($sub['subject_name'], ENT_QUOTES) ?>" data-units="<?= $sub['units'] ?>">
                  <?= htmlspecialchars($sub['subject_code'] . ' - ' . $sub['subject_name'], ENT_QUOTES) ?> (<?= $sub['units'] ?> Units)
                </option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-primary px-4" type="button" id="btnAddSubject"><i class="bi bi-plus-lg"></i> Add</button>
          </div>
        </div>

        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">Current Subject List</h6>
        <div class="table-responsive bg-white rounded-3 border shadow-sm">
          <table class="table table-hover align-middle mb-0 custom-table" id="editSubjectsTable">
            <thead class="table-light text-muted small text-uppercase">
              <tr>
                <th class="ps-3">Code</th>
                <th>Name</th>
                <th class="text-center">Units</th>
                <th class="text-center pe-3">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($enrolledSubjects)): ?>
              <tr class="empty-row">
                <td colspan="4" class="text-center py-4 text-muted">No subjects currently assigned.</td>
              </tr>
              <?php else: ?>
                <?php foreach ($enrolledSubjects as $sub): ?>
                <tr>
                  <td class="ps-3 fw-bold text-dark align-middle">
                    <?= htmlspecialchars($sub['subject_code'], ENT_QUOTES) ?>
                    <input type="hidden" name="subjects[<?= $sub['subject_id'] ?>]" value="<?= $sub['section_id'] ?? '' ?>">
                  </td>
                  <td class="align-middle">
                    <?= htmlspecialchars($sub['subject_name'], ENT_QUOTES) ?>
                    <div class="text-primary mt-1" style="font-size: 0.65rem;"><?= $sub['schedule_text'] ?></div>
                  </td>
                  <td class="text-center align-middle unit-val" data-units="<?= $sub['units'] ?>"><?= $sub['units'] ?></td>
                  <td class="text-center pe-3 align-middle">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle remove-sub-btn"><i class="bi bi-trash"></i></button>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
            <tfoot class="table-light border-top">
              <tr>
                <td colspan="2" class="text-end fw-bold text-dark">Total Units:</td>
                <td class="text-center fw-bold text-primary fs-5" id="modalTotalUnits">0</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="modal-footer border-top-0 bg-white">
        <button type="button" class="btn btn-outline-secondary px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary px-4 rounded-pill fw-medium"><i class="bi bi-save me-2"></i>Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const tableBody = document.querySelector('#editSubjectsTable tbody');
  const btnAdd = document.getElementById('btnAddSubject');
  const select = document.getElementById('subjectSelect');
  const totalDisplay = document.getElementById('modalTotalUnits');

  function updateTotal() {
    let total = 0;
    document.querySelectorAll('#editSubjectsTable tbody .unit-val').forEach(td => {
      total += parseInt(td.getAttribute('data-units') || 0);
    });
    totalDisplay.textContent = total;
  }

  function attachRemoveEvent(btn) {
    btn.addEventListener('click', function() {
      this.closest('tr').remove();
      if (tableBody.querySelectorAll('tr').length === 0) {
        tableBody.innerHTML = '<tr class="empty-row"><td colspan="4" class="text-center py-4 text-muted">No subjects currently assigned.</td></tr>';
      }
      updateTotal();
    });
  }

  document.querySelectorAll('.remove-sub-btn').forEach(attachRemoveEvent);
  updateTotal();

  if(btnAdd) {
    btnAdd.addEventListener('click', function() {
      const option = select.options[select.selectedIndex];
      if (!option.value) return;

      const id = option.value;
      const code = option.getAttribute('data-code');
      const name = option.getAttribute('data-name');
      const units = option.getAttribute('data-units');

      // Check if already exists (by ID)
      let exists = false;
      document.querySelectorAll('#editSubjectsTable input[name^="subjects["]').forEach(inp => {
        // The name is subjects[ID]
        const match = inp.name.match(/subjects\[(\d+)\]/);
        if (match && match[1] === id) exists = true;
      });

      if (exists) {
        alert('This exact subject ID is already in the list.');
        return;
      }
      
      // Check for equivalent subject codes or names in the table
      let equivalentExists = false;
      document.querySelectorAll('#editSubjectsTable tbody tr:not(.empty-row)').forEach(row => {
          const rowCode = row.cells[0].textContent.trim().toLowerCase().replace(/[^a-z0-9]/g, '');
          const rowName = row.cells[1].textContent.trim().toLowerCase();
          const newCode = code.toLowerCase().replace(/[^a-z0-9]/g, '');
          const newName = name.toLowerCase().trim();
          
          if (rowCode === newCode || rowName.includes(newName) || newName.includes(rowName)) {
              equivalentExists = true;
          }
      });
      
      if (equivalentExists) {
          if (!confirm('A subject with a very similar code or name is already in the list. Are you sure you want to add this?')) {
              return;
          }
      }

      // Remove empty row if present
      const emptyRow = tableBody.querySelector('.empty-row');
      if (emptyRow) emptyRow.remove();

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="ps-3 fw-bold text-dark align-middle">
          ${code}
          <input type="hidden" name="subjects[${id}]" value="">
        </td>
        <td class="align-middle">
            ${name}
            <div class="text-primary mt-1" style="font-size: 0.65rem;"><i class="text-muted fst-italic">Schedule to be decided</i></div>
        </td>
        <td class="text-center align-middle unit-val" data-units="${units}">${units}</td>
        <td class="text-center pe-3 align-middle">
          <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle remove-sub-btn"><i class="bi bi-trash"></i></button>
        </td>
      `;
      tableBody.appendChild(tr);
      attachRemoveEvent(tr.querySelector('.remove-sub-btn'));
      updateTotal();
      
      select.value = '';
    });
  }
});
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const assignSectionEl = document.getElementById('assign_section');
  const previewEl = document.getElementById('enrollmentSummaryPreview');
  const statusEl = document.getElementById('status');
  const generateAssessmentEl = document.getElementById('generate_assessment');

  if (assignSectionEl && previewEl) {
    assignSectionEl.addEventListener('change', function() {
      const sectionId = this.value;
      if (!sectionId) {
        previewEl.innerHTML = '';
        return;
      }

      previewEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm" role="status"></div><span class="ms-2 small text-muted">Retrieving curriculum...</span></div>';

      fetch(`../ajax/get_enrollment_summary.php?section_id=${sectionId}&app_id=<?= $appId ?>`)
        .then(response => response.text())
        .then(html => {
          previewEl.innerHTML = html;
        })
        .catch(error => {
          previewEl.innerHTML = '<div class="alert alert-danger mb-0">Failed to load summary.</div>';
          console.error('Error fetching enrollment summary:', error);
        });
    });
  }

  if (statusEl && generateAssessmentEl) {
    statusEl.addEventListener('change', function() {
      if (this.value === 'approved') {
        generateAssessmentEl.checked = true;
      }
    });
  }
});
</script>

</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

