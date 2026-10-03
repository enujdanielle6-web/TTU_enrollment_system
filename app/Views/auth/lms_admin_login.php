<?php
$pageTitle = 'LMS Administrator Portal Login - Triple T University';
require_once __DIR__ . '/../components/header.php';
?>

<main class="auth-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-8 col-xl-6">
        <div class="auth-island portal-admin fade-in-up" style="animation-delay: 0.08s;">
          
          <!-- LMS Role Switcher Segmented Bar -->
          <nav class="auth-portal-nav portal-admin-nav mb-3" aria-label="LMS Role Selection">
            <a href="<?= BASE_PATH ?>/auth/lms_student_login.php" title="Student LMS Portal">
              <i class="bi bi-mortarboard"></i> <span>Student</span>
            </a>
            <a href="<?= BASE_PATH ?>/auth/lms_faculty_login.php" title="Faculty LMS Portal">
              <i class="bi bi-person-video3"></i> <span>Faculty</span>
            </a>
            <a href="<?= BASE_PATH ?>/auth/lms_admin_login.php" class="active" title="Admin LMS Governance">
              <i class="bi bi-shield-lock-fill"></i> <span>Admin Portal</span>
            </a>
          </nav>

          <!-- Brand & Header -->
          <div class="text-center mb-4">
            <a href="<?= BASE_PATH ?>/" class="auth-brand-badge text-decoration-none" title="Triple T University Home">
              <img src="<?= BASE_PATH ?>/images/TTU_LOGO.png" alt="TTU Seal">
            </a>
            <h1 class="h4 fw-bold text-dark mb-1" style="letter-spacing: -0.025em; font-family: 'Poppins', var(--font-family-sans, sans-serif);">Triple T University</h1>
            <div class="d-inline-flex align-items-center gap-1.5 px-3 py-1 rounded-pill mb-2 bg-indigo bg-opacity-10 text-primary border border-primary border-opacity-25 fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
              <i class="bi bi-shield-lock-fill"></i> LMS Governance &amp; Administration
            </div>
            <p class="text-muted mb-0 small" style="font-size: 0.84rem; line-height: 1.45;">Secure administrative authentication for institutional e-learning operations.</p>
          </div>

          <!-- Alert Notifications -->
          <?php if (!empty($success)): ?>
            <div class="alert alert-success rounded-3 border-0 bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-2.5 px-3 small shadow-xs mb-3 d-flex align-items-center gap-2">
              <i class="bi bi-check-circle-fill fs-5 flex-shrink-0 text-success"></i>
              <div class="fw-medium"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          <?php endif; ?>

          <?php if (!empty($warning)): ?>
            <div class="alert alert-warning rounded-3 border-0 bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 py-2.5 px-3 small shadow-xs mb-3 d-flex align-items-center gap-2">
              <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 text-warning"></i>
              <div class="fw-medium"><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8'); ?></div>
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
          <form id="adminLoginForm" action="<?= BASE_PATH ?>/auth/lms_login_process.php" method="post" novalidate>
            <?= getCsrfInput() ?>
            <input type="hidden" name="role" value="admin">
            
            <div class="mb-3 text-start">
              <label class="form-label text-dark small fw-bold text-uppercase mb-1" for="employee_id" style="font-size: 0.72rem; letter-spacing: 0.04em;">Admin Email or Employee ID</label>
              <div class="auth-input-group">
                <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                <input class="form-control" type="text" id="employee_id" name="employee_id" value="<?= htmlspecialchars($old['employee_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="e.g. admin@ttu.edu.ph" autofocus autocomplete="username">
              </div>
            </div>

            <div class="mb-3 text-start">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label text-dark small fw-bold text-uppercase mb-0" for="password" style="font-size: 0.72rem; letter-spacing: 0.04em;">Password</label>
                <a href="<?= BASE_PATH ?>/auth/forgot_password.php?portal=admin" class="small text-decoration-none text-primary fw-semibold" style="font-size: 0.78rem;">Forgot Password?</a>
              </div>
              <div class="auth-input-group">
                <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                <input class="form-control" type="password" id="password" name="password" required placeholder="Enter your password" autocomplete="current-password">
                <button class="btn-password-toggle" type="button" id="togglePassword" aria-label="Toggle password visibility">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <!-- Submit Button (Directly following password) -->
            <button class="btn btn-primary w-100 fw-bold rounded-pill py-2.5 shadow-sm d-flex align-items-center justify-content-center gap-2 mt-3" id="submitBtn" type="submit" style="background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%); border: none; font-size: 0.95rem; box-shadow: 0 10px 24px -4px rgba(79, 70, 229, 0.45); transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);">
              <i class="bi bi-shield-lock-fill fs-5"></i>
              <span id="btnText">Sign In to LMS Admin</span>
            </button>
          </form>

          <!-- Quick Test Credentials Section -->
          <div class="mt-4 pt-3 border-top">
            <?php if (app_show_demo_logins()): ?>
            <div class="test-access-panel">
              <div class="d-flex align-items-center justify-content-between mb-2.5">
                <span class="small fw-bold text-uppercase d-flex align-items-center gap-1.5" style="font-size: 0.68rem; letter-spacing: 0.05em; color: #475569;">
                  <i class="bi bi-lightning-charge-fill text-warning"></i> Fast Demo Access
                </span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">1-Click Auto-Login</span>
              </div>
              
              <div class="p-2.5 bg-white rounded-3 border test-cred-card" 
                   onclick="quickAdminLogin('admin@ttu.edu.ph', 'admin123')"
                   role="button"
                   tabindex="0"
                   title="Click to instantly log in as System LMS Administrator">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <span class="fw-bold text-primary small d-inline-flex align-items-center gap-1" style="font-size: 0.78rem;">
                    <i class="bi bi-shield-lock"></i> System LMS Administrator
                  </span>
                  <span class="badge bg-primary bg-opacity-10 text-primary px-1.5 py-0.5 rounded-pill" style="font-size: 0.62rem;">Auto-Login</span>
                </div>
                <div style="font-size: 0.74rem;"><span class="text-muted">Account:</span> <code class="fw-bold text-dark">admin@ttu.edu.ph</code></div>
                <div style="font-size: 0.74rem;"><span class="text-muted">Pass:</span> <code class="text-secondary">admin123</code></div>
              </div>

              <div class="text-muted mt-2 d-flex align-items-start gap-1.5" style="font-size: 0.72rem; line-height: 1.35;">
                <i class="bi bi-info-circle text-primary mt-0.5 flex-shrink-0"></i>
                <span>Registrar and admissions accounts manage enrollment via the SIS portal. Platform system operations are governed here by the LMS Administrator.</span>
              </div>
            </div>
            <?php endif; ?>

            <!-- Footer Navigation Links -->
            <div class="text-center mt-3 pt-1">
              <p class="mb-2 text-muted small" style="font-size: 0.82rem;">
                Looking for University Admissions or Enrollment? <a href="<?= BASE_PATH ?>/auth/login.php" class="fw-bold text-decoration-none text-primary hover-underline">Admissions &amp; Enrollment Login &rarr;</a>
              </p>
              <div class="d-flex justify-content-center align-items-center gap-3 mt-2 flex-wrap" style="font-size: 0.8rem;">
                <a href="<?= BASE_PATH ?>/auth/lms_student_login.php" class="text-decoration-none text-primary fw-semibold d-inline-flex align-items-center gap-1">
                  <i class="bi bi-mortarboard-fill"></i> Student LMS Portal
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
  const togglePassword = document.getElementById("togglePassword");
  const passwordInput = document.getElementById("password");
  const loginForm = document.getElementById("adminLoginForm");
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

  // Support keyboard accessibility (Enter/Space) on test credential cards
  document.querySelectorAll('.test-cred-card').forEach(function(card) {
    card.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        this.click();
      }
    });
  });
});

function quickAdminLogin(email, password) {
  const idField = document.getElementById('employee_id');
  const passField = document.getElementById('password');
  const form = document.getElementById('adminLoginForm');
  const submitBtn = document.getElementById('submitBtn');
  const btnText = document.getElementById('btnText');

  if (idField && passField && form) {
    idField.value = email;
    passField.value = password;
    if (submitBtn && btnText) {
      submitBtn.disabled = true;
      btnText.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5" role="status" aria-hidden="true"></span> Authenticating...';
    }
    form.submit();
  }
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
