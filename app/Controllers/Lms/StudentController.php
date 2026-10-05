<?php
namespace App\Controllers\Lms;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use Exception;

class StudentController extends BaseController
{
    public function dashboard(Request $request, Response $response)
    {
        $lmsService = new \App\Services\LmsService();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $enrolled_courses = $lmsService->getStudentCourses($userId);
        $upcoming_deadlines = $lmsService->getStudentUpcomingDeadlines($userId, 4);
        $recent_announcements = $lmsService->getStudentAnnouncements($userId, 3);
        $next_event = $lmsService->getStudentNextEvent($userId);
        $streak_count = $lmsService->getStudentStreak($userId);
        
        $pageTitle = 'Dashboard - TTU LMS';

        return $this->render('lms/student/dashboard', get_defined_vars());
    }

    public function course(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        $lmsService = new \App\Services\LmsService();
        $userId = $_SESSION['user_id'] ?? 0;

        $lms_course_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$lms_course_id) {
            $response->redirect(BASE_PATH . "/lms/student/dashboard.php");
            return;
        }

        // 1. Verify Enrollment via Service
        if (!$lmsService->isStudentAuthorizedForCourse($userId, $lms_course_id)) {
            $response->redirect(BASE_PATH . "/lms/student/dashboard.php");
            return;
        }

        // 2. Fetch Course Details
        $course = $lmsService->getCourseDetails($lms_course_id);
        if (!$course) {
            $response->redirect(BASE_PATH . "/lms/student/dashboard.php");
            return;
        }

        // LMS Course Metadata mapping for View
        $course['subject_name'] = $course['subject_name'] ?? 'Unknown Course';
        $course['subject_code'] = $course['subject_code'] ?? 'N/A';
        $course['section_code'] = $course['section_code'] ?? 'Global Section';
        
        $instructor_name = trim(($course['instructor_first'] ?? '') . ' ' . ($course['instructor_last'] ?? ''));
        if (empty($instructor_name)) {
            $instructor_name = 'Instructor TBA';
        }
        $instructor_email = $course['instructor_email'] ?? 'N/A';
        $welcome_message = 'Welcome to ' . htmlspecialchars($course['subject_name']) . '! Your instructor will post materials soon.';

        // 3. Fetch Modules and Materials
        try {
            $modules = $lmsService->getModulesWithMaterialsForCourse($lms_course_id);
        } catch (Exception $e) {
            error_log("LMS Course Modules Error: " . $e->getMessage());
            $modules = [];
        }

        // 3b. Lesson preview kinds and the student's progress (lessons, assignments, quizzes)
        $progressService = new \App\Services\LmsProgressService();
        try {
            $lesson_progress = $progressService->getLessonProgressForCourse((int)$userId, (int)$lms_course_id);
            $course_progress = $progressService->getCourseProgress((int)$userId, (int)$lms_course_id);
        } catch (\Throwable $e) {
            // The lms_material_progress table is missing until the phase 12 migration is run
            error_log("LMS Course Progress Error: " . $e->getMessage());
            $lesson_progress = [];
            $course_progress = null;
        }
        $course_materials = [];
        foreach ($modules as &$module) {
            $module['materials'] = $module['materials'] ?? [];
            foreach ($module['materials'] as &$material) {
                $material['kind'] = \App\Services\LmsService::lessonKind($material);
                $material['progress'] = $lesson_progress[(int)$material['id']] ?? null;
                $course_materials[] = $material;
            }
            unset($material);
        }
        unset($module);

        // 4. Badge Counts for Course Navigation Tabs
        $announcementService = new \App\Services\LmsAnnouncementService();
        $quizService = new \App\Services\LmsQuizService();

        try {
            $assignment_count = count($lmsService->getAssignmentsByCourse($lms_course_id, true));
        } catch (\Throwable $e) {
            $assignment_count = 0;
        }

        try {
            $quiz_count = count($quizService->getQuizzesByCourse($lms_course_id, true));
        } catch (\Throwable $e) {
            $quiz_count = 0;
        }

        try {
            $announcement_count = count($announcementService->getCourseAnnouncements($lms_course_id, true));
        } catch (\Throwable $e) {
            $announcement_count = 0;
        }

        $pageTitle = $course['subject_code'] . ' - TTU LMS';
        $current_page = 'my_courses.php'; // Highlight "My Courses" in sidebar

        return $this->render('lms/student/course', get_defined_vars());
    }

    /**
     * JSON for the lesson preview window: how to show the file, plus its text for
     * Word/PowerPoint/plain-text lessons. Opening a lesson records it as started.
     */
    public function lessonPreview(Request $request, Response $response, string $id)
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $lmsService = new \App\Services\LmsService();
        $material = $lmsService->getMaterial((int)$id);

        if (!$material || !$lmsService->isStudentAuthorizedForCourse($userId, (int)$material['lms_course_id'])) {
            return $response->json(['success' => false, 'error' => 'Lesson not found.'], 404);
        }

        $kind = \App\Services\LmsService::lessonKind($material);
        $path = $lmsService->resolveMaterialPath($material);
        $sections = [];
        if ($path && in_array($kind, ['office', 'text'], true)) {
            try {
                $extractor = new \App\Services\Quiz\CourseContentExtractor();
                $sections = $extractor->previewSections($path, (string)pathinfo($material['file_path'], PATHINFO_EXTENSION));
            } catch (\Throwable $e) {
                error_log('Lesson preview text extraction failed for material ' . $material['id'] . ': ' . $e->getMessage());
            }
        }

        $progress = null;
        try {
            $progress = (new \App\Services\LmsProgressService())->recordLessonProgress($userId, (int)$material['id'], 0);
        } catch (\Throwable $e) {
            error_log('Lesson progress unavailable: ' . $e->getMessage());
        }

        return $response->json([
            'success' => true,
            'id' => (int)$material['id'],
            'title' => $material['file_name'],
            'kind' => $kind,
            'file_available' => $path !== null,
            'file_size' => (int)($material['file_size'] ?? 0),
            'view_url' => BASE_PATH . '/lms/view/material/' . (int)$material['id'],
            'download_url' => BASE_PATH . '/lms/download/material/' . (int)$material['id'],
            'sections' => $sections,
            'progress' => $progress,
        ]);
    }

    /**
     * Saves how far the student has scrolled through a lesson (0-100) and returns the
     * lesson's and the course's updated progress.
     */
    public function lessonProgress(Request $request, Response $response, string $id)
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $lmsService = new \App\Services\LmsService();
        $material = $lmsService->getMaterial((int)$id);

        if (!$material || !$lmsService->isStudentAuthorizedForCourse($userId, (int)$material['lms_course_id'])) {
            return $response->json(['success' => false, 'error' => 'Lesson not found.'], 404);
        }

        $percent = filter_var($request->input('percent'), FILTER_VALIDATE_FLOAT);
        if ($percent === false || $percent === null) {
            return $response->json(['success' => false, 'error' => 'A scroll percentage is required.'], 422);
        }

        try {
            $progressService = new \App\Services\LmsProgressService();
            $lesson = $progressService->recordLessonProgress($userId, (int)$material['id'], (float)$percent);
            $course = $progressService->getCourseProgress($userId, (int)$material['lms_course_id']);
        } catch (\Throwable $e) {
            error_log('Lesson progress save failed: ' . $e->getMessage());
            return $response->json(['success' => false, 'error' => 'Progress could not be saved.'], 500);
        }

        return $response->json(['success' => true, 'lesson' => $lesson, 'course' => $course]);
    }

    public function myCourses(Request $request, Response $response)
    {
        $pdo = Database::getConnection();
        $lmsService = new \App\Services\LmsService();
        $userId = (int)($_SESSION['user_id'] ?? 0);

        $enrolled_courses = $lmsService->getStudentCourses($userId);
        
        // Fetch active academic profile details
        $stmtApp = $pdo->prepare("
            SELECT a.academic_level, a.grade_level, a.school_year, a.semester, a.student_type, a.strand,
                   COALESCE(cs.section_code, ss.section_code) as section_code
            FROM applications a
            LEFT JOIN college_sections cs ON a.section_id = cs.id AND (a.academic_level = 'College' OR a.academic_level IS NULL)
            LEFT JOIN shs_sections ss ON a.section_id = ss.id AND (a.academic_level = 'SHS' OR a.academic_level = 'Senior High School')
            WHERE a.user_id = :uid AND a.status = 'enrolled'
            ORDER BY a.id DESC LIMIT 1
        ");
        $stmtApp->execute(['uid' => $userId]);
        $student_meta = $stmtApp->fetch(PDO::FETCH_ASSOC) ?: [];

        $total_units = array_sum(array_column($enrolled_courses, 'units'));
        $total_courses = count($enrolled_courses);

        $pageTitle = 'My Courses - TTU LMS';

        return $this->render('lms/student/my_courses', get_defined_vars());
    }

    public function profile(Request $request, Response $response)
    {
        $pageTitle = 'My Profile - TTU LMS';
        return $this->render('lms/student/profile', get_defined_vars());
    }

    public function messages(Request $request, Response $response)
    {
        $studentUserId = (int)($_SESSION['user_id'] ?? 0);
        $messageService = new \App\Services\LmsMessageService();

        $threads = $messageService->getUserThreads($studentUserId);
        $contacts = $messageService->getStudentContacts($studentUserId);

        $activeThreadId = (int)$request->input('thread_id');
        $activeThread = null;
        $activeMessages = [];

        if ($activeThreadId > 0) {
            $activeThread = $messageService->getThread($activeThreadId, $studentUserId);
            if ($activeThread) {
                $activeMessages = $messageService->getThreadMessages($activeThreadId, $studentUserId);
            }
        } elseif (!empty($threads)) {
            $activeThreadId = (int)$threads[0]['id'];
            $activeThread = $messageService->getThread($activeThreadId, $studentUserId);
            $activeMessages = $messageService->getThreadMessages($activeThreadId, $studentUserId);
        }

        $pageTitle = 'Messages & Forums - TTU LMS';
        return $this->render('lms/student/messages', [
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
        $studentUserId = (int)($_SESSION['user_id'] ?? 0);
        $threadId = (int)$id;
        $messageService = new \App\Services\LmsMessageService();

        if (!$messageService->isParticipant($threadId, $studentUserId)) {
            $response->setStatusCode(403);
            return $response->json(['success' => false, 'error' => 'Unauthorized access to this conversation.']);
        }

        $thread = $messageService->getThread($threadId, $studentUserId);
        $messages = $messageService->getThreadMessages($threadId, $studentUserId);

        return $response->json([
            'success' => true,
            'thread' => $thread,
            'messages' => $messages
        ]);
    }

    public function sendMessage(Request $request, Response $response)
    {
        $studentUserId = (int)($_SESSION['user_id'] ?? 0);
        $messageService = new \App\Services\LmsMessageService();
        $data = $request->getBody();

        $threadId = (int)($data['thread_id'] ?? 0);
        $body = trim($data['body'] ?? ($data['message'] ?? ''));

        if (empty($body)) {
            if ($request->header('Accept') === 'application/json' || $request->input('ajax')) {
                $response->setStatusCode(422);
                return $response->json(['success' => false, 'error' => 'Message content cannot be empty.']);
            }
            $this->redirect(BASE_PATH . '/lms/student/messages.php');
            return;
        }

        try {
            if ($threadId > 0) {
                $messageId = $messageService->replyThread($threadId, $studentUserId, $body);
            } else {
                $recipientId = (int)($data['recipient_id'] ?? 0);
                if ($recipientId <= 0) {
                    throw new \Exception("Please select an instructor recipient.");
                }
                $subject = trim($data['subject'] ?? 'Course Inquiry');
                $courseId = !empty($data['lms_course_id']) ? (int)$data['lms_course_id'] : null;

                $threadId = $messageService->createThread($studentUserId, [$recipientId], $subject, $body, $courseId);
            }

            if ($request->header('Accept') === 'application/json' || $request->input('ajax')) {
                return $response->json([
                    'success' => true,
                    'thread_id' => $threadId,
                    'redirect_url' => BASE_PATH . '/lms/student/messages.php?thread_id=' . $threadId
                ]);
            }

            $this->redirect(BASE_PATH . '/lms/student/messages.php?thread_id=' . $threadId);
        } catch (\Exception $e) {
            if ($request->header('Accept') === 'application/json' || $request->input('ajax')) {
                $response->setStatusCode(400);
                return $response->json(['success' => false, 'error' => $e->getMessage()]);
            }
            $this->redirect(BASE_PATH . '/lms/student/messages.php');
        }
    }

    public function getContacts(Request $request, Response $response)
    {
        $studentUserId = (int)($_SESSION['user_id'] ?? 0);
        $messageService = new \App\Services\LmsMessageService();
        $contacts = $messageService->getStudentContacts($studentUserId);
        return $response->json(['success' => true, 'contacts' => $contacts]);
    }

    public function legacyAssignments(Request $request, Response $response)
    {
        $courseId = (int)$request->input('course_id');
        $id = (int)$request->input('id');
        if ($courseId > 0 && $id > 0) {
            $this->redirect(BASE_PATH . "/lms/student/course/{$courseId}/assignments/{$id}");
        } elseif ($courseId > 0) {
            $this->redirect(BASE_PATH . "/lms/student/course/{$courseId}/assignments");
        } else {
            $this->redirect(BASE_PATH . "/lms/student/dashboard.php");
        }
    }

    public function legacyQuizzes(Request $request, Response $response)
    {
        $courseId = (int)$request->input('course_id');
        $id = (int)$request->input('id');
        if ($courseId > 0 && $id > 0) {
            $this->redirect(BASE_PATH . "/lms/student/course/{$courseId}/quizzes/{$id}");
        } elseif ($courseId > 0) {
            $this->redirect(BASE_PATH . "/lms/student/course/{$courseId}/quizzes");
        } else {
            $this->redirect(BASE_PATH . "/lms/student/dashboard.php");
        }
    }
}
