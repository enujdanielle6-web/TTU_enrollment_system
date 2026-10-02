<?php
$pageTitle = 'Enrollment ↔ LMS Synchronization & Conflict Resolution - Administrator';
require_once __DIR__ . '/../layout_header.php';

$missingCount = count($syncReport['missing_courses'] ?? []);
$mismatchCount = count($syncReport['faculty_mismatches'] ?? []);
$duplicateGroupCount = count($conflictReport['duplicate_groups'] ?? []);
$duplicateShellCount = $conflictReport['summary']['total_duplicate_shells'] ?? 0;
$orphanCount = count($conflictReport['orphan_courses'] ?? []);
$healthyCount = $syncReport['healthy_count'] ?? 0;
$isFullySynced = ($missingCount === 0 && $mismatchCount === 0 && $duplicateGroupCount === 0 && $orphanCount === 0);
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Hero Header Strip (Registrar Consistent) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-arrow-repeat"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Enrollment ↔ LMS Synchronization &amp; Conflict Hub</h1>
              <?php if ($isFullySynced): ?>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold d-inline-flex align-items-center gap-1">
                  <span class="pulse-dot-green" style="width: 6px; height: 6px;"></span> 100% In-Sync
                </span>
              <?php else: ?>
                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold d-inline-flex align-items-center gap-1">
                  <span class="pulse-dot-amber" style="width: 6px; height: 6px;"></span> Discrepancies Detected
                </span>
              <?php endif; ?>
            </div>
            <p class="text-muted small mb-0">Diagnostic inspection, deterministic timetable auto-reconciliation, and safe conservative resolution of duplicate and orphan course shells.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <?php if ($missingCount > 0 || $mismatchCount > 0): ?>
            <form action="/sia/lms/admin/sync/reconcile" method="POST" class="m-0" onsubmit="return confirm('Execute deterministic reconciliation? This will provision missing shells and align faculty assignments to the official timetable.');">
              <?= getCsrfInput() ?>
              <button type="submit" class="btn btn-primary rounded-pill px-3.5 py-2 fw-medium d-inline-flex align-items-center gap-1.5 shadow-sm hover-lift">
                <i class="bi bi-magic"></i>
                <span>Run Safe Timetable Reconcile</span>
              </button>
            </form>
          <?php endif; ?>
          <a href="/sia/lms/admin/cloner" class="btn btn-outline-primary border rounded-pill px-3 py-2 fw-medium d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift">
            <i class="bi bi-copy"></i>
            <span>Template Cloner</span>
          </a>
          <a href="/sia/lms/admin/dashboard" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs hover-lift">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>LMS Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Domain Boundary & Data Integrity Invariant Callout -->
    <div class="alert alert-info border-0 shadow-sm rounded-4 d-flex align-items-start gap-3 mb-4 p-3.5 fade-in-up" style="background-color: #f0f7ff; border-left: 4px solid #0d6efd !important;">
      <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center p-2 mt-0.5 flex-shrink-0" style="width: 32px; height: 32px;">
        <i class="bi bi-shield-check"></i>
      </div>
      <div>
        <h6 class="fw-bold text-primary mb-1">Architectural Rule: High-Risk Conflict Resolution &amp; Data Preservation</h6>
        <p class="text-secondary small mb-0">
          <strong>ENROLLMENT OWNS ACADEMIC TRUTH. LMS OWNS THE LEARNING EXPERIENCE.</strong><br>
          LMS Admin may resolve shell inconsistencies, but must <strong>never change official sections, subject codes, curriculum, or registrar records</strong>.
          Automatic destructive merging of student submissions or quiz attempts across course boundaries is <strong>strictly prohibited</strong> to prevent student grade corruption.
          The system enforces conservative preservation: archiving redundant shells, preserving all student work, and restricting deletion to 100% empty, detached shells.
        </p>
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

    <!-- Diagnostic Summary Status Cards Grid (5-column KPI grid matching registrar design) -->
    <div class="row g-4 mb-4">
      <div class="col-sm-6 col-lg-4 col-xl">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.1s;">
          <div class="stat-card-glow bg-success"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
              <i class="bi bi-check2-circle"></i>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
              <i class="bi bi-shield-check me-1"></i> Aligned
            </span>
          </div>
          <div class="stat-number-display mb-1 text-success"><?= (int)$healthyCount ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Healthy In-Sync</h2>
          <p class="text-muted small mb-0">Course shells 100% aligned</p>
          <div class="stat-card-footer">
            <span>Timetable integrity</span>
            <span class="stat-card-action text-success">Verified <i class="bi bi-check2"></i></span>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-4 col-xl">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.15s;">
          <div class="stat-card-glow <?= $missingCount > 0 ? 'bg-danger' : 'bg-success' ?>"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper <?= $missingCount > 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-light text-muted' ?>">
              <i class="bi bi-plus-square"></i>
            </div>
            <span class="badge <?= $missingCount > 0 ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' : 'bg-light text-muted border' ?> rounded-pill px-2.5 py-1 small fw-semibold">
              Missing
            </span>
          </div>
          <div class="stat-number-display mb-1 <?= $missingCount > 0 ? 'text-danger' : 'text-dark' ?>"><?= (int)$missingCount ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Missing Shells</h2>
          <p class="text-muted small mb-0">In timetable, no LMS shell</p>
          <div class="stat-card-footer">
            <span>Needs provisioning</span>
            <span class="stat-card-action <?= $missingCount > 0 ? 'text-danger' : 'text-muted' ?>">Reconcile <i class="bi bi-arrow-right"></i></span>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-4 col-xl">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.2s;">
          <div class="stat-card-glow <?= $mismatchCount > 0 ? 'bg-warning' : 'bg-success' ?>"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper <?= $mismatchCount > 0 ? 'bg-warning bg-opacity-10 text-warning' : 'bg-light text-muted' ?>">
              <i class="bi bi-person-x"></i>
            </div>
            <span class="badge <?= $mismatchCount > 0 ? 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25' : 'bg-light text-muted border' ?> rounded-pill px-2.5 py-1 small fw-semibold">
              Mismatch
            </span>
          </div>
          <div class="stat-number-display mb-1 <?= $mismatchCount > 0 ? 'text-warning' : 'text-dark' ?>"><?= (int)$mismatchCount ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Faculty Mismatches</h2>
          <p class="text-muted small mb-0">LMS instructor != Timetable</p>
          <div class="stat-card-footer">
            <span>Staffing sync</span>
            <span class="stat-card-action <?= $mismatchCount > 0 ? 'text-warning' : 'text-muted' ?>">Align <i class="bi bi-arrow-right"></i></span>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-4 col-xl">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.25s;">
          <div class="stat-card-glow <?= $duplicateGroupCount > 0 ? 'bg-danger' : 'bg-success' ?>"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper <?= $duplicateGroupCount > 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-light text-muted' ?>">
              <i class="bi bi-files"></i>
            </div>
            <span class="badge <?= $duplicateGroupCount > 0 ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' : 'bg-light text-muted border' ?> rounded-pill px-2.5 py-1 small fw-semibold">
              Duplicates
            </span>
          </div>
          <div class="stat-number-display mb-1 <?= $duplicateGroupCount > 0 ? 'text-danger' : 'text-dark' ?>"><?= (int)$duplicateGroupCount ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Duplicate Shells</h2>
          <p class="text-muted small mb-0"><?= (int)$duplicateShellCount ?> conflicting spaces</p>
          <div class="stat-card-footer">
            <span>Deduplication</span>
            <span class="stat-card-action <?= $duplicateGroupCount > 0 ? 'text-danger' : 'text-muted' ?>">Inspect <i class="bi bi-arrow-right"></i></span>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-lg-4 col-xl">
        <div class="stat-card-kpi fade-in-up" style="animation-delay: 0.3s;">
          <div class="stat-card-glow <?= $orphanCount > 0 ? 'bg-secondary' : 'bg-success' ?>"></div>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon-wrapper <?= $orphanCount > 0 ? 'bg-secondary bg-opacity-10 text-secondary' : 'bg-light text-muted' ?>">
              <i class="bi bi-diagram-3"></i>
            </div>
            <span class="badge <?= $orphanCount > 0 ? 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25' : 'bg-light text-muted border' ?> rounded-pill px-2.5 py-1 small fw-semibold">
              Detached
            </span>
          </div>
          <div class="stat-number-display mb-1 <?= $orphanCount > 0 ? 'text-danger' : 'text-dark' ?>"><?= (int)$orphanCount ?></div>
          <h2 class="h6 fw-bold text-dark mb-1">Orphan Courses</h2>
          <p class="text-muted small mb-0">Detached / Cancelled shells</p>
          <div class="stat-card-footer">
            <span>Archive safe</span>
            <span class="stat-card-action <?= $orphanCount > 0 ? 'text-secondary' : 'text-muted' ?>">Resolve <i class="bi bi-arrow-right"></i></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Diagnostic Navigation Tabs -->
    <ul class="nav nav-pills mb-4 gap-2" id="syncTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active rounded-pill px-4 py-2.5 fw-semibold small d-inline-flex align-items-center gap-2" id="reconcile-tab" data-bs-toggle="tab" data-bs-target="#reconcile-pane" type="button" role="tab">
          <i class="bi bi-arrow-repeat"></i>
          <span>Timetable Reconciliation</span>
          <?php if ($missingCount + $mismatchCount > 0): ?>
            <span class="badge bg-warning text-dark rounded-pill px-2 py-0.5"><?= $missingCount + $mismatchCount ?></span>
          <?php endif; ?>
        </button>
      </li>

      <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4 py-2.5 fw-semibold small d-inline-flex align-items-center gap-2" id="duplicates-tab" data-bs-toggle="tab" data-bs-target="#duplicates-pane" type="button" role="tab">
          <i class="bi bi-files"></i>
          <span>Duplicate Shell Conflicts</span>
          <?php if ($duplicateGroupCount > 0): ?>
            <span class="badge bg-danger rounded-pill px-2 py-0.5"><?= $duplicateGroupCount ?></span>
          <?php endif; ?>
        </button>
      </li>

      <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4 py-2.5 fw-semibold small d-inline-flex align-items-center gap-2" id="orphans-tab" data-bs-toggle="tab" data-bs-target="#orphans-pane" type="button" role="tab">
          <i class="bi bi-diagram-3"></i>
          <span>Orphan Course Shells</span>
          <?php if ($orphanCount > 0): ?>
            <span class="badge bg-danger rounded-pill px-2 py-0.5"><?= $orphanCount ?></span>
          <?php endif; ?>
        </button>
      </li>
    </ul>

    <div class="tab-content" id="syncTabContent">

      <!-- ========================================== -->
      <!-- TAB 1: TIMETABLE RECONCILIATION            -->
      <!-- ========================================== -->
      <div class="tab-pane fade show active" id="reconcile-pane" role="tabpanel">
        
        <!-- Missing LMS Course Shells -->
        <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden mb-4">
          <div class="p-3.5 px-4 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="fw-bold text-dark mb-0">
              <i class="bi bi-plus-square text-danger me-2"></i> Missing LMS Course Shells (<?= $missingCount ?>)
            </h6>
            <?php if ($missingCount > 0): ?>
              <form action="/sia/lms/admin/sync/reconcile" method="POST" class="m-0" onsubmit="return confirm('Auto-provision missing course shells from official timetable?');">
                <?= getCsrfInput() ?>
                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">Auto-Provision All</button>
              </form>
            <?php endif; ?>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="bg-light text-muted small text-uppercase">
                <tr>
                  <th class="ps-4">Academic Level</th>
                  <th>Section Code</th>
                  <th>Subject</th>
                  <th>Timetable Instructor</th>
                  <th class="text-end pe-4">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($syncReport['missing_courses'])): ?>
                  <tr>
                    <td colspan="5" class="text-center py-4 text-muted"><div class="lms-table-empty"><i class="bi bi-check2 text-success me-1"></i> All active timetable offerings have active LMS course shells.</div></td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($syncReport['missing_courses'] as $m): ?>
                    <tr>
                      <td class="ps-4"><span class="badge bg-light text-dark border rounded-pill px-2.5 py-0.5"><?= htmlspecialchars($m['academic_level'], ENT_QUOTES, 'UTF-8') ?></span></td>
                      <td class="fw-semibold text-dark"><?= htmlspecialchars($m['section_code'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td>
                        <strong><?= htmlspecialchars($m['subject_code'], ENT_QUOTES, 'UTF-8') ?></strong> &bull; <?= htmlspecialchars($m['subject_name'], ENT_QUOTES, 'UTF-8') ?>
                      </td>
                      <td><?= htmlspecialchars($m['timetable_instructor'] ?: 'TBA (Unassigned)', ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="text-end pe-4"><span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2.5 py-1">Missing Shell</span></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Faculty Mismatches -->
        <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden mb-4">
          <div class="p-3.5 px-4 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="fw-bold text-dark mb-0">
              <i class="bi bi-person-x text-warning me-2"></i> Faculty Assignment Mismatches (<?= $mismatchCount ?>)
            </h6>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="bg-light text-muted small text-uppercase">
                <tr>
                  <th class="ps-4">Course Shell</th>
                  <th>Section</th>
                  <th>Subject</th>
                  <th>Current LMS Instructor</th>
                  <th>Timetable Schedule Instructor</th>
                  <th class="text-end pe-4">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($syncReport['faculty_mismatches'])): ?>
                  <tr>
                    <td colspan="6" class="text-center py-4 text-muted"><div class="lms-table-empty"><i class="bi bi-check2 text-success me-1"></i> All LMS courses match their authoritative scheduling instructors.</div></td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($syncReport['faculty_mismatches'] as $f): ?>
                    <tr>
                      <td class="ps-4 fw-bold">
                        <a href="/sia/lms/admin/courses/<?= (int)$f['lms_course_id'] ?>" class="text-decoration-none">
                          #<?= (int)$f['lms_course_id'] ?>
                        </a>
                      </td>
                      <td><?= htmlspecialchars($f['section_code'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><strong><?= htmlspecialchars($f['subject_code'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                      <td class="text-danger fw-semibold"><?= htmlspecialchars($f['lms_instructor'] ?: 'TBA (Unassigned)', ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="text-success fw-semibold"><?= htmlspecialchars($f['timetable_instructor'] ?: 'TBA', ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="text-end pe-4"><span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2.5 py-1">Mismatch</span></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- ========================================== -->
      <!-- TAB 2: DUPLICATE COURSE SHELL CONFLICTS    -->
      <!-- ========================================== -->
      <div class="tab-pane fade" id="duplicates-pane" role="tabpanel">
        
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-4 bg-white border-start border-4 border-danger">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h5 class="fw-bold text-dark mb-0">
              <i class="bi bi-files text-danger me-2"></i> Duplicate Course Shell Conflicts & Forensics
            </h5>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1 fw-bold">
              Automatic Merging Prohibited
            </span>
          </div>
          <p class="text-muted small mb-0">
            Duplicate course shells occur when multiple LMS instances map to the same section and subject offering. 
            <strong>DO NOT automatically merge or delete duplicate shells containing student work.</strong> 
            Moving submissions or quiz attempts across course boundaries causes gradebook collision, breaks foreign key constraints, and corrupts academic transcripts. 
            Review the side-by-side artifact dossiers below and apply safe administrative preservation.
          </p>
        </div>

        <?php if (empty($conflictReport['duplicate_groups'])): ?>
          <div class="card border-0 shadow-sm rounded-4 p-5 bg-white text-center">
            <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-3" style="width: 64px; height: 64px; font-size: 1.8rem;">
              <i class="bi bi-check-circle-fill"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Zero Duplicate Course Shells Detected</h5>
            <p class="text-muted small mb-0">All active course shells conform to the uniqueness constraint <code class="bg-light px-2 py-0.5 rounded text-dark">UNIQUE(academic_level, academic_section_id, subject_id)</code>.</p>
          </div>
        <?php else: ?>
          <?php foreach ($conflictReport['duplicate_groups'] as $gIndex => $group): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white">
              
              <!-- Group Header -->
              <div class="card-header bg-light border-bottom p-3.5 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2.5">
                  <span class="badge bg-danger rounded-pill px-2.5 py-1 fw-bold">Group #<?= $gIndex + 1 ?></span>
                  <h6 class="fw-bold text-dark mb-0">
                    <?= htmlspecialchars($group['shells'][0]['subject_code'] ?? 'Subject') ?> &bull; 
                    <?= htmlspecialchars($group['shells'][0]['section_code'] ?? 'Section') ?> 
                    (<?= htmlspecialchars($group['shells'][0]['academic_level'] ?? '') ?>)
                  </h6>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <?php if ($group['multiple_have_student_work']): ?>
                    <span class="badge bg-danger rounded-pill px-3 py-1.5 fw-bold">
                      <i class="bi bi-exclamation-octagon-fill me-1"></i> Manual Resolution Required — Multiple Shells Have Student Work
                    </span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1.5 fw-bold">
                      <i class="bi bi-shield-check me-1"></i> Safe Archive Recommended
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="card-body p-4">
                
                <!-- Strategy Alert -->
                <div class="p-3 rounded-3 bg-light border mb-4 small d-flex align-items-start gap-2.5">
                  <i class="bi bi-info-circle-fill text-primary fs-5 mt-0.5"></i>
                  <div>
                    <strong class="text-dark">Forensic Diagnostic Strategy:</strong>
                    <div class="text-muted mt-0.5"><?= htmlspecialchars($group['recommendation'], ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                </div>

                <!-- Side-by-Side Shell Cards Grid -->
                <div class="row g-3 mb-3">
                  <?php foreach ($group['shells'] as $shell): ?>
                    <div class="col-lg-<?= count($group['shells']) > 2 ? '4' : '6' ?>">
                      <div class="card border rounded-4 h-100 p-3.5 bg-white <?= $shell['has_student_work'] ? 'border-primary shadow-xs' : 'border-secondary-subtle' ?>">
                        
                        <div class="d-flex align-items-center justify-content-between mb-2">
                          <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-1 fw-bold">
                            Shell #<?= (int)$shell['id'] ?>
                          </span>
                          <span class="badge <?= $shell['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?> rounded-pill px-2.5 py-0.5 small">
                            <?= ucfirst($shell['status']) ?>
                          </span>
                        </div>

                        <h6 class="fw-bold text-dark mb-1">
                          <?= htmlspecialchars($shell['subject_name'] ?? '') ?>
                        </h6>
                        <p class="text-muted small mb-2.5">
                          Created: <?= date('M d, Y', strtotime($shell['created_at'])) ?> &bull; 
                          Instructor: <strong class="text-dark"><?= htmlspecialchars(($shell['first_name'] ?? '') . ' ' . ($shell['last_name'] ?? 'Unassigned')) ?></strong>
                        </p>

                        <!-- Artifact Counters Grid -->
                        <div class="row g-2 text-center small mb-3">
                          <div class="col-4">
                            <div class="p-2 border rounded-3 bg-light">
                              <div class="fw-bold text-dark"><?= (int)$shell['modules_count'] ?></div>
                              <div class="text-muted" style="font-size: 0.72rem;">Modules</div>
                            </div>
                          </div>
                          <div class="col-4">
                            <div class="p-2 border rounded-3 bg-light">
                              <div class="fw-bold text-dark"><?= (int)$shell['assignments_count'] ?> / <?= (int)$shell['quizzes_count'] ?></div>
                              <div class="text-muted" style="font-size: 0.72rem;">Assg / Quiz</div>
                            </div>
                          </div>
                          <div class="col-4">
                            <div class="p-2 border rounded-3 bg-light">
                              <div class="fw-bold text-dark"><?= (int)$shell['roster_count'] ?></div>
                              <div class="text-muted" style="font-size: 0.72rem;">Students</div>
                            </div>
                          </div>
                        </div>

                        <!-- Student Artifacts Highlight -->
                        <div class="p-2.5 rounded-3 mb-3 small <?= $shell['has_student_work'] ? 'bg-danger bg-opacity-10 border border-danger border-opacity-25' : 'bg-light text-muted' ?>">
                          <div class="d-flex justify-content-between mb-1">
                            <span>Submissions:</span>
                            <strong class="<?= $shell['submissions_count'] > 0 ? 'text-danger' : 'text-dark' ?>"><?= (int)$shell['submissions_count'] ?></strong>
                          </div>
                          <div class="d-flex justify-content-between mb-1">
                            <span>Quiz Attempts:</span>
                            <strong class="<?= $shell['quiz_attempts_count'] > 0 ? 'text-danger' : 'text-dark' ?>"><?= (int)$shell['quiz_attempts_count'] ?></strong>
                          </div>
                          <div class="d-flex justify-content-between">
                            <span>Attendance Records:</span>
                            <strong class="<?= $shell['attendance_records_count'] > 0 ? 'text-danger' : 'text-dark' ?>"><?= (int)$shell['attendance_records_count'] ?></strong>
                          </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-auto d-flex flex-column gap-1.5 pt-2 border-top">
                          <div class="d-flex gap-2">
                            <!-- Archive Shell Button (Safe Preservation) -->
                            <?php if ($shell['status'] === 'active'): ?>
                              <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill w-100 fw-medium d-inline-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#archiveModal<?= (int)$shell['id'] ?>">
                                <i class="bi bi-archive"></i>
                                <span>Archive Shell</span>
                              </button>
                            <?php else: ?>
                              <span class="badge bg-light text-muted border rounded-pill w-100 py-1.5">Already Archived</span>
                            <?php endif; ?>

                            <!-- Flag for Review -->
                            <button type="button" class="btn btn-sm btn-light border rounded-pill w-100 fw-medium d-inline-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#quarantineModal<?= (int)$shell['id'] ?>">
                              <i class="bi bi-flag"></i>
                              <span>Flag Review</span>
                            </button>
                          </div>

                          <!-- Safe Delete (Only if 100% empty) -->
                          <?php if ($shell['is_empty_shell']): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill w-100 fw-medium d-inline-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#deleteEmptyModal<?= (int)$shell['id'] ?>">
                              <i class="bi bi-trash"></i>
                              <span>Safe Delete (Empty Shell)</span>
                            </button>
                          <?php else: ?>
                            <button type="button" class="btn btn-sm btn-light text-muted border rounded-pill w-100" disabled title="Cannot delete: Shell contains student submissions or instructional content. Archive instead.">
                              <i class="bi bi-slash-circle me-1"></i> Deletion Prohibited (Contains Data)
                            </button>
                          <?php endif; ?>
                        </div>

                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>

      <!-- ========================================== -->
      <!-- TAB 3: ORPHAN COURSE SHELLS                -->
      <!-- ========================================== -->
      <div class="tab-pane fade" id="orphans-pane" role="tabpanel">
        
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-4 bg-white border-start border-4 border-warning">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h5 class="fw-bold text-dark mb-0">
              <i class="bi bi-diagram-3 text-warning me-2"></i> Orphan LMS Course Shells
            </h5>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-1 fw-bold">
              Preservation Over Deletion
            </span>
          </div>
          <p class="text-muted small mb-0">
            Orphan courses are LMS shells whose academic section, subject catalog entry, or official timetable scheduling offering was deleted or cancelled in the Registrar/Scheduler system.
            <strong>Orphan courses must NOT automatically be deleted.</strong> They frequently contain submitted coursework, student grades, and lecture materials from prior semesters.
          </p>
        </div>

        <?php if (empty($conflictReport['orphan_courses'])): ?>
          <div class="card border-0 shadow-sm rounded-4 p-5 bg-white text-center">
            <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-3" style="width: 64px; height: 64px; font-size: 1.8rem;">
              <i class="bi bi-check-circle-fill"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Zero Orphan Course Shells Detected</h5>
            <p class="text-muted small mb-0">All existing course shells map to verified, active subjects, sections, and scheduled timetable offerings.</p>
          </div>
        <?php else: ?>
          <div class="dossier-card bg-white border rounded-4 shadow-sm overflow-hidden mb-4">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                  <tr>
                    <th class="ps-4">Course Shell</th>
                    <th>Orphan Diagnostics</th>
                    <th>Instructional Content</th>
                    <th>Student Work</th>
                    <th>Recommended Action</th>
                    <th class="text-end pe-4">Resolution</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($conflictReport['orphan_courses'] as $orphan): ?>
                    <tr>
                      <td class="ps-4">
                        <strong class="text-dark d-block">
                          <a href="/sia/lms/admin/courses/<?= (int)$orphan['id'] ?>" class="text-decoration-none">
                            #<?= (int)$orphan['id'] ?>: <?= htmlspecialchars($orphan['subject_code'] ?? 'Unknown') ?>
                          </a>
                        </strong>
                        <span class="text-muted small">
                          Section: <?= htmlspecialchars($orphan['section_code'] ?? 'Detached') ?> (<?= htmlspecialchars($orphan['academic_level']) ?>)
                        </span>
                      </td>

                      <td>
                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold d-inline-block mb-1">
                          <?= htmlspecialchars($orphan['orphan_category']) ?>
                        </span>
                        <div class="text-muted small" style="max-width: 280px;">
                          <?= htmlspecialchars($orphan['orphan_reason'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                      </td>

                      <td>
                        <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small">
                          <?= (int)$orphan['modules_count'] ?> mods, <?= (int)$orphan['materials_count'] ?> files
                        </span>
                        <div class="text-muted small mt-0.5">
                          <?= (int)$orphan['assignments_count'] ?> assg, <?= (int)$orphan['quizzes_count'] ?> quiz
                        </div>
                      </td>

                      <td>
                        <?php if ($orphan['has_student_work']): ?>
                          <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 small fw-bold">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Student Work Exists
                          </span>
                          <div class="text-muted small mt-0.5">
                            <?= (int)$orphan['submissions_count'] ?> sub, <?= (int)$orphan['quiz_attempts_count'] ?> att
                          </div>
                        <?php else: ?>
                          <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-0.5 small">
                            Zero Student Work
                          </span>
                        <?php endif; ?>
                      </td>

                      <td>
                        <div class="text-dark small fw-medium" style="max-width: 260px;">
                          <?= htmlspecialchars($orphan['recommendation'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                      </td>

                      <td class="text-end pe-4">
                        <div class="d-inline-flex align-items-center gap-1.5">
                          <?php if ($orphan['status'] === 'active'): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1" data-bs-toggle="modal" data-bs-target="#archiveModal<?= (int)$orphan['id'] ?>" title="Archive to preserve student submissions">
                              <i class="bi bi-archive me-1"></i> Archive
                            </button>
                          <?php else: ?>
                            <span class="badge bg-light text-muted border rounded-pill px-2 py-1">Archived</span>
                          <?php endif; ?>

                          <button type="button" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1" data-bs-toggle="modal" data-bs-target="#quarantineModal<?= (int)$orphan['id'] ?>" title="Flag for registrar investigation">
                            <i class="bi bi-flag"></i>
                          </button>

                          <?php if ($orphan['can_safe_delete']): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1" data-bs-toggle="modal" data-bs-target="#deleteEmptyModal<?= (int)$orphan['id'] ?>" title="Delete empty detached shell">
                              <i class="bi bi-trash"></i>
                            </button>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>

      </div>

    </div>

    <!-- ==================================================== -->
    <!-- DYNAMIC RESOLUTION MODALS                            -->
    <!-- ==================================================== -->
    
    <!-- Build Modals for All Duplicate & Orphan Shells -->
    <?php 
    $allConflictShells = [];
    foreach ($conflictReport['duplicate_groups'] ?? [] as $dg) {
        foreach ($dg['shells'] as $s) {
            $allConflictShells[$s['id']] = $s;
        }
    }
    foreach ($conflictReport['orphan_courses'] ?? [] as $o) {
        $allConflictShells[$o['id']] = $o;
    }
    ?>

    <?php foreach ($allConflictShells as $cid => $cs): ?>
      
      <!-- Modal: Archive Course Shell -->
      <div class="modal fade" id="archiveModal<?= (int)$cid ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom p-4">
              <h5 class="modal-title fw-bold text-dark">
                <i class="bi bi-archive text-secondary me-2"></i> Archive Course Shell #<?= (int)$cid ?>
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/sia/lms/admin/conflicts/resolve" method="POST">
              <?= getCsrfInput() ?>
              <input type="hidden" name="resolution_action" value="archive_shell">
              <input type="hidden" name="course_id" value="<?= (int)$cid ?>">
              
              <div class="modal-body p-4">
                <p class="text-dark small mb-3">
                  You are about to mark course shell <strong>#<?= (int)$cid ?> (<?= htmlspecialchars($cs['subject_code'] ?? '') ?>)</strong> as <strong>Archived</strong>.
                </p>
                <div class="alert alert-success bg-opacity-10 border-0 p-3 small mb-3 rounded-3 text-dark">
                  <i class="bi bi-shield-check text-success me-1"></i>
                  <strong>Safe Preservation Guarantee:</strong> Archiving removes this shell from active student/faculty listings while preserving 100% of modules, student submissions (<?= (int)$cs['submissions_count'] ?>), quiz attempts (<?= (int)$cs['quiz_attempts_count'] ?>), and attendance records.
                </div>
                <div class="mb-3">
                  <label for="notesArchive<?= (int)$cid ?>" class="form-label small fw-bold text-dark text-uppercase">Administrative Resolution Notes:</label>
                  <textarea name="notes" id="notesArchive<?= (int)$cid ?>" class="form-control" rows="2" placeholder="e.g. Archived redundant shell; primary shell #... contains active student submissions."></textarea>
                </div>
              </div>
              <div class="modal-footer border-top p-3.5 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-light border rounded-pill px-3 py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-secondary rounded-pill px-4 py-2 fw-bold">Confirm Safe Archive</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Modal: Quarantine / Flag for Review -->
      <div class="modal fade" id="quarantineModal<?= (int)$cid ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom p-4">
              <h5 class="modal-title fw-bold text-dark">
                <i class="bi bi-flag text-warning me-2"></i> Flag Shell #<?= (int)$cid ?> for Manual Review
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/sia/lms/admin/conflicts/resolve" method="POST">
              <?= getCsrfInput() ?>
              <input type="hidden" name="resolution_action" value="flag_quarantine">
              <input type="hidden" name="course_id" value="<?= (int)$cid ?>">
              
              <div class="modal-body p-4">
                <p class="text-dark small mb-3">
                  Record an administrative flag in the LMS audit trail to mark shell <strong>#<?= (int)$cid ?></strong> for investigation by the Registrar or Department Chair.
                </p>
                <div class="mb-3">
                  <label for="notesFlag<?= (int)$cid ?>" class="form-label small fw-bold text-dark text-uppercase">Investigation Reason / Notes:</label>
                  <textarea name="notes" id="notesFlag<?= (int)$cid ?>" class="form-control" rows="3" required placeholder="Specify discrepancies, student enrollment inquiries, or scheduling questions..."></textarea>
                </div>
              </div>
              <div class="modal-footer border-top p-3.5 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-light border rounded-pill px-3 py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning rounded-pill px-4 py-2 fw-bold">Log Review Flag</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Modal: Safe Delete (Empty Only) -->
      <?php if (!empty($cs['is_empty_shell'])): ?>
        <div class="modal fade" id="deleteEmptyModal<?= (int)$cid ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
              <div class="modal-header border-bottom p-4">
                <h5 class="modal-title fw-bold text-danger">
                  <i class="bi bi-trash text-danger me-2"></i> Remove Empty Shell #<?= (int)$cid ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <form action="/sia/lms/admin/conflicts/resolve" method="POST">
                <?= getCsrfInput() ?>
                <input type="hidden" name="resolution_action" value="delete_empty_shell">
                <input type="hidden" name="course_id" value="<?= (int)$cid ?>">
                
                <div class="modal-body p-4">
                  <p class="text-dark small mb-3">
                    Are you sure you want to delete empty course shell <strong>#<?= (int)$cid ?></strong>?
                  </p>
                  <div class="alert alert-warning border-0 p-3 small mb-3 rounded-3">
                    <i class="bi bi-check-circle text-success me-1"></i>
                    <strong>System Safety Verification:</strong> This shell has been verified to have <strong>0 modules, 0 materials, 0 assignments, 0 quizzes, and 0 student submissions</strong>. It is completely empty and unlinked from the timetable.
                  </div>
                  <div class="mb-3">
                    <label for="notesDel<?= (int)$cid ?>" class="form-label small fw-bold text-dark text-uppercase">Administrative Justification:</label>
                    <input type="text" name="notes" id="notesDel<?= (int)$cid ?>" class="form-control" placeholder="e.g. Empty redundant shell removed after schedule cancellation." required>
                  </div>
                </div>
                <div class="modal-footer border-top p-3.5 px-4 d-flex justify-content-between">
                  <button type="button" class="btn btn-light border rounded-pill px-3 py-2" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-danger rounded-pill px-4 py-2 fw-bold">Confirm Deletion</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endif; ?>

    <?php endforeach; ?>

  </div>
</main>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
