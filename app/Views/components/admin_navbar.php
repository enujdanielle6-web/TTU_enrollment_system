<?php
// admin/components/navbar.php
$current_page = basename($_SERVER['REQUEST_URI']);
if (($pos = strpos($current_page, '?')) !== false) {
    $current_page = substr($current_page, 0, $pos);
}
$baseAdminUrl = '/sia/admin/';
?>
<div class="admin-wrapper d-flex min-vh-100">
  
  <!-- Left Sidebar -->
  <aside class="admin-sidebar bg-white d-flex flex-column" id="adminSidebar">
    
    <!-- Floating toggle button -->
    <button class="btn btn-primary rounded-circle position-absolute d-none d-lg-flex align-items-center justify-content-center shadow-sm sidebar-minimize-btn" id="sidebarMinimize" style="width: 28px; height: 28px; top: 28px; right: -14px; z-index: 1050; padding: 0;">
      <i class="bi bi-chevron-left toggle-icon" style="font-size: 14px;"></i>
    </button>
    <!-- Brand / Logo Area -->
    <div class="p-4 border-bottom d-flex align-items-center justify-content-between">
      <a class="text-decoration-none d-flex align-items-center" href="<?= esc($baseAdminUrl) ?>dashboard.php" aria-label="Admin portal home">
        <img src="<?= esc($baseAdminUrl) ?>../images/TTU_LOGO.png" alt="TTU Logo" style="height: 36px; width: auto; object-fit: contain;">
        <span class="text-dark fw-bold ms-3 fs-5 nav-text">Admin Portal</span>
      </a>
      <button class="btn btn-sm btn-light d-lg-none" id="sidebarClose" onclick="document.getElementById('adminSidebar').classList.remove('show'); document.getElementById('sidebarBackdrop').classList.add('d-none');">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- Navigation Links -->
<nav class="flex-grow-1 p-3 overflow-y-auto accordion" id="adminSidebarNav">
  <?php 
  $uri = $_SERVER['REQUEST_URI'];
  $isAdmissions = strpos($uri, '/admissions/') !== false || $current_page === 'dashboard.php';
  $isClinic = strpos($uri, '/clinic/') !== false;
  $isRegistrarCore = in_array($current_page, ['registrar_dashboard.php', 'students.php', 'subjects.php']);
  $isShs = strpos($current_page, 'shs_') === 0;
  $isCollege = strpos($current_page, 'college_') === 0;
  $isScheduler = strpos($uri, '/scheduler/') !== false;
  $isScholarship = strpos($uri, '/scholarship/') !== false;
  $isFinance = strpos($uri, '/finance/') !== false;
  $isSysAdmin = strpos($uri, '/system/') !== false;
  ?>

  <?php if (hasPermission(['applications.view_queue'])): ?>
  <button title="Admissions" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.1s;" class="nav-link w-100 fade-in-left text-start d-flex align-items-center justify-content-between sidebar-toggle <?= esc($isAdmissions ? '' : 'collapsed') ?> mb-2" data-bs-toggle="collapse" data-bs-target="#collapseAdmissions" aria-expanded="<?= esc($isAdmissions ? 'true' : 'false') ?>">
    <div class="d-flex align-items-center gap-3 toggle-content"><i class="bi bi-folder2-open fs-5 text-muted"></i><span class="small fw-bold text-muted text-uppercase nav-text">Admissions</span></div>
    <i class="bi bi-chevron-down transition-transform text-muted"></i>
  </button>
  <div class="collapse <?= esc($isAdmissions ? 'show' : '') ?>" id="collapseAdmissions">
    <div class="ms-3 mb-3 border-start border-2 ps-3">
      <a title="Dashboard" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.1s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc(($current_page === 'dashboard.php' || $current_page === 'admissions_dashboard.php') ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>admissions/admissions_dashboard.php">
        <i class="bi bi-grid-1x2 fs-5"></i> <span class="nav-text">Dashboard</span>
      </a>
      <a title="Applications" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.15s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'review.php' || $current_page === 'application_detail.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>admissions/review.php">
        <i class="bi bi-inbox fs-5"></i> <span class="nav-text">Applications</span>
      </a>
    </div>
  </div>
  <?php endif; ?>

  <?php if (hasPermission('medical.review')): ?>
  <button title="University Clinic" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.15s;" class="nav-link w-100 fade-in-left text-start d-flex align-items-center justify-content-between sidebar-toggle <?= esc($isClinic ? '' : 'collapsed') ?> mb-2" data-bs-toggle="collapse" data-bs-target="#collapseClinic" aria-expanded="<?= esc($isClinic ? 'true' : 'false') ?>">
    <div class="d-flex align-items-center gap-3 toggle-content"><i class="bi bi-bandaid fs-5 text-muted"></i><span class="small fw-bold text-muted text-uppercase nav-text">University Clinic</span></div>
    <i class="bi bi-chevron-down transition-transform text-muted"></i>
  </button>
  <div class="collapse <?= esc($isClinic ? 'show' : '') ?>" id="collapseClinic">
    <div class="ms-3 mb-3 border-start border-2 ps-3">
      <a title="Clinic Dashboard" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.1s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc(($current_page === 'dashboard.php' || $current_page === 'clinic_dashboard.php') ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>clinic/clinic_dashboard.php">
        <i class="bi bi-heart-pulse fs-5"></i> <span class="nav-text">Clinic Dashboard</span>
      </a>
      <a title="Medical Clearance" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.15s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'medical_clearance.php' || $current_page === 'medical_detail.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>clinic/medical_clearance.php">
        <i class="bi bi-file-medical fs-5"></i> <span class="nav-text">Medical Clearance</span>
      </a>
    </div>
  </div>
  <?php endif; ?>

  <?php if (hasPermission(['students.view', 'programs.manage'])): ?>
  <button title="Registrar" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.2s;" class="nav-link w-100 fade-in-left text-start d-flex align-items-center justify-content-between sidebar-toggle <?= esc($isRegistrarCore ? '' : 'collapsed') ?> mb-2" data-bs-toggle="collapse" data-bs-target="#collapseRegistrar" aria-expanded="<?= esc($isRegistrarCore ? 'true' : 'false') ?>">
    <div class="d-flex align-items-center gap-3 toggle-content"><i class="bi bi-journal-bookmark fs-5 text-muted"></i><span class="small fw-bold text-muted text-uppercase nav-text">Registrar</span></div>
    <i class="bi bi-chevron-down transition-transform text-muted"></i>
  </button>
  <div class="collapse <?= esc($isRegistrarCore ? 'show' : '') ?>" id="collapseRegistrar">
    <div class="ms-3 mb-3 border-start border-2 ps-3">
      <a title="Dashboard" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.1s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc(($current_page === 'dashboard.php' || $current_page === 'registrar_dashboard.php') ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>registrar/registrar_dashboard.php">
        <i class="bi bi-grid-1x2 fs-5"></i> <span class="nav-text">Dashboard</span>
      </a>
      <a title="Students" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.15s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'students.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>registrar/students.php">
        <i class="bi bi-people fs-5"></i> <span class="nav-text">Students</span>
      </a>
      <a title="Global Subjects" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.2s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'subjects.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>registrar/subjects.php">
        <i class="bi bi-journal-text fs-5"></i> <span class="nav-text">Global Subjects</span>
      </a>
    </div>
  </div>

  <button title="Senior High School" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.25s;" class="nav-link w-100 fade-in-left text-start d-flex align-items-center justify-content-between sidebar-toggle <?= esc($isShs ? '' : 'collapsed') ?> mb-2" data-bs-toggle="collapse" data-bs-target="#collapseShs" aria-expanded="<?= esc($isShs ? 'true' : 'false') ?>">
    <div class="d-flex align-items-center gap-3 toggle-content"><i class="bi bi-backpack fs-5 text-muted"></i><span class="small fw-bold text-muted text-uppercase nav-text">Senior High School</span></div>
    <i class="bi bi-chevron-down transition-transform text-muted"></i>
  </button>
  <div class="collapse <?= esc($isShs ? 'show' : '') ?>" id="collapseShs">
    <div class="ms-3 mb-3 border-start border-2 ps-3">
      <a title="Strands" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.1s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'shs_strands.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>registrar/shs_strands.php">
        <i class="bi bi-mortarboard fs-5"></i> <span class="nav-text">Strands</span>
      </a>
      <a title="Curriculum" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.15s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'shs_curriculum.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>registrar/shs_curriculum.php">
        <i class="bi bi-diagram-3 fs-5"></i> <span class="nav-text">Curriculum</span>
      </a>
      <a title="Enrollments" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.25s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'shs_enrollment_queue.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>registrar/shs_enrollment_queue.php">
        <i class="bi bi-person-lines-fill fs-5"></i> <span class="nav-text">Enrollments</span>
      </a>
    </div>
  </div>

  <button title="College" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.3s;" class="nav-link w-100 fade-in-left text-start d-flex align-items-center justify-content-between sidebar-toggle <?= esc($isCollege ? '' : 'collapsed') ?> mb-2" data-bs-toggle="collapse" data-bs-target="#collapseCollege" aria-expanded="<?= esc($isCollege ? 'true' : 'false') ?>">
    <div class="d-flex align-items-center gap-3 toggle-content"><i class="bi bi-bank fs-5 text-muted"></i><span class="small fw-bold text-muted text-uppercase nav-text">College</span></div>
    <i class="bi bi-chevron-down transition-transform text-muted"></i>
  </button>
  <div class="collapse <?= esc($isCollege ? 'show' : '') ?>" id="collapseCollege">
    <div class="ms-3 mb-3 border-start border-2 ps-3">
      <a title="Programs" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.1s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'college_programs.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>registrar/college_programs.php">
        <i class="bi bi-mortarboard fs-5"></i> <span class="nav-text">Programs</span>
      </a>
      <a title="Curriculum" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.15s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'college_curriculum.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>registrar/college_curriculum.php">
        <i class="bi bi-diagram-3 fs-5"></i> <span class="nav-text">Curriculum</span>
      </a>
      <a title="Enrollments" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.25s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'college_enrollment_queue.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>registrar/college_enrollment_queue.php">
        <i class="bi bi-person-lines-fill fs-5"></i> <span class="nav-text">Enrollments</span>
      </a>
    </div>
  </div>
  <?php endif; ?>

  <?php if (hasPermission(['sections.manage', 'schedules.manage'])): ?>
  <button title="Scheduler" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.35s;" class="nav-link w-100 fade-in-left text-start d-flex align-items-center justify-content-between sidebar-toggle <?= esc($isScheduler ? '' : 'collapsed') ?> mb-2" data-bs-toggle="collapse" data-bs-target="#collapseScheduler" aria-expanded="<?= esc($isScheduler ? 'true' : 'false') ?>">
    <div class="d-flex align-items-center gap-3 toggle-content"><i class="bi bi-calendar-week fs-5 text-muted"></i><span class="small fw-bold text-muted text-uppercase nav-text">Scheduler</span></div>
    <i class="bi bi-chevron-down transition-transform text-muted"></i>
  </button>
  <div class="collapse <?= esc($isScheduler ? 'show' : '') ?>" id="collapseScheduler">
    <div class="ms-3 mb-3 border-start border-2 ps-3">
      <a title="Dashboard" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.1s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc(($current_page === 'dashboard.php' || $current_page === 'scheduler_dashboard.php') ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>scheduler/scheduler_dashboard.php">
        <i class="bi bi-grid-1x2 fs-5"></i> <span class="nav-text">Dashboard</span>
      </a>
      <a title="SHS Sections" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.15s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'shs_sections.php' || (strpos($current_page, 'schedule_builder.php') !== false && isset($_GET['type']) && $_GET['type'] === 'shs') ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>scheduler/shs_sections.php">
        <i class="bi bi-diagram-3-fill fs-5"></i> <span class="nav-text">SHS Sections & Scheds</span>
      </a>
      <a title="College Sections" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.2s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'college_sections.php' || (strpos($current_page, 'schedule_builder.php') !== false && isset($_GET['type']) && $_GET['type'] === 'college') ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>scheduler/college_sections.php">
        <i class="bi bi-diagram-3-fill fs-5"></i> <span class="nav-text">College Sections & Scheds</span>
      </a>
    </div>
  </div>
  <?php endif; ?>

  <?php if (hasPermission(['scholarships.manage', 'scholarship_applications.review'])): ?>
  <button title="Scholarship" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.35s;" class="nav-link w-100 fade-in-left text-start d-flex align-items-center justify-content-between sidebar-toggle <?= esc($isScholarship ? '' : 'collapsed') ?> mb-2" data-bs-toggle="collapse" data-bs-target="#collapseScholarship" aria-expanded="<?= esc($isScholarship ? 'true' : 'false') ?>">
    <div class="d-flex align-items-center gap-3 toggle-content"><i class="bi bi-award fs-5 text-muted"></i><span class="small fw-bold text-muted text-uppercase nav-text">Scholarship</span></div>
    <i class="bi bi-chevron-down transition-transform text-muted"></i>
  </button>
  <div class="collapse <?= esc($isScholarship ? 'show' : '') ?>" id="collapseScholarship">
    <div class="ms-3 mb-3 border-start border-2 ps-3">
      <a title="Dashboard" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.1s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'scholarship_dashboard.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>scholarship/scholarship_dashboard.php">
        <i class="bi bi-pie-chart fs-5"></i> <span class="nav-text">Dashboard</span>
      </a>
      <a title="Scholarships" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.15s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'scholarships.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>scholarship/scholarships.php">
        <i class="bi bi-award fs-5"></i> <span class="nav-text">Scholarships</span>
      </a>
      <a title="Applications" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.2s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'scholarship_review.php' || $current_page === 'scholarship_detail.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>scholarship/scholarship_review.php">
        <i class="bi bi-file-earmark-text fs-5"></i> <span class="nav-text">Applications</span>
      </a>
      <a title="Active Scholars" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.25s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'scholars.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>scholarship/scholars.php">
        <i class="bi bi-people-fill fs-5"></i> <span class="nav-text">Active Scholars</span>
      </a>
    </div>
  </div>
  <?php endif; ?>

  <?php if (hasPermission(['fees.manage', 'assessments.generate', 'payments.record'])): ?>
  <button title="Finance" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.4s;" class="nav-link w-100 fade-in-left text-start d-flex align-items-center justify-content-between sidebar-toggle <?= esc($isFinance ? '' : 'collapsed') ?> mb-2" data-bs-toggle="collapse" data-bs-target="#collapseFinance" aria-expanded="<?= esc($isFinance ? 'true' : 'false') ?>">
    <div class="d-flex align-items-center gap-3 toggle-content"><i class="bi bi-cash-coin fs-5 text-muted"></i><span class="small fw-bold text-muted text-uppercase nav-text">Finance</span></div>
    <i class="bi bi-chevron-down transition-transform text-muted"></i>
  </button>
  <div class="collapse <?= esc($isFinance ? 'show' : '') ?>" id="collapseFinance">
    <div class="ms-3 mb-3 border-start border-2 ps-3">
      <a title="Dashboard / Accounts" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.1s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc(($current_page === 'cashier_dashboard.php' || $current_page === 'cashier_assessment.php') ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>finance/cashier_dashboard.php">
        <i class="bi bi-grid fs-5"></i> <span class="nav-text">Dashboard / Accounts</span>
      </a>
      <a title="Payment History" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.15s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'cashier_payments.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>finance/cashier_payments.php">
        <i class="bi bi-cash-stack fs-5"></i> <span class="nav-text">Payment History</span>
      </a>
      <a title="Fee Templates" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.2s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'fees.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>finance/fees.php">
        <i class="bi bi-tags fs-5"></i> <span class="nav-text">Fee Templates</span>
      </a>
    </div>
  </div>
  <?php endif; ?>

  <?php if (hasPermission(['*'])): ?>
  <button title="System Admin" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.45s;" class="nav-link w-100 fade-in-left text-start d-flex align-items-center justify-content-between sidebar-toggle <?= esc($isSysAdmin ? '' : 'collapsed') ?> mb-2" data-bs-toggle="collapse" data-bs-target="#collapseSysAdmin" aria-expanded="<?= esc($isSysAdmin ? 'true' : 'false') ?>">
    <div class="d-flex align-items-center gap-3 toggle-content"><i class="bi bi-shield-lock fs-5 text-muted"></i><span class="small fw-bold text-muted text-uppercase nav-text">System Admin</span></div>
    <i class="bi bi-chevron-down transition-transform text-muted"></i>
  </button>
  <div class="collapse <?= esc($isSysAdmin ? 'show' : '') ?>" id="collapseSysAdmin">
    <div class="ms-3 mb-3 border-start border-2 ps-3">
      <a title="Dashboard" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.1s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc(($current_page === 'dashboard.php' || $current_page === 'sysadmin_dashboard.php') ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>system/sysadmin_dashboard.php">
        <i class="bi bi-grid-1x2 fs-5"></i> <span class="nav-text">Dashboard</span>
      </a>
      <a title="User Management" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.15s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'users.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>system/users.php">
        <i class="bi bi-person-badge fs-5"></i> <span class="nav-text">User Management</span>
      </a>
      <a title="Reports" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.2s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'reports.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>system/reports.php">
        <i class="bi bi-bar-chart fs-5"></i> <span class="nav-text">Reports</span>
      </a>
      <a title="System Settings" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.25s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'settings.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>system/settings.php">
        <i class="bi bi-sliders fs-5"></i> <span class="nav-text">System Settings</span>
      </a>
      <a title="Audit Logs" data-sidebar-tooltip="true" data-bs-placement="right" style="animation-delay: 0.3s;" class="nav-link d-flex fade-in-left align-items-center gap-3 <?= esc($current_page === 'audit_logs.php' ? 'active' : '';) ?>" href="<?= esc($baseAdminUrl) ?>system/audit_logs.php">
        <i class="bi bi-shield-check fs-5"></i> <span class="nav-text">Audit Logs</span>
      </a>
    </div>
  </div>
  <?php endif; ?>
</nav>

<!-- User Profile Area -->
    <div class="p-3 border-top bg-light mt-auto">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 42px; height: 42px;">
          <?= strtoupper(substr($_SESSION['user_first_name'] ?? 'A', 0, 1) . substr($_SESSION['user_last_name'] ?? 'D', 0, 1)); ?>
        </div>
        <div class="overflow-hidden nav-text user-profile-text">
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
                      default => 'Administrator'
                  };
              }
            ?>
          </span>
        </div>
      </div>
      <a class="btn btn-outline-danger w-100 btn-sm rounded-pill fw-medium shadow-sm" href="<?= esc($baseAdminUrl) ?>../auth/logout.php">
        <i class="bi bi-box-arrow-right"></i> <span class="nav-text">Sign Out</span>
      </a>
    </div>
  </aside>

  <!-- Main Content Wrapper -->
  <div id="spa-main" class="admin-main flex-grow-1 d-flex flex-column">
    
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

