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
    
    <div class="mb-4 d-flex align-items-center justify-content-between">
      <div>
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
        <a href="<?= esc($backUrl) ?>" class="text-decoration-none text-muted small fw-medium"><i class="bi bi-arrow-left"></i> Back to List</a>
        <h1 class="h3 fw-bold text-dark mt-2 mb-1">
          Review Application 
          <span class="badge <?= esc($badgeClass) ?> ms-2 fs-6 align-middle"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
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
      <input type="hidden" name="application_id" value="<?= esc($app['id']) ?>">
      <input type="hidden" name="user_id" value="<?= esc($app['user_id']) ?>">
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
                <div class="fw-medium text-dark"><?= htmlspecialchars(getStrandLabel($app['strand'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small fw-semibold text-uppercase">Application Curriculum</label>
                <div class="fw-medium text-dark"><?= !empty($app['assigned_curriculum_version']) ? htmlspecialchars($app['assigned_curriculum_version'], ENT_QUOTES, 'UTF-8') : '<span class="text-warning fst-italic">Pending Assignment</span>' ?></div>
              </div>
              <div class="col-12 mt-2">
                <div class="p-2 bg-success bg-opacity-10 rounded border border-success border-opacity-25">
                  <label class="text-success small fw-semibold text-uppercase"><i class="bi bi-file-earmark-lock2-fill"></i> Official Student Curriculum</label>
                  <div class="fw-bold text-success-emphasis"><?= !empty($app['user_curriculum_version']) ? htmlspecialchars($app['user_curriculum_version'], ENT_QUOTES, 'UTF-8') : '<span class="text-warning fst-italic">Pending First Enrollment</span>' ?></div>
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
                        <?php if (!empty($sub['section_code'])): ?>
                            <span class="badge bg-secondary mb-1"><?= htmlspecialchars($sub['section_code'], ENT_QUOTES, 'UTF-8') ?></span><br>
                        <?php endif; ?>
                        <?= !empty($sub['schedule_text']) ? esc($sub['schedule_text']) : '<span class="text-muted fst-italic">No schedule</span>' ?>
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
                    <td class="text-end pe-4 fw-bold text-dark fs-5"><?= esc($totalUnits) ?></td>
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
                <div class="fw-medium text-dark"><?= !empty($app['lrn']) ? htmlspecialchars($app['lrn'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small fw-semibold text-uppercase">Previous School Name</label>
                <div class="fw-medium text-dark"><?= !empty($app['previous_school']) ? htmlspecialchars($app['previous_school'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small fw-semibold text-uppercase">Previous School Type</label>
                <div class="fw-medium text-dark"><?= !empty($app['previous_school_type']) ? htmlspecialchars($app['previous_school_type'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small fw-semibold text-uppercase">Last School Year Attended</label>
                <div class="fw-medium text-dark"><?= !empty($app['previous_school_year']) ? htmlspecialchars($app['previous_school_year'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Family & Guardian Background -->
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light">
            <i class="bi bi-people-fill"></i>
            <h2>Family & Guardian Information</h2>
          </div>
          <div class="island-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Primary Guardian / Parent</label>
                <div class="fw-medium text-dark"><?= !empty($app['guardian_name']) ? htmlspecialchars($app['guardian_name'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Relationship to Applicant</label>
                <div class="fw-medium text-dark"><?= !empty($app['guardian_relationship']) ? htmlspecialchars($app['guardian_relationship'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Guardian Contact Number</label>
                <div class="fw-medium text-dark"><?= !empty($app['guardian_contact']) ? htmlspecialchars($app['guardian_contact'], ENT_QUOTES, 'UTF-8') : 'N/A' ?></div>
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
                <div class="fw-medium text-dark"><?= !empty($app['emergency_name']) ? htmlspecialchars($app['emergency_name'], ENT_QUOTES, 'UTF-8') : (!empty($health['emergency_name']) ? htmlspecialchars($health['emergency_name'], ENT_QUOTES, 'UTF-8') : 'N/A') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Relationship</label>
                <div class="fw-medium text-dark"><?= !empty($app['emergency_relationship']) ? htmlspecialchars($app['emergency_relationship'], ENT_QUOTES, 'UTF-8') : (!empty($health['emergency_relationship']) ? htmlspecialchars($health['emergency_relationship'], ENT_QUOTES, 'UTF-8') : 'N/A') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Contact Number</label>
                <div class="fw-medium text-dark"><?= !empty($app['emergency_contact']) ? htmlspecialchars($app['emergency_contact'], ENT_QUOTES, 'UTF-8') : (!empty($health['emergency_contact']) ? htmlspecialchars($health['emergency_contact'], ENT_QUOTES, 'UTF-8') : 'N/A') ?></div>
              </div>
              
              <div class="col-12"><hr class="my-1 border-light"></div>

              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">Height & Weight</label>
                <div class="fw-medium text-dark">
                  <?= !empty($health['height']) ? htmlspecialchars($health['height']) . ' cm' : '—' ?> / 
                  <?= !empty($health['weight']) ? htmlspecialchars($health['weight']) . ' kg' : '—' ?>
                </div>
              </div>
              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">Blood Type</label>
                <div class="fw-medium text-dark"><?= !empty($health['blood_type']) ? htmlspecialchars($health['blood_type'], ENT_QUOTES, 'UTF-8') : '—' ?></div>
              </div>
              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">Allergies</label>
                <div class="fw-medium text-dark"><?= !empty($health['allergies_details']) ? htmlspecialchars($health['allergies_details'], ENT_QUOTES, 'UTF-8') : (!empty($health['has_allergies']) ? 'Yes' : 'None') ?></div>
              </div>
              <div class="col-md-3">
                <label class="text-muted small fw-semibold text-uppercase">Medical Clearance</label>
                <div>
                  <?php 
                    $hStatus = $health['status'] ?? 'pending';
                    $hBadge = match($hStatus) {
                      'verified' => 'bg-success',
                      'rejected' => 'bg-danger',
                      'correction_required' => 'bg-warning text-dark',
                      default => 'bg-secondary bg-opacity-10 text-secondary'
                    };
                  ?>
                  <span class="badge <?= esc($hBadge) ?> rounded-pill text-uppercase" style="font-size: 0.7rem;"><?= ucfirst(htmlspecialchars($hStatus, ENT_QUOTES, 'UTF-8')) ?></span>
                </div>
              </div>
              <?php if (!empty($health['medical_conditions'])): ?>
              <div class="col-12">
                <label class="text-muted small fw-semibold text-uppercase">Declared Medical Conditions</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($health['medical_conditions'], ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

                        <!-- Documents -->
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-folder-fill text-primary"></i>
              <h2 class="mb-0">Uploaded Documents</h2>
            </div>
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#adminUploadDocModal">
              <i class="bi bi-cloud-arrow-up"></i>
              <span>Upload Document</span>
            </button>
          </div>
          <div class="island-body">
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
                             <span class="badge <?= esc($statusBadge) ?> rounded-pill text-uppercase" style="font-size: 0.65rem;"><?= ucfirst(htmlspecialchars($doc['status'] ?? 'pending', ENT_QUOTES, 'UTF-8')) ?></span>
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
                         <label class="small text-muted fw-bold mb-1">Verify Status</label>
                         <select name="doc_status[<?= esc($doc['id']) ?>]" class="form-select form-select-sm">
                           <option value="pending" <?= esc(($doc['status'] ?? '') === 'pending' ? 'selected' : '') ?>>Pending / Awaiting</option>
                           <option value="verified" <?= esc(($doc['status'] ?? '') === 'verified' ? 'selected' : '') ?>>Verified / Approved</option>
                           <option value="rejected" <?= esc(($doc['status'] ?? '') === 'rejected' ? 'selected' : '') ?>>Rejected / Needs Reupload</option>
                         </select>
                       </div>
                       <div class="col-md-8">
                         <label class="small text-muted fw-bold mb-1">Feedback Comment</label>
                         <input type="text" name="doc_feedback[<?= esc($doc['id']) ?>]" class="form-control form-control-sm" placeholder="e.g. Please upload clear copy of LRN card..." value="<?= htmlspecialchars($doc['feedback'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
                    <option value="<?= esc($sec['id']) ?>" <?= esc($isFull ? 'disabled' : '') ?>>
                      <?= htmlspecialchars($sec['section_code'], ENT_QUOTES, 'UTF-8') ?> 
                      (<?= esc($sec['schedule_type']) ?> | <?= esc($remaining) ?> slots left) <?= esc($isFull ? '[FULL]' : '') ?>
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
                <select name="status" id="status" class="form-select form-select-sm" required <?= esc($app['status'] === 'enrolled' ? 'disabled' : '') ?>>
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
                <div class="fw-semibold small text-dark mb-1"><i class="bi bi-cash-stack text-warning"></i> Automated Assessment Generation</div>
                <div class="form-text mt-0" style="font-size: 0.7rem;">The system will automatically generate a fee assessment for <strong><?= htmlspecialchars($app['grade_level'] ?? '', ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($app['strand'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> as soon as this application is marked as <strong>Approved</strong>. This is required before the student can apply for scholarships.</div>
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

<?php if (($app['student_type'] ?? '') === 'Irregular'): 
    // Get currently enrolled subject IDs
    $currentSubIds = array_column($enrolledSubjects, 'id');
?>
<!-- Edit Subjects Modal -->
<div class="modal fade" id="editSubjectsModal" tabindex="-1" aria-labelledby="editSubjectsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content border-0 shadow" method="POST" action="application_process.php">
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
              <?php if (empty($enrolledSubjects)): ?>
              <tr class="empty-row">
                <td colspan="4" class="text-center py-4 text-muted">No subjects currently assigned.</td>
              </tr>
              <?php else: ?>
                <?php foreach ($enrolledSubjects as $sub): ?>
                <tr>
                  <td class="ps-3 fw-bold text-dark align-middle">
                    <?= htmlspecialchars($sub['subject_code'], ENT_QUOTES) ?>
                    <input type="hidden" name="subjects[<?= esc($sub['subject_id']) ?>]" value="<?= esc($sub['section_id'] ?? '') ?>">
                  </td>
                  <td class="align-middle">
                    <?= htmlspecialchars($sub['subject_name'], ENT_QUOTES) ?>
                    <div class="text-primary mt-1" style="font-size: 0.65rem;"><?= esc($sub['schedule_text']) ?></div>
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

      fetch(`../ajax/get_enrollment_summary.php?section_id=${sectionId}&app_id=<?= esc($appId) ?>`)
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
