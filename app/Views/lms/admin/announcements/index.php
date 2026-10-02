<?php
$pageTitle = 'LMS Platform Announcements - TTU';
require_once __DIR__ . '/../layout_header.php';

$currentStatus = $filters['status'] ?? '';
$currentAudience = $filters['audience'] ?? '';
$currentSeverity = $filters['severity'] ?? '';
$searchQuery = $filters['search'] ?? '';
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">

    <!-- Hero Header Strip (Registrar Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-megaphone-fill"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Platform Announcements</h1>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-broadcast me-1"></i> LMS Governance
              </span>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                Term: <?= htmlspecialchars($activeTerm['academic_year'] ?? '2026-2027') ?> <?= htmlspecialchars($activeTerm['semester'] ?? 'First') ?> Sem
              </span>
            </div>
            <p class="text-muted small mb-0">Broadcast emergency notices, maintenance downtime alerts, academic deadlines, and service notices across student and faculty LMS portals.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <button type="button" class="btn btn-primary rounded-pill px-3.5 py-2 fw-medium d-inline-flex align-items-center gap-1.5 shadow-sm hover-lift" data-bs-toggle="modal" data-bs-target="#createAnnouncementModal">
            <i class="bi bi-plus-circle-fill"></i>
            <span>New Announcement</span>
          </button>
          <a href="/sia/lms/admin/dashboard" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>LMS Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4 p-3 fade-in-up">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div class="small fw-semibold"><?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4 p-3 fade-in-up">
        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
        <div class="small fw-semibold"><?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Summary KPI Cards (4-Column Grid matching Registrar Dashboard) -->
    <div class="row g-4 mb-4">
      <!-- Total Notices -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.1s;">
          <div class="stat-card-glow bg-primary"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-collection"></i>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              Platform
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= (int)($summary['total'] ?? 0) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Total Notices</h2>
          <p class="text-muted small mb-0">Recorded advisories &amp; bulletins</p>
          <div class="stat-card-footer">
            <span>Broadcast catalog</span>
            <span class="stat-card-action text-primary">Overview <i class="bi bi-arrow-right"></i></span>
          </div>
        </div>
      </div>

      <!-- Active & Live -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.15s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-broadcast-pin"></i>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <span class="pulse-dot-green me-1" style="width: 6px; height: 6px;"></span> Live
            </span>
          </div>
          <div class="stat-number-display mb-1 text-success"><?= (int)($summary['active'] ?? 0) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Active &amp; Live</h2>
          <p class="text-muted small mb-0">Currently broadcast to users</p>
          <div class="stat-card-footer">
            <span>Active banners</span>
            <span class="stat-card-action text-success">Broadcasting <i class="bi bi-check2"></i></span>
          </div>
        </div>
      </div>

      <!-- Scheduled Future -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow bg-info"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
              <i class="bi bi-clock-history"></i>
            </div>
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              Queued
            </span>
          </div>
          <div class="stat-number-display mb-1 text-info"><?= (int)($summary['scheduled'] ?? 0) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Scheduled Future</h2>
          <p class="text-muted small mb-0">Queued for automated release</p>
          <div class="stat-card-footer">
            <span>Pending window</span>
            <span class="stat-card-action text-info">Timetable <i class="bi bi-arrow-right"></i></span>
          </div>
        </div>
      </div>

      <!-- Drafts / Concluded -->
      <div class="col-sm-6 col-xl-3">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.25s;">
          <div class="stat-card-glow bg-secondary"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-secondary bg-opacity-10 text-secondary">
              <i class="bi bi-archive-fill"></i>
            </div>
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              Archived
            </span>
          </div>
          <div class="stat-number-display mb-1"><?= (int)($summary['draft'] ?? 0) + (int)($summary['expired'] ?? 0) ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Drafts / Expired</h2>
          <p class="text-muted small mb-0"><?= (int)($summary['draft'] ?? 0) ?> Draft(s) • <?= (int)($summary['expired'] ?? 0) ?> Expired</p>
          <div class="stat-card-footer">
            <span>Inactive notices</span>
            <span class="stat-card-action text-secondary">History <i class="bi bi-arrow-right"></i></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter & Search Toolbar (Dossier Card) -->
    <div class="dossier-card mb-4 fade-in-up" style="animation-delay: 0.3s;">
      <div class="p-3.5 px-4">
        <form method="GET" action="/sia/lms/admin/announcements" class="row g-2.5 align-items-center">
          <div class="col-12 col-md-4">
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0 text-muted rounded-start-pill ps-3"><i class="bi bi-search"></i></span>
              <input type="text" name="search" class="form-control bg-light border-start-0 shadow-none rounded-end-pill py-2 small" placeholder="Search announcement title or message..." value="<?= htmlspecialchars($searchQuery) ?>">
            </div>
          </div>
          <div class="col-6 col-md-2">
            <select name="audience" class="form-select bg-light border-0 shadow-none rounded-pill py-2 small">
              <option value="">All Audiences</option>
              <option value="all" <?= $currentAudience === 'all' ? 'selected' : '' ?>>Target: All Users</option>
              <option value="students" <?= $currentAudience === 'students' ? 'selected' : '' ?>>Target: Students Only</option>
              <option value="faculty" <?= $currentAudience === 'faculty' ? 'selected' : '' ?>>Target: Faculty Only</option>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <select name="severity" class="form-select bg-light border-0 shadow-none rounded-pill py-2 small">
              <option value="">All Severities</option>
              <option value="info" <?= $currentSeverity === 'info' ? 'selected' : '' ?>>Info (Blue)</option>
              <option value="warning" <?= $currentSeverity === 'warning' ? 'selected' : '' ?>>Warning (Yellow)</option>
              <option value="danger" <?= $currentSeverity === 'danger' ? 'selected' : '' ?>>Danger/Outage (Red)</option>
              <option value="success" <?= $currentSeverity === 'success' ? 'selected' : '' ?>>Success (Green)</option>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <select name="status" class="form-select bg-light border-0 shadow-none rounded-pill py-2 small">
              <option value="">All Statuses</option>
              <option value="published" <?= $currentStatus === 'published' ? 'selected' : '' ?>>Published</option>
              <option value="draft" <?= $currentStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
            </select>
          </div>
          <div class="col-6 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary rounded-pill w-100 fw-semibold shadow-xs hover-lift d-inline-flex align-items-center justify-content-center gap-1.5">
              <i class="bi bi-funnel"></i>
              <span>Filter</span>
            </button>
            <a href="/sia/lms/admin/announcements" class="btn btn-light border rounded-pill px-3 shadow-xs hover-lift" title="Reset Filters">
              <i class="bi bi-arrow-counterclockwise"></i>
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Announcements Table Dossier Card (Registrar Consistent Table) -->
    <div class="dossier-card mb-5 fade-in-up" style="animation-delay: 0.35s;">
      <div class="dossier-card-header">
        <div class="d-flex align-items-center gap-2.5">
          <div class="dossier-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-megaphone-fill"></i>
          </div>
          <div>
            <h5 class="fw-bold text-dark mb-0 d-inline-block align-middle">Platform Announcements Roster</h5>
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold ms-2 align-middle">
              <?= count($announcements) ?> Items
            </span>
          </div>
        </div>
        <span class="text-muted small d-none d-md-inline">Course-level faculty notices remain isolated in course spaces</span>
      </div>

      <div class="p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 dashboard-table">
          <thead class="table-light text-muted small text-uppercase">
            <tr>
              <th class="ps-4" style="width: 50px;">ID</th>
              <th style="min-width: 250px;">Announcement Notice</th>
              <th>Audience</th>
              <th>Severity</th>
              <th>Lifecycle State</th>
              <th>Display Window</th>
              <th>Author / Created</th>
              <th class="text-end pe-4" style="min-width: 140px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($announcements)): ?>
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="bi bi-megaphone text-muted fs-1 d-block mb-2"></i>
                  <h6 class="fw-bold mb-1">No platform announcements found</h6>
                  <p class="small text-muted mb-3">No announcements match your search filters or none have been published yet.</p>
                  <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#createAnnouncementModal">
                    <i class="bi bi-plus me-1"></i> Create First Announcement
                  </button>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($announcements as $ann): 
                $lifecycle = $ann['lifecycle'] ?? 'draft';
                $severity = $ann['severity'] ?? 'info';
                $audience = $ann['target_audience'] ?? 'all';
                $status = $ann['status'] ?? 'draft';

                // Badges
                $severityBadge = 'bg-info bg-opacity-10 text-info border border-info border-opacity-25';
                $severityIcon = 'bi-info-circle-fill';
                if ($severity === 'warning') {
                  $severityBadge = 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
                  $severityIcon = 'bi-exclamation-triangle-fill';
                } elseif ($severity === 'danger') {
                  $severityBadge = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                  $severityIcon = 'bi-exclamation-octagon-fill';
                } elseif ($severity === 'success') {
                  $severityBadge = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                  $severityIcon = 'bi-check-circle-fill';
                }

                $audienceBadge = 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25';
                $audienceLabel = 'All LMS Users';
                $audienceIcon = 'bi-people-fill';
                if ($audience === 'students') {
                  $audienceBadge = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                  $audienceLabel = 'Students Only';
                  $audienceIcon = 'bi-mortarboard-fill';
                } elseif ($audience === 'faculty') {
                  $audienceBadge = 'bg-dark bg-opacity-10 text-dark border border-secondary border-opacity-25';
                  $audienceLabel = 'Faculty Only';
                  $audienceIcon = 'bi-person-video3';
                }

                $stateBadge = 'bg-secondary bg-opacity-10 text-secondary border';
                $stateLabel = ucfirst($lifecycle);
                if ($lifecycle === 'active') {
                  $stateBadge = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                  $stateLabel = 'Live Active';
                } elseif ($lifecycle === 'scheduled') {
                  $stateBadge = 'bg-info bg-opacity-10 text-info border border-info border-opacity-25';
                  $stateLabel = 'Scheduled';
                } elseif ($lifecycle === 'draft') {
                  $stateBadge = 'bg-secondary bg-opacity-10 text-secondary border';
                  $stateLabel = 'Draft';
                } elseif ($lifecycle === 'expired') {
                  $stateBadge = 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25';
                  $stateLabel = 'Expired';
                }
              ?>
                <tr>
                  <td class="ps-4 text-muted fw-semibold small">#<?= (int)$ann['id'] ?></td>
                  <td>
                    <div class="fw-bold text-dark mb-0.5"><?= htmlspecialchars($ann['title']) ?></div>
                    <div class="text-muted small text-truncate" style="max-width: 320px;" title="<?= htmlspecialchars($ann['content']) ?>">
                      <?= htmlspecialchars($ann['content']) ?>
                    </div>
                  </td>
                  <td>
                    <span class="badge <?= esc($audienceBadge) ?> rounded-pill px-2.5 py-1 small fw-semibold">
                      <i class="bi <?= esc($audienceIcon) ?> me-1"></i><?= esc($audienceLabel) ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge <?= esc($severityBadge) ?> rounded-pill px-2.5 py-1 small fw-semibold">
                      <i class="bi <?= esc($severityIcon) ?> me-1"></i><?= ucfirst(esc($severity)) ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge <?= esc($stateBadge) ?> rounded-pill px-2.5 py-1 small fw-semibold">
                      <?= esc($stateLabel) ?>
                    </span>
                  </td>
                  <td>
                    <div class="small text-dark">
                      <i class="bi bi-calendar-check text-muted me-1"></i>
                      <?= !empty($ann['published_at']) ? date('M d, Y h:i A', strtotime($ann['published_at'])) : '<span class="text-muted">Immediate</span>' ?>
                    </div>
                    <div class="small text-muted">
                      <i class="bi bi-calendar-x text-muted me-1"></i>
                      <?= !empty($ann['expires_at']) ? date('M d, Y h:i A', strtotime($ann['expires_at'])) : 'No Expiration' ?>
                    </div>
                  </td>
                  <td>
                    <div class="small fw-semibold text-dark"><?= htmlspecialchars($ann['first_name'] . ' ' . $ann['last_name']) ?></div>
                    <div class="text-muted" style="font-size: 0.72rem;"><?= date('M d, Y h:i A', strtotime($ann['created_at'])) ?></div>
                  </td>
                  <td class="text-end pe-4">
                    <div class="d-inline-flex align-items-center gap-1">
                      <!-- Toggle Status Form -->
                      <form method="POST" action="/sia/lms/admin/announcements/<?= (int)$ann['id'] ?>/status" class="d-inline">
                        <?= getCsrfInput() ?>
                        <button type="submit" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 small text-dark" title="<?= $status === 'published' ? 'Switch to Draft' : 'Publish Notice' ?>">
                          <?php if ($status === 'published'): ?>
                            <i class="bi bi-pause-circle text-warning me-1"></i>Unpublish
                          <?php else: ?>
                            <i class="bi bi-play-circle text-success me-1"></i>Publish
                          <?php endif; ?>
                        </button>
                      </form>

                      <!-- Edit Button -->
                      <button type="button" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 text-primary btn-edit-announcement"
                        data-id="<?= (int)$ann['id'] ?>"
                        data-title="<?= htmlspecialchars($ann['title'], ENT_QUOTES) ?>"
                        data-content="<?= htmlspecialchars($ann['content'], ENT_QUOTES) ?>"
                        data-audience="<?= esc($ann['target_audience']) ?>"
                        data-severity="<?= esc($ann['severity']) ?>"
                        data-status="<?= esc($ann['status']) ?>"
                        data-published-at="<?= !empty($ann['published_at']) ? date('Y-m-d\TH:i', strtotime($ann['published_at'])) : '' ?>"
                        data-expires-at="<?= !empty($ann['expires_at']) ? date('Y-m-d\TH:i', strtotime($ann['expires_at'])) : '' ?>"
                        title="Edit Announcement">
                        <i class="bi bi-pencil-square"></i>
                      </button>

                      <!-- Delete Form -->
                      <button type="button" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 text-danger btn-delete-announcement"
                        data-id="<?= (int)$ann['id'] ?>"
                        data-title="<?= htmlspecialchars($ann['title'], ENT_QUOTES) ?>"
                        title="Delete Announcement">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</main>

<!-- ========================================================================= -->
<!-- MODAL: CREATE PLATFORM ANNOUNCEMENT                                       -->
<!-- ========================================================================= -->
<div class="modal fade" id="createAnnouncementModal" tabindex="-1" aria-labelledby="createAnnouncementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <form method="POST" action="/sia/lms/admin/announcements/store">
        <?= getCsrfInput() ?>
        <div class="modal-header bg-primary text-white p-3.5 px-4">
          <div class="d-flex align-items-center gap-2.5">
            <div class="d-flex align-items-center justify-content-center bg-white bg-opacity-20 text-white rounded-3" style="width: 38px; height: 38px; font-size: 1.25rem;">
              <i class="bi bi-megaphone-fill"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-white mb-0" id="createAnnouncementModalLabel">New Platform-Wide Announcement</h5>
              <small class="text-white-50">Broadcast critical information across TTU LMS</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 bg-light">
          <div class="bg-white p-3.5 rounded-3 border mb-3">
            <div class="mb-3">
              <label for="createTitle" class="form-label fw-bold text-dark small">Announcement Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control shadow-none" id="createTitle" name="title" required placeholder="e.g. Scheduled LMS Maintenance: Oct 5, 10:00 PM - 12:00 AM">
              <div class="form-text small">Short, descriptive summary visible in portal alerts and notifications.</div>
            </div>

            <div class="mb-3">
              <label for="createContent" class="form-label fw-bold text-dark small">Announcement Message <span class="text-danger">*</span></label>
              <textarea class="form-control shadow-none" id="createContent" name="content" rows="4" required placeholder="Detailed message explaining the purpose, scope, and instructions for students or faculty..."></textarea>
              <div class="form-text small">Plain text only. HTML tags will be automatically stripped for institutional security.</div>
            </div>
          </div>

          <div class="bg-white p-3.5 rounded-3 border">
            <h6 class="fw-bold text-dark small mb-3 text-uppercase text-muted"><i class="bi bi-sliders me-1"></i> Targeting &amp; Scheduling Governance</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="createAudience" class="form-label fw-semibold text-dark small">Target Audience <span class="text-danger">*</span></label>
                <select class="form-select shadow-none" id="createAudience" name="target_audience" required>
                  <option value="all" selected>All LMS Users (Students &amp; Faculty)</option>
                  <option value="students">Students Only</option>
                  <option value="faculty">Faculty Only</option>
                </select>
                <div class="form-text small">Administrative-only notes are never visible to students.</div>
              </div>

              <div class="col-md-6">
                <label for="createSeverity" class="form-label fw-semibold text-dark small">Severity / Type <span class="text-danger">*</span></label>
                <select class="form-select shadow-none" id="createSeverity" name="severity" required>
                  <option value="info" selected>Info (Blue — General Notice)</option>
                  <option value="warning">Warning (Yellow — Maintenance / Outage Alert)</option>
                  <option value="danger">Danger (Red — Critical Emergency / System Outage)</option>
                  <option value="success">Success (Green — Resolution / Good News)</option>
                </select>
              </div>

              <div class="col-md-4">
                <label for="createStatus" class="form-label fw-semibold text-dark small">Publication Status <span class="text-danger">*</span></label>
                <select class="form-select shadow-none" id="createStatus" name="status" required>
                  <option value="published" selected>Published</option>
                  <option value="draft">Save as Draft</option>
                </select>
              </div>

              <div class="col-md-4">
                <label for="createPublishedAt" class="form-label fw-semibold text-dark small">Start / Release Time</label>
                <input type="datetime-local" class="form-control shadow-none" id="createPublishedAt" name="published_at">
                <div class="form-text small">Leave blank to publish immediately.</div>
              </div>

              <div class="col-md-4">
                <label for="createExpiresAt" class="form-label fw-semibold text-dark small">Expiration Time</label>
                <input type="datetime-local" class="form-control shadow-none" id="createExpiresAt" name="expires_at">
                <div class="form-text small">Optional. Notice disappears after this time.</div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light border-top p-3 px-4 d-flex justify-content-between">
          <button type="button" class="btn btn-light border rounded-pill px-3.5" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 fw-medium shadow-sm">
            <i class="bi bi-send-fill me-1"></i> Broadcast Announcement
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EDIT PLATFORM ANNOUNCEMENT                                         -->
<!-- ========================================================================= -->
<div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <form method="POST" id="editAnnouncementForm" action="">
        <?= getCsrfInput() ?>
        <div class="modal-header bg-dark text-white p-3.5 px-4">
          <div class="d-flex align-items-center gap-2.5">
            <div class="d-flex align-items-center justify-content-center bg-white bg-opacity-20 text-white rounded-3" style="width: 38px; height: 38px; font-size: 1.25rem;">
              <i class="bi bi-pencil-square"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-white mb-0" id="editAnnouncementModalLabel">Edit Platform Announcement</h5>
              <small class="text-white-50">Modify broadcast parameters or message body</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4 bg-light">
          <div class="bg-white p-3.5 rounded-3 border mb-3">
            <div class="mb-3">
              <label for="editTitle" class="form-label fw-bold text-dark small">Announcement Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control shadow-none" id="editTitle" name="title" required>
            </div>

            <div class="mb-3">
              <label for="editContent" class="form-label fw-bold text-dark small">Announcement Message <span class="text-danger">*</span></label>
              <textarea class="form-control shadow-none" id="editContent" name="content" rows="4" required></textarea>
            </div>
          </div>

          <div class="bg-white p-3.5 rounded-3 border">
            <h6 class="fw-bold text-dark small mb-3 text-uppercase text-muted"><i class="bi bi-sliders me-1"></i> Targeting &amp; Scheduling Governance</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="editAudience" class="form-label fw-semibold text-dark small">Target Audience <span class="text-danger">*</span></label>
                <select class="form-select shadow-none" id="editAudience" name="target_audience" required>
                  <option value="all">All LMS Users (Students &amp; Faculty)</option>
                  <option value="students">Students Only</option>
                  <option value="faculty">Faculty Only</option>
                </select>
              </div>

              <div class="col-md-6">
                <label for="editSeverity" class="form-label fw-semibold text-dark small">Severity / Type <span class="text-danger">*</span></label>
                <select class="form-select shadow-none" id="editSeverity" name="severity" required>
                  <option value="info">Info (Blue — General Notice)</option>
                  <option value="warning">Warning (Yellow — Maintenance / Outage Alert)</option>
                  <option value="danger">Danger (Red — Critical Emergency / System Outage)</option>
                  <option value="success">Success (Green — Resolution / Good News)</option>
                </select>
              </div>

              <div class="col-md-4">
                <label for="editStatus" class="form-label fw-semibold text-dark small">Publication Status <span class="text-danger">*</span></label>
                <select class="form-select shadow-none" id="editStatus" name="status" required>
                  <option value="published">Published</option>
                  <option value="draft">Draft</option>
                </select>
              </div>

              <div class="col-md-4">
                <label for="editPublishedAt" class="form-label fw-semibold text-dark small">Start / Release Time</label>
                <input type="datetime-local" class="form-control shadow-none" id="editPublishedAt" name="published_at">
              </div>

              <div class="col-md-4">
                <label for="editExpiresAt" class="form-label fw-semibold text-dark small">Expiration Time</label>
                <input type="datetime-local" class="form-control shadow-none" id="editExpiresAt" name="expires_at">
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light border-top p-3 px-4 d-flex justify-content-between">
          <button type="button" class="btn btn-light border rounded-pill px-3.5" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 fw-medium shadow-sm">
            <i class="bi bi-save me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: DELETE CONFIRMATION                                                -->
<!-- ========================================================================= -->
<div class="modal fade" id="deleteAnnouncementModal" tabindex="-1" aria-labelledby="deleteAnnouncementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <form method="POST" id="deleteAnnouncementForm" action="">
        <?= getCsrfInput() ?>
        <div class="modal-header bg-danger text-white p-3.5 px-4">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-trash-fill fs-5"></i>
            <h5 class="modal-title fw-bold text-white mb-0" id="deleteAnnouncementModalLabel">Confirm Deletion</h5>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4 text-center">
          <i class="bi bi-exclamation-triangle-fill text-danger fs-1 d-block mb-3"></i>
          <h6 class="fw-bold mb-2">Are you sure you want to delete this announcement?</h6>
          <p class="text-muted small mb-0" id="deleteAnnouncementNoticeTitle"></p>
          <p class="text-muted small mt-2">This action is permanent and will be recorded in the institutional audit log.</p>
        </div>
        <div class="modal-footer bg-light border-top p-3 px-4 d-flex justify-content-between">
          <button type="button" class="btn btn-light border rounded-pill px-3.5" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger rounded-pill px-4 fw-medium shadow-sm">
            <i class="bi bi-trash me-1"></i> Permanently Delete
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Edit Announcement Prepopulation
  document.querySelectorAll('.btn-edit-announcement').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = this.getAttribute('data-id');
      var title = this.getAttribute('data-title');
      var content = this.getAttribute('data-content');
      var audience = this.getAttribute('data-audience');
      var severity = this.getAttribute('data-severity');
      var status = this.getAttribute('data-status');
      var publishedAt = this.getAttribute('data-published-at');
      var expiresAt = this.getAttribute('data-expires-at');

      document.getElementById('editAnnouncementForm').action = '/sia/lms/admin/announcements/' + id + '/update';
      document.getElementById('editTitle').value = title;
      document.getElementById('editContent').value = content;
      document.getElementById('editAudience').value = audience;
      document.getElementById('editSeverity').value = severity;
      document.getElementById('editStatus').value = status;
      document.getElementById('editPublishedAt').value = publishedAt;
      document.getElementById('editExpiresAt').value = expiresAt;

      var editModal = new bootstrap.Modal(document.getElementById('editAnnouncementModal'));
      editModal.show();
    });
  });

  // Delete Announcement Setup
  document.querySelectorAll('.btn-delete-announcement').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = this.getAttribute('data-id');
      var title = this.getAttribute('data-title');

      document.getElementById('deleteAnnouncementForm').action = '/sia/lms/admin/announcements/' + id + '/delete';
      document.getElementById('deleteAnnouncementNoticeTitle').textContent = '"' + title + '" (ID #' + id + ')';

      var deleteModal = new bootstrap.Modal(document.getElementById('deleteAnnouncementModal'));
      deleteModal.show();
    });
  });
});
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
