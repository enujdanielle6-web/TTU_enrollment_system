<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requirePermission('*');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit;
}

verifyCsrfToken();

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
            $costPerUnit = number_format((float)$_POST['college_cost_per_unit'], 2, '.', '');
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

header('Location: settings.php');
exit;

