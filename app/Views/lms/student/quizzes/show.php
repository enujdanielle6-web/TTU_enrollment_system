<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container-fluid py-4 px-md-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0 py-2 px-3 bg-white rounded-3 border shadow-xs align-items-center" style="font-size: 0.88rem;">
            <li class="breadcrumb-item"><a href="/sia/lms/student/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i>Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/student/my_courses.php" class="text-decoration-none text-muted">My Courses</a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/student/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-primary fw-medium"><?= htmlspecialchars($course['subject_code']) ?></a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?= htmlspecialchars($quiz['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-xl-9 col-lg-10">

            <!-- Hero Quiz Header Card -->
            <div class="lms-card border-0 shadow-sm rounded-4 mb-4 position-relative overflow-hidden" style="background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);">
                <div class="position-absolute top-0 end-0 p-4 opacity-10 pointer-events-none d-none d-md-block">
                    <i class="bi bi-patch-question-fill text-primary" style="font-size: 8rem; line-height: 1;"></i>
                </div>
                
                <div class="p-4 p-md-5 position-relative">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-3 py-1.5 rounded-pill fw-semibold" style="font-size: 0.78rem;">
                            <i class="bi bi-book me-1"></i><?= htmlspecialchars($course['subject_code']) ?> &bull; <?= htmlspecialchars($course['section_name'] ?? 'Section') ?>
                        </span>

                        <?php if ($in_progress): ?>
                            <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning border-opacity-25 px-3 py-1.5 rounded-pill fw-semibold" style="font-size: 0.78rem;">
                                <i class="bi bi-clock-history me-1"></i>Attempt #<?= esc($in_progress['attempt_number']) ?> in Progress
                            </span>
                        <?php elseif ($can_attempt): ?>
                            <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-3 py-1.5 rounded-pill fw-semibold" style="font-size: 0.78rem;">
                                <i class="bi bi-check-circle me-1"></i>Ready for Assessment
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary bg-opacity-15 text-secondary border border-secondary border-opacity-25 px-3 py-1.5 rounded-pill fw-semibold" style="font-size: 0.78rem;">
                                <i class="bi bi-lock-fill me-1"></i>Attempts Exhausted
                            </span>
                        <?php endif; ?>
                    </div>

                    <h1 class="h2 fw-bold text-dark mb-2" style="letter-spacing: -0.02em;">
                        <?= htmlspecialchars($quiz['title']) ?>
                    </h1>

                    <p class="text-muted fs-6 mb-4" style="max-width: 720px; line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($quiz['description'] ?: 'Complete this assessment within the allotted duration. Answer all questions to the best of your ability.')) ?>
                    </p>

                    <!-- Key Quiz Stats Grid -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-white rounded-3 border border-light-subtle shadow-xs d-flex align-items-center gap-3 transition-card">
                                <div class="stat-icon-wrapper rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: #e0edff; color: #0d6efd;">
                                    <i class="bi bi-stopwatch-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Time Limit</div>
                                    <div class="fw-bold text-dark fs-6"><?= esc($quiz['time_limit'] ? $quiz['time_limit'] . ' Mins' : 'Unlimited') ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-white rounded-3 border border-light-subtle shadow-xs d-flex align-items-center gap-3 transition-card">
                                <div class="stat-icon-wrapper rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: #e0f7fa; color: #0288d1;">
                                    <i class="bi bi-ui-checks-grid fs-5"></i>
                                </div>
                                <div>
                                    <div class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Total Items</div>
                                    <div class="fw-bold text-dark fs-6"><?= esc($total_questions ?? count($attempts)) ?> Questions</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-white rounded-3 border border-light-subtle shadow-xs d-flex align-items-center gap-3 transition-card">
                                <div class="stat-icon-wrapper rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: #fef3c7; color: #d97706;">
                                    <i class="bi bi-award-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Total Points</div>
                                    <div class="fw-bold text-dark fs-6"><?= esc(number_format($total_points ?? 0, 1)) ?> Pts</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-white rounded-3 border border-light-subtle shadow-xs d-flex align-items-center gap-3 transition-card">
                                <div class="stat-icon-wrapper rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: #f3e8ff; color: #7c3aed;">
                                    <i class="bi bi-arrow-repeat fs-5"></i>
                                </div>
                                <div>
                                    <div class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Attempts</div>
                                    <div class="fw-bold text-dark fs-6"><?= count($attempts) ?> / <?= esc($quiz['max_attempts'] ?: '&infin;') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Call to Action Banner -->
                    <div class="p-4 rounded-4 border bg-white shadow-xs">
                        <?php if ($in_progress): ?>
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                        <span class="spinner-grow spinner-grow-sm text-warning" role="status"></span>
                                        Attempt #<?= esc($in_progress['attempt_number']) ?> is actively in progress!
                                    </h5>
                                    <p class="text-muted small mb-0">Your answers are preserved. You can resume this attempt immediately.</p>
                                </div>
                                <form action="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/start" method="POST" class="m-0 flex-shrink-0">
                                    <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token'] ?? '') ?>">
                                    <button type="submit" class="btn btn-warning btn-lg px-4 py-2.5 rounded-pill shadow-sm fw-bold d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-play-circle-fill fs-5"></i>
                                        <span>Resume Attempt #<?= esc($in_progress['attempt_number']) ?></span>
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($can_attempt): ?>
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h5 class="fw-bold text-dark mb-1">Ready to take Attempt #<?= count($attempts) + 1 ?>?</h5>
                                    <p class="text-muted small mb-0">Ensure you have a reliable internet connection before starting.</p>
                                </div>
                                <form action="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/start" method="POST" class="m-0 flex-shrink-0">
                                    <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token'] ?? '') ?>">
                                    <button type="submit" class="btn btn-primary btn-lg px-5 py-2.5 rounded-pill shadow-sm fw-bold d-inline-flex align-items-center gap-2" style="background: linear-gradient(135deg, #0d6efd 0%, #1d4ed8 100%); border: none;">
                                        <i class="bi bi-rocket-takeoff-fill fs-5"></i>
                                        <span>Start Quiz Now</span>
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="icon-circle rounded-circle bg-secondary bg-opacity-10 text-secondary p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                        <i class="bi bi-lock-fill fs-4"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1">Assessment Closed or Attempts Exhausted</h6>
                                        <p class="text-muted small mb-0">You have completed all <?= esc($quiz['max_attempts']) ?> allowed attempts for this quiz.</p>
                                    </div>
                                </div>
                                <?php if (!empty($attempts)): ?>
                                    <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/result/<?= esc($attempts[0]['id']) ?>" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-eye-fill"></i>
                                        <span>Review Latest Results</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- Assessment Instructions & Policies -->
            <div class="lms-card border-0 shadow-sm rounded-4 mb-4 p-4 p-md-4 bg-white">
                <h6 class="fw-bold text-dark text-uppercase mb-3 d-flex align-items-center gap-2" style="font-size: 0.8rem; letter-spacing: 0.06em;">
                    <i class="bi bi-shield-check text-primary"></i>
                    Assessment Guidelines & Policies
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex gap-3">
                            <i class="bi bi-stopwatch text-danger fs-5 mt-0.5"></i>
                            <div>
                                <span class="d-block fw-semibold text-dark small">Continuous Countdown Timer</span>
                                <span class="text-muted small" style="line-height: 1.4;">Once initiated, the clock runs continuously and will auto-submit when time reaches zero.</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-3">
                            <i class="bi bi-check2-circle text-success fs-5 mt-0.5"></i>
                            <div>
                                <span class="d-block fw-semibold text-dark small">Single Best Score Recorded</span>
                                <span class="text-muted small" style="line-height: 1.4;">
                                    Passing threshold is <?= esc($quiz['passing_score'] ? $quiz['passing_score'] . '%' : '75%') ?>. Your highest recorded score will appear in your gradebook.
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-3">
                            <i class="bi bi-cloud-arrow-up text-primary fs-5 mt-0.5"></i>
                            <div>
                                <span class="d-block fw-semibold text-dark small">Live Selection Saving</span>
                                <span class="text-muted small" style="line-height: 1.4;">Selections are recorded instantly. Review the question navigator before submitting.</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-3">
                            <i class="bi bi-mortarboard text-secondary fs-5 mt-0.5"></i>
                            <div>
                                <span class="d-block fw-semibold text-dark small">Institutional Honor Code</span>
                                <span class="text-muted small" style="line-height: 1.4;">All student submissions are logged and governed by Triple T University academic integrity bylaws.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Past Attempts History Table -->
            <?php if (!empty($attempts)): ?>
                <div class="lms-card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                    <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Your Past Attempts</h5>
                            <small class="text-muted">Detailed log of previous submissions and scores</small>
                        </div>
                        <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-semibold">
                            <?= count($attempts) ?> Attempt<?= count($attempts) > 1 ? 's' : '' ?> Recorded
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.92rem;">
                            <thead class="table-light text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em; color: #64748b;">
                                <tr>
                                    <th class="ps-4 py-3">Attempt</th>
                                    <th class="py-3">Date & Time</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3">Score & Rating</th>
                                    <th class="text-end pe-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attempts as $attempt): 
                                    $scoreNum = (float)($attempt['score'] ?? 0);
                                    $totalPts = (float)($total_points > 0 ? $total_points : 1);
                                    $pct = round(($scoreNum / $totalPts) * 100);
                                    $passingThreshold = (float)($quiz['passing_score'] ?: 75);
                                    $isPassed = $pct >= $passingThreshold;
                                ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="attempt-badge rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.82rem;">
                                                    #<?= esc($attempt['attempt_number']) ?>
                                                </div>
                                                <span class="fw-bold text-dark">Attempt <?= esc($attempt['attempt_number']) ?></span>
                                            </div>
                                        </td>
                                        <td class="py-3 text-muted">
                                            <i class="bi bi-calendar3 me-1 small"></i>
                                            <?= esc(!empty($attempt['submitted_at']) ? date('M d, Y &bull; h:i A', strtotime($attempt['submitted_at'])) : date('M d, Y &bull; h:i A', strtotime($attempt['started_at']))) ?>
                                        </td>
                                        <td class="py-3">
                                            <?php if ($attempt['status'] === 'graded'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Graded
                                                </span>
                                            <?php elseif ($attempt['status'] === 'submitted'): ?>
                                                <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1">
                                                    <i class="bi bi-hourglass-split me-1"></i>Awaiting Review
                                                </span>
                                            <?php elseif ($attempt['status'] === 'in_progress'): ?>
                                                <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning border-opacity-25 rounded-pill px-2.5 py-1">
                                                    <i class="bi bi-clock me-1"></i>In Progress
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2.5 py-1">
                                                    <?= ucfirst(esc($attempt['status'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3">
                                            <?php if ($attempt['status'] === 'graded'): ?>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-bold <?= esc($isPassed ? 'text-success' : 'text-danger') ?>" style="font-size: 1.05rem;">
                                                        <?= esc(number_format($scoreNum, 1)) ?> <span class="text-muted fw-normal small">/ <?= esc(number_format($totalPts, 1)) ?></span>
                                                    </span>
                                                    <span class="badge <?= esc($isPassed ? 'bg-success' : 'bg-danger') ?> bg-opacity-10 <?= esc($isPassed ? 'text-success' : 'text-danger') ?> border <?= esc($isPassed ? 'border-success' : 'border-danger') ?> border-opacity-25 rounded-pill px-2 py-0.5 small">
                                                        <?= esc($pct) ?>% &bull; <?= $isPassed ? 'Passed' : 'Failed' ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">&mdash; <?= $attempt['status'] === 'submitted' ? 'Score pending review' : 'Pending Submission' ?> &mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4 py-3">
                                            <?php if ($attempt['status'] !== 'in_progress'): ?>
                                                <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/result/<?= esc($attempt['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-xs">
                                                    <span>View Results</span>
                                                    <i class="bi bi-arrow-right-short fs-6"></i>
                                                </a>
                                            <?php else: ?>
                                                <form action="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/start" method="POST" class="d-inline m-0">
                                                    <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token'] ?? '') ?>">
                                                    <button type="submit" class="btn btn-sm btn-warning rounded-pill px-3 py-1.5 fw-semibold shadow-xs">
                                                        Resume &rarr;
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<style>
.shadow-xs {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.transition-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.transition-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06) !important;
}
</style>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>
