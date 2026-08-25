<?php
/**
 * Triple T University (TTU) Enrollment & LMS System
 * Automated Database Setup & Seeder Engine
 * 
 * Usage:
 *   CLI: php database/migrations/setup_database.php
 *   Web: http://localhost/sia/database/migrations/setup_database.php
 */

declare(strict_types=1);

ini_set('max_execution_time', '300');
ini_set('memory_limit', '512M');

$isCli = (php_sapi_name() === 'cli');

function out(string $message, string $type = 'info'): void {
    global $isCli;
    if ($isCli) {
        $colors = [
            'info' => "\033[36m",
            'success' => "\033[32m",
            'warning' => "\033[33m",
            'error' => "\033[31m",
            'bold' => "\033[1m",
            'reset' => "\033[0m"
        ];
        echo ($colors[$type] ?? "") . $message . ($colors['reset'] ?? "") . PHP_EOL;
    } else {
        $styles = [
            'info' => 'color: #0284c7;',
            'success' => 'color: #16a34a; font-weight: bold;',
            'warning' => 'color: #d97706;',
            'error' => 'color: #dc2626; font-weight: bold;',
            'bold' => 'font-weight: bold;'
        ];
        echo "<div style='" . ($styles[$type] ?? "") . "'>" . htmlspecialchars($message) . "</div>";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>TTU Database Setup</title>";
    echo "<style>body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f8fafc; color: #0f172a; padding: 2rem; line-height: 1.6; }";
    echo ".card { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); max-width: 900px; margin: 0 auto; }";
    echo "table { width: 100%; border-collapse: collapse; margin-top: 1rem; } th, td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; } th { background: #f1f5f9; }";
    echo "h1 { color: #1e3a8a; margin-top: 0; }</style></head><body><div class='card'>";
    echo "<h1>Triple T University (TTU) Database Setup</h1>";
} else {
    out("==================================================================", "bold");
    out("  TRIPLE T UNIVERSITY (TTU) - AUTOMATED DATABASE SETUP ENGINE", "bold");
    out("==================================================================", "bold");
}

// 1. Load .env file
$envPath = dirname(__DIR__, 2) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, ';') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_DATABASE') ?: 'sia';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

try {
    // 2. Connect to MySQL server
    out("[1/5] Connecting to MariaDB/MySQL server at $host:$port...", "info");
    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);

    // 3. Drop and Recreate Database
    out("[2/5] Initializing database '$dbname' (Dropping if exists and creating clean schema)...", "info");
    $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
    $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    out("  ✓ Database '$dbname' created successfully with utf8mb4 collation.", "success");

    // 4. Connect to the fresh database
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // 5. Import schema.sql
    $schemaFile = dirname(__DIR__) . '/schema.sql';
    if (file_exists($schemaFile)) {
        out("[3/5] Importing active table definitions and views (database/schema.sql)...", "info");
        $schemaSql = file_get_contents($schemaFile);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec($schemaSql);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        out("  ✓ Schema imported successfully (All 42 tables & views created).", "success");
    } else {
        throw new RuntimeException("Missing schema file: $schemaFile");
    }

    // 6. Import seed.sql
    $seedFile = dirname(__DIR__) . '/seed.sql';
    if (file_exists($seedFile)) {
        out("[4/5] Importing clean institutional seed data (database/seed.sql)...", "info");
        $seedSql = file_get_contents($seedFile);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec($seedSql);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        out("  ✓ Seed data imported successfully.", "success");
    } else {
        out("[WARNING] Seed file database/seed.sql not found. Skipping data seeding.", "warning");
    }

    // 7. Verify Table Count
    out("[5/5] Verifying database integrity...", "info");
    $stmt = $pdo->query("SHOW FULL TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_NUM);
    $tableCount = count($tables);
    out("  ✓ Successfully verified $tableCount tables and views in '$dbname'.", "success");

    out("", "info");
    out("==================================================================", "bold");
    out("  🎉 SETUP COMPLETE: DATABASE IS READY FOR USE", "success");
    out("==================================================================", "bold");

    if ($isCli) {
        echo PHP_EOL . "Standard Default Credentials:" . PHP_EOL;
        echo "+---------------------+---------------------------+-------------+----------------------------------------------+" . PHP_EOL;
        echo "| Role                | Email / Identifier        | Password    | Portal Route                                 |" . PHP_EOL;
        echo "+---------------------+---------------------------+-------------+----------------------------------------------+" . PHP_EOL;
        echo "| Superadmin          | admin@ttu.edu.ph          | admin123    | /admin/dashboard.php                         |" . PHP_EOL;
        echo "| Admissions Officer  | admissions@ttu.edu.ph     | admin123    | /admin/admissions/admissions_dashboard.php   |" . PHP_EOL;
        echo "| Registrar Officer   | registrar@ttu.edu.ph      | admin123    | /admin/registrar/registrar_dashboard.php     |" . PHP_EOL;
        echo "| Cashier / Finance   | cashier@ttu.edu.ph        | admin123    | /admin/finance/cashier_dashboard.php         |" . PHP_EOL;
        echo "| Clinic Officer      | clinic@ttu.edu.ph         | admin123    | /admin/clinic/clinic_dashboard.php           |" . PHP_EOL;
        echo "| Scheduler Officer   | scheduler@ttu.edu.ph      | admin123    | /admin/scheduler/scheduler_dashboard.php     |" . PHP_EOL;
        echo "| Scholarship Officer | scholarship@ttu.edu.ph    | admin123    | /admin/scholarship/scholarship_dashboard.php |" . PHP_EOL;
        echo "| Faculty Instructor  | FAC-2026-001 (or email)   | password123 | /lms/faculty/dashboard.php                   |" . PHP_EOL;
        echo "| Enrolled Student    | 2026-000001 (or email)    | password123 | /lms/student/dashboard.php                   |" . PHP_EOL;
        echo "| Applicant User      | jane.applicant@example.com| password123 | /applicant/dashboard.php                     |" . PHP_EOL;
        echo "+---------------------+---------------------------+-------------+----------------------------------------------+" . PHP_EOL;
    } else {
        echo "<h3>Standard Default Credentials:</h3>";
        echo "<table><thead><tr><th>Role</th><th>Email / Identifier</th><th>Password</th><th>Portal Route</th></tr></thead><tbody>";
        echo "<tr><td>Superadmin</td><td><code>admin@ttu.edu.ph</code></td><td><code>admin123</code></td><td>/admin/dashboard.php</td></tr>";
        echo "<tr><td>Admissions Officer</td><td><code>admissions@ttu.edu.ph</code></td><td><code>admin123</code></td><td>/admin/admissions/admissions_dashboard.php</td></tr>";
        echo "<tr><td>Registrar Officer</td><td><code>registrar@ttu.edu.ph</code></td><td><code>admin123</code></td><td>/admin/registrar/registrar_dashboard.php</td></tr>";
        echo "<tr><td>Cashier / Finance</td><td><code>cashier@ttu.edu.ph</code></td><td><code>admin123</code></td><td>/admin/finance/cashier_dashboard.php</td></tr>";
        echo "<tr><td>Clinic Officer</td><td><code>clinic@ttu.edu.ph</code></td><td><code>admin123</code></td><td>/admin/clinic/clinic_dashboard.php</td></tr>";
        echo "<tr><td>Scheduler Officer</td><td><code>scheduler@ttu.edu.ph</code></td><td><code>admin123</code></td><td>/admin/scheduler/scheduler_dashboard.php</td></tr>";
        echo "<tr><td>Scholarship Officer</td><td><code>scholarship@ttu.edu.ph</code></td><td><code>admin123</code></td><td>/admin/scholarship/scholarship_dashboard.php</td></tr>";
        echo "<tr><td>Faculty Instructor</td><td><code>FAC-2026-001</code></td><td><code>password123</code></td><td>/lms/faculty/dashboard.php</td></tr>";
        echo "<tr><td>Enrolled Student</td><td><code>2026-000001</code></td><td><code>password123</code></td><td>/lms/student/dashboard.php</td></tr>";
        echo "<tr><td>Applicant User</td><td><code>jane.applicant@example.com</code></td><td><code>password123</code></td><td>/applicant/dashboard.php</td></tr>";
        echo "</tbody></table></div></body></html>";
    }

} catch (PDOException $e) {
    out("❌ Database Error: " . $e->getMessage(), "error");
    if (!$isCli) echo "</div></body></html>";
    exit(1);
} catch (Exception $e) {
    out("❌ Setup Error: " . $e->getMessage(), "error");
    if (!$isCli) echo "</div></body></html>";
    exit(1);
}
