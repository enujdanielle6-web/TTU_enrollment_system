<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

$pageTitle = 'Applicant Dashboard - Triple T University';
$userId = (int) $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Applicant';
$application = null;
$fetchError = null;

// User details fallbacks
$user_first_name = '';
$user_last_name = '';
$user_email = '';
$user_created_at = '';

if (isset($_SESSION['error_msg'])) {
    $fetchError = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

$documents = [];
$activities = [];
$announcements = [];

try {
    $statement = $pdo->prepare(
        'SELECT
            a.id,
            a.reference_number,
            a.status,
            a.academic_level,
            a.semester,
            a.nstp,
            a.grade_level,
            a.school_year,
            a.strand,
            a.contact_number,
            a.birth_date,
            a.gender,
            a.address,
            a.guardian_name,
            a.guardian_contact,
            a.document_submission_method,
            a.created_at,
            a.updated_at,
            u.first_name,
            u.last_name,
            u.email,
            u.student_number,
            u.created_at as user_created_at
         FROM applications a
         INNER JOIN users u ON u.id = a.user_id
         WHERE a.user_id = :user_id
         ORDER BY a.created_at DESC
         LIMIT 1'
    );
    $statement->execute(['user_id' => $userId]);
    $application = $statement->fetch() ?: null;
    
    if ($application === null) {
        $userStmt = $pdo->prepare('SELECT first_name, last_name, email, student_number, created_at FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => $userId]);
        $userBasic = $userStmt->fetch();
        if ($userBasic) {
            $user_first_name = $userBasic['first_name'];
            $user_last_name = $userBasic['last_name'];
            $user_email = $userBasic['email'];
            $user_student_number = $userBasic['student_number'] ?? null;
            $user_created_at = $userBasic['created_at'];
        }
    } else {
        $user_first_name = $application['first_name'];
        $user_last_name = $application['last_name'];
        $user_email = $application['email'];
        $user_student_number = $application['student_number'] ?? null;
        $user_created_at = $application['user_created_at'];
        
        $docStmt = $pdo->prepare('SELECT * FROM application_documents WHERE application_id = :app_id ORDER BY created_at DESC');
        $docStmt->execute(['app_id' => $application['id']]);
        $documents = $docStmt->fetchAll();
    }
    
    $actStmt = $pdo->prepare('SELECT * FROM activity_logs WHERE user_id = :user_id ORDER BY created_at DESC');
    $actStmt->execute(['user_id' => $userId]);
    $activities = $actStmt->fetchAll();
    
    $annStmt = $pdo->query('SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC');
    $announcements = $annStmt->fetchAll();
    
} catch (PDOException $exception) {
    error_log('Applicant dashboard fetch failed: ' . $exception->getMessage());
    $fetchError = 'Unable to load your dashboard. Please try again later.';
}

$statusLabel = $application ? formatApplicationStatus($application['status']) : 'Not Submitted';
$statusBadgeClass = $application ? getApplicationStatusBadgeClass($application['status']) : 'bg-secondary';
$statusMessage = $application ? getApplicationStatusMessage($application['status']) : 'You have not submitted an enrollment application yet.';

// Fetch timestamps & timeline steps
$timestamps = getApplicationTimestamps($userId);
// Check if user has uploaded any documents
$hasUploadedDocs = false;
if ($application && ($application['document_submission_method'] ?? 'online') === 'online') {
    try {
        $docStmt = $pdo->prepare('SELECT COUNT(*) FROM application_documents WHERE application_id = :app_id AND status != "rejected"');
        $docStmt->execute(['app_id' => $application['id']]);
        $hasUploadedDocs = $docStmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        $hasUploadedDocs = false;
    }
}

$timelineSteps = getApplicationTimelineSteps($application ? $application['status'] : 'not_started', $application['document_submission_method'] ?? 'online', $timestamps, $hasUploadedDocs);

// Calculate overall completion percentage
// Step 1: Application Form Submitted -> 25% (Total 25%)
// Step 2: Document Requirements -> Up to +50% (Max 75%)
// Step 3: Officially Enrolled -> +25% (Max 100%)
$completionPercentage = 0;
$detailedChecklist = [];

if ($application) {
    $completionPercentage += 25;
    $detailedChecklist = getDetailedChecklist((int)$application['id']);
    
    if ($application['document_submission_method'] === 'on_campus') {
        $completionPercentage += 50;
    } else {
        $uploadedCount = 0;
        foreach ($detailedChecklist as $doc) {
            if ($doc['status'] === 'Uploaded' || $doc['status'] === 'Verified') {
                $uploadedCount++;
            }
        }
        $totalRequired = count($detailedChecklist);
        if ($totalRequired > 0) {
            $completionPercentage += (int) (($uploadedCount / $totalRequired) * 50);
        }
    }
    
    if ($application['status'] === 'enrolled') {
        $completionPercentage += 25;
    }
}

// Fetch health status to refine timeline
$healthStatus = null;
if ($application && in_array($application['status'], ['approved', 'enrolled'])) {
    try {
        $hStmt = $pdo->prepare('SELECT status FROM health_records WHERE user_id = :user_id LIMIT 1');
        $hStmt->execute(['user_id' => $userId]);
        $healthStatus = $hStmt->fetchColumn();
        
        // Refine timeline steps based on health status
        if ($application['status'] === 'approved') {
            foreach ($timelineSteps as &$step) {
                if ($step['key'] === 'health_info') {
                    if ($healthStatus) {
                        $step['state'] = 'completed';
                    } else {
                        $step['state'] = 'active';
                    }
                }
                if ($step['key'] === 'medical_clearance') {
                    if ($healthStatus === 'verified') {
                        $step['state'] = 'completed';
                    } elseif ($healthStatus) {
                        $step['state'] = 'active';
                    }
                }
                if ($step['key'] === 'scholarship') {
                    if ($healthStatus === 'verified') {
                        $step['state'] = 'active';
                        // Check if they applied for a scholarship
                        $scholAppStmt = $pdo->prepare('SELECT status FROM scholarship_applications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1');
                        $scholAppStmt->execute(['user_id' => $userId]);
                        $scholStatus = $scholAppStmt->fetchColumn();
                        if ($scholStatus) {
                            $step['state'] = 'completed';
                        }
                    }
                }
                if ($step['key'] === 'cashier') {
                    // Check if they have an assessment
                    $assStmt = $pdo->prepare('SELECT id, payment_status FROM student_assessments WHERE application_id = :app_id LIMIT 1');
                    $assStmt->execute(['app_id' => $application['id']]);
                    $assessment = $assStmt->fetch();
                    if ($assessment) {
                        if (in_array($assessment['payment_status'], ['paid', 'partial'])) {
                            $step['state'] = 'completed';
                        } else {
                            $step['state'] = 'active';
                        }
                        // If assessment is generated, scholarship step is optionally skipped/completed
                        foreach ($timelineSteps as &$innerStep) {
                            if ($innerStep['key'] === 'scholarship' && $innerStep['state'] !== 'completed') {
                                $innerStep['state'] = 'completed';
                            }
                        }
                        unset($innerStep);
                    }
                }
            }
            unset($step);
        }
} catch (PDOException $e) {
        // Ignore
    }
}

// Fetch global enrollment status
$globalEnrollStatus = getSystemSetting($pdo, 'enrollment_status', 'open');

$completionPercentage = min($completionPercentage, 100);

require_once __DIR__ . '/../components/header.php';
?>

<?php require_once __DIR__ . '/components/navbar.php'; ?>

<main class="py-5 bg-light min-vh-100">
  <div class="container">
    <?php if ($fetchError !== null): ?>
      <div class="alert alert-danger shadow-sm rounded-12">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= htmlspecialchars($fetchError, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php else: ?>
      
      <!-- Welcome Hero Island -->
      <div class="island island-hero mb-4">
        <div class="row align-items-center g-3">
          <div class="col-md-8">
            <?php if ($application && !empty($application['school_year'])): ?>
              <span class="badge bg-primary-light text-primary mb-2 fw-semibold px-3 py-2 rounded-pill">
                <i class="bi bi-mortarboard-fill me-1"></i> Academic Year <?= htmlspecialchars($application['school_year'], ENT_QUOTES, 'UTF-8'); ?>
              </span>
            <?php endif; ?>
            <h1 class="h3 mb-1 text-dark fw-bold">Welcome, <?= htmlspecialchars($user_first_name . ' ' . $user_last_name, ENT_QUOTES, 'UTF-8'); ?>!</h1>
            <p class="text-muted mb-0">
              <?php if ($application === null): ?>
                Your account is ready. Get started by submitting your enrollment application today.
              <?php else: ?>
                Track your enrollment application progress and review your academic details below.
              <?php endif; ?>
            </p>
          </div>
          <div class="col-md-4 text-md-end">
            <p class="text-muted small mb-1">
              <i class="bi bi-clock me-1"></i> Today is <?= date('F j, Y'); ?>
            </p>
            <?php if ($application !== null): ?>
              <span class="badge <?= htmlspecialchars($statusBadgeClass, ENT_QUOTES, 'UTF-8'); ?> px-3 py-2 rounded-12 status-badge">
                Status: <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
              </span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Enrollment Completion Tracker -->
        <div class="mt-4 p-3 bg-white bg-opacity-75 rounded-12 shadow-sm border border-white">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-dark small fw-bold"><i class="bi bi-percent me-1 text-primary"></i>Enrollment Completion Progress</span>
            <span class="badge bg-primary rounded-pill"><?= $completionPercentage ?>% Complete</span>
          </div>
          <div class="progress" style="height: 10px; background-color: rgba(0, 0, 0, 0.05);">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: <?= $completionPercentage ?>%" aria-valuenow="<?= $completionPercentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <div class="d-flex justify-content-between text-muted small mt-2" style="font-size: 0.75rem;">
            <span>Account Created (15%)</span>
            <span>Form Submitted (50%)</span>
            <span>Documents Completed (100%)</span>
          </div>
        </div>

        <!-- System Notifications / Alerts inside Hero -->
        <div class="mt-4">
          <?php if ($application === null): ?>
            <?php if ($globalEnrollStatus === 'closed'): ?>
              <div class="alert alert-secondary border-0 shadow-sm rounded-12 p-3 d-flex align-items-center gap-3 mb-0">
                <div class="bg-secondary text-white rounded-circle p-2 flex-shrink-0" style="width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center;">
                  <i class="bi bi-door-closed-fill"></i>
                </div>
                <div>
                  <h4 class="h6 mb-1 fw-bold">Enrollment is Closed</h4>
                  <p class="mb-0 text-muted small">The university is not currently accepting new enrollment applications. Please check back later.</p>
                </div>
              </div>
            <?php else: ?>
              <div class="alert alert-warning border-0 shadow-sm rounded-12 p-3 d-flex align-items-center gap-3 mb-0">
                <div class="admission-icon bg-warning text-dark rounded-circle p-2 flex-shrink-0" style="width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center;">
                  <i class="bi bi-info-circle-fill"></i>
                </div>
                <div>
                  <h4 class="h6 mb-1 fw-bold">Action Required: Complete Enrollment</h4>
                  <p class="mb-0 text-muted small">You have not submitted an enrollment application yet. Click the "Start Enrollment" action to begin your admissions process.</p>
                </div>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <?php if ($application['status'] === 'correction_required'): ?>
              <div class="alert alert-danger border-0 shadow-sm rounded-12 p-3 d-flex align-items-center gap-3 mb-0">
                <div class="bg-danger text-white rounded-circle p-2 flex-shrink-0" style="width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center;">
                  <i class="bi bi-exclamation-octagon-fill"></i>
                </div>
                <div>
                  <h4 class="h6 mb-1 fw-bold">Correction Required</h4>
                  <p class="mb-0 small">The admissions office has reviewed your profile and requested a correction. Please review feedback in your application detail page.</p>
                </div>
              </div>
            <?php elseif ($application['status'] === 'approved'): ?>
              <div class="alert alert-success border-0 shadow-sm rounded-12 p-3 d-flex align-items-center gap-3 mb-0">
                <div class="bg-success text-white rounded-circle p-2 flex-shrink-0" style="width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center;">
                  <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                  <h4 class="h6 mb-1 fw-bold">Application Approved</h4>
                  <p class="mb-0 small">Congratulations! Your application has been approved. Please follow the next steps in your timeline.</p>
                </div>
              </div>
              
              <!-- Health Info Prompt -->
              <?php if (!$healthStatus): ?>
                  <div class="alert alert-warning border-0 shadow-sm rounded-12 p-3 d-flex align-items-center gap-3 mt-3 mb-0">
                    <div class="bg-warning text-dark rounded-circle p-2 flex-shrink-0" style="width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center;">
                      <i class="bi bi-heart-pulse-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                      <h4 class="h6 mb-1 fw-bold">Action Required: Health Information</h4>
                      <p class="mb-0 small text-muted">You must submit your health information and get medical clearance to proceed to enrollment.</p>
                    </div>
                    <div>
                      <a href="health_info.php" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">Submit Now</a>
                    </div>
                  </div>
              <?php elseif (in_array($healthStatus, ['pending', 'under_review', 'correction_required', 'rejected'])): ?>
                  <div class="alert alert-info border-0 shadow-sm rounded-12 p-3 d-flex align-items-center gap-3 mt-3 mb-0">
                    <div class="bg-info text-dark rounded-circle p-2 flex-shrink-0" style="width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center;">
                      <i class="bi bi-file-medical-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                      <h4 class="h6 mb-1 fw-bold">Medical Clearance: <?= ucfirst(str_replace('_', ' ', $healthStatus)) ?></h4>
                      <p class="mb-0 small text-muted">Please check your Health Information page for updates or remarks from the clinic.</p>
                    </div>
                    <div>
                      <a href="health_info.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 bg-white">View Status</a>
                    </div>
                  </div>
              <?php endif; ?>
            <?php elseif ($application['status'] === 'enrolled'): ?>
              <div class="alert alert-success border-0 shadow-sm rounded-12 p-3 d-flex align-items-center gap-3 mb-0">
                <div class="bg-success text-white rounded-circle p-2 flex-shrink-0" style="width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center;">
                  <i class="bi bi-patch-check-fill"></i>
                </div>
                <div>
                  <h4 class="h6 mb-1 fw-bold">Successfully Enrolled</h4>
                  <?php
                    $activeYr = getSystemSetting($pdo, 'active_school_year', '2026-2027');
                  ?>
                  <p class="mb-0 small">Welcome! You are officially enrolled for the Academic Year <?= htmlspecialchars($activeYr, ENT_QUOTES, 'UTF-8') ?>.</p>
                </div>
              </div>
            <?php elseif ($application['status'] === 'rejected'): ?>
              <div class="alert alert-danger border-0 shadow-sm rounded-12 p-3 d-flex align-items-center gap-3 mb-0">
                <div class="bg-danger text-white rounded-circle p-2 flex-shrink-0" style="width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center;">
                  <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                  <h4 class="h6 mb-1 fw-bold">Application Rejected</h4>
                  <p class="mb-0 small">Your application was not approved. Please contact the admissions office for further inquiries.</p>
                </div>
              </div>
            <?php else: ?>
              <div class="alert alert-info border-0 shadow-sm rounded-12 p-3 d-flex align-items-center gap-3 mb-0">
                <div class="bg-info text-white rounded-circle p-2 flex-shrink-0" style="width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center;">
                  <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                  <h4 class="h6 mb-1 fw-bold">Application Under Review</h4>
                  <p class="mb-0 small text-dark">The admissions team is currently checking your submitted details. We will notify you of updates here.</p>
                </div>
              </div>
            <?php endif; ?>

            <!-- General System Reminder Placeholder -->
            <div class="alert alert-primary border-0 shadow-sm rounded-12 p-3 d-flex align-items-center gap-3 mt-3 mb-0">
              <div class="bg-primary text-white rounded-circle p-2 flex-shrink-0" style="width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center;">
                <i class="bi bi-bell-fill"></i>
              </div>
              <div>
                <h4 class="h6 mb-1 fw-bold">Admissions Reminder</h4>
                <p class="mb-0 small text-primary-dark">Verify that all your uploaded documents are legible. Please present original copies on-site upon request.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Application Progress Tracker (Full Width Top) -->
      <div class="island mb-4 overflow-hidden">
        <div class="island-header">
          <i class="bi bi-geo-alt-fill"></i>
          <h2>Application Progress</h2>
        </div>
        <div class="island-body p-4">
          <div class="position-relative d-flex justify-content-between align-items-start w-100">
            <!-- Progress Line -->
            <div class="progress position-absolute start-0 w-100" style="height: 4px; z-index: 1; top: 18px; left: 7% !important; width: 86% !important;">
              <?php
                // Calculate how many steps are completed to set progress width
                $completedSteps = 0;
                foreach($timelineSteps as $s) {
                    if($s['state'] === 'completed') $completedSteps++;
                }
                $progressWidth = (count($timelineSteps) > 1) ? ($completedSteps / (count($timelineSteps) - 1)) * 100 : 0;
              ?>
              <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $progressWidth ?>%"></div>
            </div>
            
            <?php foreach ($timelineSteps as $index => $step): ?>
              <?php
              $stepState = $step['state'];
              $iconClass = match ($stepState) {
                  'completed' => 'bg-primary text-white border-primary',
                  'active' => 'bg-warning text-dark border-warning shadow',
                  'rejected' => 'bg-danger text-white border-danger',
                  default => 'bg-white text-muted border-secondary'
              };
              $icon = match ($stepState) {
                  'completed' => 'bi-check-lg',
                  'active' => 'bi-hourglass-split',
                  'rejected' => 'bi-x-lg',
                  default => 'bi-circle-fill'
              };
              ?>
              <div class="text-center position-relative" style="z-index: 2; width: 14%;">
                <div class="rounded-circle border border-2 mx-auto d-flex align-items-center justify-content-center <?= $iconClass ?>" style="width: 40px; height: 40px; transition: all 0.3s;">
                  <i class="bi <?= $icon ?>"></i>
                </div>
                <div class="mt-2">
                  <p class="mb-0 fw-bold small lh-sm <?= $stepState === 'active' ? 'text-primary' : ($stepState === 'pending' ? 'text-muted' : 'text-dark') ?>"><?= htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Status Overview Island -->
      <div class="island mb-4">
        <div class="island-header">
          <i class="bi bi-bar-chart-fill"></i>
          <h2>Applicant Overview</h2>
        </div>
        <div class="island-body">
          <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3">
            <!-- Item 1: Application Status -->
            <div class="col">
              <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-12 h-100 border border-light">
                <div class="bg-primary-light text-primary rounded-12 p-3 flex-shrink-0" style="width: 48px; height: 48px; display: inline-flex; align-items: center; justify-content: center;">
                  <i class="bi bi-shield-check fs-5"></i>
                </div>
                <div>
                  <p class="text-muted small mb-0 lh-sm">Current Status</p>
                  <p class="mb-0 fw-bold text-dark small mt-1"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
              </div>
            </div>

            <!-- Item 2: Reference Number -->
            <div class="col">
              <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-12 h-100 border border-light">
                <div class="bg-primary-light text-primary rounded-12 p-3 flex-shrink-0" style="width: 48px; height: 48px; display: inline-flex; align-items: center; justify-content: center;">
                  <i class="bi bi-upc-scan fs-5"></i>
                </div>
                <div>
                  <p class="text-muted small mb-0 lh-sm">Application Reference No.</p>
                  <p class="mb-0 fw-bold text-dark small mt-1"><?= $application ? htmlspecialchars($application['reference_number'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">N/A</span>'; ?></p>
                </div>
              </div>
            </div>

            <!-- Item 3: School Year -->
            <div class="col">
              <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-12 h-100 border border-light">
                <div class="bg-primary-light text-primary rounded-12 p-3 flex-shrink-0" style="width: 48px; height: 48px; display: inline-flex; align-items: center; justify-content: center;">
                  <i class="bi bi-calendar-range fs-5"></i>
                </div>
                <div>
                  <p class="text-muted small mb-0 lh-sm">School Year</p>
                  <p class="mb-0 fw-bold text-dark small mt-1"><?= $application && !empty($application['school_year']) ? htmlspecialchars($application['school_year'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">Not Set</span>'; ?></p>
                </div>
              </div>
            </div>

            <!-- Item 4: Program/Grade Level -->
            <div class="col">
              <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-12 h-100 border border-light">
                <div class="bg-primary-light text-primary rounded-12 p-3 flex-shrink-0" style="width: 48px; height: 48px; display: inline-flex; align-items: center; justify-content: center;">
                  <i class="bi bi-mortarboard fs-5"></i>
                </div>
                <div>
                  <p class="text-muted small mb-0 lh-sm">Program Strand</p>
                  <p class="mb-0 fw-bold text-dark small mt-1"><?= $application && $application['strand'] ? htmlspecialchars(strtoupper($application['strand']), ENT_QUOTES, 'UTF-8') : '<span class="text-muted">Not Selected</span>'; ?></p>
                </div>
              </div>
            </div>

            <!-- Item 5: Last Updated -->
            <div class="col">
              <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-12 h-100 border border-light">
                <div class="bg-primary-light text-primary rounded-12 p-3 flex-shrink-0" style="width: 48px; height: 48px; display: inline-flex; align-items: center; justify-content: center;">
                  <i class="bi bi-calendar3 fs-5"></i>
                </div>
                <div>
                  <p class="text-muted small mb-0 lh-sm">Last Updated</p>
                  <p class="mb-0 fw-bold text-dark small mt-1"><?= $application ? htmlspecialchars(date('M j, Y', strtotime($application['updated_at'])), ENT_QUOTES, 'UTF-8') : '<span class="text-muted">N/A</span>'; ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Workspace Grid -->
      <div class="row g-4">
        
        <!-- Left Side Column -->
        <div class="col-lg-8">
          

          


          <!-- Applicant Information Summary -->
          <div class="island">
            <div class="island-header">
              <i class="bi bi-person-lines-fill"></i>
              <h2>Applicant Profile Summary</h2>
            </div>
            <div class="island-body mt-2">
              <?php if ($application === null): ?>
                <div class="text-center py-5">
                  <div class="empty-state-icon bg-light text-muted rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-card-text fs-2"></i>
                  </div>
                  <h3 class="h6 fw-bold text-dark">No Application Profile</h3>
                  <p class="text-muted small mb-4">Complete the enrollment form to generate your student profile summary.</p>
                  <a class="btn btn-primary btn-sm px-4 rounded-pill" href="enroll.php">
                    <i class="bi bi-pencil-square me-1"></i> Start Enrollment Form
                  </a>
                </div>
              <?php else: ?>
                <div class="row g-3">
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">Full Name</p>
                    <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($user_first_name . ' ' . $user_last_name, ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">Email Address</p>
                    <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">Student Number</p>
                    <p class="mb-0 fw-semibold text-dark"><?= $user_student_number ? htmlspecialchars($user_student_number, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">Not Assigned</span>'; ?></p>
                  </div>
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">Contact Number</p>
                    <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($application['contact_number'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">Application Reference No.</p>
                    <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($application['reference_number'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">Grade Level</p>
                    <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($application['grade_level'] ?? 'Not Set', ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">Strand or Course</p>
                    <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars(getStrandLabel($application['strand'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">School Year</p>
                    <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($application['school_year'] ?? 'Not Set', ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">Semester</p>
                    <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($application['semester'] ?? 'Not Set', ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <?php if (($application['academic_level'] ?? '') === 'College' && ($application['grade_level'] ?? '') === '1st Year'): ?>
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">NSTP Choice</p>
                    <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($application['nstp'] ?? 'Not Selected', ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <?php endif; ?>
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">Gender</p>
                    <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars(ucfirst($application['gender'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">Birth Date</p>
                    <p class="mb-0 fw-semibold text-dark"><?= $application['birth_date'] ? htmlspecialchars(date('F j, Y', strtotime($application['birth_date'])), ENT_QUOTES, 'UTF-8') : '—'; ?></p>
                  </div>
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">Guardian Name</p>
                    <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($application['guardian_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <div class="col-md-6 col-lg-4">
                    <p class="text-muted small mb-1">Guardian Contact</p>
                    <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($application['guardian_contact'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <div class="col-12">
                    <p class="text-muted small mb-1">Complete Home Address</p>
                    <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($application['address'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Recent Activity Timeline (Placeholders) -->
          <div class="island mb-4">
            <div class="island-header">
              <i class="bi bi-activity"></i>
              <h2>Recent Activity</h2>
            </div>
            <div class="island-body mt-2">
              <div class="list-group list-group-flush">
                <?php if (empty($activities)): ?>
                  <div class="text-center py-4">
                    <i class="bi bi-clock-history text-muted fs-1 mb-3"></i>
                    <p class="text-muted mb-0">No recent activity found.</p>
                  </div>
                <?php else: ?>
                  <?php foreach ($activities as $index => $activity): ?>
                    <div class="list-group-item bg-transparent border-0 px-0 <?= $index > 0 ? 'pt-3 pb-3 border-top border-light' : 'pb-3' ?>">
                      <div class="d-flex w-100 justify-content-between mb-1">
                        <h6 class="mb-0 fw-bold"><i class="bi <?= htmlspecialchars($activity['icon'], ENT_QUOTES, 'UTF-8'); ?> text-primary me-2"></i><?= htmlspecialchars($activity['title'], ENT_QUOTES, 'UTF-8'); ?></h6>
                        <small class="text-muted"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($activity['created_at'])), ENT_QUOTES, 'UTF-8'); ?></small>
                      </div>
                      <?php if (!empty($activity['description'])): ?>
                        <p class="mb-0 text-muted small"><?= htmlspecialchars($activity['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>

        </div>

        <!-- Right Side Column -->
        <div class="col-lg-4">
          


          <!-- Required Documents Checklist -->
          <div class="island">
            <div class="island-header">
              <i class="bi bi-file-earmark-check"></i>
              <h2>Required Documents</h2>
            </div>
            <div class="island-body mt-2">
              <?php if ($application !== null && $application['document_submission_method'] === 'on_campus'): ?>
                <div class="text-center py-4">
                  <i class="bi bi-building text-primary fs-1 mb-3"></i>
                  <p class="text-muted mb-0 small">You opted for On-Campus verification. Please present your original documents at the admissions office.</p>
                </div>
              <?php elseif ($application === null): ?>
                <div class="text-center py-4">
                  <i class="bi bi-file-earmark-x text-muted fs-1 mb-3"></i>
                  <p class="text-muted mb-0">Submit your enrollment form to unlock the document checklist.</p>
                </div>
              <?php else: ?>
                <?php
                $verifiedCount = 0;
                $uploadedCount = 0;
                foreach ($detailedChecklist as $doc) {
                    if ($doc['status'] === 'Verified') {
                        $verifiedCount++;
                        $uploadedCount++;
                    } elseif ($doc['status'] === 'Uploaded') {
                        $uploadedCount++;
                    }
                }
                $docPercent = (int) (($uploadedCount / 4) * 100);
                $verPercent = (int) (($verifiedCount / 4) * 100);
                ?>
                <div class="mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold">Upload Progress</span>
                    <span class="text-dark small fw-bold"><?= $docPercent ?>%</span>
                  </div>
                  <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?= $docPercent ?>%" aria-valuenow="<?= $docPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                </div>
                
                <div class="mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold">Verification Progress</span>
                    <span class="text-dark small fw-bold"><?= $verPercent ?>%</span>
                  </div>
                  <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $verPercent ?>%" aria-valuenow="<?= $verPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                </div>

                <ul class="list-group list-group-flush small mt-3">
                  <?php foreach ($detailedChecklist as $doc): ?>
                    <?php
                      $docStatus = $doc['status'];
                      $docIcon = match($docStatus) {
                          'Verified' => 'bi-check-circle-fill text-success',
                          'Needs Reupload' => 'bi-exclamation-circle-fill text-danger',
                          'Uploaded' => 'bi-hourglass-split text-info',
                          default => 'bi-circle text-muted'
                      };
                      $docBadge = match($docStatus) {
                          'Verified' => 'bg-success',
                          'Needs Reupload' => 'bg-danger',
                          'Uploaded' => 'bg-info text-dark',
                          default => 'bg-secondary text-white'
                      };
                    ?>
                    <li class="list-group-item bg-transparent px-0 py-2 border-light">
                      <div class="d-flex justify-content-between align-items-center">
                        <span><i class="bi <?= $docIcon ?> me-2"></i><?= htmlspecialchars($doc['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="badge <?= $docBadge ?>"><?= htmlspecialchars($docStatus, ENT_QUOTES, 'UTF-8'); ?></span>
                      </div>
                      <?php if (!empty($doc['feedback'])): ?>
                        <div class="text-muted mt-1 ps-4" style="font-size: 0.75rem;">
                          <i class="bi bi-chat-left-text me-1 text-warning"></i>
                          <?= htmlspecialchars($doc['feedback'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                      <?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>

          <!-- School Announcements -->
          <div class="island mb-4">
            <div class="island-header">
              <i class="bi bi-megaphone"></i>
              <h2>Announcements</h2>
            </div>
            <div class="island-body mt-2">
              <?php if (empty($announcements)): ?>
                <div class="text-center py-4">
                  <i class="bi bi-megaphone text-muted fs-1 mb-3"></i>
                  <p class="text-muted mb-0">No new announcements at this time.</p>
                </div>
              <?php else: ?>
                <?php foreach ($announcements as $index => $ann): ?>
                  <div class="<?= $index > 0 ? 'mb-3 border-top pt-3 border-light' : 'mb-3' ?>">
                    <span class="badge bg-<?= htmlspecialchars($ann['badge_color'], ENT_QUOTES, 'UTF-8'); ?><?= in_array($ann['badge_color'], ['warning', 'info']) ? ' text-dark' : ''; ?> mb-1 small"><?= htmlspecialchars($ann['badge_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <h6 class="fw-bold mb-1 small text-dark"><?= htmlspecialchars($ann['title'], ENT_QUOTES, 'UTF-8'); ?></h6>
                    <p class="text-muted mb-0" style="font-size: 0.8rem;"><?= nl2br(htmlspecialchars($ann['content'], ENT_QUOTES, 'UTF-8')); ?></p>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

        </div>

      </div>

    <?php endif; ?>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
