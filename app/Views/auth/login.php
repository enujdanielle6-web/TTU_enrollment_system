<?php
$pageTitle = 'Login - Admissions & Academic Portal - Triple T University';
require_once __DIR__ . '/../components/header.php';
?>

<main class="auth-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-8 col-xl-6">
        <div class="auth-island fade-in-up" style="animation-delay: 0.08s;">
          
          <!-- Portal Switcher Pill Link -->
          <div class="d-flex justify-content-center mb-3">
            <div class="d-inline-flex align-items-center gap-2 p-1 px-2.5 rounded-pill bg-slate-100 border border-slate-200 shadow-2xs" style="background: rgba(241, 245, 249, 0.95); font-size: 0.75rem;">
              <span class="badge bg-primary text-white rounded-pill px-2 py-0.5 fw-semibold">
                <i class="bi bi-shield-check me-1"></i> SIS Portal
              </span>
              <a href="<?= BASE_PATH ?>/auth/lms_student_login.php" class="text-decoration-none text-muted fw-semibold hover-text-primary d-inline-flex align-items-center gap-1 transition-all">
                <span>Switch to LMS Portal</span>
                <i class="bi bi-arrow-right-short fs-6"></i>
              </a>
            </div>
          </div>

          <!-- Brand & Header -->
          <div class="text-center mb-4">
            <a href="<?= BASE_PATH ?>/" class="auth-brand-badge text-decoration-none" title="Triple T University Home">
              <img src="<?= BASE_PATH ?>/images/TTU_LOGO.png" alt="TTU Seal">
            </a>
            <h1 class="h4 fw-bold text-dark mb-1" style="letter-spacing: -0.025em; font-family: 'Poppins', var(--font-family-sans, sans-serif);">Triple T University</h1>
            <div class="d-inline-flex align-items-center gap-1.5 px-3 py-1 rounded-pill mb-2 bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
              <i class="bi bi-mortarboard-fill"></i> Admissions &amp; Enrollment Portal
            </div>
            <p class="text-muted mb-0 small" style="font-size: 0.84rem; line-height: 1.45;">Sign in to access your administrative workspace or student applicant account.</p>
          </div>

          <!-- Alert Notifications -->
          <?php if (!empty($success)): ?>
            <div class="alert alert-success rounded-3 border-0 bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-2.5 px-3 small shadow-xs mb-3 d-flex align-items-center gap-2">
              <i class="bi bi-check-circle-fill fs-5 flex-shrink-0 text-success"></i>
              <div class="fw-medium"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger rounded-3 border-0 bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-2.5 px-3 small shadow-xs mb-3">
              <?php foreach ((array)$errors as $error): ?>
                <div class="d-flex align-items-center gap-2 mb-1 last-mb-0">
                  <i class="bi bi-exclamation-circle-fill fs-5 flex-shrink-0 text-danger"></i>
                  <span class="fw-medium"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <!-- Main Login Form -->
          <form id="sisLoginForm" action="<?= BASE_PATH ?>/auth/login_process.php" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="mb-3 text-start">
              <label class="form-label text-dark small fw-bold text-uppercase mb-1" for="email" style="font-size: 0.72rem; letter-spacing: 0.04em;">Email Address</label>
              <div class="auth-input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input class="form-control" type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="name@ttu.edu.ph or applicant email" autofocus autocomplete="email">
              </div>
            </div>

            <div class="mb-3 text-start">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label text-dark small fw-bold text-uppercase mb-0" for="password" style="font-size: 0.72rem; letter-spacing: 0.04em;">Password</label>
                <a href="<?= BASE_PATH ?>/auth/forgot_password.php?portal=applicant" class="small text-decoration-none text-primary fw-semibold" style="font-size: 0.78rem;">Forgot Password?</a>
              </div>
              <div class="auth-input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input class="form-control" type="password" id="password" name="password" required placeholder="Enter your password" autocomplete="current-password">
                <button class="btn-password-toggle" type="button" id="togglePassword" aria-label="Toggle password visibility">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <!-- Submit Button (With immediate debouncing) -->
            <button class="btn btn-primary w-100 fw-bold rounded-pill py-2.5 shadow-sm d-flex align-items-center justify-content-center gap-2 mt-3" id="submitBtn" type="submit" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); border: none; font-size: 0.95rem; box-shadow: 0 10px 24px -4px rgba(37, 99, 235, 0.45); transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);">
              <i class="bi bi-box-arrow-in-right fs-5"></i>
              <span id="btnText">Sign In to SIS Portal</span>
            </button>
          </form>

          <!-- Footer Links -->
          <div class="mt-4 pt-3 border-top">

            <!-- Registration & Home links -->
            <div class="text-center mt-3 pt-1">
              <p class="mb-2 text-muted small" style="font-size: 0.82rem;">
                New student applicant? <a href="<?= BASE_PATH ?>/auth/register.php" class="fw-bold text-decoration-none text-primary hover-underline">Start registration here &rarr;</a>
              </p>
              <div class="d-flex justify-content-center align-items-center gap-3 mt-2 flex-wrap" style="font-size: 0.8rem;">
                <a href="<?= BASE_PATH ?>/auth/lms_student_login.php" class="text-decoration-none text-primary fw-semibold d-inline-flex align-items-center gap-1.5">
                  <i class="bi bi-mortarboard-fill"></i> Access TTU LMS Portal
                </a>
                <span class="text-muted opacity-50">&bull;</span>
                <a href="<?= BASE_PATH ?>/" class="text-muted text-decoration-none d-inline-flex align-items-center gap-1.5 hover-text-dark transition-all">
                  <i class="bi bi-arrow-left"></i> Back to Homepage
                </a>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
  sessionStorage.removeItem('enrollmentFormData');

  const togglePassword = document.getElementById("togglePassword");
  const passwordInput = document.getElementById("password");
  const loginForm = document.getElementById("sisLoginForm");
  const submitBtn = document.getElementById("submitBtn");
  const btnText = document.getElementById("btnText");

  if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", function() {
      const isPass = passwordInput.getAttribute("type") === "password";
      passwordInput.setAttribute("type", isPass ? "text" : "password");
      this.innerHTML = isPass ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
      this.setAttribute("aria-label", isPass ? "Hide password" : "Show password");
    });
  }

  if (loginForm && submitBtn) {
    loginForm.addEventListener("submit", function() {
      submitBtn.disabled = true;
      if (btnText) {
        btnText.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5" role="status" aria-hidden="true"></span> Authenticating...';
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
