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
              <div class="col-md-6">
                <label class="text-muted small fw-semibold text-uppercase">Applied For Term</label>
                <div class="fw-medium text-dark"><?= htmlspecialchars($app['ay_name'] ?? 'Current Term', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($app['semester'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row g-3">
              <div class="col-md-12">
                <label class="text-muted small fw-semibold text-uppercase">Requested Scholarship</label>
                <div class="fw-bold fs-5 text-primary"><?= htmlspecialchars($app['scholarship_name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-muted small mt-1"><?= htmlspecialchars($app['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
              
            </div>
            
          </div>
        </div>

        <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4 fade-in-up" style="animation-delay: 0.4s;">
          <div class="position-absolute top-0 start-0 w-100 bg-info" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light fade-in-up" style="animation-delay: 0.5s;">
            <i class="bi bi-file-earmark-check"></i>
            <h2>Submitted Documents / Requirements</h2>
          </div>
          <div class="island-body bg-light rounded-bottom-4 fade-in-up" style="animation-delay: 0.6s;">
            <div class="p-3 bg-white rounded border">
                <?php
                    $docs = json_decode($app['submitted_documents'] ?? '[]', true);
                    if (empty($docs)):
                ?>
                    <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>No documents uploaded or submitted electronically.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush mb-0">
                        <?php foreach($docs as $doc): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                    <span class="fw-medium"><?= htmlspecialchars($doc['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <a href="<?= htmlspecialchars($doc['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
          </div>
        </div>

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
                    Note: Changing this from Approved will not automatically cancel active scholarship grants; you must suspend them in the Scholar tracker.
                  </div>
                <?php else: ?>
                  <div class="form-text text-success mt-1" style="font-size: 0.7rem;">
                    Approving this will register the applicant as a Scholar for the active Academic Year/Semester.
                  </div>
                <?php endif; ?>
              </div>

              <div class="mb-4">
                <label for="admin_feedback" class="form-label fw-semibold small text-dark">Applicant Feedback (Visible to Student)</label>
                <textarea name="admin_feedback" id="admin_feedback" rows="4" class="form-control form-control-sm" placeholder="Provide reasons for approval or rejection..."><?= htmlspecialchars($app['admin_feedback'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
              </div>

              <div class="d-grid">
                <button type="submit" class="btn btn-primary fw-medium rounded-pill shadow-sm" id="submitBtn">
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

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
