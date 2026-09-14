<?php
$pageTitle = 'Active Scholars - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-people-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Active Scholars Registry</h1>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> Scholar Roster
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-person-check text-success me-1"></i><?= count($recipients ?? []) ?> Enrolled Scholars
              </span>
            </div>
            <p class="text-muted small mb-0">Monitor active student grant beneficiaries, renewal statuses, term allocations, and suspensions.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="scholarship_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-speedometer2 text-primary"></i>
            <span>Dashboard</span>
          </a>
          <a href="scholarship_review.php" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-inbox-fill"></i>
            <span>Review Queue</span>
          </a>
        </div>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
        <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
        <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <!-- Scholar Roster Card (Dossier Card Styling) -->
    <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.15s;">
      <div class="dossier-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-success bg-opacity-10 text-success">
            <i class="bi bi-people-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Funded Student Beneficiaries</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <?= count($recipients ?? []) ?> Scholars
            </span>
          </div>
        </div>
        <div class="input-group input-group-sm" style="width: 240px;">
          <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
          <input type="text" id="scholarSearch" class="form-control form-control-sm border-start-0 ps-0" placeholder="Search scholars...">
        </div>
      </div>
      
      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table">
            <thead>
              <tr>
                <th class="ps-4">Student Scholar</th>
                <th>Student ID</th>
                <th>Scholarship Program</th>
                <th>Academic Term</th>
                <th>Status</th>
                <th class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recipients)): ?>
                <tr>
                  <td colspan="6" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                        <i class="bi bi-people fs-1 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">No Active Scholars Found</h3>
                      <p class="small text-muted mb-0">Approved scholarship applicants will automatically be enrolled into this active roster.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($recipients as $recipient): 
                  $fLetter = !empty($recipient['first_name']) ? strtoupper(substr($recipient['first_name'], 0, 1)) : 'S';
                  $lLetter = !empty($recipient['last_name']) ? strtoupper(substr($recipient['last_name'], 0, 1)) : 'C';
                  $statusClass = match($recipient['status']) {
                      'Active', 'Renewed' => 'bg-success bg-opacity-10 text-success border-success',
                      'Suspended' => 'bg-warning bg-opacity-10 text-warning border-warning',
                      'Terminated' => 'bg-danger bg-opacity-10 text-danger border-danger',
                      default => 'bg-secondary bg-opacity-10 text-secondary border-secondary'
                  };
                ?>
                  <tr>
                    <td class="ps-4">
                      <div class="d-flex align-items-center gap-2.5">
                        <div class="applicant-avatar bg-success bg-opacity-10 text-success fw-bold" style="width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                          <?= esc($fLetter . $lLetter) ?>
                        </div>
                        <div>
                          <div class="fw-bold text-dark"><?= htmlspecialchars($recipient['last_name'] . ', ' . $recipient['first_name'], ENT_QUOTES, 'UTF-8') ?></div>
                          <span class="text-muted extra-small">Recipient #<?= esc($recipient['id']) ?></span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="applicant-ref-badge fw-bold">
                        <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($recipient['student_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>
                    <td>
                      <div class="fw-semibold text-dark"><?= htmlspecialchars($recipient['scholarship_name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <span class="badge bg-light text-secondary border rounded-pill extra-small mt-0.5">
                        <i class="bi bi-tag-fill me-1 text-primary"></i><?= htmlspecialchars($recipient['category'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                        <?= htmlspecialchars($recipient['ay_name'] ?? 'Current Term', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($recipient['semester'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge <?= esc($statusClass) ?> border border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold"><?= htmlspecialchars($recipient['status'], ENT_QUOTES, 'UTF-8') ?></span>
                      <?php if (!empty($recipient['remarks'])): ?>
                        <div class="text-muted extra-small mt-1"><i class="bi bi-chat-text me-1"></i><?= htmlspecialchars($recipient['remarks'], ENT_QUOTES, 'UTF-8') ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <button class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 extra-small fw-medium d-inline-flex align-items-center gap-1 update-status-btn" 
                              data-id="<?= esc($recipient['id']) ?>"
                              data-status="<?= esc($recipient['status']) ?>"
                              data-remarks="<?= htmlspecialchars($recipient['remarks'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-bs-toggle="modal" data-bs-target="#updateStatusModal"
                              title="Update Scholar Status">
                        <i class="bi bi-pencil-square"></i>
                        <span>Status</span>
                      </button>
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

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form action="scholarship_process.php" method="POST">
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" value="update_recipient_status">
        <input type="hidden" name="recipient_id" id="modal_recipient_id" value="">
        
        <div class="modal-header bg-light border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Update Scholar Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body p-4 pt-2">
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Status</label>
                <select class="form-select bg-light" name="status" id="modal_status" required>
                    <option value="Active">Active</option>
                    <option value="Renewed">Renewed</option>
                    <option value="Suspended">Suspended</option>
                    <option value="Terminated">Terminated</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Remarks / Reason</label>
                <textarea class="form-control bg-light" name="remarks" id="modal_remarks" rows="3" placeholder="Add any notes regarding this status change..."></textarea>
            </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 rounded-pill fw-medium shadow-sm">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
if (!window.__scholarStatusHandlerAttached__) {
    window.__scholarStatusHandlerAttached__ = true;
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.update-status-btn');
        if (!btn) return;

        const recipientId = btn.getAttribute('data-id');
        const status = btn.getAttribute('data-status');
        const remarks = btn.getAttribute('data-remarks') || '';

        const modalIdEl = document.getElementById('modal_recipient_id');
        const modalStatusEl = document.getElementById('modal_status');
        const modalRemarksEl = document.getElementById('modal_remarks');

        if (modalIdEl) modalIdEl.value = recipientId;
        if (modalStatusEl) modalStatusEl.value = status;
        if (modalRemarksEl) modalRemarksEl.value = remarks;

        const modalEl = document.getElementById('updateStatusModal');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
            const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modalInstance.show();
        }
    });

    // Client-side search logic
    document.addEventListener('keyup', function(e) {
        if (e.target && e.target.id === 'scholarSearch') {
            const filter = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.dashboard-table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
