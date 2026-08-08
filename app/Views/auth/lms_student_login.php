<?php require_once __DIR__ . '/../components/header.php'; ?>

<main class="auth-page py-5 bg-light" style="min-height: 100vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="auth-island fade-in-up" style="animation-delay: 0.1s;">
          <div class="text-center mb-4">
            <div class="mx-auto mb-3">
              <img src="../images/TTU_LOGO.png" alt="TTU Logo" style="height: 64px; width: auto; object-fit: contain;">
            </div>
            <h1 class="h4 mb-2 fw-bold text-dark">Student LMS Portal</h1>
            <p class="text-muted mb-0 small">Login to access your enrolled courses.</p>
          </div>

          <form action="lms_login_process.php" method="post" novalidate>
            <input type="hidden" name="role" value="student">
            <div class="mb-3">
              <label class="form-label text-muted small fw-semibold" for="student_id">Student ID</label>
              <input class="form-control" style="padding: 0.75rem 1rem; border-radius: 10px;" type="text" id="student_id" name="student_id" required placeholder="e.g. 2024-0001">
            </div>

            <div class="mb-4">
              <label class="form-label text-muted small fw-semibold" for="password">Password</label>
              <div class="input-group" style="border-radius: 10px; overflow: hidden; border: 1px solid #dee2e6;">
                <input class="form-control border-0" style="padding: 0.75rem 1rem;" type="password" id="password" name="password" required placeholder="••••••••">
              </div>
            </div>

            <div class="mb-3 d-flex justify-content-end">
              <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="document.getElementById('student_id').value='2026-9999'; document.getElementById('password').value='password123';">
                <i class="bi bi-magic me-1"></i> Auto-fill Test Student
              </button>
            </div>

            <button class="btn btn-primary w-100 fw-semibold" style="padding: 0.75rem 1rem; border-radius: 10px;" type="submit">
              <i class="bi bi-unlock"></i> Login to LMS
            </button>
          </form>

          <div class="mt-4 text-center border-top pt-4">
            <p class="mb-2 text-muted small">Not enrolled yet? <a href="../public/index.php" class="fw-bold text-decoration-none text-primary">Apply here</a></p>
            <a href="../public/index.php" class="text-muted small text-decoration-none btn-link d-inline-flex align-items-center gap-1 mt-2 transition-all">
              <i class="bi bi-arrow-left"></i> Back to Homepage
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

