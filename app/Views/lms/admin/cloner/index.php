<?php
$pageTitle = 'Course Content / Syllabus Template Cloner - LMS Admin';
require_once __DIR__ . '/../layout_header.php';

$sourceCourseId = $sourceCourseId ?? 0;
$targetCourseId = $targetCourseId ?? 0;
$preview = $preview ?? null;
?>

<main class="py-5 bg-light min-vh-100">
  <div class="container-fluid px-lg-5">
    
    <!-- Hero Header Strip -->
    <div class="dossier-hero-strip mb-4 fade-in-up">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
            <i class="bi bi-copy"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <h1 class="h4 fw-bold text-dark mb-0">Course Content / Syllabus Template Cloner</h1>
              <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-shield-lock-fill me-1"></i> Atomic PDO Transaction
              </span>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                <i class="bi bi-mortarboard-fill me-1"></i> Instructional Reusability
              </span>
            </div>
            <p class="text-muted small mb-0">Replicate syllabus modules, learning materials, assignment prompts, and quizzes from completed or template courses into active course shells.</p>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="/sia/lms/admin/courses" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-collection text-primary"></i>
            <span>Course Catalog</span>
          </a>
          <a href="/sia/lms/admin/dashboard" class="btn btn-light border rounded-pill px-3 py-2 fw-medium text-dark d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left text-primary"></i>
            <span>LMS Dashboard</span>
          </a>
        </div>
      </div>
    </div>

    <!-- 3-Step Visual Stepper -->
    <div class="dossier-card mb-4 p-3 bg-white">
      <div class="lms-cloner-stepper shadow-none mb-0">
        <div class="lms-step-item <?= $preview ? 'completed' : 'active' ?>">
          <div class="lms-step-number"><?= $preview ? '<i class="bi bi-check-lg"></i>' : '1' ?></div>
          <div>
            <div class="small fw-bold">Step 1</div>
            <div style="font-size: 0.75rem;" class="text-muted">Select Shells</div>
          </div>
        </div>
        <div class="lms-step-divider"></div>
        <div class="lms-step-item <?= $preview ? 'active' : '' ?>">
          <div class="lms-step-number">2</div>
          <div>
            <div class="small fw-bold">Step 2</div>
            <div style="font-size: 0.75rem;" class="text-muted">Preview Content</div>
          </div>
        </div>
        <div class="lms-step-divider"></div>
        <div class="lms-step-item">
          <div class="lms-step-number">3</div>
          <div>
            <div class="small fw-bold">Step 3</div>
            <div style="font-size: 0.75rem;" class="text-muted">Atomic Replicate</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Institutional Domain Boundary Callout -->
    <div class="alert alert-info border-0 shadow-sm rounded-4 d-flex align-items-start gap-3 mb-4 p-3.5" style="background-color: #f0f7ff; border-left: 4px solid #0d6efd !important;">
      <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center p-2 mt-0.5 flex-shrink-0" style="width: 32px; height: 32px;">
        <i class="bi bi-shield-check"></i>
      </div>
      <div>
        <h6 class="fw-bold text-primary mb-1">Architectural Domain Invariant: Academic Truth vs Learning Experience</h6>
        <p class="text-secondary small mb-0">
          <strong>ENROLLMENT OWNS ACADEMIC TRUTH. LMS OWNS THE LEARNING EXPERIENCE.</strong><br>
          This administrative cloner replicates <em>only</em> reusable instructional structures: modules, lecture attachments, assignment prompts, and quizzes. It <strong>never</strong> clones student enrollments, submissions, quiz attempts, answers, official sections, schedules, attendance, or registrar grades. Cloned assignments and quizzes have their term due dates and schedule windows reset to <code class="text-dark bg-white px-1 rounded">NULL</code>.
        </p>
      </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4 p-3">
        <i class="bi bi-check-circle-fill fs-5 text-success"></i>
        <div class="small fw-semibold"><?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-2 mb-4 p-3">
        <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
        <div class="small fw-semibold"><?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Course Selection Panel -->
    <div class="dossier-card mb-4 overflow-hidden border-0 shadow-sm">
      <div class="dossier-card-header bg-white border-bottom p-3.5 px-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 32px; height: 32px;">
            <i class="bi bi-sliders"></i>
          </div>
          <h2 class="h5 fw-bold text-dark mb-0">Step 1: Select Source Template & Target Shell</h2>
        </div>
        <span class="text-muted small">Choose origin content course & destination course shell</span>
      </div>
      <div class="card-body p-4">
        <form method="GET" action="/sia/lms/admin/cloner" class="row g-3 align-items-end">
          
          <!-- Source Course -->
          <div class="col-md-5">
            <label for="source_id" class="form-label fw-bold text-dark small text-uppercase">
              <i class="bi bi-box-arrow-up-right text-primary me-1"></i> Source Course (Template / Origin)
            </label>
            <select name="source_id" id="source_id" class="form-select rounded-3 py-2.5" required>
              <option value="">-- Choose Source Course --</option>
              <optgroup label="Archived / Completed Courses (Recommended Templates)">
                <?php foreach ($courses as $c): ?>
                  <?php if ($c['status'] === 'archived'): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $sourceCourseId === (int)$c['id'] ? 'selected' : '' ?>>
                      #<?= (int)$c['id'] ?>: <?= htmlspecialchars($c['subject_code']) ?> - <?= htmlspecialchars($c['subject_name']) ?> (<?= htmlspecialchars($c['section_code'] ?? 'No Section') ?> | <?= (int)$c['modules_count'] ?> mods, <?= (int)$c['assignments_count'] ?> assg, <?= (int)$c['quizzes_count'] ?> quiz) [ARCHIVED]
                    </option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="Active Courses (Live Offerings)">
                <?php foreach ($courses as $c): ?>
                  <?php if ($c['status'] === 'active'): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $sourceCourseId === (int)$c['id'] ? 'selected' : '' ?>>
                      #<?= (int)$c['id'] ?>: <?= htmlspecialchars($c['subject_code']) ?> - <?= htmlspecialchars($c['subject_name']) ?> (<?= htmlspecialchars($c['section_code'] ?? 'No Section') ?> | <?= (int)$c['modules_count'] ?> mods, <?= (int)$c['assignments_count'] ?> assg, <?= (int)$c['quizzes_count'] ?> quiz)
                    </option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </optgroup>
            </select>
            <div class="form-text small">Select an archived semester shell or active course with published modules.</div>
          </div>

          <!-- Target Course -->
          <div class="col-md-5">
            <label for="target_id" class="form-label fw-bold text-dark small text-uppercase">
              <i class="bi bi-box-arrow-in-down-right text-success me-1"></i> Target Course (Destination Shell)
            </label>
            <select name="target_id" id="target_id" class="form-select rounded-3 py-2.5" required>
              <option value="">-- Choose Target Course --</option>
              <optgroup label="Active Courses (Destination Shells)">
                <?php foreach ($courses as $c): ?>
                  <?php if ($c['status'] === 'active'): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $targetCourseId === (int)$c['id'] ? 'selected' : '' ?>>
                      #<?= (int)$c['id'] ?>: <?= htmlspecialchars($c['subject_code']) ?> - <?= htmlspecialchars($c['subject_name']) ?> (<?= htmlspecialchars($c['section_code'] ?? 'No Section') ?> | Current: <?= (int)$c['modules_count'] ?> mods)
                    </option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="Archived Courses">
                <?php foreach ($courses as $c): ?>
                  <?php if ($c['status'] === 'archived'): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $targetCourseId === (int)$c['id'] ? 'selected' : '' ?>>
                      #<?= (int)$c['id'] ?>: <?= htmlspecialchars($c['subject_code']) ?> - <?= htmlspecialchars($c['subject_name']) ?> (<?= htmlspecialchars($c['section_code'] ?? 'No Section') ?>) [ARCHIVED]
                    </option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </optgroup>
            </select>
            <div class="form-text small">Select the course shell that will receive the cloned instructional hierarchy.</div>
          </div>

          <!-- Inspect & Preview Button -->
          <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary rounded-pill py-2.5 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm">
              <i class="bi bi-eye"></i>
              <span>Inspect & Preview</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <?php if ($preview): ?>
      <!-- Preview & Validation Dossier -->
      <div class="row g-4 mb-4 fade-in-up" style="animation-delay: 0.2s;">
        
        <!-- Source vs Target Comparison Cards -->
        <div class="col-lg-6">
          <div class="dossier-card h-100 p-4 bg-white border-top border-4 border-primary">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1 fw-bold">
                <i class="bi bi-box-arrow-up-right me-1"></i> SOURCE TEMPLATE
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small">
                Shell #<?= (int)$preview['source_course']['id'] ?>
              </span>
            </div>
            
            <h5 class="fw-bold text-dark mb-1">
              <?= htmlspecialchars($preview['source_course']['subject_code']) ?> — <?= htmlspecialchars($preview['source_course']['subject_name']) ?>
            </h5>
            <p class="text-muted small mb-3">
              Section: <strong class="text-dark"><?= htmlspecialchars($preview['source_course']['section_code'] ?? 'N/A') ?></strong> |
              Term: <?= htmlspecialchars($preview['source_course']['academic_year'] ?? '') ?> (<?= htmlspecialchars($preview['source_course']['semester'] ?? '') ?>) |
              Level: <?= htmlspecialchars($preview['source_course']['academic_level']) ?>
            </p>

            <div class="p-3 bg-light rounded-3 mb-3 small">
              <div class="d-flex justify-content-between mb-1.5">
                <span class="text-muted">Instructor:</span>
                <span class="fw-semibold text-dark">
                  <?= htmlspecialchars(($preview['source_course']['first_name'] ?? '') . ' ' . ($preview['source_course']['last_name'] ?? 'Unassigned')) ?>
                </span>
              </div>
              <div class="d-flex justify-content-between mb-1.5">
                <span class="text-muted">Lifecycle Status:</span>
                <span>
                  <?php if ($preview['source_course']['status'] === 'archived'): ?>
                    <span class="badge bg-secondary rounded-pill px-2 py-0.5">Archived Term</span>
                  <?php else: ?>
                    <span class="badge bg-success rounded-pill px-2 py-0.5">Active</span>
                  <?php endif; ?>
                </span>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Total Clonable Items:</span>
                <span class="fw-bold text-primary">
                  <?= (int)$preview['source_content']['total_items'] ?> Reusable Items
                </span>
              </div>
            </div>

            <!-- Content Counter Badges -->
            <div class="row g-2 text-center small">
              <div class="col-3">
                <div class="p-2 border rounded-3 bg-white">
                  <div class="fw-bold text-dark fs-5"><?= (int)$preview['source_content']['modules_count'] ?></div>
                  <div class="text-muted" style="font-size: 0.75rem;">Modules</div>
                </div>
              </div>
              <div class="col-3">
                <div class="p-2 border rounded-3 bg-white">
                  <div class="fw-bold text-dark fs-5"><?= (int)$preview['source_content']['materials_count'] ?></div>
                  <div class="text-muted" style="font-size: 0.75rem;">Materials</div>
                </div>
              </div>
              <div class="col-3">
                <div class="p-2 border rounded-3 bg-white">
                  <div class="fw-bold text-dark fs-5"><?= (int)$preview['source_content']['assignments_count'] ?></div>
                  <div class="text-muted" style="font-size: 0.75rem;">Assignments</div>
                </div>
              </div>
              <div class="col-3">
                <div class="p-2 border rounded-3 bg-white">
                  <div class="fw-bold text-dark fs-5"><?= (int)$preview['source_content']['quizzes_count'] ?></div>
                  <div class="text-muted" style="font-size: 0.75rem;">Quizzes</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Target Destination Card -->
        <div class="col-lg-6">
          <div class="dossier-card h-100 p-4 bg-white border-top border-4 border-success">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 fw-bold">
                <i class="bi bi-box-arrow-in-down-right me-1"></i> TARGET DESTINATION
              </span>
              <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small">
                Shell #<?= (int)$preview['target_course']['id'] ?>
              </span>
            </div>
            
            <h5 class="fw-bold text-dark mb-1">
              <?= htmlspecialchars($preview['target_course']['subject_code']) ?> — <?= htmlspecialchars($preview['target_course']['subject_name']) ?>
            </h5>
            <p class="text-muted small mb-3">
              Section: <strong class="text-dark"><?= htmlspecialchars($preview['target_course']['section_code'] ?? 'N/A') ?></strong> |
              Term: <?= htmlspecialchars($preview['target_course']['academic_year'] ?? '') ?> (<?= htmlspecialchars($preview['target_course']['semester'] ?? '') ?>) |
              Level: <?= htmlspecialchars($preview['target_course']['academic_level']) ?>
            </p>

            <div class="p-3 bg-light rounded-3 mb-3 small">
              <div class="d-flex justify-content-between mb-1.5">
                <span class="text-muted">Assigned Instructor:</span>
                <span class="fw-semibold text-dark">
                  <?= htmlspecialchars(($preview['target_course']['first_name'] ?? '') . ' ' . ($preview['target_course']['last_name'] ?? 'Unassigned')) ?>
                </span>
              </div>
              <div class="d-flex justify-content-between mb-1.5">
                <span class="text-muted">Target Shell Status:</span>
                <span>
                  <?php if ($preview['target_course']['status'] === 'active'): ?>
                    <span class="badge bg-success rounded-pill px-2 py-0.5">Active Shell</span>
                  <?php else: ?>
                    <span class="badge bg-secondary rounded-pill px-2 py-0.5">Archived</span>
                  <?php endif; ?>
                </span>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Current Target Content:</span>
                <span>
                  <?php if ($preview['target_content']['has_content']): ?>
                    <span class="badge bg-warning text-dark rounded-pill px-2 py-0.5 fw-semibold">
                      <i class="bi bi-exclamation-circle-fill me-1"></i> Not Empty (<?= (int)$preview['target_content']['modules_count'] ?> modules)
                    </span>
                  <?php else: ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-0.5 fw-semibold">
                      <i class="bi bi-check-circle-fill me-1"></i> Clean Shell (0 modules)
                    </span>
                  <?php endif; ?>
                </span>
              </div>
            </div>

            <!-- Target Existing Content Summary -->
            <div class="row g-2 text-center small">
              <div class="col-3">
                <div class="p-2 border rounded-3 bg-white">
                  <div class="fw-bold text-dark fs-5"><?= (int)$preview['target_content']['modules_count'] ?></div>
                  <div class="text-muted" style="font-size: 0.75rem;">Existing Mods</div>
                </div>
              </div>
              <div class="col-3">
                <div class="p-2 border rounded-3 bg-white">
                  <div class="fw-bold text-dark fs-5"><?= (int)$preview['target_content']['materials_count'] ?></div>
                  <div class="text-muted" style="font-size: 0.75rem;">Existing Files</div>
                </div>
              </div>
              <div class="col-3">
                <div class="p-2 border rounded-3 bg-white">
                  <div class="fw-bold text-dark fs-5"><?= (int)$preview['target_content']['assignments_count'] ?></div>
                  <div class="text-muted" style="font-size: 0.75rem;">Existing Assg</div>
                </div>
              </div>
              <div class="col-3">
                <div class="p-2 border rounded-3 bg-white">
                  <div class="fw-bold text-dark fs-5"><?= (int)$preview['target_content']['quizzes_count'] ?></div>
                  <div class="text-muted" style="font-size: 0.75rem;">Existing Quiz</div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Safety Warnings & Compatibility Alerts -->
      <?php if (!empty($preview['compatibility']['warnings'])): ?>
        <div class="dossier-card mb-4 overflow-hidden fade-in-up" style="animation-delay: 0.25s;">
          <div class="dossier-card-header bg-warning bg-opacity-10 border-bottom border-warning border-opacity-25 p-3 px-4 d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
            <h6 class="fw-bold text-dark mb-0">Pre-Execution Safety & Compatibility Diagnostics</h6>
          </div>
          <div class="card-body p-4 bg-white">
            <div class="d-flex flex-column gap-2.5">
              <?php foreach ($preview['compatibility']['warnings'] as $warning): ?>
                <div class="p-3 rounded-3 bg-light border-start border-4 border-warning d-flex align-items-start gap-2.5">
                  <i class="bi bi-info-circle-fill text-warning mt-0.5"></i>
                  <span class="small text-dark fw-medium"><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Detailed Content Breakdown Accordion -->
      <div class="dossier-card mb-4 overflow-hidden fade-in-up" style="animation-delay: 0.3s;">
        <div class="dossier-card-header bg-white border-bottom p-3.5 px-4 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 32px; height: 32px;">
              <i class="bi bi-list-check"></i>
            </div>
            <h5 class="fw-bold text-dark mb-0">Step 2: Review Instructional Content to be Cloned</h5>
          </div>
          <span class="badge bg-light text-dark border rounded-pill px-3 py-1 small">
            <?= (int)$preview['source_content']['total_items'] ?> Items Ready
          </span>
        </div>
        <div class="card-body p-4">
          
          <ul class="nav nav-pills mb-3 gap-2" id="clonerContentTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active rounded-pill px-3.5 py-2 fw-medium small" id="modules-tab" data-bs-toggle="tab" data-bs-target="#modules-pane" type="button" role="tab">
                <i class="bi bi-journal-bookmark me-1.5"></i> Modules & Materials (<?= (int)$preview['source_content']['modules_count'] ?>)
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link rounded-pill px-3.5 py-2 fw-medium small" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments-pane" type="button" role="tab">
                <i class="bi bi-clipboard-check me-1.5"></i> Assignment Prompts (<?= (int)$preview['source_content']['assignments_count'] ?>)
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link rounded-pill px-3.5 py-2 fw-medium small" id="quizzes-tab" data-bs-toggle="tab" data-bs-target="#quizzes-pane" type="button" role="tab">
                <i class="bi bi-question-circle me-1.5"></i> Quizzes & Question Banks (<?= (int)$preview['source_content']['quizzes_count'] ?>)
              </button>
            </li>
          </ul>

          <div class="tab-content" id="clonerContentTabContent">
            
            <!-- Modules & Materials Pane -->
            <div class="tab-pane fade show active" id="modules-pane" role="tabpanel">
              <?php if (empty($preview['source_content']['modules'])): ?>
                <div class="text-center py-4 text-muted small">
                  <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                  No syllabus modules found in this source course.
                </div>
              <?php else: ?>
                <div class="accordion" id="modulesAccordion">
                  <?php foreach ($preview['source_content']['modules'] as $idx => $mod): ?>
                    <div class="accordion-item border rounded-3 mb-2 overflow-hidden">
                      <h2 class="accordion-header" id="headingMod<?= (int)$mod['id'] ?>">
                        <button class="accordion-button <?= $idx > 0 ? 'collapsed' : '' ?> py-3 px-3.5 bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMod<?= (int)$mod['id'] ?>">
                          <div class="d-flex align-items-center justify-content-between w-100 me-3">
                            <div class="d-flex align-items-center gap-2">
                              <span class="badge bg-primary bg-opacity-10 text-primary border rounded-pill px-2 py-0.5 small">
                                Order #<?= (int)$mod['display_order'] ?>
                              </span>
                              <strong class="text-dark"><?= htmlspecialchars($mod['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2.5 py-1 small">
                              <?= count($mod['materials']) ?> file(s) attached
                            </span>
                          </div>
                        </button>
                      </h2>
                      <div id="collapseMod<?= (int)$mod['id'] ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>" data-bs-parent="#modulesAccordion">
                        <div class="accordion-body p-3.5">
                          <?php if (!empty($mod['description'])): ?>
                            <p class="text-muted small mb-3"><?= nl2br(htmlspecialchars($mod['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                          <?php endif; ?>

                          <?php if (!empty($mod['materials'])): ?>
                            <h6 class="fw-bold text-dark small text-uppercase mb-2">Attached Materials / Syllabus Files:</h6>
                            <div class="list-group list-group-flush rounded-3 border">
                              <?php foreach ($mod['materials'] as $mat): ?>
                                <div class="list-group-item d-flex align-items-center justify-content-between p-2.5 px-3">
                                  <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-text text-primary"></i>
                                    <span class="small fw-medium text-dark"><?= htmlspecialchars($mat['file_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                  </div>
                                  <span class="badge bg-light text-muted border small"><?= htmlspecialchars($mat['mime_type'] ?? 'file') ?></span>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          <?php else: ?>
                            <span class="text-muted small fst-italic">No physical files attached to this module.</span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Assignments Pane -->
            <div class="tab-pane fade" id="assignments-pane" role="tabpanel">
              <?php if (empty($preview['source_content']['assignments'])): ?>
                <div class="text-center py-4 text-muted small">
                  <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                  No assignment prompts found in this source course.
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0 dashboard-table">
                    <thead>
                      <tr>
                        <th class="ps-3">Title</th>
                        <th>Linked Module</th>
                        <th>Max Score</th>
                        <th>Status</th>
                        <th class="pe-3">Due Date Note</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($preview['source_content']['assignments'] as $ass): ?>
                        <tr>
                          <td class="ps-3">
                            <strong class="text-dark d-block"><?= htmlspecialchars($ass['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (!empty($ass['description'])): ?>
                              <span class="text-muted small text-truncate d-inline-block" style="max-width: 320px;">
                                <?= htmlspecialchars(strip_tags($ass['description']), ENT_QUOTES, 'UTF-8') ?>
                              </span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if (!empty($ass['module_title'])): ?>
                              <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                                <i class="bi bi-journal-bookmark me-1"></i> <?= htmlspecialchars($ass['module_title'], ENT_QUOTES, 'UTF-8') ?>
                              </span>
                            <?php else: ?>
                              <span class="text-muted fst-italic">Standalone</span>
                            <?php endif; ?>
                          </td>
                          <td><span class="fw-bold text-dark"><?= (int)$ass['max_score'] ?> pts</span></td>
                          <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2.5 py-0.5">
                              <?= htmlspecialchars(ucfirst($ass['status'])) ?>
                            </span>
                          </td>
                          <td class="pe-3">
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2 py-0.5">
                              <i class="bi bi-arrow-repeat me-1"></i> Reset to NULL (Term Safe)
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

            <!-- Quizzes Pane -->
            <div class="tab-pane fade" id="quizzes-pane" role="tabpanel">
              <?php if (empty($preview['source_content']['quizzes'])): ?>
                <div class="text-center py-4 text-muted small">
                  <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                  No quizzes found in this source course.
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0 dashboard-table">
                    <thead>
                      <tr>
                        <th class="ps-3">Quiz Title</th>
                        <th>Time Limit</th>
                        <th>Passing Score</th>
                        <th>Question Pool</th>
                        <th class="pe-3">Schedule Note</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($preview['source_content']['quizzes'] as $q): ?>
                        <tr>
                          <td class="ps-3">
                            <strong class="text-dark d-block"><?= htmlspecialchars($q['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (!empty($q['description'])): ?>
                              <span class="text-muted small"><?= htmlspecialchars($q['description'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?= $q['time_limit'] ? (int)$q['time_limit'] . ' mins' : '<span class="text-muted fst-italic">Unlimited</span>' ?>
                          </td>
                          <td>
                            <?= $q['passing_score'] ? (float)$q['passing_score'] . ' pts' : '<span class="text-muted fst-italic">None</span>' ?>
                          </td>
                          <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1">
                              <?= count($q['questions']) ?> Question(s) with Choices
                            </span>
                          </td>
                          <td class="pe-3">
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2 py-0.5">
                              <i class="bi bi-calendar-x me-1"></i> Start/End Reset to NULL
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

          </div>

        </div>
      </div>

      <!-- Step 3: Execution Controls Form -->
      <div class="dossier-card mb-4 overflow-hidden fade-in-up" style="animation-delay: 0.35s;">
        <div class="dossier-card-header bg-white border-bottom p-3.5 px-4 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 32px; height: 32px;">
              <i class="bi bi-shield-shaded"></i>
            </div>
            <h5 class="fw-bold text-dark mb-0">Step 3: Cloning Mode & Execution Authorization</h5>
          </div>
          <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1 small">
            Confirmation Required
          </span>
        </div>
        <div class="card-body p-4">
          
          <form method="POST" action="/sia/lms/admin/cloner/process" id="cloneExecutionForm">
            <?= getCsrfInput() ?>
            <input type="hidden" name="source_course_id" value="<?= (int)$preview['source_course']['id'] ?>">
            <input type="hidden" name="target_course_id" value="<?= (int)$preview['target_course']['id'] ?>">

            <div class="row g-4 mb-4">
              <!-- Mode Selection -->
              <div class="col-md-7">
                <label class="form-label fw-bold text-dark small text-uppercase mb-2">Select Cloner Operation Mode:</label>
                
                <div class="form-check p-3 border rounded-3 mb-2 bg-light d-flex align-items-start gap-2.5">
                  <input class="form-check-input mt-1" type="radio" name="clone_mode" id="mode_empty_only" value="empty_only" <?= !$preview['target_content']['has_content'] ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="mode_empty_only">
                    <strong class="text-dark d-block">Safe Mode: Clean Target Only (Default & Recommended)</strong>
                    <span class="text-muted">
                      Will clone content strictly if the destination target course shell is currently empty (0 modules, 0 assignments, 0 quizzes). Prevents accidental duplication of course structure.
                    </span>
                  </label>
                </div>

                <div class="form-check p-3 border rounded-3 bg-light d-flex align-items-start gap-2.5">
                  <input class="form-check-input mt-1" type="radio" name="clone_mode" id="mode_append" value="append" <?= $preview['target_content']['has_content'] ? 'checked' : '' ?>>
                  <label class="form-check-label small" for="mode_append">
                    <strong class="text-dark d-block">Append Mode: Append to Existing Content</strong>
                    <span class="text-muted">
                      Appends cloned modules after existing modules with shifted display ordering sequences (<code class="bg-white px-1 rounded">display_order + offset</code>). Use this only if you deliberately want to merge multiple templates.
                    </span>
                  </label>
                </div>
              </div>

              <!-- Safety Invariant Confirmation -->
              <div class="col-md-5">
                <label class="form-label fw-bold text-dark small text-uppercase mb-2">Institutional Invariant Checks:</label>
                <div class="p-3 border rounded-3 bg-light small mb-3">
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check_no_students" required checked disabled>
                    <label class="form-check-label text-muted" for="check_no_students">
                      Zero student records, submissions, or quiz attempts copied
                    </label>
                  </div>
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check_source_safe" required checked disabled>
                    <label class="form-check-label text-muted" for="check_source_safe">
                      Source course remains 100% untouched & immutable
                    </label>
                  </div>
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check_academic_truth" required checked disabled>
                    <label class="form-check-label text-muted" for="check_academic_truth">
                      Registrar enrollment & schedules remain unaffected
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirm_clone_intent" required>
                    <label class="form-check-label fw-bold text-dark" for="confirm_clone_intent">
                      I confirm and authorize cloning content from Shell #<?= (int)$preview['source_course']['id'] ?> into Shell #<?= (int)$preview['target_course']['id'] ?>.
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Execution Actions -->
            <div class="d-flex align-items-center justify-content-between pt-3 border-top">
              <a href="/sia/lms/admin/cloner" class="btn btn-light border rounded-pill px-4 py-2 fw-medium text-dark shadow-xs">
                Cancel & Clear Selection
              </a>

              <?php if ($preview['compatibility']['is_same_course']): ?>
                <button type="button" class="btn btn-danger rounded-pill px-4 py-2.5 fw-bold" disabled>
                  <i class="bi bi-x-circle me-1"></i> Cannot Clone Identical Courses
                </button>
              <?php elseif ($preview['source_content']['total_items'] === 0): ?>
                <button type="button" class="btn btn-secondary rounded-pill px-4 py-2.5 fw-bold" disabled>
                  <i class="bi bi-slash-circle me-1"></i> Source Has No Content
                </button>
              <?php else: ?>
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold d-inline-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#confirmCloneModal">
                  <i class="bi bi-play-circle-fill"></i>
                  <span>Execute Safe Course Clone</span>
                </button>
              <?php endif; ?>
            </div>

            <!-- Confirmation Modal -->
            <div class="modal fade" id="confirmCloneModal" tabindex="-1" aria-labelledby="confirmCloneModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                  <div class="modal-header border-bottom p-4">
                    <div class="d-flex align-items-center gap-2.5">
                      <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 40px; height: 40px; font-size: 1.3rem;">
                        <i class="bi bi-copy"></i>
                      </div>
                      <h5 class="modal-title fw-bold text-dark" id="confirmCloneModalLabel">Confirm Course Content Clone</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body p-4">
                    <p class="text-dark small mb-3">
                      You are about to clone the following instructional structure:
                    </p>
                    <ul class="list-group list-group-flush border rounded-3 mb-3 small">
                      <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">From Source:</span>
                        <strong class="text-primary">Course #<?= (int)$preview['source_course']['id'] ?> (<?= htmlspecialchars($preview['source_course']['subject_code']) ?>)</strong>
                      </li>
                      <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Into Target:</span>
                        <strong class="text-success">Course #<?= (int)$preview['target_course']['id'] ?> (<?= htmlspecialchars($preview['target_course']['subject_code']) ?>)</strong>
                      </li>
                      <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Modules & Materials:</span>
                        <span><?= (int)$preview['source_content']['modules_count'] ?> modules (<?= (int)$preview['source_content']['materials_count'] ?> files)</span>
                      </li>
                      <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Assignments & Quizzes:</span>
                        <span><?= (int)$preview['source_content']['assignments_count'] ?> assignments, <?= (int)$preview['source_content']['quizzes_count'] ?> quizzes</span>
                      </li>
                    </ul>

                    <div class="alert alert-warning border-0 p-3 small mb-0 rounded-3">
                      <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i>
                      <strong>Important Notice:</strong> This operation runs in an atomic database transaction. If any error occurs, all changes will be completely rolled back.
                    </div>
                  </div>
                  <div class="modal-footer border-top p-3.5 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-light border rounded-pill px-3 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm">
                      <i class="bi bi-check-lg"></i>
                      <span>Yes, Execute Clone</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

          </form>

        </div>
      </div>

    <?php else: ?>
      <!-- Introductory Guidance Card (when no preview loaded) -->
      <div class="dossier-card p-5 bg-white text-center fade-in-up" style="animation-delay: 0.2s;">
        <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-3" style="width: 72px; height: 72px; font-size: 2rem;">
          <i class="bi bi-mortarboard-fill"></i>
        </div>
        <h4 class="fw-bold text-dark mb-2">Select a Source Course and Target Course Shell</h4>
        <p class="text-muted small mx-auto mb-4" style="max-width: 620px;">
          The Course Content Cloner enables department heads and administrators to standardize course syllabi by copying instructional chapters, materials, assignment prompts, and quiz questions from previous semesters without manually recreating them.
        </p>

        <div class="row g-3 text-start mx-auto" style="max-width: 820px;">
          <div class="col-md-4">
            <div class="p-3 border rounded-3 bg-light h-100">
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-primary rounded-circle p-1" style="width: 22px; height: 22px; text-align: center;">1</span>
                <strong class="text-dark small">Select Origin Source</strong>
              </div>
              <p class="text-muted mb-0" style="font-size: 0.8rem;">Pick a completed or archived course containing approved syllabus modules and rubrics.</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 border rounded-3 bg-light h-100">
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-primary rounded-circle p-1" style="width: 22px; height: 22px; text-align: center;">2</span>
                <strong class="text-dark small">Select Target Shell</strong>
              </div>
              <p class="text-muted mb-0" style="font-size: 0.8rem;">Select the new term's active course shell deployed by the automated course generator.</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 border rounded-3 bg-light h-100">
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-primary rounded-circle p-1" style="width: 22px; height: 22px; text-align: center;">3</span>
                <strong class="text-dark small">Inspect & Execute</strong>
              </div>
              <p class="text-muted mb-0" style="font-size: 0.8rem;">Verify the pre-flight checks, review questions and assignments, then clone with one click.</p>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('cloneExecutionForm');
  const confirmCheckbox = document.getElementById('confirm_clone_intent');

  if (form && confirmCheckbox) {
    form.addEventListener('submit', function(e) {
      if (!confirmCheckbox.checked) {
        e.preventDefault();
        alert('Please check the confirmation box to authorize this operation.');
        confirmCheckbox.focus();
        return false;
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
