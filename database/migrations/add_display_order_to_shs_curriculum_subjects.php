<?php
/**
 * Migration: Add display_order column to shs_curriculum_subjects
 */
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/Database.php';

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Checking shs_curriculum_subjects for display_order column...\n";

    $cols = $pdo->query("SHOW COLUMNS FROM `shs_curriculum_subjects` LIKE 'display_order'")->fetchAll();
    if (empty($cols)) {
        echo "Adding display_order column...\n";
        $pdo->exec("ALTER TABLE `shs_curriculum_subjects` ADD COLUMN `display_order` INT(11) NOT NULL DEFAULT 0 AFTER `semester`");
        
        // Populate sequential display_order per curriculum, grade_level, semester
        $stmt = $pdo->query("
            SELECT id, curriculum_id, grade_level, semester 
            FROM `shs_curriculum_subjects` 
            ORDER BY curriculum_id, grade_level, semester, id
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $orderMap = [];
        $updateStmt = $pdo->prepare("UPDATE `shs_curriculum_subjects` SET `display_order` = ? WHERE `id` = ?");
        foreach ($rows as $r) {
            $key = $r['curriculum_id'] . '_' . $r['grade_level'] . '_' . $r['semester'];
            if (!isset($orderMap[$key])) {
                $orderMap[$key] = 1;
            } else {
                $orderMap[$key]++;
            }
            $updateStmt->execute([$orderMap[$key], $r['id']]);
        }
        echo "Successfully added display_order and populated sequence.\n";
    } else {
        echo "Column display_order already exists on shs_curriculum_subjects.\n";
    }
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
