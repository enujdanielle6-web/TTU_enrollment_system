<?php
/**
 * CLI Migration & Provisioning Script: provision_lms_courses.php
 * Provisions LMS course shells for all existing timetable section subjects that lack one.
 */
require_once __DIR__ . '/../app/Core/Database.php';

$pdo = App\Core\Database::getConnection();

echo "Starting LMS Course Shell Provisioning...\n";

// 1. College section subjects
$stmt = $pdo->query("
    SELECT css.id, 'College' as academic_level, css.college_section_id as section_id, css.subject_id, css.faculty_user_id
    FROM college_section_subjects css
    LEFT JOIN lms_courses lc ON lc.academic_level = 'College' 
        AND lc.academic_section_id = css.college_section_id 
        AND lc.subject_id = css.subject_id
    WHERE lc.id IS NULL
");
$unmappedCollege = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. SHS section subjects
$stmtShs = $pdo->query("
    SELECT sss.id, 'SHS' as academic_level, sss.shs_section_id as section_id, sss.subject_id, sss.faculty_user_id
    FROM shs_section_subjects sss
    LEFT JOIN lms_courses lc ON lc.academic_level = 'SHS' 
        AND lc.academic_section_id = sss.shs_section_id 
        AND lc.subject_id = sss.subject_id
    WHERE lc.id IS NULL
");
$unmappedShs = $stmtShs->fetchAll(PDO::FETCH_ASSOC);

$totalToProvision = count($unmappedCollege) + count($unmappedShs);
echo "Found {$totalToProvision} unmapped section subjects (College: " . count($unmappedCollege) . ", SHS: " . count($unmappedShs) . ")\n";

$insStmt = $pdo->prepare("
    INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status)
    VALUES (:lvl, :sec, :sub, :fac, 'active')
    ON DUPLICATE KEY UPDATE 
        faculty_user_id = IF(VALUES(faculty_user_id) IS NOT NULL, VALUES(faculty_user_id), faculty_user_id),
        status = 'active'
");

$provisioned = 0;
foreach (array_merge($unmappedCollege, $unmappedShs) as $row) {
    $insStmt->execute([
        'lvl' => $row['academic_level'],
        'sec' => $row['section_id'],
        'sub' => $row['subject_id'],
        'fac' => !empty($row['faculty_user_id']) ? (int)$row['faculty_user_id'] : null
    ]);
    $provisioned++;
}

echo "Successfully provisioned {$provisioned} LMS course shells.\n";
