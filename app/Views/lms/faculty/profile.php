<?php require_once __DIR__ . '/layout_header.php'; ?>

<main class="py-4 bg-light min-vh-100">
  <div class="container-fluid px-lg-4">

    <!-- Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-person-badge-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Faculty Profile &amp; Preferences</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                Instructor Credentials
              </span>
            </div>
            <p class="text-muted small mb-0">
              Manage your academic profile, notification subscriptions, and office hours visibility.
            </p>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="/sia/lms/faculty/dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <div class="row g-4 fade-in-up">
      <!-- Left Column: Identity & Account Summary -->
      <div class="col-lg-4">
        <!-- Profile Card -->
        <div class="dossier-card p-4 text-center border-0 shadow-sm mb-4 position-relative overflow-hidden bg-white rounded-4">
          <div class="position-absolute top-0 start-0 w-100" style="height: 90px; background: linear-gradient(135deg, rgba(13,110,253,0.15) 0%, rgba(13,110,253,0.05) 100%);"></div>
          <div class="position-relative mt-3 mb-3">
            <div class="applicant-avatar text-white fw-bold shadow-sm mx-auto" style="width: 88px; height: 88px; font-size: 2.25rem; border: 4px solid #fff; background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); display: flex; align-items: center; justify-content: center; border-radius: 50%;">
              <?= esc(substr($_SESSION['user_name'] ?? $_SESSION['lms_name'] ?? 'F', 0, 1)) ?>
            </div>
          </div>
          <h4 class="fw-bold mb-1 text-dark fs-5"><?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['lms_name'] ?? 'Faculty Name') ?></h4>
          <p class="text-muted small mb-3"><?= htmlspecialchars($_SESSION['user_email'] ?? $_SESSION['lms_email'] ?? 'faculty@ttu.edu.ph') ?></p>
          <div class="d-flex justify-content-center gap-2 flex-wrap">
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1.5 rounded-pill fw-semibold small">
              <i class="bi bi-patch-check-fill me-1"></i>Verified Faculty
            </span>
            <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-semibold small">
              Taguig Technological University
            </span>
          </div>
        </div>

        <!-- Account Credentials Details -->
        <div class="dossier-card p-4 border-0 shadow-sm bg-white rounded-4">
          <div class="dossier-card-header bg-white border-bottom pb-3 mb-3 d-flex align-items-center justify-content-between">
            <h6 class="fw-bold mb-0 text-dark text-uppercase small" style="letter-spacing: 0.05em;">
              <i class="bi bi-shield-check text-primary me-2"></i>Institutional Account
            </h6>
            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-0.5 small fw-semibold">Active</span>
          </div>
          <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
            <li class="d-flex align-items-center">
              <div class="d-flex align-items-center justify-content-center bg-light text-primary rounded-3 me-3" style="width: 38px; height: 38px;">
                <i class="bi bi-person-badge fs-5"></i>
              </div>
              <div>
                <small class="text-muted d-block" style="font-size: 0.72rem;">Academic Role</small>
                <span class="fw-bold text-dark small">Faculty / Course Instructor</span>
              </div>
            </li>
            <li class="d-flex align-items-center">
              <div class="d-flex align-items-center justify-content-center bg-light text-primary rounded-3 me-3" style="width: 38px; height: 38px;">
                <i class="bi bi-building fs-5"></i>
              </div>
              <div>
                <small class="text-muted d-block" style="font-size: 0.72rem;">Affiliation</small>
                <span class="fw-bold text-dark small">Taguig Technological University</span>
              </div>
            </li>
            <li class="d-flex align-items-center">
              <div class="d-flex align-items-center justify-content-center bg-light text-primary rounded-3 me-3" style="width: 38px; height: 38px;">
                <i class="bi bi-clock-history fs-5"></i>
              </div>
              <div>
                <small class="text-muted d-block" style="font-size: 0.72rem;">Timezone &amp; Locale</small>
                <span class="fw-bold text-dark small">Asia/Manila (UTC+8)</span>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Right Column: Preferences & Notifications -->
      <div class="col-lg-8">
        <div class="dossier-card p-4 border-0 shadow-sm bg-white rounded-4 h-100">
          <div class="dossier-card-header bg-white border-bottom pb-3 mb-4 d-flex align-items-center justify-content-between">
            <div>
              <h5 class="fw-bold text-dark mb-1">Instructional Delivery Preferences</h5>
              <div class="text-muted small">Configure alerts for student submissions and consultations</div>
            </div>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small">
              <i class="bi bi-gear-fill me-1 text-primary"></i>Config
            </span>
          </div>
          
          <div class="mb-4">
            <h6 class="fw-bold text-dark mb-3 small text-uppercase" style="letter-spacing: 0.05em;">Notifications &amp; Activity Alerts</h6>
            <div class="p-3 border rounded-3 bg-light bg-opacity-50 mb-3">
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="notifEmail" checked>
                <label class="form-check-label fw-bold text-dark small" for="notifEmail">Email Notifications</label>
              </div>
              <small class="text-muted d-block ps-4 ms-1">Receive automated email alerts whenever students submit assignments, finish quizzes, or ask questions in course forums.</small>
            </div>
            <div class="p-3 border rounded-3 bg-light bg-opacity-50">
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="notifPush" checked>
                <label class="form-check-label fw-bold text-dark small" for="notifPush">In-App Notification Panel</label>
              </div>
              <small class="text-muted d-block ps-4 ms-1">Show real-time badge counters and alert pills in the faculty navigation sidebar.</small>
            </div>
          </div>

          <div class="mb-4">
            <h6 class="fw-bold text-dark mb-2 small text-uppercase" style="letter-spacing: 0.05em;">Faculty Office Hours &amp; Consultation</h6>
            <p class="text-muted small mb-2">Provide your weekly availability schedule for student advising and consultations.</p>
            <textarea class="form-control rounded-3" rows="3" placeholder="E.g., Monday &amp; Wednesday: 2:00 PM – 4:00 PM (Room 304 / Google Meet)"></textarea>
          </div>

          <div class="pt-3 border-top d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light rounded-pill px-4 fw-medium text-dark border">Discard Changes</button>
            <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Save Preferences</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
