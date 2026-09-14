<?php
$pageTitle = 'System Admin Dashboard - Triple T University';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/admin_navbar.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">

    <!-- Dossier Hero Header Strip (Design System Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-shield-lock-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">System Admin Dashboard</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> System Administration
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-calendar-check text-primary me-1"></i> AY <?= esc($systemSettings['active_school_year'] ?? '2026–2027') ?>
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-people text-primary me-1"></i> <?= number_format($stats['total_users']) ?> Users
              </span>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-activity me-1"></i> <?= esc($stats['system_health'] ?? 'Operational') ?>
              </span>
            </div>
            <p class="text-muted small mb-0">Central governance console for identity management, role-based access control, security audit logging, and global configuration.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="users.php" class="btn btn-primary rounded-pill px-3 py-2 fw-medium shadow-sm d-inline-flex align-items-center gap-2">
            <i class="bi bi-person-gear"></i>
            <span>User Directory</span>
          </a>
          <a href="settings.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-2 shadow-xs">
            <i class="bi bi-sliders text-primary"></i>
            <span>System Settings</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (!empty($successMsg)): ?>
      <div class="alert alert-success d-flex align-items-center shadow-sm rounded-12 fade-in-up mb-4" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2.5 text-success"></i>
        <div><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
      <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-12 fade-in-up mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2.5 text-danger"></i>
        <div><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    <?php endif; ?>

    <!-- Executive KPI Metric Cards (Consistent 4-Column Grid) -->
    <div class="row g-4 mb-4">
      
      <!-- Card 1: Total Users -->
      <div class="col-sm-6 col-xl-3">
        <a href="users.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.1s;">
          <div class="stat-card-glow bg-primary"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-people-fill"></i>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-check-circle me-1"></i> Active Roster
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['total_users']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Total System Users</h2>
          <p class="text-muted small mb-0"><?= number_format($stats['staff_count']) ?> Staff • <?= number_format($stats['student_count']) ?> Students</p>
          <div class="stat-card-footer">
            <span>Identity Directory</span>
            <span class="stat-card-action text-primary">Manage Users <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 2: Application Records -->
      <div class="col-sm-6 col-xl-3">
        <a href="/sia/admin/admissions/review.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.15s;">
          <div class="stat-card-glow bg-info"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
              <i class="bi bi-file-earmark-check-fill"></i>
            </div>
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-mortarboard me-1"></i> Matriculation
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['total_apps']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Total Applications</h2>
          <p class="text-muted small mb-0"><?= number_format($stats['enrolled_apps']) ?> Enrolled • <?= number_format(max(0, $stats['total_apps'] - $stats['enrolled_apps'])) ?> In Queue</p>
          <div class="stat-card-footer">
            <span>Admissions Hub</span>
            <span class="stat-card-action text-info">View Records <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 3: Audit Logs -->
      <div class="col-sm-6 col-xl-3">
        <a href="audit_logs.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow bg-warning"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-shield-check"></i>
            </div>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-journal-text me-1"></i> Audit Trail
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['total_logs']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Logged Audit Events</h2>
          <p class="text-muted small mb-0">Traceable system activity records</p>
          <div class="stat-card-footer">
            <span>Security Ledger</span>
            <span class="stat-card-action text-warning">Review Logs <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 4: New Users This Week -->
      <div class="col-sm-6 col-xl-3">
        <a href="users.php" class="stat-card-kpi fade-in-up" style="animation-delay: 0.25s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-person-plus-fill"></i>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-lightning-charge me-1"></i> +<?= number_format($stats['today_regs']) ?> Today
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['week_regs']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Recent Registrations</h2>
          <p class="text-muted small mb-0">Added in the last 7 calendar days</p>
          <div class="stat-card-footer">
            <span>Onboarding Log</span>
            <span class="stat-card-action text-success">View Directory <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

    </div>

    <!-- Governance Consoles & Shortcuts (Dossier Card Styling) -->
    <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.3s;">
      <div class="dossier-card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-grid-fill"></i>
          </div>
          <h2 class="h6 fw-bold text-dark mb-0">System Governance & Control Consoles</h2>
        </div>
        <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold">
          <i class="bi bi-sliders text-primary me-1"></i>6 Modules
        </span>
      </div>
      <div class="p-4">
        <div class="row g-3">
          
          <!-- Shortcut 1: User Directory -->
          <div class="col-md-6 col-lg-4">
            <a href="users.php" class="p-3 bg-white border rounded-3 text-decoration-none d-flex align-items-start gap-3 h-100 hover-lift shadow-xs">
              <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3 flex-shrink-0" style="width: 46px; height: 46px; font-size: 1.35rem;">
                <i class="bi bi-person-badge-fill"></i>
              </div>
              <div>
                <span class="fw-bold text-dark d-block mb-1">User Directory & RBAC</span>
                <p class="small text-muted mb-0">Manage accounts, assign roles, configure permissions, and reset passwords.</p>
              </div>
            </a>
          </div>

          <!-- Shortcut 2: Audit Logs -->
          <div class="col-md-6 col-lg-4">
            <a href="audit_logs.php" class="p-3 bg-white border rounded-3 text-decoration-none d-flex align-items-start gap-3 h-100 hover-lift shadow-xs">
              <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-3 flex-shrink-0" style="width: 46px; height: 46px; font-size: 1.35rem;">
                <i class="bi bi-shield-check"></i>
              </div>
              <div>
                <span class="fw-bold text-dark d-block mb-1">Audit Trail & Security</span>
                <p class="small text-muted mb-0">Review system activity, authentication events, and critical overrides.</p>
              </div>
            </a>
          </div>

          <!-- Shortcut 3: Reports & Exports -->
          <div class="col-md-6 col-lg-4">
            <a href="reports.php" class="p-3 bg-white border rounded-3 text-decoration-none d-flex align-items-start gap-3 h-100 hover-lift shadow-xs">
              <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-3 flex-shrink-0" style="width: 46px; height: 46px; font-size: 1.35rem;">
                <i class="bi bi-bar-chart-line-fill"></i>
              </div>
              <div>
                <span class="fw-bold text-dark d-block mb-1">Reports & Data Exports</span>
                <p class="small text-muted mb-0">Generate institutional reports, student census, and revenue exports.</p>
              </div>
            </a>
          </div>

          <!-- Shortcut 4: System Settings -->
          <div class="col-md-6 col-lg-4">
            <a href="settings.php" class="p-3 bg-white border rounded-3 text-decoration-none d-flex align-items-start gap-3 h-100 hover-lift shadow-xs">
              <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-3 flex-shrink-0" style="width: 46px; height: 46px; font-size: 1.35rem;">
                <i class="bi bi-sliders2"></i>
              </div>
              <div>
                <span class="fw-bold text-dark d-block mb-1">Global Parameters</span>
                <p class="small text-muted mb-0">Configure academic years, tuition cost per unit, and enrollment gateway.</p>
              </div>
            </a>
          </div>

          <!-- Shortcut 5: Database Backup -->
          <div class="col-md-6 col-lg-4">
            <a href="backup.php" class="p-3 bg-white border rounded-3 text-decoration-none d-flex align-items-start gap-3 h-100 hover-lift shadow-xs">
              <div class="d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary rounded-3 flex-shrink-0" style="width: 46px; height: 46px; font-size: 1.35rem;">
                <i class="bi bi-database-down"></i>
              </div>
              <div>
                <span class="fw-bold text-dark d-block mb-1">Database Backup & Restore</span>
                <p class="small text-muted mb-0">Download encrypted SQL database snapshots or restore states.</p>
              </div>
            </a>
          </div>

          <!-- Shortcut 6: LMS Automation -->
          <div class="col-md-6 col-lg-4">
            <a href="/sia/admin/lms/generator" class="p-3 bg-white border rounded-3 text-decoration-none d-flex align-items-start gap-3 h-100 hover-lift shadow-xs">
              <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3 flex-shrink-0" style="width: 46px; height: 46px; font-size: 1.35rem;">
                <i class="bi bi-cpu-fill"></i>
              </div>
              <div>
                <span class="fw-bold text-dark d-block mb-1">LMS Course Provisioning</span>
                <p class="small text-muted mb-0">Batch-generate LMS courses from verified enrollment section rosters.</p>
              </div>
            </a>
          </div>

        </div>
      </div>
    </div>

    <!-- Recent User Registrations (Dossier Card Styling) -->
    <div class="dossier-card fade-in-up" id="recentRegistrations" style="animation-delay: 0.35s;">
      <div class="dossier-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-person-lines-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Recent User Registrations</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <i class="bi bi-people text-primary me-1"></i><span id="userCount"><?= count($recent_regs) ?></span> Latest Users
            </span>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">
          <!-- Real-Time Client Search -->
          <div class="input-group shadow-xs" style="min-width: 240px;">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" id="userTableSearch" class="form-control border-start-0 ps-0" placeholder="Search recent users..." autocomplete="off">
            <button class="btn btn-outline-secondary border-start-0 bg-white text-muted d-none" type="button" id="clearSearchBtn">
              <i class="bi bi-x-circle-fill"></i>
            </button>
          </div>
          <a href="users.php" class="btn btn-sm btn-light border rounded-pill px-3 py-1.5 fw-medium text-dark d-inline-flex align-items-center gap-1 shadow-xs">
            <span>View All</span>
            <i class="bi bi-arrow-right small"></i>
          </a>
        </div>

      </div>

      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table custom-table">
            <thead>
              <tr>
                <th scope="col" class="ps-4">User / Identity</th>
                <th scope="col">Email Address</th>
                <th scope="col">Role & Privileges</th>
                <th scope="col">Status</th>
                <th scope="col">Last Login</th>
                <th scope="col">Registered On</th>
                <th scope="col" class="text-end pe-4">Action</th>
              </tr>
            </thead>
            <tbody id="userTableBody">
              <?php if (empty($recent_regs)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 64px; height: 64px;">
                        <i class="bi bi-inbox fs-2 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">No Recent User Registrations</h3>
                      <p class="text-muted small mb-0">Newly onboarded users will appear here automatically.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($recent_regs as $reg): 
                    $fName = trim($reg['first_name'] ?? '');
                    $lName = trim($reg['last_name'] ?? '');
                    $fullName = trim($fName . ' ' . $lName);
                    if ($fullName === '') $fullName = 'Unknown User';

                    $initials = strtoupper(mb_substr($fName, 0, 1) . mb_substr($lName, 0, 1));
                    if ($initials === '') $initials = 'US';

                    $role = strtolower($reg['role'] ?? 'applicant');
                    $roleBadge = match($role) {
                        'superadmin'  => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                        'admin'       => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                        'scheduler'   => 'bg-indigo-subtle text-primary border border-primary border-opacity-25',
                        'admissions'  => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                        'cashier'     => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                        'scholarship' => 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25',
                        'clinic'      => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                        'faculty'     => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                        'student'     => 'bg-light text-dark border',
                        default       => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25'
                    };
                    $roleIcon = match($role) {
                        'superadmin'  => 'bi-shield-shaded',
                        'admin'       => 'bi-mortarboard-fill',
                        'scheduler'   => 'bi-calendar3',
                        'admissions'  => 'bi-person-check-fill',
                        'cashier'     => 'bi-cash-stack',
                        'scholarship' => 'bi-award-fill',
                        'clinic'      => 'bi-heart-pulse-fill',
                        'faculty'     => 'bi-journal-bookmark-fill',
                        'student'     => 'bi-person-badge',
                        default       => 'bi-person'
                    };
                    $roleLabel = match($role) {
                        'superadmin'  => 'Superadmin',
                        'admin'       => 'Registrar',
                        'scheduler'   => 'Scheduler',
                        'admissions'  => 'Admissions',
                        'scholarship' => 'Scholarship',
                        'cashier'     => 'Cashier',
                        'clinic'      => 'Clinic',
                        'faculty'     => 'Faculty',
                        'student'     => 'Student',
                        default       => 'Applicant'
                    };
                ?>
                  <tr class="user-row" 
                      data-name="<?= esc(strtolower($fullName)) ?>"
                      data-email="<?= esc(strtolower($reg['email'] ?? '')) ?>"
                      data-role="<?= esc(strtolower($roleLabel)) ?>">
                    
                    <!-- Identity -->
                    <td class="ps-4">
                      <div class="d-flex align-items-center gap-2.5">
                        <div class="applicant-avatar">
                          <?= esc($initials) ?>
                        </div>
                        <div>
                          <span class="fw-bold text-dark d-block"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></span>
                          <span class="extra-small text-muted font-monospace">UID #<?= esc($reg['id']) ?></span>
                        </div>
                      </div>
                    </td>

                    <!-- Email -->
                    <td>
                      <a href="mailto:<?= htmlspecialchars($reg['email'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none small text-dark d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-envelope text-muted"></i>
                        <span><?= htmlspecialchars($reg['email'], ENT_QUOTES, 'UTF-8') ?></span>
                      </a>
                    </td>

                    <!-- Role -->
                    <td>
                      <span class="badge <?= esc($roleBadge) ?> rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1.5">
                        <i class="bi <?= esc($roleIcon) ?>"></i>
                        <span><?= esc($roleLabel) ?></span>
                      </span>
                    </td>

                    <!-- Status -->
                    <td>
                      <?php if (!empty($reg['is_active'])): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1">
                          <i class="bi bi-check-circle-fill"></i> Active
                        </span>
                      <?php else: ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1">
                          <i class="bi bi-dash-circle-fill"></i> Inactive
                        </span>
                      <?php endif; ?>
                    </td>

                    <!-- Last Login -->
                    <td>
                      <?php if (!empty($reg['last_login'])): ?>
                        <div class="small fw-medium text-dark d-flex align-items-center gap-1">
                          <i class="bi bi-clock-history text-muted extra-small"></i>
                          <?= date('M d, Y', strtotime($reg['last_login'])) ?>
                        </div>
                        <div class="extra-small text-muted"><?= date('g:i A', strtotime($reg['last_login'])) ?></div>
                      <?php else: ?>
                        <span class="extra-small text-muted fst-italic">Never logged in</span>
                      <?php endif; ?>
                    </td>

                    <!-- Registered On -->
                    <td>
                      <div class="small fw-medium text-dark"><?= date('M d, Y', strtotime($reg['created_at'])) ?></div>
                      <div class="extra-small text-muted"><?= date('g:i A', strtotime($reg['created_at'])) ?></div>
                    </td>

                    <!-- Action -->
                    <td class="text-end pe-4">
                      <a href="users.php?search=<?= urlencode($reg['email']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium d-inline-flex align-items-center gap-1">
                        <span>Inspect</span>
                        <i class="bi bi-arrow-right small"></i>
                      </a>
                    </td>

                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>

              <!-- No Search Results Row -->
              <tr id="noResultsRow" style="display: none;">
                <td colspan="7" class="text-center py-5 text-muted">
                  <div class="d-flex flex-column align-items-center justify-content-center py-4">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 64px; height: 64px;">
                      <i class="bi bi-search fs-2 text-muted"></i>
                    </div>
                    <h3 class="h6 fw-bold text-dark mb-1">No Matching Users</h3>
                    <p class="text-muted small mb-0">No recent accounts matched your search keyword.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </div>
</main>

<script>
(function() {
    function initSysadminDashboard() {
        const searchInput = document.getElementById('userTableSearch');
        const clearBtn = document.getElementById('clearSearchBtn');
        const rows = document.querySelectorAll('#userTableBody tr.user-row');
        const noResultsRow = document.getElementById('noResultsRow');
        const userCountEl = document.getElementById('userCount');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();
                let visibleCount = 0;

                if (clearBtn) {
                    clearBtn.classList.toggle('d-none', query === '');
                }

                rows.forEach(row => {
                    const name = row.dataset.name || '';
                    const email = row.dataset.email || '';
                    const role = row.dataset.role || '';

                    if (query === '' || name.includes(query) || email.includes(query) || role.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (userCountEl) userCountEl.textContent = visibleCount;
                if (noResultsRow) {
                    noResultsRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
                }
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (searchInput) {
                    searchInput.value = '';
                    clearBtn.classList.add('d-none');
                    searchInput.focus();
                    searchInput.dispatchEvent(new Event('input'));
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSysadminDashboard);
    } else {
        initSysadminDashboard();
    }
    document.addEventListener('spa:navigated', initSysadminDashboard);
})();
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
