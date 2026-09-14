<?php
$pageTitle = 'Clinic Dashboard - Triple T University';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';

// Safe fallback data loading if variables were not pre-injected by controller
if (!isset($stats) || !isset($recent_records)) {
    $stats = [
        'pending' => 0,
        'verified' => 0,
        'correction_required' => 0,
    ];
    $recent_records = [];

    try {
        $statsStmt = $pdo->query('
            SELECT 
                COALESCE(SUM(CASE WHEN status IN ("pending", "under_review") THEN 1 ELSE 0 END), 0) as pending,
                COALESCE(SUM(CASE WHEN status = "verified" THEN 1 ELSE 0 END), 0) as verified,
                COALESCE(SUM(CASE WHEN status = "correction_required" THEN 1 ELSE 0 END), 0) as correction_required
            FROM health_records
        ');
        $rawStats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $stats['pending'] = (int)($rawStats['pending'] ?? 0);
        $stats['verified'] = (int)($rawStats['verified'] ?? 0);
        $stats['correction_required'] = (int)($rawStats['correction_required'] ?? 0);

        $recentStmt = $pdo->query('
            SELECT h.id, h.status, h.created_at, a.reference_number, a.academic_level, a.strand,
                   u.first_name, u.last_name
            FROM health_records h
            INNER JOIN applications a ON h.application_id = a.id
            INNER JOIN users u ON h.user_id = u.id
            ORDER BY h.created_at DESC LIMIT 8
        ');
        $recent_records = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Clinic dashboard fallback stats failed: ' . $e->getMessage());
    }
}

$pendingCount = (int)($stats['pending'] ?? 0);
$verifiedCount = (int)($stats['verified'] ?? 0);
$correctionCount = (int)($stats['correction_required'] ?? 0);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">

    <!-- Clinic Dashboard Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.1s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-heart-pulse-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">University Clinic Dashboard</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> Medical Officer
              </span>
            </div>
            <p class="text-muted small mb-0">Overview of pending and recent student medical clearances.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="medical_clearance.php" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-folder2-open"></i>
            <span>Open Clearance Queue</span>
            <i class="bi bi-arrow-right small"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Flash Notifications -->
    <?php if (!empty($successMsg)): ?>
      <div class="alert alert-success d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
        <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?></div>
      </div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
        <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
      </div>
    <?php endif; ?>

    <!-- Executive KPI Metric Cards (Identical to Admissions) -->
    <div class="row g-4 mb-4">
      
      <!-- Card 1: Pending Clearances -->
      <div class="col-lg-4 col-md-6">
        <a href="medical_clearance.php?status=pending" class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow bg-warning"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-hourglass-split"></i>
            </div>
            <?php if ($pendingCount > 0): ?>
              <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-clock-history me-1"></i> Awaiting Review
              </span>
            <?php else: ?>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-check2-circle me-1"></i> Queue Clear
              </span>
            <?php endif; ?>
          </div>
          <div class="stat-number-display mb-1"><?= esc($pendingCount) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Pending Clearances</h2>
          <p class="text-muted small mb-0">Student health submissions awaiting medical examination</p>
          <div class="stat-card-footer">
            <span>Medical review</span>
            <span class="stat-card-action text-warning">Review Queue <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 2: Total Verified -->
      <div class="col-lg-4 col-md-6">
        <a href="medical_clearance.php?status=verified" class="stat-card-kpi fade-in-up" style="animation-delay: 0.3s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-shield-check"></i>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-check2-circle me-1"></i> Certified
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= esc($verifiedCount) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Total Verified</h2>
          <p class="text-muted small mb-0">Health records verified & cleared for student enrollment</p>
          <div class="stat-card-footer">
            <span>Certified candidates</span>
            <span class="stat-card-action text-success">View Verified <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 3: Requires Correction -->
      <div class="col-lg-4 col-md-6">
        <a href="medical_clearance.php?status=correction_required" class="stat-card-kpi fade-in-up" style="animation-delay: 0.4s;">
          <div class="stat-card-glow bg-info"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
              <i class="bi bi-arrow-repeat"></i>
            </div>
            <?php if ($correctionCount > 0): ?>
              <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-exclamation-circle me-1"></i> Needs Follow-up
              </span>
            <?php else: ?>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-check2-circle me-1"></i> Zero Pending
              </span>
            <?php endif; ?>
          </div>
          <div class="stat-number-display mb-1"><?= esc($correctionCount) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Requires Correction</h2>
          <p class="text-muted small mb-0">Returned to applicant for document update or follow-up</p>
          <div class="stat-card-footer">
            <span>Action pending</span>
            <span class="stat-card-action text-info">Inspect Cases <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

    </div>

    <!-- Recent Submissions (Exact Dossier Card Styling from Admissions) -->
    <div class="dossier-card fade-in-up" style="animation-delay: 0.5s;">
      <div class="dossier-card-header">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-people-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Recent Submissions</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <i class="bi bi-activity text-primary me-1"></i><?= count($recent_records) ?> Records
            </span>
          </div>
        </div>
        <div>
          <a href="medical_clearance.php" class="btn btn-sm btn-light border rounded-pill px-3 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-none">
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
              <?php if (empty($recent_records)): ?>
              <tr>
                <td colspan="6" class="text-center py-5">
                  <div class="d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                      <i class="bi bi-inbox fs-1 text-muted"></i>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-1">No Recent Health Records</h3>
                    <p class="text-muted small mb-0">New student medical clearances will automatically appear here once submitted.</p>
                  </div>
                </td>
              </tr>
              <?php else: ?>
                <?php foreach ($recent_records as $rec): ?>
                <?php
                  $firstName = trim($rec['first_name'] ?? '');
                  $lastName = trim($rec['last_name'] ?? '');
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

                  $rawStatus = strtolower($rec['status'] ?? 'pending');
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
                <tr>
                  <td class="ps-4">
                    <span class="applicant-ref-badge">
                      <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($rec['reference_number'], ENT_QUOTES, 'UTF-8') ?>
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
                      <i class="bi bi-mortarboard text-primary"></i><?= htmlspecialchars(strtoupper($rec['strand'] ?: ($rec['academic_level'] ?? 'N/A')), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-semibold text-dark small">
                        <i class="bi bi-calendar-event text-muted me-1"></i><?= date('M j, Y', strtotime($rec['created_at'])) ?>
                      </span>
                      <span class="text-muted extra-small">
                        <i class="bi bi-clock text-muted me-1"></i><?= date('g:i A', strtotime($rec['created_at'])) ?>
                      </span>
                    </div>
                  </td>
                  <td>
                    <span class="badge <?= esc($statusBadge) ?> border px-2.5 py-1.5 rounded-pill small fw-semibold d-inline-flex align-items-center gap-1">
                      <i class="bi <?= esc($statusIcon) ?>"></i>
                      <?= htmlspecialchars(ucwords(str_replace('_', ' ', $rec['status'])), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td class="text-end pe-4">
                    <a href="medical_detail.php?id=<?= esc($rec['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium d-inline-flex align-items-center gap-1.5">
                      <i class="bi bi-folder2-open"></i> Review
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
