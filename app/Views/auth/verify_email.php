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
              <img src="<?= BASE_PATH ?>/images/TTU_LOGO.png" alt="TTU Logo" style="height: 64px; width: auto; object-fit: contain;">
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

          <div id="timeoutAlertContainer"></div>

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

          <form id="verifyForm" class="no-spinner" action="<?= BASE_PATH ?>/auth/verify_email_process.php" method="post" novalidate>
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
              <div class="text-center text-muted small mt-2" id="expiryNotice" style="font-size: 0.78rem;">
                <i class="bi bi-clock-history me-1"></i> Code expires in <span id="codeExpiryTimer" class="fw-bold text-primary">15:00</span>
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
              <form action="<?= BASE_PATH ?>/auth/resend_verification.php" method="post" id="resendForm" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-link p-0 text-decoration-none small text-primary fw-semibold" id="resendBtn">
                  <i class="bi bi-arrow-repeat me-1"></i> Resend Code
                </button>
              </form>
              <span id="countdownTimer" class="small text-muted d-none ms-1"></span>
            </div>
            
            <a href="<?= BASE_PATH ?>/auth/register.php" class="small text-muted text-decoration-none">
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
  const resendBtn = document.getElementById('resendBtn');
  const countdownTimer = document.getElementById('countdownTimer');
  const codeExpiryTimer = document.getElementById('codeExpiryTimer');
  const expiryNotice = document.getElementById('expiryNotice');
  const timeoutAlertContainer = document.getElementById('timeoutAlertContainer');

  let remainingSeconds = <?= (int)($remainingSeconds ?? 900) ?>;
  let isExpired = remainingSeconds <= 0;
  let resendInterval = null;
  let cooldown = 60;

  function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
  }

  function stopCooldown() {
    if (resendInterval) {
      clearInterval(resendInterval);
      resendInterval = null;
    }
    if (resendBtn) {
      resendBtn.classList.remove('disabled', 'text-muted');
      resendBtn.style.pointerEvents = 'auto';
    }
    if (countdownTimer) {
      countdownTimer.classList.add('d-none');
    }
  }

  function startCooldown() {
    if (isExpired) {
      stopCooldown();
      return;
    }
    if (!resendBtn || !countdownTimer) return;
    resendBtn.classList.add('disabled', 'text-muted');
    resendBtn.style.pointerEvents = 'none';
    countdownTimer.classList.remove('d-none');
    countdownTimer.textContent = `(${cooldown}s)`;

    resendInterval = setInterval(() => {
      cooldown--;
      countdownTimer.textContent = `(${cooldown}s)`;
      if (cooldown <= 0) {
        stopCooldown();
        cooldown = 60;
      }
    }, 1000);
  }

  function handleTimeout() {
    isExpired = true;
    if (codeExpiryTimer) {
      codeExpiryTimer.textContent = '00:00';
    }
    if (expiryNotice) {
      expiryNotice.innerHTML = '<span class="text-danger fw-semibold"><i class="bi bi-clock-history me-1"></i> Code expired (15 minutes elapsed)</span>';
    }
    if (timeoutAlertContainer && !document.getElementById('timeoutAlertBanner')) {
      timeoutAlertContainer.innerHTML = `
        <div class="alert alert-danger rounded-3 border-0 bg-danger text-white py-2 px-3 small shadow-sm mb-4 d-flex align-items-center" id="timeoutAlertBanner">
          <i class="bi bi-hourglass-bottom me-2 fs-5 flex-shrink-0"></i>
          <div>
            <strong>Verification code timed out.</strong> Your code expired because it was not entered within 15 minutes. Please click &ldquo;Resend Code&rdquo; to receive a new code.
          </div>
        </div>
      `;
    }
    digits.forEach(d => {
      d.disabled = true;
      d.style.backgroundColor = '#e9ecef';
      d.style.cursor = 'not-allowed';
    });
    if (verifyBtn) {
      verifyBtn.disabled = true;
      verifyBtn.classList.add('opacity-50');
      verifyBtn.style.cursor = 'not-allowed';
    }
    stopCooldown();
  }

  // Expiry timer initialization
  if (isExpired) {
    handleTimeout();
  } else {
    if (codeExpiryTimer) {
      codeExpiryTimer.textContent = formatTime(remainingSeconds);
    }
    startCooldown();

    const expiryInterval = setInterval(() => {
      remainingSeconds--;
      if (remainingSeconds <= 0) {
        clearInterval(expiryInterval);
        handleTimeout();
      } else {
        if (codeExpiryTimer) {
          codeExpiryTimer.textContent = formatTime(remainingSeconds);
          if (remainingSeconds <= 30) {
            codeExpiryTimer.classList.remove('text-primary');
            codeExpiryTimer.classList.add('text-danger');
          }
        }
      }
    }, 1000);
  }

  function updateFullCode() {
    const code = digits.map(d => d.value).join('');
    fullCodeInput.value = code;
    return code;
  }

  digits.forEach((input, index) => {
    // Focus effect
    input.addEventListener('focus', function () {
      if (isExpired) return;
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
      if (isExpired) return;
      const val = this.value.replace(/[^0-9]/g, '');
      this.value = val ? val[val.length - 1] : '';

      if (this.value && index < digits.length - 1) {
        digits[index + 1].focus();
      }
      
      const currentCode = updateFullCode();
      if (currentCode.length === 6) {
        verifyBtn.focus();
      }
    });

    // Handle Backspace & Navigation
    input.addEventListener('keydown', function (e) {
      if (isExpired) return;
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

    // Handle Paste
    input.addEventListener('paste', function (e) {
      if (isExpired) return;
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
    if (isExpired) {
      e.preventDefault();
      alert('Verification code timed out. Your code expired because it was not entered within 15 minutes. Please click "Resend Code" to receive a new code.');
      return false;
    }
    const code = updateFullCode();
    if (code.length !== 6) {
      e.preventDefault();
      alert('Please enter all 6 digits of the verification code.');
      digits.find(d => !d.value)?.focus();
    }
  });
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
