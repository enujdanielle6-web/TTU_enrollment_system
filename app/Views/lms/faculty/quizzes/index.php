<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Course Header & Horizontal Navigation -->
    <?php 
    $active_tab = 'quizzes';
    require __DIR__ . '/../components/course_header.php'; 
    ?>

    <div id="course-tab-content" class="course-tab-content">
        <?php
        $publishedQuizzes = 0;
        $draftQuizzes = 0;
        foreach ($quizzes as $q) {
            if (($q['status'] ?? '') === 'published') {
                $publishedQuizzes++;
            } else {
                $draftQuizzes++;
            }
        }
        $totalQuizzes = count($quizzes);
        ?>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card-kpi h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-label">Total Quizzes</span>
                        <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);">
                            <i class="bi bi-pencil-square text-white"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $totalQuizzes ?></div>
                    <div class="stat-subtext text-primary">
                        <i class="bi bi-collection me-1"></i> Assessment Repository
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-kpi h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-label">Active / Published</span>
                        <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <i class="bi bi-check2-circle text-white"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $publishedQuizzes ?></div>
                    <div class="stat-subtext text-success">
                        <i class="bi bi-broadcast me-1"></i> Open to Students
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-kpi h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-label">Drafts / In-Progress</span>
                        <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <i class="bi bi-clock-history text-white"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $draftQuizzes ?></div>
                    <div class="stat-subtext text-warning">
                        <i class="bi bi-pen-fill me-1"></i> Hidden / In Authoring
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Header Bar -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h4 class="h5 fw-bold text-dark mb-1">
                    <i class="bi bi-pencil-square me-2 text-info"></i>Online Quizzes &amp; Assessments
                </h4>
                <p class="text-muted small mb-0">Author mixed-type quizzes (multiple choice, true/false, identification, fill in the blank), import or generate questions, and review student attempts.</p>
            </div>
            <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes/create" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>Create Quiz</span>
            </a>
        </div>

        <!-- Quizzes List Card -->
        <div class="lms-card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="p-0">
                <?php if (empty($quizzes)): ?>
                    <div class="text-center py-5 p-4">
                        <i class="bi bi-pencil-square text-muted opacity-50 mb-3" style="font-size: 3.5rem;"></i>
                        <h5 class="fw-bold text-dark">No Quizzes Created Yet</h5>
                        <p class="text-muted mb-4" style="max-width: 450px; margin: 0 auto;">
                            Prepare timed assessments and tests with automated scoring and comprehensive attempt diagnostics.
                        </p>
                        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes/create" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                            <i class="bi bi-plus-lg me-1"></i> Create First Quiz
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($quizzes as $quiz): 
                            $isPublished = ($quiz['status'] === 'published');
                        ?>
                            <div class="list-group-item p-4 transition-all border-bottom hover-bg-light">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                    <div class="d-flex align-items-start gap-3 min-w-0" style="flex: 1 1 340px;">
                                        <div class="rounded-3 p-3 d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info flex-shrink-0" style="width: 48px; height: 48px;">
                                            <i class="bi bi-ui-checks fs-4"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                <?php if ($isPublished): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-bold">
                                                        <i class="bi bi-check-circle me-1"></i>Published
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-bold">
                                                        <i class="bi bi-clock me-1"></i>Draft
                                                    </span>
                                                <?php endif; ?>
                                                <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                                                    <i class="bi bi-stopwatch me-1"></i><?= !empty($quiz['time_limit']) ? esc($quiz['time_limit']) . ' Mins' : 'Unlimited Time' ?>
                                                </span>
                                                <?php if (!empty($quiz['max_attempts'])): ?>
                                                    <span class="badge bg-light text-muted border rounded-pill px-2 py-0.5 small">
                                                        <?= esc($quiz['max_attempts']) ?> Max <?= (int)$quiz['max_attempts'] === 1 ? 'Attempt' : 'Attempts' ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <h5 class="fw-bold text-dark mb-1 text-truncate" title="<?= htmlspecialchars($quiz['title']) ?>">
                                                <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/questions" class="text-decoration-none text-dark hover-primary">
                                                    <?= htmlspecialchars($quiz['title']) ?>
                                                </a>
                                            </h5>
                                            <div class="text-muted small d-flex flex-wrap gap-3">
                                                <span>
                                                    <i class="bi bi-calendar-range me-1 text-primary"></i>
                                                    <?php if ($quiz['start_date'] || $quiz['end_date']): ?>
                                                        <?= $quiz['start_date'] ? date('M d, Y', strtotime($quiz['start_date'])) : 'Open' ?> 
                                                        &rarr; 
                                                        <?= $quiz['end_date'] ? date('M d, Y, g:i A', strtotime($quiz['end_date'])) : 'No End Date' ?>
                                                    <?php else: ?>
                                                        Always Available
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/questions" class="btn btn-outline-primary rounded-pill px-3 py-1.5 fw-semibold btn-sm shadow-xs">
                                            <i class="bi bi-list-check me-1"></i> Questions
                                        </a>
                                        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/results" class="btn btn-outline-info rounded-pill px-3 py-1.5 fw-semibold btn-sm">
                                            <i class="bi bi-bar-chart me-1"></i> Results
                                        </a>
                                        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/edit" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold btn-sm" title="Edit Quiz Settings">
                                            <i class="bi bi-gear me-1"></i> Settings
                                        </a>
                                        <form method="POST" action="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/delete" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this quiz, its questions, and all student attempts?');">
                                            <?= getCsrfInput() ?>
                                            <button type="submit" class="btn btn-outline-danger rounded-pill px-2.5 py-1.5 btn-sm" title="Delete Quiz">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
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
