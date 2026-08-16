<?php require_once __DIR__ . '/../../../../Views/components/header.php'; ?>

<div class="container py-5">
    <div class="mb-4">
        <a href="/sia/lms/faculty/course/<?= $course['lms_course_id'] ?>/assignments" class="text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Back to Assignments
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4 bg-light border-bottom rounded-top-4">
            <h2 class="h4 fw-bold mb-1"><?= htmlspecialchars($assignment['title']) ?></h2>
            <p class="text-muted mb-0">Submissions Overview &bull; Max Score: <?= $assignment['max_score'] ?></p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3">Student Name</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Submitted At</th>
                            <th class="py-3">File</th>
                            <th class="py-3">Score</th>
                            <th class="text-end pe-4 py-3">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($submissions)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                                    No submissions yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($submissions as $sub): ?>
                                <tr>
                                    <td class="ps-4 fw-medium text-dark">
                                        <?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?>
                                    </td>
                                    <td>
                                        <?php if ($sub['status'] === 'GRADED'): ?>
                                            <span class="badge bg-success">Graded</span>
                                        <?php elseif ($sub['status'] === 'RESUBMITTED'): ?>
                                            <span class="badge bg-info">Resubmitted</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Submitted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= date('M d, Y h:i A', strtotime($sub['submitted_at'])) ?>
                                    </td>
                                    <td>
                                        <a href="/sia/lms/download/submission/<?= $sub['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="bi bi-download"></i> <?= htmlspecialchars($sub['file_name']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= $sub['grade'] !== null ? $sub['grade'] . ' / ' . $assignment['max_score'] : '<span class="text-muted">Not graded</span>' ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#gradeModal<?= $sub['id'] ?>">
                                            <?= $sub['grade'] !== null ? 'Update Grade' : 'Grade' ?>
                                        </button>

                                        <!-- Grading Modal -->
                                        <div class="modal fade text-start" id="gradeModal<?= $sub['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="/sia/lms/faculty/course/<?= $course['lms_course_id'] ?>/assignments/<?= $assignment['id'] ?>/grade" method="POST">
                                                        <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Grade Submission: <?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Score (out of <?= $assignment['max_score'] ?>)</label>
                                                                <input type="number" step="0.01" name="grade" class="form-control" value="<?= $sub['grade'] ?>" required max="<?= $assignment['max_score'] ?>" min="0">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Feedback / Comments (Optional)</label>
                                                                <textarea name="feedback" class="form-control" rows="4"><?= htmlspecialchars($sub['feedback']) ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">Save Grade</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/components/footer.php'; ?>
