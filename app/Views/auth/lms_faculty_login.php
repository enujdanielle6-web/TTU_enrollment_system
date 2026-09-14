<?php require_once __DIR__ . '/../components/header.php'; ?>

<main class="auth-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="auth-island fade-in-up" style="animation-delay: 0.1s;">
          <div class="text-center mb-4">
            <div class="mx-auto mb-3">
              <img src="../images/TTU_LOGO.png" alt="TTU Logo" style="height: 64px; width: auto; object-fit: contain;">
            </div>
            <h1 class="h4 mb-2 fw-bold text-dark">Faculty LMS Portal</h1>
            <p class="text-muted mb-0 small">Login to manage your course modules.</p>
          </div>

          <?php if (!empty($success)): ?>
            <div class="alert alert-success rounded-3 border-0 bg-success text-white py-2 px-3 small shadow-sm mb-4">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i>
                <span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($warning)): ?>
            <div class="alert alert-warning rounded-3 border-0 bg-warning text-dark py-2 px-3 small shadow-sm mb-4">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger rounded-3 border-0 bg-danger text-white py-2 px-3 small shadow-sm mb-4">
              <?php foreach ((array)$errors as $error): ?>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-exclamation-circle-fill"></i>
                  <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form action="/sia/auth/lms_login_process.php" method="post" novalidate>
            <?= getCsrfInput() ?>
            <input type="hidden" name="role" value="faculty">
            <div class="mb-3">
              <label class="form-label text-muted small fw-semibold" for="employee_id">Employee ID or Email</label>
              <input class="form-control" style="padding: 0.75rem 1rem; border-radius: 10px;" type="text" id="employee_id" name="employee_id" value="<?= htmlspecialchars($old['employee_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="e.g. FAC-2026-001 or admin@ttu.edu.ph">
            </div>

            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label text-muted small fw-semibold mb-0" for="password">Password</label>
                <a href="/sia/auth/forgot_password.php?portal=faculty" class="small text-decoration-none text-primary fw-semibold">Forgot Password?</a>
              </div>
              <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #dee2e6;">
                <input class="form-control border-0 shadow-none" style="padding: 0.75rem 1rem;" type="password" id="password" name="password" required placeholder="••••••••">
                <button class="btn btn-light border-0 px-3 text-muted" type="button" id="togglePassword" tabindex="-1" title="Toggle password visibility">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <!-- Quick Auto-Fill Buttons -->
            <div class="mb-3 d-flex flex-wrap gap-2 justify-content-between">
              <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 flex-fill shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5" onclick="quickFacultyLogin('FAC-2026-001', 'password123')" title="Click to instantly log in as Faculty Instructor">
                <i class="bi bi-person-workspace"></i> <span>Auto-fill Faculty</span>
              </button>
              <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 flex-fill shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5" onclick="quickFacultyLogin('admin@ttu.edu.ph', 'admin123')" title="Click to instantly log in as Superadmin">
                <i class="bi bi-shield-lock"></i> <span>Auto-fill Test Admin</span>
              </button>
            </div>

            <!-- Test Credentials Card -->
            <div class="card bg-light border-0 rounded-3 p-3 mb-3 text-start">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 0.05em;"><i class="bi bi-key-fill me-1"></i> Test Login Credentials <span class="text-primary fw-normal text-none ms-1">(Click card to log in)</span></span>
              </div>
              <div class="row g-2" style="font-size: 0.8rem;">
                <div class="col-12 col-sm-6">
                  <div class="p-2.5 bg-white rounded-3 border border-light-subtle shadow-xs test-cred-card h-100" 
                       onclick="quickFacultyLogin('FAC-2026-001', 'password123')"
                       role="button"
                       tabindex="0"
                       title="Click to instantly log in as Faculty Instructor">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <span class="fw-semibold text-primary"><i class="bi bi-mortarboard me-1"></i> Faculty</span>
                      <span class="badge bg-primary bg-opacity-10 text-primary px-1.5 py-0.5 rounded-pill" style="font-size: 0.65rem;"><i class="bi bi-box-arrow-in-right me-0.5"></i> Log In</span>
                    </div>
                    <div><span class="text-muted small">ID:</span> <code class="fw-bold text-dark user-select-all">FAC-2026-001</code></div>
                    <div><span class="text-muted small">Pass:</span> <code class="text-secondary user-select-all">password123</code></div>
                  </div>
                </div>
                <div class="col-12 col-sm-6">
                  <div class="p-2.5 bg-white rounded-3 border border-light-subtle shadow-xs test-cred-card h-100" 
                       onclick="quickFacultyLogin('admin@ttu.edu.ph', 'admin123')"
                       role="button"
                       tabindex="0"
                       title="Click to instantly log in as Superadmin">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <span class="fw-semibold text-dark"><i class="bi bi-shield-check me-1"></i> Superadmin</span>
                      <span class="badge bg-dark bg-opacity-10 text-dark px-1.5 py-0.5 rounded-pill" style="font-size: 0.65rem;"><i class="bi bi-box-arrow-in-right me-0.5"></i> Log In</span>
                    </div>
                    <div><span class="text-muted small">Email:</span> <code class="fw-bold text-dark user-select-all">admin@ttu.edu.ph</code></div>
                    <div><span class="text-muted small">Pass:</span> <code class="text-secondary user-select-all">admin123</code></div>
                  </div>
                </div>
              </div>
            </div>

            <button class="btn btn-outline-primary w-100 fw-semibold" style="padding: 0.75rem 1rem; border-radius: 10px;" type="submit">
              <i class="bi bi-unlock"></i> Login to LMS
            </button>
          </form>

          <div class="mt-4 text-center border-top pt-4">
            <a href="../public/index.php" class="text-muted small text-decoration-none btn-link d-inline-flex align-items-center gap-1 mt-2 transition-all">
              <i class="bi bi-arrow-left"></i> Back to Homepage
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<style>
  .test-cred-card {
    cursor: pointer;
    transition: all 0.18s ease-in-out;
    user-select: none;
  }
  .test-cred-card:hover {
    border-color: #0d6efd !important;
    background-color: #f8faff !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.12) !important;
  }
  .test-cred-card:active {
    transform: translateY(0);
    box-shadow: none !important;
  }
</style>

<script>
  function quickFacultyLogin(idOrEmail, password) {
    const idField = document.getElementById('employee_id');
    const passField = document.getElementById('password');
    if (idField && passField) {
      idField.value = idOrEmail;
      passField.value = password;
      
      // Animate card / button briefly before submit
      const form = idField.closest('form');
      if (form) {
        form.submit();
      }
    }
  }

  document.addEventListener("DOMContentLoaded", function() {
    const togglePassword = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("password");

    if (togglePassword && passwordInput) {
      togglePassword.addEventListener("click", function() {
        const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
        passwordInput.setAttribute("type", type);
        
        const icon = this.querySelector("i");
        if (icon) {
          icon.classList.toggle("bi-eye");
          icon.classList.toggle("bi-eye-slash");
        }
      });
    }

    // Support keyboard trigger (Enter/Space) on test credential cards
    document.querySelectorAll('.test-cred-card').forEach(function(card) {
      card.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.click();
        }
      });
    });
  });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

