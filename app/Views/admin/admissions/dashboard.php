<?php
$pageTitle = 'Admissions Dashboard - Triple T University';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';

$showMedical = !empty($stats['pending_medical']) || (function_exists('hasPermission') && hasPermission('medical.review'));
$colClass = $showMedical ? 'col-lg-4 col-md-6' : 'col-md-6';
?>
<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">

    <!-- Admissions Dashboard Hero Header -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.1s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-person-badge-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Admissions Dashboard</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> Admissions Officer
              </span>
            </div>
            <p class="text-muted small mb-0">Overview of pending applications, documents, and medical clearances.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="review.php" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-folder2-open"></i>
            <span>Open Review Queue</span>
            <i class="bi bi-arrow-right small"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Executive KPI Metric Cards -->
    <div class="row g-4 mb-4">
      <div class="<?= esc($colClass) ?>">
        <a href="review.php?status=pending" class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow bg-warning"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-hourglass-split"></i>
            </div>
            <?php if (($stats['pending_apps'] ?? 0) > 0): ?>
              <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-clock-history me-1"></i> Awaiting Decision
              </span>
            <?php else: ?>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-check2-circle me-1"></i> Queue Clear
              </span>
            <?php endif; ?>
          </div>
          <div class="stat-number-display mb-1"><?= esc($stats['pending_apps'] ?? 0) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Pending Applications</h2>
          <p class="text-muted small mb-0">Applicant submissions awaiting evaluation & verification</p>
          <div class="stat-card-footer">
            <span>Intake queue</span>
            <span class="stat-card-action text-warning">Review Queue <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <div class="<?= esc($colClass) ?>">
        <a href="review.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.3s;">
          <div class="stat-card-glow bg-info"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
              <i class="bi bi-file-earmark-check"></i>
            </div>
            <?php if (($stats['pending_docs'] ?? 0) > 0): ?>
              <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-exclamation-circle me-1"></i> Verification Needed
              </span>
            <?php else: ?>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-check2-circle me-1"></i> All Verified
              </span>
            <?php endif; ?>
          </div>
          <div class="stat-number-display mb-1"><?= esc($stats['pending_docs'] ?? 0) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Pending Documents</h2>
          <p class="text-muted small mb-0">Uploaded credentials and certificates awaiting validation</p>
          <div class="stat-card-footer">
            <span>Credential audit</span>
            <span class="stat-card-action text-info">Inspect Files <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <?php if ($showMedical): ?>
      <div class="<?= esc($colClass) ?>">
        <a href="../clinic/medical_records.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.4s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-heart-pulse"></i>
            </div>
            <?php if (($stats['pending_medical'] ?? 0) > 0): ?>
              <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-heart-pulse-fill me-1"></i> Clinic Action
              </span>
            <?php else: ?>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-check2-circle me-1"></i> All Cleared
              </span>
            <?php endif; ?>
          </div>
          <div class="stat-number-display mb-1"><?= esc($stats['pending_medical'] ?? 0) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Pending Medical Clearances</h2>
          <p class="text-muted small mb-0">Health records and clinic forms awaiting medical review</p>
          <div class="stat-card-footer">
            <span>Clinic queue</span>
            <span class="stat-card-action text-success">View Medical <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>
      <?php endif; ?>
    </div>

    <!-- Recent Applicants -->
    <div class="dossier-card fade-in-up" style="animation-delay: 0.5s;">
      <div class="dossier-card-header">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-people-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Recent Applicants</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <i class="bi bi-activity text-primary me-1"></i><?= count($recent_apps) ?> Records
            </span>
          </div>
        </div>
        <div>
          <a href="review.php" class="btn btn-sm btn-light border rounded-pill px-3 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-none">
            <i class="bi bi-list-ul me-1"></i>
            <span>View Full Queue</span>
            <i class="bi bi-chevron-right small text-muted"></i>
          </a>
        </div>
      </div>
      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table">
            <thead>
              <tr>
                <th class="ps-4">Reference</th>
                <th>Applicant Name</th>
                <th>Program / Strand</th>
                <th>Date Submitted</th>
                <th>Status</th>
                <th class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recent_apps)): ?>
              <tr>
                <td colspan="6" class="text-center py-5">
                  <div class="d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                      <i class="bi bi-inbox fs-1 text-muted"></i>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-1">No Recent Applications</h3>
                    <p class="text-muted small mb-0">New student applications will automatically appear here once submitted.</p>
                  </div>
                </td>
              </tr>
              <?php else: ?>
                <?php foreach ($recent_apps as $app): ?>
                <?php
                  $firstName = trim($app['first_name'] ?? '');
                  $lastName = trim($app['last_name'] ?? '');
                  $fullName = trim($firstName . ' ' . $lastName);
                  if ($fullName === '') {
                      $fullName = 'Unknown Applicant';
                  }
                  $firstInitial = mb_substr($firstName, 0, 1);
                  $lastInitial = mb_substr($lastName, 0, 1);
                  $initials = strtoupper($firstInitial . $lastInitial);
                  if ($initials === '') {
                      $initials = 'AP';
                  }

                  $rawStatus = strtolower($app['status'] ?? 'pending');
                  $statusBadge = match($rawStatus) {
                      'approved', 'verified' => 'bg-success bg-opacity-10 text-success border-success border-opacity-25',
                      'enrolled' => 'bg-success bg-opacity-10 text-success border-success border-opacity-25',
                      'rejected', 'failed' => 'bg-danger bg-opacity-10 text-danger border-danger border-opacity-25',
                      'pending' => 'bg-warning bg-opacity-10 text-warning border-warning border-opacity-25',
                      'correction_required' => 'bg-warning bg-opacity-10 text-warning border-warning border-opacity-25',
                      'under_review' => 'bg-info bg-opacity-10 text-info border-info border-opacity-25',
                      default => 'bg-secondary bg-opacity-10 text-secondary border-secondary border-opacity-25'
                  };
                  $statusIcon = match($rawStatus) {
                      'approved', 'verified' => 'bi-check-circle-fill',
                      'enrolled' => 'bi-mortarboard-fill',
                      'rejected', 'failed' => 'bi-x-circle-fill',
                      'pending' => 'bi-hourglass-split',
                      'correction_required' => 'bi-exclamation-triangle-fill',
                      'under_review' => 'bi-eye-fill',
                      default => 'bi-info-circle-fill'
                  };
                ?>
                <tr>
                  <td class="ps-4">
                    <span class="applicant-ref-badge">
                      <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($app['reference_number'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td>
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
                    <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-pill fw-semibold small d-inline-flex align-items-center gap-1">
                      <i class="bi bi-mortarboard text-primary"></i><?= htmlspecialchars(strtoupper($app['strand'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-semibold text-dark small">
                        <i class="bi bi-calendar-event text-muted me-1"></i><?= date('M j, Y', strtotime($app['created_at'])) ?>
                      </span>
                      <span class="text-muted extra-small">
                        <i class="bi bi-clock text-muted me-1"></i><?= date('g:i A', strtotime($app['created_at'])) ?>
                      </span>
                    </div>
                  </td>
                  <td>
                    <span class="badge <?= esc($statusBadge) ?> border px-2.5 py-1.5 rounded-pill small fw-semibold d-inline-flex align-items-center gap-1">
                      <i class="bi <?= esc($statusIcon) ?>"></i>
                      <?= htmlspecialchars(ucwords(str_replace('_', ' ', $app['status'])), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td class="text-end pe-4">
                    <a href="application_detail.php?id=<?= esc($app['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium d-inline-flex align-items-center gap-1.5">
                      <i class="bi bi-folder2-open"></i> View
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>



