<?php
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 fade-in-up" style="animation-delay: 0.1s;">
      <h1 class="h3 fw-bold text-dark mb-1">Active Scholars</h1>
      <p class="text-muted mb-0">Track, renew, or suspend active scholarship recipients.</p>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4 fade-in-up" style="animation-delay: 0.2s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light fade-in-up" style="animation-delay: 0.3s;">
        <i class="bi bi-people-fill text-primary"></i>
        <h2 class="mb-0 text-dark">Scholar Roster</h2>
      </div>
      
      <div class="island-body p-0 fade-in-up" style="animation-delay: 0.4s;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Student Name</th>
                <th scope="col">Student ID</th>
                <th scope="col">Scholarship Program</th>
                <th scope="col">Term</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recipients)): ?>
                <tr>
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-people fs-1 d-block mb-3 text-secondary"></i>
                    No active scholars found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($recipients as $recipient): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark">
                      <?= htmlspecialchars($recipient['last_name'] . ', ' . $recipient['first_name'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                      <?= htmlspecialchars($recipient['student_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                      <div class="fw-bold"><?= htmlspecialchars($recipient['scholarship_name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <span class="badge bg-light text-dark border"><i class="bi bi-tag-fill me-1 text-primary"></i><?= htmlspecialchars($recipient['category'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td>
                      <?= htmlspecialchars($recipient['ay_name'] ?? 'Current Term', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($recipient['semester'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                      <?php 
                        $statusClass = match($recipient['status']) {
                            'Active', 'Renewed' => 'bg-success',
                            'Suspended' => 'bg-warning text-dark',
                            'Terminated' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                      ?>
                      <span class="badge <?= esc($statusClass) ?> rounded-pill px-3"><?= htmlspecialchars($recipient['status'], ENT_QUOTES, 'UTF-8') ?></span>
                      <?php if ($recipient['remarks']): ?>
                        <div class="text-muted small mt-1"><i class="bi bi-chat-text me-1"></i><?= htmlspecialchars($recipient['remarks'], ENT_QUOTES, 'UTF-8') ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <button class="btn btn-sm btn-outline-primary rounded-pill px-3 update-status-btn" 
                              data-id="<?= esc($recipient['id']) ?>"
                              data-status="<?= esc($recipient['status']) ?>"
                              data-remarks="<?= htmlspecialchars($recipient['remarks'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                        Update Status
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

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btns = document.querySelectorAll('.update-status-btn');
    btns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modal_recipient_id').value = this.getAttribute('data-id');
            document.getElementById('modal_status').value = this.getAttribute('data-status');
            document.getElementById('modal_remarks').value = this.getAttribute('data-remarks');
        });
    });
});
</script>
</body>
</html>
