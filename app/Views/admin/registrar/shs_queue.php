<?php
$pageTitle = 'SHS Enrollment Queue - Registrar';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip (Admissions Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-journal-bookmark-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">SHS Enrollment Queue</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> University Registrar
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-layers text-info me-1"></i><?= count($applications ?? []) ?> Ready for Matriculation
              </span>
            </div>
            <p class="text-muted small mb-0">Review approved applications with verified payments and finalize their official senior high enrollment.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="registrar_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-speedometer2 text-primary"></i>
            <span>Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Flash Notifications -->
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
        <?php if (($search ?? '') !== '' || ($sortOrder ?? 'newest') !== 'newest'): ?>
          <a href="shs_enrollment_queue.php" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 extra-small d-inline-flex align-items-center gap-1">
            <i class="bi bi-x-circle"></i> Reset Filters
          </a>
        <?php endif; ?>
      </div>
      <div class="p-3">
        <form action="shs_enrollment_queue.php" method="GET" class="row g-2.5 align-items-center">
          <div class="col-lg-4 col-md-6">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
              <input type="text" name="search" class="form-control form-control-sm border-start-0 ps-0" placeholder="Search applicant name, reference no..." value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>
          <div class="col-lg-3 col-md-4">
            <select name="sort" class="form-select form-select-sm bg-light" onchange="this.form.submit()">
              <option value="newest" <?= esc(($sortOrder ?? 'newest') === 'newest' ? 'selected' : '') ?>>Newest Applications First</option>
              <option value="oldest" <?= esc(($sortOrder ?? '') === 'oldest' ? 'selected' : '') ?>>Oldest Applications First</option>
            </select>
          </div>
          <div class="col-lg-2 col-md-2">
            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 w-100">Filter</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Enrollment Queue Table Card (Dossier Card Styling) -->
    <div class="dossier-card fade-in-up" style="animation-delay: 0.15s;">
      <div class="dossier-card-header">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-info bg-opacity-10 text-info">
            <i class="bi bi-person-check-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Senior High Enrollees Queue</h2>
          </div>
        </div>
      </div>

      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table">
            <thead>
              <tr>
                <th class="ps-4">Reference No.</th>
                <th>Applicant Name</th>
                <th>Level / Program</th>
                <th>Payment Status</th>
                <th>Total Paid</th>
                <th>Action Required</th>
                <th class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($applications)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">Queue Clear</h3>
                      <p class="small text-muted mb-0">No senior high applications are currently awaiting enrollment finalization.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($applications as $app): ?>
                  <?php
                    $firstName = trim($app['first_name'] ?? '');
                    $lastName = trim($app['last_name'] ?? '');
                    $fullName = trim($lastName . ', ' . $firstName);
                    if ($fullName === ', ') $fullName = 'Unknown Applicant';
                    $initials = strtoupper(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1));
                    if ($initials === '') $initials = 'AP';
                  ?>
                  <tr>
                    <td class="ps-4">
                      <span class="applicant-ref-badge">
                        <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($app['reference_number'], ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2.5">
                        <div class="applicant-avatar">
                          <?= esc($initials) ?>
                        </div>
                        <div>
                          <span class="fw-bold text-dark d-block"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold mb-1">
                        <?= htmlspecialchars($app['academic_level'], ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                      <div class="small fw-semibold text-dark"><?= htmlspecialchars(strtoupper($app['strand'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></div>
                    </td>
                    <td>
                      <?php if (($app['payment_status'] ?? '') === 'paid'): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1">
                          <i class="bi bi-check-circle-fill"></i> Fully Paid
                        </span>
                      <?php else: ?>
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1">
                          <i class="bi bi-info-circle-fill"></i> Partially Paid
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="fw-bold text-dark small">₱<?= number_format((float)($app['total_paid'] ?? 0), 2) ?></span>
                    </td>
                    <td>
                      <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1">
                        <i class="bi bi-clock-history"></i> Needs Finalization
                      </span>
                    </td>
                    <td class="pe-4 text-end">
                      <?php 
                        $appDetails = [
                          'id' => $app['id'],
                          'ref_number' => $app['reference_number'],
                          'name' => $fullName,
                          'email' => $app['email'] ?? '',
                          'academic_level' => $app['academic_level'],
                          'program' => strtoupper($app['strand'] ?? ''),
                          'grade_level' => $app['grade_level'] ?? '',
                          'student_type' => $app['student_type'] ?? 'Regular',
                          'section' => $app['section_code'] ?? 'Not Assigned',
                          'payment_status' => ($app['payment_status'] === 'paid' ? 'Fully Paid' : 'Partially Paid'),
                          'total_paid' => '₱' . number_format((float)($app['total_paid'] ?? 0), 2),
                          'total_assessment' => !empty($app['total_assessment']) ? '₱' . number_format((float)$app['total_assessment'], 2) : 'N/A',
                          'medical_status' => ucfirst($app['medical_status'] ?? 'Not Submitted'),
                          'date_applied' => date('M d, Y', strtotime($app['created_at']))
                        ];
                      ?>
                      <form method="POST" action="finalize_enrollment.php" class="d-inline form-finalize" data-app="<?= htmlspecialchars(json_encode($appDetails), ENT_QUOTES, 'UTF-8'); ?>">
                        <?= getCsrfInput() ?>
                        <input type="hidden" name="application_id" value="<?= esc($app['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm fw-semibold d-inline-flex align-items-center gap-1">
                          <i class="bi bi-mortarboard-fill"></i> Finalize
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination Footer -->
        <?php if (($totalPages ?? 1) > 1): ?>
        <div class="border-top py-3 px-4 d-flex justify-content-end align-items-center">
          <nav aria-label="Queue Pagination">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item <?= esc(($page <= 1) ? 'disabled' : '') ?>">
                <a class="page-link rounded-start-pill" href="?page=<?= esc($page - 1) ?>&search=<?= esc(urlencode($search ?? '')) ?>&sort=<?= esc(urlencode($sortOrder ?? 'newest')) ?>">Previous</a>
              </li>
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= esc(($i === $page) ? 'active' : '') ?>">
                  <a class="page-link" href="?page=<?= esc($i) ?>&search=<?= esc(urlencode($search ?? '')) ?>&sort=<?= esc(urlencode($sortOrder ?? 'newest')) ?>"><?= esc($i) ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= esc(($page >= $totalPages) ? 'disabled' : '') ?>">
                <a class="page-link rounded-end-pill" href="?page=<?= esc($page + 1) ?>&search=<?= esc(urlencode($search ?? '')) ?>&sort=<?= esc(urlencode($sortOrder ?? 'newest')) ?>">Next</a>
              </li>
            </ul>
          </nav>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>



