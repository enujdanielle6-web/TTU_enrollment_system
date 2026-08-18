<?php require_once __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Dashboard / My Courses</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-dark mb-0">My Teaching Courses</h1>
    </div>

    <div class="row g-4">
        <?php if (empty($faculty_courses)): ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-journal-x fs-1 d-block mb-3 opacity-50"></i>
                You are not assigned to any courses.
            </div>
        <?php else: ?>
            <?php foreach ($faculty_courses as $course): ?>
                <div class="col-md-6 col-xl-4">
                    <a href="/sia/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none">
                        <div class="lms-card p-0 border-0 shadow-sm rounded-4 overflow-hidden h-100 position-relative transition-all hover-lift">
                            <div class="bg-primary p-4 text-white d-flex flex-column justify-content-between" style="min-height: 140px; position: relative;">
                                <div class="position-absolute top-0 end-0 p-3">
                                    <i class="bi bi-three-dots-vertical text-white-50"></i>
                                </div>
                                <div>
                                    <h4 class="fw-bold mb-1 text-truncate" title="<?= htmlspecialchars($course['subject_code']) ?>">
                                        <?= htmlspecialchars($course['subject_code']) ?>
                                    </h4>
                                    <p class="mb-0 text-white-50 text-truncate" title="<?= htmlspecialchars($course['subject_name']) ?>">
                                        <?= htmlspecialchars($course['subject_name']) ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-white d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between text-muted small">
                                    <span><i class="bi bi-person-badge me-1"></i> <?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></span>
                                    <span><i class="bi bi-diagram-2 me-1"></i> <?= htmlspecialchars($course['section_code']) ?></span>
                                </div>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-light text-dark border fw-normal px-2 py-1"><i class="bi bi-journal-text me-1"></i> Manage</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
