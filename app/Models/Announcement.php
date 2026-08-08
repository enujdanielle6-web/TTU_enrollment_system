<?php

namespace App\Models;

use App\Core\Database;

class Announcement
{
    public static function getActiveAnnouncements(): array
    {
        $pdo = Database::getConnection();
        $annStmt = $pdo->query('SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC');
        return $annStmt->fetchAll();
    }
}
