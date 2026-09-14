<?php
$pageTitle = 'Review Scholarship Application - Admin';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <a href="scholarship_review.php" class="btn btn-light border rounded-circle d-flex align-items-center justify-content-center shadow-xs text-muted" style="width: 44px; height: 44px;" title="Back to Review Queue">
            <i class="bi bi-arrow-left fs-5"></i>
          </a>
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 52px; height: 52px; font-size: 1.5rem; flex-shrink: 0;">
            <i class="bi bi-person-vcard-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Application Evaluation</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-hash text-muted"></i>APP-<?= str_pad($app['id'], 5, '0', STR_PAD_LEFT) ?>
              </span>
              <span class="badge <?= esc($badgeClass) ?> border border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <?= esc($statusLabel) ?>
              </span>
            </div>
            <p class="text-muted small mb-0">Applicant: <strong class="text-dark"><?= htmlspecialchars($app['last_name'] . ', ' . $app['first_name'], ENT_QUOTES, 'UTF-8') ?></strong> &bull; Evaluation for <strong class="text-primary"><?= htmlspecialchars($app['scholarship_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="scholarship_review.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-inbox text-warning"></i>
            <span>Review Queue</span>
          </a>
        </div>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
        <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
        <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <form action="scholarship_process.php" method="POST">
      <input type="hidden" name="action" value="process_application">
      <input type="hidden" name="application_id" value="<?= esc($app['id']) ?>">
      <input type="hidden" name="user_id" value="<?= esc($app['user_id']) ?>">
      <input type="hidden" name="scholarship_id" value="<?= esc($app['scholarship_id']) ?>">
      <?= getCsrfInput() ?>
      
      <div class="row g-4">
        
        <div class="col-lg-8">
        
          <!-- Applicant & Scholarship Details Dossier Card -->
          <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.1s;">
            <div class="dossier-card-header d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2.5">
                <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                  <i class="bi bi-person-vcard-fill"></i>
                </div>
                <div>
                  <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Applicant & Scholarship Details</h2>
                </div>
              </div>
            </div>
            
            <div class="p-4">
              <div class="row g-3 mb-4">
                <div class="col-md-4">
                  <label class="text-muted small fw-semibold text-uppercase">Full Name</label>
                  <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($app['last_name'] . ', ' . $app['first_name'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="col-md-4">
                  <label class="text-muted small fw-semibold text-uppercase">Email Address</label>
                  <div class="fw-medium text-dark"><?= htmlspecialchars($app['email'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="col-md-4">
                  <label class="text-muted small fw-semibold text-uppercase">Applied For Term</label>
                  <div>
                    <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold">
                      <?= htmlspecialchars($app['ay_name'] ?? 'Current Term', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($app['semester'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </div>
                </div>
              </div>
              
              <div class="p-3 bg-light rounded-4 border">
                <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                  <div>
                    <label class="text-muted small fw-semibold text-uppercase d-block mb-1">Target Scholarship Program</label>
                    <div class="fw-bold fs-5 text-primary"><?= htmlspecialchars($app['scholarship_name'], ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                  <span class="badge bg-white text-dark border rounded-pill px-3 py-1 fw-semibold shadow-xs">
                    <i class="bi bi-tag-fill me-1 text-primary"></i><?= htmlspecialchars($app['category'] ?? 'Institutional', ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </div>
                <?php if (!empty($app['description'])): ?>
                  <p class="text-muted small mb-0 mt-2"><?= htmlspecialchars($app['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
              </div>
              
            </div>
          </div>

          <!-- Submitted Documents Dossier Card -->
          <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.15s;">
            <div class="dossier-card-header d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2.5">
                <div class="dossier-header-icon bg-info bg-opacity-10 text-info">
                  <i class="bi bi-file-earmark-check-fill"></i>
                </div>
                <div>
                  <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Submitted Verification Documents</h2>
                </div>
              </div>
            </div>
            
            <div class="p-4">
              <?php
                $docs = json_decode($app['submitted_documents'] ?? '[]', true);
                if (empty($docs)):
              ?>
                <div class="text-center py-4 text-muted">
                  <i class="bi bi-info-circle fs-3 text-secondary d-block mb-2"></i>
                  <p class="mb-0">No documents uploaded or submitted electronically for this application.</p>
                </div>
              <?php else: ?>
                <div class="list-group list-group-flush rounded-3 border">
                  <?php foreach($docs as $doc): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-3">
                      <div class="d-flex align-items-center gap-2.5">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                          <i class="bi bi-file-earmark-pdf fs-5"></i>
                        </div>
                        <div>
                          <div class="fw-semibold text-dark"><?= htmlspecialchars($doc['name'] ?? 'Document', ENT_QUOTES, 'UTF-8') ?></div>
                          <span class="text-muted extra-small">Uploaded Attachment</span>
                        </div>
                      </div>
                      <a href="<?= htmlspecialchars($doc['url'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 extra-small fw-medium d-inline-flex align-items-center gap-1">
                        <i class="bi bi-box-arrow-up-right"></i>
                        <span>View Document</span>
                      </a>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

        </div>

        <!-- Sticky Admin Action Panel -->
        <div class="col-lg-4">
          <div class="dossier-card sticky-top fade-in-up" style="top: 80px; animation-delay: 0.2s;">
            <div class="dossier-card-header">
              <div class="d-flex align-items-center gap-2.5">
                <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                  <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                  <h2 class="h6 fw-bold text-dark mb-0">Evaluation & Action</h2>
                </div>
              </div>
            </div>
            
            <div class="p-4">
              <div class="mb-3">
                <label for="status" class="form-label fw-semibold small text-dark">Decision Status <span class="text-danger">*</span></label>
                <select name="status" id="status" class="form-select" required>
                  <option value="pending" <?= esc($app['status'] === 'pending' ? 'selected' : '') ?>>Pending</option>
                  <option value="under_review" <?= esc($app['status'] === 'under_review' ? 'selected' : '') ?>>Under Review</option>
                  <option value="approved" <?= esc($app['status'] === 'approved' ? 'selected' : '') ?>>Approve Scholarship</option>
                  <option value="rejected" <?= esc($app['status'] === 'rejected' ? 'selected' : '') ?>>Reject Scholarship</option>
                </select>
                <?php if ($app['status'] === 'approved'): ?>
                  <div class="form-text text-danger mt-2 small">
                    <i class="bi bi-info-circle me-1"></i>Note: Changing from Approved will not automatically cancel active grants; manage them in the Active Scholars roster.
                  </div>
                <?php else: ?>
                  <div class="form-text text-success mt-2 small">
                    <i class="bi bi-check2-circle me-1"></i>Approving this will enroll the student into the active scholar registry and apply the tuition discount.
                  </div>
                <?php endif; ?>
              </div>

              <div class="mb-4">
                <label for="admin_feedback" class="form-label fw-semibold small text-dark">Review Feedback (Visible to Student)</label>
                <textarea name="admin_feedback" id="admin_feedback" rows="4" class="form-control" placeholder="Add remarks or justification for this decision..."><?= htmlspecialchars($app['admin_feedback'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
              </div>

              <div class="d-grid">
                <button type="submit" class="btn btn-primary fw-medium rounded-pill py-2 shadow-sm d-inline-flex align-items-center justify-content-center gap-2" id="submitBtn">
                  <i class="bi bi-save2"></i>
                  <span>Save Decision</span>
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
