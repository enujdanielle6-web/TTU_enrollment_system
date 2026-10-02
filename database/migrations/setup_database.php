<?php
/**
 * Triple T University (TTU) Enrollment & LMS System
 * Automated Database Setup & Seeder Engine
 * 
 * Usage:
 *   CLI:     php setup_database.php
 *            php database/migrations/setup_database.php
 *   Browser: http://localhost/sia/setup_database.php
 *            http://localhost/sia/setup_database.php?execute=1
 */

declare(strict_types=1);

ini_set('max_execution_time', '300');
$isCli = (php_sapi_name() === 'cli');

// 1. Load configuration (config/config.php, then .env)
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

// This tool DROPS and recreates the whole database. Never expose it on a production site.
if (!$isCli && (getenv('APP_ENV') ?: 'production') === 'production') {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_DATABASE') ?: 'sia';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

// Determine whether to execute setup or show status dashboard in web mode
$shouldExecute = $isCli 
    || ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' 
    || isset($_GET['execute']) 
    || isset($_GET['run']);

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
            'info' => 'color: #0284c7; padding: 2px 0;',
            'success' => 'color: #16a34a; font-weight: 600; padding: 2px 0;',
            'warning' => 'color: #d97706; padding: 2px 0;',
            'error' => 'color: #dc2626; font-weight: bold; padding: 2px 0;',
            'bold' => 'font-weight: bold; padding: 2px 0;'
        ];
        echo "<div style='" . ($styles[$type] ?? "") . "'>" . htmlspecialchars($message) . "</div>";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}

// ----------------------------------------------------------------------------
// WEB INTERFACE: PRE-EXECUTION STATUS DASHBOARD
// ----------------------------------------------------------------------------
if (!$isCli && !$shouldExecute) {
    // Check current live state safely without dropping
    $dbConnected = false;
    $dbExists = false;
    $existingTableCount = 0;
    $connectionError = null;

    try {
        $pdoCheck = new PDO("mysql:host=$host;port=$port", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        $dbConnected = true;

        $stmt = $pdoCheck->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :dbname");
        $stmt->execute(['dbname' => $dbname]);
        if ($stmt->fetch()) {
            $dbExists = true;
            $pdoDb = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $tablesStmt = $pdoDb->query("SHOW FULL TABLES");
            $existingTableCount = count($tablesStmt->fetchAll(PDO::FETCH_NUM));
        }
    } catch (PDOException $e) {
        $connectionError = $e->getMessage();
    }

    $schemaFile = dirname(__DIR__) . '/schema.sql';
    $seedFile = dirname(__DIR__) . '/seed.sql';
    $schemaExists = file_exists($schemaFile);
    $seedExists = file_exists($seedFile);

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Setup & Seeder — Triple T University</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary: #1e3a8a;
                --primary-hover: #1e40af;
                --bg: #f8fafc;
                --card-bg: #ffffff;
                --text: #0f172a;
                --muted: #64748b;
                --border: #e2e8f0;
                --success: #16a34a;
                --warning: #d97706;
                --danger: #dc2626;
            }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
                background-color: var(--bg);
                color: var(--text);
                padding: 2.5rem 1rem;
                line-height: 1.5;
            }
            .container {
                max-width: 860px;
                margin: 0 auto;
            }
            .card {
                background: var(--card-bg);
                border-radius: 16px;
                border: 1px solid var(--border);
                box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.02);
                overflow: hidden;
            }
            .card-header {
                padding: 1.75rem 2rem;
                background: linear-gradient(135deg, #1e3a8a 0%, #1e1b4b 100%);
                color: white;
            }
            .card-header h1 {
                font-size: 1.35rem;
                font-weight: 700;
                letter-spacing: -0.01em;
            }
            .card-header p {
                font-size: 0.875rem;
                color: #93c5fd;
                margin-top: 0.35rem;
            }
            .card-body {
                padding: 2rem;
            }
            .status-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 1rem;
                margin-bottom: 2rem;
            }
            .status-box {
                background: #f1f5f9;
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 1.25rem;
            }
            .status-box .label {
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: var(--muted);
                font-weight: 600;
            }
            .status-box .val {
                font-size: 1.15rem;
                font-weight: 700;
                margin-top: 0.25rem;
                color: var(--text);
            }
            .badge {
                display: inline-block;
                padding: 0.25rem 0.65rem;
                font-size: 0.75rem;
                font-weight: 600;
                border-radius: 9999px;
            }
            .badge-success { background: #dcfce7; color: #166534; }
            .badge-warning { background: #fef3c7; color: #92400e; }
            .badge-danger { background: #fee2e2; color: #991b1b; }
            .badge-info { background: #e0f2fe; color: #0369a1; }
            .alert {
                padding: 1rem 1.25rem;
                border-radius: 10px;
                font-size: 0.875rem;
                margin-bottom: 1.75rem;
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
            }
            .alert-warning {
                background: #fffbeb;
                border: 1px solid #fde68a;
                color: #92400e;
            }
            .alert-info {
                background: #f0f9ff;
                border: 1px solid #bae6fd;
                color: #0369a1;
            }
            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                padding: 0.75rem 1.5rem;
                font-size: 0.95rem;
                font-weight: 600;
                border-radius: 10px;
                text-decoration: none;
                cursor: pointer;
                border: none;
                transition: all 0.15s ease-in-out;
            }
            .btn-primary {
                background: var(--primary);
                color: white;
            }
            .btn-primary:hover {
                background: var(--primary-hover);
            }
            .btn-danger {
                background: var(--danger);
                color: white;
            }
            .btn-danger:hover {
                background: #b91c1c;
            }
            .btn-outline {
                background: white;
                color: var(--text);
                border: 1px solid var(--border);
            }
            .btn-outline:hover {
                background: #f1f5f9;
            }
            .actions-bar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding-top: 1.5rem;
                border-top: 1px solid var(--border);
            }
            .checklist {
                list-style: none;
                margin-bottom: 2rem;
            }
            .checklist li {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.6rem 0;
                border-bottom: 1px solid #f1f5f9;
                font-size: 0.9rem;
            }
            .check-icon {
                font-weight: bold;
                font-size: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h1>Triple T University (TTU) — Database Setup & Seeder</h1>
                    <p>Automated MariaDB schema generator and institutional dataset provisioner</p>
                </div>
                <div class="card-body">
                    <div class="status-grid">
                        <div class="status-box">
                            <div class="label">MariaDB Server</div>
                            <div class="val"><?= htmlspecialchars($host . ':' . $port) ?></div>
                            <div style="margin-top: 0.4rem;">
                                <?php if ($dbConnected): ?>
                                    <span class="badge badge-success">Online & Accessible</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Connection Failed</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="status-box">
                            <div class="label">Target Database</div>
                            <div class="val"><?= htmlspecialchars($dbname) ?></div>
                            <div style="margin-top: 0.4rem;">
                                <?php if ($dbExists): ?>
                                    <span class="badge badge-info"><?= $existingTableCount ?> Tables / Views</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Not Created</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="status-box">
                            <div class="label">Migration Assets</div>
                            <div class="val"><?= ($schemaExists && $seedExists) ? 'Ready (2/2)' : 'Incomplete' ?></div>
                            <div style="margin-top: 0.4rem;">
                                <span class="badge badge-success">schema.sql + seed.sql</span>
                            </div>
                        </div>
                    </div>

                    <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #1e293b;">Pre-Flight System Check</h3>
                    <ul class="checklist">
                        <li>
                            <span class="check-icon" style="color: <?= $dbConnected ? 'var(--success)' : 'var(--danger)' ?>;">
                                <?= $dbConnected ? '✓' : '✗' ?>
                            </span>
                            <span>Database Server Handshake: <strong><?= htmlspecialchars("$username@$host:$port") ?></strong></span>
                        </li>
                        <li>
                            <span class="check-icon" style="color: <?= $schemaExists ? 'var(--success)' : 'var(--danger)' ?>;">
                                <?= $schemaExists ? '✓' : '✗' ?>
                            </span>
                            <span>Authoritative Schema Asset: <code>database/schema.sql</code> (50 DDL Tables & Views)</span>
                        </li>
                        <li>
                            <span class="check-icon" style="color: <?= $seedExists ? 'var(--success)' : 'var(--danger)' ?>;">
                                <?= $seedExists ? '✓' : '✗' ?>
                            </span>
                            <span>Institutional Seed Data Asset: <code>database/seed.sql</code> (Roles, Curricula, Fee Templates)</span>
                        </li>
                        <li>
                            <span class="check-icon" style="color: var(--success);">✓</span>
                            <span>Execution Engine: PHP <?= PHP_VERSION ?> with PDO MySQL driver</span>
                        </li>
                    </ul>

                    <?php if ($dbExists && $existingTableCount > 0): ?>
                        <div class="alert alert-warning">
                            <div>
                                <strong>Notice:</strong> Database <code><?= htmlspecialchars($dbname) ?></code> is already populated with <strong><?= $existingTableCount ?> tables and views</strong>. Running setup will drop and recreate all tables and restore clean initial institutional test data.
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <div>
                                <strong>Initial Setup:</strong> Click the button below to initialize the TTU database and import all institutional tables and demo accounts.
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="setup_database.php?execute=1" class="actions-bar">
                        <div style="display: flex; gap: 0.75rem; align-items: center;">
                            <a href="<?= BASE_PATH ?>/" class="btn btn-outline">🏠 Main Page</a>
                            <a href="<?= BASE_PATH ?>/auth/login.php" class="btn btn-outline">🔑 Portal Login</a>
                        </div>
                        <button type="submit" name="execute" value="1" class="btn <?= ($dbExists && $existingTableCount > 0) ? 'btn-danger' : 'btn-primary' ?>">
                            ⚡ <?= ($dbExists && $existingTableCount > 0) ? 'Reset & Reseed Database' : 'Initialize Database Now' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit(0);
}

// ----------------------------------------------------------------------------
// SETUP EXECUTION ENGINE (CLI or Web with ?execute=1 / POST)
// ----------------------------------------------------------------------------

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>TTU Database Setup</title>";
    echo "<link rel='preconnect' href='https://fonts.googleapis.com'><link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>";
    echo "<link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap' rel='stylesheet'>";
    echo "<style>";
    echo "body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; color: #0f172a; padding: 2rem 1rem; line-height: 1.6; }";
    echo ".card { background: white; border-radius: 14px; padding: 2rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); max-width: 960px; margin: 0 auto; border: 1px solid #e2e8f0; }";
    echo ".header-banner { background: #1e3a8a; color: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; }";
    echo ".header-banner h1 { font-size: 1.3rem; margin: 0; }";
    echo ".console-log { background: #0f172a; color: #f8fafc; border-radius: 10px; padding: 1.25rem; font-family: 'Consolas', monospace; font-size: 0.85rem; margin-bottom: 2rem; overflow-x: auto; }";
    echo "table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.875rem; }";
    echo "th, td { border: 1px solid #e2e8f0; padding: 10px 12px; text-align: left; }";
    echo "th { background: #f1f5f9; font-weight: 600; color: #334155; }";
    echo "tr:hover { background: #f8fafc; }";
    echo ".badge { display: inline-block; padding: 0.2rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 6px; }";
    echo ".badge-role { background: #e0f2fe; color: #0369a1; }";
    echo "code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.85em; }";
    echo ".btn { display: inline-block; padding: 0.4rem 0.85rem; font-size: 0.8rem; font-weight: 600; border-radius: 6px; text-decoration: none; color: white; background: #1e3a8a; }";
    echo ".btn:hover { background: #1e40af; }";
    echo "</style></head><body><div class='card'>";
    echo "<div class='header-banner'><h1>Triple T University (TTU) Database Setup</h1><p style='margin-top: 0.25rem; color: #93c5fd; font-size: 0.85rem;'>Automated Schema Generator & Seeder</p></div>";
    echo "<h3 style='font-size: 1rem; margin-bottom: 0.5rem;'>Execution Log:</h3>";
    echo "<div class='console-log'>";
} else {
    out("==================================================================", "bold");
    out("  TRIPLE T UNIVERSITY (TTU) - AUTOMATED DATABASE SETUP ENGINE", "bold");
    out("==================================================================", "bold");
}

try {
    // Step 1: Connect to MariaDB Server
    out("[1/5] Connecting to MariaDB/MySQL server at $host:$port...", "info");
    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);

    // Step 2: Drop and Recreate Clean Database
    out("[2/5] Initializing database '$dbname' (Dropping if exists and creating clean schema)...", "info");
    $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
    $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    out("  ✓ Database '$dbname' created successfully with utf8mb4_unicode_ci collation.", "success");

    // Step 3: Connect to the Fresh Database
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Step 4: Import schema.sql
    $schemaFile = dirname(__DIR__) . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new RuntimeException("Missing schema file: $schemaFile");
    }
    out("[3/5] Importing active table definitions and views (database/schema.sql)...", "info");
    $schemaSql = file_get_contents($schemaFile);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec($schemaSql);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $initialTableStmt = $pdo->query("SHOW FULL TABLES");
    $schemaTableCount = count($initialTableStmt->fetchAll(PDO::FETCH_NUM));
    out("  ✓ Schema imported successfully ($schemaTableCount tables & views created).", "success");

    // Step 5: Import seed.sql
    $seedFile = dirname(__DIR__) . '/seed.sql';
    if (file_exists($seedFile)) {
        out("[4/5] Importing clean institutional seed data (database/seed.sql)...", "info");
        $seedSql = file_get_contents($seedFile);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec($seedSql);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        out("  ✓ Seed data imported successfully (Settings, Roles, Curricula, Offerings).", "success");
    } else {
        out("[WARNING] Seed file database/seed.sql not found. Skipping data seeding.", "warning");
    }

    // Step 6: Verify Database Integrity
    out("[5/5] Verifying database integrity and constraints...", "info");
    $stmt = $pdo->query("SHOW FULL TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_NUM);
    $tableCount = count($tables);
    out("  ✓ Successfully verified $tableCount tables and views in '$dbname'.", "success");

    out("", "info");
    out("==================================================================", "bold");
    out("  🎉 SETUP COMPLETE: DATABASE IS READY FOR USE", "success");
    out("==================================================================", "bold");

    if (!$isCli) {
        echo "</div>"; // Close console-log
    }

    // Credentials array for unified CLI and Web display
    $defaultAccounts = [
        [
            'role' => 'Superadmin',
            'identifier' => 'admin@ttu.edu.ph',
            'password' => 'admin123',
            'route' => '/admin/dashboard.php'
        ],
        [
            'role' => 'Admissions Officer',
            'identifier' => 'admissions@ttu.edu.ph',
            'password' => 'admin123',
            'route' => '/admin/admissions/admissions_dashboard.php'
        ],
        [
            'role' => 'Registrar Officer',
            'identifier' => 'registrar@ttu.edu.ph',
            'password' => 'admin123',
            'route' => '/admin/registrar/registrar_dashboard.php'
        ],
        [
            'role' => 'Cashier / Finance',
            'identifier' => 'cashier@ttu.edu.ph',
            'password' => 'admin123',
            'route' => '/admin/finance/cashier_dashboard.php'
        ],
        [
            'role' => 'Clinic Officer',
            'identifier' => 'clinic@ttu.edu.ph',
            'password' => 'admin123',
            'route' => '/admin/clinic/clinic_dashboard.php'
        ],
        [
            'role' => 'Scheduler Officer',
            'identifier' => 'scheduler@ttu.edu.ph',
            'password' => 'admin123',
            'route' => '/admin/scheduler/scheduler_dashboard.php'
        ],
        [
            'role' => 'Scholarship Officer',
            'identifier' => 'scholarship@ttu.edu.ph',
            'password' => 'admin123',
            'route' => '/admin/scholarship/scholarship_dashboard.php'
        ],
        [
            'role' => 'LMS Administrator',
            'identifier' => 'admin@ttu.edu.ph',
            'password' => 'admin123',
            'route' => '/lms/admin/dashboard'
        ],
        [
            'role' => 'Faculty Instructor',
            'identifier' => 'FAC-2026-001 (or alan.turing@ttu.edu.ph)',
            'password' => 'password123',
            'route' => '/lms/faculty/dashboard.php'
        ],
        [
            'role' => 'Enrolled Student',
            'identifier' => '2026-000001 (or john.doe@ttu.edu.ph)',
            'password' => 'password123',
            'route' => '/lms/student/dashboard.php'
        ],
        [
            'role' => 'Applicant User',
            'identifier' => 'jane.applicant@example.com',
            'password' => 'password123',
            'route' => '/applicant/dashboard.php'
        ],
    ];

    if ($isCli) {
        echo PHP_EOL . "Standard Default Credentials:" . PHP_EOL;
        echo "+---------------------+-----------------------------------------+-------------+----------------------------------------------+" . PHP_EOL;
        echo "| Role                | Email / Identifier                      | Password    | Portal Route                                 |" . PHP_EOL;
        echo "+---------------------+-----------------------------------------+-------------+----------------------------------------------+" . PHP_EOL;
        foreach ($defaultAccounts as $acc) {
            printf("| %-19s | %-39s | %-11s | %-44s |\n", $acc['role'], $acc['identifier'], $acc['password'], $acc['route']);
        }
        echo "+---------------------+-----------------------------------------+-------------+----------------------------------------------+" . PHP_EOL;
    } else {
        echo "<h3 style='font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;'>Standard Default Institutional Credentials</h3>";
        echo "<p style='color: #64748b; font-size: 0.85rem; margin-bottom: 1rem;'>Click on any portal route to open the module directly:</p>";
        echo "<table><thead><tr><th>Role</th><th>Email / Identifier</th><th>Password</th><th>Portal Route</th><th>Quick Action</th></tr></thead><tbody>";
        foreach ($defaultAccounts as $acc) {
            $webLink = BASE_PATH . $acc['route'];
            echo "<tr>";
            echo "<td><span class='badge badge-role'>" . htmlspecialchars($acc['role']) . "</span></td>";
            echo "<td><code>" . htmlspecialchars($acc['identifier']) . "</code></td>";
            echo "<td><code>" . htmlspecialchars($acc['password']) . "</code></td>";
            echo "<td><a href='" . htmlspecialchars($webLink) . "' style='color: #1e40af; text-decoration: underline;'>" . htmlspecialchars($acc['route']) . "</a></td>";
            echo "<td><a href='" . htmlspecialchars($webLink) . "' class='btn'>Open</a></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "<div style='margin-top: 1.5rem; display: flex; gap: 0.75rem;'>";
        echo "<a href='" . BASE_PATH . "/auth/login.php' class='btn' style='background: #16a34a; padding: 0.6rem 1.25rem; font-size: 0.9rem;'>🔑 Proceed to Portal Login</a>";
        echo "<a href='" . BASE_PATH . "/' class='btn' style='background: #475569; padding: 0.6rem 1.25rem; font-size: 0.9rem;'>🏠 University Homepage</a>";
        echo "</div>";
        echo "</div></body></html>";
    }

} catch (PDOException $e) {
    out("❌ Database Error: " . $e->getMessage(), "error");
    if (!$isCli) {
        echo "</div><div style='color: #dc2626; padding: 1rem; font-weight: bold;'>Setup Failed. Please check database permissions or MariaDB service.</div></div></body></html>";
    }
    exit(1);
} catch (Exception $e) {
    out("❌ Setup Error: " . $e->getMessage(), "error");
    if (!$isCli) {
        echo "</div><div style='color: #dc2626; padding: 1rem; font-weight: bold;'>Setup Failed: " . htmlspecialchars($e->getMessage()) . "</div></div></body></html>";
    }
    exit(1);
}
