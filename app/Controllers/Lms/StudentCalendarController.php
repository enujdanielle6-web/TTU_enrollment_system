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
        $userId = $_SESSION['user_id'] ?? 0;
        
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        
        $events = $this->calendarService->getStudentCalendarEvents($userId, $month, $year);

        return $this->render('lms/student/calendar/index', [
            'month' => $month,
            'year' => $year,
            'events' => $events
        ]);
    }
}
