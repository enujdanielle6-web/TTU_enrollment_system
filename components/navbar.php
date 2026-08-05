<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top main-navbar">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="#hero" aria-label="Triple T University home">
      <img src="../images/TTU_LOGO.png" alt="TTU Logo" style="height: 40px; width: auto; object-fit: contain;">
      <span class="school-name mb-0">Triple T University</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item">
          <a class="nav-link" href="#hero">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#about">About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#admission-process">Admissions</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#courses">Courses</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#contact">Contact</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle fw-semibold" style="color: var(--color-primary);" href="#" id="lmsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            LMS Portal
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-2" aria-labelledby="lmsDropdown" style="min-width: 240px; animation: fadeInUp 0.2s ease forwards;">
            <li>
              <a class="dropdown-item rounded-3 py-2 px-3 d-flex align-items-center gap-3 transition-all hover-lift" href="../auth/lms_student_login.php">
                <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                  <i class="bi bi-mortarboard-fill fs-5"></i>
                </div>
                <div>
                  <span class="d-block fw-semibold text-dark">Student Portal</span>
                  <span class="d-block text-muted" style="font-size: 0.75rem;">Access your courses</span>
                </div>
              </a>
            </li>
            <li>
              <a class="dropdown-item rounded-3 py-2 px-3 d-flex align-items-center gap-3 mt-1 transition-all hover-lift" href="../auth/lms_faculty_login.php">
                <div class="bg-info bg-opacity-10 text-info rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                  <i class="bi bi-person-video3 fs-5"></i>
                </div>
                <div>
                  <span class="d-block fw-semibold text-dark">Faculty Portal</span>
                  <span class="d-block text-muted" style="font-size: 0.75rem;">Manage your classes</span>
                </div>
              </a>
            </li>
          </ul>
        </li>
        <?php if (!empty($_SESSION['logged_in'])): ?>
          <li class="nav-item ms-lg-2">
            <?php if (in_array($_SESSION['user_role'] ?? '', ['superadmin', 'admissions', 'scholarship', 'cashier'], true)): ?>
              <a class="nav-link nav-login" href="../admin/dashboard.php">
                <i class="bi bi-grid"></i>
                Dashboard
              </a>
            <?php else: ?>
              <a class="nav-link nav-login" href="../applicant/dashboard.php">
                <i class="bi bi-grid"></i>
                Dashboard
              </a>
            <?php endif; ?>
          </li>
          <li class="nav-item">
            <a class="btn btn-outline-primary nav-register" href="../auth/logout.php">
              <i class="bi bi-box-arrow-right"></i>
              Logout
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item ms-lg-2">
            <a class="nav-link nav-login" href="../auth/login.php">
              <i class="bi bi-box-arrow-in-right"></i>
              Login
            </a>
          </li>
          <li class="nav-item">
            <a class="btn btn-primary nav-register" href="../auth/register.php">
              <i class="bi bi-person-plus"></i>
              Register
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

