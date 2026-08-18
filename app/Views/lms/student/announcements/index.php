<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sia/lms/student/dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/student/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none"><?= htmlspecialchars($course['subject_code']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Announcements</li>
        </ol>
    </nav>

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

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>
