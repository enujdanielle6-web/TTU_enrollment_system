<?php require_once __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($course['subject_code']) ?> - Modules</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= htmlspecialchars($course['subject_code']) ?>: <?= htmlspecialchars($course['subject_name']) ?></h1>
            <p class="text-muted mb-0">Section <?= htmlspecialchars($course['section_code']) ?></p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createModuleModal">
            <i class="bi bi-plus-lg me-1"></i> New Module
        </button>
    </div>

    <!-- Modules List -->
    <div class="row g-4">
        <?php if (empty($modulesWithMaterials)): ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-folder2-open fs-1 d-block mb-3 opacity-50"></i>
                No modules have been created yet.
            </div>
        <?php else: ?>
            <?php foreach ($modulesWithMaterials as $module): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-folder me-2 text-primary"></i> <?= htmlspecialchars($module['title']) ?></h5>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#uploadMaterialModal<?= $module['id'] ?>">
                                <i class="bi bi-cloud-arrow-up me-1"></i> Upload Material
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush rounded-bottom-4">
                                <?php if (empty($module['materials'])): ?>
                                    <li class="list-group-item p-3 text-muted text-center border-0">No materials uploaded yet.</li>
                                <?php else: ?>
                                    <?php foreach ($module['materials'] as $mat): ?>
                                        <li class="list-group-item p-3 d-flex justify-content-between align-items-center border-bottom-0 border-top">
                                            <div class="d-flex align-items-center gap-3">
                                                <?php 
                                                    $icon = 'bi-file-earmark';
                                                    if ($mat['file_type'] == 'pdf') $icon = 'bi-file-earmark-pdf text-danger';
                                                    if (in_array($mat['file_type'], ['doc', 'docx'])) $icon = 'bi-file-earmark-word text-primary';
                                                    if (in_array($mat['file_type'], ['ppt', 'pptx'])) $icon = 'bi-file-earmark-slides text-warning';
                                                ?>
                                                <i class="bi <?= $icon ?> fs-4"></i>
                                                <div>
                                                    <span class="d-block fw-semibold text-dark"><?= htmlspecialchars($mat['title']) ?></span>
                                                    <small class="text-muted"><?= strtoupper($mat['file_type']) ?> Document</small>
                                                </div>
                                            </div>
                                            <a href="/sia/lms/download/material/<?= $mat['id'] ?>" class="btn btn-light btn-sm text-primary" target="_blank">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Upload Material Modal -->
                <div class="modal fade" id="uploadMaterialModal<?= $module['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <form class="modal-content border-0 shadow" method="POST" action="/sia/lms/faculty/material_upload.php" enctype="multipart/form-data">
                            <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title fw-bold">Upload to <?= htmlspecialchars($module['title']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body py-4">
                                <input type="hidden" name="lms_course_id" value="<?= $course['lms_course_id'] ?>">
                                <input type="hidden" name="lms_module_id" value="<?= $module['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Material Title</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">File</label>
                                    <input type="file" name="material_file" class="form-control" required>
                                    <div class="form-text">PDF, DOC, DOCX, PPT, PPTX</div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light border-top-0">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary px-4">Upload File</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Create Module Modal -->
<div class="modal fade" id="createModuleModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content border-0 shadow" method="POST" action="/sia/lms/faculty/module_create.php">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Create New Module</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <input type="hidden" name="lms_course_id" value="<?= $course['lms_course_id'] ?>">
                <div class="mb-3">
                    <label class="form-label fw-bold">Module Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Week 1: Introduction" required>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Create Module</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
