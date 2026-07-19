<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('*');

$pageTitle = 'User Management - Administrator';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../components/navbar.php';

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$searchQuery = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? 'all');

$whereClauses = [];
$params = [];

if ($searchQuery !== '') {
    $whereClauses[] = '(email LIKE :search OR first_name LIKE :search OR last_name LIKE :search)';
    $params[':search'] = '%' . $searchQuery . '%';
}

if ($roleFilter !== 'all' && in_array($roleFilter, ['applicant', 'superadmin', 'admissions', 'scholarship', 'cashier'])) {
    $whereClauses[] = 'role = :role';
    $params[':role'] = $roleFilter;
}

$whereSQL = '';
if (!empty($whereClauses)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
}

$users = [];
$totalUsers = 0;

try {
    // Count total users
    $countStmt = $pdo->prepare("SELECT COUNT(id) FROM users $whereSQL");
    $countStmt->execute($params);
    $totalUsers = (int) $countStmt->fetchColumn();

    // Fetch users
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, role, department, permissions, is_active, last_login, created_at FROM users $whereSQL ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('User fetch failed: ' . $e->getMessage());
}

$totalPages = ceil($totalUsers / $limit);

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <div class="island island-hero mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">User Directory</h1>
        <p class="text-muted mb-0">Manage system access, reset credentials, and assign administrative roles.</p>
      </div>
      <div>
        <button type="button" class="btn btn-primary fw-medium shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addUserModal">
          <i class="bi bi-person-plus-fill me-1"></i> Add User
        </button>
      </div>
    </div>

    <?php if ($successMsg): ?>
      <div class="alert alert-success shadow-sm rounded-12"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger shadow-sm rounded-12"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="island position-relative overflow-hidden border-0 shadow-sm rounded-4">
      <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
      <div class="island-header border-bottom border-light d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center">
          <i class="bi bi-people-fill text-primary"></i>
          <h2 class="mb-0 text-dark">Registered Accounts</h2>
        </div>
        
        <!-- Search and Filter Form -->
        <form action="users.php" method="GET" class="d-flex gap-2 align-items-center flex-wrap">
          <select name="role" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
            <option value="all" <?= $roleFilter === 'all' ? 'selected' : '' ?>>All Roles</option>
            <option value="applicant" <?= $roleFilter === 'applicant' ? 'selected' : '' ?>>Applicants</option>
            <option value="superadmin" <?= $roleFilter === 'superadmin' ? 'selected' : '' ?>>Super Administrators</option>
            <option value="admissions" <?= $roleFilter === 'admissions' ? 'selected' : '' ?>>Admissions Officers</option>
            <option value="scholarship" <?= $roleFilter === 'scholarship' ? 'selected' : '' ?>>Scholarship Officers</option>
            <option value="cashier" <?= $roleFilter === 'cashier' ? 'selected' : '' ?>>Cashiers</option>
          </select>
          <div class="input-group input-group-sm shadow-sm" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="tableSearch" name="search" class="form-control border-start-0" placeholder="Search accounts..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <?php if ($searchQuery !== '' || $roleFilter !== 'all'): ?>
            <a href="users.php" class="btn btn-sm btn-outline-secondary">Clear</a>
          <?php endif; ?>
        </form>
      </div>
      
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light">
              <tr>
                <th scope="col" class="ps-4">Name</th>
                <th scope="col">Email Address</th>
                <th scope="col">Department</th>
                <th scope="col">Role</th>
                <th scope="col">Status</th>
                <th scope="col">Last Login</th>
                <th scope="col" class="text-end pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($users)): ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-person-x fs-1 d-block mb-3 text-secondary"></i>
                    No user accounts found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($users as $user): ?>
                  <tr>
                    <td class="ps-4">
                      <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                          <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                        </div>
                        <div>
                          <span class="d-block fw-bold text-dark"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <a href="mailto:<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none small text-muted">
                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>
                      </a>
                    </td>
                    <td>
                      <span class="small text-muted fw-medium"><?= htmlspecialchars($user['department'] ?? 'None', ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td>
                      <?php 
                        $badgeClass = match($user['role']) {
                            'superadmin' => 'bg-danger',
                            'admissions' => 'bg-primary',
                            'scholarship' => 'bg-success',
                            'cashier' => 'bg-info text-dark',
                            default => 'bg-secondary'
                        };
                        $roleLabel = match($user['role']) {
                            'superadmin' => 'Superadmin',
                            'admissions' => 'Admissions',
                            'scholarship' => 'Scholarship',
                            'cashier' => 'Cashier',
                            default => 'Applicant'
                        };
                      ?>
                      <span class="badge <?= $badgeClass ?> rounded-pill px-3"><?= $roleLabel ?></span>
                    </td>
                    <td>
                      <?php if ((int)$user['is_active'] === 1): ?>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3"><i class="bi bi-check-circle me-1"></i>Active</span>
                      <?php else: ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3"><i class="bi bi-x-circle me-1"></i>Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($user['last_login']): ?>
                        <span class="small text-muted" title="<?= date('M j, Y H:i:s', strtotime($user['last_login'])) ?>">
                          <?= date('M j, Y H:i', strtotime($user['last_login'])) ?>
                        </span>
                      <?php else: ?>
                        <span class="small text-muted fst-italic">Never</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                      <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                          <i class="bi bi-three-dots-vertical"></i> Manage
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                          <li>
                            <button class="dropdown-item edit-user-btn" 
                                    data-id="<?= $user['id'] ?>"
                                    data-fname="<?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-lname="<?= htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-email="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-role="<?= $user['role'] ?>"
                                    data-dept="<?= htmlspecialchars($user['department'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-perms="<?= htmlspecialchars($user['permissions'] ?? '[]', ENT_QUOTES, 'UTF-8') ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editUserModal">
                              <i class="bi bi-pencil-square me-2 text-secondary"></i> Edit User
                            </button>
                          </li>
                          <li>
                            <a href="user_activity.php?id=<?= $user['id'] ?>" class="dropdown-item">
                              <i class="bi bi-clock-history me-2 text-info"></i> View Activity
                            </a>
                          </li>
                          <li>
                            <form action="user_process.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reset this user\'s password to @Admin123?');">
                              <input type="hidden" name="action" value="reset_password">
                              <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
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
                              <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                              <input type="hidden" name="current_status" value="<?= $user['is_active'] ?>">
                              <?= getCsrfInput() ?>
                              <button type="submit" class="dropdown-item <?= (int)$user['is_active'] === 1 ? 'text-danger' : 'text-success' ?>">
                                <i class="bi <?= (int)$user['is_active'] === 1 ? 'bi-lock-fill' : 'bi-unlock-fill' ?> me-2"></i> 
                                <?= (int)$user['is_active'] === 1 ? 'Deactivate Account' : 'Activate Account' ?>
                              </button>
                            </form>
                          </li>
                        </ul>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              <tr id="noResultsRow" style="display: none;">
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="bi bi-search fs-1 d-block mb-3 text-secondary"></i>
                  No accounts on this page match your search.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Pagination Controls -->
      <?php if ($totalPages > 1): ?>
        <div class="island-body border-top border-light py-3 d-flex justify-content-between align-items-center">
          <span class="text-muted small">Showing page <?= $page ?> of <?= $totalPages ?></span>
          <nav aria-label="User Pagination">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchQuery) ?>&role=<?= urlencode($roleFilter) ?>">Previous</a>
              </li>
              
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchQuery) ?>&role=<?= urlencode($roleFilter) ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              
              <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchQuery) ?>&role=<?= urlencode($roleFilter) ?>">Next</a>
              </li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>

    </div>
  </div>
</main>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Create New Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="user_process.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="create_user">
          <?= getCsrfInput() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-dark">First Name</label>
              <input type="text" name="first_name" class="form-control form-control-sm bg-light" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-dark">Last Name</label>
              <input type="text" name="last_name" class="form-control form-control-sm bg-light" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-dark">Email Address</label>
              <input type="email" name="email" class="form-control form-control-sm bg-light" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-dark">Password</label>
              <input type="password" name="password" class="form-control form-control-sm bg-light" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-dark">Account Role</label>
              <select name="role" class="form-select form-select-sm bg-light" required>
                <option value="applicant">Applicant</option>
                <option value="admissions">Admissions Officer</option>
                <option value="scholarship">Scholarship Officer</option>
                <option value="cashier">Cashier</option>
                <option value="superadmin">Super Administrator</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-dark">Department</label>
              <select name="department" class="form-select form-select-sm bg-light">
                <option value="">None / N/A</option>
                <option value="System Administration">System Administration</option>
                <option value="Registrar">Registrar</option>
                <option value="Admissions">Admissions</option>
                <option value="Finance">Finance</option>
                <option value="Scholarship">Scholarship</option>
              </select>
            </div>
            <div class="col-12 mt-3">
              <label class="form-label small fw-semibold text-dark border-bottom pb-2 w-100">Custom Permissions</label>
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
                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $p ?>" id="perm_add_<?= str_replace('.', '_', $p) ?>">
                    <label class="form-check-label text-muted" for="perm_add_<?= str_replace('.', '_', $p) ?>">
                      <?= htmlspecialchars($p) ?>
                    </label>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Create Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Edit Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="user_process.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="update_user">
          <?= getCsrfInput() ?>
          <input type="hidden" name="user_id" id="editUserId">
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-dark">First Name</label>
              <input type="text" name="first_name" id="editFirstName" class="form-control form-control-sm bg-light" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-dark">Last Name</label>
              <input type="text" name="last_name" id="editLastName" class="form-control form-control-sm bg-light" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-dark">Email Address</label>
              <input type="email" name="email" id="editEmail" class="form-control form-control-sm bg-light" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-dark">Account Role</label>
              <select name="role" id="editUserRole" class="form-select form-select-sm bg-light" required>
                <option value="applicant">Applicant</option>
                <option value="admissions">Admissions Officer</option>
                <option value="scholarship">Scholarship Officer</option>
                <option value="cashier">Cashier</option>
                <option value="superadmin">Super Administrator</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-dark">Department</label>
              <select name="department" id="editUserDepartment" class="form-select form-select-sm bg-light">
                <option value="">None / N/A</option>
                <option value="System Administration">System Administration</option>
                <option value="Registrar">Registrar</option>
                <option value="Admissions">Admissions</option>
                <option value="Finance">Finance</option>
                <option value="Scholarship">Scholarship</option>
              </select>
            </div>
            <div class="col-12 mt-3">
              <label class="form-label small fw-semibold text-dark border-bottom pb-2 w-100">Custom Permissions</label>
              <div class="row g-2 small">
                <?php foreach ($allPerms as $p): ?>
                <div class="col-md-4">
                  <div class="form-check">
                    <input class="form-check-input edit-perm-checkbox" type="checkbox" name="permissions[]" value="<?= $p ?>" id="perm_edit_<?= str_replace('.', '_', $p) ?>">
                    <label class="form-check-label text-muted" for="perm_edit_<?= str_replace('.', '_', $p) ?>">
                      <?= htmlspecialchars($p) ?>
                    </label>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="col-12 mt-4">
              <div class="alert alert-warning border-0 p-3 mb-0 rounded-3">
                <p class="small fw-bold mb-2"><i class="bi bi-key-fill me-1"></i> Reset Password</p>
                <input type="text" name="new_password" class="form-control form-control-sm" placeholder="Type new password to reset (leave blank to keep current)">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
  $(document).ready(function() {
    $('.edit-user-btn').on('click', function() {
      $('#editUserId').val($(this).data('id'));
      $('#editFirstName').val($(this).data('fname'));
      $('#editLastName').val($(this).data('lname'));
      $('#editEmail').val($(this).data('email'));
      $('#editUserRole').val($(this).data('role'));
      $('#editUserDepartment').val($(this).data('dept'));
      
      // Reset all checkboxes
      $('.edit-perm-checkbox').prop('checked', false);
      
      // Check the ones user has
      let perms = $(this).data('perms');
      if (perms) {
          $.each(perms, function(i, val) {
              $('input.edit-perm-checkbox[value="' + val + '"]').prop('checked', true);
          });
      }
    });

    const searchInput = document.getElementById('tableSearch');
    if(searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('.custom-table tbody tr');
            let visibleCount = 0;
            let hasDataRows = false;
            
            rows.forEach(row => {
                if(row.id === 'noResultsRow' || row.querySelector('td[colspan]')) return;
                hasDataRows = true;
                
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            const noResultsRow = document.getElementById('noResultsRow');
            if (noResultsRow) {
                noResultsRow.style.display = (visibleCount === 0 && hasDataRows) ? '' : 'none';
            }
        });
    }
  });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

