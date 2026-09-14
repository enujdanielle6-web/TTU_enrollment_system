<?php
$pageTitle = 'Application Status - Triple T University';
require_once __DIR__ . '/../components/header.php';
?>
<?php require_once __DIR__ . '/../components/applicant_navbar.php'; ?>

<main id="spa-main" class="py-5 bg-light min-vh-100">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-10">
        
        <div class="island island-hero mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 fade-in-up" style="animation-delay: 0.1s;">
          <div>
            <h1 class="h3 fw-bold text-dark mb-1">Application Status</h1>
            <p class="text-muted mb-0">Track your enrollment application progress and review your submitted details.</p>
          </div>
        </div>

        <?php if (isset($fetchError) && $fetchError !== null): ?>
          <div class="alert alert-danger shadow-sm rounded-12">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($fetchError, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php elseif ($application === null): ?>
          <div class="island text-center py-5 fade-in-up" style="animation-delay: 0.2s;">
            <div class="status-empty-icon mx-auto mb-3">
              <i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
            </div>
            <h2 class="h4 mb-2 text-dark fw-bold">No Application Found</h2>
            <p class="text-muted mb-4">You have not submitted an enrollment application yet.</p>
            <a class="btn btn-primary px-4 py-2" style="border-radius: 12px; font-weight: 600;" href="enroll.php">
              <i class="bi bi-pencil-square me-2"></i> Start Enrollment
            </a>
          </div>
        <?php else: ?>
          <div class="row g-4">
            
            <div class="col-lg-4">
              <div class="island h-100 fade-in-up" style="animation-delay: 0.3s;">
                <div class="island-header fade-in-up" style="animation-delay: 0.4s;">
                  <i class="bi bi-info-circle"></i>
                  <h2>Status Details</h2>
                </div>
                <div class="island-body mt-2 fade-in-up" style="animation-delay: 0.5s;">
                  <p class="text-muted small mb-1">Application Reference Number</p>
                  <p class="mb-4 fw-bold text-dark" style="font-size: 1.1rem;"><?= htmlspecialchars($application['reference_number'], ENT_QUOTES, 'UTF-8'); ?></p>

                  <p class="text-muted small mb-1">Current Status</p>
                  <span class="badge status-badge px-3 py-2 <?= htmlspecialchars($statusBadgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                  </span>

                  <p class="text-muted mt-4 mb-0 small">
                    <?= htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8'); ?>
                  </p>

                  <?php if ($application['status'] === 'correction_required'): ?>
                    <div class="mt-4 p-3 bg-warning-light border border-warning rounded-3">
                      <p class="text-dark fw-bold mb-1"><i class="bi bi-chat-left-dots-fill text-warning me-2"></i>Admin Feedback</p>
                      <p class="text-dark small mb-3">
                        <?= htmlspecialchars($adminFeedback ?? 'Please review and update your application details.', ENT_QUOTES, 'UTF-8'); ?>
                      </p>
                      <a href="enroll.php" class="btn btn-warning btn-sm fw-medium shadow-sm w-100">
                        <i class="bi bi-pencil-square me-1"></i> Edit Application
                      </a>
                    </div>
                  <?php elseif (($application['document_submission_method'] ?? '') === 'on_campus' && in_array($application['status'], ['pending', 'under_review'], true)): ?>
                    <div class="mt-4 p-3 bg-light border border-warning rounded-3 text-start">
                      <p class="text-dark fw-bold mb-1 small"><i class="bi bi-geo-alt-fill text-warning me-1"></i> Action: Visit Admissions Office</p>
                      <p class="text-muted small mb-3">Please present your original physical documents at the Admissions Office to complete validation and receive approval.</p>
                      <a href="documents.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold w-100">
                        <i class="bi bi-card-checklist me-1"></i> View Office Checklist
                      </a>
                    </div>
                  <?php elseif (in_array($application['status'], ['approved', 'payment_verified', 'enrolled'], true)): ?>
                    <?php if ($application['status'] === 'enrolled'): ?>
                      <div class="mt-3 p-3 bg-light border border-info rounded-3 text-start">
                        <p class="text-dark fw-bold mb-1 small"><i class="bi bi-envelope-check-fill text-primary me-1"></i> LMS & TTU Email Sent</p>
                        <p class="text-muted small mb-2">Check your registered email inbox for your institutional login credentials.</p>
                        <a href="/sia/auth/lms_student_login.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold w-100">
                          <i class="bi bi-box-arrow-in-right me-1"></i> Open Student LMS
                        </a>
                      </div>
                    <?php endif; ?>
                    <div class="mt-3">
                      <a href="print_slip.php" class="btn btn-success fw-medium shadow-sm w-100">
                        <i class="bi bi-printer me-2"></i> View Admission Slip
                      </a>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="col-lg-8">
              <div class="island h-100 fade-in-up" style="animation-delay: 0.6s;">
                <div class="island-header fade-in-up" style="animation-delay: 0.7s;">
                  <i class="bi bi-clock-history"></i>
                  <h2>Progress Timeline</h2>
                </div>
                <div class="island-body mt-2 fade-in-up" style="animation-delay: 0.8s;">
                  <div class="status-timeline">
                    <?php foreach ($timelineSteps as $step): ?>
                      <?php
                      $stepState = $step['state'];
                      $stepIcon = match ($stepState) {
                          'completed' => 'bi-check-circle-fill',
                          'active' => 'bi-hourglass-split',
                          'rejected' => 'bi-x-circle-fill',
                          default => 'bi-circle',
                      };
                      ?>
                      <div class="status-step status-step-<?= htmlspecialchars($stepState, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="status-step-marker shadow-sm">
                          <i class="bi <?= htmlspecialchars($stepIcon, ENT_QUOTES, 'UTF-8'); ?>"></i>
                        </div>
                        <div class="status-step-content py-2 px-3">
                          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                            <h3 class="h6 mb-0 fw-bold"><?= htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="d-flex align-items-center gap-2">
                              <?php if (!empty($step['timestamp'])): ?>
                                <span class="text-muted small"><i class="bi bi-clock me-1"></i><?= formatDisplayDate($step['timestamp']); ?></span>
                              <?php endif; ?>
                              <?php 
                                $stepAction = getStepAction($step, $application, $healthStatus ?? null);
                                if ($stepAction):
                              ?>
                                <a href="<?= esc($stepAction['url']) ?>" class="btn <?= esc($stepAction['class']) ?> btn-sm rounded-pill px-3 py-1 fw-semibold shadow-sm text-nowrap d-inline-flex align-items-center" style="font-size: 0.78rem;">
                                  <i class="bi <?= esc($stepAction['icon']) ?> me-1.5"></i> <?= esc($stepAction['label']) ?>
                                </a>
                              <?php endif; ?>
                            </div>
                          </div>
                          <p class="text-muted small mb-0"><?= htmlspecialchars($step['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="island fade-in-up" style="animation-delay: 0.85s;">
                <div class="island-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-check"></i>
                    <h2 class="h5 mb-0 fw-bold">Required Documents Status</h2>
                  </div>
                  <?php if ($docMethod !== 'on_campus'): ?>
                    <a href="documents.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                      <i class="bi bi-folder2-open me-1"></i> Documents Portal
                    </a>
                  <?php endif; ?>
                </div>
                <div class="island-body mt-3">
                  <?php 
                    $hasRejectedDocs = false;
                    foreach ($documents ?? [] as $docItem) {
                        if (($docItem['status'] ?? '') === 'rejected') {
                            $hasRejectedDocs = true;
                            break;
                        }
                    }
                  ?>

                  <?php if ($hasRejectedDocs): ?>
                    <div class="alert alert-danger d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 rounded-3 shadow-sm border-danger">
                      <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-octagon-fill fs-4 text-danger flex-shrink-0"></i>
                        <div>
                          <strong class="d-block text-danger">Action Required: Correction Needed</strong>
                          <span class="small text-danger-emphasis">One or more uploaded documents were rejected by Admissions. Please review the feedback and re-upload the correct files.</span>
                        </div>
                      </div>
                      <a href="documents.php" class="btn btn-danger btn-sm rounded-pill px-3 fw-semibold text-nowrap">
                        <i class="bi bi-upload me-1"></i> Re-upload in Portal
                      </a>
                    </div>
                  <?php endif; ?>

                  <?php if (empty($documents)): ?>
                    <?php if ($docMethod === 'on_campus'): ?>
                      <div class="p-3 bg-light rounded-3 border text-center py-4">
                        <i class="bi bi-building-check text-primary fs-2 mb-2 d-block"></i>
                        <h6 class="fw-bold mb-1">On-Campus Physical Document Verification</h6>
                        <p class="text-muted small mb-0">You selected on-campus document submission. Please bring your original documents and photocopies to the Admissions Office.</p>
                      </div>
                    <?php else: ?>
                      <div class="p-3 bg-light rounded-3 border text-center py-4">
                        <i class="bi bi-cloud-arrow-up text-muted fs-2 mb-2 d-block"></i>
                        <h6 class="fw-bold mb-1">No Documents Uploaded Yet</h6>
                        <p class="text-muted small mb-3">Please upload your required credentials to proceed with document verification.</p>
                        <a href="documents.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold">
                          <i class="bi bi-upload me-1"></i> Upload Requirements
                        </a>
                      </div>
                    <?php endif; ?>
                  <?php else: ?>
                    <div class="table-responsive">
                      <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                          <tr class="small text-muted text-uppercase">
                            <th style="min-width: 200px;">Document Name</th>
                            <th>Status</th>
                            <th>Review Feedback</th>
                            <th class="text-end">Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($documents as $doc): ?>
                            <?php
                              $dStatus = $doc['status'] ?? 'pending';
                              $docFormattedName = ucwords(str_replace(['_', '-'], ' ', $doc['document_name']));
                            ?>
                            <tr>
                              <td>
                                <div class="d-flex align-items-center gap-2">
                                  <div class="p-2 rounded-2 bg-light text-primary">
                                    <i class="bi bi-file-earmark-text fs-5"></i>
                                  </div>
                                  <div>
                                    <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($docFormattedName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-muted small"><?= htmlspecialchars($doc['document_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                  </div>
                                </div>
                              </td>
                              <td>
                                <?php if ($dStatus === 'verified'): ?>
                                  <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill">
                                    <i class="bi bi-check-circle-fill me-1"></i> Verified
                                  </span>
                                <?php elseif ($dStatus === 'rejected'): ?>
                                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 rounded-pill">
                                    <i class="bi bi-x-circle-fill me-1"></i> Rejected
                                  </span>
                                <?php else: ?>
                                  <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 rounded-pill">
                                    <i class="bi bi-hourglass-split me-1"></i> Under Review
                                  </span>
                                <?php endif; ?>
                              </td>
                              <td>
                                <?php if ($dStatus === 'rejected' && !empty($doc['feedback'])): ?>
                                  <div class="p-2 rounded-3 bg-danger-subtle border border-danger-subtle text-danger small">
                                    <i class="bi bi-chat-left-dots-fill me-1"></i> <strong>Admissions Note:</strong> <?= htmlspecialchars($doc['feedback'], ENT_QUOTES, 'UTF-8') ?>
                                  </div>
                                <?php elseif (!empty($doc['feedback'])): ?>
                                  <span class="text-muted small"><?= htmlspecialchars($doc['feedback'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                  <span class="text-muted small fst-italic">No feedback remarks</span>
                                <?php endif; ?>
                              </td>
                              <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                  <?php if (!empty($doc['file_path'])): ?>
                                    <a href="<?= htmlspecialchars($doc['file_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-sm btn-light border rounded-pill px-2.5" title="View Uploaded File">
                                      <i class="bi bi-eye"></i>
                                    </a>
                                  <?php endif; ?>
                                  <?php if ($dStatus === 'rejected'): ?>
                                    <a href="documents.php" class="btn btn-sm btn-danger rounded-pill px-3" title="Re-upload">
                                      <i class="bi bi-upload me-1"></i> Re-upload
                                    </a>
                                  <?php endif; ?>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="island fade-in-up" style="animation-delay: 0.9s;">
                <div class="island-header fade-in-up" style="animation-delay: 1s;">
                  <i class="bi bi-file-earmark-text"></i>
                  <h2>Application Summary</h2>
                </div>
                <div class="island-body mt-2 fade-in-up" style="animation-delay: 1.1s;">
                  <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                      <p class="text-muted small mb-1">Applicant Name</p>
                      <p class="mb-0 fw-semibold text-dark">
                        <?= htmlspecialchars(trim($application['first_name'] . ' ' . $application['last_name']), ENT_QUOTES, 'UTF-8'); ?>
                      </p>
                    </div>

                    <div class="col-md-6 col-lg-4">
                      <p class="text-muted small mb-1">Email Address</p>
                      <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($application['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <div class="col-md-6 col-lg-4">
                      <p class="text-muted small mb-1">Reference Number</p>
                      <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($application['reference_number'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <div class="col-md-6 col-lg-4">
                      <p class="text-muted small mb-1">Application Status</p>
                      <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <div class="col-md-6 col-lg-4">
                      <p class="text-muted small mb-1">Grade Level</p>
                      <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($application['grade_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <div class="col-md-6 col-lg-4">
                      <p class="text-muted small mb-1">School Year</p>
                      <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($application['school_year'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <div class="col-md-6 col-lg-4">
                      <p class="text-muted small mb-1">Program / Strand</p>
                      <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars(getStrandLabel($application['strand'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <div class="col-md-6 col-lg-4">
                      <p class="text-muted small mb-1">Document Submission</p>
                      <p class="mb-0 fw-semibold text-dark"><?= esc($docMethod === 'on_campus' ? 'On-Campus Verification' : 'Online Upload') ?></p>
                    </div>

                    <div class="col-md-6 col-lg-4">
                      <p class="text-muted small mb-1">Date Submitted</p>
                      <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars(formatDisplayDate($application['created_at']), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <div class="col-md-6 col-lg-4">
                      <p class="text-muted small mb-1">Last Updated</p>
                      <p class="mb-0 fw-semibold text-dark"><?= htmlspecialchars(formatDisplayDate($application['updated_at']), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

