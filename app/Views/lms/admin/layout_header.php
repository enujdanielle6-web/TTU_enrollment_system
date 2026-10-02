<?php
// app/Views/lms/admin/layout_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$adminName = $_SESSION['user_name'] ?? 'LMS Administrator';
$adminEmail = $_SESSION['user_email'] ?? 'admin@ttu.edu.ph';
$adminInitial = strtoupper(substr($adminName, 0, 1));
$userRole = $_SESSION['user_role'] ?? 'admin';

// Determine active module
$activeTab = 'dashboard';
if (strpos($request_uri, '/courses') !== false || strpos($request_uri, '/generator') !== false) {
    $activeTab = 'courses';
} elseif (strpos($request_uri, '/sync') !== false) {
    $activeTab = 'sync';
} elseif (strpos($request_uri, '/cloner') !== false) {
    $activeTab = 'cloner';
} elseif (strpos($request_uri, '/announcements') !== false) {
    $activeTab = 'announcements';
} elseif (strpos($request_uri, '/users') !== false) {
    $activeTab = 'users';
} elseif (strpos($request_uri, '/archive') !== false) {
    $activeTab = 'archive';
} elseif (strpos($request_uri, '/audit_logs') !== false) {
    $activeTab = 'audit_logs';
}

$lmsCssFile = dirname(__DIR__, 4) . '/public/css/lms.css';
$lmsCssVer = file_exists($lmsCssFile) ? filemtime($lmsCssFile) : '1.0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'LMS Administration & Governance - TTU' ?></title>
    <!-- CSS & Fonts -->
    <link href="/sia/public/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/sia/public/vendor/fonts/fonts.css">
    <link rel="stylesheet" href="/sia/public/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link href="/sia/css/main.css?v=<?= esc(file_exists(dirname(__DIR__, 4) . '/css/main.css') ? filemtime(dirname(__DIR__, 4) . '/css/main.css') : '1.0') ?>" rel="stylesheet">
    <!-- Custom LMS CSS -->
    <link rel="stylesheet" href="/sia/public/css/lms.css?v=<?= esc($lmsCssVer) ?>">
    <style>
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
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        position: sticky;
        top: 0;
        z-index: 1020;
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
      .badge-frosted-white {
        background: rgba(255, 255, 255, 0.22) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.35) !important;
        -webkit-backdrop-filter: blur(4px);
        backdrop-filter: blur(4px);
        font-weight: 600;
        display: inline-flex;
        align-items: center;
      }
      .badge-frosted-dark {
        background: rgba(0, 0, 0, 0.22) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        -webkit-backdrop-filter: blur(4px);
        backdrop-filter: blur(4px);
        font-weight: 600;
        display: inline-flex;
        align-items: center;
      }
      .badge-frosted-solid {
        background: #ffffff !important;
        color: #0f172a !important;
        font-weight: 700;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
        display: inline-flex;
        align-items: center;
      }
      .lms-qa-red .lms-qa-icon { background: rgba(225, 29, 72, 0.12); color: #e11d48; }
      .lms-qa-red:hover .lms-qa-icon { background: #e11d48; color: #ffffff; }
      .lms-qa-purple .lms-qa-icon { background: rgba(124, 58, 237, 0.12); color: #7c3aed; }
      .lms-qa-purple:hover .lms-qa-icon { background: #7c3aed; color: #ffffff; }
    </style>
</head>
<body class="lms-layout lms-admin-layout">

<!-- Unified LMS Sidebar (Admin Portal) -->
<aside class="lms-sidebar" id="lmsSidebar">
    <!-- Brand Header -->
    <div class="lms-sidebar-brand d-flex align-items-center justify-content-between px-3 py-3 border-bottom">
        <a href="/sia/lms/admin/dashboard" class="d-flex align-items-center gap-2 text-decoration-none">
            <div class="lms-brand-icon shadow-xs">
                <img src="/sia/images/TTU_LOGO.png" alt="TTU Logo" style="height: 28px; width: auto; object-fit: contain;">
            </div>
            <div class="nav-text">
                <span class="fw-bold text-dark d-block" style="font-size: 1.05rem; line-height: 1.15; letter-spacing: -0.01em;">TTU LMS</span>
                <span class="badge badge-indigo-subtle rounded-pill px-2 py-0.5 fw-bold mt-0.5" style="font-size: 0.62rem; letter-spacing: 0.05em;">ADMIN GOVERNANCE</span>
            </div>
        </a>
        <button id="sidebarToggle" class="lms-toggle-btn text-muted" type="button" title="Toggle Sidebar">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>
    </div>

    <!-- Quick Search -->
    <div class="px-3 my-3 lms-search-container">
        <div class="lms-search-box d-flex align-items-center gap-2 px-3 py-1.5 rounded-3">
            <i class="bi bi-search text-muted small"></i>
            <input type="search" class="form-control bg-transparent border-0 p-0 shadow-none nav-text small lms-sidebar-search" placeholder="Quick search modules..." aria-label="Search navigation" autocomplete="off">
            <kbd class="lms-kbd-shortcut nav-text" aria-hidden="true">⌘K</kbd>
        </div>
    </div>

    <!-- Navigation Menu Items -->
    <div class="lms-nav-menu flex-grow-1">
        <div class="lms-section-label">Operational Hub</div>
        <a href="/sia/lms/admin/dashboard" class="lms-nav-link <?= esc($activeTab === 'dashboard' ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-grid-1x2-fill"></i>
            </div>
            <span class="nav-text">Dashboard</span>
        </a>

        <a href="/sia/lms/admin/courses" class="lms-nav-link <?= esc($activeTab === 'courses' ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-journal-bookmark-fill"></i>
            </div>
            <span class="nav-text">Course Catalog</span>
        </a>

        <a href="/sia/lms/admin/sync" class="lms-nav-link <?= esc($activeTab === 'sync' ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <span class="nav-text">Sync &amp; Conflicts</span>
        </a>

        <a href="/sia/lms/admin/cloner" class="lms-nav-link <?= esc($activeTab === 'cloner' ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-copy"></i>
            </div>
            <span class="nav-text">Content Cloner</span>
        </a>

        <a href="/sia/lms/admin/announcements" class="lms-nav-link <?= esc($activeTab === 'announcements' ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-megaphone-fill"></i>
            </div>
            <span class="nav-text">Announcements</span>
        </a>

        <div class="lms-section-label mt-3">Governance &amp; Audit</div>
        <a href="/sia/lms/admin/users" class="lms-nav-link <?= esc($activeTab === 'users' ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-person-badge-fill"></i>
            </div>
            <span class="nav-text">User Access</span>
        </a>

        <a href="/sia/lms/admin/archive" class="lms-nav-link <?= esc($activeTab === 'archive' ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-archive-fill"></i>
            </div>
            <span class="nav-text">Term Archival</span>
        </a>

        <a href="/sia/lms/admin/audit_logs" class="lms-nav-link <?= esc($activeTab === 'audit_logs' ? 'active' : '') ?>">
            <div class="lms-nav-icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <span class="nav-text">LMS Audit Logs</span>
        </a>

        <div class="lms-section-label mt-3">System Gateways</div>
        <a href="/sia/lms/faculty/dashboard.php" class="lms-nav-link">
            <div class="lms-nav-icon">
                <i class="bi bi-person-video3"></i>
            </div>
            <span class="nav-text">Faculty View</span>
        </a>

        <?php if ($userRole === 'superadmin'): ?>
        <a href="/sia/admin/dashboard.php" class="lms-nav-link">
            <div class="lms-nav-icon">
                <i class="bi bi-box-arrow-left"></i>
            </div>
            <span class="nav-text">Main SIS Portal</span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Footer Profile & Actions -->
    <div class="lms-sidebar-footer">
        <div class="lms-user-card mb-2">
            <div class="d-flex align-items-center">
                <div class="position-relative flex-shrink-0" style="width: 36px; height: 36px;">
                    <div class="lms-avatar bg-primary text-white d-flex align-items-center justify-content-center rounded-circle fw-bold">
                        <?= esc($adminInitial) ?>
                    </div>
                    <span class="lms-status-dot" title="Online"></span>
                </div>
                <div class="nav-text flex-grow-1 min-w-0 overflow-hidden ps-2" style="line-height: 1.25;">
                    <div class="fw-bold text-dark text-truncate small" title="<?= htmlspecialchars($adminName) ?>">
                        <?= htmlspecialchars($adminName) ?>
                    </div>
                    <div class="text-muted text-truncate" style="font-size: 0.7rem;" title="<?= htmlspecialchars($adminEmail) ?>">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-1.5 py-0 small fw-semibold">LMS Admin</span>
                    </div>
                </div>
            </div>
        </div>
        
        <a href="/sia/auth/lms_admin_logout.php" class="lms-logout-btn text-decoration-none">
            <i class="bi bi-box-arrow-right"></i>
            <span class="nav-text">Sign out</span>
        </a>
    </div>
</aside>

<?php
$moduleLabels = [
    'dashboard' => 'Dashboard',
    'courses' => 'Course Catalog',
    'sync' => 'Sync & Conflicts',
    'cloner' => 'Content Cloner',
    'announcements' => 'Announcements',
    'users' => 'User Access',
    'archive' => 'Term Archival',
    'audit_logs' => 'Audit Logs'
];
$currentModuleTitle = $moduleLabels[$activeTab] ?? 'Governance';
?>
<!-- Main Content Area -->
<div class="lms-main" id="spa-main">

<!-- Sleek LMS Admin Topbar -->
<header class="lms-admin-topbar">
  <div class="container-fluid px-lg-5 d-flex align-items-center justify-content-between gap-3">
    <!-- Left: Mobile Toggle & Breadcrumbs -->
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-light border d-lg-none shadow-xs" type="button" onclick="document.getElementById('lmsSidebar').classList.toggle('show');" aria-label="Toggle Navigation">
        <i class="bi bi-list fs-5"></i>
      </button>
      <nav aria-label="Breadcrumb">
        <ul class="lms-breadcrumb d-flex align-items-center mb-0">
          <li>
            <a href="/sia/lms/admin/dashboard">
              <i class="bi bi-shield-check text-primary"></i>
              <span>LMS Governance</span>
            </a>
          </li>
          <li class="separator"><i class="bi bi-chevron-right"></i></li>
          <li class="current"><?= esc($currentModuleTitle) ?></li>
        </ul>
      </nav>
    </div>

    <!-- Right: Context Indicators & Profile Dropdown -->
    <div class="d-flex align-items-center gap-2.5">
      <!-- Active Term Badge -->
      <div class="badge badge-emerald-subtle rounded-pill px-3 py-1.5 small fw-semibold d-none d-md-inline-flex align-items-center gap-1.5 shadow-xs">
        <span class="pulse-dot pulse-dot-emerald"></span>
        <span>2026-2027 First Sem</span>
      </div>

      <!-- Quick Sync Status Link -->
      <a href="/sia/lms/admin/sync" class="btn btn-sm btn-light border rounded-pill px-3 py-1.5 small fw-semibold text-secondary d-none d-sm-inline-flex align-items-center gap-1.5 shadow-xs hover-lift" title="Reconciliation & Diagnostics">
        <i class="bi bi-arrow-repeat text-primary"></i>
        <span>Sync Hub</span>
      </a>

      <!-- Main SIS Switcher for Superadmin -->
      <?php if ($userRole === 'superadmin'): ?>
      <a href="/sia/admin/dashboard.php" class="btn btn-sm btn-light border rounded-pill px-3 py-1.5 small fw-semibold text-secondary d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift" title="Switch to Main SIS">
        <i class="bi bi-box-arrow-left text-primary"></i>
        <span class="d-none d-sm-inline">Main SIS</span>
      </a>
      <?php endif; ?>

      <!-- User Profile Dropdown Pill -->
      <div class="dropdown">
        <button class="btn btn-sm btn-white border rounded-pill px-2.5 py-1 d-flex align-items-center gap-2 shadow-xs dropdown-toggle" type="button" id="adminUserDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <div class="lms-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 26px; height: 26px; font-size: 0.75rem;">
            <?= esc($adminInitial) ?>
          </div>
          <span class="fw-semibold small text-dark d-none d-md-inline"><?= esc($adminName) ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-3 py-2 mt-1" aria-labelledby="adminUserDropdown" style="min-width: 220px;">
          <li class="px-3 py-1.5 border-bottom mb-1">
            <div class="fw-bold text-dark small text-truncate"><?= esc($adminName) ?></div>
            <div class="text-muted small text-truncate" style="font-size: 0.72rem;"><?= esc($adminEmail) ?></div>
            <span class="badge bg-primary bg-opacity-10 text-primary mt-1 px-2 py-0.5" style="font-size: 0.65rem;">LMS Administrator</span>
          </li>
          <li>
            <a class="dropdown-item py-1.5 small d-flex align-items-center gap-2" href="/sia/lms/faculty/dashboard.php">
              <i class="bi bi-person-video3 text-primary"></i>
              <span>Faculty Portal View</span>
            </a>
          </li>
          <?php if ($userRole === 'superadmin'): ?>
          <li>
            <a class="dropdown-item py-1.5 small d-flex align-items-center gap-2" href="/sia/admin/dashboard.php">
              <i class="bi bi-building text-primary"></i>
              <span>University Main SIS</span>
            </a>
          </li>
          <?php endif; ?>
          <li><hr class="dropdown-divider my-1"></li>
          <li>
            <a class="dropdown-item py-1.5 small d-flex align-items-center gap-2 text-danger" href="/sia/auth/lms_admin_logout.php">
              <i class="bi bi-box-arrow-right"></i>
              <span>Sign Out</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</header>

