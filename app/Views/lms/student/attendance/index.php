<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sia/lms/student/dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/student/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none"><?= htmlspecialchars($course['subject_code']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Attendance</li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="lms-card p-4 border-0 shadow-sm rounded-4 mb-4 text-center">
                <h5 class="fw-bold text-dark mb-4">Overall Attendance</h5>
                
                <div class="d-inline-block border border-3 rounded-circle p-5 mb-4 shadow-sm <?= esc($percentage >= 80 ? 'border-success' : ($percentage >= 60 ? 'border-warning' : 'border-danger')) ?>" style="width: 200px; height: 200px; display: flex; align-items: center; justify-content: center;">
                    <div>
                        <div class="fs-1 fw-bold <?= esc($percentage >= 80 ? 'text-success' : ($percentage >= 60 ? 'text-warning text-dark' : 'text-danger')) ?>">
                            <?= number_format($percentage, 0) ?>%
                        </div>
                        <div class="text-muted small">Presence</div>
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-3 text-start">
                    <div>
                        <div class="text-success fw-bold"><i class="bi bi-circle-fill small me-1"></i> <?= esc($stats['present']) ?> Present</div>
                        <div class="text-secondary fw-bold"><i class="bi bi-circle-fill small me-1"></i> <?= esc($stats['excused']) ?> Excused</div>
                    </div>
                    <div>
                        <div class="text-danger fw-bold"><i class="bi bi-circle-fill small me-1"></i> <?= esc($stats['absent']) ?> Absent</div>
                        <div class="text-warning text-dark fw-bold"><i class="bi bi-circle-fill small me-1"></i> <?= esc($stats['late']) ?> Late</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="lms-card p-0 border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="bg-light p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar3 me-2"></i>Attendance History</h5>
                    <span class="badge bg-secondary"><?= esc($stats['total']) ?> Sessions</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history)): ?>
                                <tr><td colspan="3" class="text-center py-5 text-muted">No attendance recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td class="ps-4 fw-medium text-dark">
                                            <?= date('l, M d, Y', strtotime($h['session_date'])) ?>
                                            <?php if ($h['start_time']): ?>
                                                <div class="text-muted small"><?= date('h:i A', strtotime($h['start_time'])) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($h['status'] === 'present'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2"><i class="bi bi-check-circle me-1"></i> Present</span>
                                            <?php elseif ($h['status'] === 'absent'): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2"><i class="bi bi-x-circle me-1"></i> Absent</span>
                                            <?php elseif ($h['status'] === 'late'): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 px-3 py-2"><i class="bi bi-clock me-1"></i> Late</span>
                                            <?php elseif ($h['status'] === 'excused'): ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-2"><i class="bi bi-info-circle me-1"></i> Excused</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4 text-muted">
                                            <?= htmlspecialchars($h['remarks'] ?: '-') ?>
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
</div>

<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_footer.php'; ?>
