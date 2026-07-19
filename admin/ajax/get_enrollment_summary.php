<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Only admins handling enrollment can access
requirePermission('enrollment.finalize');

header('Content-Type: text/html');

$sectionId = (int)($_GET['section_id'] ?? 0);
if ($sectionId <= 0) {
    echo '<div class="alert alert-danger mb-0">Invalid Section ID.</div>';
    exit;
}

try {
    $appId = (int)($_GET['app_id'] ?? 0);
    
    // Retrieve Section Details
    $secStmt = $pdo->prepare('
        SELECT cs.id, cs.section_code, cs.year_level, cs.semester, cs.curriculum_id, 
               cp.code as program_code, cp.name as program_name
        FROM college_sections cs
        JOIN college_programs cp ON cs.program_id = cp.id
        WHERE cs.id = ?
    ');
    $secStmt->execute([$sectionId]);
    $section = $secStmt->fetch(PDO::FETCH_ASSOC);

    if (!$section) {
        echo '<div class="alert alert-warning mb-0">Section not found.</div>';
        exit;
    }

    $studentCurriculumId = null;
    $versionName = 'Unknown Curriculum';

    // Look up the student's permanently assigned curriculum if they have one
    if ($appId > 0) {
        $appStmt = $pdo->prepare('SELECT user_id, strand FROM applications WHERE id = ?');
        $appStmt->execute([$appId]);
        $appData = $appStmt->fetch(PDO::FETCH_ASSOC);

        if ($appData) {
            $userStmt = $pdo->prepare('SELECT college_curriculum_id FROM users WHERE id = ?');
            $userStmt->execute([$appData['user_id']]);
            $studentCurriculumId = $userStmt->fetchColumn();

            if (!$studentCurriculumId) {
                // Determine active curriculum for the program
                $activeCurrStmt = $pdo->prepare('
                    SELECT cc.id 
                    FROM college_curricula cc 
                    JOIN college_programs cp ON cc.program_id = cp.id 
                    WHERE cp.code = ? AND cc.status = "active" 
                    ORDER BY cc.created_at DESC LIMIT 1
                ');
                $activeCurrStmt->execute([$appData['strand']]);
                $studentCurriculumId = $activeCurrStmt->fetchColumn();
            }
        }
    }

    // Fallback if needed
    if (!$studentCurriculumId) $studentCurriculumId = $section['curriculum_id'];

    if (!$studentCurriculumId) {
        echo '<div class="alert alert-warning mb-0">No Curriculum is explicitly bound to this Section or Student.</div>';
        exit;
    }

    // Get the Version Name of the curriculum the student will use
    $verStmt = $pdo->prepare('SELECT curriculum_name FROM college_curricula WHERE id = ?');
    $verStmt->execute([$studentCurriculumId]);
    $versionName = $verStmt->fetchColumn() ?: 'Unknown';

    // Retrieve Subjects strictly from the STUDENT's Curriculum matching Year/Sem
    $subStmt = $pdo->prepare('
        SELECT sub.id as subject_id, sub.subject_code, sub.subject_name, sub.units, sub.subject_type, ccs.display_order
        FROM college_curriculum_subjects ccs
        JOIN subjects sub ON ccs.subject_id = sub.id
        WHERE ccs.curriculum_id = ? AND ccs.year_level = ? AND (ccs.semester = ? OR ccs.semester IS NULL OR ccs.semester = "")
        ORDER BY ccs.display_order ASC, sub.subject_code ASC
    ');
    $subStmt->execute([$studentCurriculumId, $section['year_level'], $section['semester']]);
    $subjects = $subStmt->fetchAll(PDO::FETCH_ASSOC);

    // Retrieve Schedule from college_section_subjects
    $schedStmt = $pdo->prepare('
        SELECT subject_id, day, start_time, end_time, room, instructor
        FROM college_section_subjects
        WHERE college_section_id = ?
    ');
    $schedStmt->execute([$sectionId]);
    $schedulesData = $schedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group schedules by subject
    $schedules = [];
    foreach ($schedulesData as $s) {
        $schedules[$s['subject_id']][] = $s;
    }

    $totalUnits = array_sum(array_column($subjects, 'units'));
    $totalSubjects = count($subjects);

    // Render HTML Summary
    ?>
    <div class="mt-3 p-3 bg-light border border-info rounded">
        <h6 class="fw-bold text-info-emphasis mb-3"><i class="bi bi-card-checklist me-1"></i> Enrollment Summary Preview</h6>
        
        <div class="row g-2 mb-3 small">
            <div class="col-6">
                <div class="text-muted fw-semibold">Program</div>
                <div class="fw-bold text-dark"><?= htmlspecialchars($section['program_name'], ENT_QUOTES) ?></div>
            </div>
            <div class="col-6">
                <div class="text-muted fw-semibold">Curriculum</div>
                <div class="fw-bold text-dark text-truncate"><?= htmlspecialchars($versionName, ENT_QUOTES) ?></div>
            </div>
            <div class="col-4">
                <div class="text-muted fw-semibold">Year & Sem</div>
                <div class="fw-bold text-dark"><?= htmlspecialchars($section['year_level'], ENT_QUOTES) ?> - <?= htmlspecialchars($section['semester'], ENT_QUOTES) ?></div>
            </div>
            <div class="col-4">
                <div class="text-muted fw-semibold">Total Subjects</div>
                <div class="fw-bold text-dark"><?= $totalSubjects ?></div>
            </div>
            <div class="col-4">
                <div class="text-muted fw-semibold">Total Units</div>
                <div class="fw-bold text-dark"><?= $totalUnits ?></div>
            </div>
        </div>

        <div class="table-responsive bg-white rounded border shadow-sm">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                <thead class="table-light text-muted text-uppercase">
                    <tr>
                        <th class="ps-2">Subject</th>
                        <th>Schedule</th>
                        <th class="text-center pe-2">Units</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">No subjects found in Curriculum.</td></tr>
                    <?php else: ?>
                        <?php foreach ($subjects as $sub): ?>
                            <tr>
                                <td class="ps-2">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($sub['subject_code'], ENT_QUOTES) ?></div>
                                    <div class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($sub['subject_name'], ENT_QUOTES) ?></div>
                                </td>
                                <td>
                                    <?php 
                                        $subScheds = $schedules[$sub['subject_id']] ?? [];
                                        if (empty($subScheds)) {
                                            echo '<span class="text-warning fst-italic">Unscheduled</span>';
                                        } else {
                                            foreach ($subScheds as $sc) {
                                                if ($sc['day'] === 'TBA') {
                                                    echo '<span class="text-warning fst-italic">TBA</span><br>';
                                                    continue;
                                                }
                                                $st = date('h:i A', strtotime($sc['start_time']));
                                                $et = date('h:i A', strtotime($sc['end_time']));
                                                $room = $sc['room'] ? htmlspecialchars($sc['room'], ENT_QUOTES) : 'TBA';
                                                echo "<div class='text-primary fw-medium' style='font-size: 0.7rem;'>{$sc['day']} {$st}-{$et} ({$room})</div>";
                                            }
                                        }
                                    ?>
                                </td>
                                <td class="text-center pe-2 fw-medium text-dark"><?= $sub['units'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-3 text-muted" style="font-size: 0.7rem;">
            <i class="bi bi-info-circle me-1"></i> Subjects and schedules are automatically derived from the assigned Curriculum Version and Section Schedule.
        </div>
    </div>
    <?php
} catch (\Throwable $e) {
    error_log('Enrollment Summary error: ' . $e->getMessage());
    echo '<div class="alert alert-danger mb-0">Error fetching summary: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</div>';
}
