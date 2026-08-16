<?php
require_once __DIR__ . '/config/database.php';

$tables = ['users', 'applications', 'students'];

foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " | " . $row['Type'] . "\n";
        }
    } catch (Exception $e) {
        echo "Table $table doesn't exist or error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
