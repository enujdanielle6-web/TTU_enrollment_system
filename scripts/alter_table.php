<?php
require 'vendor/autoload.php'; // if exists
require 'app/Core/Database.php';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=sia;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM `fee_templates` LIKE 'is_per_unit'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `fee_templates` ADD COLUMN `is_per_unit` tinyint(1) NOT NULL DEFAULT 0 AFTER `strand`");
        echo "Column is_per_unit added successfully.\n";
    } else {
        echo "Column is_per_unit already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
