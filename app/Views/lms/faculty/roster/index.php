<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Course Header & Horizontal Navigation -->
    <?php 
    $active_tab = 'roster';
    require __DIR__ . '/../components/course_header.php'; 
    ?>

    <div id="course-tab-content" class="course-tab-content">
        <!-- Quick Stats -->
        <?php
        $regularCount = 0;
        $irregularCount = 0;
        foreach ($students as $s) {
            if (strtolower($s['enrollment_type'] ?? '') === 'irregular') {
                $irregularCount++;
            } else {
                $regularCount++;
            }
        }
        $totalStudents = count($students);
        ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card-kpi h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-label">Total Class Size</span>
                        <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $totalStudents ?></div>
                    <div class="stat-subtext text-primary">
                        <i class="bi bi-people-fill me-1"></i> Official Enrolled Students
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-kpi h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-label">Regular Students</span>
                        <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $regularCount ?></div>
                    <div class="stat-subtext text-success">
                        <i class="bi bi-check-circle-fill me-1"></i> Block Section Enrollees
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-kpi h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="stat-label">Irregular Students</span>
                        <div class="stat-icon-wrapper" style="background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);">
                            <i class="bi bi-shuffle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $irregularCount ?></div>
                    <div class="stat-subtext text-info">
                        <i class="bi bi-arrow-left-right me-1"></i> Cross-Enrolled / Retakers
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Action Toolbar -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
            <div>
                <h4 class="h5 fw-bold text-dark mb-1">
                    <i class="bi bi-people me-2 text-primary"></i>Official Student Roster
                </h4>
                <p class="text-muted small mb-0">Officially enrolled students taking <?= htmlspecialchars($course['subject_code']) ?> in Section <?= htmlspecialchars($course['section_code']) ?>.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div style="min-width: 260px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted rounded-start-pill ps-3"><i class="bi bi-search"></i></span>
                        <input type="text" id="rosterSearchInput" class="form-control bg-white border-start-0 rounded-end-pill py-2 shadow-none small" placeholder="Filter student name or #...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Roster Table Card -->
        <div class="dossier-card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
            <div class="p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 dashboard-table" id="rosterTable">
                        <thead>
                            <tr>
                                <th class="ps-4 py-3" style="width: 60px;">#</th>
                                <th class="py-3">Student Number</th>
                                <th class="py-3">Student Name</th>
                                <th class="py-3">Institutional Email</th>
                                <th class="py-3">Classification</th>
                                <th class="py-3">Enrollment Status</th>
                                <th class="text-end pe-4 py-3">Enrolled Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <div class="lms-table-empty">
                                          <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
                                          No students are currently enrolled in this course shell.
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $idx => $st): 
                                    $studentName = trim($st['last_name'] . ', ' . $st['first_name']);
                                    $initial = strtoupper(substr($st['first_name'] ?? 'S', 0, 1));
                                    $isIrreg = (strtolower($st['enrollment_type'] ?? '') === 'irregular');
                                ?>
                                    <tr class="roster-row">
                                        <td class="ps-4 text-muted small fw-semibold"><?= $idx + 1 ?></td>
                                        <td>
                                            <span class="applicant-ref-badge font-monospace roster-snum">
                                                <i class="bi bi-fingerprint text-primary me-1"></i><?= htmlspecialchars($st['student_number']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2.5">
                                                <div class="applicant-avatar text-white fw-bold shadow-xs flex-shrink-0" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">
                                                    <?= esc($initial) ?>
                                                </div>
                                                <span class="fw-bold text-dark roster-student-name">
                                                    <?= htmlspecialchars($studentName) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted small">
                                                <i class="bi bi-envelope me-1 text-primary"></i>
                                                <?= htmlspecialchars($st['email'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($isIrreg): ?>
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-1">
                                                    <i class="bi bi-shuffle me-1"></i>Irregular
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1">
                                                    <i class="bi bi-check-circle me-1"></i>Regular
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 fw-bold">
                                                <?= strtoupper(htmlspecialchars($st['enrollment_status'] ?? 'ENROLLED')) ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4 text-muted small">
                                            <?= esc($st['enrolled_at'] ? date('M d, Y', strtotime($st['enrolled_at'])) : '-') ?>
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

<script>
(function() {
    const input = document.getElementById('rosterSearchInput');
    if (!input) return;
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#rosterTable .roster-row');
        rows.forEach(row => {
            const nameEl = row.querySelector('.roster-student-name');
            const numEl = row.querySelector('.roster-snum');
            const name = nameEl ? nameEl.textContent.toLowerCase() : '';
            const snum = numEl ? numEl.textContent.toLowerCase() : '';
            if (name.includes(query) || snum.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
