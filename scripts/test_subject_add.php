<?php
require_once __DIR__ . '/app/Core/Database.php';

try {
    $pdo = \App\Core\Database::getConnection();
    $stmt = $pdo->prepare('INSERT INTO subjects (subject_code, subject_name, units, subject_type, description, education_level) VALUES (:code, :name, :units, :type, :desc, :level)');
    $stmt->execute([
        'code' => 'TEST101', 
        'name' => 'Test Subject', 
        'units' => 3, 
        'type' => 'Lecture', 
        'desc' => 'Test description', 
        'level' => 'College'
    ]);
    echo "Subject added successfully. Insert ID: " . $pdo->lastInsertId() . "\n";
} catch (\PDOException $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
