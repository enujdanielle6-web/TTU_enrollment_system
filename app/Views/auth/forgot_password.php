<?php
$portalTitles = [
    'faculty' => 'Faculty Password Recovery - Triple T University',
    'student' => 'Student Password Recovery - Triple T University',
    'applicant' => 'Password Recovery - Triple T University'
];
$pageTitle = $portalTitles[$portal] ?? $portalTitles['applicant'];
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
            <h1 class="h4 mb-2 fw-bold text-dark">
              <?php if ($portal === 'faculty'): ?>
                Faculty Password Recovery
              <?php elseif ($portal === 'student'): ?>
                Student Password Recovery
              <?php else: ?>
                Applicant Password Recovery
              <?php endif; ?>
            </h1>
            <p class="text-muted mb-0 small">
              <?php if ($portal === 'faculty'): ?>
                Enter your Employee ID or institutional TTU email address.
              <?php elseif ($portal === 'student'): ?>
                Enter your Student ID or institutional TTU email address.
              <?php else: ?>
                Enter your registered email address to receive a 6-digit verification code.
              <?php endif; ?>
            </p>
          </div>

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

          <form action="/sia/auth/forgot_password_process.php" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="portal" value="<?= htmlspecialchars($portal, ENT_QUOTES, 'UTF-8'); ?>">

            <?php if ($portal === 'faculty'): ?>
              <div class="mb-4">
                <label class="form-label text-muted small fw-semibold" for="identifier">Employee ID or TTU Email</label>
                <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #dee2e6;">
                  <span class="input-group-text bg-light border-0 text-muted px-3">
                    <i class="bi bi-person-badge"></i>
                  </span>
                  <input class="form-control border-0 shadow-none" style="padding: 0.75rem 1rem;" type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($old['identifier'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="e.g. EMP-001 or faculty@ttu.edu.ph" autofocus>
                </div>
                <div class="form-text small text-muted mt-1">
                  The 6-digit OTP code will be securely sent to your institutional TTU email address.
                </div>
              </div>
            <?php elseif ($portal === 'student'): ?>
              <div class="mb-4">
                <label class="form-label text-muted small fw-semibold" for="identifier">Student ID or TTU Email</label>
                <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #dee2e6;">
                  <span class="input-group-text bg-light border-0 text-muted px-3">
                    <i class="bi bi-mortarboard"></i>
                  </span>
                  <input class="form-control border-0 shadow-none" style="padding: 0.75rem 1rem;" type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($old['identifier'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="e.g. 2026-000002 or first.last@ttu.edu.ph" autofocus>
                </div>
                <div class="form-text small text-muted mt-1">
                  The 6-digit OTP code will be securely sent to your institutional TTU email address.
                </div>
              </div>
            <?php else: ?>
              <div class="mb-4">
                <label class="form-label text-muted small fw-semibold" for="email">Applicant Email Address</label>
                <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #dee2e6;">
                  <span class="input-group-text bg-light border-0 text-muted px-3">
                    <i class="bi bi-envelope"></i>
                  </span>
                  <input class="form-control border-0 shadow-none" style="padding: 0.75rem 1rem;" type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="name@example.com" autofocus>
                </div>
                <div class="form-text small text-muted mt-1">
                  We will send a 6-digit password reset OTP to this address.
                </div>
              </div>
            <?php endif; ?>

            <button class="btn btn-primary w-100 fw-semibold shadow-sm" style="padding: 0.75rem 1rem; border-radius: 10px;" type="submit">
              <i class="bi bi-send-fill me-1"></i> Send 6-Digit Code
            </button>
          </form>

          <div class="mt-4 text-center border-top pt-4">
            <?php if ($portal === 'faculty'): ?>
              <a href="/sia/auth/lms_faculty_login.php" class="text-muted small text-decoration-none btn-link d-inline-flex align-items-center gap-1 transition-all">
                <i class="bi bi-arrow-left"></i> Back to Faculty Login
              </a>
            <?php elseif ($portal === 'student'): ?>
              <a href="/sia/auth/lms_student_login.php" class="text-muted small text-decoration-none btn-link d-inline-flex align-items-center gap-1 transition-all">
                <i class="bi bi-arrow-left"></i> Back to Student Login
              </a>
            <?php else: ?>
              <a href="/sia/auth/login.php" class="text-muted small text-decoration-none btn-link d-inline-flex align-items-center gap-1 transition-all">
                <i class="bi bi-arrow-left"></i> Back to Applicant Login
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
