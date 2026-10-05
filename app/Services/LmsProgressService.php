<?php
namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Student progress in an LMS course.
 *
 * A course's items are its lesson files (lms_materials), published assignments and
 * published quizzes. An item is finished when:
 *   - lesson:     the student scrolled to the end of it in the preview window
 *                 (lms_material_progress.completed_at is set), or downloaded a file
 *                 type that has no preview
 *   - assignment: the student has a submission for it
 *   - quiz:       the student has a submitted or graded attempt
 *
 * The totals are counted on every request, so adding a lesson lowers the percentage.
 */
class LmsProgressService
{
    /** Scroll position (percent of the lesson) at which a lesson counts as finished. */
    public const COMPLETE_AT_PERCENT = 95.0;

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Records how far a student has scrolled through a lesson. The stored value only
     * ever grows, and completed_at is set the first time it reaches COMPLETE_AT_PERCENT.
     *
     * @return array{percent: float, completed: bool}
     */
    public function recordLessonProgress(int $studentId, int $materialId, float $percent): array
    {
        $percent = round(max(0.0, min(100.0, $percent)), 2);

        // Decided here rather than in SQL: emulated prepares bind numbers as strings,
        // and MySQL can then compare them as text ('100.00' < '95').
        $done = $percent >= self::COMPLETE_AT_PERCENT ? 1 : 0;

        $stmt = $this->pdo->prepare("
            INSERT INTO lms_material_progress
                (lms_material_id, student_id, max_scroll_percent, first_opened_at, last_opened_at, completed_at)
            VALUES
                (:mid, :sid, :pct, NOW(), NOW(), IF(:done = 1, NOW(), NULL))
            ON DUPLICATE KEY UPDATE
                completed_at = COALESCE(completed_at, IF(:done2 = 1, NOW(), NULL)),
                max_scroll_percent = GREATEST(max_scroll_percent, CAST(:pct2 AS DECIMAL(5,2))),
                last_opened_at = NOW()
        ");
        $stmt->bindValue('mid', $materialId, PDO::PARAM_INT);
        $stmt->bindValue('sid', $studentId, PDO::PARAM_INT);
        $stmt->bindValue('pct', number_format($percent, 2, '.', ''));
        $stmt->bindValue('done', $done, PDO::PARAM_INT);
        $stmt->bindValue('done2', $done, PDO::PARAM_INT);
        $stmt->bindValue('pct2', number_format($percent, 2, '.', ''));
        $stmt->execute();

        $row = $this->pdo->prepare("
            SELECT max_scroll_percent, completed_at
            FROM lms_material_progress
            WHERE lms_material_id = :mid AND student_id = :sid
        ");
        $row->execute(['mid' => $materialId, 'sid' => $studentId]);
        $saved = $row->fetch(PDO::FETCH_ASSOC) ?: ['max_scroll_percent' => $percent, 'completed_at' => null];

        return [
            'percent' => (float)$saved['max_scroll_percent'],
            'completed' => $saved['completed_at'] !== null,
        ];
    }

    /**
     * Lesson progress for every material in a course, keyed by material id.
     *
     * @return array<int, array{percent: float, completed: bool}>
     */
    public function getLessonProgressForCourse(int $studentId, int $lmsCourseId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT mp.lms_material_id, mp.max_scroll_percent, mp.completed_at
            FROM lms_material_progress mp
            JOIN lms_materials mat ON mat.id = mp.lms_material_id
            JOIN lms_modules md ON md.id = mat.lms_module_id
            WHERE mp.student_id = :sid AND md.lms_course_id = :lcid
        ");
        $stmt->execute(['sid' => $studentId, 'lcid' => $lmsCourseId]);

        $progress = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $progress[(int)$row['lms_material_id']] = [
                'percent' => (float)$row['max_scroll_percent'],
                'completed' => $row['completed_at'] !== null,
            ];
        }
        return $progress;
    }

    /**
     * Overall course progress for one student.
     *
     * @return array{percent: int, done: int, total: int,
     *               lessons: array{done: int, total: int},
     *               assignments: array{done: int, total: int},
     *               quizzes: array{done: int, total: int}}
     */
    public function getCourseProgress(int $studentId, int $lmsCourseId): array
    {
        $lessons = $this->countPair("
            SELECT COUNT(*) AS total,
                   COALESCE(SUM(CASE WHEN mp.completed_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS done
            FROM lms_materials mat
            JOIN lms_modules md ON md.id = mat.lms_module_id
            LEFT JOIN lms_material_progress mp
                   ON mp.lms_material_id = mat.id AND mp.student_id = :sid
            WHERE md.lms_course_id = :lcid
        ", $studentId, $lmsCourseId);

        $assignments = $this->countPair("
            SELECT COUNT(*) AS total,
                   COALESCE(SUM(CASE WHEN EXISTS (
                       SELECT 1 FROM lms_submissions sub
                       WHERE sub.assignment_id = a.id AND sub.student_id = :sid
                   ) THEN 1 ELSE 0 END), 0) AS done
            FROM lms_assignments a
            WHERE a.lms_course_id = :lcid AND a.status = 'published'
        ", $studentId, $lmsCourseId);

        $quizzes = $this->countPair("
            SELECT COUNT(*) AS total,
                   COALESCE(SUM(CASE WHEN EXISTS (
                       SELECT 1 FROM lms_quiz_attempts att
                       WHERE att.lms_quiz_id = q.id AND att.student_id = :sid
                         AND att.status IN ('submitted', 'graded')
                   ) THEN 1 ELSE 0 END), 0) AS done
            FROM lms_quizzes q
            WHERE q.lms_course_id = :lcid AND q.status = 'published'
        ", $studentId, $lmsCourseId);

        $done = $lessons['done'] + $assignments['done'] + $quizzes['done'];
        $total = $lessons['total'] + $assignments['total'] + $quizzes['total'];

        return [
            'percent' => $total > 0 ? (int)floor($done * 100 / $total) : 0,
            'done' => $done,
            'total' => $total,
            'lessons' => $lessons,
            'assignments' => $assignments,
            'quizzes' => $quizzes,
        ];
    }

    /** @return array{done: int, total: int} */
    private function countPair(string $sql, int $studentId, int $lmsCourseId): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['sid' => $studentId, 'lcid' => $lmsCourseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['done' => (int)($row['done'] ?? 0), 'total' => (int)($row['total'] ?? 0)];
    }
}
