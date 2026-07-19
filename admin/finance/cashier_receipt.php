<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('receipts.print');

$paymentId = (int) ($_GET['id'] ?? 0);

if ($paymentId <= 0) {
    header('Location: cashier_payments.php');
    exit;
}

try {
    // Fetch Payment Details
    $stmt = $pdo->prepare('
        SELECT pr.*, 
               u.first_name as student_first, u.last_name as student_last,
               c.first_name as cashier_first, c.last_name as cashier_last,
               a.reference_number as app_ref,
               sa.total_amount, sa.discount_amount, sa.net_amount, sa.total_paid
        FROM payment_records pr
        INNER JOIN users u ON pr.user_id = u.id
        LEFT JOIN users c ON pr.cashier_id = c.id
        INNER JOIN student_assessments sa ON pr.assessment_id = sa.id
        INNER JOIN applications a ON sa.application_id = a.id
        WHERE pr.id = :id LIMIT 1
    ');
    $stmt->execute(['id' => $paymentId]);
    $receipt = $stmt->fetch();

    if (!$receipt) {
        header('Location: cashier_payments.php');
        exit;
    }

    $balance = (float)$receipt['net_amount'] - (float)$receipt['total_paid'];
    if ($balance < 0) $balance = 0;

} catch (PDOException $e) {
    error_log('Receipt fetch failed: ' . $e->getMessage());
    $_SESSION['error_msg'] = 'A database error occurred while querying the receipt.';
    header('Location: cashier_payments.php');
    exit;
}

$pageTitle = 'Receipt - ' . htmlspecialchars($receipt['receipt_number'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .receipt-container { max-width: 600px; margin: 40px auto; background: #fff; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 12px; }
        .receipt-header { border-bottom: 2px dashed #dee2e6; padding-bottom: 20px; margin-bottom: 20px; text-align: center; }
        .receipt-logo { font-weight: 900; font-size: 24px; color: #0d6efd; letter-spacing: -1px; }
        .receipt-details { margin-bottom: 30px; font-size: 14px; }
        .receipt-amounts { border-top: 2px solid #212529; padding-top: 20px; }
        .amount-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .amount-row.total { font-size: 18px; font-weight: bold; border-top: 1px solid #dee2e6; padding-top: 10px; margin-top: 10px; }
        @media print {
            body { background: #fff; }
            .receipt-container { box-shadow: none; margin: 0; padding: 20px; max-width: 100%; border-radius: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    
    <div class="container mb-3 mt-3 no-print text-center">
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success d-inline-block mx-auto mb-3"><?= htmlspecialchars($_SESSION['success_msg'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>
        <div>
            <a href="cashier_assessment.php?id=<?= $receipt['assessment_id'] ?>" class="btn btn-outline-secondary me-2">&larr; Back to Account</a>
            <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print Receipt</button>
        </div>
    </div>

    <div class="receipt-container">
        <div class="receipt-header">
            <div class="receipt-logo mb-2">TRIPLE T UNIVERSITY</div>
            <div class="text-muted small mb-2">123 University Blvd, City, Country<br>Phone: (123) 456-7890</div>
            <h4 class="mt-3 text-uppercase fw-bold text-dark mb-0">Official Receipt</h4>
        </div>

        <div class="receipt-details row g-3">
            <div class="col-6">
                <div class="text-muted small fw-bold text-uppercase">Receipt No.</div>
                <div class="fw-bold text-dark"><?= htmlspecialchars($receipt['receipt_number'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="col-6 text-end">
                <div class="text-muted small fw-bold text-uppercase">Date & Time</div>
                <div class="fw-bold text-dark"><?= date('M d, Y g:i A', strtotime($receipt['created_at'])) ?></div>
            </div>
            <div class="col-6">
                <div class="text-muted small fw-bold text-uppercase">Student Name</div>
                <div class="fw-bold text-dark"><?= htmlspecialchars($receipt['student_last'] . ', ' . $receipt['student_first'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="col-6 text-end">
                <div class="text-muted small fw-bold text-uppercase">Enrollment Ref</div>
                <div class="fw-bold text-dark"><?= htmlspecialchars($receipt['app_ref'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="col-6">
                <div class="text-muted small fw-bold text-uppercase">Payment Method</div>
                <div class="fw-bold text-dark"><?= htmlspecialchars($receipt['payment_method'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="col-6 text-end">
                <div class="text-muted small fw-bold text-uppercase">Ext. Reference</div>
                <div class="fw-bold text-dark"><?= htmlspecialchars($receipt['reference_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <div class="receipt-amounts">
            <div class="amount-row text-muted small">
                <span>Assessment Net Amount:</span>
                <span>₱<?= number_format((float)$receipt['net_amount'], 2) ?></span>
            </div>
            <div class="amount-row total">
                <span>Amount Paid:</span>
                <span>₱<?= number_format((float)$receipt['amount'], 2) ?></span>
            </div>
            <div class="amount-row text-muted mt-3">
                <span class="fw-bold text-dark">Current Balance:</span>
                <span class="fw-bold text-dark">₱<?= number_format($balance, 2) ?></span>
            </div>
        </div>

        <div class="mt-5 pt-3 border-top text-center text-muted" style="font-size: 12px;">
            <p class="mb-1">Processed By: <strong><?= htmlspecialchars($receipt['cashier_first'] . ' ' . $receipt['cashier_last'], ENT_QUOTES, 'UTF-8') ?></strong></p>
            <p class="mb-0">Thank you for your payment!</p>
            <p class="mt-2"><em>This is a system-generated document.</em></p>
        </div>
    </div>
</body>
</html>

