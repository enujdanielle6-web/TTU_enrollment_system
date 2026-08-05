<?php
require_once __DIR__ . '/config/database.php';
// $pdo is now available globally from config/database.php

try {
    $pdo->beginTransaction();

    // 1. Insert User
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role, student_number, is_active) VALUES ('John', 'Student', 'john.student@example.com', :password, 'applicant', '2026-9999', 1)");
    $stmt->execute(['password' => $password]);
    $userId = $pdo->lastInsertId();

    // 2. Insert Application
    $stmt = $pdo->prepare("INSERT INTO applications (user_id, reference_number, status, document_submission_method, academic_level, grade_level, school_year, semester, strand) VALUES (:uid, 'SIA-TEST-999', 'enrolled', 'online', 'College', '1st Year', '2026-2027', 'First', 'BSIT')");
    $stmt->execute(['uid' => $userId]);
    $appId = $pdo->lastInsertId();

    // 3. Insert Enrollment (subject_id 4)
    $stmt = $pdo->prepare("INSERT INTO college_enrollments (application_id, subject_id, college_section_id) VALUES (:appid, 4, 1)");
    $stmt->execute(['appid' => $appId]);

    // 4. Insert a Test Faculty
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role, student_number, is_active) VALUES ('Prof.', 'Smith', 'prof.smith@ttu.edu.ph', :password, 'faculty', 'EMP-001', 1)");
    $stmt->execute(['password' => $password]);

    $pdo->commit();
    echo "Successfully inserted test student and test faculty!";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
