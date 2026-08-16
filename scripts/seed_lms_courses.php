<?php
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    
    // Get a default user ID
    $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $facultyId = $stmt->fetchColumn();
    
    if (!$facultyId) {
        echo "No user found. Cannot seed.\n";
        exit(1);
    }
    
    echo "Seeding College LMS Courses...\n";
    $college_stmt = $pdo->prepare("
        SELECT css.id, 'College' as academic_level, cs.id as section_id, s.id as subject_id
        FROM college_section_subjects css
        JOIN college_sections cs ON css.college_section_id = cs.id
        JOIN subjects s ON css.subject_id = s.id
        LEFT JOIN lms_courses lc ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id AND lc.subject_id = s.id
        WHERE lc.id IS NULL
    ");
    $college_stmt->execute();
    $college_courses = $college_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $insert = $pdo->prepare("INSERT INTO lms_courses (academic_level, academic_section_id, subject_id, faculty_user_id, status) VALUES (:lvl, :sec, :sub, :fac, 'active')");
    
    $count = 0;
    foreach ($college_courses as $c) {
        $insert->execute([
            'lvl' => $c['academic_level'],
            'sec' => $c['section_id'],
            'sub' => $c['subject_id'],
            'fac' => $facultyId
        ]);
        $count++;
    }
    
    echo "Seeded $count College courses.\n";
    
    echo "Seeding SHS LMS Courses...\n";
    $shs_stmt = $pdo->prepare("
        SELECT sss.id, 'SHS' as academic_level, ss.id as section_id, s.id as subject_id
        FROM shs_section_subjects sss
        JOIN shs_sections ss ON sss.shs_section_id = ss.id
        JOIN subjects s ON sss.subject_id = s.id
        LEFT JOIN lms_courses lc ON lc.academic_level = 'SHS' AND lc.academic_section_id = ss.id AND lc.subject_id = s.id
        WHERE lc.id IS NULL
    ");
    $shs_stmt->execute();
    $shs_courses = $shs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count2 = 0;
    foreach ($shs_courses as $c) {
        $insert->execute([
            'lvl' => $c['academic_level'],
            'sec' => $c['section_id'],
            'sub' => $c['subject_id'],
            'fac' => $facultyId
        ]);
        $count2++;
    }
    
    echo "Seeded $count2 SHS courses.\n";
    echo "Done.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
