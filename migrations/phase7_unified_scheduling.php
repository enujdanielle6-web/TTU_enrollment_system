<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

echo "Starting Phase 7 Migration (Unified Scheduling Architecture)...\n";

try {

    // 1. Create unified section_subjects table
    echo "1. Creating section_subjects table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS section_subjects (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            section_id INT UNSIGNED NOT NULL,
            subject_id INT UNSIGNED NOT NULL,
            instructor VARCHAR(255) DEFAULT NULL,
            room VARCHAR(100) DEFAULT NULL,
            building VARCHAR(100) DEFAULT NULL,
            day VARCHAR(20) NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            delivery_mode ENUM('Face-to-Face', 'Online', 'Hybrid') NOT NULL DEFAULT 'Face-to-Face',
            capacity INT NOT NULL DEFAULT 40,
            status TINYINT(1) NOT NULL DEFAULT 1,
            legacy_schedule_id INT UNSIGNED NULL, -- Temporary mapping for SHS
            legacy_offering_id INT UNSIGNED NULL, -- Temporary mapping for College
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Alter enrollment_subjects to add section_subject_id
    echo "2. Adding section_subject_id to enrollment_subjects...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM enrollment_subjects LIKE 'section_subject_id'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("
            ALTER TABLE enrollment_subjects
            ADD COLUMN section_subject_id INT UNSIGNED NULL AFTER subject_offering_id,
            ADD CONSTRAINT fk_enrollment_section_subject 
                FOREIGN KEY (section_subject_id) REFERENCES section_subjects(id) ON DELETE SET NULL
        ");
    } else {
        echo "   -> Column section_subject_id already exists.\n";
    }

    // 3. Migrate SHS schedules to section_subjects
    echo "3. Migrating SHS schedules (section_schedules)... \n";
    $affected = $pdo->exec("
        INSERT INTO section_subjects (
            section_id, subject_id, instructor, room, day, 
            start_time, end_time, capacity, legacy_schedule_id, 
            created_at, updated_at
        )
        SELECT 
            ss.section_id, ss.subject_id, ss.instructor, ss.room, ss.day, 
            ss.start_time, ss.end_time, s.capacity, ss.id, 
            ss.created_at, ss.updated_at
        FROM section_schedules ss
        JOIN sections s ON ss.section_id = s.id
        WHERE NOT EXISTS (
            SELECT 1 FROM section_subjects x WHERE x.legacy_schedule_id = ss.id
        )
    ");
    echo "   -> Migrated $affected SHS schedules.\n";

    // 4. Migrate College schedules to section_subjects
    echo "4. Migrating College schedules (subject_offerings)... \n";
    // Check if subject_offerings table exists before migrating
    $stmt = $pdo->query("SHOW TABLES LIKE 'subject_offerings'");
    if ($stmt->rowCount() > 0) {
        $affected = $pdo->exec("
            INSERT INTO section_subjects (
                section_id, subject_id, instructor, room, building, day, 
                start_time, end_time, delivery_mode, capacity, status, 
                legacy_offering_id, created_at, updated_at
            )
            SELECT 
                so.section_id, so.subject_id, so.instructor, so.room, so.building, so.day, 
                so.start_time, so.end_time, so.delivery_mode, so.capacity, so.status, 
                so.id, so.created_at, so.updated_at
            FROM subject_offerings so
            WHERE NOT EXISTS (
                SELECT 1 FROM section_subjects x WHERE x.legacy_offering_id = so.id
            )
        ");
        echo "   -> Migrated $affected College schedules.\n";
    } else {
        echo "   -> subject_offerings table does not exist. Skipping.\n";
    }

    // 5. Update enrollment_subjects for SHS students
    echo "5. Linking existing SHS enrollments to section_subjects...\n";
    $affected = $pdo->exec("
        UPDATE enrollment_subjects es
        JOIN section_subjects ss 
            ON es.section_id = ss.section_id 
            AND es.subject_id = ss.subject_id
        SET es.section_subject_id = ss.id
        WHERE es.section_id IS NOT NULL 
          AND es.section_subject_id IS NULL
    ");
    echo "   -> Linked $affected SHS enrollment subject records.\n";

    // 6. Update enrollment_subjects for College students
    echo "6. Linking existing College enrollments to section_subjects...\n";
    if ($stmt->rowCount() > 0) {
        $affected = $pdo->exec("
            UPDATE enrollment_subjects es
            JOIN section_subjects ss 
                ON es.subject_offering_id = ss.legacy_offering_id
            SET es.section_subject_id = ss.id
            WHERE es.subject_offering_id IS NOT NULL 
              AND es.section_subject_id IS NULL
        ");
        echo "   -> Linked $affected College enrollment subject records.\n";
    }

    echo "Phase 7 Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
