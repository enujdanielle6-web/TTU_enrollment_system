<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireApplicantLogin();

$pageTitle = 'My Profile - Triple T University';
$userId = (int) $_SESSION['user_id'];
$user = null;
$application = null;
$fetchError = null;

$successMsg = $_SESSION['profile_success'] ?? '';
$errors = $_SESSION['profile_errors'] ?? [];
$old = $_SESSION['profile_old'] ?? [];
unset($_SESSION['profile_success'], $_SESSION['profile_errors'], $_SESSION['profile_old']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    // Fetch user details
    $userStmt = $pdo->prepare('SELECT first_name, last_name, email, role, created_at FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute(['id' => $userId]);
    $user = $userStmt->fetch();

    if ($user) {
        // Fetch application details for contact number and reference number
        $appStmt = $pdo->prepare('SELECT reference_number, contact_number FROM applications WHERE user_id = :user_id LIMIT 1');
        $appStmt->execute(['user_id' => $userId]);
        $application = $appStmt->fetch() ?: null;
    }
} catch (PDOException $e) {
    error_log('Profile page fetch failed: ' . $e->getMessage());
    $fetchError = 'Unable to retrieve profile details at this time.';
}

if (!$user) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../components/header.php';
?>

<?php require_once __DIR__ . '/components/navbar.php'; ?>

<main class="py-5 bg-light min-vh-100">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        
        <!-- Profile Header -->
        <div class="island island-hero mb-4 border-0 shadow-sm position-relative overflow-hidden" style="border-radius: 16px;">
          <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
          <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-4">
              <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center shadow" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
              </div>
              <div>
                <h1 class="h3 mb-1 fw-bold text-dark"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="text-muted mb-0"><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
              </div>
            </div>

          </div>
        </div>

        <?php if ($successMsg): ?>
          <div class="alert alert-success shadow-sm rounded-12 p-3 d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-check-circle-fill fs-5 text-success"></i>
            <span class="small fw-semibold"><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger shadow-sm rounded-12 p-3 mb-4">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
              <span class="fw-bold small">Please correct the following errors:</span>
            </div>
            <ul class="mb-0 ps-3 small">
              <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($fetchError !== null): ?>
          <div class="alert alert-danger shadow-sm rounded-12">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($fetchError, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php else: ?>
          
          <!-- 1. Account Details Card -->
          <div class="island mb-4 border-0 shadow-sm position-relative overflow-hidden" style="border-radius: 16px;">
            <div class="island-header pt-4 px-4 pb-0 border-0 d-flex align-items-center">
              <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                <i class="bi bi-person-badge fs-5"></i>
              </div>
              <h2 class="h5 mb-0 fw-bold text-dark">Account Details</h2>
            </div>
            <div class="island-body p-4">
              <form action="profile_process.php" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="update_account">
                
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label text-muted small fw-semibold" for="firstName">First Name</label>
                    <input class="form-control" type="text" id="firstName" name="first_name" value="<?= htmlspecialchars($old['first_name'] ?? $user['first_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    <div class="invalid-feedback">First name is required.</div>
                  </div>
                  
                  <div class="col-md-6">
                    <label class="form-label text-muted small fw-semibold" for="lastName">Last Name</label>
                    <input class="form-control" type="text" id="lastName" name="last_name" value="<?= htmlspecialchars($old['last_name'] ?? $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    <div class="invalid-feedback">Last name is required.</div>
                  </div>
                  
                  <div class="col-md-6">
                    <label class="form-label text-muted small fw-semibold" for="email">Email Address</label>
                    <input class="form-control" type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? $user['email'], ENT_QUOTES, 'UTF-8'); ?>" required placeholder="name@example.com">
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label text-muted small fw-semibold">Account Role</label>
                    <span class="form-control bg-light text-uppercase small fw-bold text-secondary"><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label text-muted small fw-semibold">Portal Registration Date</label>
                    <input class="form-control bg-light" type="text" value="<?= htmlspecialchars(date('F j, Y g:i A', strtotime($user['created_at'])), ENT_QUOTES, 'UTF-8'); ?>" disabled readonly>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label text-muted small fw-semibold">Application Reference Number</label>
                    <div class="form-control bg-light fw-bold text-dark">
                      <?= $application ? htmlspecialchars($application['reference_number'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted fw-normal">Not Submitted Yet</span>'; ?>
                    </div>
                  </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                  <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                    <i class="bi bi-save me-1"></i> Save Account Details
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- 2. Contact Details Card -->
          <div class="island mb-4 border-0 shadow-sm position-relative overflow-hidden" style="border-radius: 16px;">
            <div class="island-header pt-4 px-4 pb-0 border-0 d-flex align-items-center">
              <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                <i class="bi bi-telephone fs-5"></i>
              </div>
              <h2 class="h5 mb-0 fw-bold text-dark">Contact Details</h2>
            </div>
            <div class="island-body p-4">
              <?php if (!$application): ?>
                <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-dark small rounded-3 p-3 mb-0 d-flex align-items-center gap-2">
                  <i class="bi bi-info-circle-fill text-warning fs-5"></i>
                  <span>You have not submitted an enrollment application yet. You can set and manage your contact details by starting the enrollment form.</span>
                </div>
              <?php else: ?>
                <form action="profile_process.php" method="POST" class="needs-validation" novalidate>
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="action" value="update_contact">
                  
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label text-muted small fw-semibold" for="contactNumber">Mobile Number</label>
                      <input class="form-control" type="tel" id="contactNumber" name="contact_number" value="<?= htmlspecialchars($old['contact_number'] ?? $application['contact_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="e.g. 09123456789" pattern="^09\d{9}$" maxlength="11">
                      <div class="invalid-feedback">Must be a valid 11-digit mobile number starting with 09.</div>
                    </div>
                  </div>

                  <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                      <i class="bi bi-save me-1"></i> Update Contact Info
                    </button>
                  </div>
                </form>
              <?php endif; ?>
            </div>
          </div>

          <!-- 3. Security Credentials Card -->
          <div class="island mb-4 border-0 shadow-sm position-relative overflow-hidden" style="border-radius: 16px;">
            <div class="island-header pt-4 px-4 pb-0 border-0 d-flex align-items-center">
              <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                <i class="bi bi-shield-lock fs-5"></i>
              </div>
              <h2 class="h5 mb-0 fw-bold text-dark">Security Credentials</h2>
            </div>
            <div class="island-body p-4">
              <form action="profile_process.php" method="POST" class="needs-validation" id="passwordForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="change_password">

                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label text-muted small fw-semibold" for="currentPassword">Current Password</label>
                    <input class="form-control" type="password" id="currentPassword" name="current_password" required placeholder="••••••••">
                    <div class="invalid-feedback">Current password is required.</div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label text-muted small fw-semibold" for="newPassword">New Password</label>
                    <input class="form-control" type="password" id="newPassword" name="new_password" minlength="8" required placeholder="••••••••">
                    <div class="invalid-feedback">New password must be at least 8 characters.</div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label text-muted small fw-semibold" for="confirmPassword">Confirm New Password</label>
                    <input class="form-control" type="password" id="confirmPassword" name="confirm_password" minlength="8" required placeholder="••••••••">
                    <div class="invalid-feedback">Please match the new password.</div>
                  </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                  <button type="submit" class="btn btn-danger btn-sm px-4 rounded-pill">
                    <i class="bi bi-key me-1"></i> Change Password
                  </button>
                </div>
              </form>
            </div>
          </div>

        <?php endif; ?>

      </div>
    </div>
  </div>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
  $(function () {
    // Client-side bootstrap validations
    var forms = $('.needs-validation');
    forms.on('submit', function (event) {
      var form = this;
      
      if (form.id === 'passwordForm') {
        var newPass = $('#newPassword').val();
        var confPass = $('#confirmPassword').val();
        $('#confirmPassword')[0].setCustomValidity(
          newPass === confPass ? '' : 'Passwords do not match.'
        );
      }

      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      $(form).addClass('was-validated');
    });
  });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
