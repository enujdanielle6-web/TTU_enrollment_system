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
    padding: 1rem 1.5rem;
    border: 2px solid #e9ecef;
    border-radius: 16px;
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
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.1);
  }
  .method-option input[type="radio"]:disabled + .method-label {
    opacity: 0.5;
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
</style>

<main class="py-5 bg-light min-vh-100">
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
              <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-gear-fill text-primary fs-5"></i>
                <h2 class="mb-0 fs-5 fw-bold text-dark">Submission Method</h2>
              </div>
            </div>
            <div class="island-body pt-0 fade-in-up" style="animation-delay: 0.2s;">
              <form action="document_workflow.php" method="POST" class="d-flex flex-column gap-3">
                <?= getCsrfInput() ?>
                <p class="mb-1 text-muted small">Choose how you would like to submit your documents:</p>
                
                <div class="d-flex flex-column flex-md-row gap-3 mt-2">
                  <div class="method-option">
                    <input type="radio" name="submission_method" id="methodOnline" value="online" <?= $method === 'online' ? 'checked' : ''; ?> <?= $isLocked ? 'disabled' : ''; ?>>
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
                    <input type="radio" name="submission_method" id="methodOnCampus" value="on_campus" <?= $method === 'on_campus' ? 'checked' : ''; ?> <?= $isLocked ? 'disabled' : ''; ?>>
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
                  <div class="text-end mt-2">
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm">Save Preference</button>
                  </div>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <?php if ($method === 'on_campus'): ?>
            <div class="island text-center py-5 shadow-sm rounded-4 border-0 fade-in-up" style="animation-delay: 0.2s;">
              <div class="bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                <i class="bi bi-building fs-1"></i>
              </div>
              <h3 class="h5 fw-bold text-dark">On-Campus Verification Selected</h3>
              <p class="text-muted mb-0 mx-auto" style="max-width: 500px;">Please bring the original copies of all required documents to the admissions office during your scheduled physical verification.</p>
            </div>
          <?php else: ?>
            
            <!-- Online Upload List -->
            <div class="row g-4">
              <?php 
                $delay = 0.2;
                foreach ($requiredDocs as $docName => $docDesc): 
                  $hasDoc = isset($documents[$docName]);
                  $docStatus = $hasDoc ? $documents[$docName]['status'] : null;
                  $docId = $hasDoc ? $documents[$docName]['id'] : null;
              ?>
                <div class="col-12 fade-in-up" style="animation-delay: <?= $delay ?>s;">
                  <div class="doc-card <?= $hasDoc ? 'border-success' : '' ?> fade-in-up" style="animation-delay: 0.3s;">
                    <div class="p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                      
                      <div class="d-flex align-items-center gap-3">
                        <div class="doc-icon">
                           <i class="bi <?= $hasDoc ? 'bi-file-earmark-check-fill' : 'bi-file-earmark-text' ?>"></i>
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
                              <span class="badge badge-status <?= $badgeClass ?> rounded-pill"><?= ucfirst(htmlspecialchars($docStatus, ENT_QUOTES, 'UTF-8')); ?></span>
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
                            <a href="document_view.php?id=<?= $docId ?>" target="_blank" class="btn btn-outline-primary rounded-pill px-4 fw-semibold shadow-sm btn-sm">
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
                               <i class="bi bi-upload me-1"></i> <?= $hasDoc ? 'Replace' : 'Upload' ?>
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
                    card.classList.add('border-success');
                    
                    // Update badge
                    const badgeContainer = card.querySelector('.d-flex.align-items-center.gap-2.mb-1');
                    let badge = badgeContainer.querySelector('.badge-status');
                    if (badge) {
                        badge.className = 'badge badge-status bg-warning text-dark rounded-pill';
                        badge.textContent = 'Pending';
                    } else {
                        // Create badge if Missing
                        const title = badgeContainer.querySelector('h3');
                        badge = document.createElement('span');
                        badge.className = 'badge badge-status bg-warning text-dark rounded-pill';
                        badge.textContent = 'Pending';
                        badgeContainer.insertBefore(badge, title.nextSibling);
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
                        viewBtnContainer = document.createElement('div');
                        viewBtnContainer.className = 'mb-2';
                        uploadContainer.insertBefore(viewBtnContainer, form);
                    }
                    
                    viewBtnContainer.innerHTML = `
                        <a href="document_view.php?id=${data.doc_id}" target="_blank" class="btn btn-outline-primary rounded-pill px-4 fw-semibold shadow-sm btn-sm">
                            <i class="bi bi-eye me-1"></i> View Current File
                        </a>
                    `;
                    
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

