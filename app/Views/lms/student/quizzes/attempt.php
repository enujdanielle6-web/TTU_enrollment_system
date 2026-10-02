<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<!-- Top Sticky Assessment HUD -->
<div class="quiz-sticky-hud bg-white border-bottom shadow-xs py-3 px-3 px-md-4 mb-4">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-3">
        <!-- Quiz Info -->
        <div class="d-flex align-items-center gap-3">
            <div class="hud-icon rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="bi bi-pencil-square fs-5"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-secondary border px-2 py-0.5 rounded-pill small"><?= htmlspecialchars($course['subject_code']) ?></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-2 py-0.5 rounded-pill small">Attempt #<?= esc($attempt['attempt_number']) ?></span>
                </div>
                <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width: 320px;" title="<?= htmlspecialchars($quiz['title']) ?>">
                    <?= htmlspecialchars($quiz['title']) ?>
                </h6>
            </div>
        </div>

        <!-- Progress Overview -->
        <div class="d-none d-md-block flex-grow-1 mx-lg-5" style="max-width: 380px;">
            <div class="d-flex justify-content-between align-items-center mb-1 small">
                <span class="text-muted fw-semibold">Progress</span>
                <span class="fw-bold text-primary"><span id="hudAnsweredCount">0</span> of <?= count($questions) ?> answered (<span id="hudAnsweredPct">0</span>%)</span>
            </div>
            <div class="progress" style="height: 6px; background-color: #f1f5f9; border-radius: 999px;">
                <div id="hudProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%; transition: width 0.3s ease;"></div>
            </div>
        </div>

        <!-- Timer & Quick Submit -->
        <div class="d-flex align-items-center gap-2 ms-auto">
            <?php if ($quiz['time_limit']): ?>
                <div id="quizTimerWrapper" class="d-flex align-items-center gap-2 px-3 py-1.5 rounded-pill border timer-normal">
                    <i class="bi bi-clock-history fs-5" id="timerIcon"></i>
                    <div>
                        <div class="text-uppercase fw-bold text-muted lh-1" style="font-size: 0.62rem; letter-spacing: 0.05em;">Time Left</div>
                        <span id="timeDisplay" class="fw-bold fs-6 font-monospace lh-1">--:--</span>
                    </div>
                </div>
            <?php endif; ?>

            <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-xs" onclick="window.quizApp.openSubmitModal();">
                <i class="bi bi-check2-circle fs-5"></i>
                <span class="d-none d-sm-inline">Submit</span>
            </button>
        </div>
    </div>
</div>

<div class="container-fluid px-md-4 pb-5">
    <form id="quizForm" action="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz['id']) ?>/attempt/<?= esc($attempt['id']) ?>/submit" method="POST">
        <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token'] ?? '') ?>">

        <div class="row g-4">
            <!-- Questions Column -->
            <div class="col-lg-8 col-xl-9">
                <?php foreach ($questions as $index => $q): 
                    $qNum = $index + 1;
                ?>
                    <div class="lms-card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4 question-card" id="q-card-<?= esc($q['id']) ?>" data-qid="<?= esc($q['id']) ?>">
                        
                        <!-- Question Header -->
                        <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-3 py-1.5 rounded-pill fw-bold" style="font-size: 0.85rem;">
                                    Question <?= $qNum ?>
                                </span>
                                <span class="badge bg-light text-secondary border px-2.5 py-1.5 rounded-pill small">
                                    <?= esc($q['points']) ?> Point<?= $q['points'] > 1 ? 's' : '' ?>
                                </span>
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 btn-flag-review" data-qid="<?= esc($q['id']) ?>" title="Flag question to review later">
                                <i class="bi bi-flag me-1"></i> <span class="flag-text">Flag for Review</span>
                            </button>
                        </div>

                        <!-- Question Text -->
                        <div class="question-prompt mb-4">
                            <h5 class="fw-bold text-dark lh-base mb-0" style="font-size: 1.15rem; letter-spacing: -0.01em;">
                                <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                            </h5>
                        </div>

                        <?php if (\App\Services\Quiz\QuizQuestionValidator::isTextType($q['question_type'])): ?>
                        <!-- Typed Answer (identification / fill in the blank) -->
                        <div class="typed-answer">
                            <label class="form-label small fw-semibold text-muted" for="answer_<?= esc($q['id']) ?>">
                                <?= $q['question_type'] === 'fill_blank' ? 'Type the word or phrase that fills the blank' : 'Type your answer' ?>
                            </label>
                            <input type="text"
                                   class="form-control form-control-lg text-answer-input"
                                   name="answers[<?= esc($q['id']) ?>]"
                                   id="answer_<?= esc($q['id']) ?>"
                                   data-qid="<?= esc($q['id']) ?>"
                                   maxlength="1000"
                                   autocomplete="off"
                                   spellcheck="false">
                            <div class="form-text"><?= !empty($q['case_sensitive']) ? 'Letter case counts for this question.' : 'Letter case does not matter.' ?></div>
                        </div>
                        <?php else: ?>
                        <!-- Choice Options Grid -->
                        <div class="choices-list d-flex flex-column gap-2.5">
                            <?php foreach ($q['choices'] as $cIndex => $c): 
                                $letter = chr(65 + $cIndex);
                            ?>
                                <label class="quiz-choice-item d-flex align-items-center p-3 rounded-3 border transition-card cursor-pointer" for="choice_<?= esc($c['id']) ?>">
                                    <input class="form-check-input choice-radio d-none" 
                                           type="radio" 
                                           name="answers[<?= esc($q['id']) ?>]" 
                                           id="choice_<?= esc($c['id']) ?>" 
                                           value="<?= esc($c['id']) ?>"
                                           data-qid="<?= esc($q['id']) ?>">
                                    
                                    <div class="choice-letter-badge rounded-circle border d-flex align-items-center justify-content-center fw-bold me-3 flex-shrink-0" style="width: 34px; height: 34px; font-size: 0.88rem;">
                                        <?= $letter ?>
                                    </div>

                                    <div class="choice-text flex-grow-1 text-dark fs-6 lh-base">
                                        <?= htmlspecialchars($c['choice_text']) ?>
                                    </div>

                                    <div class="choice-check-indicator ms-2 opacity-0 text-primary fs-5">
                                        <i class="bi bi-check-circle-fill"></i>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>

                <!-- Bottom Submit Bar -->
                <div class="lms-card p-4 border-0 shadow-sm rounded-4 text-center bg-white mb-5">
                    <h5 class="fw-bold text-dark mb-2">Reached the end of the assessment?</h5>
                    <p class="text-muted small mb-4">Make sure you have reviewed flagged questions before finalizing your submission.</p>
                    <button type="button" class="btn btn-primary btn-lg px-5 py-2.5 rounded-pill fw-bold shadow-sm d-inline-flex align-items-center gap-2" onclick="window.quizApp.openSubmitModal();">
                        <i class="bi bi-send-check-fill fs-5"></i>
                        <span>Review & Submit Quiz</span>
                    </button>
                </div>
            </div>

            <!-- Sticky Sidebar: Question Navigator -->
            <div class="col-lg-4 col-xl-3">
                <div class="sticky-quiz-sidebar">
                    <div class="lms-card p-4 border-0 shadow-sm rounded-4 bg-white mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark text-uppercase mb-0" style="font-size: 0.8rem; letter-spacing: 0.05em;">
                                <i class="bi bi-grid-3x3-gap-fill text-primary me-1"></i> Question Navigator
                            </h6>
                            <span class="badge bg-light text-secondary border px-2 py-0.5 rounded-pill small">
                                <?= count($questions) ?> Total
                            </span>
                        </div>

                        <!-- Legend -->
                        <div class="d-flex flex-wrap gap-2 pb-3 mb-3 border-bottom small text-muted" style="font-size: 0.72rem;">
                            <span class="d-flex align-items-center gap-1"><span class="legend-dot bg-primary"></span> Answered</span>
                            <span class="d-flex align-items-center gap-1"><span class="legend-dot bg-light border"></span> Unanswered</span>
                            <span class="d-flex align-items-center gap-1"><span class="legend-dot bg-warning"></span> Flagged</span>
                        </div>

                        <!-- Question Numbers Grid -->
                        <div class="nav-question-grid mb-4">
                            <?php foreach ($questions as $index => $q): 
                                $qNum = $index + 1;
                            ?>
                                <button type="button" 
                                        class="btn-nav-question rounded-3 d-flex align-items-center justify-content-center fw-bold transition-card position-relative" 
                                        id="nav-btn-<?= esc($q['id']) ?>" 
                                        data-qid="<?= esc($q['id']) ?>" 
                                        onclick="window.quizApp.scrollToQuestion(<?= esc($q['id']) ?>);">
                                    <?= $qNum ?>
                                    <span class="flag-indicator d-none"><i class="bi bi-flag-fill"></i></span>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Mini Stats Summary -->
                        <div class="bg-light p-3 rounded-3 mb-3" style="font-size: 0.82rem;">
                            <div class="d-flex justify-content-between mb-1.5">
                                <span class="text-muted">Answered:</span>
                                <span class="fw-bold text-success" id="sidebarAnswered">0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1.5">
                                <span class="text-muted">Unanswered:</span>
                                <span class="fw-bold text-danger" id="sidebarUnanswered"><?= count($questions) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Flagged:</span>
                                <span class="fw-bold text-warning" id="sidebarFlagged">0</span>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100 rounded-pill py-2.5 fw-bold shadow-xs d-flex align-items-center justify-content-center gap-2" onclick="window.quizApp.openSubmitModal();">
                            <i class="bi bi-check-lg fs-5"></i>
                            <span>Submit Assessment</span>
                        </button>
                    </div>

                    <!-- Assessment Safe Notice -->
                    <div class="p-3 rounded-3 border bg-white small text-muted">
                        <i class="bi bi-info-circle text-primary me-1"></i>
                        <span>Questions can be answered in any sequence before submitting.</span>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Submission Confirmation Modal -->
<div class="modal fade" id="submitConfirmModal" tabindex="-1" aria-labelledby="submitConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-question-circle-fill fs-5"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="submitConfirmModalLabel">Confirm Submission</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <p class="text-muted mb-3" style="line-height: 1.5;">
                    Are you sure you are ready to submit your assessment? Once submitted, your answers cannot be altered.
                </p>

                <div class="p-3 bg-light rounded-3 mb-3" id="submissionStatusSummary">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Total Questions:</span>
                        <span class="fw-bold text-dark"><?= count($questions) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Answered:</span>
                        <span class="fw-bold text-success" id="modalAnsweredCount">0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Unanswered:</span>
                        <span class="fw-bold text-danger" id="modalUnansweredCount"><?= count($questions) ?></span>
                    </div>
                </div>

                <div id="unansweredWarningAlert" class="alert alert-warning py-2.5 px-3 rounded-3 d-none mb-0 small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Caution:</strong> You still have unanswered questions (<span id="unansweredList"></span>). You may return and answer them before submitting.
                </div>
            </div>

            <div class="modal-footer border-top p-3 bg-light d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-medium" data-bs-dismiss="modal">
                    Review Questions
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold d-inline-flex align-items-center gap-2" id="btnConfirmSubmit" onclick="window.quizApp.finalizeSubmission();">
                    <span id="btnSubmitSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    <span id="btnSubmitText">Confirm & Submit</span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Sticky Assessment Top Bar */
.quiz-sticky-hud {
    position: sticky;
    top: 0;
    z-index: 1030;
    background: rgba(255, 255, 255, 0.96) !important;
    backdrop-filter: blur(10px);
}

.sticky-quiz-sidebar {
    position: sticky;
    top: 90px;
    z-index: 1010;
}

/* Choice Option Styling */
.quiz-choice-item {
    background: #ffffff;
    border-color: #e5e7eb !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    user-select: none;
}
.quiz-choice-item:hover {
    border-color: #0d6efd !important;
    background-color: #f8fbff;
    transform: translateX(3px);
}
.quiz-choice-item.selected {
    border-color: #0d6efd !important;
    background-color: #eff6ff !important;
    box-shadow: 0 0 0 1px #0d6efd;
}
.quiz-choice-item.selected .choice-letter-badge {
    background-color: #0d6efd !important;
    color: #ffffff !important;
    border-color: #0d6efd !important;
}
.quiz-choice-item.selected .choice-check-indicator {
    opacity: 1 !important;
}
.choice-letter-badge {
    background-color: #f8fafc;
    color: #475569;
    border-color: #cbd5e1 !important;
    transition: all 0.2s ease;
}

/* Question Navigator Grid */
.nav-question-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
}
.btn-nav-question {
    aspect-ratio: 1;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 0.88rem;
    cursor: pointer;
}
.btn-nav-question:hover {
    border-color: #0d6efd;
    color: #0d6efd;
}
.btn-nav-question.answered {
    background-color: #0d6efd !important;
    color: #ffffff !important;
    border-color: #0d6efd !important;
}
.btn-nav-question.flagged {
    border-color: #f59e0b !important;
    color: #b45309 !important;
    background-color: #fffbeb !important;
}
.btn-nav-question.answered.flagged {
    background-color: #0d6efd !important;
    border-color: #f59e0b !important;
    color: #ffffff !important;
}
.btn-nav-question .flag-indicator {
    position: absolute;
    top: 2px;
    right: 3px;
    font-size: 0.62rem;
    color: #f59e0b;
}
.btn-nav-question.answered.flagged .flag-indicator {
    color: #fef08a;
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

/* Timer styles */
.timer-normal {
    background-color: #f0f7ff;
    border-color: #bfdbfe !important;
    color: #0d6efd;
}
.timer-warning {
    background-color: #fefce8;
    border-color: #fef08a !important;
    color: #ca8a04;
}
.timer-urgent {
    background-color: #fef2f2;
    border-color: #fecaca !important;
    color: #dc2626;
    animation: timerPulse 1s infinite alternate;
}

@keyframes timerPulse {
    0% { transform: scale(1); }
    100% { transform: scale(1.03); }
}

.shadow-xs {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
</style>

<script>
(function() {
    const totalQuestions = <?= count($questions) ?>;
    const answeredMap = new Set();
    const flaggedMap = new Set();
    const questionList = [
        <?php foreach ($questions as $index => $q): ?>
            { id: <?= $q['id'] ?>, num: <?= $index + 1 ?> },
        <?php endforeach; ?>
    ];

    window.quizApp = {
        init: function() {
            this.bindEvents();
            this.updateStats();
            <?php if ($quiz['time_limit']): ?>
                this.initTimer();
            <?php endif; ?>
        },

        bindEvents: function() {
            // Radio button selection
            document.querySelectorAll('.choice-radio').forEach(radio => {
                radio.addEventListener('change', (e) => {
                    const qId = parseInt(e.target.dataset.qid);
                    answeredMap.add(qId);
                    
                    // Update visual choice state within same question
                    const card = document.getElementById('q-card-' + qId);
                    if (card) {
                        card.querySelectorAll('.quiz-choice-item').forEach(item => {
                            item.classList.remove('selected');
                        });
                        const selectedLabel = e.target.closest('.quiz-choice-item');
                        if (selectedLabel) selectedLabel.classList.add('selected');
                    }

                    // Update navigator button
                    const navBtn = document.getElementById('nav-btn-' + qId);
                    if (navBtn) navBtn.classList.add('answered');

                    this.updateStats();
                });
            });

            // Typed answers count as answered once they contain text
            document.querySelectorAll('.text-answer-input').forEach(input => {
                input.addEventListener('input', (e) => {
                    const qId = parseInt(e.target.dataset.qid);
                    const navBtn = document.getElementById('nav-btn-' + qId);
                    if (e.target.value.trim() !== '') {
                        answeredMap.add(qId);
                        if (navBtn) navBtn.classList.add('answered');
                    } else {
                        answeredMap.delete(qId);
                        if (navBtn) navBtn.classList.remove('answered');
                    }
                    this.updateStats();
                });
                // Enter in a text box should not submit the whole quiz
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') e.preventDefault();
                });
            });

            // Flag for review button
            document.querySelectorAll('.btn-flag-review').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const qId = parseInt(btn.dataset.qid);
                    const navBtn = document.getElementById('nav-btn-' + qId);
                    const flagIndicator = navBtn ? navBtn.querySelector('.flag-indicator') : null;
                    const textSpan = btn.querySelector('.flag-text');

                    if (flaggedMap.has(qId)) {
                        flaggedMap.delete(qId);
                        btn.classList.remove('btn-warning');
                        btn.classList.add('btn-outline-secondary');
                        if (textSpan) textSpan.innerText = 'Flag for Review';
                        if (navBtn) navBtn.classList.remove('flagged');
                        if (flagIndicator) flagIndicator.classList.add('d-none');
                    } else {
                        flaggedMap.add(qId);
                        btn.classList.remove('btn-outline-secondary');
                        btn.classList.add('btn-warning');
                        if (textSpan) textSpan.innerText = 'Flagged';
                        if (navBtn) navBtn.classList.add('flagged');
                        if (flagIndicator) flagIndicator.classList.remove('d-none');
                    }

                    this.updateStats();
                });
            });
        },

        updateStats: function() {
            const answeredCount = answeredMap.size;
            const unansweredCount = totalQuestions - answeredCount;
            const pct = Math.round((answeredCount / totalQuestions) * 100);

            // HUD
            const hudCount = document.getElementById('hudAnsweredCount');
            const hudPct = document.getElementById('hudAnsweredPct');
            const hudBar = document.getElementById('hudProgressBar');
            if (hudCount) hudCount.innerText = answeredCount;
            if (hudPct) hudPct.innerText = pct;
            if (hudBar) hudBar.style.width = pct + '%';

            // Sidebar
            const sideAns = document.getElementById('sidebarAnswered');
            const sideUnans = document.getElementById('sidebarUnanswered');
            const sideFlag = document.getElementById('sidebarFlagged');
            if (sideAns) sideAns.innerText = answeredCount;
            if (sideUnans) sideUnans.innerText = unansweredCount;
            if (sideFlag) sideFlag.innerText = flaggedMap.size;

            // Modal
            const modalAns = document.getElementById('modalAnsweredCount');
            const modalUnans = document.getElementById('modalUnansweredCount');
            if (modalAns) modalAns.innerText = answeredCount;
            if (modalUnans) modalUnans.innerText = unansweredCount;

            const warningAlert = document.getElementById('unansweredWarningAlert');
            const listSpan = document.getElementById('unansweredList');
            if (unansweredCount > 0) {
                const unansweredNums = questionList.filter(q => !answeredMap.has(q.id)).map(q => '#' + q.num);
                if (listSpan) listSpan.innerText = unansweredNums.join(', ');
                if (warningAlert) warningAlert.classList.remove('d-none');
            } else {
                if (warningAlert) warningAlert.classList.add('d-none');
            }
        },

        scrollToQuestion: function(qId) {
            const el = document.getElementById('q-card-' + qId);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        },

        openSubmitModal: function() {
            this.updateStats();
            const modalEl = document.getElementById('submitConfirmModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            } else {
                if (confirm('Are you sure you want to submit your quiz attempt?')) {
                    this.finalizeSubmission();
                }
            }
        },

        finalizeSubmission: function() {
            const btn = document.getElementById('btnConfirmSubmit');
            const spinner = document.getElementById('btnSubmitSpinner');
            const btnText = document.getElementById('btnSubmitText');

            if (btn) btn.disabled = true;
            if (spinner) spinner.classList.remove('d-none');
            if (btnText) btnText.innerText = 'Submitting & Grading...';

            document.getElementById('quizForm').submit();
        },

        <?php if ($quiz['time_limit']): ?>
        initTimer: function() {
            const startedAt = new Date("<?= esc($attempt['started_at']) ?>").getTime();
            const timeLimitMs = <?= esc($quiz['time_limit']) ?> * 60 * 1000;
            const endTime = startedAt + timeLimitMs;
            const display = document.getElementById('timeDisplay');
            const wrapper = document.getElementById('quizTimerWrapper');

            function tick() {
                const now = new Date().getTime();
                const remaining = endTime - now;

                if (remaining <= 0) {
                    if (display) display.innerText = "00:00";
                    if (wrapper) {
                        wrapper.classList.remove('timer-normal', 'timer-warning');
                        wrapper.classList.add('timer-urgent');
                    }
                    alert("Time has expired! Your assessment will now be submitted automatically.");
                    window.quizApp.finalizeSubmission();
                    return;
                }

                const totalSec = Math.floor(remaining / 1000);
                const minutes = Math.floor(totalSec / 60);
                const seconds = totalSec % 60;

                const m = minutes < 10 ? "0" + minutes : minutes;
                const s = seconds < 10 ? "0" + seconds : seconds;

                if (display) display.innerText = m + ":" + s;

                // Color thresholds
                if (wrapper) {
                    if (totalSec <= 60) {
                        wrapper.classList.remove('timer-normal', 'timer-warning');
                        wrapper.classList.add('timer-urgent');
                    } else if (totalSec <= 300) {
                        wrapper.classList.remove('timer-normal', 'timer-urgent');
                        wrapper.classList.add('timer-warning');
                    }
                }
            }

            setInterval(tick, 1000);
            tick();
        }
        <?php endif; ?>
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.quizApp.init());
    } else {
        window.quizApp.init();
    }
})();
</script>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>
