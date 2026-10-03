<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

// Configuration (config/config.php or .env) for scripts that use this class directly
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

class Database
{
    private static ?PDO $connection = null;

    private function __construct()
    {
        // Private constructor to prevent multiple instances
    }

    private function __clone()
    {
        // Private clone to prevent duplication
    }

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $dbConfig = [
                'host' => app_env('DB_HOST') ?: 'localhost',
                'port' => app_env('DB_PORT') ?: '3306',
                'database' => app_env('DB_DATABASE') ?: 'sia',
                'username' => app_env('DB_USERNAME') ?: 'root',
                'password' => app_env('DB_PASSWORD') ?: '',
                'charset' => app_env('DB_CHARSET') ?: 'utf8mb4',
            ];

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $dbConfig['host'],
                $dbConfig['port'],
                $dbConfig['database'],
                $dbConfig['charset']
            );

            date_default_timezone_set('Asia/Manila');

            try {
                self::$connection = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+08:00'",
                ]);
            } catch (PDOException $exception) {
                error_log('Database connection failed: ' . $exception->getMessage());
                throw new RuntimeException('Unable to connect to the database.');
            }
        }

        return self::$connection;
    }
}
