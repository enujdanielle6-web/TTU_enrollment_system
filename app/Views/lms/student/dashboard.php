<?php require_once __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">

    <div class="row g-4">
        <!-- Left Column: Main Content -->
        <div class="col-lg-8">
            <!-- Next Upcoming Event -->
            <?php if (!empty($next_event)): ?>
                <div class="lms-card p-4 mb-4 bg-primary bg-opacity-10 border-0 shadow-sm position-relative overflow-hidden rounded-4">
                    <div class="position-absolute" style="top: -50px; right: -50px; width: 150px; height: 150px; background: var(--lms-primary-light); border-radius: 50%; opacity: 0.3; filter: blur(30px);"></div>
                    <div class="d-flex justify-content-between align-items-center mb-3 position-relative z-1">
                        <span class="text-primary fw-bold small text-uppercase"><i class="bi bi-calendar-event me-2"></i>Upcoming Schedule / Event</span>
                        <a href="/sia/lms/student/calendar" class="text-primary small fw-semibold text-decoration-none">View Calendar &rarr;</a>
                    </div>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 position-relative z-1">
                        <div class="d-flex align-items-center gap-4">
                            <div class="bg-white rounded-4 p-3 text-center shadow-sm" style="min-width: 75px;">
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
                        <a href="<?= htmlspecialchars($next_event['url']) ?>" class="btn btn-primary px-4 py-2 fw-bold rounded-pill shadow-sm">View Details</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="lms-card p-4 mb-4 bg-primary bg-opacity-10 border-0 shadow-sm rounded-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-white rounded-circle p-3 text-primary shadow-sm"><i class="bi bi-mortarboard-fill fs-3"></i></div>
                            <div>
                                <h3 class="fw-bold text-dark mb-1 h5">Welcome to your Student LMS Portal</h3>
                                <p class="text-muted small mb-0">You have <?= count($enrolled_courses) ?> enrolled subjects for the active term. Select a course below to view syllabus and modules.</p>
                            </div>
                        </div>
                        <a href="/sia/lms/student/my_courses.php" class="btn btn-primary rounded-pill px-4 fw-bold">My Courses</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <h4 class="fw-bold mb-3 h5 text-dark mt-2">Quick Actions</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <a href="/sia/lms/student/my_courses.php" class="text-decoration-none">
                        <div class="lms-card p-3 h-100 transition-all shadow-sm-hover d-flex align-items-center gap-3 border-0 rounded-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px;">
                                <i class="bi bi-journal-bookmark-fill fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark fs-6 lh-sm">My Courses</h6>
                                <p class="text-muted small mb-0 lh-sm" style="font-size: 0.75rem;">View enrolled subjects</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="/sia/lms/student/calendar" class="text-decoration-none">
                        <div class="lms-card p-3 h-100 transition-all shadow-sm-hover d-flex align-items-center gap-3 border-0 rounded-3">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px;">
                                <i class="bi bi-calendar-event-fill fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark fs-6 lh-sm">Calendar</h6>
                                <p class="text-muted small mb-0 lh-sm" style="font-size: 0.75rem;">Upcoming deadlines</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="/sia/lms/student/messages.php" class="text-decoration-none">
                        <div class="lms-card p-3 h-100 transition-all shadow-sm-hover d-flex align-items-center gap-3 border-0 rounded-3">
                            <div class="bg-info bg-opacity-10 text-info rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px;">
                                <i class="bi bi-chat-dots-fill fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark fs-6 lh-sm">Messages</h6>
                                <p class="text-muted small mb-0 lh-sm" style="font-size: 0.75rem;">Connect with peers</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="/sia/lms/student/profile.php" class="text-decoration-none">
                        <div class="lms-card p-3 h-100 transition-all shadow-sm-hover d-flex align-items-center gap-3 border-0 rounded-3">
                            <div class="bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px;">
                                <i class="bi bi-person-fill fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark fs-6 lh-sm">Profile</h6>
                                <p class="text-muted small mb-0 lh-sm" style="font-size: 0.75rem;">Manage your account</p>
                            </div>
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
                <div class="lms-card p-0 position-relative overflow-hidden border-0 shadow-sm rounded-4">
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
                                <div class="lms-card h-100 transition-all shadow-sm-hover overflow-hidden border bg-white rounded-4">
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
            <div class="lms-card mb-4 bg-primary bg-opacity-10 border-0 rounded-4">
                <div class="d-flex align-items-center gap-3 p-3">
                    <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                        <i class="bi bi-fire fs-2 text-danger"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-0 fs-6"><?= $streak_count ?> Day Active Streak!</h4>
                        <p class="text-muted small mb-0" style="font-size: 0.78rem;">Keep logging in and completing your coursework!</p>
                    </div>
                </div>
            </div>

            <!-- Upcoming Deadlines Widget -->
            <div class="lms-card mb-4 rounded-4 border-0 shadow-sm">
                <div class="lms-card-header d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 p-3">
                    <h4 class="lms-card-title mb-0 h6 fw-bold"><i class="bi bi-hourglass-split me-1 text-warning"></i> Upcoming Deadlines</h4>
                    <a href="/sia/lms/student/calendar" class="text-muted small text-decoration-none fw-semibold">VIEW ALL &rarr;</a>
                </div>
                <div class="p-3 pt-0">
                    <?php if (empty($upcoming_deadlines)): ?>
                        <div class="p-3 text-center text-muted small">
                            <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
                            No pending deadlines. You're all caught up!
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcoming_deadlines as $dl): 
                            $ts = strtotime($dl['due_date']);
                            $m = date('M', $ts);
                            $d = date('d', $ts);
                        ?>
                            <a href="<?= htmlspecialchars($dl['url']) ?>" class="text-decoration-none text-dark d-block mb-2 pb-2 border-bottom">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="bg-danger bg-opacity-10 text-danger rounded p-2 text-center" style="min-width: 50px;">
                                        <div class="small fw-bold text-uppercase" style="font-size: 0.7rem;"><?= $m ?></div>
                                        <div class="fs-6 fw-bold lh-1"><?= $d ?></div>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-bold text-dark small text-truncate"><?= htmlspecialchars($dl['title']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($dl['course_code']) ?> • <?= htmlspecialchars($dl['type']) ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notifications / Course Announcements Widget -->
            <div class="lms-card mb-4 rounded-4 border-0 shadow-sm">
                <div class="lms-card-header border-bottom pb-3 mb-3 p-3 d-flex justify-content-between align-items-center">
                    <h4 class="lms-card-title mb-0 h6 fw-bold"><i class="bi bi-bell-fill me-1 text-primary"></i> Course Announcements</h4>
                    <a href="/sia/lms/student/announcements" class="text-muted small text-decoration-none fw-semibold">VIEW ALL &rarr;</a>
                </div>
                <div class="p-3 pt-0">
                    <?php if (empty($recent_announcements)): ?>
                        <div class="p-3 text-center text-muted small">
                            <i class="bi bi-chat-left-dots text-muted opacity-50 fs-3 d-block mb-1"></i>
                            No course announcements yet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_announcements as $ann): ?>
                            <div class="mb-3 pb-2 border-bottom">
                                <div class="border-start border-3 border-primary ps-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <div class="fw-bold text-dark small text-truncate" style="max-width: 170px;"><?= htmlspecialchars($ann['title']) ?></div>
                                        <div class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars(date('M d', strtotime($ann['created_at']))) ?></div>
                                    </div>
                                    <div class="text-muted text-truncate small" style="font-size: 0.75rem;"><?= htmlspecialchars(strip_tags($ann['content'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

