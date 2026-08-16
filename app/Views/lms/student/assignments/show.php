<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sia/lms/student/dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/student/my_courses.php" class="text-decoration-none">My Courses</a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/student/course.php?id=<?= $course['lms_course_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($course['subject_code']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Assignment</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Assignment Details -->
        <div class="col-lg-8">
            <div class="lms-card p-4 mb-4 border-0 shadow-sm rounded-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h1 class="h3 fw-bold text-dark mb-0"><?= htmlspecialchars($assignment['title']) ?></h1>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 fs-6">
                        <?= $assignment['max_score'] ?> pts
                    </span>
                </div>
                
                <p class="text-muted mb-4 fw-medium">
                    <i class="bi bi-calendar-event me-1"></i> Due: 
                    <?= $assignment['due_date'] ? date('M d, Y h:i A', strtotime($assignment['due_date'])) : 'No deadline' ?>
                </p>

                <h5 class="fw-bold mb-3">Instructions</h5>
                <div class="text-secondary lh-lg mb-4">
                    <?= nl2br(htmlspecialchars($assignment['description'] ?? 'No instructions provided.')) ?>
                </div>
            </div>
        </div>

        <!-- Submission Panel -->
        <div class="col-lg-4">
            <div class="lms-card p-4 border-0 shadow-sm rounded-4">
                <h4 class="h5 fw-bold mb-4 border-bottom pb-3">Your Submission</h4>

                <?php if ($submission): ?>
                    <!-- Existing Submission Details -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted fw-bold">Status</span>
                            <?php if ($submission['status'] === 'GRADED'): ?>
                                <span class="badge bg-success">Graded</span>
                            <?php elseif ($submission['status'] === 'RESUBMITTED'): ?>
                                <span class="badge bg-info">Resubmitted</span>
                            <?php else: ?>
                                <span class="badge bg-primary">Submitted</span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted fw-bold">Submitted At</span>
                            <span class="text-dark"><?= date('M d, Y h:i A', strtotime($submission['submitted_at'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted fw-bold">File</span>
                            <a href="/sia/lms/download/submission/<?= $submission['id'] ?>" class="text-decoration-none fw-medium text-truncate" style="max-width: 150px;">
                                <i class="bi bi-file-earmark-arrow-down"></i> <?= htmlspecialchars($submission['file_name']) ?>
                            </a>
                        </div>
                    </div>

                    <!-- Grading / Feedback -->
                    <?php if ($submission['status'] === 'GRADED'): ?>
                        <div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 p-3 rounded-3 mb-4">
                            <h6 class="alert-heading fw-bold text-success mb-2">Grade: <?= $submission['grade'] ?> / <?= $assignment['max_score'] ?></h6>
                            <?php if ($submission['feedback']): ?>
                                <p class="mb-0 text-dark small mt-2 pt-2 border-top border-success border-opacity-25">
                                    <strong>Feedback:</strong><br>
                                    <?= nl2br(htmlspecialchars($submission['feedback'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25 p-3 rounded-3 mb-4">
                        <div class="d-flex align-items-center gap-2 text-warning fw-bold">
                            <i class="bi bi-exclamation-circle"></i> Not Submitted
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Upload Form -->
                <?php 
                $canSubmit = true;
                // Basic logic: if graded, maybe don't allow resubmit? Or allow until deadline?
                // Let's allow resubmission unless it's graded for now.
                if ($submission && $submission['status'] === 'GRADED') {
                    $canSubmit = false;
                }
                ?>
                
                <?php if ($canSubmit): ?>
                    <form action="/sia/lms/student/course/<?= $course['lms_course_id'] ?>/assignments/<?= $assignment['id'] ?>/submit" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small text-uppercase">Upload <?= $submission ? 'New ' : '' ?>File</label>
                            <input type="file" name="submission_file" class="form-control" required>
                            <div class="form-text">Max file size: 10MB. Allowed formats: PDF, DOCX, ZIP, PNG, JPG.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="bi bi-cloud-upload me-2"></i> <?= $submission ? 'Resubmit Assignment' : 'Submit Assignment' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <button class="btn btn-secondary w-100 fw-bold" disabled>
                        <i class="bi bi-lock me-2"></i> Submission Closed (Graded)
                    </button>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>
