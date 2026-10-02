<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;

class FacultyController extends BaseController
{
    public function dashboard(Request $request, Response $response)
    {
        $lmsService = new \App\Services\LmsService();
        $facultyUserId = (int)($_SESSION['user_id'] ?? $_SESSION['lms_user_id'] ?? 0);
        $facultyName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
        if (empty($facultyName)) {
            $facultyName = 'Professor';
        }

        $faculty_courses = $lmsService->getFacultyCourses($facultyUserId);
        
        $totalCourses = count($faculty_courses);
        $totalStudents = 0;
        foreach ($faculty_courses as $fc) {
            $totalStudents += (int)($fc['enrolled_count'] ?? 0);
        }

        $pendingSubmissionsCount = $lmsService->getFacultyPendingSubmissionsCount($facultyUserId);
        $recentSubmissions = $lmsService->getFacultyRecentSubmissions($facultyUserId, 5);
        $recentAnnouncements = $lmsService->getFacultyRecentAnnouncements($facultyUserId, 5);

        $pageTitle = 'Faculty Dashboard - TTU LMS';

        return $this->render('lms/faculty/dashboard', get_defined_vars());
    }

    public function course(Request $request, Response $response)
    {
        $lmsService = new \App\Services\LmsService();
        $facultyUserId = $_SESSION['user_id'] ?? 0;
        $courseId = (int)$request->input('id');

        if (!$lmsService->isFacultyAuthorizedForCourse($facultyUserId, $courseId)) {
            $response->setStatusCode(403);
            echo "403 Forbidden - You do not have access to this course.";
            exit;
        }

        $course = $lmsService->getCourseDetails($courseId);
        if (!$course) {
            $this->notFound($response, '404 Not Found - The requested course does not exist.');
        }
        $modulesWithMaterials = $lmsService->getModulesWithMaterialsForCourse($courseId);

        $pageTitle = 'Course Management - ' . $course['subject_code'];

        return $this->render('lms/faculty/course', get_defined_vars());
    }

    public function createModule(Request $request, Response $response)
    {
        $lmsService = new \App\Services\LmsService();
        $facultyUserId = $_SESSION['user_id'] ?? 0;
        $data = $request->getBody();
        $courseId = (int)($data['lms_course_id'] ?? 0);

        if (!$lmsService->isFacultyAuthorizedForCourse($facultyUserId, $courseId)) {
            $response->setStatusCode(403);
            echo "403 Forbidden";
            exit;
        }

        $title = $data['title'] ?? 'New Module';
        $orderIndex = (int)($data['order_index'] ?? 1);
        
        $lmsService->createModule($courseId, $title, $orderIndex);
        
        $this->redirect(BASE_PATH . '/lms/faculty/course.php?id=' . $courseId);
    }

    public function uploadMaterial(Request $request, Response $response)
    {
        $lmsService = new \App\Services\LmsService();
        $facultyUserId = $_SESSION['user_id'] ?? 0;
        $data = $request->getBody();
        $courseId = (int)($data['lms_course_id'] ?? 0);
        $moduleId = (int)($data['lms_module_id'] ?? 0);

        if (!$lmsService->isFacultyAuthorizedForCourse($facultyUserId, $courseId)) {
            $response->setStatusCode(403);
            echo "403 Forbidden - You are not authorized to upload materials for this course.";
            exit;
        }

        $module = $lmsService->getModule($moduleId);
        if (!$module || (int)$module['lms_course_id'] !== $courseId) {
            $response->setStatusCode(400);
            echo "400 Bad Request - Module does not belong to this course.";
            exit;
        }

        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
            $rawName = basename($_FILES['material_file']['name']);
            $ext = strtolower(pathinfo($rawName, PATHINFO_EXTENSION));
            $forbiddenExts = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'exe', 'sh', 'bat', 'cmd', 'js', 'html', 'htm', 'pl', 'cgi'];

            if (in_array($ext, $forbiddenExts, true)) {
                $response->setStatusCode(400);
                echo "400 Bad Request - Executable or script file types are prohibited.";
                exit;
            }

            $uploadDir = dirname(__DIR__, 3) . '/storage/uploads/lms/materials/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = 'mat_' . $courseId . '_' . $moduleId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['material_file']['tmp_name'], $targetPath)) {
                $mimeType = mime_content_type($targetPath) ?: ($_FILES['material_file']['type'] ?? 'application/octet-stream');
                $fileSize = filesize($targetPath) ?: (int)($_FILES['material_file']['size'] ?? 0);

                $materialData = [
                    'lms_module_id' => $moduleId,
                    'title' => !empty(trim($data['title'] ?? '')) ? trim($data['title']) : $rawName,
                    'file_path' => $fileName,
                    'file_type' => $mimeType,
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                    'status' => 'published'
                ];
                $lmsService->createMaterial($materialData);
            }
        }
        
        $this->redirect(BASE_PATH . '/lms/faculty/course.php?id=' . $courseId);
    }

    public function updateModule(Request $request, Response $response)
    {
        $lmsService = new \App\Services\LmsService();
        $facultyUserId = $_SESSION['user_id'] ?? 0;
        $data = $request->getBody();
        $courseId = (int)($data['lms_course_id'] ?? 0);
        $moduleId = (int)($data['lms_module_id'] ?? 0);

        if (!$lmsService->isFacultyAuthorizedForCourse($facultyUserId, $courseId)) {
            $response->setStatusCode(403);
            echo "403 Forbidden";
            exit;
        }

        $module = $lmsService->getModule($moduleId);
        if (!$module || (int)$module['lms_course_id'] !== $courseId) {
            $response->setStatusCode(400);
            echo "400 Bad Request - Module not found in this course.";
            exit;
        }

        $title = !empty(trim($data['title'] ?? '')) ? trim($data['title']) : $module['title'];
        $orderIndex = isset($data['order_index']) ? (int)$data['order_index'] : (int)$module['display_order'];

        $lmsService->updateModule($moduleId, $title, $orderIndex);
        $this->redirect(BASE_PATH . '/lms/faculty/course.php?id=' . $courseId);
    }

    public function deleteModule(Request $request, Response $response)
    {
        $lmsService = new \App\Services\LmsService();
        $facultyUserId = $_SESSION['user_id'] ?? 0;
        $data = $request->getBody();
        $courseId = (int)($data['lms_course_id'] ?? 0);
        $moduleId = (int)($data['lms_module_id'] ?? 0);

        if (!$lmsService->isFacultyAuthorizedForCourse($facultyUserId, $courseId)) {
            $response->setStatusCode(403);
            echo "403 Forbidden";
            exit;
        }

        $module = $lmsService->getModule($moduleId);
        if (!$module || (int)$module['lms_course_id'] !== $courseId) {
            $response->setStatusCode(400);
            echo "400 Bad Request - Module not found in this course.";
            exit;
        }

        $lmsService->deleteModule($moduleId);
        $this->redirect(BASE_PATH . '/lms/faculty/course.php?id=' . $courseId);
    }

    public function deleteMaterial(Request $request, Response $response)
    {
        $lmsService = new \App\Services\LmsService();
        $facultyUserId = $_SESSION['user_id'] ?? 0;
        $data = $request->getBody();
        $courseId = (int)($data['lms_course_id'] ?? 0);
        $materialId = (int)($data['lms_material_id'] ?? 0);

        if (!$lmsService->isFacultyAuthorizedForCourse($facultyUserId, $courseId)) {
            $response->setStatusCode(403);
            echo "403 Forbidden";
            exit;
        }

        $material = $lmsService->getMaterial($materialId);
        if (!$material || (int)$material['lms_course_id'] !== $courseId) {
            $response->setStatusCode(400);
            echo "400 Bad Request - Material not found in this course.";
            exit;
        }

        $lmsService->deleteMaterial($materialId);
        $this->redirect(BASE_PATH . '/lms/faculty/course.php?id=' . $courseId);
    }

    public function roster(Request $request, Response $response, string $courseId)
    {
        $lmsService = new \App\Services\LmsService();
        $facultyUserId = $_SESSION['user_id'] ?? 0;
        $lmsCourseId = (int)$courseId;

        if (!$lmsService->isFacultyAuthorizedForCourse($facultyUserId, $lmsCourseId)) {
            $response->setStatusCode(403);
            echo "403 Forbidden - You do not have access to this course.";
            exit;
        }

        $course = $lmsService->getCourseDetails($lmsCourseId);
        $students = $lmsService->getCourseRoster($lmsCourseId);
        $pageTitle = 'Student Roster - ' . ($course['subject_code'] ?? 'Course');

        return $this->render('lms/faculty/roster/index', [
            'course' => $course,
            'students' => $students,
            'pageTitle' => $pageTitle
        ]);
    }

    public function profile(Request $request, Response $response)
    {
        $pageTitle = 'My Profile - TTU LMS';
        return $this->render('lms/faculty/profile', get_defined_vars());
    }

    public function messages(Request $request, Response $response)
    {
        $facultyUserId = (int)($_SESSION['user_id'] ?? 0);
        $messageService = new \App\Services\LmsMessageService();

        $threads = $messageService->getUserThreads($facultyUserId);
        $contacts = $messageService->getFacultyContacts($facultyUserId);

        $activeThreadId = (int)$request->input('thread_id');
        $activeThread = null;
        $activeMessages = [];

        if ($activeThreadId > 0) {
            $activeThread = $messageService->getThread($activeThreadId, $facultyUserId);
            if ($activeThread) {
                $activeMessages = $messageService->getThreadMessages($activeThreadId, $facultyUserId);
            }
        } elseif (!empty($threads)) {
            $activeThreadId = (int)$threads[0]['id'];
            $activeThread = $messageService->getThread($activeThreadId, $facultyUserId);
            $activeMessages = $messageService->getThreadMessages($activeThreadId, $facultyUserId);
        }

        $pageTitle = 'Messages & Forums - TTU LMS';
        return $this->render('lms/faculty/messages', [
            'pageTitle' => $pageTitle,
            'threads' => $threads,
            'contacts' => $contacts,
            'activeThread' => $activeThread,
            'activeMessages' => $activeMessages,
            'activeThreadId' => $activeThreadId
        ]);
    }

    public function getThreadMessages(Request $request, Response $response, string $id)
    {
        $facultyUserId = (int)($_SESSION['user_id'] ?? 0);
        $threadId = (int)$id;
        $messageService = new \App\Services\LmsMessageService();

        if (!$messageService->isParticipant($threadId, $facultyUserId)) {
            $response->setStatusCode(403);
            return $response->json(['success' => false, 'error' => 'Unauthorized access to this conversation.']);
        }

        $thread = $messageService->getThread($threadId, $facultyUserId);
        $messages = $messageService->getThreadMessages($threadId, $facultyUserId);

        return $response->json([
            'success' => true,
            'thread' => $thread,
            'messages' => $messages
        ]);
    }

    public function sendMessage(Request $request, Response $response)
    {
        $facultyUserId = (int)($_SESSION['user_id'] ?? 0);
        $messageService = new \App\Services\LmsMessageService();
        $data = $request->getBody();

        $threadId = (int)($data['thread_id'] ?? 0);
        $body = trim($data['body'] ?? ($data['message'] ?? ''));

        if (empty($body)) {
            if ($request->header('Accept') === 'application/json' || $request->input('ajax')) {
                $response->setStatusCode(422);
                return $response->json(['success' => false, 'error' => 'Message content cannot be empty.']);
            }
            $this->redirect(BASE_PATH . '/lms/faculty/messages.php');
            return;
        }

        try {
            if ($threadId > 0) {
                $messageId = $messageService->replyThread($threadId, $facultyUserId, $body);
            } else {
                $recipientId = (int)($data['recipient_id'] ?? 0);
                if ($recipientId <= 0) {
                    throw new \Exception("Please select a valid student recipient.");
                }
                $subject = trim($data['subject'] ?? 'Academic Consultation');
                $courseId = !empty($data['lms_course_id']) ? (int)$data['lms_course_id'] : null;

                $threadId = $messageService->createThread($facultyUserId, [$recipientId], $subject, $body, $courseId);
            }

            if ($request->header('Accept') === 'application/json' || $request->input('ajax')) {
                return $response->json([
                    'success' => true,
                    'thread_id' => $threadId,
                    'redirect_url' => BASE_PATH . '/lms/faculty/messages.php?thread_id=' . $threadId
                ]);
            }

            $this->redirect(BASE_PATH . '/lms/faculty/messages.php?thread_id=' . $threadId);
        } catch (\Exception $e) {
            if ($request->header('Accept') === 'application/json' || $request->input('ajax')) {
                $response->setStatusCode(400);
                return $response->json(['success' => false, 'error' => $e->getMessage()]);
            }
            $this->redirect(BASE_PATH . '/lms/faculty/messages.php');
        }
    }

    public function getContacts(Request $request, Response $response)
    {
        $facultyUserId = (int)($_SESSION['user_id'] ?? 0);
        $messageService = new \App\Services\LmsMessageService();
        $contacts = $messageService->getFacultyContacts($facultyUserId);
        return $response->json(['success' => true, 'contacts' => $contacts]);
    }

    public function legacyAssignments(Request $request, Response $response)
    {
        $courseId = (int)$request->input('course_id');
        if ($courseId > 0) {
            $this->redirect(BASE_PATH . "/lms/faculty/course/{$courseId}/assignments");
        } else {
            $this->redirect(BASE_PATH . "/lms/faculty/dashboard.php");
        }
    }

    public function legacyQuizzes(Request $request, Response $response)
    {
        $courseId = (int)$request->input('course_id');
        if ($courseId > 0) {
            $this->redirect(BASE_PATH . "/lms/faculty/course/{$courseId}/quizzes");
        } else {
            $this->redirect(BASE_PATH . "/lms/faculty/dashboard.php");
        }
    }
}
