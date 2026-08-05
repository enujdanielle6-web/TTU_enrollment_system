<?php
require 'config/database.php';
$stmt = $pdo->query("SELECT * FROM users WHERE student_number = 'EMP-001'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
