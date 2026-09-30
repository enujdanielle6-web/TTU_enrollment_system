<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Breadcrumb & Return -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 align-items-center">
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($course['subject_code']) ?></a></li>
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="text-decoration-none text-muted">Quizzes</a></li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page"><?= esc($quiz ? 'Edit Quiz Settings' : 'Create Quiz') ?></li>
            </ol>
        </nav>
        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Quizzes
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box-sm bg-info bg-opacity-10 text-info rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-pencil-square fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-dark"><?= esc($quiz ? 'Edit Quiz Settings' : 'Create New Online Quiz') ?></h4>
                            <small class="text-muted"><?= htmlspecialchars($course['subject_code']) ?> &bull; Section <?= htmlspecialchars($course['section_code']) ?></small>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    <form action="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($quiz ? $quiz['id'] . '/update' : 'store') ?>" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                        <?= getCsrfInput() ?>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Quiz Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-lg" placeholder="e.g. Midterm Examination / Quiz 1: Algorithms" value="<?= $quiz ? htmlspecialchars($quiz['title']) : '' ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Description &amp; Instructions</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Instructions, topics covered, policy on calculators/references, etc..."><?= $quiz ? htmlspecialchars($quiz['description']) : '' ?></textarea>
                        </div>

                        <div class="row mb-4 g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Time Limit (Minutes)</label>
                                <input type="number" name="time_limit" class="form-control" value="<?= esc($quiz ? $quiz['time_limit'] : '') ?>" placeholder="e.g. 60" min="1">
                                <div class="form-text">Leave blank for unlimited duration.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Max Attempts Allowed</label>
                                <input type="number" name="max_attempts" class="form-control" value="<?= esc($quiz ? $quiz['max_attempts'] : 1) ?>" min="1" required>
                                <div class="form-text">Number of times a student can take it.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Passing Score (%)</label>
                                <input type="number" name="passing_score" class="form-control" value="<?= esc($quiz ? $quiz['passing_score'] : '75') ?>" placeholder="75" min="0" max="100">
                                <div class="form-text">Percentage required to pass.</div>
                            </div>
                        </div>

                        <div class="row mb-4 g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Available From (Start Date)</label>
                                <input type="datetime-local" name="start_date" class="form-control" value="<?= esc($quiz && $quiz['start_date'] ? date('Y-m-d\TH:i', strtotime($quiz['start_date'])) : '') ?>">
                                <div class="form-text">Quiz becomes accessible to students.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Closes On (End Date)</label>
                                <input type="datetime-local" name="end_date" class="form-control" value="<?= esc($quiz && $quiz['end_date'] ? date('Y-m-d\TH:i', strtotime($quiz['end_date'])) : '') ?>">
                                <div class="form-text">Quiz access deadline cutoff.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Publishing Status</label>
                                <select name="status" class="form-select">
                                    <option value="published" <?= esc(($quiz && $quiz['status'] === 'published') || !$quiz ? 'selected' : '') ?>>Published (Visible to students)</option>
                                    <option value="draft" <?= esc(($quiz && $quiz['status'] === 'draft') ? 'selected' : '') ?>>Draft (Hidden / In-progress)</option>
                                </select>
                                <div class="form-text">Drafts cannot be accessed by students.</div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="btn btn-light rounded-pill px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i>
                                <?= esc($quiz ? 'Save Quiz Settings' : 'Create Quiz') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
