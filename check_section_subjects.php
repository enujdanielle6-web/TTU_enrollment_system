<?php
require 'config/database.php';
$stmt = $pdo->query('DESCRIBE section_subjects');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
