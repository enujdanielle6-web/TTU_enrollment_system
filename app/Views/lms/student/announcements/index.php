<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Unified Course Header & Horizontal Navigation (Option A) -->
    <?php 
    $active_tab = 'announcements';
    require __DIR__ . '/../components/course_header.php'; 
    ?>

    <div id="course-tab-content" class="course-tab-content">
        <div class="row g-4">
            <?php if (empty($announcements)): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="bi bi-megaphone fs-1 d-block mb-3 opacity-50"></i>
                    No announcements for this course.
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <div class="col-12">
                        <div class="lms-card p-4 border-0 shadow-sm rounded-4">
                            <h4 class="fw-bold mb-2 text-dark"><?= htmlspecialchars($ann['title']) ?></h4>
                            <div class="text-muted small mb-3">
                                <i class="bi bi-person me-1"></i> <?= htmlspecialchars($ann['first_name'] . ' ' . $ann['last_name']) ?> &bull; 
                                <i class="bi bi-clock me-1"></i> <?= date('F d, Y h:i A', strtotime($ann['published_at'])) ?>
                            </div>
                            <p class="text-secondary mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($ann['content']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>
