<?php
$portalTitles = [
    'faculty' => 'Reset Faculty Password - Triple T University',
    'student' => 'Reset Student Password - Triple T University',
    'applicant' => 'Reset Password - Triple T University'
];
$pageTitle = $portalTitles[$portal] ?? $portalTitles['applicant'];
require_once __DIR__ . '/../components/header.php';
?>

<main class="auth-page">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="auth-island fade-in-up" style="max-width: 500px; animation-delay: 0.1s;">
          <div class="text-center mb-4">
            <div class="mx-auto mb-3">
              <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 64px; width: auto; object-fit: contain;">
            </div>
            <div class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-2 fw-semibold">
              <i class="bi bi-shield-lock me-1"></i> 
              <?php if ($portal === 'faculty'): ?>
                Faculty Password Reset
              <?php elseif ($portal === 'student'): ?>
                Student Password Reset
              <?php else: ?>
                Applicant Password Reset
              <?php endif; ?>
            </div>
            <h1 class="h4 mb-2 fw-bold text-dark">Set New Password</h1>
            <p class="text-muted mb-0 small">
              Enter the 6-digit code sent to:<br>
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
              <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
              <div><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger rounded-3 border-0 bg-danger text-white py-2 px-3 small shadow-sm mb-4 d-flex align-items-center">
              <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
              <div>
                <ul class="mb-0 ps-2 list-unstyled">
                  <?php foreach ((array)$errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          <?php endif; ?>

          <form id="resetForm" class="no-spinner" action="/sia/auth/reset_password_process.php" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="portal" value="<?= htmlspecialchars($portal ?? 'applicant', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="fullCodeInput" name="code" value="<?= htmlspecialchars($code ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <!-- 6-Digit OTP Block -->
            <div class="mb-4">
              <label class="form-label text-muted small fw-semibold text-center d-block mb-3">6-Digit Verification Code</label>
              
              <div class="d-flex justify-content-between gap-2" id="otpInputs">
                <?php 
                  $codeStr = (string)($code ?? '');
                  for ($i = 0; $i < 6; $i++): 
                    $val = isset($codeStr[$i]) ? $codeStr[$i] : '';
                ?>
                  <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" 
                         value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>"
                         class="form-control text-center fs-3 fw-bold otp-digit" 
                         style="height: 56px; border-radius: 12px; border: 2px solid #dee2e6;" 
                         <?= ($i === 0 && empty($val)) ? 'autofocus' : '' ?> required>
                <?php endfor; ?>
              </div>
              <div class="text-center text-muted small mt-2" style="font-size: 0.78rem;">
                <i class="bi bi-clock-history me-1"></i> Code expires in 15 minutes
              </div>
            </div>

            <!-- New Password -->
            <div class="mb-3">
              <label class="form-label text-muted small fw-semibold" for="password">New Password</label>
              <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #dee2e6;">
                <input class="form-control border-0 shadow-none" style="padding: 0.75rem 1rem;" type="password" id="password" name="password" minlength="8" required placeholder="Minimum 8 characters">
                <button class="btn btn-light border-0 px-3 text-muted toggle-pwd" type="button" data-target="password" tabindex="-1">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
              <label class="form-label text-muted small fw-semibold" for="confirm_password">Confirm New Password</label>
              <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #dee2e6;">
                <input class="form-control border-0 shadow-none" style="padding: 0.75rem 1rem;" type="password" id="confirm_password" name="confirm_password" minlength="8" required placeholder="Re-enter new password">
                <button class="btn btn-light border-0 px-3 text-muted toggle-pwd" type="button" data-target="confirm_password" tabindex="-1">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="d-grid mb-3">
              <button class="btn btn-primary btn-lg shadow-sm fw-semibold" type="submit" id="submitBtn" style="border-radius: 10px; padding: 0.8rem;">
                <i class="bi bi-check2-circle me-1"></i> Save New Password
              </button>
            </div>
          </form>

          <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4">
            <div>
              <form action="/sia/auth/resend_reset_otp.php" method="post" id="resendForm" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="portal" value="<?= htmlspecialchars($portal ?? 'applicant', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-link p-0 text-decoration-none small text-primary fw-semibold" id="resendBtn">
                  <i class="bi bi-arrow-repeat me-1"></i> Resend Code
                </button>
              </form>
              <span id="countdownTimer" class="small text-muted d-none ms-1"></span>
            </div>
            
            <?php if ($portal === 'faculty'): ?>
              <a href="/sia/auth/lms_faculty_login.php" class="small text-muted text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Back to Faculty Login
              </a>
            <?php elseif ($portal === 'student'): ?>
              <a href="/sia/auth/lms_student_login.php" class="small text-muted text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Back to Student Login
              </a>
            <?php else: ?>
              <a href="/sia/auth/login.php" class="small text-muted text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Back to Applicant Login
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
  const digits = document.querySelectorAll(".otp-digit");
  const fullCodeInput = document.getElementById("fullCodeInput");
  const resetForm = document.getElementById("resetForm");

  function updateFullCode() {
    let code = "";
    digits.forEach(d => { code += d.value.trim(); });
    fullCodeInput.value = code;
  }

  // Handle OTP Inputs
  digits.forEach((digit, index) => {
    digit.addEventListener("input", function(e) {
      this.value = this.value.replace(/\D/g, "");
      if (this.value.length >= 1) {
        this.value = this.value.charAt(0);
        this.style.borderColor = "#0d6efd";
        if (index < digits.length - 1) {
          digits[index + 1].focus();
        }
      } else {
        this.style.borderColor = "#dee2e6";
      }
      updateFullCode();
    });

    digit.addEventListener("keydown", function(e) {
      if (e.key === "Backspace") {
        if (this.value === "" && index > 0) {
          digits[index - 1].focus();
          digits[index - 1].value = "";
          digits[index - 1].style.borderColor = "#dee2e6";
        } else {
          this.value = "";
          this.style.borderColor = "#dee2e6";
        }
        updateFullCode();
      } else if (e.key === "ArrowLeft" && index > 0) {
        digits[index - 1].focus();
      } else if (e.key === "ArrowRight" && index < digits.length - 1) {
        digits[index + 1].focus();
      }
    });

    digit.addEventListener("paste", function(e) {
      e.preventDefault();
      const pasteData = (e.clipboardData || window.clipboardData).getData("text").trim();
      const cleanData = pasteData.replace(/\D/g, "").slice(0, 6);
      
      if (cleanData.length > 0) {
        cleanData.split("").forEach((char, i) => {
          if (i < digits.length) {
            digits[i].value = char;
            digits[i].style.borderColor = "#0d6efd";
          }
        });
        const nextIndex = Math.min(cleanData.length, digits.length - 1);
        digits[nextIndex].focus();
        updateFullCode();
      }
    });
  });

  // Toggle Password Visibility
  document.querySelectorAll(".toggle-pwd").forEach(btn => {
    btn.addEventListener("click", function() {
      const targetId = this.getAttribute("data-target");
      const input = document.getElementById(targetId);
      if (input) {
        const type = input.getAttribute("type") === "password" ? "text" : "password";
        input.setAttribute("type", type);
        const icon = this.querySelector("i");
        if (icon) {
          icon.classList.toggle("bi-eye");
          icon.classList.toggle("bi-eye-slash");
        }
      }
    });
  });

  // Form submission check
  resetForm.addEventListener("submit", function(e) {
    updateFullCode();
    if (fullCodeInput.value.length !== 6) {
      e.preventDefault();
      alert("Please enter the complete 6-digit verification code.");
      digits[0].focus();
      return false;
    }
  });

  // Initialize full code if digits pre-filled
  updateFullCode();

  // Resend Countdown Timer (60 seconds)
  const resendBtn = document.getElementById("resendBtn");
  const countdownTimer = document.getElementById("countdownTimer");
  
  if (resendBtn && countdownTimer) {
    let lastSentTime = sessionStorage.getItem("ttu_reset_otp_sent");
    let now = Math.floor(Date.now() / 1000);
    
    if (lastSentTime && (now - parseInt(lastSentTime, 10)) < 60) {
      startCooldown(60 - (now - parseInt(lastSentTime, 10)));
    }

    resendBtn.addEventListener("click", function() {
      sessionStorage.setItem("ttu_reset_otp_sent", Math.floor(Date.now() / 1000));
    });

    function startCooldown(seconds) {
      resendBtn.classList.add("disabled", "text-muted");
      resendBtn.style.pointerEvents = "none";
      countdownTimer.classList.remove("d-none");
      
      let remaining = seconds;
      countdownTimer.textContent = "(" + remaining + "s)";
      
      let timer = setInterval(function() {
        remaining--;
        if (remaining <= 0) {
          clearInterval(timer);
          resendBtn.classList.remove("disabled", "text-muted");
          resendBtn.style.pointerEvents = "auto";
          countdownTimer.classList.add("d-none");
          sessionStorage.removeItem("ttu_reset_otp_sent");
        } else {
          countdownTimer.textContent = "(" + remaining + "s)";
        }
      }, 1000);
    }
  }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
