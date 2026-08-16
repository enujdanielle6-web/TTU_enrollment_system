<?php require_once __DIR__ . '/../../../../Views/components/header.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Assignments: <?= htmlspecialchars($course['subject_code']) ?></h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($course['subject_name']) ?> &bull; Section <?= htmlspecialchars($course['section_code']) ?></p>
        </div>
        <a href="/sia/lms/faculty/course/<?= $course['lms_course_id'] ?>/assignments/create" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> New Assignment
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3">Title</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Due Date</th>
                            <th class="py-3">Max Score</th>
                            <th class="text-end pe-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignments)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                                    No assignments created yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td class="ps-4 fw-medium text-dark">
                                        <?= htmlspecialchars($assignment['title']) ?>
                                    </td>
                                    <td>
                                        <?php if ($assignment['status'] === 'published'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Published</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $assignment['due_date'] ? date('M d, Y h:i A', strtotime($assignment['due_date'])) : '<span class="text-muted">No due date</span>' ?>
                                    </td>
                                    <td><?= $assignment['max_score'] ?> pts</td>
                                    <td class="text-end pe-4">
                                        <a href="/sia/lms/faculty/course/<?= $course['lms_course_id'] ?>/assignments/<?= $assignment['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <a href="/sia/lms/faculty/course/<?= $course['lms_course_id'] ?>/assignments/<?= $assignment['id'] ?>/submissions" class="btn btn-sm btn-info text-white ms-1">View Submissions</a>
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
