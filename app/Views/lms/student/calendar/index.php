<?php 
require_once dirname(__DIR__) . '/layout_header.php'; 

// Basic Calendar Calculations
$date = "$year-$month-01";
$firstDayOfMonth = (int)date('w', strtotime($date));
$daysInMonth = (int)date('t', strtotime($date));
$monthName = date('F', strtotime($date));

$prevMonth = date('m', strtotime("$date -1 month"));
$prevYear = date('Y', strtotime("$date -1 month"));
$nextMonth = date('m', strtotime("$date +1 month"));
$nextYear = date('Y', strtotime("$date +1 month"));

// Group events by day
$eventsByDay = [];
foreach ($events as $event) {
    $day = (int)date('d', strtotime($event['date']));
    $eventsByDay[$day][] = $event;
}

$todayDay = (int)date('d');
$todayMonth = date('m');
$todayYear = date('Y');
?>

<div class="container-fluid py-4">

    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/sia/lms/student/dashboard.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Calendar</li>
            </ol>
        </nav>
        <span class="badge bg-white border text-secondary px-3 py-1.5 rounded-pill shadow-xs small">
            <i class="bi bi-clock me-1 text-primary"></i> <?= date('l, F d, Y') ?>
        </span>
    </div>

    <div class="row g-4">

        <!-- Left Column: Main Calendar (8 Cols) -->
        <div class="col-lg-8">
            <div class="lms-calendar-card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
                
                <!-- Calendar Header Bar -->
                <div class="lms-cal-header-bar d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box-sm bg-primary bg-opacity-10 text-primary rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-calendar-event-fill fs-5"></i>
                        </div>
                        <div>
                            <h2 class="h4 fw-bold text-dark mb-0"><?= esc($monthName) ?> <?= esc($year) ?></h2>
                            <small class="text-muted"><?= count($events) ?> Scheduled <?= count($events) === 1 ? 'event' : 'events' ?> this month</small>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <!-- View Switcher -->
                        <div class="btn-group btn-group-sm bg-light p-1 rounded-pill border" role="group">
                            <button type="button" class="btn btn-sm rounded-pill active px-3 py-1" id="calViewGridBtn" title="Month Grid View">
                                <i class="bi bi-calendar-month me-1"></i>Month
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill text-muted px-3 py-1" id="calViewAgendaBtn" title="Deadlines Agenda List">
                                <i class="bi bi-list-task me-1"></i>Agenda
                            </button>
                        </div>

                        <!-- Month Nav Controls -->
                        <div class="btn-group btn-group-sm border rounded-pill overflow-hidden bg-white shadow-xs">
                            <a href="/sia/lms/student/calendar?month=<?= esc($prevMonth) ?>&year=<?= esc($prevYear) ?>" class="btn btn-white text-dark px-2.5 py-1.5" title="Previous Month">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                            <a href="/sia/lms/student/calendar?month=<?= esc($todayMonth) ?>&year=<?= esc($todayYear) ?>" class="btn btn-white text-dark px-3 py-1.5 fw-bold" title="Current Month">
                                Today
                            </a>
                            <a href="/sia/lms/student/calendar?month=<?= esc($nextMonth) ?>&year=<?= esc($nextYear) ?>" class="btn btn-white text-dark px-2.5 py-1.5" title="Next Month">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Event Category Filter Legend -->
                <div class="bg-light px-4 py-2.5 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap" id="calendarLegendFilters">
                        <span class="text-muted small fw-semibold me-1">Filter Events:</span>
                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1 active bg-white border shadow-xs fw-semibold small filter-event-btn" data-event-type="all">
                            All (<?= count($events) ?>)
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1 text-primary bg-primary bg-opacity-10 border border-primary border-opacity-25 fw-semibold small filter-event-btn" data-event-type="assignment">
                            <i class="bi bi-journal-text me-1"></i>Assignments
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1 text-warning-emphasis bg-warning bg-opacity-15 border border-warning border-opacity-40 fw-semibold small filter-event-btn" data-event-type="quiz">
                            <i class="bi bi-ui-checks me-1"></i>Quizzes
                        </button>
                    </div>
                    <small class="text-muted fst-italic">Click any event pill for details</small>
                </div>

                <!-- 1. Month Calendar Table View -->
                <div class="table-responsive" id="calMonthGridView">
                    <table class="lms-calendar-table">
                        <thead>
                            <tr>
                                <th style="width: 14.28%">Sun</th>
                                <th style="width: 14.28%">Mon</th>
                                <th style="width: 14.28%">Tue</th>
                                <th style="width: 14.28%">Wed</th>
                                <th style="width: 14.28%">Thu</th>
                                <th style="width: 14.28%">Fri</th>
                                <th style="width: 14.28%">Sat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php
                                $dayCount = 1;

                                // Blank previous month days
                                for ($i = 0; $i < $firstDayOfMonth; $i++) {
                                    echo '<td class="lms-cal-day lms-cal-day-muted"></td>';
                                }

                                // Days of month (Week 1)
                                for ($i = $firstDayOfMonth; $i < 7; $i++) {
                                    $isToday = ($dayCount == $todayDay && $month == $todayMonth && $year == $todayYear);
                                    $todayClass = $isToday ? 'lms-cal-today' : '';
                                    echo '<td class="lms-cal-day ' . $todayClass . '">';
                                    echo '<div class="d-flex justify-content-between align-items-center mb-1">';
                                    echo '<span class="lms-cal-day-num">' . $dayCount . '</span>';
                                    if ($isToday) {
                                        echo '<span class="badge bg-primary rounded-pill px-1.5 py-0 small" style="font-size: 0.6rem;">TODAY</span>';
                                    }
                                    echo '</div>';

                                    if (isset($eventsByDay[$dayCount])) {
                                        foreach ($eventsByDay[$dayCount] as $ev) {
                                            $pillClass = ($ev['type'] === 'assignment') ? 'lms-cal-event-primary' : 'lms-cal-event-warning';
                                            $iconClass = ($ev['type'] === 'assignment') ? 'bi-journal-text' : 'bi-ui-checks';
                                            
                                            echo '<div class="lms-cal-event-pill ' . $pillClass . ' cal-event-item" ';
                                            echo ' data-event-type="' . esc($ev['type']) . '"';
                                            echo ' data-title="' . htmlspecialchars($ev['title']) . '"';
                                            echo ' data-full-title="' . htmlspecialchars($ev['full_title'] ?? $ev['title']) . '"';
                                            echo ' data-course-code="' . htmlspecialchars($ev['course_code'] ?? '') . '"';
                                            echo ' data-course-name="' . htmlspecialchars($ev['course_name'] ?? '') . '"';
                                            echo ' data-date="' . esc($ev['date']) . '"';
                                            echo ' data-time="' . esc($ev['time']) . '"';
                                            echo ' data-type="' . esc(ucfirst($ev['type'])) . '"';
                                            echo ' data-desc="' . htmlspecialchars($ev['description'] ?? '') . '"';
                                            echo ' data-max-score="' . htmlspecialchars($ev['max_score'] ?? '') . '"';
                                            echo ' data-time-limit="' . htmlspecialchars($ev['time_limit'] ?? '') . '"';
                                            echo ' data-url="' . htmlspecialchars($ev['url'] ?? '#') . '"';
                                            echo ' title="' . htmlspecialchars($ev['full_title'] ?? $ev['title']) . '">';
                                            echo '<i class="bi ' . $iconClass . ' flex-shrink-0"></i>';
                                            echo '<span class="text-truncate">' . htmlspecialchars($ev['time'] . ' ' . $ev['full_title']) . '</span>';
                                            echo '</div>';
                                        }
                                    }
                                    echo '</td>';
                                    $dayCount++;
                                }
                                echo '</tr>';

                                // Remaining weeks
                                while ($dayCount <= $daysInMonth) {
                                    echo '<tr>';
                                    for ($i = 0; $i < 7; $i++) {
                                        if ($dayCount <= $daysInMonth) {
                                            $isToday = ($dayCount == $todayDay && $month == $todayMonth && $year == $todayYear);
                                            $todayClass = $isToday ? 'lms-cal-today' : '';
                                            echo '<td class="lms-cal-day ' . $todayClass . '">';
                                            echo '<div class="d-flex justify-content-between align-items-center mb-1">';
                                            echo '<span class="lms-cal-day-num">' . $dayCount . '</span>';
                                            if ($isToday) {
                                                echo '<span class="badge bg-primary rounded-pill px-1.5 py-0 small" style="font-size: 0.6rem;">TODAY</span>';
                                            }
                                            echo '</div>';

                                            if (isset($eventsByDay[$dayCount])) {
                                                foreach ($eventsByDay[$dayCount] as $ev) {
                                                    $pillClass = ($ev['type'] === 'assignment') ? 'lms-cal-event-primary' : 'lms-cal-event-warning';
                                                    $iconClass = ($ev['type'] === 'assignment') ? 'bi-journal-text' : 'bi-ui-checks';
                                                    
                                                    echo '<div class="lms-cal-event-pill ' . $pillClass . ' cal-event-item" ';
                                                    echo ' data-event-type="' . esc($ev['type']) . '"';
                                                    echo ' data-title="' . htmlspecialchars($ev['title']) . '"';
                                                    echo ' data-full-title="' . htmlspecialchars($ev['full_title'] ?? $ev['title']) . '"';
                                                    echo ' data-course-code="' . htmlspecialchars($ev['course_code'] ?? '') . '"';
                                                    echo ' data-course-name="' . htmlspecialchars($ev['course_name'] ?? '') . '"';
                                                    echo ' data-date="' . esc($ev['date']) . '"';
                                                    echo ' data-time="' . esc($ev['time']) . '"';
                                                    echo ' data-type="' . esc(ucfirst($ev['type'])) . '"';
                                                    echo ' data-desc="' . htmlspecialchars($ev['description'] ?? '') . '"';
                                                    echo ' data-max-score="' . htmlspecialchars($ev['max_score'] ?? '') . '"';
                                                    echo ' data-time-limit="' . htmlspecialchars($ev['time_limit'] ?? '') . '"';
                                                    echo ' data-url="' . htmlspecialchars($ev['url'] ?? '#') . '"';
                                                    echo ' title="' . htmlspecialchars($ev['full_title'] ?? $ev['title']) . '">';
                                                    echo '<i class="bi ' . $iconClass . ' flex-shrink-0"></i>';
                                                    echo '<span class="text-truncate">' . htmlspecialchars($ev['time'] . ' ' . $ev['full_title']) . '</span>';
                                                    echo '</div>';
                                                }
                                            }
                                            echo '</td>';
                                            $dayCount++;
                                        } else {
                                            echo '<td class="lms-cal-day lms-cal-day-muted"></td>';
                                        }
                                    }
                                    echo '</tr>';
                                }
                                ?>
                        </tbody>
                    </table>
                </div>

                <!-- 2. Agenda List View (Toggled via button) -->
                <div class="d-none" id="calAgendaListView">
                    <?php if (empty($events)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-calendar-check display-4 mb-3 d-block opacity-50"></i>
                            <h4 class="h6 fw-bold text-dark">No Events Scheduled This Month</h4>
                            <p class="small text-muted mb-0">You have no pending assignments or quizzes due in <?= esc($monthName) ?> <?= esc($year) ?>.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($events as $ev): 
                                $isQuiz = ($ev['type'] === 'quiz');
                                $badgeColor = $isQuiz ? 'warning' : 'primary';
                                $iconClass = $isQuiz ? 'bi-ui-checks' : 'bi-journal-text';
                                $dtFormatted = date('l, M d, Y \a\t h:i A', strtotime($ev['datetime']));
                            ?>
                                <div class="list-group-item p-3.5 d-flex align-items-center justify-content-between flex-wrap gap-3 cal-event-item"
                                     data-event-type="<?= esc($ev['type']) ?>"
                                     data-title="<?= htmlspecialchars($ev['title']) ?>"
                                     data-full-title="<?= htmlspecialchars($ev['full_title'] ?? $ev['title']) ?>"
                                     data-course-code="<?= htmlspecialchars($ev['course_code'] ?? '') ?>"
                                     data-course-name="<?= htmlspecialchars($ev['course_name'] ?? '') ?>"
                                     data-date="<?= esc($ev['date']) ?>"
                                     data-time="<?= esc($ev['time']) ?>"
                                     data-type="<?= esc(ucfirst($ev['type'])) ?>"
                                     data-desc="<?= htmlspecialchars($ev['description'] ?? '') ?>"
                                     data-max-score="<?= htmlspecialchars($ev['max_score'] ?? '') ?>"
                                     data-time-limit="<?= htmlspecialchars($ev['time_limit'] ?? '') ?>"
                                     data-url="<?= htmlspecialchars($ev['url'] ?? '#') ?>"
                                     style="cursor: pointer;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 p-3 text-center border bg-light" style="min-width: 65px;">
                                            <div class="fw-bold text-primary small text-uppercase"><?= date('M', strtotime($ev['date'])) ?></div>
                                            <div class="fs-4 fw-bold text-dark lh-1"><?= date('d', strtotime($ev['date'])) ?></div>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                <span class="badge bg-light text-secondary border small px-2 py-0.5">
                                                    <?= htmlspecialchars($ev['course_code']) ?>
                                                </span>
                                                <span class="badge bg-<?= $badgeColor ?> bg-opacity-10 text-<?= $badgeColor ?> rounded-pill px-2 py-0.5 small">
                                                    <i class="bi <?= $iconClass ?> me-1"></i><?= ucfirst($ev['type']) ?>
                                                </span>
                                            </div>
                                            <h4 class="h6 fw-bold text-dark mb-1"><?= htmlspecialchars($ev['title']) ?></h4>
                                            <div class="text-muted small">
                                                <i class="bi bi-clock me-1 text-primary"></i> Due <?= esc($dtFormatted) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <a href="<?= htmlspecialchars($ev['url']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                                            Open <?= ucfirst($ev['type']) ?> &rarr;
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Right Column: Sidebar Widgets (4 Cols) -->
        <div class="col-lg-4">

            <!-- Widget 1: Imminent Upcoming Deadlines -->
            <div class="lms-card mb-4 rounded-4 border shadow-sm">
                <div class="lms-card-header d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 p-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box-sm bg-warning bg-opacity-10 text-warning text-dark rounded-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <h3 class="lms-card-title mb-0 h6 fw-bold">Upcoming Deadlines</h3>
                    </div>
                    <?php if (!empty($upcomingDeadlines)): ?>
                        <span class="badge bg-warning bg-opacity-15 text-dark rounded-pill px-2.5 py-1 fw-bold small">
                            <?= count($upcomingDeadlines) ?> Pending
                        </span>
                    <?php endif; ?>
                </div>

                <div class="p-3 pt-0">
                    <?php if (empty($upcomingDeadlines)): ?>
                        <div class="p-4 text-center text-muted small">
                            <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
                            No pending deadlines. You're all caught up!
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcomingDeadlines as $dl): 
                            $ts = strtotime($dl['due_date']);
                            $m = date('M', $ts);
                            $d = date('d', $ts);
                            $isUrgent = ($ts - time() < 86400 * 2);
                            $tileClass = $isUrgent ? 'urgent' : 'standard';
                            $typeLower = strtolower($dl['type'] ?? '');
                            $isQuiz = (strpos($typeLower, 'quiz') !== false);
                        ?>
                            <a href="<?= htmlspecialchars($dl['url']) ?>" class="lms-deadline-card text-decoration-none mb-2">
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
                                            <span class="badge bg-warning bg-opacity-15 text-dark rounded-pill px-1.5 py-0" style="font-size: 0.65rem;">
                                                <i class="bi bi-pencil-square me-1"></i>Quiz
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-1.5 py-0" style="font-size: 0.65rem;">
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

            <!-- Widget 2: Weekly Class Timetable -->
            <div class="lms-card mb-4 rounded-4 border shadow-sm">
                <div class="lms-card-header d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 p-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box-sm bg-success bg-opacity-10 text-success rounded-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-calendar-week"></i>
                        </div>
                        <h3 class="lms-card-title mb-0 h6 fw-bold">Weekly Schedule</h3>
                    </div>
                    <span class="badge bg-light text-muted border small"><?= count($studentCourses ?? []) ?> Courses</span>
                </div>

                <div class="p-3 pt-0">
                    <?php if (empty($studentCourses)): ?>
                        <p class="text-muted small mb-0 p-2 text-center">No weekly classes scheduled.</p>
                    <?php else: ?>
                        <?php foreach ($studentCourses as $sc): 
                            $hasSchedule = !empty($sc['day']) && !empty($sc['start_time']) && !empty($sc['end_time']);
                            $timeStr = $hasSchedule 
                                ? date('h:i A', strtotime($sc['start_time'])) . ' - ' . date('h:i A', strtotime($sc['end_time']))
                                : 'Time TBA';
                            $dayStr = !empty($sc['day']) ? htmlspecialchars($sc['day']) : 'TBA';
                            $roomStr = !empty($sc['room']) ? htmlspecialchars($sc['room']) : 'TBA';
                        ?>
                            <div class="p-3 mb-2 rounded-3 border bg-light bg-opacity-50">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-primary text-white fw-bold px-2 py-0.5 rounded-pill small">
                                        <?= htmlspecialchars($sc['code']) ?>
                                    </span>
                                    <span class="badge bg-white text-secondary border px-2 py-0.5 small fw-semibold">
                                        <?= $dayStr ?>
                                    </span>
                                </div>
                                <div class="fw-bold text-dark small text-truncate mb-1" title="<?= htmlspecialchars($sc['name']) ?>">
                                    <?= htmlspecialchars($sc['name']) ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center text-muted small" style="font-size: 0.72rem;">
                                    <span><i class="bi bi-clock me-1 text-primary"></i><?= $timeStr ?></span>
                                    <span><i class="bi bi-geo-alt me-1 text-success"></i><?= $roomStr ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Widget 3: Calendar Tips Card -->
            <div class="lms-card rounded-4 border shadow-sm p-4" style="background: linear-gradient(145deg, #f8fafc 0%, #eff6ff 100%); border-color: #dbeafe !important;">
                <div class="d-flex align-items-start gap-3">
                    <div class="icon-box-sm bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                        <i class="bi bi-lightbulb-fill"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-1 small">Academic Tip</h4>
                        <p class="text-muted small mb-0" style="font-size: 0.75rem; line-height: 1.4;">
                            Keep track of submission cutoffs. Click any event chip in the calendar grid to review requirements and open your assignment directly.
                        </p>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Interactive Event Detail Modal -->
<div class="modal fade" id="calendarEventModal" tabindex="-1" aria-labelledby="calendarEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-3 py-1 fw-bold small" id="modalEventTypeBadge">
                        Event
                    </span>
                    <span class="badge bg-light text-secondary border px-2.5 py-1 rounded-pill small" id="modalCourseCodeBadge">
                        COURSE
                    </span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <h3 class="h5 fw-bold text-dark mb-2" id="modalEventTitle">Event Title</h3>
                <div class="text-muted small mb-3" id="modalCourseName">Course Full Name</div>

                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="row g-2 text-dark small">
                        <div class="col-6">
                            <div class="text-muted small">Due Date:</div>
                            <div class="fw-bold" id="modalEventDate">Sept 20, 2026</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Due Time:</div>
                            <div class="fw-bold text-danger" id="modalEventTime">11:59 PM</div>
                        </div>
                        <div class="col-6 mt-2" id="modalScoreCol">
                            <div class="text-muted small">Max Score:</div>
                            <div class="fw-bold text-primary" id="modalMaxScore">100 Points</div>
                        </div>
                        <div class="col-6 mt-2" id="modalTimeLimitCol">
                            <div class="text-muted small">Time Limit:</div>
                            <div class="fw-bold text-warning-emphasis" id="modalTimeLimit">60 Minutes</div>
                        </div>
                    </div>
                </div>

                <div id="modalDescWrapper" class="mb-2">
                    <div class="text-muted small fw-semibold mb-1">Instructions / Description:</div>
                    <p class="text-dark small mb-0 p-2.5 bg-white border rounded-2" id="modalEventDesc" style="max-height: 120px; overflow-y: auto;">
                        No special instructions provided.
                    </p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                <a href="#" id="modalActionBtn" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                    Open Event &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Interactivity & View Toggle JavaScript -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const calViewGridBtn = document.getElementById('calViewGridBtn');
    const calViewAgendaBtn = document.getElementById('calViewAgendaBtn');
    const calMonthGridView = document.getElementById('calMonthGridView');
    const calAgendaListView = document.getElementById('calAgendaListView');

    // Toggle Month vs Agenda View
    if (calViewGridBtn && calViewAgendaBtn) {
        calViewGridBtn.addEventListener('click', function() {
            calViewGridBtn.classList.add('active', 'btn-white');
            calViewGridBtn.classList.remove('text-muted');
            calViewAgendaBtn.classList.remove('active', 'btn-white');
            calViewAgendaBtn.classList.add('text-muted');

            if (calMonthGridView) calMonthGridView.classList.remove('d-none');
            if (calAgendaListView) calAgendaListView.classList.add('d-none');
        });

        calViewAgendaBtn.addEventListener('click', function() {
            calViewAgendaBtn.classList.add('active', 'btn-white');
            calViewAgendaBtn.classList.remove('text-muted');
            calViewGridBtn.classList.remove('active', 'btn-white');
            calViewGridBtn.classList.add('text-muted');

            if (calMonthGridView) calMonthGridView.classList.add('d-none');
            if (calAgendaListView) calAgendaListView.classList.remove('d-none');
        });
    }

    // Filter Legend Buttons
    const filterBtns = document.querySelectorAll('.filter-event-btn');
    const eventItems = document.querySelectorAll('.cal-event-item');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active', 'bg-white', 'shadow-xs'));
            this.classList.add('active', 'bg-white', 'shadow-xs');

            const type = this.getAttribute('data-event-type');
            eventItems.forEach(item => {
                const itemType = item.getAttribute('data-event-type');
                if (type === 'all' || itemType === type) {
                    item.classList.remove('d-none');
                } else {
                    item.classList.add('d-none');
                }
            });
        });
    });

    // Event Detail Modal population
    const modalEl = document.getElementById('calendarEventModal');
    if (modalEl) {
        const bsModal = new bootstrap.Modal(modalEl);

        document.addEventListener('click', function(e) {
            const eventTrigger = e.target.closest('.cal-event-item');
            if (!eventTrigger) return;

            // Don't trigger modal if user explicitly clicked direct link button inside agenda item
            if (e.target.closest('a') && !e.target.closest('.lms-cal-event-pill')) {
                return;
            }

            e.preventDefault();

            const title = eventTrigger.getAttribute('data-title') || 'Event Details';
            const courseCode = eventTrigger.getAttribute('data-course-code') || '';
            const courseName = eventTrigger.getAttribute('data-course-name') || '';
            const date = eventTrigger.getAttribute('data-date') || '';
            const time = eventTrigger.getAttribute('data-time') || '';
            const type = eventTrigger.getAttribute('data-type') || 'Event';
            const desc = eventTrigger.getAttribute('data-desc') || 'No additional instructions provided for this item.';
            const maxScore = eventTrigger.getAttribute('data-max-score');
            const timeLimit = eventTrigger.getAttribute('data-time-limit');
            const url = eventTrigger.getAttribute('data-url') || '#';

            // Populate Modal Fields
            document.getElementById('modalEventTitle').textContent = title;
            document.getElementById('modalCourseCodeBadge').textContent = courseCode;
            document.getElementById('modalCourseName').textContent = courseName;
            document.getElementById('modalEventDate').textContent = date;
            document.getElementById('modalEventTime').textContent = time;
            document.getElementById('modalEventDesc').textContent = desc;

            const typeBadge = document.getElementById('modalEventTypeBadge');
            const actionBtn = document.getElementById('modalActionBtn');

            if (type.toLowerCase() === 'quiz') {
                typeBadge.className = 'badge rounded-pill px-3 py-1 fw-bold small bg-warning bg-opacity-15 text-dark border border-warning border-opacity-50';
                typeBadge.innerHTML = '<i class="bi bi-ui-checks me-1"></i>Quiz';
                actionBtn.textContent = 'Take / View Quiz →';
            } else {
                typeBadge.className = 'badge rounded-pill px-3 py-1 fw-bold small bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25';
                typeBadge.innerHTML = '<i class="bi bi-journal-text me-1"></i>Assignment';
                actionBtn.textContent = 'View Assignment & Submit →';
            }

            actionBtn.href = url;

            // Score and time limit display toggles
            const scoreCol = document.getElementById('modalScoreCol');
            const timeLimitCol = document.getElementById('modalTimeLimitCol');

            if (maxScore) {
                document.getElementById('modalMaxScore').textContent = maxScore + ' Points';
                scoreCol.classList.remove('d-none');
            } else {
                scoreCol.classList.add('d-none');
            }

            if (timeLimit) {
                document.getElementById('modalTimeLimit').textContent = timeLimit + ' Mins';
                timeLimitCol.classList.remove('d-none');
            } else {
                timeLimitCol.classList.add('d-none');
            }

            bsModal.show();
        });
    }
});
</script>

<?php require_once dirname(__DIR__) . '/layout_footer.php'; ?>
