<?php
require_once __DIR__ . '/config/database.php';

$stmt = $pdo->query("SELECT id, first_name, last_name, email, role FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo sprintf("%-5s | %-20s | %-30s | %-15s\n", 'ID', 'Name', 'Email', 'Role');
echo str_repeat('-', 75) . "\n";
foreach ($users as $u) {
    echo sprintf("%-5s | %-20s | %-30s | %-15s\n", $u['id'], $u['first_name'] . ' ' . $u['last_name'], $u['email'], $u['role']);
}
