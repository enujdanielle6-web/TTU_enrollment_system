<?php
require_once __DIR__ . '/../../components/header.php';

require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip (Admissions Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-journal-bookmark-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Senior High School Sections</h1>
              <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> University Scheduler
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-layers text-info me-1"></i><?= count($shs_sections ?? []) ?> Sections
              </span>
            </div>
            <p class="text-muted small mb-0">Manage senior high track sections, classroom capacity, and schedule blocks.</p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="scheduler_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-speedometer2 text-primary"></i>
            <span>Dashboard</span>
          </a>
          <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addSectionModal">
            <i class="bi bi-plus-lg"></i>
            <span>Add Section</span>
          </button>
        </div>
      </div>
    </div>

    <?php if (isset($_SESSION['admin_success'])): ?>
      <div class="alert alert-success d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
        <div><?= htmlspecialchars($_SESSION['admin_success'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
      <?php unset($_SESSION['admin_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['admin_error'])): ?>
      <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-4 mb-4 border-0" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
        <div><?= htmlspecialchars($_SESSION['admin_error'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
      <?php unset($_SESSION['admin_error']); ?>
    <?php endif; ?>

    <!-- SHS Sections Catalog Card (Dossier Card Styling) -->
    <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.15s;">
      <div class="dossier-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-info bg-opacity-10 text-info">
            <i class="bi bi-journal-bookmark-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">All SHS Sections</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <?= count($shs_sections ?? []) ?> Records
            </span>
          </div>
        </div>
        <div class="input-group input-group-sm" style="width: 240px;">
          <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
          <input type="text" id="sectionSearch" class="form-control form-control-sm border-start-0 ps-0" placeholder="Search sections...">
        </div>
      </div>
      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table">
            <thead>
              <tr>
                <th class="ps-4">Section Code</th>
                <th>Strand & Curriculum</th>
                <th>Grade Level</th>
                <th>Academic Year</th>
                <th>Schedule</th>
                <th>Adviser</th>
                <th>Capacity</th>
                <th>Status</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($shs_sections)): ?>
                <tr>
                  <td colspan="9" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">No SHS Sections Found</h3>
                      <p class="small text-muted mb-0">Use the "Add Section" button to create new senior high class sections.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($shs_sections as $s): ?>
                  <tr>
                    <td class="ps-4">
                      <span class="applicant-ref-badge fw-bold text-dark">
                        <i class="bi bi-hash text-muted"></i><?= htmlspecialchars($s['section_code'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle"><?= htmlspecialchars($s['program_code'], ENT_QUOTES, 'UTF-8') ?></span>
                      <?php if (!empty($s['curriculum_version'])): ?>
                        <div class="small text-muted mt-1">v<?= htmlspecialchars($s['curriculum_version'], ENT_QUOTES, 'UTF-8') ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="fw-semibold text-dark"><?= htmlspecialchars($s['grade_level'], ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td>
                      <span class="badge bg-light text-secondary border rounded-pill"><?= htmlspecialchars($s['academic_year'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td><i class="bi bi-clock me-1 text-muted"></i><?= htmlspecialchars($s['schedule_type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($s['adviser'] ?: 'Unassigned', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <?php 
                          $enrolled = (int)$s['current_enrollment'];
                          $cap = (int)$s['capacity'];
                          $pct = $cap > 0 ? ($enrolled / $cap) * 100 : 0;
                          $bg = $pct >= 100 ? 'bg-danger' : ($pct > 80 ? 'bg-warning' : 'bg-primary');
                        ?>
                        <div class="fw-medium <?= esc($pct >= 100 ? 'text-danger' : 'text-dark') ?>"><?= esc($enrolled) ?>/<?= esc($cap) ?></div>
                        <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                          <div class="progress-bar <?= esc($bg) ?>" style="width: <?= esc(min(100, $pct)) ?>%"></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?php if ((int)$s['status'] === 1): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Active</span>
                      <?php else: ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <a href="schedule_builder.php?type=shs&id=<?= esc($s['id']) ?>" data-spa="false" class="btn btn-sm btn-outline-primary rounded-pill me-1" title="Manage Schedule">
                        <i class="bi bi-calendar-range"></i>
                      </a>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="section_id" value="<?= esc($s['id']) ?>">
                        <input type="hidden" name="action" value="toggle_status">
                        <button type="submit" class="btn btn-sm <?= esc((int)$s['status'] === 1 ? 'btn-outline-danger' : 'btn-outline-success') ?> rounded-pill me-1" title="Toggle Status">
                          <i class="bi <?= esc((int)$s['status'] === 1 ? 'bi-lock' : 'bi-unlock') ?>"></i>
                        </button>
                      </form>
                      <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" 
                              data-bs-toggle="modal" data-bs-target="#deleteSectionModal" 
                              onclick="setDeleteSection(<?= esc($s['id']) ?>, '<?= htmlspecialchars($s['section_code'], ENT_QUOTES, 'UTF-8') ?>')" title="Delete Section">
                        <i class="bi bi-trash-fill"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</main>

<!-- Add Section Modal -->
<div class="modal fade" id="addSectionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="shs_sections.php" class="needs-validation" novalidate>
        <div class="modal-header bg-primary text-white border-bottom-0 pb-3">
          <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Add New Section</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 bg-light">
          <input type="hidden" name="action" value="add_section">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Section Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="section_code" required placeholder="e.g., BSIT-1A">
            <div class="invalid-feedback">Please provide a section code.</div>
          </div>
          
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Academic Program <span class="text-danger">*</span></label>
            <select class="form-select" name="strand_id" id="programSelect" required onchange="filterCurricula()">
              <option value="" selected disabled>Select a program...</option>
              <?php foreach ($programs as $prog): ?>
                <option value="<?= esc($prog['id']) ?>" data-category="Senior High School">
                  <?= htmlspecialchars($prog['code'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Please select an academic program.</div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Curriculum Version <span class="text-danger">*</span></label>
            <select class="form-select" name="curriculum_id" id="curriculumSelect" required disabled>
              <option value="" selected disabled>Select program first...</option>
            </select>
            <div class="invalid-feedback">Please select a curriculum.</div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label text-muted small fw-bold">Grade Level <span class="text-danger">*</span></label>
              <select class="form-select" name="grade_level" required>
                <option value="Grade 11">Grade 11</option>
                <option value="Grade 12">Grade 12</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label text-muted small fw-bold">Academic Year <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="academic_year" required placeholder="e.g. 2026-2027" pattern="\d{4}-\d{4}">
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Capacity <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="capacity" value="40" required min="1">
            <div class="invalid-feedback">Please specify a valid capacity (minimum 1).</div>
          </div>
          
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Schedule Type <span class="text-danger">*</span></label>
            <select class="form-select" name="schedule_type" required>
              <option value="Morning" selected>Morning</option>
              <option value="Afternoon">Afternoon</option>
            </select>
            <div class="invalid-feedback">Please select a schedule type.</div>
          </div>
          
          <div class="mb-3">
            <label class="form-label text-muted small fw-bold">Adviser (Optional)</label>
            <input type="text" class="form-control" name="adviser" placeholder="Name of section adviser">
          </div>
        </div>
        <div class="modal-footer bg-white border-top pt-3">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-medium">
            <i class="bi bi-save me-1"></i> Save Section
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Section Modal -->
<div class="modal fade" id="deleteSectionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white border-bottom-0 pb-3">
        <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Section</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-4 text-center">
        <i class="bi bi-trash text-danger display-1 mb-3 d-block"></i>
        <h5 class="fw-bold mb-2">Are you absolutely sure?</h5>
        <p class="text-muted mb-0">This will permanently delete the section <strong id="deleteSectionCode" class="text-dark"></strong> and its schedules. This cannot be undone.</p>
      </div>
      <div class="modal-footer bg-light border-top-0 pt-3 justify-content-center">
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        <form method="POST" class="d-inline">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="action" value="delete_section">
          <input type="hidden" name="section_id" id="deleteSectionId">
          <button type="submit" class="btn btn-danger rounded-pill px-4 shadow-sm fw-medium">Yes, Delete Section</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="/sia/public/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script>
const allCurricula = <?= json_encode($curricula ?? []) ?>;

function filterCurricula() {
    const strandId = document.getElementById('programSelect').value;
    const curriculumSelect = document.getElementById('curriculumSelect');
    
    curriculumSelect.innerHTML = '<option value="" selected disabled>Select curriculum...</option>';
    
    if (!strandId) {
        curriculumSelect.disabled = true;
        return;
    }
    
    const filtered = allCurricula.filter(c => String(c.strand_id) === String(strandId));
    
    if (filtered.length === 0) {
        curriculumSelect.innerHTML = '<option value="" selected disabled>No active curricula found</option>';
        curriculumSelect.disabled = true;
    } else {
        filtered.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.curriculum_name + ' (v' + c.version + ') - ' + (c.effective_academic_year || 'Any');
            curriculumSelect.appendChild(opt);
        });
        curriculumSelect.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    
    // Form Validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Search Filter Logic
    const searchInput = document.getElementById('sectionSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('.dashboard-table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
    

});

function setDeleteSection(id, code) {
    document.getElementById('deleteSectionId').value = id;
    document.getElementById('deleteSectionCode').textContent = code;
}

</script>
</body>
</html>




