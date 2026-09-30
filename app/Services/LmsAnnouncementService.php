<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class LmsAnnouncementService
{
    private PDO $pdo;

    public function __construct()
    {
        date_default_timezone_set('Asia/Manila');
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

    // =========================================================================
    // PLATFORM-WIDE LMS ANNOUNCEMENTS (LMS ADMIN GOVERNANCE)
    // =========================================================================

    /**
     * Retrieves active platform-wide announcements for target audience (students, faculty, or all).
     * Strictly filters for published, unexpired notices where lms_course_id IS NULL.
     */
    public function getPlatformAnnouncements(?string $audience = null, bool $activeOnly = true): array
    {
        $sql = "
            SELECT a.*, u.first_name, u.last_name, u.email as author_email
            FROM lms_announcements a
            JOIN users u ON a.author_user_id = u.id
            WHERE a.lms_course_id IS NULL
        ";
        $params = [];

        if ($activeOnly) {
            $sql .= " AND a.status = 'published'
                      AND (a.published_at IS NULL OR a.published_at <= CURRENT_TIMESTAMP)
                      AND (a.expires_at IS NULL OR a.expires_at > CURRENT_TIMESTAMP)";
        }

        if ($audience !== null && in_array($audience, ['students', 'faculty'], true)) {
            $sql .= " AND (a.target_audience = 'all' OR a.target_audience = :audience)";
            $params['audience'] = $audience;
        }

        $sql .= " ORDER BY COALESCE(a.published_at, a.created_at) DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all platform announcements with optional filtering for LMS Admin management table.
     */
    public function getAllPlatformAnnouncements(array $filters = []): array
    {
        $sql = "
            SELECT a.*, u.first_name, u.last_name, u.email as author_email
            FROM lms_announcements a
            JOIN users u ON a.author_user_id = u.id
            WHERE a.lms_course_id IS NULL
        ";
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], ['draft', 'published'], true)) {
            $sql .= " AND a.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['audience']) && in_array($filters['audience'], ['all', 'students', 'faculty'], true)) {
            $sql .= " AND a.target_audience = :audience";
            $params['audience'] = $filters['audience'];
        }

        if (!empty($filters['severity']) && in_array($filters['severity'], ['info', 'warning', 'danger', 'success'], true)) {
            $sql .= " AND a.severity = :severity";
            $params['severity'] = $filters['severity'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (a.title LIKE :search OR a.content LIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= " ORDER BY COALESCE(a.published_at, a.created_at) DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich with operational lifecycle state
        $now = date('Y-m-d H:i:s');
        foreach ($results as &$item) {
            if ($item['status'] === 'draft') {
                $item['lifecycle'] = 'draft';
            } elseif (!empty($item['expires_at']) && $item['expires_at'] < $now) {
                $item['lifecycle'] = 'expired';
            } elseif (!empty($item['published_at']) && $item['published_at'] > $now) {
                $item['lifecycle'] = 'scheduled';
            } else {
                $item['lifecycle'] = 'active';
            }
        }
        unset($item);

        return $results;
    }

    /**
     * Creates a new platform-wide LMS announcement with full validation, sanitization, and audit trail.
     */
    public function createPlatformAnnouncement(array $data, int $adminUserId): int
    {
        $title = trim(strip_tags($data['title'] ?? ''));
        $content = trim(strip_tags($data['content'] ?? ''));
        $targetAudience = in_array($data['target_audience'] ?? '', ['all', 'students', 'faculty'], true) ? $data['target_audience'] : 'all';
        $severity = in_array($data['severity'] ?? '', ['info', 'warning', 'danger', 'success'], true) ? $data['severity'] : 'info';
        $status = ($data['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

        if (mb_strlen($title) < 3) {
            throw new \InvalidArgumentException("Announcement title must be at least 3 characters long.");
        }
        if (mb_strlen($content) < 5) {
            throw new \InvalidArgumentException("Announcement message must be at least 5 characters long.");
        }

        $publishedAt = null;
        if (!empty($data['published_at'])) {
            $timestamp = strtotime($data['published_at']);
            if ($timestamp !== false) {
                $publishedAt = date('Y-m-d H:i:s', $timestamp);
            }
        } elseif ($status === 'published') {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $expiresAt = null;
        if (!empty($data['expires_at'])) {
            $expTimestamp = strtotime($data['expires_at']);
            if ($expTimestamp !== false) {
                $expiresAt = date('Y-m-d H:i:s', $expTimestamp);
                if ($publishedAt && $expiresAt <= $publishedAt) {
                    throw new \InvalidArgumentException("Expiration date must be strictly after the start / publication date.");
                }
            }
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO lms_announcements (
                lms_course_id, author_user_id, title, content, target_audience, severity, status, published_at, expires_at
            ) VALUES (
                NULL, :author, :title, :content, :audience, :severity, :status, :pub, :exp
            )
        ");

        $stmt->execute([
            'author' => $adminUserId,
            'title' => $title,
            'content' => $content,
            'audience' => $targetAudience,
            'severity' => $severity,
            'status' => $status,
            'pub' => $publishedAt,
            'exp' => $expiresAt
        ]);

        $announcementId = (int)$this->pdo->lastInsertId();

        // Audit Logging
        if (function_exists('logActivity')) {
            logActivity(
                $adminUserId,
                'bi-megaphone-fill',
                'LMS Platform Announcement: Created',
                "Created platform announcement #{$announcementId}: '{$title}' targeted to '{$targetAudience}' with severity '{$severity}' (Status: {$status}).",
                'lms_announcements',
                null,
                [
                    'id' => $announcementId,
                    'title' => $title,
                    'target_audience' => $targetAudience,
                    'severity' => $severity,
                    'status' => $status,
                    'published_at' => $publishedAt,
                    'expires_at' => $expiresAt
                ],
                'Platform Announcement Management'
            );
        }

        return $announcementId;
    }

    /**
     * Updates an existing platform announcement. Preserves course announcement boundary.
     */
    public function updatePlatformAnnouncement(int $id, array $data, int $adminUserId): bool
    {
        $current = $this->getAnnouncement($id);
        if (!$current || !is_null($current['lms_course_id'])) {
            throw new \InvalidArgumentException("Target announcement #{$id} is not a valid platform-level notice or does not exist.");
        }

        $title = trim(strip_tags($data['title'] ?? ''));
        $content = trim(strip_tags($data['content'] ?? ''));
        $targetAudience = in_array($data['target_audience'] ?? '', ['all', 'students', 'faculty'], true) ? $data['target_audience'] : 'all';
        $severity = in_array($data['severity'] ?? '', ['info', 'warning', 'danger', 'success'], true) ? $data['severity'] : 'info';
        $status = ($data['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

        if (mb_strlen($title) < 3) {
            throw new \InvalidArgumentException("Announcement title must be at least 3 characters long.");
        }
        if (mb_strlen($content) < 5) {
            throw new \InvalidArgumentException("Announcement message must be at least 5 characters long.");
        }

        $publishedAt = $current['published_at'];
        if (!empty($data['published_at'])) {
            $timestamp = strtotime($data['published_at']);
            if ($timestamp !== false) {
                $publishedAt = date('Y-m-d H:i:s', $timestamp);
            }
        } elseif ($status === 'published' && empty($publishedAt)) {
            $publishedAt = date('Y-m-d H:i:s');
        } elseif ($status === 'draft') {
            // Keep existing or clear
        }

        $expiresAt = null;
        if (!empty($data['expires_at'])) {
            $expTimestamp = strtotime($data['expires_at']);
            if ($expTimestamp !== false) {
                $expiresAt = date('Y-m-d H:i:s', $expTimestamp);
                if ($publishedAt && $expiresAt <= $publishedAt) {
                    throw new \InvalidArgumentException("Expiration date must be strictly after the start / publication date.");
                }
            }
        }

        $stmt = $this->pdo->prepare("
            UPDATE lms_announcements 
            SET title = :title, 
                content = :content, 
                target_audience = :audience, 
                severity = :severity, 
                status = :status, 
                published_at = :pub, 
                expires_at = :exp
            WHERE id = :id AND lms_course_id IS NULL
        ");

        $success = $stmt->execute([
            'title' => $title,
            'content' => $content,
            'audience' => $targetAudience,
            'severity' => $severity,
            'status' => $status,
            'pub' => $publishedAt,
            'exp' => $expiresAt,
            'id' => $id
        ]);

        if ($success && function_exists('logActivity')) {
            logActivity(
                $adminUserId,
                'bi-pencil-square',
                'LMS Platform Announcement: Updated',
                "Updated platform announcement #{$id}: '{$title}' (Status: {$status}, Audience: {$targetAudience}, Severity: {$severity}).",
                'lms_announcements',
                $current,
                [
                    'id' => $id,
                    'title' => $title,
                    'target_audience' => $targetAudience,
                    'severity' => $severity,
                    'status' => $status,
                    'published_at' => $publishedAt,
                    'expires_at' => $expiresAt
                ],
                'Platform Announcement Update'
            );
        }

        return $success;
    }

    /**
     * Toggles an announcement status between 'draft' and 'published'.
     */
    public function togglePlatformAnnouncementStatus(int $id, int $adminUserId): array
    {
        $current = $this->getAnnouncement($id);
        if (!$current || !is_null($current['lms_course_id'])) {
            throw new \InvalidArgumentException("Announcement #{$id} is not a valid platform-level notice.");
        }

        $newStatus = ($current['status'] === 'published') ? 'draft' : 'published';
        $publishedAt = $current['published_at'];
        if ($newStatus === 'published' && empty($publishedAt)) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $stmt = $this->pdo->prepare("
            UPDATE lms_announcements 
            SET status = :status, published_at = :pub 
            WHERE id = :id AND lms_course_id IS NULL
        ");
        $stmt->execute([
            'status' => $newStatus,
            'pub' => $publishedAt,
            'id' => $id
        ]);

        if (function_exists('logActivity')) {
            logActivity(
                $adminUserId,
                'bi-toggle-on',
                'LMS Platform Announcement: Status Toggled',
                "Toggled status of platform announcement #{$id} from '{$current['status']}' to '{$newStatus}'.",
                'lms_announcements',
                ['status' => $current['status']],
                ['status' => $newStatus],
                'Platform Announcement Status Toggle'
            );
        }

        return ['success' => true, 'new_status' => $newStatus];
    }

    /**
     * Deletes a platform announcement. Strictly preserves course-scoped announcements.
     */
    public function deletePlatformAnnouncement(int $id, int $adminUserId): bool
    {
        $current = $this->getAnnouncement($id);
        if (!$current || !is_null($current['lms_course_id'])) {
            throw new \InvalidArgumentException("Target announcement #{$id} is not a platform-level notice or does not exist.");
        }

        $stmt = $this->pdo->prepare("DELETE FROM lms_announcements WHERE id = :id AND lms_course_id IS NULL");
        $success = $stmt->execute(['id' => $id]);

        if ($success && function_exists('logActivity')) {
            logActivity(
                $adminUserId,
                'bi-trash-fill',
                'LMS Platform Announcement: Deleted',
                "Deleted platform announcement #{$id}: '{$current['title']}'.",
                'lms_announcements',
                $current,
                null,
                'Platform Announcement Removal'
            );
        }

        return $success;
    }
}
