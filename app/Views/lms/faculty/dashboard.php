<?php require_once __DIR__ . '/layout_header.php'; ?>


<main class="py-4 bg-light min-vh-100 flex-grow-1">
  <div class="container-fluid px-lg-4">

    <!-- Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-mortarboard-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Welcome, <?= htmlspecialchars($facultyName) ?></h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                Faculty Instructor
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                AY 2026-2027 First Sem
              </span>
            </div>
            <p class="text-muted small mb-0">
              Leading instructional delivery for <strong class="text-dark"><?= count($faculty_courses) ?></strong> active course sections with <strong class="text-dark"><?= (int)($totalStudents ?? 0) ?></strong> enrolled students this academic term.
            </p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="<?= BASE_PATH ?>/lms/faculty/calendar" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift">
            <i class="bi bi-calendar-event text-primary"></i>
            <span>Calendar</span>
          </a>
          <a href="<?= BASE_PATH ?>/lms/faculty/messages.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift">
            <i class="bi bi-chat-dots text-primary"></i>
            <span>Messages</span>
          </a>
        </div>
      </div>
    </div>

    <!-- 4-Column Executive KPI Cards -->
    <div class="row g-3 mb-4 fade-in-up">
      <!-- Active Courses -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi h-100">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label">Active Courses</span>
            <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);">
              <i class="bi bi-journal-bookmark-fill"></i>
            </div>
          </div>
          <div class="stat-value"><?= count($faculty_courses) ?></div>
          <div class="stat-subtext text-primary">
            <i class="bi bi-check-circle-fill me-1"></i> Assigned Course Shells
          </div>
        </div>
      </div>

      <!-- Enrolled Students -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi h-100">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label">Enrolled Students</span>
            <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
              <i class="bi bi-people-fill"></i>
            </div>
          </div>
          <div class="stat-value"><?= (int)($totalStudents ?? 0) ?></div>
          <div class="stat-subtext text-success">
            <i class="bi bi-person-check-fill me-1"></i> Active Class Roster
          </div>
        </div>
      </div>

      <!-- Submissions to Grade -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi h-100">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label">Grading Queue</span>
            <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
              <i class="bi bi-hourglass-split"></i>
            </div>
          </div>
          <div class="stat-value"><?= (int)($pendingSubmissionsCount ?? 0) ?></div>
          <div class="stat-subtext <?= (((int)($pendingSubmissionsCount ?? 0)) > 0) ? 'text-warning' : 'text-muted' ?>">
            <i class="bi <?= (((int)($pendingSubmissionsCount ?? 0)) > 0) ? 'bi-exclamation-circle-fill' : 'bi-check-all' ?> me-1"></i>
            <?= (((int)($pendingSubmissionsCount ?? 0)) > 0) ? 'Awaiting Evaluation' : 'All Graded' ?>
          </div>
        </div>
      </div>

      <!-- Course Notices -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi h-100">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="stat-label">Course Notices</span>
            <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);">
              <i class="bi bi-megaphone-fill"></i>
            </div>
          </div>
          <div class="stat-value"><?= count($recentAnnouncements ?? []) ?></div>
          <div class="stat-subtext text-info">
            <i class="bi bi-broadcast me-1"></i> Platform Broadcasts
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Action Shortcuts Hub -->
    <div class="row g-3 mb-4 fade-in-up">
      <div class="col-sm-6 col-md-3">
        <a href="<?= BASE_PATH ?>/lms/faculty/dashboard.php" class="shortcut-item h-100">
          <div class="icon-box">
            <i class="bi bi-journal-bookmark-fill"></i>
          </div>
          <div>
            <div class="shortcut-title">Teaching</div>
            <div class="shortcut-desc">Manage classes &amp; modules</div>
          </div>
        </a>
      </div>
      <div class="col-sm-6 col-md-3">
        <a href="<?= BASE_PATH ?>/lms/faculty/calendar" class="shortcut-item shortcut-item-orange h-100">
          <div class="icon-box">
            <i class="bi bi-calendar-event-fill"></i>
          </div>
          <div>
            <div class="shortcut-title">Calendar</div>
            <div class="shortcut-desc">Schedules &amp; term dates</div>
          </div>
        </a>
      </div>
      <div class="col-sm-6 col-md-3">
        <a href="<?= BASE_PATH ?>/lms/faculty/messages.php" class="shortcut-item shortcut-item-cyan h-100">
          <div class="icon-box">
            <i class="bi bi-chat-dots-fill"></i>
          </div>
          <div>
            <div class="shortcut-title">Messages</div>
            <div class="shortcut-desc">Student inquiries &amp; forum</div>
          </div>
        </a>
      </div>
      <div class="col-sm-6 col-md-3">
        <a href="<?= BASE_PATH ?>/lms/faculty/profile.php" class="shortcut-item shortcut-item-success h-100">
          <div class="icon-box">
            <i class="bi bi-person-fill"></i>
          </div>
          <div>
            <div class="shortcut-title">Profile</div>
            <div class="shortcut-desc">Faculty credentials &amp; settings</div>
          </div>
        </a>
      </div>
    </div>

    <!-- Main Workspace Split -->
    <div class="row g-4">
      
      <!-- Left Column: My Teaching Courses (col-lg-8) -->
      <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-2">
            <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 32px; height: 32px;">
              <i class="bi bi-mortarboard"></i>
            </div>
            <div>
              <h2 class="h5 fw-bold text-dark mb-0">My Teaching Courses</h2>
              <div class="text-muted small" style="font-size: 0.75rem;">Manage instructional syllabus, assignments, quizzes, attendance, and grading</div>
            </div>
          </div>
          <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-bold small">
            <?= count($faculty_courses) ?> Active Shells
          </span>
        </div>

        <?php if (empty($faculty_courses)): ?>
          <!-- Clean Empty State -->
          <div class="dossier-card p-5 bg-white text-center border-0 shadow-sm rounded-4">
            <div class="d-flex align-items-center justify-content-center bg-light text-muted rounded-circle mx-auto mb-3 shadow-xs" style="width: 72px; height: 72px; font-size: 2rem;">
              <i class="bi bi-journal-x opacity-50"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">No Assigned Teaching Courses</h5>
            <p class="text-muted small mx-auto mb-3" style="max-width: 480px;">
              You are not currently assigned as an instructor to any active course sections for this academic term. Please coordinate with the Academic Department Chairperson or Registrar Office for course loading.
            </p>
            <a href="<?= BASE_PATH ?>/lms/faculty/profile.php" class="btn btn-outline-primary rounded-pill px-4 py-2 small fw-semibold">
              <i class="bi bi-person me-1"></i> View Profile &amp; Load
            </a>
          </div>
        <?php else: ?>
          <div class="row g-3">
            <?php 
              $headerGradients = [
                'linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%)', // Royal Blue
                'linear-gradient(135deg, #4f46e5 0%, #6366f1 100%)', // Indigo
                'linear-gradient(135deg, #065f46 0%, #059669 100%)', // Emerald
                'linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%)', // Cyan
                'linear-gradient(135deg, #b45309 0%, #d97706 100%)', // Amber
                'linear-gradient(135deg, #6b21a8 0%, #7e22ce 100%)'  // Purple
              ];

              foreach ($faculty_courses as $idx => $course): 
                $grad = $headerGradients[$idx % count($headerGradients)];
                $levelBadge = ($course['academic_level'] === 'College') ? 'College' : 'SHS';
            ?>
              <div class="col-md-6">
                <a href="<?= BASE_PATH ?>/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-dark d-block h-100">
                  <div class="dossier-card h-100 overflow-hidden border-0 shadow-sm hover-lift bg-white rounded-4 transition-all">
                    
                    <!-- Course Header Ribbon -->
                    <div class="p-3 px-3.5 text-white d-flex justify-content-between align-items-center" style="background: <?= $grad ?>;">
                      <div class="d-flex align-items-center gap-1.5 flex-wrap">
                        <span class="badge badge-frosted-solid fw-bold px-2.5 py-1 rounded-pill small shadow-xs">
                          <?= htmlspecialchars($course['subject_code']) ?>
                        </span>
                        <span class="badge badge-frosted-white fw-semibold px-2.5 py-1 rounded-pill small">
                          <?= esc($levelBadge) ?>
                        </span>
                      </div>
                      <span class="small fw-semibold opacity-90 text-nowrap">
                        <i class="bi bi-journal-text me-1"></i><?= htmlspecialchars($course['units'] ?? 3) ?> Units
                      </span>
                    </div>

                    <!-- Course Body -->
                    <div class="p-3.5 d-flex flex-column justify-content-between" style="min-height: 155px;">
                      <div>
                        <h3 class="h6 fw-bold text-dark text-truncate mb-2" title="<?= htmlspecialchars($course['subject_name']) ?>">
                          <?= htmlspecialchars($course['subject_name']) ?>
                        </h3>
                        
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top text-muted small">
                          <span class="applicant-ref-badge font-monospace">
                            <i class="bi bi-diagram-2 text-primary me-1"></i><?= htmlspecialchars($course['section_code']) ?>
                          </span>
                          <span class="badge bg-light text-primary border rounded-pill px-2.5 py-1 fw-semibold">
                            <i class="bi bi-people-fill me-1"></i><?= (int)($course['enrolled_count'] ?? 0) ?> Enrolled
                          </span>
                        </div>
                      </div>

                      <!-- Course Footer Action -->
                      <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small d-inline-flex align-items-center gap-1">
                          <span class="pulse-dot-green" style="width: 6px; height: 6px;"></span> Active Term
                        </span>
                        <span class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-semibold shadow-xs" style="font-size: 0.78rem;">
                          Manage <i class="bi bi-arrow-right ms-1"></i>
                        </span>
                      </div>
                    </div>

                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Right Column: Sidebar Widgets (col-lg-4) -->
      <div class="col-lg-4">
        
        <!-- Instructor Identity Card -->
        <div class="dossier-card mb-4 border-0 shadow-sm bg-white overflow-hidden rounded-4">
          <div class="d-flex align-items-center gap-3 p-3.5">
            <div class="applicant-avatar text-white fw-bold shadow-xs flex-shrink-0" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
              <?= esc($facultyInitial) ?>
            </div>
            <div class="min-w-0 flex-grow-1">
              <div class="d-flex align-items-center gap-1.5 flex-wrap">
                <h4 class="fw-bold text-dark mb-0 fs-6 text-truncate" title="<?= htmlspecialchars($facultyName) ?>">
                  <?= htmlspecialchars($facultyName) ?>
                </h4>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-0.5 small fw-semibold" style="font-size: 0.68rem;">
                  Faculty Verified
                </span>
              </div>
              <p class="text-muted small mb-0 text-truncate" style="font-size: 0.75rem;">
                <?= htmlspecialchars($facultyEmail) ?>
              </p>
              <div class="mt-1 text-muted" style="font-size: 0.7rem;">
                <span class="badge bg-light text-secondary border rounded-pill px-2 py-0">Taguig Technological University</span>
              </div>
            </div>
          </div>
          <div class="p-2.5 px-3.5 bg-light border-top d-flex justify-content-between align-items-center">
            <span class="text-muted small" style="font-size: 0.75rem;">Academic Year: <strong>2026-2027</strong></span>
            <a href="<?= BASE_PATH ?>/lms/faculty/profile.php" class="small fw-semibold text-primary text-decoration-none">
              Profile &rarr;
            </a>
          </div>
        </div>

        <!-- Grading Queue Widget -->
        <div class="dossier-card mb-4 border-0 shadow-sm bg-white overflow-hidden rounded-4">
          <div class="dossier-card-header bg-white border-bottom p-3 px-3.5 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-3" style="width: 32px; height: 32px;">
                <i class="bi bi-hourglass-split"></i>
              </div>
              <h3 class="h6 fw-bold text-dark mb-0">Grading Queue</h3>
            </div>
            <?php if (!empty($pendingSubmissionsCount)): ?>
              <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill px-2.5 py-1 fw-bold small">
                <?= (int)$pendingSubmissionsCount ?> Pending
              </span>
            <?php else: ?>
              <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-0.5 small fw-semibold">
                Clear
              </span>
            <?php endif; ?>
          </div>

          <div class="p-3">
            <?php if (empty($recentSubmissions)): ?>
              <div class="py-4 text-center text-muted small">
                <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-2" style="width: 44px; height: 44px; font-size: 1.25rem;">
                  <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="fw-bold text-dark">No Pending Submissions</div>
                <div class="text-muted" style="font-size: 0.75rem;">All student works have been evaluated and recorded.</div>
              </div>
            <?php else: ?>
              <div class="d-flex flex-column gap-2">
                <?php foreach ($recentSubmissions as $sub): 
                  $studentName = trim(($sub['student_first'] ?? '') . ' ' . ($sub['student_last'] ?? ''));
                  if (empty($studentName)) $studentName = 'Student';
                  $subDate = date('M d, h:i A', strtotime($sub['submitted_at']));
                ?>
                  <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($sub['lms_course_id']) ?>/assignments/<?= esc($sub['assignment_id']) ?>/submissions" class="d-flex align-items-center gap-2.5 p-2.5 border rounded-3 bg-light bg-opacity-50 text-decoration-none hover-lift">
                    <div class="d-flex flex-column align-items-center justify-content-center bg-white border rounded-3 p-1 flex-shrink-0" style="width: 42px; height: 42px;">
                      <span class="fw-bold text-primary" style="font-size: 0.78rem; line-height: 1;"><?= date('d', strtotime($sub['submitted_at'])) ?></span>
                      <span class="text-muted text-uppercase" style="font-size: 0.58rem;"><?= date('M', strtotime($sub['submitted_at'])) ?></span>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                      <div class="fw-bold text-dark small text-truncate" title="<?= htmlspecialchars($sub['assignment_title']) ?>">
                        <?= htmlspecialchars($sub['assignment_title']) ?>
                      </div>
                      <div class="d-flex align-items-center gap-1.5 mt-0.5 text-muted" style="font-size: 0.72rem;">
                        <span class="badge bg-light text-secondary border px-1.5 py-0">
                          <?= htmlspecialchars($sub['subject_code']) ?>
                        </span>
                        <span class="text-truncate">
                          <i class="bi bi-person me-0.5"></i><?= htmlspecialchars($studentName) ?>
                        </span>
                      </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted small"></i>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Course Notices Widget -->
        <div class="dossier-card mb-4 border-0 shadow-sm bg-white overflow-hidden rounded-4">
          <div class="dossier-card-header bg-white border-bottom p-3 px-3.5 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 32px; height: 32px;">
                <i class="bi bi-megaphone-fill"></i>
              </div>
              <h3 class="h6 fw-bold text-dark mb-0">Course Notices</h3>
            </div>
            <?php if (!empty($faculty_courses)): ?>
              <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($faculty_courses[0]['lms_course_id']) ?>/announcements" class="text-primary small text-decoration-none fw-semibold">
                Manage &rarr;
              </a>
            <?php endif; ?>
          </div>

          <div class="p-3">
            <?php if (empty($recentAnnouncements)): ?>
              <div class="py-4 text-center text-muted small">
                <div class="d-flex align-items-center justify-content-center bg-light text-muted rounded-circle mx-auto mb-2" style="width: 44px; height: 44px; font-size: 1.25rem;">
                  <i class="bi bi-chat-left-dots opacity-50"></i>
                </div>
                <div class="fw-bold text-dark">No Course Notices Published</div>
                <div class="text-muted" style="font-size: 0.75rem;">Post class announcements or syllabus reminders to your students.</div>
              </div>
            <?php else: ?>
              <div class="d-flex flex-column gap-2.5">
                <?php foreach ($recentAnnouncements as $ann): 
                  $annDate = date('M d, Y', strtotime($ann['created_at']));
                ?>
                  <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($ann['lms_course_id']) ?>/announcements" class="p-2.5 border rounded-3 bg-light bg-opacity-50 text-decoration-none d-block hover-lift">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 px-2 py-0.5 fw-bold" style="font-size: 0.65rem;">
                        <?= htmlspecialchars($ann['subject_code']) ?>
                      </span>
                      <span class="text-muted small" style="font-size: 0.68rem;">
                        <i class="bi bi-clock me-1"></i><?= esc($annDate) ?>
                      </span>
                    </div>
                    <div class="fw-bold text-dark small text-truncate" title="<?= htmlspecialchars($ann['title']) ?>">
                      <?= htmlspecialchars($ann['title']) ?>
                    </div>
                    <div class="text-muted small mt-1" style="font-size: 0.73rem; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                      <?= htmlspecialchars(strip_tags($ann['content'] ?? '')) ?>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
