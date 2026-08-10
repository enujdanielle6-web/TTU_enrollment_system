<?php
require_once __DIR__ . '/config/database.php';

$idsToKeep = [6, 10, 11, 18];

try {
    $pdo->beginTransaction();

    // Find users to delete
    $placeholders = implode(',', array_fill(0, count($idsToKeep), '?'));
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'applicant' AND id NOT IN ($placeholders)");
    $stmt->execute($idsToKeep);
    $usersToDelete = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($usersToDelete)) {
        echo "No applicant accounts found to delete.\n";
    } else {
        $delIds = implode(',', $usersToDelete);
        echo "Deleting applicant IDs: $delIds\n";

        // Try deleting applications first to avoid FK constraints
        $pdo->exec("DELETE FROM application_documents WHERE application_id IN (SELECT id FROM applications WHERE user_id IN ($delIds))");
        $pdo->exec("DELETE FROM college_enrollments WHERE application_id IN (SELECT id FROM applications WHERE user_id IN ($delIds))");
        $pdo->exec("DELETE FROM applications WHERE user_id IN ($delIds)");
        
        // Delete the users
        $pdo->exec("DELETE FROM users WHERE id IN ($delIds)");
        
        echo "Successfully deleted " . count($usersToDelete) . " applicant accounts and their associated applications.\n";
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
