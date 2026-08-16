<?php
require 'app/Core/Database.php';
$pdo = \App\Core\Database::getConnection();
$tables = $pdo->query("SHOW TABLES LIKE 'shs%'")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
$columns = $pdo->query("DESCRIBE shs_curriculum_subjects")->fetchAll(PDO::FETCH_COLUMN);
print_r($columns);
