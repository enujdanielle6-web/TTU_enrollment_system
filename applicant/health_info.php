<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

$userId = (int) $_SESSION['user_id'];
$pageTitle = 'Health Information - Applicant Portal';

// Fetch application
$appStmt = $pdo->prepare('SELECT id, status, emergency_contact_person, emergency_contact_relationship, emergency_contact_number FROM applications WHERE user_id = :user_id LIMIT 1');
$appStmt->execute(['user_id' => $userId]);
$application = $appStmt->fetch();

if (!$application || !in_array($application['status'], ['approved', 'enrolled'])) {
    header('Location: dashboard.php');
    exit;
}
$appId = (int) $application['id'];

// Fetch health record
$healthStmt = $pdo->prepare('SELECT * FROM health_records WHERE user_id = :user_id LIMIT 1');
$healthStmt->execute(['user_id' => $userId]);
$health = $healthStmt->fetch();

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/components/navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container px-lg-5">
    
    <div class="island island-hero mb-4">
      <h1 class="h3 fw-bold text-dark mb-1">Health & Medical Clearance</h1>
      <p class="text-muted mb-0">Submit your health information to proceed with your enrollment.</p>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($health && in_array($health['status'], ['pending', 'under_review', 'verified', 'rejected'])): ?>
      
      <!-- Medical Clearance Card -->
      <div class="island text-center py-5">
        <?php if ($health['status'] === 'pending'): ?>
            <i class="bi bi-file-medical-fill text-warning" style="font-size: 4rem;"></i>
            <h3 class="mt-3 fw-bold">Medical Clearance Required</h3>
            <p class="text-muted mx-auto" style="max-width: 600px;">
                Please visit the TTU Clinic to complete your required medical examination.
                <br>Bring any documents required by the university.
                <br>Your enrollment cannot continue until your Medical Clearance has been verified.
            </p>
            <span class="badge bg-warning text-dark fs-6 rounded-pill px-4 py-2 mt-2">Status: Pending Clinic Visit</span>
            
        <?php elseif ($health['status'] === 'under_review'): ?>
            <i class="bi bi-search text-info" style="font-size: 4rem;"></i>
            <h3 class="mt-3 fw-bold">Under Review</h3>
            <p class="text-muted mx-auto" style="max-width: 600px;">Your medical information is currently being reviewed by the clinic staff.</p>
            <span class="badge bg-info fs-6 rounded-pill px-4 py-2 mt-2">Status: Under Review</span>
            
        <?php elseif ($health['status'] === 'verified'): ?>
            <i class="bi bi-heart-pulse-fill text-success" style="font-size: 4rem;"></i>
            <h3 class="mt-3 fw-bold">Medical Clearance Verified</h3>
            <p class="text-muted mx-auto" style="max-width: 600px;">You are medically cleared! You may now proceed to the next stages of your enrollment.</p>
            <span class="badge bg-success fs-6 rounded-pill px-4 py-2 mt-2">Status: Verified</span>
            <div class="mt-4">
                <a href="scholarships.php" class="btn btn-primary rounded-pill px-4 fw-medium">Continue to Scholarships</a>
            </div>

        <?php elseif ($health['status'] === 'rejected'): ?>
            <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
            <h3 class="mt-3 fw-bold">Medical Clearance Rejected</h3>
            <p class="text-muted mx-auto" style="max-width: 600px;">Your medical clearance was rejected. Please contact the Admissions office for details.</p>
            <span class="badge bg-danger fs-6 rounded-pill px-4 py-2 mt-2">Status: Rejected</span>
        <?php endif; ?>

        <?php if (!empty($health['admin_remarks'])): ?>
            <div class="alert alert-info mt-4 mx-auto text-start" style="max-width: 600px;">
                <strong><i class="bi bi-chat-left-text me-2"></i>Clinic Remarks:</strong>
                <p class="mb-0 mt-1 small"><?= nl2br(htmlspecialchars($health['admin_remarks'], ENT_QUOTES, 'UTF-8')) ?></p>
            </div>
        <?php endif; ?>
      </div>

    <?php else: ?>
      
      <div class="island text-center py-5">
        <i class="bi bi-clock-history text-secondary" style="font-size: 4rem;"></i>
        <h3 class="mt-3 fw-bold">Not Yet Available</h3>
        <p class="text-muted mx-auto" style="max-width: 600px;">
            Your medical clearance step will become available after your enrollment application has been reviewed and approved by the Admissions Office.
        </p>
      </div>

    <?php endif; ?>

  </div>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
