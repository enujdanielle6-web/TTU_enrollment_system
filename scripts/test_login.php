<?php

function testLogin(string $email, string $password, string $expectedLocation): void {
    $cookieFile = __DIR__ . '/cookie_' . md5($email) . '.txt';
    if (file_exists($cookieFile)) unlink($cookieFile);

    // 1. Fetch Login Page
    $ch = curl_init('http://localhost/sia/auth/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    $html = curl_exec($ch);
    curl_close($ch);

    preg_match('/name="csrf_token" value="([^"]+)"/', $html, $matches);
    $csrf = $matches[1] ?? '';

    // 2. Submit Login
    $ch = curl_init('http://localhost/sia/auth/login_process.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'email' => $email,
        'password' => $password,
        'csrf_token' => $csrf
    ]));
    curl_setopt($ch, CURLOPT_HEADER, true);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    if (file_exists($cookieFile)) unlink($cookieFile);

    $status = ($httpCode === 302 && strpos($redirectUrl, $expectedLocation) !== false) ? "✅ PASS" : "❌ FAIL (Code: $httpCode, Loc: $redirectUrl)";
    echo sprintf("%-28s | %-12s | %s\n", $email, $password, $status);
}

function testLmsStudent(string $studentId, string $password): void {
    $cookieFile = __DIR__ . '/cookie_lms_std.txt';
    if (file_exists($cookieFile)) unlink($cookieFile);

    // Fetch page for CSRF
    $ch = curl_init('http://localhost/sia/auth/lms_student_login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    $html = curl_exec($ch);
    curl_close($ch);

    preg_match('/name="csrf_token" value="([^"]+)"/', $html, $matches);
    $csrf = $matches[1] ?? '';

    $ch = curl_init('http://localhost/sia/auth/lms_login_process.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'role' => 'student',
        'student_id' => $studentId,
        'password' => $password,
        'csrf_token' => $csrf
    ]));
    curl_setopt($ch, CURLOPT_HEADER, true);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    if (file_exists($cookieFile)) unlink($cookieFile);

    $status = ($httpCode === 302 && strpos($redirectUrl, 'dashboard.php') !== false) ? "✅ PASS" : "❌ FAIL (Code: $httpCode, Loc: $redirectUrl)";
    echo sprintf("LMS Student: %-15s | %-12s | %s\n", $studentId, $password, $status);
}

function testLmsFaculty(string $empId, string $password): void {
    $cookieFile = __DIR__ . '/cookie_lms_fac.txt';
    if (file_exists($cookieFile)) unlink($cookieFile);

    // Fetch page for CSRF
    $ch = curl_init('http://localhost/sia/auth/lms_faculty_login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    $html = curl_exec($ch);
    curl_close($ch);

    preg_match('/name="csrf_token" value="([^"]+)"/', $html, $matches);
    $csrf = $matches[1] ?? '';

    $ch = curl_init('http://localhost/sia/auth/lms_login_process.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'role' => 'faculty',
        'employee_id' => $empId,
        'password' => $password,
        'csrf_token' => $csrf
    ]));
    curl_setopt($ch, CURLOPT_HEADER, true);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    if (file_exists($cookieFile)) unlink($cookieFile);

    $status = ($httpCode === 302 && strpos($redirectUrl, 'dashboard.php') !== false) ? "✅ PASS" : "❌ FAIL (Code: $httpCode, Loc: $redirectUrl)";
    echo sprintf("LMS Faculty: %-15s | %-12s | %s\n", $empId, $password, $status);
}

echo "=== AUTH / LOGIN TEST SUITE ===\n";
testLogin('admin@ttu.edu.ph', 'admin123', '/sia/admin/dashboard.php');
testLogin('admissions@ttu.edu.ph', 'admin123', '/sia/admin/dashboard.php');
testLogin('registrar@ttu.edu.ph', 'admin123', '/sia/admin/dashboard.php');
testLogin('cashier@ttu.edu.ph', 'admin123', '/sia/admin/dashboard.php');
testLogin('clinic@ttu.edu.ph', 'admin123', '/sia/admin/dashboard.php');
testLogin('scheduler@ttu.edu.ph', 'admin123', '/sia/admin/dashboard.php');
testLogin('scholarship@ttu.edu.ph', 'admin123', '/sia/admin/dashboard.php');
testLogin('jane.applicant@example.com', 'password123', '/sia/applicant/dashboard.php');

echo "\n=== LMS LOGIN TEST SUITE ===\n";
testLmsStudent('2026-000001', 'password123');
testLmsStudent('2026-000002', 'password123');
testLmsFaculty('FAC-2026-001', 'password123');
testLmsFaculty('FAC-2026-002', 'password123');
