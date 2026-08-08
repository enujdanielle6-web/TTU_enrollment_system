<?php
$pageTitle = 'Payment Receipt - Cashier';
require_once __DIR__ . '/../../components/header.php';
?>
<style>
@import url('/sia/public/vendor/fonts/fonts.css');

body {
    background-color: #f8f9fa;
}

@media print {
    .no-print, .no-print *, .d-lg-none, .admin-sidebar {
        display: none !important;
    }
    .print-only {
        display: block !important;
    }
    body {
        background-color: #fff !important;
        margin: 0;
        padding: 0;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    main {
        padding: 0 !important;
        background: transparent !important;
    }
    .admin-wrapper {
        display: block !important;
    }
    .admin-main {
        width: 100% !important;
        margin-left: 0 !important;
    }
    .receipt-container {
        max-width: 100% !important;
    }
}

.receipt-container {
    max-width: 750px;
    margin: 0 auto;
    font-family: 'Outfit', sans-serif;
    position: relative;
    z-index: 1;
}

.receipt-watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0.03;
    z-index: -1;
    width: 60%;
    pointer-events: none;
}

.receipt-header {
    border-bottom: 2px dashed #cbd5e1;
    margin-bottom: 30px;
    padding-bottom: 25px;
}

.receipt-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.receipt-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0;
}

.receipt-box {
    background: linear-gradient(145deg, #ffffff, #f8fafc);
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
}

.print-btn-float {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    transition: all 0.3s ease;
}
.print-btn-float:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
}
</style>

<?php require_once __DIR__ . '/../../components/admin_navbar.php'; ?>

<main class="py-5 min-vh-100 position-relative">
  <div class="container-fluid px-lg-5">
    
    <div class="no-print d-flex justify-content-between align-items-center mb-4 receipt-container">
      <div>
        <a href="cashier_payments.php" class="btn btn-white border shadow-sm rounded-pill px-4 fw-medium text-secondary hover-primary transition-all">
          <i class="bi bi-arrow-left me-2"></i> Back to Payments
        </a>
      </div>
      <div>
        <button onclick="window.print()" class="btn print-btn-float text-white px-4 py-2 shadow rounded-pill fw-bold border-0">
          <i class="bi bi-printer-fill me-2"></i> Print Official Receipt
        </button>
      </div>
    </div>

    <div class="card receipt-box rounded-4 receipt-container overflow-hidden">
      <!-- Decorative Top Border -->
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 6px;"></div>
      
      <img src="/sia/images/TTU_LOGO.png" alt="Watermark" class="receipt-watermark">
      
      <div class="card-body p-sm-5 p-4">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center receipt-header">
          <div class="d-flex align-items-center">
            <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 65px; width: auto; object-fit: contain;" class="me-3">
            <div>
              <h1 class="h3 fw-black text-dark mb-0" style="letter-spacing: -0.5px;">TRIPLE T UNIVERSITY</h1>
              <p class="text-muted mb-0 fw-medium">Office of the Cashier & Finance</p>
            </div>
          </div>
          <div class="text-end">
            <h2 class="h5 fw-bold text-primary mb-2 text-uppercase tracking-wider">Official Receipt</h2>
            <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3 py-1 fs-6 fw-bold">
              <i class="bi bi-check-circle-fill me-1"></i> VERIFIED
            </span>
          </div>
        </div>

        <!-- Details Grid -->
        <div class="row g-4 mb-5">
          <div class="col-sm-6">
            <p class="receipt-label">Receipt Number</p>
            <p class="receipt-value text-primary fs-5">RCPT-<?= str_pad((string)$payment['id'], 6, '0', STR_PAD_LEFT) ?></p>
          </div>
          <div class="col-sm-6 text-sm-end">
            <p class="receipt-label">Date Processed</p>
            <p class="receipt-value"><?= date('M j, Y • h:i A', strtotime($payment['updated_at'])) ?></p>
          </div>
          <div class="col-sm-6">
            <p class="receipt-label">Received From</p>
            <p class="receipt-value fs-4 fw-bold text-dark"><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name'], ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <div class="col-sm-6 text-sm-end">
            <p class="receipt-label">Student / App Number</p>
            <p class="receipt-value font-monospace bg-light d-inline-block px-3 py-1 rounded border"><?= htmlspecialchars($payment['student_number'] ?? $payment['app_ref'], ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        </div>

        <!-- Payment Breakdown Box -->
        <div class="bg-white p-4 rounded-4 mb-4 border shadow-sm position-relative">
          <div class="position-absolute top-0 start-0 w-4px h-100 bg-success rounded-start"></div>
          
          <div class="row align-items-center mb-3">
            <div class="col-7">
              <span class="receipt-label d-block mb-1">Payment Method</span>
              <span class="fs-5 fw-bold text-dark d-flex align-items-center">
                <i class="bi bi-wallet2 text-primary me-2"></i> <?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?>
              </span>
            </div>
            <div class="col-5 text-end">
              <span class="receipt-label d-block mb-1">Reference No.</span>
              <span class="fs-6 fw-semibold text-secondary font-monospace"><?= htmlspecialchars($payment['reference_number'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          </div>
          
          <hr class="my-3 border-secondary border-opacity-25 dashed">
          
          <div class="d-flex justify-content-between align-items-end mt-4">
            <div>
              <span class="text-uppercase fw-bold text-muted" style="letter-spacing: 1px; font-size: 0.85rem;">Total Amount Paid</span>
            </div>
            <div class="text-end">
              <span class="fw-black text-success" style="font-size: 2.5rem; line-height: 1; letter-spacing: -1px;">₱<?= number_format((float)$payment['amount'], 2) ?></span>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-end mt-5 pt-4 border-top border-light">
          <div>
            <p class="text-muted mb-1" style="font-size: 0.75rem;"><i class="bi bi-info-circle me-1"></i> This is a system-generated official receipt.</p>
            <p class="text-muted mb-0" style="font-size: 0.75rem;">Document is valid without signature. For inquiries, contact cashier@triplet.edu.ph.</p>
          </div>
          <div class="text-end">
            <img src="/sia/images/TTU_LOGO.png" alt="TTU" style="height: 30px; opacity: 0.3; filter: grayscale(100%);">
          </div>
        </div>

      </div>
    </div>

  </div>
</main>

<div class="no-print">
<?php require_once __DIR__ . '/../../components/footer.php'; ?>
</div>

