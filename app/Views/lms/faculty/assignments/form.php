<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Breadcrumb & Return -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 align-items-center">
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/faculty/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($course['subject_code']) ?></a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments" class="text-decoration-none text-muted">Assignments</a></li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page"><?= esc($assignment ? 'Edit Assignment' : 'Create Assignment') ?></li>
            </ol>
        </nav>
        <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Assignments
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box-sm bg-primary bg-opacity-10 text-primary rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-journal-plus fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-dark"><?= esc($assignment ? 'Edit Assignment' : 'Create New Assignment') ?></h4>
                            <small class="text-muted"><?= htmlspecialchars($course['subject_code']) ?> &bull; Section <?= htmlspecialchars($course['section_code']) ?></small>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    <form action="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments/<?= esc($assignment ? $assignment['id'] . '/update' : 'store') ?>" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                        <?= getCsrfInput() ?>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Assignment Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-lg" placeholder="e.g. Laboratory Activity 1: Relational Data Models" value="<?= $assignment ? htmlspecialchars($assignment['title']) : '' ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Instructions / Problem Statement</label>
                            <textarea name="description" class="form-control" rows="6" placeholder="Provide complete guidelines, submission requirements, and rubrics for students..."><?= $assignment ? htmlspecialchars($assignment['description']) : '' ?></textarea>
                            <div class="form-text">Students will read these instructions when viewing the assignment.</div>
                        </div>

                        <div class="row mb-4 g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Due Date &amp; Time</label>
                                <input type="datetime-local" name="due_date" class="form-control" value="<?= esc($assignment && $assignment['due_date'] ? date('Y-m-d\TH:i', strtotime($assignment['due_date'])) : '') ?>">
                                <div class="form-text">Leave blank if no strict submission cutoff.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Maximum Points <span class="text-danger">*</span></label>
                                <input type="number" name="max_score" class="form-control" value="<?= esc($assignment ? $assignment['max_score'] : 100) ?>" required min="1">
                                <div class="form-text">Used for grade calculation and weighting.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">Publishing Status</label>
                                <select name="status" class="form-select">
                                    <option value="published" <?= esc(($assignment && $assignment['status'] === 'published') || !$assignment ? 'selected' : '') ?>>Published (Visible to students)</option>
                                    <option value="draft" <?= esc(($assignment && $assignment['status'] === 'draft') ? 'selected' : '') ?>>Draft (Hidden / In-progress)</option>
                                </select>
                                <div class="form-text">Drafts cannot be viewed or submitted by students.</div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments" class="btn btn-light rounded-pill px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i>
                                <?= esc($assignment ? 'Save Changes' : 'Publish Assignment') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
