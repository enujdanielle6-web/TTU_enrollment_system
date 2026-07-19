<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

try {
    echo "Starting database migration for College Subject Offerings...\n";

    // 1. Create subject_offerings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subject_offerings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subject_id INT UNSIGNED NOT NULL,
            section_id INT UNSIGNED NOT NULL,
            instructor VARCHAR(255) DEFAULT NULL,
            room VARCHAR(100) DEFAULT NULL,
            building VARCHAR(100) DEFAULT NULL,
            day VARCHAR(20) NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            delivery_mode ENUM('Face-to-Face', 'Online', 'Hybrid') NOT NULL DEFAULT 'Face-to-Face',
            capacity INT NOT NULL DEFAULT 40,
            semester VARCHAR(50) DEFAULT NULL,
            school_year VARCHAR(50) DEFAULT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table 'subject_offerings' created/verified.\n";

    // 2. Add subject_offering_id to enrollment_subjects
    // Check if column exists first
    $stmt = $pdo->query("SHOW COLUMNS FROM enrollment_subjects LIKE 'subject_offering_id'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("
            ALTER TABLE enrollment_subjects 
            ADD COLUMN subject_offering_id INT UNSIGNED NULL AFTER subject_id,
            ADD CONSTRAINT fk_enrollment_subject_offering FOREIGN KEY (subject_offering_id) REFERENCES subject_offerings(id) ON DELETE SET NULL
        ");
        echo "Column 'subject_offering_id' added to enrollment_subjects.\n";
    } else {
        echo "Column 'subject_offering_id' already exists.\n";
    }

    // 3. Migrate existing College schedules from section_schedules
    // Join section -> program to identify College schedules
    $collegeScheds = $pdo->query("
        SELECT ss.* 
        FROM section_schedules ss
        INNER JOIN sections sec ON ss.section_id = sec.id
        INNER JOIN academic_programs ap ON sec.program_id = ap.id
        WHERE ap.category = 'College'
    ")->fetchAll();

    if (!empty($collegeScheds)) {
        echo "Found " . count($collegeScheds) . " existing College schedules to migrate.\n";
        
        $insStmt = $pdo->prepare("
            INSERT INTO subject_offerings 
            (subject_id, section_id, instructor, room, day, start_time, end_time, capacity, created_at, updated_at)
            VALUES (:sub, :sec, :inst, :room, :day, :st, :et, :cap, :ca, :ua)
        ");
        
        $migratedCount = 0;
        foreach ($collegeScheds as $cs) {
            // Check if already exists to avoid duplicates if run multiple times
            $check = $pdo->prepare("SELECT id FROM subject_offerings WHERE subject_id = ? AND section_id = ? AND day = ? AND start_time = ? AND end_time = ?");
            $check->execute([$cs['subject_id'], $cs['section_id'], $cs['day'], $cs['start_time'], $cs['end_time']]);
            
            if ($check->rowCount() === 0) {
                // Fetch section capacity to set a default capacity for the offering
                $secCap = $pdo->prepare("SELECT capacity FROM sections WHERE id = ?");
                $secCap->execute([$cs['section_id']]);
                $capacity = $secCap->fetchColumn() ?: 40;
                
                $insStmt->execute([
                    'sub' => $cs['subject_id'],
                    'sec' => $cs['section_id'],
                    'inst' => $cs['instructor'],
                    'room' => $cs['room'],
                    'day' => $cs['day'],
                    'st' => $cs['start_time'],
                    'et' => $cs['end_time'],
                    'cap' => $capacity,
                    'ca' => $cs['created_at'],
                    'ua' => $cs['updated_at']
                ]);
                $migratedCount++;
            }
        }
        echo "Migrated $migratedCount schedules.\n";
        
        // Optional: Remove them from section_schedules to clean up
        $pdo->exec("
            DELETE ss FROM section_schedules ss
            INNER JOIN sections sec ON ss.section_id = sec.id
            INNER JOIN academic_programs ap ON sec.program_id = ap.id
            WHERE ap.category = 'College'
        ");
        echo "Cleaned up old College schedules from section_schedules.\n";
        
    } else {
        echo "No existing College schedules found in section_schedules.\n";
    }

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
}
