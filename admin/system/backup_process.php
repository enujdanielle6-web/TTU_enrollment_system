<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('*');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: backup.php');
    exit;
}

verifyCsrfToken();

$action = $_POST['action'] ?? '';

if ($action === 'export') {
    try {
        // Prepare output
        $sqlScript = "-- Online Enrollment System SQL Dump\n";
        $sqlScript .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        // Get all tables
        $tables = [];
        $stmt = $pdo->query('SHOW TABLES');
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            // Get Create Table statement
            $sqlScript .= "-- --------------------------------------------------------\n";
            $sqlScript .= "-- Table structure for table `{$table}`\n";
            $sqlScript .= "-- --------------------------------------------------------\n\n";
            $sqlScript .= "DROP TABLE IF EXISTS `{$table}`;\n";
            
            $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $sqlScript .= $row[1] . ";\n\n";

            // Get Data
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $rowCount = $stmt->rowCount();
            if ($rowCount > 0) {
                $sqlScript .= "-- Dumping data for table `{$table}`\n\n";
                $sqlScript .= "INSERT INTO `{$table}` VALUES \n";
                
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $valuesArr = [];
                foreach ($rows as $r) {
                    $valLine = [];
                    foreach ($r as $val) {
                        if ($val === null) {
                            $valLine[] = 'NULL';
                        } else {
                            // Escape single quotes and backslashes
                            $escapedVal = str_replace(['\\', "'", "\r", "\n"], ['\\\\', "''", '\r', '\n'], $val);
                            $valLine[] = "'" . $escapedVal . "'";
                        }
                    }
                    $valuesArr[] = "(" . implode(", ", $valLine) . ")";
                }
                $sqlScript .= implode(",\n", $valuesArr) . ";\n\n";
            }
        }

        $sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";

        // Log Export
        logActivity((int)$_SESSION['user_id'], 'bi-download', 'Database Backup', 'Generated a full SQL database backup.');

        // Serve as download
        $filename = 'backup_sia_' . date('Y-m-d_His') . '.sql';
        
        ob_clean();
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sqlScript));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo $sqlScript;
        exit;

    } catch (PDOException $e) {
        error_log('Export failed: ' . $e->getMessage());
        $_SESSION['error_msg'] = 'Failed to generate database backup. Please try again.';
        header('Location: backup.php');
        exit;
    }
} elseif ($action === 'import') {
    try {
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Please select a valid SQL backup file to restore.');
        }

        $fileTmp = $_FILES['backup_file']['tmp_name'];
        $fileName = $_FILES['backup_file']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExt !== 'sql') {
            throw new Exception('Invalid file format. Please upload an .sql file.');
        }

        $sqlScript = file_get_contents($fileTmp);
        if (empty(trim($sqlScript))) {
            throw new Exception('The uploaded SQL file is empty.');
        }

        // Execute raw SQL script
        $pdo->exec($sqlScript);

        // Log Restore
        logActivity((int)$_SESSION['user_id'], 'bi-cloud-arrow-up', 'Database Restored', 'Restored the system database from an SQL backup file.');

        $_SESSION['success_msg'] = 'Database restored successfully.';
    } catch (Exception $e) {
        error_log('Import failed: ' . $e->getMessage());
        $_SESSION['error_msg'] = $e->getMessage();
    }

    header('Location: backup.php');
    exit;
} else {
    $_SESSION['error_msg'] = 'Invalid action requested.';
    header('Location: backup.php');
    exit;
}

