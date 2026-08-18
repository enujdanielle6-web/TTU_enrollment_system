<?php require_once __DIR__ . '/../../../../Views/components/header.php'; ?>

<div class="container py-5">
    <div class="mb-4">
        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments" class="text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Back to Assignments
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom p-4">
            <h4 class="mb-0 fw-bold"><?= esc($assignment ? 'Edit Assignment' : 'Create Assignment') ?></h4>
        </div>
        <div class="card-body p-4">
            <form action="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments/<?= esc($assignment ? $assignment['id'] . '/update' : 'store') ?>" method="POST">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Assignment Title</label>
                    <input type="text" name="title" class="form-control" value="<?= $assignment ? htmlspecialchars($assignment['title']) : '' ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Instructions / Description</label>
                    <textarea name="description" class="form-control" rows="5"><?= $assignment ? htmlspecialchars($assignment['description']) : '' ?></textarea>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Due Date (Optional)</label>
                        <input type="datetime-local" name="due_date" class="form-control" value="<?= esc($assignment && $assignment['due_date'] ? date('Y-m-d\TH:i', strtotime($assignment['due_date'])) : '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Max Score</label>
                        <input type="number" name="max_score" class="form-control" value="<?= esc($assignment ? $assignment['max_score'] : 100) ?>" required min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" <?= esc(($assignment && $assignment['status'] === 'draft') ? 'selected' : '') ?>>Draft (Hidden)</option>
                            <option value="published" <?= esc(($assignment && $assignment['status'] === 'published') ? 'selected' : '') ?>>Published (Visible to students)</option>
                        </select>
                    </div>
                </div>

                <hr class="mb-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4"><?= esc($assignment ? 'Save Changes' : 'Create Assignment') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/components/footer.php'; ?>
