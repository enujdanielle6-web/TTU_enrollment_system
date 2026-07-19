<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('*');

$pageTitle = 'Backup & Restore - Administrator';

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Database Backup & Restore</h1>
        <p class="text-muted mb-0">Securely export the enrollment database or restore from a previous state.</p>
      </div>
      <div>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="row g-4">
      
      <!-- Export Backup Island -->
      <div class="col-lg-6">
        <div class="island position-relative overflow-hidden border-0 shadow-sm h-100" style="border-radius: 16px;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light bg-primary-light mb-3">
            <i class="bi bi-cloud-download text-primary"></i>
            <h2 class="text-primary">Generate Database Backup</h2>
          </div>
          <div class="island-body d-flex flex-column justify-content-between">
            <div>
              <p class="text-dark">Download a complete snapshot of the system's database. This includes all users, applications, uploaded file paths, and settings.</p>
              <div class="alert alert-info border-0 bg-info bg-opacity-10 text-dark small rounded-3 mt-3">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                The generated file will be a standard <code>.sql</code> script which can be used to completely restore this system or migrate to another server.
              </div>
            </div>
            <div class="mt-4 pt-3 border-top border-light">
              <form action="backup_process.php" method="POST">
                <input type="hidden" name="action" value="export">
                <?= getCsrfInput() ?>
                <button type="submit" class="btn btn-primary fw-medium shadow-sm px-4 rounded-pill">
                  <i class="bi bi-download me-2"></i> Download Backup File
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Restore Database Island -->
      <div class="col-lg-6">
        <div class="island position-relative overflow-hidden border-0 shadow-sm h-100" style="border-radius: 16px;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="island-header border-bottom border-light bg-danger bg-opacity-10 mb-3">
            <i class="bi bi-exclamation-triangle-fill text-danger"></i>
            <h2 class="text-danger">Restore Database</h2>
          </div>
          <div class="island-body d-flex flex-column justify-content-between">
            <div>
              <p class="text-dark fw-medium mb-2">Upload a previously generated <code>.sql</code> backup file to revert the system state.</p>
              <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger small rounded-3 mt-3">
                <strong><i class="bi bi-shield-x me-1"></i> Warning: Destructive Action!</strong><br>
                Restoring a database will permanently overwrite and erase all current data in the system. Ensure you have backed up any recent changes before proceeding.
              </div>
            </div>
            <div class="mt-4 pt-3 border-top border-light">
              <form action="backup_process.php" method="POST" enctype="multipart/form-data" id="restoreForm">
                <input type="hidden" name="action" value="import">
                <?= getCsrfInput() ?>
                <div class="mb-3">
                  <label for="backupFile" class="form-label fw-semibold text-dark small">Select Backup File (.sql)</label>
                  <input type="file" class="form-control" id="backupFile" name="backup_file" accept=".sql" required>
                </div>
                <button type="submit" class="btn btn-danger fw-medium shadow-sm px-4 rounded-pill" id="restoreBtn">
                  <i class="bi bi-cloud-arrow-up me-2"></i> Restore System Data
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="confirmRestoreModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-octagon-fill me-2"></i>Confirm Database Restore</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you absolutely sure you want to restore the database from this file?</p>
        <p class="mb-0 fw-semibold text-dark">This action cannot be undone and will overwrite all existing records!</p>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light fw-medium rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger fw-medium rounded-pill px-4 shadow-sm" id="confirmRestoreExecuteBtn">
          Yes, Overwrite Data
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  $(function() {
    $('#restoreForm').on('submit', function(e) {
      e.preventDefault();
      if ($('#backupFile').val() === '') {
        alert('Please select a file to restore.');
        return;
      }
      var modal = new bootstrap.Modal(document.getElementById('confirmRestoreModal'));
      modal.show();
    });

    $('#confirmRestoreExecuteBtn').on('click', function() {
      // Disable button to prevent double submission
      $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Restoring...');
      $('#restoreForm')[0].submit();
    });
  });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

