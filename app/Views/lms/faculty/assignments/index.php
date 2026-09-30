<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Course Header & Horizontal Navigation -->
    <?php 
    $active_tab = 'assignments';
    require __DIR__ . '/../components/course_header.php'; 
    ?>

    <div id="course-tab-content" class="course-tab-content">
        <?php
        $publishedCount = 0;
        $draftCount = 0;
        foreach ($assignments as $a) {
            if (($a['status'] ?? '') === 'published') {
                $publishedCount++;
            } else {
                $draftCount++;
            }
        }
        $totalAssignments = count($assignments);
        ?>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="lms-card p-3 bg-white border-0 shadow-sm rounded-4 border-start border-4 border-primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Total Assignments</span>
                            <h3 class="mb-0 fw-bold mt-1 text-dark"><?= $totalAssignments ?></h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-journal-text fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="lms-card p-3 bg-white border-0 shadow-sm rounded-4 border-start border-4 border-success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Published (Visible)</span>
                            <h3 class="mb-0 fw-bold mt-1 text-success"><?= $publishedCount ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-check-circle fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="lms-card p-3 bg-white border-0 shadow-sm rounded-4 border-start border-4 border-warning">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Drafts (Hidden)</span>
                            <h3 class="mb-0 fw-bold mt-1 text-warning"><?= $draftCount ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-pencil-square fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Header Bar -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h4 class="h5 fw-bold text-dark mb-1">
                    <i class="bi bi-journal-text me-2 text-primary"></i>Course Assignments
                </h4>
                <p class="text-muted small mb-0">Create, manage, and grade student homework, laboratory activities, and project submissions.</p>
            </div>
            <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments/create" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>New Assignment</span>
            </a>
        </div>

        <!-- Assignments Container -->
        <div class="lms-card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="p-0">
                <?php if (empty($assignments)): ?>
                    <div class="text-center py-5 p-4">
                        <i class="bi bi-journal-x text-muted opacity-50 mb-3" style="font-size: 3.5rem;"></i>
                        <h5 class="fw-bold text-dark">No Assignments Created Yet</h5>
                        <p class="text-muted mb-4" style="max-width: 450px; margin: 0 auto;">
                            There are currently no assignments authored for this course section. Students will see assignments once published.
                        </p>
                        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments/create" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                            <i class="bi bi-plus-lg me-1"></i> Create First Assignment
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($assignments as $assignment): 
                            $isPublished = ($assignment['status'] === 'published');
                            $isPastDue = (!empty($assignment['due_date']) && strtotime($assignment['due_date']) < time());
                        ?>
                            <div class="list-group-item p-4 transition-all border-bottom hover-bg-light">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                    <div class="d-flex align-items-start gap-3 min-w-0" style="flex: 1 1 340px;">
                                        <div class="rounded-3 p-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary flex-shrink-0" style="width: 48px; height: 48px;">
                                            <i class="bi bi-journal-text fs-4"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                <?php if ($isPublished): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-bold">
                                                        <i class="bi bi-check-circle me-1"></i>Published
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-bold">
                                                        <i class="bi bi-clock me-1"></i>Draft (Hidden)
                                                    </span>
                                                <?php endif; ?>
                                                <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                                                    <?= esc($assignment['max_score']) ?> Pts Max
                                                </span>
                                            </div>
                                            <h5 class="fw-bold text-dark mb-1 text-truncate" title="<?= htmlspecialchars($assignment['title']) ?>">
                                                <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments/<?= esc($assignment['id']) ?>/submissions" class="text-decoration-none text-dark hover-primary">
                                                    <?= htmlspecialchars($assignment['title']) ?>
                                                </a>
                                            </h5>
                                            <div class="text-muted small d-flex flex-wrap gap-3">
                                                <span>
                                                    <i class="bi bi-calendar-event me-1 text-primary"></i>
                                                    <?php if (!empty($assignment['due_date'])): ?>
                                                        Due: <?= date('M d, Y, g:i A', strtotime($assignment['due_date'])) ?>
                                                        <?php if ($isPastDue): ?>
                                                            <span class="text-danger fw-semibold ms-1">(Closed)</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        No due date set
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments/<?= esc($assignment['id']) ?>/submissions" class="btn btn-primary rounded-pill px-3.5 py-1.5 fw-bold btn-sm shadow-xs">
                                            <i class="bi bi-people-fill me-1"></i> Submissions
                                        </a>
                                        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments/<?= esc($assignment['id']) ?>/edit" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold btn-sm">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </a>
                                        <form method="POST" action="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments/<?= esc($assignment['id']) ?>/delete" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this assignment and all student submissions? This cannot be undone.');">
                                            <?= getCsrfInput() ?>
                                            <button type="submit" class="btn btn-outline-danger rounded-pill px-2.5 py-1.5 btn-sm" title="Delete Assignment">
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
