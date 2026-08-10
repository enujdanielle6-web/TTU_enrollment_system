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
                  <?php elseif (in_array($application['status'], ['approved', 'enrolled'], true)): ?>
                    <div class="mt-4">
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
                          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <h3 class="h6 mb-1 fw-bold"><?= htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <?php if (!empty($step['timestamp'])): ?>
                              <span class="text-muted small"><i class="bi bi-clock me-1"></i><?= formatDisplayDate($step['timestamp']); ?></span>
                            <?php endif; ?>
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
                      <p class="mb-0 fw-semibold text-dark"><?= $docMethod === 'on_campus' ? 'On-Campus Verification' : 'Online Upload'; ?></p>
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

