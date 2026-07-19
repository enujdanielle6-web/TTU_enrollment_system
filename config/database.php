<?php

declare(strict_types=1);

$dbConfig = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'sia',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
];

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['port'],
    $dbConfig['database'],
    $dbConfig['charset']
);

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    throw new RuntimeException('Unable to connect to the database.');
}
