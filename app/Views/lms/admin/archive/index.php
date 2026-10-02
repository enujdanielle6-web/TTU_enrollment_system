<?php
$pageTitle = 'LMS Course & Term Archival - Administrator';
require_once __DIR__ . '/../layout_header.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-archive-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Academic Term & Course Archival</h1>
              <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                Archived: <?= (int)$totalArchived ?> Courses
              </span>
            </div>
            <p class="text-muted small mb-0">Archive concluded semester course shells while non-destructively preserving all grades, submissions, quiz attempts, and materials.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="/sia/lms/admin/dashboard" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>LMS Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4 p-3">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div class="small fw-semibold"><?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4 p-3">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
        <div class="small fw-semibold"><?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Term Bulk Archival Card -->
    <div class="dossier-card mb-4 overflow-hidden p-4 bg-white border-0 shadow-sm">
      <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2.5">
        <div class="d-flex align-items-center gap-2">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 32px; height: 32px;">
            <i class="bi bi-calendar2-range"></i>
          </div>
          <h2 class="h5 fw-bold text-dark mb-0">Bulk Archive Term Courses</h2>
        </div>
        <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small">Non-Destructive Archival</span>
      </div>

      <div class="p-3 bg-info bg-opacity-10 border border-info border-opacity-25 rounded-3 mb-4 small text-muted">
        <i class="bi bi-shield-check text-info me-1"></i>
        <strong>Historical Learning Record Protection:</strong> Archival sets <code>status = 'archived'</code>. It will <em>never</em> delete student submissions, grades, quiz attempts, attendance records, or instructor materials.
      </div>

      <form action="/sia/lms/admin/archive/term" method="POST" class="row g-3 align-items-end" onsubmit="return confirm('Archive all active course shells for this term? Courses will become read-only while preserving all student submissions.');">
        <?= getCsrfInput() ?>

        <div class="col-md-3">
          <label class="form-label small fw-semibold text-muted text-uppercase">Academic Level</label>
          <select name="academic_level" class="form-select bg-light border-0 shadow-none rounded-3" required>
            <option value="College">College</option>
            <option value="SHS">Senior High School (SHS)</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label small fw-semibold text-muted text-uppercase">Academic Year</label>
          <select name="academic_year" class="form-select bg-light border-0 shadow-none rounded-3" required>
            <option value="">Select Academic Year...</option>
            <?php 
              $years = array_unique(array_column($terms ?? [], 'academic_year'));
              if (empty($years)) $years = ['2026-2027', '2025-2026'];
              foreach ($years as $yr): 
            ?>
              <option value="<?= htmlspecialchars($yr, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($yr, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label small fw-semibold text-muted text-uppercase">Semester / Term</label>
          <select name="semester" class="form-select bg-light border-0 shadow-none rounded-3" required>
            <option value="">Select Semester...</option>
            <option value="First">First Semester</option>
            <option value="Second">Second Semester</option>
            <option value="Summer">Summer Term</option>
          </select>
        </div>

        <div class="col-md-3">
          <button type="submit" class="btn btn-secondary rounded-pill px-4 w-100 shadow-sm d-inline-flex align-items-center justify-content-center gap-1.5 hover-lift">
            <i class="bi bi-archive-fill"></i>
            <span>Archive Concluded Term</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Currently Archived Courses Table Card -->
    <div class="dossier-card mb-4 overflow-hidden border-0 shadow-sm">
      <div class="dossier-card-header bg-white border-bottom p-3.5 px-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <div class="d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary rounded-3" style="width: 32px; height: 32px;">
            <i class="bi bi-collection"></i>
          </div>
          <div>
            <h3 class="h6 fw-bold text-dark mb-0">Archived Course Shells</h3>
            <div class="text-muted small" style="font-size: 0.75rem;">Concluded courses preserved with immutable records</div>
          </div>
        </div>
        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-1 small fw-semibold">
          <?= (int)$totalArchived ?> Courses
        </span>
      </div>

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
              <th>Status</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($archivedCourses)): ?>
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                  <div class="py-4">
                    <i class="bi bi-archive fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    <div class="fw-bold text-dark">No Archived Course Shells</div>
                    <div class="small text-muted mt-1">No concluded academic courses have been archived yet.</div>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($archivedCourses as $c): ?>
                <tr>
                  <td class="ps-4 fw-bold">
                    <span class="applicant-ref-badge">
                      #<?= (int)$c['id'] ?>
                    </span>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['subject_code'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($c['subject_name'], ENT_QUOTES, 'UTF-8') ?></div>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-0.5 small"><?= htmlspecialchars($c['section_code'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="text-muted small mt-0.5"><?= htmlspecialchars($c['academic_level'], ENT_QUOTES, 'UTF-8') ?></div>
                  </td>
                  <td class="small text-muted">
                    <?= htmlspecialchars($c['academic_year'] ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($c['semester'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </td>
                  <td>
                    <?= htmlspecialchars(trim(($c['instructor_first'] ?? '') . ' ' . ($c['instructor_last'] ?? '')) ?: 'TBA', ENT_QUOTES, 'UTF-8') ?>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                      <i class="bi bi-person-check text-secondary me-1"></i><?= (int)$c['enrolled_count'] ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2.5 py-1 small">Archived</span>
                  </td>
                  <td class="text-end pe-4">
                    <div class="d-inline-flex gap-2">
                      <a href="/sia/lms/admin/courses/<?= (int)$c['id'] ?>" class="btn btn-sm btn-light border rounded-pill px-3 shadow-xs">Inspect</a>
                      <form action="/sia/lms/admin/courses/<?= (int)$c['id'] ?>/status" method="POST" class="m-0" onsubmit="return confirm('Restore this course shell to active status?');">
                        <?= getCsrfInput() ?>
                        <input type="hidden" name="status" value="active">
                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-xs">Restore</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <?php if ($totalPages > 1): ?>
        <div class="p-3.5 border-top d-flex justify-content-between align-items-center bg-light bg-opacity-50">
          <span class="text-muted small">Showing Page <?= $currentPage ?> of <?= $totalPages ?></span>
          <ul class="pagination pagination-sm mb-0">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                <a class="page-link rounded-2 mx-0.5" href="/sia/lms/admin/archive?page=<?= $p ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
