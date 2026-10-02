<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Course Header & Horizontal Navigation -->
    <?php 
    $active_tab = 'announcements';
    require __DIR__ . '/../components/course_header.php'; 
    ?>

    <div id="course-tab-content" class="course-tab-content">
        <?php
        $publishedCount = 0;
        $draftCount = 0;
        foreach ($announcements as $ann) {
            if (($ann['status'] ?? '') === 'published') {
                $publishedCount++;
            } else {
                $draftCount++;
            }
        }
        $totalAnnouncements = count($announcements);
        ?>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card-kpi h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-label">Total Notices</span>
                        <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);">
                            <i class="bi bi-megaphone text-white"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $totalAnnouncements ?></div>
                    <div class="stat-subtext text-primary">
                        <i class="bi bi-bell-fill me-1"></i> Course Announcements
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card-kpi h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-label">Published</span>
                        <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <i class="bi bi-broadcast text-white"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $publishedCount ?></div>
                    <div class="stat-subtext text-success">
                        <i class="bi bi-check-circle-fill me-1"></i> Active Broadcasts
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card-kpi h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-label">Draft Notices</span>
                        <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <i class="bi bi-pencil-square text-white"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $draftCount ?></div>
                    <div class="stat-subtext text-warning">
                        <i class="bi bi-clock-history me-1"></i> Unpublished Drafts
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Action Toolbar -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h4 class="h5 fw-bold text-dark mb-1">
                    <i class="bi bi-megaphone me-2 text-primary"></i>Course Announcements
                </h4>
                <p class="text-muted small mb-0">Broadcast messages, exam guidelines, room adjustments, and reminders to your class.</p>
            </div>
            <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/announcements/create" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>New Announcement</span>
            </a>
        </div>

        <!-- Announcements Feed -->
        <div class="row g-4">
            <?php if (empty($announcements)): ?>
                <div class="col-12">
                    <div class="lms-card p-5 text-center border-0 shadow-sm bg-white rounded-4">
                        <i class="bi bi-megaphone text-muted opacity-50 mb-3" style="font-size: 3.5rem;"></i>
                        <h5 class="fw-bold text-dark">No Course Announcements Yet</h5>
                        <p class="text-muted mb-4" style="max-width: 450px; margin: 0 auto;">
                            Keep your students updated by posting your first course notice, syllabus adjustment, or deadline reminder.
                        </p>
                        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/announcements/create" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                            <i class="bi bi-plus-lg me-1"></i> Post Announcement
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $ann): 
                    $isPublished = ($ann['status'] === 'published');
                    $authorName = trim(($ann['first_name'] ?? '') . ' ' . ($ann['last_name'] ?? ''));
                    if (empty($authorName)) $authorName = 'Instructor';
                    $initial = strtoupper(substr($authorName, 0, 1));
                    $dateText = $isPublished && !empty($ann['published_at']) 
                        ? 'Published ' . date('M d, Y \a\t g:i A', strtotime($ann['published_at']))
                        : 'Draft created ' . date('M d, Y \a\t g:i A', strtotime($ann['created_at']));
                ?>
                    <div class="col-12">
                        <div class="lms-card border-0 shadow-sm rounded-4 bg-white overflow-hidden transition-all hover-shadow">
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 42px; height: 42px; flex-shrink: 0; font-size: 1.1rem;">
                                            <?= esc($initial) ?>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-0.5 flex-wrap">
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($authorName) ?></span>
                                                <span class="badge bg-light text-primary border rounded-pill px-2 py-0 small fw-semibold">Instructor</span>
                                                <?php if ($isPublished): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-0 small fw-bold">
                                                        <i class="bi bi-broadcast me-1"></i>Published
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill px-2 py-0 small fw-bold">
                                                        <i class="bi bi-pencil me-1"></i>Draft
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted"><i class="bi bi-clock me-1"></i><?= esc($dateText) ?></small>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                                        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/announcements/<?= esc($ann['id']) ?>/edit" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 fw-semibold">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </a>
                                        <form method="POST" action="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/announcements/<?= esc($ann['id']) ?>/delete" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                            <?= getCsrfInput() ?>
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-1" title="Delete Announcement">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($ann['title']) ?></h5>
                                <div class="text-secondary lh-base mb-0" style="white-space: pre-wrap; font-size: 0.95rem;"><?= htmlspecialchars($ann['content']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
