<?php
/**
 * TTU LMS Admin - Unified Navigation Ribbon & Enhanced Styling Header
 * Shared across all /admin/lms/* pages for consistent aesthetic with the LMS portal.
 */

$lmsCurrentUri = $_SERVER['REQUEST_URI'] ?? '';
$activeTab = 'dashboard';
if (strpos($lmsCurrentUri, '/courses') !== false || strpos($lmsCurrentUri, '/generator') !== false) {
    $activeTab = 'courses';
} elseif (strpos($lmsCurrentUri, '/sync') !== false) {
    $activeTab = 'sync';
} elseif (strpos($lmsCurrentUri, '/cloner') !== false) {
    $activeTab = 'cloner';
} elseif (strpos($lmsCurrentUri, '/announcements') !== false) {
    $activeTab = 'announcements';
} elseif (strpos($lmsCurrentUri, '/users') !== false) {
    $activeTab = 'users';
} elseif (strpos($lmsCurrentUri, '/archive') !== false) {
    $activeTab = 'archive';
} elseif (strpos($lmsCurrentUri, '/audit_logs') !== false) {
    $activeTab = 'audit_logs';
}
?>

<!-- Load LMS Core Stylesheet for unified aesthetic -->
<?php
$lmsCssFile = dirname(__DIR__, 5) . '/public/css/lms.css';
$lmsCssVer = file_exists($lmsCssFile) ? filemtime($lmsCssFile) : '1.0';
?>
<link rel="stylesheet" href="/sia/public/css/lms.css?v=<?= esc($lmsCssVer) ?>">

<style>
/* TTU LMS Admin Enhanced Styling */
:root {
  --lms-admin-primary: #0d6efd;
  --lms-admin-surface: #ffffff;
  --lms-admin-bg: #f8fafc;
  --lms-admin-border: #e2e8f0;
}

body {
  background-color: var(--lms-admin-bg) !important;
}

.lms-admin-ribbon {
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--lms-admin-border);
  box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
  position: sticky;
  top: 0;
  z-index: 1030;
}

.lms-tab-link {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.42rem 0.85rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #64748b;
  text-decoration: none;
  border-radius: 9999px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
}

.lms-tab-link:hover {
  color: #0f172a;
  background: #f1f5f9;
}

.lms-tab-link.active {
  color: var(--lms-admin-primary);
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  box-shadow: 0 1px 3px rgba(13, 110, 253, 0.08);
}

.lms-tab-link.active i {
  color: var(--lms-admin-primary);
}

/* Card Bands for Course Grid (Matching Faculty Portal) */
.course-card-premium {
  border-radius: 1.15rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

.course-card-premium:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px -6px rgba(15, 23, 42, 0.09), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
  border-color: #cbd5e1;
}

.card-band {
  padding: 0.75rem 1rem;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card-band-blue { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); }
.card-band-purple { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); }
.card-band-emerald { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
.card-band-cyan { background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%); }
.card-band-amber { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
.card-band-rose { background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); }

/* Quick Action Overrides */
.lms-qa-red .lms-qa-icon {
  background: rgba(225, 29, 72, 0.12);
  color: #e11d48;
}
.lms-qa-red:hover .lms-qa-icon {
  background: #e11d48;
  color: #ffffff;
}

.lms-qa-purple .lms-qa-icon {
  background: rgba(124, 58, 237, 0.12);
  color: #7c3aed;
}
.lms-qa-purple:hover .lms-qa-icon {
  background: #7c3aed;
  color: #ffffff;
}
</style>

<!-- Dedicated LMS Admin Sub-Navbar / Ribbon -->
<div class="lms-admin-ribbon">
  <div class="container-fluid px-lg-5 py-2 d-flex flex-wrap align-items-center justify-content-between gap-3">
    <!-- Brand & Portal Tag -->
    <div class="d-flex align-items-center gap-2.5">
      <a href="/sia/lms/admin/dashboard" class="d-flex align-items-center gap-2.5 text-decoration-none">
        <div class="d-flex align-items-center justify-content-center bg-white border rounded-3 p-1 shadow-xs" style="width: 38px; height: 38px;">
          <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 26px; width: auto; object-fit: contain;">
        </div>
        <div>
          <div class="d-flex align-items-center gap-2">
            <span class="fw-bold text-dark" style="font-size: 1.02rem; letter-spacing: -0.01em; line-height: 1.2;">TTU LMS</span>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 0.62rem; letter-spacing: 0.04em;">ADMIN GOVERNANCE</span>
          </div>
        </div>
      </a>
    </div>

    <!-- Central Navigation Tabs (All 8 Modules) -->
    <nav class="d-flex align-items-center gap-1 overflow-auto py-1" aria-label="LMS Admin Navigation">
      <a href="/sia/lms/admin/dashboard" class="lms-tab-link <?= $activeTab === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Dashboard</span>
      </a>
      <a href="/sia/lms/admin/courses" class="lms-tab-link <?= $activeTab === 'courses' ? 'active' : '' ?>">
        <i class="bi bi-journal-bookmark-fill"></i>
        <span>Courses</span>
      </a>
      <a href="/sia/lms/admin/sync" class="lms-tab-link <?= $activeTab === 'sync' ? 'active' : '' ?>">
        <i class="bi bi-arrow-repeat"></i>
        <span>Sync &amp; Conflicts</span>
      </a>
      <a href="/sia/lms/admin/cloner" class="lms-tab-link <?= $activeTab === 'cloner' ? 'active' : '' ?>">
        <i class="bi bi-copy"></i>
        <span>Cloner</span>
      </a>
      <a href="/sia/lms/admin/announcements" class="lms-tab-link <?= $activeTab === 'announcements' ? 'active' : '' ?>">
        <i class="bi bi-megaphone-fill"></i>
        <span>Announcements</span>
      </a>
      <a href="/sia/lms/admin/users" class="lms-tab-link <?= $activeTab === 'users' ? 'active' : '' ?>">
        <i class="bi bi-person-badge-fill"></i>
        <span>User Access</span>
      </a>
      <a href="/sia/lms/admin/archive" class="lms-tab-link <?= $activeTab === 'archive' ? 'active' : '' ?>">
        <i class="bi bi-archive-fill"></i>
        <span>Archival</span>
      </a>
      <a href="/sia/lms/admin/audit_logs" class="lms-tab-link <?= $activeTab === 'audit_logs' ? 'active' : '' ?>">
        <i class="bi bi-shield-check"></i>
        <span>Audit Logs</span>
      </a>
    </nav>

    <!-- Context & Quick Exit -->
    <div class="d-flex align-items-center gap-2">
      <a href="/sia/admin/dashboard.php" class="btn btn-sm btn-light border rounded-pill px-3 py-1.5 small fw-semibold text-secondary d-inline-flex align-items-center gap-1 shadow-xs hover-lift" title="Back to University Main SIS">
        <i class="bi bi-box-arrow-left text-primary"></i>
        <span>Main SIS</span>
      </a>
    </div>
  </div>
</div>
