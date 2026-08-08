<?php

namespace App\Models;

use App\Core\Database;

class ScholarshipApplication
{
    public static function getStatus(int $userId): ?string
    {
        $pdo = Database::getConnection();
        $scholAppStmt = $pdo->prepare('SELECT status FROM scholarship_applications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1');
        $scholAppStmt->execute(['user_id' => $userId]);
        $status = $scholAppStmt->fetchColumn();
        return $status !== false ? (string)$status : null;
    }
}
