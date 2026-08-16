<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="/sia/lms/student/my_courses.php" class="text-decoration-none">Courses</a></li>
                    <li class="breadcrumb-item"><a href="/sia/lms/student/course.php?id=<?= $course['lms_course_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($course['subject_code']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Quizzes</li>
                </ol>
            </nav>
            <h3 class="mb-0 fw-bold text-dark">Online Quizzes</h3>
        </div>
    </div>

    <div class="card lms-card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($quizzes)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-pencil-square display-1 text-light mb-3"></i>
                    <h5 class="fw-bold">No Quizzes Yet</h5>
                    <p>Your instructor has not published any quizzes for this course.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($quizzes as $q): ?>
                        <a href="/sia/lms/student/course/<?= $course['lms_course_id'] ?>/quizzes/<?= $q['id'] ?>" class="list-group-item list-group-item-action p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1 text-primary fw-bold">
                                        <i class="bi bi-pencil-square me-2"></i><?= htmlspecialchars($q['title']) ?>
                                    </h5>
                                    <p class="mb-1 text-muted small">
                                        <strong>Available Until:</strong> <?= $q['end_date'] ? date('F j, Y, g:i a', strtotime($q['end_date'])) : 'No Deadline' ?> &bull; 
                                        <strong>Time Limit:</strong> <?= $q['time_limit'] ?: 'None' ?> mins
                                    </p>
                                </div>
                                <?php if (!empty($q['attempts'])): ?>
                                    <span class="badge bg-success rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i> Attempted (<?= count($q['attempts']) ?>/<?= $q['max_attempts'] ?>)</span>
                                <?php else: ?>
                                    <?php if ($q['end_date'] && strtotime($q['end_date']) < time()): ?>
                                        <span class="badge bg-danger rounded-pill px-3 py-2"><i class="bi bi-exclamation-circle me-1"></i> Missed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary rounded-pill px-3 py-2">Not Attempted</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>
