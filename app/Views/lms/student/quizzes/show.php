<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sia/lms/student/dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/student/course.php?id=<?= $course['lms_course_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($course['subject_code']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Quiz</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="lms-card p-5 border-0 shadow-sm rounded-4 text-center">
                <h1 class="h3 fw-bold text-dark mb-3"><?= htmlspecialchars($quiz['title']) ?></h1>
                
                <p class="text-muted mb-4 mx-auto" style="max-width: 600px;">
                    <?= nl2br(htmlspecialchars($quiz['description'] ?? 'No additional instructions.')) ?>
                </p>

                <div class="d-flex justify-content-center gap-4 mb-5 text-start">
                    <div class="bg-light p-3 rounded-3 border">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Time Limit</div>
                        <div class="fs-5 fw-bold text-dark">
                            <i class="bi bi-stopwatch text-primary me-1"></i>
                            <?= $quiz['time_limit'] ? $quiz['time_limit'] . ' minutes' : 'Unlimited' ?>
                        </div>
                    </div>
                    <div class="bg-light p-3 rounded-3 border">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Attempts</div>
                        <div class="fs-5 fw-bold text-dark">
                            <i class="bi bi-arrow-repeat text-primary me-1"></i>
                            <?= count($attempts) ?> / <?= $quiz['max_attempts'] ?: '&infin;' ?>
                        </div>
                    </div>
                </div>

                <?php if ($in_progress): ?>
                    <div class="alert alert-warning mb-4">
                        You have an attempt in progress.
                    </div>
                    <form action="/sia/lms/student/course/<?= $course['lms_course_id'] ?>/quizzes/<?= $quiz['id'] ?>/start" method="POST">
                        <button type="submit" class="btn btn-warning btn-lg px-5 shadow-sm fw-bold">Resume Attempt</button>
                    </form>
                <?php elseif ($can_attempt): ?>
                    <form action="/sia/lms/student/course/<?= $course['lms_course_id'] ?>/quizzes/<?= $quiz['id'] ?>/start" method="POST">
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm fw-bold">Start Quiz</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-secondary">
                        This quiz is currently unavailable or you have reached the maximum number of attempts.
                    </div>
                <?php endif; ?>

                <?php if (!empty($attempts)): ?>
                    <hr class="my-5">
                    <h5 class="fw-bold mb-4 text-start">Your Past Attempts</h5>
                    <div class="table-responsive text-start">
                        <table class="table align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Attempt</th>
                                    <th>Status</th>
                                    <th>Score</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attempts as $attempt): ?>
                                    <tr>
                                        <td>#<?= $attempt['attempt_number'] ?></td>
                                        <td>
                                            <?php if ($attempt['status'] === 'graded'): ?>
                                                <span class="badge bg-success">Graded</span>
                                            <?php elseif ($attempt['status'] === 'in_progress'): ?>
                                                <span class="badge bg-warning text-dark">In Progress</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= ucfirst($attempt['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold">
                                            <?= $attempt['score'] !== null ? $attempt['score'] : '-' ?>
                                        </td>
                                        <td>
                                            <?php if ($attempt['status'] !== 'in_progress'): ?>
                                                <a href="/sia/lms/student/course/<?= $course['lms_course_id'] ?>/quizzes/<?= $quiz['id'] ?>/result/<?= $attempt['id'] ?>" class="btn btn-sm btn-outline-primary">View Results</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>
