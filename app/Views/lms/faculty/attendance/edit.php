<?php require_once __DIR__ . '/../../../../Views/components/header.php'; ?>

<div class="container py-5">
    <div class="mb-4">
        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance" class="text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Back to Attendance
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4 bg-light border-bottom rounded-top-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 fw-bold mb-1">Roll Call: <?= date('l, M d, Y', strtotime($session['session_date'])) ?></h2>
                    <p class="text-muted mb-0">
                        <?= esc($session['start_time'] ? date('h:i A', strtotime($session['start_time'])) . ' - ' : '') ?>
                        <?= htmlspecialchars($session['notes'] ?? 'No notes') ?>
                    </p>
                </div>
                <button type="button" class="btn btn-outline-secondary" onclick="markAll('present')">
                    <i class="bi bi-check2-all me-1"></i> Mark All Present
                </button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <form action="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance/<?= esc($session['id']) ?>/update" method="POST">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3" style="width: 300px;">Student Name</th>
                                <th class="py-3 text-center">Status</th>
                                <th class="text-end pe-4 py-3" style="width: 250px;">Remarks (Optional)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        No students enrolled in this section.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): 
                                    $sId = $student['id'];
                                    $rec = $records[$sId] ?? null;
                                    $status = $rec['status'] ?? 'present'; // default present
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-medium text-dark">
                                            <?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name']) ?>
                                            <div class="text-muted small"><?= htmlspecialchars($student['student_number']) ?></div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <input type="radio" class="btn-check status-radio" name="attendance[<?= esc($sId) ?>][status]" id="status_<?= esc($sId) ?>_present" value="present" <?= esc($status === 'present' ? 'checked' : '') ?>>
                                                <label class="btn btn-outline-success" for="status_<?= esc($sId) ?>_present">Present</label>

                                                <input type="radio" class="btn-check status-radio" name="attendance[<?= esc($sId) ?>][status]" id="status_<?= esc($sId) ?>_late" value="late" <?= esc($status === 'late' ? 'checked' : '') ?>>
                                                <label class="btn btn-outline-warning" for="status_<?= esc($sId) ?>_late">Late</label>

                                                <input type="radio" class="btn-check status-radio" name="attendance[<?= esc($sId) ?>][status]" id="status_<?= esc($sId) ?>_absent" value="absent" <?= esc($status === 'absent' ? 'checked' : '') ?>>
                                                <label class="btn btn-outline-danger" for="status_<?= esc($sId) ?>_absent">Absent</label>

                                                <input type="radio" class="btn-check status-radio" name="attendance[<?= esc($sId) ?>][status]" id="status_<?= esc($sId) ?>_excused" value="excused" <?= esc($status === 'excused' ? 'checked' : '') ?>>
                                                <label class="btn btn-outline-secondary" for="status_<?= esc($sId) ?>_excused">Excused</label>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <input type="text" name="attendance[<?= esc($sId) ?>][remarks]" class="form-control form-control-sm" placeholder="Reason/Notes" value="<?= htmlspecialchars($rec['remarks'] ?? '') ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if (!empty($students)): ?>
                <div class="card-footer bg-white border-top p-4 text-end rounded-bottom-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold shadow-sm">Save Attendance</button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
function markAll(statusValue) {
    const radios = document.querySelectorAll(`input[type="radio"][value="${statusValue}"]`);
    radios.forEach(radio => {
        radio.checked = true;
    });
}
</script>

<?php require_once __DIR__ . '/../../../../Views/components/footer.php'; ?>
