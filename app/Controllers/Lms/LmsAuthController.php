<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;

class LmsAuthController extends BaseController
{
    public function showFacultyLogin(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$pageTitle = 'Faculty LMS Login - Triple T University';

        return $this->render('auth/lms_faculty_login', get_defined_vars());
    }
    public function showStudentLogin(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$pageTitle = 'Student LMS Login - Triple T University';

        return $this->render('auth/lms_student_login', get_defined_vars());
    }
    public function loginProcess(Request $request, Response $response)
    {
        $pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response->redirect("/sia/auth/../public/index.php");
    return;
}

$role = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';

// $pdo is provided by config/database.php

if ($role === 'student') {
    $student_id = $_POST['student_id'] ?? '';
    if (empty($student_id) || empty($password)) {
        // Handle error (in reality we would use session errors and redirect back, simplified here)
        echo "<script>alert('Please provide student ID and password.'); window.location.href='lms_student_login.php';</script>";
        return;
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
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_first_name'] = $user['first_name'];
            $_SESSION['user_last_name'] = $user['last_name'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = 'student'; // Force student role for LMS perspective
            $_SESSION['user_department'] = $user['department'] ?? 'None';
            
            // Backward compatibility
            $_SESSION['lms_logged_in'] = true;
            $_SESSION['lms_user_id'] = $user['id'];
            $_SESSION['lms_role'] = 'student';
            $_SESSION['lms_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $response->redirect("/sia/auth/../lms/student/dashboard.php");
            return;
        } else {
            echo "<script>alert('You are not officially enrolled yet.'); window.location.href='lms_student_login.php';</script>";
            return;
        }
    } else {
        echo "<script>alert('Invalid Student ID or Password.'); window.location.href='lms_student_login.php';</script>";
        return;
    }
} elseif ($role === 'faculty') {
    $employee_id = $_POST['employee_id'] ?? '';
    if (empty($employee_id) || empty($password)) {
        echo "<script>alert('Please provide Employee ID and password.'); window.location.href='lms_faculty_login.php';</script>";
        return;
    }

    // Checking 'faculty' role, using student_number column as the employee_id storage
    $stmt = $pdo->prepare("SELECT * FROM users WHERE student_number = :eid AND role = 'faculty' AND is_active = 1");
    $stmt->execute(['eid' => $employee_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_first_name'] = $user['first_name'];
        $_SESSION['user_last_name'] = $user['last_name'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = 'faculty'; // Force faculty role for LMS perspective
        $_SESSION['user_department'] = $user['department'] ?? 'None';
        
        // Backward compatibility
        $_SESSION['lms_logged_in'] = true;
        $_SESSION['lms_user_id'] = $user['id'];
        $_SESSION['lms_role'] = 'faculty';
        $_SESSION['lms_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $response->redirect("/sia/auth/../lms/faculty/dashboard.php");
        return;
    } else {
        echo "<script>alert('Invalid Employee ID or Password.'); window.location.href='lms_faculty_login.php';</script>";
        return;
    }
}

// Fallback
$response->redirect("/sia/auth/../public/index.php");
return;
    }
}



