<?php require_once __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Unified Course Header & Horizontal Navigation (Option A) -->
    <?php 
    $active_tab = 'modules';
    require __DIR__ . '/components/course_header.php'; 
    ?>

    <div id="course-tab-content" class="course-tab-content">
        <div class="row g-4">
        <!-- Main Content (Overview + Modules) -->
        <div class="col-lg-8">
            <!-- Welcome Overview -->
            <div class="lms-card p-4 mb-4 border-0 shadow-sm">
                <h4 class="h5 fw-bold mb-3">Course Overview</h4>
                <div class="text-secondary lh-lg">
                    <?= nl2br(htmlspecialchars($welcome_message)) ?>
                </div>
            </div>

            <!-- Modules List -->
            <h4 class="h5 fw-bold mb-3 mt-5">Learning Modules</h4>
            
            <?php if (empty($modules)): ?>
                <div class="lms-card p-5 text-center border-0 shadow-sm bg-light">
                    <i class="bi bi-box-seam text-muted opacity-50 mb-3" style="font-size: 3rem;"></i>
                    <h5 class="fw-bold text-dark">No Modules Yet</h5>
                    <p class="text-muted mb-0">Your instructor hasn't published any learning modules for this course yet. Check back later!</p>
                </div>
            <?php else: ?>
                <div class="accordion" id="modulesAccordion">
                    <?php foreach ($modules as $index => $module): ?>
                        <div class="accordion-item border-0 mb-3 rounded-4 shadow-sm overflow-hidden lms-card">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?= esc($index === 0 ? '' : 'collapsed') ?> bg-white fw-bold text-dark p-4" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMod<?= esc($module['id']) ?>">
                                    <div class="d-flex align-items-center gap-3 w-100">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; flex-shrink: 0;">
                                            <?= esc($index + 1) ?>
                                        </div>
                                        <?= htmlspecialchars($module['title']) ?>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapseMod<?= esc($module['id']) ?>" class="accordion-collapse collapse <?= esc($index === 0 ? 'show' : '') ?>" data-bs-parent="#modulesAccordion">
                                <div class="accordion-body p-4 pt-0 text-secondary bg-white">
                                    <p class="mb-3"><?= nl2br(htmlspecialchars($module['description'] ?? 'No description provided.')) ?></p>
                                    
                                    <!-- Placeholder for Lessons -->
                                    <div class="list-group list-group-flush border-top pt-2">
                                        <?php if (empty($module['materials'])): ?>
                                            <div class="list-group-item px-0 py-3 d-flex align-items-center gap-3 border-bottom-0 text-muted">
                                                <i class="bi bi-journal-text fs-5"></i>
                                                <div>
                                                    <span class="d-block fw-semibold">No materials uploaded yet</span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($module['materials'] as $material): ?>
                                                <a href="/sia/lms/download/material/<?= esc($material['id']) ?>" class="list-group-item list-group-item-action px-0 py-3 d-flex align-items-center gap-3 border-bottom-0 text-dark">
                                                    <i class="bi bi-file-earmark-arrow-down fs-4 text-primary"></i>
                                                    <div>
                                                        <span class="d-block fw-bold"><?= htmlspecialchars($material['file_name']) ?></span>
                                                        <small class="text-muted"><?= esc(round($material['file_size'] / 1024, 2)) ?> KB &bull; Uploaded <?= date('M d, Y', strtotime($material['created_at'])) ?></small>
                                                    </div>
                                                </a>
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

        <!-- Sidebar Widgets (Course Resources) -->
        <div class="col-lg-4">
            <div class="lms-card p-4 mb-4 border-0 shadow-sm">
                <h4 class="h6 fw-bold mb-3 text-uppercase text-muted"><i class="bi bi-folder-fill me-2 text-primary"></i> Course Resources</h4>
                <div class="text-center py-4 bg-light rounded-3">
                    <p class="text-muted small mb-0">No resources attached.</p>
                </div>
            </div>

            <div class="lms-card p-4 border-0 shadow-sm">
                <h4 class="h6 fw-bold mb-3 text-uppercase text-muted"><i class="bi bi-pie-chart-fill me-2 text-primary"></i> Your Progress</h4>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold fs-3 text-dark">0%</span>
                    <span class="text-muted small">0 of 0 lessons</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

