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
              <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 flex-fill" onclick="document.getElementById('employee_id').value='FAC-2026-001'; document.getElementById('password').value='password123';">
                <i class="bi bi-person-workspace me-1"></i> Auto-fill Faculty
              </button>
              <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 flex-fill" onclick="document.getElementById('employee_id').value='admin@ttu.edu.ph'; document.getElementById('password').value='admin123';">
                <i class="bi bi-shield-lock me-1"></i> Auto-fill Test Admin
              </button>
            </div>

            <!-- Test Credentials Card -->
            <div class="card bg-light border-0 rounded-3 p-3 mb-3 text-start">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 0.05em;"><i class="bi bi-key-fill me-1"></i> Test Login Credentials</span>
              </div>
              <div class="row g-2" style="font-size: 0.8rem;">
                <div class="col-12 col-sm-6">
                  <div class="p-2 bg-white rounded border border-light-subtle shadow-xs">
                    <div class="fw-semibold text-primary mb-1"><i class="bi bi-mortarboard me-1"></i> Faculty Instructor</div>
                    <div><span class="text-muted">ID:</span> <code class="user-select-all">FAC-2026-001</code></div>
                    <div><span class="text-muted">Pass:</span> <code>password123</code></div>
                  </div>
                </div>
                <div class="col-12 col-sm-6">
                  <div class="p-2 bg-white rounded border border-light-subtle shadow-xs">
                    <div class="fw-semibold text-dark mb-1"><i class="bi bi-shield-check me-1"></i> Superadmin</div>
                    <div><span class="text-muted">Email:</span> <code class="user-select-all">admin@ttu.edu.ph</code></div>
                    <div><span class="text-muted">Pass:</span> <code>admin123</code></div>
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

<script>
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
  });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

