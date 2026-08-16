<?php require_once __DIR__ . '/../../../../Views/components/header.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Announcements: <?= htmlspecialchars($course['subject_code']) ?></h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($course['subject_name']) ?> &bull; Section <?= htmlspecialchars($course['section_code']) ?></p>
        </div>
        <a href="/sia/lms/faculty/course/<?= $course['lms_course_id'] ?>/announcements/create" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> New Announcement
        </a>
    </div>

    <div class="row g-4">
        <?php if (empty($announcements)): ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-megaphone fs-1 d-block mb-3 opacity-50"></i>
                No announcements created yet.
            </div>
        <?php else: ?>
            <?php foreach ($announcements as $ann): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($ann['title']) ?></h4>
                                <div class="d-flex gap-2">
                                    <?php if ($ann['status'] === 'published'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2"><i class="bi bi-broadcast me-1"></i> Published</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-2"><i class="bi bi-pencil me-1"></i> Draft</span>
                                    <?php endif; ?>
                                    <a href="/sia/lms/faculty/course/<?= $course['lms_course_id'] ?>/announcements/<?= $ann['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                                        Edit
                                    </a>
                                </div>
                            </div>
                            
                            <div class="text-muted small mb-3">
                                <i class="bi bi-person me-1"></i> <?= htmlspecialchars($ann['first_name'] . ' ' . $ann['last_name']) ?> &bull; 
                                <i class="bi bi-clock me-1"></i> 
                                <?php if ($ann['status'] === 'published'): ?>
                                    Published <?= date('M d, Y h:i A', strtotime($ann['published_at'])) ?>
                                <?php else: ?>
                                    Created <?= date('M d, Y h:i A', strtotime($ann['created_at'])) ?>
                                <?php endif; ?>
                            </div>

                            <p class="text-secondary mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($ann['content']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/components/footer.php'; ?>
