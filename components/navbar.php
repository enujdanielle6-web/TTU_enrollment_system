<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top main-navbar">
  <div class="container">
    <a class="navbar-brand" href="#hero" aria-label="Triple T University home">
      <span class="school-logo">
        <i class="bi bi-mortarboard-fill"></i>
      </span>
      <span class="school-name">Triple T University</span>
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

