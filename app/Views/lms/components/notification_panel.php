<?php
/**
 * Bell notification panel and the window that shows one notification in full.
 * Shared by the student and faculty LMS layouts; public/js/lms-notifications.js
 * opens the window and marks notifications as read.
 *
 * @var string $notifPortal      'student' | 'faculty'
 * @var array  $notifFeed        ['items' => [...], 'unread' => int] from LmsNotificationService
 * @var string $notifTitle       panel heading
 * @var string $notifSubtitle    panel sub-heading
 * @var string $notifEmptyText   shown when there is nothing
 */
$notifItems = $notifFeed['items'] ?? [];
$notifUnread = (int)($notifFeed['unread'] ?? 0);

$notifStyles = [
    'announcement' => ['bi-megaphone-fill', 'bg-primary bg-opacity-10 text-primary', 'Announcement'],
    'assignment' => ['bi-journal-text', 'bg-success bg-opacity-10 text-success', 'Assignment'],
    'quiz' => ['bi-pencil-square', 'bg-info bg-opacity-10 text-info', 'Quiz'],
    'submission' => ['bi-journal-check', 'bg-success bg-opacity-10 text-success', 'Submission'],
];
$notifActions = [
    'announcement' => 'Go to announcements',
    'assignment' => 'Open assignment',
    'quiz' => 'Open quiz',
    'submission' => 'View submissions',
];
?>
<div class="lms-notification-panel shadow-lg" id="sidebarNotificationPanel"
     data-notif-portal="<?= esc($notifPortal) ?>"
     data-read-url="<?= BASE_PATH ?>/lms/<?= esc($notifPortal) ?>/notifications/read"
     data-read-all-url="<?= BASE_PATH ?>/lms/<?= esc($notifPortal) ?>/notifications/read-all"
     data-csrf="<?= esc($_SESSION['csrf_token'] ?? '') ?>">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
        <div class="d-flex align-items-center gap-2">
            <div class="icon-box-sm bg-primary bg-opacity-10 text-primary" style="width: 32px; height: 32px; font-size: 0.95rem; border-radius: 0.5rem;">
                <i class="bi bi-bell-fill"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.88rem;"><?= htmlspecialchars($notifTitle) ?></h6>
                <small class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($notifSubtitle) ?></small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1 fw-bold text-nowrap <?= $notifUnread > 0 ? '' : 'd-none' ?>" style="font-size: 0.65rem;" data-notif-count><?= $notifUnread ?> New</span>
            <button type="button" class="btn btn-sm btn-light border-0 text-muted p-1 rounded-2" id="closeNotificationPanel" title="Close" aria-label="Close notifications">
                <i class="bi bi-x-lg" style="font-size: 0.75rem;"></i>
            </button>
        </div>
    </div>

    <div class="lms-notification-list">
        <?php if (empty($notifItems)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
                <p class="mb-0 small fw-medium">All caught up!</p>
                <small class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($notifEmptyText) ?></small>
            </div>
        <?php else: ?>
            <?php foreach ($notifItems as $notif):
                $type = $notif['type'] ?? 'announcement';
                [$iconClass, $iconBg, $typeLabel] = $notifStyles[$type] ?? $notifStyles['announcement'];
                $when = date('M d, Y h:i A', strtotime($notif['created_at']));
                $person = $notifPortal === 'faculty' ? ($notif['student_name'] ?? '') : ($notif['professor_name'] ?? '');
                $payload = [
                    'type' => $type,
                    'id' => (int)$notif['id'],
                    'typeLabel' => $typeLabel,
                    'icon' => $iconClass,
                    'title' => $notif['title'] ?? '',
                    'course' => $notif['subject_code'] ?? '',
                    'person' => $type === 'submission' ? 'Submitted by ' . $person : ($person === 'You' ? 'Posted by you' : 'From ' . $person),
                    'when' => $when,
                    'due' => !empty($notif['due_date']) ? date('M d, Y h:i A', strtotime($notif['due_date'])) : '',
                    'status' => $type === 'submission' ? ucfirst(strtolower((string)($notif['status'] ?? ''))) : '',
                    'file' => $notif['file_name'] ?? '',
                    'body' => trim((string)($notif['content'] ?? '')),
                    'emptyBody' => $type === 'submission' ? 'The student did not add a note. Open the submissions to see the file.' : 'No further details were written for this notification.',
                    'url' => $notif['url'] ?? '',
                    'action' => $notifActions[$type] ?? 'Open',
                    'tracked' => $notifPortal === 'faculty' ? $type === 'submission' : true,
                ];
            ?>
                <a href="<?= htmlspecialchars($notif['url']) ?>" data-spa="false"
                   class="lms-notification-item d-flex gap-2 p-3 border-bottom text-decoration-none <?= empty($notif['is_read']) ? 'is-unread' : '' ?>"
                   data-notif="<?= htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="icon-box-sm <?= esc($iconBg) ?>" style="width: 34px; height: 34px; font-size: 0.95rem; border-radius: 0.55rem; flex-shrink: 0;">
                        <i class="bi <?= esc($iconClass) ?>"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0" style="overflow: hidden;">
                        <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                            <span class="badge bg-light text-secondary border px-2 py-0 small flex-shrink-0" style="font-size: 0.65rem;">
                                <?= htmlspecialchars($notif['subject_code']) ?>
                            </span>
                            <span class="text-muted small text-nowrap flex-shrink-0" style="font-size: 0.68rem;"><?= esc(date('M d, h:i A', strtotime($notif['created_at']))) ?></span>
                        </div>
                        <div class="fw-bold text-dark small text-truncate" title="<?= htmlspecialchars($notif['title']) ?>">
                            <?= htmlspecialchars($notif['title']) ?>
                        </div>
                        <div class="text-muted text-truncate small mt-1" style="font-size: 0.72rem;">
                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($person) ?>
                            <?php if (!empty($notif['due_date']) && $type !== 'submission'): ?>
                                <span class="text-danger ms-1">&bull; Due <?= date('M d', strtotime($notif['due_date'])) ?></span>
                            <?php endif; ?>
                            <?php if ($type === 'submission' && !empty($notif['status'])): ?>
                                <span class="badge bg-warning bg-opacity-10 text-dark ms-1"><?= htmlspecialchars(ucfirst(strtolower($notif['status']))) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="lms-notification-unread-dot" aria-hidden="true"></span>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="p-2 bg-light border-top d-flex justify-content-between align-items-center px-3">
        <button type="button" class="btn btn-link btn-sm p-0 small fw-semibold text-decoration-none <?= $notifUnread > 0 ? '' : 'invisible' ?>" data-notif-read-all>
            <i class="bi bi-check2-all me-1"></i>Mark all as read
        </button>
        <a href="<?= BASE_PATH ?>/lms/<?= esc($notifPortal) ?>/calendar" class="small fw-semibold text-primary text-decoration-none">
            Calendar &rarr;
        </a>
    </div>
</div>

<!-- Full notification window -->
<div class="modal fade" id="lmsNotificationModal" tabindex="-1" aria-labelledby="lmsNotificationModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0 align-items-start gap-3">
                <div class="icon-box-sm bg-primary bg-opacity-10 text-primary flex-shrink-0" style="width: 42px; height: 42px; font-size: 1.15rem; border-radius: 0.7rem;" data-notif-modal-iconbox>
                    <i class="bi bi-bell-fill" data-notif-modal-icon></i>
                </div>
                <div class="min-w-0 flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <span class="badge bg-light text-secondary border" data-notif-modal-course></span>
                        <span class="badge bg-primary bg-opacity-10 text-primary" data-notif-modal-type></span>
                    </div>
                    <h5 class="modal-title fw-bold text-break" id="lmsNotificationModalTitle"></h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-unstyled small text-muted mb-3 d-flex flex-wrap gap-3">
                    <li><i class="bi bi-person me-1"></i><span data-notif-modal-person></span></li>
                    <li><i class="bi bi-clock me-1"></i><span data-notif-modal-when></span></li>
                    <li class="text-danger" data-notif-modal-due-row><i class="bi bi-calendar-event me-1"></i>Due <span data-notif-modal-due></span></li>
                    <li data-notif-modal-status-row><i class="bi bi-flag me-1"></i><span data-notif-modal-status></span></li>
                    <li data-notif-modal-file-row><i class="bi bi-paperclip me-1"></i><span data-notif-modal-file></span></li>
                </ul>
                <div class="lms-notification-body text-dark" data-notif-modal-body></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                <a href="#" class="btn btn-primary rounded-pill px-3" data-notif-modal-link>Open</a>
            </div>
        </div>
    </div>
</div>
