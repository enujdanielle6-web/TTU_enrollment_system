<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsCalendarService;

class StudentCalendarController extends BaseController
{
    private LmsCalendarService $calendarService;

    public function __construct()
    {
        $this->calendarService = new LmsCalendarService();
    }

    public function index(Request $request, Response $response)
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $lmsService = new \App\Services\LmsService();
        
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        
        // Pad month to 2 digits
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        
        $events = $this->calendarService->getStudentCalendarEvents($userId, $month, $year);
        $upcomingDeadlines = $lmsService->getStudentUpcomingDeadlines($userId, 6);
        $studentCourses = $lmsService->getStudentCourses($userId);
        
        $pageTitle = 'Academic Calendar - TTU LMS';

        return $this->render('lms/student/calendar/index', [
            'pageTitle' => $pageTitle,
            'month' => $month,
            'year' => $year,
            'events' => $events,
            'upcomingDeadlines' => $upcomingDeadlines,
            'studentCourses' => $studentCourses
        ]);
    }
}
