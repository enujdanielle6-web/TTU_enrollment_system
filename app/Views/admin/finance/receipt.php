<?php
$pageTitle = 'Official Payment Receipt - Triple T University';
require_once __DIR__ . '/../../components/header.php';

// Safe resolution of student and academic metadata to prevent any undefined index notices
$studentFirst = $payment['first_name'] ?? $payment['student_first'] ?? '';
$studentLast  = $payment['last_name'] ?? $payment['student_last'] ?? '';
$studentName  = trim($studentFirst . ' ' . $studentLast);
if (empty($studentName)) {
    $studentName = $payment['student_email'] ?? 'Enrolled Student';
}

$studentNumber = $payment['student_number'] ?? '';
$appRef        = $payment['app_ref'] ?? '';
$identifier    = !empty($studentNumber) ? $studentNumber : (!empty($appRef) ? $appRef : 'N/A');

$academicLevel = $payment['academic_level'] ?? ($assessment['academic_level'] ?? 'College');
$gradeLevel    = $payment['grade_level'] ?? ($assessment['grade_level'] ?? '');
$strand        = $payment['strand'] ?? ($assessment['strand'] ?? '');
$programInfo   = trim($academicLevel . ($gradeLevel ? ' • ' . $gradeLevel : '') . ($strand ? ' (' . $strand . ')' : ''));

$schoolYear    = $payment['app_school_year'] ?? ($systemSettings['active_school_year'] ?? '2026-2027');
$semester      = $payment['app_semester'] ?? ($systemSettings['active_semester'] ?? 'First');
$termString    = "A.Y. {$schoolYear}" . (!empty($semester) ? " • {$semester} Semester" : "");

$cashierName   = !empty($payment['cashier_first']) 
    ? trim($payment['cashier_first'] . ' ' . $payment['cashier_last']) 
    : ($payment['payment_method'] === 'PayMongo' ? 'Online Gateway (Automated)' : 'Cashier Office');

$receiptNo     = !empty($payment['receipt_number']) ? $payment['receipt_number'] : 'REC-PENDING';
$refNo         = $payment['reference_number'] ?? 'N/A';
$payMethod     = $payment['payment_method'] ?? 'Cash';
$amountPaid    = (float)($payment['amount'] ?? 0);
$netAmount     = (float)($assessment['net_amount'] ?? $amountPaid);
$totalPaid     = (float)($assessment['total_paid'] ?? $amountPaid);
$balance       = max(0.0, $netAmount - $totalPaid);
$processedDate = !empty($payment['updated_at']) ? $payment['updated_at'] : $payment['created_at'];

// Inline pure SVG Code 39 Barcode generator for thermal & standard output
function generateCode39Svg(string $text, int $height = 38): string
{
    $chars = [
        '0'=>'000110100', '1'=>'100100001', '2'=>'001100001', '3'=>'101100000',
        '4'=>'000110001', '5'=>'100110000', '6'=>'001110000', '7'=>'000100101',
        '8'=>'100100100', '9'=>'001100100', 'A'=>'100001001', 'B'=>'001001001',
        'C'=>'101001000', 'D'=>'000011001', 'E'=>'100011000', 'F'=>'001011000',
        'G'=>'000001101', 'H'=>'100001100', 'I'=>'001001100', 'J'=>'000011100',
        'K'=>'100000011', 'L'=>'001000011', 'M'=>'101000010', 'N'=>'000010011',
        'O'=>'100010010', 'P'=>'001010010', 'Q'=>'000000111', 'R'=>'100000110',
        'S'=>'001000110', 'T'=>'000010110', 'U'=>'110000001', 'V'=>'011000001',
        'W'=>'111000000', 'X'=>'010010001', 'Y'=>'110010000', 'Z'=>'011010000',
        '-'=>'010000101', '.'=>'110000100', ' '=>'011000100', '*'=>'010010100',
        '$'=>'010101000', '/'=>'010100010', '+'=>'010001010', '%'=>'000101010'
    ];

    $clean = preg_replace('/[^A-Z0-9\-\.\ \$\/\+\%]/', '', strtoupper(trim($text)));
    if (empty($clean)) $clean = 'REC-0000';
    $formatted = '*' . $clean . '*';
    $narrow = 1.35;
    $wide = 3.3;
    $gap = 1.35;

    $elements = [];
    $x = 0;

    for ($i = 0; $i < strlen($formatted); $i++) {
        $c = $formatted[$i];
        if (!isset($chars[$c])) continue;
        $pattern = $chars[$c];
        for ($b = 0; $b < 9; $b++) {
            $isBar = ($b % 2 === 0);
            $isWide = ($pattern[$b] === '1');
            $w = $isWide ? $wide : $narrow;
            if ($isBar) {
                $elements[] = sprintf('<rect x="%.2f" y="0" width="%.2f" height="%d" fill="#111" />', $x, $w, $height);
            }
            $x += $w;
        }
        $x += $gap;
    }

    $totalWidth = ceil($x);
    return sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" class="barcode-svg" style="width: 100%%; max-width: 250px; height: %dpx; display: block; margin: 0 auto;">%s</svg>',
        $totalWidth,
        $height,
        $height,
        implode('', $elements)
    );
}

$barcodeSvg = generateCode39Svg($receiptNo, 36);
?>

<style>
@import url('/sia/public/vendor/fonts/fonts.css');

/* Main Canvas Styling */
body {
    background-color: #f1f5f9;
}

/* Print View Controls */
.view-mode-pill {
    background: #e2e8f0;
    border-radius: 9999px;
    padding: 3px;
    display: inline-flex;
    gap: 2px;
}
.view-mode-pill button {
    border: none;
    background: transparent;
    padding: 6px 16px;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
    transition: all 0.2s ease;
}
.view-mode-pill button.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

/* ===================================================
   THERMAL POS RECEIPT (80mm Width Layout)
   =================================================== */
.thermal-receipt-stage {
    display: flex;
    justify-content: center;
    padding: 1.5rem 0 3rem;
}

.receipt-thermal {
    width: 340px;
    max-width: 100%;
    background: #ffffff;
    padding: 24px 20px 28px;
    margin: 0 auto;
    color: #000000;
    font-family: 'Courier New', Courier, 'Lucida Console', Monaco, monospace;
    font-size: 11.5px;
    line-height: 1.35;
    position: relative;
    box-shadow: 0 12px 35px -8px rgba(0, 0, 0, 0.18), 0 0 0 1px rgba(0, 0, 0, 0.05);
    border-top: 4px solid #1e293b;
}

/* Perforated Thermal Cut Edge effect */
.receipt-thermal::before,
.receipt-thermal::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    height: 6px;
    background-size: 12px 6px;
    background-repeat: repeat-x;
}
.receipt-thermal::after {
    bottom: -6px;
    background-image: radial-gradient(circle at 6px 0, transparent 4px, #ffffff 4px);
}

.receipt-thermal .thermal-header {
    text-align: center;
    margin-bottom: 12px;
}
.receipt-thermal .thermal-logo {
    width: 44px;
    height: 44px;
    object-fit: contain;
    filter: grayscale(100%) contrast(150%);
    margin-bottom: 6px;
}
.receipt-thermal .thermal-title {
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 0.5px;
    margin-bottom: 1px;
    text-transform: uppercase;
}
.receipt-thermal .thermal-sub {
    font-size: 10px;
    margin-bottom: 2px;
    text-transform: uppercase;
}
.receipt-thermal .thermal-dash {
    border-top: 1px dashed #000000;
    margin: 8px 0;
}
.receipt-thermal .thermal-double-dash {
    border-top: 1px double #000000;
    border-bottom: 1px double #000000;
    height: 3px;
    margin: 8px 0;
}
.receipt-thermal .thermal-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 3px;
}
.receipt-thermal .thermal-row.bold {
    font-weight: 700;
}
.receipt-thermal .thermal-label {
    text-transform: uppercase;
}
.receipt-thermal .thermal-val {
    text-align: right;
    font-weight: 600;
}
.receipt-thermal .thermal-stamp {
    display: inline-block;
    border: 1.5px solid #000;
    padding: 3px 8px;
    font-weight: 800;
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin: 6px 0;
}
.receipt-thermal .thermal-footer {
    text-align: center;
    font-size: 10px;
    margin-top: 12px;
    line-height: 1.35;
}

/* ===================================================
   STANDARD A4 DOCUMENT RECEIPT
   =================================================== */
.receipt-standard {
    max-width: 760px;
    margin: 0 auto;
    font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
    position: relative;
    background: #ffffff;
    border-radius: 1.25rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 15px 35px -5px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.receipt-standard .receipt-watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0.035;
    z-index: 0;
    width: 62%;
    pointer-events: none;
}

.receipt-standard-header {
    border-bottom: 2px dashed #e2e8f0;
    padding-bottom: 1.5rem;
    margin-bottom: 1.75rem;
}

.receipt-standard-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.04em;
    margin-bottom: 3px;
}

.receipt-standard-value {
    font-size: 1.05rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0;
}

/* ===================================================
   PRINT MEDIA RULES
   =================================================== */
@media print {
    /* Hide surrounding navigation, buttons, and sidebars */
    .no-print, 
    .no-print *, 
    .admin-navbar, 
    .admin-sidebar, 
    header, 
    footer,
    nav {
        display: none !important;
    }

    body {
        background-color: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    main {
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
    }

    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
    }

    .admin-wrapper {
        display: block !important;
    }
    .admin-main {
        width: 100% !important;
        margin-left: 0 !important;
        padding: 0 !important;
    }

    /* Print Mode 1: THERMAL 80mm ROLL */
    body:not(.print-standard-mode) .receipt-standard {
        display: none !important;
    }

    body:not(.print-standard-mode) .thermal-receipt-stage {
        padding: 0 !important;
        margin: 0 auto !important;
        display: block !important;
    }

    body:not(.print-standard-mode) .receipt-thermal {
        width: 80mm !important;
        max-width: 80mm !important;
        padding: 3mm 2.5mm !important;
        margin: 0 auto !important;
        box-shadow: none !important;
        border: none !important;
        color: #000000 !important;
        page-break-inside: avoid;
    }

    body:not(.print-standard-mode) .receipt-thermal::before,
    body:not(.print-standard-mode) .receipt-thermal::after {
        display: none !important;
    }

    @page {
        size: 80mm auto;
        margin: 0;
    }

    /* Print Mode 2: STANDARD A4 CERTIFICATE (when activated) */
    body.print-standard-mode .thermal-receipt-stage {
        display: none !important;
    }

    body.print-standard-mode .receipt-standard {
        display: block !important;
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    body.print-standard-mode @page {
        size: A4 portrait;
        margin: 12mm 15mm;
    }
}
</style>

<?php require_once __DIR__ . '/../../components/admin_navbar.php'; ?>

<main class="py-4 py-lg-5 min-vh-100 position-relative">
  <div class="container-fluid px-3 px-lg-5">
    
    <!-- Top Action & Layout Selector Toolbar -->
    <div class="no-print d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 mx-auto" style="max-width: 760px;">
      <div class="d-flex align-items-center gap-2">
        <a href="cashier_payments.php" class="btn btn-white bg-white border shadow-xs rounded-pill px-3.5 py-1.5 fw-semibold text-secondary hover-primary transition-all small d-inline-flex align-items-center">
          <i class="bi bi-arrow-left me-1.5"></i> Payments
        </a>

        <!-- Interactive Layout Switcher (Thermal vs Standard) -->
        <div class="view-mode-pill shadow-xs">
          <button type="button" id="tabThermal" class="active" onclick="switchView('thermal')">
            <i class="bi bi-receipt me-1"></i> Thermal POS (80mm)
          </button>
          <button type="button" id="tabStandard" onclick="switchView('standard')">
            <i class="bi bi-file-earmark-text me-1"></i> Standard Voucher
          </button>
        </div>
      </div>

      <!-- Quick Print Buttons -->
      <div class="d-flex align-items-center gap-2">
        <button type="button" onclick="printThermal()" class="btn btn-primary rounded-pill px-3.5 py-1.5 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); border: none;">
          <i class="bi bi-printer-fill fs-6"></i>
          <span>Print Thermal Slip</span>
        </button>
        <button type="button" onclick="printStandard()" class="btn btn-outline-secondary bg-white rounded-pill px-3 py-1.5 fw-semibold shadow-xs small d-inline-flex align-items-center gap-1" title="Print as full-page A4 document">
          <i class="bi bi-file-pdf"></i>
          <span>Print A4</span>
        </button>
      </div>
    </div>

    <!-- ========================================================
         VIEW 1: THERMAL PAPER RECEIPT (80mm POS Slip Layout)
         ======================================================== -->
    <div id="thermalViewSection" class="thermal-receipt-stage">
      <div class="receipt-thermal">
        
        <!-- Header -->
        <div class="thermal-header">
          <img src="/sia/images/TTU_LOGO.png" alt="TTU" class="thermal-logo">
          <div class="thermal-title">Triple T University</div>
          <div class="thermal-sub">Office of the Cashier & Finance</div>
          <div style="font-size: 9px; opacity: 0.85;">Institution Code: TTU-NCR-2026</div>
          <div class="thermal-stamp">*** OFFICIAL RECEIPT ***</div>
        </div>

        <div class="thermal-dash"></div>

        <!-- Meta Grid -->
        <div class="thermal-row">
          <span class="thermal-label">OR NUMBER:</span>
          <span class="thermal-val" style="font-weight: 800;"><?= htmlspecialchars($receiptNo, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="thermal-row">
          <span class="thermal-label">DATE & TIME:</span>
          <span class="thermal-val"><?= date('M d, Y • h:i A', strtotime($processedDate)) ?></span>
        </div>
        <div class="thermal-row">
          <span class="thermal-label">PAYMENT METHOD:</span>
          <span class="thermal-val"><?= htmlspecialchars($payMethod, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="thermal-row">
          <span class="thermal-label">REF NUMBER:</span>
          <span class="thermal-val" style="font-size: 10px;"><?= htmlspecialchars($refNo, ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <div class="thermal-dash"></div>

        <!-- Student Payor Info -->
        <div class="thermal-row">
          <span class="thermal-label">RECEIVED FROM:</span>
        </div>
        <div style="font-size: 13px; font-weight: 800; text-transform: uppercase; margin-bottom: 2px;">
          <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="thermal-row">
          <span class="thermal-label">ID / APP NO:</span>
          <span class="thermal-val"><?= htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="thermal-row">
          <span class="thermal-label">PROGRAM:</span>
          <span class="thermal-val" style="max-width: 170px; word-break: break-word;"><?= htmlspecialchars($programInfo, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="thermal-row">
          <span class="thermal-label">TERM:</span>
          <span class="thermal-val"><?= htmlspecialchars($termString, ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <div class="thermal-dash"></div>

        <!-- Fee Items / Breakdown -->
        <div style="font-weight: 700; text-transform: uppercase; margin-bottom: 4px; display: flex; justify-content: space-between;">
          <span>ITEM / DESCRIPTION</span>
          <span>AMOUNT</span>
        </div>

        <?php if (!empty($items)): ?>
          <?php foreach ($items as $item): ?>
            <div class="thermal-row">
              <span style="max-width: 210px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <?= htmlspecialchars($item['item_name'] ?? $item['item_code'], ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($item['units']) && (float)$item['units'] > 0): ?>
                  <span style="font-size: 9.5px; opacity: 0.75;">(<?= (float)$item['units'] ?>u)</span>
                <?php endif; ?>
              </span>
              <span class="thermal-val">₱<?= number_format((float)$item['amount'], 2) ?></span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <!-- Category Fee Fallbacks from Assessment Snapshot -->
          <?php if (!empty($assessment['tuition_fee']) && (float)$assessment['tuition_fee'] > 0): ?>
            <div class="thermal-row"><span>Tuition Fee</span><span class="thermal-val">₱<?= number_format((float)$assessment['tuition_fee'], 2) ?></span></div>
          <?php endif; ?>
          <?php if (!empty($assessment['laboratory_fee']) && (float)$assessment['laboratory_fee'] > 0): ?>
            <div class="thermal-row"><span>Laboratory Fee</span><span class="thermal-val">₱<?= number_format((float)$assessment['laboratory_fee'], 2) ?></span></div>
          <?php endif; ?>
          <?php if (!empty($assessment['miscellaneous_fee']) && (float)$assessment['miscellaneous_fee'] > 0): ?>
            <div class="thermal-row"><span>Miscellaneous Fee</span><span class="thermal-val">₱<?= number_format((float)$assessment['miscellaneous_fee'], 2) ?></span></div>
          <?php endif; ?>
          <?php if (!empty($assessment['registration_fee']) && (float)$assessment['registration_fee'] > 0): ?>
            <div class="thermal-row"><span>Registration Fee</span><span class="thermal-val">₱<?= number_format((float)$assessment['registration_fee'], 2) ?></span></div>
          <?php endif; ?>
          <?php if (!empty($assessment['other_fees']) && (float)$assessment['other_fees'] > 0): ?>
            <div class="thermal-row"><span>Other School Fees</span><span class="thermal-val">₱<?= number_format((float)$assessment['other_fees'], 2) ?></span></div>
          <?php endif; ?>
        <?php endif; ?>

        <div class="thermal-dash"></div>

        <!-- Totals Calculation -->
        <div class="thermal-row">
          <span class="thermal-label">Total Assessment:</span>
          <span class="thermal-val">₱<?= number_format($netAmount, 2) ?></span>
        </div>
        <?php if (!empty($assessment['discount_amount']) && (float)$assessment['discount_amount'] > 0): ?>
          <div class="thermal-row">
            <span class="thermal-label">Discounts / Grant:</span>
            <span class="thermal-val">-₱<?= number_format((float)$assessment['discount_amount'], 2) ?></span>
          </div>
        <?php endif; ?>

        <div class="thermal-double-dash"></div>

        <div class="thermal-row bold" style="font-size: 15px; margin: 4px 0;">
          <span class="thermal-label">AMOUNT PAID:</span>
          <span class="thermal-val">₱<?= number_format($amountPaid, 2) ?></span>
        </div>

        <div class="thermal-double-dash"></div>

        <div class="thermal-row">
          <span class="thermal-label">Total Cumulative Paid:</span>
          <span class="thermal-val">₱<?= number_format($totalPaid, 2) ?></span>
        </div>
        <div class="thermal-row">
          <span class="thermal-label">Remaining Balance:</span>
          <span class="thermal-val <?= $balance <= 0 ? 'text-success' : 'text-danger' ?>">
            ₱<?= number_format($balance, 2) ?>
            <?= $balance <= 0 ? ' (PAID IN FULL)' : '' ?>
          </span>
        </div>
        <div class="thermal-row">
          <span class="thermal-label">STATUS:</span>
          <span class="thermal-val" style="font-weight: 800;"><?= strtoupper($payment['status']) ?></span>
        </div>

        <div class="thermal-dash"></div>

        <!-- Cashier & Verification Barcode -->
        <div class="thermal-row">
          <span class="thermal-label">PROCESSED BY:</span>
          <span class="thermal-val"><?= htmlspecialchars($cashierName, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="thermal-row">
          <span class="thermal-label">CASHIER SIGNATURE:</span>
          <span class="thermal-val">____________________</span>
        </div>

        <div class="my-3 text-center">
          <?= $barcodeSvg ?>
          <div style="font-size: 9.5px; letter-spacing: 2px; margin-top: 2px; font-weight: 700;">
            *<?= htmlspecialchars($receiptNo, ENT_QUOTES, 'UTF-8') ?>*
          </div>
        </div>

        <!-- Footer Notice -->
        <div class="thermal-footer">
          <div style="font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">Thank You for Enrolling at TTU!</div>
          <div>This computer-generated slip serves as an Official Receipt.</div>
          <div>Valid for official enrollment clearance and subject registration.</div>
          <div class="mt-2" style="font-size: 9px; opacity: 0.7;">
            - - - - - - - - ✂ TEAR HERE ✂ - - - - - - - -
          </div>
        </div>

      </div>
    </div>

    <!-- ========================================================
         VIEW 2: STANDARD VOUCHER RECEIPT (A4 Layout)
         ======================================================== -->
    <div id="standardViewSection" class="receipt-standard" style="display: none;">
      
      <!-- Top Decorative Accent -->
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 6px;"></div>
      
      <!-- Background Seal Watermark -->
      <img src="/sia/images/TTU_LOGO.png" alt="TTU Watermark" class="receipt-watermark">
      
      <div class="p-4 p-sm-5 position-relative" style="z-index: 2;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center receipt-standard-header">
          <div class="d-flex align-items-center">
            <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 68px; width: auto; object-fit: contain;" class="me-3">
            <div>
              <h1 class="h4 fw-black text-dark mb-0" style="letter-spacing: -0.5px; font-family: 'Poppins', sans-serif;">TRIPLE T UNIVERSITY</h1>
              <p class="text-muted mb-0 small fw-medium">Office of the Cashier & Student Financial Accounting</p>
              <div class="text-muted" style="font-size: 0.72rem; letter-spacing: 0.3px;">Manila Campus • Institution Code: TTU-NCR-2026</div>
            </div>
          </div>
          <div class="text-end">
            <h2 class="h5 fw-bold text-primary mb-1 text-uppercase tracking-wider">Official Receipt</h2>
            <?php if ($payment['status'] === 'pending'): ?>
              <span class="badge bg-warning text-dark border border-warning rounded-pill px-3 py-1 fs-6 fw-bold">
                <i class="bi bi-hourglass-split me-1"></i> PENDING VERIFICATION
              </span>
            <?php elseif ($payment['status'] === 'rejected'): ?>
              <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill px-3 py-1 fs-6 fw-bold">
                <i class="bi bi-x-circle-fill me-1"></i> REJECTED
              </span>
            <?php else: ?>
              <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3 py-1 fs-6 fw-bold">
                <i class="bi bi-check-circle-fill me-1"></i> VERIFIED &amp; POSTED
              </span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Metadata Grid -->
        <div class="row g-4 mb-4">
          <div class="col-sm-6">
            <p class="receipt-standard-label">Receipt Number</p>
            <p class="receipt-standard-value text-primary fs-5 fw-bold">
              <?= htmlspecialchars($receiptNo, ENT_QUOTES, 'UTF-8') ?>
            </p>
          </div>
          <div class="col-sm-6 text-sm-end">
            <p class="receipt-standard-label">Date Processed</p>
            <p class="receipt-standard-value"><?= date('M j, Y • h:i A', strtotime($processedDate)) ?></p>
          </div>

          <div class="col-sm-6">
            <p class="receipt-standard-label">Received From (Student / Payor)</p>
            <p class="receipt-standard-value fs-4 fw-bold text-dark mb-0"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="small text-muted"><?= htmlspecialchars($programInfo, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
          <div class="col-sm-6 text-sm-end">
            <p class="receipt-standard-label">Student / Application Number</p>
            <p class="receipt-standard-value font-monospace bg-light d-inline-block px-3 py-1 rounded border"><?= htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="small text-muted mt-1"><?= htmlspecialchars($termString, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </div>

        <!-- Breakdown Table if line items exist -->
        <?php if (!empty($items)): ?>
          <div class="table-responsive mb-4 rounded-3 border">
            <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.85rem;">
              <thead class="table-light">
                <tr>
                  <th class="ps-3 py-2 text-uppercase fw-bold text-muted" style="font-size: 0.7rem;">Item / Fee Description</th>
                  <th class="text-center text-uppercase fw-bold text-muted" style="font-size: 0.7rem;">Units</th>
                  <th class="text-end text-uppercase fw-bold text-muted" style="font-size: 0.7rem;">Rate</th>
                  <th class="text-end pe-3 text-uppercase fw-bold text-muted" style="font-size: 0.7rem;">Amount</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                  <tr>
                    <td class="ps-3 py-2 fw-medium text-dark"><?= htmlspecialchars($item['item_name'] ?? $item['item_code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-center text-muted"><?= (float)($item['units'] ?? 0) > 0 ? (float)$item['units'] : '—' ?></td>
                    <td class="text-end text-muted"><?= (float)($item['rate_per_unit'] ?? 0) > 0 ? '₱' . number_format((float)$item['rate_per_unit'], 2) : '—' ?></td>
                    <td class="text-end pe-3 fw-bold text-dark">₱<?= number_format((float)$item['amount'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <!-- Payment Breakdown Box -->
        <div class="bg-light bg-opacity-75 p-4 rounded-4 mb-4 border shadow-2xs position-relative">
          <div class="row align-items-center mb-3">
            <div class="col-7">
              <span class="receipt-standard-label d-block mb-1">Payment Method</span>
              <span class="fs-5 fw-bold text-dark d-flex align-items-center">
                <i class="bi bi-wallet2 text-primary me-2"></i> <?= htmlspecialchars($payMethod, ENT_QUOTES, 'UTF-8') ?>
              </span>
            </div>
            <div class="col-5 text-end">
              <span class="receipt-standard-label d-block mb-1">Reference No.</span>
              <span class="fs-6 fw-semibold text-secondary font-monospace"><?= htmlspecialchars($refNo, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          </div>
          
          <hr class="my-3 border-secondary border-opacity-25 dashed">
          
          <div class="row g-3">
            <div class="col-sm-4">
              <span class="text-muted small d-block">Total Assessment</span>
              <span class="fw-bold fs-6">₱<?= number_format($netAmount, 2) ?></span>
            </div>
            <div class="col-sm-4 text-sm-center">
              <span class="text-muted small d-block">Cumulative Total Paid</span>
              <span class="fw-bold fs-6 text-primary">₱<?= number_format($totalPaid, 2) ?></span>
            </div>
            <div class="col-sm-4 text-sm-end">
              <span class="text-muted small d-block">Remaining Balance</span>
              <span class="fw-bold fs-6 <?= $balance <= 0 ? 'text-success' : 'text-danger' ?>">
                ₱<?= number_format($balance, 2) ?>
              </span>
            </div>
          </div>

          <hr class="my-3 border-secondary border-opacity-25 dashed">

          <div class="d-flex justify-content-between align-items-end mt-3">
            <div>
              <span class="text-uppercase fw-bold text-muted small" style="letter-spacing: 0.5px;">Amount Paid on this Receipt</span>
              <div class="small text-muted">Processed by: <strong><?= htmlspecialchars($cashierName, ENT_QUOTES, 'UTF-8') ?></strong></div>
            </div>
            <div class="text-end">
              <span class="fw-black text-success" style="font-size: 2.35rem; line-height: 1; letter-spacing: -1px;">₱<?= number_format($amountPaid, 2) ?></span>
            </div>
          </div>
        </div>

        <!-- Signatures & Barcode Section -->
        <div class="row align-items-end pt-3 mt-4 border-top">
          <div class="col-sm-7">
            <div class="mb-2">
              <?= $barcodeSvg ?>
              <div class="text-center small font-monospace text-muted mt-1" style="font-size: 0.75rem;">*<?= htmlspecialchars($receiptNo, ENT_QUOTES, 'UTF-8') ?>*</div>
            </div>
            <p class="text-muted mb-1" style="font-size: 0.72rem;"><i class="bi bi-info-circle me-1"></i> System-generated Official Electronic Receipt (e-OR).</p>
            <p class="text-muted mb-0" style="font-size: 0.72rem;">Valid for academic enrollment clearance and student matriculation without physical stamp.</p>
          </div>
          <div class="col-sm-5 text-sm-end mt-4 mt-sm-0">
            <div class="d-inline-block text-center" style="width: 200px;">
              <div class="border-bottom border-dark pb-1 fw-bold text-dark small"><?= htmlspecialchars($cashierName, ENT_QUOTES, 'UTF-8') ?></div>
              <div class="text-muted" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px;">Authorized Cashier Signature</div>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</main>

<div class="no-print">
<?php require_once __DIR__ . '/../../components/footer.php'; ?>
</div>

<script>
function switchView(mode) {
  const thermalSection = document.getElementById('thermalViewSection');
  const standardSection = document.getElementById('standardViewSection');
  const tabThermal = document.getElementById('tabThermal');
  const tabStandard = document.getElementById('tabStandard');

  if (mode === 'thermal') {
    if (thermalSection) thermalSection.style.display = 'flex';
    if (standardSection) standardSection.style.display = 'none';
    if (tabThermal) tabThermal.classList.add('active');
    if (tabStandard) tabStandard.classList.remove('active');
    document.body.classList.remove('print-standard-mode');
  } else {
    if (thermalSection) thermalSection.style.display = 'none';
    if (standardSection) standardSection.style.display = 'block';
    if (tabThermal) tabThermal.classList.remove('active');
    if (tabStandard) tabStandard.classList.add('active');
    document.body.classList.add('print-standard-mode');
  }
}

function printThermal() {
  switchView('thermal');
  setTimeout(function() {
    window.print();
  }, 100);
}

function printStandard() {
  switchView('standard');
  setTimeout(function() {
    window.print();
  }, 100);
}

// Ensure default print hotkey (Ctrl+P / Cmd+P) respects the user's active tab view
window.onbeforeprint = function() {
  const isStandardActive = document.getElementById('tabStandard') && document.getElementById('tabStandard').classList.contains('active');
  if (isStandardActive) {
    document.body.classList.add('print-standard-mode');
  } else {
    document.body.classList.remove('print-standard-mode');
  }
};
</script>
