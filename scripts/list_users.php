<?php
$pdo = new PDO("mysql:host=localhost;dbname=sia;charset=utf8mb4", "root", "");
$stmt = $pdo->query("SELECT id, first_name, last_name, email, role, password FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo sprintf("%-5s | %-20s | %-28s | %-12s | %s\n", 'ID', 'Name', 'Email', 'Role', 'Password Match');
echo str_repeat('-', 95) . "\n";
foreach ($users as $u) {
    $match = [];
    if (password_verify('admin123', $u['password'])) $match[] = 'admin123';
    if (password_verify('password123', $u['password'])) $match[] = 'password123';
    echo sprintf("%-5s | %-20s | %-28s | %-12s | %s\n", $u['id'], $u['first_name'] . ' ' . $u['last_name'], $u['email'], $u['role'], implode(', ', $match) ?: 'NO MATCH');
}
