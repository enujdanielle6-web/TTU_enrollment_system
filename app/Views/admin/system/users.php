<?php
$pageTitle = 'User Management - Administrator';
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
            <i class="bi bi-people-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">User Directory & RBAC</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-check me-1"></i> Identity & Access
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-calendar-check text-primary me-1"></i> AY <?= esc($systemSettings['active_school_year'] ?? '2026–2027') ?>
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-person-check text-primary me-1"></i> <?= number_format($stats['total_users']) ?> Total Accounts
              </span>
            </div>
            <p class="text-muted small mb-0">Institutional account directory, role-based security assignments, password resets, and granular permissions governance.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="sysadmin_dashboard.php" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>Dashboard</span>
          </a>
          <button type="button" class="btn btn-primary fw-medium shadow-sm rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill"></i>
            <span>Add User</span>
          </button>
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
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.1s;">
          <div class="stat-card-glow bg-primary"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-people-fill"></i>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-database me-1"></i> Directory
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= number_format($stats['total_users']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Total System Users</h2>
          <p class="text-muted small mb-0">All registered institutional profiles</p>
          <div class="stat-card-footer">
            <span>Roster Scope</span>
            <span class="stat-card-action text-primary">Global Census <i class="bi bi-check2"></i></span>
          </div>
        </div>
      </div>

      <!-- Card 2: Staff & Officers -->
      <div class="col-sm-6 col-xl-3">
        <a href="users.php?role=all" class="stat-card-kpi fade-in-up" style="animation-delay: 0.15s;">
          <div class="stat-card-glow bg-info"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
              <i class="bi bi-person-badge-fill"></i>
            </div>
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-shield-lock me-1"></i> Admin & Faculty
            </span>
          </div>
          <div class="stat-number-display mb-1 text-info"><?= number_format($stats['staff_count']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Staff & Officers</h2>
          <p class="text-muted small mb-0">Administrative & academic accounts</p>
          <div class="stat-card-footer">
            <span>Privileged Roles</span>
            <span class="stat-card-action text-info">Inspect Roles <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 3: Student Accounts -->
      <div class="col-sm-6 col-xl-3">
        <a href="users.php?role=student" class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-mortarboard-fill"></i>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-check-circle me-1"></i> Enrollees
            </span>
          </div>
          <div class="stat-number-display mb-1 text-success"><?= number_format($stats['student_count']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Student Accounts</h2>
          <p class="text-muted small mb-0">Enrolled portal users</p>
          <div class="stat-card-footer">
            <span>Student Portal</span>
            <span class="stat-card-action text-success">Filter Students <i class="bi bi-arrow-right"></i></span>
          </div>
        </a>
      </div>

      <!-- Card 4: Active Status -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.25s;">
          <div class="stat-card-glow bg-warning"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-shield-check"></i>
            </div>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-lock-fill me-1"></i> Operational
            </span>
          </div>
          <div class="stat-number-display mb-1 text-warning"><?= number_format($stats['active_count']) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Active Accounts</h2>
          <p class="text-muted small mb-0"><?= $stats['total_users'] > 0 ? round(($stats['active_count'] / $stats['total_users']) * 100) : 100 ?>% in good standing</p>
          <div class="stat-card-footer">
            <span>Account Health</span>
            <span class="stat-card-action text-warning">Healthy <i class="bi bi-check2"></i></span>
          </div>
        </div>
      </div>

    </div>

    <!-- Registered Accounts (Dossier Card Styling) -->
    <div class="dossier-card fade-in-up" id="usersTableCard" style="animation-delay: 0.3s;">
      <div class="dossier-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-person-lines-fill"></i>
          </div>
          <div>
            <h2 class="h5 fw-bold text-dark mb-0 d-inline-block align-middle">Registered Accounts</h2>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <i class="bi bi-people text-primary me-1"></i><?= number_format($totalUsers) ?> Records
            </span>
          </div>
        </div>

        <!-- Search and Filter Form -->
        <form action="users.php" method="GET" class="d-flex gap-2 align-items-center flex-wrap">
          <select name="role" class="form-select form-select-sm bg-light" style="width: auto; border-radius: 20px; font-weight: 500;" onchange="this.form.submit()">
            <option value="all" <?= esc($roleFilter === 'all' ? 'selected' : '') ?>>All Roles (<?= $stats['total_users'] ?>)</option>
            <option value="student" <?= esc($roleFilter === 'student' ? 'selected' : '') ?>>Students (<?= $stats['student_count'] ?>)</option>
            <option value="applicant" <?= esc($roleFilter === 'applicant' ? 'selected' : '') ?>>Applicants</option>
            <option value="superadmin" <?= esc($roleFilter === 'superadmin' ? 'selected' : '') ?>>Super Administrators</option>
            <option value="admin" <?= esc($roleFilter === 'admin' ? 'selected' : '') ?>>Registrars</option>
            <option value="scheduler" <?= esc($roleFilter === 'scheduler' ? 'selected' : '') ?>>Schedulers</option>
            <option value="admissions" <?= esc($roleFilter === 'admissions' ? 'selected' : '') ?>>Admissions Officers</option>
            <option value="scholarship" <?= esc($roleFilter === 'scholarship' ? 'selected' : '') ?>>Scholarship Officers</option>
            <option value="cashier" <?= esc($roleFilter === 'cashier' ? 'selected' : '') ?>>Cashiers</option>
            <option value="clinic" <?= esc($roleFilter === 'clinic' ? 'selected' : '') ?>>Clinic Officers</option>
            <option value="faculty" <?= esc($roleFilter === 'faculty' ? 'selected' : '') ?>>Faculty</option>
          </select>
          <div class="input-group input-group-sm shadow-xs" style="width: 240px;">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" id="tableSearch" name="search" class="form-control border-start-0 ps-0" placeholder="Search accounts..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <?php if ($searchQuery !== '' || $roleFilter !== 'all'): ?>
            <a href="users.php" class="btn btn-sm btn-light border rounded-pill px-3">Reset</a>
          <?php endif; ?>
        </form>

      </div>
      
      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table custom-table">
            <thead>
              <tr>
                <th scope="col" class="ps-4">Name / Identity</th>
                <th scope="col">Email Address</th>
                <th scope="col">Department</th>
                <th scope="col">Role & Permissions</th>
                <th scope="col">Status</th>
                <th scope="col">Last Login</th>
                <th scope="col" class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($users)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <div class="d-flex flex-column align-items-center justify-content-center py-4">
                      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 64px; height: 64px;">
                        <i class="bi bi-person-x fs-2 text-muted"></i>
                      </div>
                      <h3 class="h6 fw-bold text-dark mb-1">No Accounts Found</h3>
                      <p class="text-muted small mb-0">No users match your specified role filter or search criteria.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($users as $user): 
                    $uFirst = trim($user['first_name'] ?? '');
                    $uLast = trim($user['last_name'] ?? '');
                    $uFullName = trim($uFirst . ' ' . $uLast);
                    if ($uFullName === '') $uFullName = 'Unknown User';

                    $initials = strtoupper(mb_substr($uFirst, 0, 1) . mb_substr($uLast, 0, 1));
                    if ($initials === '') $initials = 'US';

                    $badgeClass = match($user['role']) {
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
                    $roleLabel = match($user['role']) {
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
                  <tr>
                    <!-- Name & Identity -->
                    <td class="ps-4">
                      <div class="d-flex align-items-center gap-2.5">
                        <div class="applicant-avatar">
                          <?= esc($initials) ?>
                        </div>
                        <div>
                          <span class="d-block fw-bold text-dark"><?= htmlspecialchars($uFullName, ENT_QUOTES, 'UTF-8') ?></span>
                          <span class="extra-small text-muted font-monospace">UID #<?= esc($user['id']) ?></span>
                        </div>
                      </div>
                    </td>

                    <!-- Email -->
                    <td>
                      <a href="mailto:<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none small text-dark d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-envelope text-muted"></i>
                        <span><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></span>
                      </a>
                    </td>

                    <!-- Department -->
                    <td>
                      <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                        <?= htmlspecialchars($user['department'] ?? 'Institutional', ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>

                    <!-- Role -->
                    <td>
                      <span class="badge <?= esc($badgeClass) ?> rounded-pill px-2.5 py-1 small fw-semibold">
                        <?= esc($roleLabel) ?>
                      </span>
                    </td>

                    <!-- Status -->
                    <td>
                      <?php if ((int)$user['is_active'] === 1): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1">
                          <i class="bi bi-check-circle-fill"></i> Active
                        </span>
                      <?php else: ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold d-inline-flex align-items-center gap-1">
                          <i class="bi bi-x-circle-fill"></i> Inactive
                        </span>
                      <?php endif; ?>
                    </td>

                    <!-- Last Login -->
                    <td>
                      <?php if (!empty($user['last_login'])): ?>
                        <div class="small fw-medium text-dark d-flex align-items-center gap-1">
                          <i class="bi bi-clock-history text-muted extra-small"></i>
                          <?= date('M d, Y', strtotime($user['last_login'])) ?>
                        </div>
                        <div class="extra-small text-muted"><?= date('g:i A', strtotime($user['last_login'])) ?></div>
                      <?php else: ?>
                        <span class="extra-small text-muted fst-italic">Never logged in</span>
                      <?php endif; ?>
                    </td>

                    <!-- Actions -->
                    <td class="text-end pe-4">
                      <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-xs dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                          Manage
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                          <li>
                            <button class="dropdown-item edit-user-btn" 
                                    data-id="<?= esc($user['id']) ?>"
                                    data-fname="<?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-lname="<?= htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-email="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-role="<?= esc($user['role']) ?>"
                                    data-dept="<?= htmlspecialchars($user['department'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-perms="<?= htmlspecialchars($user['permissions'] ?? '[]', ENT_QUOTES, 'UTF-8') ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editUserModal">
                              <i class="bi bi-pencil-square me-2 text-primary"></i> Edit Account
                            </button>
                          </li>
                          <li>
                            <a href="user_activity.php?id=<?= esc($user['id']) ?>" class="dropdown-item">
                              <i class="bi bi-clock-history me-2 text-info"></i> View Activity
                            </a>
                          </li>
                          <li>
                            <form action="user_process.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reset this user\'s password to @Admin123?');">
                              <input type="hidden" name="action" value="reset_password">
                              <input type="hidden" name="user_id" value="<?= esc($user['id']) ?>">
                              <?= getCsrfInput() ?>
                              <button type="submit" class="dropdown-item">
                                <i class="bi bi-key-fill me-2 text-warning"></i> Reset Password
                              </button>
                            </form>
                          </li>
                          <li><hr class="dropdown-divider"></li>
                          <li>
                            <form action="user_process.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to change the active status of this user?');">
                              <input type="hidden" name="action" value="toggle_status">
                              <input type="hidden" name="user_id" value="<?= esc($user['id']) ?>">
                              <input type="hidden" name="current_status" value="<?= esc($user['is_active']) ?>">
                              <?= getCsrfInput() ?>
                              <button type="submit" class="dropdown-item <?= esc((int)$user['is_active'] === 1 ? 'text-danger' : 'text-success') ?>">
                                <i class="bi <?= esc((int)$user['is_active'] === 1 ? 'bi-lock-fill' : 'bi-unlock-fill') ?> me-2"></i> 
                                <?= esc((int)$user['is_active'] === 1 ? 'Deactivate Account' : 'Activate Account') ?>
                              </button>
                            </form>
                          </li>
                        </ul>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Pagination Controls in Card Footer -->
      <?php if ($totalPages > 1): ?>
        <div class="dossier-card-footer border-top border-light py-3 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
          <span class="text-muted small">Showing page <strong><?= esc($page) ?></strong> of <strong><?= esc($totalPages) ?></strong> (<?= number_format($totalUsers) ?> total entries)</span>
          <nav aria-label="User Pagination">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item <?= esc(($page <= 1) ? 'disabled' : '') ?>">
                <a class="page-link rounded-start-pill" href="?page=<?= esc($page - 1) ?>&search=<?= esc(urlencode($searchQuery)) ?>&role=<?= esc(urlencode($roleFilter)) ?>">
                  <i class="bi bi-chevron-left me-1"></i> Prev
                </a>
              </li>
              
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= esc(($i === $page) ? 'active' : '') ?>">
                  <a class="page-link" href="?page=<?= esc($i) ?>&search=<?= esc(urlencode($searchQuery)) ?>&role=<?= esc(urlencode($roleFilter)) ?>"><?= esc($i) ?></a>
                </li>
              <?php endfor; ?>
              
              <li class="page-item <?= esc(($page >= $totalPages) ? 'disabled' : '') ?>">
                <a class="page-link rounded-end-pill" href="?page=<?= esc($page + 1) ?>&search=<?= esc(urlencode($searchQuery)) ?>&role=<?= esc(urlencode($roleFilter)) ?>">
                  Next <i class="bi bi-chevron-right ms-1"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>

    </div>
  </div>
</main>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-white border-bottom py-3">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="addUserModalLabel">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle me-3" style="width: 36px; height: 36px;">
            <i class="bi bi-person-plus-fill fs-5"></i>
          </div>
          Create New System Account
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="user_process.php" method="POST">
        <div class="modal-body p-4 bg-light">
          <input type="hidden" name="action" value="create_user">
          <?= getCsrfInput() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">First Name <span class="text-danger">*</span></label>
              <input type="text" name="first_name" class="form-control bg-white" required placeholder="e.g. Maria">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Last Name <span class="text-danger">*</span></label>
              <input type="text" name="last_name" class="form-control bg-white" required placeholder="e.g. Santos">
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold text-dark">Institutional Email Address <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control bg-white" required placeholder="name@ttu.edu.ph">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-dark">Initial Password <span class="text-danger">*</span></label>
              <input type="password" name="password" class="form-control bg-white" required placeholder="Min 8 chars, 1 number">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-dark">Account Role <span class="text-danger">*</span></label>
              <select name="role" id="addRoleSelect" class="form-select bg-white" required onchange="toggleFacultyAddFields(this.value)">
                <option value="applicant">Applicant</option>
                <option value="admin">Registrar</option>
                <option value="scheduler">Scheduler</option>
                <option value="admissions">Admissions Officer</option>
                <option value="scholarship">Scholarship Officer</option>
                <option value="cashier">Cashier</option>
                <option value="clinic">Clinic Officer</option>
                <option value="faculty">Faculty</option>
                <option value="superadmin">Super Administrator</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold text-dark">Assigned Department</label>
              <select name="department" class="form-select bg-white">
                <option value="">None / Institutional</option>
                <option value="System Administration">System Administration</option>
                <option value="Registrar">Registrar</option>
                <option value="Admissions">Admissions</option>
                <option value="Finance">Finance</option>
                <option value="Scholarship">Scholarship</option>
                <option value="Health Services">Health Services</option>
              </select>
            </div>
            <div class="col-md-6 d-none" id="addFacultyEmpIdCol">
              <label class="form-label small fw-bold text-dark">Employee ID</label>
              <input type="text" name="employee_id" class="form-control bg-white" placeholder="e.g. FAC-2026-004">
            </div>
            <div class="col-md-6 d-none" id="addFacultyRankCol">
              <label class="form-label small fw-bold text-dark">Academic Rank</label>
              <select name="academic_rank" class="form-select bg-white">
                <option value="Instructor I">Instructor I</option>
                <option value="Instructor II">Instructor II</option>
                <option value="Assistant Professor">Assistant Professor</option>
                <option value="Associate Professor">Associate Professor</option>
                <option value="Professor">Professor</option>
              </select>
            </div>
            <div class="col-12 mt-3">
              <label class="form-label small fw-bold text-dark border-bottom pb-2 w-100">Custom Role Overrides / Permissions</label>
              <div class="row g-2 small">
                <?php 
                $allPerms = [
                    'students.view', 'students.edit', 'programs.manage', 'subjects.manage', 
                    'curriculum.manage', 'shs_curriculum.manage', 'college_curriculum.manage', 
                    'sections.manage', 'shs_sections.manage', 'college_sections.manage', 'schedules.manage', 'enrollment.finalize',
                    'applications.view_queue', 'applications.view_details', 'applications.review', 
                    'documents.verify', 'medical.review', 'fees.manage', 'assessments.generate', 
                    'payments.record', 'receipts.print', 'scholarships.manage', 
                    'scholarship_applications.review', 'users.manage', 'settings.manage', 'reports.view'
                ];
                foreach ($allPerms as $p): ?>
                <div class="col-md-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= esc($p) ?>" id="perm_add_<?= esc(str_replace('.', '_', $p)) ?>">
                    <label class="form-check-label text-muted" for="perm_add_<?= esc(str_replace('.', '_', $p)) ?>">
                      <?= htmlspecialchars($p) ?>
                    </label>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 bg-light d-flex justify-content-between">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold">
            <i class="bi bi-check-circle me-1"></i> Create Account
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header bg-white border-bottom py-3">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="editUserModalLabel">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle me-3" style="width: 36px; height: 36px;">
            <i class="bi bi-pencil-square fs-5"></i>
          </div>
          Edit User Profile & Permissions
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="user_process.php" method="POST">
        <div class="modal-body p-4 bg-light">
          <input type="hidden" name="action" value="update_user">
          <?= getCsrfInput() ?>
          <input type="hidden" name="user_id" id="editUserId">
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">First Name <span class="text-danger">*</span></label>
              <input type="text" name="first_name" id="editFirstName" class="form-control bg-white" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Last Name <span class="text-danger">*</span></label>
              <input type="text" name="last_name" id="editLastName" class="form-control bg-white" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold text-dark">Email Address <span class="text-danger">*</span></label>
              <input type="email" name="email" id="editEmail" class="form-control bg-white" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Account Role <span class="text-danger">*</span></label>
              <select name="role" id="editUserRole" class="form-select bg-white" required>
                <option value="applicant">Applicant</option>
                <option value="admin">Registrar</option>
                <option value="scheduler">Scheduler</option>
                <option value="admissions">Admissions Officer</option>
                <option value="scholarship">Scholarship Officer</option>
                <option value="cashier">Cashier</option>
                <option value="clinic">Clinic Officer</option>
                <option value="faculty">Faculty</option>
                <option value="superadmin">Super Administrator</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Department</label>
              <select name="department" id="editUserDepartment" class="form-select bg-white">
                <option value="">None / Institutional</option>
                <option value="System Administration">System Administration</option>
                <option value="Registrar">Registrar</option>
                <option value="Admissions">Admissions</option>
                <option value="Finance">Finance</option>
                <option value="Scholarship">Scholarship</option>
                <option value="Health Services">Health Services</option>
              </select>
            </div>
            <div class="col-12 mt-3">
              <label class="form-label small fw-bold text-dark border-bottom pb-2 w-100">Custom Role Overrides / Permissions</label>
              <div class="row g-2 small">
                <?php foreach ($allPerms as $p): ?>
                <div class="col-md-4">
                  <div class="form-check">
                    <input class="form-check-input edit-perm-checkbox" type="checkbox" name="permissions[]" value="<?= esc($p) ?>" id="perm_edit_<?= esc(str_replace('.', '_', $p)) ?>">
                    <label class="form-check-label text-muted" for="perm_edit_<?= esc(str_replace('.', '_', $p)) ?>">
                      <?= htmlspecialchars($p) ?>
                    </label>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="col-12 mt-4">
              <div class="alert alert-warning border-0 p-3 mb-0 rounded-3 shadow-xs">
                <p class="small fw-bold mb-1 text-dark"><i class="bi bi-key-fill me-1 text-warning"></i> Set Custom Password</p>
                <p class="extra-small text-muted mb-2">Leave blank if you do not want to modify the current password.</p>
                <input type="text" name="new_password" class="form-control bg-white" placeholder="Type new password to override">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0 bg-light d-flex justify-content-between">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold">
            <i class="bi bi-check-circle me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function() {
    // Delegated Edit Button Event Listener (SPA Safe, Vanilla JS)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.edit-user-btn');
        if (btn) {
            const uid = btn.getAttribute('data-id') || '';
            const fname = btn.getAttribute('data-fname') || '';
            const lname = btn.getAttribute('data-lname') || '';
            const email = btn.getAttribute('data-email') || '';
            const role = btn.getAttribute('data-role') || '';
            const dept = btn.getAttribute('data-dept') || '';
            let perms = [];
            try {
                perms = JSON.parse(btn.getAttribute('data-perms') || '[]');
            } catch(err) {
                perms = [];
            }

            const idEl = document.getElementById('editUserId');
            const fnameEl = document.getElementById('editFirstName');
            const lnameEl = document.getElementById('editLastName');
            const emailEl = document.getElementById('editEmail');
            const roleEl = document.getElementById('editUserRole');
            const deptEl = document.getElementById('editUserDepartment');

            if (idEl) idEl.value = uid;
            if (fnameEl) fnameEl.value = fname;
            if (lnameEl) lnameEl.value = lname;
            if (emailEl) emailEl.value = email;
            if (roleEl) roleEl.value = role;
            if (deptEl) deptEl.value = dept;

            // Reset all perm checkboxes
            document.querySelectorAll('.edit-perm-checkbox').forEach(cb => {
                cb.checked = perms.includes(cb.value);
            });
        }
    });

    window.toggleFacultyAddFields = function(role) {
        const isFac = (role === 'faculty');
        const empCol = document.getElementById('addFacultyEmpIdCol');
        const rankCol = document.getElementById('addFacultyRankCol');
        if (empCol) empCol.classList.toggle('d-none', !isFac);
        if (rankCol) rankCol.classList.toggle('d-none', !isFac);
    };
})();
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
