<?php
$pageTitle = 'LMS Course Catalog - Administrator';
require_once __DIR__ . '/../layout_header.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Hero Header Strip (Registrar Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-collection-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">LMS Course Catalog</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-layers-fill me-1"></i> Total: <?= (int)$totalCourses ?> Shells
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                Page <?= (int)$currentPage ?> of <?= max(1, (int)$totalPages) ?>
              </span>
            </div>
            <p class="text-muted small mb-0">Inspect active and archived instructional course shells, verify section rosters, and monitor instructor bindings.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="/sia/lms/admin/sync" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-medium d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift">
            <i class="bi bi-arrow-repeat"></i>
            <span>Sync Hub</span>
          </a>
          <a href="/sia/lms/admin/generator" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-medium d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift">
            <i class="bi bi-plus-circle"></i>
            <span>Course Generator</span>
          </a>
          <a href="/sia/lms/admin/dashboard" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>LMS Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4 p-3 fade-in-up">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div class="small fw-semibold"><?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4 p-3 fade-in-up">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
        <div class="small fw-semibold"><?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Filter Bar Dossier Card -->
    <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.1s;">
      <div class="p-3.5 px-4">
        <form action="/sia/lms/admin/courses" method="GET" class="row g-2.5 align-items-center">
          <div class="col-md-4">
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0 text-muted rounded-start-pill ps-3"><i class="bi bi-search"></i></span>
              <input type="text" name="search" class="form-control bg-light border-start-0 shadow-none rounded-end-pill py-2 small" placeholder="Search subject code, name, section, instructor..." value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>

          <div class="col-sm-6 col-md-2">
            <select name="academic_level" class="form-select bg-light border-0 shadow-none rounded-pill py-2 small">
              <option value="">All Levels</option>
              <option value="College" <?= ($filters['academic_level'] ?? '') === 'College' ? 'selected' : '' ?>>College</option>
              <option value="SHS" <?= ($filters['academic_level'] ?? '') === 'SHS' ? 'selected' : '' ?>>Senior High (SHS)</option>
            </select>
          </div>

          <div class="col-sm-6 col-md-2">
            <select name="status" class="form-select bg-light border-0 shadow-none rounded-pill py-2 small">
              <option value="">All Statuses</option>
              <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active Only</option>
              <option value="archived" <?= ($filters['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived Only</option>
            </select>
          </div>

          <div class="col-sm-6 col-md-2">
            <select name="faculty_filter" class="form-select bg-light border-0 shadow-none rounded-pill py-2 small">
              <option value="">Faculty Assignment</option>
              <option value="assigned" <?= ($filters['faculty_filter'] ?? '') === 'assigned' ? 'selected' : '' ?>>Assigned Faculty</option>
              <option value="unassigned" <?= ($filters['faculty_filter'] ?? '') === 'unassigned' ? 'selected' : '' ?>>TBA / Unassigned</option>
            </select>
          </div>

          <div class="col-sm-6 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary rounded-pill px-3 w-100 fw-semibold shadow-xs hover-lift d-inline-flex align-items-center justify-content-center gap-1.5">
              <i class="bi bi-funnel"></i>
              <span>Filter</span>
            </button>
            <a href="/sia/lms/admin/courses" class="btn btn-light border rounded-pill px-3 shadow-xs hover-lift" title="Reset Filters">
              <i class="bi bi-arrow-counterclockwise"></i>
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Course Catalog Table Dossier Card (Registrar Consistent Table) -->
    <div class="dossier-card fade-in-up" style="animation-delay: 0.15s;">
      <div class="dossier-card-header">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-journal-bookmark-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Course Shell Masterlist</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <i class="bi bi-activity text-primary me-1"></i><?= count($courses) ?> of <?= (int)$totalCourses ?> Shells
            </span>
          </div>
        </div>
      </div>

      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table">
            <thead>
              <tr>
                <th class="ps-4" style="width: 100px;">Shell ID</th>
                <th>Course Subject</th>
                <th>Section / Level</th>
                <th>Academic Term</th>
                <th>Instructor</th>
                <th class="text-center">Enrolled</th>
                <th class="text-center">Content</th>
                <th>Status</th>
                <th class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($courses)): ?>
                <tr>
                  <td colspan="9" class="text-center py-5 text-muted">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">No LMS Course Shells Found</h3>
                      <p class="small text-muted mb-0">No course shells match your current search and filter criteria.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($courses as $c): 
                  $hasFaculty = !empty($c['instructor_first']);
                  $facName = $hasFaculty ? trim($c['instructor_first'] . ' ' . $c['instructor_last']) : 'Unassigned (TBA)';
                  $facInitial = strtoupper(substr($facName, 0, 1));
                  $isCollege = (($c['academic_level'] ?? '') === 'College');
                ?>
                  <tr>
                    <td class="ps-4">
                      <span class="applicant-ref-badge">
                        <i class="bi bi-hash text-muted"></i><?= (int)$c['id'] ?>
                      </span>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-1.5 mb-0.5">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 0.72rem;">
                          <?= htmlspecialchars($c['subject_code'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                      </div>
                      <div class="fw-bold text-dark" style="font-size: 0.92rem;"><?= htmlspecialchars($c['subject_name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="text-muted extra-small"><?= (int)$c['units'] ?> Academic Units</div>
                    </td>
                    <td>
                      <span class="badge bg-light text-primary border rounded-pill px-2.5 py-1 fw-semibold small">
                        <i class="bi bi-people me-1"></i><?= htmlspecialchars($c['section_code'] ?? 'No Section', ENT_QUOTES, 'UTF-8') ?>
                      </span>
                      <div class="mt-1">
                        <span class="badge <?= $isCollege ? 'bg-info bg-opacity-10 text-info border border-info border-opacity-25' : 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' ?> rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">
                          <?= htmlspecialchars($c['academic_level'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                      </div>
                    </td>
                    <td>
                      <strong class="text-dark d-block small"><?= htmlspecialchars($c['academic_year'] ?? '2026-2027', ENT_QUOTES, 'UTF-8') ?></strong>
                      <span class="text-muted extra-small"><?= htmlspecialchars($c['semester'] ?? 'First', ENT_QUOTES, 'UTF-8') ?> Semester</span>
                    </td>
                    <td>
                      <?php if ($hasFaculty): ?>
                        <div class="d-flex align-items-center gap-2">
                          <div class="applicant-avatar" style="width: 34px; height: 34px; font-size: 0.8rem;">
                            <?= esc($facInitial) ?>
                          </div>
                          <div>
                            <div class="fw-bold text-dark small"><?= htmlspecialchars($facName, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted extra-small"><?= htmlspecialchars($c['instructor_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                          </div>
                        </div>
                      <?php else: ?>
                        <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                          <i class="bi bi-exclamation-triangle me-1"></i> Needs Faculty
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 fw-semibold small">
                        <i class="bi bi-person-check text-success me-1"></i> <?= (int)$c['enrolled_count'] ?>
                      </span>
                    </td>
                    <td class="text-center small text-muted">
                      <span title="Modules" class="badge bg-light text-secondary border px-2 py-1 me-1"><i class="bi bi-folder2-open text-primary me-0.5"></i> <?= (int)$c['modules_count'] ?></span>
                      <span title="Assessments" class="badge bg-light text-secondary border px-2 py-1"><i class="bi bi-journal-text text-warning me-0.5"></i> <?= (int)$c['assignments_count'] + (int)$c['quizzes_count'] ?></span>
                    </td>
                    <td>
                      <?php if ($c['status'] === 'active'): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small d-inline-flex align-items-center gap-1 fw-semibold">
                          <span class="pulse-dot-green" style="width: 6px; height: 6px;"></span> Active
                        </span>
                      <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2.5 py-1 small fw-semibold">Archived</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <a href="/sia/lms/admin/courses/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 fw-medium d-inline-flex align-items-center gap-1 hover-lift">
                        <i class="bi bi-eye"></i>
                        <span>Inspect</span>
                        <i class="bi bi-chevron-right small"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination Footer -->
        <?php if ($totalPages > 1): ?>
          <div class="p-3.5 px-4 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 bg-light bg-opacity-50">
            <span class="text-muted small">Showing Page <?= $currentPage ?> of <?= $totalPages ?> (Total: <?= $totalCourses ?> Shells)</span>
            <ul class="pagination pagination-sm mb-0">
              <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                  <a class="page-link rounded-pill mx-0.5 px-2.5" href="/sia/lms/admin/courses?page=<?= $p ?>&search=<?= urlencode($filters['search'] ?? '') ?>&academic_level=<?= urlencode($filters['academic_level'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>&faculty_filter=<?= urlencode($filters['faculty_filter'] ?? '') ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
