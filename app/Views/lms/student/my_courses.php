<?php require_once __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">My Courses</h1>
            <p class="text-muted mb-0">View and manage all your enrolled courses for the current semester.</p>
        </div>
        
        <div class="d-flex gap-2">
            <div class="input-group input-group-sm bg-white rounded shadow-sm overflow-hidden border">
                <span class="input-group-text bg-transparent border-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control bg-transparent border-0 shadow-none" placeholder="Search courses..." style="width: 200px;">
            </div>
            <button class="btn btn-sm btn-light border shadow-sm">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </div>

    <!-- Filter Tabs -->
    <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3">
        <li class="nav-item">
            <a class="nav-link active bg-primary text-white rounded-pill px-4" href="#">All Courses (<?= count($enrolled_courses) ?>)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-muted bg-white border rounded-pill px-4" href="#">In Progress</a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-muted bg-white border rounded-pill px-4" href="#">Completed</a>
        </li>
    </ul>

    <?php if (empty($enrolled_courses)): ?>
        <div class="text-center py-5 bg-white rounded shadow-sm border">
            <i class="bi bi-journal-x text-muted mb-3" style="font-size: 3rem;"></i>
            <h3 class="h5 fw-bold text-dark">No Courses Found</h3>
            <p class="text-muted">You are not currently enrolled in any courses for this semester.</p>
            <a href="/sia/applicant/dashboard.php" class="btn btn-primary mt-2">Go to Admissions</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($enrolled_courses as $course): ?>
                <div class="col-md-4 col-lg-3">
                    <a href="/sia/lms/student/course.php?id=<?= $course['lms_course_id'] ?>" class="text-decoration-none text-dark d-block h-100">
                        <div class="lms-card h-100 transition-all shadow-sm-hover overflow-hidden border bg-white rounded">
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center border-bottom" style="height: 160px;">
                                <i class="bi bi-image text-muted opacity-25" style="font-size: 3rem;"></i>
                            </div>
                            <div class="p-4 text-center">
                                <h4 class="h6 fw-bold text-dark text-truncate mb-1" title="<?= htmlspecialchars($course['name']) ?>">
                                    <?= htmlspecialchars($course['name']) ?>
                                </h4>
                                <div class="text-muted small fw-semibold">
                                    <?= htmlspecialchars($course['code']) ?>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

