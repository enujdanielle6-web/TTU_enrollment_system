<?php require_once __DIR__ . '/../../../../Views/components/header.php'; ?>

<div class="container py-5">
    <div class="mb-4">
        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance" class="text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Back to Attendance
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-4">
                    <h4 class="mb-0 fw-bold">Create Attendance Session</h4>
                </div>
                <div class="card-body p-4">
                    <form action="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance/store" method="POST">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Session Date</label>
                            <input type="date" name="session_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="row mb-3 g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Start Time</label>
                                <input type="time" name="start_time" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">End Time</label>
                                <input type="time" name="end_time" class="form-control">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Notes / Topic (Optional)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="e.g. Midterm Review, Guest Speaker"></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">Create & Proceed to Roll Call</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/components/footer.php'; ?>
