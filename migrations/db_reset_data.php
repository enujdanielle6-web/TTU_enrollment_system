<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    $tablesToTruncate = [
        'activity_logs',
        'announcements',
        'application_documents',
        'applications',
        'enrollment_subjects',
        'health_records',
        'login_attempts',
        'payment_records',
        'scholarship_applications',
        'student_assessments',
        'student_scholarships',
        'section_subjects',
        'sections',
        'curriculum',
        'subjects'
    ];

    foreach ($tablesToTruncate as $table) {
        $pdo->exec("TRUNCATE TABLE `$table`");
        echo "Truncated $table\n";
    }

    // Handle users (keep admin)
    $pdo->exec("DELETE FROM users WHERE role != 'admin'");
    // Auto increment reset for users
    $pdo->exec("ALTER TABLE users AUTO_INCREMENT = 2");
    echo "Cleared users table (kept admin)\n";

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "Database reset complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}
