<?php
require 'config/database.php';
$stmt = $pdo->query('DESCRIBE college_sections');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
