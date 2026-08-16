<?php require_once __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sia/lms/student/dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">My Profile</li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="lms-card p-4 text-center border-0 shadow-sm mb-4 position-relative overflow-hidden">
                <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 100px; opacity: 0.1;"></div>
                <div class="position-relative mt-4 mb-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto shadow-sm" style="width: 100px; height: 100px; font-size: 2.5rem; border: 4px solid #fff;">
                        <?= substr($_SESSION['lms_name'] ?? 'S', 0, 1) ?>
                    </div>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($_SESSION['lms_name'] ?? 'Student Name') ?></h4>
                <p class="text-muted mb-3"><?= htmlspecialchars($_SESSION['lms_email'] ?? 'student@ttu.edu.ph') ?></p>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">
                    Student Account
                </span>
            </div>

            <!-- About Card -->
            <div class="lms-card p-4 border-0 shadow-sm">
                <h6 class="fw-bold mb-3 text-dark text-uppercase small">Account Details</h6>
                <ul class="list-unstyled mb-0">
                    <li class="d-flex align-items-center mb-3">
                        <i class="bi bi-person-badge text-muted me-3 fs-5"></i>
                        <div>
                            <small class="text-muted d-block">Role</small>
                            <span class="fw-medium text-dark">Student</span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-3">
                        <i class="bi bi-building text-muted me-3 fs-5"></i>
                        <div>
                            <small class="text-muted d-block">Institution</small>
                            <span class="fw-medium text-dark">Taguig Technological University</span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center">
                        <i class="bi bi-clock-history text-muted me-3 fs-5"></i>
                        <div>
                            <small class="text-muted d-block">Timezone</small>
                            <span class="fw-medium text-dark">Asia/Manila (UTC+8)</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Settings Card -->
            <div class="lms-card p-4 border-0 shadow-sm h-100">
                <h5 class="fw-bold mb-4 text-dark border-bottom pb-3">Preferences</h5>
                
                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-3">Notifications</h6>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="notifEmail" checked>
                        <label class="form-check-label text-dark" for="notifEmail">Email Notifications</label>
                        <small class="d-block text-muted">Receive email updates for assignments and grades.</small>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notifPush" checked>
                        <label class="form-check-label text-dark" for="notifPush">In-App Notifications</label>
                        <small class="d-block text-muted">Show bell alerts for course announcements.</small>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-3">Privacy</h6>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="privProfile" checked>
                        <label class="form-check-label text-dark" for="privProfile">Public Profile in Course Roster</label>
                        <small class="d-block text-muted">Allow classmates to see your name in the course members list.</small>
                    </div>
                </div>

                <div class="mt-5 pt-3 border-top">
                    <button class="btn btn-primary px-4 fw-semibold shadow-sm">Save Preferences</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
