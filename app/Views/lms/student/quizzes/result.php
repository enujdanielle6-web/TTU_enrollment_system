<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sia/lms/student/dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/student/course.php?id=<?= $course['lms_course_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($course['subject_code']) ?></a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/student/course/<?= $course['lms_course_id'] ?>/quizzes/<?= $quiz['id'] ?>" class="text-decoration-none">Quiz</a></li>
            <li class="breadcrumb-item active" aria-current="page">Result</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            
            <div class="lms-card p-5 border-0 shadow-sm rounded-4 text-center mb-4">
                <h1 class="h3 fw-bold text-dark mb-2"><?= htmlspecialchars($quiz['title']) ?> - Attempt #<?= $attempt['attempt_number'] ?></h1>
                <p class="text-muted mb-4">Submitted on <?= date('M d, Y h:i A', strtotime($attempt['submitted_at'])) ?></p>

                <?php
                $totalPossible = 0;
                foreach ($questions as $q) $totalPossible += $q['points'];
                $percentage = $totalPossible > 0 ? ($attempt['score'] / $totalPossible) * 100 : 0;
                
                $passed = true;
                if ($quiz['passing_score'] !== null && $percentage < $quiz['passing_score']) {
                    $passed = false;
                }
                ?>

                <div class="d-inline-block border rounded-circle p-5 mb-3 shadow-sm <?= $passed ? 'border-success' : 'border-danger' ?>" style="width: 200px; height: 200px; display: flex; align-items: center; justify-content: center;">
                    <div>
                        <div class="fs-1 fw-bold <?= $passed ? 'text-success' : 'text-danger' ?>"><?= $attempt['score'] ?></div>
                        <div class="text-muted small">out of <?= $totalPossible ?></div>
                    </div>
                </div>

                <?php if ($quiz['passing_score'] !== null): ?>
                    <h5 class="fw-bold mt-3 <?= $passed ? 'text-success' : 'text-danger' ?>">
                        <?= $passed ? '<i class="bi bi-check-circle-fill me-1"></i> Passed' : '<i class="bi bi-x-circle-fill me-1"></i> Failed' ?>
                    </h5>
                    <p class="text-muted small">Passing score: <?= $quiz['passing_score'] ?>%</p>
                <?php endif; ?>
            </div>

            <h4 class="fw-bold mb-4">Review Answers</h4>

            <?php foreach ($questions as $index => $q): 
                $myAns = $answers[$q['id']] ?? null;
            ?>
                <div class="lms-card p-4 border border-2 shadow-sm rounded-4 mb-4 <?= ($myAns && $myAns['is_correct']) ? 'border-success border-opacity-50' : 'border-danger border-opacity-50' ?>">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <h5 class="fw-bold mb-0 lh-base">
                            <span class="text-primary me-2"><?= $index + 1 ?>.</span>
                            <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                        </h5>
                        <div class="text-end">
                            <?php if ($myAns && $myAns['is_correct']): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fs-6 mb-1"><i class="bi bi-check"></i> <?= $myAns['points_awarded'] ?> / <?= $q['points'] ?> pts</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fs-6 mb-1"><i class="bi bi-x"></i> 0 / <?= $q['points'] ?> pts</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="ps-4 ms-2">
                        <?php foreach ($q['choices'] as $c): 
                            $isSelected = $myAns && $myAns['lms_question_choice_id'] == $c['id'];
                            $isCorrect = $c['is_correct'];
                        ?>
                            <div class="mb-3 p-3 rounded-3 border <?= $isCorrect ? 'bg-success bg-opacity-10 border-success' : ($isSelected ? 'bg-danger bg-opacity-10 border-danger' : 'bg-light') ?>">
                                <div class="d-flex align-items-center">
                                    <i class="bi <?= $isSelected ? 'bi-record-circle' : 'bi-circle' ?> me-3 fs-5 <?= $isCorrect ? 'text-success' : ($isSelected ? 'text-danger' : 'text-muted') ?>"></i>
                                    <span class="fs-6 <?= $isCorrect ? 'fw-bold text-success' : ($isSelected ? 'text-danger' : '') ?>">
                                        <?= htmlspecialchars($c['choice_text']) ?>
                                    </span>
                                    
                                    <?php if ($isCorrect): ?>
                                        <span class="badge bg-success ms-auto">Correct Answer</span>
                                    <?php elseif ($isSelected && !$isCorrect): ?>
                                        <span class="badge bg-danger ms-auto">Your Answer</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (!$myAns || !$myAns['lms_question_choice_id']): ?>
                            <div class="alert alert-warning py-2 mt-3 mb-0 small"><i class="bi bi-exclamation-triangle me-1"></i> You did not answer this question.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="text-center mt-5 mb-4">
                <a href="/sia/lms/student/course/<?= $course['lms_course_id'] ?>/quizzes/<?= $quiz['id'] ?>" class="btn btn-outline-primary btn-lg px-5 fw-bold">Back to Quiz Details</a>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>
