<?php
$pageTitle = 'Medical Clearance - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';

$statusFilter = $statusFilter ?? ($_GET['status'] ?? 'all');
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-file-medical-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Medical Clearance</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> Medical Officer
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-layers text-primary me-1"></i><?= count($records ?? []) ?> Records
              </span>
            </div>
            <p class="text-muted small mb-0">Review submitted health information and update medical clearance status.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="clinic_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-speedometer2 text-primary"></i>
            <span>Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($successMsg)): ?>
      <div class="alert alert-success d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
        <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
        <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <!-- Search & Filter Console Card (Consistent with Admissions) -->
    <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.1s;">
      <div class="dossier-card-header py-2.5 px-3 bg-light border-bottom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-funnel-fill text-primary"></i>
          <span class="fw-bold small text-uppercase text-secondary">Filters & Search</span>
        </div>
        <?php if ($statusFilter !== 'all'): ?>
          <a href="medical_clearance.php" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 extra-small d-inline-flex align-items-center gap-1">
            <i class="bi bi-x-circle"></i> Reset Filters
          </a>
        <?php endif; ?>
      </div>
      <div class="p-3">
        <form action="medical_clearance.php" method="GET" class="row g-2.5 align-items-center" id="filterForm">
          <!-- Search Input -->
          <div class="col-lg-4 col-md-6">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
              <input type="text" id="queueSearch" class="form-control form-control-sm border-start-0 ps-0" placeholder="Search applicant, ref, program...">
            </div>
          </div>

          <!-- Status Filter -->
          <div class="col-lg-3 col-md-6">
            <select name="status" class="form-select form-select-sm bg-light" onchange="this.form.submit()">
              <option value="all" <?= esc($statusFilter === 'all' ? 'selected' : '') ?>>All Statuses</option>
              <option value="pending" <?= esc($statusFilter === 'pending' ? 'selected' : '') ?>>Pending Review</option>
              <option value="verified" <?= esc($statusFilter === 'verified' ? 'selected' : '') ?>>Verified</option>
              <option value="correction_required" <?= esc($statusFilter === 'correction_required' ? 'selected' : '') ?>>Correction Required</option>
              <option value="rejected" <?= esc($statusFilter === 'rejected' ? 'selected' : '') ?>>Rejected</option>
            </select>
          </div>
        </form>
      </div>
    </div>

    <!-- Health Records Queue Card -->
    <div class="dossier-card fade-in-up" style="animation-delay: 0.15s;">
      <div class="dossier-card-header">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-file-medical-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Health Records Queue</h2>
          </div>
        </div>
      </div>
      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table" id="queueTable">
            <thead>
              <tr>
                <th class="ps-4">Applicant</th>
                <th>Reference No.</th>
                <th>Academic Program</th>
                <th>Status</th>
                <th>Last Updated</th>
                <th class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($records)): ?>
                <tr>
                  <td colspan="6" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">No Health Records Found</h3>
                      <p class="small text-muted mb-0">No records matching the filter "<?= htmlspecialchars($statusFilter, ENT_QUOTES, 'UTF-8') ?>".</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($records as $record): 
                  $firstName = trim($record['first_name'] ?? '');
                  $lastName = trim($record['last_name'] ?? '');
                  $fullName = trim($firstName . ' ' . $lastName);
                  if ($fullName === '') $fullName = 'Unknown Applicant';
                  $initials = strtoupper(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1));
                  if ($initials === '') $initials = 'AP';

                  $rawStatus = strtolower($record['status'] ?? 'pending');
                  $statusBadge = match($rawStatus) {
                      'verified' => 'bg-success bg-opacity-10 text-success border-success border-opacity-25',
                      'rejected' => 'bg-danger bg-opacity-10 text-danger border-danger border-opacity-25',
                      'pending' => 'bg-warning bg-opacity-10 text-warning border-warning border-opacity-25',
                      'correction_required' => 'bg-info bg-opacity-10 text-info border-info border-opacity-25',
                      'under_review' => 'bg-info bg-opacity-10 text-info border-info border-opacity-25',
                      default => 'bg-secondary bg-opacity-10 text-secondary border-secondary border-opacity-25'
                  };
                  $statusIcon = match($rawStatus) {
                      'verified' => 'bi-check-circle-fill',
                      'rejected' => 'bi-x-circle-fill',
                      'pending' => 'bi-hourglass-split',
                      'correction_required' => 'bi-exclamation-triangle-fill',
                      'under_review' => 'bi-eye-fill',
                      default => 'bi-info-circle-fill'
                  };
                ?>
                  <tr class="queue-row" data-search="<?= esc(strtolower($fullName . ' ' . $record['reference_number'] . ' ' . ($record['strand'] ?? '') . ' ' . ($record['academic_level'] ?? ''))) ?>">
                    <td class="ps-4">
                      <div class="d-flex align-items-center gap-2.5">
                        <div class="applicant-avatar">
                          <?= esc($initials) ?>
                        </div>
                        <div>
                          <span class="fw-bold text-dark d-block"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="applicant-ref-badge">
                        <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($record['reference_number'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-pill fw-semibold small d-inline-flex align-items-center gap-1">
                        <i class="bi bi-mortarboard text-primary"></i><?= htmlspecialchars(strtoupper(getStrandLabel($record['strand'] ?? '') ?: ($record['academic_level'] ?? 'N/A')), ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge <?= esc($statusBadge) ?> border px-2.5 py-1.5 rounded-pill small fw-semibold d-inline-flex align-items-center gap-1">
                        <i class="bi <?= esc($statusIcon) ?>"></i>
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $record['status'])), ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>
                    <td>
                      <div class="d-flex flex-column">
                        <span class="fw-semibold text-dark small">
                          <i class="bi bi-calendar-event text-muted me-1"></i><?= date('M j, Y', strtotime($record['updated_at'])) ?>
                        </span>
                        <span class="text-muted extra-small">
                          <i class="bi bi-clock text-muted me-1"></i><?= date('g:i A', strtotime($record['updated_at'])) ?>
                        </span>
                      </div>
                    </td>
                    <td class="text-end pe-4">
                      <a href="medical_detail.php?id=<?= esc($record['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-folder2-open"></i> Review
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>

              <tr id="queueNoMatchRow" style="display: none;">
                <td colspan="6" class="text-center py-5">
                  <div class="d-flex flex-column align-items-center justify-content-center text-muted">
                    <i class="bi bi-search fs-2 mb-2 text-secondary"></i>
                    <div class="fw-bold text-dark fs-6 mb-1">No Matching Candidates</div>
                    <p class="small text-muted mb-0">Try clearing your search keyword.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</main>

<script>
(function() {
  function initQueueFilter() {
    const searchInput = document.getElementById('queueSearch');
    const rows = document.querySelectorAll('#queueTable tbody tr.queue-row');
    const noMatchRow = document.getElementById('queueNoMatchRow');

    if (!rows.length || !searchInput) return;

    searchInput.addEventListener('input', function() {
      const q = this.value.trim().toLowerCase();
      let visible = 0;

      rows.forEach(r => {
        const text = r.getAttribute('data-search') || '';
        if (!q || text.includes(q)) {
          r.style.display = '';
          visible++;
        } else {
          r.style.display = 'none';
        }
      });

      if (noMatchRow) {
        noMatchRow.style.display = (visible === 0) ? '' : 'none';
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initQueueFilter);
  } else {
    initQueueFilter();
  }
  document.addEventListener('spa:navigated', initQueueFilter);
})();
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
