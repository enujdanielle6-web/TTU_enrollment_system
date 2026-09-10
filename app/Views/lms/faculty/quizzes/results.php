<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sia/lms/faculty/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($course['subject_code']) ?></a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="text-decoration-none text-muted">Quizzes</a></li>
            <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Results</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4 bg-light border-bottom rounded-top-4">
            <h2 class="h4 fw-bold mb-1"><?= htmlspecialchars($quiz['title']) ?></h2>
            <p class="text-muted mb-0">Quiz Results Overview</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3">Student Name</th>
                            <th class="py-3">Attempt #</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Submitted At</th>
                            <th class="text-end pe-4 py-3">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attempts)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                                    No attempts recorded yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attempts as $attempt): ?>
                                <tr>
                                    <td class="ps-4 fw-medium text-dark">
                                        <?= htmlspecialchars($attempt['first_name'] . ' ' . $attempt['last_name']) ?>
                                    </td>
                                    <td><?= esc($attempt['attempt_number']) ?></td>
                                    <td>
                                        <?php if ($attempt['status'] === 'graded'): ?>
                                            <span class="badge bg-success">Graded</span>
                                        <?php elseif ($attempt['status'] === 'in_progress'): ?>
                                            <span class="badge bg-warning text-dark">In Progress</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= ucfirst($attempt['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= !empty($attempt['submitted_at']) ? esc(date('M d, Y h:i A', strtotime($attempt['submitted_at']))) : '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td class="text-end pe-4 fw-bold">
                                        <?= $attempt['score'] !== null ? esc($attempt['score'] . ' pts') : '<span class="text-muted">-</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
