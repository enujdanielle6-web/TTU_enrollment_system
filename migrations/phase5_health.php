<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

try {
    echo "Starting Phase 5 Health Module Migration...\n";

    echo "Creating health_records table...\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS health_records (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            application_id INT UNSIGNED NOT NULL,
            height VARCHAR(50) DEFAULT NULL,
            weight VARCHAR(50) DEFAULT NULL,
            blood_type VARCHAR(10) DEFAULT NULL,
            has_allergies TINYINT(1) DEFAULT 0,
            has_asthma TINYINT(1) DEFAULT 0,
            has_diabetes TINYINT(1) DEFAULT 0,
            has_hypertension TINYINT(1) DEFAULT 0,
            has_heart_disease TINYINT(1) DEFAULT 0,
            has_physical_disability TINYINT(1) DEFAULT 0,
            has_existing_condition TINYINT(1) DEFAULT 0,
            has_previous_surgery TINYINT(1) DEFAULT 0,
            has_maintenance_medication TINYINT(1) DEFAULT 0,
            has_hospitalized TINYINT(1) DEFAULT 0,
            medical_conditions TEXT DEFAULT NULL,
            allergies_details TEXT DEFAULT NULL,
            current_medications TEXT DEFAULT NULL,
            other_notes TEXT DEFAULT NULL,
            emergency_name VARCHAR(100) DEFAULT NULL,
            emergency_relationship VARCHAR(100) DEFAULT NULL,
            emergency_contact VARCHAR(50) DEFAULT NULL,
            status ENUM('pending', 'under_review', 'verified', 'correction_required', 'rejected') NOT NULL DEFAULT 'pending',
            admin_remarks TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
