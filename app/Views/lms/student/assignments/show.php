<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container-fluid px-0 py-2">
    <!-- Breadcrumb & Navigation -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 align-items-center">
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/student/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/student/my_courses.php" class="text-decoration-none text-muted">My Courses</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/lms/student/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-primary fw-semibold"><?= htmlspecialchars($course['subject_code']) ?></a></li>
                <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Assignment</li>
            </ol>
        </nav>
        <a href="<?= BASE_PATH ?>/lms/student/course.php?id=<?= esc($course['lms_course_id']) ?>&tab=assignments" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-arrow-left"></i> <span>Back to Assignments</span>
        </a>
    </div>

    <!-- Assignment Hero Header Strip (Consistent with Registrar Dashboard) -->
    <div class="dossier-hero-strip mb-4 fade-in-up" style="animation-delay: 0.05s;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-4 shadow-sm" style="width: 54px; height: 54px; font-size: 1.6rem; flex-shrink: 0;">
                    <i class="bi bi-journal-text"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="h4 fw-bold text-dark mb-0"><?= htmlspecialchars($assignment['title']) ?></h1>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-0.5 small fw-semibold">
                            <i class="bi bi-award-fill me-1"></i> <?= esc($assignment['max_score']) ?> pts
                        </span>
                        <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small fw-semibold">
                            <i class="bi bi-calendar-check text-primary me-1"></i> Due: <?= esc($assignment['due_date'] ? date('M d, Y h:i A', strtotime($assignment['due_date'])) : 'No deadline') ?>
                        </span>
                    </div>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($course['subject_code']) ?> &bull; <?= htmlspecialchars($course['subject_name'] ?? 'Course Assignment') ?></p>
                </div>
            </div>
            <?php if ($submission): ?>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($submission['status'] === 'GRADED'): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2 fw-semibold">
                            <i class="bi bi-check2-all me-1"></i> Graded (<?= esc($submission['grade']) ?>/<?= esc($assignment['max_score']) ?>)
                        </span>
                    <?php else: ?>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 fw-semibold">
                            <i class="bi bi-check2 me-1"></i> Submitted
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- Assignment Details -->
        <div class="col-lg-8">
            <div class="lms-card p-4 mb-4 border rounded-4 bg-white" style="border-color: #e2e8f0 !important; box-shadow: 0 4px 20px -2px rgba(0,0,0,0.04) !important;">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <span class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 32px; height: 32px; font-size: 1rem;">
                        <i class="bi bi-card-text"></i>
                    </span>
                    <h2 class="h6 fw-bold text-dark mb-0 text-uppercase" style="letter-spacing: 0.04em;">Instructions</h2>
                </div>
                
                <div class="p-3.5 rounded-3 bg-light border text-secondary lh-lg mb-4" style="background-color: #f8fafc !important; border-color: #eef2f6 !important; font-size: 0.95rem;">
                    <?= nl2br(htmlspecialchars($assignment['description'] ?? 'No instructions provided.')) ?>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white" style="border-color: #e2e8f0 !important;">
                            <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-3" style="width: 40px; height: 40px; font-size: 1.2rem;">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Grading Scale</span>
                                <strong class="text-dark small"><?= esc($assignment['max_score']) ?> Maximum Points</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3 border bg-white" style="border-color: #e2e8f0 !important;">
                            <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-3" style="width: 40px; height: 40px; font-size: 1.2rem;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Target Due Date</span>
                                <strong class="text-dark small"><?= esc($assignment['due_date'] ? date('M d, Y', strtotime($assignment['due_date'])) : 'Open Submission') ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submission Panel -->
        <div class="col-lg-4">
            <div class="lms-card p-4 border rounded-4 bg-white" style="border-color: #e2e8f0 !important; box-shadow: 0 4px 20px -2px rgba(0,0,0,0.04) !important;">
                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                    <h3 class="h5 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-cloud-arrow-up text-primary"></i> Your Submission
                    </h3>
                </div>

                <?php if ($submission): ?>
                    <!-- Existing Submission Details -->
                    <div class="p-3 rounded-3 bg-light border mb-4" style="background-color: #f8fafc !important; border-color: #e2e8f0 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-semibold">Status</span>
                            <?php if ($submission['status'] === 'GRADED'): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                                    <i class="bi bi-check2-circle me-1"></i> Graded
                                </span>
                            <?php elseif ($submission['status'] === 'RESUBMITTED'): ?>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                                    <i class="bi bi-arrow-repeat me-1"></i> Resubmitted
                                </span>
                            <?php else: ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 small fw-semibold">
                                    <i class="bi bi-check2 me-1"></i> Submitted
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-semibold">Submitted At</span>
                            <span class="text-dark small fw-bold"><?= date('M d, Y h:i A', strtotime($submission['submitted_at'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Uploaded File</span>
                            <a href="<?= BASE_PATH ?>/lms/download/submission/<?= esc($submission['id']) ?>" class="btn btn-sm btn-white border rounded-pill px-2.5 py-1 text-primary text-decoration-none fw-semibold shadow-xs d-inline-flex align-items-center gap-1.5" style="max-width: 170px;">
                                <i class="bi bi-file-earmark-arrow-down-fill"></i>
                                <span class="text-truncate" style="font-size: 0.78rem;"><?= htmlspecialchars($submission['file_name']) ?></span>
                            </a>
                        </div>
                    </div>

                    <!-- Grading / Feedback -->
                    <?php if ($submission['status'] === 'GRADED'): ?>
                        <div class="p-3 rounded-3 mb-4 border" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-success small text-uppercase" style="letter-spacing: 0.03em;">Grade Awarded</span>
                                <span class="badge bg-success text-white rounded-pill px-2.5 py-1 fw-bold fs-6">
                                    <?= esc($submission['grade']) ?> / <?= esc($assignment['max_score']) ?>
                                </span>
                            </div>
                            <?php if ($submission['feedback']): ?>
                                <div class="text-dark small mt-2 pt-2 border-top border-success border-opacity-25">
                                    <strong class="text-success-emphasis d-block mb-1">Feedback:</strong>
                                    <div class="text-secondary"><?= nl2br(htmlspecialchars($submission['feedback'])) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="p-3 rounded-3 mb-4 border d-flex align-items-center gap-3" style="background-color: #fffbeb; border-color: #fde68a !important;">
                        <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-20 text-warning rounded-circle flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.2rem;">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <strong class="text-warning-emphasis d-block" style="font-size: 0.88rem;">Not Submitted</strong>
                            <small class="text-muted" style="font-size: 0.75rem;">Your response is awaiting file upload before deadline.</small>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Upload Form -->
                <?php 
                $canSubmit = true;
                if ($submission && $submission['status'] === 'GRADED') {
                    $canSubmit = false;
                }
                ?>
                
                <?php if ($canSubmit): ?>
                    <form action="<?= BASE_PATH ?>/lms/student/course/<?= esc($course['lms_course_id']) ?>/assignments/<?= esc($assignment['id']) ?>/submit" method="POST" enctype="multipart/form-data" id="assignmentSubmitForm">
                        <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token'] ?? '') ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small text-uppercase d-block mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                Upload <?= esc($submission ? 'New ' : '') ?>File
                            </label>
                            
                            <div class="lms-file-dropzone p-3 text-center rounded-3 border-2 border-dashed position-relative" id="dropZoneContainer" style="border: 2px dashed #cbd5e1; background-color: #f8fafc; cursor: pointer; transition: all 0.2s ease;">
                                <input type="file" name="submission_file" id="submissionFileInput" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer; z-index: 5;" required onchange="handleFileSelect(this)">
                                <div class="py-2" id="dropZonePrompt">
                                    <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-2" style="width: 44px; height: 44px; font-size: 1.3rem;">
                                        <i class="bi bi-cloud-arrow-up-fill"></i>
                                    </div>
                                    <p class="mb-1 text-dark small fw-bold" id="dropZoneText">Click to browse or drag file here</p>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">PDF, DOCX, ZIP, PNG, JPG (Max 10MB)</small>
                                </div>
                                <div class="py-2 d-none" id="dropZoneSelected">
                                    <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-2" style="width: 44px; height: 44px; font-size: 1.3rem;">
                                        <i class="bi bi-file-earmark-check-fill"></i>
                                    </div>
                                    <p class="mb-1 text-dark small fw-bold text-truncate px-2" id="selectedFileName"></p>
                                    <small class="text-success fw-medium" style="font-size: 0.72rem;">Ready for submission</small>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill w-100 py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); border: none;">
                            <i class="bi bi-cloud-upload-fill"></i> 
                            <span><?= esc($submission ? 'Resubmit Assignment' : 'Submit Assignment') ?></span>
                        </button>
                    </form>

                    <script>
                        function handleFileSelect(input) {
                            const prompt = document.getElementById('dropZonePrompt');
                            const selected = document.getElementById('dropZoneSelected');
                            const nameDisplay = document.getElementById('selectedFileName');
                            const container = document.getElementById('dropZoneContainer');
                            if (input.files && input.files[0]) {
                                nameDisplay.textContent = input.files[0].name;
                                prompt.classList.add('d-none');
                                selected.classList.remove('d-none');
                                container.style.borderColor = '#10b981';
                                container.style.backgroundColor = '#f0fdf4';
                            } else {
                                prompt.classList.remove('d-none');
                                selected.classList.add('d-none');
                                container.style.borderColor = '#cbd5e1';
                                container.style.backgroundColor = '#f8fafc';
                            }
                        }
                    </script>
                <?php else: ?>
                    <div class="text-center p-3 rounded-3 bg-light border text-muted">
                        <i class="bi bi-lock-fill fs-4 d-block mb-1 text-secondary"></i>
                        <span class="small fw-semibold">Submission Closed (Graded)</span>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>

