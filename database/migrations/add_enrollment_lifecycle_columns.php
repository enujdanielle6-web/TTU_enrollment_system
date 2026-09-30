<?php
/**
 * Migration: Add lifecycle status ('enrolled', 'dropped', 'withdrawn') and dropped_at
 * to college_enrollments and shs_enrollments to support drop/withdraw propagation
 * without destroying historical LMS records.
 */
require_once __DIR__ . '/../../app/Core/Database.php';

$pdo = App\Core\Database::getConnection();

echo "Starting migration for enrollment lifecycle columns...\n";

// 1. college_enrollments
$cols = $pdo->query("SHOW COLUMNS FROM college_enrollments LIKE 'status'")->fetchAll();
if (empty($cols)) {
    echo "Adding status and dropped_at to college_enrollments...\n";
    $pdo->exec("
        ALTER TABLE college_enrollments
        ADD COLUMN status ENUM('enrolled', 'dropped', 'withdrawn') NOT NULL DEFAULT 'enrolled' AFTER college_section_id,
        ADD COLUMN dropped_at TIMESTAMP NULL DEFAULT NULL AFTER status,
        ADD INDEX idx_ce_status_section (college_section_id, subject_id, status)
    ");
    echo "college_enrollments updated successfully.\n";
} else {
    echo "Columns already exist in college_enrollments.\n";
}

// 2. shs_enrollments
$cols = $pdo->query("SHOW COLUMNS FROM shs_enrollments LIKE 'status'")->fetchAll();
if (empty($cols)) {
    echo "Adding status and dropped_at to shs_enrollments...\n";
    $pdo->exec("
        ALTER TABLE shs_enrollments
        ADD COLUMN status ENUM('enrolled', 'dropped', 'withdrawn') NOT NULL DEFAULT 'enrolled' AFTER shs_section_id,
        ADD COLUMN dropped_at TIMESTAMP NULL DEFAULT NULL AFTER status,
        ADD INDEX idx_se_status_section (shs_section_id, subject_id, status)
    ");
    echo "shs_enrollments updated successfully.\n";
} else {
    echo "Columns already exist in shs_enrollments.\n";
}

echo "Migration completed successfully.\n";
