<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sia/lms/faculty/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($course['subject_code']) ?></a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments" class="text-decoration-none text-muted">Assignments</a></li>
            <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Submissions</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4 bg-light border-bottom rounded-top-4">
            <h2 class="h4 fw-bold mb-1"><?= htmlspecialchars($assignment['title']) ?></h2>
            <p class="text-muted mb-0">Submissions Overview &bull; Max Score: <?= esc($assignment['max_score']) ?> pts</p>
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
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1">Graded</span>
                                        <?php elseif ($sub['status'] === 'RESUBMITTED'): ?>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2.5 py-1">Resubmitted</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1">Submitted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= date('M d, Y h:i A', strtotime($sub['submitted_at'])) ?>
                                    </td>
                                    <td>
                                        <a href="/sia/lms/download/submission/<?= esc($sub['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" target="_blank">
                                            <i class="bi bi-download"></i> <?= htmlspecialchars($sub['file_name']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= esc($sub['grade'] !== null ? $sub['grade'] . ' / ' . $assignment['max_score'] : '-') ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-xs" data-bs-toggle="modal" data-bs-target="#gradeModal<?= esc($sub['id']) ?>">
                                            <i class="bi bi-pencil-square me-1"></i> Grade
                                        </button>
                                        
                                        <!-- Modal -->
                                        <div class="modal fade text-start" id="gradeModal<?= esc($sub['id']) ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content border-0 shadow rounded-4">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Grade Submission: <?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments/submissions/<?= esc($sub['id']) ?>/grade" method="POST">
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Grade (Max: <?= esc($assignment['max_score']) ?>)</label>
                                                                <input type="number" step="0.01" name="grade" class="form-control" max="<?= esc($assignment['max_score']) ?>" min="0" value="<?= esc($sub['grade'] ?? '') ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Feedback</label>
                                                                <textarea name="feedback" class="form-control" rows="4"><?= htmlspecialchars($sub['feedback'] ?? '') ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">Save Grade</button>
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

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
