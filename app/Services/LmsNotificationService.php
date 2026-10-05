<?php
namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * LMS bell notifications and badge counts.
 *
 * Students are notified of published announcements, assignments and quizzes in their
 * courses; faculty of student submissions (their own announcements are listed too, but
 * never count as new). Opening a notification stores a row in lms_notification_reads,
 * so the bell, the "New" count and the course tab badges only count what is unread or
 * still to do.
 */
class LmsNotificationService
{
    public const STUDENT_TYPES = ['announcement', 'assignment', 'quiz'];
    public const FACULTY_TYPES = ['submission'];

    private PDO $pdo;
    private LmsService $lmsService;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->lmsService = new LmsService();
    }

    /**
     * @return array{items: array<int, array>, unread: int}
     */
    public function getStudentFeed(int $studentId, int $limit = 8): array
    {
        $items = $this->lmsService->getStudentProfessorUpdates($studentId, $limit);
        return $this->withReadState($studentId, $items, self::STUDENT_TYPES);
    }

    /**
     * @return array{items: array<int, array>, unread: int}
     */
    public function getFacultyFeed(int $facultyId, int $limit = 8): array
    {
        $items = $this->lmsService->getFacultyRecentActivity($facultyId, $limit);
        return $this->withReadState($facultyId, $items, self::FACULTY_TYPES);
    }

    /**
     * Marks one notification as read after checking it is one this user can see.
     */
    public function markRead(int $userId, string $role, string $type, int $itemId): bool
    {
        if (!$this->canSee($userId, $role, $type, $itemId)) {
            return false;
        }
        $this->insertReads($userId, [[$type, $itemId]]);
        return true;
    }

    /**
     * Marks every notification currently in the user's bell as read.
     */
    public function markAllRead(int $userId, string $role): void
    {
        $feed = $role === 'faculty' ? $this->getFacultyFeed($userId) : $this->getStudentFeed($userId);
        $pairs = [];
        foreach ($feed['items'] as $item) {
            if (empty($item['is_read'])) {
                $pairs[] = [$item['type'], (int)$item['id']];
            }
        }
        $this->insertReads($userId, $pairs);
    }

    /**
     * Opening a course's Announcements tab counts as reading all of them.
     */
    public function markCourseAnnouncementsRead(int $studentId, int $lmsCourseId): void
    {
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO lms_notification_reads (user_id, item_type, item_id, read_at)
            SELECT :uid, 'announcement', ann.id, NOW()
            FROM lms_announcements ann
            WHERE ann.lms_course_id = :lcid AND ann.status = 'published'
        ");
        $stmt->execute(['uid' => $studentId, 'lcid' => $lmsCourseId]);
    }

    /**
     * Student course tab badges: unread announcements, assignments not yet submitted,
     * and open quizzes not yet taken.
     *
     * @return array{announcements: int, assignments: int, quizzes: int}
     */
    public function getStudentCourseBadges(int $studentId, int $lmsCourseId): array
    {
        $params = ['sid' => $studentId, 'lcid' => $lmsCourseId];

        $ann = $this->pdo->prepare("
            SELECT COUNT(*) FROM lms_announcements ann
            LEFT JOIN lms_notification_reads r
                   ON r.user_id = :sid AND r.item_type = 'announcement' AND r.item_id = ann.id
            WHERE ann.lms_course_id = :lcid AND ann.status = 'published' AND r.id IS NULL
        ");
        $ann->execute($params);

        $asg = $this->pdo->prepare("
            SELECT COUNT(*) FROM lms_assignments a
            WHERE a.lms_course_id = :lcid AND a.status = 'published'
              AND NOT EXISTS (
                  SELECT 1 FROM lms_submissions sub
                  WHERE sub.assignment_id = a.id AND sub.student_id = :sid
              )
        ");
        $asg->execute($params);

        $quiz = $this->pdo->prepare("
            SELECT COUNT(*) FROM lms_quizzes q
            WHERE q.lms_course_id = :lcid AND q.status = 'published'
              AND (q.end_date IS NULL OR q.end_date >= NOW())
              AND NOT EXISTS (
                  SELECT 1 FROM lms_quiz_attempts att
                  WHERE att.lms_quiz_id = q.id AND att.student_id = :sid
                    AND att.status IN ('submitted', 'graded')
              )
        ");
        $quiz->execute($params);

        return [
            'announcements' => (int)$ann->fetchColumn(),
            'assignments' => (int)$asg->fetchColumn(),
            'quizzes' => (int)$quiz->fetchColumn(),
        ];
    }

    /**
     * Faculty course tab badges: submissions and quiz attempts waiting to be graded.
     * Announcements are the teacher's own posts, so they get no badge.
     *
     * @return array{announcements: int, assignments: int, quizzes: int}
     */
    public function getFacultyCourseBadges(int $lmsCourseId): array
    {
        $asg = $this->pdo->prepare("
            SELECT COUNT(*) FROM lms_submissions sub
            JOIN lms_assignments a ON a.id = sub.assignment_id
            WHERE a.lms_course_id = :lcid AND sub.status <> 'GRADED'
        ");
        $asg->execute(['lcid' => $lmsCourseId]);

        $quiz = $this->pdo->prepare("
            SELECT COUNT(*) FROM lms_quiz_attempts att
            JOIN lms_quizzes q ON q.id = att.lms_quiz_id
            WHERE q.lms_course_id = :lcid AND att.status = 'submitted'
        ");
        $quiz->execute(['lcid' => $lmsCourseId]);

        return [
            'announcements' => 0,
            'assignments' => (int)$asg->fetchColumn(),
            'quizzes' => (int)$quiz->fetchColumn(),
        ];
    }

    /**
     * @param array<int, array> $items
     * @param string[] $trackedTypes types that can be unread; anything else is always read
     * @return array{items: array<int, array>, unread: int}
     */
    private function withReadState(int $userId, array $items, array $trackedTypes): array
    {
        $readKeys = $this->readKeys($userId, $items);
        $unread = 0;
        foreach ($items as &$item) {
            $type = (string)($item['type'] ?? '');
            $isRead = !in_array($type, $trackedTypes, true) || isset($readKeys[$type . ':' . (int)$item['id']]);
            $item['is_read'] = $isRead;
            if (!$isRead) {
                $unread++;
            }
        }
        unset($item);
        return ['items' => $items, 'unread' => $unread];
    }

    /** @return array<string, true> keys like "quiz:4" */
    private function readKeys(int $userId, array $items): array
    {
        if (empty($items)) {
            return [];
        }
        try {
            $stmt = $this->pdo->prepare("SELECT item_type, item_id FROM lms_notification_reads WHERE user_id = ?");
            $stmt->execute([$userId]);
        } catch (\PDOException $e) {
            // Table not created yet (phase 13 migration): show everything as new.
            error_log('lms_notification_reads unavailable: ' . $e->getMessage());
            return [];
        }
        $keys = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $keys[$row['item_type'] . ':' . (int)$row['item_id']] = true;
        }
        return $keys;
    }

    private function canSee(int $userId, string $role, string $type, int $itemId): bool
    {
        if ($role === 'faculty') {
            if ($type !== 'submission') {
                return false;
            }
            $stmt = $this->pdo->prepare("
                SELECT 1 FROM lms_submissions sub
                JOIN lms_assignments a ON a.id = sub.assignment_id
                JOIN lms_courses lc ON lc.id = a.lms_course_id
                WHERE sub.id = :id AND lc.faculty_user_id = :uid
            ");
            $stmt->execute(['id' => $itemId, 'uid' => $userId]);
            return (bool)$stmt->fetchColumn();
        }

        $tables = ['announcement' => 'lms_announcements', 'assignment' => 'lms_assignments', 'quiz' => 'lms_quizzes'];
        if (!isset($tables[$type])) {
            return false;
        }
        $stmt = $this->pdo->prepare("SELECT lms_course_id FROM {$tables[$type]} WHERE id = :id AND status = 'published'");
        $stmt->execute(['id' => $itemId]);
        $courseId = $stmt->fetchColumn();
        return $courseId !== false && $this->lmsService->isStudentAuthorizedForCourse($userId, (int)$courseId);
    }

    /** @param array<int, array{0: string, 1: int}> $pairs */
    private function insertReads(int $userId, array $pairs): void
    {
        if (empty($pairs)) {
            return;
        }
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO lms_notification_reads (user_id, item_type, item_id, read_at)
            VALUES (:uid, :type, :id, NOW())
        ");
        foreach ($pairs as [$type, $id]) {
            $stmt->execute(['uid' => $userId, 'type' => $type, 'id' => $id]);
        }
    }
}
