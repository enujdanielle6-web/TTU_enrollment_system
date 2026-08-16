<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class LmsAttendanceService
{
    private PDO $pdo;
    private LmsService $lmsService;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->lmsService = new LmsService();
    }

    public function getCourseSessions(int $lmsCourseId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM lms_attendance_sessions 
            WHERE lms_course_id = :lcid 
            ORDER BY session_date DESC, created_at DESC
        ");
        $stmt->execute(['lcid' => $lmsCourseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSessionDetails(int $sessionId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM lms_attendance_sessions WHERE id = :id");
        $stmt->execute(['id' => $sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        return $session ?: null;
    }

    public function createSession(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO lms_attendance_sessions (lms_course_id, session_date, start_time, end_time, notes)
            VALUES (:course, :date, :start, :end, :notes)
        ");
        $stmt->execute([
            'course' => $data['lms_course_id'],
            'date' => $data['session_date'],
            'start' => !empty($data['start_time']) ? $data['start_time'] : null,
            'end' => !empty($data['end_time']) ? $data['end_time'] : null,
            'notes' => $data['notes'] ?? null
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateSession(int $sessionId, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE lms_attendance_sessions 
            SET session_date = :date, start_time = :start, end_time = :end, notes = :notes
            WHERE id = :id
        ");
        return $stmt->execute([
            'date' => $data['session_date'],
            'start' => !empty($data['start_time']) ? $data['start_time'] : null,
            'end' => !empty($data['end_time']) ? $data['end_time'] : null,
            'notes' => $data['notes'] ?? null,
            'id' => $sessionId
        ]);
    }

    public function saveAttendance(int $sessionId, array $records): bool
    {
        $this->pdo->beginTransaction();
        try {
            // Upsert records (Insert or Update if exists)
            $stmt = $this->pdo->prepare("
                INSERT INTO lms_attendance_records (lms_attendance_session_id, student_id, status, remarks)
                VALUES (:sess, :stu, :status, :remarks)
                ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks)
            ");

            foreach ($records as $studentId => $data) {
                $stmt->execute([
                    'sess' => $sessionId,
                    'stu' => $studentId,
                    'status' => $data['status'] ?? 'present',
                    'remarks' => $data['remarks'] ?? null
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log("Attendance Save Error: " . $e->getMessage());
            return false;
        }
    }

    public function getSessionRecords(int $sessionId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT ar.*, u.first_name, u.last_name, u.student_number
            FROM lms_attendance_records ar
            JOIN users u ON ar.student_id = u.id
            WHERE ar.lms_attendance_session_id = :sid
        ");
        $stmt->execute(['sid' => $sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $records = [];
        foreach ($rows as $r) {
            $records[$r['student_id']] = $r;
        }
        return $records;
    }

    public function getStudentAttendanceHistory(int $lmsCourseId, int $studentId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.session_date, s.start_time, s.end_time, s.notes, ar.status, ar.remarks
            FROM lms_attendance_sessions s
            JOIN lms_attendance_records ar ON s.id = ar.lms_attendance_session_id
            WHERE s.lms_course_id = :lcid AND ar.student_id = :sid
            ORDER BY s.session_date DESC
        ");
        $stmt->execute(['lcid' => $lmsCourseId, 'sid' => $studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
