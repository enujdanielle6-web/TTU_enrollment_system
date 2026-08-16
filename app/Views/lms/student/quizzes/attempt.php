<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h4 fw-bold text-dark mb-0"><?= htmlspecialchars($quiz['title']) ?> - Attempt #<?= $attempt['attempt_number'] ?></h1>
                <?php if ($quiz['time_limit']): ?>
                    <div class="badge bg-danger bg-opacity-10 text-danger border border-danger p-2 fs-6" id="timer">
                        <i class="bi bi-clock me-1"></i> <span id="timeDisplay">--:--</span>
                    </div>
                <?php endif; ?>
            </div>

            <form id="quizForm" action="/sia/lms/student/course/<?= $course['lms_course_id'] ?>/quizzes/<?= $quiz['id'] ?>/attempt/<?= $attempt['id'] ?>/submit" method="POST">
                
                <?php foreach ($questions as $index => $q): ?>
                    <div class="lms-card p-4 border-0 shadow-sm rounded-4 mb-4">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <h5 class="fw-bold mb-0 lh-base">
                                <span class="text-primary me-2"><?= $index + 1 ?>.</span>
                                <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                            </h5>
                            <span class="badge bg-light text-secondary border"><?= $q['points'] ?> pts</span>
                        </div>
                        
                        <div class="ps-4 ms-2">
                            <?php foreach ($q['choices'] as $c): ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" id="choice_<?= $c['id'] ?>" value="<?= $c['id'] ?>">
                                    <label class="form-check-label fs-5" style="cursor: pointer;" for="choice_<?= $c['id'] ?>">
                                        <?= htmlspecialchars($c['choice_text']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="lms-card p-4 border-0 shadow-sm rounded-4 text-center">
                    <p class="text-muted mb-3">Please review your answers before submitting. You cannot change them once submitted.</p>
                    <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold" onclick="return confirm('Are you sure you want to submit your attempt?');">Submit Quiz</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php if ($quiz['time_limit']): ?>
<script>
    const startedAt = new Date("<?= $attempt['started_at'] ?>").getTime();
    const timeLimitMs = <?= $quiz['time_limit'] ?> * 60 * 1000;
    const endTime = startedAt + timeLimitMs;

    function updateTimer() {
        const now = new Date().getTime();
        const remaining = endTime - now;

        if (remaining <= 0) {
            document.getElementById('timeDisplay').innerText = "00:00";
            document.getElementById('quizForm').submit();
            return;
        }

        const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((remaining % (1000 * 60)) / 1000);

        const m = minutes < 10 ? "0" + minutes : minutes;
        const s = seconds < 10 ? "0" + seconds : seconds;

        document.getElementById('timeDisplay').innerText = m + ":" + s;
    }

    setInterval(updateTimer, 1000);
    updateTimer();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>
