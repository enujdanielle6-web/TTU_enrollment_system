<?php if (!empty($flash) && is_array($flash)):
    $flashType = in_array($flash['type'] ?? '', ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'info';
?>
    <div class="alert alert-<?= esc($flashType) ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
        <?= esc($flash['message'] ?? '') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
