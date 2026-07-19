<?php

declare(strict_types=1);

/**
 * Migration Script: Phase 1 Academic Expansion
 * 
 * Expands the system to support both Senior High School and College.
 */

require_once __DIR__ . '/../config/database.php';

echo "Starting Phase 1 Expansion Migration...\n<br><br>";

try {
    // 1. Rename academic_strands to academic_programs
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS academic_programs LIKE academic_strands;
        INSERT IGNORE INTO academic_programs SELECT * FROM academic_strands;
        DROP TABLE IF EXISTS academic_strands;
    ");
    echo "✅ Renamed academic_strands to academic_programs.\n<br>";

    // 2. Add category to academic_programs
    $pdo->exec("
        ALTER TABLE academic_programs 
        ADD COLUMN IF NOT EXISTS category ENUM('Senior High School', 'College') NOT NULL DEFAULT 'Senior High School' AFTER name;
    ");
    echo "✅ Added 'category' to academic_programs.\n<br>";

    // 3. Add academic_level to applications
    $pdo->exec("
        ALTER TABLE applications 
        ADD COLUMN IF NOT EXISTS academic_level ENUM('Senior High School', 'College') DEFAULT NULL AFTER document_submission_method;
    ");
    // Backfill existing applications to SHS
    $pdo->exec("UPDATE applications SET academic_level = 'Senior High School' WHERE academic_level IS NULL");
    echo "✅ Added 'academic_level' to applications.\n<br>";

    // 4. Add academic_level to fee_templates
    $pdo->exec("
        ALTER TABLE fee_templates 
        ADD COLUMN IF NOT EXISTS academic_level ENUM('Senior High School', 'College') DEFAULT NULL AFTER name;
    ");
    // Backfill existing fee_templates to SHS
    $pdo->exec("UPDATE fee_templates SET academic_level = 'Senior High School' WHERE academic_level IS NULL");
    echo "✅ Added 'academic_level' to fee_templates.\n<br><br>";

    echo "<h3>🎉 Migration Complete!</h3>";
} catch (PDOException $e) {
    echo "❌ <b>Database Error:</b> " . $e->getMessage() . "\n<br>";
} catch (Exception $e) {
    echo "❌ <b>Error:</b> " . $e->getMessage() . "\n<br>";
}
