<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class LmsCalendarService
{
    private PDO $pdo;
    private LmsService $lmsService;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->lmsService = new LmsService();
    }

    public function getStudentCalendarEvents(int $userId, string $month, string $year): array
    {
        $courses = $this->lmsService->getStudentCourses($userId);
        if (empty($courses)) return [];

        $courseIds = array_column($courses, 'lms_course_id');
        return $this->fetchEvents($courseIds, $month, $year, true);
    }

    public function getFacultyCalendarEvents(int $userId, string $month, string $year): array
    {
        $courses = $this->lmsService->getFacultyCourses($userId);
        if (empty($courses)) return [];

        $courseIds = array_column($courses, 'lms_course_id');
        return $this->fetchEvents($courseIds, $month, $year, false);
    }

    private function fetchEvents(array $courseIds, string $month, string $year, bool $publishedOnly): array
    {
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        
        $startDate = "$year-$month-01 00:00:00";
        $endDate = date('Y-m-t 23:59:59', strtotime($startDate));

        $events = [];

        // 1. Fetch Assignments
        $sql = "
            SELECT a.id, a.title, a.due_date, c.subject_id, s.subject_code
            FROM lms_assignments a
            JOIN lms_courses c ON a.lms_course_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            WHERE a.lms_course_id IN ($placeholders)
            AND a.due_date BETWEEN ? AND ?
        ";
        if ($publishedOnly) {
            $sql .= " AND a.status = 'published'";
        }

        $params = array_merge($courseIds, [$startDate, $endDate]);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($assignments as $a) {
            $events[] = [
                'type' => 'assignment',
                'id' => $a['id'],
                'title' => $a['subject_code'] . ' - ' . $a['title'],
                'date' => date('Y-m-d', strtotime($a['due_date'])),
                'time' => date('h:i A', strtotime($a['due_date'])),
                'color' => 'primary'
            ];
        }

        // 2. Fetch Quizzes (End Time)
        $sql = "
            SELECT q.id, q.title, q.end_date as available_until, c.subject_id, s.subject_code
            FROM lms_quizzes q
            JOIN lms_courses c ON q.lms_course_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            WHERE q.lms_course_id IN ($placeholders)
            AND q.end_date BETWEEN ? AND ?
        ";
        if ($publishedOnly) {
            $sql .= " AND q.status = 'published'";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($quizzes as $q) {
            $events[] = [
                'type' => 'quiz',
                'id' => $q['id'],
                'title' => $q['subject_code'] . ' - ' . $q['title'] . ' Due',
                'date' => date('Y-m-d', strtotime($q['available_until'])),
                'time' => date('h:i A', strtotime($q['available_until'])),
                'color' => 'info'
            ];
        }

        // Sort by date
        usort($events, function($a, $b) {
            return strtotime($a['date'] . ' ' . $a['time']) - strtotime($b['date'] . ' ' . $b['time']);
        });

        return $events;
    }
}
