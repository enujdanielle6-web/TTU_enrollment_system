<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('applications.view_queue');

$pageTitle = 'Review Applications - Administrator';

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$strandFilter = trim($_GET['strand'] ?? 'all');
$gradeFilter = trim($_GET['grade'] ?? 'all');
$levelFilter = trim($_GET['level'] ?? 'all');

// Fetch programs for filter
$programs = [];
try {
    $progStmt = $pdo->query('
        SELECT code, name FROM college_programs WHERE is_active = 1 
        UNION ALL 
        SELECT code, name FROM shs_strands WHERE is_active = 1 
        ORDER BY code ASC
    ');
    $programs = $progStmt->fetchAll();
} catch (PDOException $e) {
    error_log('Failed to fetch programs: ' . $e->getMessage());
}
$sortOrder = trim($_GET['sort'] ?? 'newest');

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = '(a.reference_number LIKE :search OR CONCAT(u.first_name, " ", u.last_name) LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($statusFilter !== 'all') {
    $whereClauses[] = 'a.status = :status';
    $params[':status'] = $statusFilter;
}

if ($strandFilter !== 'all') {
    $whereClauses[] = 'a.strand = :strand';
    $params[':strand'] = $strandFilter;
}

if ($gradeFilter !== 'all') {
    $whereClauses[] = 'a.grade_level = :grade';
    $params[':grade'] = $gradeFilter;
}

if ($levelFilter !== 'all') {
    $whereClauses[] = 'a.academic_level = :level';
    $params[':level'] = $levelFilter;
}

$whereSQL = '';
if (!empty($whereClauses)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
}

$orderBy = 'a.created_at DESC';
if ($sortOrder === 'oldest') {
    $orderBy = 'a.created_at ASC';
}

$applications = [];
$totalApps = 0;

try {
    // Count total matching
    $countStmt = $pdo->prepare("
        SELECT COUNT(a.id) 
        FROM applications a 
        INNER JOIN users u ON u.id = a.user_id
        $whereSQL
    ");
    $countStmt->execute($params);
    $totalApps = (int) $countStmt->fetchColumn();

    // Fetch paginated
    $stmt = $pdo->prepare("
        SELECT 
            a.id, 
            a.reference_number, 
            a.status, 
            a.academic_level,
            a.strand, 
            a.grade_level,
            a.created_at, 
            a.document_submission_method,
            u.first_name, 
            u.last_name,
            (SELECT COUNT(*) FROM application_documents d WHERE d.application_id = a.id) as doc_count
        FROM applications a
        INNER JOIN users u ON u.id = a.user_id
        $whereSQL
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $applications = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Admin review list query failed: ' . $e->getMessage());
}

$totalPages = ceil($totalApps / $limit);

$successMsg = $_SESSION['admin_success'] ?? '';
$errorMsg = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Review Applications</h1>
        <p class="text-muted mb-0">Browse and process incoming student enrollment requests.</p>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- Filters Panel -->
    <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4" style="border-radius: 16px;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-body py-3">
        <form action="review.php" method="GET" class="row g-3 align-items-center" id="filterForm">
          <div class="col-md-auto fw-semibold text-muted small text-uppercase">
            <i class="bi bi-funnel-fill me-1"></i> Filter By:
          </div>
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
              <input type="text" name="search" id="searchInput" class="form-control form-control-sm" placeholder="Search name, ref..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>
          <div class="col-md-2">
            <select name="status" class="form-select form-select-sm bg-light filter-select">
              <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
              <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="under_review" <?= $statusFilter === 'under_review' ? 'selected' : '' ?>>Under Review</option>
              <option value="correction_required" <?= $statusFilter === 'correction_required' ? 'selected' : '' ?>>Correction Required</option>
              <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
              <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
              <option value="enrolled" <?= $statusFilter === 'enrolled' ? 'selected' : '' ?>>Officially Enrolled</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="level" class="form-select form-select-sm bg-light filter-select">
              <option value="all" <?= $levelFilter === 'all' ? 'selected' : '' ?>>All Levels</option>
              <option value="Senior High School" <?= $levelFilter === 'Senior High School' ? 'selected' : '' ?>>Senior High School</option>
              <option value="College" <?= $levelFilter === 'College' ? 'selected' : '' ?>>College</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="strand" class="form-select form-select-sm bg-light filter-select">
              <option value="all" <?= $strandFilter === 'all' ? 'selected' : '' ?>>All Programs</option>
              <?php foreach ($programs as $prog): ?>
                <option value="<?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?>" <?= $strandFilter === $prog['code'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars(strtoupper($prog['code']), ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <select name="grade" class="form-select form-select-sm bg-light filter-select">
              <option value="all" <?= $gradeFilter === 'all' ? 'selected' : '' ?>>All Grades</option>
              <option value="Grade 11" <?= $gradeFilter === 'Grade 11' ? 'selected' : '' ?>>Grade 11</option>
              <option value="Grade 12" <?= $gradeFilter === 'Grade 12' ? 'selected' : '' ?>>Grade 12</option>
            </select>
          </div>
          <div class="col-md-1">
            <select name="sort" class="form-select form-select-sm bg-light filter-select">
              <option value="newest" <?= $sortOrder === 'newest' ? 'selected' : '' ?>>Newest</option>
              <option value="oldest" <?= $sortOrder === 'oldest' ? 'selected' : '' ?>>Oldest</option>
            </select>
          </div>
          <div class="col-md-auto ms-auto">
            <?php if ($search !== '' || $statusFilter !== 'all' || $strandFilter !== 'all' || $gradeFilter !== 'all' || $levelFilter !== 'all'): ?>
              <a href="review.php" class="btn btn-sm btn-outline-secondary rounded-pill">Clear</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <div class="island position-relative overflow-hidden border-0 shadow-sm" style="border-radius: 16px;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-body p-0">
        <form action="bulk_process.php" method="POST" id="bulkForm">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="bulk_status" id="bulkStatusInput" value="">
          
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 custom-table">
              <thead class="table-light text-uppercase small text-muted">
                <tr>
                  <th scope="col" class="ps-4 py-3" style="width: 40px;">
                    <input type="checkbox" id="selectAll" class="form-check-input">
                  </th>
                  <th scope="col" class="py-3 fw-semibold">Reference No.</th>
                  <th scope="col" class="py-3 fw-semibold">Applicant Name</th>
                  <th scope="col" class="py-3 fw-semibold">Level</th>
                  <th scope="col" class="py-3 fw-semibold">Grade/Year</th>
                  <th scope="col" class="py-3 fw-semibold">Program</th>
                  <th scope="col" class="py-3 fw-semibold">Date Submitted</th>
                  <th scope="col" class="py-3 fw-semibold">Docs</th>
                  <th scope="col" class="py-3 fw-semibold">Status</th>
                  <th scope="col" class="pe-4 py-3 text-end fw-semibold">Action</th>
                </tr>
              </thead>
              <tbody class="border-top-0">
                <?php if (empty($applications)): ?>
                  <tr>
                    <td colspan="10" class="text-center py-5 text-muted">
                      <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                      No applications match the selected filter criteria.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($applications as $app): ?>
                    <?php
                      $statusLabel = formatApplicationStatus($app['status']);
                      $badgeClass = getApplicationStatusBadgeClass($app['status']);
                      $docBadge = $app['document_submission_method'] === 'on_campus' 
                          ? '<span class="badge bg-secondary" title="On-Campus"><i class="bi bi-building"></i> Physical</span>'
                          : '<span class="badge bg-info text-dark"><i class="bi bi-cloud-arrow-up"></i> ' . $app['doc_count'] . ' Files</span>';
                    ?>
                    <tr>
                      <td class="ps-4">
                        <input type="checkbox" name="selected_apps[]" value="<?= $app['id'] ?>" class="form-check-input app-checkbox">
                      </td>
                      <td class="fw-semibold text-dark"><?= htmlspecialchars($app['reference_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($app['last_name'] . ', ' . $app['first_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                      </td>
                      <td><span class="text-muted small"><?= htmlspecialchars($app['academic_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span></td>
                      <td><span class="text-muted small"><?= htmlspecialchars($app['grade_level'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span></td>
                      <td><span class="text-muted small"><?= htmlspecialchars(strtoupper($app['strand'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                      <td><span class="text-muted small"><?= date('M j, Y g:i A', strtotime($app['created_at'])); ?></span></td>
                      <td><?= $docBadge; ?></td>
                      <td>
                        <span class="badge <?= $badgeClass; ?> px-2 py-1 rounded-pill small">
                          <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>
                      <td class="pe-4 text-end">
                        <a href="application_detail.php?id=<?= $app['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                          Review <i class="bi bi-arrow-right-short"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Bulk Actions & Pagination Footer -->
          <div class="border-top border-light py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-2">
              <span class="small text-muted fw-semibold me-2"><i class="bi bi-check2-all"></i> Bulk Actions:</span>
              <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 bulk-btn" data-action="approved" disabled>Approve</button>
              <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 bulk-btn" data-action="rejected" disabled>Reject</button>
              <button type="button" class="btn btn-sm btn-outline-info text-dark rounded-pill px-3 bulk-btn" data-action="under_review" disabled>Review</button>
              <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3 bulk-btn" data-action="correction_required" disabled>Corrections</button>
            </div>

            <!-- Pagination Controls -->
            <?php if ($totalPages > 1): ?>
              <nav aria-label="Review Pagination">
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&strand=<?= urlencode($strandFilter) ?>&grade=<?= urlencode($gradeFilter) ?>&sort=<?= urlencode($sortOrder) ?>">Previous</a>
                  </li>
                  
                  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                      <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&strand=<?= urlencode($strandFilter) ?>&grade=<?= urlencode($gradeFilter) ?>&sort=<?= urlencode($sortOrder) ?>"><?= $i ?></a>
                    </li>
                  <?php endfor; ?>
                  
                  <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&strand=<?= urlencode($strandFilter) ?>&grade=<?= urlencode($gradeFilter) ?>&sort=<?= urlencode($sortOrder) ?>">Next</a>
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
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Confirm Bulk Action</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to change the status of <span id="selectedCount" class="fw-bold">0</span> selected application(s) to <span id="targetStatusText" class="fw-bold text-primary">Status</span>?</p>
        <p class="mb-0 small text-muted">This update will trigger system activity logs and applicant notifications immediately.</p>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="executeBulkBtn">Yes, Process Applications</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
        const anyChecked = checkboxes.filter(':checked').length > 0;
        bulkBtns.prop('disabled', !anyChecked);
    }

    // Trigger Bulk Actions
    let targetAction = '';
    bulkBtns.on('click', function() {
        targetAction = $(this).data('action');
        const count = checkboxes.filter(':checked').length;
        
        let statusText = '';
        switch(targetAction) {
            case 'approved': statusText = 'Approved'; break;
            case 'rejected': statusText = 'Rejected'; break;
            case 'under_review': statusText = 'Under Review'; break;
            case 'correction_required': statusText = 'Correction Required'; break;
        }

        $('#selectedCount').text(count);
        $('#targetStatusText').text(statusText);
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

<?php require_once __DIR__ . '/../components/footer.php'; ?>

