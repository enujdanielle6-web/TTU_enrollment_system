<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Breadcrumb & Return -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 align-items-center">
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/faculty/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($course['subject_code']) ?></a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance" class="text-decoration-none text-muted">Attendance</a></li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">New Session</li>
            </ol>
        </nav>
        <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Attendance
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box-sm bg-success bg-opacity-10 text-success rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-calendar-plus fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-dark">Schedule Attendance Session</h4>
                            <small class="text-muted"><?= htmlspecialchars($course['subject_code']) ?> &bull; Section <?= htmlspecialchars($course['section_code']) ?></small>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    <form action="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance/store" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                        <?= getCsrfInput() ?>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Session Date <span class="text-danger">*</span></label>
                            <input type="date" name="session_date" class="form-control form-control-lg" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="row mb-4 g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Class Start Time</label>
                                <input type="time" name="start_time" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Class End Time</label>
                                <input type="time" name="end_time" class="form-control">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Session Topic / Remarks (Optional)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="e.g. Chapter 4 Lecture: Database Normalization & Indexing"></textarea>
                            <div class="form-text">Will appear as the session label in the class attendance history.</div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance" class="btn btn-light rounded-pill px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Create &amp; Take Roll Call
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
