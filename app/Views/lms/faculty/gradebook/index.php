<?php require_once __DIR__ . '/../../../../Views/components/header.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Gradebook: <?= htmlspecialchars($course['subject_code']) ?></h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($course['subject_name']) ?> &bull; Section <?= htmlspecialchars($course['section_code']) ?></p>
        </div>
        <button class="btn btn-outline-secondary shadow-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print / Export
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 text-center" style="min-width: 1000px;">
                    <thead class="table-light align-middle">
                        <tr>
                            <th rowspan="2" class="text-start ps-4" style="width: 250px;">Student Name</th>
                            
                            <?php if (!empty($gradebook['assignments'])): ?>
                                <th colspan="<?= count($gradebook['assignments']) ?>" class="bg-primary bg-opacity-10 text-primary border-primary border-opacity-25">Assignments</th>
                            <?php endif; ?>

                            <?php if (!empty($gradebook['quizzes'])): ?>
                                <th colspan="<?= count($gradebook['quizzes']) ?>" class="bg-info bg-opacity-10 text-info border-info border-opacity-25">Quizzes</th>
                            <?php endif; ?>

                            <th rowspan="2" class="bg-light text-dark fw-bold">Total Pts<br><span class="text-muted small fw-normal"><?= esc($gradebook['total_possible']) ?></span></th>
                            <th rowspan="2" class="bg-light text-dark fw-bold">Percentage</th>
                        </tr>
                        <tr>
                            <?php foreach ($gradebook['assignments'] as $a): ?>
                                <th class="small fw-medium bg-primary bg-opacity-10 border-primary border-opacity-25" style="width: 120px;">
                                    <div class="text-truncate" style="max-width: 100px;" title="<?= htmlspecialchars($a['title']) ?>">
                                        <?= htmlspecialchars($a['title']) ?>
                                    </div>
                                    <div class="text-muted"><?= esc($a['max_score']) ?> pts</div>
                                </th>
                            <?php endforeach; ?>

                            <?php foreach ($gradebook['quizzes'] as $q): ?>
                                <th class="small fw-medium bg-info bg-opacity-10 border-info border-opacity-25" style="width: 120px;">
                                    <div class="text-truncate" style="max-width: 100px;" title="<?= htmlspecialchars($q['title']) ?>">
                                        <?= htmlspecialchars($q['title']) ?>
                                    </div>
                                    <div class="text-muted"><?= esc($gradebook['quiz_max_points'][$q['id']]) ?> pts</div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($gradebook['grid'])): ?>
                            <tr>
                                <td colspan="100%" class="text-center py-5 text-muted">
                                    No students enrolled in this course section.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($gradebook['grid'] as $row): ?>
                                <tr>
                                    <td class="text-start ps-4 fw-bold text-dark">
                                        <?= htmlspecialchars($row['student']['last_name'] . ', ' . $row['student']['first_name']) ?>
                                    </td>

                                    <?php foreach ($gradebook['assignments'] as $a): ?>
                                        <td class="<?= esc($row['assignments'][$a['id']] === null ? 'text-muted bg-light' : '') ?>">
                                            <?= esc($row['assignments'][$a['id']] !== null ? $row['assignments'][$a['id']] : '-') ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <?php foreach ($gradebook['quizzes'] as $q): ?>
                                        <td class="<?= esc($row['quizzes'][$q['id']] === null ? 'text-muted bg-light' : '') ?>">
                                            <?= esc($row['quizzes'][$q['id']] !== null ? $row['quizzes'][$q['id']] : '-') ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <td class="fw-bold bg-light"><?= esc($row['total']) ?></td>
                                    <td class="fw-bold bg-light <?= esc($row['percentage'] >= 75 ? 'text-success' : 'text-danger') ?>">
                                        <?= number_format($row['percentage'], 1) ?>%
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
