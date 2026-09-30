<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Breadcrumb & Return -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 align-items-center">
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($course['subject_code']) ?></a></li>
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/announcements" class="text-decoration-none text-muted">Announcements</a></li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page"><?= esc($announcement ? 'Edit Announcement' : 'Create Announcement') ?></li>
            </ol>
        </nav>
        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/announcements" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Announcements
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box-sm bg-primary bg-opacity-10 text-primary rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-megaphone-fill fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-dark"><?= esc($announcement ? 'Edit Announcement' : 'Compose Class Announcement') ?></h4>
                            <small class="text-muted"><?= htmlspecialchars($course['subject_code']) ?> &bull; Section <?= htmlspecialchars($course['section_code']) ?></small>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    <form action="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/announcements/<?= esc($announcement ? $announcement['id'] . '/update' : 'store') ?>" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                        <?= getCsrfInput() ?>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Announcement Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-lg" placeholder="e.g. Schedule Update: Room Assignment for Friday Lecture" value="<?= htmlspecialchars($announcement['title'] ?? '') ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Announcement Message <span class="text-danger">*</span></label>
                            <textarea name="content" class="form-control" rows="7" placeholder="Write your broadcast message to the class here..." required><?= htmlspecialchars($announcement['content'] ?? '') ?></textarea>
                            <div class="form-text">Will be displayed on student LMS dashboard and course announcement feeds.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Publishing Status</label>
                            <select name="status" class="form-select">
                                <option value="published" <?= esc(($announcement['status'] ?? 'published') === 'published' ? 'selected' : '') ?>>Published (Visible immediately to all students)</option>
                                <option value="draft" <?= esc(($announcement['status'] ?? '') === 'draft' ? 'selected' : '') ?>>Draft (Save without publishing)</option>
                            </select>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/announcements" class="btn btn-light rounded-pill px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                                <i class="bi bi-broadcast me-1"></i>
                                <?= esc($announcement ? 'Save Changes' : 'Broadcast Announcement') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
