<?php
require_once __DIR__ . '/verify_phase3.php';
use App\Core\Database;
$pdo = Database::getConnection();
$stmt = $pdo->query('SELECT receipt_number FROM payment_records WHERE receipt_number IS NOT NULL ORDER BY id DESC LIMIT 5');
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
