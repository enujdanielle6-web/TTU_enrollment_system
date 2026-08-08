
<nav class="navbar navbar-expand-lg navbar-dark bg-info sticky-top shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
      <img src="../../images/TTU_LOGO.png" alt="TTU Logo" style="height: 32px; width: auto; object-fit: contain;">
      <span class="fw-bold">TTU LMS Faculty</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#lmsNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="lmsNavbar">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link active" href="dashboard.php"><i class="bi bi-grid me-1"></i> Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#"><i class="bi bi-journal-text me-1"></i> Manage Classes</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#"><i class="bi bi-folder-plus me-1"></i> Modules</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#"><i class="bi bi-check2-square me-1"></i> Grading</a>
        </li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <span class="text-white-50"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['lms_name'] ?? 'Instructor', ENT_QUOTES, 'UTF-8'); ?></span>
        <a href="../../auth/logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>
    </div>
  </div>
</nav>

<main class="py-5 bg-light" style="min-height: calc(100vh - 60px);">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 fw-bold text-dark">Welcome, <?= htmlspecialchars($_SESSION['lms_name'] ?? 'Instructor', ENT_QUOTES, 'UTF-8'); ?></h1>
      <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2">
        <i class="bi bi-shield-check me-1"></i> Faculty Access
      </span>
    </div>

    <div class="row g-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
          <div class="text-muted mb-3">
            <i class="bi bi-gear display-1 text-info opacity-50"></i>
          </div>
          <h2 class="h5 fw-bold">Faculty Portal Ready</h2>
          <p class="text-muted mx-auto" style="max-width: 500px;">
            The LMS core has been successfully connected to the user directory. Here you will be able to manage your assigned subjects and upload modules once Phase 2 is complete.
          </p>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

