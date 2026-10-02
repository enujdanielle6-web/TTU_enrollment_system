<?php
/**
 * Automated Verification Suite for LMS Faculty Portal UI Enhancements
 * Enforces design system consistency, token usage, syntax validity, and security.
 */

$testResults = [];
$totalTests = 0;
$passedTests = 0;

function runTest(string $description, callable $fn) {
    global $totalTests, $passedTests;
    $totalTests++;
    try {
        $result = $fn();
        if ($result === true) {
            $passedTests++;
            echo " [PASS] Test {$totalTests}: {$description}\n";
            return true;
        } else {
            echo " [FAIL] Test {$totalTests}: {$description} (Assertion returned false)\n";
            return false;
        }
    } catch (\Throwable $e) {
        echo " [FAIL] Test {$totalTests}: {$description} (Exception: " . $e->getMessage() . ")\n";
        return false;
    }
}

echo "====================================================================\n";
echo "      LMS FACULTY PORTAL UI ENHANCEMENT VERIFICATION SUITE          \n";
echo "====================================================================\n\n";

$facultyViewsDir = dirname(__DIR__) . '/app/Views/lms/faculty';

// 1. Check layout_header.php
runTest("Faculty layout header links <?= BASE_PATH ?>/css/main.css and applies lms-faculty-layout", function () use ($facultyViewsDir) {
    $content = file_get_contents($facultyViewsDir . '/layout_header.php');
    return strpos($content, '<?= BASE_PATH ?>/css/main.css') !== false 
        && strpos($content, 'lms-faculty-layout') !== false
        && strpos($content, 'lms-admin-topbar') !== false;
});

// 2. Check layout_header.php topbar elements
runTest("Faculty layout header includes term indicator badge, schedule link, and profile dropdown", function () use ($facultyViewsDir) {
    $content = file_get_contents($facultyViewsDir . '/layout_header.php');
    return strpos($content, 'pulse-dot-green') !== false
        && strpos($content, '<?= BASE_PATH ?>/lms/faculty/calendar') !== false
        && strpos($content, 'facultyUserDropdown') !== false;
});

// 3. Check dashboard.php tokens
runTest("Faculty dashboard uses dossier-hero-strip, stat-card-kpi, shortcut-item, and dossier-card", function () use ($facultyViewsDir) {
    $content = file_get_contents($facultyViewsDir . '/dashboard.php');
    return strpos($content, 'dossier-hero-strip') !== false
        && strpos($content, 'stat-card-kpi') !== false
        && strpos($content, 'shortcut-item') !== false
        && strpos($content, 'dossier-card') !== false
        && strpos($content, 'applicant-avatar') !== false;
});

// 4. Check roster/index.php
runTest("Faculty roster index incorporates stat-card-kpi and dashboard-table with valid syntax", function () use ($facultyViewsDir) {
    $content = file_get_contents($facultyViewsDir . '/roster/index.php');
    return strpos($content, 'stat-card-kpi') !== false
        && strpos($content, 'dashboard-table') !== false
        && strpos($content, 'applicant-ref-badge') !== false;
});

// 5. Check profile.php
runTest("Faculty profile incorporates dossier-hero-strip and dossier-card", function () use ($facultyViewsDir) {
    $content = file_get_contents($facultyViewsDir . '/profile.php');
    return strpos($content, 'dossier-hero-strip') !== false
        && strpos($content, 'dossier-card') !== false
        && strpos($content, 'applicant-avatar') !== false;
});

// 6. Check messages.php
runTest("Faculty messages incorporates dossier-hero-strip and 2-pane dossier-card", function () use ($facultyViewsDir) {
    $content = file_get_contents($facultyViewsDir . '/messages.php');
    return strpos($content, 'dossier-hero-strip') !== false
        && strpos($content, 'dossier-card') !== false
        && strpos($content, 'newMessageModal') !== false;
});

// 7. Check calendar/index.php
runTest("Faculty calendar incorporates dossier-hero-strip and clean main layout wrapper", function () use ($facultyViewsDir) {
    $content = file_get_contents($facultyViewsDir . '/calendar/index.php');
    return strpos($content, 'dossier-hero-strip') !== false
        && strpos($content, '<main class="py-4 bg-light min-vh-100">') !== false;
});

// 8. Check assignments/index.php
runTest("Faculty assignments index utilizes stat-card-kpi executive tokens", function () use ($facultyViewsDir) {
    $content = file_get_contents($facultyViewsDir . '/assignments/index.php');
    return strpos($content, 'stat-card-kpi') !== false;
});

// 9. Check announcements/index.php
runTest("Faculty announcements index utilizes stat-card-kpi executive tokens", function () use ($facultyViewsDir) {
    $content = file_get_contents($facultyViewsDir . '/announcements/index.php');
    return strpos($content, 'stat-card-kpi') !== false;
});

// 10. Check quizzes/index.php
runTest("Faculty quizzes index utilizes stat-card-kpi executive tokens", function () use ($facultyViewsDir) {
    $content = file_get_contents($facultyViewsDir . '/quizzes/index.php');
    return strpos($content, 'stat-card-kpi') !== false;
});

// 11. Check gradebook/index.php
runTest("Faculty gradebook index utilizes stat-card-kpi executive tokens", function () use ($facultyViewsDir) {
    $content = file_get_contents($facultyViewsDir . '/gradebook/index.php');
    return strpos($content, 'stat-card-kpi') !== false;
});

// 12. Check attendance/index.php
runTest("Faculty attendance index utilizes stat-card-kpi executive tokens", function () use ($facultyViewsDir) {
    $content = file_get_contents($facultyViewsDir . '/attendance/index.php');
    return strpos($content, 'stat-card-kpi') !== false;
});

// 13. Check CSRF protection across faculty forms
runTest("Faculty post forms implement getCsrfInput() protection", function () use ($facultyViewsDir) {
    $courseFile = file_get_contents($facultyViewsDir . '/course.php');
    return strpos($courseFile, 'getCsrfInput()') !== false;
});

// 14. Check PHP syntax across all faculty view templates
runTest("All faculty view templates compile cleanly via PHP lint", function () use ($facultyViewsDir) {
    $files = [
        $facultyViewsDir . '/layout_header.php',
        $facultyViewsDir . '/layout_footer.php',
        $facultyViewsDir . '/dashboard.php',
        $facultyViewsDir . '/course.php',
        $facultyViewsDir . '/profile.php',
        $facultyViewsDir . '/messages.php',
        $facultyViewsDir . '/calendar/index.php',
        $facultyViewsDir . '/assignments/index.php',
        $facultyViewsDir . '/announcements/index.php',
        $facultyViewsDir . '/quizzes/index.php',
        $facultyViewsDir . '/gradebook/index.php',
        $facultyViewsDir . '/attendance/index.php',
        $facultyViewsDir . '/roster/index.php'
    ];

    foreach ($files as $file) {
        $output = [];
        $returnVar = 0;
        exec("php -l " . escapeshellarg($file), $output, $returnVar);
        if ($returnVar !== 0) {
            throw new Exception("Lint error in {$file}: " . implode("\n", $output));
        }
    }
    return true;
});

echo "\n====================================================================\n";
echo "SUMMARY: {$passedTests} / {$totalTests} TESTS PASSED (" . round(($passedTests / $totalTests) * 100) . "%)\n";
echo "====================================================================\n";

if ($passedTests === $totalTests) {
    echo ">>> RELEASE GATE: SUCCESS - ALL FACULTY UI TESTS PASSED! <<<\n\n";
    exit(0);
} else {
    echo ">>> RELEASE GATE: FAILED - SOME TESTS DID NOT PASS. <<<\n\n";
    exit(1);
}
