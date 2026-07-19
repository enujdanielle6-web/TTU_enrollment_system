<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission(['users.manage', 'settings.manage', 'reports.view']);

$pageTitle = 'System Admin Dashboard - Triple T University';
require_once __DIR__ . '/../../components/header.php';

$stats = [
    'total_users' => 0,
    'total_apps' => 0,
    'today_regs' => 0,
    'week_regs' => 0
];

$recent_regs = [];

try {
    // 1. Total Users
    $stmtUsers = $pdo->query('SELECT COUNT(*) FROM users');
    $stats['total_users'] = (int) $stmtUsers->fetchColumn();

    // 2. Total Apps
    $stmtApps = $pdo->query('SELECT COUNT(*) FROM applications');
    $stats['total_apps'] = (int) $stmtApps->fetchColumn();

    // 3. User Reg Stats
    $userStats = $pdo->query('
        SELECT
            COALESCE(SUM(CASE WHEN created_at >= CURDATE() THEN 1 ELSE 0 END), 0) as today_regs,
            COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END), 0) as week_regs
        FROM users
    ')->fetch();

    if ($userStats) {
        $stats['today_regs'] = (int) $userStats['today_regs'];
        $stats['week_regs'] = (int) $userStats['week_regs'];
    }

    // 4. Recent Registrations
    $recentRegsStmt = $pdo->query('
        SELECT id, first_name, last_name, email, role, created_at 
        FROM users 
        ORDER BY created_at DESC LIMIT 8
    ');
    $recent_regs = $recentRegsStmt->fetchAll();

} catch (PDOException $e) {
    error_log('SysAdmin dashboard stats failed: ' . $e->getMessage());
}

?>

<?php require_once __DIR__ . '/../components/navbar.php'; ?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    <div class="island island-hero mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 fw-bold text-dark mb-1">System Admin Dashboard</h1>
          <p class="text-muted mb-0">Monitor system health, manage users, and configure global settings.</p>
        </div>
      </div>
    </div>

    <!-- Health Statistics -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <a href="users.php" class="text-decoration-none">
            <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
              <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
              <div class="mb-3 d-flex justify-content-center">
                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 56px; height: 56px;">
                  <i class="bi bi-people-fill fs-4"></i>
                </div>
              </div>
              <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['total_users'] ?></h2>
              <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Total System Users</p>
            </div>
        </a>
      </div>
      
      <div class="col-md-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-info" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-file-earmark-bar-graph fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['total_apps'] ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">Total App Records</p>
        </div>
      </div>

      <div class="col-md-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-success" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-person-plus fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['today_regs'] ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">New Users Today</p>
        </div>
      </div>

      <div class="col-md-3">
        <div class="island p-4 h-100 text-center position-relative overflow-hidden border-0 shadow-sm rounded-4">
          <div class="position-absolute top-0 start-0 w-100 bg-warning" style="height: 4px;"></div>
          <div class="mb-3 d-flex justify-content-center">
            <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle" style="width: 56px; height: 56px;">
              <i class="bi bi-graph-up fs-4"></i>
            </div>
          </div>
          <h2 class="display-6 fw-bold text-dark mb-1"><?= $stats['week_regs'] ?></h2>
          <p class="text-muted small fw-semibold text-uppercase tracking-wide mb-0">New Users This Week</p>
        </div>
      </div>
    </div>

    <!-- Quick Navigation Links -->
    <div class="island mb-4">
      <div class="island-header border-bottom pb-2">
        <i class="bi bi-compass text-primary"></i>
        <h2>System Shortcuts</h2>
      </div>
      <div class="island-body p-4">
        <div class="row g-3">
          <div class="col-md-3">
            <a href="users.php" class="btn btn-outline-primary w-100 text-start p-3 rounded-12">
              <i class="bi bi-person-badge fs-4 d-block mb-2"></i>
              <span class="fw-bold d-block">User Management</span>
              <span class="small text-muted">Manage roles and accounts</span>
            </a>
          </div>
          <div class="col-md-3">
            <a href="audit_logs.php" class="btn btn-outline-primary w-100 text-start p-3 rounded-12">
              <i class="bi bi-shield-check fs-4 d-block mb-2"></i>
              <span class="fw-bold d-block">Audit Logs</span>
              <span class="small text-muted">Review system activity</span>
            </a>
          </div>
          <div class="col-md-3">
            <a href="reports.php" class="btn btn-outline-primary w-100 text-start p-3 rounded-12">
              <i class="bi bi-bar-chart fs-4 d-block mb-2"></i>
              <span class="fw-bold d-block">Reports & Exports</span>
              <span class="small text-muted">Generate data exports</span>
            </a>
          </div>
          <div class="col-md-3">
            <a href="settings.php" class="btn btn-outline-primary w-100 text-start p-3 rounded-12">
              <i class="bi bi-sliders fs-4 d-block mb-2"></i>
              <span class="fw-bold d-block">System Settings</span>
              <span class="small text-muted">Configure global options</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Registrations -->
    <div class="island">
      <div class="island-header border-bottom pb-2">
        <i class="bi bi-person-lines-fill text-primary"></i>
        <h2>Recent User Registrations</h2>
      </div>
      <div class="island-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-table">
            <thead class="table-light text-muted small text-uppercase">
              <tr>
                <th class="ps-4">Name</th>
                <th>Email</th>
                <th>Role</th>
                <th class="text-end pe-4">Registered On</th>
              </tr>
            </thead>
            <tbody class="border-top-0">
              <?php if (empty($recent_regs)): ?>
              <tr>
                <td colspan="4" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i>
                  No recent registrations.
                </td>
              </tr>
              <?php else: ?>
                <?php foreach ($recent_regs as $reg): ?>
                <tr>
                  <td class="ps-4 fw-medium text-dark"><?= htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($reg['email'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <?php
                      $roleBadge = match($reg['role']) {
                          'superadmin' => 'bg-danger',
                          'admissions' => 'bg-primary',
                          'scholarship' => 'bg-success',
                          'cashier' => 'bg-info text-dark',
                          'admin' => 'bg-warning text-dark',
                          default => 'bg-secondary'
                      };
                    ?>
                    <span class="badge <?= $roleBadge ?> px-2 py-1 rounded-pill small">
                      <?= htmlspecialchars(ucfirst($reg['role']), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td class="text-end pe-4 text-muted small">
                    <?= date('M j, Y g:i A', strtotime($reg['created_at'])) ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

