<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

$userId = (int) $_SESSION['user_id'];

// Fetch the applicant's assessment
$assessment = null;
$payments = [];
try {
    $stmt = $pdo->prepare('
        SELECT sa.*, a.reference_number, a.academic_level, a.grade_level, a.strand, a.school_year, s.name as scholarship_name
        FROM student_assessments sa
        INNER JOIN applications a ON sa.application_id = a.id
        LEFT JOIN scholarships s ON sa.scholarship_id = s.id
        WHERE sa.user_id = :user_id
        ORDER BY sa.created_at DESC LIMIT 1
    ');
    $stmt->execute(['user_id' => $userId]);
    $assessment = $stmt->fetch();

    if ($assessment) {
        $payStmt = $pdo->prepare('SELECT * FROM payment_records WHERE assessment_id = :assessment_id ORDER BY created_at DESC');
        $payStmt->execute(['assessment_id' => $assessment['id']]);
        $payments = $payStmt->fetchAll();
        
        // Fetch Enrolled Subjects for College Students
        $enrolledSubjects = [];
        if ($assessment['academic_level'] === 'College') {
            $subStmt = $pdo->prepare('
                SELECT s.subject_code, s.subject_name, s.units 
                FROM college_enrollments es
                JOIN subjects s ON es.subject_id = s.id
                WHERE es.application_id = :app_id
            ');
            $subStmt->execute(['app_id' => $assessment['application_id']]);
            $enrolledSubjects = $subStmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    error_log('Applicant assessment fetch failed: ' . $e->getMessage());
}

$pageTitle = 'Financial Assessment - Applicant Portal';
require_once __DIR__ . '/../components/header.php';
?>

<?php require_once __DIR__ . '/components/navbar.php'; ?>

<main class="py-5 bg-light min-vh-100">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-10">
        
        <div class="island island-hero mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
          <div>
            <h1 class="h3 fw-bold text-dark mb-1">Financial Assessment & Payments</h1>
            <p class="text-muted mb-0">Review your fee breakdown and track your payment history.</p>
          </div>
        </div>

        <?php if (!$assessment): ?>
          <?php
             // Fetch application status if assessment doesn't exist
             $appStmt = $pdo->prepare('SELECT status FROM applications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1');
             $appStmt->execute(['user_id' => $userId]);
             $appStatus = $appStmt->fetchColumn();
          ?>
          <div class="island text-center py-5">
            <div class="status-empty-icon mx-auto mb-3">
              <i class="bi bi-receipt text-muted" style="font-size: 3rem;"></i>
            </div>
            <h2 class="h4 mb-2 text-dark fw-bold">No Assessment Available</h2>
            <?php if ($appStatus === 'approved'): ?>
                <p class="text-muted mb-0">Your application has been approved. Your financial assessment is currently being prepared by the admission office and will be available shortly.</p>
            <?php elseif ($appStatus === 'rejected'): ?>
                <p class="text-muted mb-0">Your application was not approved. No financial assessment will be generated.</p>
            <?php else: ?>
                <p class="text-muted mb-0">Your financial assessment will be generated once your application is approved by the admission office.</p>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <?php
            $balance = (float)$assessment['net_amount'] - (float)$assessment['total_paid'];
            if ($balance < 0) $balance = 0;
            $statusBadge = match($assessment['payment_status']) {
                'paid' => 'bg-success',
                'partial' => 'bg-warning text-dark',
                default => 'bg-danger'
            };
            $statusLabel = match($assessment['payment_status']) {
                'paid' => 'Fully Paid',
                'partial' => 'Partially Paid',
                default => 'Unpaid'
            };
          ?>
          <div class="row g-4">
            <!-- Left Column: Breakdown -->
            <div class="col-lg-7">
              <?php if ($assessment['academic_level'] === 'College' && !empty($enrolledSubjects)): ?>
              <div class="island mb-4">
                <div class="island-header">
                  <i class="bi bi-journal-text"></i>
                  <h2>Curriculum Enrolled</h2>
                </div>
                <div class="island-body p-0">
                  <div class="table-responsive">
                    <table class="table table-hover mb-0">
                      <thead class="table-light text-muted small text-uppercase">
                        <tr>
                          <th class="ps-4">Subject Code</th>
                          <th>Subject Name</th>
                          <th class="text-end pe-4">Units</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php 
                        $totalUnits = 0;
                        foreach ($enrolledSubjects as $sub): 
                          $totalUnits += (int)$sub['units'];
                        ?>
                          <tr>
                            <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($sub['subject_code'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($sub['subject_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end pe-4"><?= (int)$sub['units'] ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                      <tfoot class="table-light">
                        <tr>
                          <td colspan="2" class="text-end fw-bold text-dark">Total Units:</td>
                          <td class="text-end pe-4 fw-bold text-dark fs-5"><?= $totalUnits ?></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <div class="island minimal-card mb-4">
                <div class="island-header bg-transparent border-bottom px-4 pt-4 pb-3">
                  <h2 class="mb-0 fs-5 fw-bold text-dark"><i class="bi bi-receipt me-2 text-primary"></i>Fee Breakdown</h2>
                </div>
                <div class="island-body p-0">
                  <ul class="list-group list-group-flush border-0">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-bottom-dashed">
                      <div>
                        <span class="text-muted fw-medium">Tuition Fee</span>
                        <?php if ($assessment['academic_level'] === 'College' && isset($totalUnits) && $totalUnits > 0): ?>
                          <?php $inferredCost = (float)$assessment['tuition_fee'] / $totalUnits; ?>
                          <small class="text-secondary d-block mt-1"><?= $totalUnits ?> units @ ₱<?= number_format($inferredCost, 2) ?>/unit</small>
                        <?php endif; ?>
                      </div>
                      <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['tuition_fee'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-bottom-dashed">
                      <span class="text-muted fw-medium">Miscellaneous Fee</span>
                      <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['miscellaneous_fee'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-bottom-dashed">
                      <span class="text-muted fw-medium">Registration Fee</span>
                      <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['registration_fee'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-bottom-dashed">
                      <span class="text-muted fw-medium">Laboratory Fee</span>
                      <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['laboratory_fee'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-bottom">
                      <span class="text-muted fw-medium">Other Fees</span>
                      <span class="fw-semibold text-dark">₱<?= number_format((float)$assessment['other_fees'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 bg-light border-bottom">
                      <span class="fw-bold text-dark text-uppercase small tracking-wide">Gross Amount</span>
                      <span class="fw-bold text-dark">₱<?= number_format((float)$assessment['total_amount'], 2) ?></span>
                    </li>
                    <?php if ((float)$assessment['discount_amount'] > 0): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 text-success border-bottom">
                      <span class="fw-medium"><i class="bi bi-tag-fill me-2"></i><?= htmlspecialchars($assessment['scholarship_name'] ?? 'Scholarship', ENT_QUOTES, 'UTF-8') ?></span>
                      <span class="fw-bold">- ₱<?= number_format((float)$assessment['discount_amount'], 2) ?></span>
                    </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-4 px-4 bg-primary text-white border-0 rounded-bottom">
                      <span class="fw-bold fs-5 text-uppercase tracking-wide">Net Payable</span>
                      <span class="fw-bold fs-4">₱<?= number_format((float)$assessment['net_amount'], 2) ?></span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Right Column: Summary & Payments -->
            <div class="col-lg-5">
              
              <?php
                $netPayable = (float)$assessment['net_amount'];
                $totalPaid = (float)$assessment['total_paid'];
                $paidPercent = $netPayable > 0 ? min(100, round(($totalPaid / $netPayable) * 100)) : 0;
              ?>
              <div class="island minimal-card mb-4 border-0">
                <div class="island-body p-4 p-md-5">
                  <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="text-muted small text-uppercase fw-bold tracking-wide">Financial Status</span>
                    <span class="badge <?= $statusBadge ?> px-3 py-1.5 rounded-pill fs-7 fw-semibold tracking-wide text-uppercase shadow-sm"><?= $statusLabel ?></span>
                  </div>
                  
                  <div class="text-center my-4">
                    <p class="text-muted small mb-1 text-uppercase fw-bold tracking-wide">Remaining Balance</p>
                    <h2 class="display-5 fw-bolder text-dark mb-1" style="letter-spacing: -1.5px;">₱<?= number_format($balance, 2) ?></h2>
                  </div>

                  <!-- Progress Bar -->
                  <div class="mb-4">
                    <div class="d-flex justify-content-between text-muted small fw-semibold mb-1">
                      <span>Payment Progress</span>
                      <span><?= $paidPercent ?>% Paid</span>
                    </div>
                    <div class="progress rounded-pill" style="height: 8px; background-color: #e9ecef;">
                      <div class="progress-bar bg-success rounded-pill" role="progressbar" style="width: <?= $paidPercent ?>%;" aria-valuenow="<?= $paidPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                  </div>

                  <div class="row g-3 text-start border-top pt-4">
                    <div class="col-6">
                      <p class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;"><i class="bi bi-wallet2 text-primary me-1"></i> Total Assessed</p>
                      <p class="fw-bold text-dark mb-0 fs-5">₱<?= number_format($netPayable, 2) ?></p>
                    </div>
                    <div class="col-6 border-start ps-3 border-secondary border-opacity-10">
                      <p class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;"><i class="bi bi-check-circle-fill text-success me-1"></i> Total Paid</p>
                      <p class="fw-bold text-success mb-0 fs-5">₱<?= number_format($totalPaid, 2) ?></p>
                    </div>
                  </div>

                  <!-- Quick Action Note -->
                  <div class="mt-4 p-3 bg-light rounded-3 text-center">
                    <?php if ($balance > 0): ?>
                      <p class="text-muted small mb-0 fw-medium">
                        <i class="bi bi-info-circle-fill text-primary me-1"></i> Settle your outstanding balance at the campus cashier to complete your enrollment.
                      </p>
                    <?php else: ?>
                      <p class="text-success small mb-0 fw-bold">
                        <i class="bi bi-patch-check-fill me-1"></i> Your account is fully settled. You are now officially enrolled!
                      </p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Payment History -->
              <div class="island minimal-card">
                <div class="island-header bg-transparent border-bottom px-4 pt-4 pb-3">
                  <h2 class="mb-0 fs-5 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Payment History</h2>
                </div>
                <div class="island-body p-0">
                  <div class="list-group list-group-flush border-0">
                    <?php if (empty($payments)): ?>
                      <div class="text-center py-5">
                        <i class="bi bi-wallet2 text-muted opacity-50 mb-3 d-block" style="font-size: 2.5rem;"></i>
                        <p class="text-muted mb-0 small fw-medium">No payments recorded yet.</p>
                      </div>
                    <?php else: ?>
                      <?php foreach ($payments as $payment): ?>
                        <div class="list-group-item py-4 px-4 border-bottom-dashed">
                          <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark fs-5">₱<?= number_format((float)$payment['amount'], 2) ?></span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 fw-medium"><i class="bi bi-check-circle-fill me-1"></i> Verified</span>
                          </div>
                          <div class="d-flex justify-content-between align-items-center text-muted small mt-2">
                            <span class="fw-medium"><i class="bi bi-calendar-event me-1"></i><?= date('M d, Y', strtotime($payment['payment_date'])) ?> &bull; <?= htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="font-monospace opacity-75">Ref: <?= htmlspecialchars($payment['receipt_number'], ENT_QUOTES, 'UTF-8') ?></span>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</main>

<style>
/* Minimalist Premium Enhancements */
.minimal-card {
    background-color: #ffffff;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.minimal-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    transform: translateY(-2px);
}
.tracking-wide {
    letter-spacing: 0.06em;
}
.border-bottom-dashed {
    border-bottom: 1px dashed #e9ecef;
}
.island-header {
    border-bottom: none;
}
</style>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
