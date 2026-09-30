<?php
$pageTitle = 'LMS User Access Management - Administrator';
require_once __DIR__ . '/../../../components/header.php';
require_once __DIR__ . '/../../../components/admin_navbar.php';
require_once __DIR__ . '/../components/lms_header.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-person-badge-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">LMS User Access Governance</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                Total: <?= (int)$totalUsers ?> Accounts
              </span>
            </div>
            <p class="text-muted small mb-0">Manage LMS platform access states (Active, Suspended, Inactive) without corrupting official Registrar enrollment identity.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="/sia/admin/lms/dashboard" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>LMS Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div><?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
        <div><?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Filter Bar Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
      <form action="/sia/admin/lms/users" method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control bg-light border-start-0 shadow-none" placeholder="Search by name, student #, employee ID, or email..." value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>

        <div class="col-sm-6 col-md-2">
          <select name="role" class="form-select bg-light shadow-none">
            <option value="">All Roles</option>
            <option value="student" <?= ($filters['role'] ?? '') === 'student' ? 'selected' : '' ?>>Students</option>
            <option value="faculty" <?= ($filters['role'] ?? '') === 'faculty' ? 'selected' : '' ?>>Faculty Instructors</option>
            <option value="admin" <?= ($filters['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrators</option>
          </select>
        </div>

        <div class="col-sm-6 col-md-2">
          <select name="lms_status" class="form-select bg-light shadow-none">
            <option value="">All LMS Statuses</option>
            <option value="active" <?= ($filters['lms_status'] ?? '') === 'active' ? 'selected' : '' ?>>Active Access</option>
            <option value="suspended" <?= ($filters['lms_status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended Access</option>
            <option value="inactive" <?= ($filters['lms_status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <div class="col-sm-6 col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary rounded-pill px-3 w-100">Filter</button>
          <a href="/sia/admin/lms/users" class="btn btn-light border rounded-pill px-3">Reset</a>
        </div>
      </form>
    </div>

    <!-- Users Table Card -->
    <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden mb-4">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light text-muted small text-uppercase">
            <tr>
              <th class="ps-4">User</th>
              <th>Role</th>
              <th>Identifier</th>
              <th>Email</th>
              <th class="text-center">Active Courses</th>
              <th>LMS Status</th>
              <th class="text-end pe-4">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                  No users found matching current filters.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td class="ps-4">
                    <div class="fw-bold text-dark"><?= htmlspecialchars($u['last_name'] . ', ' . $u['first_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-muted small" style="font-size: 0.75rem;">UID #<?= (int)$u['id'] ?></div>
                  </td>
                  <td>
                    <?php if ($u['role'] === 'student'): ?>
                      <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-0.5">Student</span>
                    <?php elseif ($u['role'] === 'faculty'): ?>
                      <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2.5 py-0.5">Faculty</span>
                    <?php else: ?>
                      <span class="badge bg-dark bg-opacity-10 text-dark rounded-pill px-2.5 py-0.5"><?= htmlspecialchars(ucfirst($u['role']), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <code><?= htmlspecialchars($u['student_number'] ?: $u['employee_id'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></code>
                  </td>
                  <td class="small text-muted"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-center">
                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                      <?= (int)$u['active_courses_count'] ?> Courses
                    </span>
                  </td>
                  <td>
                    <?php if ($u['lms_status'] === 'active'): ?>
                      <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1">
                        <i class="bi bi-check-circle me-1"></i> Active
                      </span>
                    <?php elseif ($u['lms_status'] === 'suspended'): ?>
                      <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2.5 py-1">
                        <i class="bi bi-slash-circle me-1"></i> Suspended
                      </span>
                    <?php else: ?>
                      <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2.5 py-1">
                        Inactive
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end pe-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-xs" data-bs-toggle="modal" data-bs-target="#userStatusModal<?= (int)$u['id'] ?>">
                      <i class="bi bi-shield-lock me-1"></i> Access
                    </button>

                    <!-- User Status Modal -->
                    <div class="modal fade" id="userStatusModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true" style="text-align: left;">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                          <form action="/sia/admin/lms/users/<?= (int)$u['id'] ?>/status" method="POST">
                            <?= getCsrfInput() ?>
                            <div class="modal-header bg-light border-bottom p-3.5 px-4">
                              <h5 class="modal-title fw-bold text-dark">
                                <i class="bi bi-shield-lock text-primary me-2"></i> Update LMS Access: <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name'], ENT_QUOTES, 'UTF-8') ?>
                              </h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                              <div class="p-3 bg-info bg-opacity-10 border border-info border-opacity-25 rounded-3 mb-3 small text-muted">
                                <i class="bi bi-info-circle-fill text-info me-1"></i>
                                <strong>LMS Access Isolation Rule:</strong> Modifying LMS status suspends or enables learning portal access only. It does <em>not</em> alter registrar enrollment, assessments, or user identity.
                              </div>

                              <div class="mb-3">
                                <label class="form-label small fw-semibold">LMS Access Status</label>
                                <select name="lms_status" class="form-select rounded-3 shadow-none" required>
                                  <option value="active" <?= $u['lms_status'] === 'active' ? 'selected' : '' ?>>Active (Full learning platform access)</option>
                                  <option value="suspended" <?= $u['lms_status'] === 'suspended' ? 'selected' : '' ?>>Suspended (Blocked from course participation)</option>
                                  <option value="inactive" <?= $u['lms_status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Offboarded / Non-participating)</option>
                                </select>
                              </div>

                              <div class="mb-2">
                                <label class="form-label small fw-semibold">Reason for Modification</label>
                                <input type="text" name="reason" class="form-control rounded-3 shadow-none" placeholder="e.g. Disciplinary hold, Clearance review, Term restoration...">
                              </div>
                            </div>
                            <div class="modal-footer border-top bg-light p-3 px-4">
                              <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top d-flex justify-content-between align-items-center">
          <span class="text-muted small">Showing Page <?= $currentPage ?> of <?= $totalPages ?> (Total: <?= $totalUsers ?>)</span>
          <ul class="pagination pagination-sm mb-0">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="/sia/admin/lms/users?page=<?= $p ?>&search=<?= urlencode($filters['search'] ?? '') ?>&role=<?= urlencode($filters['role'] ?? '') ?>&lms_status=<?= urlencode($filters['lms_status'] ?? '') ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../../../components/footer.php'; ?>
