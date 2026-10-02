<?php
namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class LmsMessageService
{
    private PDO $pdo;

    public function __construct()
    {
        date_default_timezone_set('Asia/Manila');
        $this->pdo = Database::getConnection();
    }

    /**
     * Get all conversation threads for a user ordered by most recent activity.
     */
    public function getUserThreads(int $userId): array
    {
        $sql = "
            SELECT 
                t.id,
                t.lms_course_id,
                t.subject,
                t.created_by,
                t.last_message_at,
                t.created_at,
                tp.last_read_at,
                s.subject_code,
                s.subject_name,
                COALESCE(cs.section_code, ss.section_code) AS section_code,
                (
                    SELECT m.body 
                    FROM lms_messages m 
                    WHERE m.thread_id = t.id 
                    ORDER BY m.id DESC LIMIT 1
                ) AS last_message_body,
                (
                    SELECT m.sender_id 
                    FROM lms_messages m 
                    WHERE m.thread_id = t.id 
                    ORDER BY m.id DESC LIMIT 1
                ) AS last_sender_id,
                (
                    SELECT COUNT(m.id)
                    FROM lms_messages m
                    WHERE m.thread_id = t.id
                      AND m.sender_id != :uid1
                      AND (tp.last_read_at IS NULL OR m.created_at > tp.last_read_at)
                ) AS unread_count
            FROM lms_threads t
            JOIN lms_thread_participants tp ON t.id = tp.thread_id AND tp.user_id = :uid2
            LEFT JOIN lms_courses lc ON t.lms_course_id = lc.id
            LEFT JOIN subjects s ON lc.subject_id = s.id
            LEFT JOIN college_sections cs ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id
            LEFT JOIN shs_sections ss ON lc.academic_level = 'Senior High School' AND lc.academic_section_id = ss.id
            ORDER BY t.last_message_at DESC, t.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid1' => $userId, 'uid2' => $userId]);
        $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch other participants for each thread
        foreach ($threads as &$thread) {
            $thread['other_participants'] = $this->getOtherParticipants((int)$thread['id'], $userId);
        }

        return $threads;
    }

    /**
     * Get the participants of a thread excluding the current user.
     */
    public function getOtherParticipants(int $threadId, int $currentUserId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.role
            FROM lms_thread_participants tp
            JOIN users u ON tp.user_id = u.id
            WHERE tp.thread_id = :tid AND tp.user_id != :uid
        ");
        $stmt->execute(['tid' => $threadId, 'uid' => $currentUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a user is a participant in a given thread.
     */
    public function isParticipant(int $threadId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM lms_thread_participants 
            WHERE thread_id = :tid AND user_id = :uid
            LIMIT 1
        ");
        $stmt->execute(['tid' => $threadId, 'uid' => $userId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Get thread details if the user is authorized.
     */
    public function getThread(int $threadId, int $userId): ?array
    {
        if (!$this->isParticipant($threadId, $userId)) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT 
                t.*,
                s.subject_code,
                s.subject_name,
                COALESCE(cs.section_code, ss.section_code) AS section_code
            FROM lms_threads t
            LEFT JOIN lms_courses lc ON t.lms_course_id = lc.id
            LEFT JOIN subjects s ON lc.subject_id = s.id
            LEFT JOIN college_sections cs ON lc.academic_level = 'College' AND lc.academic_section_id = cs.id
            LEFT JOIN shs_sections ss ON lc.academic_level = 'Senior High School' AND lc.academic_section_id = ss.id
            WHERE t.id = :tid
        ");
        $stmt->execute(['tid' => $threadId]);
        $thread = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($thread) {
            $thread['other_participants'] = $this->getOtherParticipants($threadId, $userId);
        }

        return $thread ?: null;
    }

    /**
     * Fetch all messages in a thread and mark thread as read for the user.
     */
    public function getThreadMessages(int $threadId, int $userId): array
    {
        if (!$this->isParticipant($threadId, $userId)) {
            return [];
        }

        // Mark as read
        $this->markAsRead($threadId, $userId);

        $stmt = $this->pdo->prepare("
            SELECT 
                m.id,
                m.thread_id,
                m.sender_id,
                m.body,
                m.created_at,
                u.first_name,
                u.last_name,
                u.email,
                u.role
            FROM lms_messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.thread_id = :tid
            ORDER BY m.id ASC
        ");
        $stmt->execute(['tid' => $threadId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($messages as &$msg) {
            $msg['is_mine'] = ((int)$msg['sender_id'] === $userId);
            $msg['initials'] = strtoupper(substr($msg['first_name'] ?? 'U', 0, 1));
            $msg['formatted_time'] = date('M j, g:i A', strtotime($msg['created_at']));
        }

        return $messages;
    }

    /**
     * Update participant's last_read_at timestamp.
     */
    public function markAsRead(int $threadId, int $userId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE lms_thread_participants 
            SET last_read_at = NOW() 
            WHERE thread_id = :tid AND user_id = :uid
        ");
        $stmt->execute(['tid' => $threadId, 'uid' => $userId]);
    }

    /**
     * Create a new message thread atomically.
     */
    public function createThread(
        int $creatorUserId,
        array $recipientUserIds,
        string $subject,
        string $body,
        ?int $lmsCourseId = null
    ): int {
        $subject = trim($subject);
        $body = trim($body);

        if (empty($subject)) {
            $subject = 'Academic Consultation';
        }
        if (empty($body)) {
            throw new Exception("Message content cannot be empty.");
        }
        if (empty($recipientUserIds)) {
            throw new Exception("At least one recipient is required.");
        }

        $this->pdo->beginTransaction();
        try {
            // 1. Insert thread
            $stmt = $this->pdo->prepare("
                INSERT INTO lms_threads (lms_course_id, subject, created_by, last_message_at)
                VALUES (:course_id, :subject, :creator, NOW())
            ");
            $stmt->execute([
                'course_id' => $lmsCourseId ?: null,
                'subject' => $subject,
                'creator' => $creatorUserId
            ]);
            $threadId = (int)$this->pdo->lastInsertId();

            // 2. Insert creator into participants
            $stmt = $this->pdo->prepare("
                INSERT INTO lms_thread_participants (thread_id, user_id, last_read_at)
                VALUES (:tid, :uid, NOW())
            ");
            $stmt->execute(['tid' => $threadId, 'uid' => $creatorUserId]);

            // 3. Insert recipients into participants (unique list, excluding creator)
            $uniqueRecipients = array_unique(array_filter(array_map('intval', $recipientUserIds)));
            $stmtRecipient = $this->pdo->prepare("
                INSERT IGNORE INTO lms_thread_participants (thread_id, user_id, last_read_at)
                VALUES (:tid, :uid, NULL)
            ");

            foreach ($uniqueRecipients as $recipId) {
                if ($recipId !== $creatorUserId) {
                    $stmtRecipient->execute(['tid' => $threadId, 'uid' => $recipId]);
                }
            }

            // 4. Insert initial message
            $stmtMsg = $this->pdo->prepare("
                INSERT INTO lms_messages (thread_id, sender_id, body, created_at)
                VALUES (:tid, :sender, :body, NOW())
            ");
            $stmtMsg->execute([
                'tid' => $threadId,
                'sender' => $creatorUserId,
                'body' => $body
            ]);

            $this->pdo->commit();
            return $threadId;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Send a reply in an existing thread.
     */
    public function replyThread(int $threadId, int $senderUserId, string $body): int
    {
        $body = trim($body);
        if (empty($body)) {
            throw new Exception("Message content cannot be empty.");
        }

        if (!$this->isParticipant($threadId, $senderUserId)) {
            throw new Exception("Unauthorized to post in this conversation thread.");
        }

        $this->pdo->beginTransaction();
        try {
            // 1. Insert message
            $stmt = $this->pdo->prepare("
                INSERT INTO lms_messages (thread_id, sender_id, body, created_at)
                VALUES (:tid, :sender, :body, NOW())
            ");
            $stmt->execute([
                'tid' => $threadId,
                'sender' => $senderUserId,
                'body' => $body
            ]);
            $messageId = (int)$this->pdo->lastInsertId();

            // 2. Update thread's last_message_at
            $stmtThread = $this->pdo->prepare("
                UPDATE lms_threads 
                SET last_message_at = NOW() 
                WHERE id = :tid
            ");
            $stmtThread->execute(['tid' => $threadId]);

            // 3. Update sender's last_read_at
            $this->markAsRead($threadId, $senderUserId);

            $this->pdo->commit();
            return $messageId;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get all enrolled students in courses taught by the faculty member.
     */
    public function getFacultyContacts(int $facultyUserId): array
    {
        $lmsService = new LmsService();
        $facultyCourses = $lmsService->getFacultyCourses($facultyUserId);

        $contacts = [];
        $seen = [];

        foreach ($facultyCourses as $course) {
            $courseId = (int)$course['id'];
            $roster = $lmsService->getCourseRoster($courseId);

            foreach ($roster as $student) {
                $studentId = (int)$student['id'];
                $key = "{$studentId}_{$courseId}";
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $contacts[] = [
                        'user_id' => $studentId,
                        'name' => trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')),
                        'email' => $student['email'] ?? '',
                        'student_number' => $student['student_number'] ?? '',
                        'course_id' => $courseId,
                        'course_code' => $course['subject_code'] ?? '',
                        'course_name' => $course['subject_name'] ?? '',
                        'section_code' => $student['section_code'] ?? ($course['section_code'] ?? '')
                    ];
                }
            }
        }

        return $contacts;
    }

    /**
     * Get all faculty instructors teaching courses the student is enrolled in.
     */
    public function getStudentContacts(int $studentUserId): array
    {
        $lmsService = new LmsService();
        $studentCourses = $lmsService->getStudentCourses($studentUserId);

        $contacts = [];
        $seen = [];

        foreach ($studentCourses as $course) {
            $courseId = (int)($course['lms_course_id'] ?? $course['id']);
            
            // Get instructor user ID from course
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.first_name, u.last_name, u.email
                FROM lms_courses lc
                JOIN users u ON lc.faculty_user_id = u.id
                WHERE lc.id = :cid
            ");
            $stmt->execute(['cid' => $courseId]);
            $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($instructor) {
                $instId = (int)$instructor['id'];
                $key = "{$instId}_{$courseId}";
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $contacts[] = [
                        'user_id' => $instId,
                        'name' => trim($instructor['first_name'] . ' ' . $instructor['last_name']),
                        'email' => $instructor['email'],
                        'course_id' => $courseId,
                        'course_code' => $course['subject_code'] ?? ($course['code'] ?? ''),
                        'course_name' => $course['subject_name'] ?? ($course['name'] ?? ''),
                        'section_code' => $course['section_name'] ?? ''
                    ];
                }
            }
        }

        return $contacts;
    }

    /**
     * Get total unread message count for a user across all active threads.
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(m.id)
            FROM lms_messages m
            JOIN lms_thread_participants tp ON m.thread_id = tp.thread_id AND tp.user_id = :uid
            WHERE m.sender_id != :uid2
              AND (tp.last_read_at IS NULL OR m.created_at > tp.last_read_at)
        ");
        $stmt->execute(['uid' => $userId, 'uid2' => $userId]);
        return (int)$stmt->fetchColumn();
    }
}
