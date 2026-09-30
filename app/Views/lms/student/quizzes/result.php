<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container-fluid py-4 px-md-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0 py-2 px-3 bg-white rounded-3 border shadow-xs align-items-center" style="font-size: 0.88rem;">
            <li class="breadcrumb-item"><a href="/sia/lms/student/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i>Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/student/my_courses.php" class="text-decoration-none text-muted">My Courses</a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/student/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-primary fw-medium"><?= htmlspecialchars($course['subject_code']) ?></a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($quiz['title']) ?></a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Attempt #<?= esc($attempt['attempt_number']) ?> Result</li>
        </ol>
    </nav>

    <?php
    $totalPossible = 0.0;
    $correctCount = 0;
    $totalQuestions = count($questions);

    foreach ($questions as $q) {
        $totalPossible += (float)$q['points'];
        $ans = $answers[$q['id']] ?? null;
        if ($ans && $ans['is_correct']) {
            $correctCount++;
        }
    }

    $attemptScore = (float)($attempt['score'] ?? 0);
    $percentage = $totalPossible > 0 ? round(($attemptScore / $totalPossible) * 100) : 0;
    $passingThreshold = (float)($quiz['passing_score'] ?: 75);
    $passed = $percentage >= $passingThreshold;
    $incorrectCount = $totalQuestions - $correctCount;
    ?>

    <div class="row g-4 justify-content-center">
        <div class="col-xl-9 col-lg-10">
            
            <!-- Hero Score Results Card -->
            <div class="lms-card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white text-center position-relative">
                <div class="py-5 px-4 position-relative" style="background: <?= $passed ? 'linear-gradient(180deg, rgba(25, 135, 84, 0.06) 0%, rgba(255, 255, 255, 1) 100%)' : 'linear-gradient(180deg, rgba(220, 53, 69, 0.06) 0%, rgba(255, 255, 255, 1) 100%)' ?>;">
                    
                    <div class="d-inline-flex align-items-center gap-2 mb-3">
                        <span class="badge <?= $passed ? 'bg-success' : 'bg-danger' ?> bg-opacity-10 <?= $passed ? 'text-success' : 'text-danger' ?> border <?= $passed ? 'border-success' : 'border-danger' ?> border-opacity-25 px-3 py-1.5 rounded-pill fw-bold" style="font-size: 0.85rem;">
                            <i class="bi <?= $passed ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-1"></i>
                            <?= $passed ? 'Assessment Passed' : 'Assessment Failed' ?>
                        </span>
                        <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-semibold" style="font-size: 0.85rem;">
                            Attempt #<?= esc($attempt['attempt_number']) ?>
                        </span>
                    </div>

                    <h1 class="h2 fw-bold text-dark mb-2"><?= htmlspecialchars($quiz['title']) ?></h1>
                    <p class="text-muted small mb-4">
                        <i class="bi bi-clock-history me-1"></i> Submitted on <?= !empty($attempt['submitted_at']) ? date('F d, Y &bull; h:i A', strtotime($attempt['submitted_at'])) : 'Recently' ?>
                    </p>

                    <!-- Score Badge Dial -->
                    <div class="d-inline-flex flex-column align-items-center justify-content-center rounded-circle shadow-sm border <?= $passed ? 'border-success' : 'border-danger' ?> border-3 mb-4 bg-white" style="width: 180px; height: 180px;">
                        <span class="fs-1 fw-bolder <?= $passed ? 'text-success' : 'text-danger' ?> lh-1">
                            <?= number_format($attemptScore, 1) ?>
                        </span>
                        <span class="text-muted small mt-1">out of <?= number_format($totalPossible, 1) ?> pts</span>
                        <span class="badge bg-light text-secondary border rounded-pill mt-2 px-2 py-0.5 small fw-semibold">
                            <?= $percentage ?>%
                        </span>
                    </div>

                    <!-- Mini KPI Summary Row -->
                    <div class="row g-3 justify-content-center max-w-700 mx-auto mt-2">
                        <div class="col-6 col-sm-3">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="text-muted text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.05em;">Correct</div>
                                <div class="fw-bold text-success fs-5"><?= $correctCount ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="text-muted text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.05em;">Incorrect</div>
                                <div class="fw-bold text-danger fs-5"><?= $incorrectCount ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="text-muted text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.05em;">Passing Mark</div>
                                <div class="fw-bold text-dark fs-5"><?= $passingThreshold ?>%</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="text-muted text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.05em;">Accuracy</div>
                                <div class="fw-bold text-primary fs-5"><?= $percentage ?>%</div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                        <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                            <i class="bi bi-arrow-left"></i>
                            <span>Quiz Overview</span>
                        </a>
                        <a href="/sia/lms/student/course.php?id=<?= esc($course['lms_course_id']) ?>" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                            <i class="bi bi-journal-bookmark-fill"></i>
                            <span>Return to Course</span>
                        </a>
                    </div>

                </div>
            </div>

            <!-- Detailed Answer Breakdown -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-journal-check text-primary"></i>
                    Question Breakdown & Review
                </h4>
                <span class="text-muted small">
                    <?= $correctCount ?> of <?= $totalQuestions ?> answered correctly
                </span>
            </div>

            <?php foreach ($questions as $index => $q): 
                $myAns = $answers[$q['id']] ?? null;
                $isCorrect = $myAns && $myAns['is_correct'];
            ?>
                <div class="lms-card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4 bg-white position-relative overflow-hidden" style="border-left: 5px solid <?= $isCorrect ? '#198754' : '#dc3545' ?> !important;">
                    
                    <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge <?= $isCorrect ? 'bg-success' : 'bg-danger' ?> bg-opacity-10 <?= $isCorrect ? 'text-success' : 'text-danger' ?> border <?= $isCorrect ? 'border-success' : 'border-danger' ?> border-opacity-25 px-3 py-1.5 rounded-pill fw-bold" style="font-size: 0.85rem;">
                                Question <?= $index + 1 ?>
                            </span>
                            <span class="badge bg-light text-secondary border px-2.5 py-1.5 rounded-pill small">
                                <?= esc($q['points']) ?> Pts
                            </span>
                        </div>

                        <div>
                            <?php if ($isCorrect): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1.5 rounded-pill fw-bold">
                                    <i class="bi bi-check-lg me-1"></i>+<?= esc($myAns['points_awarded']) ?> Pts (Correct)
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-1.5 rounded-pill fw-bold">
                                    <i class="bi bi-x-lg me-1"></i>0.0 Pts (Incorrect)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark lh-base mb-4" style="font-size: 1.12rem;">
                        <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                    </h5>

                    <!-- Choice Options -->
                    <div class="d-flex flex-column gap-2.5">
                        <?php foreach ($q['choices'] as $cIndex => $c): 
                            $letter = chr(65 + $cIndex);
                            $isSelected = $myAns && $myAns['lms_question_choice_id'] == $c['id'];
                            $isChoiceCorrect = (bool)$c['is_correct'];

                            $cardClass = 'bg-white border-light-subtle';
                            $badgeClass = 'bg-light text-secondary border';
                            if ($isChoiceCorrect) {
                                $cardClass = 'bg-success bg-opacity-10 border-success border-opacity-50 text-success';
                                $badgeClass = 'bg-success text-white border-success';
                            } elseif ($isSelected && !$isChoiceCorrect) {
                                $cardClass = 'bg-danger bg-opacity-10 border-danger border-opacity-50 text-danger';
                                $badgeClass = 'bg-danger text-white border-danger';
                            }
                        ?>
                            <div class="p-3 rounded-3 border d-flex align-items-center gap-3 <?= esc($cardClass) ?>" style="transition: all 0.2s ease;">
                                <div class="choice-letter-badge rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0 <?= esc($badgeClass) ?>" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                    <?= $letter ?>
                                </div>
                                <div class="flex-grow-1 fw-medium <?= esc($isChoiceCorrect ? 'text-success fw-bold' : ($isSelected ? 'text-danger' : 'text-dark')) ?>">
                                    <?= htmlspecialchars($c['choice_text']) ?>
                                </div>
                                <div class="ms-auto flex-shrink-0">
                                    <?php if ($isChoiceCorrect): ?>
                                        <span class="badge bg-success rounded-pill px-3 py-1.5 small fw-semibold">
                                            <i class="bi bi-check2 me-1"></i>Correct Answer
                                        </span>
                                    <?php elseif ($isSelected && !$isChoiceCorrect): ?>
                                        <span class="badge bg-danger rounded-pill px-3 py-1.5 small fw-semibold">
                                            <i class="bi bi-x-circle me-1"></i>Your Answer
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (!$myAns || !$myAns['lms_question_choice_id']): ?>
                            <div class="alert alert-warning py-2.5 px-3 rounded-3 mt-2 mb-0 small d-flex align-items-center gap-2">
                                <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                                <span>You did not select an answer for this question during the attempt.</span>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>

            <!-- Bottom Action Navigation -->
            <div class="text-center py-4 mb-5">
                <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>" class="btn btn-outline-primary btn-lg rounded-pill px-5 fw-bold shadow-xs">
                    <i class="bi bi-arrow-left me-1"></i> Back to Quiz Overview
                </a>
            </div>

        </div>
    </div>
</div>

<style>
.shadow-xs {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
</style>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>
