<?php
$pageTitle = 'LMS Course Generator - Administrator';

$lmsHeader = __DIR__ . '/../../lms/admin/layout_header.php';
$lmsFooter = __DIR__ . '/../../lms/admin/layout_footer.php';

if (file_exists($lmsHeader)) {
    require_once $lmsHeader;
} else {
    require_once __DIR__ . '/../../components/header.php';
    require_once __DIR__ . '/../../components/admin_navbar.php';
}
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-cpu-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">LMS Course Shell Generator</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-mortarboard-fill me-1"></i> Provisioning Engine
              </span>
              <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <?= count($unmapped_courses ?? []) ?> Pending Shells
              </span>
            </div>
            <p class="text-muted small mb-0">Pair unmapped timetable class section subjects with designated faculty instructors to generate isolated LMS subject course shells.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="<?= BASE_PATH ?>/lms/admin/courses" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift">
            <i class="bi bi-grid-3x3-gap text-primary"></i>
            <span>Course Catalog</span>
          </a>
          <a href="<?= BASE_PATH ?>/lms/admin/dashboard" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Flash Notifications -->
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center gap-2.5 mb-4 p-3 fade-in-up">
        <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0"></i>
        <div class="small fw-medium"><?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-2.5 mb-4 p-3 fade-in-up">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0"></i>
        <div class="small fw-medium"><?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Main Dossier Card -->
    <div class="dossier-card mb-4 overflow-hidden border-0 shadow-sm fade-in-up">
      <div class="dossier-card-header bg-white border-bottom p-3.5 px-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2.5">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 36px; height: 36px; font-size: 1.15rem;">
            <i class="bi bi-diagram-3-fill"></i>
          </div>
          <div>
            <h2 class="h6 fw-bold text-dark mb-0">Unmapped Section Offerings</h2>
            <div class="text-muted small" style="font-size: 0.75rem;">Select an active faculty instructor to instantiate and deploy the isolated LMS course shell</div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1.5 small font-monospace">
            <?= count($unmapped_courses ?? []) ?> unmapped subjects
          </span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 dashboard-table">
          <thead>
            <tr>
              <th scope="col" class="ps-4" style="width: 120px;">Level</th>
              <th scope="col" style="width: 140px;">Section</th>
              <th scope="col">Subject</th>
              <th scope="col" style="width: 190px;">Timetable Instructor</th>
              <th scope="col" style="width: 380px;" class="pe-4">Assign LMS Faculty &amp; Provision</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($unmapped_courses)): ?>
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                  <div class="d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-3 shadow-xs" style="width: 68px; height: 68px; font-size: 2rem;">
                      <i class="bi bi-check2-all"></i>
                    </div>
                    <div class="fw-bold text-dark fs-6">All Course Shells Generated</div>
                    <p class="small text-muted mb-3" style="max-width: 420px;">Every official timetable section subject has an active, provisioned LMS course space.</p>
                    <a href="<?= BASE_PATH ?>/lms/admin/courses" class="btn btn-sm btn-primary rounded-pill px-3.5 shadow-xs hover-lift">
                      <i class="bi bi-grid-3x3-gap me-1"></i> View Course Catalog
                    </a>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($unmapped_courses as $course): ?>
                <tr>
                  <!-- Academic Level -->
                  <td class="ps-4">
                    <span class="badge <?= $course['academic_level'] === 'College' ? 'bg-primary' : 'bg-success' ?> bg-opacity-10 text-<?= $course['academic_level'] === 'College' ? 'primary' : 'success' ?> border border-<?= $course['academic_level'] === 'College' ? 'primary' : 'success' ?> border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                      <?= htmlspecialchars($course['academic_level'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>

                  <!-- Section Code -->
                  <td>
                    <span class="applicant-ref-badge font-monospace fw-bold">
                      <?= htmlspecialchars($course['section_code'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>

                  <!-- Subject Details -->
                  <td>
                    <div class="fw-bold text-dark small"><?= htmlspecialchars($course['subject_code'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-muted small" style="font-size: 0.78rem; line-height: 1.35;"><?= htmlspecialchars($course['subject_name'], ENT_QUOTES, 'UTF-8') ?></div>
                  </td>

                  <!-- Timetable Instructor -->
                  <td>
                    <span class="text-secondary small d-inline-flex align-items-center gap-1">
                      <i class="bi bi-person-badge opacity-75"></i>
                      <span><?= htmlspecialchars($course['old_instructor_string'] ?: 'TBA / Unassigned', ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                  </td>

                  <!-- Faculty Selector & Submit Form -->
                  <td class="pe-4">
                    <form action="<?= BASE_PATH ?>/lms/admin/generate" method="POST" class="d-flex align-items-center gap-2 m-0 w-100">
                      <?= getCsrfInput() ?>
                      <input type="hidden" name="academic_level" value="<?= htmlspecialchars($course['academic_level'], ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="section_id" value="<?= (int)$course['section_id'] ?>">
                      <input type="hidden" name="subject_id" value="<?= (int)$course['subject_id'] ?>">

                      <select name="faculty_user_id" class="form-select form-select-sm border rounded-pill shadow-none" required style="min-width: 220px; font-size: 0.8rem;">
                        <option value="">Choose Assigned Faculty...</option>
                        <?php foreach ($faculty_users as $faculty): ?>
                          <option value="<?= (int)$faculty['id'] ?>">
                            <?= htmlspecialchars($faculty['last_name'] . ', ' . $faculty['first_name'] . ' (' . $faculty['email'] . ')', ENT_QUOTES, 'UTF-8') ?>
                          </option>
                        <?php endforeach; ?>
                      </select>

                      <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 shadow-xs hover-lift d-inline-flex align-items-center gap-1.5 flex-shrink-0 fw-semibold">
                        <i class="bi bi-plus-circle"></i>
                        <span>Generate</span>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<?php 
if (file_exists($lmsFooter)) {
    require_once $lmsFooter;
} else {
    require_once __DIR__ . '/../../components/footer.php';
}
?>
