<?php
$pageTitle = 'Login - Triple T University';
require_once __DIR__ . '/../components/header.php';
?>

<main class="auth-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="auth-island fade-in-up" style="animation-delay: 0.1s;">
          <div class="text-center mb-4">
            <div class="mx-auto mb-3">
              <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 64px; width: auto; object-fit: contain;">
            </div>
            <h1 class="h4 mb-2 fw-bold text-dark">TRIPLE T UNIVERSITY</h1>
            <p class="text-muted mb-0 small">Login to continue your enrollment account.</p>
          </div>

          <?php if (!empty($success)): ?>
            <div class="alert alert-success rounded-3 border-0 bg-success text-white py-2 px-3 small shadow-sm mb-4">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i>
                <span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger rounded-3 border-0 bg-danger text-white py-2 px-3 small shadow-sm mb-4">
              <?php foreach ($errors as $error): ?>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-exclamation-circle-fill"></i>
                  <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form action="/sia/auth/login_process.php" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <div class="mb-3">
              <label class="form-label text-muted small fw-semibold" for="email">Email Address</label>
              <input class="form-control" style="padding: 0.75rem 1rem; border-radius: 10px;" type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="name@example.com">
            </div>

            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label text-muted small fw-semibold mb-0" for="password">Password</label>
                <a href="/sia/auth/forgot_password.php?portal=applicant" class="small text-decoration-none text-primary fw-semibold">Forgot Password?</a>
              </div>
              <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #dee2e6;">
                <input class="form-control border-0" style="padding: 0.75rem 1rem;" type="password" id="password" name="password" required placeholder="••••••••">
                <button class="btn btn-light border-0 px-3 text-muted" type="button" id="togglePassword" tabindex="-1">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <button class="btn btn-primary w-100 fw-semibold" style="padding: 0.75rem 1rem; border-radius: 10px;" type="submit">
              <i class="bi bi-unlock"></i> Sign In
            </button>
          </form>

          <div class="mt-4 text-center border-top pt-4">
            
            <!-- TEMPORARY TESTING SHORTCUTS -->
            <div class="mb-4 bg-light rounded-3 p-3 border border-warning border-opacity-50">
                <p class="small text-muted fw-bold mb-2"><i class="bi bi-bug"></i> Test Accounts (Auto-fill)</p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="fillLogin('admin@ttu.edu.ph', 'password123')">Superadmin</button>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="fillLogin('registrar@ttu.edu.ph', 'password123')">Registrar</button>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="fillLogin('cashier@ttu.edu.ph', 'password123')">Cashier</button>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="fillLogin('admissions@ttu.edu.ph', 'password123')">Admissions</button>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="fillLogin('scholarship@ttu.edu.ph', 'password123')">Scholarship</button>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="fillLogin('clinic@example.com', 'password123')">Clinic</button>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="fillLogin('scheduler@ttu.edu.ph', 'password123')">Scheduler</button>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="fillLogin('jane.doe@example.com', 'password123')">Applicant</button>
                </div>
            </div>
            
            <p class="mb-2 text-muted small">Don't have an account? <a href="/sia/auth/register.php" class="fw-bold text-decoration-none text-primary">Register here</a></p>
            <a href="/sia/public/index.php" class="text-muted small text-decoration-none btn-link d-inline-flex align-items-center gap-1 mt-2 transition-all">
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
    // Clear any cached enrollment form data from previous sessions
    sessionStorage.removeItem('enrollmentFormData');

    const togglePassword = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("password");

    if (togglePassword && passwordInput) {
      togglePassword.addEventListener("click", function() {
        const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
        passwordInput.setAttribute("type", type);
        this.innerHTML = type === "password" ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
      });
    }
  });
</script>
<script>
  function fillLogin(email, password) {
      document.getElementById('email').value = email;
      document.getElementById('password').value = password;
      document.querySelector('form').submit();
  }
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

