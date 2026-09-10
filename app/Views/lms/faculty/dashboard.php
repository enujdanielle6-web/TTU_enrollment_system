<?php require_once __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">

    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sia/lms/faculty/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
            <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">My Teaching Courses</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Left Column: Main Content -->
        <div class="col-lg-8">
            <!-- Welcome Hero Banner -->
            <div class="lms-card p-4 mb-4 border shadow-sm rounded-4 position-relative overflow-hidden" style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.08) 0%, #ffffff 100%); border-color: rgba(13, 110, 253, 0.18) !important;">
                <div class="position-absolute" style="top: -50px; right: -50px; width: 160px; height: 160px; background: var(--lms-primary-light); border-radius: 50%; opacity: 0.35; filter: blur(30px);"></div>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 position-relative z-1">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white rounded-circle p-3 text-primary shadow-sm border border-primary border-opacity-25 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="bi bi-mortarboard-fill fs-3"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h3 class="fw-bold text-dark mb-0 h5">Welcome, <?= htmlspecialchars($facultyName) ?></h3>
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1 small fw-bold">Faculty Portal</span>
                            </div>
                            <p class="text-muted small mb-0">
                                You are leading <?= count($faculty_courses) ?> active courses with <?= (int)($totalStudents ?? 0) ?> students enrolled for this academic term.
                            </p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="text-center px-3 py-2 bg-white rounded-3 shadow-xs border">
                            <span class="d-block fw-bold text-primary fs-5 lh-1"><?= count($faculty_courses) ?></span>
                            <span class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 700;">Courses</span>
                        </div>
                        <div class="text-center px-3 py-2 bg-white rounded-3 shadow-xs border">
                            <span class="d-block fw-bold text-success fs-5 lh-1"><?= (int)($totalStudents ?? 0) ?></span>
                            <span class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 700;">Students</span>
                        </div>
                        <div class="text-center px-3 py-2 bg-white rounded-3 shadow-xs border">
                            <span class="d-block fw-bold text-warning fs-5 lh-1"><?= (int)($pendingSubmissionsCount ?? 0) ?></span>
                            <span class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 700;">To Grade</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <h4 class="fw-bold mb-3 h5 text-dark mt-2">Quick Actions</h4>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <a href="/sia/lms/faculty/dashboard.php" class="lms-quick-action lms-qa-blue">
                        <div class="lms-qa-icon">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                        <div class="lms-qa-content">
                            <div class="lms-qa-title text-truncate">Teaching</div>
                            <div class="lms-qa-subtitle text-truncate">Manage classes</div>
                        </div>
                        <div class="lms-qa-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/sia/lms/faculty/calendar" class="lms-quick-action lms-qa-amber">
                        <div class="lms-qa-icon">
                            <i class="bi bi-calendar-event-fill"></i>
                        </div>
                        <div class="lms-qa-content">
                            <div class="lms-qa-title text-truncate">Calendar</div>
                            <div class="lms-qa-subtitle text-truncate">Schedules &amp; dates</div>
                        </div>
                        <div class="lms-qa-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/sia/lms/faculty/messages.php" class="lms-quick-action lms-qa-cyan">
                        <div class="lms-qa-icon">
                            <i class="bi bi-chat-dots-fill"></i>
                        </div>
                        <div class="lms-qa-content">
                            <div class="lms-qa-title text-truncate">Messages</div>
                            <div class="lms-qa-subtitle text-truncate">Student inquiries</div>
                        </div>
                        <div class="lms-qa-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/sia/lms/faculty/profile.php" class="lms-quick-action lms-qa-green">
                        <div class="lms-qa-icon">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div class="lms-qa-content">
                            <div class="lms-qa-title text-truncate">Profile</div>
                            <div class="lms-qa-subtitle text-truncate">Faculty settings</div>
                        </div>
                        <div class="lms-qa-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Teaching Courses Section -->
            <div class="d-flex justify-content-between align-items-end mb-3 mt-2">
                <div>
                    <h3 class="h5 fw-bold mb-0 text-dark">My Teaching Courses</h3>
                    <p class="text-muted small mb-0">Select a course to publish materials, create quizzes, record attendance, and evaluate grades.</p>
                </div>
                <span class="badge bg-primary rounded-pill px-3 py-2 fw-bold"><?= count($faculty_courses) ?> Active</span>
            </div>

            <?php if (empty($faculty_courses)): ?>
                <!-- No courses card -->
                <div class="lms-card p-0 position-relative overflow-hidden border shadow-sm rounded-4" style="border-color: #eef2f6 !important;">
                    <div class="row g-0 align-items-center">
                        <div class="col-md-4 bg-light p-4 d-flex justify-content-center align-items-center border-end" style="min-height: 220px;">
                            <i class="bi bi-journal-x text-muted opacity-50" style="font-size: 5rem;"></i>
                        </div>
                        <div class="col-md-8 p-4 p-md-5">
                            <h3 class="fw-bold text-dark mb-2 h5">No Assigned Courses</h3>
                            <p class="text-muted mb-3 small">
                                You are not currently assigned as an instructor to any active course sections. Please coordinate with the Registrar or Department Chairperson for teaching assignments.
                            </p>
                            <a href="/sia/lms/faculty/profile.php" class="btn btn-outline-primary px-4 py-2 rounded-pill shadow-sm fw-bold btn-sm">View Profile &rarr;</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php 
                        $gradients = [
                            'linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%)',
                            'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)',
                            'linear-gradient(135deg, #059669 0%, #10b981 100%)',
                            'linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%)',
                            'linear-gradient(135deg, #d97706 0%, #f59e0b 100%)'
                        ];
                        foreach ($faculty_courses as $idx => $course): 
                            $grad = $gradients[$idx % count($gradients)];
                            $levelBadge = ($course['academic_level'] === 'College') ? 'College' : 'SHS';
                    ?>
                        <div class="col-md-6">
                            <a href="/sia/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-dark d-block h-100">
                                <div class="lms-card h-100 transition-all shadow-sm-hover overflow-hidden border bg-white rounded-4" style="border-color: #eef2f6 !important;">
                                    <div class="p-3 text-white d-flex justify-content-between align-items-center" style="background: <?= $grad ?>;">
                                        <div class="d-flex align-items-center gap-1.5">
                                            <span class="badge bg-white text-dark fw-bold px-2.5 py-1 rounded-pill small"><?= htmlspecialchars($course['subject_code']) ?></span>
                                            <span class="badge bg-white bg-opacity-25 text-white fw-semibold px-2 py-1 rounded-pill small"><?= esc($levelBadge) ?></span>
                                        </div>
                                        <span class="small fw-semibold opacity-90"><i class="bi bi-journal-text me-1"></i><?= htmlspecialchars($course['units'] ?? 3) ?> Units</span>
                                    </div>
                                    <div class="p-3">
                                        <h4 class="h6 fw-bold text-dark text-truncate mb-1" title="<?= htmlspecialchars($course['subject_name']) ?>">
                                            <?= htmlspecialchars($course['subject_name']) ?>
                                        </h4>
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top text-muted small">
                                            <span class="text-truncate" style="max-width: 150px;">
                                                <i class="bi bi-diagram-2 me-1 text-primary"></i> <?= htmlspecialchars($course['section_code']) ?>
                                            </span>
                                            <span class="badge bg-light text-primary border fw-semibold">
                                                <i class="bi bi-people-fill me-1"></i> <?= (int)($course['enrolled_count'] ?? 0) ?> Enrolled
                                            </span>
                                        </div>
                                        <div class="mt-3 pt-2 d-flex justify-content-between align-items-center">
                                            <span class="badge bg-light text-muted border fw-normal px-2.5 py-1">
                                                <i class="bi bi-check2-circle me-1 text-success"></i> Active Term
                                            </span>
                                            <span class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-bold" style="font-size: 0.75rem;">
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

        <!-- Right Column (Sidebar Widgets) -->
        <div class="col-lg-4">
            
            <!-- Quick Summary Widget -->
            <div class="lms-card mb-4 rounded-4 border shadow-sm" style="background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%); border-color: #bfdbfe !important;">
                <div class="d-flex align-items-center gap-3 p-3">
                    <div class="rounded-circle shadow-sm d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px; background: #e0f2fe; border: 1px solid #bae6fd;">
                        <i class="bi bi-person-workspace fs-2 text-primary"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h4 class="fw-bold text-dark mb-0 fs-6">Instructor Portal</h4>
                            <span class="badge bg-primary bg-opacity-20 text-primary rounded-pill px-2 py-0 small fw-bold">Verified</span>
                        </div>
                        <p class="text-muted small mb-0 mt-0.5" style="font-size: 0.78rem;">Taguig Technological University Faculty</p>
                    </div>
                </div>
            </div>

            <!-- Submissions to Grade Widget -->
            <div class="lms-card mb-4 rounded-4 border shadow-sm" style="border-color: #eef2f6 !important;">
                <div class="lms-card-header d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 p-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box-sm bg-warning bg-opacity-10 text-warning text-dark" style="width: 32px; height: 32px; border-radius: 0.5rem; font-size: 0.95rem;">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <h4 class="lms-card-title mb-0 h6 fw-bold">Grading Queue</h4>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php if (!empty($pendingSubmissionsCount)): ?>
                            <span class="badge bg-warning bg-opacity-10 text-dark rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.65rem;"><?= $pendingSubmissionsCount ?> Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="p-3 pt-0">
                    <?php if (empty($recentSubmissions)): ?>
                        <div class="p-4 text-center text-muted small">
                            <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
                            No pending student submissions to grade!
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentSubmissions as $sub): 
                            $studentName = trim(($sub['student_first'] ?? '') . ' ' . ($sub['student_last'] ?? ''));
                            if (empty($studentName)) $studentName = 'Student';
                            $subDate = date('M d, h:i A', strtotime($sub['submitted_at']));
                        ?>
                            <a href="/sia/lms/faculty/course/<?= esc($sub['lms_course_id']) ?>/assignments/<?= esc($sub['assignment_id']) ?>/submissions" class="lms-deadline-card text-decoration-none">
                                <div class="lms-date-tile standard">
                                    <span class="date-month"><?= date('M', strtotime($sub['submitted_at'])) ?></span>
                                    <span class="date-day"><?= date('d', strtotime($sub['submitted_at'])) ?></span>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-dark small text-truncate" title="<?= htmlspecialchars($sub['assignment_title']) ?>">
                                        <?= htmlspecialchars($sub['assignment_title']) ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 mt-1 flex-wrap">
                                        <span class="badge bg-light text-secondary border px-1.5 py-0" style="font-size: 0.65rem;">
                                            <?= htmlspecialchars($sub['subject_code']) ?>
                                        </span>
                                        <span class="text-muted" style="font-size: 0.7rem;">
                                            <i class="bi bi-person me-0.5"></i><?= htmlspecialchars($studentName) ?>
                                        </span>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right lms-deadline-chevron"></i>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Course Announcements Widget -->
            <div class="lms-card mb-4 rounded-4 border shadow-sm" style="border-color: #eef2f6 !important;">
                <div class="lms-card-header border-bottom pb-3 mb-3 p-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box-sm bg-primary bg-opacity-10 text-primary" style="width: 32px; height: 32px; border-radius: 0.5rem; font-size: 0.95rem;">
                            <i class="bi bi-megaphone-fill"></i>
                        </div>
                        <div>
                            <h4 class="lms-card-title mb-0 h6 fw-bold">Course Notices</h4>
                        </div>
                    </div>
                    <?php if (!empty($faculty_courses)): ?>
                        <a href="/sia/lms/faculty/course/<?= esc($faculty_courses[0]['lms_course_id']) ?>/announcements" class="text-primary small text-decoration-none fw-semibold">VIEW &rarr;</a>
                    <?php endif; ?>
                </div>
                <div class="p-3 pt-0">
                    <?php if (empty($recentAnnouncements)): ?>
                        <div class="p-4 text-center text-muted small">
                            <i class="bi bi-chat-left-dots text-muted opacity-50 fs-3 d-block mb-1"></i>
                            No course announcements published yet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentAnnouncements as $ann): 
                            $annDate = date('M d, Y', strtotime($ann['created_at']));
                        ?>
                            <a href="/sia/lms/faculty/course/<?= esc($ann['lms_course_id']) ?>/announcements" class="lms-announcement-card text-decoration-none">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 px-2 py-0.5 fw-bold" style="font-size: 0.65rem;">
                                        <?= htmlspecialchars($ann['subject_code']) ?>
                                    </span>
                                    <span class="text-muted small" style="font-size: 0.68rem;">
                                        <i class="bi bi-clock me-1"></i><?= esc($annDate) ?>
                                    </span>
                                </div>
                                <div class="fw-bold text-dark small text-truncate mt-1" style="font-size: 0.85rem;" title="<?= htmlspecialchars($ann['title']) ?>">
                                    <?= htmlspecialchars($ann['title']) ?>
                                </div>
                                <div class="text-muted small mt-1 line-clamp-2" style="font-size: 0.74rem; line-height: 1.38; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= htmlspecialchars(strip_tags($ann['content'] ?? '')) ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
