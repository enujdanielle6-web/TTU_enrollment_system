<?php require_once __DIR__ . '/../../../../Views/components/header.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Attendance: <?= htmlspecialchars($course['subject_code']) ?></h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($course['subject_name']) ?> &bull; Section <?= htmlspecialchars($course['section_code']) ?></p>
        </div>
        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance/create" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> New Session
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3">Session Date</th>
                            <th class="py-3">Time</th>
                            <th class="py-3">Notes</th>
                            <th class="text-end pe-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sessions)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-event fs-1 d-block mb-3 opacity-50"></i>
                                    No attendance sessions created yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sessions as $session): ?>
                                <tr>
                                    <td class="ps-4 fw-medium text-dark">
                                        <?= date('l, M d, Y', strtotime($session['session_date'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($session['start_time']): ?>
                                            <?= date('h:i A', strtotime($session['start_time'])) ?> 
                                            <?= esc($session['end_time'] ? '- ' . date('h:i A', strtotime($session['end_time'])) : '') ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 250px;">
                                            <?= htmlspecialchars($session['notes'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance/<?= esc($session['id']) ?>/edit" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square me-1"></i> Mark / View Attendance
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../../../Views/components/footer.php'; ?>
