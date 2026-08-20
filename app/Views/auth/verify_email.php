<?php
$pageTitle = 'Verify Email - Triple T University';
require_once __DIR__ . '/../components/header.php';
?>

<main class="auth-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="auth-island fade-in-up" style="max-width: 480px; animation-delay: 0.1s;">
          <div class="text-center mb-4">
            <div class="mx-auto mb-3">
              <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 64px; width: auto; object-fit: contain;">
            </div>
            <div class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-2 fw-semibold">
              <i class="bi bi-envelope-check me-1"></i> Email Verification
            </div>
            <h1 class="h4 mb-2 fw-bold text-dark">Check Your Inbox</h1>
            <p class="text-muted mb-0 small">
              We've sent a 6-digit verification code to:<br>
              <strong class="text-dark fs-6"><?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
            </p>
          </div>

          <?php if (!empty($success)): ?>
            <div class="alert alert-success rounded-3 border-0 bg-success text-white py-2 px-3 small shadow-sm mb-4 d-flex align-items-center">
              <i class="bi bi-check-circle-fill me-2 fs-5"></i>
              <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          <?php endif; ?>

          <?php if (!empty($warning)): ?>
            <div class="alert alert-warning rounded-3 border-0 bg-warning text-dark py-2 px-3 small shadow-sm mb-4 d-flex align-items-center">
              <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-warning-emphasis"></i>
              <div><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger rounded-3 border-0 bg-danger text-white py-2 px-3 small shadow-sm mb-4 d-flex align-items-center">
              <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
              <div>
                <ul class="mb-0 ps-2 list-unstyled">
                  <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          <?php endif; ?>

          <form id="verifyForm" class="no-spinner" action="/sia/auth/verify_email_process.php" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="fullCodeInput" name="code" value="">

            <div class="mb-4">
              <label class="form-label text-muted small fw-semibold text-center d-block mb-3">Enter 6-Digit Code</label>
              
              <div class="d-flex justify-content-between gap-2" id="otpInputs">
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="form-control text-center fs-3 fw-bold otp-digit" style="height: 58px; border-radius: 12px; border: 2px solid #dee2e6;" autofocus required>
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="form-control text-center fs-3 fw-bold otp-digit" style="height: 58px; border-radius: 12px; border: 2px solid #dee2e6;" required>
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="form-control text-center fs-3 fw-bold otp-digit" style="height: 58px; border-radius: 12px; border: 2px solid #dee2e6;" required>
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="form-control text-center fs-3 fw-bold otp-digit" style="height: 58px; border-radius: 12px; border: 2px solid #dee2e6;" required>
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="form-control text-center fs-3 fw-bold otp-digit" style="height: 58px; border-radius: 12px; border: 2px solid #dee2e6;" required>
                <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" class="form-control text-center fs-3 fw-bold otp-digit" style="height: 58px; border-radius: 12px; border: 2px solid #dee2e6;" required>
              </div>
              <div class="text-center text-muted small mt-2" style="font-size: 0.78rem;">
                <i class="bi bi-clock-history me-1"></i> Code expires in 15 minutes
              </div>
            </div>

            <div class="d-grid mb-3">
              <button class="btn btn-primary btn-lg shadow-sm fw-semibold" type="submit" id="verifyBtn" style="border-radius: 10px; padding: 0.8rem;">
                <i class="bi bi-shield-check me-2"></i> Verify & Proceed
              </button>
            </div>
          </form>

          <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4">
            <div>
              <form action="/sia/auth/resend_verification.php" method="post" id="resendForm" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-link p-0 text-decoration-none small text-primary fw-semibold" id="resendBtn">
                  <i class="bi bi-arrow-repeat me-1"></i> Resend Code
                </button>
              </form>
              <span id="countdownTimer" class="small text-muted d-none ms-1"></span>
            </div>
            
            <a href="/sia/auth/register.php" class="small text-muted text-decoration-none">
              <i class="bi bi-arrow-left me-1"></i> Change Email
            </a>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const digits = Array.from(document.querySelectorAll('.otp-digit'));
  const fullCodeInput = document.getElementById('fullCodeInput');
  const verifyForm = document.getElementById('verifyForm');
  const verifyBtn = document.getElementById('verifyBtn');

  function updateFullCode() {
    const code = digits.map(d => d.value).join('');
    fullCodeInput.value = code;
    return code;
  }

  digits.forEach((input, index) => {
    // Focus effect
    input.addEventListener('focus', function () {
      this.select();
      this.style.borderColor = '#0d6efd';
      this.style.boxShadow = '0 0 0 0.25rem rgba(13, 110, 253, 0.15)';
    });

    input.addEventListener('blur', function () {
      this.style.borderColor = '#dee2e6';
      this.style.boxShadow = 'none';
    });

    // Handle Input
    input.addEventListener('input', function (e) {
      const val = this.value.replace(/[^0-9]/g, '');
      this.value = val ? val[val.length - 1] : '';

      if (this.value && index < digits.length - 1) {
        digits[index + 1].focus();
      }
      
      const currentCode = updateFullCode();
      if (currentCode.length === 6) {
        // Auto submit if all 6 digits are typed
        verifyBtn.focus();
      }
    });

    // Handle Backspace & Navigation
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace') {
        if (!this.value && index > 0) {
          digits[index - 1].focus();
          digits[index - 1].value = '';
        } else {
          this.value = '';
        }
        updateFullCode();
      } else if (e.key === 'ArrowLeft' && index > 0) {
        digits[index - 1].focus();
      } else if (e.key === 'ArrowRight' && index < digits.length - 1) {
        digits[index + 1].focus();
      }
    });

    // Handle Paste (e.g. user pastes 6-digit code)
    input.addEventListener('paste', function (e) {
      e.preventDefault();
      const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
      const numbersOnly = pasteData.replace(/[^0-9]/g, '').slice(0, 6);

      if (numbersOnly) {
        numbersOnly.split('').forEach((char, i) => {
          if (digits[i]) {
            digits[i].value = char;
          }
        });
        const lastFilled = Math.min(numbersOnly.length, digits.length - 1);
        digits[lastFilled].focus();
        updateFullCode();
      }
    });
  });

  verifyForm.addEventListener('submit', function (e) {
    const code = updateFullCode();
    if (code.length !== 6) {
      e.preventDefault();
      alert('Please enter all 6 digits of the verification code.');
      digits.find(d => !d.value)?.focus();
    }
  });

  // Resend cooldown timer
  const resendBtn = document.getElementById('resendBtn');
  const countdownTimer = document.getElementById('countdownTimer');
  let cooldown = 60;
  
  function startCooldown() {
    resendBtn.classList.add('disabled', 'text-muted');
    resendBtn.style.pointerEvents = 'none';
    countdownTimer.classList.remove('d-none');
    countdownTimer.textContent = `(${cooldown}s)`;

    const timer = setInterval(() => {
      cooldown--;
      countdownTimer.textContent = `(${cooldown}s)`;
      if (cooldown <= 0) {
        clearInterval(timer);
        resendBtn.classList.remove('disabled', 'text-muted');
        resendBtn.style.pointerEvents = 'auto';
        countdownTimer.classList.add('d-none');
        cooldown = 60;
      }
    }, 1000);
  }

  // Start cooldown on page load
  startCooldown();
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
