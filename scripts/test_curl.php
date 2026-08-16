<?php
$ch = curl_init('http://localhost/sia/admin/registrar/subject_process.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'add',
    'subject_code' => 'CURL101',
    'subject_name' => 'Curl Test',
    'units' => 3,
    'subject_type' => 'Lecture',
    'education_level' => 'College',
    'description' => ''
]));

// We might need to fake the session, but SubjectController doesn't check session logged_in directly
// RoleMiddleware checks logged_in!
// If RoleMiddleware intercepts it, it will redirect to /sia/auth/login.php!
$response = curl_exec($ch);
$info = curl_getinfo($ch);
echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Redirect URL: " . $info['redirect_url'] . "\n";
echo "Response: " . substr($response, 0, 500) . "\n";
