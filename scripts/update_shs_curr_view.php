<?php
$file = 'app/Views/admin/registrar/shs_curriculum.php';

$newContent = <<<'EOD'
<?php
require_once __DIR__ . '/../../components/header.php';
?>
<?php require_once __DIR__ . '/../../components/admin_navbar.php'; ?>
<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    <div class="island island-hero mb-4 fade-in-up" style="animation-delay: 0.1s;">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">SHS Curriculum Management</h1>
          <p class="text-muted mb-0">Manage and version curricula for SHS Strands</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createCurriculumModal">
          <i class="bi bi-plus-lg me-2"></i>Create Curriculum
        </button>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4 mb-4 fade-in-up" style="animation-delay: 0.2s;">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light d-flex justify-content-between align-items-center fade-in-up" style="animation-delay: 0.3s;">
        <div>
          <i class="bi bi-diagram-3"></i>
          <h2 class="mb-0 d-inline-block">Curricula</h2>
        </div>
        <div>
          <div class="input-group shadow-sm" style="width: 250px;">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="tableSearch" class="form-control border-start-0" placeholder="Search curricula...">
          </div>
        </div>
      </div>
      <div class="island-body p-0 fade-in-up" style="animation-delay: 0.4s;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light text-muted small text-uppercase">
              <tr>
                <th class="ps-4">Strand</th>
                <th>Curriculum Name</th>
                <th>Version</th>
                <th>Effective Year</th>
                <th>Status</th>
                <th>Total Subjects</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($curriculaData)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No curricula found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($curriculaData as $cur): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($cur['strand_code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="fw-medium text-dark"><?= htmlspecialchars($cur['curriculum_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge bg-secondary rounded-pill px-3">v<?= htmlspecialchars($cur['version'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($cur['effective_academic_year'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <?php if ($cur['status'] === 'active'): ?>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Active</span>
                      <?php elseif ($cur['status'] === 'inactive'): ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Inactive</span>
                      <?php else: ?>
                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3">Draft</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $cur['total_subjects'] ?> (<?= $cur['total_units'] ?> Units)</td>
                    <td class="text-end pe-4">
                      <a href="shs_curriculum_builder.php?curriculum_id=<?= $cur['curriculum_id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="bi bi-tools me-1"></i> Builder
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              <tr id="noResultsRow" style="display: none;">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-search fs-1 d-block mb-3 text-secondary"></i>
                  No curricula match your search.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Create Curriculum Modal -->
<div class="modal fade" id="createCurriculumModal" tabindex="-1" aria-labelledby="createCurriculumModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-primary text-white border-0 py-3">
        <h5 class="modal-title fw-bold" id="createCurriculumModalLabel"><i class="bi bi-plus-circle me-2"></i>Create Curriculum</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/sia/admin/registrar/shs_curriculum.php" method="POST">
        <div class="modal-body p-4 bg-light">
          <input type="hidden" name="action" value="create_curriculum">
          <div class="mb-3">
            <label class="form-label fw-semibold text-dark">SHS Strand</label>
            <select class="form-select form-control-lg shadow-sm" name="strand_id" required>
              <option value="">-- Select Strand --</option>
              <?php foreach ($activeStrands as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['code'] . ' - ' . $s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold text-dark">Curriculum Name</label>
            <input type="text" class="form-control form-control-lg shadow-sm" name="curriculum_name" placeholder="e.g. 2025 STEM Standard" required>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold text-dark">Version</label>
              <input type="text" class="form-control shadow-sm" name="version" value="1.0" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold text-dark">Effective Year</label>
              <input type="text" class="form-control shadow-sm" name="effective_academic_year" placeholder="e.g. 2025-2026">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold text-dark">Description</label>
            <textarea class="form-control shadow-sm" name="description" rows="2" placeholder="Optional internal notes"></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 bg-white p-3">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-save me-2"></i>Save Curriculum</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Main table search
    const searchInput = document.getElementById('tableSearch');
    if(searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('.table tbody tr');
            let visibleCount = 0;
            let hasDataRows = false;
            
            rows.forEach(row => {
                if(row.id === 'noResultsRow' || row.querySelector('td[colspan]')) return;
                hasDataRows = true;
                
                const text = row.textContent.toLowerCase();
                if(text.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            const noResults = document.getElementById('noResultsRow');
            if (hasDataRows) {
                if(visibleCount === 0) {
                    noResults.style.display = '';
                } else {
                    noResults.style.display = 'none';
                }
            }
        });
    }
});
</script>
<?php require_once __DIR__ . '/../../components/footer.php'; ?>
EOD;

file_put_contents($file, $newContent);
echo "shs_curriculum view updated.\n";
