<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class LmsAnnouncementService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function getCourseAnnouncements(int $lmsCourseId, bool $publishedOnly = true): array
    {
        $sql = "
            SELECT a.*, u.first_name, u.last_name 
            FROM lms_announcements a
            JOIN users u ON a.author_user_id = u.id
            WHERE a.lms_course_id = :lcid
        ";
        
        if ($publishedOnly) {
            $sql .= " AND a.status = 'published' AND (a.expires_at IS NULL OR a.expires_at > CURRENT_TIMESTAMP)";
        }
        
        $sql .= " ORDER BY COALESCE(a.published_at, a.created_at) DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['lcid' => $lmsCourseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAnnouncement(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lms_announcements WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
        return $announcement ?: null;
    }

    public function createAnnouncement(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO lms_announcements (lms_course_id, author_user_id, title, content, status, published_at, expires_at)
            VALUES (:course, :author, :title, :content, :status, :pub, :exp)
        ");
        
        $isPublished = ($data['status'] ?? 'draft') === 'published';
        
        $stmt->execute([
            'course' => $data['lms_course_id'],
            'author' => $data['author_user_id'],
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $data['status'] ?? 'draft',
            'pub' => $isPublished ? date('Y-m-d H:i:s') : null,
            'exp' => !empty($data['expires_at']) ? $data['expires_at'] : null
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateAnnouncement(int $id, array $data): bool
    {
        $current = $this->getAnnouncement($id);
        if (!$current) return false;

        $isPublishedNow = ($data['status'] ?? 'draft') === 'published';
        $wasPublishedBefore = $current['status'] === 'published';
        
        $publishedAt = $current['published_at'];
        if ($isPublishedNow && !$wasPublishedBefore) {
            $publishedAt = date('Y-m-d H:i:s');
        } elseif (!$isPublishedNow) {
            $publishedAt = null;
        }

        $stmt = $this->pdo->prepare("
            UPDATE lms_announcements 
            SET title = :title, content = :content, status = :status, published_at = :pub, expires_at = :exp
            WHERE id = :id
        ");
        
        return $stmt->execute([
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $data['status'] ?? 'draft',
            'pub' => $publishedAt,
            'exp' => !empty($data['expires_at']) ? $data['expires_at'] : null,
            'id' => $id
        ]);
    }

    public function deleteAnnouncement(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM lms_announcements WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
