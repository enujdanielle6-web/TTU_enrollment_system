<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Unified Course Header & Horizontal Navigation (Option A) -->
    <?php 
    $active_tab = 'assignments';
    require __DIR__ . '/../components/course_header.php'; 
    ?>

    <div id="course-tab-content" class="course-tab-content">
        <div class="card lms-card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($assignments)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-journal-text display-1 text-light mb-3"></i>
                        <h5 class="fw-bold">No Assignments Yet</h5>
                        <p>Your instructor has not published any assignments for this course.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($assignments as $a): ?>
                            <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/assignments/<?= esc($a['id']) ?>" class="list-group-item list-group-item-action p-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1 text-primary fw-bold">
                                            <i class="bi bi-journal-text me-2"></i><?= htmlspecialchars($a['title']) ?>
                                        </h5>
                                        <p class="mb-1 text-muted small">
                                            <strong>Due:</strong> <?= esc($a['due_date'] ? date('F j, Y, g:i a', strtotime($a['due_date'])) : 'No Due Date') ?> &bull; 
                                            <strong>Points:</strong> <?= esc($a['max_score']) ?>
                                        </p>
                                    </div>
                                    <?php if ($a['submission']): ?>
                                        <span class="badge bg-success rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i> Submitted</span>
                                    <?php else: ?>
                                        <?php if ($a['due_date'] && strtotime($a['due_date']) < time()): ?>
                                            <span class="badge bg-danger rounded-pill px-3 py-2"><i class="bi bi-exclamation-circle me-1"></i> Missing</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-3 py-2">Not Submitted</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>
