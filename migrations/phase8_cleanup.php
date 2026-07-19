<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "1. Dropping legacy columns from section_subjects...\n";
    try { $pdo->exec("ALTER TABLE section_subjects DROP COLUMN legacy_schedule_id"); } catch(Exception $e) { echo $e->getMessage() . "\n"; }
    try { $pdo->exec("ALTER TABLE section_subjects DROP COLUMN legacy_offering_id"); } catch(Exception $e) { echo $e->getMessage() . "\n"; }
    echo "Done.\n";
    
    echo "2. Dropping subject_offering_id from enrollment_subjects...\n";
    try { $pdo->exec("ALTER TABLE enrollment_subjects DROP FOREIGN KEY fk_enrollment_subject_offering"); } catch(Exception $e) { echo $e->getMessage() . "\n"; }
    try { $pdo->exec("ALTER TABLE enrollment_subjects DROP COLUMN subject_offering_id"); } catch(Exception $e) { echo $e->getMessage() . "\n"; }
    echo "Done.\n";

    echo "3. Dropping legacy tables...\n";
    $pdo->exec("DROP TABLE IF EXISTS subject_offerings");
    $pdo->exec("DROP TABLE IF EXISTS section_schedules");
    echo "Done.\n";
    
    // Cleanup duplicates in section_subjects
    echo "4. Removing duplicate schedules in section_subjects...\n";
    $pdo->exec("
        DELETE t1 FROM section_subjects t1
        INNER JOIN section_subjects t2 
        WHERE t1.id > t2.id 
          AND t1.section_id = t2.section_id 
          AND t1.subject_id = t2.subject_id
          AND t1.day = t2.day
          AND t1.start_time = t2.start_time
    ");
    echo "Done.\n";
    
    echo "Phase 8 Cleanup Completed Successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
