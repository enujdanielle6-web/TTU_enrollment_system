<?php require_once __DIR__ . '/../../../../Views/components/header.php'; ?>

<div class="container py-5">
    <div class="mb-4">
        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Back to Quizzes
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4 bg-light border-bottom rounded-top-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1"><?= htmlspecialchars($quiz['title']) ?></h2>
                <p class="text-muted mb-0">Total Points: <?= number_format($total_points, 2) ?> &bull; Questions: <?= count($questions) ?></p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                <i class="bi bi-plus-lg me-1"></i> Add Question
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <?php if (empty($questions)): ?>
                <div class="text-center py-5 text-muted bg-white rounded-4 shadow-sm">
                    <i class="bi bi-ui-radios-grid fs-1 d-block mb-3 opacity-50"></i>
                    No questions added yet.
                </div>
            <?php else: ?>
                <?php foreach ($questions as $index => $q): ?>
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="fw-bold mb-0">
                                    <span class="text-primary me-2"><?= esc($index + 1) ?>.</span>
                                    <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                                </h5>
                                <span class="badge bg-secondary"><?= esc($q['points']) ?> pts</span>
                            </div>
                            
                            <div class="ps-4 ms-2">
                                <?php if ($q['question_type'] === 'multiple_choice'): ?>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($q['choices'] as $c): ?>
                                            <li class="mb-2 <?= esc($c['is_correct'] ? 'text-success fw-bold' : 'text-muted') ?>">
                                                <i class="bi <?= esc($c['is_correct'] ? 'bi-check-circle-fill' : 'bi-circle') ?> me-2"></i>
                                                <?= htmlspecialchars($c['choice_text']) ?>
                                                <?= esc($c['is_correct'] ? ' <span class="badge bg-success ms-1">Correct Answer</span>' : '') ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php elseif ($q['question_type'] === 'true_false'): ?>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($q['choices'] as $c): ?>
                                            <li class="mb-2 <?= esc($c['is_correct'] ? 'text-success fw-bold' : 'text-muted') ?>">
                                                <i class="bi <?= esc($c['is_correct'] ? 'bi-check-circle-fill' : 'bi-circle') ?> me-2"></i>
                                                <?= htmlspecialchars($c['choice_text']) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/questions/store" method="POST">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold">Add Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Question Type</label>
                        <select name="question_type" class="form-select" id="questionTypeSelect" onchange="toggleQuestionType()">
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True / False</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Question Text</label>
                        <textarea name="question_text" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Points</label>
                        <input type="number" name="points" class="form-control" value="1" step="0.5" min="0">
                    </div>

                    <hr class="my-4">

                    <!-- Multiple Choice Section -->
                    <div id="mcSection">
                        <label class="form-label fw-bold mb-3">Choices & Correct Answer</label>
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <div class="input-group mb-2">
                                <div class="input-group-text bg-white">
                                    <input class="form-check-input mt-0" type="radio" name="correct_mc" value="<?= esc($i) ?>" <?= esc($i===1 ? 'checked' : '') ?> title="Mark as correct answer">
                                </div>
                                <input type="text" name="mc_choice_<?= esc($i) ?>" class="form-control" placeholder="Choice <?= esc($i) ?> text" <?= esc($i <= 2 ? 'required' : '') ?>>
                            </div>
                        <?php endfor; ?>
                        <div class="form-text mt-2">Select the radio button next to the correct answer. At least 2 choices are required.</div>
                    </div>

                    <!-- True/False Section -->
                    <div id="tfSection" style="display: none;">
                        <label class="form-label fw-bold mb-3">Correct Answer</label>
                        <div class="d-flex gap-4">
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
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Question</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleQuestionType() {
    const type = document.getElementById('questionTypeSelect').value;
    if (type === 'multiple_choice') {
        document.getElementById('mcSection').style.display = 'block';
        document.getElementById('tfSection').style.display = 'none';
        // enable required
        document.querySelector('input[name="mc_choice_1"]').required = true;
        document.querySelector('input[name="mc_choice_2"]').required = true;
    } else {
        document.getElementById('mcSection').style.display = 'none';
        document.getElementById('tfSection').style.display = 'block';
        // disable required
        document.querySelector('input[name="mc_choice_1"]').required = false;
        document.querySelector('input[name="mc_choice_2"]').required = false;
    }
}
</script>

<?php require_once __DIR__ . '/../../../../Views/components/footer.php'; ?>
