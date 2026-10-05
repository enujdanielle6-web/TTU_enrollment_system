<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\LmsNotificationService;

/**
 * Marks LMS bell notifications as read (student and faculty portals).
 */
class LmsNotificationController extends BaseController
{
    public function studentRead(Request $request, Response $response)
    {
        return $this->markRead($request, $response, 'student');
    }

    public function facultyRead(Request $request, Response $response)
    {
        return $this->markRead($request, $response, 'faculty');
    }

    public function studentReadAll(Request $request, Response $response)
    {
        return $this->markAllRead($response, 'student');
    }

    public function facultyReadAll(Request $request, Response $response)
    {
        return $this->markAllRead($response, 'faculty');
    }

    private function markRead(Request $request, Response $response, string $role)
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $type = (string)$request->input('type');
        $itemId = (int)$request->input('id');

        try {
            $service = new LmsNotificationService();
            if ($itemId <= 0 || !$service->markRead($userId, $role, $type, $itemId)) {
                return $response->json(['success' => false, 'error' => 'Notification not found.'], 404);
            }
            return $response->json(['success' => true, 'unread' => $this->unreadCount($service, $userId, $role)]);
        } catch (\Throwable $e) {
            error_log('Notification read failed: ' . $e->getMessage());
            return $response->json(['success' => false, 'error' => 'Could not save.'], 500);
        }
    }

    private function markAllRead(Response $response, string $role)
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        try {
            $service = new LmsNotificationService();
            $service->markAllRead($userId, $role);
            return $response->json(['success' => true, 'unread' => $this->unreadCount($service, $userId, $role)]);
        } catch (\Throwable $e) {
            error_log('Notification read-all failed: ' . $e->getMessage());
            return $response->json(['success' => false, 'error' => 'Could not save.'], 500);
        }
    }

    private function unreadCount(LmsNotificationService $service, int $userId, string $role): int
    {
        $feed = $role === 'faculty' ? $service->getFacultyFeed($userId) : $service->getStudentFeed($userId);
        return $feed['unread'];
    }
}
