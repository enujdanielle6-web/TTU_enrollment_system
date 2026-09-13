<?php
$pageTitle = 'Review Applications - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
?>
<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">

    <!-- Dossier Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-inbox-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Review Applications</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> Admissions Officer
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-layers text-primary me-1"></i><?= number_format($totalCount) ?> Total Applications
              </span>
            </div>
            <p class="text-muted small mb-0">Manage student admissions and review submitted documents.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="admissions_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-speedometer2 text-primary"></i>
            <span>Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Search & Filter Console Card -->
    <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.1s;">
      <div class="dossier-card-header py-2.5 px-3 bg-light border-bottom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-funnel-fill text-primary"></i>
          <span class="fw-bold small text-uppercase text-secondary">Filters & Search</span>
        </div>
        <?php if ($search !== '' || $statusFilter !== 'all' || $strandFilter !== 'all' || $gradeFilter !== 'all' || $levelFilter !== 'all' || $sortOrder !== 'newest'): ?>
          <a href="review.php" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 extra-small d-inline-flex align-items-center gap-1">
            <i class="bi bi-x-circle"></i> Reset Filters
          </a>
        <?php endif; ?>
      </div>
      <div class="p-3">
        <form action="review.php" method="GET" class="row g-2.5 align-items-center" id="filterForm">
          <!-- Search Input -->
          <div class="col-lg-3 col-md-6">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
              <input type="text" name="search" id="searchInput" class="form-control form-control-sm border-start-0 ps-0" placeholder="Search name, ref, email..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
              <?php if ($search !== ''): ?>
                <a href="review.php" class="input-group-text bg-light border-start-0 text-muted text-decoration-none" title="Clear search"><i class="bi bi-x"></i></a>
              <?php endif; ?>
            </div>
          </div>

          <!-- Status Filter -->
          <div class="col-lg-2 col-md-6">
            <select name="status" class="form-select form-select-sm bg-light filter-select">
              <option value="all" <?= esc($statusFilter === 'all' ? 'selected' : '') ?>>All Statuses</option>
              <option value="pending" <?= esc($statusFilter === 'pending' ? 'selected' : '') ?>>Pending</option>
              <option value="under_review" <?= esc($statusFilter === 'under_review' ? 'selected' : '') ?>>Under Review</option>
              <option value="correction_required" <?= esc($statusFilter === 'correction_required' ? 'selected' : '') ?>>Correction Required</option>
              <option value="approved" <?= esc($statusFilter === 'approved' ? 'selected' : '') ?>>Approved</option>
              <option value="rejected" <?= esc($statusFilter === 'rejected' ? 'selected' : '') ?>>Rejected</option>
              <option value="enrolled" <?= esc($statusFilter === 'enrolled' ? 'selected' : '') ?>>Officially Enrolled</option>
            </select>
          </div>

          <!-- Academic Level Filter -->
          <div class="col-lg-2 col-md-4">
            <select name="level" class="form-select form-select-sm bg-light filter-select">
              <option value="all" <?= esc($levelFilter === 'all' ? 'selected' : '') ?>>All Levels</option>
              <option value="Senior High School" <?= esc($levelFilter === 'Senior High School' ? 'selected' : '') ?>>Senior High School</option>
              <option value="College" <?= esc($levelFilter === 'College' ? 'selected' : '') ?>>College</option>
            </select>
          </div>

          <!-- Program Filter -->
          <div class="col-lg-2 col-md-4">
            <select name="strand" class="form-select form-select-sm bg-light filter-select">
              <option value="all" <?= esc($strandFilter === 'all' ? 'selected' : '') ?>>All Programs</option>
              <?php foreach ($programs as $prog): ?>
                <option value="<?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?>" <?= esc($strandFilter === $prog['code'] ? 'selected' : '') ?>>
                  <?= htmlspecialchars(strtoupper($prog['code']), ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Grade / Year Filter -->
          <div class="col-lg-2 col-md-4">
            <select name="grade" class="form-select form-select-sm bg-light filter-select">
              <option value="all" <?= esc($gradeFilter === 'all' ? 'selected' : '') ?>>All Grades / Years</option>
              <optgroup label="Senior High School">
                <option value="Grade 11" <?= esc($gradeFilter === 'Grade 11' ? 'selected' : '') ?>>Grade 11</option>
                <option value="Grade 12" <?= esc($gradeFilter === 'Grade 12' ? 'selected' : '') ?>>Grade 12</option>
              </optgroup>
              <optgroup label="College">
                <option value="1st Year" <?= esc($gradeFilter === '1st Year' ? 'selected' : '') ?>>1st Year</option>
                <option value="2nd Year" <?= esc($gradeFilter === '2nd Year' ? 'selected' : '') ?>>2nd Year</option>
                <option value="3rd Year" <?= esc($gradeFilter === '3rd Year' ? 'selected' : '') ?>>3rd Year</option>
                <option value="4th Year" <?= esc($gradeFilter === '4th Year' ? 'selected' : '') ?>>4th Year</option>
              </optgroup>
            </select>
          </div>

          <!-- Sort Order -->
          <div class="col-lg-1 col-md-12">
            <select name="sort" class="form-select form-select-sm bg-light filter-select" title="Sort order">
              <option value="newest" <?= esc($sortOrder === 'newest' ? 'selected' : '') ?>>Newest</option>
              <option value="oldest" <?= esc($sortOrder === 'oldest' ? 'selected' : '') ?>>Oldest</option>
              <option value="name_asc" <?= esc($sortOrder === 'name_asc' ? 'selected' : '') ?>>Name A-Z</option>
              <option value="name_desc" <?= esc($sortOrder === 'name_desc' ? 'selected' : '') ?>>Name Z-A</option>
            </select>
          </div>
        </form>
      </div>
    </div>

    <!-- Applications Table Dossier Card -->
    <div class="dossier-card fade-in-up" style="animation-delay: 0.15s;">
      <div class="dossier-card-header">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-card-checklist"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Applications Directory</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              Showing <?= count($applications) ?> of <?= number_format($totalCount) ?> Records
            </span>
          </div>
        </div>
      </div>

      <div class="p-0">
        <form action="bulk_process.php" method="POST" id="bulkForm">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="bulk_status" id="bulkStatusInput" value="">
          
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 dashboard-table">
              <thead>
                <tr>
                  <th scope="col" class="ps-4" style="width: 44px;">
                    <input type="checkbox" id="selectAll" class="form-check-input" title="Select All">
                  </th>
                  <th scope="col">Reference No.</th>
                  <th scope="col">Applicant Name</th>
                  <th scope="col">Level</th>
                  <th scope="col">Grade/Year</th>
                  <th scope="col">Program</th>
                  <th scope="col">Date Submitted</th>
                  <th scope="col">Docs</th>
                  <th scope="col">Status</th>
                  <th scope="col" class="pe-4 text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($applications)): ?>
                  <tr>
                    <td colspan="10" class="text-center py-5">
                      <div class="d-flex flex-column align-items-center justify-content-center py-4">
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                          <i class="bi bi-inbox fs-1 text-muted"></i>
                        </div>
                        <h3 class="h6 fw-bold text-dark mb-1">No Applications Found</h3>
                        <p class="text-muted small mb-0">No student applications match the selected filter criteria.</p>
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($applications as $app): ?>
                    <?php
                      $firstName = trim($app['first_name'] ?? '');
                      $lastName = trim($app['last_name'] ?? '');
                      $displayName = ($lastName !== '' || $firstName !== '') ? ($lastName . ', ' . $firstName) : 'Unknown Applicant';
                      $firstInitial = mb_substr($firstName, 0, 1);
                      $lastInitial = mb_substr($lastName, 0, 1);
                      $initials = strtoupper($firstInitial . $lastInitial);
                      if ($initials === '') {
                          $initials = 'AP';
                      }

                      $rawStatus = strtolower($app['status'] ?? 'pending');
                      $statusLabel = formatApplicationStatus($app['status']);
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

                      $isPhysical = ($app['document_submission_method'] ?? '') === 'on_campus';
                      $docCount = (int)($app['doc_count'] ?? 0);
                      $pendingDocs = (int)($app['pending_docs'] ?? 0);
                    ?>
                    <tr>
                      <td class="ps-4">
                        <input type="checkbox" name="selected_apps[]" value="<?= esc($app['id']) ?>" class="form-check-input app-checkbox">
                      </td>
                      <td>
                        <span class="applicant-ref-badge">
                          <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($app['reference_number'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>
                      <td>
                        <div class="d-flex align-items-center gap-2.5">
                          <div class="applicant-avatar"><?= esc($initials) ?></div>
                          <div>
                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if (!empty($app['email'])): ?>
                              <span class="text-muted extra-small d-block"><?= htmlspecialchars($app['email'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small fw-semibold">
                          <?= htmlspecialchars($app['academic_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>
                      <td>
                        <span class="text-secondary small fw-semibold">
                          <?= htmlspecialchars($app['grade_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>
                      <td>
                        <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-pill fw-semibold small d-inline-flex align-items-center gap-1">
                          <i class="bi bi-mortarboard text-primary"></i>
                          <?= htmlspecialchars(strtoupper($app['strand'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>
                      <td>
                        <div class="d-flex flex-column">
                          <span class="fw-semibold text-dark small">
                            <i class="bi bi-calendar-event text-muted me-1"></i><?= date('M j, Y', strtotime($app['created_at'])); ?>
                          </span>
                          <span class="text-muted extra-small">
                            <i class="bi bi-clock text-muted me-1"></i><?= date('g:i A', strtotime($app['created_at'])); ?>
                          </span>
                        </div>
                      </td>
                      <td>
                        <?php if ($isPhysical): ?>
                          <span class="badge bg-light text-secondary border px-2.5 py-1 rounded-pill small fw-semibold" title="Physical Submission On-Campus">
                            <i class="bi bi-building me-1"></i>Physical
                          </span>
                        <?php else: ?>
                          <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2.5 py-1 rounded-pill small fw-semibold d-inline-flex align-items-center gap-1">
                            <i class="bi bi-cloud-arrow-up"></i> <?= $docCount ?> Files
                          </span>
                          <?php if ($pendingDocs > 0): ?>
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill extra-small ms-1" title="Pending Verification">
                              <?= $pendingDocs ?>
                            </span>
                          <?php endif; ?>
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="badge <?= esc($statusBadge) ?> border px-2.5 py-1.5 rounded-pill small fw-semibold d-inline-flex align-items-center gap-1">
                          <i class="bi <?= esc($statusIcon) ?>"></i>
                          <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>
                      <td class="pe-4 text-end">
                        <a href="application_detail.php?id=<?= esc($app['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium d-inline-flex align-items-center gap-1">
                          Review <i class="bi bi-arrow-right-short"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Tactical Bulk Operations Bar & Pagination Footer -->
          <div class="py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3 border-top" id="bulkToolbar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="badge bg-light text-dark border px-3 py-1.5 rounded-pill small fw-semibold d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-check2-all text-primary"></i>
                <span id="selectedCountBadge">0 Selected</span>
              </span>
              <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-medium bulk-btn d-inline-flex align-items-center gap-1" data-action="approved" disabled>
                <i class="bi bi-check-circle"></i> Approve
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-medium bulk-btn d-inline-flex align-items-center gap-1" data-action="rejected" disabled>
                <i class="bi bi-x-circle"></i> Reject
              </button>
              <button type="button" class="btn btn-sm btn-outline-info text-dark rounded-pill px-3 fw-medium bulk-btn d-inline-flex align-items-center gap-1" data-action="under_review" disabled>
                <i class="bi bi-eye"></i> Review
              </button>
              <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3 fw-medium bulk-btn d-inline-flex align-items-center gap-1" data-action="correction_required" disabled>
                <i class="bi bi-exclamation-triangle"></i> Corrections
              </button>
            </div>

            <!-- Pagination Controls -->
            <?php if ($totalPages > 1): ?>
              <nav aria-label="Review Pagination">
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item <?= esc(($page <= 1) ? 'disabled' : '') ?>">
                    <a class="page-link rounded-start-pill" href="?page=<?= esc($page - 1) ?>&search=<?= esc(urlencode($search)) ?>&status=<?= esc(urlencode($statusFilter)) ?>&level=<?= esc(urlencode($levelFilter)) ?>&strand=<?= esc(urlencode($strandFilter)) ?>&grade=<?= esc(urlencode($gradeFilter)) ?>&sort=<?= esc(urlencode($sortOrder)) ?>">
                      <i class="bi bi-chevron-left"></i> Previous
                    </a>
                  </li>
                  
                  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= esc(($i === $page) ? 'active' : '') ?>">
                      <a class="page-link" href="?page=<?= esc($i) ?>&search=<?= esc(urlencode($search)) ?>&status=<?= esc(urlencode($statusFilter)) ?>&level=<?= esc(urlencode($levelFilter)) ?>&strand=<?= esc(urlencode($strandFilter)) ?>&grade=<?= esc(urlencode($gradeFilter)) ?>&sort=<?= esc(urlencode($sortOrder)) ?>"><?= esc($i) ?></a>
                    </li>
                  <?php endfor; ?>
                  
                  <li class="page-item <?= esc(($page >= $totalPages) ? 'disabled' : '') ?>">
                    <a class="page-link rounded-end-pill" href="?page=<?= esc($page + 1) ?>&search=<?= esc(urlencode($search)) ?>&status=<?= esc(urlencode($statusFilter)) ?>&level=<?= esc(urlencode($levelFilter)) ?>&strand=<?= esc(urlencode($strandFilter)) ?>&grade=<?= esc(urlencode($gradeFilter)) ?>&sort=<?= esc(urlencode($sortOrder)) ?>">
                      Next <i class="bi bi-chevron-right"></i>
                    </a>
                  </li>
                </ul>
              </nav>
            <?php endif; ?>
          </div>

        </form>
      </div>
    </div>

  </div>
</main>

<!-- Bulk Action Confirmation Modal -->
<div class="modal fade" id="bulkConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-light border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle" style="width: 36px; height: 36px;">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <h5 class="modal-title fw-bold text-dark mb-0">Confirm Bulk Action</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-secondary mb-3">
          Are you sure you want to change the status of <span id="selectedCount" class="badge bg-primary px-2.5 py-1 rounded-pill fw-bold">0</span> selected application(s) to <span id="targetStatusText" class="badge bg-secondary px-2.5 py-1 rounded-pill fw-bold">Status</span>?
        </p>
        <div class="p-3 bg-light rounded-3 border">
          <div class="d-flex align-items-center gap-2 text-muted small">
            <i class="bi bi-info-circle text-primary"></i>
            <span>This batch update will trigger institutional activity logs and notify applicants immediately.</span>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light border-top py-3 px-4">
        <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-medium" id="executeBulkBtn">Yes, Process Applications</button>
      </div>
    </div>
  </div>
</div>

<script src="/sia/public/vendor/jquery/jquery.min.js"></script>
<script>
$(function() {
    const selectAll = $('#selectAll');
    const checkboxes = $('.app-checkbox');
    const bulkBtns = $('.bulk-btn');
    const bulkForm = $('#bulkForm');
    const bulkConfirmModal = new bootstrap.Modal(document.getElementById('bulkConfirmModal'));

    // Toggle checkboxes
    selectAll.on('change', function() {
        checkboxes.prop('checked', this.checked);
        toggleBulkButtons();
    });

    checkboxes.on('change', function() {
        selectAll.prop('checked', checkboxes.length === checkboxes.filter(':checked').length);
        toggleBulkButtons();
    });

    function toggleBulkButtons() {
        const count = checkboxes.filter(':checked').length;
        bulkBtns.prop('disabled', count === 0);
        $('#selectedCountBadge').text(count + ' Selected');
        if (count > 0) {
            $('#bulkToolbar').addClass('bulk-toolbar-active');
        } else {
            $('#bulkToolbar').removeClass('bulk-toolbar-active');
        }
    }

    // Trigger Bulk Actions
    let targetAction = '';
    bulkBtns.on('click', function() {
        targetAction = $(this).data('action');
        const count = checkboxes.filter(':checked').length;
        
        let statusText = '';
        let badgeClass = 'bg-secondary';
        switch(targetAction) {
            case 'approved': statusText = 'Approved'; badgeClass = 'bg-success'; break;
            case 'rejected': statusText = 'Rejected'; badgeClass = 'bg-danger'; break;
            case 'under_review': statusText = 'Under Review'; badgeClass = 'bg-info text-dark'; break;
            case 'correction_required': statusText = 'Correction Required'; badgeClass = 'bg-warning text-dark'; break;
        }

        $('#selectedCount').text(count);
        $('#targetStatusText').text(statusText).attr('class', 'badge px-2.5 py-1 rounded-pill fw-bold ' + badgeClass);
        bulkConfirmModal.show();
    });

    $('#executeBulkBtn').on('click', function() {
        $('#bulkStatusInput').val(targetAction);
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...');
        bulkForm[0].submit();
    });

    // Real-time filter submission
    let filterTimeout;
    $('#searchInput').on('input', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => {
            $('#filterForm').submit();
        }, 600);
    });

    $('.filter-select').on('change', function() {
        $('#filterForm').submit();
    });
});
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>



