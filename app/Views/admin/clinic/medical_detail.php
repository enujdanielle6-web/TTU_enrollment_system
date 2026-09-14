<?php
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';

$initials = strtoupper(substr($record['first_name'] ?? 'A', 0, 1) . substr($record['last_name'] ?? 'P', 0, 1));
?>

<main class="py-4 py-lg-5 bg-light min-vh-100">
  <div class="container-fluid px-3 px-lg-5">
    
    <!-- Hero Header -->
    <div class="clinic-hero-card p-4 p-md-5 mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <i class="bi bi-person-vcard-fill clinic-hero-watermark"></i>
      <div class="row align-items-center position-relative" style="z-index: 1;">
        <div class="col-lg-8 mb-3 mb-lg-0">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar-initials shadow-sm" style="width: 54px; height: 54px; font-size: 1.25rem;">
              <?= $initials ?>
            </div>
            <div>
              <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge badge-subtle badge-emerald-subtle">Medical Clearance Record</span>
                <span class="ref-tag">
                  <?= htmlspecialchars($record['reference_number'], ENT_QUOTES, 'UTF-8') ?>
                </span>
              </div>
              <h1 class="h2 fw-bold text-dark mb-0 tracking-tight">
                <?= htmlspecialchars($record['last_name'] . ', ' . $record['first_name'], ENT_QUOTES, 'UTF-8') ?>
              </h1>
              <span class="text-muted extra-small">
                Program: <?= htmlspecialchars($record['strand'] ?: ($record['academic_level'] ?? 'Standard'), ENT_QUOTES, 'UTF-8') ?> • 
                School Year: <?= htmlspecialchars($record['school_year'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
              </span>
            </div>
          </div>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="medical_clearance.php" class="btn btn-outline-secondary rounded-pill px-4 py-2 shadow-sm hover-lift fw-medium d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Queue</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($successMsg)): ?>
      <div class="alert alert-success d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
        <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
        <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      
      <!-- Main Details Column -->
      <div class="col-lg-8">
        
        <!-- Physical Metrics Card -->
        <div class="island shadow-sm border-0 rounded-4 p-4 mb-4 fade-in-up" style="animation-delay: 0.1s;">
          <div class="d-flex align-items-center gap-2 border-bottom pb-3 mb-3">
            <div class="icon-box-lg icon-box-emerald" style="width: 42px; height: 42px; font-size: 1.2rem;">
              <i class="bi bi-person-lines-fill"></i>
            </div>
            <div>
              <h2 class="h6 fw-bold text-dark mb-0">Physical Measurements & Vital Information</h2>
              <span class="text-muted extra-small">Applicant self-reported biometric values</span>
            </div>
          </div>
          
          <div class="row g-3 text-center">
            <div class="col-sm-4">
              <div class="p-3 bg-light rounded-3 border border-light">
                <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Height</span>
                <div class="fw-bold text-dark fs-5"><?= htmlspecialchars($record['height'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="p-3 bg-light rounded-3 border border-light">
                <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Weight</span>
                <div class="fw-bold text-dark fs-5"><?= htmlspecialchars($record['weight'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="p-3 bg-light rounded-3 border border-light">
                <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Blood Type</span>
                <div class="fw-bold text-danger fs-5">
                  <i class="bi bi-droplet-fill me-1"></i><?= htmlspecialchars($record['blood_type'] ?: 'Unspecified', ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Medical History Checklist Card -->
        <div class="island shadow-sm border-0 rounded-4 p-4 mb-4 fade-in-up" style="animation-delay: 0.2s;">
          <div class="d-flex align-items-center gap-2 border-bottom pb-3 mb-3">
            <div class="icon-box-lg icon-box-cyan" style="width: 42px; height: 42px; font-size: 1.2rem;">
              <i class="bi bi-clipboard2-pulse"></i>
            </div>
            <div>
              <h2 class="h6 fw-bold text-dark mb-0">Medical History Declarations</h2>
              <span class="text-muted extra-small">Specific condition indicators checked during registration</span>
            </div>
          </div>

          <div class="row g-3">
            <?php 
              $historyFields = [
                  'has_allergies' => 'Allergies',
                  'has_asthma' => 'Asthma / Respiratory',
                  'has_diabetes' => 'Diabetes',
                  'has_hypertension' => 'Hypertension',
                  'has_heart_disease' => 'Cardiovascular Disease',
                  'has_physical_disability' => 'Physical Disability',
                  'has_existing_condition' => 'Pre-Existing Medical Condition',
                  'has_previous_surgery' => 'Previous Major Surgery',
                  'has_maintenance_medication' => 'Maintenance Medication',
                  'has_hospitalized' => 'Hospitalized in Past 12 Months'
              ];
              foreach ($historyFields as $field => $label): 
                $isChecked = !empty($record[$field]);
            ?>
              <div class="col-md-6">
                <div class="p-2 px-3 rounded-3 border d-flex align-items-center justify-content-between <?= $isChecked ? 'bg-danger bg-opacity-10 border-danger-subtle' : 'bg-light border-light' ?>">
                  <div class="d-flex align-items-center gap-2">
                    <?php if ($isChecked): ?>
                      <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                      <span class="fw-bold text-dark small"><?= esc($label) ?></span>
                    <?php else: ?>
                      <i class="bi bi-check2 text-muted fs-5"></i>
                      <span class="text-muted small"><?= esc($label) ?></span>
                    <?php endif; ?>
                  </div>
                  <?php if ($isChecked): ?>
                    <span class="badge bg-danger rounded-pill extra-small">Declared</span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-muted rounded-pill extra-small">None</span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Additional Medical Details Card -->
        <div class="island shadow-sm border-0 rounded-4 p-4 mb-4 fade-in-up" style="animation-delay: 0.3s;">
          <div class="d-flex align-items-center gap-2 border-bottom pb-3 mb-3">
            <div class="icon-box-lg icon-box-indigo" style="width: 42px; height: 42px; font-size: 1.2rem;">
              <i class="bi bi-journal-medical"></i>
            </div>
            <div>
              <h2 class="h6 fw-bold text-dark mb-0">Detailed Medical Explanations</h2>
              <span class="text-muted extra-small">Applicant written notes and medication specifics</span>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <div class="p-3 bg-light rounded-3 border border-light h-100">
                <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Medical Conditions</span>
                <div class="text-dark small"><?= nl2br(htmlspecialchars($record['medical_conditions'] ?: 'None reported', ENT_QUOTES, 'UTF-8')) ?></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 bg-light rounded-3 border border-light h-100">
                <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Allergies Specifics</span>
                <div class="text-dark small"><?= nl2br(htmlspecialchars($record['allergies_details'] ?: 'None reported', ENT_QUOTES, 'UTF-8')) ?></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 bg-light rounded-3 border border-light h-100">
                <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Current Medications</span>
                <div class="text-dark small"><?= nl2br(htmlspecialchars($record['current_medications'] ?: 'None reported', ENT_QUOTES, 'UTF-8')) ?></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 bg-light rounded-3 border border-light h-100">
                <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Other Clinical Notes</span>
                <div class="text-dark small"><?= nl2br(htmlspecialchars($record['other_notes'] ?: 'None reported', ENT_QUOTES, 'UTF-8')) ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Emergency Contact Card -->
        <div class="island shadow-sm border-0 rounded-4 p-4 mb-4 fade-in-up" style="animation-delay: 0.4s;">
          <div class="d-flex align-items-center gap-2 border-bottom pb-3 mb-3">
            <div class="icon-box-lg icon-box-rose" style="width: 42px; height: 42px; font-size: 1.2rem;">
              <i class="bi bi-telephone-plus"></i>
            </div>
            <div>
              <h2 class="h6 fw-bold text-dark mb-0">Emergency Contact Person</h2>
              <span class="text-muted extra-small">Primary contact in case of campus health incident</span>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <div class="p-3 bg-light rounded-3 border border-light">
                <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Contact Name</span>
                <div class="fw-bold text-dark"><?= htmlspecialchars($record['emergency_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 bg-light rounded-3 border border-light">
                <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Relationship</span>
                <div class="fw-bold text-dark"><?= htmlspecialchars($record['emergency_relationship'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 bg-light rounded-3 border border-light">
                <span class="text-muted extra-small fw-bold text-uppercase d-block mb-1">Contact Phone</span>
                <div class="fw-bold text-primary">
                  <i class="bi bi-telephone-fill me-1"></i><?= htmlspecialchars($record['emergency_contact'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Action Panel Column -->
      <div class="col-lg-4">
        <div class="island shadow-sm border-0 rounded-4 p-4 sticky-top fade-in-up" style="top: 90px; animation-delay: 0.5s;">
          <div class="d-flex align-items-center gap-2 border-bottom pb-3 mb-3">
            <div class="icon-box-lg icon-box-emerald" style="width: 40px; height: 40px; font-size: 1.1rem;">
              <i class="bi bi-shield-check"></i>
            </div>
            <h2 class="h6 fw-bold text-dark mb-0">Clearance Certification</h2>
          </div>

          <form action="medical_process.php" method="POST" id="medicalProcessForm">
            <?= getCsrfInput() ?>
            <input type="hidden" name="record_id" value="<?= esc($record['id']) ?>">
            <input type="hidden" name="user_id" value="<?= esc($record['user_id']) ?>">
            
            <div class="mb-3">
              <label class="form-label extra-small fw-bold text-muted text-uppercase">Current Health Status</label>
              <div class="mt-1">
                <?php 
                  echo match($record['status']) {
                      'verified' => '<span class="badge badge-subtle badge-emerald-subtle fs-6 w-100 py-2 justify-content-center"><span class="pulse-dot pulse-dot-emerald me-2"></span> Verified & Cleared</span>',
                      'pending' => '<span class="badge badge-subtle badge-amber-subtle fs-6 w-100 py-2 justify-content-center"><span class="pulse-dot pulse-dot-amber me-2"></span> Pending Review</span>',
                      'correction_required' => '<span class="badge badge-subtle badge-cyan-subtle fs-6 w-100 py-2 justify-content-center"><span class="pulse-dot pulse-dot-cyan me-2"></span> Correction Required</span>',
                      'rejected' => '<span class="badge badge-subtle badge-rose-subtle fs-6 w-100 py-2 justify-content-center"><span class="pulse-dot pulse-dot-rose me-2"></span> Rejected</span>',
                      default => '<span class="badge badge-subtle badge-slate-subtle fs-6 w-100 py-2 justify-content-center">' . ucfirst($record['status']) . '</span>'
                  };
                ?>
              </div>
            </div>

            <?php if ($record['status'] === 'verified'): ?>
              <div class="alert alert-success border-0 bg-success bg-opacity-10 rounded-4 my-3 p-3">
                <div class="d-flex align-items-start gap-2">
                  <i class="bi bi-check-circle-fill text-success fs-5 mt-1"></i>
                  <div>
                    <div class="fw-bold text-success small">Clearance Certified</div>
                    <div class="text-success extra-small opacity-75">This applicant has satisfied institutional medical prerequisites and is eligible for Admissions file approval.</div>
                  </div>
                </div>
              </div>

              <?php if (!empty($record['admin_remarks'])): ?>
                <div class="mb-3">
                  <label class="form-label extra-small fw-bold text-muted text-uppercase">Physician Remarks</label>
                  <div class="p-3 bg-light rounded-3 border border-light small text-dark">
                    <?= nl2br(htmlspecialchars($record['admin_remarks'], ENT_QUOTES, 'UTF-8')) ?>
                  </div>
                </div>
              <?php endif; ?>

            <?php else: ?>
              
              <div class="mb-3 mt-3">
                <label class="form-label small fw-bold text-dark">Update Medical Clearance</label>
                <select name="status" class="form-select bg-light border-0 shadow-sm" required>
                  <option value="pending" <?= esc($record['status'] === 'pending' ? 'selected' : '') ?>>Pending Review</option>
                  <option value="verified" <?= esc($record['status'] === 'verified' ? 'selected' : '') ?>>Verified (Clear for Admission)</option>
                  <option value="correction_required" <?= esc($record['status'] === 'correction_required' ? 'selected' : '') ?>>Correction Required (Return to Student)</option>
                  <option value="rejected" <?= esc($record['status'] === 'rejected' ? 'selected' : '') ?>>Rejected (Medical Ineligibility)</option>
                </select>
              </div>
              
              <div class="mb-4">
                <label class="form-label small fw-bold text-dark">Physician / Clinic Remarks</label>
                <textarea name="admin_remarks" class="form-control bg-light border-0 shadow-sm" rows="4" placeholder="Enter clinical notes, recommendations, or items requiring student resubmission..."><?= htmlspecialchars($record['admin_remarks'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="form-text extra-small text-muted">Notes entered here will be rendered on the applicant's clearance status view.</div>
              </div>

              <button type="submit" id="saveClearanceBtn" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm hover-lift d-inline-flex align-items-center justify-content-center gap-2">
                <span>Save Medical Clearance</span>
                <i class="bi bi-check-lg"></i>
              </button>

            <?php endif; ?>

          </form>

        </div>
      </div>

    </div>

  </div>
</main>

<script>
(function() {
  const form = document.getElementById('medicalProcessForm');
  const btn = document.getElementById('saveClearanceBtn');

  if (form && btn) {
    form.addEventListener('submit', function() {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Updating Clearance...';
    });
  }
})();
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
