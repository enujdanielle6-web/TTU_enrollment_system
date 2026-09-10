<?php require_once __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">

    <div class="row g-4">
        <!-- Left Column: Main Content -->
        <div class="col-lg-8">
            <!-- Next Upcoming Event -->
            <?php if (!empty($next_event)): ?>
                <div class="lms-card p-4 mb-4 border shadow-sm position-relative overflow-hidden rounded-4" style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.07) 0%, #ffffff 100%); border-color: rgba(13, 110, 253, 0.18) !important;">
                    <div class="position-absolute" style="top: -50px; right: -50px; width: 160px; height: 160px; background: var(--lms-primary-light); border-radius: 50%; opacity: 0.35; filter: blur(30px);"></div>
                    <div class="d-flex justify-content-between align-items-center mb-3 position-relative z-1">
                        <span class="text-primary fw-bold small text-uppercase"><i class="bi bi-calendar-event me-2"></i>Upcoming Schedule / Event</span>
                        <a href="/sia/lms/student/calendar" class="text-primary small fw-semibold text-decoration-none">View Calendar &rarr;</a>
                    </div>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 position-relative z-1">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-white rounded-4 p-3 text-center shadow-sm border" style="min-width: 78px; border-color: #e2e8f0 !important;">
                                <div class="text-primary fw-bold text-uppercase small mb-1"><?= htmlspecialchars($next_event['month']) ?></div>
                                <div class="fs-2 fw-bold text-dark lh-1"><?= htmlspecialchars($next_event['day']) ?></div>
                            </div>
                            <div>
                                <h3 class="fw-bold text-dark mb-1 h5"><?= htmlspecialchars($next_event['title']) ?></h3>
                                <div class="text-muted small d-flex flex-wrap gap-3 mt-1">
                                    <span><i class="bi bi-clock me-1 text-primary"></i> <?= htmlspecialchars($next_event['time']) ?></span>
                                    <span><i class="bi bi-book me-1 text-primary"></i> <?= htmlspecialchars($next_event['course']) ?></span>
                                </div>
                            </div>
                        </div>
                        <a href="<?= htmlspecialchars($next_event['url']) ?>" class="btn btn-primary px-4 py-2 fw-bold rounded-pill shadow-sm">View Details &rarr;</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="lms-card p-4 mb-4 border shadow-sm rounded-4" style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.07) 0%, #ffffff 100%); border-color: rgba(13, 110, 253, 0.18) !important;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-white rounded-circle p-3 text-primary shadow-sm border"><i class="bi bi-mortarboard-fill fs-3"></i></div>
                            <div>
                                <h3 class="fw-bold text-dark mb-1 h5">Welcome to your Student LMS Portal</h3>
                                <p class="text-muted small mb-0">You have <?= count($enrolled_courses) ?> enrolled subjects for the active term. Select a course below to view syllabus and modules.</p>
                            </div>
                        </div>
                        <a href="/sia/lms/student/my_courses.php" class="btn btn-primary rounded-pill px-4 fw-bold">My Courses &rarr;</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <h4 class="fw-bold mb-3 h5 text-dark mt-2">Quick Actions</h4>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <a href="/sia/lms/student/my_courses.php" class="lms-quick-action lms-qa-blue">
                        <div class="lms-qa-icon">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                        <div class="lms-qa-content">
                            <div class="lms-qa-title text-truncate">My Courses</div>
                            <div class="lms-qa-subtitle text-truncate">View enrolled subjects</div>
                        </div>
                        <div class="lms-qa-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/sia/lms/student/calendar" class="lms-quick-action lms-qa-amber">
                        <div class="lms-qa-icon">
                            <i class="bi bi-calendar-event-fill"></i>
                        </div>
                        <div class="lms-qa-content">
                            <div class="lms-qa-title text-truncate">Calendar</div>
                            <div class="lms-qa-subtitle text-truncate">Upcoming deadlines</div>
                        </div>
                        <div class="lms-qa-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/sia/lms/student/messages.php" class="lms-quick-action lms-qa-cyan">
                        <div class="lms-qa-icon">
                            <i class="bi bi-chat-dots-fill"></i>
                        </div>
                        <div class="lms-qa-content">
                            <div class="lms-qa-title text-truncate">Messages</div>
                            <div class="lms-qa-subtitle text-truncate">Connect with peers</div>
                        </div>
                        <div class="lms-qa-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/sia/lms/student/profile.php" class="lms-quick-action lms-qa-green">
                        <div class="lms-qa-icon">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div class="lms-qa-content">
                            <div class="lms-qa-title text-truncate">Profile</div>
                            <div class="lms-qa-subtitle text-truncate">Manage your account</div>
                        </div>
                        <div class="lms-qa-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </a>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-end mb-3 mt-2">
                <h3 class="h5 fw-bold mb-0 text-dark">My Enrolled Courses</h3>
                <span class="badge bg-primary rounded-pill px-3 py-2"><?= count($enrolled_courses) ?> Enrolled</span>
            </div>
            
            <?php if (empty($enrolled_courses)): ?>
                <!-- No courses card -->
                <div class="lms-card p-0 position-relative overflow-hidden border shadow-sm rounded-4" style="border-color: #eef2f6 !important;">
                    <div class="row g-0 align-items-center">
                        <div class="col-md-4 bg-light p-4 d-flex justify-content-center align-items-center border-end" style="min-height: 220px;">
                            <i class="bi bi-journal-x text-muted opacity-50" style="font-size: 5rem;"></i>
                        </div>
                        <div class="col-md-8 p-4 p-md-5">
                            <h3 class="fw-bold text-dark mb-2 h5">No Enrolled Courses Found</h3>
                            <p class="text-muted mb-3 small">
                                Your enrolled subjects will automatically synchronize with your LMS portal once your official enrollment and section assignments are finalized.
                            </p>
                            <a href="/sia/applicant/dashboard.php" class="btn btn-outline-primary px-4 py-2 rounded-pill shadow-sm fw-bold btn-sm">Check Admissions &rarr;</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php 
                        $gradients = [
                            'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)',
                            'linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%)',
                            'linear-gradient(135deg, #059669 0%, #10b981 100%)',
                            'linear-gradient(135deg, #d97706 0%, #f59e0b 100%)',
                            'linear-gradient(135deg, #dc2626 0%, #ef4444 100%)'
                        ];
                        foreach ($enrolled_courses as $idx => $course): 
                            $grad = $gradients[$idx % count($gradients)];
                    ?>
                        <div class="col-md-6">
                            <a href="/sia/lms/student/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-dark d-block h-100">
                                <div class="lms-card h-100 transition-all shadow-sm-hover overflow-hidden border bg-white rounded-4" style="border-color: #eef2f6 !important;">
                                    <div class="p-3 text-white d-flex justify-content-between align-items-center" style="background: <?= $grad ?>;">
                                        <span class="badge bg-white bg-opacity-25 text-white fw-bold px-3 py-1 rounded-pill small"><?= htmlspecialchars($course['code']) ?></span>
                                        <span class="small fw-semibold opacity-90"><i class="bi bi-journal-text me-1"></i><?= htmlspecialchars($course['units'] ?? 3) ?> Units</span>
                                    </div>
                                    <div class="p-3">
                                        <h4 class="h6 fw-bold text-dark text-truncate mb-1" title="<?= htmlspecialchars($course['name']) ?>">
                                            <?= htmlspecialchars($course['name']) ?>
                                        </h4>
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top text-muted small">
                                            <span class="text-truncate" style="max-width: 150px;"><i class="bi bi-person-badge me-1"></i> <?= htmlspecialchars(trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? ''))) ?></span>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($course['section_name'] ?? 'Section') ?></span>
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
            
            <!-- Streak Widget -->
            <div class="lms-card mb-4 rounded-4 border shadow-sm" style="background: linear-gradient(135deg, #fffaf0 0%, #ffffff 100%); border-color: #fed7aa !important;">
                <div class="d-flex align-items-center gap-3 p-3">
                    <div class="rounded-circle shadow-sm d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px; background: #fff5eb; border: 1px solid #fed7aa;">
                        <i class="bi bi-fire fs-2 text-danger"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h4 class="fw-bold text-dark mb-0 fs-6"><?= $streak_count ?> Day Active Streak!</h4>
                            <span class="badge bg-warning bg-opacity-20 text-warning text-dark rounded-pill px-2 py-0 small fw-bold">Active</span>
                        </div>
                        <p class="text-muted small mb-0 mt-0.5" style="font-size: 0.78rem;">Keep logging in and completing your coursework!</p>
                    </div>
                </div>
            </div>

            <!-- Upcoming Deadlines Widget -->
            <div class="lms-card mb-4 rounded-4 border shadow-sm" style="border-color: #eef2f6 !important;">
                <div class="lms-card-header d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 p-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box-sm bg-warning bg-opacity-10 text-warning text-dark" style="width: 32px; height: 32px; border-radius: 0.5rem; font-size: 0.95rem;">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <h4 class="lms-card-title mb-0 h6 fw-bold">Upcoming Deadlines</h4>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php if (!empty($upcoming_deadlines)): ?>
                            <span class="badge bg-warning bg-opacity-10 text-warning text-dark rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.65rem;"><?= count($upcoming_deadlines) ?> Pending</span>
                        <?php endif; ?>
                        <a href="/sia/lms/student/calendar" class="text-primary small text-decoration-none fw-semibold">VIEW ALL &rarr;</a>
                    </div>
                </div>
                <div class="p-3 pt-0">
                    <?php if (empty($upcoming_deadlines)): ?>
                        <div class="p-4 text-center text-muted small">
                            <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
                            No pending deadlines. You're all caught up!
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcoming_deadlines as $dl): 
                            $ts = strtotime($dl['due_date']);
                            $m = date('M', $ts);
                            $d = date('d', $ts);
                            $isUrgent = ($ts - time() < 86400 * 2);
                            $tileClass = $isUrgent ? 'urgent' : 'standard';
                            $typeLower = strtolower($dl['type'] ?? '');
                            $isQuiz = (strpos($typeLower, 'quiz') !== false);
                        ?>
                            <a href="<?= htmlspecialchars($dl['url']) ?>" class="lms-deadline-card text-decoration-none">
                                <div class="lms-date-tile <?= esc($tileClass) ?>">
                                    <span class="date-month"><?= esc($m) ?></span>
                                    <span class="date-day"><?= esc($d) ?></span>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-dark small text-truncate" title="<?= htmlspecialchars($dl['title']) ?>">
                                        <?= htmlspecialchars($dl['title']) ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 mt-1 flex-wrap">
                                        <span class="badge bg-light text-secondary border px-1.5 py-0" style="font-size: 0.65rem;">
                                            <?= htmlspecialchars($dl['course_code']) ?>
                                        </span>
                                        <?php if ($isQuiz): ?>
                                            <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-1.5 py-0" style="font-size: 0.65rem;">
                                                <i class="bi bi-pencil-square me-1"></i>Quiz
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-1.5 py-0" style="font-size: 0.65rem;">
                                                <i class="bi bi-journal-text me-1"></i>Assignment
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($isUrgent): ?>
                                            <span class="text-danger fw-semibold" style="font-size: 0.65rem;">
                                                <i class="bi bi-exclamation-circle-fill me-0.5"></i>Due Soon
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right lms-deadline-chevron"></i>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notifications / Course Announcements Widget -->
            <div class="lms-card mb-4 rounded-4 border shadow-sm" style="border-color: #eef2f6 !important;">
                <div class="lms-card-header border-bottom pb-3 mb-3 p-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box-sm bg-primary bg-opacity-10 text-primary" style="width: 32px; height: 32px; border-radius: 0.5rem; font-size: 0.95rem;">
                            <i class="bi bi-megaphone-fill"></i>
                        </div>
                        <div>
                            <h4 class="lms-card-title mb-0 h6 fw-bold">Course Announcements</h4>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php if (!empty($recent_announcements)): ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.65rem;"><?= count($recent_announcements) ?> Latest</span>
                        <?php endif; ?>
                        <?php 
                        $firstCourseId = !empty($enrolled_courses) ? ($enrolled_courses[0]['lms_course_id'] ?? 1) : 1;
                        ?>
                        <a href="/sia/lms/student/course/<?= $firstCourseId ?>/announcements" class="text-primary small text-decoration-none fw-semibold">VIEW ALL &rarr;</a>
                    </div>
                </div>
                <div class="p-3 pt-0">
                    <?php if (empty($recent_announcements)): ?>
                        <div class="p-4 text-center text-muted small">
                            <i class="bi bi-chat-left-dots text-muted opacity-50 fs-3 d-block mb-1"></i>
                            No course announcements yet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_announcements as $ann): 
                            $annCourseId = (int)($ann['lms_course_id'] ?? 1);
                            $authorName = trim(($ann['author_first'] ?? '') . ' ' . ($ann['author_last'] ?? ''));
                            if (empty($authorName)) $authorName = 'Course Instructor';
                            $annDate = date('M d, Y', strtotime($ann['created_at']));
                        ?>
                            <a href="/sia/lms/student/course/<?= $annCourseId ?>/announcements" class="lms-announcement-card text-decoration-none">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 px-2 py-0.5 fw-bold" style="font-size: 0.65rem;">
                                        <?= htmlspecialchars($ann['subject_code'] ?? 'Course') ?>
                                    </span>
                                    <span class="text-muted small" style="font-size: 0.68rem;">
                                        <i class="bi bi-clock me-1"></i><?= esc($annDate) ?>
                                    </span>
                                </div>
                                <div class="fw-bold text-dark small text-truncate mt-1" style="font-size: 0.85rem;" title="<?= htmlspecialchars($ann['title']) ?>">
                                    <?= htmlspecialchars($ann['title']) ?>
                                </div>
                                <div class="text-muted small mt-1 line-clamp-2" style="font-size: 0.74rem; line-height: 1.38; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= htmlspecialchars(strip_tags($ann['content'])) ?>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-2 pt-1.5 border-top text-muted" style="font-size: 0.7rem; border-color: #f1f5f9 !important;">
                                    <span class="text-truncate" style="max-width: 160px;">
                                        <i class="bi bi-person me-1 text-primary"></i><?= htmlspecialchars($authorName) ?>
                                    </span>
                                    <span class="text-primary fw-semibold lms-read-more" style="font-size: 0.7rem;">
                                        Read &rarr;
                                    </span>
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
