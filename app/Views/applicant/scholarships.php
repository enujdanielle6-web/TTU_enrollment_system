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
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($scholarship['name'], ENT_QUOTES, 'UTF-8') ?></h5>
                                                    <span class="badge bg-light text-dark border"><i class="bi bi-tag-fill me-1 text-primary"></i><?= htmlspecialchars($scholarship['category'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                                <?php if ($scholarship['provider']): ?>
                                                    <div class="small text-muted mb-2"><i class="bi bi-building me-1"></i><?= htmlspecialchars($scholarship['provider'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php endif; ?>
                                                <p class="text-muted small mb-3"><?= htmlspecialchars($scholarship['description'] ?? 'No description provided.', ENT_QUOTES, 'UTF-8') ?></p>
                                                
                                                <div class="d-flex gap-2 mb-3 flex-wrap">
                                                    <span class="badge bg-success-light text-success fw-bold border border-success border-opacity-25 px-3 py-2 rounded-pill">
                                                        <?php if ($scholarship['tuition_coverage_type'] === 'percentage'): ?>
                                                            <?= number_format((float)$scholarship['tuition_coverage_value'], 0) ?>% Tuition Coverage
                                                        <?php elseif ($scholarship['tuition_coverage_type'] === 'fixed'): ?>
                                                            ₱<?= number_format((float)$scholarship['tuition_coverage_value'], 2) ?> Tuition Coverage
                                                        <?php else: ?>
                                                            Full Tuition Coverage
                                                        <?php endif; ?>
                                                    </span>
                                                    <?php if ($scholarship['stipend_amount'] > 0): ?>
                                                        <span class="badge bg-info-light text-info fw-bold border border-info border-opacity-25 px-3 py-2 rounded-pill">
                                                            ₱<?= number_format((float)$scholarship['stipend_amount'], 2) ?> Stipend
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if (!empty($scholarship['requirements'])): ?>
                                                    <div class="bg-light p-3 rounded-3 mb-2 border border-secondary border-opacity-10">
                                                        <span class="d-block fw-bold small text-dark mb-1"><i class="bi bi-card-checklist me-1 text-primary"></i>Requirements:</span>
                                                        <p class="small text-muted mb-0" style="white-space: pre-line;"><?= htmlspecialchars($scholarship['requirements'], ENT_QUOTES, 'UTF-8') ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mt-2 text-md-end">
                                                <button type="button" class="btn btn-primary rounded-pill px-4 fw-medium shadow-sm apply-btn" 
                                                        data-id="<?= esc($scholarship['id']) ?>"
                                                        data-name="<?= htmlspecialchars($scholarship['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-req="<?= htmlspecialchars($scholarship['requirements'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                        data-bs-toggle="modal" data-bs-target="#applyModal">
                                                    Apply Now
                                                </button>
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
                                            <span class="badge <?= esc($badgeClass) ?> rounded-pill"><?= esc($statusLabel) ?></span>
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

<!-- Application Modal -->
<div class="modal fade" id="applyModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form action="scholarship_apply.php" method="POST" enctype="multipart/form-data">
        <?= getCsrfInput() ?>
        <input type="hidden" name="scholarship_id" id="modal_scholarship_id" value="">
        
        <div class="modal-header bg-light border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold text-dark" id="modal_title"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i>Apply for Scholarship</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body p-4 pt-2">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2" id="modal_scholarship_name">Scholarship Name</h6>
            
            <div class="alert alert-info py-2 small shadow-sm rounded">
                <i class="bi bi-info-circle me-1"></i> Ensure you meet all requirements before applying.
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Upload Required Documents</label>
                <input class="form-control" type="file" name="requirements[]" id="requirements" multiple accept=".pdf,.png,.jpg,.jpeg">
                <div class="form-text small text-muted">You can select multiple files (Max 5MB each). Allowed formats: PDF, JPG, PNG. Make sure to upload everything requested.</div>
            </div>
            
            <div class="mb-2">
                <p class="small text-muted mb-0"><i class="bi bi-card-checklist me-1 text-primary"></i><strong>Requirements reminder:</strong></p>
                <div id="modal_requirements_text" class="small text-muted ms-3" style="white-space: pre-line;"></div>
            </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 rounded-pill fw-medium shadow-sm">Submit Application</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const applyBtns = document.querySelectorAll('.apply-btn');
    applyBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modal_scholarship_id').value = this.getAttribute('data-id');
            document.getElementById('modal_scholarship_name').innerText = this.getAttribute('data-name');
            const req = this.getAttribute('data-req');
            document.getElementById('modal_requirements_text').innerText = req ? req : 'No specific documents listed, but upload any necessary files.';
        });
    });
});
</script>
</body>
</html>
