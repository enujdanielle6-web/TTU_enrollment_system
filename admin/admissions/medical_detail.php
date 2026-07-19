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
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Height</span>
                            <div class="fw-medium text-dark fs-5"><?= htmlspecialchars($record['height'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Weight</span>
                            <div class="fw-medium text-dark fs-5"><?= htmlspecialchars($record['weight'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Blood Type</span>
                            <div class="fw-medium text-danger fs-5"><?= htmlspecialchars($record['blood_type'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
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
                            <div class="d-flex align-items-center border-bottom pb-2 pt-2">
                                <?php if ($isChecked): ?>
                                    <i class="bi bi-check-circle-fill text-danger me-3 fs-5"></i>
                                    <span class="fw-bold text-dark"><?= $label ?></span>
                                <?php else: ?>
                                    <i class="bi bi-x-circle text-muted me-3 fs-5"></i>
                                    <span class="text-muted"><?= $label ?></span>
                                <?php endif; ?>
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
                    <div class="mb-3 border-bottom pb-3">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Medical Conditions</span>
                        <div class="text-dark"><?= nl2br(htmlspecialchars($record['medical_conditions'] ?: 'None reported', ENT_QUOTES, 'UTF-8')) ?></div>
                    </div>
                    <div class="mb-3 border-bottom pb-3">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Allergies</span>
                        <div class="text-dark"><?= nl2br(htmlspecialchars($record['allergies_details'] ?: 'None reported', ENT_QUOTES, 'UTF-8')) ?></div>
                    </div>
                    <div class="mb-3 border-bottom pb-3">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Current Medications</span>
                        <div class="text-dark"><?= nl2br(htmlspecialchars($record['current_medications'] ?: 'None reported', ENT_QUOTES, 'UTF-8')) ?></div>
                    </div>
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Other Notes</span>
                        <div class="text-dark"><?= nl2br(htmlspecialchars($record['other_notes'] ?: 'None reported', ENT_QUOTES, 'UTF-8')) ?></div>
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
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Contact Name</span>
                            <div class="fw-medium text-dark"><?= htmlspecialchars($record['emergency_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Relationship</span>
                            <div class="fw-medium text-dark"><?= htmlspecialchars($record['emergency_relationship'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Contact Number</span>
                            <div class="fw-medium text-dark"><?= htmlspecialchars($record['emergency_contact'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
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
                    <form action="medical_process.php" method="POST">
                        <?= getCsrfInput() ?>
                        <input type="hidden" name="record_id" value="<?= $record['id'] ?>">
                        <input type="hidden" name="user_id" value="<?= $record['user_id'] ?>">
                        
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
                    </form>
                </div>
            </div>
        </div>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

