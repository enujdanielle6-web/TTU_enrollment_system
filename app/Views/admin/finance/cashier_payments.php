<?php
require_once __DIR__ . '/../../components/header.php';

require_once __DIR__ . '/../../components/admin_navbar.php';

// Fetch all payments
$payments = [];
try {
    $stmt = $pdo->query('
        SELECT pr.*, 
               u.first_name as student_first, u.last_name as student_last,
               c.first_name as cashier_first, c.last_name as cashier_last,
               a.reference_number as app_ref
        FROM payment_records pr
        INNER JOIN users u ON pr.user_id = u.id
        LEFT JOIN users c ON pr.cashier_id = c.id
        INNER JOIN student_assessments sa ON pr.assessment_id = sa.id
        INNER JOIN applications a ON sa.application_id = a.id
        ORDER BY pr.created_at DESC
    ');
    $payments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Cashier payments fetch failed: ' . $e->getMessage());
}

?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 fade-in-up" style="animation-delay: 0.1s;">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Payment History</h1>
        <p class="text-muted mb-0">Global ledger of all recorded student payments.</p>
      </div>
      <div>
        <div class="input-group shadow-sm">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="tableSearch" class="form-control border-start-0" placeholder="Search payments...">
        </div>
      </div>
    </div>

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4 fade-in-up" style="animation-delay: 0.2s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light fade-in-up" style="animation-delay: 0.3s;">
        <i class="bi bi-receipt text-primary"></i>
        <h2 class="mb-0 text-dark">All Transactions</h2>
      </div>
      
      <div class="island-body p-0 fade-in-up" style="animation-delay: 0.4s;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Receipt No.</th>
                <th scope="col">Date</th>
                <th scope="col">Student Name</th>
                <th scope="col">Method</th>
                <th scope="col">Amount</th>
                <th scope="col">Processed By</th>
                <th scope="col" class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($payments)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No payments have been recorded yet.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-primary">
                      <?php if ($payment['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Pending Verification</span>
                      <?php elseif ($payment['status'] === 'rejected'): ?>
                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Rejected</span>
                      <?php else: ?>
                        <?= htmlspecialchars($payment['receipt_number'], ENT_QUOTES, 'UTF-8') ?>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?= date('M d, Y g:i A', strtotime($payment['created_at'])) ?>
                    </td>
                    <td class="fw-bold text-dark">
                      <?= htmlspecialchars($payment['student_last'] . ', ' . $payment['student_first'], ENT_QUOTES, 'UTF-8') ?>
                      <div class="small text-muted fw-normal">Ref: <?= htmlspecialchars($payment['app_ref'], ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td>
                      <span class="badge bg-secondary rounded-pill px-3"><?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td class="fw-bold text-success">
                      ₱<?= number_format((float)$payment['amount'], 2) ?>
                    </td>
                    <td class="small text-muted">
                      <?php if ($payment['status'] === 'pending'): ?>
                        <span class="text-warning fw-medium">Needs Review</span>
                      <?php elseif ($payment['status'] === 'rejected'): ?>
                        <span class="text-danger fw-medium">Rejected</span>
                      <?php else: ?>
                        <?= htmlspecialchars($payment['cashier_last'] ?? 'System', ENT_QUOTES, 'UTF-8') ?>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <?php if ($payment['status'] === 'pending'): ?>
                        <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 fw-semibold verify-btn shadow-sm"
                                data-id="<?= $payment['id'] ?>"
                                data-name="<?= htmlspecialchars($payment['student_first'] . ' ' . $payment['student_last'], ENT_QUOTES, 'UTF-8') ?>"
                                data-amount="<?= number_format((float)$payment['amount'], 2) ?>"
                                data-method="<?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?>"
                                data-ref="<?= htmlspecialchars($payment['reference_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>"
                                data-img="/sia/app/uploads/payments/<?= htmlspecialchars($payment['proof_image'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-bs-toggle="modal" data-bs-target="#verifyModal">
                          <i class="bi bi-shield-check"></i> Verify
                        </button>
                      <?php elseif ($payment['status'] === 'rejected'): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-medium"
                                onclick="showRejectReason('<?= htmlspecialchars(addslashes($payment['remarks'] ?? 'No reason provided.')) ?>')">
                          <i class="bi bi-info-circle me-1"></i> Reason
                        </button>
                      <?php else: ?>
                        <a href="cashier_receipt.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium" target="_blank">
                          <i class="bi bi-printer"></i> View
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              <tr id="noResultsRow" style="display: none;">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-search fs-1 d-block mb-3 text-secondary"></i>
                  No payments match your search.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Verify Payment Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-white border-bottom py-3">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="verifyModalLabel">
          <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-25 text-warning-emphasis rounded-circle me-3" style="width: 36px; height: 36px;">
            <i class="bi bi-shield-check fs-5" style="color: #d97706;"></i>
          </div>
          Verify Proof of Payment
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="cashier_process.php" method="POST">
        <div class="modal-body p-4 bg-light">
          <input type="hidden" name="action" value="verify_online_payment">
          <input type="hidden" name="payment_id" id="verifyPaymentId" value="">
          <?= getCsrfInput() ?>

          <div class="row g-4">
            <div class="col-md-5">
              <div class="d-flex flex-column gap-3 h-100">
                <div class="p-3 bg-white border rounded-3 shadow-sm">
                  <p class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px;"><i class="bi bi-person me-1"></i> Student Name</p>
                  <p class="fw-bold text-dark mb-0 fs-6" id="verifyStudentName"></p>
                </div>
                <div class="p-3 bg-white border rounded-3 shadow-sm" style="border-color: #a3cfbb !important;">
                  <p class="text-success small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px;"><i class="bi bi-cash-stack me-1"></i> Amount Declared</p>
                  <p class="fw-bolder text-success mb-0 fs-3" style="letter-spacing: -1px;">₱<span id="verifyAmount"></span></p>
                </div>
                <div class="p-3 bg-white border rounded-3 shadow-sm">
                  <p class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px;"><i class="bi bi-credit-card me-1"></i> Method & Reference</p>
                  <div class="d-flex align-items-center gap-2 mt-2">
                    <span class="badge bg-light text-dark border px-2 py-1" id="verifyMethod"></span>
                    <span class="fw-medium text-secondary font-monospace small" id="verifyRef"></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-7">
              <div class="bg-light p-3 rounded-4 h-100 d-flex flex-column align-items-center justify-content-center position-relative" style="min-height: 350px; border: 2px dashed #dee2e6;">
                <div class="position-absolute top-0 start-0 w-100 p-2 text-center text-muted small fw-medium" style="z-index: 1;">
                  <i class="bi bi-image me-1"></i> Transaction Screenshot
                </div>
                <img id="verifyImage" src="" alt="Proof of Payment" class="img-fluid rounded shadow-sm position-relative mt-4" style="max-height: 320px; object-fit: contain; z-index: 2;">
              </div>
            </div>
          </div>
          
          <div class="mt-4 pt-3 border-top border-light">
            <label for="verifyRemarks" class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing: 0.5px;"><i class="bi bi-chat-left-text me-1"></i>Remarks / Reason for Rejection</label>
            <textarea name="remarks" id="verifyRemarks" class="form-control border-secondary border-opacity-25 rounded-3 shadow-sm" rows="2" placeholder="e.g., Screenshot is blurry, Amount is incorrect, etc."></textarea>
            <div class="form-text small text-danger mt-1"><i class="bi bi-info-circle me-1"></i>Please provide a reason if you are rejecting the payment.</div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 bg-light d-flex justify-content-between">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <div>
            <button type="button" class="btn btn-outline-danger rounded-pill px-4 shadow-sm fw-semibold me-2" onclick="confirmReject(this)"><i class="bi bi-x-circle me-1"></i> Reject Payment</button>
            <button type="submit" name="decision" value="approve" class="btn btn-success rounded-pill px-4 shadow-sm fw-semibold"><i class="bi bi-check-circle me-1"></i> Approve Payment</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Custom Reason Modal -->
<div class="modal fade" id="customReasonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger bg-opacity-10 border-bottom-0 pb-0">
        <h6 class="modal-title text-danger fw-bold"><i class="bi bi-info-circle me-2"></i>Rejection Reason</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center pt-3 pb-4">
        <p class="text-dark fw-medium mb-0" id="customReasonText"></p>
      </div>
    </div>
  </div>
</div>

<!-- Custom Warning Modal -->
<div class="modal fade" id="customWarningModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-4">
        <div class="text-warning mb-3">
          <i class="bi bi-exclamation-triangle-fill display-4"></i>
        </div>
        <h6 class="fw-bold mb-2">Remarks Required</h6>
        <p class="text-muted small mb-4">Please provide a reason for rejection in the Remarks field before proceeding.</p>
        <button type="button" class="btn btn-warning rounded-pill px-4 fw-medium text-dark" data-bs-dismiss="modal" onclick="setTimeout(()=>document.getElementById('verifyRemarks').focus(), 300)">OK, Got It</button>
      </div>
    </div>
  </div>
</div>

<!-- Custom Confirm Modal -->
<div class="modal fade" id="customConfirmModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-body p-4 text-center">
        <div class="text-danger mb-3">
          <i class="bi bi-question-circle display-4"></i>
        </div>
        <h5 class="fw-bold mb-3">Reject Payment?</h5>
        <p class="text-muted mb-3">Are you sure you want to <b>REJECT</b> this payment?</p>
        <div class="bg-light p-3 rounded mb-4 text-start border">
            <span class="text-danger fw-bold small text-uppercase" style="letter-spacing: 0.5px;">Reason:</span><br>
            <span class="fw-medium text-dark" id="customConfirmReasonText"></span>
        </div>
        <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger rounded-pill px-4" id="customConfirmBtn">Yes, Reject It</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('tableSearch');
    if(searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('.custom-table tbody tr');
            let visibleCount = 0;
            let hasDataRows = false;
            
            rows.forEach(row => {
                if(row.id === 'noResultsRow' || row.querySelector('td[colspan]')) return;
                hasDataRows = true;
                
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            const noResultsRow = document.getElementById('noResultsRow');
            if (noResultsRow) {
                noResultsRow.style.display = (visibleCount === 0 && hasDataRows) ? '' : 'none';
            }
        });
    }

    const verifyModal = document.getElementById('verifyModal');
    if (verifyModal) {
        verifyModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            
            document.getElementById('verifyPaymentId').value = button.getAttribute('data-id') || '';
            document.getElementById('verifyStudentName').textContent = button.getAttribute('data-name') || '';
            document.getElementById('verifyAmount').textContent = button.getAttribute('data-amount') || '';
            document.getElementById('verifyMethod').textContent = button.getAttribute('data-method') || '';
            document.getElementById('verifyRef').textContent = button.getAttribute('data-ref') || '';
            document.getElementById('verifyImage').src = button.getAttribute('data-img') || '';
        });
    }
});

function showRejectReason(reason) {
    document.getElementById('customReasonText').textContent = reason || 'No reason provided.';
    const modal = new bootstrap.Modal(document.getElementById('customReasonModal'));
    modal.show();
}

function confirmReject(btn) {
    const remarks = document.getElementById('verifyRemarks').value.trim();
    if (remarks === '') {
        const warnModal = new bootstrap.Modal(document.getElementById('customWarningModal'));
        warnModal.show();
        return;
    }
    
    document.getElementById('customConfirmReasonText').textContent = remarks;
    const confirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
    
    const confirmBtn = document.getElementById('customConfirmBtn');
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    newConfirmBtn.addEventListener('click', function() {
        const form = btn.closest('form');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'decision';
        input.value = 'reject';
        form.appendChild(input);
        form.submit();
    });
    
    confirmModal.show();
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>



