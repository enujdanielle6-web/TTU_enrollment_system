<?php
$pageTitle = "Course #{$courseId} Inspection - {$course['subject_code']}";
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
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0"><?= htmlspecialchars($course['subject_code'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($course['subject_name'], ENT_QUOTES, 'UTF-8') ?></h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                Shell #<?= (int)$courseId ?>
              </span>
              <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <?= htmlspecialchars($course['section_code'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($course['academic_level'], ENT_QUOTES, 'UTF-8') ?>)
              </span>
              <?php if ($course['status'] === 'active'): ?>
                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-0.5 small fw-semibold">Active Shell</span>
              <?php else: ?>
                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2.5 py-0.5 small fw-semibold">Archived</span>
              <?php endif; ?>
            </div>
            <p class="text-muted small mb-0">Detailed course inspection, learning materials, enrolled student roster, assignments, quizzes, and instructor timetable governance.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <!-- Reassign Faculty Modal Trigger -->
          <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-medium d-inline-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#reassignFacultyModal">
            <i class="bi bi-person-gear"></i>
            <span>Reassign Faculty</span>
          </button>
          
          <!-- Status Toggle Form -->
          <form action="/sia/admin/lms/courses/<?= (int)$courseId ?>/status" method="POST" class="m-0" onsubmit="return confirm('Change status of this course shell?');">
            <?= getCsrfInput() ?>
            <?php if ($course['status'] === 'active'): ?>
              <input type="hidden" name="status" value="archived">
              <button type="submit" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-medium d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-archive"></i>
                <span>Archive Course</span>
              </button>
            <?php else: ?>
              <input type="hidden" name="status" value="active">
              <button type="submit" class="btn btn-outline-success rounded-pill px-3 py-2 fw-medium d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-check-circle"></i>
                <span>Activate Course</span>
              </button>
            <?php endif; ?>
          </form>

          <!-- Cloner Shortcut: Clone Content into this Shell -->
          <a href="/sia/admin/lms/cloner?target_id=<?= (int)$courseId ?>" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-medium d-inline-flex align-items-center gap-1.5 shadow-xs" title="Clone content into this course shell">
            <i class="bi bi-copy"></i>
            <span>Clone Content</span>
          </a>

          <a href="/sia/admin/lms/courses" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>All Courses</span>
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

    <!-- Course Overview Top Cards -->
    <div class="row g-3 mb-4">
      <!-- Assigned Instructor Card -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
          <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
            <span class="text-muted small fw-semibold text-uppercase">Assigned Course Instructor</span>
            <button type="button" class="btn btn-sm btn-link p-0 text-primary text-decoration-none" data-bs-toggle="modal" data-bs-target="#reassignFacultyModal">
              <i class="bi bi-pencil-square me-1"></i> Edit
            </button>
          </div>
          <?php if (!empty($course['instructor_first'])): ?>
            <div class="d-flex align-items-center gap-3">
              <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 52px; height: 52px;">
                <?= strtoupper(substr($course['instructor_first'], 0, 1) . substr($course['instructor_last'], 0, 1)) ?>
              </div>
              <div>
                <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($course['instructor_first'] . ' ' . $course['instructor_last'], ENT_QUOTES, 'UTF-8') ?></h5>
                <div class="text-muted small"><?= htmlspecialchars($course['instructor_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="badge bg-light text-dark border rounded-pill px-2.5 py-0.5 mt-1 small">Faculty User ID: <?= (int)$course['faculty_user_id'] ?></div>
              </div>
            </div>
          <?php else: ?>
            <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3 text-warning">
              <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Instructor is TBA / Unassigned.</strong><br>
              <span class="small text-muted">This course shell is valid but no arbitrary faculty can access or author content until an instructor is designated.</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Timetable & Scheduling Card -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
          <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
            <span class="text-muted small fw-semibold text-uppercase">Scheduling Timetable Source</span>
            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-0.5 small">
              <?= $timetable ? 'Synchronized' : 'No Schedule Row' ?>
            </span>
          </div>
          <?php if ($timetable): ?>
            <div class="row g-2 small">
              <div class="col-6"><strong>Days & Time:</strong> <?= htmlspecialchars($timetable['day'] ?? 'TBA', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($timetable['start_time'] ?? '', ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($timetable['end_time'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
              <div class="col-6"><strong>Room / Lab:</strong> <?= htmlspecialchars($timetable['room'] ?? 'TBA', ENT_QUOTES, 'UTF-8') ?></div>
              <div class="col-6"><strong>Timetable Instructor:</strong> <?= htmlspecialchars($timetable['instructor'] ?? 'TBA', ENT_QUOTES, 'UTF-8') ?></div>
              <div class="col-6"><strong>Delivery Mode:</strong> <?= htmlspecialchars($timetable['delivery_mode'] ?? 'Face-to-Face', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
          <?php else: ?>
            <p class="text-muted small mb-0">No active timetable block mapped for Section ID <?= (int)$course['academic_section_id'] ?> and Subject ID <?= (int)$course['subject_id'] ?>.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Enrolled Students Roster Card -->
    <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden mb-4">
      <div class="p-3.5 px-4 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2.5">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 36px; height: 36px; font-size: 1.15rem;">
            <i class="bi bi-people-fill"></i>
          </div>
          <div>
            <h5 class="fw-bold text-dark mb-0">Officially Enrolled Students</h5>
            <span class="text-muted small">Derived from live registrar subject enrollments (Includes regular & irregular students).</span>
          </div>
        </div>
        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1.5 fw-semibold fs-6">
          <?= count($roster) ?> Students
        </span>
      </div>

      <div class="table-responsive" style="max-height: 380px;">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light text-muted small text-uppercase sticky-top">
            <tr>
              <th class="ps-4">Student Number</th>
              <th>Student Name</th>
              <th>Email</th>
              <th>Type</th>
              <th>Section Code</th>
              <th>Enrolled Date</th>
              <th class="pe-4 text-end">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($roster)): ?>
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">No students currently enrolled in this course subject.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($roster as $s): ?>
                <tr>
                  <td class="ps-4 fw-bold text-dark"><code><?= htmlspecialchars($s['student_number'], ENT_QUOTES, 'UTF-8') ?></code></td>
                  <td class="fw-semibold text-dark"><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="small text-muted"><?= htmlspecialchars($s['email'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <?php if (($s['student_type'] ?? '') === 'Irregular' || ($s['enrollment_type'] ?? '') === 'Irregular'): ?>
                      <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-0.5 small">Irregular</span>
                    <?php else: ?>
                      <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-0.5 small">Regular</span>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge bg-light text-dark border rounded-pill px-2 py-0.5 small"><?= htmlspecialchars($s['section_code'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td class="small text-muted"><?= htmlspecialchars(date('M d, Y', strtotime($s['enrolled_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="pe-4 text-end">
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1">Enrolled</span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Course Content Grid (Modules, Assignments, Quizzes) -->
    <div class="row g-4 mb-4">
      <!-- Modules & Materials -->
      <div class="col-lg-4">
        <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden h-100">
          <div class="p-3.5 px-4 border-bottom bg-light d-flex align-items-center justify-content-between">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-folder2-open text-primary me-2"></i> Modules & Materials (<?= count($modules) ?>)</h6>
          </div>
          <div class="p-3" style="max-height: 400px; overflow-y: auto;">
            <?php if (empty($modules)): ?>
              <p class="text-muted small text-center py-4 mb-0">No instructional modules created yet.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($modules as $m): ?>
                  <li class="list-group-item px-0 py-2 border-0">
                    <div class="fw-semibold text-dark small"><i class="bi bi-folder me-1 text-primary"></i> <?= htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if (!empty($m['materials'])): ?>
                      <div class="ms-3 ps-2 border-start mt-1">
                        <?php foreach ($m['materials'] as $mat): ?>
                          <div class="small text-muted d-flex align-items-center gap-1.5 py-0.5">
                            <i class="bi bi-file-earmark-text text-secondary"></i>
                            <span class="text-truncate" style="max-width: 200px;"><?= htmlspecialchars($mat['file_name'], ENT_QUOTES, 'UTF-8') ?></span>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <div class="text-muted small ms-3" style="font-size: 0.75rem;">No materials uploaded.</div>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Assignments -->
      <div class="col-lg-4">
        <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden h-100">
          <div class="p-3.5 px-4 border-bottom bg-light d-flex align-items-center justify-content-between">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-journal-text text-warning me-2"></i> Assignments (<?= count($assignments) ?>)</h6>
          </div>
          <div class="p-3" style="max-height: 400px; overflow-y: auto;">
            <?php if (empty($assignments)): ?>
              <p class="text-muted small text-center py-4 mb-0">No assignments created yet.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($assignments as $a): ?>
                  <li class="list-group-item px-0 py-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="fw-semibold text-dark small"><?= htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8') ?></span>
                      <span class="badge bg-light text-dark border rounded-pill px-2 py-0.5 small"><?= htmlspecialchars($a['status'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mt-1" style="font-size: 0.75rem;">
                      <span>Max: <?= (float)$a['max_score'] ?> pts</span>
                      <span>Submissions: <strong><?= (int)$a['submissions_count'] ?></strong> (<?= (int)$a['graded_count'] ?> graded)</span>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Quizzes -->
      <div class="col-lg-4">
        <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden h-100">
          <div class="p-3.5 px-4 border-bottom bg-light d-flex align-items-center justify-content-between">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-patch-question text-info me-2"></i> Quizzes (<?= count($quizzes) ?>)</h6>
          </div>
          <div class="p-3" style="max-height: 400px; overflow-y: auto;">
            <?php if (empty($quizzes)): ?>
              <p class="text-muted small text-center py-4 mb-0">No quizzes authoring yet.</p>
            <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($quizzes as $q): ?>
                  <li class="list-group-item px-0 py-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="fw-semibold text-dark small"><?= htmlspecialchars($q['title'], ENT_QUOTES, 'UTF-8') ?></span>
                      <span class="badge bg-light text-dark border rounded-pill px-2 py-0.5 small"><?= htmlspecialchars($q['status'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mt-1" style="font-size: 0.75rem;">
                      <span>Questions: <?= (int)$q['questions_count'] ?></span>
                      <span>Attempts: <strong><?= (int)$q['attempts_count'] ?></strong></span>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>

<!-- Reassign Faculty Modal -->
<div class="modal fade" id="reassignFacultyModal" tabindex="-1" aria-labelledby="reassignModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <form action="/sia/admin/lms/courses/<?= (int)$courseId ?>/reassign" method="POST">
        <?= getCsrfInput() ?>
        <div class="modal-header bg-light border-bottom p-3.5 px-4">
          <h5 class="modal-title fw-bold text-dark" id="reassignModalLabel">
            <i class="bi bi-person-gear text-primary me-2"></i> Reassign Course Instructor
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <p class="small text-muted mb-3">
            Select an active faculty instructor for <strong><?= htmlspecialchars($course['subject_code'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($course['section_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>)</strong>.
          </p>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Faculty Instructor</label>
            <select name="faculty_user_id" class="form-select rounded-3 shadow-none">
              <option value="">-- Set to TBA (Unassigned) --</option>
              <?php foreach ($availableFaculty as $fac): ?>
                <option value="<?= (int)$fac['id'] ?>" <?= ((int)($course['faculty_user_id'] ?? 0) === (int)$fac['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($fac['last_name'] . ', ' . $fac['first_name'] . ' (' . $fac['email'] . ')', ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-check p-3 bg-light border rounded-3 mb-0">
            <input class="form-check-input" type="checkbox" name="sync_schedule" value="1" id="syncScheduleCheck" checked>
            <label class="form-check-label small" for="syncScheduleCheck">
              <strong>Synchronize Authoritative Scheduling Timetable</strong><br>
              <span class="text-muted" style="font-size: 0.75rem;">Keep official enrollment section offering aligned with LMS instructor to prevent faculty conflicts.</span>
            </label>
          </div>
        </div>
        <div class="modal-footer border-top bg-light p-3 px-4">
          <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Assignment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../../components/footer.php'; ?>
