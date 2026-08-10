<?php
require_once __DIR__ . '/../components/header.php';
?>
<?php require_once __DIR__ . '/../components/applicant_navbar.php'; ?>

<main id="spa-main" class="py-5 bg-light min-vh-100">
  <div class="container px-lg-5">
    
    <div class="island island-hero mb-4 fade-in-up" style="animation-delay: 0.1s;">
      <h1 class="h3 fw-bold text-dark mb-1">Scholarships</h1>
      <p class="text-muted mb-0">Apply for financial aid and academic scholarships.</p>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (!$isApproved): ?>
        <div class="island text-center py-5 fade-in-up" style="animation-delay: 0.2s;">
            <i class="bi bi-lock text-muted" style="font-size: 3rem;"></i>
            <h3 class="mt-3">Scholarships Locked</h3>
            <p class="text-muted">You can only apply for scholarships once your enrollment application has been <strong>Approved</strong>.</p>

        </div>
    <?php elseif (!$isMedicalVerified): ?>
        <div class="island text-center py-5 border-warning border-2 fade-in-up" style="animation-delay: 0.3s;">
            <i class="bi bi-heart-pulse text-warning" style="font-size: 3rem;"></i>
            <h3 class="mt-3">Medical Clearance Required</h3>
            <p class="text-muted">You must complete your Medical Clearance before applying for scholarships.</p>
            <a href="health_info.php" class="btn btn-primary rounded-pill mt-3 px-4">Go to Health Information</a>
        </div>
    <?php elseif (!$hasAssessment): ?>
        <div class="island text-center py-5 border-warning border-2 fade-in-up" style="animation-delay: 0.4s;">
            <i class="bi bi-hourglass-split text-warning" style="font-size: 3rem;"></i>
            <h3 class="mt-3">Awaiting Assessment</h3>
            <p class="text-muted">Your application is approved, but the administration is still finalizing your fee assessment.<br>Please check back later.</p>

        </div>
    <?php else: ?>

        <div class="row g-4">
            
            <div class="col-lg-8">
                <div class="island mb-4 fade-in-up" style="animation-delay: 0.5s;">
                    <div class="island-header fade-in-up" style="animation-delay: 0.6s;">
                        <i class="bi bi-award-fill text-primary"></i>
                        <h2 class="mb-0 text-dark">Available Scholarships</h2>
                    </div>
                    <div class="island-body p-0 fade-in-up" style="animation-delay: 0.7s;">
                        <div class="list-group list-group-flush rounded-bottom-4">
                            <?php if (empty($activeScholarships)): ?>
                                <div class="p-5 text-center text-muted">
                                    <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                                    No scholarships are currently open for applications.
                                </div>
                            <?php else: ?>
                                <?php foreach ($activeScholarships as $scholarship): ?>
                                    <div class="list-group-item p-4 border-bottom">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                            <div>
                                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($scholarship['name'], ENT_QUOTES, 'UTF-8') ?></h5>
                                                <p class="text-muted small mb-2"><?= htmlspecialchars($scholarship['description'] ?? 'No description provided.', ENT_QUOTES, 'UTF-8') ?></p>
                                                <?php if (!empty($scholarship['requirements'])): ?>
                                                    <div class="bg-light p-3 rounded-3 mb-3 border border-secondary border-opacity-10">
                                                        <span class="d-block fw-bold small text-dark mb-1"><i class="bi bi-card-checklist me-1 text-primary"></i>Requirements:</span>
                                                        <p class="small text-muted mb-0" style="white-space: pre-line;"><?= htmlspecialchars($scholarship['requirements'], ENT_QUOTES, 'UTF-8') ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                <span class="badge bg-success-light text-success fw-bold border border-success border-opacity-25 px-3 py-2 rounded-pill">
                                                    <?php if ($scholarship['discount_type'] === 'percentage'): ?>
                                                        <?= number_format((float)$scholarship['discount_value'], 0) ?>% Discount
                                                    <?php else: ?>
                                                        ₱<?= number_format((float)$scholarship['discount_value'], 2) ?> Discount
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div>
                                                <form action="scholarship_apply.php" method="POST">
                                                    <?= getCsrfInput() ?>
                                                    <input type="hidden" name="scholarship_id" value="<?= $scholarship['id'] ?>">
                                                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-medium shadow-sm">
                                                        Apply Now
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="island sticky-top fade-in-up" style="top: 80px; animation-delay: 0.8s;">
                    <div class="island-header bg-light fade-in-up" style="animation-delay: 0.9s;">
                        <i class="bi bi-clock-history"></i>
                        <h2 class="mb-0">My Applications</h2>
                    </div>
                    <div class="island-body p-0 fade-in-up" style="animation-delay: 1s;">
                        <?php if (empty($myApplications)): ?>
                            <div class="p-4 text-center text-muted small">
                                You haven't applied for any scholarships yet.
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush rounded-bottom-4">
                                <?php foreach ($myApplications as $myApp): ?>
                                    <?php 
                                        $badgeClass = match($myApp['status']) {
                                            'approved' => 'bg-success',
                                            'rejected' => 'bg-danger',
                                            'under_review' => 'bg-info',
                                            default => 'bg-warning text-dark'
                                        };
                                        $statusLabel = match($myApp['status']) {
                                            'under_review' => 'Under Review',
                                            default => ucfirst($myApp['status'])
                                        };
                                    ?>
                                    <li class="list-group-item p-3">
                                        <div class="fw-bold text-dark small mb-1"><?= htmlspecialchars($myApp['scholarship_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge <?= $badgeClass ?> rounded-pill"><?= $statusLabel ?></span>
                                            <span class="text-muted" style="font-size: 0.75rem;"><?= date('M d, Y', strtotime($myApp['created_at'])) ?></span>
                                        </div>
                                        <?php if (!empty($myApp['admin_feedback'])): ?>
                                            <div class="mt-2 p-2 bg-light rounded text-muted" style="font-size: 0.75rem;">
                                                <strong>Feedback:</strong> <?= htmlspecialchars($myApp['admin_feedback'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

