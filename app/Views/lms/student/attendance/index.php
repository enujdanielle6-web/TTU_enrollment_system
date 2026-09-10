<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Unified Course Header & Horizontal Navigation (Option A) -->
    <?php 
    $active_tab = 'attendance';
    require __DIR__ . '/../components/course_header.php'; 
    ?>

    <div id="course-tab-content" class="course-tab-content">
        <?php
        $stats = $stats ?? ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
        $totalSessions = (int)($stats['total'] ?? 0);
        $percentage = (float)($percentage ?? 100);

        $presentCount = (int)($stats['present'] ?? 0);
        $lateCount = (int)($stats['late'] ?? 0);
        $excusedCount = (int)($stats['excused'] ?? 0);
        $absentCount = (int)($stats['absent'] ?? 0);

        $presentPct = $totalSessions > 0 ? ($presentCount / $totalSessions) * 100 : 0;
        $latePct = $totalSessions > 0 ? ($lateCount / $totalSessions) * 100 : 0;
        $excusedPct = $totalSessions > 0 ? ($excusedCount / $totalSessions) * 100 : 0;
        $absentPct = $totalSessions > 0 ? ($absentCount / $totalSessions) * 100 : 0;

        $attendedCount = $presentCount + $excusedCount;

        // Standing & Compliance Classification
        if ($totalSessions == 0) {
            $standingLabel = 'No Sessions Yet';
            $standingBadgeClass = 'bg-secondary text-white';
            $ringColor = '#94a3b8';
        } elseif ($percentage >= 90) {
            $standingLabel = 'Excellent · Compliant';
            $standingBadgeClass = 'bg-success text-white';
            $ringColor = '#10b981';
        } elseif ($percentage >= 80) {
            $standingLabel = 'Good Standing · Regular';
            $standingBadgeClass = 'bg-primary text-white';
            $ringColor = 'var(--lms-primary)';
        } elseif ($percentage >= 75) {
            $standingLabel = 'Warning · Near Limit';
            $standingBadgeClass = 'bg-warning text-dark';
            $ringColor = '#f59e0b';
        } else {
            $standingLabel = 'Critical · Risk of Drop';
            $standingBadgeClass = 'bg-danger text-white';
            $ringColor = '#ef4444';
        }
        ?>

        <!-- 1. Top Metrics Grid -->
        <div class="row g-4 mb-4">
            <!-- Overall Attendance Hero Card -->
            <div class="col-xl-4 col-lg-5">
                <div class="grade-stat-card grade-hero-card p-4 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-uppercase small fw-bold text-muted">Attendance Standing</span>
                        <span class="badge rounded-pill <?= esc($standingBadgeClass) ?> px-3 py-1 fw-bold">
                            <?= esc($standingLabel) ?>
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-4 my-2">
                        <!-- SVG Circular Progress Gauge -->
                        <div class="position-relative d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px; flex-shrink: 0;">
                            <svg class="w-100 h-100" viewBox="0 0 36 36" style="transform: rotate(-90deg);">
                                <path stroke-width="3.5" stroke="currentColor" fill="none"
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    style="stroke: #e2e8f0;" />
                                <path stroke-dasharray="<?= esc(round($percentage, 1)) ?>, 100" stroke-width="3.5" stroke-linecap="round" fill="none"
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    style="stroke: <?= esc($ringColor) ?>; transition: stroke-dasharray 0.6s ease;" />
                            </svg>
                            <div class="position-absolute text-center">
                                <span class="fw-bold fs-5 text-dark"><?= esc(round($percentage, 0)) ?>%</span>
                            </div>
                        </div>

                        <div>
                            <div class="display-6 fw-bold text-dark mb-0"><?= number_format($percentage, 1) ?>%</div>
                            <div class="text-muted small fw-semibold">
                                <i class="bi bi-check2-circle me-1 text-primary"></i> <?= esc($attendedCount) ?> of <?= esc($totalSessions) ?> Sessions Attended
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 mt-2 border-top d-flex justify-content-between align-items-center text-muted small">
                        <span><i class="bi bi-shield-check text-success me-1"></i> Policy Requirement</span>
                        <span class="fw-bold text-dark">&ge; 80% Minimum Presence</span>
                    </div>
                </div>
            </div>

            <!-- Breakdown Stat Tiles (2x2 Grid) -->
            <div class="col-xl-8 col-lg-7">
                <div class="row g-3 h-100">
                    <!-- Present -->
                    <div class="col-sm-6">
                        <div class="grade-stat-card p-3.5 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icon-box-sm bg-success bg-opacity-10 text-success">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </div>
                                        <h6 class="fw-bold mb-0 text-dark">Present</h6>
                                    </div>
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1 fw-bold">
                                        <?= round($presentPct, 0) ?>%
                                    </span>
                                </div>
                                <div class="fs-4 fw-bold text-dark mb-1">
                                    <?= esc($presentCount) ?> <span class="fs-6 fw-normal text-muted">/ <?= esc($totalSessions) ?> sessions</span>
                                </div>
                            </div>
                            <div>
                                <div class="progress mb-2" style="height: 6px; border-radius: 10px; background: #e2e8f0;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= esc(min(100, $presentPct)) ?>%;"></div>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Recorded Present</span>
                                    <span class="fw-semibold text-dark"><?= esc($presentCount) ?> roll calls</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Late -->
                    <div class="col-sm-6">
                        <div class="grade-stat-card p-3.5 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icon-box-sm bg-warning bg-opacity-10 text-warning text-dark">
                                            <i class="bi bi-clock-fill"></i>
                                        </div>
                                        <h6 class="fw-bold mb-0 text-dark">Late</h6>
                                    </div>
                                    <span class="badge bg-warning bg-opacity-10 text-warning text-dark rounded-pill px-2.5 py-1 fw-bold">
                                        <?= round($latePct, 0) ?>%
                                    </span>
                                </div>
                                <div class="fs-4 fw-bold text-dark mb-1">
                                    <?= esc($lateCount) ?> <span class="fs-6 fw-normal text-muted">/ <?= esc($totalSessions) ?> sessions</span>
                                </div>
                            </div>
                            <div>
                                <div class="progress mb-2" style="height: 6px; border-radius: 10px; background: #e2e8f0;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= esc(min(100, $latePct)) ?>%;"></div>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Tardy Entries</span>
                                    <span class="fw-semibold text-dark"><?= esc($lateCount) ?> roll calls</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Excused -->
                    <div class="col-sm-6">
                        <div class="grade-stat-card p-3.5 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icon-box-sm bg-info bg-opacity-10 text-info">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <h6 class="fw-bold mb-0 text-dark">Excused</h6>
                                    </div>
                                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2.5 py-1 fw-bold">
                                        <?= round($excusedPct, 0) ?>%
                                    </span>
                                </div>
                                <div class="fs-4 fw-bold text-dark mb-1">
                                    <?= esc($excusedCount) ?> <span class="fs-6 fw-normal text-muted">/ <?= esc($totalSessions) ?> sessions</span>
                                </div>
                            </div>
                            <div>
                                <div class="progress mb-2" style="height: 6px; border-radius: 10px; background: #e2e8f0;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?= esc(min(100, $excusedPct)) ?>%;"></div>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Official Excuses</span>
                                    <span class="fw-semibold text-dark"><?= esc($excusedCount) ?> approved</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Absent -->
                    <div class="col-sm-6">
                        <div class="grade-stat-card p-3.5 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icon-box-sm bg-danger bg-opacity-10 text-danger">
                                            <i class="bi bi-x-circle-fill"></i>
                                        </div>
                                        <h6 class="fw-bold mb-0 text-dark">Absent</h6>
                                    </div>
                                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2.5 py-1 fw-bold">
                                        <?= round($absentPct, 0) ?>%
                                    </span>
                                </div>
                                <div class="fs-4 fw-bold text-dark mb-1">
                                    <?= esc($absentCount) ?> <span class="fs-6 fw-normal text-muted">/ <?= esc($totalSessions) ?> sessions</span>
                                </div>
                            </div>
                            <div>
                                <div class="progress mb-2" style="height: 6px; border-radius: 10px; background: #e2e8f0;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?= esc(min(100, $absentPct)) ?>%;"></div>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Missed Sessions</span>
                                    <span class="fw-semibold text-dark"><?= esc($absentCount) ?> absences</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Interactive Filter Bar & Attendance Records -->
        <div class="lms-card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold text-dark mb-1"><i class="bi bi-calendar3-range me-2 text-primary"></i>Attendance History</h5>
                    <p class="text-muted small mb-0">Detailed log of all recorded roll calls, time stamps, and instructor remarks.</p>
                </div>
                
                <!-- Filter Pills -->
                <div class="d-flex flex-wrap gap-2" id="attendanceFilterGroup">
                    <button class="grade-filter-btn active" data-filter="all" type="button">
                        All Sessions <span class="badge rounded-pill bg-light text-dark ms-1"><?= esc($totalSessions) ?></span>
                    </button>
                    <button class="grade-filter-btn" data-filter="present" type="button">
                        Present <span class="badge rounded-pill bg-light text-dark ms-1"><?= esc($presentCount) ?></span>
                    </button>
                    <button class="grade-filter-btn" data-filter="late" type="button">
                        Late <span class="badge rounded-pill bg-light text-dark ms-1"><?= esc($lateCount) ?></span>
                    </button>
                    <button class="grade-filter-btn" data-filter="excused" type="button">
                        Excused <span class="badge rounded-pill bg-light text-dark ms-1"><?= esc($excusedCount) ?></span>
                    </button>
                    <button class="grade-filter-btn" data-filter="absent" type="button">
                        Absent <span class="badge rounded-pill bg-light text-dark ms-1"><?= esc($absentCount) ?></span>
                    </button>
                </div>
            </div>

            <!-- Attendance Sessions List -->
            <div id="attendanceListContainer">
                <?php if (empty($history)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x display-3 text-muted opacity-50 mb-3 d-block"></i>
                        <h5 class="fw-bold text-dark">No Attendance Records Yet</h5>
                        <p class="text-muted mb-0">Your instructor has not opened or recorded any attendance sessions for this course.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($history as $h): 
                        $status = strtolower($h['status'] ?? 'present');
                        $dateTs = strtotime($h['session_date']);
                        $month = date('M', $dateTs);
                        $day = date('d', $dateTs);
                        $fullDate = date('l, F j, Y', $dateTs);
                        $timeStr = !empty($h['start_time']) ? date('h:i A', strtotime($h['start_time'])) : null;
                        if (!empty($h['end_time'])) {
                            $timeStr .= ' - ' . date('h:i A', strtotime($h['end_time']));
                        }
                    ?>
                        <div class="assessment-item-card attendance-session-item" data-status="<?= esc($status) ?>">
                            <div class="row align-items-center g-3">
                                <!-- Date & Session Info -->
                                <div class="col-md-5">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="attendance-date-badge shadow-sm">
                                            <span class="date-month"><?= esc($month) ?></span>
                                            <span class="date-day"><?= esc($day) ?></span>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark mb-0.5"><?= esc($fullDate) ?></div>
                                            <div class="d-flex flex-wrap align-items-center gap-2 text-muted small">
                                                <?php if ($timeStr): ?>
                                                    <span><i class="bi bi-clock me-1"></i><?= esc($timeStr) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($h['notes'])): ?>
                                                    <span class="text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($h['notes']) ?>">
                                                        <i class="bi bi-bookmark me-1 text-primary"></i><?= htmlspecialchars($h['notes']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Remarks -->
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($h['remarks'])): ?>
                                            <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-normal text-truncate" style="max-width: 100%;">
                                                <i class="bi bi-chat-left-text me-1.5 text-muted"></i> <?= htmlspecialchars($h['remarks']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">
                                                <i class="bi bi-dash"></i> No remarks noted
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div class="col-md-3 text-md-end">
                                    <?php if ($status === 'present'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1.5 fw-bold">
                                            <i class="bi bi-check-circle-fill me-1"></i> Present
                                        </span>
                                    <?php elseif ($status === 'late'): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 rounded-pill px-3 py-1.5 fw-bold">
                                            <i class="bi bi-clock-fill me-1"></i> Late
                                        </span>
                                    <?php elseif ($status === 'excused'): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-1.5 fw-bold">
                                            <i class="bi bi-shield-check me-1"></i> Excused
                                        </span>
                                    <?php elseif ($status === 'absent'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1.5 fw-bold">
                                            <i class="bi bi-x-circle-fill me-1"></i> Absent
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-1.5 fw-bold">
                                            <?= htmlspecialchars(ucfirst($status)) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Institutional Policy Callout -->
        <div class="alert bg-white border rounded-4 p-4 shadow-sm mb-4">
            <div class="d-flex align-items-start gap-3">
                <div class="icon-box-sm bg-primary bg-opacity-10 text-primary mt-1">
                    <i class="bi bi-shield-exclamation"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">Institutional Attendance & Compliance Policy</h6>
                    <p class="text-muted small mb-0 lh-base">
                        Students are expected to maintain at least <strong>80% attendance</strong> across all scheduled class sessions throughout the academic semester. Unexcused absences exceeding 20% of required class contact hours may result in an administrative grade of <em>Dropped due to Absences (FDA)</em>. If any recorded status requires rectification, please submit valid excuse documentation to your course instructor.
                    </p>
                </div>
            </div>
        </div>

        <script>
            (function initAttendanceFilter() {
                const attachFilters = () => {
                    const buttons = document.querySelectorAll('#attendanceFilterGroup .grade-filter-btn');
                    const items = document.querySelectorAll('.attendance-session-item');

                    buttons.forEach(btn => {
                        btn.onclick = function() {
                            buttons.forEach(b => b.classList.remove('active'));
                            this.classList.add('active');

                            const filter = this.getAttribute('data-filter');

                            items.forEach(item => {
                                if (filter === 'all' || item.getAttribute('data-status') === filter) {
                                    item.style.display = 'block';
                                } else {
                                    item.style.display = 'none';
                                }
                            });
                        };
                    });
                };

                attachFilters();
                document.addEventListener('spa:navigated', attachFilters);
            })();
        </script>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>
