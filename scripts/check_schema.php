<?php
require 'vendor/autoload.php'; // if exists
require 'app/Core/Database.php';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=sia;charset=utf8mb4', 'root', '');
    $stmt = $pdo->query('DESCRIBE fee_templates');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
