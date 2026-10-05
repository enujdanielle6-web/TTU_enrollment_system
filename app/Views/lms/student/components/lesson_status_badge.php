<?php
/**
 * Read/progress badge for one lesson. Expects $material with 'id' and 'progress'
 * (null, or ['percent' => float, 'completed' => bool]). lms-lesson-viewer.js updates it live.
 */
$lessonProgress = $material['progress'] ?? null;
$lessonPercent = $lessonProgress ? (int)floor($lessonProgress['percent']) : 0;
?>
<span class="badge rounded-pill flex-shrink-0 lesson-status-badge <?= !empty($lessonProgress['completed']) ? 'bg-success' : ($lessonPercent > 0 ? 'bg-primary bg-opacity-10 text-primary' : 'bg-light text-muted border') ?>"
      data-lesson-badge="<?= esc($material['id']) ?>">
    <?php if (!empty($lessonProgress['completed'])): ?>
        <i class="bi bi-check-lg"></i> Read
    <?php elseif ($lessonPercent > 0): ?>
        <?= $lessonPercent ?>% read
    <?php else: ?>
        Not started
    <?php endif; ?>
</span>
