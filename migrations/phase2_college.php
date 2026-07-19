<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

echo "Starting Phase 2 Migration (College Enrollment Features)...\n";

try {
    // 1. Add semester and nstp to applications table
    echo "Adding semester and nstp columns to applications...\n";
    $pdo->exec("
        ALTER TABLE applications
        ADD COLUMN IF NOT EXISTS semester ENUM('First', 'Second', 'Summer') DEFAULT NULL AFTER school_year,
        ADD COLUMN IF NOT EXISTS nstp ENUM('CWTS', 'ROTC', 'LTS') DEFAULT NULL AFTER student_type
    ");

    // 2. Add student_number to users table
    echo "Adding student_number column to users...\n";
    $pdo->exec("
        ALTER TABLE users
        ADD COLUMN IF NOT EXISTS student_number VARCHAR(50) DEFAULT NULL AFTER role
    ");
    
    // Add unique key safely
    try {
        $pdo->exec("ALTER TABLE users ADD UNIQUE KEY uq_users_student_number (student_number)");
    } catch (PDOException $e) {
        // Ignore if key already exists
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
    }

    echo "Phase 2 Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
