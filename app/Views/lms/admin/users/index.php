<?php
$pageTitle = 'LMS User Access Management - Administrator';
require_once __DIR__ . '/../layout_header.php';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up">
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
          <a href="/sia/lms/admin/dashboard" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>LMS Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4 p-3">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div class="small fw-semibold"><?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4 p-3">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
        <div class="small fw-semibold"><?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Filter Bar Card -->
    <div class="dossier-card mb-4 p-3.5 bg-white shadow-sm border-0 rounded-4">
      <form action="/sia/lms/admin/users" method="GET" class="row g-2.5 align-items-center">
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-light border-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control bg-light border-0 shadow-none small" placeholder="Search by name, student #, employee ID, or email..." value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>

        <div class="col-sm-6 col-md-2">
          <select name="role" class="form-select bg-light border-0 shadow-none small">
            <option value="">All Roles</option>
            <option value="student" <?= ($filters['role'] ?? '') === 'student' ? 'selected' : '' ?>>Students</option>
            <option value="faculty" <?= ($filters['role'] ?? '') === 'faculty' ? 'selected' : '' ?>>Faculty Instructors</option>
            <option value="admin" <?= ($filters['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrators</option>
          </select>
        </div>

        <div class="col-sm-6 col-md-2">
          <select name="lms_status" class="form-select bg-light border-0 shadow-none small">
            <option value="">All LMS Statuses</option>
            <option value="active" <?= ($filters['lms_status'] ?? '') === 'active' ? 'selected' : '' ?>>Active Access</option>
            <option value="suspended" <?= ($filters['lms_status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended Access</option>
            <option value="inactive" <?= ($filters['lms_status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <div class="col-sm-6 col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary rounded-pill px-3 w-100 fw-semibold shadow-xs">
            <i class="bi bi-filter me-1"></i> Filter
          </button>
          <a href="/sia/lms/admin/users" class="btn btn-light border rounded-pill px-3 shadow-xs">Reset</a>
        </div>
      </form>
    </div>

    <!-- Users Table Card -->
    <div class="dossier-card mb-4 overflow-hidden border-0 shadow-sm">
      <div class="dossier-card-header bg-white border-bottom p-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 32px; height: 32px;">
            <i class="bi bi-people-fill"></i>
          </div>
          <div>
            <h6 class="fw-bold text-dark mb-0">LMS User Access Directory</h6>
            <div class="text-muted small" style="font-size: 0.75rem;">Platform role allocations & isolation states</div>
          </div>
        </div>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 small fw-semibold">
          <?= (int)$totalUsers ?> Active Records
        </span>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 dashboard-table">
          <thead>
            <tr>
              <th class="ps-4">User Details</th>
              <th>System Role</th>
              <th>Identifier (ID)</th>
              <th>Email Address</th>
              <th class="text-center">Active Shells</th>
              <th>LMS Access Status</th>
              <th class="text-end pe-4">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <div class="py-4">
                    <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    <div class="fw-bold text-dark">No LMS Users Found</div>
                    <div class="small text-muted mt-1">No accounts match your current filter parameters.</div>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($users as $u): 
                $userName = trim($u['first_name'] . ' ' . $u['last_name']);
                $initial = strtoupper(substr($u['first_name'] ?? 'U', 0, 1));
                $avatarBg = ($u['role'] === 'student') ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : (($u['role'] === 'faculty') ? 'background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);' : 'background: linear-gradient(135deg, #475569 0%, #1e293b 100%);');
              ?>
                <tr>
                  <td class="ps-4">
                    <div class="d-flex align-items-center gap-2.5">
                      <div class="applicant-avatar text-white fw-bold shadow-xs flex-shrink-0" style="<?= $avatarBg ?> width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.82rem;">
                        <?= esc($initial) ?>
                      </div>
                      <div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted small" style="font-size: 0.72rem;">User ID #<?= (int)$u['id'] ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <?php if ($u['role'] === 'student'): ?>
                      <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small">
                        <i class="bi bi-mortarboard me-1"></i> Student
                      </span>
                    <?php elseif ($u['role'] === 'faculty'): ?>
                      <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small">
                        <i class="bi bi-person-video3 me-1"></i> Faculty
                      </span>
                    <?php else: ?>
                      <span class="badge bg-dark bg-opacity-10 text-dark border border-secondary border-opacity-25 rounded-pill px-2.5 py-1 small">
                        <?= htmlspecialchars(ucfirst($u['role']), ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="applicant-ref-badge">
                      <i class="bi bi-fingerprint text-primary me-1"></i><?= htmlspecialchars($u['student_number'] ?: $u['employee_id'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td class="small text-muted">
                    <i class="bi bi-envelope text-secondary me-1"></i><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 fw-semibold">
                      <i class="bi bi-collection text-primary me-1"></i> <?= (int)$u['active_courses_count'] ?> Shells
                    </span>
                  </td>
                  <td>
                    <?php if ($u['lms_status'] === 'active'): ?>
                      <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small d-inline-flex align-items-center gap-1">
                        <span class="pulse-dot-green" style="width: 6px; height: 6px;"></span> Active Access
                      </span>
                    <?php elseif ($u['lms_status'] === 'suspended'): ?>
                      <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 small">
                        <i class="bi bi-slash-circle me-1"></i> Suspended
                      </span>
                    <?php else: ?>
                      <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2.5 py-1 small">
                        Inactive
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end pe-4">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-xs hover-lift" data-bs-toggle="modal" data-bs-target="#userStatusModal<?= (int)$u['id'] ?>">
                      <i class="bi bi-shield-lock me-1"></i> Access
                    </button>

                    <!-- User Status Modal -->
                    <div class="modal fade" id="userStatusModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true" style="text-align: left;">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                          <form action="/sia/lms/admin/users/<?= (int)$u['id'] ?>/status" method="POST">
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
                <a class="page-link" href="/sia/lms/admin/users?page=<?= $p ?>&search=<?= urlencode($filters['search'] ?? '') ?>&role=<?= urlencode($filters['role'] ?? '') ?>&lms_status=<?= urlencode($filters['lms_status'] ?? '') ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
