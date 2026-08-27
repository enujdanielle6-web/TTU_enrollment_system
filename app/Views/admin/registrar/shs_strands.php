<?php
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';

// Fetch strands
$strands = [];
try {
    $stmt = $pdo->query('SELECT * FROM shs_strands ORDER BY created_at ASC');
    $strands = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Academic strands fetch failed: ' . $e->getMessage());
}

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 fade-in-up" style="animation-delay: 0.1s;">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Senior High School Strands</h1>
        <p class="text-muted mb-0">Manage SHS academic strands and customize how each strand card appears on the landing page.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="/sia/#courses" target="_blank" class="btn btn-outline-secondary fw-medium shadow-sm rounded-pill px-3">
          <i class="bi bi-box-arrow-up-right me-1"></i> Preview Landing Page
        </a>
        <button type="button" class="btn btn-primary fw-medium shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addStrandModal">
          <i class="bi bi-plus-circle-fill me-1"></i> Add Strand
        </button>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4 fade-in-up" style="animation-delay: 0.2s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light d-flex justify-content-between align-items-center fade-in-up" style="animation-delay: 0.3s;">
        <div>
          <i class="bi bi-mortarboard-fill text-primary"></i>
          <h2 class="mb-0 text-dark d-inline-block">Strand Offerings</h2>
        </div>
        <div>
          <div class="input-group shadow-sm" style="width: 250px;">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="tableSearch" class="form-control border-start-0" placeholder="Search strands...">
          </div>
        </div>
      </div>
      
      <div class="island-body p-0 fade-in-up" style="animation-delay: 0.4s;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Strand Code</th>
                <th scope="col">Full Name / Description</th>
                <th scope="col">Landing Card Details</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($strands)): ?>
                <tr>
                  <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-x-circle fs-1 d-block mb-3 text-secondary"></i>
                    No SHS strands defined.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($strands as $strand): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark">
                      <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 p-2 bg-light text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.1rem;">
                          <i class="bi <?= esc(!empty($strand['icon']) ? $strand['icon'] : 'bi-mortarboard') ?>"></i>
                        </div>
                        <div>
                          <span class="fw-bold fs-6 text-primary"><?= htmlspecialchars(strtoupper($strand['code']), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="fw-semibold text-dark"><?= htmlspecialchars($strand['name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <?php if (!empty($strand['description'])): ?>
                        <small class="text-muted text-truncate d-inline-block" style="max-width: 300px;"><?= htmlspecialchars($strand['description'], ENT_QUOTES, 'UTF-8') ?></small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="small">
                        <?php if (!empty($strand['careers'])): ?>
                          <div class="text-truncate text-secondary mb-1" style="max-width: 260px;" title="<?= esc($strand['careers']) ?>">
                            <i class="bi bi-briefcase me-1 text-primary"></i> <?= esc($strand['careers']) ?>
                          </div>
                        <?php else: ?>
                          <span class="text-muted fst-italic">Default career mapping</span>
                        <?php endif; ?>
                        <?php if (!empty($strand['custom_tuition'])): ?>
                          <div class="text-success small fw-medium">
                            <i class="bi bi-cash-coin me-1"></i> <?= esc($strand['custom_tuition']) ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td>
                      <?php if ($strand['is_active']): ?>
                        <span class="badge bg-success rounded-pill px-3">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary rounded-pill px-3">Disabled</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <!-- Edit Landing Card Button -->
                      <button class="btn btn-sm btn-primary rounded-pill edit-card-btn me-1 shadow-sm" 
                              data-id="<?= esc($strand['id']) ?>"
                              data-code="<?= htmlspecialchars(strtoupper($strand['code']), ENT_QUOTES, 'UTF-8') ?>"
                              data-name="<?= htmlspecialchars($strand['name'], ENT_QUOTES, 'UTF-8') ?>"
                              data-description="<?= htmlspecialchars($strand['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-icon="<?= htmlspecialchars($strand['icon'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-careers="<?= htmlspecialchars($strand['careers'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-custom-tuition="<?= htmlspecialchars($strand['custom_tuition'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-bs-toggle="modal" 
                              data-bs-target="#editLandingCardModal"
                              title="Customize Landing Page Card">
                        <i class="bi bi-layout-text-window-reverse me-1"></i> Edit Card
                      </button>

                      <!-- Edit Strand Button -->
                      <button class="btn btn-sm btn-outline-secondary rounded-pill edit-strand-btn me-1" 
                              data-id="<?= esc($strand['id']) ?>"
                              data-code="<?= htmlspecialchars($strand['code'], ENT_QUOTES, 'UTF-8') ?>"
                              data-name="<?= htmlspecialchars($strand['name'], ENT_QUOTES, 'UTF-8') ?>"
                              data-description="<?= htmlspecialchars($strand['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-icon="<?= htmlspecialchars($strand['icon'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-careers="<?= htmlspecialchars($strand['careers'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-custom-tuition="<?= htmlspecialchars($strand['custom_tuition'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                              data-bs-toggle="modal" 
                              data-bs-target="#editStrandModal"
                              title="Edit Strand Information">
                        <i class="bi bi-pencil-square"></i>
                      </button>

                      <!-- Toggle Status Form -->
                      <form action="shs_strand_process.php" method="POST" class="d-inline">
                        <input type="hidden" name="action" value="toggle_strand">
                        <?= getCsrfInput() ?>
                        <input type="hidden" name="id" value="<?= esc($strand['id']) ?>">
                        <input type="hidden" name="status" value="<?= esc($strand['is_active'] ? '0' : '1') ?>">
                        <button type="submit" class="btn btn-sm <?= esc($strand['is_active'] ? 'btn-outline-danger' : 'btn-outline-success') ?> rounded-pill" title="<?= esc($strand['is_active'] ? 'Disable' : 'Enable') ?>">
                          <i class="bi <?= esc($strand['is_active'] ? 'bi-eye-slash' : 'bi-eye') ?>"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              <tr id="noResultsRow" style="display: none;">
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-search fs-1 d-block mb-3 text-secondary"></i>
                  No strands match your search.
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
            <h5 class="modal-title fw-bold mb-0">Customize Landing Page Card</h5>
            <small class="opacity-75" id="cardModalStrandSubtitle">Customize how this strand is presented to prospective students</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="shs_strand_process.php" method="POST">
        <div class="modal-body p-4">
          <input type="hidden" name="action" value="update_landing_card">
          <?= getCsrfInput() ?>
          <input type="hidden" name="id" id="cardStrandId">

          <div class="row g-4">
            <!-- Left Side: Form Controls -->
            <div class="col-lg-7">
              <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Card Tagline / Overview</label>
                <textarea name="description" id="cardDescriptionInput" class="form-control bg-light" rows="3" placeholder="Brief blurb describing this strand on the landing page..."></textarea>
                <div class="form-text">Shown directly beneath the strand title.</div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Strand Icon</label>
                <div class="input-group mb-2">
                  <span class="input-group-text bg-light text-primary" id="iconAddonPreview"><i class="bi bi-mortarboard" id="iconPreviewEl"></i></span>
                  <input type="text" name="icon" id="cardIconInput" class="form-control bg-light" placeholder="e.g. bi-calculator, bi-briefcase, bi-tools">
                </div>
                <div class="d-flex flex-wrap gap-1 mt-2" id="iconPickerList">
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-calculator"><i class="bi bi-calculator"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-briefcase"><i class="bi bi-briefcase"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-chat-square-quote"><i class="bi bi-chat-square-quote"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-tools"><i class="bi bi-tools"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-laptop"><i class="bi bi-laptop"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-journal-bookmark"><i class="bi bi-journal-bookmark"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-book-half"><i class="bi bi-book-half"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-palette"><i class="bi bi-palette"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-trophy"><i class="bi bi-trophy"></i></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary icon-chip" data-icon="bi-mortarboard"><i class="bi bi-mortarboard"></i></button>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Career Opportunities</label>
                <input type="text" name="careers" id="cardCareersInput" class="form-control bg-light" placeholder="e.g. Engineer, Programmer, Architect">
                <div class="form-text">Comma-separated key career paths or academic tracks.</div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Tuition Display Text (Optional Override)</label>
                <input type="text" name="custom_tuition" id="cardTuitionInput" class="form-control bg-light" placeholder="e.g. ₱15,000 - ₱20,000 / sem (or leave blank for auto)">
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
                      <i class="bi bi-mortarboard" id="previewCardIcon"></i>
                    </div>
                    <h3 class="h5 fw-bold text-primary mb-2" id="previewCardCode">CODE</h3>
                    <p class="text-secondary small mb-3" id="previewCardDesc" style="min-height: 48px;">Strand description blurb will appear here...</p>
                    <p class="text-muted small mb-1"><i class="bi bi-cash-coin me-1"></i> Tuition: <span id="previewCardTuition" class="fw-medium text-dark">₱15,000 - ₱20,000 / sem</span></p>
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

<!-- Add Strand Modal -->
<div class="modal fade" id="addStrandModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Add New Strand</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="shs_strand_process.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="create_strand">
          <?= getCsrfInput() ?>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Strand Code (e.g. STEM, HUMSS)</label>
            <input type="text" name="code" class="form-control bg-light" required pattern="[A-Za-z0-9\-]+" title="Alphanumeric and dashes only.">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Full Strand Name</label>
            <input type="text" name="name" class="form-control bg-light" required placeholder="e.g. Science, Technology, Engineering, & Math">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Landing Card Overview / Tagline</label>
            <textarea name="description" class="form-control bg-light" rows="2" placeholder="Brief overview for the landing page card..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Career Paths (Optional)</label>
            <input type="text" name="careers" class="form-control bg-light" placeholder="e.g. Engineer, Scientist, Architect">
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Strand</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Strand Modal -->
<div class="modal fade" id="editStrandModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Edit Strand Information</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="shs_strand_process.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="update_strand">
          <?= getCsrfInput() ?>
          <input type="hidden" name="id" id="editStrandId">
          
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Strand Code</label>
            <input type="text" name="code" id="editStrandCode" class="form-control bg-light" required pattern="[A-Za-z0-9\-]+">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Full Strand Name</label>
            <input type="text" name="name" id="editStrandName" class="form-control bg-light" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Landing Card Overview / Tagline</label>
            <textarea name="description" id="editStrandDesc" class="form-control bg-light" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Career Paths</label>
            <input type="text" name="careers" id="editStrandCareers" class="form-control bg-light">
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="/sia/public/vendor/jquery/jquery.min.js"></script>
<script>
  $(document).ready(function() {
    // Edit Strand Basic Modal
    $('.edit-strand-btn').on('click', function() {
      $('#editStrandId').val($(this).data('id'));
      $('#editStrandCode').val($(this).data('code'));
      $('#editStrandName').val($(this).data('name'));
      $('#editStrandDesc').val($(this).data('description'));
      $('#editStrandCareers').val($(this).data('careers'));
    });

    // Edit Landing Card Modal & Real-time Live Preview
    let currentCode = '';
    let currentName = '';

    function updatePreview() {
      const code = currentCode || 'CODE';
      const desc = $('#cardDescriptionInput').val().trim() || currentName || 'Strand description blurb will appear here...';
      const icon = $('#cardIconInput').val().trim() || 'bi-mortarboard';
      const careers = $('#cardCareersInput').val().trim() || 'Higher Education, Career Readiness';
      const tuition = $('#cardTuitionInput').val().trim() || '₱15,000 - ₱20,000 / sem';

      $('#previewCardCode').text(code);
      $('#previewCardDesc').text(desc);
      $('#previewCardCareers').text(careers);
      $('#previewCardTuition').text(tuition);

      $('#iconPreviewEl').attr('class', 'bi ' + icon);
      $('#previewCardIcon').attr('class', 'bi ' + icon);
    }

    $('.edit-card-btn').on('click', function() {
      const id = $(this).data('id');
      currentCode = $(this).data('code');
      currentName = $(this).data('name');
      const desc = $(this).data('description') || '';
      const icon = $(this).data('icon') || '';
      const careers = $(this).data('careers') || '';
      const tuition = $(this).data('custom-tuition') || '';

      $('#cardStrandId').val(id);
      $('#cardModalStrandSubtitle').text(currentCode + ' - ' + currentName);
      $('#cardDescriptionInput').val(desc);
      $('#cardIconInput').val(icon);
      $('#cardCareersInput').val(careers);
      $('#cardTuitionInput').val(tuition);

      updatePreview();
    });

    $('#cardDescriptionInput, #cardIconInput, #cardCareersInput, #cardTuitionInput').on('input', updatePreview);

    // Icon chip selection
    $('.icon-chip').on('click', function() {
      const icon = $(this).data('icon');
      $('#cardIconInput').val(icon);
      updatePreview();
    });

    // Table Search filter
    const searchInput = document.getElementById('tableSearch');
    if(searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('.custom-table tbody tr');
            let visibleCount = 0;
            let hasDataRows = false;
            
            rows.forEach(row => {
                if(row.id === 'noResultsRow' || row.querySelector('td[colspan]')) return;
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




