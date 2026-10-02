<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Breadcrumb & Return -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 align-items-center">
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($course['subject_code']) ?></a></li>
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="text-decoration-none text-muted">Quizzes</a></li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Quiz Results</li>
            </ol>
        </nav>
        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Quizzes
        </a>
    </div>

    <!-- Quiz Context Hero Card -->
    <div class="lms-card p-4 mb-4 border-0 shadow-sm bg-white rounded-4 position-relative overflow-hidden">
        <div class="row align-items-center g-3">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-1.5 rounded-pill fw-bold">
                        <i class="bi bi-pencil-square me-1"></i><?= htmlspecialchars($course['subject_code']) ?>
                    </span>
                    <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-semibold">
                        Section <?= htmlspecialchars($course['section_code']) ?>
                    </span>
                    <?php if (!empty($quiz['passing_score'])): ?>
                        <span class="badge bg-light text-muted border px-3 py-1.5 rounded-pill">
                            Passing Score: <?= esc($quiz['passing_score']) ?>%
                        </span>
                    <?php endif; ?>
                </div>
                <h3 class="h4 fw-bold text-dark mb-1"><?= htmlspecialchars($quiz['title']) ?></h3>
                <p class="text-muted small mb-0">Review student test attempt submissions, recorded scores, and completion statuses.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <?php
                $totalAttempts = count($attempts);
                $scores = [];
                foreach ($attempts as $at) {
                    if ($at['score'] !== null) {
                        $scores[] = (float)$at['score'];
                    }
                }
                $avgScore = !empty($scores) ? round(array_sum($scores) / count($scores), 1) : 0;
                $highestScore = !empty($scores) ? max($scores) : 0;
                ?>
                <div class="d-inline-flex gap-2">
                    <div class="text-center px-3 py-2 bg-light rounded-3 border">
                        <span class="d-block fw-bold text-dark fs-5 lh-1"><?= $totalAttempts ?></span>
                        <span class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 700;">Attempts</span>
                    </div>
                    <div class="text-center px-3 py-2 bg-light rounded-3 border">
                        <span class="d-block fw-bold text-success fs-5 lh-1"><?= $highestScore ?></span>
                        <span class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 700;">Top Score</span>
                    </div>
                    <div class="text-center px-3 py-2 bg-light rounded-3 border">
                        <span class="d-block fw-bold text-primary fs-5 lh-1"><?= $avgScore ?></span>
                        <span class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 700;">Class Avg</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attempts Table Card -->
    <div class="lms-card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="h6 fw-bold text-dark mb-0">Student Attempts Log</h5>
            <div style="max-width: 280px; width: 100%;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="quizSearchInput" class="form-control bg-light border-start-0" placeholder="Filter student name...">
                </div>
            </div>
        </div>

        <div class="p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="quizAttemptsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted text-uppercase small" style="letter-spacing: 0.04em;">Student Name</th>
                            <th class="py-3 text-muted text-uppercase small" style="letter-spacing: 0.04em;">Attempt</th>
                            <th class="py-3 text-muted text-uppercase small" style="letter-spacing: 0.04em;">Status</th>
                            <th class="py-3 text-muted text-uppercase small" style="letter-spacing: 0.04em;">Submitted At</th>
                            <th class="text-end pe-4 py-3 text-muted text-uppercase small" style="letter-spacing: 0.04em;">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attempts)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <div class="lms-table-empty">
                                      <i class="bi bi-pencil-square fs-1 d-block mb-2 opacity-50"></i>
                                      No student quiz attempts recorded yet.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attempts as $attempt): 
                                $studentName = trim(($attempt['first_name'] ?? '') . ' ' . ($attempt['last_name'] ?? ''));
                                $initial = strtoupper(substr($attempt['first_name'] ?? 'S', 0, 1));
                                $status = strtolower($attempt['status'] ?? '');
                            ?>
                                <tr class="attempt-row">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; flex-shrink: 0;">
                                                <?= esc($initial) ?>
                                            </div>
                                            <div>
                                                <span class="d-block fw-bold text-dark student-name-text"><?= htmlspecialchars($studentName) ?></span>
                                                <small class="text-muted font-monospace"><?= htmlspecialchars($attempt['student_number'] ?? 'Student') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 fw-semibold">
                                            Attempt #<?= esc($attempt['attempt_number']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($status === 'graded' || $status === 'completed'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 fw-bold">
                                                <i class="bi bi-check-circle me-1"></i>Completed
                                            </span>
                                        <?php elseif ($status === 'in_progress'): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill px-2.5 py-1 fw-bold">
                                                <i class="bi bi-clock me-1"></i>In Progress
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-2.5 py-1"><?= ucfirst(htmlspecialchars($status)) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="small text-muted">
                                            <?= !empty($attempt['submitted_at']) ? '<i class="bi bi-clock me-1"></i>' . date('M d, Y h:i A', strtotime($attempt['submitted_at'])) : '<span class="text-muted">—</span>' ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if ($attempt['score'] !== null): ?>
                                            <span class="fw-bold fs-6 text-dark"><?= esc($attempt['score']) ?></span>
                                            <span class="text-muted small">Pts</span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
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

<script>
(function() {
    const input = document.getElementById('quizSearchInput');
    if (!input) return;
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#quizAttemptsTable .attempt-row');
        rows.forEach(row => {
            const nameEl = row.querySelector('.student-name-text');
            const name = nameEl ? nameEl.textContent.toLowerCase() : '';
            if (name.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
