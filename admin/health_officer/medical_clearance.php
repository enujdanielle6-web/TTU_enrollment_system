<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('medical.review');

$pageTitle = 'Medical Clearance - Administrator';

$statusFilter = $_GET['status'] ?? 'all';

$query = '
    SELECT h.id, h.status, h.updated_at,
           u.first_name, u.last_name,
           a.reference_number, a.academic_level, a.strand
    FROM health_records h
    INNER JOIN users u ON h.user_id = u.id
    INNER JOIN applications a ON h.application_id = a.id
';
$params = [];

if ($statusFilter !== 'all') {
    $query .= ' WHERE h.status = :status';
    $params['status'] = $statusFilter;
}

$query .= ' ORDER BY h.updated_at DESC';

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Medical clearance fetch failed: ' . $e->getMessage());
    $records = [];
}

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
        <h1 class="h3 fw-bold text-dark mb-1">Medical Clearance</h1>
        <p class="text-muted mb-0">Review submitted health information and update medical clearance status.</p>
      </div>
      <div>
        <form action="" method="GET" class="d-flex gap-2">
            <select name="status" class="form-select bg-white border-0 shadow-sm" onchange="this.form.submit()">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                <option value="under_review" <?= $statusFilter === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                <option value="verified" <?= $statusFilter === 'verified' ? 'selected' : '' ?>>Verified</option>
                <option value="correction_required" <?= $statusFilter === 'correction_required' ? 'selected' : '' ?>>Correction Required</option>
                <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </form>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light">
        <i class="bi bi-file-medical text-primary"></i>
        <h2 class="mb-0 text-dark">Health Records Queue</h2>
      </div>
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Applicant</th>
                <th scope="col">Reference No.</th>
                <th scope="col">Academic Program</th>
                <th scope="col">Status</th>
                <th scope="col">Last Updated</th>
                <th scope="col" class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($records)): ?>
                <tr>
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No health records found for the selected filter.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($records as $record): ?>
                  <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 40px; height: 40px;">
                                <?= strtoupper(substr($record['first_name'], 0, 1) . substr($record['last_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($record['last_name'] . ', ' . $record['first_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="fw-medium text-dark">
                        <?= htmlspecialchars($record['reference_number'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                        <span class="d-block fw-medium text-dark"><?= htmlspecialchars($record['academic_level'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="text-muted small"><?= htmlspecialchars(getStrandLabel($record['strand']), ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td>
                      <?php 
                        $badgeClass = match($record['status']) {
                            'verified' => 'bg-success',
                            'rejected' => 'bg-danger',
                            'correction_required' => 'bg-warning text-dark',
                            'under_review' => 'bg-info text-dark',
                            default => 'bg-secondary'
                        };
                      ?>
                      <span class="badge <?= $badgeClass ?> rounded-pill px-3"><?= formatApplicationStatus($record['status']) ?></span>
                    </td>
                    <td class="text-muted small">
                        <?= formatDisplayDate($record['updated_at']) ?>
                    </td>
                    <td class="text-end pe-4">
                      <a href="medical_detail.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium">
                        Review <i class="bi bi-arrow-right ms-1"></i>
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

<?php require_once __DIR__ . '/../components/footer.php'; ?>

