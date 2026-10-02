<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Breadcrumb & Return -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 align-items-center">
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($course['subject_code']) ?></a></li>
                <li class="breadcrumb-item"><a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance" class="text-decoration-none text-muted">Attendance</a></li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Session Roll Call</li>
            </ol>
        </nav>
        <a href="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Attendance
        </a>
    </div>

    <!-- Session Header Card -->
    <div class="lms-card p-4 mb-4 border-0 shadow-sm bg-white rounded-4 position-relative overflow-hidden">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-1.5 rounded-pill fw-bold">
                        <i class="bi bi-calendar-check me-1"></i><?= date('l, F j, Y', strtotime($session['session_date'])) ?>
                    </span>
                    <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-semibold">
                        Section <?= htmlspecialchars($course['section_code']) ?>
                    </span>
                    <?php if (!empty($session['start_time'])): ?>
                        <span class="badge bg-light text-muted border px-3 py-1.5 rounded-pill">
                            <i class="bi bi-clock me-1"></i>
                            <?= date('h:i A', strtotime($session['start_time'])) ?>
                            <?= esc($session['end_time'] ? ' - ' . date('h:i A', strtotime($session['end_time'])) : '') ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h3 class="h4 fw-bold text-dark mb-1">
                    <?= htmlspecialchars($session['notes'] ?: 'Class Roll Call') ?>
                </h3>
                <p class="text-muted small mb-0">Record present, late, absent, or excused status for enrolled students.</p>
            </div>
            <!-- Bulk Action Buttons -->
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold" onclick="markAll('present')">
                    <i class="bi bi-check2-all me-1"></i> Mark All Present
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold" onclick="markAll('excused')">
                    <i class="bi bi-shield-check me-1"></i> Mark All Excused
                </button>
            </div>
        </div>
    </div>

    <!-- Roll Call Form & Table Card -->
    <div class="lms-card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <form action="/sia/lms/faculty/course/<?= esc($course['lms_course_id']) ?>/attendance/<?= esc($session['id']) ?>/update" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
            <?= getCsrfInput() ?>
            
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="h6 fw-bold text-dark mb-0">Enrolled Student Roster (<?= count($students) ?>)</h5>
                <div style="max-width: 280px; width: 100%;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="attendanceSearchInput" class="form-control bg-light border-start-0" placeholder="Filter student name...">
                    </div>
                </div>
            </div>

            <div class="p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="attendanceTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3 text-muted text-uppercase small" style="width: 320px; letter-spacing: 0.04em;">Student Name</th>
                                <th class="py-3 text-center text-muted text-uppercase small" style="letter-spacing: 0.04em;">Attendance Status</th>
                                <th class="text-end pe-4 py-3 text-muted text-uppercase small" style="width: 280px; letter-spacing: 0.04em;">Remarks / Excuse Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <div class="lms-table-empty">
                                          <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
                                          No students enrolled in this section.
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): 
                                    $sId = $student['id'];
                                    $rec = $records[$sId] ?? null;
                                    $status = $rec['status'] ?? 'present';
                                    $studentName = trim($student['last_name'] . ', ' . $student['first_name']);
                                    $initial = strtoupper(substr($student['first_name'] ?? 'S', 0, 1));
                                ?>
                                    <tr class="att-row">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2.5">
                                                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; flex-shrink: 0; font-size: 0.85rem;">
                                                    <?= esc($initial) ?>
                                                </div>
                                                <div>
                                                    <span class="d-block fw-bold text-dark att-student-name"><?= htmlspecialchars($studentName) ?></span>
                                                    <small class="text-muted font-monospace" style="font-size: 0.72rem;"><?= htmlspecialchars($student['student_number']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <input type="radio" class="btn-check status-radio" name="attendance[<?= esc($sId) ?>][status]" id="status_<?= esc($sId) ?>_present" value="present" <?= esc($status === 'present' ? 'checked' : '') ?>>
                                                <label class="btn btn-outline-success px-3 fw-semibold" for="status_<?= esc($sId) ?>_present">
                                                    <i class="bi bi-check2"></i> Present
                                                </label>

                                                <input type="radio" class="btn-check status-radio" name="attendance[<?= esc($sId) ?>][status]" id="status_<?= esc($sId) ?>_late" value="late" <?= esc($status === 'late' ? 'checked' : '') ?>>
                                                <label class="btn btn-outline-warning px-3 fw-semibold text-dark" for="status_<?= esc($sId) ?>_late">
                                                    <i class="bi bi-clock"></i> Late
                                                </label>

                                                <input type="radio" class="btn-check status-radio" name="attendance[<?= esc($sId) ?>][status]" id="status_<?= esc($sId) ?>_absent" value="absent" <?= esc($status === 'absent' ? 'checked' : '') ?>>
                                                <label class="btn btn-outline-danger px-3 fw-semibold" for="status_<?= esc($sId) ?>_absent">
                                                    <i class="bi bi-x"></i> Absent
                                                </label>

                                                <input type="radio" class="btn-check status-radio" name="attendance[<?= esc($sId) ?>][status]" id="status_<?= esc($sId) ?>_excused" value="excused" <?= esc($status === 'excused' ? 'checked' : '') ?>>
                                                <label class="btn btn-outline-secondary px-3 fw-semibold" for="status_<?= esc($sId) ?>_excused">
                                                    <i class="bi bi-shield-check"></i> Excused
                                                </label>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <input type="text" name="attendance[<?= esc($sId) ?>][remarks]" class="form-control form-control-sm" placeholder="Reason / notes (optional)" value="<?= htmlspecialchars($rec['remarks'] ?? '') ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if (!empty($students)): ?>
                <div class="card-footer bg-white border-top p-4 d-flex justify-content-between align-items-center rounded-bottom-4">
                    <span class="text-muted small">All changes are recorded in the LMS course attendance log.</span>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Save Roll Call Records
                    </button>
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

(function() {
    const input = document.getElementById('attendanceSearchInput');
    if (!input) return;
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#attendanceTable .att-row');
        rows.forEach(row => {
            const nameEl = row.querySelector('.att-student-name');
            const name = nameEl ? nameEl.textContent.toLowerCase() : '';
            if (name.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
