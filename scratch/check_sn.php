<?php
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Core/Database.php';

// Load .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim(trim($value), '"\'');
        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

$pdo = App\Core\Database::getConnection();
$rows = $pdo->query("SELECT id, student_number FROM users WHERE student_number IS NOT NULL AND student_number != ''")->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($rows) . " students with numbers:\n";
foreach ($rows as $r) {
    echo "User ID {$r['id']}: {$r['student_number']}\n";
}
