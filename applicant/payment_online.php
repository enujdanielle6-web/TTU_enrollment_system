<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

$assessmentId = (int) ($_GET['assessment_id'] ?? 0);
$userId = (int) $_SESSION['user_id'];

if ($assessmentId <= 0) {
    header('Location: assessment.php');
    exit;
}

try {
    $stmt = $pdo->prepare('
        SELECT sa.*, a.reference_number 
        FROM student_assessments sa
        JOIN applications a ON sa.application_id = a.id
        WHERE sa.id = :id AND sa.user_id = :user_id LIMIT 1
    ');
    $stmt->execute(['id' => $assessmentId, 'user_id' => $userId]);
    $assessment = $stmt->fetch();

    if (!$assessment || !(bool)$assessment['preview_accepted']) {
        header('Location: assessment.php');
        exit;
    }
    
    $balance = (float)$assessment['net_amount'] - (float)$assessment['total_paid'];
    if ($balance <= 0) {
        header('Location: assessment.php');
        exit;
    }

} catch (PDOException $e) {
    error_log('Online payment fetch failed: ' . $e->getMessage());
    header('Location: assessment.php');
    exit;
}

$pageTitle = 'Secure Online Payment';
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/components/navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container px-lg-5" style="max-width: 800px;">
    
    <div class="mb-4 d-flex align-items-center">
      <a href="assessment.php" class="btn btn-sm btn-light border shadow-sm rounded-pill px-3 fw-medium text-dark me-3"><i class="bi bi-arrow-left me-1"></i> Back</a>
      <h1 class="h4 fw-bold text-dark mb-0">Secure Online Payment</h1>
    </div>

    <div class="island border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="bg-primary text-white p-4 text-center">
        <i class="bi bi-shield-check fs-1 d-block mb-2 text-white-50"></i>
        <h2 class="h5 fw-bold mb-1">TTU Secure Gateway (Mock)</h2>
        <p class="mb-0 text-white-50 small">This is a simulated payment gateway for demonstration purposes.</p>
      </div>
      
      <div class="island-body p-4 p-md-5">
        <div class="text-center mb-5">
            <p class="text-muted small text-uppercase fw-bold mb-1">Total Amount Due</p>
            <h3 class="display-5 fw-bold text-dark">₱<?= number_format($balance, 2) ?></h3>
            <p class="text-muted small mb-0">Ref: <?= htmlspecialchars($assessment['reference_number'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        
        <form action="payment_process_online.php" method="POST">
            <input type="hidden" name="assessment_id" value="<?= $assessment['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            
            <div class="mb-4">
                <label class="form-label fw-semibold text-dark">Select Payment Method</label>
                <div class="row g-3">
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="payment_method" id="method_gcash" value="GCash Online" checked>
                        <label class="btn btn-outline-primary w-100 p-3 rounded-4 text-start d-flex align-items-center" for="method_gcash">
                            <i class="bi bi-phone fs-4 me-3"></i>
                            <div>
                                <div class="fw-bold">GCash</div>
                                <div class="small text-muted" style="font-size: 0.7rem;">E-Wallet</div>
                            </div>
                        </label>
                    </div>
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="payment_method" id="method_maya" value="Maya Online">
                        <label class="btn btn-outline-primary w-100 p-3 rounded-4 text-start d-flex align-items-center" for="method_maya">
                            <i class="bi bi-wallet2 fs-4 me-3"></i>
                            <div>
                                <div class="fw-bold">Maya</div>
                                <div class="small text-muted" style="font-size: 0.7rem;">E-Wallet</div>
                            </div>
                        </label>
                    </div>
                    <div class="col-12">
                        <input type="radio" class="btn-check" name="payment_method" id="method_card" value="Credit/Debit Card">
                        <label class="btn btn-outline-primary w-100 p-3 rounded-4 text-start d-flex align-items-center" for="method_card">
                            <i class="bi bi-credit-card-2-front fs-4 me-3"></i>
                            <div>
                                <div class="fw-bold">Credit/Debit Card</div>
                                <div class="small text-muted" style="font-size: 0.7rem;">Visa, Mastercard, JCB</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label fw-semibold text-dark">Amount to Pay (₱)</label>
                <input type="number" step="0.01" min="1000" max="<?= $balance ?>" name="amount" class="form-control form-control-lg bg-light fw-bold" required value="<?= $balance ?>">
                <div class="form-text small">You can pay partially, but full settlement is required to be officially enrolled. Minimum amount is ₱1,000.00.</div>
            </div>

            <div class="d-grid mt-5">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">
                    <i class="bi bi-lock-fill me-2"></i> Confirm & Pay Securely
                </button>
            </div>
        </form>
        
      </div>
    </div>
  </div>
</main>

<style>
.btn-check:checked + .btn-outline-primary {
    background-color: rgba(13, 110, 253, 0.05);
    border-color: #0d6efd;
    color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}
.btn-check + .btn-outline-primary {
    border-color: #dee2e6;
    color: #495057;
}
.btn-check + .btn-outline-primary:hover {
    background-color: #f8f9fa;
    border-color: #ced4da;
    color: #495057;
}
</style>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
