<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsService;
use App\Services\LmsAttendanceService;

class StudentAttendanceController extends BaseController
{
    private LmsService $lmsService;
    private LmsAttendanceService $attendanceService;

    public function __construct()
    {
        $this->lmsService = new LmsService();
        $this->attendanceService = new LmsAttendanceService();
    }

    private function authorizeStudent(Response $response, int $lmsCourseId)
    {
        $userId = $_SESSION['user_id'] ?? 0;
        if (!$this->lmsService->isStudentAuthorizedForCourse($userId, $lmsCourseId)) {
            $response->setStatusCode(403);
            echo "403 Forbidden - You do not have access to this course.";
            exit;
        }
    }

    public function index(Request $request, Response $response, string $courseId)
    {
        $lmsCourseId = (int)$courseId;
        $userId = $_SESSION['user_id'];
        
        $this->authorizeStudent($response, $lmsCourseId);

        $course = $this->lmsService->getCourseDetails($lmsCourseId);
        $history = $this->attendanceService->getStudentAttendanceHistory($lmsCourseId, $userId);

        $stats = [
            'total' => count($history),
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0
        ];

        foreach ($history as $h) {
            $stats[$h['status']]++;
        }

        // Percentage calculation (excused doesn't penalize, late is usually .5 or full present depending on rules. We'll count Present + Excused as full, late as .5 for display purposes, or just present/total).
        // Standard formula: (Present + Excused + (Late * 0.5)) / Total
        $percentage = 100;
        if ($stats['total'] > 0) {
            $earned = $stats['present'] + $stats['excused'] + ($stats['late'] * 0.5);
            $percentage = ($earned / $stats['total']) * 100;
        }

        return $this->render('lms/student/attendance/index', [
            'course' => $course,
            'history' => $history,
            'stats' => $stats,
            'percentage' => $percentage
        ]);
    }
}
