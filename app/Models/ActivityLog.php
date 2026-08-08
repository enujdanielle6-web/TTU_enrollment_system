<?php

namespace App\Models;

use App\Core\Database;

class ActivityLog
{
    public static function findByUserId(int $userId): array
    {
        $pdo = Database::getConnection();
        $actStmt = $pdo->prepare('SELECT * FROM activity_logs WHERE user_id = :user_id ORDER BY created_at DESC');
        $actStmt->execute(['user_id' => $userId]);
        return $actStmt->fetchAll();
    }
}
