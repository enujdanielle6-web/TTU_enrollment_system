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
      
      <!-- Health Information Form -->
      <form action="health_process.php" method="POST" class="needs-validation" novalidate>
        <?= getCsrfInput() ?>
        
        <?php if ($health && $health['status'] === 'correction_required'): ?>
            <div class="alert alert-warning shadow-sm rounded-12 mb-4">
                <h5 class="fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Correction Required</h5>
                <p class="mb-2">The clinic has requested updates to your submitted health information. Please review their remarks and update your form below.</p>
                <?php if (!empty($health['admin_remarks'])): ?>
                    <hr>
                    <strong>Remarks:</strong> <?= nl2br(htmlspecialchars($health['admin_remarks'], ENT_QUOTES, 'UTF-8')) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="island mb-4">
            <div class="island-header">
                <i class="bi bi-person-lines-fill text-primary"></i>
                <h2 class="mb-0 text-dark">Physical Information</h2>
            </div>
            <div class="island-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Height</label>
                        <div class="input-group has-validation">
                            <input type="number" name="height" class="form-control bg-light border-end-0" required step="any" min="0" placeholder="0" value="<?= htmlspecialchars(preg_replace('/[^0-9.]/', '', $health['height'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" onkeydown="if(event.key === '-' || event.key === 'e') event.preventDefault();" oninput="if(this.value < 0) this.value = Math.abs(this.value);">
                            <span class="input-group-text bg-light text-muted border-start-0">cm</span>
                            <div class="invalid-feedback">Height is required and must be positive.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Weight</label>
                        <div class="input-group has-validation">
                            <input type="number" name="weight" class="form-control bg-light border-end-0" required step="any" min="0" placeholder="0" value="<?= htmlspecialchars(preg_replace('/[^0-9.]/', '', $health['weight'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" onkeydown="if(event.key === '-' || event.key === 'e') event.preventDefault();" oninput="if(this.value < 0) this.value = Math.abs(this.value);">
                            <span class="input-group-text bg-light text-muted border-start-0">kg</span>
                            <div class="invalid-feedback">Weight is required and must be positive.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Blood Type</label>
                        <select name="blood_type" class="form-select bg-light" required>
                            <option value="" disabled <?= empty($health['blood_type']) ? 'selected' : '' ?>>Select Blood Type</option>
                            <?php foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'] as $type): ?>
                                <option value="<?= $type ?>" <?= ($health['blood_type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="island mb-4">
            <div class="island-header">
                <i class="bi bi-clipboard2-pulse text-primary"></i>
                <h2 class="mb-0 text-dark">Medical History</h2>
            </div>
            <div class="island-body">
                <p class="text-muted small mb-3">Please check all that apply to you.</p>
                <div class="row g-3">
                    <?php 
                        $historyFields = [
                            'has_allergies' => 'Allergies',
                            'has_asthma' => 'Asthma',
                            'has_diabetes' => 'Diabetes',
                            'has_hypertension' => 'Hypertension',
                            'has_heart_disease' => 'Heart Disease',
                            'has_physical_disability' => 'Physical Disability',
                            'has_existing_condition' => 'Existing Medical Condition',
                            'has_previous_surgery' => 'Previous Surgery',
                            'has_maintenance_medication' => 'Maintenance Medication',
                            'has_hospitalized' => 'Hospitalized Within the Last Year'
                        ];
                        foreach($historyFields as $field => $label): 
                            $isChecked = !empty($health[$field]);
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-check custom-checkbox">
                            <input class="form-check-input" type="checkbox" name="<?= $field ?>" id="<?= $field ?>" value="1" <?= $isChecked ? 'checked' : '' ?>>
                            <label class="form-check-label text-dark fw-medium" for="<?= $field ?>">
                                <?= $label ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="island mb-4">
            <div class="island-header">
                <i class="bi bi-journal-medical text-primary"></i>
                <h2 class="mb-0 text-dark">Additional Information</h2>
            </div>
            <div class="island-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Medical Conditions</label>
                        <textarea name="medical_conditions" class="form-control bg-light" rows="3" placeholder="Describe any existing medical conditions..."><?= htmlspecialchars($health['medical_conditions'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Allergies</label>
                        <textarea name="allergies_details" class="form-control bg-light" rows="3" placeholder="List any food, drug, or environmental allergies..."><?= htmlspecialchars($health['allergies_details'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Current Medications</label>
                        <textarea name="current_medications" class="form-control bg-light" rows="3" placeholder="List any maintenance or current medications..."><?= htmlspecialchars($health['current_medications'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Other Medical Notes</label>
                        <textarea name="other_notes" class="form-control bg-light" rows="3" placeholder="Any other health-related notes..."><?= htmlspecialchars($health['other_notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="island mb-4">
            <div class="island-header">
                <i class="bi bi-telephone-fill text-primary"></i>
                <h2 class="mb-0 text-dark">Emergency Contact</h2>
            </div>
            <div class="island-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Contact Name</label>
                        <input type="text" name="emergency_name" class="form-control bg-light" required value="<?= htmlspecialchars($health['emergency_name'] ?? $application['emergency_contact_person'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Relationship</label>
                        <input type="text" name="emergency_relationship" class="form-control bg-light" required value="<?= htmlspecialchars($health['emergency_relationship'] ?? $application['emergency_contact_relationship'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Contact Number</label>
                        <input type="text" name="emergency_contact" class="form-control bg-light" required value="<?= htmlspecialchars($health['emergency_contact'] ?? $application['emergency_contact_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-5">
            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow">
                Submit Health Information <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
      </form>

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
