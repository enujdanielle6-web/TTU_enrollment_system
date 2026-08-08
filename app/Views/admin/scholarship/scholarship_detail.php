<?php
require_once __DIR__ . '/../../components/header.php';

require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="mb-4 d-flex align-items-center justify-content-between">
      <div>
        <a href="scholarship_review.php" class="text-decoration-none text-muted small fw-medium"><i class="bi bi-arrow-left"></i> Back to Queue</a>
        <h1 class="h3 fw-bold text-dark mt-2 mb-1">
          Review Scholarship Application 
          <span class="badge <?= $badgeClass ?> ms-2 fs-6 align-middle"><?= $statusLabel ?></span>
        </h1>
        <p class="text-muted mb-0">Applicant: <span class="fw-medium text-dark"><?= htmlspecialchars($app['last_name'] . ', ' . $app['first_name'], ENT_QUOTES, 'UTF-8') ?></span></p>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form action="scholarship_process.php" method="POST">
      <input type="hidden" name="action" value="process_application">
      <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
      <input type="hidden" name="user_id" value="<?= $app['user_id'] ?>">
      <input type="hidden" name="scholarship_id" value="<?= $app['scholarship_id'] ?>">
      <input type="hidden" name="assessment_id" value="<?= $app['assessment_id'] ?? 0 ?>">
      <?= getCsrfInput() ?>
      
      <div class="row g-4">
        
        <div class="col-lg-8">
        
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4 fade-in-up" style="animation-delay: 0.1s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light fade-in-up" style="animation-delay: 0.2s;">
            <i class="bi bi-person-vcard-fill"></i>
            <h2>Applicant & Scholarship Details</h2>
          </div>
          <div class="island-body fade-in-up" style="animation-delay: 0.3s;">
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
                <label class="text-muted small fw-semibold text-uppercase">Grade Level</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['grade_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Strand</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['strand'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-4">
                <label class="text-muted small fw-semibold text-uppercase">Enrollment Ref</label>
                <div class="fw-medium text-dark">
                  <a href="../admissions/application_detail.php?id=<?= $app['application_id'] ?? 0 ?>" target="_blank"><?= htmlspecialchars($app['reference_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> <i class="bi bi-box-arrow-up-right small"></i></a>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row g-3">
              <div class="col-md-12">
                <label class="text-muted small fw-semibold text-uppercase">Requested Scholarship</label>
                <div class="fw-bold fs-5 text-primary"><?= htmlspecialchars($app['scholarship_name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-muted small mt-1"><?= htmlspecialchars($app['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small fw-semibold text-uppercase">Discount Value</label>
                <div class="fw-medium text-success fs-5">
                  <?php if ($app['discount_type'] === 'percentage'): ?>
                    <?= number_format((float)$app['discount_value'], 0) ?>% Discount
                  <?php else: ?>
                    ₱<?= number_format((float)$app['discount_value'], 2) ?> Discount
                  <?php endif; ?>
                </div>
              </div>
            </div>
            
          </div>
        </div>

        <?php if ($app['assessment_id']): ?>
        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4 fade-in-up" style="animation-delay: 0.4s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light fade-in-up" style="animation-delay: 0.5s;">
            <i class="bi bi-calculator-fill"></i>
            <h2>Assessment Impact Preview</h2>
          </div>
          <div class="island-body bg-light rounded-bottom-4 fade-in-up" style="animation-delay: 0.6s;">
            <div class="row text-center">
              <div class="col-md-4 border-end">
                <div class="text-muted small text-uppercase fw-bold mb-2">Original Total</div>
                <div class="fs-4 fw-bold text-dark">₱<?= number_format((float)$app['total_amount'], 2) ?></div>
              </div>
              <div class="col-md-4 border-end">
                <div class="text-muted small text-uppercase fw-bold mb-2">Estimated Discount</div>
                <div class="fs-4 fw-bold text-success">
                  <?php
                    $total = (float)$app['total_amount'];
                    $tuition = (float)$app['tuition_fee'];
                    $discountValue = (float)$app['discount_value'];
                    $discountAmount = 0;
                    if ($app['discount_type'] === 'percentage') {
                        $discountAmount = $tuition * ($discountValue / 100);
                    } else {
                        $discountAmount = $discountValue;
                    }
                    if ($discountAmount > $tuition) $discountAmount = $tuition; // Cap at 100% of tuition
                    
                    echo '-₱' . number_format($discountAmount, 2);
                  ?>
                </div>
              </div>
              <div class="col-md-4">
                <div class="text-muted small text-uppercase fw-bold mb-2">New Net Amount</div>
                <div class="fs-4 fw-bold text-primary">
                  ₱<?= number_format($total - $discountAmount, 2) ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning shadow-sm rounded-12 border-warning">
          <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Warning:</strong> This applicant does not have a generated fee assessment. You must generate an assessment for their enrollment application before you can approve this scholarship.
        </div>
        <?php endif; ?>

      </div>

      <div class="col-lg-4">
        <div class="island border-primary border-top border-4 sticky-top fade-in-up" style="top: 80px; animation-delay: 0.7s;">
          <div class="island-header bg-primary-light fade-in-up" style="animation-delay: 0.8s;">
            <i class="bi bi-shield-lock-fill text-primary"></i>
            <h2 class="text-primary">Admin Action Panel</h2>
          </div>
          <div class="island-body fade-in-up" style="animation-delay: 0.9s;">
            
              <div class="mb-3">
                <label for="status" class="form-label fw-semibold small text-dark">Decision</label>
                <select name="status" id="status" class="form-select form-select-sm" required>
                  <option value="pending" <?= $app['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="under_review" <?= $app['status'] === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                  <option value="approved" <?= $app['status'] === 'approved' ? 'selected' : '' ?>>Approve Scholarship</option>
                  <option value="rejected" <?= $app['status'] === 'rejected' ? 'selected' : '' ?>>Reject Scholarship</option>
                </select>
                <?php if ($app['status'] === 'approved'): ?>
                  <div class="form-text text-danger mt-1" style="font-size: 0.7rem;">
                    Note: If you change this from Approved to another status, the discount will be removed from their assessment.
                  </div>
                <?php endif; ?>
              </div>

              <div class="mb-4">
                <label for="admin_feedback" class="form-label fw-semibold small text-dark">Applicant Feedback (Visible to Student)</label>
                <textarea name="admin_feedback" id="admin_feedback" rows="4" class="form-control form-control-sm" placeholder="Provide reasons for approval or rejection..."><?= htmlspecialchars($app['admin_feedback'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
              </div>

              <div class="d-grid">
                <button type="submit" class="btn btn-primary fw-medium rounded-pill shadow-sm" <?= (!$app['assessment_id'] && $app['status'] !== 'approved') ? 'id="submitBtn"' : '' ?>>
                  <i class="bi bi-save2 me-1"></i> Save Decision
                </button>
              </div>

          </div>
        </div>
      </div>

      </div>
    </form>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const submitBtn = document.getElementById('submitBtn');
    const hasAssessment = <?= $app['assessment_id'] ? 'true' : 'false' ?>;
    
    if (statusSelect && submitBtn && !hasAssessment) {
        statusSelect.addEventListener('change', function() {
            if (this.value === 'approved') {
                alert('You cannot approve this scholarship because the applicant does not have a fee assessment generated yet. Please generate their assessment via their Enrollment Application Review page first.');
                this.value = '<?= $app['status'] ?>'; // reset
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>



