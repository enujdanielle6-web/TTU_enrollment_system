<?php
$pageTitle = 'LMS Course Generator - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Dossier Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">LMS Course Generator</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-cpu-fill me-1"></i> Course Provisioning
              </span>
              <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <?= count($unmapped_courses ?? []) ?> Pending Shells
              </span>
            </div>
            <p class="text-muted small mb-0">Map timetable class sections to designated faculty instructors to generate isolated LMS subject course shells.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="sysadmin_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Flash Notifications -->
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

    <!-- Main Dossier Card -->
    <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden mb-4">
      <div class="p-3.5 px-4 border-bottom d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2.5">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 36px; height: 36px; font-size: 1.15rem;">
            <i class="bi bi-diagram-3-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0">Unmapped Section Offerings</h2>
            <div class="text-muted small">Pair unmapped section subjects with active faculty members to deploy course spaces</div>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table dashboard-table align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th scope="col" class="ps-4" style="width: 110px;">Level</th>
              <th scope="col" style="width: 140px;">Section</th>
              <th scope="col">Subject</th>
              <th scope="col" style="width: 160px;">Timetable Instructor</th>
              <th scope="col" style="width: 260px;">Assign LMS Faculty</th>
              <th scope="col" class="text-end pe-4" style="width: 160px;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($unmapped_courses)): ?>
              <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                  <div class="d-flex flex-column align-items-center justify-content-center">
                    <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-3" style="width: 56px; height: 56px;">
                      <i class="bi bi-check2-circle fs-2"></i>
                    </div>
                    <div class="fw-bold text-dark">All Course Shells Generated</div>
                    <div class="small text-muted">Every timetable section subject has an active LMS course mapping.</div>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($unmapped_courses as $course): ?>
                <tr>
                  <td class="ps-4">
                    <span class="badge <?= $course['academic_level'] === 'College' ? 'bg-primary' : 'bg-success' ?> bg-opacity-10 text-<?= $course['academic_level'] === 'College' ? 'primary' : 'success' ?> border rounded-pill px-2.5 py-1 small fw-semibold">
                      <?= htmlspecialchars($course['academic_level'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td>
                    <span class="applicant-ref-badge fw-semibold">
                      <?= htmlspecialchars($course['section_code'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td>
                    <div class="fw-bold text-dark small"><?= htmlspecialchars($course['subject_code'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($course['subject_name'], ENT_QUOTES, 'UTF-8') ?></div>
                  </td>
                  <td>
                    <span class="text-secondary small fst-italic">
                      <i class="bi bi-person me-1"></i><?= htmlspecialchars($course['old_instructor_string'] ?: 'TBA / Unassigned', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td colspan="2" class="p-0">
                    <form action="/sia/admin/lms/generate" method="POST" class="d-flex align-items-center justify-content-between p-2 pe-4 gap-2 m-0 w-100">
                      <?= getCsrfInput() ?>
                      <input type="hidden" name="academic_level" value="<?= htmlspecialchars($course['academic_level'], ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="section_id" value="<?= (int)$course['section_id'] ?>">
                      <input type="hidden" name="subject_id" value="<?= (int)$course['subject_id'] ?>">

                      <select name="faculty_user_id" class="form-select form-select-sm border rounded-pill shadow-none" required style="min-width: 220px;">
                        <option value="">Select Faculty...</option>
                        <?php foreach ($faculty_users as $faculty): ?>
                          <option value="<?= (int)$faculty['id'] ?>">
                            <?= htmlspecialchars($faculty['last_name'] . ', ' . $faculty['first_name'] . ' (' . $faculty['email'] . ')', ENT_QUOTES, 'UTF-8') ?>
                          </option>
                        <?php endforeach; ?>
                      </select>

                      <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 py-1 shadow-sm d-inline-flex align-items-center gap-1.5 flex-shrink-0">
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

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
