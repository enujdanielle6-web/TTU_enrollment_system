<?php
require_once __DIR__ . '/verify_phase4.php';
use App\Core\Database;
$pdo = Database::getConnection();
$items = $pdo->query('SELECT assessment_id, item_type, item_code, item_name, units, amount FROM assessment_items ORDER BY assessment_id, id')->fetchAll(PDO::FETCH_ASSOC);
print_r($items);
