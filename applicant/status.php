<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

$pageTitle = 'Application Status - Triple T University';
$userId = (int) $_SESSION['user_id'];
$application = null;
$fetchError = null;

try {
    $statement = $pdo->prepare(
        'SELECT
            a.id,
            a.reference_number,
            a.status,
            a.grade_level,
            a.school_year,
            a.strand,
            a.document_submission_method,
            a.admin_feedback,
            a.created_at,
            a.updated_at,
            u.first_name,
            u.last_name,
            u.email
         FROM applications a
         INNER JOIN users u ON u.id = a.user_id
         WHERE a.user_id = :user_id
         ORDER BY a.created_at DESC
         LIMIT 1'
    );
    $statement->execute(['user_id' => $userId]);
    $application = $statement->fetch() ?: null;
} catch (PDOException $exception) {
    error_log('Application status fetch failed: ' . $exception->getMessage());
    $fetchError = 'Unable to load your application status. Please try again later.';
}

$timestamps = getApplicationTimestamps($userId);
$docMethod = $application['document_submission_method'] ?? 'online';
$hasUploadedDocs = false;
if ($application && $docMethod === 'online') {
    try {
        $docStmt = $pdo->prepare('SELECT COUNT(*) FROM application_documents WHERE application_id = :app_id AND status != "rejected"');
        $docStmt->execute(['app_id' => $application['id']]);
        $hasUploadedDocs = $docStmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        $hasUploadedDocs = false;
    }
}

$timelineSteps = $application ? getApplicationTimelineSteps($application['status'], $docMethod, $timestamps, $hasUploadedDocs) : [];

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

$statusLabel = $application ? formatApplicationStatus($application['status']) : '';
$statusBadgeClass = $application ? getApplicationStatusBadgeClass($application['status']) : '';
$statusMessage = $application ? getApplicationStatusMessage($application['status']) : '';

// Pull the latest admin feedback from the applications table
$adminFeedback = $application ? ($application['admin_feedback'] ?? null) : null;

require_once __DIR__ . '/../components/header.php';
?>

<?php require_once __DIR__ . '/components/navbar.php'; ?>

<main class="py-5 bg-light min-vh-100">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-10">
        
        <div class="island island-hero mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
          <div>
            <h1 class="h3 fw-bold text-dark mb-1">Application Status</h1>
            <p class="text-muted mb-0">Track your enrollment application progress and review your submitted details.</p>
          </div>
        </div>

        <?php if ($fetchError !== null): ?>
          <div class="alert alert-danger shadow-sm rounded-12">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($fetchError, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php elseif ($application === null): ?>
          <div class="island text-center py-5">
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
              <div class="island h-100">
                <div class="island-header">
                  <i class="bi bi-info-circle"></i>
                  <h2>Status Details</h2>
                </div>
                <div class="island-body mt-2">
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
                      <a href="print_slip.php" target="_blank" class="btn btn-success fw-medium shadow-sm w-100">
                        <i class="bi bi-printer-fill me-2"></i> Print Admission Slip
                      </a>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="col-lg-8">
              <div class="island h-100">
                <div class="island-header">
                  <i class="bi bi-clock-history"></i>
                  <h2>Progress Timeline</h2>
                </div>
                <div class="island-body mt-2">
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
              <div class="island">
                <div class="island-header">
                  <i class="bi bi-file-earmark-text"></i>
                  <h2>Application Summary</h2>
                </div>
                <div class="island-body mt-2">
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
