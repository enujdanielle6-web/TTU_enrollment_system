<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo->beginTransaction();

    // 1. Create lms_topics
    $pdo->exec("CREATE TABLE IF NOT EXISTS lms_topics (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        subject_id INT UNSIGNED NOT NULL,
        section_id INT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (section_id) REFERENCES college_sections(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 2. Create lms_materials
    $pdo->exec("CREATE TABLE IF NOT EXISTS lms_materials (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        topic_id INT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        material_type ENUM('pdf', 'ppt', 'video', 'audio', 'link', 'document') NOT NULL,
        file_path VARCHAR(255),
        external_link VARCHAR(500),
        estimated_time_minutes INT DEFAULT 0,
        prerequisite_material_id INT UNSIGNED NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (topic_id) REFERENCES lms_topics(id) ON DELETE CASCADE,
        FOREIGN KEY (prerequisite_material_id) REFERENCES lms_materials(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 3. Create lms_student_materials
    $pdo->exec("CREATE TABLE IF NOT EXISTS lms_student_materials (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        student_id INT UNSIGNED NOT NULL,
        material_id INT UNSIGNED NOT NULL,
        is_completed BOOLEAN DEFAULT 0,
        is_bookmarked BOOLEAN DEFAULT 0,
        last_viewed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY student_material_unique (student_id, material_id),
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (material_id) REFERENCES lms_materials(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Assign faculty to a section
    // Find Introduction to Computing subject
    $stmt = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = 'CC101' LIMIT 1");
    $stmt->execute();
    $subjectId = $stmt->fetchColumn();
    
    // Find a section
    $stmt = $pdo->prepare("SELECT id FROM college_sections LIMIT 1");
    $stmt->execute();
    $sectionId = $stmt->fetchColumn();
    
    // Find faculty EMP-001
    $stmt = $pdo->prepare("SELECT id FROM users WHERE student_number = 'EMP-001' LIMIT 1");
    $stmt->execute();
    $facultyId = $stmt->fetchColumn();
    
    if ($subjectId && $sectionId && $facultyId) {
        // We will assign this faculty to the section_subjects table, which is the schedule mapping
        // Oh wait, college_sections does not link directly to faculty.
        // It's section_subjects that links sections, subjects, and instructor.
        // Wait, what's the name of the section_subjects table?
        // Let's check if there's a section_subjects table.
        // I will just print if we have it.
        echo "Will assign later after checking section_subjects schema.\n";
    }

    $pdo->commit();
    echo "LMS Material tables created successfully!\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
