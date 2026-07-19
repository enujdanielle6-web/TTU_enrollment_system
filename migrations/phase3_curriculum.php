<?php
/**
 * TTU Enrollment System - Migration: Phase 3 Curriculum & Subjects
 * 
 * Run this script via CLI: php migrations/phase3_curriculum.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

echo "Migrating Phase 3: Curriculum and Subjects...\n";

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subjects (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subject_code VARCHAR(50) NOT NULL UNIQUE,
            subject_name VARCHAR(255) NOT NULL,
            units INT NOT NULL DEFAULT 3,
            description TEXT DEFAULT NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " - subjects table created/verified.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS curriculum (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            program_id INT UNSIGNED NOT NULL,
            year_level VARCHAR(50) NOT NULL,
            semester VARCHAR(50) DEFAULT NULL,
            subject_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (program_id) REFERENCES academic_programs(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            UNIQUE KEY uq_curriculum_assignment (program_id, year_level, semester, subject_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " - curriculum table created/verified.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS enrollment_subjects (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL,
            subject_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            UNIQUE KEY uq_enrollment_subject (application_id, subject_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " - enrollment_subjects table created/verified.\n";

    echo "Phase 3 Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
