<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findByEmail(string $email)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT id, first_name, last_name, email, password, role, is_active, department, permissions
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public static function recordFailedAttempt(string $ipAddress, string $email): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO login_attempts (ip_address, email) VALUES (:ip, :email)');
        $stmt->execute(['ip' => $ipAddress, 'email' => $email]);
    }

    public static function clearFailedAttempts(string $ipAddress, string $email): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = :ip OR email = :email');
        $stmt->execute(['ip' => $ipAddress, 'email' => $email]);
    }

    public static function pruneStaleAttempts(): void
    {
        $pdo = Database::getConnection();
        $pdo->exec('DELETE FROM login_attempts WHERE attempt_time < NOW() - INTERVAL 24 HOUR');
    }

    public static function updateLastLogin(int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
        $stmt->execute(['id' => $userId]);
    }

    public static function logActivity(int $userId, string $title, string $description, string $icon): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO activity_logs (user_id, title, description, icon) VALUES (:uid, :title, :desc, :icon)');
        $stmt->execute([
            'uid' => $userId,
            'title' => $title,
            'desc' => $description,
            'icon' => $icon
        ]);
    }

    public static function pruneStaleApplicants(): void
    {
        $pdo = Database::getConnection();
        $pdo->exec(
            "UPDATE users 
             SET is_active = 0 
             WHERE role = 'applicant' 
               AND is_active = 1 
               AND created_at < NOW() - INTERVAL 3 DAY 
               AND id NOT IN (SELECT user_id FROM applications)"
        );
    }
}
