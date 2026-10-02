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
                <div class="stat-card-kpi h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-label">Total Class Sessions</span>
                        <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <i class="bi bi-calendar-check text-white"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $totalSessions ?></div>
                    <div class="stat-subtext text-success">
                        <i class="bi bi-calendar3 me-1"></i> Recorded Roll Calls
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card-kpi h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-label">Latest Attendance</span>
                        <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);">
                            <i class="bi bi-clock-history text-white"></i>
                        </div>
                    </div>
                    <div class="stat-value" style="font-size: 1.5rem; line-height: 1.6;">
                        <?= $latestSession ? date('M d, Y', strtotime($latestSession)) : 'No sessions' ?>
                    </div>
                    <div class="stat-subtext text-primary">
                        <i class="bi bi-check-circle-fill me-1"></i> Most Recent Log
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card-kpi h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-label">Class Section</span>
                        <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                            <i class="bi bi-diagram-2 text-white"></i>
                        </div>
                    </div>
                    <div class="stat-value font-monospace" style="font-size: 1.85rem; line-height: 1.4;">
                        <?= htmlspecialchars($course['section_code']) ?>
                    </div>
                    <div class="stat-subtext text-info">
                        <i class="bi bi-building me-1"></i> Enrolled Cohort
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
