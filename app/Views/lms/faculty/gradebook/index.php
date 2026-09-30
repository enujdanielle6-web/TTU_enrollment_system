<?php require_once __DIR__ . '/../layout_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Course Header & Horizontal Navigation -->
    <?php 
    $active_tab = 'gradebook';
    require __DIR__ . '/../components/course_header.php'; 
    ?>

    <div id="course-tab-content" class="course-tab-content">
        <?php
        $assignments = $gradebook['assignments'] ?? [];
        $quizzes = $gradebook['quizzes'] ?? [];
        $grid = $gradebook['grid'] ?? [];
        $totalPossible = (float)($gradebook['total_possible'] ?? 0);
        $totalStudents = count($grid);

        $passingStudents = 0;
        $totalPercentages = [];
        foreach ($grid as $row) {
            $pct = (float)($row['percentage'] ?? 0);
            $totalPercentages[] = $pct;
            if ($pct >= 75.0) {
                $passingStudents++;
            }
        }

        $classAverage = !empty($totalPercentages) ? round(array_sum($totalPercentages) / count($totalPercentages), 1) : 0;
        $passingRate = $totalStudents > 0 ? round(($passingStudents / $totalStudents) * 100, 1) : 0;
        $totalAssessmentItems = count($assignments) + count($quizzes);
        ?>

        <!-- KPI Summary Strip -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="lms-card p-3 bg-white border-0 shadow-sm rounded-4 border-start border-4 border-primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Class Average</span>
                            <h3 class="mb-0 fw-bold mt-1 text-primary"><?= $classAverage ?>%</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-graph-up-arrow fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="lms-card p-3 bg-white border-0 shadow-sm rounded-4 border-start border-4 border-success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Passing Rate</span>
                            <h3 class="mb-0 fw-bold mt-1 text-success"><?= $passingRate ?>%</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-check2-all fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="lms-card p-3 bg-white border-0 shadow-sm rounded-4 border-start border-4 border-info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Class Roster</span>
                            <h3 class="mb-0 fw-bold mt-1 text-dark"><?= $totalStudents ?></h3>
                        </div>
                        <div class="bg-info bg-opacity-10 text-info rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-people fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="lms-card p-3 bg-white border-0 shadow-sm rounded-4 border-start border-4 border-warning">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Gradable Items</span>
                            <h3 class="mb-0 fw-bold mt-1 text-dark"><?= $totalAssessmentItems ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-clipboard2-check fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Action Toolbar -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
            <div>
                <h4 class="h5 fw-bold text-dark mb-1">
                    <i class="bi bi-star me-2 text-warning"></i>Course Gradebook Matrix
                </h4>
                <p class="text-muted small mb-0">Continuous assessment record of assignments, quizzes, and weighted course percentages.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div style="min-width: 240px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="gradebookStudentFilter" class="form-control bg-white border-start-0" placeholder="Filter student name...">
                    </div>
                </div>
                <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold shadow-xs" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print / Export
                </button>
            </div>
        </div>

        <!-- Gradebook Table Card -->
        <div class="lms-card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
            <div class="p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0 text-center" id="gradebookTable" style="min-width: 1100px;">
                        <thead class="table-light align-middle">
                            <tr>
                                <th rowspan="2" class="text-start ps-4 bg-light text-dark fw-bold" style="width: 260px; position: sticky; left: 0; z-index: 2; box-shadow: 2px 0 5px rgba(0,0,0,0.04);">
                                    Student Information
                                </th>
                                
                                <?php if (!empty($assignments)): ?>
                                    <th colspan="<?= count($assignments) ?>" class="bg-primary bg-opacity-10 text-primary border-primary border-opacity-25 fw-bold py-2">
                                        <i class="bi bi-journal-text me-1"></i> Course Assignments (<?= count($assignments) ?>)
                                    </th>
                                <?php endif; ?>

                                <?php if (!empty($quizzes)): ?>
                                    <th colspan="<?= count($quizzes) ?>" class="bg-info bg-opacity-10 text-info border-info border-opacity-25 fw-bold py-2">
                                        <i class="bi bi-pencil-square me-1"></i> Online Quizzes (<?= count($quizzes) ?>)
                                    </th>
                                <?php endif; ?>

                                <th rowspan="2" class="bg-light text-dark fw-bold" style="width: 120px;">
                                    Total Points<br>
                                    <span class="text-muted small fw-normal">/ <?= esc($totalPossible) ?></span>
                                </th>
                                <th rowspan="2" class="bg-light text-dark fw-bold" style="width: 130px;">
                                    Overall Grade
                                </th>
                            </tr>
                            <tr>
                                <?php foreach ($assignments as $a): ?>
                                    <th class="small fw-semibold bg-primary bg-opacity-10 border-primary border-opacity-25" style="width: 125px;">
                                        <div class="text-truncate fw-bold text-dark" style="max-width: 115px;" title="<?= htmlspecialchars($a['title']) ?>">
                                            <?= htmlspecialchars($a['title']) ?>
                                        </div>
                                        <div class="text-muted small"><?= esc($a['max_score']) ?> pts</div>
                                    </th>
                                <?php endforeach; ?>

                                <?php foreach ($quizzes as $q): ?>
                                    <th class="small fw-semibold bg-info bg-opacity-10 border-info border-opacity-25" style="width: 125px;">
                                        <div class="text-truncate fw-bold text-dark" style="max-width: 115px;" title="<?= htmlspecialchars($q['title']) ?>">
                                            <?= htmlspecialchars($q['title']) ?>
                                        </div>
                                        <div class="text-muted small"><?= esc($gradebook['quiz_max_points'][$q['id']] ?? 0) ?> pts</div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($grid)): ?>
                                <tr>
                                    <td colspan="100%" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
                                        No students are currently enrolled in this course section.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($grid as $row): 
                                    $studentName = trim($row['student']['last_name'] . ', ' . $row['student']['first_name']);
                                    $studentNum = $row['student']['student_number'] ?? 'N/A';
                                    $initial = strtoupper(substr($row['student']['first_name'] ?? 'S', 0, 1));
                                    $pct = (float)$row['percentage'];
                                    $isPassing = ($pct >= 75.0);
                                ?>
                                    <tr class="gradebook-row">
                                        <td class="text-start ps-4 bg-white" style="position: sticky; left: 0; z-index: 1; box-shadow: 2px 0 5px rgba(0,0,0,0.04);">
                                            <div class="d-flex align-items-center gap-2.5">
                                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; flex-shrink: 0; font-size: 0.8rem;">
                                                    <?= esc($initial) ?>
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="d-block fw-bold text-dark text-truncate gb-student-name" style="max-width: 180px;" title="<?= htmlspecialchars($studentName) ?>">
                                                        <?= htmlspecialchars($studentName) ?>
                                                    </span>
                                                    <small class="text-muted font-monospace" style="font-size: 0.72rem;"><?= htmlspecialchars($studentNum) ?></small>
                                                </div>
                                            </div>
                                        </td>

                                        <?php foreach ($assignments as $a): 
                                            $sc = $row['assignments'][$a['id']] ?? null;
                                        ?>
                                            <td class="<?= esc($sc === null ? 'text-muted bg-light bg-opacity-50' : 'fw-semibold text-dark') ?>">
                                                <?= $sc !== null ? esc($sc) : '<span class="text-muted">—</span>' ?>
                                            </td>
                                        <?php endforeach; ?>

                                        <?php foreach ($quizzes as $q): 
                                            $sc = $row['quizzes'][$q['id']] ?? null;
                                        ?>
                                            <td class="<?= esc($sc === null ? 'text-muted bg-light bg-opacity-50' : 'fw-semibold text-dark') ?>">
                                                <?= $sc !== null ? esc($sc) : '<span class="text-muted">—</span>' ?>
                                            </td>
                                        <?php endforeach; ?>

                                        <td class="fw-bold bg-light text-dark">
                                            <?= esc($row['total']) ?>
                                        </td>
                                        <td class="bg-light">
                                            <span class="badge rounded-pill px-2.5 py-1 fw-bold <?= esc($isPassing ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25') ?>">
                                                <?= number_format($pct, 1) ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Academic Policy Callout (Consistent with Student Portal) -->
        <div class="alert bg-white border rounded-4 p-4 shadow-sm mb-4">
            <div class="d-flex align-items-start gap-3">
                <div class="icon-box-sm bg-primary bg-opacity-10 text-primary mt-1 flex-shrink-0" style="width: 36px; height: 36px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-info-circle-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">About Continuous Assessment Grades</h6>
                    <p class="text-muted small mb-0 lh-base">
                        The grades shown in this matrix represent live coursework and online quiz assessments recorded inside the TTU LMS. Final academic grades computed with official departmental transmutation rubrics are submitted at the close of the academic period via the <strong>Grading Sheet Portal</strong> to the Office of the Registrar.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const input = document.getElementById('gradebookStudentFilter');
    if (!input) return;
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#gradebookTable .gradebook-row');
        rows.forEach(row => {
            const nameEl = row.querySelector('.gb-student-name');
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
