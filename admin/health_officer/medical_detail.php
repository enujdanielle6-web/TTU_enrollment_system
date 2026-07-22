<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('medical.review');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: medical_clearance.php');
    exit;
}

try {
    $stmt = $pdo->prepare('
        SELECT h.*, 
               u.first_name, u.last_name, u.email, 
               a.reference_number, a.academic_level, a.strand, a.school_year, a.contact_number
        FROM health_records h
        INNER JOIN users u ON h.user_id = u.id
        INNER JOIN applications a ON h.application_id = a.id
        WHERE h.id = :id
    ');
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch();

    if (!$record) {
        $_SESSION['error_msg'] = 'Health record not found.';
        header('Location: medical_clearance.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Medical detail fetch failed: ' . $e->getMessage());
    $_SESSION['error_msg'] = 'A database error occurred.';
    header('Location: medical_clearance.php');
    exit;
}

$pageTitle = 'Medical Clearance Detail - Administrator';
$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="mb-4 d-flex align-items-center justify-content-between">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Medical Record: <?= htmlspecialchars($record['last_name'] . ', ' . $record['first_name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0">Ref No: <?= htmlspecialchars($record['reference_number'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <a href="medical_clearance.php" class="btn btn-outline-secondary fw-medium shadow-sm rounded-pill px-4">
        <i class="bi bi-arrow-left me-1"></i> Back to Queue
      </a>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form action="medical_process.php" method="POST" class="needs-validation" novalidate>
      <?= getCsrfInput() ?>
      <input type="hidden" name="record_id" value="<?= $record['id'] ?>">
      <input type="hidden" name="user_id" value="<?= $record['user_id'] ?>">
      
      <div class="row g-4">
          <!-- Main Details -->
          <div class="col-lg-8">
              <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
        <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
                  <div class="island-header border-bottom border-light">
                      <i class="bi bi-person-lines-fill text-primary"></i>
                      <h2 class="mb-0 text-dark">Physical Information</h2>
                  </div>
                  <div class="island-body">
                      <div class="row g-3">
                          <div class="col-md-4">
                              <label class="form-label small fw-bold text-uppercase text-muted">Height</label>
                              <div class="input-group">
                                <input type="number" name="height" class="form-control" step="any" min="0" value="<?= htmlspecialchars(preg_replace('/[^0-9.]/', '', $record['height'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <span class="input-group-text bg-light text-muted">cm</span>
                              </div>
                          </div>
                          <div class="col-md-4">
                              <label class="form-label small fw-bold text-uppercase text-muted">Weight</label>
                              <div class="input-group">
                                <input type="number" name="weight" class="form-control" step="any" min="0" value="<?= htmlspecialchars(preg_replace('/[^0-9.]/', '', $record['weight'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <span class="input-group-text bg-light text-muted">kg</span>
                              </div>
                          </div>
                          <div class="col-md-4">
                              <label class="form-label small fw-bold text-uppercase text-muted">Blood Type</label>
                              <select name="blood_type" class="form-select">
                                  <option value="" disabled <?= empty($record['blood_type']) ? 'selected' : '' ?>>Select</option>
                                  <?php foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'] as $type): ?>
                                      <option value="<?= $type ?>" <?= ($record['blood_type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option>
                                  <?php endforeach; ?>
                              </select>
                          </div>
                      </div>
                  </div>
              </div>

              <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
        <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
                  <div class="island-header border-bottom border-light">
                      <i class="bi bi-clipboard2-pulse text-primary"></i>
                      <h2 class="mb-0 text-dark">Medical History</h2>
                  </div>
                  <div class="island-body">
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
                                  'has_hospitalized' => 'Hospitalized Within Last Year'
                              ];
                              foreach($historyFields as $field => $label): 
                                  $isChecked = !empty($record[$field]);
                          ?>
                          <div class="col-md-6">
                              <div class="form-check custom-checkbox py-2 border-bottom">
                                  <input class="form-check-input" type="checkbox" name="<?= $field ?>" id="<?= $field ?>" value="1" <?= $isChecked ? 'checked' : '' ?>>
                                  <label class="form-check-label fw-bold text-dark" for="<?= $field ?>">
                                      <?= $label ?>
                                  </label>
                              </div>
                          </div>
                          <?php endforeach; ?>
                      </div>
                  </div>
              </div>

              <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
        <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
                  <div class="island-header border-bottom border-light">
                      <i class="bi bi-journal-medical text-primary"></i>
                      <h2 class="mb-0 text-dark">Additional Details</h2>
                  </div>
                  <div class="island-body">
                      <div class="mb-3">
                          <label class="form-label small fw-bold text-muted text-uppercase">Medical Conditions</label>
                          <textarea name="medical_conditions" class="form-control" rows="2"><?= htmlspecialchars($record['medical_conditions'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                      </div>
                      <div class="mb-3">
                          <label class="form-label small fw-bold text-muted text-uppercase">Allergies</label>
                          <textarea name="allergies_details" class="form-control" rows="2"><?= htmlspecialchars($record['allergies_details'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                      </div>
                      <div class="mb-3">
                          <label class="form-label small fw-bold text-muted text-uppercase">Current Medications</label>
                          <textarea name="current_medications" class="form-control" rows="2"><?= htmlspecialchars($record['current_medications'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                      </div>
                      <div>
                          <label class="form-label small fw-bold text-muted text-uppercase">Other Notes</label>
                          <textarea name="other_notes" class="form-control" rows="2"><?= htmlspecialchars($record['other_notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                      </div>
                  </div>
              </div>
              
              <div class="island position-relative overflow-hidden border-0 shadow-sm mb-4 rounded-4">
        <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
                  <div class="island-header border-bottom border-light">
                      <i class="bi bi-telephone-fill text-primary"></i>
                      <h2 class="mb-0 text-dark">Emergency Contact</h2>
                  </div>
                  <div class="island-body">
                      <div class="row g-3">
                          <div class="col-md-4">
                              <label class="form-label small fw-bold text-muted text-uppercase">Contact Name</label>
                              <input type="text" name="emergency_name" class="form-control" value="<?= htmlspecialchars($record['emergency_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                          </div>
                          <div class="col-md-4">
                              <label class="form-label small fw-bold text-muted text-uppercase">Relationship</label>
                              <input type="text" name="emergency_relationship" class="form-control" value="<?= htmlspecialchars($record['emergency_relationship'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                          </div>
                          <div class="col-md-4">
                              <label class="form-label small fw-bold text-muted text-uppercase">Contact Number</label>
                              <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($record['emergency_contact'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                          </div>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Sidebar / Action Panel -->
          <div class="col-lg-4">
              <div class="island sticky-top" style="top: 80px;">
                  <div class="island-header bg-light">
                      <i class="bi bi-shield-check"></i>
                      <h2 class="mb-0">Medical Clearance Action</h2>
                  </div>
                  <div class="island-body bg-light">
                          <div class="mb-3">
                              <label class="form-label small fw-bold text-muted text-uppercase">Current Status</label>
                              <?php 
                                  $badgeClass = match($record['status']) {
                                      'verified' => 'bg-success',
                                      'rejected' => 'bg-danger',
                                      'correction_required' => 'bg-warning text-dark',
                                      'under_review' => 'bg-info text-dark',
                                      default => 'bg-secondary'
                                  };
                              ?>
                              <div class="fs-5 mt-1">
                                  <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-2 w-100"><?= formatApplicationStatus($record['status']) ?></span>
                              </div>
                          </div>
                          
                          <div class="mb-3">
                              <label class="form-label fw-semibold text-dark">Update Status</label>
                              <select name="status" class="form-select" required>
                                  <option value="pending" <?= $record['status'] === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                                  <option value="under_review" <?= $record['status'] === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                                  <option value="verified" <?= $record['status'] === 'verified' ? 'selected' : '' ?>>Verified (Cleared)</option>
                                  <option value="correction_required" <?= $record['status'] === 'correction_required' ? 'selected' : '' ?>>Correction Required</option>
                                  <option value="rejected" <?= $record['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                              </select>
                          </div>
                          
                          <div class="mb-4">
                              <label class="form-label fw-semibold text-dark">Clinic / Admin Remarks</label>
                              <textarea name="admin_remarks" class="form-control" rows="4" placeholder="Enter instructions or reasons if correction is required..."><?= htmlspecialchars($record['admin_remarks'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                              <div class="form-text small">These remarks will be visible to the applicant on their medical clearance card.</div>
                          </div>

                          <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm">
                              Save Medical Clearance <i class="bi bi-save ms-1"></i>
                          </button>
                  </div>
              </div>
          </div>
      </div>
    </form>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
