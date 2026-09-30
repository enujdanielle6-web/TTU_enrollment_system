<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Breadcrumb & Return -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 align-items-center">
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($course['subject_code']) ?></a></li>
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments" class="text-decoration-none text-muted">Assignments</a></li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Submissions</li>
            </ol>
        </nav>
        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Assignments
        </a>
    </div>

    <!-- Assignment Context Hero Card -->
    <div class="lms-card p-4 mb-4 border-0 shadow-sm bg-white rounded-4 position-relative overflow-hidden">
        <div class="row align-items-center g-3">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1.5 rounded-pill fw-bold">
                        <i class="bi bi-journal-text me-1"></i><?= htmlspecialchars($course['subject_code']) ?>
                    </span>
                    <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-semibold">
                        Max Score: <?= esc($assignment['max_score']) ?> Pts
                    </span>
                    <?php if (!empty($assignment['due_date'])): ?>
                        <span class="badge bg-light text-muted border px-3 py-1.5 rounded-pill">
                            <i class="bi bi-clock me-1"></i>Due <?= date('M d, Y h:i A', strtotime($assignment['due_date'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h3 class="h4 fw-bold text-dark mb-1"><?= htmlspecialchars($assignment['title']) ?></h3>
                <?php if (!empty($assignment['description'])): ?>
                    <p class="text-muted small mb-0 mt-2 lh-base" style="max-width: 750px;">
                        <?= nl2br(htmlspecialchars($assignment['description'])) ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="col-md-4 text-md-end">
                <?php
                $totalSubs = count($submissions);
                $gradedCount = 0;
                foreach ($submissions as $sub) {
                    if (($sub['status'] ?? '') === 'GRADED' || $sub['grade'] !== null) {
                        $gradedCount++;
                    }
                }
                $pendingCount = $totalSubs - $gradedCount;
                ?>
                <div class="d-inline-flex gap-2">
                    <div class="text-center px-3 py-2 bg-light rounded-3 border">
                        <span class="d-block fw-bold text-dark fs-5 lh-1"><?= $totalSubs ?></span>
                        <span class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 700;">Turned In</span>
                    </div>
                    <div class="text-center px-3 py-2 bg-light rounded-3 border">
                        <span class="d-block fw-bold text-success fs-5 lh-1"><?= $gradedCount ?></span>
                        <span class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 700;">Graded</span>
                    </div>
                    <div class="text-center px-3 py-2 bg-light rounded-3 border">
                        <span class="d-block fw-bold text-warning fs-5 lh-1"><?= $pendingCount ?></span>
                        <span class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 700;">To Grade</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submissions Table Card -->
    <div class="lms-card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="h6 fw-bold text-dark mb-0">Student Submissions</h5>
            <div style="max-width: 280px; width: 100%;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="submissionSearchInput" class="form-control bg-light border-start-0" placeholder="Filter by student name...">
                </div>
            </div>
        </div>

        <div class="p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="submissionsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted text-uppercase small" style="letter-spacing: 0.04em;">Student Name</th>
                            <th class="py-3 text-muted text-uppercase small" style="letter-spacing: 0.04em;">Status</th>
                            <th class="py-3 text-muted text-uppercase small" style="letter-spacing: 0.04em;">Submitted At</th>
                            <th class="py-3 text-muted text-uppercase small" style="letter-spacing: 0.04em;">Attached File</th>
                            <th class="py-3 text-muted text-uppercase small" style="letter-spacing: 0.04em;">Score</th>
                            <th class="text-end pe-4 py-3 text-muted text-uppercase small" style="letter-spacing: 0.04em;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($submissions)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    No student submissions turned in for this assignment yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($submissions as $sub): 
                                $studentName = trim(($sub['first_name'] ?? '') . ' ' . ($sub['last_name'] ?? ''));
                                $initial = strtoupper(substr($sub['first_name'] ?? 'S', 0, 1));
                                $isGraded = ($sub['status'] === 'GRADED' || $sub['grade'] !== null);
                            ?>
                                <tr class="submission-row">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; flex-shrink: 0;">
                                                <?= esc($initial) ?>
                                            </div>
                                            <div>
                                                <span class="d-block fw-bold text-dark student-name-text"><?= htmlspecialchars($studentName) ?></span>
                                                <small class="text-muted font-monospace"><?= htmlspecialchars($sub['student_number'] ?? 'Student') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($isGraded): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 fw-bold">
                                                <i class="bi bi-check-circle me-1"></i>Graded
                                            </span>
                                        <?php elseif (($sub['status'] ?? '') === 'RESUBMITTED'): ?>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 fw-bold">
                                                <i class="bi bi-arrow-repeat me-1"></i>Resubmitted
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill px-2.5 py-1 fw-bold">
                                                <i class="bi bi-hourglass-split me-1"></i>Pending Review
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="small text-muted">
                                            <i class="bi bi-clock me-1"></i><?= date('M d, Y h:i A', strtotime($sub['submitted_at'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($sub['file_name'])): ?>
                                            <a href="/sia/lms/download/submission/<?= esc($sub['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1.5" target="_blank">
                                                <i class="bi bi-file-earmark-arrow-down"></i>
                                                <span class="text-truncate" style="max-width: 160px;"><?= htmlspecialchars($sub['file_name']) ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">No file</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isGraded): ?>
                                            <span class="fw-bold text-dark fs-6"><?= esc($sub['grade']) ?></span>
                                            <span class="text-muted small">/ <?= esc($assignment['max_score']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">— / <?= esc($assignment['max_score']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm <?= esc($isGraded ? 'btn-outline-primary' : 'btn-primary') ?> rounded-pill px-3.5 py-1 fw-bold shadow-xs" data-bs-toggle="modal" data-bs-target="#gradeModal<?= esc($sub['id']) ?>">
                                            <i class="bi <?= esc($isGraded ? 'bi-pencil' : 'bi-check2-circle') ?> me-1"></i>
                                            <?= esc($isGraded ? 'Edit Grade' : 'Grade') ?>
                                        </button>
                                        
                                        <!-- Grade Modal -->
                                        <div class="modal fade text-start" id="gradeModal<?= esc($sub['id']) ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg rounded-4">
                                                    <div class="modal-header border-bottom p-4">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="icon-box-sm bg-primary bg-opacity-10 text-primary">
                                                                <i class="bi bi-award"></i>
                                                            </div>
                                                            <div>
                                                                <h5 class="modal-title fw-bold text-dark mb-0">Grade Submission</h5>
                                                                <small class="text-muted"><?= htmlspecialchars($studentName) ?></small>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments/submissions/<?= esc($sub['id']) ?>/grade" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                                                        <?= getCsrfInput() ?>
                                                        <input type="hidden" name="submission_id" value="<?= esc($sub['id']) ?>">
                                                        <div class="modal-body p-4">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold text-dark">Awarded Grade / Score</label>
                                                                <div class="input-group">
                                                                    <input type="number" step="0.5" name="grade" class="form-control form-control-lg fw-bold" max="<?= esc($assignment['max_score']) ?>" min="0" value="<?= esc($sub['grade'] ?? '') ?>" placeholder="0" required>
                                                                    <span class="input-group-text bg-light text-muted fw-bold">/ <?= esc($assignment['max_score']) ?> Pts</span>
                                                                </div>
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="form-label fw-bold text-dark">Instructor Feedback</label>
                                                                <textarea name="feedback" class="form-control" rows="4" placeholder="Provide constructive remarks, commendations, or areas for improvement..."><?= htmlspecialchars($sub['feedback'] ?? '') ?></textarea>
                                                                <div class="form-text">Visible to the student inside their LMS assignment grade view.</div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer bg-light border-top p-3 rounded-bottom-4">
                                                            <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                                                                <i class="bi bi-check2-circle me-1"></i> Save Grade
                                                            </button>
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

<script>
(function() {
    const input = document.getElementById('submissionSearchInput');
    if (!input) return;
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#submissionsTable .submission-row');
        rows.forEach(row => {
            const nameEl = row.querySelector('.student-name-text');
            const name = nameEl ? nameEl.textContent.toLowerCase() : '';
            if (name.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
