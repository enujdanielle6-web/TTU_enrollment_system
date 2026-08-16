<?php require_once __DIR__ . '/../../../../Views/components/header.php'; ?>

<div class="container py-5">
    <div class="mb-4">
        <a href="/sia/lms/faculty/course/<?= $course['lms_course_id'] ?>/announcements" class="text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Back to Announcements
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-4">
                    <h4 class="mb-0 fw-bold"><?= $announcement ? 'Edit Announcement' : 'Create Announcement' ?></h4>
                </div>
                <div class="card-body p-4">
                    <form action="/sia/lms/faculty/course/<?= $course['lms_course_id'] ?>/announcements/<?= $announcement ? $announcement['id'] . '/update' : 'store' ?>" method="POST">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Title</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($announcement['title'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Content</label>
                            <textarea name="content" class="form-control" rows="6" required><?= htmlspecialchars($announcement['content'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" <?= ($announcement['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= ($announcement['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="/sia/lms/faculty/course/<?= $course['lms_course_id'] ?>/announcements" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4"><?= $announcement ? 'Update' : 'Create' ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/components/footer.php'; ?>
