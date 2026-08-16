<?php require_once __DIR__ . '/../../../../Views/components/header.php'; ?>

<div class="container py-5">
    <div class="mb-4">
        <a href="/sia/lms/faculty/course/<?= $course['lms_course_id'] ?>/quizzes" class="text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Back to Quizzes
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom p-4">
            <h4 class="mb-0 fw-bold"><?= $quiz ? 'Edit Quiz Settings' : 'Create Quiz' ?></h4>
        </div>
        <div class="card-body p-4">
            <form action="/sia/lms/faculty/course/<?= $course['lms_course_id'] ?>/quizzes/<?= $quiz ? $quiz['id'] . '/update' : 'store' ?>" method="POST">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Quiz Title</label>
                    <input type="text" name="title" class="form-control" value="<?= $quiz ? htmlspecialchars($quiz['title']) : '' ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Description / Instructions</label>
                    <textarea name="description" class="form-control" rows="4"><?= $quiz ? htmlspecialchars($quiz['description']) : '' ?></textarea>
                </div>

                <div class="row mb-4 g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Time Limit (Minutes)</label>
                        <input type="number" name="time_limit" class="form-control" value="<?= $quiz ? $quiz['time_limit'] : '' ?>" placeholder="Leave blank for unlimited" min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Max Attempts</label>
                        <input type="number" name="max_attempts" class="form-control" value="<?= $quiz ? $quiz['max_attempts'] : 1 ?>" min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Passing Score (%)</label>
                        <input type="number" name="passing_score" class="form-control" value="<?= $quiz ? $quiz['passing_score'] : '' ?>" placeholder="Optional" min="0" max="100">
                    </div>
                </div>

                <div class="row mb-4 g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Start Date & Time</label>
                        <input type="datetime-local" name="start_date" class="form-control" value="<?= $quiz && $quiz['start_date'] ? date('Y-m-d\TH:i', strtotime($quiz['start_date'])) : '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">End Date & Time</label>
                        <input type="datetime-local" name="end_date" class="form-control" value="<?= $quiz && $quiz['end_date'] ? date('Y-m-d\TH:i', strtotime($quiz['end_date'])) : '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" <?= ($quiz && $quiz['status'] === 'draft') ? 'selected' : '' ?>>Draft (Hidden)</option>
                            <option value="published" <?= ($quiz && $quiz['status'] === 'published') ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>
                </div>

                <hr class="mb-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="/sia/lms/faculty/course/<?= $course['lms_course_id'] ?>/quizzes" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4"><?= $quiz ? 'Save Settings' : 'Create Quiz' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/components/footer.php'; ?>
