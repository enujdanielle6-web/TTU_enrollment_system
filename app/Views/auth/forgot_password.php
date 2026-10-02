<?php
$portalTitles = [
    'admin' => 'Admin Password Recovery - Triple T University',
    'faculty' => 'Faculty Password Recovery - Triple T University',
    'student' => 'Student Password Recovery - Triple T University',
    'applicant' => 'Password Recovery - Triple T University'
];
$portal = $portal ?? 'applicant';
$pageTitle = $portalTitles[$portal] ?? $portalTitles['applicant'];
require_once __DIR__ . '/../components/header.php';
?>

<main class="auth-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-7 col-xl-6">
        <div class="auth-island fade-in-up" style="animation-delay: 0.08s;">
          
          <div class="text-center mb-4">
            <a href="<?= BASE_PATH ?>/" class="d-inline-block text-decoration-none mb-3">
              <img src="<?= BASE_PATH ?>/images/TTU_LOGO.png" alt="TTU Logo" style="height: 64px; width: auto; object-fit: contain; filter: drop-shadow(0 4px 10px rgba(13, 110, 253, 0.2));">
            </a>
            <h1 class="h4 mb-1 fw-bold text-dark" style="letter-spacing: -0.02em;">
              <?php if ($portal === 'admin'): ?>
                LMS Admin Password Recovery
              <?php elseif ($portal === 'faculty'): ?>
                Faculty Password Recovery
              <?php elseif ($portal === 'student'): ?>
                Student Password Recovery
              <?php else: ?>
                Password Recovery
              <?php endif; ?>
            </h1>
            <p class="text-muted mb-0 small">
              <?php if ($portal === 'admin'): ?>
                Enter your Administrator Email or Employee ID.
              <?php elseif ($portal === 'faculty'): ?>
                Enter your Employee ID or institutional TTU email address.
              <?php elseif ($portal === 'student'): ?>
                Enter your Student ID or institutional TTU email address.
              <?php else: ?>
                Enter your registered applicant email address to receive a verification OTP.
              <?php endif; ?>
            </p>
          </div>

          <?php if (!empty($warning)): ?>
            <div class="alert alert-warning rounded-3 border-0 bg-warning text-dark py-2.5 px-3 small shadow-sm mb-4 d-flex align-items-center gap-2">
              <i class="bi bi-exclamation-triangle-fill fs-6 flex-shrink-0"></i>
              <div><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger rounded-3 border-0 bg-danger text-white py-2.5 px-3 small shadow-sm mb-4">
              <?php foreach ((array)$errors as $error): ?>
                <div class="d-flex align-items-center gap-2 mb-1 last-mb-0">
                  <i class="bi bi-exclamation-circle-fill fs-6 flex-shrink-0"></i>
                  <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form action="<?= BASE_PATH ?>/auth/forgot_password_process.php" method="post" novalidate id="forgotPasswordForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="portal" value="<?= htmlspecialchars($portal, ENT_QUOTES, 'UTF-8'); ?>">

            <?php if ($portal === 'admin'): ?>
              <div class="mb-4">
                <label class="form-label text-dark small fw-semibold mb-1.5" for="identifier">Admin Email or Employee ID</label>
                <div class="auth-input-group">
                  <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                  <input class="form-control" type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($old['identifier'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="e.g. admin@ttu.edu.ph" autofocus>
                </div>
                <div class="form-text small text-muted mt-1.5">
                  The password reset OTP will be dispatched to your registered administrator email.
                </div>
              </div>
            <?php elseif ($portal === 'faculty'): ?>
              <div class="mb-4">
                <label class="form-label text-dark small fw-semibold mb-1.5" for="identifier">Employee ID or TTU Email</label>
                <div class="auth-input-group">
                  <span class="input-group-text"><i class="bi bi-briefcase"></i></span>
                  <input class="form-control" type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($old['identifier'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="e.g. FAC-2026-001 or faculty@ttu.edu.ph" autofocus>
                </div>
                <div class="form-text small text-muted mt-1.5">
                  The 6-digit OTP code will be securely sent to your institutional TTU email address.
                </div>
              </div>
            <?php elseif ($portal === 'student'): ?>
              <div class="mb-4">
                <label class="form-label text-dark small fw-semibold mb-1.5" for="identifier">Student ID or TTU Email</label>
                <div class="auth-input-group">
                  <span class="input-group-text"><i class="bi bi-mortarboard"></i></span>
                  <input class="form-control" type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($old['identifier'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="e.g. 2026-000001 or student@ttu.edu.ph" autofocus>
                </div>
                <div class="form-text small text-muted mt-1.5">
                  The 6-digit OTP code will be securely sent to your institutional TTU email address.
                </div>
              </div>
            <?php else: ?>
              <div class="mb-4">
                <label class="form-label text-dark small fw-semibold mb-1.5" for="email">Applicant Email Address</label>
                <div class="auth-input-group">
                  <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                  <input class="form-control" type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="name@example.com" autofocus>
                </div>
                <div class="form-text small text-muted mt-1.5">
                  We will send a 6-digit password reset OTP code to this address.
                </div>
              </div>
            <?php endif; ?>

            <button class="btn btn-primary w-100 fw-bold rounded-pill py-2.5 shadow-sm d-flex align-items-center justify-content-center gap-2" id="submitBtn" type="submit" style="background: linear-gradient(135deg, #0d6efd 0%, #1d4ed8 100%); border: none;">
              <i class="bi bi-send-fill"></i>
              <span id="btnText">Send 6-Digit Code</span>
            </button>
          </form>

          <div class="mt-4 text-center border-top pt-3">
            <?php if ($portal === 'admin'): ?>
              <a href="<?= BASE_PATH ?>/auth/lms_admin_login.php" class="text-muted small text-decoration-none btn-link d-inline-flex align-items-center gap-1.5 transition-all">
                <i class="bi bi-arrow-left"></i> Back to LMS Admin Login
              </a>
            <?php elseif ($portal === 'faculty'): ?>
              <a href="<?= BASE_PATH ?>/auth/lms_faculty_login.php" class="text-muted small text-decoration-none btn-link d-inline-flex align-items-center gap-1.5 transition-all">
                <i class="bi bi-arrow-left"></i> Back to Faculty Login
              </a>
            <?php elseif ($portal === 'student'): ?>
              <a href="<?= BASE_PATH ?>/auth/lms_student_login.php" class="text-muted small text-decoration-none btn-link d-inline-flex align-items-center gap-1.5 transition-all">
                <i class="bi bi-arrow-left"></i> Back to Student Login
              </a>
            <?php else: ?>
              <a href="<?= BASE_PATH ?>/auth/login.php" class="text-muted small text-decoration-none btn-link d-inline-flex align-items-center gap-1.5 transition-all">
                <i class="bi bi-arrow-left"></i> Back to Login
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const form = document.getElementById("forgotPasswordForm");
  const submitBtn = document.getElementById("submitBtn");
  const btnText = document.getElementById("btnText");

  if (form && submitBtn) {
    form.addEventListener("submit", function() {
      submitBtn.disabled = true;
      if (btnText) {
        btnText.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5" role="status" aria-hidden="true"></span> Sending Code...';
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
