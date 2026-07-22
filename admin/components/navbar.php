<?php
// admin/components/navbar.php
$current_page = basename($_SERVER['PHP_SELF']);
$baseAdminUrl = substr($_SERVER['SCRIPT_NAME'], 0, strpos($_SERVER['SCRIPT_NAME'], '/admin/') + 7);
?>
<div class="admin-wrapper d-flex min-vh-100">
  
  <!-- Left Sidebar -->
  <aside class="admin-sidebar bg-white d-flex flex-column" id="adminSidebar">
    <!-- Brand / Logo Area -->
    <div class="p-4 border-bottom d-flex align-items-center justify-content-between">
      <a class="text-decoration-none d-flex align-items-center" href="<?= $baseAdminUrl ?>dashboard.php" aria-label="Admin portal home">
        <span class="bg-primary-light text-primary border border-primary border-opacity-10 shadow-sm d-flex align-items-center justify-content-center rounded-circle" style="width: 44px; height: 44px;">
          <i class="bi bi-shield-lock-fill fs-5"></i>
        </span>
        <span class="text-dark fw-bold ms-3 fs-5">Admin Portal</span>
      </a>
      <button class="btn btn-sm btn-light d-lg-none" id="sidebarClose" onclick="document.getElementById('adminSidebar').classList.remove('show'); document.getElementById('sidebarBackdrop').classList.add('d-none');">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-grow-1 p-3 overflow-y-auto">
      <?php if (hasPermission(['applications.view_queue', 'medical.review'])): ?>
      <div class="small fw-bold text-muted text-uppercase mb-2 px-3">Admissions</div>
      <?php endif; ?>
      
      <?php if (hasPermission('applications.view_queue')): ?>
      <a class="nav-link d-flex align-items-center gap-3 <?= ($current_page === 'dashboard.php' || $current_page === 'admissions_dashboard.php') ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>admissions/admissions_dashboard.php">
        <i class="bi bi-grid-1x2 fs-5"></i> Dashboard
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'review.php' || $current_page === 'application_detail.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>admissions/review.php">
        <i class="bi bi-inbox fs-5"></i> Applications
      </a>
      <?php endif; ?>
      
      <?php if (hasPermission('medical.review')): ?>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'medical_clearance.php' || $current_page === 'medical_detail.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>health_officer/medical_clearance.php">
        <i class="bi bi-heart-pulse fs-5"></i> Medical Clearance
      </a>
      <?php endif; ?>

      <?php if (hasPermission(['students.view', 'programs.manage', 'sections.manage'])): ?>
      <div class="small fw-bold text-muted text-uppercase mt-4 mb-2 px-3">Registrar</div>
      <a class="nav-link d-flex align-items-center gap-3 <?= ($current_page === 'dashboard.php' || $current_page === 'registrar_dashboard.php') ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>registrar/registrar_dashboard.php">
        <i class="bi bi-grid-1x2 fs-5"></i> Dashboard
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'students.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>registrar/students.php">
        <i class="bi bi-people fs-5"></i> Students
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'subjects.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>registrar/subjects.php">
        <i class="bi bi-journal-text fs-5"></i> Global Subjects
      </a>

      <div class="small fw-bold text-muted text-uppercase mt-4 mb-2 px-3">Senior High School</div>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'shs_strands.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>registrar/shs_strands.php">
        <i class="bi bi-mortarboard fs-5"></i> Strands
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'shs_curriculum.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>registrar/shs_curriculum.php">
        <i class="bi bi-diagram-3 fs-5"></i> Curriculum
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'shs_sections.php' || $current_page === 'shs_schedule_builder.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>registrar/shs_sections.php">
        <i class="bi bi-diagram-3-fill fs-5"></i> Sections & Schedules
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'shs_enrollment_queue.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>registrar/shs_enrollment_queue.php">
        <i class="bi bi-person-lines-fill fs-5"></i> Enrollments
      </a>

      <div class="small fw-bold text-muted text-uppercase mt-4 mb-2 px-3">College</div>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'college_programs.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>registrar/college_programs.php">
        <i class="bi bi-mortarboard fs-5"></i> Programs
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'college_curriculum.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>registrar/college_curriculum.php">
        <i class="bi bi-diagram-3 fs-5"></i> Curriculum
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'college_sections.php' || $current_page === 'college_schedule_builder.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>registrar/college_sections.php">
        <i class="bi bi-diagram-3-fill fs-5"></i> Sections & Schedules
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'college_enrollment_queue.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>registrar/college_enrollment_queue.php">
        <i class="bi bi-person-lines-fill fs-5"></i> Enrollments
      </a>
      <?php endif; ?>

      <?php if (hasPermission(['scholarships.manage', 'scholarship_applications.review'])): ?>
      <div class="small fw-bold text-muted text-uppercase mt-4 mb-2 px-3">Scholarship</div>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'scholarship_dashboard.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>scholarship/scholarship_dashboard.php">
        <i class="bi bi-pie-chart fs-5"></i> Dashboard
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'scholarships.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>scholarship/scholarships.php">
        <i class="bi bi-award fs-5"></i> Scholarships
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'scholarship_review.php' || $current_page === 'scholarship_detail.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>scholarship/scholarship_review.php">
        <i class="bi bi-file-earmark-text fs-5"></i> Applications
      </a>
      <?php endif; ?>

      <?php if (hasPermission(['fees.manage', 'assessments.generate', 'payments.record'])): ?>
      <div class="small fw-bold text-muted text-uppercase mt-4 mb-2 px-3">Finance</div>
      <a class="nav-link d-flex align-items-center gap-3 <?= ($current_page === 'cashier_dashboard.php' || $current_page === 'cashier_assessment.php') ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>finance/cashier_dashboard.php">
        <i class="bi bi-grid fs-5"></i> Dashboard / Accounts
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'cashier_payments.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>finance/cashier_payments.php">
        <i class="bi bi-cash-stack fs-5"></i> Payment History
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'fees.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>finance/fees.php">
        <i class="bi bi-tags fs-5"></i> Fee Templates
      </a>
      <?php endif; ?>

      <?php if (hasPermission(['*'])): ?>
      <div class="small fw-bold text-muted text-uppercase mt-4 mb-2 px-3">System Admin</div>
      <a class="nav-link d-flex align-items-center gap-3 <?= ($current_page === 'dashboard.php' || $current_page === 'sysadmin_dashboard.php') ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>system/sysadmin_dashboard.php">
        <i class="bi bi-grid-1x2 fs-5"></i> Dashboard
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'users.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>system/users.php">
        <i class="bi bi-person-badge fs-5"></i> User Management
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'reports.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>system/reports.php">
        <i class="bi bi-bar-chart fs-5"></i> Reports
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'settings.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>system/settings.php">
        <i class="bi bi-sliders fs-5"></i> System Settings
      </a>
      <a class="nav-link d-flex align-items-center gap-3 <?= $current_page === 'audit_logs.php' ? 'active' : ''; ?>" href="<?= $baseAdminUrl ?>system/audit_logs.php">
        <i class="bi bi-shield-check fs-5"></i> Audit Logs
      </a>
      <?php endif; ?>
    </nav>

    <!-- User Profile Area -->
    <div class="p-3 border-top bg-light mt-auto">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 42px; height: 42px;">
          <?= strtoupper(substr($_SESSION['user_first_name'] ?? 'A', 0, 1) . substr($_SESSION['user_last_name'] ?? 'D', 0, 1)); ?>
        </div>
        <div class="overflow-hidden">
          <span class="d-block fw-bold text-dark text-truncate" style="font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="text-muted text-truncate d-block" style="font-size: 0.75rem;">
            <?php 
              $dept = $_SESSION['user_department'] ?? '';
              if ($dept) {
                  echo htmlspecialchars(ucfirst($dept) . ' Department', ENT_QUOTES, 'UTF-8');
              } else {
                  echo match($_SESSION['user_role'] ?? '') {
                      'superadmin' => 'System Administrator',
                      'admin' => 'Registrar',
                      'admissions' => 'Admissions Officer',
                      'scholarship' => 'Scholarship Officer',
                      'cashier' => 'Finance Officer',
                      'health_officer' => 'Health Officer',
                      default => 'Administrator'
                  };
              }
            ?>
          </span>
        </div>
      </div>
      <a class="btn btn-outline-danger w-100 btn-sm rounded-pill fw-medium shadow-sm" href="<?= $baseAdminUrl ?>../auth/logout.php">
        <i class="bi bi-box-arrow-right"></i> Sign Out
      </a>
    </div>
  </aside>

  <!-- Main Content Wrapper -->
  <div class="admin-main flex-grow-1 d-flex flex-column">
    
    <!-- Mobile Topbar -->
    <div class="d-lg-none bg-white border-bottom shadow-sm p-3 d-flex align-items-center justify-content-between sticky-top">
      <div class="d-flex align-items-center gap-2">
        <span class="bg-primary-light text-primary border border-primary border-opacity-10 d-flex align-items-center justify-content-center rounded-circle" style="width: 32px; height: 32px;">
          <i class="bi bi-shield-lock-fill fs-6"></i>
        </span>
        <span class="fw-bold text-dark">Admin</span>
      </div>
      <button class="btn btn-light border-0 shadow-sm" id="sidebarToggle">
        <i class="bi bi-list fs-5"></i>
      </button>
    </div>
