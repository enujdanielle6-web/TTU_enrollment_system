<?php
$pageTitle = 'LMS Administration Dashboard - TTU';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
require_once __DIR__ . '/components/lms_header.php';

$activeTermStr = htmlspecialchars(($stats['active_term']['academic_year'] ?? '2026-2027') . ' ' . ($stats['active_term']['semester'] ?? 'First') . ' Sem');
$adminName = htmlspecialchars($_SESSION['user_name'] ?? 'System Administrator', ENT_QUOTES, 'UTF-8');
$hasConflicts = !empty($conflictSummary['duplicate_groups']) || !empty($conflictSummary['orphan_courses']);
?>

<main class="py-4 min-vh-100" style="background-color: #f8fafc;">
  <div class="container-fluid px-lg-5">

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

    <!-- Welcome Hero Banner (Matching LMS Portal Aesthetic) -->
    <div class="lms-card p-4 mb-4 border shadow-sm rounded-4 position-relative overflow-hidden" style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.08) 0%, #ffffff 100%); border-color: rgba(13, 110, 253, 0.18) !important;">
      <div class="position-absolute" style="top: -50px; right: -50px; width: 180px; height: 180px; background: var(--lms-primary-light, #e7f1ff); border-radius: 50%; opacity: 0.45; filter: blur(36px);"></div>
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 position-relative z-1">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-white rounded-circle p-3 text-primary shadow-sm border border-primary border-opacity-25 d-flex align-items-center justify-content-center" style="width: 58px; height: 58px; flex-shrink: 0;">
            <i class="bi bi-mortarboard-fill fs-3"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h3 class="fw-bold text-dark mb-0 h5">Welcome, <?= $adminName ?></h3>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-bold">
                LMS Governance
              </span>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                <i class="bi bi-calendar2-check me-1"></i><?= $activeTermStr ?>
              </span>
            </div>
            <p class="text-muted small mb-0">
              Leading institutional governance for <?= (int)($stats['active_courses'] ?? 0) ?> active course shells with <?= (int)($stats['active_students'] ?? 0) ?> students enrolled for this academic term.
            </p>
          </div>
        </div>

        <!-- Metric Counter Badges on Right -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="text-center px-3 py-2 bg-white rounded-3 shadow-xs border" style="min-width: 82px;">
            <span class="d-block fw-bold text-primary fs-5 lh-1"><?= (int)($stats['active_courses'] ?? 0) ?></span>
            <span class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 700;">Courses</span>
          </div>
          <div class="text-center px-3 py-2 bg-white rounded-3 shadow-xs border" style="min-width: 82px;">
            <span class="d-block fw-bold text-success fs-5 lh-1"><?= (int)($stats['active_students'] ?? 0) ?></span>
            <span class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 700;">Students</span>
          </div>
          <div class="text-center px-3 py-2 bg-white rounded-3 shadow-xs border" style="min-width: 82px;">
            <span class="d-block fw-bold <?= ($stats['unassigned_courses'] ?? 0) > 0 ? 'text-warning' : 'text-success' ?> fs-5 lh-1"><?= (int)($stats['unassigned_courses'] ?? 0) ?></span>
            <span class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 700;">TBA Shells</span>
          </div>
          <div class="text-center px-3 py-2 bg-white rounded-3 shadow-xs border" style="min-width: 82px;">
            <span class="d-block fw-bold <?= $hasConflicts ? 'text-danger' : 'text-info' ?> fs-5 lh-1"><?= $hasConflicts ? 'Alert' : '100%' ?></span>
            <span class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 700;">Sync Health</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions Header & Grid (Direct LMS Quick Action Styles) -->
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h4 class="fw-bold mb-0 h6 text-dark text-uppercase letter-spacing-1" style="font-size: 0.85rem; letter-spacing: 0.05em; color: #64748b !important;">
        <i class="bi bi-grid-fill me-1.5 text-primary"></i> Administrative Command Hub
      </h4>
    </div>
    
    <div class="row g-3 mb-4">
      <!-- 1. Course Catalog -->
      <div class="col-6 col-md-4 col-xl-2">
        <a href="/sia/admin/lms/courses" class="lms-quick-action lms-qa-blue text-decoration-none">
          <div class="lms-qa-icon">
            <i class="bi bi-journal-bookmark-fill"></i>
          </div>
          <div class="lms-qa-content">
            <div class="lms-qa-title text-truncate">Course Catalog</div>
            <div class="lms-qa-subtitle text-truncate">Inspect &amp; assign</div>
          </div>
          <div class="lms-qa-arrow">
            <i class="bi bi-arrow-right-short"></i>
          </div>
        </a>
      </div>

      <!-- 2. Sync & Conflicts -->
      <div class="col-6 col-md-4 col-xl-2">
        <a href="/sia/admin/lms/sync" class="lms-quick-action lms-qa-orange text-decoration-none">
          <div class="lms-qa-icon">
            <i class="bi bi-arrow-repeat"></i>
          </div>
          <div class="lms-qa-content">
            <div class="lms-qa-title text-truncate">Sync &amp; Conflicts</div>
            <div class="lms-qa-subtitle text-truncate">Reconciliation hub</div>
          </div>
          <div class="lms-qa-arrow">
            <i class="bi bi-arrow-right-short"></i>
          </div>
        </a>
      </div>

      <!-- 3. Content Cloner -->
      <div class="col-6 col-md-4 col-xl-2">
        <a href="/sia/admin/lms/cloner" class="lms-quick-action lms-qa-cyan text-decoration-none">
          <div class="lms-qa-icon">
            <i class="bi bi-copy"></i>
          </div>
          <div class="lms-qa-content">
            <div class="lms-qa-title text-truncate">Content Cloner</div>
            <div class="lms-qa-subtitle text-truncate">Replicate modules</div>
          </div>
          <div class="lms-qa-arrow">
            <i class="bi bi-arrow-right-short"></i>
          </div>
        </a>
      </div>

      <!-- 4. Platform Announcements -->
      <div class="col-6 col-md-4 col-xl-2">
        <a href="/sia/admin/lms/announcements" class="lms-quick-action lms-qa-red text-decoration-none">
          <div class="lms-qa-icon">
            <i class="bi bi-megaphone-fill"></i>
          </div>
          <div class="lms-qa-content">
            <div class="lms-qa-title text-truncate">Announcements</div>
            <div class="lms-qa-subtitle text-truncate">Broadcast alerts</div>
          </div>
          <div class="lms-qa-arrow">
            <i class="bi bi-arrow-right-short"></i>
          </div>
        </a>
      </div>

      <!-- 5. User Access -->
      <div class="col-6 col-md-4 col-xl-2">
        <a href="/sia/admin/lms/users" class="lms-quick-action lms-qa-green text-decoration-none">
          <div class="lms-qa-icon">
            <i class="bi bi-person-badge-fill"></i>
          </div>
          <div class="lms-qa-content">
            <div class="lms-qa-title text-truncate">User Access</div>
            <div class="lms-qa-subtitle text-truncate">Manage logins</div>
          </div>
          <div class="lms-qa-arrow">
            <i class="bi bi-arrow-right-short"></i>
          </div>
        </a>
      </div>

      <!-- 6. Term Archival -->
      <div class="col-6 col-md-4 col-xl-2">
        <a href="/sia/admin/lms/archive" class="lms-quick-action lms-qa-purple text-decoration-none">
          <div class="lms-qa-icon">
            <i class="bi bi-archive-fill"></i>
          </div>
          <div class="lms-qa-content">
            <div class="lms-qa-title text-truncate">Term Archival</div>
            <div class="lms-qa-subtitle text-truncate">Batch archive</div>
          </div>
          <div class="lms-qa-arrow">
            <i class="bi bi-arrow-right-short"></i>
          </div>
        </a>
      </div>
    </div>

    <!-- Main Two-Column Content Grid -->
    <div class="row g-4">
      
      <!-- =================================================================== -->
      <!-- LEFT COLUMN: ACTIVE COURSE SHELLS (Matching Screenshot Grid)        -->
      <!-- =================================================================== -->
      <div class="col-lg-8">
        
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div>
            <h4 class="fw-bold mb-1 h5 text-dark">Active LMS Course Shells</h4>
            <p class="text-muted small mb-0">Select an instructional course shell to inspect syllabus, review enrolled rosters, and assign faculty.</p>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary text-white rounded-pill px-3 py-1.5 fw-bold small"><?= (int)($stats['active_courses'] ?? 0) ?> Active</span>
            <a href="/sia/admin/lms/courses" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">View All &rarr;</a>
          </div>
        </div>

        <!-- Course Cards Grid -->
        <div class="row g-3">
          <?php if (empty($recentCourses)): ?>
            <div class="col-12">
              <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <i class="bi bi-journal-x text-muted fs-1 mb-2"></i>
                <h6 class="fw-bold text-dark">No Active Course Shells Found</h6>
                <p class="text-muted small mb-3">Course shells have not been provisioned for the current semester yet.</p>
                <div>
                  <a href="/sia/admin/lms/sync" class="btn btn-primary rounded-pill px-4 fw-medium">
                    <i class="bi bi-arrow-repeat me-1"></i> Run Timetable Sync
                  </a>
                </div>
              </div>
            </div>
          <?php else: ?>
            <?php 
              $colorBands = ['card-band-blue', 'card-band-purple', 'card-band-emerald', 'card-band-cyan', 'card-band-amber', 'card-band-rose'];
              $bandIdx = 0;
              foreach ($recentCourses as $course): 
                $bandClass = $colorBands[$bandIdx % count($colorBands)];
                $bandIdx++;
                $units = (int)($course['units'] ?? 3);
                $courseLevel = ($course['academic_level'] ?? 'college') === 'shs' ? 'Senior High' : 'College';
                $instructorFirst = $course['instructor_first'] ?? $course['faculty_first_name'] ?? '';
                $instructorLast = $course['instructor_last'] ?? $course['faculty_last_name'] ?? '';
                $facultyName = trim($instructorFirst . ' ' . $instructorLast);
                $hasFaculty = !empty($facultyName);
                $subjectName = $course['subject_name'] ?? $course['subject_title'] ?? $course['name'] ?? 'Course';
            ?>
              <div class="col-md-6">
                <div class="course-card-premium shadow-sm h-100 d-flex flex-column">
                  <!-- Colored Header Band (Matching Screenshot) -->
                  <div class="card-band <?= esc($bandClass) ?>">
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-white text-dark rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 0.72rem;">
                        <?= htmlspecialchars($course['subject_code']) ?>
                      </span>
                      <span class="small text-white-50 fw-medium"><?= esc($courseLevel) ?></span>
                    </div>
                    <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-2 py-0.5 small fw-semibold">
                      <i class="bi bi-layers me-1"></i><?= $units ?> Units
                    </span>
                  </div>

                  <!-- Course Card Body -->
                  <div class="p-3.5 px-4 d-flex flex-column flex-grow-1">
                    <h6 class="fw-bold text-dark mb-2 text-truncate" title="<?= htmlspecialchars($subjectName) ?>" style="font-size: 0.98rem;">
                      <?= htmlspecialchars($subjectName) ?>
                    </h6>

                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                      <div class="d-flex align-items-center gap-1.5 text-primary small fw-semibold">
                        <i class="bi bi-diagram-3-fill"></i>
                        <span><?= htmlspecialchars($course['section_code'] ?? 'Unassigned Section') ?></span>
                      </div>
                      <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small">
                        <i class="bi bi-people me-1"></i><?= (int)($course['enrolled_count'] ?? 0) ?> Enrolled
                      </span>
                    </div>

                    <!-- Instructor Dossier Strip -->
                    <div class="p-2.5 bg-light rounded-3 mb-3 border d-flex align-items-center gap-2 mt-auto">
                      <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs flex-shrink-0 <?= $hasFaculty ? 'bg-primary' : 'bg-warning' ?>" style="width: 32px; height: 32px; font-size: 0.8rem;">
                        <?= $hasFaculty ? strtoupper(substr($instructorFirst ?: $facultyName, 0, 1)) : '?' ?>
                      </div>
                      <div class="min-w-0 flex-grow-1 overflow-hidden">
                        <div class="small fw-bold text-dark text-truncate" title="<?= $hasFaculty ? htmlspecialchars($facultyName) : 'Unassigned TBA' ?>">
                          <?= $hasFaculty ? htmlspecialchars($facultyName) : 'Unassigned TBA' ?>
                        </div>
                        <div class="text-muted text-truncate" style="font-size: 0.7rem;">
                          <?= $hasFaculty ? 'Assigned Instructor' : 'Needs faculty assignment' ?>
                        </div>
                      </div>
                    </div>

                    <!-- Bottom Controls -->
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                      <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i>Active Term
                      </span>
                      <a href="/sia/admin/lms/courses/<?= (int)$course['lms_course_id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1 shadow-xs hover-lift">
                        <span>Inspect Shell</span>
                        <i class="bi bi-arrow-right-short"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Course Generator Banner Strip -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mt-4 bg-white">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 44px; height: 44px; font-size: 1.3rem;">
                <i class="bi bi-cpu-fill"></i>
              </div>
              <div>
                <h6 class="fw-bold text-dark mb-0.5">Need to provision specific timetable offerings?</h6>
                <p class="text-muted small mb-0">Use the manual course generator to deploy isolated shells for unmapped section-subject offerings.</p>
              </div>
            </div>
            <a href="/sia/admin/lms/generator" class="btn btn-outline-primary rounded-pill px-3.5 py-1.5 fw-semibold small">
              Open Generator &rarr;
            </a>
          </div>
        </div>

      </div>

      <!-- =================================================================== -->
      <!-- RIGHT COLUMN: GOVERNANCE WIDGETS & AUDIT ACTIVITY (Matching Layout) -->
      <!-- =================================================================== -->
      <div class="col-lg-4">
        
        <!-- Widget 1: Operational Governance Dossier -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-shield-lock-fill text-primary fs-5"></i>
              <h6 class="fw-bold text-dark mb-0">LMS Governance Status</h6>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-0.5 small fw-bold">Live</span>
          </div>

          <div class="bg-light p-3 rounded-3 border mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1.5">
              <span class="text-muted small">Academic Year:</span>
              <strong class="text-dark small"><?= htmlspecialchars($stats['active_term']['academic_year'] ?? '2026-2027') ?></strong>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1.5">
              <span class="text-muted small">Semester:</span>
              <strong class="text-dark small"><?= htmlspecialchars($stats['active_term']['semester'] ?? 'First') ?> Semester</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1.5">
              <span class="text-muted small">Faculty Assigned:</span>
              <strong class="text-dark small"><?= (int)($stats['active_faculty'] ?? 0) ?> Members</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small">Work Items:</span>
              <strong class="text-dark small"><?= (int)($stats['total_assignments'] ?? 0) + (int)($stats['total_quizzes'] ?? 0) ?> Tasks</strong>
            </div>
          </div>

          <a href="/sia/admin/lms/sync" class="btn btn-light border rounded-pill w-100 py-2 small fw-semibold text-dark shadow-xs d-flex align-items-center justify-content-center gap-1.5 hover-lift">
            <i class="bi bi-arrow-repeat text-primary"></i>
            <span>Run Timetable Reconciliation</span>
          </a>
        </div>

        <!-- Widget 2: Platform Announcements Live Broadcast -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-broadcast text-danger fs-5"></i>
              <h6 class="fw-bold text-dark mb-0">Active Platform Notices</h6>
            </div>
            <a href="/sia/admin/lms/announcements" class="small fw-semibold text-primary text-decoration-none">Manage &rarr;</a>
          </div>

          <?php if (empty($platformNotices)): ?>
            <div class="p-4 text-center text-muted bg-light rounded-3 border">
              <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
              <div class="fw-bold small text-dark">No Active Platform Alerts</div>
              <p class="mb-0 text-muted" style="font-size: 0.72rem;">All systems operational. No maintenance downtime or emergency advisories broadcast.</p>
            </div>
          <?php else: ?>
            <div class="d-flex flex-column gap-2 mb-3">
              <?php foreach (array_slice($platformNotices, 0, 3) as $not): 
                $sev = $not['severity'] ?? 'info';
                $icon = 'bi-info-circle-fill text-info';
                if ($sev === 'warning') $icon = 'bi-exclamation-triangle-fill text-warning';
                if ($sev === 'danger') $icon = 'bi-exclamation-octagon-fill text-danger';
                if ($sev === 'success') $icon = 'bi-check-circle-fill text-success';
              ?>
                <div class="p-2.5 bg-light rounded-3 border">
                  <div class="d-flex align-items-start gap-2">
                    <i class="bi <?= $icon ?> fs-6 mt-0.5"></i>
                    <div class="min-w-0 flex-grow-1">
                      <div class="fw-bold text-dark small text-truncate"><?= htmlspecialchars($not['title']) ?></div>
                      <div class="text-muted text-truncate" style="font-size: 0.7rem;"><?= htmlspecialchars($not['content']) ?></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <a href="/sia/admin/lms/announcements" class="btn btn-outline-danger rounded-pill w-100 py-1.5 small fw-semibold mt-2">
            <i class="bi bi-plus-circle me-1"></i> Broadcast New Notice
          </a>
        </div>

        <!-- Widget 3: Recent Administrative Audit Activity -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-clock-history text-secondary fs-5"></i>
              <h6 class="fw-bold text-dark mb-0">LMS Audit Stream</h6>
            </div>
            <a href="/sia/admin/lms/audit_logs" class="small fw-semibold text-primary text-decoration-none">Full Log &rarr;</a>
          </div>

          <?php if (empty($stats['recent_logs'])): ?>
            <div class="p-3 text-center text-muted small">No recent administrative actions recorded.</div>
          <?php else: ?>
            <div class="d-flex flex-column gap-2.5">
              <?php foreach (array_slice($stats['recent_logs'], 0, 5) as $log): 
                $timeAgo = date('M d, h:i A', strtotime($log['created_at']));
                $actor = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: 'Admin';
              ?>
                <div class="d-flex align-items-start gap-2.5 pb-2 border-bottom">
                  <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-2 flex-shrink-0 mt-0.5" style="width: 28px; height: 28px; font-size: 0.85rem;">
                    <i class="bi <?= htmlspecialchars($log['icon'] ?? 'bi-activity') ?>"></i>
                  </div>
                  <div class="min-w-0 flex-grow-1">
                    <div class="fw-semibold text-dark small text-truncate" title="<?= htmlspecialchars($log['title'] ?? 'LMS Operation') ?>">
                      <?= htmlspecialchars($log['title'] ?? 'LMS Operation') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between text-muted" style="font-size: 0.7rem;">
                      <span><?= htmlspecialchars($actor) ?></span>
                      <span><?= esc($timeAgo) ?></span>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>

    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
