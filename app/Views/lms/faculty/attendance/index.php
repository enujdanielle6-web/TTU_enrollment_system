<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Course Header & Horizontal Navigation -->
    <?php 
    $active_tab = 'attendance';
    require __DIR__ . '/../components/course_header.php'; 
    ?>

    <div id="course-tab-content" class="course-tab-content">
        <?php
        $totalSessions = count($sessions);
        $latestSession = !empty($sessions) ? $sessions[0]['session_date'] : null;
        ?>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="lms-card p-3 bg-white border-0 shadow-sm rounded-4 border-start border-4 border-success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Total Class Sessions</span>
                            <h3 class="mb-0 fw-bold mt-1 text-dark"><?= $totalSessions ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-calendar-check fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="lms-card p-3 bg-white border-0 shadow-sm rounded-4 border-start border-4 border-primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Latest Attendance</span>
                            <h4 class="mb-0 fw-bold mt-1 text-primary fs-5">
                                <?= $latestSession ? date('M d, Y', strtotime($latestSession)) : 'No sessions' ?>
                            </h4>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="lms-card p-3 bg-white border-0 shadow-sm rounded-4 border-start border-4 border-info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Class Section</span>
                            <h4 class="mb-0 fw-bold mt-1 text-dark fs-5"><?= htmlspecialchars($course['section_code']) ?></h4>
                        </div>
                        <div class="bg-info bg-opacity-10 text-info rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-diagram-2 fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Action Toolbar -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h4 class="h5 fw-bold text-dark mb-1">
                    <i class="bi bi-person-check me-2 text-success"></i>Class Attendance Records
                </h4>
                <p class="text-muted small mb-0">Record daily roll calls, track student presence, tardiness, and verified absences.</p>
            </div>
            <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance/create" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>New Session</span>
            </a>
        </div>

        <!-- Attendance Sessions Card -->
        <div class="lms-card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="p-0">
                <?php if (empty($sessions)): ?>
                    <div class="text-center py-5 p-4">
                        <i class="bi bi-calendar-event text-muted opacity-50 mb-3" style="font-size: 3.5rem;"></i>
                        <h5 class="fw-bold text-dark">No Attendance Sessions Logged</h5>
                        <p class="text-muted mb-4" style="max-width: 450px; margin: 0 auto;">
                            Create a class session to begin taking roll call for enrolled students in Section <?= htmlspecialchars($course['section_code']) ?>.
                        </p>
                        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance/create" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                            <i class="bi bi-plus-lg me-1"></i> Create First Session
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($sessions as $session): 
                            $dt = strtotime($session['session_date']);
                        ?>
                            <div class="list-group-item p-4 transition-all border-bottom hover-bg-light">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                    <div class="d-flex align-items-center gap-3 min-w-0" style="flex: 1 1 340px;">
                                        <!-- Date Badge Tile -->
                                        <div class="bg-light rounded-4 p-2.5 text-center shadow-xs border flex-shrink-0" style="min-width: 68px;">
                                            <span class="d-block text-primary fw-bold text-uppercase small" style="font-size: 0.7rem;">
                                                <?= date('M', $dt) ?>
                                            </span>
                                            <span class="d-block fs-3 fw-bold text-dark lh-1 my-0.5">
                                                <?= date('d', $dt) ?>
                                            </span>
                                            <span class="d-block text-muted" style="font-size: 0.65rem;">
                                                <?= date('Y', $dt) ?>
                                            </span>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-bold">
                                                    <?= date('l', $dt) ?>
                                                </span>
                                                <?php if (!empty($session['start_time'])): ?>
                                                    <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small">
                                                        <i class="bi bi-clock me-1"></i>
                                                        <?= date('h:i A', strtotime($session['start_time'])) ?> 
                                                        <?= esc($session['end_time'] ? ' - ' . date('h:i A', strtotime($session['end_time'])) : '') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <h5 class="fw-bold text-dark mb-0 text-truncate">
                                                <?= htmlspecialchars($session['notes'] ?: 'Regular Class Session') ?>
                                            </h5>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance/<?= esc($session['id']) ?>/edit" class="btn btn-primary rounded-pill px-4 py-1.5 fw-bold btn-sm shadow-xs d-inline-flex align-items-center gap-1.5">
                                            <i class="bi bi-check2-square"></i>
                                            <span>Mark / Review Attendance</span>
                                        </a>
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

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
