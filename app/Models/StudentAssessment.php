<?php

namespace App\Models;

use App\Core\Database;

class StudentAssessment
{
    public static function findByApplicationId(int $appId)
    {
        $pdo = Database::getConnection();
        $assStmt = $pdo->prepare('SELECT id, payment_status FROM student_assessments WHERE application_id = :app_id LIMIT 1');
        $assStmt->execute(['app_id' => $appId]);
        return $assStmt->fetch() ?: null;
    }
}
