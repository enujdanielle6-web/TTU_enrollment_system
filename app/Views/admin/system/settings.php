<?php
$pageTitle = 'System Settings - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip (Design System Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-sliders"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">System Global Settings</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-gear-fill me-1"></i> Core Configuration
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-calendar-check text-primary me-1"></i> AY <?= esc($settings['active_school_year'] ?? '2026–2027') ?>
              </span>
              <span class="badge <?= ($settings['enrollment_status'] ?? 'open') === 'open' ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' ?> rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> Gateway: <?= ucfirst($settings['enrollment_status'] ?? 'open') ?>
              </span>
            </div>
            <p class="text-muted small mb-0">Manage global institutional parameters, applicant registration gates, and institutional student portal announcements.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="sysadmin_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>Dashboard</span>
          </a>
          <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
            <i class="bi bi-megaphone-fill"></i>
            <span>New Announcement</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($successMsg): ?>
      <div class="alert alert-success d-flex align-items-center shadow-sm rounded-12 fade-in-up mb-4" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2.5 text-success"></i>
        <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-12 fade-in-up mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2.5 text-danger"></i>
        <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      
      <!-- System Configurations -->
      <div class="col-lg-5">
        <div class="dossier-card h-100 fade-in-up" style="animation-delay: 0.1s;">
          <div class="dossier-card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-sliders"></i>
              </div>
              <h2 class="h6 fw-bold text-dark mb-0">Global Parameters</h2>
            </div>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
              Live Environment
            </span>
          </div>
          <div class="p-4">
            <form action="settings_process.php" method="POST">
              <input type="hidden" name="action" value="update_settings">
              <?= getCsrfInput() ?>
              
              <div class="mb-4">
                <label for="active_school_year" class="form-label fw-bold text-dark small">Active Academic School Year <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-white text-muted"><i class="bi bi-calendar3"></i></span>
                  <input type="text" id="active_school_year" name="active_school_year" class="form-control bg-white" value="<?= htmlspecialchars($settings['active_school_year'] ?? '2026-2027', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-text extra-small text-muted mt-1">Default school year automatically attributed to new admissions and curriculum cohorts.</div>
              </div>

              <div class="mb-4">
                <label for="enrollment_status" class="form-label fw-bold text-dark small">Enrollment Gateway Status <span class="text-danger">*</span></label>
                <select id="enrollment_status" name="enrollment_status" class="form-select bg-white">
                  <option value="open" <?= esc(($settings['enrollment_status'] ?? 'open') === 'open' ? 'selected' : '') ?>>Open for Applications</option>
                  <option value="closed" <?= esc(($settings['enrollment_status'] ?? '') === 'closed' ? 'selected' : '') ?>>Closed / Paused</option>
                </select>
                <div class="form-text extra-small text-muted mt-1">When closed, the public enrollment registration portal disables new form submissions.</div>
              </div>

              <hr class="my-4 border-light">
              
              <button type="submit" class="btn btn-primary w-100 fw-semibold rounded-pill py-2.5 shadow-sm d-inline-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-floppy-fill"></i>
                <span>Save Configurations</span>
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Announcements Manager -->
      <div class="col-lg-7">
        <div class="dossier-card h-100 fade-in-up" style="animation-delay: 0.15s;">
          <div class="dossier-card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <div class="dossier-header-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-megaphone-fill"></i>
              </div>
              <h2 class="h6 fw-bold text-dark mb-0">Portal Announcements</h2>
            </div>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold">
              <?= count($announcements) ?> Posts
            </span>
          </div>
          <div class="p-4">
            <?php if (empty($announcements)): ?>
              <div class="text-center py-5 text-muted">
                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-xs mx-auto" style="width: 64px; height: 64px;">
                  <i class="bi bi-chat-square-quote fs-2 text-muted"></i>
                </div>
                <h3 class="h6 fw-bold text-dark mb-1">No Announcements Published</h3>
                <p class="text-muted small mb-0">Create an announcement to broadcast bulletins to applicant portals.</p>
              </div>
            <?php else: ?>
              <div class="d-flex flex-column gap-3">
                <?php foreach ($announcements as $ann): ?>
                  <div class="p-3 bg-white border rounded-3 shadow-xs position-relative">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                      <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-<?= htmlspecialchars($ann['badge_color'], ENT_QUOTES, 'UTF-8') ?> rounded-pill px-2.5 py-1 small fw-semibold">
                          <?= htmlspecialchars($ann['badge_label'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php if (!(int)$ann['is_active']): ?>
                          <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2 py-0.5 extra-small">Hidden</span>
                        <?php else: ?>
                          <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-0.5 extra-small">Published</span>
                        <?php endif; ?>
                      </div>
                      
                      <form action="settings_process.php" method="POST" class="d-inline">
                        <input type="hidden" name="action" value="toggle_announcement">
                        <?= getCsrfInput() ?>
                        <input type="hidden" name="id" value="<?= esc($ann['id']) ?>">
                        <input type="hidden" name="status" value="<?= esc((int)$ann['is_active'] ? '0' : '1') ?>">
                        <button type="submit" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 extra-small" title="<?= esc((int)$ann['is_active'] ? 'Deactivate' : 'Activate') ?>">
                          <i class="bi <?= esc((int)$ann['is_active'] ? 'bi-eye-slash-fill text-muted' : 'bi-eye-fill text-primary') ?> me-1"></i>
                          <span><?= esc((int)$ann['is_active'] ? 'Hide' : 'Show') ?></span>
                        </button>
                      </form>
                    </div>

                    <h3 class="h6 fw-bold text-dark mb-1"><?= htmlspecialchars($ann['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="text-muted small mb-2"><?= nl2br(htmlspecialchars($ann['content'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <div class="extra-small text-muted d-flex align-items-center gap-1">
                      <i class="bi bi-clock-history"></i>
                      <span>Posted on <?= date('M j, Y g:i A', strtotime($ann['created_at'])) ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>

<!-- New Announcement Modal -->
<div class="modal fade" id="newAnnouncementModal" tabindex="-1" aria-labelledby="newAnnouncementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-white border-bottom py-3">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="newAnnouncementModalLabel">
          <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-circle me-3" style="width: 36px; height: 36px;">
            <i class="bi bi-megaphone-fill fs-5"></i>
          </div>
          Create Announcement Post
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="settings_process.php" method="POST">
        <div class="modal-body p-4 bg-light">
          <input type="hidden" name="action" value="add_announcement">
          <?= getCsrfInput() ?>
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold small text-dark">Badge Label <span class="text-danger">*</span></label>
              <input type="text" name="badge_label" class="form-control bg-white" placeholder="e.g. Important, Urgent" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small text-dark">Badge Theme Color <span class="text-danger">*</span></label>
              <select name="badge_color" class="form-select bg-white">
                <option value="primary">Primary (Blue)</option>
                <option value="danger">Danger (Red)</option>
                <option value="warning">Warning (Yellow)</option>
                <option value="success">Success (Green)</option>
                <option value="info">Info (Cyan)</option>
                <option value="dark">Dark (Gray)</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold small text-dark">Announcement Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control bg-white" placeholder="e.g. Enrollment Period Extended" required>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold small text-dark">Message Content <span class="text-danger">*</span></label>
              <textarea name="content" class="form-control bg-white" rows="4" placeholder="Detailed message for students and applicants..." required></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 bg-light d-flex justify-content-between">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold">
            <i class="bi bi-send me-1"></i> Post Announcement
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
