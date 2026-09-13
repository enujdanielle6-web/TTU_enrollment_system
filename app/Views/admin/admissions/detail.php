<?php
$successMsg = $successMsg ?? $_SESSION['admin_success'] ?? null;
$errorMsg = $errorMsg ?? $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$statusLabel = formatApplicationStatus($app['status']);
$badgeClass = getApplicationStatusBadgeClass($app['status']);
$pageTitle = 'Review Application ' . htmlspecialchars($app['reference_number'], ENT_QUOTES, 'UTF-8') . ' - Admin';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
?>
<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Top Header -->
    <div class="dossier-hero-strip mb-4 fade-in-up">
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
          <div class="d-flex align-items-center gap-2 mb-2">
            <?php 
              if (isset($_SERVER['HTTP_REFERER']) && 
                  str_contains($_SERVER['HTTP_REFERER'], '/admin/') && 
                  !str_contains($_SERVER['HTTP_REFERER'], 'application_detail.php') && 
                  !str_contains($_SERVER['HTTP_REFERER'], 'application_process.php')) {
                  $_SESSION['app_detail_back_url'] = $_SERVER['HTTP_REFERER'];
              }
              $backUrl = isset($_SESSION['app_detail_back_url']) 
                  ? htmlspecialchars($_SESSION['app_detail_back_url'], ENT_QUOTES, 'UTF-8') 
                  : 'review.php';
            ?>
            <a href="<?= esc($backUrl) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 0.78rem;">
              <i class="bi bi-arrow-left"></i>
              <span>Back to List</span>
            </a>
            <span class="badge bg-light text-secondary border rounded-pill px-3 py-1 font-monospace" style="font-size: 0.75rem;">
              <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($app['reference_number'], ENT_QUOTES, 'UTF-8') ?>
            </span>
          </div>
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <h1 class="h3 fw-bold text-dark mb-0">Review Application</h1>
            <span class="badge <?= esc($badgeClass) ?> rounded-pill px-3 py-1 fs-6 fw-semibold align-middle shadow-sm">
              <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
            </span>
          </div>
          <div class="text-muted small mt-1">
            Applicant: <strong class="text-dark"><?= htmlspecialchars($app['last_name'] . ', ' . $app['first_name'], ENT_QUOTES, 'UTF-8') ?></strong>
            <?php if (!empty($app['created_at'])): ?>
              <span class="mx-1 text-black-50">•</span> Submitted on <?= date('M j, Y g:i A', strtotime($app['created_at'])) ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 fw-semibold" style="font-size: 0.8rem;">
            <i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($app['academic_level'] ?? 'College', ENT_QUOTES, 'UTF-8') ?>
          </span>
          <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-2 fw-semibold" style="font-size: 0.8rem;">
            <i class="bi bi-bookmark me-1"></i><?= htmlspecialchars($app['strand'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          </span>
        </div>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form action="application_process.php" method="POST">
      <input type="hidden" name="application_id" value="<?= esc($app['id']) ?>">
      <input type="hidden" name="user_id" value="<?= esc($app['user_id']) ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      
      <div class="row g-4">
        
        <!-- Left Column: Application Details -->
        <div class="col-lg-8">
        
        <!-- Personal Information -->
        <div class="dossier-card mb-4 fade-in-up">
          <div class="dossier-card-header">
            <div class="d-flex align-items-center gap-3">
              <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-person-vcard-fill"></i>
              </div>
              <div>
                <h2 class="h6 fw-bold text-dark mb-0">Personal Information</h2>
                <small class="text-muted" style="font-size: 0.72rem;">Primary identity and demographic data</small>
              </div>
            </div>
          </div>
          <div class="dossier-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-person"></i> Full Name</span>
                  <div class="info-tile-value"><?= htmlspecialchars($app['last_name'] . ', ' . $app['first_name'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-envelope"></i> Email Address</span>
                  <div class="info-tile-value text-break"><?= htmlspecialchars($app['email'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-person-badge"></i> Student Number</span>
                  <div class="info-tile-value">
                    <?= $app['student_number'] ? htmlspecialchars($app['student_number'], ENT_QUOTES, 'UTF-8') : '<span class="badge bg-light text-muted border rounded-pill px-2 py-1 fw-normal">Not Assigned</span>' ?>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-calendar3"></i> Date of Birth</span>
                  <div class="info-tile-value"><?= htmlspecialchars($app['birth_date'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-gender-ambiguous"></i> Gender</span>
                  <div class="info-tile-value"><?= htmlspecialchars(ucfirst($app['gender'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-telephone"></i> Contact No.</span>
                  <div class="info-tile-value"><?= htmlspecialchars($app['contact_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <div class="col-md-8">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-geo-alt"></i> Complete Address</span>
                  <div class="info-tile-value">
                    <?php 
                      $fullAddress = trim(($app['address_house_number'] ?? '') . ' ' . ($app['address'] ?? ''));
                      echo htmlspecialchars($fullAddress !== '' ? $fullAddress : 'N/A', ENT_QUOTES, 'UTF-8');
                    ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Academic Information -->
        <div class="dossier-card mb-4 fade-in-up">
          <div class="dossier-card-header">
            <div class="d-flex align-items-center gap-3">
              <div class="dossier-header-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-mortarboard-fill"></i>
              </div>
              <div>
                <h2 class="h6 fw-bold text-dark mb-0">Enrollment Details</h2>
                <small class="text-muted" style="font-size: 0.72rem;">Academic classification, term, and curriculum track</small>
              </div>
            </div>
          </div>
          <div class="dossier-card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-mortarboard"></i> Academic Level</span>
                  <div class="info-tile-value"><?= htmlspecialchars($app['academic_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-person-gear"></i> Student Type</span>
                  <div class="info-tile-value"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $app['student_type'] ?? 'N/A')), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-award"></i> Grade/Year Level</span>
                  <div class="info-tile-value"><?= htmlspecialchars($app['grade_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-calendar-event"></i> School Year</span>
                  <div class="info-tile-value"><?= htmlspecialchars($app['school_year'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-clock-history"></i> Semester</span>
                  <div class="info-tile-value"><?= htmlspecialchars($app['semester'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <?php if (($app['academic_level'] ?? '') === 'College' && ($app['grade_level'] ?? '') === '1st Year'): ?>
              <div class="col-md-3">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-shield-check"></i> NSTP Choice</span>
                  <div class="info-tile-value"><?= htmlspecialchars($app['nstp'] ?? 'Not Selected', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <?php endif; ?>
              <div class="col-md-6">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-book"></i> Selected Program</span>
                  <div class="info-tile-value"><?= htmlspecialchars(getStrandLabel($app['strand'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-file-earmark-code"></i> Application Curriculum</span>
                  <div class="info-tile-value">
                    <?= !empty($app['assigned_curriculum_version']) 
                        ? htmlspecialchars($app['assigned_curriculum_version'], ENT_QUOTES, 'UTF-8') 
                        : '<span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-1 small fw-normal">Pending Assignment</span>' ?>
                  </div>
                </div>
              </div>
              <div class="col-12 mt-2">
                <div class="p-3 bg-success bg-opacity-10 rounded-3 border border-success border-opacity-25 d-flex align-items-center justify-content-between flex-wrap gap-2">
                  <div>
                    <span class="text-success text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;"><i class="bi bi-file-earmark-lock2-fill me-1"></i> Official Student Curriculum</span>
                    <div class="fw-bold text-success-emphasis fs-6 mt-1">
                      <?= !empty($app['user_curriculum_version']) ? htmlspecialchars($app['user_curriculum_version'], ENT_QUOTES, 'UTF-8') : '<span class="text-secondary small fst-italic">Pending First Enrollment</span>' ?>
                    </div>
                  </div>
                  <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 small">Binding Record</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <?php 
        $displaySubs = !empty($enrolledSubjects) ? $enrolledSubjects : ($requestedSubjects ?? []);
        $isRequested = empty($enrolledSubjects) && !empty($requestedSubjects);
        $isIrregular = ($app['student_type'] ?? '') === 'Irregular';
        ?>
        <?php if (!empty($displaySubs) || $isIrregular): ?>
        <!-- Subjects Section -->
        <div class="dossier-card mb-4 fade-in-up">
          <div class="dossier-card-header flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
              <div class="dossier-header-icon <?= $isRequested ? 'bg-warning bg-opacity-15 text-warning' : 'bg-primary bg-opacity-10 text-primary' ?>">
                <i class="bi <?= $isRequested ? 'bi-clipboard-check-fill' : 'bi-journal-text' ?>"></i>
              </div>
              <div>
                <h2 class="h6 fw-bold text-dark mb-0"><?= $isRequested ? 'Requested Subjects (Irregular Student)' : 'Enrolled Subjects' ?></h2>
                <?php if ($isRequested): ?>
                  <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-50 rounded-pill px-2 py-0 small mt-1">Awaiting Evaluation</span>
                <?php else: ?>
                  <small class="text-muted" style="font-size: 0.72rem;">Academic load and timetabled classes</small>
                <?php endif; ?>
              </div>
            </div>
            <?php if ($isIrregular && $app['status'] !== 'enrolled' && $app['status'] !== 'rejected'): ?>
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium" data-bs-toggle="modal" data-bs-target="#editSubjectsModal">
              <i class="bi bi-pencil-square me-1"></i> Edit Subjects
            </button>
            <?php endif; ?>
          </div>
          <div class="p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 custom-table">
                <thead class="table-light text-muted small text-uppercase">
                  <tr>
                    <th class="ps-4">Subject Code</th>
                    <th>Subject Name</th>
                    <th>Section / Schedule</th>
                    <th>Type</th>
                    <th class="text-end pe-4">Units</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($displaySubs)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                      <em>No subjects requested or enrolled yet. Click "Edit Subjects" to assign subjects.</em>
                    </td>
                  </tr>
                  <?php else: ?>
                    <?php 
                    $totalUnits = 0;
                    foreach ($displaySubs as $sub): 
                        $totalUnits += (int)$sub['units'];
                    ?>
                    <tr>
                      <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($sub['subject_code'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="fw-medium text-dark"><?= htmlspecialchars($sub['subject_name'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="text-primary small" style="font-size: 0.8rem;">
                          <?php if (!empty($sub['section_code'])): ?>
                              <span class="badge bg-secondary mb-1"><?= htmlspecialchars($sub['section_code'], ENT_QUOTES, 'UTF-8') ?></span><br>
                          <?php endif; ?>
                          <?= !empty($sub['schedule_text']) ? esc($sub['schedule_text']) : '<span class="text-muted fst-italic">Standard Schedule</span>' ?>
                      </td>
                      <td>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle rounded-pill px-2 py-1">
                          <?= htmlspecialchars($sub['subject_type'] ?? 'Curriculum', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                      </td>
                      <td class="text-end pe-4 fw-semibold text-primary"><?= htmlspecialchars((string)$sub['units'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
                <?php if (!empty($displaySubs)): ?>
                <tfoot class="table-light">
                  <tr>
                    <td colspan="3" class="text-end fw-bold text-dark ps-4">Total Academic Units:</td>
                    <td class="text-end pe-4 fw-bold text-dark fs-6" colspan="2"><?= esc($totalUnits) ?> Units</td>
                  </tr>
                </tfoot>
                <?php endif; ?>
              </table>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Educational History -->
        <div class="dossier-card mb-4 fade-in-up">
          <div class="dossier-card-header">
            <div class="d-flex align-items-center gap-3">
              <div class="dossier-header-icon bg-secondary bg-opacity-10 text-secondary">
                <i class="bi bi-building"></i>
              </div>
              <div>
                <h2 class="h6 fw-bold text-dark mb-0">Educational History</h2>
                <small class="text-muted" style="font-size: 0.72rem;">Previous schooling and learner credentials</small>
              </div>
            </div>
          </div>
          <div class="dossier-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-upc-scan"></i> Learner Reference Number (LRN)</span>
                  <div class="info-tile-value font-monospace"><?= !empty($app['lrn']) ? htmlspecialchars($app['lrn'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-building"></i> Previous School Name</span>
                  <div class="info-tile-value"><?= !empty($app['previous_school']) ? htmlspecialchars($app['previous_school'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-tag"></i> Previous School Type</span>
                  <div class="info-tile-value"><?= !empty($app['previous_school_type']) ? htmlspecialchars($app['previous_school_type'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-calendar-range"></i> Last School Year Attended</span>
                  <div class="info-tile-value"><?= !empty($app['previous_school_year']) ? htmlspecialchars($app['previous_school_year'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Family & Guardian Background -->
        <div class="dossier-card mb-4 fade-in-up">
          <div class="dossier-card-header">
            <div class="d-flex align-items-center gap-3">
              <div class="dossier-header-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-people-fill"></i>
              </div>
              <div>
                <h2 class="h6 fw-bold text-dark mb-0">Family & Guardian Information</h2>
                <small class="text-muted" style="font-size: 0.72rem;">Authorized parent or primary guardian contacts</small>
              </div>
            </div>
          </div>
          <div class="dossier-card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-person"></i> Primary Guardian / Parent</span>
                  <div class="info-tile-value"><?= !empty($app['guardian_name']) ? htmlspecialchars($app['guardian_name'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-heart"></i> Relationship to Applicant</span>
                  <div class="info-tile-value"><?= !empty($app['guardian_relationship']) ? htmlspecialchars($app['guardian_relationship'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-telephone"></i> Guardian Contact Number</span>
                  <div class="info-tile-value"><?= !empty($app['guardian_contact']) ? htmlspecialchars($app['guardian_contact'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Emergency & Medical Information -->
        <div class="dossier-card mb-4 fade-in-up">
          <div class="dossier-card-header">
            <div class="d-flex align-items-center gap-3">
              <div class="dossier-header-icon bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-heart-pulse-fill"></i>
              </div>
              <div>
                <h2 class="h6 fw-bold text-dark mb-0">Emergency & Medical Info</h2>
                <small class="text-muted" style="font-size: 0.72rem;">Health profile and designated emergency point of contact</small>
              </div>
            </div>
          </div>
          <div class="dossier-card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-person-exclamation"></i> Emergency Contact</span>
                  <div class="info-tile-value"><?= !empty($app['emergency_name']) ? htmlspecialchars($app['emergency_name'], ENT_QUOTES, 'UTF-8') : (!empty($health['emergency_name']) ? htmlspecialchars($health['emergency_name'], ENT_QUOTES, 'UTF-8') : 'N/A') ?></div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-link-45deg"></i> Relationship</span>
                  <div class="info-tile-value"><?= !empty($app['emergency_relationship']) ? htmlspecialchars($app['emergency_relationship'], ENT_QUOTES, 'UTF-8') : (!empty($health['emergency_relationship']) ? htmlspecialchars($health['emergency_relationship'], ENT_QUOTES, 'UTF-8') : 'N/A') ?></div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-telephone-forward"></i> Contact Number</span>
                  <div class="info-tile-value"><?= !empty($app['emergency_contact']) ? htmlspecialchars($app['emergency_contact'], ENT_QUOTES, 'UTF-8') : (!empty($health['emergency_contact']) ? htmlspecialchars($health['emergency_contact'], ENT_QUOTES, 'UTF-8') : 'N/A') ?></div>
                </div>
              </div>
              
              <div class="col-md-3">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-rulers"></i> Height &amp; Weight</span>
                  <div class="info-tile-value">
                    <?= !empty($health['height']) ? htmlspecialchars($health['height']) . ' cm' : '—' ?> / 
                    <?= !empty($health['weight']) ? htmlspecialchars($health['weight']) . ' kg' : '—' ?>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-droplet-half text-danger"></i> Blood Type</span>
                  <div class="info-tile-value"><?= !empty($health['blood_type']) ? htmlspecialchars($health['blood_type'], ENT_QUOTES, 'UTF-8') : '—' ?></div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-bandaid"></i> Allergies</span>
                  <div class="info-tile-value"><?= !empty($health['allergies_details']) ? htmlspecialchars($health['allergies_details'], ENT_QUOTES, 'UTF-8') : (!empty($health['has_allergies']) ? 'Yes' : 'None') ?></div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-shield-check"></i> Medical Clearance</span>
                  <div class="info-tile-value mt-1">
                    <?php 
                      $hStatus = $health['status'] ?? 'pending';
                      $hBadge = match($hStatus) {
                        'verified' => 'bg-success text-white',
                        'rejected' => 'bg-danger text-white',
                        'correction_required' => 'bg-warning text-dark',
                        default => 'bg-secondary bg-opacity-10 text-secondary'
                      };
                    ?>
                    <span class="badge <?= esc($hBadge) ?> rounded-pill px-3 py-1 text-uppercase" style="font-size: 0.7rem;"><?= ucfirst(htmlspecialchars($hStatus, ENT_QUOTES, 'UTF-8')) ?></span>
                  </div>
                </div>
              </div>
              <?php if (!empty($health['medical_conditions'])): ?>
              <div class="col-12">
                <div class="info-tile">
                  <span class="info-tile-label"><i class="bi bi-journal-medical"></i> Declared Medical Conditions</span>
                  <div class="info-tile-value"><?= htmlspecialchars($health['medical_conditions'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Documents -->
        <div class="dossier-card mb-4 fade-in-up">
          <div class="dossier-card-header flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
              <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-folder-fill"></i>
              </div>
              <div>
                <h2 class="h6 fw-bold text-dark mb-0">Uploaded Documents</h2>
                <small class="text-muted" style="font-size: 0.72rem;">Submitted credentials and verification status</small>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#adminUploadDocModal">
              <i class="bi bi-cloud-arrow-up"></i>
              <span>Upload Document</span>
            </button>
          </div>
          <div class="dossier-card-body">
            <?php if ($app['document_submission_method'] === 'on_campus'): ?>
               <div class="alert alert-secondary bg-light border-0 d-flex align-items-center justify-content-between flex-wrap gap-2 py-3 px-3 mb-3 rounded-3">
                 <div class="d-flex align-items-center gap-3">
                   <i class="bi bi-building fs-3 text-primary"></i>
                   <div>
                     <span class="fw-semibold text-dark d-block">On-Campus Physical Submission Mode</span>
                     <p class="text-muted small mb-0">The applicant elected to present physical documents in person. Admissions staff can upload scanned copies below.</p>
                   </div>
                 </div>
                 <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#adminUploadDocModal">
                   <i class="bi bi-plus-lg me-1"></i> Attach Document
                 </button>
               </div>
            <?php endif; ?>

            <?php if (empty($documents)): ?>
               <div class="text-center py-4 bg-light rounded-3 border">
                 <i class="bi bi-file-earmark-x fs-2 text-muted d-block mb-2"></i>
                 <p class="text-muted mb-2 small">No documents have been uploaded for this application yet.</p>
                 <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#adminUploadDocModal">
                   <i class="bi bi-cloud-upload me-1"></i> Upload First Document
                 </button>
               </div>
            <?php else: ?>
               <ul class="list-group list-group-flush border rounded-3 mb-0">
                 <?php foreach ($documents as $doc): 
                    $docExt = strtolower(pathinfo($doc['file_path'] ?? '', PATHINFO_EXTENSION));
                    $isPdf = $docExt === 'pdf';
                    $iconClass = $isPdf ? 'bi-file-earmark-pdf-fill text-danger' : 'bi-file-earmark-image-fill text-primary';
                    $statusBadge = match($doc['status'] ?? 'pending') {
                      'verified' => 'bg-success text-white',
                      'rejected' => 'bg-danger text-white',
                      default => 'bg-warning text-dark'
                    };
                    $viewUrl = "/sia/admin/admissions/document_view.php?id=" . (int)$doc['id'];
                    $docNameEsc = htmlspecialchars($doc['document_name'], ENT_QUOTES, 'UTF-8');
                 ?>
                   <li class="list-group-item py-3">
                     <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                       <div class="d-flex align-items-center gap-3">
                         <div class="fs-3"><i class="bi <?= esc($iconClass) ?>"></i></div>
                         <div>
                           <div class="d-flex align-items-center gap-2 flex-wrap">
                             <span class="fw-bold text-dark"><?= $docNameEsc ?></span>
                             <span class="badge <?= esc($statusBadge) ?> rounded-pill text-uppercase px-2" style="font-size: 0.65rem;"><?= ucfirst(htmlspecialchars($doc['status'] ?? 'pending', ENT_QUOTES, 'UTF-8')) ?></span>
                           </div>
                           <div class="small text-muted mt-1">
                             <i class="bi bi-clock me-1"></i> Uploaded: <?= !empty($doc['created_at']) ? date('M j, Y g:i A', strtotime($doc['created_at'])) : 'N/A' ?>
                           </div>
                         </div>
                       </div>
                       <div class="d-flex align-items-center gap-2">
                         <a href="<?= esc($viewUrl) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 preview-doc-btn" 
                           data-doc-id="<?= esc($doc['id']) ?>" 
                           data-doc-name="<?= $docNameEsc ?>" 
                           data-doc-url="<?= esc($viewUrl) ?>"
                           data-doc-ext="<?= esc($docExt) ?>"
                           onclick="event.preventDefault(); window.previewDocument('<?= esc($doc['id']) ?>', '<?= addslashes($doc['document_name']) ?>', '<?= esc($viewUrl) ?>', '<?= esc($docExt) ?>');">
                           <i class="bi bi-eye me-1"></i> View
                         </a>
                         <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 replace-doc-btn" 
                           data-doc-name="<?= $docNameEsc ?>"
                           onclick="window.openReplaceModal('<?= addslashes($doc['document_name']) ?>');">
                           <i class="bi bi-arrow-repeat me-1"></i> Replace
                         </button>
                       </div>
                     </div>
                     
                     <div class="row g-2 mt-2 align-items-center bg-light p-2 rounded-3 border">
                       <div class="col-md-4">
                         <label class="small text-muted fw-bold mb-1" style="font-size: 0.72rem;">Verify Status</label>
                         <select name="doc_status[<?= esc($doc['id']) ?>]" class="form-select form-select-sm rounded-3">
                           <option value="pending" <?= esc(($doc['status'] ?? '') === 'pending' ? 'selected' : '') ?>>Pending / Awaiting</option>
                           <option value="verified" <?= esc(($doc['status'] ?? '') === 'verified' ? 'selected' : '') ?>>Verified / Approved</option>
                           <option value="rejected" <?= esc(($doc['status'] ?? '') === 'rejected' ? 'selected' : '') ?>>Rejected / Needs Reupload</option>
                         </select>
                       </div>
                       <div class="col-md-8">
                         <label class="small text-muted fw-bold mb-1" style="font-size: 0.72rem;">Feedback Comment</label>
                         <input type="text" name="doc_feedback[<?= esc($doc['id']) ?>]" class="form-control form-control-sm rounded-3" placeholder="e.g. Please upload clear copy of LRN card..." value="<?= htmlspecialchars($doc['feedback'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
        
        <div class="action-console-card sticky-top" style="top: 85px;">
          <div class="action-console-header d-flex align-items-center gap-3">
            <div class="rounded-3 bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px; flex-shrink: 0; background: linear-gradient(135deg, #0d6efd 0%, #1d4ed8 100%);">
              <i class="bi bi-shield-lock-fill fs-5"></i>
            </div>
            <div>
              <h6 class="fw-bold text-dark mb-0">Admin Action Panel</h6>
              <small class="text-muted" style="font-size: 0.72rem;">Evaluation & Status Workflow</small>
            </div>
          </div>
          <div class="p-4">
            
              <?php if (hasPermission('enrollment.finalize') && empty($app['section_id'])): ?>
              <div class="mb-3 p-3 bg-light rounded-3 border border-primary border-opacity-25">
                <label for="assign_section" class="form-label fw-semibold small text-primary mb-1"><i class="bi bi-diagram-3-fill me-1"></i> Assign Section</label>
                <select name="assign_section" id="assign_section" class="form-select form-select-sm rounded-3">
                  <option value="">Do not assign section yet</option>
                  <?php foreach ($availableSections as $sec): 
                          $remaining = (int)$sec['capacity'] - (int)$sec['current_enrollment'];
                          $isFull = $remaining <= 0;
                  ?>
                    <option value="<?= esc($sec['id']) ?>" <?= esc($isFull ? 'disabled' : '') ?>>
                      <?= htmlspecialchars($sec['section_code'], ENT_QUOTES, 'UTF-8') ?> 
                      (<?= esc($sec['schedule_type']) ?> | <?= esc($remaining) ?> slots left) <?= esc($isFull ? '[FULL]' : '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text text-muted mt-1" style="font-size: 0.7rem;">When assigned, the system will automatically retrieve the curriculum subjects and schedule for this student.</div>
                <div id="enrollmentSummaryPreview"></div>
              </div>
              <?php endif; ?>

              <?php if (!empty($app['section_id'])): ?>
              <div class="mb-3">
                 <label class="form-label fw-semibold small text-dark mb-1">Assigned Section</label>
                 <?php 
                    $currSec = array_filter($availableSections, fn($s) => $s['id'] == $app['section_id']);
                    $currSec = reset($currSec);
                 ?>
                 <div class="p-2 px-3 bg-light rounded-3 border fw-semibold text-dark d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-diagram-3 text-primary me-2"></i><?= $currSec ? htmlspecialchars($currSec['section_code'], ENT_QUOTES, 'UTF-8') : 'Unknown Section ID: ' . $app['section_id'] ?></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill small">Current</span>
                 </div>
              </div>
              <?php endif; ?>

              <div class="mb-3">
                <label for="status" class="form-label fw-semibold small text-dark mb-1">Update Status</label>
                <select name="status" id="status" class="form-select form-select-sm rounded-3 py-2 fw-medium" required <?= esc($app['status'] === 'enrolled' ? 'disabled' : '') ?>>
                  <?php if ($app['status'] === 'enrolled'): ?>
                    <option value="enrolled" selected>Officially Enrolled</option>
                  <?php else: ?>
                    <option value="under_review" <?= esc($app['status'] === 'under_review' ? 'selected' : '') ?> <?= esc($app['status'] === 'approved' ? 'disabled' : '') ?>>Under Review</option>
                    <option value="correction_required" <?= esc($app['status'] === 'correction_required' ? 'selected' : '') ?> <?= esc($app['status'] === 'approved' ? 'disabled' : '') ?>>Correction Required</option>
                    <option value="approved" <?= esc($app['status'] === 'approved' ? 'selected' : '') ?>>Approved</option>
                    <option value="rejected" <?= esc($app['status'] === 'rejected' ? 'selected' : '') ?> <?= esc($app['status'] === 'approved' ? 'disabled' : '') ?>>Rejected</option>
                    <?php if (hasPermission('enrollment.finalize')): ?>
                    <option value="enrolled">Officially Enrolled</option>
                    <?php endif; ?>
                  <?php endif; ?>
                </select>
                <?php if ($app['status'] === 'enrolled'): ?>
                  <input type="hidden" name="status" value="enrolled">
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label for="feedback" class="form-label fw-semibold small text-dark mb-0"><i class="bi bi-chat-left-text text-primary me-1"></i> Applicant Feedback</label>
                  <span class="badge bg-light text-muted border rounded-pill" style="font-size: 0.65rem;">Visible to Student</span>
                </div>
                <textarea name="feedback" id="feedback" rows="3" class="form-control form-control-sm rounded-3" placeholder="e.g. Please upload a clearer copy of your birth certificate."><?= htmlspecialchars($app['admin_feedback'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                <div class="form-text text-muted" style="font-size: 0.7rem;">Feedback will be recorded in the applicant's activity timeline and displayed on their dashboard.</div>
              </div>

              <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label for="internal_notes" class="form-label fw-semibold small text-dark mb-0"><i class="bi bi-lock-fill text-secondary me-1"></i> Internal Admin Notes</label>
                  <span class="badge bg-light text-muted border rounded-pill" style="font-size: 0.65rem;">Admin Only</span>
                </div>
                <textarea name="internal_notes" id="internal_notes" rows="3" class="form-control form-control-sm rounded-3" placeholder="Internal notes about the application..."><?= htmlspecialchars($app['internal_notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                <div class="form-text text-muted" style="font-size: 0.7rem;">These notes are only visible to system administrators.</div>
              </div>

              <?php if (!$assessment): ?>
              <div class="mb-4 p-3 bg-warning bg-opacity-10 rounded-3 border border-warning border-opacity-25">
                <div class="fw-semibold small text-dark mb-1"><i class="bi bi-cash-stack text-warning me-1"></i> Automated Assessment Notice</div>
                <div class="text-muted" style="font-size: 0.72rem; line-height: 1.5;">The system will automatically generate a fee assessment for <strong><?= htmlspecialchars($app['grade_level'] ?? '', ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($app['strand'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> as soon as this application is marked as <strong>Approved</strong>. This is required before the student can apply for scholarships.</div>
              </div>
              <?php else: ?>
              <div class="mb-4 p-3 bg-success bg-opacity-10 rounded-3 border border-success border-opacity-25">
                <div class="fw-semibold small text-success-emphasis d-flex align-items-center justify-content-between">
                  <span><i class="bi bi-check-circle-fill text-success me-1"></i> Assessment Generated</span>
                  <span class="badge bg-success bg-opacity-20 text-success rounded-pill px-2 py-1 small">Active</span>
                </div>
                <div class="small text-muted mt-2 pt-2 border-top border-success border-opacity-25 d-flex justify-content-between">
                  <span>Net Amount:</span>
                  <strong class="text-dark">₱<?= number_format((float)$assessment['net_amount'], 2) ?></strong>
                </div>
                <?php if ((float)$assessment['discount_amount'] > 0): ?>
                <div class="small text-muted d-flex justify-content-between mt-1">
                  <span>Discount:</span>
                  <span class="text-success fw-medium">-₱<?= number_format((float)$assessment['discount_amount'], 2) ?></span>
                </div>
                <?php endif; ?>
              </div>
              <?php endif; ?>

              <div class="d-grid">
                <button type="submit" class="btn btn-primary fw-bold rounded-pill py-2 shadow-sm d-flex align-items-center justify-content-center gap-2" style="background: linear-gradient(135deg, #0d6efd 0%, #1d4ed8 100%); border: none;">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Save Decision &amp; Changes</span>
                </button>
              </div>

          </div>
        </div>

      </div>

      </div>
    </form>
  </div>

<?php if (($app['student_type'] ?? '') === 'Irregular'): 
    $modalSubs = !empty($enrolledSubjects) ? $enrolledSubjects : ($requestedSubjects ?? []);
    $currentSubIds = array_column($modalSubs, 'subject_id');
?>
<!-- Edit Subjects Modal -->
<div class="modal fade" id="editSubjectsModal" tabindex="-1" aria-labelledby="editSubjectsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content border-0 shadow" method="POST" action="application_process.php">
      <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token'] ?? '') ?>">
      <input type="hidden" name="action" value="update_subjects">
      <input type="hidden" name="application_id" value="<?= esc($app['id']) ?>">
      
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
                <option value="<?= esc($sub['id']) ?>" data-code="<?= htmlspecialchars($sub['subject_code'], ENT_QUOTES) ?>" data-name="<?= htmlspecialchars($sub['subject_name'], ENT_QUOTES) ?>" data-units="<?= esc($sub['units']) ?>">
                  <?= htmlspecialchars($sub['subject_code'] . ' - ' . $sub['subject_name'], ENT_QUOTES) ?> (<?= esc($sub['units']) ?> Units)
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
              <?php if (empty($modalSubs)): ?>
              <tr class="empty-row">
                <td colspan="4" class="text-center py-4 text-muted">No subjects currently assigned or requested.</td>
              </tr>
              <?php else: ?>
                <?php foreach ($modalSubs as $sub): 
                    $subId = $sub['subject_id'] ?? $sub['id'];
                ?>
                <tr>
                  <td class="ps-3 fw-bold text-dark align-middle">
                    <?= htmlspecialchars($sub['subject_code'], ENT_QUOTES) ?>
                    <input type="hidden" name="subjects[<?= esc($subId) ?>]" value="<?= esc($sub['section_id'] ?? '') ?>">
                  </td>
                  <td class="align-middle">
                    <?= htmlspecialchars($sub['subject_name'], ENT_QUOTES) ?>
                    <?php if (!empty($sub['schedule_text'])): ?>
                    <div class="text-primary mt-1" style="font-size: 0.65rem;"><?= esc($sub['schedule_text']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="text-center align-middle unit-val" data-units="<?= esc($sub['units']) ?>"><?= esc($sub['units']) ?></td>
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

      fetch(`../ajax/get_enrollment_summary.php?section_id=${sectionId}&app_id=<?= esc($app['id']) ?>`)
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

<!-- Document Preview Modal -->
<div class="modal fade" id="docPreviewModal" tabindex="-1" aria-labelledby="docPreviewModalLabel" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content rounded-4 shadow-lg border-0 overflow-hidden">
      <div class="modal-header bg-dark text-white border-0 py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-file-earmark-text text-primary fs-4"></i>
          <div>
            <h5 class="modal-title fw-bold text-white mb-0" id="docPreviewModalLabel">Document Preview</h5>
            <small class="text-white-50" id="docPreviewSubtitle">Student Admission Document</small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a id="docPreviewNewTabBtn" href="#" target="_blank" class="btn btn-sm btn-outline-light rounded-pill px-3">
            <i class="bi bi-box-arrow-up-right me-1"></i> Open in Tab
          </a>
          <a id="docPreviewDownloadBtn" href="#" download class="btn btn-sm btn-light rounded-pill px-3 fw-medium">
            <i class="bi bi-download me-1"></i> Download
          </a>
          <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body p-0 bg-light d-flex justify-content-center align-items-center position-relative" style="min-height: 520px; max-height: 75vh; overflow: auto;">
        <div id="docPreviewSpinner" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="text-muted mt-2 small">Loading document preview...</p>
        </div>
        <div id="docPreviewContainer" class="w-100 h-100 d-flex justify-content-center align-items-center p-3">
          <!-- Dynamically inserted iframe or img -->
        </div>
      </div>
      <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between">
        <div class="text-muted small">
          <i class="bi bi-shield-check text-success me-1"></i> Official Student Admission Document Record
        </div>
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Admin Document Upload Modal -->
<div class="modal fade" id="adminUploadDocModal" tabindex="-1" aria-labelledby="adminUploadDocModalLabel" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow-lg border-0 overflow-hidden">
      <div class="modal-header bg-primary text-white border-0 py-3 px-4">
        <h5 class="modal-title fw-bold text-white mb-0" id="adminUploadDocModalLabel"><i class="bi bi-cloud-arrow-up me-2"></i>Upload Student Document</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/sia/admin/admissions/document_upload.php" method="POST" enctype="multipart/form-data" id="adminDocUploadForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="application_id" value="<?= esc($app['id']) ?>">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold small text-dark">Document Requirement Type</label>
            <select name="document_name" id="modalDocNameSelect" class="form-select" required>
              <option value="">-- Select Document Type --</option>
              <option value="Form 138 (Report Card)">Form 138 (Report Card)</option>
              <option value="Certificate of Good Moral Character">Certificate of Good Moral Character</option>
              <option value="PSA Birth Certificate">PSA Birth Certificate</option>
              <option value="2x2 ID Picture">2x2 ID Picture</option>
              <option value="Transcript of Records (TOR)">Transcript of Records (TOR)</option>
              <option value="Honorable Dismissal">Honorable Dismissal</option>
              <option value="Medical Certificate">Medical Certificate</option>
              <option value="Other Official Document">Other Official Document</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small text-dark">Select File (PDF, JPG, PNG, WEBP - Max 5MB)</label>
            <input type="file" name="document_file" id="modalDocFileInput" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
            <div class="form-text small">Accepted formats: PDF document or clear Image (Max 5MB).</div>
          </div>
          <div class="alert alert-info py-2 px-3 small mb-0 rounded-3">
            <i class="bi bi-info-circle me-1"></i> Documents uploaded by Admissions Staff will be automatically saved and marked as <strong>Verified</strong>.
          </div>
        </div>
        <div class="modal-footer bg-light border-top-0 py-3 px-4">
          <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 fw-medium" id="btnAdminUploadSubmit">
            <i class="bi bi-upload me-1"></i> Upload &amp; Verify
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
window.previewDocument = function(docId, docName, docUrl, docExt) {
  const previewModalEl = document.getElementById('docPreviewModal');
  const previewModalLabel = document.getElementById('docPreviewModalLabel');
  const previewSubtitle = document.getElementById('docPreviewSubtitle');
  const previewContainer = document.getElementById('docPreviewContainer');
  const previewSpinner = document.getElementById('docPreviewSpinner');
  const previewNewTabBtn = document.getElementById('docPreviewNewTabBtn');
  const previewDownloadBtn = document.getElementById('docPreviewDownloadBtn');

  if (previewModalLabel) previewModalLabel.textContent = docName || 'Document Preview';
  if (previewSubtitle) previewSubtitle.textContent = `Document ID #${docId}`;
  if (previewNewTabBtn) previewNewTabBtn.href = docUrl;
  if (previewDownloadBtn) {
    previewDownloadBtn.href = docUrl;
    previewDownloadBtn.setAttribute('download', `${(docName || 'document').replace(/[^a-zA-Z0-9_-]/g, '_')}`);
  }

  if (previewSpinner) previewSpinner.style.display = 'block';
  if (previewContainer) previewContainer.innerHTML = '';

  const ext = (docExt || '').toLowerCase();
  if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext)) {
    const img = document.createElement('img');
    img.className = 'img-fluid rounded shadow-sm';
    img.style.maxHeight = '70vh';
    img.style.maxWidth = '100%';
    img.style.objectFit = 'contain';
    img.src = docUrl;
    img.onload = function() {
      if (previewSpinner) previewSpinner.style.display = 'none';
    };
    img.onerror = function() {
      if (previewSpinner) previewSpinner.style.display = 'none';
      previewContainer.innerHTML = '<div class="alert alert-warning py-3 text-center"><i class="bi bi-exclamation-triangle me-2"></i>Unable to load preview directly. <a href="' + docUrl + '" target="_blank" class="alert-link">Open in new tab</a></div>';
    };
    previewContainer.appendChild(img);
  } else {
    const iframe = document.createElement('iframe');
    iframe.style.width = '100%';
    iframe.style.height = '70vh';
    iframe.style.border = 'none';
    iframe.src = docUrl;
    iframe.onload = function() {
      if (previewSpinner) previewSpinner.style.display = 'none';
    };
    previewContainer.appendChild(iframe);
  }

  if (previewModalEl) {
    const modal = bootstrap.Modal.getOrCreateInstance(previewModalEl);
    modal.show();
  }
};

window.openReplaceModal = function(docName) {
  const uploadModalEl = document.getElementById('adminUploadDocModal');
  const modalDocNameSelect = document.getElementById('modalDocNameSelect');

  if (modalDocNameSelect && docName) {
    let matched = false;
    for (let i = 0; i < modalDocNameSelect.options.length; i++) {
      if (modalDocNameSelect.options[i].value.toLowerCase() === docName.toLowerCase()) {
        modalDocNameSelect.selectedIndex = i;
        matched = true;
        break;
      }
    }
    if (!matched) {
      modalDocNameSelect.value = docName;
    }
  }

  if (uploadModalEl) {
    const modal = bootstrap.Modal.getOrCreateInstance(uploadModalEl);
    modal.show();
  }
};

// Modal Cleanup on Hide
document.addEventListener('DOMContentLoaded', function() {
  const previewModalEl = document.getElementById('docPreviewModal');
  if (previewModalEl) {
    previewModalEl.addEventListener('hidden.bs.modal', function () {
      const previewContainer = document.getElementById('docPreviewContainer');
      const previewSpinner = document.getElementById('docPreviewSpinner');
      if (previewContainer) previewContainer.innerHTML = '';
      if (previewSpinner) previewSpinner.style.display = 'block';
    });
  }
});
</script>
</main>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
