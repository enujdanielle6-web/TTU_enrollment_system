<?php
/**
 * Lesson preview window for the student course page. Opened by any element with
 * class "js-lesson-open" and data-lesson-id (see public/js/lms-lesson-viewer.js).
 */
$lessonViewerJs = __DIR__ . '/../../../../../public/js/lms-lesson-viewer.js';
?>
<style>
    #lessonPreviewModal .lesson-preview-scroll { height: 72vh; overflow-y: auto; background: #eef2f7; border-radius: 0.75rem; }
    #lessonPreviewModal .lesson-pdf-page { display: block; margin: 0 auto 1rem; background: #fff; box-shadow: 0 1px 4px rgba(15, 23, 42, 0.15); }
    #lessonPreviewModal .lesson-pdf-page canvas { display: block; width: 100%; height: 100%; }
    #lessonPreviewModal .lesson-text-section { background: #fff; border-radius: 0.75rem; padding: 1.25rem 1.5rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08); }
    #lessonPreviewModal .lesson-preview-image { display: block; max-width: 100%; margin: 0 auto; }
    @media (max-width: 991.98px) { #lessonPreviewModal .lesson-preview-scroll { height: calc(100vh - 190px); } }
</style>

<div class="modal fade" id="lessonPreviewModal" tabindex="-1" aria-labelledby="lessonPreviewTitle" aria-hidden="true"
     data-preview-base="<?= BASE_PATH ?>/lms/student/material/"
     data-pdfjs-base="<?= BASE_PATH ?>/public/vendor/pdfjs/"
     data-csrf="<?= esc($_SESSION['csrf_token'] ?? '') ?>"
     data-complete-at="<?= esc(\App\Services\LmsProgressService::COMPLETE_AT_PERCENT) ?>">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-2 gap-2">
                <div class="min-w-0 flex-grow-1">
                    <h5 class="modal-title fw-bold text-truncate" id="lessonPreviewTitle">Lesson</h5>
                    <small class="text-muted" data-lesson-meta></small>
                </div>
                <a href="#" class="btn btn-primary btn-sm rounded-pill px-3 flex-shrink-0" data-lesson-download data-spa="false">
                    <i class="bi bi-download me-1"></i> Download
                </a>
                <button type="button" class="btn-close ms-1" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="px-3">
                <div class="progress" style="height: 4px;" aria-hidden="true">
                    <div class="progress-bar bg-success" data-lesson-read-bar style="width: 0%;"></div>
                </div>
            </div>
            <div class="modal-body pt-2">
                <div class="lesson-preview-scroll p-3" data-lesson-scroll tabindex="0">
                    <div data-lesson-content></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-between">
                <small class="text-muted" data-lesson-status>Scroll to the end to mark this lesson as read.</small>
                <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_PATH ?>/public/js/lms-lesson-viewer.js?v=<?= esc(is_file($lessonViewerJs) ? filemtime($lessonViewerJs) : 1) ?>"></script>
