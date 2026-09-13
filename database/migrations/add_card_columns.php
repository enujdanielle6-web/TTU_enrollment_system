<?php
/**
 * Migration: Add landing card customization columns to shs_strands and college_programs
 */
require_once __DIR__ . '/../../app/Core/Database.php';

$pdo = App\Core\Database::getConnection();

echo "Starting migration for card customization columns...\n";

// 1. shs_strands
$cols = $pdo->query("SHOW COLUMNS FROM shs_strands LIKE 'icon'")->fetchAll();
if (empty($cols)) {
    echo "Adding icon, careers, custom_tuition to shs_strands...\n";
    $pdo->exec("
        ALTER TABLE shs_strands
        ADD COLUMN icon VARCHAR(100) NULL DEFAULT 'bi-mortarboard' AFTER description,
        ADD COLUMN careers TEXT NULL DEFAULT NULL AFTER icon,
        ADD COLUMN custom_tuition VARCHAR(100) NULL DEFAULT NULL AFTER careers
    ");
} else {
    echo "Columns already exist in shs_strands.\n";
}

// 2. college_programs
$cols = $pdo->query("SHOW COLUMNS FROM college_programs LIKE 'icon'")->fetchAll();
if (empty($cols)) {
    echo "Adding icon, careers, custom_tuition to college_programs...\n";
    $pdo->exec("
        ALTER TABLE college_programs
        ADD COLUMN icon VARCHAR(100) NULL DEFAULT 'bi-mortarboard' AFTER description,
        ADD COLUMN careers TEXT NULL DEFAULT NULL AFTER icon,
        ADD COLUMN custom_tuition VARCHAR(100) NULL DEFAULT NULL AFTER careers
    ");
} else {
    echo "Columns already exist in college_programs.\n";
}

// Backfill initial data for strands
$pdo->exec("
    UPDATE shs_strands SET 
        icon = 'bi-calculator', 
        careers = 'Engineer, Programmer, Architect', 
        custom_tuition = '₱16,500 / sem' 
    WHERE code = 'STEM' AND (icon IS NULL OR icon = '' OR icon = 'bi-mortarboard');
");

$pdo->exec("
    UPDATE shs_strands SET 
        icon = 'bi-briefcase', 
        careers = 'Accountant, Entrepreneur, Manager', 
        custom_tuition = '₱15,000 - ₱20,000 / sem' 
    WHERE code = 'ABM' AND (icon IS NULL OR icon = '' OR icon = 'bi-mortarboard');
");

$pdo->exec("
    UPDATE shs_strands SET 
        icon = 'bi-chat-square-quote', 
        careers = 'Lawyer, Teacher, Psychologist', 
        custom_tuition = '₱15,000 - ₱20,000 / sem' 
    WHERE code = 'HUMSS' AND (icon IS NULL OR icon = '' OR icon = 'bi-mortarboard');
");

$pdo->exec("
    UPDATE shs_strands SET 
        icon = 'bi-laptop', 
        careers = 'Technician, Web Developer, IT Support', 
        custom_tuition = '₱15,000 / sem' 
    WHERE code = 'TVL-ICT' AND (icon IS NULL OR icon = '' OR icon = 'bi-mortarboard');
");

// Backfill initial data for college programs
$pdo->exec("
    UPDATE college_programs SET 
        icon = 'bi-pc-display', 
        careers = 'Software Engineer, IT Analyst, System Admin', 
        custom_tuition = '₱500 / unit (Est. ₱6,000 / sem)' 
    WHERE code = 'BSIT' AND (icon IS NULL OR icon = '' OR icon = 'bi-mortarboard');
");

$pdo->exec("
    UPDATE college_programs SET 
        icon = 'bi-laptop', 
        careers = 'Data Scientist, Systems Architect, AI Researcher', 
        custom_tuition = '₱500 / unit (Est. ₱6,000 / sem)' 
    WHERE code = 'BSCS' AND (icon IS NULL OR icon = '' OR icon = 'bi-mortarboard');
");

$pdo->exec("
    UPDATE college_programs SET 
        icon = 'bi-diagram-3', 
        careers = 'Systems Analyst, ERP Consultant, IT Manager', 
        custom_tuition = '₱25,000 - ₱30,000 / sem' 
    WHERE code = 'BSIS' AND (icon IS NULL OR icon = '' OR icon = 'bi-mortarboard');
");

$pdo->exec("
    UPDATE college_programs SET 
        icon = 'bi-cup-hot', 
        careers = 'Hotel Manager, F&B Director, Event Coordinator', 
        custom_tuition = '₱25,000 - ₱30,000 / sem' 
    WHERE code = 'BSHM' AND (icon IS NULL OR icon = '' OR icon = 'bi-mortarboard');
");

echo "Migration and data backfill completed successfully!\n";
