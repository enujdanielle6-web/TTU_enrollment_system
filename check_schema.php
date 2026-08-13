<?php
require __DIR__ . '/app/Core/Database.php';
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sia;charset=utf8mb4', 'root', '');
    $pdo->exec('ALTER TABLE payment_records ADD COLUMN remarks TEXT DEFAULT NULL AFTER status');
    echo "Added remarks column successfully.";
} catch (Exception $e) {
    echo $e->getMessage();
}
