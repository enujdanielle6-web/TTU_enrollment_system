<?php

declare(strict_types=1);

session_start();

$pageTitle = 'Login - Triple T University';
$errors = $_SESSION['login_errors'] ?? [];
$old = $_SESSION['login_old'] ?? [];

unset($_SESSION['login_errors'], $_SESSION['login_old']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../components/header.php';
?>

<main class="auth-page py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="auth-island">
          <div class="text-center mb-4">
            <div class="school-logo mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; border-radius: 16px; background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%); box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);">
              <i class="bi bi-box-arrow-in-right"></i>
            </div>
            <h1 class="h4 mb-2 fw-bold text-dark">Welcome Back</h1>
            <p class="text-muted mb-0 small">Login to continue your enrollment account.</p>
          </div>

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

          <form action="login_process.php" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="mb-3">
              <label class="form-label text-muted small fw-semibold" for="email">Email Address</label>
              <input class="form-control" style="padding: 0.75rem 1rem; border-radius: 10px;" type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="name@example.com">
            </div>

            <div class="mb-4">
              <label class="form-label text-muted small fw-semibold" for="password">Password</label>
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
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="fillLogin('applicant@example.com', 'password123')">Applicant</button>
                </div>
            </div>
            
            <p class="mb-2 text-muted small">Don't have an account? <a href="register.php" class="fw-bold text-decoration-none text-primary">Register here</a></p>
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
