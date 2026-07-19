<?php
/**
 * TTU Enrollment System - Automated Database Setup
 * 
 * Run this script via CLI: php setup_database.php
 * Or access it via browser: http://localhost/sia/setup_database.php
 */

declare(strict_types=1);

ini_set('max_execution_time', '300');

echo "<h2>TTU Enrollment System - Database Setup</h2>\n";

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_DATABASE') ?: 'sia';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

try {
    // 1. Connect without database to create it
    echo "<p>1. Connecting to MySQL server...</p>\n";
    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Drop and Create Database
    echo "<p>2. Creating database '$dbname' (dropping if exists)...</p>\n";
    $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
    $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // 3. Connect to the new database
    echo "<p>3. Connecting to database '$dbname'...</p>\n";
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 4. Import schema.sql
    $schemaPath = __DIR__ . '/database/schema.sql';
    if (file_exists($schemaPath)) {
        echo "<p>4. Importing initial schema (database/schema.sql)...</p>\n";
        $schemaSql = file_get_contents($schemaPath);
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec($schemaSql);
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        echo "   -> Schema imported successfully.<br>\n";
    } else {
        echo "<p>4. [WARNING] database/schema.sql not found! Proceeding...</p>\n";
    }

    // 5. Import seed.sql (if exists)
    $seedPath = __DIR__ . '/database/seed.sql';
    if (file_exists($seedPath)) {
        echo "<p>5. Importing seed data (database/seed.sql)...</p>\n";
        $seedSql = file_get_contents($seedPath);
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec($seedSql);
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        echo "   -> Seed data imported successfully.<br>\n";
    }
    
    // Note: Phase migrations are no longer needed for fresh setups as schema.sql
    // has been updated to include all features up to Phase 5.
    
    echo "<h3>🎉 Setup Complete!</h3>";
    echo "You can now log in using the credentials defined in your seed file or system.<br>";
    echo "Default Admin: <b>admin@ttu.edu.ph</b> / <b>password123</b><br>";
    echo "Default Applicant: <b>applicant@example.com</b> / <b>password123</b><br>";
    echo "<br><br><b>SECURITY WARNING:</b> Please delete this setup_database.php file before deploying to production.<br>";

} catch (PDOException $e) {
    echo "❌ <b>Database Error:</b> " . $e->getMessage() . "\n<br>";
    echo "Please check your credentials in config/database.php.\n<br>";
} catch (Exception $e) {
    echo "❌ <b>Error:</b> " . $e->getMessage() . "\n<br>";
}
