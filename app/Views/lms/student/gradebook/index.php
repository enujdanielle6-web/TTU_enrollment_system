<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Unified Course Header & Horizontal Navigation (Option A) -->
    <?php 
    $active_tab = 'gradebook';
    require __DIR__ . '/../components/course_header.php'; 
    ?>

    <div id="course-tab-content" class="course-tab-content">
        <?php
        $assignments = $data['assignments'] ?? [];
        $quizzes = $data['quizzes'] ?? [];
        $myGrades = $data['my_grades'] ?? null;
        $totalPossible = (float)($data['total_possible'] ?? 0);
        $totalEarned = (float)($myGrades['total'] ?? 0);
        $overallPercentage = $totalPossible > 0 ? ($totalEarned / $totalPossible) * 100 : 0;

        // Assignments stats
        $totalAssignPossible = (float)($data['max_assignment_points'] ?? array_sum(array_column($assignments, 'max_score')));
        $totalAssignEarned = 0;
        $gradedAssignCount = 0;
        foreach ($assignments as $a) {
            $sc = $myGrades ? ($myGrades['assignments'][$a['id']] ?? null) : null;
            if ($sc !== null) {
                $totalAssignEarned += (float)$sc;
                $gradedAssignCount++;
            }
        }
        $assignPercentage = $totalAssignPossible > 0 ? ($totalAssignEarned / $totalAssignPossible) * 100 : 0;

        // Quizzes stats
        $totalQuizPossible = (float)($data['max_quiz_points'] ?? 0);
        if ($totalQuizPossible == 0 && !empty($data['quiz_max_points'])) {
            $totalQuizPossible = array_sum($data['quiz_max_points']);
        }
        $totalQuizEarned = 0;
        $gradedQuizCount = 0;
        foreach ($quizzes as $q) {
            $sc = $myGrades ? ($myGrades['quizzes'][$q['id']] ?? null) : null;
            if ($sc !== null) {
                $totalQuizEarned += (float)$sc;
                $gradedQuizCount++;
            }
        }
        $quizPercentage = $totalQuizPossible > 0 ? ($totalQuizEarned / $totalQuizPossible) * 100 : 0;

        $totalItems = count($assignments) + count($quizzes);
        $totalGradedItems = $gradedAssignCount + $gradedQuizCount;
        $evalProgress = $totalItems > 0 ? ($totalGradedItems / $totalItems) * 100 : 0;

        // Transmutation Scale (Philippine Higher Education 1.00 - 5.00 Grading System)
        if ($totalGradedItems === 0) {
            $transmutedGrade = '—';
            $gradeLabel = 'Course In Progress';
            $gradeBadgeClass = 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25';
        } elseif ($overallPercentage >= 97) {
            $transmutedGrade = '1.00';
            $gradeLabel = 'Excellent';
            $gradeBadgeClass = 'bg-success text-white';
        } elseif ($overallPercentage >= 93) {
            $transmutedGrade = '1.25';
            $gradeLabel = 'Superior';
            $gradeBadgeClass = 'bg-success text-white';
        } elseif ($overallPercentage >= 89) {
            $transmutedGrade = '1.50';
            $gradeLabel = 'Very Good';
            $gradeBadgeClass = 'bg-success text-white';
        } elseif ($overallPercentage >= 85) {
            $transmutedGrade = '1.75';
            $gradeLabel = 'Good';
            $gradeBadgeClass = 'bg-primary text-white';
        } elseif ($overallPercentage >= 80) {
            $transmutedGrade = '2.00';
            $gradeLabel = 'Satisfactory';
            $gradeBadgeClass = 'bg-primary text-white';
        } elseif ($overallPercentage >= 75) {
            $transmutedGrade = '3.00';
            $gradeLabel = 'Passing';
            $gradeBadgeClass = 'bg-warning text-dark';
        } else {
            $transmutedGrade = '5.00';
            $gradeLabel = 'Below Passing';
            $gradeBadgeClass = 'bg-danger text-white';
        }
        ?>

        <!-- 1. Top Metrics Grid -->
        <div class="row g-4 mb-4">
            <!-- Overall Course Grade Card -->
            <div class="col-xl-4 col-lg-5">
                <div class="grade-stat-card grade-hero-card p-4 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-uppercase small fw-bold text-muted" style="letter-spacing: 0.04em;">Academic Standing</span>
                        <span class="badge rounded-pill <?= esc($gradeBadgeClass) ?> px-3 py-1 fw-bold">
                            <?php if ($totalGradedItems === 0): ?>
                                <i class="bi bi-clock-history me-1"></i> <?= esc($gradeLabel) ?>
                            <?php else: ?>
                                <?= esc($transmutedGrade) ?> &bull; <?= esc($gradeLabel) ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-4 my-2">
                        <!-- SVG Circular Progress Gauge -->
                        <div class="position-relative d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px; flex-shrink: 0;">
                            <svg class="w-100 h-100" viewBox="0 0 36 36" style="transform: rotate(-90deg);">
                                <path stroke-width="3.5" stroke="currentColor" fill="none"
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    style="stroke: #e2e8f0;" />
                                <?php if ($totalGradedItems > 0 && $overallPercentage > 0): ?>
                                <path stroke-dasharray="<?= esc(round($overallPercentage, 1)) ?>, 100" stroke-width="3.5" stroke-linecap="round" fill="none"
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    style="stroke: <?= esc($overallPercentage >= 75 ? 'var(--lms-primary)' : '#ef4444') ?>; transition: stroke-dasharray 0.6s ease;" />
                                <?php endif; ?>
                            </svg>
                            <div class="position-absolute text-center">
                                <?php if ($totalGradedItems > 0): ?>
                                    <span class="fw-bold fs-5 text-dark"><?= esc(round($overallPercentage, 0)) ?>%</span>
                                <?php else: ?>
                                    <span class="fw-bold fs-5 text-secondary">0%</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <?php if ($totalGradedItems > 0): ?>
                                <div class="display-6 fw-bold text-dark mb-0"><?= number_format($overallPercentage, 1) ?>%</div>
                                <div class="text-muted small fw-semibold">
                                    <i class="bi bi-bullseye me-1 text-primary"></i> <?= esc($totalEarned) ?> / <?= esc($totalPossible) ?> Total Points
                                </div>
                            <?php else: ?>
                                <div class="display-6 fw-bold text-dark mb-0">0.0%</div>
                                <div class="text-secondary small fw-semibold">
                                    <i class="bi bi-info-circle me-1 text-primary"></i> No assessments graded yet
                                </div>
                                <div class="text-muted smaller mt-1">
                                    <i class="bi bi-bullseye me-1"></i> 0 / <?= esc($totalPossible) ?> Course Points
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="pt-3 mt-2 border-top d-flex justify-content-between align-items-center small">
                        <?php if ($totalGradedItems === 0): ?>
                            <span class="text-muted"><i class="bi bi-hourglass text-muted me-1"></i> Graded Tasks</span>
                            <span class="badge bg-light text-secondary border px-2 py-1 fw-semibold">0 of <?= esc($totalItems) ?> Items</span>
                        <?php elseif ($totalGradedItems < $totalItems): ?>
                            <span class="text-muted"><i class="bi bi-hourglass-split text-warning me-1"></i> Partially Graded</span>
                            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 px-2 py-1 fw-semibold"><?= esc($totalGradedItems) ?> of <?= esc($totalItems) ?> Items</span>
                        <?php else: ?>
                            <span class="text-muted"><i class="bi bi-check-circle-fill text-success me-1"></i> All Assessments Graded</span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-semibold"><?= esc($totalGradedItems) ?> of <?= esc($totalItems) ?> Items</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Breakdown Stat Tiles -->
            <div class="col-xl-8 col-lg-7">
                <div class="row g-3 h-100">
                    <!-- Assignments Breakdown -->
                    <div class="col-md-6">
                        <div class="grade-stat-card p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icon-box-sm bg-primary bg-opacity-10 text-primary">
                                            <i class="bi bi-journal-text"></i>
                                        </div>
                                        <h6 class="fw-bold mb-0 text-dark">Assignments</h6>
                                    </div>
                                    <?php if ($gradedAssignCount > 0): ?>
                                        <span class="badge bg-primary text-white rounded-pill px-3 py-1 fw-bold">
                                            <?= esc(round($assignPercentage, 1)) ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill px-3 py-1 fw-semibold">
                                            0.0%
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="fs-4 fw-bold text-dark mb-1">
                                    <?= esc($totalAssignEarned) ?> <span class="fs-6 fw-normal text-muted">/ <?= esc($totalAssignPossible) ?> pts</span>
                                </div>
                            </div>
                            <div>
                                <div class="progress mb-2" style="height: 6px; border-radius: 10px; background: #e2e8f0;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= esc(min(100, $assignPercentage)) ?>%;"></div>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Graded Status</span>
                                    <span class="fw-semibold text-dark"><?= esc($gradedAssignCount) ?> of <?= esc(count($assignments)) ?> assignments</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quizzes Breakdown -->
                    <div class="col-md-6">
                        <div class="grade-stat-card p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icon-box-sm bg-info bg-opacity-10 text-info">
                                            <i class="bi bi-pencil-square"></i>
                                        </div>
                                        <h6 class="fw-bold mb-0 text-dark">Online Quizzes</h6>
                                    </div>
                                    <?php if ($gradedQuizCount > 0): ?>
                                        <span class="badge bg-info text-white rounded-pill px-3 py-1 fw-bold">
                                            <?= esc(round($quizPercentage, 1)) ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill px-3 py-1 fw-semibold">
                                            0.0%
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="fs-4 fw-bold text-dark mb-1">
                                    <?= esc($totalQuizEarned) ?> <span class="fs-6 fw-normal text-muted">/ <?= esc($totalQuizPossible) ?> pts</span>
                                </div>
                            </div>
                            <div>
                                <div class="progress mb-2" style="height: 6px; border-radius: 10px; background: #e2e8f0;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?= esc(min(100, $quizPercentage)) ?>%;"></div>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Graded Status</span>
                                    <span class="fw-semibold text-dark"><?= esc($gradedQuizCount) ?> of <?= esc(count($quizzes)) ?> quizzes</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Interactive Filter Bar & Assessment Items -->
        <div class="lms-card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold text-dark mb-1"><i class="bi bi-card-checklist me-2 text-primary"></i>Assessment Breakdown</h5>
                    <p class="text-muted small mb-0">Detailed view of your graded submissions and quizzes for this course.</p>
                </div>
                
                <!-- Filter Pills -->
                <div class="d-flex flex-wrap gap-2" id="gradeFilterGroup">
                    <button class="grade-filter-btn active" data-filter="all" type="button">
                        All Items <span class="badge rounded-pill bg-light text-dark ms-1"><?= esc($totalItems) ?></span>
                    </button>
                    <button class="grade-filter-btn" data-filter="assignment" type="button">
                        Assignments <span class="badge rounded-pill bg-light text-dark ms-1"><?= esc(count($assignments)) ?></span>
                    </button>
                    <button class="grade-filter-btn" data-filter="quiz" type="button">
                        Quizzes <span class="badge rounded-pill bg-light text-dark ms-1"><?= esc(count($quizzes)) ?></span>
                    </button>
                </div>
            </div>

            <!-- Assessment List -->
            <div id="assessmentListContainer">
                <?php if (empty($assignments) && empty($quizzes)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-clipboard2-x display-3 text-muted opacity-50 mb-3 d-block"></i>
                        <h5 class="fw-bold text-dark">No Assessments Published</h5>
                        <p class="text-muted mb-0">Your instructor has not published any gradable assignments or quizzes yet.</p>
                    </div>
                <?php else: ?>
                    <!-- Assignments List -->
                    <?php foreach ($assignments as $a): 
                        $score = $myGrades ? ($myGrades['assignments'][$a['id']] ?? null) : null;
                        $sub = $myGrades ? ($myGrades['assignment_submissions'][$a['id']] ?? null) : null;
                        $maxScore = (float)$a['max_score'];
                        $itemPct = ($score !== null && $maxScore > 0) ? ($score / $maxScore) * 100 : 0;
                        $isSubmitted = ($sub !== null && !empty($sub['status']));
                        $isGraded = ($score !== null);
                    ?>
                        <div class="assessment-item-card assessment-item" data-type="assignment">
                            <div class="row align-items-center g-3">
                                <div class="col-md-5">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="icon-box-sm bg-primary bg-opacity-10 text-primary">
                                            <i class="bi bi-journal-text"></i>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-0 small fw-bold">Assignment</span>
                                                <?php if (!empty($a['due_date'])): ?>
                                                    <small class="text-muted"><i class="bi bi-clock me-1"></i>Due <?= date('M d, Y', strtotime($a['due_date'])) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/assignments/<?= esc($a['id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                                <?= htmlspecialchars($a['title']) ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <?php if ($isGraded): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; background: #e2e8f0; border-radius: 10px;">
                                                <div class="progress-bar <?= esc($itemPct >= 75 ? 'bg-success' : 'bg-warning') ?>" role="progressbar" style="width: <?= esc(min(100, $itemPct)) ?>%;"></div>
                                            </div>
                                            <small class="text-muted fw-bold"><?= esc(round($itemPct, 0)) ?>%</small>
                                        </div>
                                    <?php elseif ($isSubmitted): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill px-3 py-1 small fw-semibold">
                                            <i class="bi bi-hourglass-split text-warning me-1"></i> Turned In &bull; Pending Grade
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">
                                            <i class="bi bi-dash me-1"></i> Not Submitted
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-2 text-md-center">
                                    <?php if ($isGraded): ?>
                                        <div class="fw-bold fs-6 text-dark"><?= esc($score) ?> <span class="text-muted small fw-normal">/ <?= esc($maxScore) ?> pts</span></div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">
                                            — / <?= esc($maxScore) ?> pts
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-2 text-md-end">
                                    <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/assignments/<?= esc($a['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                                        View <i class="bi bi-chevron-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Quizzes List -->
                    <?php foreach ($quizzes as $q): 
                        $score = $myGrades ? ($myGrades['quizzes'][$q['id']] ?? null) : null;
                        $attempts = $myGrades ? ($myGrades['quiz_attempts'][$q['id']] ?? []) : [];
                        $maxPts = (float)($data['quiz_max_points'][$q['id']] ?? 0);
                        $itemPct = ($score !== null && $maxPts > 0) ? ($score / $maxPts) * 100 : 0;
                        $isGraded = ($score !== null);
                        $hasAttempted = !empty($attempts);
                    ?>
                        <div class="assessment-item-card assessment-item" data-type="quiz">
                            <div class="row align-items-center g-3">
                                <div class="col-md-5">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="icon-box-sm bg-info bg-opacity-10 text-info">
                                            <i class="bi bi-pencil-square"></i>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2 py-0 small fw-bold">Online Quiz</span>
                                                <?php if (!empty($q['end_date'])): ?>
                                                    <small class="text-muted"><i class="bi bi-clock me-1"></i>Closes <?= date('M d, Y', strtotime($q['end_date'])) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($q['id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                                <?= htmlspecialchars($q['title']) ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <?php if ($isGraded): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; background: #e2e8f0; border-radius: 10px;">
                                                <div class="progress-bar <?= esc($itemPct >= 75 ? 'bg-success' : 'bg-warning') ?>" role="progressbar" style="width: <?= esc(min(100, $itemPct)) ?>%;"></div>
                                            </div>
                                            <small class="text-muted fw-bold"><?= esc(round($itemPct, 0)) ?>%</small>
                                        </div>
                                    <?php elseif ($hasAttempted): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill px-3 py-1 small fw-semibold">
                                            <i class="bi bi-hourglass-split text-warning me-1"></i> Attempted &bull; Pending Review
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">
                                            <i class="bi bi-dash me-1"></i> Not Attempted
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-2 text-md-center">
                                    <?php if ($isGraded): ?>
                                        <div class="fw-bold fs-6 text-dark"><?= esc($score) ?> <span class="text-muted small fw-normal">/ <?= esc($maxPts) ?> pts</span></div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">
                                            — / <?= esc($maxPts) ?> pts
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-2 text-md-end">
                                    <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($q['id']) ?>" class="btn btn-sm btn-outline-info rounded-pill px-3 fw-semibold">
                                        View <i class="bi bi-chevron-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Academic Policy Callout -->
        <div class="alert bg-white border rounded-4 p-4 shadow-sm mb-4">
            <div class="d-flex align-items-start gap-3">
                <div class="icon-box-sm bg-primary bg-opacity-10 text-primary mt-1">
                    <i class="bi bi-info-circle-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">About LMS Grade Calculations</h6>
                    <p class="text-muted small mb-0 lh-base">
                        The grades shown here represent continuous assessment scores recorded inside the TTU LMS for coursework, assignments, and online quizzes. Official midterm and final grades computed according to institutional weight criteria are officially submitted to the Registrar and accessible via the <strong>SIS Student Portal</strong>.
                    </p>
                </div>
            </div>
        </div>

        <script>
            (function initGradeFilter() {
                const attachFilters = () => {
                    const buttons = document.querySelectorAll('#gradeFilterGroup .grade-filter-btn');
                    const items = document.querySelectorAll('.assessment-item');

                    buttons.forEach(btn => {
                        btn.onclick = function() {
                            buttons.forEach(b => b.classList.remove('active'));
                            this.classList.add('active');

                            const filter = this.getAttribute('data-filter');

                            items.forEach(item => {
                                if (filter === 'all' || item.getAttribute('data-type') === filter) {
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
