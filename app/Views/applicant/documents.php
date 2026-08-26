<?php
$pageTitle = 'Document Requirements - Triple T University';
require_once __DIR__ . '/../components/header.php';
?>
<?php require_once __DIR__ . '/../components/applicant_navbar.php'; ?>

<style>
  .doc-card {
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    border-radius: 20px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    border: 1px solid #e9ecef;
    overflow: hidden;
    position: relative;
  }
  .doc-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.08);
    border-color: #dee2e6;
  }
  .doc-card.border-success {
    border: 2px solid #198754 !important;
    background: #f8fff9;
  }
  .doc-card.border-success:hover {
    box-shadow: 0 12px 28px rgba(25,135,84,0.15);
  }
  .doc-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    background: #f8f9fa;
    color: #6c757d;
    flex-shrink: 0;
  }
  .doc-card.border-success .doc-icon {
    background: #d1e7dd;
    color: #198754;
  }
  .custom-file-upload input[type="file"] {
    font-size: 0.85rem;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 0.25rem;
    background: #f8f9fa;
  }
  .custom-file-upload input[type="file"]::file-selector-button {
    border: none;
    background: #e9ecef;
    color: #495057;
    padding: 0.4rem 1rem;
    border-radius: 8px;
    margin-right: 0.75rem;
    transition: background 0.2s ease;
    cursor: pointer;
    font-weight: 500;
  }
  .custom-file-upload input[type="file"]::file-selector-button:hover {
    background: #dde2e6;
  }

  .method-option {
    position: relative;
    flex: 1;
  }
  .method-option input[type="radio"] {
    position: absolute;
    opacity: 0;
  }
  .method-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    border: 2px solid #e9ecef;
    border-radius: 18px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
    margin: 0;
    height: 100%;
    font-weight: 600;
    color: #6c757d;
    background: #ffffff;
  }
  .method-label i { font-size: 1.25rem; }
  .method-label:hover {
    background: #f8f9fa;
    border-color: #dee2e6;
    transform: translateY(-2px);
  }
  .method-option input[type="radio"]:checked + .method-label {
    border-color: var(--color-primary);
    background: rgba(13, 110, 253, 0.04);
    color: var(--color-primary);
    box-shadow: 0 6px 16px rgba(13, 110, 253, 0.12);
  }
  .method-option input[type="radio"]:disabled + .method-label {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }
  
  .badge-status {
    font-size: 0.7rem;
    padding: 0.35em 0.65em;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 700;
  }
  
  .fade-in-up {
    animation: fadeInUp 0.5s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
    opacity: 0;
    transform: translateY(15px);
  }
  @keyframes fadeInUp {
    to { opacity: 1; transform: translateY(0); }
  }

  .cursor-pointer {
    cursor: pointer;
  }
</style>

<main id="spa-main" class="py-5 bg-light min-vh-100">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-9 col-lg-10">
        
        <div class="island island-hero mb-4 d-flex align-items-center justify-content-between fade-in-up">
          <div>
            <h1 class="h3 fw-bold text-dark mb-1">Document Requirements</h1>
            <p class="text-muted mb-0">Manage and submit your required academic documents.</p>
          </div>
        </div>

        <?php if (!empty($successMsg)): ?>
          <div class="alert alert-success shadow-sm rounded-4 border-0 fade-in-up" style="animation-delay: 0.1s;"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMsg)): ?>
          <div class="alert alert-danger shadow-sm rounded-4 border-0 fade-in-up" style="animation-delay: 0.1s;"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($application === null): ?>
          <div class="island text-center py-5 shadow-sm rounded-4 border-0 fade-in-up" style="animation-delay: 0.2s;">
            <i class="bi bi-file-earmark-x text-muted opacity-50 mb-3 d-block" style="font-size: 4rem;"></i>
            <h2 class="h4 mb-2 text-dark fw-bold">No Application Found</h2>
            <p class="text-muted mb-4 mx-auto" style="max-width: 400px;">You must submit an enrollment application before you can upload your supporting documents.</p>
            <a class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm" href="enroll.php"><i class="bi bi-pencil-square me-2"></i> Start Enrollment</a>
          </div>
        <?php else: ?>

          <!-- Submission Workflow Configuration -->
          <div class="island mb-4 shadow-sm rounded-4 border-0 fade-in-up" style="animation-delay: 0.1s;">
            <div class="island-header border-bottom-0 pb-0 fade-in-up" style="animation-delay: 0.1s;">
              <div class="d-flex flex-column flex-sm-row sm-align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-gear-fill text-primary fs-5"></i>
                  <h2 class="mb-0 fs-5 fw-bold text-dark">Submission Method</h2>
                </div>
                <?php if ($isLocked): ?>
                  <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-1.5 fw-semibold align-self-start align-self-sm-auto">
                    <i class="bi bi-lock-fill me-1"></i> Selection Locked
                  </span>
                <?php else: ?>
                  <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 rounded-pill px-3 py-1.5 fw-semibold align-self-start align-self-sm-auto">
                    <i class="bi bi-shield-exclamation me-1"></i> Irreversible once confirmed
                  </span>
                <?php endif; ?>
              </div>
            </div>
            <div class="island-body pt-0 fade-in-up" style="animation-delay: 0.2s;">
              <form id="submissionMethodForm" action="document_workflow.php" method="POST" class="d-flex flex-column gap-3">
                <?= getCsrfInput() ?>
                <input type="hidden" name="action" value="save_preference">
                <p class="mb-1 text-muted small">Choose how you would like to submit your documents:</p>
                
                <div class="d-flex flex-column flex-md-row gap-3 mt-1">
                  <div class="method-option">
                    <input type="radio" name="submission_method" id="methodOnline" value="online" <?= esc($method === 'online' ? 'checked' : '') ?> <?= esc($isLocked ? 'disabled' : '') ?>>
                    <label class="method-label flex-column align-items-start p-4 h-100" for="methodOnline">
                      <div class="d-flex align-items-center gap-2 mb-3 w-100 border-bottom pb-2">
                        <i class="bi bi-cloud-arrow-up fs-4 text-primary"></i> 
                        <span class="fs-5 fw-bold text-dark">Online Upload</span>
                      </div>
                      <div class="small w-100 text-start">
                        <div class="text-success fw-semibold mb-2"><i class="bi bi-plus-circle-fill me-1"></i> Pros:<br><span class="text-muted fw-normal ms-3">Fast, convenient, and your application process starts immediately.</span></div>
                        <div class="text-danger fw-semibold"><i class="bi bi-dash-circle-fill me-1"></i> Cons:<br><span class="text-muted fw-normal ms-3">Requires a scanner or clear camera. Blurry files may be rejected.</span></div>
                      </div>
                    </label>
                  </div>
                  <div class="method-option">
                    <input type="radio" name="submission_method" id="methodOnCampus" value="on_campus" <?= esc($method === 'on_campus' ? 'checked' : '') ?> <?= esc($isLocked ? 'disabled' : '') ?>>
                    <label class="method-label flex-column align-items-start p-4 h-100" for="methodOnCampus">
                      <div class="d-flex align-items-center gap-2 mb-3 w-100 border-bottom pb-2">
                        <i class="bi bi-building fs-4 text-primary"></i> 
                        <span class="fs-5 fw-bold text-dark">On-Campus Submission</span>
                      </div>
                      <div class="small w-100 text-start">
                        <div class="text-success fw-semibold mb-2"><i class="bi bi-plus-circle-fill me-1"></i> Pros:<br><span class="text-muted fw-normal ms-3">Guaranteed acceptance of physical copies, face-to-face assistance.</span></div>
                        <div class="text-danger fw-semibold"><i class="bi bi-dash-circle-fill me-1"></i> Cons:<br><span class="text-muted fw-normal ms-3">Application is paused and will not proceed until you visit the campus.</span></div>
                      </div>
                    </label>
                  </div>
                </div>

                <?php if (!$isLocked): ?>
                  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mt-3 pt-3 border-top">
                    <div class="text-muted small">
                      <i class="bi bi-info-circle text-primary me-1"></i> You will be asked for a secondary confirmation before your choice is finalized.
                    </div>
                    <button type="button" id="btnOpenMethodConfirmModal" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm text-nowrap">
                      <i class="bi bi-shield-check me-1"></i> Save Preference
                    </button>
                  </div>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <?php if ($method === 'on_campus'): ?>
            <!-- On-Campus Verification Instructional Guide -->
            <div class="island mb-4 shadow-sm rounded-4 border-0 fade-in-up" style="animation-delay: 0.2s;">
              <div class="island-header border-bottom-0 pb-0">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="bi bi-building-fill-check text-primary fs-5"></i>
                  <h2 class="mb-0 fs-5 fw-bold text-dark">On-Campus Physical Verification Required</h2>
                </div>
                <p class="text-muted small mb-0">Because you selected On-Campus Submission, your application cannot be approved online until you present your original physical documents at the Admissions Office.</p>
              </div>

              <div class="island-body pt-3">
                <!-- Action Required Banner -->
                <div class="alert alert-warning border-0 rounded-4 p-3.5 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 bg-warning bg-opacity-10" style="border-left: 4px solid #ffc107 !important;">
                  <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-dark flex-shrink-0 mt-1" style="width: 42px; height: 42px;">
                      <i class="bi bi-geo-alt-fill fs-5"></i>
                    </div>
                    <div>
                      <h6 class="fw-bold text-dark mb-1">Action Required: Visit Admissions Office for Validation</h6>
                      <p class="small text-muted mb-0">
                        Please visit the campus with your original documents and Reference Number: <strong class="text-dark font-monospace"><?= htmlspecialchars($application['reference_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong>. An Admissions Officer will evaluate your physical credentials to approve your application.
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Office Information & Hours -->
                <div class="row g-3 mb-4">
                  <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                      <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-geo-alt text-primary fs-5"></i>
                        <h6 class="fw-bold text-dark mb-0">Office Location</h6>
                      </div>
                      <p class="small text-muted mb-0">
                        <strong>Triple T University - Admissions Office</strong><br>
                        Ground Floor, Administration Building, Main Campus<br>
                        <span class="text-secondary">Present your Reference Number at Window 1 or 2.</span>
                      </p>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                      <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-clock text-primary fs-5"></i>
                        <h6 class="fw-bold text-dark mb-0">Operating Hours</h6>
                      </div>
                      <p class="small text-muted mb-0">
                        <strong>Monday to Friday:</strong> 8:00 AM – 5:00 PM<br>
                        <strong>Saturday:</strong> 8:00 AM – 12:00 PM<br>
                        <span class="text-secondary">Closed on Sundays and Official Holidays.</span>
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Required Physical Documents Checklist -->
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-card-checklist text-primary me-2"></i>Documents Checklist to Bring</h6>
                <div class="row g-3 mb-4">
                  <?php foreach ($requiredDocs as $doc): ?>
                    <div class="col-md-6">
                      <div class="p-3 rounded-3 border bg-white h-100 d-flex align-items-start gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary flex-shrink-0" style="width: 36px; height: 36px;">
                          <i class="bi bi-file-earmark-check"></i>
                        </div>
                        <div class="flex-grow-1">
                          <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fw-bold text-dark small"><?= htmlspecialchars($doc['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill" style="font-size: 0.65rem;">Original + Copy</span>
                          </div>
                          <p class="text-muted small mb-0" style="font-size: 0.78rem;"><?= htmlspecialchars($doc['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <!-- Step-by-Step Walkthrough -->
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-signpost-split text-primary me-2"></i>What Happens Next?</h6>
                <div class="p-3 bg-light rounded-3 border">
                  <ol class="mb-0 ps-3 small text-muted">
                    <li class="mb-2"><strong class="text-dark">Physical Document Evaluation:</strong> The Admissions Officer will review and verify your documents.</li>
                    <li class="mb-2"><strong class="text-dark">Application Approval:</strong> Once verified, the officer will mark your status as <strong class="text-success">Approved</strong> in the system.</li>
                    <li class="mb-0"><strong class="text-dark">Enrollment Unlocked:</strong> After approval, you can immediately log back into this portal to complete your Medical Clearance, select your class section, and view your tuition assessment.</li>
                  </ol>
                </div>

              </div>
            </div>
          <?php else: ?>
            
            <?php if ($allMandatorySubmitted && !$isLocked): ?>
              <!-- Ready for Submission Notification Card -->
              <div class="island mb-4 p-4 shadow-sm rounded-4 border-0 bg-success bg-opacity-10 border border-success border-opacity-25 fade-in-up" style="animation-delay: 0.2s;">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-success text-white flex-shrink-0" style="width: 48px; height: 48px;">
                      <i class="bi bi-check2-all fs-4"></i>
                    </div>
                    <div>
                      <h5 class="fw-bold text-dark mb-1">All Mandatory Documents Uploaded!</h5>
                      <p class="text-muted small mb-0">You have uploaded all required requirements. Click below to formally submit your documents for Admissions review.</p>
                    </div>
                  </div>
                  <form id="submitAllDocsForm" action="document_workflow.php" method="POST" class="m-0">
                    <?= getCsrfInput() ?>
                    <input type="hidden" name="action" value="submit_documents">
                    <button type="button" id="btnSubmitAllDocs" class="btn btn-success px-4 py-2.5 rounded-pill fw-semibold shadow-sm text-nowrap">
                      <i class="bi bi-send-check me-1"></i> Submit for Verification
                    </button>
                  </form>
                </div>
              </div>
            <?php endif; ?>

            <!-- Online Upload List -->
            <div class="row g-4">
              <?php 
                $delay = 0.2;
                foreach ($requiredDocs as $doc): 
                  $docName = $doc['name'];
                  $docDesc = $doc['description'];
                  $hasDoc = isset($documents[$docName]);
                  $docStatus = $hasDoc ? $documents[$docName]['status'] : null;
                  $docId = $hasDoc ? $documents[$docName]['id'] : null;
              ?>
                <div class="col-12 fade-in-up" style="animation-delay: <?= esc($delay) ?>s;">
                  <div class="doc-card <?= esc($hasDoc ? 'border-success' : '') ?>">
                    <div class="p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                      
                      <div class="d-flex align-items-center gap-3">
                        <div class="doc-icon">
                           <i class="bi <?= esc($hasDoc ? 'bi-file-earmark-check-fill' : 'bi-file-earmark-text') ?>"></i>
                        </div>
                        <div>
                          <div class="d-flex align-items-center gap-2 mb-1">
                            <h3 class="h6 fw-bold mb-0 text-dark"><?= htmlspecialchars($docName, ENT_QUOTES, 'UTF-8'); ?></h3>
                            <?php if ($hasDoc): ?>
                              <?php 
                                $badgeClass = match($docStatus) {
                                  'verified' => 'bg-success',
                                  'rejected' => 'bg-danger',
                                  default => 'bg-warning text-dark'
                                };
                              ?>
                              <span class="badge badge-status <?= esc($badgeClass) ?> rounded-pill"><?= ucfirst(htmlspecialchars($docStatus, ENT_QUOTES, 'UTF-8')); ?></span>
                            <?php else: ?>
                              <span class="badge badge-status bg-secondary bg-opacity-25 text-secondary rounded-pill">Missing</span>
                            <?php endif; ?>
                          </div>
                          <p class="text-muted small mb-0"><?= htmlspecialchars($docDesc, ENT_QUOTES, 'UTF-8'); ?></p>
                           <?php if ($hasDoc && !empty($documents[$docName]['feedback'])): ?>
                              <div class="alert alert-warning border-0 p-2 mt-2 mb-0 small rounded-3 d-flex align-items-start gap-2" style="font-size: 0.75rem;">
                                <i class="bi bi-chat-left-text-fill text-warning mt-1"></i>
                                <span><strong>Admin Feedback:</strong> <?= htmlspecialchars($documents[$docName]['feedback'], ENT_QUOTES, 'UTF-8'); ?></span>
                              </div>
                           <?php elseif ($hasDoc && $docStatus === 'rejected'): ?>
                              <p class="text-danger small mt-2 mb-0 fw-medium"><i class="bi bi-exclamation-circle me-1"></i>This document was rejected. Please upload a clearer copy.</p>
                           <?php endif; ?>
                        </div>
                      </div>

                      <div class="text-md-end custom-file-upload">
                        <?php if ($hasDoc): ?>
                          <div class="mb-2">
                            <a href="document_view.php?id=<?= esc($docId) ?>" target="_blank" class="btn btn-outline-primary rounded-pill px-4 fw-semibold shadow-sm btn-sm">
                              <i class="bi bi-eye me-1"></i> View Current File
                            </a>
                          </div>
                        <?php endif; ?>
                        
                        <?php if (!$isLocked && $docStatus !== 'verified'): ?>
                          <form action="document_upload.php" method="POST" enctype="multipart/form-data" class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center justify-content-md-end">
                            <?= getCsrfInput() ?>
                            <input type="hidden" name="document_name" value="<?= htmlspecialchars($docName, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm flex-shrink-0">
                               <i class="bi bi-upload me-1"></i> <?= esc($hasDoc ? 'Replace' : 'Upload') ?>
                            </button>
                          </form>
                        <?php elseif ($isLocked || $docStatus === 'verified'): ?>
                          <div class="text-muted small bg-light rounded-pill px-3 py-2 fw-medium border d-inline-block mt-2"><i class="bi bi-lock-fill me-1"></i> Upload Locked</div>
                        <?php endif; ?>
                      </div>

                    </div>
                  </div>
                </div>
              <?php $delay += 0.1; endforeach; ?>
            </div>

          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Secondary Confirmation Modal: Submission Method -->
  <div class="modal fade" id="confirmMethodModal" tabindex="-1" aria-labelledby="confirmMethodModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-header border-0 bg-light py-3 px-4">
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning" style="width: 38px; height: 38px;">
              <i class="bi bi-shield-exclamation fs-5"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0" id="confirmMethodModalLabel">Confirm Submission Method</h5>
              <small class="text-muted">Secondary Confirmation & Lock</small>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4">
          
          <!-- Dynamic Selected Method Card -->
          <div id="modalMethodPreview" class="p-3 rounded-3 mb-3 border bg-light d-flex align-items-center gap-3">
            <div id="modalMethodIcon" class="rounded-3 d-flex align-items-center justify-content-center fs-3 bg-primary bg-opacity-10 text-primary" style="width: 50px; height: 50px;">
              <i class="bi bi-cloud-arrow-up"></i>
            </div>
            <div class="flex-grow-1">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span id="modalMethodTitle" class="fw-bold text-dark fs-6">Online Upload</span>
                <span id="modalMethodBadge" class="badge bg-primary rounded-pill">Selected</span>
              </div>
              <small id="modalMethodSubtitle" class="text-muted d-block">Submit digital copies of requirements</small>
            </div>
          </div>

          <!-- Irreversible Warning Alert -->
          <div class="alert alert-warning border-0 rounded-3 p-3 mb-3 d-flex align-items-start gap-3 bg-warning bg-opacity-10">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-4 flex-shrink-0 mt-1"></i>
            <div>
              <h6 class="fw-bold text-dark mb-1">Warning: This choice is irreversible!</h6>
              <p class="small text-muted mb-0" id="modalWarningText">
                Once you confirm, your submission method is locked and cannot be changed online. Please ensure you are ready to proceed with this method.
              </p>
            </div>
          </div>

          <!-- Method Specific Details -->
          <div id="modalMethodDetails" class="small p-3 rounded-3 bg-white border mb-3">
            <!-- Dynamically populated via JS -->
          </div>

          <!-- Secondary Confirmation Checkbox -->
          <div class="form-check p-3 bg-light rounded-3 border">
            <input class="form-check-input ms-0 me-2" type="checkbox" id="irreversibleConsentCheck">
            <label class="form-check-label small fw-semibold text-dark user-select-none cursor-pointer" for="irreversibleConsentCheck">
              I understand that this choice is permanent, irreversible, and cannot be undone online.
            </label>
          </div>

        </div>

        <div class="modal-footer border-0 bg-light py-3 px-4 d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="confirmSaveMethodBtn" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm" disabled>
            <span class="spinner-border spinner-border-sm d-none me-1" id="saveMethodSpinner" role="status" aria-hidden="true"></span>
            <span id="saveMethodBtnText"><i class="bi bi-check2-circle me-1"></i> Confirm Choice</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Secondary Confirmation Modal: Final Document Submission -->
  <div class="modal fade" id="confirmSubmitDocsModal" tabindex="-1" aria-labelledby="confirmSubmitDocsModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-header border-0 bg-light py-3 px-4">
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success" style="width: 38px; height: 38px;">
              <i class="bi bi-send-check fs-5"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0" id="confirmSubmitDocsModalLabel">Submit Documents for Review</h5>
              <small class="text-muted">Finalize Online Uploads</small>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4">
          <!-- Irreversible Warning Alert -->
          <div class="alert alert-warning border-0 rounded-3 p-3 mb-3 d-flex align-items-start gap-3 bg-warning bg-opacity-10">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-4 flex-shrink-0 mt-1"></i>
            <div>
              <h6 class="fw-bold text-dark mb-1">Warning: Document Lock Notice</h6>
              <p class="small text-muted mb-0">
                Once submitted for verification, all your uploaded documents will be locked and sent directly to the Admissions Office. You will not be able to upload or replace files unless an evaluator requests corrections.
              </p>
            </div>
          </div>

          <div class="small p-3 rounded-3 bg-white border mb-3">
            <ul class="mb-0 ps-3 text-muted">
              <li><strong class="text-dark">Verification Process:</strong> Admissions officers will inspect each document for authenticity and legibility.</li>
              <li><strong class="text-dark">Status Transition:</strong> Your application status will update to <strong class="text-primary">Under Review</strong>.</li>
            </ul>
          </div>

          <div class="form-check p-3 bg-light rounded-3 border">
            <input class="form-check-input ms-0 me-2" type="checkbox" id="submitDocsConsentCheck">
            <label class="form-check-label small fw-semibold text-dark user-select-none cursor-pointer" for="submitDocsConsentCheck">
              I confirm that all uploaded documents are authentic, legible, and final.
            </label>
          </div>
        </div>

        <div class="modal-footer border-0 bg-light py-3 px-4 d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="confirmSubmitDocsBtn" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm" disabled>
            <span class="spinner-border spinner-border-sm d-none me-1" id="submitDocsSpinner" role="status" aria-hidden="true"></span>
            <span id="submitDocsBtnText"><i class="bi bi-send-check me-1"></i> Yes, Submit for Review</span>
          </button>
        </div>
      </div>
    </div>
  </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function showToast(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-4 shadow`;
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = `
            <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(alertDiv);
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }, 4000);
    }

    // --- Submission Method Secondary Confirmation Logic ---
    const methodForm = document.getElementById('submissionMethodForm');
    const openMethodModalBtn = document.getElementById('btnOpenMethodConfirmModal');
    const confirmModalEl = document.getElementById('confirmMethodModal');
    const consentCheck = document.getElementById('irreversibleConsentCheck');
    const confirmSaveMethodBtn = document.getElementById('confirmSaveMethodBtn');
    const saveMethodSpinner = document.getElementById('saveMethodSpinner');
    const saveMethodBtnText = document.getElementById('saveMethodBtnText');

    const modalMethodIcon = document.getElementById('modalMethodIcon');
    const modalMethodTitle = document.getElementById('modalMethodTitle');
    const modalMethodBadge = document.getElementById('modalMethodBadge');
    const modalMethodSubtitle = document.getElementById('modalMethodSubtitle');
    const modalWarningText = document.getElementById('modalWarningText');
    const modalMethodDetails = document.getElementById('modalMethodDetails');

    let methodModal = null;
    if (confirmModalEl) {
        methodModal = new bootstrap.Modal(confirmModalEl);
    }

    if (openMethodModalBtn && methodForm && methodModal) {
        openMethodModalBtn.addEventListener('click', function() {
            const checkedRadio = methodForm.querySelector('input[name="submission_method"]:checked');
            const selectedMethod = checkedRadio ? checkedRadio.value : 'online';

            if (selectedMethod === 'on_campus') {
                modalMethodIcon.className = 'rounded-3 d-flex align-items-center justify-content-center fs-3 bg-primary bg-opacity-10 text-primary';
                modalMethodIcon.innerHTML = '<i class="bi bi-building"></i>';
                modalMethodTitle.textContent = 'On-Campus Submission';
                modalMethodBadge.className = 'badge bg-warning text-dark rounded-pill';
                modalMethodBadge.textContent = 'Physical Verification';
                modalMethodSubtitle.textContent = 'Submit original copies at the TTU Admissions Office';
                modalWarningText.textContent = 'Selecting On-Campus Submission will immediately set your application to Under Review and disable online document uploading. This action cannot be undone online.';
                modalMethodDetails.innerHTML = `
                    <ul class="mb-0 ps-3 text-muted">
                        <li><strong class="text-dark">Physical Presence Required:</strong> You must visit the Admissions Office with your original documents.</li>
                        <li><strong class="text-dark">Upload Locked:</strong> You will not be able to upload or replace files online.</li>
                        <li><strong class="text-dark">Irreversible:</strong> You cannot switch back to Online Upload once confirmed.</li>
                    </ul>
                `;
            } else {
                modalMethodIcon.className = 'rounded-3 d-flex align-items-center justify-content-center fs-3 bg-primary bg-opacity-10 text-primary';
                modalMethodIcon.innerHTML = '<i class="bi bi-cloud-arrow-up"></i>';
                modalMethodTitle.textContent = 'Online Upload';
                modalMethodBadge.className = 'badge bg-primary rounded-pill';
                modalMethodBadge.textContent = 'Digital Upload';
                modalMethodSubtitle.textContent = 'Submit digital copies of requirements through the portal';
                modalWarningText.textContent = 'Selecting Online Upload locks your choice to digital verification. You will be required to submit clear digital copies of all mandatory documents through this portal.';
                modalMethodDetails.innerHTML = `
                    <ul class="mb-0 ps-3 text-muted">
                        <li><strong class="text-dark">Digital Copies Required:</strong> Prepare PDF, JPG, or PNG files under 5MB for each document.</li>
                        <li><strong class="text-dark">Online Verification:</strong> Admissions officers will verify your documents directly on the portal.</li>
                        <li><strong class="text-dark">Irreversible:</strong> Once confirmed, online submission is your primary verification mode.</li>
                    </ul>
                `;
            }

            if (consentCheck) consentCheck.checked = false;
            if (confirmSaveMethodBtn) confirmSaveMethodBtn.disabled = true;

            methodModal.show();
        });
    }

    if (consentCheck && confirmSaveMethodBtn) {
        consentCheck.addEventListener('change', function() {
            confirmSaveMethodBtn.disabled = !this.checked;
        });
    }

    if (confirmSaveMethodBtn && methodForm) {
        confirmSaveMethodBtn.addEventListener('click', function() {
            confirmSaveMethodBtn.disabled = true;
            if (saveMethodSpinner) saveMethodSpinner.classList.remove('d-none');
            if (saveMethodBtnText) saveMethodBtnText.textContent = 'Saving...';
            methodForm.submit();
        });
    }

    // --- Final Document Submission Modal Logic ---
    const btnSubmitAllDocs = document.getElementById('btnSubmitAllDocs');
    const submitDocsModalEl = document.getElementById('confirmSubmitDocsModal');
    const submitDocsConsentCheck = document.getElementById('submitDocsConsentCheck');
    const confirmSubmitDocsBtn = document.getElementById('confirmSubmitDocsBtn');
    const submitAllDocsForm = document.getElementById('submitAllDocsForm');
    const submitDocsSpinner = document.getElementById('submitDocsSpinner');
    const submitDocsBtnText = document.getElementById('submitDocsBtnText');

    let submitDocsModal = null;
    if (submitDocsModalEl) {
        submitDocsModal = new bootstrap.Modal(submitDocsModalEl);
    }

    if (btnSubmitAllDocs && submitDocsModal) {
        btnSubmitAllDocs.addEventListener('click', function() {
            if (submitDocsConsentCheck) submitDocsConsentCheck.checked = false;
            if (confirmSubmitDocsBtn) confirmSubmitDocsBtn.disabled = true;
            submitDocsModal.show();
        });
    }

    if (submitDocsConsentCheck && confirmSubmitDocsBtn) {
        submitDocsConsentCheck.addEventListener('change', function() {
            confirmSubmitDocsBtn.disabled = !this.checked;
        });
    }

    if (confirmSubmitDocsBtn && submitAllDocsForm) {
        confirmSubmitDocsBtn.addEventListener('click', function() {
            confirmSubmitDocsBtn.disabled = true;
            if (submitDocsSpinner) submitDocsSpinner.classList.remove('d-none');
            if (submitDocsBtnText) submitDocsBtnText.textContent = 'Submitting...';
            submitAllDocsForm.submit();
        });
    }

    // --- AJAX Document Upload Form Handlers ---
    const uploadForms = document.querySelectorAll('form[action="document_upload.php"]');
    uploadForms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = form.querySelector('button[type="submit"]');
            const originalBtnHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uploading...';
            btn.disabled = true;

            const formData = new FormData(form);
            formData.append('ajax', '1');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    
                    const card = form.closest('.doc-card');
                    if (card) {
                        card.classList.add('border-success');
                        
                        // Update badge
                        const badgeContainer = card.querySelector('.d-flex.align-items-center.gap-2.mb-1');
                        if (badgeContainer) {
                            let badge = badgeContainer.querySelector('.badge-status');
                            if (badge) {
                                badge.className = 'badge badge-status bg-warning text-dark rounded-pill';
                                badge.textContent = 'Pending';
                            } else {
                                const title = badgeContainer.querySelector('h3');
                                badge = document.createElement('span');
                                badge.className = 'badge badge-status bg-warning text-dark rounded-pill';
                                badge.textContent = 'Pending';
                                badgeContainer.insertBefore(badge, title.nextSibling);
                            }
                        }
                        
                        // Update icon
                        const icon = card.querySelector('.doc-icon i');
                        if (icon) {
                            icon.className = 'bi bi-file-earmark-check-fill';
                        }
                        
                        // Add or update "View Current File" button
                        let viewBtnContainer = card.querySelector('.mb-2');
                        if (!viewBtnContainer) {
                            const uploadContainer = card.querySelector('.text-md-end.custom-file-upload');
                            if (uploadContainer) {
                                viewBtnContainer = document.createElement('div');
                                viewBtnContainer.className = 'mb-2';
                                uploadContainer.insertBefore(viewBtnContainer, form);
                            }
                        }
                        
                        if (viewBtnContainer) {
                            viewBtnContainer.innerHTML = `
                                <a href="document_view.php?id=${data.doc_id}" target="_blank" class="btn btn-outline-primary rounded-pill px-4 fw-semibold shadow-sm btn-sm">
                                    <i class="bi bi-eye me-1"></i> View Current File
                                </a>
                            `;
                        }
                    }
                    
                    // Change button text to 'Replace'
                    btn.innerHTML = '<i class="bi bi-upload me-1"></i> Replace';
                } else {
                    showToast(data.message, 'danger');
                    btn.innerHTML = originalBtnHtml;
                }
            } catch (err) {
                console.error(err);
                showToast('An unexpected error occurred during upload.', 'danger');
                btn.innerHTML = originalBtnHtml;
            } finally {
                btn.disabled = false;
            }
        });
    });
});
</script>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
