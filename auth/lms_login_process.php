<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../public/index.php");
    exit;
}

$role = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';

// $pdo is provided by config/database.php

if ($role === 'student') {
    $student_id = $_POST['student_id'] ?? '';
    if (empty($student_id) || empty($password)) {
        // Handle error (in reality we would use session errors and redirect back, simplified here)
        echo "<script>alert('Please provide student ID and password.'); window.location.href='lms_student_login.php';</script>";
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE student_number = :sid AND role = 'applicant' AND is_active = 1");
    $stmt->execute(['sid' => $student_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // For students, check if they are officially enrolled
        // We'll check if they have any college_enrollments via their application
        $enrStmt = $pdo->prepare("
            SELECT COUNT(*) FROM college_enrollments ce
            JOIN applications a ON ce.application_id = a.id
            WHERE a.user_id = :uid
        ");
        $enrStmt->execute(['uid' => $user['id']]);
        $enrolledCount = (int)$enrStmt->fetchColumn();

        if ($enrolledCount > 0) {
            // Success
            $_SESSION['lms_logged_in'] = true;
            $_SESSION['lms_user_id'] = $user['id'];
            $_SESSION['lms_role'] = 'student';
            $_SESSION['lms_name'] = $user['first_name'] . ' ' . $user['last_name'];
            header("Location: ../lms/student/dashboard.php");
            exit;
        } else {
            echo "<script>alert('You are not officially enrolled yet.'); window.location.href='lms_student_login.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Invalid Student ID or Password.'); window.location.href='lms_student_login.php';</script>";
        exit;
    }
} elseif ($role === 'faculty') {
    $employee_id = $_POST['employee_id'] ?? '';
    if (empty($employee_id) || empty($password)) {
        echo "<script>alert('Please provide Employee ID and password.'); window.location.href='lms_faculty_login.php';</script>";
        exit;
    }

    // Checking 'faculty' role, using student_number column as the employee_id storage
    $stmt = $pdo->prepare("SELECT * FROM users WHERE student_number = :eid AND role = 'faculty' AND is_active = 1");
    $stmt->execute(['eid' => $employee_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['lms_logged_in'] = true;
        $_SESSION['lms_user_id'] = $user['id'];
        $_SESSION['lms_role'] = 'faculty';
        $_SESSION['lms_name'] = $user['first_name'] . ' ' . $user['last_name'];
        header("Location: ../lms/faculty/dashboard.php");
        exit;
    } else {
        echo "<script>alert('Invalid Employee ID or Password.'); window.location.href='lms_faculty_login.php';</script>";
        exit;
    }
}

// Fallback
header("Location: ../public/index.php");
exit;
