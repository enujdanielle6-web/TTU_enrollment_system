<?php

declare(strict_types=1);

session_start();

$pageTitle = 'Register - Triple T University';
$errors = $_SESSION['register_errors'] ?? [];
$old = $_SESSION['register_old'] ?? [];

unset($_SESSION['register_errors'], $_SESSION['register_old']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../components/header.php';
?>

<main class="auth-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="auth-island" style="max-width: 520px;">
          <div class="text-center mb-4">
            <div class="school-logo mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; border-radius: 16px; background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%); box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);">
              <i class="bi bi-person-plus"></i>
            </div>
            <h1 class="h4 mb-2 fw-bold text-dark">Create Account</h1>
            <p class="text-muted mb-0 small">Start your online enrollment application.</p>
          </div>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger rounded-3 border-0 bg-danger text-white py-2 px-3 small shadow-sm mb-4">
              <ul class="mb-0 ps-3">
                <?php foreach ($errors as $error): ?>
                  <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form id="registerForm" action="register_process.php" method="post" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label text-muted small fw-semibold" for="firstName">First Name</label>
                <input class="form-control" style="padding: 0.75rem 1rem; border-radius: 10px;" type="text" id="firstName" name="first_name" value="<?= htmlspecialchars($old['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <div class="invalid-feedback">First name is required.</div>
              </div>

              <div class="col-md-6">
                <label class="form-label text-muted small fw-semibold" for="lastName">Last Name</label>
                <input class="form-control" style="padding: 0.75rem 1rem; border-radius: 10px;" type="text" id="lastName" name="last_name" value="<?= htmlspecialchars($old['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <div class="invalid-feedback">Last name is required.</div>
              </div>

              <div class="col-12">
                <label class="form-label text-muted small fw-semibold" for="email">Email Address</label>
                <input class="form-control" style="padding: 0.75rem 1rem; border-radius: 10px;" type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required placeholder="name@example.com">
                <div class="invalid-feedback">Enter a valid email address.</div>
              </div>

              <div class="col-12">
                <label class="form-label text-muted small fw-semibold" for="password">Password</label>
                <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #dee2e6;">
                  <input class="form-control border-0" style="padding: 0.75rem 1rem;" type="password" id="password" name="password" minlength="8" autocomplete="new-password" required placeholder="••••••••">
                  <button class="btn btn-light border-0 px-3 text-muted toggle-password" type="button" data-target="password" tabindex="-1">
                    <i class="bi bi-eye"></i>
                  </button>
                  <div class="invalid-feedback">Password must be at least 8 characters.</div>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label text-muted small fw-semibold" for="confirmPassword">Confirm Password</label>
                <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #dee2e6;">
                  <input class="form-control border-0" style="padding: 0.75rem 1rem;" type="password" id="confirmPassword" name="confirm_password" minlength="8" autocomplete="new-password" required placeholder="••••••••">
                  <button class="btn btn-light border-0 px-3 text-muted toggle-password" type="button" data-target="confirmPassword" tabindex="-1">
                    <i class="bi bi-eye"></i>
                  </button>
                  <div class="invalid-feedback">Passwords must match.</div>
                </div>
              </div>

              <div class="col-12 mt-4">
                <button class="btn btn-primary w-100 fw-semibold" style="padding: 0.75rem 1rem; border-radius: 10px;" type="submit">
                  <i class="bi bi-check-circle"></i> Create Account
                </button>
              </div>
            </div>
          </form>

          <div class="mt-4 text-center border-top pt-4">
            <p class="mb-2 text-muted small">Already have an account? <a href="login.php" class="fw-bold text-decoration-none text-primary">Login here</a></p>
            <a href="../public/index.php" class="text-muted small text-decoration-none btn-link d-inline-flex align-items-center gap-1 mt-2 transition-all">
              <i class="bi bi-arrow-left"></i> Back to Homepage
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
  $(function () {
    $('#registerForm').on('submit', function (event) {
      var form = this;
      var password = $('#password').val();
      var confirmPassword = $('#confirmPassword').val();

      $('#confirmPassword')[0].setCustomValidity(
        password === confirmPassword ? '' : 'Passwords do not match.'
      );

      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }

      $(form).addClass('was-validated');
    });

    // Password visibility toggle
    $('.toggle-password').on('click', function() {
      const targetId = $(this).data('target');
      const input = $('#' + targetId);
      const icon = $(this).find('i');
      
      if (input.attr('type') === 'password') {
        input.attr('type', 'text');
        icon.removeClass('bi-eye').addClass('bi-eye-slash');
      } else {
        input.attr('type', 'password');
        icon.removeClass('bi-eye-slash').addClass('bi-eye');
      }
    });
  });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
