<?php
require_once __DIR__ . '/../../app/Core/Database.php';

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Starting LMS Foundation Migration...\n";

    // 1. Create lms_courses proxy table
    echo "Creating lms_courses table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `lms_courses` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `academic_level` enum('College','SHS') NOT NULL,
            `academic_section_id` int(10) unsigned NOT NULL COMMENT 'Maps to college_sections.id or shs_sections.id logically',
            `subject_id` int(10) unsigned NOT NULL,
            `faculty_user_id` int(10) unsigned NOT NULL,
            `status` enum('active','archived') NOT NULL DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_lms_course_subject` (`subject_id`),
            KEY `idx_lms_course_faculty` (`faculty_user_id`),
            CONSTRAINT `fk_lms_course_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_lms_course_faculty` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Clear existing lms_modules/lms_assignments to avoid FK constraint errors during schema change
    echo "Clearing existing lms_modules and lms_assignments to apply new schema...\n";
    // Disable FK checks temporarily for the wipe
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE `lms_submissions`;");
    $pdo->exec("TRUNCATE TABLE `lms_assignments`;");
    $pdo->exec("TRUNCATE TABLE `lms_modules`;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 3. Alter lms_modules
    echo "Altering lms_modules schema...\n";
    // Check if columns exist before altering to allow re-runs
    try {
        $pdo->exec("ALTER TABLE `lms_modules` DROP FOREIGN KEY `fk_lms_mod_teacher`");
        $pdo->exec("ALTER TABLE `lms_modules` DROP FOREIGN KEY `fk_lms_mod_subject`");
        $pdo->exec("ALTER TABLE `lms_modules` DROP COLUMN `teacher_id`");
        $pdo->exec("ALTER TABLE `lms_modules` DROP COLUMN `subject_id`");
        $pdo->exec("ALTER TABLE `lms_modules` ADD COLUMN `lms_course_id` int(10) unsigned NOT NULL AFTER `id`");
        $pdo->exec("ALTER TABLE `lms_modules` ADD COLUMN `display_order` int(11) NOT NULL DEFAULT 0 AFTER `description`");
        $pdo->exec("ALTER TABLE `lms_modules` DROP COLUMN `file_path`");
        $pdo->exec("ALTER TABLE `lms_modules` ADD CONSTRAINT `fk_lms_mod_course` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE");
    } catch(Exception $e) { echo "lms_modules alter skipped or failed: " . $e->getMessage() . "\n"; }

    // 4. Create lms_materials
    echo "Creating lms_materials table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `lms_materials` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `lms_module_id` int(10) unsigned NOT NULL,
            `file_name` varchar(255) NOT NULL,
            `file_path` varchar(255) NOT NULL,
            `mime_type` varchar(100) DEFAULT NULL,
            `file_size` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `fk_lms_mat_module` (`lms_module_id`),
            CONSTRAINT `fk_lms_mat_module` FOREIGN KEY (`lms_module_id`) REFERENCES `lms_modules` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 5. Alter lms_assignments
    echo "Altering lms_assignments schema...\n";
    try {
        $pdo->exec("ALTER TABLE `lms_assignments` DROP FOREIGN KEY `fk_lms_ass_teacher`");
        $pdo->exec("ALTER TABLE `lms_assignments` DROP FOREIGN KEY `fk_lms_ass_subject`");
        $pdo->exec("ALTER TABLE `lms_assignments` DROP COLUMN `teacher_id`");
        $pdo->exec("ALTER TABLE `lms_assignments` DROP COLUMN `subject_id`");
        $pdo->exec("ALTER TABLE `lms_assignments` ADD COLUMN `lms_course_id` int(10) unsigned NOT NULL AFTER `id`");
        $pdo->exec("ALTER TABLE `lms_assignments` ADD COLUMN `lms_module_id` int(10) unsigned DEFAULT NULL AFTER `lms_course_id`");
        $pdo->exec("ALTER TABLE `lms_assignments` ADD COLUMN `max_score` int(11) NOT NULL DEFAULT 100 AFTER `due_date`");
        $pdo->exec("ALTER TABLE `lms_assignments` ADD CONSTRAINT `fk_lms_ass_course` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE");
        $pdo->exec("ALTER TABLE `lms_assignments` ADD CONSTRAINT `fk_lms_ass_module` FOREIGN KEY (`lms_module_id`) REFERENCES `lms_modules` (`id`) ON DELETE CASCADE");
    } catch(Exception $e) { echo "lms_assignments alter skipped or failed: " . $e->getMessage() . "\n"; }

    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
