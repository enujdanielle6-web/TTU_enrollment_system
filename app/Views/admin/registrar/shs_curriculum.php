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
          <p class="text-muted mb-0">Manage subjects assigned to SHS Strands</p>
        </div>
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
          <h2 class="mb-0 d-inline-block">SHS Strands</h2>
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
            <thead class="table-light text-muted small text-uppercase">
              <tr>
                <th class="ps-4">Strand Code</th>
                <th>Strand Name</th>
                <th>Total Subjects</th>
                <th>Total Units</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($strandsData)): ?>
                <tr>
                  <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                    No active SHS Strands found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($strandsData as $strand): ?>
                  <tr>
                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($strand['code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="fw-medium text-dark"><?= htmlspecialchars($strand['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge bg-secondary rounded-pill px-3"><?= $strand['total_subjects'] ?></span></td>
                    <td class="fw-medium text-success"><?= $strand['total_units'] ?></td>
                    <td class="text-end pe-4">
                      <a href="shs_curriculum_builder.php?strand_id=<?= $strand['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="bi bi-tools me-1"></i> Builder
                      </a>
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

