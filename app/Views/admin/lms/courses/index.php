<?php
$pageTitle = 'LMS Course Catalog - Administrator';
require_once __DIR__ . '/../../../components/header.php';
require_once __DIR__ . '/../../../components/admin_navbar.php';
require_once __DIR__ . '/../components/lms_header.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Hero Header Strip -->
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
                Total: <?= (int)$totalCourses ?> Shells
              </span>
            </div>
            <p class="text-muted small mb-0">Inspect active and archived instructional course shells, verify section rosters, and monitor instructor bindings.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="/sia/admin/lms/generator" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-medium d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-plus-circle"></i>
            <span>Course Generator</span>
          </a>
          <a href="/sia/admin/lms/dashboard" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>LMS Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div><?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
        <div><?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Filter Bar Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
      <form action="/sia/admin/lms/courses" method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control bg-light border-start-0 shadow-none" placeholder="Search subject code, name, section, instructor..." value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>

        <div class="col-sm-6 col-md-2">
          <select name="academic_level" class="form-select bg-light shadow-none">
            <option value="">All Levels</option>
            <option value="College" <?= ($filters['academic_level'] ?? '') === 'College' ? 'selected' : '' ?>>College</option>
            <option value="SHS" <?= ($filters['academic_level'] ?? '') === 'SHS' ? 'selected' : '' ?>>Senior High (SHS)</option>
          </select>
        </div>

        <div class="col-sm-6 col-md-2">
          <select name="status" class="form-select bg-light shadow-none">
            <option value="">All Statuses</option>
            <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active Only</option>
            <option value="archived" <?= ($filters['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived Only</option>
          </select>
        </div>

        <div class="col-sm-6 col-md-2">
          <select name="faculty_filter" class="form-select bg-light shadow-none">
            <option value="">Faculty Assignment</option>
            <option value="assigned" <?= ($filters['faculty_filter'] ?? '') === 'assigned' ? 'selected' : '' ?>>Assigned Faculty</option>
            <option value="unassigned" <?= ($filters['faculty_filter'] ?? '') === 'unassigned' ? 'selected' : '' ?>>TBA / Unassigned</option>
          </select>
        </div>

        <div class="col-sm-6 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary rounded-pill px-3 w-100">Filter</button>
          <a href="/sia/admin/lms/courses" class="btn btn-light border rounded-pill px-3">Reset</a>
        </div>
      </form>
    </div>

    <!-- Course Catalog Table Card -->
    <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden mb-4">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light text-muted small text-uppercase">
            <tr>
              <th class="ps-4">ID</th>
              <th>Subject</th>
              <th>Section / Level</th>
              <th>Term</th>
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
                  <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                  No LMS course shells match your current filter criteria.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($courses as $c): ?>
                <tr>
                  <td class="ps-4 fw-bold text-muted">#<?= (int)$c['id'] ?></td>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['subject_code'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($c['subject_name'], ENT_QUOTES, 'UTF-8') ?> (<?= (int)$c['units'] ?> units)</div>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 fw-semibold">
                      <?= htmlspecialchars($c['section_code'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <div class="text-muted small mt-0.5"><?= htmlspecialchars($c['academic_level'], ENT_QUOTES, 'UTF-8') ?></div>
                  </td>
                  <td class="small text-muted">
                    <?= htmlspecialchars($c['academic_year'] ?? '2026-2027', ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars($c['semester'] ?? 'First', ENT_QUOTES, 'UTF-8') ?> Sem
                  </td>
                  <td>
                    <?php if (!empty($c['instructor_first'])): ?>
                      <div class="fw-semibold text-dark">
                        <?= htmlspecialchars($c['instructor_first'] . ' ' . $c['instructor_last'], ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($c['instructor_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <?php else: ?>
                      <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1">
                        <i class="bi bi-person-x me-1"></i> TBA (Unassigned)
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1">
                      <i class="bi bi-people-fill me-1"></i> <?= (int)$c['enrolled_count'] ?>
                    </span>
                  </td>
                  <td class="text-center small text-muted">
                    <span title="Modules"><i class="bi bi-folder2-open text-primary"></i> <?= (int)$c['modules_count'] ?></span> &bull; 
                    <span title="Assignments"><i class="bi bi-journal-text text-warning"></i> <?= (int)$c['assignments_count'] ?></span> &bull; 
                    <span title="Quizzes"><i class="bi bi-patch-question text-info"></i> <?= (int)$c['quizzes_count'] ?></span>
                  </td>
                  <td>
                    <?php if ($c['status'] === 'active'): ?>
                      <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1">Active</span>
                    <?php else: ?>
                      <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2.5 py-1">Archived</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end pe-4">
                    <a href="/sia/admin/lms/courses/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-xs d-inline-flex align-items-center gap-1">
                      <i class="bi bi-eye"></i>
                      <span>Inspect</span>
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
        <div class="p-3 border-top d-flex justify-content-between align-items-center">
          <span class="text-muted small">Showing Page <?= $currentPage ?> of <?= $totalPages ?> (Total: <?= $totalCourses ?>)</span>
          <ul class="pagination pagination-sm mb-0">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="/sia/admin/lms/courses?page=<?= $p ?>&search=<?= urlencode($filters['search'] ?? '') ?>&academic_level=<?= urlencode($filters['academic_level'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>&faculty_filter=<?= urlencode($filters['faculty_filter'] ?? '') ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../../../components/footer.php'; ?>
