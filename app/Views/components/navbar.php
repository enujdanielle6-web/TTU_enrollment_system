<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg sticky-top main-navbar py-2 py-lg-3">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="#hero" aria-label="Triple T University home">
      <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 44px; width: auto; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(13, 110, 253, 0.2));">
      <div class="d-flex flex-column">
        <span class="school-name mb-0 fw-bold text-dark" style="font-size: 1.15rem; letter-spacing: -0.2px;">Triple T University</span>
        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.65rem; letter-spacing: 1px;">Admissions & Academic Portal</span>
      </div>
    </a>
    <button class="navbar-toggler border-0 shadow-none p-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 pt-3 pt-lg-0">
        <li class="nav-item">
          <a class="nav-link px-3" href="#hero">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3" href="#about">About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3" href="#admission-process">Admissions</a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3" href="#courses">Programs</a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3" href="#scholarships">Scholarships</a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3" href="#contact">Contact</a>
        </li>
        <li class="nav-item dropdown ms-lg-1">
          <a class="nav-link dropdown-toggle fw-semibold d-inline-flex align-items-center gap-1 text-primary px-3 rounded-pill bg-primary bg-opacity-10" href="#" id="lmsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-cloud-arrow-up-fill"></i>
            LMS Portal
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-2" aria-labelledby="lmsDropdown" style="min-width: 270px; backdrop-filter: blur(16px); background: rgba(255, 255, 255, 0.98); border: 1px solid rgba(226, 232, 240, 0.8) !important;">
            <li class="px-3 py-1 text-muted small fw-bold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">E-Learning Gateways</li>
            <li>
              <a class="dropdown-item rounded-3 py-2 px-3 d-flex align-items-center gap-3 transition-all hover-lift" href="/sia/auth/lms_student_login.php">
                <div class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width: 42px; height: 42px; background: linear-gradient(135deg, #0d6efd 0%, #2563eb 100%); box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);">
                  <i class="bi bi-mortarboard-fill fs-5"></i>
                </div>
                <div>
                  <span class="d-block fw-bold text-dark" style="font-size: 0.9rem;">Student Portal</span>
                  <span class="d-block text-muted" style="font-size: 0.75rem;">Courses, grades & submissions</span>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item rounded-3 py-2 px-3 d-flex align-items-center gap-3 mt-1 transition-all hover-lift" href="/sia/auth/lms_faculty_login.php">
                <div class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width: 42px; height: 42px; background: linear-gradient(135deg, #0284c7 0%, #06b6d4 100%); box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);">
                  <i class="bi bi-person-video3 fs-5"></i>
                </div>
                <div>
                  <span class="d-block fw-bold text-dark" style="font-size: 0.9rem;">Faculty Portal</span>
                  <span class="d-block text-muted" style="font-size: 0.75rem;">Manage classes & grading</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        <?php if (!empty($_SESSION['logged_in'])): ?>
          <li class="nav-item ms-lg-2">
            <?php if (in_array($_SESSION['user_role'] ?? '', ['superadmin', 'admissions', 'scholarship', 'cashier'], true)): ?>
              <a class="nav-link nav-login px-3 fw-semibold text-primary" href="/sia/admin/dashboard.php">
                <i class="bi bi-grid-fill me-1"></i>
                Dashboard
              </a>
            <?php else: ?>
              <a class="nav-link nav-login px-3 fw-semibold text-primary" href="/sia/applicant/dashboard.php">
                <i class="bi bi-grid-fill me-1"></i>
                Dashboard
              </a>
            <?php endif; ?>
          </li>
          <li class="nav-item ms-lg-1">
            <a class="btn btn-outline-danger btn-sm rounded-pill px-3 py-2 fw-semibold" href="/sia/auth/logout.php">
              <i class="bi bi-box-arrow-right me-1"></i>
              Logout
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item ms-lg-2">
            <a class="nav-link px-3 fw-semibold text-dark" href="/sia/auth/login.php">
              <i class="bi bi-box-arrow-in-right me-1 text-primary"></i>
              Login
            </a>
          </li>
          <li class="nav-item ms-lg-1">
            <a class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm" href="/sia/auth/register.php" style="background: linear-gradient(135deg, #0d6efd 0%, #1d4ed8 100%); border: none; box-shadow: 0 4px 14px rgba(13, 110, 253, 0.35) !important;">
              <i class="bi bi-pencil-square"></i>
              <span>Register</span>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>


