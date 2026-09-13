<?php require_once __DIR__ . '/layout_header.php'; 

$totalCourses = $total_courses ?? count($enrolled_courses ?? []);
$totalUnits = $total_units ?? array_sum(array_column($enrolled_courses ?? [], 'units'));
$activeTerm = !empty($student_meta['semester']) && !empty($student_meta['school_year']) 
    ? htmlspecialchars($student_meta['semester'] . ' Semester, A.Y. ' . $student_meta['school_year'])
    : 'Academic Term 2026-2027';
$sectionCode = !empty($student_meta['section_code']) 
    ? htmlspecialchars($student_meta['section_code']) 
    : (!empty($enrolled_courses[0]['section_name']) ? htmlspecialchars($enrolled_courses[0]['section_name']) : 'Enrolled Section');
$programStrand = !empty($student_meta['strand']) ? htmlspecialchars($student_meta['strand']) : 'Undergraduate Program';
?>

<div class="container-fluid py-4">

    <!-- Hero Statistics Banner -->
    <div class="lms-hero-banner mb-4">
        <div class="row align-items-center g-4 position-relative" style="z-index: 2;">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-1 fw-bold small">
                        <i class="bi bi-mortarboard-fill me-1.5"></i><?= $programStrand ?>
                    </span>
                    <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-1 fw-bold small">
                        <i class="bi bi-people-fill me-1.5"></i>Section <?= $sectionCode ?>
                    </span>
                    <span class="badge bg-white bg-opacity-10 text-white rounded-pill px-3 py-1 small">
                        <i class="bi bi-calendar3 me-1.5"></i><?= $activeTerm ?>
                    </span>
                </div>
                <h1 class="h2 fw-bold text-white mb-2">My Enrolled Courses</h1>
                <p class="text-white text-opacity-90 mb-0" style="max-width: 600px; font-size: 0.95rem;">
                    Access your virtual classrooms, syllabus materials, interactive modules, scheduled quizzes, and weekly coursework assignments.
                </p>
            </div>
            
            <div class="col-lg-5">
                <div class="row g-2 justify-content-lg-end">
                    <div class="col-6 col-sm-4 col-lg-4">
                        <div class="lms-hero-stat-pill text-center d-flex flex-column align-items-center p-3">
                            <div class="fs-3 fw-bold text-white lh-1 mb-1"><?= $totalCourses ?></div>
                            <div class="small text-white text-opacity-80 fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Courses</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-lg-4">
                        <div class="lms-hero-stat-pill text-center d-flex flex-column align-items-center p-3">
                            <div class="fs-3 fw-bold text-white lh-1 mb-1"><?= number_format($totalUnits, 1) ?></div>
                            <div class="small text-white text-opacity-80 fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Academic Units</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4 col-lg-4">
                        <div class="lms-hero-stat-pill text-center d-flex flex-column align-items-center p-3">
                            <div class="fs-3 fw-bold text-white lh-1 mb-1"><i class="bi bi-shield-check"></i></div>
                            <div class="small text-white text-opacity-80 fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Active Status</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Controls Toolbar: Search, Filters & View Toggle -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <div class="row g-3 align-items-center justify-content-between">
            <!-- Left: Filter Tabs -->
            <div class="col-md-auto order-2 order-md-1">
                <ul class="nav nav-pills gap-1" id="courseFilterTabs">
                    <li class="nav-item">
                        <button class="nav-link active rounded-pill px-3 py-1.5 fw-semibold small" data-filter="all">
                            All Courses <span class="badge bg-white text-primary rounded-pill ms-1"><?= $totalCourses ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link text-muted bg-light border-0 rounded-pill px-3 py-1.5 fw-semibold small" data-filter="major">
                            Major / Core
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link text-muted bg-light border-0 rounded-pill px-3 py-1.5 fw-semibold small" data-filter="gened">
                            General Education
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link text-muted bg-light border-0 rounded-pill px-3 py-1.5 fw-semibold small" data-filter="completed">
                            Completed
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Right: Search Input & View Switcher -->
            <div class="col-md-auto order-1 order-md-2 ms-auto">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="input-group input-group-sm bg-light rounded-pill overflow-hidden border px-2 py-0.5" style="min-width: 240px;">
                        <span class="input-group-text bg-transparent border-0 text-muted ps-1"><i class="bi bi-search"></i></span>
                        <input type="text" id="courseSearchInput" class="form-control bg-transparent border-0 shadow-none small" placeholder="Search courses or professors...">
                        <button class="btn btn-link btn-sm text-muted text-decoration-none p-0 d-none" id="clearSearchBtn" type="button"><i class="bi bi-x-circle-fill"></i></button>
                    </div>

                    <div class="btn-group btn-group-sm bg-light p-1 rounded-pill border" role="group" aria-label="Layout view">
                        <button type="button" class="btn btn-sm rounded-pill active px-2.5 py-1" id="btnViewGrid" title="Card Grid View">
                            <i class="bi bi-grid-fill"></i>
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill text-muted px-2.5 py-1" id="btnViewList" title="Timetable Table View">
                            <i class="bi bi-table"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Empty State: Zero Enrollments in System -->
    <?php if (empty($enrolled_courses)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm border p-5">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center p-4 mb-3" style="width: 80px; height: 80px;">
                <i class="bi bi-journal-x fs-1"></i>
            </div>
            <h3 class="h5 fw-bold text-dark mb-2">No Enrolled Courses Found</h3>
            <p class="text-muted mx-auto mb-4" style="max-width: 480px;">
                You are not currently registered in any active courses for this term. Your subjects will appear automatically upon official admissions section assignment.
            </p>
            <a href="/sia/applicant/dashboard.php" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                <i class="bi bi-arrow-left me-1.5"></i>Check Admissions Status
            </a>
        </div>
    <?php else: ?>

        <!-- Empty State: Zero Search Results -->
        <div id="courseEmptySearch" class="text-center py-5 bg-white rounded-4 shadow-sm border p-5 d-none">
            <i class="bi bi-search text-muted opacity-50 display-4 mb-3 d-block"></i>
            <h4 class="h5 fw-bold text-dark mb-1">No Matching Courses Found</h4>
            <p class="text-muted small mb-3">We couldn't find any course matching your search or active filter criteria.</p>
            <button class="btn btn-outline-primary rounded-pill btn-sm px-4 fw-semibold" id="resetFiltersBtn">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset All Filters
            </button>
        </div>

        <!-- 1. Grid View (Default) -->
        <div class="row g-4" id="courseGridView">
            <?php 
                $gradientPresets = [
                    'linear-gradient(135deg, #4338ca 0%, #6366f1 100%)', // Indigo
                    'linear-gradient(135deg, #0369a1 0%, #0ea5e9 100%)', // Sky Blue
                    'linear-gradient(135deg, #047857 0%, #10b981 100%)', // Emerald
                    'linear-gradient(135deg, #b45309 0%, #f59e0b 100%)', // Amber
                    'linear-gradient(135deg, #701a75 0%, #d946ef 100%)', // Fuchsia
                    'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)'  // Teal
                ];

                foreach ($enrolled_courses as $idx => $course): 
                    $grad = $gradientPresets[$idx % count($gradientPresets)];
                    $instructorName = trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? ''));
                    if (empty($instructorName)) {
                        $instructorName = 'Faculty Instructor';
                    }
                    $initials = '';
                    $words = explode(' ', $instructorName);
                    foreach ($words as $w) {
                        if (!empty($w)) $initials .= strtoupper($w[0]);
                    }
                    $initials = substr($initials, 0, 2);

                    // Categorization helper
                    $codeUpper = strtoupper($course['code'] ?? '');
                    $isMajor = (strpos($codeUpper, 'CC') !== false || strpos($codeUpper, 'IT') !== false || strpos($codeUpper, 'CS') !== false || strpos($codeUpper, 'IS') !== false);
                    $categoryTag = $isMajor ? 'major' : 'gened';

                    // Schedule string
                    $hasSchedule = !empty($course['day']) && !empty($course['start_time']) && !empty($course['end_time']);
                    $scheduleStr = $hasSchedule 
                        ? htmlspecialchars($course['day']) . ' ' . date('h:i A', strtotime($course['start_time'])) . ' - ' . date('h:i A', strtotime($course['end_time']))
                        : 'Schedule TBA';
                    $roomStr = !empty($course['room']) ? htmlspecialchars($course['room']) : 'Room TBA';
                    $deliveryMode = htmlspecialchars($course['delivery_mode'] ?? 'Face-to-Face');
            ?>
                <div class="col-md-6 col-xl-4 course-card-wrapper" 
                     data-code="<?= esc(strtolower($course['code'])) ?>" 
                     data-name="<?= esc(strtolower($course['name'])) ?>" 
                     data-instructor="<?= esc(strtolower($instructorName)) ?>"
                     data-category="<?= esc($categoryTag) ?>">
                    
                    <div class="lms-course-card">
                        <!-- Card Header Banner -->
                        <div class="lms-course-header-banner" style="background: <?= $grad ?>;">
                            <span class="lms-course-code-badge">
                                <i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($course['code']) ?>
                            </span>
                            <span class="lms-course-units-chip">
                                <i class="bi bi-award-fill me-1"></i><?= htmlspecialchars($course['units'] ?? 3) ?> Units
                            </span>
                        </div>

                        <!-- Card Body -->
                        <div class="lms-course-body">
                            <!-- Course Title -->
                            <h3 class="h6 fw-bold text-dark mb-2 lh-base text-truncate-2" title="<?= htmlspecialchars($course['name']) ?>" style="min-height: 2.6rem;">
                                <?= htmlspecialchars($course['name']) ?>
                            </h3>

                            <!-- Schedule & Location Chips -->
                            <div class="d-flex flex-wrap gap-1.5 mb-3">
                                <span class="lms-chip chip-schedule" title="Class Schedule">
                                    <i class="bi bi-clock-fill text-primary"></i> <?= $scheduleStr ?>
                                </span>
                                <span class="lms-chip chip-room" title="Classroom Location">
                                    <i class="bi bi-geo-alt-fill text-success"></i> <?= $roomStr ?>
                                </span>
                                <span class="lms-chip chip-section" title="Section">
                                    <i class="bi bi-people-fill"></i> <?= htmlspecialchars($course['section_name'] ?? 'Section') ?>
                                </span>
                            </div>

                            <!-- Course Metrics / Activity Counters -->
                            <div class="d-flex align-items-center gap-1.5 mb-3 flex-wrap">
                                <a href="/sia/lms/student/course.php?id=<?= esc($course['lms_course_id']) ?>" class="lms-activity-badge text-decoration-none" title="Course Modules">
                                    <i class="bi bi-folder2-open text-primary"></i>
                                    <span><?= (int)($course['module_count'] ?? 0) ?> Modules</span>
                                </a>
                                <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/assignments" class="lms-activity-badge text-decoration-none" title="Assignments">
                                    <i class="bi bi-journal-text text-success"></i>
                                    <span><?= (int)($course['assignment_count'] ?? 0) ?> Assignments</span>
                                </a>
                                <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="lms-activity-badge text-decoration-none" title="Quizzes">
                                    <i class="bi bi-ui-checks text-warning"></i>
                                    <span><?= (int)($course['quiz_count'] ?? 0) ?> Quizzes</span>
                                </a>
                            </div>

                            <!-- Professor Details -->
                            <div class="d-flex align-items-center justify-content-between pt-3 mt-auto border-top">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <div class="lms-instructor-avatar">
                                        <?= esc($initials) ?>
                                    </div>
                                    <div class="text-truncate">
                                        <div class="fw-bold text-dark small text-truncate" title="<?= htmlspecialchars($instructorName) ?>">
                                            <?= htmlspecialchars($instructorName) ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Course Professor</div>
                                    </div>
                                </div>
                                <span class="badge bg-light text-secondary border small px-2 py-1"><?= $deliveryMode ?></span>
                            </div>

                            <!-- Classroom Action Buttons -->
                            <div class="mt-3 pt-2">
                                <a href="/sia/lms/student/course.php?id=<?= esc($course['lms_course_id']) ?>" class="lms-btn-classroom">
                                    <span>Enter Classroom</span>
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                                <div class="d-flex justify-content-around mt-2 pt-1">
                                    <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/assignments" class="lms-quick-jump-link">
                                        <i class="bi bi-file-earmark-text me-1"></i>Assignments
                                    </a>
                                    <span class="text-muted opacity-25">&bull;</span>
                                    <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="lms-quick-jump-link">
                                        <i class="bi bi-pencil-square me-1"></i>Quizzes
                                    </a>
                                    <span class="text-muted opacity-25">&bull;</span>
                                    <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/attendance" class="lms-quick-jump-link">
                                        <i class="bi bi-calendar-check me-1"></i>Attendance
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 2. Timetable List View (Toggled via Switcher) -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden d-none" id="courseListView">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 120px;">Code</th>
                            <th>Course Title</th>
                            <th>Weekly Schedule</th>
                            <th>Room / Facility</th>
                            <th>Units</th>
                            <th>Professor</th>
                            <th class="text-end pe-4" style="width: 160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrolled_courses as $course): 
                            $instructorName = trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? ''));
                            if (empty($instructorName)) $instructorName = 'Faculty Instructor';
                            $hasSchedule = !empty($course['day']) && !empty($course['start_time']) && !empty($course['end_time']);
                            $scheduleStr = $hasSchedule 
                                ? htmlspecialchars($course['day']) . ' ' . date('h:i A', strtotime($course['start_time'])) . ' - ' . date('h:i A', strtotime($course['end_time']))
                                : 'Schedule TBA';
                            $roomStr = !empty($course['room']) ? htmlspecialchars($course['room']) : 'TBA';
                            $codeUpper = strtoupper($course['code'] ?? '');
                            $isMajor = (strpos($codeUpper, 'CC') !== false || strpos($codeUpper, 'IT') !== false || strpos($codeUpper, 'CS') !== false);
                            $categoryTag = $isMajor ? 'major' : 'gened';
                        ?>
                            <tr class="course-table-row"
                                data-code="<?= esc(strtolower($course['code'])) ?>" 
                                data-name="<?= esc(strtolower($course['name'])) ?>" 
                                data-instructor="<?= esc(strtolower($instructorName)) ?>"
                                data-category="<?= esc($categoryTag) ?>">
                                <td class="ps-4">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-bold px-2.5 py-1.5 rounded-pill">
                                        <?= htmlspecialchars($course['code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($course['name']) ?></div>
                                    <div class="small text-muted d-flex gap-2 align-items-center mt-0.5">
                                        <span><?= (int)($course['module_count'] ?? 0) ?> modules</span>
                                        <span>&bull;</span>
                                        <span><?= (int)($course['assignment_count'] ?? 0) ?> assignments</span>
                                        <span>&bull;</span>
                                        <span><?= (int)($course['quiz_count'] ?? 0) ?> quizzes</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="lms-chip chip-schedule">
                                        <i class="bi bi-clock-fill text-primary"></i> <?= $scheduleStr ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="lms-chip chip-room">
                                        <i class="bi bi-geo-alt-fill text-success"></i> <?= $roomStr ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-secondary"><?= htmlspecialchars($course['units'] ?? 3) ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small"><?= htmlspecialchars($instructorName) ?></div>
                                    <div class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($course['section_name'] ?? 'Section') ?></div>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="/sia/lms/student/course.php?id=<?= esc($course['lms_course_id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold">
                                        Open Room &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

</div>

<!-- Client-Side Search, Filter & Layout Toggle Scripts -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('courseSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    const filterTabs = document.querySelectorAll('#courseFilterTabs [data-filter]');
    const cardWrappers = document.querySelectorAll('.course-card-wrapper');
    const tableRows = document.querySelectorAll('.course-table-row');
    const emptySearch = document.getElementById('courseEmptySearch');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    const btnViewGrid = document.getElementById('btnViewGrid');
    const btnViewList = document.getElementById('btnViewList');
    const gridView = document.getElementById('courseGridView');
    const listView = document.getElementById('courseListView');

    let activeFilter = 'all';

    function applyFilterAndSearch() {
        const query = (searchInput.value || '').trim().toLowerCase();
        if (query.length > 0) {
            clearBtn.classList.remove('d-none');
        } else {
            clearBtn.classList.add('d-none');
        }

        let visibleCount = 0;

        // Filter Grid Cards
        cardWrappers.forEach(card => {
            const code = card.getAttribute('data-code') || '';
            const name = card.getAttribute('data-name') || '';
            const instructor = card.getAttribute('data-instructor') || '';
            const category = card.getAttribute('data-category') || '';

            const matchesQuery = query === '' || code.includes(query) || name.includes(query) || instructor.includes(query);
            let matchesCategory = false;
            if (activeFilter === 'all') {
                matchesCategory = true;
            } else if (activeFilter === 'major') {
                matchesCategory = (category === 'major');
            } else if (activeFilter === 'gened') {
                matchesCategory = (category === 'gened');
            } else if (activeFilter === 'completed') {
                matchesCategory = false; // All active semester courses are in-progress
            }

            if (matchesQuery && matchesCategory) {
                card.classList.remove('d-none');
                visibleCount++;
            } else {
                card.classList.add('d-none');
            }
        });

        // Filter Table Rows
        tableRows.forEach(row => {
            const code = row.getAttribute('data-code') || '';
            const name = row.getAttribute('data-name') || '';
            const instructor = row.getAttribute('data-instructor') || '';
            const category = row.getAttribute('data-category') || '';

            const matchesQuery = query === '' || code.includes(query) || name.includes(query) || instructor.includes(query);
            let matchesCategory = false;
            if (activeFilter === 'all') {
                matchesCategory = true;
            } else if (activeFilter === 'major') {
                matchesCategory = (category === 'major');
            } else if (activeFilter === 'gened') {
                matchesCategory = (category === 'gened');
            } else if (activeFilter === 'completed') {
                matchesCategory = false;
            }

            if (matchesQuery && matchesCategory) {
                row.classList.remove('d-none');
            } else {
                row.classList.add('d-none');
            }
        });

        // Toggle Empty Search Alert
        if (emptySearch) {
            if (visibleCount === 0 && (cardWrappers.length > 0)) {
                emptySearch.classList.remove('d-none');
                if (gridView) gridView.classList.add('d-none');
                if (listView) listView.classList.add('d-none');
            } else {
                emptySearch.classList.add('d-none');
                // Restore current view
                const isGridActive = btnViewGrid && btnViewGrid.classList.contains('active');
                if (isGridActive) {
                    if (gridView) gridView.classList.remove('d-none');
                    if (listView) listView.classList.add('d-none');
                } else {
                    if (gridView) gridView.classList.add('d-none');
                    if (listView) listView.classList.remove('d-none');
                }
            }
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilterAndSearch);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            applyFilterAndSearch();
            searchInput.focus();
        });
    }

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            filterTabs.forEach(t => {
                t.classList.remove('active', 'bg-primary', 'text-white');
                t.classList.add('text-muted', 'bg-light');
            });
            this.classList.add('active', 'bg-primary', 'text-white');
            this.classList.remove('text-muted', 'bg-light');
            activeFilter = this.getAttribute('data-filter') || 'all';
            applyFilterAndSearch();
        });
    });

    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            activeFilter = 'all';
            filterTabs.forEach(t => {
                if (t.getAttribute('data-filter') === 'all') {
                    t.classList.add('active', 'bg-primary', 'text-white');
                    t.classList.remove('text-muted', 'bg-light');
                } else {
                    t.classList.remove('active', 'bg-primary', 'text-white');
                    t.classList.add('text-muted', 'bg-light');
                }
            });
            applyFilterAndSearch();
        });
    }

    // View Switcher (Grid vs Table)
    function switchView(mode) {
        if (mode === 'list') {
            if (btnViewList) {
                btnViewList.classList.add('active', 'btn-white', 'shadow-sm');
                btnViewList.classList.remove('text-muted');
            }
            if (btnViewGrid) {
                btnViewGrid.classList.remove('active', 'btn-white', 'shadow-sm');
                btnViewGrid.classList.add('text-muted');
            }
            if (gridView) gridView.classList.add('d-none');
            if (listView) listView.classList.remove('d-none');
            localStorage.setItem('ttu_course_view', 'list');
        } else {
            if (btnViewGrid) {
                btnViewGrid.classList.add('active', 'btn-white', 'shadow-sm');
                btnViewGrid.classList.remove('text-muted');
            }
            if (btnViewList) {
                btnViewList.classList.remove('active', 'btn-white', 'shadow-sm');
                btnViewList.classList.add('text-muted');
            }
            if (gridView) gridView.classList.remove('d-none');
            if (listView) listView.classList.add('d-none');
            localStorage.setItem('ttu_course_view', 'grid');
        }
        applyFilterAndSearch();
    }

    if (btnViewGrid) {
        btnViewGrid.addEventListener('click', function() { switchView('grid'); });
    }
    if (btnViewList) {
        btnViewList.addEventListener('click', function() { switchView('list'); });
    }

    // Restore saved view preference
    const savedView = localStorage.getItem('ttu_course_view');
    if (savedView === 'list') {
        switchView('list');
    }
});
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
