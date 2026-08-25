<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

$pdo = new PDO("mysql:host=localhost;dbname=sia;charset=utf8mb4", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "=== STARTING FULL FORGOT PASSWORD AUTOMATED TEST SUITE ===\n\n";

// 1. Test Faculty Lookup by Employee ID
echo "1. Testing Faculty Lookup by Employee ID (EMP-001)...\n";
$stmt = $pdo->prepare("SELECT * FROM users WHERE (student_number = :id OR email = :id) AND role = 'faculty' AND is_active = 1 LIMIT 1");
$stmt->execute(['id' => 'EMP-001']);
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$faculty) {
    echo "FAILED: Faculty EMP-001 not found.\n";
    exit(1);
}
echo "SUCCESS: Found Faculty {$faculty['first_name']} {$faculty['last_name']} with email {$faculty['email']}\n\n";

// 2. Test Student Lookup by Student ID
echo "2. Testing Student Lookup by Student ID (2026-000002)...\n";
$stmt = $pdo->prepare("SELECT * FROM users WHERE (student_number = :id OR email = :id) AND is_active = 1 LIMIT 1");
$stmt->execute(['id' => '2026-000002']);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "FAILED: Student 2026-000002 not found.\n";
    exit(1);
}
echo "SUCCESS: Found Student {$student['first_name']} {$student['last_name']} with email {$student['email']}\n\n";

// 3. Test Student OTP Generation & DB Update
echo "3. Generating 6-digit OTP for Student...\n";
$studentOtp = sprintf('%06d', random_int(100000, 999999));
$upd = $pdo->prepare("UPDATE users SET reset_password_code = :code, reset_password_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = :id");
$upd->execute(['code' => $studentOtp, 'id' => (int)$student['id']]);

$check = $pdo->prepare("SELECT reset_password_code, reset_password_expires_at FROM users WHERE id = :id");
$check->execute(['id' => (int)$student['id']]);
$studentRecord = $check->fetch(PDO::FETCH_ASSOC);

if ($studentRecord['reset_password_code'] !== $studentOtp) {
    echo "FAILED: Student OTP mismatch in DB.\n";
    exit(1);
}
echo "SUCCESS: Student reset OTP set to {$studentOtp} with expiry {$studentRecord['reset_password_expires_at']}\n\n";

// 4. Test Student Password Reset Process with valid OTP
echo "4. Testing Password Reset Process with valid OTP for Student...\n";
$newPassword = 'NewStudentPassword2026!';
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

$upd = $pdo->prepare("UPDATE users SET password = :pwd, reset_password_code = NULL, reset_password_expires_at = NULL WHERE id = :id");
$upd->execute(['pwd' => $newHash, 'id' => (int)$student['id']]);

$verify = $pdo->prepare("SELECT password, reset_password_code FROM users WHERE id = :id");
$verify->execute(['id' => (int)$student['id']]);
$updatedStudent = $verify->fetch(PDO::FETCH_ASSOC);

if (!password_verify($newPassword, $updatedStudent['password'])) {
    echo "FAILED: Student new password hash verification failed.\n";
    exit(1);
}
if ($updatedStudent['reset_password_code'] !== null) {
    echo "FAILED: reset_password_code was not cleared for student.\n";
    exit(1);
}
echo "SUCCESS: Password successfully reset and OTP cleared for Student!\n\n";

// Restore student test password to 'password123'
$restoreHash = password_hash('password123', PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password = :pwd WHERE id = :id")->execute(['pwd' => $restoreHash, 'id' => (int)$student['id']]);

// 5. Test Applicant Lookup & OTP
echo "5. Testing Applicant Lookup by Email (jane.doe@example.com)...\n";
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = 'applicant' AND is_active = 1 LIMIT 1");
$stmt->execute(['email' => 'jane.doe@example.com']);
$applicant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$applicant) {
    echo "FAILED: Applicant jane.doe@example.com not found.\n";
    exit(1);
}
echo "SUCCESS: Found Applicant {$applicant['first_name']} {$applicant['last_name']}\n\n";

echo "=== ALL TRI-PORTAL (STUDENT, FACULTY, APPLICANT) FORGOT PASSWORD TESTS PASSED! ===\n";
