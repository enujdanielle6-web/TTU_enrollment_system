<?php
namespace App\Controllers\Admin;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use PDOException;

class SystemController extends BaseController
{
    public function dashboard(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
requirePermission(['users.manage', 'settings.manage', 'reports.view']);

$pageTitle = 'System Admin Dashboard - Triple T University';

        return $this->render('admin/system/sysadmin_dashboard', get_defined_vars());
    }
    public function users(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$pageTitle = 'User Management - Administrator';

        return $this->render('admin/system/users', get_defined_vars());
    }
    public function auditLogs(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$pageTitle = 'Audit Logs - Administrator';

        return $this->render('admin/system/audit_logs', get_defined_vars());
    }
    public function userActivity(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        

$userId = (int)($_GET['id'] ?? 0);

if ($userId <= 0) {
    $response->redirect("/sia/admin/system/users.php");
    return;
}

// Fetch user details
$stmtUser = $pdo->prepare('SELECT first_name, last_name, email, role, department FROM users WHERE id = :id');
$stmtUser->execute(['id' => $userId]);
$user = $stmtUser->fetch();

if (!$user) {
    $response->redirect("/sia/admin/system/users.php");
    return;
}

$pageTitle = 'User Activity History - Administrator';

        return $this->render('admin/system/user_activity', get_defined_vars());
    }
    public function backup(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
requirePermission('*');

$pageTitle = 'Backup & Restore - Administrator';

$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);


        return $this->render('admin/system/backup', get_defined_vars());
    }
    public function settings(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        
requirePermission('*');

$pageTitle = 'System Settings - Administrator';

// Fetch global settings
try {
    $settingsStmt = $pdo->query('SELECT * FROM system_settings');
    $rawSettings = $settingsStmt->fetchAll();
    
    // Map settings into key-value pairs
    $settings = [];
    foreach ($rawSettings as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log('Settings fetch failed: ' . $e->getMessage());
    $settings = [];
}

// Fetch active announcements
try {
    $announcementsStmt = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC');
    $announcements = $announcementsStmt->fetchAll();
} catch (PDOException $e) {
    error_log('Announcements fetch failed: ' . $e->getMessage());
    $announcements = [];
}

// Flash messages
$successMsg = $_SESSION['success_msg'] ?? null;
$errorMsg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);


        return $this->render('admin/system/settings', get_defined_vars());
    }
    public function processUser(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response->redirect("/sia/admin/system/users.php");
    return;
}

$action = $_POST['action'] ?? '';



try {
    if ($action === 'create_user') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = trim($_POST['role'] ?? 'applicant');
        $department = trim($_POST['department'] ?? '');
        $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? json_encode($_POST['permissions']) : null;
        if ($department === '') $department = null;

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            throw new Exception('All fields are required to create a new user.');
        }

        $passErrors = [];
        if (!isPasswordStrong($password, $passErrors)) {
            throw new Exception(implode(' ', $passErrors));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format.');
        }

        if (!in_array($role, ['applicant', 'superadmin', 'admissions', 'scholarship', 'cashier'])) {
            throw new Exception('Invalid role specified.');
        }

        // Check if email exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            throw new Exception('A user with that email already exists.');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $insertStmt = $pdo->prepare('
            INSERT INTO users (first_name, last_name, email, password, role, department, permissions) 
            VALUES (:first, :last, :email, :pass, :role, :dept, :perms)
        ');
        $insertStmt->execute([
            'first' => $firstName,
            'last' => $lastName,
            'email' => $email,
            'pass' => $hashedPassword,
            'role' => $role,
            'dept' => $department,
            'perms' => $permissions
        ]);

        $newUserId = $pdo->lastInsertId();

        // Audit Log
        logActivity(
            (int)$_SESSION['user_id'], 
            'bi-person-plus-fill', 
            'User Created', 
            "Created a new $role account for $email (ID: $newUserId).",
            "User #$newUserId",
            null,
            ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email, 'role' => $role, 'department' => $department, 'permissions' => $permissions]
        );

        $_SESSION['success_msg'] = 'User account created successfully.';
    } 
    elseif ($action === 'update_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'applicant');
        $department = trim($_POST['department'] ?? '');
        $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? json_encode($_POST['permissions']) : null;
        if ($department === '') $department = null;
        $newPassword = $_POST['new_password'] ?? '';

        if ($userId <= 0 || $firstName === '' || $lastName === '' || $email === '') {
            throw new Exception('Missing required user information for update.');
        }

        // Check if email exists for another user
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $stmt->execute(['email' => $email, 'id' => $userId]);
        if ($stmt->fetch()) {
            throw new Exception('The requested email is already used by another account.');
        }

        $stmtOld = $pdo->prepare('SELECT first_name, last_name, email, role, department, permissions FROM users WHERE id = :id');
        $stmtOld->execute(['id' => $userId]);
        $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

        // Base update query
        $updateQuery = 'UPDATE users SET first_name = :first, last_name = :last, email = :email, role = :role, department = :dept, permissions = :perms';
        $params = [
            'first' => $firstName,
            'last' => $lastName,
            'email' => $email,
            'role' => $role,
            'dept' => $department,
            'perms' => $permissions,
            'id' => $userId
        ];

        $auditDesc = "Updated account details for user ID $userId.";

        // Update password if provided
        if ($newPassword !== '') {
            $passErrors = [];
            if (!isPasswordStrong($newPassword, $passErrors)) {
                throw new Exception(implode(' ', $passErrors));
            }
            $updateQuery .= ', password = :pass';
            $params['pass'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $auditDesc .= " (Password was reset).";
        }

        $updateQuery .= ' WHERE id = :id';

        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute($params);

        // Audit Log
        logActivity(
            (int)$_SESSION['user_id'], 
            'bi-person-gear', 
            'Permission Changed', 
            $auditDesc,
            "User #$userId",
            $oldData,
            ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email, 'role' => $role, 'department' => $department, 'permissions' => $permissions]
        );

        $_SESSION['success_msg'] = 'User account updated successfully.';
    } elseif ($action === 'toggle_status') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $currentStatus = (int)($_POST['current_status'] ?? 1);
        $newStatus = $currentStatus === 1 ? 0 : 1;

        if ($userId <= 0) {
            throw new Exception('Invalid user ID.');
        }

        // Prevent deactivating yourself
        if ($userId === (int)$_SESSION['user_id']) {
            throw new Exception('You cannot deactivate your own account.');
        }

        $stmt = $pdo->prepare('UPDATE users SET is_active = :status WHERE id = :id');
        $stmt->execute(['status' => $newStatus, 'id' => $userId]);

        $statusText = $newStatus === 1 ? 'Activated' : 'Deactivated';

        logActivity(
            (int)$_SESSION['user_id'], 
            $newStatus === 1 ? 'bi-unlock-fill' : 'bi-lock-fill', 
            "Account $statusText", 
            "User ID $userId was $statusText."
        );

        $_SESSION['success_msg'] = "User account $statusText successfully.";

    } elseif ($action === 'reset_password') {
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            throw new Exception('Invalid user ID.');
        }

        $newPassword = '@Admin123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('UPDATE users SET password = :pass WHERE id = :id');
        $stmt->execute(['pass' => $hashedPassword, 'id' => $userId]);

        logActivity(
            (int)$_SESSION['user_id'], 
            'bi-key-fill', 
            'Password Reset', 
            "Password for User ID $userId was reset to the default."
        );

        $_SESSION['success_msg'] = "Password reset successfully to default (@Admin123).";

    } else {
        throw new Exception('Invalid action requested.');
    }

} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
}

$response->redirect("/sia/admin/system/users.php");
return;

    }
    public function processBackup(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response->redirect("/sia/admin/system/backup.php");
    return;
}



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
        return;

    } catch (PDOException $e) {
        error_log('Export failed: ' . $e->getMessage());
        $_SESSION['error_msg'] = 'Failed to generate database backup. Please try again.';
        $response->redirect("/sia/admin/system/backup.php");
        return;
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

    $response->redirect("/sia/admin/system/backup.php");
    return;
} else {
    $_SESSION['error_msg'] = 'Invalid action requested.';
    $response->redirect("/sia/admin/system/backup.php");
    return;
}

    }
    public function processSettings(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response->redirect("/sia/admin/system/settings.php");
    return;
}



$action = $_POST['action'] ?? '';

try {
    if ($action === 'update_settings') {
        // Save global configurations
        $activeSchoolYear = trim($_POST['active_school_year'] ?? '');
        $enrollmentStatus = trim($_POST['enrollment_status'] ?? 'open');
        
        if ($activeSchoolYear === '') {
            throw new Exception('Active school year cannot be empty.');
        }

        // Fetch old settings
        $oldSettingsList = getSystemSettings($pdo, ['active_school_year', 'enrollment_status', 'college_cost_per_unit']);

        // Upsert syntax
        $stmt = $pdo->prepare('
            INSERT INTO system_settings (setting_key, setting_value) 
            VALUES (:key, :val) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ');
        
        $stmt->execute(['key' => 'active_school_year', 'val' => $activeSchoolYear]);
        $stmt->execute(['key' => 'enrollment_status', 'val' => $enrollmentStatus]);

        $newSettings = [
            'active_school_year' => $activeSchoolYear,
            'enrollment_status' => $enrollmentStatus
        ];

        if (isset($_POST['college_cost_per_unit'])) {
            $costPerUnitFloat = (float)$_POST['college_cost_per_unit'];
            if ($costPerUnitFloat < 0) {
                throw new Exception('College cost per unit cannot be negative.');
            }
            $costPerUnit = number_format($costPerUnitFloat, 2, '.', '');
            $stmt->execute(['key' => 'college_cost_per_unit', 'val' => $costPerUnit]);
            $newSettings['college_cost_per_unit'] = $costPerUnit;
        }


        logActivity(
            (int)$_SESSION['user_id'], 
            'bi-sliders', 
            'System Settings Updated', 
            'Global system settings were updated.',
            'System Settings',
            $oldSettingsList,
            $newSettings
        );

        $_SESSION['success_msg'] = 'Global settings updated successfully.';
    } 
    elseif ($action === 'add_announcement') {
        // Create a new announcement post
        $badgeLabel = trim($_POST['badge_label'] ?? '');
        $badgeColor = trim($_POST['badge_color'] ?? 'primary');
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($title === '' || $content === '') {
            throw new Exception('Title and content are required for announcements.');
        }

        $stmt = $pdo->prepare('
            INSERT INTO announcements (badge_label, badge_color, title, content, is_active) 
            VALUES (:label, :color, :title, :content, 1)
        ');
        
        $stmt->execute([
            'label' => $badgeLabel,
            'color' => $badgeColor,
            'title' => $title,
            'content' => $content
        ]);

        $_SESSION['success_msg'] = 'Announcement posted successfully.';
    }
    elseif ($action === 'toggle_announcement') {
        // Toggle active status
        $id = (int) ($_POST['id'] ?? 0);
        $status = (int) ($_POST['status'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE announcements SET is_active = :status WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $id]);
            $_SESSION['success_msg'] = 'Announcement visibility updated.';
        }
    } 
    else {
        throw new Exception('Invalid action requested.');
    }

} catch (Exception $e) {
    $_SESSION['error_msg'] = $e->getMessage();
}

$response->redirect("/sia/admin/system/settings.php");
return;

    }
}



