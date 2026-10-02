<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Breadcrumb & Return -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 align-items-center">
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/faculty/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($course['subject_code']) ?></a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="text-decoration-none text-muted">Quizzes</a></li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Question Builder</li>
            </ol>
        </nav>
        <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Quizzes
        </a>
    </div>

    <!-- Quiz Context Hero Card -->
    <div class="lms-card p-4 mb-4 border-0 shadow-sm bg-white rounded-4 position-relative overflow-hidden">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-1.5 rounded-pill fw-bold">
                        <i class="bi bi-pencil-square me-1"></i><?= htmlspecialchars($course['subject_code']) ?>
                    </span>
                    <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-semibold">
                        <?= count($questions) ?> <?= count($questions) === 1 ? 'Question' : 'Questions' ?>
                    </span>
                    <span class="badge bg-light text-primary border px-3 py-1.5 rounded-pill fw-bold">
                        Total Points: <?= number_format($total_points, 1) ?> Pts
                    </span>
                </div>
                <h3 class="h4 fw-bold text-dark mb-1"><?= htmlspecialchars($quiz['title']) ?></h3>
                <?php if (!empty($quiz['description'])): ?>
                    <p class="text-muted small mb-0 mt-1"><?= nl2br(htmlspecialchars($quiz['description'])) ?></p>
                <?php endif; ?>
            </div>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                <i class="bi bi-plus-lg"></i>
                <span>Add Question</span>
            </button>
        </div>
    </div>

    <!-- Questions List -->
    <div class="row g-4">
        <div class="col-12">
            <?php if (empty($questions)): ?>
                <div class="lms-card p-5 text-center border-0 shadow-sm bg-light rounded-4">
                    <i class="bi bi-ui-radios-grid text-muted opacity-50 mb-3" style="font-size: 3.5rem;"></i>
                    <h5 class="fw-bold text-dark">No Questions In This Quiz</h5>
                    <p class="text-muted mb-4" style="max-width: 480px; margin: 0 auto;">
                        Build your assessment question bank by adding multiple choice or true/false items with defined points and correct answers.
                    </p>
                    <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                        <i class="bi bi-plus-lg me-1"></i> Add First Question
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($questions as $index => $q): 
                    $isMC = ($q['question_type'] === 'multiple_choice');
                ?>
                    <div class="lms-card border-0 shadow-sm rounded-4 mb-3 bg-white overflow-hidden">
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3 gap-3">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; flex-shrink: 0;">
                                        <?= esc($index + 1) ?>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                                                <?= esc($isMC ? 'Multiple Choice' : 'True / False') ?>
                                            </span>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-bold">
                                                <?= esc($q['points']) ?> <?= (float)$q['points'] === 1.0 ? 'Point' : 'Points' ?>
                                            </span>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-0 fs-6 lh-base">
                                            <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                                        </h5>
                                    </div>
                                </div>
                                <form method="POST" action="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/questions/<?= esc($q['id']) ?>/delete" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this question?');">
                                    <?= getCsrfInput() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1" title="Delete Question">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Choices Grid -->
                            <div class="ps-md-5 ms-md-2 mt-3">
                                <div class="row g-2">
                                    <?php foreach ($q['choices'] as $c): 
                                        $isCorrect = (bool)$c['is_correct'];
                                    ?>
                                        <div class="col-md-6">
                                            <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between <?= esc($isCorrect ? 'bg-success bg-opacity-10 border-success border-opacity-50 text-success' : 'bg-light text-dark') ?>">
                                                <div class="d-flex align-items-center gap-2 min-w-0">
                                                    <i class="bi <?= esc($isCorrect ? 'bi-check-circle-fill text-success fs-5' : 'bi-circle text-muted') ?>"></i>
                                                    <span class="text-truncate fw-medium"><?= htmlspecialchars($c['choice_text']) ?></span>
                                                </div>
                                                <?php if ($isCorrect): ?>
                                                    <span class="badge bg-success rounded-pill px-2 py-0.5 small flex-shrink-0 ms-2">Correct</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form action="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/questions/store" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                <?= getCsrfInput() ?>
                <div class="modal-header border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box-sm bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-plus-circle"></i>
                        </div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Add Question</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-dark">Question Type</label>
                            <select name="question_type" class="form-select" id="questionTypeSelect" onchange="toggleQuestionType()">
                                <option value="multiple_choice">Multiple Choice (Single Answer)</option>
                                <option value="true_false">True / False</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark">Points</label>
                            <input type="number" name="points" class="form-control" value="1" step="0.5" min="0.5" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Question Text <span class="text-danger">*</span></label>
                        <textarea name="question_text" class="form-control" rows="3" placeholder="Type the question or problem prompt here..." required></textarea>
                    </div>

                    <hr class="my-3">

                    <!-- Multiple Choice Section -->
                    <div id="mcSection">
                        <label class="form-label fw-bold text-dark mb-2">Options &amp; Correct Answer</label>
                        <p class="text-muted small mb-3">Type each choice and mark the radio button beside the correct option.</p>
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <div class="input-group mb-2">
                                <div class="input-group-text bg-white">
                                    <input class="form-check-input mt-0" type="radio" name="correct_mc" value="<?= esc($i) ?>" <?= esc($i === 1 ? 'checked' : '') ?> title="Mark as correct answer">
                                </div>
                                <input type="text" name="mc_choice_<?= esc($i) ?>" class="form-control" placeholder="Choice <?= esc($i) ?> text" <?= esc($i <= 2 ? 'required' : '') ?>>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- True/False Section -->
                    <div id="tfSection" style="display: none;">
                        <label class="form-label fw-bold text-dark mb-2">Correct Answer</label>
                        <div class="d-flex gap-4 p-3 bg-light rounded-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="correct_tf" id="tfTrue" value="true" checked>
                                <label class="form-check-label fw-bold text-success" for="tfTrue">True</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="correct_tf" id="tfFalse" value="false">
                                <label class="form-check-label fw-bold text-danger" for="tfFalse">False</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Save Question</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleQuestionType() {
    const type = document.getElementById('questionTypeSelect').value;
    const mcChoice1 = document.querySelector('input[name="mc_choice_1"]');
    const mcChoice2 = document.querySelector('input[name="mc_choice_2"]');
    if (type === 'multiple_choice') {
        document.getElementById('mcSection').style.display = 'block';
        document.getElementById('tfSection').style.display = 'none';
        if (mcChoice1) mcChoice1.required = true;
        if (mcChoice2) mcChoice2.required = true;
    } else {
        document.getElementById('mcSection').style.display = 'none';
        document.getElementById('tfSection').style.display = 'block';
        if (mcChoice1) mcChoice1.required = false;
        if (mcChoice2) mcChoice2.required = false;
    }
}
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
