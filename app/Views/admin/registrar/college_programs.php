<?php
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';

// Fetch programs
$programs = [];
try {
    $stmt = $pdo->query('SELECT * FROM college_programs ORDER BY created_at ASC');
    $programs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('College programs fetch failed: ' . $e->getMessage());
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip (Admissions Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">College Programs</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> University Registrar
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-layers text-primary me-1"></i><?= count($programs ?? []) ?> Offerings
              </span>
            </div>
            <p class="text-muted small mb-0">Manage degree programs and customize how each program card appears on the landing page.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="/sia/#courses" target="_blank" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-box-arrow-up-right text-primary"></i>
            <span>Preview Landing</span>
          </a>
          <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addProgramModal">
            <i class="bi bi-plus-lg"></i>
            <span>Add Program</span>
          </button>
        </div>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
        <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
        <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <!-- Programs Catalog Card (Dossier Card Styling) -->
    <div class="dossier-card fade-in-up" style="animation-delay: 0.15s;">
      <div class="dossier-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Degree Offerings</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <?= count($programs ?? []) ?> Programs
            </span>
          </div>
        </div>
        <div>
          <div class="input-group input-group-sm" style="width: 220px;">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" id="tableSearch" class="form-control form-control-sm border-start-0 ps-0" placeholder="Search programs...">
          </div>
        </div>
      </div>
      
      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table">
            <thead>
              <tr>
                <th scope="col" class="ps-4">Program Code</th>
                <th scope="col">Full Name / Description</th>
                <th scope="col">Landing Card Details</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($programs)): ?>
                <tr>
                  <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-x-circle fs-1 d-block mb-3 text-secondary"></i>
                    No college programs defined.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($programs as $prog): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark">
                      <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 p-2 bg-light text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.1rem;">
                          <i class="bi <?= esc(!empty($prog['icon']) ? $prog['icon'] : 'bi-mortarboard') ?>"></i>
                        </div>
                        <div>
                          <span class="fw-bold fs-6 text-primary"><?= htmlspecialchars(strtoupper($prog['code']), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="fw-semibold text-dark"><?= htmlspecialchars($prog['name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <?php if (!empty($prog['description'])): ?>
                        <small class="text-muted text-truncate d-inline-block" style="max-width: 300px;"><?= htmlspecialchars($prog['description'], ENT_QUOTES, 'UTF-8') ?></small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="small">
                        <?php if (!empty($prog['careers'])): ?>
                          <div class="text-truncate text-secondary mb-1" style="max-width: 260px;" title="<?= esc($prog['careers']) ?>">
                            <i class="bi bi-briefcase me-1 text-primary"></i> <?= esc($prog['careers']) ?>
                          </div>
                        <?php else: ?>
                          <span class="text-muted fst-italic">Default career mapping</span>
                        <?php endif; ?>
                        <?php if (!empty($prog['custom_tuition'])): ?>
                          <div class="text-success small fw-medium">
                            <i class="bi bi-cash-coin me-1"></i> <?= esc($prog['custom_tuition']) ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td>
                      <?php if ($prog['is_active']): ?>
                        <span class="badge bg-success rounded-pill px-3">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary rounded-pill px-3">Disabled</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <!-- Edit Landing Card Button -->
                      <button class="btn btn-sm btn-primary rounded-pill edit-card-btn me-1 shadow-sm" 
                              data-id="<?= esc($prog['id']) ?>"
                              data-code="<?= htmlspecialchars(strtoupper($prog['code']), ENT_QUOTES, 'UTF-8') ?>"
                              data-name="<?= htmlspecialchars($prog['name'], ENT_QUOTES, 'UTF-8') ?>"
                              data-description="<?= htmlspecialchars($prog['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-icon="<?= htmlspecialchars($prog['icon'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-careers="<?= htmlspecialchars($prog['careers'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-custom-tuition="<?= htmlspecialchars($prog['custom_tuition'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-bs-toggle="modal" 
                              data-bs-target="#editLandingCardModal"
                              title="Customize Landing Page Card">
                        <i class="bi bi-layout-text-window-reverse me-1"></i> Edit Card
                      </button>

                      <!-- Edit Program Details Button -->
                      <button class="btn btn-sm btn-outline-secondary rounded-pill edit-program-btn me-1" 
                              data-id="<?= esc($prog['id']) ?>"
                              data-code="<?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?>"
                              data-name="<?= htmlspecialchars($prog['name'], ENT_QUOTES, 'UTF-8') ?>"
                              data-description="<?= htmlspecialchars($prog['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-icon="<?= htmlspecialchars($prog['icon'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-careers="<?= htmlspecialchars($prog['careers'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-custom-tuition="<?= htmlspecialchars($prog['custom_tuition'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-bs-toggle="modal" 
                              data-bs-target="#editProgramModal"
                              title="Edit Program Information">
                        <i class="bi bi-pencil-square"></i>
                      </button>

                      <!-- Toggle Status Form -->
                      <form action="college_program_process.php" method="POST" class="d-inline">
                        <input type="hidden" name="action" value="toggle_program">
                        <?= getCsrfInput() ?>
                        <input type="hidden" name="id" value="<?= esc($prog['id']) ?>">
                        <input type="hidden" name="status" value="<?= esc($prog['is_active'] ? '0' : '1') ?>">
                        <button type="submit" class="btn btn-sm <?= esc($prog['is_active'] ? 'btn-outline-danger' : 'btn-outline-success') ?> rounded-pill" title="<?= esc($prog['is_active'] ? 'Disable' : 'Enable') ?>">
                          <i class="bi <?= esc($prog['is_active'] ? 'bi-eye-slash' : 'bi-eye') ?>"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              <tr id="noResultsRow" style="display: none;">
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-search fs-1 d-block mb-3 text-secondary"></i>
                  No programs match your search.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Edit Landing Card Modal with Interactive Preview -->
<div class="modal fade" id="editLandingCardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-primary text-white border-bottom-0 pb-3">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-layout-text-window-reverse fs-5"></i>
          <div>
            <h5 class="modal-title fw-bold mb-0 text-white">Customize Landing Page Card</h5>
            <small class="opacity-75 text-white" id="cardModalProgramSubtitle">Customize how this program is presented to prospective students</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="/sia/admin/registrar/college_program_process.php" method="POST">
        <div class="modal-body p-4">
          <input type="hidden" name="action" value="update_landing_card">
          <?= getCsrfInput() ?>
          <input type="hidden" name="id" id="cardProgramId">

          <div class="row g-4">
            <!-- Left Side: Form Controls -->
            <div class="col-lg-7">
              <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Card Tagline / Overview</label>
                <textarea name="description" id="cardDescriptionInput" class="form-control bg-light" rows="3" placeholder="Brief blurb describing this program on the landing page..."></textarea>
                <div class="form-text">Shown directly beneath the program title on the landing page.</div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Program Icon</label>
                <div class="input-group mb-2">
                  <span class="input-group-text bg-white text-primary border-end-0" id="iconAddonPreview"><i class="bi bi-laptop fs-5" id="iconPreviewEl"></i></span>
                  <input type="text" name="icon" id="cardIconInput" class="form-control bg-light border-start-0" placeholder="e.g. bi-pc-display, bi-laptop, bi-calculator">
                </div>
                <div class="d-flex flex-wrap gap-1 mt-2" id="cardIconPickerList">
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-pc-display" title="PC Display"><i class="bi bi-pc-display"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-laptop" title="Laptop"><i class="bi bi-laptop"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-diagram-3" title="Architecture"><i class="bi bi-diagram-3"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-cup-hot" title="Hospitality"><i class="bi bi-cup-hot"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-calculator-fill" title="Accountancy"><i class="bi bi-calculator-fill"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-bar-chart-line" title="Business"><i class="bi bi-bar-chart-line"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-book-half" title="Education"><i class="bi bi-book-half"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-heart-pulse" title="Nursing"><i class="bi bi-heart-pulse"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-building" title="Civil Engineering"><i class="bi bi-building"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-tools" title="Engineering"><i class="bi bi-tools"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-lightning-charge" title="Electrical"><i class="bi bi-lightning-charge"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-mortarboard" title="Mortarboard"><i class="bi bi-mortarboard"></i></button>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Career Opportunities</label>
                <input type="text" name="careers" id="cardCareersInput" class="form-control bg-light" placeholder="e.g. Software Engineer, IT Analyst, System Admin">
                <div class="form-text">Comma-separated key career paths.</div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Tuition Display Text (Optional Override)</label>
                <input type="text" name="custom_tuition" id="cardTuitionInput" class="form-control bg-light" placeholder="e.g. ₱25,000 - ₱30,000 / sem (or leave blank for auto)">
                <div class="form-text">Leave blank to use dynamic calculation from fee templates.</div>
              </div>
            </div>

            <!-- Right Side: Live Card Preview -->
            <div class="col-lg-5">
              <label class="form-label small fw-bold text-muted mb-2"><i class="bi bi-eye me-1"></i> Live Landing Page Preview</label>
              <div class="p-3 bg-light rounded-4 border">
                <div class="card program-card shadow-sm border-0 bg-white" style="border-radius: 16px;">
                  <div class="card-body p-4">
                    <div class="program-icon mb-3 d-inline-flex align-items-center justify-content-center text-primary bg-primary bg-opacity-10 rounded-3" style="width: 48px; height: 48px; font-size: 1.5rem;" id="previewCardIconWrap">
                      <i class="bi bi-laptop" id="previewCardIcon"></i>
                    </div>
                    <h3 class="h5 fw-bold text-primary mb-2" id="previewCardCode">CODE</h3>
                    <p class="text-secondary small mb-3" id="previewCardDesc" style="min-height: 48px;">Program description blurb will appear here...</p>
                    <p class="text-muted small mb-1"><i class="bi bi-cash-coin me-1"></i> Tuition: <span id="previewCardTuition" class="fw-medium text-dark">₱25,000 - ₱30,000 / sem</span></p>
                    <p class="text-muted small mb-0"><i class="bi bi-briefcase-fill me-1"></i> Careers: <span id="previewCardCareers" class="text-dark">Career Paths</span></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top bg-light pt-3">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Landing Card</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Program Modal -->
<div class="modal fade" id="addProgramModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-primary text-white border-bottom-0 pb-3">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-plus-circle-fill fs-5"></i>
          <div>
            <h5 class="modal-title fw-bold mb-0 text-white">Add New Degree Program</h5>
            <small class="opacity-75 text-white">Define a new College academic degree program and customize its landing page presence.</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/sia/admin/registrar/college_program_process.php" method="POST">
        <div class="modal-body p-4">
          <input type="hidden" name="action" value="create_program">
          <?= getCsrfInput() ?>
          
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label small fw-bold text-dark">Program Code <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control bg-light text-uppercase fw-bold" required pattern="[A-Za-z0-9\-]+" placeholder="e.g. BSCE" oninput="this.value = this.value.toUpperCase();">
              <div class="form-text">Alphanumeric (e.g. BSIT, BSCS, BSHM).</div>
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-bold text-dark">Full Program Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control bg-light" required placeholder="e.g. Bachelor of Science in Civil Engineering">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Landing Card Overview / Tagline</label>
            <textarea name="description" class="form-control bg-light" rows="2" placeholder="Brief overview for the landing page card..."></textarea>
            <div class="form-text">Shown directly beneath the program title on the landing page.</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Program Icon</label>
            <div class="input-group mb-2">
              <span class="input-group-text bg-white text-primary border-end-0" id="addIconAddonPreview"><i class="bi bi-laptop fs-5" id="addProgIconPreviewEl"></i></span>
              <input type="text" name="icon" id="addProgramIconInput" class="form-control bg-light border-start-0" placeholder="e.g. bi-pc-display" value="bi-laptop">
            </div>
            <div class="d-flex flex-wrap gap-1 mt-2" id="addProgIconPickerList">
              <button type="button" class="btn btn-sm btn-outline-secondary add-icon-chip" data-icon="bi-pc-display" title="PC Display"><i class="bi bi-pc-display"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary add-icon-chip" data-icon="bi-laptop" title="Laptop"><i class="bi bi-laptop"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary add-icon-chip" data-icon="bi-diagram-3" title="Architecture"><i class="bi bi-diagram-3"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary add-icon-chip" data-icon="bi-cup-hot" title="Hospitality"><i class="bi bi-cup-hot"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary add-icon-chip" data-icon="bi-calculator-fill" title="Accountancy"><i class="bi bi-calculator-fill"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary add-icon-chip" data-icon="bi-bar-chart-line" title="Business"><i class="bi bi-bar-chart-line"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary add-icon-chip" data-icon="bi-book-half" title="Education"><i class="bi bi-book-half"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary add-icon-chip" data-icon="bi-heart-pulse" title="Nursing"><i class="bi bi-heart-pulse"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary add-icon-chip" data-icon="bi-building" title="Civil Engineering"><i class="bi bi-building"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary add-icon-chip" data-icon="bi-tools" title="Engineering"><i class="bi bi-tools"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary add-icon-chip" data-icon="bi-lightning-charge" title="Electrical"><i class="bi bi-lightning-charge"></i></button>
              <button type="button" class="btn btn-sm btn-outline-secondary add-icon-chip" data-icon="bi-mortarboard" title="Mortarboard"><i class="bi bi-mortarboard"></i></button>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Career Paths (Optional)</label>
              <input type="text" name="careers" class="form-control bg-light" placeholder="e.g. Software Engineer, Systems Architect">
              <div class="form-text">Comma-separated target career paths.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Custom Tuition Override (Optional)</label>
              <input type="text" name="custom_tuition" class="form-control bg-light" placeholder="e.g. ₱25,000 - ₱30,000 / sem">
              <div class="form-text">Leave blank to use fee template defaults.</div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top bg-light pt-3">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Program</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Program Modal -->
<div class="modal fade" id="editProgramModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-light border-bottom pb-3">
        <h5 class="modal-title fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Program Information</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/sia/admin/registrar/college_program_process.php" method="POST">
        <div class="modal-body p-4">
          <input type="hidden" name="action" value="update_program">
          <?= getCsrfInput() ?>
          <input type="hidden" name="id" id="editProgramId">
          
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label small fw-bold text-dark">Program Code</label>
              <input type="text" name="code" id="editProgramCode" class="form-control bg-light text-uppercase fw-bold" required pattern="[A-Za-z0-9\-]+" oninput="this.value = this.value.toUpperCase();">
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-bold text-dark">Full Program Name</label>
              <input type="text" name="name" id="editProgramName" class="form-control bg-light" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Landing Card Overview / Tagline</label>
            <textarea name="description" id="editProgramDesc" class="form-control bg-light" rows="2"></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Career Paths</label>
              <input type="text" name="careers" id="editProgramCareers" class="form-control bg-light">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Custom Tuition Override</label>
              <input type="text" name="custom_tuition" id="editProgramTuition" class="form-control bg-light">
            </div>
          </div>
        </div>
        <div class="modal-footer border-top bg-light pt-3">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // --- Edit Landing Card Modal Setup & Live Preview ---
    const editCardModal = document.getElementById('editLandingCardModal');
    let currentCode = '';
    let currentName = '';

    function updatePreview() {
      const code = currentCode || 'CODE';
      const descInput = document.getElementById('cardDescriptionInput');
      const iconInput = document.getElementById('cardIconInput');
      const careersInput = document.getElementById('cardCareersInput');
      const tuitionInput = document.getElementById('cardTuitionInput');

      const desc = (descInput ? descInput.value.trim() : '') || currentName || 'Program description blurb will appear here...';
      const icon = (iconInput ? iconInput.value.trim() : '') || 'bi-laptop';
      const careers = (careersInput ? careersInput.value.trim() : '') || 'Industry Specialist, Professional Practitioner';
      const tuition = (tuitionInput ? tuitionInput.value.trim() : '') || '₱25,000 - ₱30,000 / sem';

      const previewCode = document.getElementById('previewCardCode');
      const previewDesc = document.getElementById('previewCardDesc');
      const previewCareers = document.getElementById('previewCardCareers');
      const previewTuition = document.getElementById('previewCardTuition');
      const iconPreviewEl = document.getElementById('iconPreviewEl');
      const previewCardIcon = document.getElementById('previewCardIcon');

      if (previewCode) previewCode.textContent = code;
      if (previewDesc) previewDesc.textContent = desc;
      if (previewCareers) previewCareers.textContent = careers;
      if (previewTuition) previewTuition.textContent = tuition;

      if (iconPreviewEl) iconPreviewEl.className = 'bi ' + icon + ' fs-5';
      if (previewCardIcon) previewCardIcon.className = 'bi ' + icon;

      // Update active chip highlight
      document.querySelectorAll('#cardIconPickerList .icon-chip').forEach(chip => {
        if (chip.getAttribute('data-icon') === icon) {
          chip.classList.remove('btn-outline-secondary');
          chip.classList.add('btn-primary', 'text-white');
        } else {
          chip.classList.remove('btn-primary', 'text-white');
          chip.classList.add('btn-outline-secondary');
        }
      });
    }

    if (editCardModal) {
      editCardModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        if (!button) return;

        const id = button.getAttribute('data-id') || '';
        currentCode = (button.getAttribute('data-code') || '').toUpperCase();
        currentName = button.getAttribute('data-name') || '';
        const desc = button.getAttribute('data-description') || '';
        const icon = button.getAttribute('data-icon') || 'bi-laptop';
        const careers = button.getAttribute('data-careers') || '';
        const tuition = button.getAttribute('data-custom-tuition') || '';

        const cardIdEl = document.getElementById('cardProgramId');
        const subtitleEl = document.getElementById('cardModalProgramSubtitle');
        const descInput = document.getElementById('cardDescriptionInput');
        const iconInput = document.getElementById('cardIconInput');
        const careersInput = document.getElementById('cardCareersInput');
        const tuitionInput = document.getElementById('cardTuitionInput');

        if (cardIdEl) cardIdEl.value = id;
        if (subtitleEl) subtitleEl.textContent = currentCode + ' - ' + currentName;
        if (descInput) descInput.value = desc;
        if (iconInput) iconInput.value = icon;
        if (careersInput) careersInput.value = careers;
        if (tuitionInput) tuitionInput.value = tuition;

        updatePreview();
      });
    }

    ['cardDescriptionInput', 'cardIconInput', 'cardCareersInput', 'cardTuitionInput'].forEach(id => {
      const el = document.getElementById(id);
      if (el) {
        el.addEventListener('input', updatePreview);
      }
    });

    // Icon chip selection for Edit Card Modal
    document.addEventListener('click', function(e) {
      const chip = e.target.closest('#cardIconPickerList .icon-chip');
      if (chip) {
        const icon = chip.getAttribute('data-icon');
        const iconInput = document.getElementById('cardIconInput');
        if (iconInput) {
          iconInput.value = icon;
          updatePreview();
        }
      }
    });

    // --- Add Program Modal Icon Picker ---
    document.addEventListener('click', function(e) {
      const chip = e.target.closest('#addProgIconPickerList .add-icon-chip');
      if (chip) {
        const icon = chip.getAttribute('data-icon');
        const iconInput = document.getElementById('addProgramIconInput');
        const previewEl = document.getElementById('addProgIconPreviewEl');
        if (iconInput) {
          iconInput.value = icon;
        }
        if (previewEl) {
          previewEl.className = 'bi ' + icon + ' fs-5';
        }
        document.querySelectorAll('#addProgIconPickerList .add-icon-chip').forEach(c => {
          if (c.getAttribute('data-icon') === icon) {
            c.classList.remove('btn-outline-secondary');
            c.classList.add('btn-primary', 'text-white');
          } else {
            c.classList.remove('btn-primary', 'text-white');
            c.classList.add('btn-outline-secondary');
          }
        });
      }
    });

    const addIconInput = document.getElementById('addProgramIconInput');
    if (addIconInput) {
      addIconInput.addEventListener('input', function() {
        const icon = this.value.trim() || 'bi-laptop';
        const previewEl = document.getElementById('addProgIconPreviewEl');
        if (previewEl) previewEl.className = 'bi ' + icon + ' fs-5';
      });
    }

    // --- Edit Program Basic Modal ---
    const editProgramModal = document.getElementById('editProgramModal');
    if (editProgramModal) {
      editProgramModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        if (!button) return;

        const idEl = document.getElementById('editProgramId');
        const codeEl = document.getElementById('editProgramCode');
        const nameEl = document.getElementById('editProgramName');
        const descEl = document.getElementById('editProgramDesc');
        const careersEl = document.getElementById('editProgramCareers');
        const tuitionEl = document.getElementById('editProgramTuition');

        if (idEl) idEl.value = button.getAttribute('data-id') || '';
        if (codeEl) codeEl.value = button.getAttribute('data-code') || '';
        if (nameEl) nameEl.value = button.getAttribute('data-name') || '';
        if (descEl) descEl.value = button.getAttribute('data-description') || '';
        if (careersEl) careersEl.value = button.getAttribute('data-careers') || '';
        if (tuitionEl) tuitionEl.value = button.getAttribute('data-custom-tuition') || '';
      });
    }

    // --- Table Search Filter ---
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
      searchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('.custom-table tbody tr');
        let visibleCount = 0;
        let hasDataRows = false;
        
        rows.forEach(row => {
          if (row.id === 'noResultsRow' || row.querySelector('td[colspan]')) return;
          hasDataRows = true;
          
          const text = row.textContent.toLowerCase();
          if (text.includes(filter)) {
            row.style.display = '';
            visibleCount++;
          } else {
            row.style.display = 'none';
          }
        });
        
        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
          noResultsRow.style.display = (visibleCount === 0 && hasDataRows) ? '' : 'none';
        }
      });
    }
  });
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>




