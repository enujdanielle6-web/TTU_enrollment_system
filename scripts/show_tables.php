<?php
require 'vendor/autoload.php';
require 'app/Core/Database.php';
$pdo = new PDO('mysql:host=localhost;dbname=sia;charset=utf8mb4', 'root', '');
$stmt = $pdo->query('SHOW TABLES;');
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
