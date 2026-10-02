<?php require_once __DIR__ . '/../layout_header.php'; ?>
<?php use App\Services\Quiz\QuizQuestionValidator; ?>
<?php $baseUrl = '/sia/lms/faculty/course/' . (int)$course['lms_course_id'] . '/quizzes/' . (int)$quiz['id']; ?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 align-items-center">
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/quizzes" class="text-decoration-none text-muted">Quizzes</a></li>
                <li class="breadcrumb-item"><a href="<?= esc($baseUrl) ?>/questions" class="text-decoration-none text-muted">Question Builder</a></li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Generate from Content</li>
            </ol>
        </nav>
        <a href="<?= esc($baseUrl) ?>/questions" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Questions
        </a>
    </div>

    <?php require __DIR__ . '/_flash.php'; ?>

    <form method="POST" action="<?= esc($baseUrl) ?>/questions/generate" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
        <?= getCsrfInput() ?>
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="lms-card p-4 border-0 shadow-sm bg-white rounded-4 h-100">
                    <h3 class="h6 fw-bold text-dark mb-1">1. Choose content from <?= esc($course['subject_code']) ?></h3>
                    <p class="small text-muted mb-3">Questions are built only from the modules and files you select here.</p>

                    <?php if (empty($modules)): ?>
                        <p class="text-muted mb-0">This course has no modules yet. Add modules and materials first.</p>
                    <?php endif; ?>

                    <?php foreach ($modules as $module): ?>
                        <div class="border rounded-3 p-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="modules[]" value="<?= (int)$module['id'] ?>" id="mod_<?= (int)$module['id'] ?>">
                                <label class="form-check-label fw-semibold" for="mod_<?= (int)$module['id'] ?>"><?= esc($module['title']) ?></label>
                                <div class="small text-muted"><?= trim((string)$module['description']) !== '' ? 'Includes the module description' : 'No module description to read' ?></div>
                            </div>
                            <?php foreach ($module['materials'] as $material): $supported = $material['support']['supported']; ?>
                                <div class="form-check ms-4 mt-2">
                                    <input class="form-check-input" type="checkbox" name="materials[]" value="<?= (int)$material['id'] ?>" id="mat_<?= (int)$material['id'] ?>" <?= $supported ? '' : 'disabled' ?>>
                                    <label class="form-check-label small <?= $supported ? '' : 'text-muted' ?>" for="mat_<?= (int)$material['id'] ?>">
                                        <?= esc($material['file_name']) ?>
                                        <span class="badge <?= $supported ? 'bg-success bg-opacity-10 text-success' : 'bg-light text-secondary border' ?> rounded-pill ms-1"><?= esc($material['support']['reason']) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <p class="small text-muted mt-3 mb-0">
                        Readable formats: module descriptions, .txt, .md, .docx and .pptx<?= $pdf_supported ? ', and .pdf files with a text layer' : '' ?>.
                        <?php if (!$pdf_supported): ?>PDF files can't be read until the smalot/pdfparser Composer package is installed on the server.<?php endif; ?>
                        Scanned or image-only files have no text to read.
                    </p>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="lms-card p-4 border-0 shadow-sm bg-white rounded-4 mb-4">
                    <h3 class="h6 fw-bold text-dark mb-3">2. Number and types of questions</h3>
                    <?php foreach (QuizQuestionValidator::TYPES as $type => $label): ?>
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                            <label class="small fw-semibold" for="count_<?= esc($type) ?>"><?= esc($label) ?></label>
                            <input type="number" class="form-control form-control-sm" style="max-width: 90px;" name="counts[<?= esc($type) ?>]" id="count_<?= esc($type) ?>" value="<?= $type === 'multiple_choice' ? 5 : 0 ?>" min="0" max="<?= (int)$max_questions ?>">
                        </div>
                    <?php endforeach; ?>
                    <div class="d-flex align-items-center justify-content-between gap-3 mt-3">
                        <label class="small fw-semibold" for="pointsInput">Points per question</label>
                        <input type="number" class="form-control form-control-sm" style="max-width: 90px;" name="points" id="pointsInput" value="1" step="0.25" min="0.25" max="100">
                    </div>
                    <p class="small text-muted mt-3 mb-0">Up to <?= (int)$max_questions ?> questions per run.</p>
                </div>

                <div class="lms-card p-4 border-0 shadow-sm bg-white rounded-4">
                    <h3 class="h6 fw-bold text-dark mb-1">How questions are generated</h3>
                    <p class="small fw-semibold mb-1"><?= esc($generator['label']) ?></p>
                    <p class="small text-muted"><?= esc($generator['description']) ?> No AI service is configured for this system. If your content has few definitions, you'll get fewer questions than requested.</p>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm w-100">Generate Draft for Review</button>
                    <p class="small text-muted mt-2 mb-0">Nothing is saved until you review and save the draft.</p>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
