<?php
// app/Views/components/sidebar.php - Standalone Administrative Sidebar Component
$uri = $_SERVER['REQUEST_URI'] ?? '';
$currentPage = basename($uri);
if (($qPos = strpos($currentPage, '?')) !== false) {
    $currentPage = substr($currentPage, 0, $qPos);
}
$isLms = (strpos($uri, '/admin/lms') !== false || strpos($uri, '/lms/admin') !== false);
$baseAdminUrl = '/sia/admin/';
?>
<aside class="admin-sidebar bg-white border-end shadow-sm d-flex flex-column" style="width: 260px; height: 100vh; max-height: 100vh; position: sticky; top: 0; overflow: hidden;">
  <div class="p-4 border-bottom d-flex align-items-center gap-3 flex-shrink-0">
    <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 32px; width: auto; object-fit: contain;">
    <span class="fw-bold text-dark fs-6">Admin Portal</span>
  </div>
  <nav class="nav flex-column p-3 gap-1 flex-grow-1 overflow-y-auto" style="min-height: 0;">
    <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center gap-2.5 <?= esc(($currentPage === 'dashboard.php' && !$isLms) ? 'active bg-primary text-white' : 'text-dark hover-bg-light') ?>" href="<?= esc($baseAdminUrl) ?>dashboard.php">
      <i class="bi bi-grid fs-5"></i>
      <span class="fw-medium">Dashboard</span>
    </a>

    <?php if (hasPermission(['applications.view_queue'])): ?>
    <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center gap-2.5 <?= esc(in_array($currentPage, ['review.php', 'application_detail.php']) ? 'active bg-primary text-white' : 'text-dark hover-bg-light') ?>" href="<?= esc($baseAdminUrl) ?>admissions/review.php">
      <i class="bi bi-inbox fs-5"></i>
      <span class="fw-medium">Admissions</span>
    </a>
    <?php endif; ?>

    <?php if (hasPermission(['students.view', 'programs.manage'])): ?>
    <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center gap-2.5 <?= esc($currentPage === 'college_enrollment_queue.php' ? 'active bg-primary text-white' : 'text-dark hover-bg-light') ?>" href="<?= esc($baseAdminUrl) ?>registrar/college_enrollment_queue.php">
      <i class="bi bi-mortarboard-fill fs-5"></i>
      <span class="fw-medium">College Queue</span>
    </a>
    <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center gap-2.5 <?= esc($currentPage === 'shs_enrollment_queue.php' ? 'active bg-primary text-white' : 'text-dark hover-bg-light') ?>" href="<?= esc($baseAdminUrl) ?>registrar/shs_enrollment_queue.php">
      <i class="bi bi-journal-bookmark-fill fs-5"></i>
      <span class="fw-medium">SHS Queue</span>
    </a>
    <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center gap-2.5 <?= esc(in_array($currentPage, ['students.php', 'students_export.php']) ? 'active bg-primary text-white' : 'text-dark hover-bg-light') ?>" href="<?= esc($baseAdminUrl) ?>registrar/students.php">
      <i class="bi bi-people fs-5"></i>
      <span class="fw-medium">Students</span>
    </a>
    <?php endif; ?>

    <?php if (hasPermission(['*'])): ?>
    <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center gap-2.5 <?= esc($currentPage === 'settings.php' ? 'active bg-primary text-white' : 'text-dark hover-bg-light') ?>" href="<?= esc($baseAdminUrl) ?>system/settings.php">
      <i class="bi bi-gear fs-5"></i>
      <span class="fw-medium">Settings</span>
    </a>
    <?php endif; ?>

    <?php if (($_SESSION['user_department'] ?? '') !== 'Registrar Office' && (hasPermission(['*', 'lms.manage', 'lms.admin', 'lms.courses.manage']) || in_array($_SESSION['user_role'] ?? '', ['superadmin', 'lms_admin'], true))): ?>
    <a class="nav-link rounded-3 px-3 py-2 d-flex align-items-center gap-2.5 <?= esc($isLms ? 'active bg-primary text-white' : 'text-dark hover-bg-light') ?>" href="/sia/lms/admin/dashboard">
      <i class="bi bi-mortarboard-fill fs-5"></i>
      <span class="fw-medium">LMS Governance</span>
    </a>
    <?php endif; ?>
  </nav>
</aside>
