<?php
$pageTitle = 'Database Backup & Restore - Administrator';
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
            <i class="bi bi-database-fill-gear"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Database Backup &amp; Restore</h1>
              <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-lock-fill me-1"></i> Super Admin Clearance
              </span>
              <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-server me-1"></i> MariaDB Engine
              </span>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-check-circle-fill me-1"></i> <?= (int)($dbStats['total_tables'] ?? 0) ?> Tables Governed
              </span>
            </div>
            <p class="text-muted small mb-0">Export atomic MariaDB database snapshots, manage institutional archival redundancy, or restore previous system states.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="sysadmin_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>Dashboard</span>
          </a>
          <a href="audit_logs.php?search=Database" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-medium d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-journal-text text-secondary"></i>
            <span>Backup History</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Flash Notifications -->
    <?php if (!empty($successMsg)): ?>
      <div class="alert alert-success border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
        <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <!-- KPI Architecture Metrics Row -->
    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
            <i class="bi bi-table"></i>
          </div>
          <div>
            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Total Tables</div>
            <div class="h4 fw-bold text-dark mb-0"><?= (int)($dbStats['total_tables'] ?? 0) ?></div>
            <div class="text-muted small" style="font-size: 0.75rem;">Relational Schemas</div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
            <i class="bi bi-hdd-network"></i>
          </div>
          <div>
            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Storage Footprint</div>
            <div class="h4 fw-bold text-dark mb-0"><?= htmlspecialchars($dbStats['db_size_mb'] ?? '0.00', ENT_QUOTES, 'UTF-8') ?> <span class="fs-6 fw-normal text-muted">MB</span></div>
            <div class="text-muted small" style="font-size: 0.75rem;">Data + Index Footprint</div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
            <i class="bi bi-shield-check"></i>
          </div>
          <div>
            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Integrity Engine</div>
            <div class="h4 fw-bold text-success mb-0">Active</div>
            <div class="text-muted small" style="font-size: 0.75rem;">Foreign Keys Governed</div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi p-3 rounded-4 bg-white border shadow-xs h-100 d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-3" style="width: 48px; height: 48px; font-size: 1.35rem; flex-shrink: 0;">
            <i class="bi bi-clock-history"></i>
          </div>
          <div style="min-width: 0;">
            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Latest DB Event</div>
            <div class="h6 fw-bold text-dark mb-0 text-truncate" title="<?= !empty($dbStats['last_event']) ? htmlspecialchars($dbStats['last_event']['title'], ENT_QUOTES, 'UTF-8') : 'None recorded' ?>">
              <?= !empty($dbStats['last_event']) ? htmlspecialchars($dbStats['last_event']['title'], ENT_QUOTES, 'UTF-8') : 'None recorded' ?>
            </div>
            <div class="text-muted small" style="font-size: 0.75rem;">
              <?= !empty($dbStats['last_event']['created_at']) ? date('M j, Y h:i A', strtotime($dbStats['last_event']['created_at'])) : 'No logs recorded' ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Operations Dual Column (Export & Restore) -->
    <div class="row g-4">
      
      <!-- Export Backup Dossier Card -->
      <div class="col-lg-6">
        <div class="dossier-card bg-white border rounded-4 shadow-sm p-4 h-100 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <div class="d-flex align-items-center gap-2.5">
                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 36px; height: 36px; font-size: 1.15rem;">
                  <i class="bi bi-cloud-download"></i>
                </div>
                <h2 class="h5 fw-bold text-dark mb-0">Generate Database Backup</h2>
              </div>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                SQL Export
              </span>
            </div>

            <p class="text-muted small mb-3">
              Download a complete relational snapshot of the Triple T University database. The generated <code class="text-primary fw-semibold">.sql</code> script contains DDL table schemas, indexes, foreign key overrides, and all entity records.
            </p>

            <div class="bg-light border rounded-3 p-3 mb-4">
              <div class="fw-semibold text-dark small mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-layers-fill text-primary"></i>
                <span>Snapshot Inclusions:</span>
              </div>
              <ul class="small text-muted mb-0 ps-3">
                <li class="mb-1">All 45 institutional tables, schema constraints, and primary keys.</li>
                <li class="mb-1">User credentials, RBAC roles, and granular permission matrices.</li>
                <li class="mb-1">Student applications, enrollment evaluations, and medical dossiers.</li>
                <li class="mb-1">Cashier payment ledgers, assessments, scholarships, and academic schedules.</li>
                <li>System activity audit logs and institutional announcements.</li>
              </ul>
            </div>

            <div class="alert alert-info border-0 bg-info bg-opacity-10 text-dark small rounded-3 mb-0 d-flex align-items-start gap-2">
              <i class="bi bi-info-circle-fill text-info mt-0.5 fs-6"></i>
              <div>
                Backups are generated dynamically with <code class="text-dark">FOREIGN_KEY_CHECKS=0</code> wrappers to ensure conflict-free restoration across foreign key hierarchies.
              </div>
            </div>
          </div>

          <div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="text-muted small">
              <i class="bi bi-shield-check text-success me-1"></i> Verified MariaDB dump
            </span>
            <form action="backup_process.php" method="POST" class="m-0">
              <input type="hidden" name="action" value="export">
              <?= getCsrfInput() ?>
              <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-download"></i>
                <span>Download Backup (.sql)</span>
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Restore Database Dossier Card -->
      <div class="col-lg-6">
        <div class="dossier-card bg-white border rounded-4 shadow-sm p-4 h-100 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
              <div class="d-flex align-items-center gap-2.5">
                <div class="d-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-3" style="width: 36px; height: 36px; font-size: 1.15rem;">
                  <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <h2 class="h5 fw-bold text-dark mb-0">Restore Database</h2>
              </div>
              <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                Destructive Action
              </span>
            </div>

            <p class="text-muted small mb-3">
              Upload and execute a previously generated <code class="text-danger fw-semibold">.sql</code> dump file to roll back or reconstruct the complete database state.
            </p>

            <!-- Prominent Warning Box -->
            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger small rounded-3 mb-4 p-3">
              <div class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-shield-x fs-6"></i>
                <span>Critical Warning: Irreversible Operation</span>
              </div>
              <div class="ps-4">
                Executing a restore will drop existing tables and replace all current operational data with the contents of the uploaded SQL script. Ensure you have downloaded an export of the current state before executing!
              </div>
            </div>

            <form action="backup_process.php" method="POST" enctype="multipart/form-data" id="restoreForm">
              <input type="hidden" name="action" value="import">
              <?= getCsrfInput() ?>

              <div class="mb-3">
                <label for="backupFile" class="form-label fw-semibold text-dark small">Select Backup File (.sql)</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border"><i class="bi bi-file-earmark-code text-primary"></i></span>
                  <input type="file" class="form-control" id="backupFile" name="backup_file" accept=".sql" required>
                </div>
                <div class="form-text small text-muted">
                  Only standard plain-text or utf8mb4 encoded <code>.sql</code> script files are accepted.
                </div>
              </div>

              <div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span class="text-muted small">
                  <i class="bi bi-lock-fill text-danger me-1"></i> Requires confirmation prompt
                </span>
                <button type="submit" class="btn btn-danger rounded-pill px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2" id="restoreBtn">
                  <i class="bi bi-cloud-arrow-up"></i>
                  <span>Restore System Data</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="confirmRestoreModal" tabindex="-1" aria-labelledby="confirmRestoreModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-danger text-white border-bottom-0 py-3">
        <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2" id="confirmRestoreModalLabel">
          <i class="bi bi-exclamation-octagon-fill fs-5"></i>
          <span>Confirm Database Restoration</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="text-center mb-3">
          <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle p-3 mb-2" style="width: 64px; height: 64px;">
            <i class="bi bi-radioactive fs-2"></i>
          </div>
          <h6 class="fw-bold text-dark">Are you absolutely sure you want to proceed?</h6>
        </div>
        <p class="text-muted small mb-2">
          This operation will execute raw SQL queries against your MariaDB database. All current records across all tables will be replaced by the contents of the selected file.
        </p>
        <div class="p-2.5 bg-light border rounded-3 small text-dark fw-medium d-flex align-items-center gap-2">
          <i class="bi bi-info-circle text-danger"></i>
          <span>This action cannot be undone once executed.</span>
        </div>
      </div>
      <div class="modal-footer border-top bg-light py-2.5 px-4 d-flex justify-content-between">
        <button type="button" class="btn btn-light border rounded-pill px-4 fw-medium text-secondary" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="button" class="btn btn-danger rounded-pill px-4 fw-semibold shadow-sm d-inline-flex align-items-center gap-2" id="confirmRestoreExecuteBtn">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>Yes, Overwrite &amp; Restore</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    'use strict';

    document.addEventListener('submit', function(e) {
      if (e.target && e.target.id === 'restoreForm') {
        const fileInput = document.getElementById('backupFile');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
          e.preventDefault();
          alert('Please select an .sql backup file to restore.');
          return;
        }

        const fileName = fileInput.files[0].name;
        if (!fileName.toLowerCase().endsWith('.sql')) {
          e.preventDefault();
          alert('Invalid file type. Please upload a file with a .sql extension.');
          return;
        }

        e.preventDefault();
        const modalEl = document.getElementById('confirmRestoreModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
          const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
          modal.show();
        }
      }
    });

    document.addEventListener('click', function(e) {
      const executeBtn = e.target.closest('#confirmRestoreExecuteBtn');
      if (executeBtn) {
        executeBtn.disabled = true;
        executeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Restoring Database...';
        const form = document.getElementById('restoreForm');
        if (form) {
          form.submit();
        }
      }
    });
  })();
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
