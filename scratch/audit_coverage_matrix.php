<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Helper to get all obsidian docs content
$docFiles = glob(__DIR__ . '/../docs/obsidian/*/*.md');
$docContents = [];
foreach ($docFiles as $df) {
    $docContents[basename($df)] = file_get_contents($df);
}

// 1. Controllers coverage
$controllers = [
    'ApplicantController.php' => 'app/Controllers/ApplicantController.php',
    'AuthController.php' => 'app/Controllers/AuthController.php',
    'DocumentController.php' => 'app/Controllers/DocumentController.php',
    'EnrollController.php' => 'app/Controllers/EnrollController.php',
    'HealthController.php' => 'app/Controllers/HealthController.php',
    'HomeController.php' => 'app/Controllers/HomeController.php',
    'LmsAdminController.php' => 'app/Controllers/Admin/LmsAdminController.php',
    'AdmissionsController.php' => 'app/Controllers/Admin/Admissions/AdmissionsController.php',
    'ClinicController.php' => 'app/Controllers/Admin/Clinic/ClinicController.php',
    'FeeController.php' => 'app/Controllers/Admin/Finance/FeeController.php',
    'FinanceController.php' => 'app/Controllers/Admin/Finance/FinanceController.php',
    'CollegeController.php' => 'app/Controllers/Admin/Registrar/CollegeController.php',
    'RegistrarController.php' => 'app/Controllers/Admin/Registrar/RegistrarController.php',
    'ShsController.php' => 'app/Controllers/Admin/Registrar/ShsController.php',
    'SubjectController.php' => 'app/Controllers/Admin/Registrar/SubjectController.php',
    'SchedulerController.php' => 'app/Controllers/Admin/Scheduler/SchedulerController.php',
    'ScholarshipController.php' => 'app/Controllers/Admin/Scholarship/ScholarshipController.php',
    'DashboardController.php' => 'app/Controllers/Admin/System/DashboardController.php',
    'ReportController.php' => 'app/Controllers/Admin/System/ReportController.php',
    'SystemController.php' => 'app/Controllers/Admin/System/SystemController.php',
    'AdminApiController.php' => 'app/Controllers/Api/AdminApiController.php',
    'ApplicantApiController.php' => 'app/Controllers/Api/ApplicantApiController.php',
    'DownloadController.php' => 'app/Controllers/Lms/DownloadController.php',
    'FacultyAnnouncementController.php' => 'app/Controllers/Lms/FacultyAnnouncementController.php',
    'FacultyAssignmentController.php' => 'app/Controllers/Lms/FacultyAssignmentController.php',
    'FacultyAttendanceController.php' => 'app/Controllers/Lms/FacultyAttendanceController.php',
    'FacultyCalendarController.php' => 'app/Controllers/Lms/FacultyCalendarController.php',
    'FacultyController.php' => 'app/Controllers/Lms/FacultyController.php',
    'FacultyGradebookController.php' => 'app/Controllers/Lms/FacultyGradebookController.php',
    'FacultyQuizController.php' => 'app/Controllers/Lms/FacultyQuizController.php',
    'LmsAuthController.php' => 'app/Controllers/Lms/LmsAuthController.php',
    'StudentAnnouncementController.php' => 'app/Controllers/Lms/StudentAnnouncementController.php',
    'StudentAssignmentController.php' => 'app/Controllers/Lms/StudentAssignmentController.php',
    'StudentAttendanceController.php' => 'app/Controllers/Lms/StudentAttendanceController.php',
    'StudentCalendarController.php' => 'app/Controllers/Lms/StudentCalendarController.php',
    'StudentController.php' => 'app/Controllers/Lms/StudentController.php',
    'StudentGradebookController.php' => 'app/Controllers/Lms/StudentGradebookController.php',
    'StudentQuizController.php' => 'app/Controllers/Lms/StudentQuizController.php',
];

echo "=== CONTROLLER COVERAGE ===\n";
$controllerCoverage = [];
foreach ($controllers as $name => $path) {
    $matches = [];
    foreach ($docContents as $docName => $text) {
        if (stripos($text, $name) !== false || stripos($text, str_replace('.php', '', $name)) !== false) {
            $matches[] = $docName;
        }
    }
    $controllerCoverage[$name] = $matches;
    echo sprintf("%-35s : %s\n", $name, count($matches) > 0 ? count($matches) . " docs (" . implode(', ', array_slice($matches, 0, 3)) . "...)" : "MISSING");
}

// 2. Models coverage
$models = [
    'ActivityLog.php', 'Announcement.php', 'Application.php', 'ApplicationDocument.php',
    'BaseModel.php', 'HealthRecord.php', 'Schedule.php', 'ScholarshipApplication.php',
    'StudentAssessment.php', 'User.php'
];

echo "\n=== MODEL COVERAGE ===\n";
foreach ($models as $m) {
    $matches = [];
    foreach ($docContents as $docName => $text) {
        if (stripos($text, $m) !== false || stripos($text, str_replace('.php', '', $m)) !== false) {
            $matches[] = $docName;
        }
    }
    echo sprintf("%-25s : %s\n", $m, count($matches) > 0 ? count($matches) . " docs" : "MISSING");
}

// 3. Services coverage
$services = [
    'AssessmentService.php', 'EnrollmentService.php', 'LmsAnnouncementService.php',
    'LmsAttendanceService.php', 'LmsCalendarService.php', 'LmsGradebookService.php',
    'LmsQuizService.php', 'LmsService.php', 'StudentNumberService.php'
];

echo "\n=== SERVICE COVERAGE ===\n";
foreach ($services as $s) {
    $matches = [];
    foreach ($docContents as $docName => $text) {
        if (stripos($text, $s) !== false || stripos($text, str_replace('.php', '', $s)) !== false) {
            $matches[] = $docName;
        }
    }
    echo sprintf("%-30s : %s\n", $s, count($matches) > 0 ? count($matches) . " docs (" . implode(', ', $matches) . ")" : "MISSING");
}

// 4. Ghost files search (files documented in obsidian that don't exist)
echo "\n=== GHOST FILE AUDIT (Documented in Obsidian but missing on disk) ===\n";
$potentialFiles = [];
foreach ($docContents as $docName => $text) {
    preg_match_all('/(?:`|\[|[\/\'\"])((?:app\/[a-zA-Z0-9_\/]+\.php)|(?:[a-zA-Z0-9_]+\.php))/', $text, $fileMatches);
    foreach ($fileMatches[1] as $pf) {
        $clean = trim($pf, "/'`[]");
        if (strpos($clean, '.php') !== false) {
            $potentialFiles[$clean][] = $docName;
        }
    }
}

$missingFiles = [];
foreach ($potentialFiles as $file => $docs) {
    $exists = false;
    if (file_exists(__DIR__ . '/../' . $file) || 
        file_exists(__DIR__ . '/../app/' . $file) || 
        file_exists(__DIR__ . '/../app/Views/' . $file) ||
        file_exists(__DIR__ . '/../app/Controllers/' . $file) ||
        file_exists(__DIR__ . '/../public/' . $file)) {
        $exists = true;
    }
    // check if it's just a view filename
    if (!$exists) {
        $vMatches = glob(__DIR__ . '/../app/Views/*/' . $file);
        $vMatches2 = glob(__DIR__ . '/../app/Views/*/*/' . $file);
        if (!empty($vMatches) || !empty($vMatches2)) $exists = true;
    }
    if (!$exists) {
        $missingFiles[$file] = array_unique($docs);
    }
}

foreach ($missingFiles as $f => $docs) {
    // filter out typical examples or standard php names
    if (in_array($f, ['functions.php', 'index.php', 'database.php', 'web.php'])) continue;
    echo sprintf("GHOST: %-35s referenced in: %s\n", $f, implode(', ', array_slice($docs, 0, 3)));
}

