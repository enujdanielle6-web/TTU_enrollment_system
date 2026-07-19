<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('*');

$pageTitle = 'System Settings - Administrator';

// Fetch global settings
try {
    $settingsStmt = $pdo->query('SELECT * FROM system_settings');
    $rawSettings = $settingsStmt->fetchAll();
    
    // Map settings into key-value pairs
    $settings = [];
    foreach ($rawSettings as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log('Settings fetch failed: ' . $e->getMessage());
    $settings = [];
}

// Fetch active announcements
try {
    $announcementsStmt = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC');
    $announcements = $announcementsStmt->fetchAll();
} catch (PDOException $e) {
    error_log('Announcements fetch failed: ' . $e->getMessage());
    $announcements = [];
}

// Flash messages
$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">System Settings</h1>
          <p class="text-muted mb-0">Configure global parameters and enrollment controls.</p>
        </div>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="row g-4">
      
      <!-- System Configurations -->
      <div class="col-lg-5">
        <div class="island position-relative overflow-hidden border-0 shadow-sm h-100 rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light">
            <i class="bi bi-sliders text-primary"></i>
            <h2 class="mb-0 text-dark">Global Parameters</h2>
          </div>
          <div class="island-body p-4">
            <form action="settings_process.php" method="POST">
              <input type="hidden" name="action" value="update_settings">
              <?= getCsrfInput() ?>
              
              <div class="mb-4">
                <label for="active_school_year" class="form-label fw-semibold text-dark small">Active School Year</label>
                <input type="text" id="active_school_year" name="active_school_year" class="form-control bg-light" value="<?= htmlspecialchars($settings['active_school_year'] ?? '2026-2027', ENT_QUOTES, 'UTF-8') ?>" required>
                <div class="form-text">The default academic year automatically assigned to new applicant records.</div>
              </div>

              <div class="mb-4">
                <label for="enrollment_status" class="form-label fw-semibold text-dark small">Enrollment Gateway Status</label>
                <select id="enrollment_status" name="enrollment_status" class="form-select bg-light">
                  <option value="open" <?= ($settings['enrollment_status'] ?? 'open') === 'open' ? 'selected' : '' ?>>Open for Applications</option>
                  <option value="closed" <?= ($settings['enrollment_status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed / Paused</option>
                </select>
                <div class="form-text">If closed, applicants cannot submit new enrollment forms.</div>
              </div>

              <div class="mb-4">
                <label for="college_cost_per_unit" class="form-label fw-semibold text-dark small">College Cost Per Unit (PHP)</label>
                <input type="number" step="0.01" min="0" id="college_cost_per_unit" name="college_cost_per_unit" class="form-control bg-light" value="<?= htmlspecialchars($settings['college_cost_per_unit'] ?? '500.00', ENT_QUOTES, 'UTF-8') ?>" required>
                <div class="form-text">Base rate multiplied by total enrolled units for college students.</div>
              </div>

              <hr class="my-4 border-light">
              
              <button type="submit" class="btn btn-primary w-100 fw-medium shadow-sm">
                <i class="bi bi-floppy-fill me-1"></i> Save Configurations
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Announcements Manager -->
      <div class="col-lg-7">
        <div class="island position-relative overflow-hidden border-0 shadow-sm h-100 rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-info" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
              <i class="bi bi-megaphone-fill text-info me-2 fs-4"></i>
              <h2 class="mb-0 text-dark">Portal Announcements</h2>
            </div>
            <button type="button" class="btn btn-sm btn-info text-white fw-medium shadow-sm px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
              <i class="bi bi-plus-lg me-1"></i> New Post
            </button>
          </div>
          <div class="island-body p-4">
            <?php if (empty($announcements)): ?>
              <div class="text-center py-5 text-muted">
                <i class="bi bi-chat-square-quote fs-1 d-block mb-3 text-secondary"></i>
                <p>No announcements found.</p>
              </div>
            <?php else: ?>
              <ul class="list-group list-group-flush rounded-bottom-4 border">
                <?php foreach ($announcements as $ann): ?>
                  <li class="list-group-item p-4">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <div class="mb-2">
                          <span class="badge bg-<?= htmlspecialchars($ann['badge_color'], ENT_QUOTES, 'UTF-8') ?> rounded-pill px-2 py-1 small fw-medium">
                            <?= htmlspecialchars($ann['badge_label'], ENT_QUOTES, 'UTF-8') ?>
                          </span>
                          <?php if (!(int)$ann['is_active']): ?>
                            <span class="badge bg-secondary rounded-pill px-2 py-1 small fw-medium ms-1">Inactive</span>
                          <?php endif; ?>
                        </div>
                        <h3 class="h6 fw-bold text-dark mb-1"><?= htmlspecialchars($ann['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="text-muted small mb-2"><?= nl2br(htmlspecialchars($ann['content'], ENT_QUOTES, 'UTF-8')) ?></p>
                        <div class="small text-muted" style="font-size: 0.75rem;">
                          Posted: <?= date('M j, Y g:i A', strtotime($ann['created_at'])) ?>
                        </div>
                      </div>
                      
                      <div class="ms-3">
                        <form action="settings_process.php" method="POST" class="d-inline">
                          <input type="hidden" name="action" value="toggle_announcement">
                          <?= getCsrfInput() ?>
                          <input type="hidden" name="id" value="<?= $ann['id'] ?>">
                          <input type="hidden" name="status" value="<?= (int)$ann['is_active'] ? '0' : '1' ?>">
                          <button type="submit" class="btn btn-sm btn-outline-secondary rounded-circle" style="width:32px; height:32px; padding:0;" title="<?= (int)$ann['is_active'] ? 'Deactivate' : 'Activate' ?>">
                            <i class="bi <?= (int)$ann['is_active'] ? 'bi-eye-slash-fill' : 'bi-eye-fill' ?>"></i>
                          </button>
                        </form>
                      </div>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
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
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark" id="newAnnouncementModalLabel">Create Announcement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="settings_process.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="add_announcement">
          <?= getCsrfInput() ?>
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-dark">Badge Label</label>
              <input type="text" name="badge_label" class="form-control form-control-sm bg-light" placeholder="e.g. Important" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small text-dark">Badge Color</label>
              <select name="badge_color" class="form-select form-select-sm bg-light">
                <option value="primary">Primary (Blue)</option>
                <option value="danger">Danger (Red)</option>
                <option value="warning">Warning (Yellow)</option>
                <option value="success">Success (Green)</option>
                <option value="info">Info (Light Blue)</option>
                <option value="dark">Dark (Black)</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold small text-dark">Title</label>
              <input type="text" name="title" class="form-control form-control-sm bg-light" placeholder="Announcement Title" required>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold small text-dark">Message Content</label>
              <textarea name="content" class="form-control form-control-sm bg-light" rows="4" placeholder="Write your announcement here..." required></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light fw-medium rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info text-white fw-medium rounded-pill px-4 shadow-sm">Post Announcement</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

