<?php require_once __DIR__ . '/../layout_header.php'; ?>
<?php use App\Services\Quiz\QuizQuestionValidator; ?>
<?php
$baseUrl = '/sia/lms/faculty/course/' . (int)$course['lms_course_id'] . '/quizzes/' . (int)$quiz['id'];
$studentName = trim(($attempt['first_name'] ?? '') . ' ' . ($attempt['last_name'] ?? ''));
$totalPoints = array_sum(array_map(fn ($q) => (float)$q['points'], $questions));
$pending = count(array_filter($answers, fn ($a) => !empty($a['needs_review'])));
$hasTextQuestions = false;
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 align-items-center">
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="text-decoration-none text-muted">Quizzes</a></li>
                <li class="breadcrumb-item"><a href="<?= esc($baseUrl) ?>/results" class="text-decoration-none text-muted">Quiz Results</a></li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Review Attempt</li>
            </ol>
        </nav>
        <a href="<?= esc($baseUrl) ?>/results" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Results
        </a>
    </div>

    <?php require __DIR__ . '/_flash.php'; ?>

    <div class="lms-card p-4 mb-4 border-0 shadow-sm bg-white rounded-4 d-flex flex-wrap justify-content-between gap-3">
        <div>
            <h3 class="h5 fw-bold text-dark mb-1"><?= esc($studentName) ?> &bull; Attempt #<?= (int)$attempt['attempt_number'] ?></h3>
            <p class="text-muted small mb-0"><?= esc($quiz['title']) ?></p>
        </div>
        <div class="text-end">
            <div class="fw-bold fs-5"><?= esc(number_format((float)$attempt['score'], 2)) ?> / <?= esc(number_format($totalPoints, 2)) ?> pts</div>
            <?php if ($pending > 0): ?>
                <span class="badge bg-warning text-dark rounded-pill"><?= $pending ?> answer(s) awaiting review &bull; score is provisional</span>
            <?php else: ?>
                <span class="badge bg-success rounded-pill">Graded</span>
            <?php endif; ?>
        </div>
    </div>

    <p class="small text-muted">
        Typed answers are matched against the accepted answers ignoring extra spaces, trailing punctuation and (unless the question is case-sensitive) letter case.
        Near misses and answers to "manual review" questions are flagged here. You can set the points for any typed answer; choice questions are graded automatically.
    </p>

    <form method="POST" action="<?= esc($baseUrl) ?>/attempts/<?= (int)$attempt['id'] ?>/review">
        <?= getCsrfInput() ?>
        <?php foreach ($questions as $n => $q):
            $answer = $answers[(int)$q['id']] ?? null;
            $isText = QuizQuestionValidator::isTextType($q['question_type']);
            $hasTextQuestions = $hasTextQuestions || $isText;
            $flagged = $answer && !empty($answer['needs_review']);
        ?>
            <div class="lms-card border-0 shadow-sm rounded-4 mb-3 bg-white <?= $flagged ? 'border border-warning' : '' ?>">
                <div class="p-4">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                        <span class="small fw-bold text-muted">Question <?= $n + 1 ?> &bull; <?= esc(QuizQuestionValidator::typeLabel($q['question_type'])) ?> &bull; <?= esc((float)$q['points']) ?> pts</span>
                        <?php if ($flagged): ?>
                            <span class="badge bg-warning text-dark rounded-pill">Needs review</span>
                        <?php elseif ($answer && !empty($answer['reviewed_at'])): ?>
                            <span class="badge bg-light text-secondary border rounded-pill">Reviewed</span>
                        <?php endif; ?>
                    </div>
                    <p class="fw-semibold mb-3"><?= nl2br(esc($q['question_text'])) ?></p>

                    <?php if ($isText): ?>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <div class="small text-muted">Student answer</div>
                                <div class="fw-semibold"><?= $answer && $answer['answer_text'] !== null ? esc($answer['answer_text']) : '<span class="text-muted">No answer</span>' ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted">Accepted answers<?= !empty($q['case_sensitive']) ? ' (case-sensitive)' : '' ?></div>
                                <div><?= esc(implode(', ', array_column($q['choices'], 'choice_text'))) ?></div>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted" for="awarded_<?= (int)$q['id'] ?>">Points awarded</label>
                                <input type="number" class="form-control form-control-sm" id="awarded_<?= (int)$q['id'] ?>" name="awarded[<?= (int)$q['id'] ?>]" value="<?= esc($answer ? (float)$answer['points_awarded'] : 0) ?>" min="0" max="<?= esc((float)$q['points']) ?>" step="0.25" <?= $answer ? '' : 'disabled' ?>>
                            </div>
                        </div>
                    <?php else:
                        $chosen = null;
                        foreach ($q['choices'] as $c) {
                            if ($answer && (int)$answer['lms_question_choice_id'] === (int)$c['id']) {
                                $chosen = $c;
                            }
                        }
                    ?>
                        <div class="small">
                            Student chose: <span class="fw-semibold"><?= $chosen ? esc($chosen['choice_text']) : 'No answer' ?></span>
                            &bull; <?= $answer && $answer['is_correct'] ? '<span class="text-success fw-semibold">Correct</span>' : '<span class="text-danger fw-semibold">Incorrect</span>' ?>
                            &bull; <?= esc($answer ? (float)$answer['points_awarded'] : 0) ?> pts
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($hasTextQuestions): ?>
            <div class="text-end">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Save Review and Recalculate Score</button>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
