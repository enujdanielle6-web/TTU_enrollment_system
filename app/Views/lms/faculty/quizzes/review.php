<?php require_once __DIR__ . '/../layout_header.php'; ?>
<?php use App\Services\Quiz\QuizQuestionValidator; ?>
<?php
$baseUrl = BASE_PATH . '/lms/faculty/course/' . (int)$course['lms_course_id'] . '/quizzes/' . (int)$quiz['id'];
$draftQuestions = $draft['questions'] ?? [];
$draftErrors = $draft['errors'] ?? [];
$errorCount = count($draftErrors);
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 align-items-center">
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/faculty/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="text-decoration-none text-muted">Quizzes</a></li>
                <li class="breadcrumb-item"><a href="<?= esc($baseUrl) ?>/questions" class="text-decoration-none text-muted">Question Builder</a></li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Review Draft</li>
            </ol>
        </nav>
        <a href="<?= esc($baseUrl) ?>/questions" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Questions
        </a>
    </div>

    <?php require __DIR__ . '/_flash.php'; ?>

    <div class="lms-card p-4 mb-4 border-0 shadow-sm bg-white rounded-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-semibold mb-2"><?= esc($draft['source_label'] ?? 'Draft') ?></span>
                <h3 class="h5 fw-bold text-dark mb-1">Review questions for "<?= esc($quiz['title']) ?>"</h3>
                <p class="text-muted small mb-0">
                    Nothing has been saved yet. Edit or remove any question, then save. The draft is added after the quiz's <?= (int)$existing_count ?> existing question(s).
                    <?php if (($draft['source'] ?? '') === 'generator'): ?>
                        Each generated question shows the passage it came from; check the answer key against it.
                    <?php endif; ?>
                </p>
            </div>
            <div class="text-end">
                <div class="fw-bold fs-5 text-dark"><span id="draftCount"><?= count($draftQuestions) ?></span> question(s)</div>
                <?php if ($errorCount > 0): ?>
                    <div class="small text-danger fw-semibold"><?= $errorCount ?> need fixing</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($draft['notices'])): ?>
            <ul class="small text-muted mt-3 mb-0 ps-3">
                <?php foreach ($draft['notices'] as $notice): ?>
                    <li><?= esc($notice) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <form method="POST" action="<?= esc($baseUrl) ?>/questions/review/save" id="draftForm">
        <?= getCsrfInput() ?>

        <?php foreach ($draftQuestions as $i => $q):
            $type = $q['type'] ?? 'multiple_choice';
            $errors = $draftErrors[$i] ?? [];
            $choices = array_pad(array_values($q['choices'] ?? []), QuizQuestionValidator::MAX_CHOICES, '');
            $correctIndex = $q['correct_index'] ?? null;
            $field = 'questions[' . (int)$i . ']';
        ?>
            <div class="lms-card border-0 shadow-sm rounded-4 mb-3 bg-white draft-card <?= $errors ? 'border border-danger' : '' ?>" data-index="<?= (int)$i ?>">
                <div class="p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1.5 fw-bold draft-number">#<?= (int)$i + 1 ?></span>
                            <?php if (!empty($q['csv_row'])): ?>
                                <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1">CSV row <?= (int)$q['csv_row'] ?></span>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-remove-draft">
                            <i class="bi bi-trash me-1"></i> Remove
                        </button>
                    </div>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger py-2 px-3 small rounded-3">
                            <?php foreach ($errors as $error): ?>
                                <div><i class="bi bi-exclamation-triangle-fill me-1"></i><?= esc($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($q['source_excerpt'])): ?>
                        <div class="small bg-light border rounded-3 p-2 mb-3">
                            <span class="fw-semibold text-muted"><?= esc($q['source_reference'] ?? 'Source') ?>:</span>
                            <span class="fst-italic">"<?= esc($q['source_excerpt']) ?>"</span>
                        </div>
                    <?php endif; ?>
                    <input type="hidden" name="<?= $field ?>[source_reference]" value="<?= esc($q['source_reference'] ?? '') ?>">
                    <input type="hidden" name="<?= $field ?>[source_excerpt]" value="<?= esc($q['source_excerpt'] ?? '') ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Type</label>
                            <select name="<?= $field ?>[type]" class="form-select form-select-sm draft-type">
                                <?php foreach (QuizQuestionValidator::TYPES as $value => $label): ?>
                                    <option value="<?= esc($value) ?>" <?= $value === $type ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Points</label>
                            <input type="number" name="<?= $field ?>[points]" class="form-control form-control-sm" value="<?= esc($q['points'] ?? 1) ?>" step="0.25" min="0.25" max="100" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Question</label>
                        <textarea name="<?= $field ?>[question_text]" class="form-control" rows="2" required><?= esc($q['question_text'] ?? '') ?></textarea>
                        <div class="form-text draft-blank-hint">For fill in the blank, mark the blank with ___ (three underscores).</div>
                    </div>

                    <div class="draft-section" data-for="multiple_choice">
                        <label class="form-label small fw-bold">Choices (select the correct one)</label>
                        <?php foreach ($choices as $c => $choiceText): ?>
                            <div class="input-group input-group-sm mb-1">
                                <div class="input-group-text bg-white">
                                    <input class="form-check-input mt-0" type="radio" name="<?= $field ?>[correct_index]" value="<?= (int)$c ?>" <?= $correctIndex !== null && (int)$correctIndex === $c ? 'checked' : '' ?> aria-label="Correct answer">
                                </div>
                                <span class="input-group-text"><?= chr(65 + $c) ?></span>
                                <input type="text" name="<?= $field ?>[choices][]" class="form-control" value="<?= esc($choiceText) ?>" maxlength="500">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="draft-section" data-for="true_false">
                        <label class="form-label small fw-bold">Correct answer</label>
                        <div class="d-flex gap-4">
                            <?php foreach (['true' => 'True', 'false' => 'False'] as $value => $label): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="<?= $field ?>[correct_tf]" id="tf_<?= (int)$i ?>_<?= $value ?>" value="<?= $value ?>" <?= ($q['correct_tf'] ?? '') === $value ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tf_<?= (int)$i ?>_<?= $value ?>"><?= $label ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="draft-section" data-for="text">
                        <label class="form-label small fw-bold">Accepted answers (one per line)</label>
                        <textarea name="<?= $field ?>[accepted_answers]" class="form-control form-control-sm mb-2" rows="2"><?= esc(implode("\n", $q['accepted_answers'] ?? [])) ?></textarea>
                        <div class="d-flex flex-wrap gap-4 small">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="<?= $field ?>[case_sensitive]" id="cs_<?= (int)$i ?>" value="1" <?= !empty($q['case_sensitive']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="cs_<?= (int)$i ?>">Letter case must match</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="<?= $field ?>[manual_review]" id="mr_<?= (int)$i ?>" value="1" <?= !empty($q['manual_review']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="mr_<?= (int)$i ?>">Send non-matching answers to manual review</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="lms-card p-4 border-0 shadow-sm rounded-4 bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="form-check mb-0">
                <?php if ($quiz['status'] === 'published'): ?>
                    <span class="small text-muted">This quiz is already published; saved questions are visible to students right away.</span>
                <?php else: ?>
                    <input class="form-check-input" type="checkbox" name="publish" value="1" id="publishInput">
                    <label class="form-check-label" for="publishInput">Publish the quiz after saving</label>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" form="discardForm" class="btn btn-light rounded-pill px-3" onclick="return confirm('Discard this draft? No questions will be added.');">Discard Draft</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Save Questions</button>
            </div>
        </div>
    </form>

    <form method="POST" action="<?= esc($baseUrl) ?>/questions/review/discard" id="discardForm">
        <?= getCsrfInput() ?>
    </form>
</div>

<script>
(function () {
    function syncCard(card) {
        const type = card.querySelector('.draft-type').value;
        const isText = type === 'identification' || type === 'fill_blank';
        card.querySelectorAll('.draft-section').forEach(function (section) {
            const target = section.dataset.for;
            const visible = target === type || (target === 'text' && isText);
            section.style.display = visible ? '' : 'none';
        });
        card.querySelector('.draft-blank-hint').style.display = type === 'fill_blank' ? '' : 'none';
    }

    function renumber() {
        const cards = document.querySelectorAll('.draft-card');
        cards.forEach(function (card, n) {
            card.querySelector('.draft-number').textContent = '#' + (n + 1);
        });
        document.getElementById('draftCount').textContent = cards.length;
    }

    document.querySelectorAll('.draft-card').forEach(function (card) {
        syncCard(card);
        card.querySelector('.draft-type').addEventListener('change', function () { syncCard(card); });
        card.querySelector('.btn-remove-draft').addEventListener('click', function () {
            card.remove();
            renumber();
        });
    });

    document.getElementById('draftForm').addEventListener('submit', function (e) {
        if (document.querySelectorAll('.draft-card').length === 0 && !confirm('All questions were removed. Save nothing and close the draft?')) {
            e.preventDefault();
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
