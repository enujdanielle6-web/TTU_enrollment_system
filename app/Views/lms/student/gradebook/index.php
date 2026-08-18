<?php require_once __DIR__ . '/../../../../Views/lms/student/layout_header.php'; ?>

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sia/lms/student/dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/sia/lms/student/course.php?id=<?= esc($course['lms_course_id']) ?>" class="text-decoration-none"><?= htmlspecialchars($course['subject_code']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Grades</li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="lms-card p-4 border-0 shadow-sm rounded-4 mb-4 text-center">
                <h5 class="fw-bold text-dark mb-4">Overall Course Grade</h5>
                
                <?php
                    $percentage = $data['my_grades'] ? $data['my_grades']['percentage'] : 0;
                    $totalScore = $data['my_grades'] ? $data['my_grades']['total'] : 0;
                ?>

                <div class="d-inline-block border border-3 rounded-circle p-5 mb-3 shadow-sm <?= esc($percentage >= 75 ? 'border-success' : 'border-danger') ?>" style="width: 200px; height: 200px; display: flex; align-items: center; justify-content: center;">
                    <div>
                        <div class="fs-1 fw-bold <?= esc($percentage >= 75 ? 'text-success' : 'text-danger') ?>">
                            <?= number_format($percentage, 1) ?>%
                        </div>
                        <div class="text-muted small"><?= esc($totalScore) ?> / <?= esc($data['total_possible']) ?> pts</div>
                    </div>
                </div>

                <p class="text-muted mt-2">
                    This grade reflects all graded assignments and quizzes in the LMS. It may not represent your final official grade.
                </p>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="lms-card p-0 border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="bg-primary bg-opacity-10 p-3 border-bottom">
                    <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-journal-text me-2"></i>Assignments</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Assignment</th>
                                <th class="text-end pe-4">Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data['assignments'])): ?>
                                <tr><td colspan="2" class="text-center py-4 text-muted">No assignments published.</td></tr>
                            <?php else: ?>
                                <?php foreach ($data['assignments'] as $a): 
                                    $score = $data['my_grades'] ? $data['my_grades']['assignments'][$a['id']] : null;
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-medium text-dark">
                                            <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/assignments/<?= esc($a['id']) ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($a['title']) ?>
                                            </a>
                                        </td>
                                        <td class="text-end pe-4 fw-bold">
                                            <?= esc($score !== null ? $score : '<span class="text-muted fw-normal">Not graded</span>') ?> / <?= esc($a['max_score']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="lms-card p-0 border-0 shadow-sm rounded-4 overflow-hidden mt-4">
                <div class="bg-info bg-opacity-10 p-3 border-bottom">
                    <h5 class="fw-bold mb-0 text-info"><i class="bi bi-ui-checks me-2"></i>Quizzes</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Quiz</th>
                                <th class="text-end pe-4">Highest Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data['quizzes'])): ?>
                                <tr><td colspan="2" class="text-center py-4 text-muted">No quizzes published.</td></tr>
                            <?php else: ?>
                                <?php foreach ($data['quizzes'] as $q): 
                                    $score = $data['my_grades'] ? $data['my_grades']['quizzes'][$q['id']] : null;
                                    $maxPts = $data['quiz_max_points'][$q['id']];
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-medium text-dark">
                                            <a href="/sia/lms/student/course/<?= esc($course['lms_course_id']) ?>/quizzes/<?= esc($q['id']) ?>" class="text-decoration-none text-info">
                                                <?= htmlspecialchars($q['title']) ?>
                                            </a>
                                        </td>
                                        <td class="text-end pe-4 fw-bold">
                                            <?= esc($score !== null ? $score : '<span class="text-muted fw-normal">Not graded</span>') ?> / <?= esc($maxPts) ?>
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
