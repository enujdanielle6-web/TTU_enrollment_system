<?php require_once __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Course Header & Horizontal Navigation -->
    <?php 
    $active_tab = 'modules';
    require __DIR__ . '/components/course_header.php'; 
    ?>

    <div id="course-tab-content" class="course-tab-content">
        <div class="row g-4">
            <!-- Main Column: Modules & Materials -->
            <div class="col-lg-8">
                <!-- Welcome / Instructional Card -->
                <div class="lms-card p-4 mb-4 border-0 shadow-sm bg-white rounded-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h4 class="h5 fw-bold text-dark mb-0">Course Curriculum &amp; Syllabus</h4>
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-0.5 small fw-bold">Faculty Authoring</span>
                            </div>
                            <p class="text-muted small mb-0">
                                Organize units, upload lecture notes, slides, readings, and downloadable course resources for your students.
                            </p>
                        </div>
                        <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createModuleModal">
                            <i class="bi bi-plus-lg"></i>
                            <span>New Module</span>
                        </button>
                    </div>
                </div>

                <!-- Learning Modules Section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-folder2-open me-2 text-primary"></i>Learning Modules
                    </h5>
                    <span class="badge bg-light text-secondary border rounded-pill px-3 py-1 fw-semibold">
                        <?= count($modulesWithMaterials) ?> <?= count($modulesWithMaterials) === 1 ? 'Unit' : 'Units' ?>
                    </span>
                </div>

                <?php if (empty($modulesWithMaterials)): ?>
                    <div class="lms-card p-5 text-center border-0 shadow-sm bg-light rounded-4">
                        <i class="bi bi-folder2-open text-muted opacity-50 mb-3" style="font-size: 3.5rem;"></i>
                        <h5 class="fw-bold text-dark">No Modules Created Yet</h5>
                        <p class="text-muted mb-4" style="max-width: 480px; margin: 0 auto;">
                            Get started by creating your first instructional module to structure your course syllabus, lecture materials, and readings.
                        </p>
                        <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#createModuleModal">
                            <i class="bi bi-plus-lg me-1"></i> Create First Module
                        </button>
                    </div>
                <?php else: ?>
                    <div class="accordion" id="modulesAccordion">
                        <?php foreach ($modulesWithMaterials as $index => $module): 
                            $matCount = count($module['materials']);
                        ?>
                            <div class="accordion-item border-0 mb-3 rounded-4 shadow-sm overflow-hidden lms-card bg-white">
                                <h2 class="accordion-header d-flex align-items-center bg-white" id="headingMod<?= esc($module['id']) ?>">
                                    <button class="accordion-button <?= esc($index === 0 ? '' : 'collapsed') ?> bg-white fw-bold text-dark p-3.5 flex-grow-1 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMod<?= esc($module['id']) ?>" aria-expanded="<?= esc($index === 0 ? 'true' : 'false') ?>">
                                        <div class="d-flex align-items-center gap-3 w-100 min-w-0">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; flex-shrink: 0;">
                                                <?= esc($index + 1) ?>
                                            </div>
                                            <div class="text-truncate flex-grow-1">
                                                <span class="d-block text-truncate fs-6 fw-bold"><?= htmlspecialchars($module['title']) ?></span>
                                                <small class="text-muted fw-normal">
                                                    <i class="bi bi-paperclip me-1"></i><?= $matCount ?> <?= $matCount === 1 ? 'material' : 'materials' ?> attached
                                                </small>
                                            </div>
                                        </div>
                                    </button>
                                    <!-- Action Toolbar for Module -->
                                    <div class="pe-3 d-flex align-items-center gap-1.5 flex-shrink-0 bg-white">
                                        <button class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 fw-semibold" data-bs-toggle="modal" data-bs-target="#uploadMaterialModal<?= esc($module['id']) ?>" title="Upload Material">
                                            <i class="bi bi-cloud-arrow-up me-1"></i><span class="d-none d-sm-inline">Upload</span>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1" data-bs-toggle="modal" data-bs-target="#editModuleModal<?= esc($module['id']) ?>" title="Edit Module Title">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="<?= BASE_PATH ?>/lms/faculty/module_delete.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this module and all its materials?');">
                                            <?= getCsrfInput() ?>
                                            <input type="hidden" name="lms_course_id" value="<?= esc($course['lms_course_id']) ?>">
                                            <input type="hidden" name="lms_module_id" value="<?= esc($module['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1" title="Delete Module">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </h2>

                                <div id="collapseMod<?= esc($module['id']) ?>" class="accordion-collapse collapse <?= esc($index === 0 ? 'show' : '') ?>" data-bs-parent="#modulesAccordion">
                                    <div class="accordion-body p-4 pt-1 bg-white border-top">
                                        <!-- Materials List -->
                                        <div class="list-group list-group-flush pt-1">
                                            <?php if (empty($module['materials'])): ?>
                                                <div class="list-group-item px-0 py-3 d-flex align-items-center justify-content-between border-0 text-muted">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-file-earmark-x fs-5 text-muted opacity-50"></i>
                                                        <span class="small">No lecture materials or slides uploaded yet.</span>
                                                    </div>
                                                    <button class="btn btn-sm btn-link text-primary text-decoration-none fw-semibold" data-bs-toggle="modal" data-bs-target="#uploadMaterialModal<?= esc($module['id']) ?>">
                                                        + Upload now
                                                    </button>
                                                </div>
                                             <?php else: ?>
                                                <?php foreach ($module['materials'] as $mat): 
                                                    $matTitle = $mat['file_name'] ?? $mat['title'] ?? 'Lecture Material';
                                                    $rawExt = pathinfo($mat['file_path'] ?? $mat['file_name'] ?? '', PATHINFO_EXTENSION);
                                                    $fType = strtolower($rawExt ?: ($mat['file_type'] ?? ''));
                                                    if (empty($fType) && !empty($mat['mime_type'])) {
                                                        if (strpos($mat['mime_type'], 'pdf') !== false) {
                                                            $fType = 'pdf';
                                                        } elseif (strpos($mat['mime_type'], 'word') !== false || strpos($mat['mime_type'], 'document') !== false) {
                                                            $fType = 'docx';
                                                        } elseif (strpos($mat['mime_type'], 'presentation') !== false || strpos($mat['mime_type'], 'powerpoint') !== false) {
                                                            $fType = 'pptx';
                                                        } elseif (strpos($mat['mime_type'], 'excel') !== false || strpos($mat['mime_type'], 'spreadsheet') !== false) {
                                                            $fType = 'xlsx';
                                                        }
                                                    }
                                                    if (empty($fType)) {
                                                        $fType = 'file';
                                                    }

                                                    $icon = 'bi-file-earmark-text text-secondary';
                                                    $bgIcon = 'bg-secondary bg-opacity-10';
                                                    if ($fType === 'pdf') {
                                                        $icon = 'bi-file-earmark-pdf text-danger';
                                                        $bgIcon = 'bg-danger bg-opacity-10';
                                                    } elseif (in_array($fType, ['doc', 'docx'])) {
                                                        $icon = 'bi-file-earmark-word text-primary';
                                                        $bgIcon = 'bg-primary bg-opacity-10';
                                                    } elseif (in_array($fType, ['ppt', 'pptx'])) {
                                                        $icon = 'bi-file-earmark-slides text-warning';
                                                        $bgIcon = 'bg-warning bg-opacity-10';
                                                    } elseif (in_array($fType, ['xls', 'xlsx', 'csv'])) {
                                                        $icon = 'bi-file-earmark-excel text-success';
                                                        $bgIcon = 'bg-success bg-opacity-10';
                                                    } elseif (in_array($fType, ['zip', 'rar', '7z', 'tar', 'gz'])) {
                                                        $icon = 'bi-file-earmark-zip text-info';
                                                        $bgIcon = 'bg-info bg-opacity-10';
                                                    }
                                                ?>
                                                    <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center border-bottom-0 border-top">
                                                        <div class="d-flex align-items-center gap-3 min-w-0">
                                                            <div class="rounded-3 p-2 d-flex align-items-center justify-content-center <?= esc($bgIcon) ?>" style="width: 40px; height: 40px; flex-shrink: 0;">
                                                                <i class="bi <?= esc($icon) ?> fs-5"></i>
                                                            </div>
                                                            <div class="min-w-0">
                                                                <span class="d-block fw-bold text-dark text-truncate" title="<?= htmlspecialchars($matTitle) ?>">
                                                                    <?= htmlspecialchars($matTitle) ?>
                                                                </span>
                                                                <small class="text-muted">
                                                                    <span class="badge bg-light text-secondary border font-monospace me-1"><?= strtoupper(htmlspecialchars($fType)) ?></span>
                                                                    <?php if (!empty($mat['file_size'])): ?>
                                                                        &bull; <?= esc(round($mat['file_size'] / 1024, 1)) ?> KB
                                                                    <?php endif; ?>
                                                                    &bull; Uploaded <?= date('M d, Y', strtotime($mat['created_at'])) ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-3">
                                                            <a href="<?= BASE_PATH ?>/lms/download/material/<?= esc($mat['id']) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold" target="_blank">
                                                                <i class="bi bi-download me-1"></i> Download
                                                            </a>
                                                            <form method="POST" action="<?= BASE_PATH ?>/lms/faculty/material_delete.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this material?');">
                                                                <?= getCsrfInput() ?>
                                                                <input type="hidden" name="lms_course_id" value="<?= esc($course['lms_course_id'] ?? $course['id']) ?>">
                                                                <input type="hidden" name="lms_material_id" value="<?= esc($mat['id']) ?>">
                                                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2.5" title="Delete Material">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Sidebar: Teaching Dossier & Stats (8:4 layout matching Student Portal) -->
            <div class="col-lg-4">
                <!-- Course Dossier Widget -->
                <div class="lms-card p-4 mb-4 border-0 shadow-sm bg-white rounded-4">
                    <h5 class="h6 fw-bold mb-3 text-uppercase text-muted" style="letter-spacing: 0.05em;">
                        <i class="bi bi-info-circle-fill me-2 text-primary"></i> Course Dossier
                    </h5>
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted small">Course Code</span>
                            <span class="fw-bold text-dark font-monospace"><?= htmlspecialchars($course['subject_code'] ?? 'N/A') ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted small">Official Section</span>
                            <span class="badge bg-light text-primary border fw-bold"><?= htmlspecialchars($course['section_code'] ?? 'N/A') ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted small">Academic Units</span>
                            <span class="fw-bold text-dark"><?= (int)($course['units'] ?? 0) ?> Units</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted small">Academic Level</span>
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold"><?= htmlspecialchars($course['academic_level'] ?? 'College') ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted small">Enrolled Students</span>
                            <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/roster" class="text-decoration-none fw-bold text-primary">
                                <i class="bi bi-people-fill me-1"></i>View Roster &rarr;
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Content Summary Widget -->
                <?php
                $totalMaterials = 0;
                foreach ($modulesWithMaterials as $m) {
                    $totalMaterials += count($m['materials']);
                }
                ?>
                <div class="lms-card p-4 mb-4 border-0 shadow-sm bg-white rounded-4">
                    <h5 class="h6 fw-bold mb-3 text-uppercase text-muted" style="letter-spacing: 0.05em;">
                        <i class="bi bi-pie-chart-fill me-2 text-primary"></i> Content Summary
                    </h5>
                    <div class="row g-2 text-center mb-3">
                        <div class="col-6">
                            <div class="bg-light p-3 rounded-3 border">
                                <div class="fs-4 fw-bold text-primary mb-0"><?= count($modulesWithMaterials) ?></div>
                                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.68rem;">Modules</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light p-3 rounded-3 border">
                                <div class="fs-4 fw-bold text-success mb-0"><?= $totalMaterials ?></div>
                                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.68rem;">Materials</small>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-semibold py-2" data-bs-toggle="modal" data-bs-target="#createModuleModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Another Module
                    </button>
                </div>

                <!-- Quick Navigation Strip -->
                <div class="lms-card p-4 border-0 shadow-sm bg-white rounded-4">
                    <h5 class="h6 fw-bold mb-3 text-uppercase text-muted" style="letter-spacing: 0.05em;">
                        <i class="bi bi-lightning-charge-fill me-2 text-warning"></i> Quick Jump
                    </h5>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/assignments" class="btn btn-light btn-sm text-start d-flex justify-content-between align-items-center py-2 px-3 rounded-3">
                            <span><i class="bi bi-journal-text me-2 text-primary"></i>Assignments</span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                        <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="btn btn-light btn-sm text-start d-flex justify-content-between align-items-center py-2 px-3 rounded-3">
                            <span><i class="bi bi-pencil-square me-2 text-info"></i>Online Quizzes</span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                        <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/gradebook" class="btn btn-light btn-sm text-start d-flex justify-content-between align-items-center py-2 px-3 rounded-3">
                            <span><i class="bi bi-star me-2 text-warning"></i>Gradebook</span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                        <a href="<?= BASE_PATH ?>/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance" class="btn btn-light btn-sm text-start d-flex justify-content-between align-items-center py-2 px-3 rounded-3">
                            <span><i class="bi bi-person-check me-2 text-success"></i>Attendance</span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals for Modules (Placed top-level to prevent backdrop z-index containment) -->
<?php if (!empty($modulesWithMaterials)): ?>
    <?php foreach ($modulesWithMaterials as $module): ?>
        <!-- Edit Module Modal -->
        <div class="modal fade" id="editModuleModal<?= esc($module['id']) ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content border-0 shadow-lg rounded-4" method="POST" action="<?= BASE_PATH ?>/lms/faculty/module_update.php">
                    <?= getCsrfInput() ?>
                    <div class="modal-header border-bottom p-4">
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-box-sm bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                            <h5 class="modal-title fw-bold text-dark mb-0">Edit Module Title</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="lms_course_id" value="<?= esc($course['lms_course_id']) ?>">
                        <input type="hidden" name="lms_module_id" value="<?= esc($module['id']) ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Module Title</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($module['title']) ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold text-dark">Display Order</label>
                            <input type="number" name="order_index" class="form-control" value="<?= esc($module['display_order'] ?? 1) ?>" min="0">
                            <div class="form-text">Modules are sorted in ascending order of their display index.</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top p-3 rounded-bottom-4">
                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Upload Material Modal -->
        <div class="modal fade" id="uploadMaterialModal<?= esc($module['id']) ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content border-0 shadow-lg rounded-4" method="POST" action="<?= BASE_PATH ?>/lms/faculty/material_upload.php" enctype="multipart/form-data">
                    <?= getCsrfInput() ?>
                    <div class="modal-header border-bottom p-4">
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-box-sm bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-cloud-arrow-up"></i>
                            </div>
                            <h5 class="modal-title fw-bold text-dark mb-0">Upload to <?= htmlspecialchars($module['title']) ?></h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="lms_course_id" value="<?= esc($course['lms_course_id']) ?>">
                        <input type="hidden" name="lms_module_id" value="<?= esc($module['id']) ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Material Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Chapter 1 Lecture Slides" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold text-dark">Document File</label>
                            <input type="file" name="material_file" class="form-control" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt">
                            <div class="form-text"><i class="bi bi-info-circle me-1"></i>Supported formats: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX (Max 25MB)</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top p-3 rounded-bottom-4">
                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                            <i class="bi bi-upload me-1"></i> Upload File
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Create Module Modal -->
<div class="modal fade" id="createModuleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow-lg rounded-4" method="POST" action="<?= BASE_PATH ?>/lms/faculty/module_create.php">
            <?= getCsrfInput() ?>
            <div class="modal-header border-bottom p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="icon-box-sm bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-folder-plus"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-dark mb-0">Create New Module</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="lms_course_id" value="<?= esc($course['lms_course_id']) ?>">
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Module Title</label>
                    <input type="text" name="title" class="form-control form-control-lg" placeholder="e.g. Unit 1: Foundations of Programming" required>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-bold text-dark">Display Order</label>
                    <input type="number" name="order_index" class="form-control" value="<?= count($modulesWithMaterials) + 1 ?>" min="1">
                    <div class="form-text">Determines the order of this unit in the course syllabus list.</div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top p-3 rounded-bottom-4">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Create Module
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
