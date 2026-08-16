<?php
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = $base_dir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require $file;
});

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    
    // 1. ALTER scholarships table
    $sql = "
    ALTER TABLE `scholarships`
        ADD COLUMN `code` VARCHAR(50) NULL AFTER `name`,
        ADD COLUMN `category` ENUM('School-Based', 'Government', 'Department-Based', 'Private', 'Special') NOT NULL DEFAULT 'School-Based' AFTER `code`,
        ADD COLUMN `provider` VARCHAR(255) NULL AFTER `category`,
        ADD COLUMN `department_id` INT(10) UNSIGNED NULL AFTER `provider`,
        ADD COLUMN `program_id` INT(10) UNSIGNED NULL AFTER `department_id`,
        ADD COLUMN `year_level` VARCHAR(50) NULL AFTER `program_id`,
        ADD COLUMN `min_gwa` DECIMAL(4,2) NULL AFTER `year_level`,
        ADD COLUMN `income_requirement` DECIMAL(12,2) NULL AFTER `min_gwa`,
        ADD COLUMN `slots` INT NULL AFTER `income_requirement`,
        ADD COLUMN `tuition_coverage_type` ENUM('full', 'percentage', 'fixed') NOT NULL DEFAULT 'fixed' AFTER `slots`,
        ADD COLUMN `tuition_coverage_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `tuition_coverage_type`,
        ADD COLUMN `misc_coverage_type` ENUM('full', 'percentage', 'fixed') NOT NULL DEFAULT 'fixed' AFTER `tuition_coverage_value`,
        ADD COLUMN `misc_coverage_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `misc_coverage_type`,
        ADD COLUMN `stipend_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `misc_coverage_value`,
        ADD COLUMN `book_allowance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `stipend_amount`,
        ADD COLUMN `required_documents` TEXT NULL AFTER `requirements`,
        ADD COLUMN `application_start` DATE NULL AFTER `required_documents`,
        ADD COLUMN `application_end` DATE NULL AFTER `application_start`,
        ADD COLUMN `status` ENUM('Draft', 'Active', 'Closed', 'Suspended') NOT NULL DEFAULT 'Draft' AFTER `application_end`;
    ";
    
    // execute safely - wrap in try-catch in case it's already run
    try {
        $pdo->exec($sql);
        echo "Successfully added new columns to scholarships.\n";
        
        // Migrate existing discount to tuition coverage
        $pdo->exec("UPDATE scholarships SET tuition_coverage_type = IF(discount_type='percentage', 'percentage', 'fixed'), tuition_coverage_value = discount_value, status = IF(is_active=1, 'Active', 'Suspended')");
        
        // Make code unique (set temporary codes first)
        $pdo->exec("UPDATE scholarships SET code = CONCAT('SCHOLAR-', id) WHERE code IS NULL");
        $pdo->exec("ALTER TABLE scholarships MODIFY `code` VARCHAR(50) NOT NULL UNIQUE");
        
        // Drop old columns
        $pdo->exec("ALTER TABLE scholarships DROP COLUMN discount_type, DROP COLUMN discount_value, DROP COLUMN is_active");
        
        echo "Successfully migrated old scholarship data.\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42S21') {
            echo "Columns already exist in scholarships.\n";
        } else {
            throw $e;
        }
    }
    
    // 2. ALTER scholarship_applications
    try {
        $pdo->exec("
            ALTER TABLE `scholarship_applications`
            ADD COLUMN `academic_year_id` INT(10) UNSIGNED NULL AFTER `scholarship_id`,
            ADD COLUMN `semester` VARCHAR(50) NULL AFTER `academic_year_id`,
            ADD COLUMN `submitted_documents` TEXT NULL AFTER `admin_feedback`;
        ");
        echo "Successfully altered scholarship_applications.\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42S21') {
            echo "Columns already exist in scholarship_applications.\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Create scholarship_recipients table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `scholarship_recipients` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT(10) UNSIGNED NOT NULL,
            `scholarship_id` INT(10) UNSIGNED NOT NULL,
            `academic_year_id` INT(10) UNSIGNED NOT NULL,
            `semester` VARCHAR(50) NOT NULL,
            `status` ENUM('Active', 'Suspended', 'Terminated', 'Renewed') NOT NULL DEFAULT 'Active',
            `remarks` TEXT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `scholarship_id` (`scholarship_id`),
            CONSTRAINT `scholarship_recipients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `scholarship_recipients_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Successfully created scholarship_recipients table.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
