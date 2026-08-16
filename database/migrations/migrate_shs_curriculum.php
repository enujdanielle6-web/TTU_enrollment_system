<?php
require_once __DIR__ . '/../../app/Core/Database.php';

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Starting SHS Curriculum Migration...\n";

    $pdo->beginTransaction();

    // 1. Create shs_curricula table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `shs_curricula` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `strand_id` int(10) unsigned NOT NULL,
          `curriculum_name` varchar(255) NOT NULL,
          `version` varchar(50) NOT NULL DEFAULT '1.0',
          `effective_academic_year` varchar(20) DEFAULT NULL,
          `description` text DEFAULT NULL,
          `status` enum('active','inactive','draft') NOT NULL DEFAULT 'active',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `strand_id` (`strand_id`),
          CONSTRAINT `shs_curricula_ibfk_1` FOREIGN KEY (`strand_id`) REFERENCES `shs_strands` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "- Created shs_curricula table.\n";

    // 2. Add curriculum_id to shs_curriculum and rename it
    // First, add the column
    $pdo->exec("ALTER TABLE `shs_curriculum` ADD COLUMN `curriculum_id` int(10) unsigned NULL AFTER `id`");
    
    // 3. Migrate data: Create a default curriculum for each strand that has subjects
    $strands = $pdo->query("SELECT DISTINCT strand_id FROM shs_curriculum")->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtCreateCurriculum = $pdo->prepare("INSERT INTO shs_curricula (strand_id, curriculum_name, version, effective_academic_year, status) VALUES (?, 'Default Curriculum', '1.0', '2025-2026', 'active')");
    $stmtUpdateSubject = $pdo->prepare("UPDATE shs_curriculum SET curriculum_id = ? WHERE strand_id = ?");

    foreach ($strands as $strand) {
        $strandId = $strand['strand_id'];
        $stmtCreateCurriculum->execute([$strandId]);
        $curriculumId = $pdo->lastInsertId();
        
        $stmtUpdateSubject->execute([$curriculumId, $strandId]);
    }
    echo "- Migrated existing subjects into default Version 1.0 curricula.\n";

    // 4. Clean up shs_curriculum (remove strand_id, drop foreign keys, add foreign keys)
    // To drop foreign keys safely, we need to know their names. In schema.sql they were shs_curriculum_ibfk_1 and shs_curriculum_ibfk_2.
    // However, we only need to drop the strand_id foreign key and column.
    // Also the unique key uq_shs_curriculum needs to be dropped and recreated.
    
    // Drop foreign keys if they exist (ignoring errors if they don't)
    try { $pdo->exec("ALTER TABLE `shs_curriculum` DROP FOREIGN KEY `shs_curriculum_ibfk_1`"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE `shs_curriculum` DROP FOREIGN KEY `shs_curriculum_ibfk_2`"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE `shs_curriculum` DROP INDEX `uq_shs_curriculum`"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE `shs_curriculum` DROP INDEX `idx_strand_id`"); } catch (Exception $e) {}

    // Now alter table structure
    $pdo->exec("
        ALTER TABLE `shs_curriculum` 
        DROP COLUMN `strand_id`,
        MODIFY COLUMN `curriculum_id` int(10) unsigned NOT NULL,
        ADD UNIQUE KEY `uq_shs_curriculum_sub` (`curriculum_id`,`grade_level`,`semester`,`subject_id`),
        ADD KEY `idx_curriculum_id` (`curriculum_id`),
        ADD CONSTRAINT `shs_curriculum_subs_ibfk_1` FOREIGN KEY (`curriculum_id`) REFERENCES `shs_curricula` (`id`) ON DELETE CASCADE,
        ADD CONSTRAINT `shs_curriculum_subs_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
    ");

    $pdo->exec("RENAME TABLE `shs_curriculum` TO `shs_curriculum_subjects`");
    echo "- Renamed shs_curriculum to shs_curriculum_subjects and restructured.\n";

    // 5. Add curriculum_id to shs_sections
    $pdo->exec("ALTER TABLE `shs_sections` ADD COLUMN `curriculum_id` int(10) unsigned NULL AFTER `strand_id`");
    echo "- Added curriculum_id to shs_sections.\n";

    $pdo->commit();
    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
